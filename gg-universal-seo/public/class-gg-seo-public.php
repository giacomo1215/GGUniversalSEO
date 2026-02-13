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
 * into the appropriate filters to override title & description.
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

        // Nothing to override.
        if ( null === $this->seo_title && null === $this->seo_desc ) {
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
    }

    /**
     * All in One SEO overrides.
     */
    private function hook_aioseo(): void {
        if ( null !== $this->seo_title ) {
            add_filter( 'aioseo_title', array( $this, 'override_title' ), 20 );
        }
        if ( null !== $this->seo_desc ) {
            add_filter( 'aioseo_description', array( $this, 'override_description' ), 20 );
        }
    }

    /**
     * Standalone fallback — no SEO plugin active.
     */
    private function hook_fallback(): void {
        if ( null !== $this->seo_title ) {
            add_filter( 'pre_get_document_title', array( $this, 'override_title' ), 20 );
        }
        if ( null !== $this->seo_desc ) {
            add_action( 'wp_head', array( $this, 'render_meta_description' ), 1 );
        }
    }

    /*--------------------------------------------------------------
     * Callbacks
     *------------------------------------------------------------*/

    /**
     * Filter callback: return the overridden SEO title.
     *
     * @param string $original Original title.
     * @return string
     */
    public function override_title( $original = '' ): string {
        return ( null !== $this->seo_title ) ? esc_html( $this->seo_title ) : (string) $original;
    }

    /**
     * Filter callback: return the overridden meta description.
     *
     * @param string $original Original description.
     * @return string
     */
    public function override_description( $original = '' ): string {
        return ( null !== $this->seo_desc ) ? esc_attr( $this->seo_desc ) : (string) $original;
    }

    /**
     * Action callback: output a <meta name="description"> tag.
     * Used only in the standalone fallback mode.
     *
     * @return void
     */
    public function render_meta_description(): void {
        if ( null === $this->seo_desc ) {
            return;
        }

        printf(
            '<meta name="description" content="%s" />' . "\n",
            esc_attr( $this->seo_desc )
        );
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

        $current_locale    = get_locale();
        $supported_locales = Helpers::get_supported_locales();

        // Check if the current locale is in the supported list.
        $locale_codes = array_column( $supported_locales, 'code' );

        if ( ! in_array( $current_locale, $locale_codes, true ) ) {
            return;
        }

        // Fetch meta for this locale.
        $title_key = Helpers::meta_key( $current_locale, 'title' );
        $desc_key  = Helpers::meta_key( $current_locale, 'description' );

        $title = get_post_meta( $post->ID, $title_key, true );
        $desc  = get_post_meta( $post->ID, $desc_key, true );

        if ( is_string( $title ) && '' !== trim( $title ) ) {
            $this->seo_title = trim( $title );
        }

        if ( is_string( $desc ) && '' !== trim( $desc ) ) {
            $this->seo_desc = trim( $desc );
        }
    }
}
