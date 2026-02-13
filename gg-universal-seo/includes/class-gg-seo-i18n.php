<?php
/**
 * Internationalization (i18n) handler.
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
 * Loads the plugin text domain for translations.
 */
final class I18n {

    /**
     * Load the plugin text domain.
     *
     * @return void
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            'gg-universal-seo',
            false,
            dirname( GG_SEO_PLUGIN_BASENAME ) . '/languages/'
        );
    }
}
