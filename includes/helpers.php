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

    /*--------------------------------------------------------------
     * Locale detection (multilingual plugin support)
     *------------------------------------------------------------*/

    /**
     * Detect the current frontend locale.
     *
     * Checks multilingual plugins in order of priority:
     *   1. TranslatePress  ($TRP_LANGUAGE global)
     *   2. Polylang         (pll_current_language)
     *   3. WPML             (ICL_LANGUAGE_CODE + locale mapping)
     *   4. WordPress        (get_locale)
     *
     * @return string WordPress locale code, e.g. 'it_IT'.
     */
    public static function detect_locale(): string {
        // --- TranslatePress ---
        // TP sets the global $TRP_LANGUAGE early in the request with
        // the full WordPress locale code (e.g. 'it_IT').
        global $TRP_LANGUAGE;
        if ( ! empty( $TRP_LANGUAGE ) && is_string( $TRP_LANGUAGE ) ) {
            return $TRP_LANGUAGE;
        }

        // Also check via TP singleton (fallback if global is not set yet).
        if ( class_exists( 'TRP_Translate_Press' ) ) {
            try {
                $trp      = \TRP_Translate_Press::get_trp_instance();
                $settings = $trp->get_component( 'settings' );
                if ( $settings ) {
                    $all = $settings->get_settings();
                    // Try to get language from URL via url_converter.
                    $url_converter = $trp->get_component( 'url_converter' );
                    if ( $url_converter && method_exists( $url_converter, 'get_lang_from_url_string' ) ) {
                        $lang = $url_converter->get_lang_from_url_string(
                            isset( $_SERVER['REQUEST_URI'] ) ? home_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : ''
                        );
                        if ( ! empty( $lang ) ) {
                            return $lang;
                        }
                    }
                    // Fallback to TP default language.
                    if ( ! empty( $all['default-language'] ) ) {
                        return $all['default-language'];
                    }
                }
            } catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
                // TP not fully loaded yet — fall through.
            }
        }

        // --- Polylang ---
        if ( function_exists( 'pll_current_language' ) ) {
            $pll = pll_current_language( 'locale' );
            if ( ! empty( $pll ) && is_string( $pll ) ) {
                return $pll;
            }
        }

        // --- WPML ---
        if ( defined( 'ICL_LANGUAGE_CODE' ) && function_exists( 'apply_filters' ) ) {
            /** @var string $wpml_code */
            $wpml_code    = ICL_LANGUAGE_CODE;
            $wpml_locale  = apply_filters( 'wpml_locale', $wpml_code );

            if ( ! empty( $wpml_locale ) && is_string( $wpml_locale ) ) {
                return $wpml_locale;
            }
        }

        // --- WordPress default ---
        return get_locale();
    }

    /**
     * Get the default (source) language of the site.
     *
     * @return string WordPress locale code.
     */
    public static function get_default_locale(): string {
        // TranslatePress.
        if ( class_exists( 'TRP_Translate_Press' ) ) {
            try {
                $trp      = \TRP_Translate_Press::get_trp_instance();
                $settings = $trp->get_component( 'settings' );
                if ( $settings ) {
                    $all = $settings->get_settings();
                    if ( ! empty( $all['default-language'] ) ) {
                        return $all['default-language'];
                    }
                }
            } catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
                // Fall through.
            }
        }

        // Polylang.
        if ( function_exists( 'pll_default_language' ) ) {
            $pll = pll_default_language( 'locale' );
            if ( ! empty( $pll ) && is_string( $pll ) ) {
                return $pll;
            }
        }

        // WPML.
        if ( function_exists( 'icl_get_default_language' ) ) {
            $code   = icl_get_default_language();
            $locale = apply_filters( 'wpml_locale', $code );
            if ( ! empty( $locale ) && is_string( $locale ) ) {
                return $locale;
            }
        }

        return get_locale();
    }

    /**
     * Check whether the current request is for a non-default language.
     *
     * @return bool
     */
    public static function is_translated_request(): bool {
        $current = self::detect_locale();
        $default = self::get_default_locale();

        return $current !== $default;
    }

    /**
     * Convert a WordPress locale code to an OpenGraph-compatible locale.
     *
     * E.g. 'it_IT' → 'it_IT', 'en_US' → 'en_US',
     *      'it' → 'it_IT' (best-effort mapping).
     *
     * @param string $locale WordPress locale code.
     * @return string OG locale string.
     */
    public static function locale_to_og( string $locale ): string {
        // Already in xx_XX format.
        if ( preg_match( '/^[a-z]{2,3}_[A-Z]{2,3}$/', $locale ) ) {
            return $locale;
        }

        // Short code → best-effort full locale.
        $map = array(
            'en' => 'en_US',
            'it' => 'it_IT',
            'fr' => 'fr_FR',
            'de' => 'de_DE',
            'es' => 'es_ES',
            'pt' => 'pt_PT',
            'nl' => 'nl_NL',
            'ru' => 'ru_RU',
            'ja' => 'ja_JP',
            'ko' => 'ko_KR',
            'zh' => 'zh_CN',
            'ar' => 'ar_SA',
            'hi' => 'hi_IN',
            'pl' => 'pl_PL',
            'sv' => 'sv_SE',
            'da' => 'da_DK',
            'fi' => 'fi_FI',
            'nb' => 'nb_NO',
            'tr' => 'tr_TR',
            'cs' => 'cs_CZ',
            'ro' => 'ro_RO',
            'hu' => 'hu_HU',
            'el' => 'el_GR',
            'he' => 'he_IL',
            'th' => 'th_TH',
            'vi' => 'vi_VN',
            'uk' => 'uk_UA',
            'bg' => 'bg_BG',
            'hr' => 'hr_HR',
            'sk' => 'sk_SK',
            'sl' => 'sl_SI',
            'et' => 'et_EE',
            'lv' => 'lv_LV',
            'lt' => 'lt_LT',
        );

        $short = strtolower( substr( $locale, 0, 2 ) );

        return $map[ $short ] ?? $locale;
    }

    /**
     * Get all published languages from TranslatePress (if active).
     *
     * @return array<int, array{code: string, label: string}> Empty if TP is not active.
     */
    public static function get_translatepress_languages(): array {
        if ( ! class_exists( 'TRP_Translate_Press' ) ) {
            return array();
        }

        try {
            $trp       = \TRP_Translate_Press::get_trp_instance();
            $settings  = $trp->get_component( 'settings' );
            $languages = $trp->get_component( 'languages' );

            if ( ! $settings || ! $languages ) {
                return array();
            }

            $all      = $settings->get_settings();
            $publish  = $all['publish-languages'] ?? array();
            $names    = $languages->get_language_names( $publish );
            $result   = array();

            foreach ( $publish as $code ) {
                $result[] = array(
                    'code'  => $code,
                    'label' => $names[ $code ] ?? $code,
                );
            }

            return $result;
        } catch ( \Throwable $e ) {
            return array();
        }
    }
}
