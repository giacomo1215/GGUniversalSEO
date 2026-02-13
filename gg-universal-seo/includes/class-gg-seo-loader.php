<?php
/**
 * The core orchestrator that registers all hooks.
 *
 * @package GG_Universal_SEO
 */

declare(strict_types=1);

namespace GG_Universal_SEO\Includes;

use GG_Universal_SEO\Admin\Admin;
use GG_Universal_SEO\Frontend\Frontend;

// Abort if called directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers all actions and filters for the plugin.
 */
final class Loader {

    /**
     * Registered actions.
     *
     * @var array<int, array{hook: string, component: object, callback: string, priority: int, accepted_args: int}>
     */
    private array $actions = array();

    /**
     * Registered filters.
     *
     * @var array<int, array{hook: string, component: object, callback: string, priority: int, accepted_args: int}>
     */
    private array $filters = array();

    /**
     * Initialize the core plugin modules.
     */
    public function __construct() {
        $this->load_i18n();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /*--------------------------------------------------------------
     * Private bootstrap methods
     *------------------------------------------------------------*/

    /**
     * Register i18n hook.
     */
    private function load_i18n(): void {
        $i18n = new I18n();
        $this->add_action( 'plugins_loaded', $i18n, 'load_textdomain' );
    }

    /**
     * Register all admin-side hooks.
     */
    private function define_admin_hooks(): void {
        // Only instantiate admin when necessary.
        if ( ! is_admin() ) {
            return;
        }

        $admin = new Admin();

        // Enqueue admin assets.
        $this->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
        $this->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );

        // Settings page.
        $this->add_action( 'admin_menu', $admin, 'add_settings_page' );
        $this->add_action( 'admin_init', $admin, 'register_settings' );

        // Meta box.
        $this->add_action( 'add_meta_boxes', $admin, 'register_meta_box' );
        $this->add_action( 'save_post', $admin, 'save_meta_box', 10, 2 );
    }

    /**
     * Register all public-facing hooks.
     */
    private function define_public_hooks(): void {
        // Frontend hooks are registered on the 'wp' action so
        // the queried object is available.
        $frontend = new Frontend();
        $this->add_action( 'wp', $frontend, 'register_seo_hooks' );
    }

    /*--------------------------------------------------------------
     * Hook registration helpers
     *------------------------------------------------------------*/

    /**
     * Add an action to the collection.
     *
     * @param string $hook          WordPress hook name.
     * @param object $component     Object instance.
     * @param string $callback      Method name.
     * @param int    $priority      Priority.
     * @param int    $accepted_args Accepted arguments.
     */
    public function add_action( string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $this->actions[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );
    }

    /**
     * Add a filter to the collection.
     *
     * @param string $hook          WordPress hook name.
     * @param object $component     Object instance.
     * @param string $callback      Method name.
     * @param int    $priority      Priority.
     * @param int    $accepted_args Accepted arguments.
     */
    public function add_filter( string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $this->filters[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );
    }

    /**
     * Execute all registered hooks.
     *
     * @return void
     */
    public function run(): void {
        foreach ( $this->filters as $hook ) {
            add_filter(
                $hook['hook'],
                array( $hook['component'], $hook['callback'] ),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ( $this->actions as $hook ) {
            add_action(
                $hook['hook'],
                array( $hook['component'], $hook['callback'] ),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}
