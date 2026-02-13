<?php
/**
 * Settings page HTML partial.
 *
 * @package GG_Universal_SEO
 */

declare(strict_types=1);

// Abort if called directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <?php settings_errors( 'gg_seo_settings_group' ); ?>

    <form method="post" action="options.php">
        <?php
        settings_fields( 'gg_seo_settings_group' );
        do_settings_sections( 'gg-universal-seo' );
        submit_button( __( 'Save Locales', 'gg-universal-seo' ) );
        ?>
    </form>

    <hr />
    <h2><?php esc_html_e( 'Detected SEO Plugin', 'gg-universal-seo' ); ?></h2>
    <?php
    $detected = \GG_Universal_SEO\Includes\Helpers::detect_seo_plugin();
    $map      = array(
        'yoast'    => 'Yoast SEO',
        'rankmath' => 'RankMath',
        'aioseo'   => 'All in One SEO',
        'none'     => __( 'None (Standalone Fallback)', 'gg-universal-seo' ),
    );
    ?>
    <p>
        <strong><?php echo esc_html( $map[ $detected ] ?? $detected ); ?></strong>
    </p>
</div>
