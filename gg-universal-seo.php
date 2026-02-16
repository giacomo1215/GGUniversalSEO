<?php
/**
 * Plugin Name:       GG Universal SEO
 * Plugin URI:        https://github.com/giacomo1215/gg-universal-seo
 * Description:       A universal SEO translation adapter. Manually input SEO Titles and Descriptions for specific locales, injected on the frontend by overriding the active SEO plugin or as a standalone fallback.
 * Version:           1.1.0
 * Author:            Giacomo Giorgi
 * Author URI:        https://github.com/giacomo1215
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gg-universal-seo
 * Domain Path:       /languages
 * Requires at least: 5.6
 * Requires PHP:      7.4
 *
 * @package GG_Universal_SEO
 */

declare(strict_types=1);

// Abort if called directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*--------------------------------------------------------------
 * Constants
 *------------------------------------------------------------*/
define( 'GG_SEO_VERSION', '1.1.0' );
define( 'GG_SEO_PLUGIN_NAME', 'gg-universal-seo' );
define( 'GG_SEO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GG_SEO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GG_SEO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/*--------------------------------------------------------------
 * Autoloader (simple file‐based, no Composer needed)
 *------------------------------------------------------------*/
require_once GG_SEO_PLUGIN_DIR . 'includes/helpers.php';
require_once GG_SEO_PLUGIN_DIR . 'includes/class-gg-seo-i18n.php';
require_once GG_SEO_PLUGIN_DIR . 'includes/class-gg-seo-loader.php';
require_once GG_SEO_PLUGIN_DIR . 'admin/class-gg-seo-admin.php';
require_once GG_SEO_PLUGIN_DIR . 'public/class-gg-seo-public.php';
require_once GG_SEO_PLUGIN_DIR . 'public/overrides/class-gg-seo-buffer-override.php';

/*--------------------------------------------------------------
 * Activation / Deactivation
 *------------------------------------------------------------*/
register_activation_hook( __FILE__, function (): void {
    // Seed default supported locales if the option does not exist yet.
    if ( false === get_option( 'gg_seo_supported_locales' ) ) {
        $defaults = array(
            array(
                'code'  => 'en_US',
                'label' => 'English',
            ),
        );
        update_option( 'gg_seo_supported_locales', $defaults, true );
    }
});

register_deactivation_hook( __FILE__, function (): void {
    // Nothing destructive on deactivation; cleanup is in uninstall.php.
});

/*--------------------------------------------------------------
 * Bootstrap
 *------------------------------------------------------------*/
/**
 * Starts the plugin.
 *
 * @return void
 */
function gg_seo_run(): void {
    $loader = new \GG_Universal_SEO\Includes\Loader();
    $loader->run();
}

gg_seo_run();
