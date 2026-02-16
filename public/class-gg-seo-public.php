<?php
/**
 * Frontend SEO injection — the "Universal Adapter".
 *
 * @package GG_Universal_SEO
 */

declare(strict_types=1);

namespace GG_Universal_SEO\Frontend;

use GG_Universal_SEO\Includes\Helpers;

// Abort if called directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Detects the current locale and active SEO plugin, then hooks
 * into the appropriate filters to override title, description,
 * Open Graph, Twitter Card, canonical URL, and og:locale.
 */
final class Frontend {

    /**
     * Cached SEO title for the current request.
     *
     * @var string|null
     */
    private ?string $seo_title = null;

    /**
     * Cached meta description for the current request.
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
     * Whether the override values have been resolved for this request.
     *
     * @var bool
     */
    private bool $resolved = false;

    /*--------------------------------------------------------------
     * Entry point (called on the 'wp' action)
     *------------------------------------------------------------*/

    /**
     * Determine whether we have override data for the current
     * locale + post and, if so, register the appropriate hooks.
     *
     * @return void
     */
    public function register_seo_hooks(): void {
        // Only act on singular views with a valid queried object.
        if ( ! is_singular() ) {
            return;
        }

        $this->resolve_meta();

        // Nothing to override at all.
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

        $plugin = Helpers::detect_seo_plugin();

        switch ( $plugin ) {
            case 'yoast':
                $this->hook_yoast();
                break;

            case 'rankmath':
                $this->hook_rankmath();
                break;

            case 'aioseo':
                $this->hook_aioseo();
                break;

            case 'none':
            default:
                $this->hook_fallback();
                break;
        }
    }

    /*--------------------------------------------------------------
     * Per-plugin hook registration
     *------------------------------------------------------------*/

    /**
     * Yoast SEO overrides.
     */
    private function hook_yoast(): void {
        if ( null !== $this->seo_title ) {
            add_filter( 'wpseo_title', array( $this, 'override_title' ), 20 );
        }
        if ( null !== $this->seo_desc ) {
            add_filter( 'wpseo_metadesc', array( $this, 'override_description' ), 20 );
        }
        if ( null !== $this->canonical_url ) {
            add_filter( 'wpseo_canonical', array( $this, 'override_canonical' ), 20 );
        }
        if ( null !== $this->og_title ) {
            add_filter( 'wpseo_opengraph_title', array( $this, 'override_og_title' ), 20 );
        }
        if ( null !== $this->og_desc ) {
            add_filter( 'wpseo_opengraph_desc', array( $this, 'override_og_description' ), 20 );
        }
        if ( null !== $this->og_image ) {
            add_filter( 'wpseo_opengraph_image', array( $this, 'override_og_image' ), 20 );
        }
        if ( null !== $this->current_locale ) {
            add_filter( 'wpseo_locale', array( $this, 'override_locale' ), 20 );
        }
    }

    /**
     * RankMath overrides.
     */
    private function hook_rankmath(): void {
        if ( null !== $this->seo_title ) {
            add_filter( 'rank_math/frontend/title', array( $this, 'override_title' ), 20 );
        }
        if ( null !== $this->seo_desc ) {
            add_filter( 'rank_math/frontend/description', array( $this, 'override_description' ), 20 );
        }
        if ( null !== $this->canonical_url ) {
            add_filter( 'rank_math/frontend/canonical', array( $this, 'override_canonical' ), 20 );
        }
        if ( null !== $this->og_title ) {
            add_filter( 'rank_math/opengraph/facebook/og_title', array( $this, 'override_og_title' ), 20 );
        }
        if ( null !== $this->og_desc ) {
            add_filter( 'rank_math/opengraph/facebook/og_description', array( $this, 'override_og_description' ), 20 );
        }
        if ( null !== $this->current_locale ) {
            add_filter( 'rank_math/opengraph/facebook/og_locale', array( $this, 'override_og_locale' ), 20 );
        }
    }

    /**
     * All in One SEO overrides.
     *
     * Uses very high priority (99999) to ensure the override runs
     * after AIOSEO's own internal processing.
     */
    private function hook_aioseo(): void {
        // --- Title & Description ---
        if ( null !== $this->seo_title ) {
            add_filter( 'aioseo_title', array( $this, 'override_title' ), 99999 );
            add_filter( 'pre_get_document_title', array( $this, 'override_title' ), 99999 );
        }
        if ( null !== $this->seo_desc ) {
            add_filter( 'aioseo_description', array( $this, 'override_description' ), 99999 );
        }

        // --- Open Graph ---
        add_filter( 'aioseo_facebook_tags', array( $this, 'override_aioseo_facebook' ), 99999 );
        add_filter( 'aioseo_twitter_tags', array( $this, 'override_aioseo_twitter' ), 99999 );

        // --- OG Locale ---
        if ( null !== $this->current_locale ) {
            add_filter( 'aioseo_og_locale', array( $this, 'override_og_locale' ), 99999 );
        }

        // --- Canonical URL ---
        if ( null !== $this->canonical_url ) {
            add_filter( 'aioseo_canonical_url', array( $this, 'override_canonical' ), 99999 );
        }

        // --- Schema ---
        add_filter( 'aioseo_schema_output', array( $this, 'override_aioseo_schema' ), 99999 );
    }

    /**
     * Standalone fallback — no SEO plugin active.
     */
    private function hook_fallback(): void {
        if ( null !== $this->seo_title ) {
            add_filter( 'pre_get_document_title', array( $this, 'override_title' ), 20 );
        }
        if ( null !== $this->seo_desc || null !== $this->og_title || null !== $this->og_desc || null !== $this->og_image ) {
            add_action( 'wp_head', array( $this, 'render_fallback_meta' ), 1 );
        }
        if ( null !== $this->canonical_url ) {
            // Remove WP default canonical, add ours.
            remove_action( 'wp_head', 'rel_canonical' );
            add_action( 'wp_head', array( $this, 'render_canonical' ), 1 );
        }
    }

    /*--------------------------------------------------------------
     * Callbacks — simple value overrides
     *------------------------------------------------------------*/

    /**
     * Return the overridden SEO title.
     *
     * @param string $original Original title.
     * @return string
     */
    public function override_title( $original = '' ): string {
        return ( null !== $this->seo_title ) ? esc_html( $this->seo_title ) : (string) $original;
    }

    /**
     * Return the overridden meta description.
     *
     * @param string $original Original description.
     * @return string
     */
    public function override_description( $original = '' ): string {
        return ( null !== $this->seo_desc ) ? esc_attr( $this->seo_desc ) : (string) $original;
    }

    /**
     * Return the overridden OG title.
     *
     * @param string $original Original OG title.
     * @return string
     */
    public function override_og_title( $original = '' ): string {
        return ( null !== $this->og_title ) ? esc_attr( $this->og_title ) : (string) $original;
    }

    /**
     * Return the overridden OG description.
     *
     * @param string $original Original OG description.
     * @return string
     */
    public function override_og_description( $original = '' ): string {
        return ( null !== $this->og_desc ) ? esc_attr( $this->og_desc ) : (string) $original;
    }

    /**
     * Return the overridden OG image URL.
     *
     * @param string $original Original OG image.
     * @return string
     */
    public function override_og_image( $original = '' ): string {
        return ( null !== $this->og_image ) ? esc_url( $this->og_image ) : (string) $original;
    }

    /**
     * Return the overridden canonical URL.
     *
     * @param string $original Original canonical URL.
     * @return string
     */
    public function override_canonical( $original = '' ): string {
        return ( null !== $this->canonical_url ) ? esc_url( $this->canonical_url ) : (string) $original;
    }

    /**
     * Return the OG locale for the current language.
     *
     * @param string $original Original locale.
     * @return string
     */
    public function override_og_locale( $original = '' ): string {
        if ( null !== $this->current_locale ) {
            return Helpers::locale_to_og( $this->current_locale );
        }
        return (string) $original;
    }

    /**
     * Override Yoast locale (same as og locale but uses wpseo_locale filter).
     *
     * @param string $original Original locale.
     * @return string
     */
    public function override_locale( $original = '' ): string {
        return $this->override_og_locale( $original );
    }

    /*--------------------------------------------------------------
     * Callbacks — AIOSEO array-based overrides
     *------------------------------------------------------------*/

    /**
     * Override AIOSEO Facebook/OG meta tags array.
     *
     * @param array<string, string> $tags Associative array of OG tags.
     * @return array<string, string>
     */
    public function override_aioseo_facebook( $tags ): array {
        if ( ! is_array( $tags ) ) {
            $tags = array();
        }

        if ( null !== $this->og_title ) {
            $tags['og:title'] = esc_attr( $this->og_title );
        }

        if ( null !== $this->og_desc ) {
            $tags['og:description'] = esc_attr( $this->og_desc );
        }

        if ( null !== $this->og_image ) {
            $tags['og:image']    = esc_url( $this->og_image );
            // Remove width/height since we don't know the new image dimensions.
            $tags['og:image:width']  = '';
            $tags['og:image:height'] = '';
        }

        if ( null !== $this->current_locale ) {
            $tags['og:locale'] = Helpers::locale_to_og( $this->current_locale );
        }

        if ( null !== $this->canonical_url ) {
            $tags['og:url'] = esc_url( $this->canonical_url );
        }

        return $tags;
    }

    /**
     * Override AIOSEO Twitter Card meta tags array.
     *
     * @param array<string, string> $tags Associative array of Twitter tags.
     * @return array<string, string>
     */
    public function override_aioseo_twitter( $tags ): array {
        if ( ! is_array( $tags ) ) {
            $tags = array();
        }

        // Twitter title: use og_title (falls back to seo_title in resolve_meta).
        if ( null !== $this->og_title ) {
            $tags['twitter:title'] = esc_attr( $this->og_title );
        }

        // Twitter description: use og_desc (falls back to seo_desc).
        if ( null !== $this->og_desc ) {
            $tags['twitter:description'] = esc_attr( $this->og_desc );
        }

        if ( null !== $this->og_image ) {
            $tags['twitter:image'] = esc_url( $this->og_image );
        }

        return $tags;
    }

    /**
     * Override AIOSEO schema output to reflect translated name/description.
     *
     * @param array $schema The schema @graph array.
     * @return array
     */
    public function override_aioseo_schema( $schema ): array {
        if ( ! is_array( $schema ) ) {
            return $schema;
        }

        foreach ( $schema as &$node ) {
            if ( ! is_array( $node ) || ! isset( $node['@type'] ) ) {
                continue;
            }

            $type = is_array( $node['@type'] ) ? $node['@type'] : array( $node['@type'] );

            // Override WebPage / Article name + description.
            if ( array_intersect( $type, array( 'WebPage', 'Article', 'BlogPosting', 'NewsArticle', 'ItemPage', 'CollectionPage' ) ) ) {
                if ( null !== $this->seo_title && isset( $node['name'] ) ) {
                    $node['name'] = $this->seo_title;
                }
                if ( null !== $this->seo_desc && isset( $node['description'] ) ) {
                    $node['description'] = $this->seo_desc;
                }
                if ( null !== $this->current_locale ) {
                    $node['inLanguage'] = str_replace( '_', '-', $this->current_locale );
                }
            }

            // Override BreadcrumbList language.
            if ( in_array( 'BreadcrumbList', $type, true ) && null !== $this->current_locale ) {
                $node['inLanguage'] = str_replace( '_', '-', $this->current_locale );
            }
        }

        return $schema;
    }

    /*--------------------------------------------------------------
     * Callbacks — Fallback rendering
     *------------------------------------------------------------*/

    /**
     * Render standalone meta tags when no SEO plugin is active.
     *
     * @return void
     */
    public function render_fallback_meta(): void {
        if ( null !== $this->seo_desc ) {
            printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $this->seo_desc ) );
        }

        $og_title = $this->og_title ?? $this->seo_title;
        $og_desc  = $this->og_desc ?? $this->seo_desc;

        if ( null !== $og_title ) {
            printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $og_title ) );
        }
        if ( null !== $og_desc ) {
            printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $og_desc ) );
        }
        if ( null !== $this->og_image ) {
            printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $this->og_image ) );
        }
        if ( null !== $this->current_locale ) {
            printf( '<meta property="og:locale" content="%s" />' . "\n", esc_attr( Helpers::locale_to_og( $this->current_locale ) ) );
        }
    }

    /**
     * Render the canonical link tag.
     *
     * @return void
     */
    public function render_canonical(): void {
        if ( null !== $this->canonical_url ) {
            printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $this->canonical_url ) );
        }
    }

    /*--------------------------------------------------------------
     * Data resolution
     *------------------------------------------------------------*/

    /**
     * Resolve the override meta values for the current request.
     *
     * Runs only once per request regardless of how many times
     * it is called.
     *
     * @return void
     */
    private function resolve_meta(): void {
        if ( $this->resolved ) {
            return;
        }

        $this->resolved = true;

        $post = get_queried_object();

        if ( ! $post instanceof \WP_Post ) {
            return;
        }

        $current_locale    = Helpers::detect_locale();
        $supported_locales = Helpers::get_supported_locales();

        // Check if the current locale is in the supported list.
        $locale_codes = array_column( $supported_locales, 'code' );

        if ( ! in_array( $current_locale, $locale_codes, true ) ) {
            return;
        }

        $this->current_locale = $current_locale;

        // --- Fetch meta for this locale ---
        $title = $this->get_meta_string( $post->ID, $current_locale, 'title' );
        $desc  = $this->get_meta_string( $post->ID, $current_locale, 'description' );

        if ( null !== $title ) {
            $this->seo_title = $title;
        }
        if ( null !== $desc ) {
            $this->seo_desc = $desc;
        }

        // OG-specific overrides (fall back to title/desc).
        $og_title = $this->get_meta_string( $post->ID, $current_locale, 'og_title' );
        $og_desc  = $this->get_meta_string( $post->ID, $current_locale, 'og_description' );
        $og_image = $this->get_meta_string( $post->ID, $current_locale, 'og_image' );

        $this->og_title = $og_title ?? $this->seo_title;
        $this->og_desc  = $og_desc  ?? $this->seo_desc;
        $this->og_image = $og_image;

        // Canonical URL override.
        $canon = $this->get_meta_string( $post->ID, $current_locale, 'canonical_url' );
        if ( null !== $canon ) {
            $this->canonical_url = $canon;
        }
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
}
