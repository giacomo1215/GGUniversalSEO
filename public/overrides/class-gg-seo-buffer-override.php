<?php
/**
 * Output-buffer-based SEO override — the "nuclear" approach.
 *
 * Intercepts the fully-rendered HTML and forcefully replaces
 * <title>, <meta description>, Open Graph, Twitter Card,
 * canonical URL, and og:locale tags with the values stored
 * by GG Universal SEO.
 *
 * This guarantees the override works regardless of how the active
 * SEO plugin (AIOSEO, Yoast, RankMath, etc.) renders its output,
 * and regardless of the current language / locale on multilingual
 * sites (WPML, Polylang, TranslatePress, etc.).
 *
 * @package    GG_Universal_SEO
 * @subpackage Frontend/Overrides
 */

declare(strict_types=1);

namespace GG_Universal_SEO\Frontend\Overrides;

use GG_Universal_SEO\Includes\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Captures the entire page output with ob_start() and rewrites
 * SEO meta tags before they reach the browser.
 */
final class BufferOverride {

    /**
     * Cached SEO title override.
     *
     * @var string|null
     */
    private ?string $seo_title = null;

    /**
     * Cached meta description override.
     *
     * @var string|null
     */
    private ?string $seo_desc = null;

    /**
     * Cached OG title (falls back to seo_title).
     *
     * @var string|null
     */
    private ?string $og_title = null;

    /**
     * Cached OG description (falls back to seo_desc).
     *
     * @var string|null
     */
    private ?string $og_desc = null;

    /**
     * Cached OG image URL.
     *
     * @var string|null
     */
    private ?string $og_image = null;

    /**
     * Cached canonical URL override.
     *
     * @var string|null
     */
    private ?string $canonical_url = null;

    /**
     * The detected current locale.
     *
     * @var string|null
     */
    private ?string $current_locale = null;

    /**
     * Whether resolve_meta() has already executed.
     *
     * @var bool
     */
    private bool $resolved = false;

    /*--------------------------------------------------------------
     * Public API — hooked by the Loader
     *------------------------------------------------------------*/

    /**
     * Conditionally start output buffering.
     *
     * Hooked to `template_redirect` at priority 0 so the buffer
     * wraps ALL subsequent plugin output (including AIOSEO which
     * hooks at `wp_head`).
     *
     * @return void
     */
    public function maybe_start_buffer(): void {
        if ( $this->should_skip() ) {
            return;
        }

        $this->resolve_meta();

        if (
            null === $this->seo_title
            && null === $this->seo_desc
            && null === $this->og_title
            && null === $this->og_desc
            && null === $this->og_image
            && null === $this->canonical_url
        ) {
            return;
        }

        // The callback receives the complete HTML when the buffer
        // is flushed (typically by wp_ob_end_flush_all at shutdown).
        ob_start( array( $this, 'process_buffer' ) );
    }

    /**
     * Output-buffer callback — processes the captured HTML.
     *
     * @param  string $html Raw HTML captured by the buffer.
     * @return string       Modified HTML with overridden SEO tags.
     */
    public function process_buffer( string $html ): string {
        // Safety: only modify actual HTML documents.
        if ( '' === $html || false === stripos( $html, '</head>' ) ) {
            return $html;
        }

        return $this->apply_overrides( $html );
    }

    /*--------------------------------------------------------------
     * Internal — HTML rewriting
     *------------------------------------------------------------*/

    /**
     * Apply all tag replacements to the HTML string.
     *
     * @param  string $html Full page HTML.
     * @return string       HTML with overridden SEO tags.
     */
    private function apply_overrides( string $html ): string {
        // --- Title ---
        if ( null !== $this->seo_title ) {
            $html = $this->replace_title_tag( $html );
        }

        // --- OG Title ---
        $effective_og_title = $this->og_title ?? $this->seo_title;
        if ( null !== $effective_og_title ) {
            $html = $this->replace_og_tag( $html, 'og:title', $effective_og_title );
            $html = $this->replace_meta_name_tag( $html, 'twitter:title', $effective_og_title );
        }

        // --- Description ---
        if ( null !== $this->seo_desc ) {
            $html = $this->replace_meta_name_tag( $html, 'description', $this->seo_desc, true );
        }

        // --- OG Description ---
        $effective_og_desc = $this->og_desc ?? $this->seo_desc;
        if ( null !== $effective_og_desc ) {
            $html = $this->replace_og_tag( $html, 'og:description', $effective_og_desc );
            $html = $this->replace_meta_name_tag( $html, 'twitter:description', $effective_og_desc );
        }

        // --- OG Image ---
        if ( null !== $this->og_image ) {
            $html = $this->replace_og_tag( $html, 'og:image', $this->og_image );
            $html = $this->replace_meta_name_tag( $html, 'twitter:image', $this->og_image );
        }

        // --- OG Locale ---
        if ( null !== $this->current_locale ) {
            $og_locale = Helpers::locale_to_og( $this->current_locale );
            $html = $this->replace_og_tag( $html, 'og:locale', $og_locale );

            // Also update the <html lang=""> attribute.
            $html = $this->replace_html_lang( $html );
        }

        // --- Canonical URL ---
        if ( null !== $this->canonical_url ) {
            $html = $this->replace_canonical( $html );
            $html = $this->replace_og_tag( $html, 'og:url', $this->canonical_url );
        }

        return $html;
    }

    /**
     * Replace the contents of the <title> tag.
     *
     * @param  string $html Full page HTML.
     * @return string
     */
    private function replace_title_tag( string $html ): string {
        $safe_title = esc_html( (string) $this->seo_title );

        return (string) preg_replace(
            '/<title\b[^>]*>.*?<\/title>/is',
            '<title>' . $safe_title . '</title>',
            $html,
            1
        );
    }

    /**
     * Replace (or optionally inject) a <meta name="…" content="…"> tag.
     *
     * @param  string $html              Current HTML string.
     * @param  string $meta_name         The `name` attribute value.
     * @param  string $value             New `content` value.
     * @param  bool   $inject_if_missing If true, injects the tag before </head>.
     * @return string
     */
    private function replace_meta_name_tag( string $html, string $meta_name, string $value, bool $inject_if_missing = false ): string {
        $esc_name  = preg_quote( $meta_name, '/' );
        $esc_value = esc_attr( $value );
        $new_tag   = '<meta name="' . esc_attr( $meta_name ) . '" content="' . $esc_value . '" />';

        $pattern = '/<meta\b[^>]*\bname\s*=\s*["\']' . $esc_name . '["\'][^>]*\/?>/i';

        if ( preg_match( $pattern, $html ) ) {
            return (string) preg_replace( $pattern, $new_tag, $html, 1 );
        }

        if ( $inject_if_missing ) {
            return (string) preg_replace(
                '/<\/head>/i',
                $new_tag . "\n</head>",
                $html,
                1
            );
        }

        return $html;
    }

    /**
     * Replace a <meta property="og:…" content="…"> tag.
     *
     * @param  string $html     Current HTML string.
     * @param  string $property The `property` attribute value.
     * @param  string $value    New `content` value.
     * @return string
     */
    private function replace_og_tag( string $html, string $property, string $value ): string {
        $esc_prop  = preg_quote( $property, '/' );
        $esc_value = esc_attr( $value );
        $new_tag   = '<meta property="' . esc_attr( $property ) . '" content="' . $esc_value . '" />';

        $pattern = '/<meta\b[^>]*\bproperty\s*=\s*["\']' . $esc_prop . '["\'][^>]*\/?>/i';

        if ( preg_match( $pattern, $html ) ) {
            return (string) preg_replace( $pattern, $new_tag, $html, 1 );
        }

        return $html;
    }

    /**
     * Replace the <link rel="canonical"> tag.
     *
     * @param  string $html Full page HTML.
     * @return string
     */
    private function replace_canonical( string $html ): string {
        $new_tag = '<link rel="canonical" href="' . esc_url( (string) $this->canonical_url ) . '" />';
        $pattern = '/<link\b[^>]*\brel\s*=\s*["\']canonical["\'][^>]*\/?>/i';

        if ( preg_match( $pattern, $html ) ) {
            return (string) preg_replace( $pattern, $new_tag, $html, 1 );
        }

        return $html;
    }

    /**
     * Replace the <html lang="…"> attribute.
     *
     * @param  string $html Full page HTML.
     * @return string
     */
    private function replace_html_lang( string $html ): string {
        if ( null === $this->current_locale ) {
            return $html;
        }

        $lang_attr = str_replace( '_', '-', $this->current_locale );

        return (string) preg_replace(
            '/(<html\b[^>]*)\blang\s*=\s*["\'][^"\']*["\']/i',
            '${1}lang="' . esc_attr( $lang_attr ) . '"',
            $html,
            1
        );
    }

    /*--------------------------------------------------------------
     * Internal — data resolution
     *------------------------------------------------------------*/

    /**
     * Resolve override values from post meta for the current locale.
     *
     * @return void
     */
    private function resolve_meta(): void {
        if ( $this->resolved ) {
            return;
        }
        $this->resolved = true;

        if ( ! is_singular() ) {
            return;
        }

        $post = get_queried_object();
        if ( ! $post instanceof \WP_Post ) {
            return;
        }

        $current_locale    = Helpers::detect_locale();
        $supported_locales = Helpers::get_supported_locales();
        $locale_codes      = array_column( $supported_locales, 'code' );

        if ( ! in_array( $current_locale, $locale_codes, true ) ) {
            return;
        }

        $this->current_locale = $current_locale;

        // Title & Description.
        $title = $this->get_meta_string( $post->ID, $current_locale, 'title' );
        $desc  = $this->get_meta_string( $post->ID, $current_locale, 'description' );

        if ( null !== $title ) {
            $this->seo_title = $title;
        }
        if ( null !== $desc ) {
            $this->seo_desc = $desc;
        }

        // OG-specific (falls back to title/desc in apply_overrides).
        $this->og_title = $this->get_meta_string( $post->ID, $current_locale, 'og_title' );
        $this->og_desc  = $this->get_meta_string( $post->ID, $current_locale, 'og_description' );
        $this->og_image = $this->get_meta_string( $post->ID, $current_locale, 'og_image' );

        // Canonical URL.
        $this->canonical_url = $this->get_meta_string( $post->ID, $current_locale, 'canonical_url' );
    }

    /**
     * Get a non-empty trimmed post meta string, or null.
     *
     * @param int    $post_id Post ID.
     * @param string $locale  Locale code.
     * @param string $field   Field name.
     * @return string|null
     */
    private function get_meta_string( int $post_id, string $locale, string $field ): ?string {
        $key   = Helpers::meta_key( $locale, $field );
        $value = get_post_meta( $post_id, $key, true );

        if ( is_string( $value ) && '' !== trim( $value ) ) {
            return trim( $value );
        }

        return null;
    }

    /*--------------------------------------------------------------
     * Internal — guard checks
     *------------------------------------------------------------*/

    /**
     * Determine whether we should skip buffering entirely.
     *
     * @return bool
     */
    private function should_skip(): bool {
        if ( is_admin() ) {
            return true;
        }
        if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
            return true;
        }
        if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
            return true;
        }
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return true;
        }
        if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
            return true;
        }
        if ( is_feed() || is_robots() ) {
            return true;
        }

        return false;
    }
}
