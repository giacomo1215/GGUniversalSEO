<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Removes all plugin data from the database.
 *
 * @package GG_Universal_SEO
 */

// If uninstall not called from WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/*--------------------------------------------------------------
 * 1. Remove the settings option.
 *------------------------------------------------------------*/
delete_option( 'gg_seo_supported_locales' );

/*--------------------------------------------------------------
 * 2. Remove all post meta created by this plugin.
 *    Meta keys follow the pattern: _gg_seo_{locale}_{field}
 *------------------------------------------------------------*/
global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
    "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_gg_seo_%'"
);
