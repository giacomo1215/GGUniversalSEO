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

$tp_active = class_exists( 'TRP_Translate_Press' );
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

    <?php if ( $tp_active ) : ?>
    <hr />
    <h2><?php esc_html_e( 'TranslatePress Integration', 'gg-universal-seo' ); ?></h2>
    <p><?php esc_html_e( 'TranslatePress is active. You can automatically import its configured languages as supported locales.', 'gg-universal-seo' ); ?></p>
    <p>
        <button type="button" class="button button-secondary" id="gg-seo-import-tp">
            <?php esc_html_e( 'Import Languages from TranslatePress', 'gg-universal-seo' ); ?>
        </button>
        <span id="gg-seo-import-tp-status" style="margin-left: 10px;"></span>
    </p>
    <script>
    (function() {
        var btn = document.getElementById('gg-seo-import-tp');
        var status = document.getElementById('gg-seo-import-tp-status');
        if (!btn) return;
        btn.addEventListener('click', function() {
            btn.disabled = true;
            status.textContent = '<?php echo esc_js( __( 'Importing…', 'gg-universal-seo' ) ); ?>';
            var data = new FormData();
            data.append('action', 'gg_seo_import_tp_locales');
            data.append('nonce', '<?php echo esc_js( wp_create_nonce( 'gg_seo_import_tp' ) ); ?>');
            fetch(ajaxurl, { method: 'POST', body: data })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        status.textContent = res.data.message;
                        status.style.color = '#00a32a';
                        setTimeout(function() { location.reload(); }, 1200);
                    } else {
                        status.textContent = res.data || '<?php echo esc_js( __( 'Import failed.', 'gg-universal-seo' ) ); ?>';
                        status.style.color = '#d63638';
                    }
                    btn.disabled = false;
                })
                .catch(function() {
                    status.textContent = '<?php echo esc_js( __( 'Network error.', 'gg-universal-seo' ) ); ?>';
                    status.style.color = '#d63638';
                    btn.disabled = false;
                });
        });
    })();
    </script>
    <?php endif; ?>

    <hr />
    <h2><?php esc_html_e( 'Detected Environment', 'gg-universal-seo' ); ?></h2>
    <?php
    $detected = \GG_Universal_SEO\Includes\Helpers::detect_seo_plugin();
    $seo_map  = array(
        'yoast'    => 'Yoast SEO',
        'rankmath' => 'RankMath',
        'aioseo'   => 'All in One SEO',
        'none'     => __( 'None (Standalone Fallback)', 'gg-universal-seo' ),
    );
    ?>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e( 'SEO Plugin', 'gg-universal-seo' ); ?></th>
            <td><strong><?php echo esc_html( $seo_map[ $detected ] ?? $detected ); ?></strong></td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Multilingual Plugin', 'gg-universal-seo' ); ?></th>
            <td>
                <strong>
                <?php
                if ( $tp_active ) {
                    esc_html_e( 'TranslatePress', 'gg-universal-seo' );
                } elseif ( function_exists( 'pll_current_language' ) ) {
                    esc_html_e( 'Polylang', 'gg-universal-seo' );
                } elseif ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
                    esc_html_e( 'WPML', 'gg-universal-seo' );
                } else {
                    esc_html_e( 'None detected', 'gg-universal-seo' );
                }
                ?>
                </strong>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Override Strategy', 'gg-universal-seo' ); ?></th>
            <td>
                <?php
                if ( 'aioseo' === $detected ) {
                    esc_html_e( 'AIOSEO filter hooks (aioseo_title, aioseo_description, aioseo_facebook_tags, aioseo_twitter_tags, aioseo_canonical_url, aioseo_og_locale, aioseo_schema_output) + output buffer fallback', 'gg-universal-seo' );
                } elseif ( 'none' === $detected ) {
                    esc_html_e( 'Standalone: pre_get_document_title + wp_head meta injection + output buffer', 'gg-universal-seo' );
                } else {
                    printf(
                        /* translators: %s: plugin name */
                        esc_html__( '%s filter hooks + output buffer fallback', 'gg-universal-seo' ),
                        esc_html( $seo_map[ $detected ] ?? $detected )
                    );
                }
                ?>
            </td>
        </tr>
    </table>
</div>
