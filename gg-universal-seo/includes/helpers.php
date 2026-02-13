<?php
/**
 * Helper functions for GG Universal SEO.
 *
 * @package GG_Universal_SEO
 */

declare(strict_types=1);

namespace GG_Universal_SEO\Includes;

// Abort if called directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Static helper utilities.
 */
final class Helpers {

    /**
     * Return the list of supported locales stored in wp_options.
     *
     * @return array<int, array{code: string, label: string}>
     */
    public static function get_supported_locales(): array {
        $locales = get_option( 'gg_seo_supported_locales', array() );

        if ( ! is_array( $locales ) ) {
            return array();
        }

        // Ensure every entry has the expected keys.
        return array_values(
            array_filter(
                $locales,
                static function ( $item ): bool {
                    return is_array( $item )
                        && isset( $item['code'], $item['label'] )
                        && '' !== trim( (string) $item['code'] );
                }
            )
        );
    }

    /**
     * Detect the currently active SEO plugin.
     *
     * @return string One of: 'yoast', 'rankmath', 'aioseo', 'none'.
     */
    public static function detect_seo_plugin(): string {
        // Yoast SEO.
        if ( defined( 'WPSEO_VERSION' ) ) {
            return 'yoast';
        }

        // RankMath.
        if ( class_exists( '\\RankMath\\Helper' ) ) {
            return 'rankmath';
        }

        // All in One SEO.
        if ( defined( 'AIOSEO_VERSION' ) || function_exists( 'aioseo' ) ) {
            return 'aioseo';
        }

        return 'none';
    }

    /**
     * Build a meta key for a given locale and field.
     *
     * @param string $locale_code e.g. 'en_US'.
     * @param string $field       'title' or 'description'.
     * @return string
     */
    public static function meta_key( string $locale_code, string $field ): string {
        return '_gg_seo_' . sanitize_key( $locale_code ) . '_' . sanitize_key( $field );
    }

    /**
     * Sanitize a locale code string.
     *
     * @param string $code Raw code input.
     * @return string
     */
    public static function sanitize_locale_code( string $code ): string {
        // Allow only alphanumeric characters, underscores, and hyphens.
        return preg_replace( '/[^a-zA-Z0-9_\-]/', '', $code ) ?? '';
    }
}
