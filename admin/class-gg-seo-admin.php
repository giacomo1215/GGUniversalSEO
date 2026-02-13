<?php
/**
 * Admin-specific functionality: Settings Page & Meta Boxes.
 *
 * @package GG_Universal_SEO
 */

declare(strict_types=1);

namespace GG_Universal_SEO\Admin;

use GG_Universal_SEO\Includes\Helpers;

// Abort if called directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles the admin Settings page and the per-post SEO Meta Box.
 */
final class Admin {

    /**
     * Option key for supported locales.
     *
     * @var string
     */
    private const OPTION_KEY = 'gg_seo_supported_locales';

    /**
     * Nonce action for the Settings page.
     *
     * @var string
     */
    private const SETTINGS_NONCE = 'gg_seo_settings_nonce';

    /**
     * Nonce action for the Meta Box.
     *
     * @var string
     */
    private const META_BOX_NONCE = 'gg_seo_meta_box_nonce';

    /**
     * Post types that receive the meta box.
     *
     * @var string[]
     */
    private const POST_TYPES = array( 'post', 'page', 'product' );

    /*--------------------------------------------------------------
     * Enqueue Assets
     *------------------------------------------------------------*/

    /**
     * Enqueue admin CSS.
     *
     * @param string $hook_suffix Current admin page.
     * @return void
     */
    public function enqueue_styles( string $hook_suffix ): void {
        // Only load on our settings page and post editors.
        if ( 'settings_page_gg-universal-seo' === $hook_suffix || $this->is_edit_screen( $hook_suffix ) ) {
            wp_enqueue_style(
                'gg-seo-admin',
                GG_SEO_PLUGIN_URL . 'admin/css/gg-seo-admin.css',
                array(),
                GG_SEO_VERSION,
                'all'
            );
        }
    }

    /**
     * Enqueue admin JS.
     *
     * @param string $hook_suffix Current admin page.
     * @return void
     */
    public function enqueue_scripts( string $hook_suffix ): void {
        // Only load the repeater script on the settings page.
        if ( 'settings_page_gg-universal-seo' === $hook_suffix ) {
            wp_enqueue_script(
                'gg-seo-admin-settings',
                GG_SEO_PLUGIN_URL . 'admin/js/gg-seo-admin-settings.js',
                array(),
                GG_SEO_VERSION,
                true
            );
        }
    }

    /**
     * Check if we are on an applicable edit screen.
     *
     * @param string $hook_suffix Current admin page hook.
     * @return bool
     */
    private function is_edit_screen( string $hook_suffix ): bool {
        return in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true );
    }

    /*--------------------------------------------------------------
     * Settings Page
     *------------------------------------------------------------*/

    /**
     * Register the settings page under Settings menu.
     *
     * @return void
     */
    public function add_settings_page(): void {
        add_options_page(
            __( 'GG Universal SEO', 'gg-universal-seo' ),
            __( 'GG Universal SEO', 'gg-universal-seo' ),
            'manage_options',
            'gg-universal-seo',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings, sections, and fields via the Settings API.
     *
     * @return void
     */
    public function register_settings(): void {
        register_setting(
            'gg_seo_settings_group',
            self::OPTION_KEY,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_locales_option' ),
                'default'           => array(),
            )
        );

        add_settings_section(
            'gg_seo_locales_section',
            __( 'Supported Locales', 'gg-universal-seo' ),
            array( $this, 'render_locales_section_description' ),
            'gg-universal-seo'
        );

        add_settings_field(
            'gg_seo_locales_field',
            __( 'Locales', 'gg-universal-seo' ),
            array( $this, 'render_locales_field' ),
            'gg-universal-seo',
            'gg_seo_locales_section'
        );
    }

    /**
     * Sanitize the locales array before saving.
     *
     * @param mixed $input Raw input from the form.
     * @return array<int, array{code: string, label: string}>
     */
    public function sanitize_locales_option( $input ): array {
        $clean = array();

        if ( ! is_array( $input ) ) {
            return $clean;
        }

        foreach ( $input as $entry ) {
            if ( ! is_array( $entry ) ) {
                continue;
            }

            $code  = isset( $entry['code'] )  ? Helpers::sanitize_locale_code( trim( (string) $entry['code'] ) )  : '';
            $label = isset( $entry['label'] ) ? sanitize_text_field( trim( (string) $entry['label'] ) ) : '';

            if ( '' === $code ) {
                continue;
            }

            $clean[] = array(
                'code'  => $code,
                'label' => $label,
            );
        }

        return $clean;
    }

    /**
     * Render the settings page (delegates to the partial).
     *
     * @return void
     */
    public function render_settings_page(): void {
        // Security: current user must have manage_options.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        include GG_SEO_PLUGIN_DIR . 'admin/partials/gg-seo-admin-display.php';
    }

    /**
     * Section description callback.
     *
     * @return void
     */
    public function render_locales_section_description(): void {
        echo '<p>' . esc_html__(
            'Define the locales for which you want to provide custom SEO metadata. Use WordPress locale codes (e.g. en_US, it_IT, fr_FR).',
            'gg-universal-seo'
        ) . '</p>';
    }

    /**
     * Render the repeater field for locales.
     *
     * @return void
     */
    public function render_locales_field(): void {
        $locales = Helpers::get_supported_locales();

        // Ensure at least one empty row.
        if ( empty( $locales ) ) {
            $locales = array( array( 'code' => '', 'label' => '' ) );
        }
        ?>
        <div id="gg-seo-locales-repeater">
            <?php foreach ( $locales as $index => $locale ) : ?>
                <div class="gg-seo-locale-row" data-index="<?php echo (int) $index; ?>">
                    <label class="screen-reader-text"><?php esc_html_e( 'Locale Code', 'gg-universal-seo' ); ?></label>
                    <input
                        type="text"
                        name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo (int) $index; ?>][code]"
                        value="<?php echo esc_attr( $locale['code'] ?? '' ); ?>"
                        placeholder="<?php esc_attr_e( 'Locale Code (e.g. en_US)', 'gg-universal-seo' ); ?>"
                        class="regular-text gg-seo-input-code"
                    />
                    <label class="screen-reader-text"><?php esc_html_e( 'Label', 'gg-universal-seo' ); ?></label>
                    <input
                        type="text"
                        name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo (int) $index; ?>][label]"
                        value="<?php echo esc_attr( $locale['label'] ?? '' ); ?>"
                        placeholder="<?php esc_attr_e( 'Label (e.g. English)', 'gg-universal-seo' ); ?>"
                        class="regular-text gg-seo-input-label"
                    />
                    <button type="button" class="button gg-seo-remove-locale" title="<?php esc_attr_e( 'Remove', 'gg-universal-seo' ); ?>">&times;</button>
                </div>
            <?php endforeach; ?>
        </div>
        <p>
            <button type="button" class="button button-secondary" id="gg-seo-add-locale">
                <?php esc_html_e( '+ Add Locale', 'gg-universal-seo' ); ?>
            </button>
        </p>
        <?php
    }

    /*--------------------------------------------------------------
     * Meta Box
     *------------------------------------------------------------*/

    /**
     * Register the SEO meta box on supported post types.
     *
     * @return void
     */
    public function register_meta_box(): void {
        $post_types = self::POST_TYPES;

        // Also support custom post type 'product' only if it exists.
        if ( ! post_type_exists( 'product' ) ) {
            $post_types = array_diff( $post_types, array( 'product' ) );
        }

        foreach ( $post_types as $post_type ) {
            add_meta_box(
                'gg_seo_meta_box',
                __( 'GG Universal SEO — Locale Overrides', 'gg-universal-seo' ),
                array( $this, 'render_meta_box' ),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Render the meta box HTML.
     *
     * @param \WP_Post $post Current post object.
     * @return void
     */
    public function render_meta_box( \WP_Post $post ): void {
        wp_nonce_field( self::META_BOX_NONCE, 'gg_seo_meta_box_nonce_field' );

        $locales = Helpers::get_supported_locales();

        if ( empty( $locales ) ) {
            echo '<p>' . esc_html__(
                'No locales configured. Please add locales in Settings → GG Universal SEO.',
                'gg-universal-seo'
            ) . '</p>';
            return;
        }

        echo '<div class="gg-seo-meta-box-wrapper">';

        foreach ( $locales as $locale ) {
            $code  = $locale['code'];
            $label = $locale['label'];

            $title_key = Helpers::meta_key( $code, 'title' );
            $desc_key  = Helpers::meta_key( $code, 'description' );

            $title_val = get_post_meta( $post->ID, $title_key, true );
            $desc_val  = get_post_meta( $post->ID, $desc_key, true );

            ?>
            <fieldset class="gg-seo-locale-fieldset">
                <legend>
                    <strong><?php echo esc_html( $label ); ?></strong>
                    <code>(<?php echo esc_html( $code ); ?>)</code>
                </legend>

                <p>
                    <label for="<?php echo esc_attr( $title_key ); ?>">
                        <?php esc_html_e( 'SEO Title', 'gg-universal-seo' ); ?>
                    </label><br />
                    <input
                        type="text"
                        id="<?php echo esc_attr( $title_key ); ?>"
                        name="<?php echo esc_attr( $title_key ); ?>"
                        value="<?php echo esc_attr( is_string( $title_val ) ? $title_val : '' ); ?>"
                        class="large-text"
                    />
                </p>

                <p>
                    <label for="<?php echo esc_attr( $desc_key ); ?>">
                        <?php esc_html_e( 'Meta Description', 'gg-universal-seo' ); ?>
                    </label><br />
                    <textarea
                        id="<?php echo esc_attr( $desc_key ); ?>"
                        name="<?php echo esc_attr( $desc_key ); ?>"
                        rows="3"
                        class="large-text"
                    ><?php echo esc_textarea( is_string( $desc_val ) ? $desc_val : '' ); ?></textarea>
                </p>
            </fieldset>
            <?php
        }

        echo '</div>';
    }

    /**
     * Save meta box data when a post is saved.
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     * @return void
     */
    public function save_meta_box( int $post_id, \WP_Post $post ): void {
        // 1. Nonce verification.
        if (
            ! isset( $_POST['gg_seo_meta_box_nonce_field'] )
            || ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['gg_seo_meta_box_nonce_field'] ) ),
                self::META_BOX_NONCE
            )
        ) {
            return;
        }

        // 2. Capability check.
        $post_type_obj = get_post_type_object( $post->post_type );
        if ( null === $post_type_obj || ! current_user_can( $post_type_obj->cap->edit_post, $post_id ) ) {
            return;
        }

        // 3. Skip auto-saves & revisions.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        // 4. Only process our supported post types.
        if ( ! in_array( $post->post_type, self::POST_TYPES, true ) ) {
            return;
        }

        // 5. Save locale meta.
        $locales = Helpers::get_supported_locales();

        foreach ( $locales as $locale ) {
            $code = $locale['code'];

            foreach ( array( 'title', 'description' ) as $field ) {
                $meta_key = Helpers::meta_key( $code, $field );

                // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked above.
                $raw_value = isset( $_POST[ $meta_key ] ) ? wp_unslash( $_POST[ $meta_key ] ) : '';
                $value     = sanitize_text_field( (string) $raw_value );

                if ( '' !== $value ) {
                    update_post_meta( $post_id, $meta_key, $value );
                } else {
                    delete_post_meta( $post_id, $meta_key );
                }
            }
        }
    }
}
