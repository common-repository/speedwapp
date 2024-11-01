<?php

/*
 * This file is part of the Speedwapp Wordpress plugin.
 *
 * (c) Akambi Fagbohoun <contact@akambi-fagbohoun.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @author     akambi <akambi@speedwapp.com>
 */
class SpeedwappAdmin extends Speedwapp_Module
{
    /**
     * The ID of this plugin.
     *
     * @since    0.9.0
     *
     * @var string The ID of this plugin.
     */
    private $plugin_name;
    /**
     * The version of this plugin.
     *
     * @since    0.9.0
     *
     * @var string The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    0.9.0
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     */
    public function __construct()
    {
    }

    public function initPlugin($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Initializes variables
     *
     * @mvc Controller
    */
    public function init()
    {
        $this->register_hook_callbacks();
    }

    public function register_hook_callbacks()
    {

    }

    /**
     * Enqueue the admin styles
     *
     * @param string $prefix
     * @param bool $force Should we force the enqueue
     *
     * @action admin_enqueue_scripts
     */
    public function enqueue_styles($prefix = '', $force = false)
    {
        if ($force || Util::is_admin_page()) {
            wp_enqueue_style(
                'speedwapp-admin',
                plugin_dir_url(__FILE__) . 'css/speedwapp-admin.css',
                array(),
                $this->version
            );
            do_action('wp_speedwapp_enqueue_admin_styles');
        }
    }

    public function load_editor_enqueue_scripts($hook, $force = false) {
        wp_register_script(
            'load-speedwapp-editor',
            // 'https://sw-localhost/app_dev.php/embed-js/builder/wordpress',
            'https://speedwapp.com/embed-js/builder/wordpress',
            array(),
            SPEEDWAPP_VERSION,
            false
        );

        wp_enqueue_script('load-speedwapp-editor');
    }

    public function editor_fullscreen_enqueue_scripts($hook, $force = false) {
        $url = get_home_url();
        if ( is_multisite() ) {
            $current_site = get_current_site();
            $siteId = $current_site->ID;
        } else {
            $siteId = get_current_blog_id();
        }
        $id = get_the_ID();
        $title = get_the_title($id);
        $speedwapp_api_token = get_option('speedwapp_api_token');

        if ($force || Util::is_admin_page()) {
            //  wp_enqueue_media();

            wp_register_script(
                'speedwapp-editor-full-screen',
                plugin_dir_url(__FILE__) . 'js/speedwapp-editor-full-screen.js',
                array(
                    'wp-auth-check'
                ),
                SPEEDWAPP_VERSION,
                true
            );

            wp_enqueue_script('speedwapp-editor-full-screen');

            $page_json_data = get_post_meta($id, '_speedwapp_json_data', true);

            wp_add_inline_script( 'speedwapp-editor-full-screen', 'var SpeedwappSettings = ' . json_encode( array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'sw_edit_url' => Util::get_edit_post_link($id, ''),
                'wp_edit_url' => get_edit_post_link($id, ''),
                'is_speedwapp_save_post_nonce' => wp_create_nonce( 'Speedwapp_Save_Post' ),
                'fullscreen' => true,
                'is_speedwapp_editor_active' => Util::is_speedwapp_editor_active($id),
                'post_id' => $id,
                'post_title' => $title,
                'post_json_data' => $page_json_data,
                'wpurl' => get_bloginfo('wpurl'),
                'preview_url' => get_preview_post_link(
                    $id,
                    [
                        'preview_nonce' => wp_create_nonce( 'speedwapp_preview_post' ),
                        'speedwapp_preview' => 1
                    ]),
                'url_homepage' => $url,
                'speedwapp_api_token' => $speedwapp_api_token,
                'label_preview' => __( 'Preview Changes', 'speedwapp' ),
                'label_publish' => __( 'Save', 'speedwapp' ),
                'label_back_to_wordpress' => __( 'Back to WordPress', 'speedwapp' ),
            ) ), 'before' );

            wp_register_style(
                'speedwapp-editor-full-screen',
                plugin_dir_url(__FILE__) . 'css/speedwapp-editor-full-screen.css',
                array(
                    'wp-auth-check',
                    'buttons',
                    'media-views'
                ),
                $this->version
            );

            wp_enqueue_style('speedwapp-editor-full-screen');
        }
    }

    /**
     * Enqueue the admin scripts
     *
     * @param string $prefix
     * @param bool $force Should we force the enqueues
     *
     * @action admin_enqueue_scripts
     */
    public function admin_enqueue_scripts($hook, $force = false)
    {
        if ( Util::is_gutenberg_active() ) {
            return;
        }

        $url = get_home_url();
        if ( is_multisite() ) {
            $current_site = get_current_site();
            $siteId = $current_site->ID;
        } else {
            $siteId = get_current_blog_id();
        }
        $id = get_the_ID();
        $title = get_the_title($id);
        $speedwapp_api_token = get_option('speedwapp_api_token');

        if ($force || Util::is_admin_page()) {
            // Media is required for row styles
            //  wp_enqueue_media();

            wp_register_script(
                'speedwapp-classic-editor',
                plugin_dir_url(__FILE__) . 'js/speedwapp-classic-editor.js',
                array(),
                SPEEDWAPP_VERSION,
                true
            );

            wp_enqueue_script('speedwapp-classic-editor');

            wp_add_inline_script( 'speedwapp-classic-editor', 'var SpeedwappClassicEditorSettings = ' . json_encode( array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'widget_model' => "WP_Widget_Calendar",
                'post_id' => $id,
                'post_title' => $title,
                'is_speedwapp_editor_active' => Util::is_speedwapp_editor_active($id),
                'sw_edit_url' => Util::get_edit_post_link($id, ''),
                'preview_url' => get_preview_post_link(
                    $id,
                    [
                        'preview_nonce' => wp_create_nonce( 'speedwapp_preview_post' ),
                        'speedwapp' => 1
                    ]),
                'url_homepage' => $url,
                'speedwapp_api_token' => $speedwapp_api_token
            ) ), 'before' );
        }
    }

    public function enqueue_block_editor_assets() {

        $id = Util::get_the_main_ID();
        $title = get_the_title($id);
        $speedwapp_api_token = get_option('speedwapp_api_token');

        wp_enqueue_script('speedwapp-block-editor',
            plugin_dir_url(__FILE__) . 'js/speedwapp-block-editor.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_add_inline_script( 'speedwapp-block-editor', 'var SpeedwappBlockEditorSettings = ' . json_encode( array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'widget_model' => "WP_Widget_Calendar",
            'post_id' => $id,
            'post_title' => $title,
            'sw_edit_url' => Util::get_edit_post_link($id, ''),
            'is_speedwapp_editor_active' => Util::is_speedwapp_editor_active($id),
            'preview_url' => get_preview_post_link(
                $id,
                [
                    'preview_nonce' => wp_create_nonce( 'speedwapp_preview_post' ),
                    'speedwapp' => 1
                ]),
            'edit_url' => Util::get_edit_post_link($id),
            'speedwapp_api_token' => $speedwapp_api_token
        ) ), 'before' );
    }

    public function enqueue_block_editor_js_templates() {
        $id = Util::get_the_main_ID();

        if ( !Util::is_gutenberg_active() ) {
            return;
        }

        echo self::render_template('switch-block-editor.php', array(
            'postId' => $id,
        ));
    }

    public function add_post_state($states, $post) {
        if ( get_post_status( $post ) === 'trash' ) {
			return $states;
		}

        if ( !Util::is_speedwapp_editor_active($post->ID) ) {
            return $states;
        }

        $states = (array) $states;
		$states['speedwapp-editor-plugin'] = __( 'Speedwapp', 'speedwapp' );

		return $states;
    }

    public function add_edit_with_speedwapp_link($actions, $post) {

        if ( !array_key_exists( 'edit', $actions ) ) {
			return $actions;
		}

        $edit_link = Util::get_edit_post_link($post->ID);

        if ( !$edit_link ) {
			return $actions;
        }

        if (!Util::is_speedwapp_editor_active($post->ID)) {
            return $actions;
        }

        /**
         * Filters the post edit link.
         *
         * @param string $link    The edit link.
         * @param int    $post_id Post ID.
         * @param string $context The link context. If set to 'display' then ampersands
         *                        are encoded.
         */
        $edit_link = apply_filters( 'speedwapp_edit_post_link', $edit_link, $post->ID );

        $edit_with_speedwapp = array(
            'edit-with-speedwapp' => sprintf(
                '<a href="%1$s">%2$s</a>',
                $edit_link,
                __( 'Edit with Speedwapp', 'speedwapp' )
            ),
        );

        // Insert the new "Edit with Speedwapp" action before the Edit action.
        $edit_offset = array_search( 'edit', array_keys( $actions ), true );
		array_splice( $actions, $edit_offset, 0, $edit_with_speedwapp );

		return $actions;
    }

    /**
     * Prepares site to use the plugin during activation
     *
     * @mvc Controller
     *
     * @param bool $network_wide
     */
    public function activate($network_wide)
    {

    }

    /**
     * Rolls back activation procedures when de-activating the plugin
     *
     * @mvc Controller
     */
    public function deactivate()
    {
    }


    /**
     * Executes the logic of upgrading from specific older versions of the plugin to the current version
     *
     * @mvc Model
     *
     * @param string $db_version
     */
    public function upgrade($db_version = 0)
    {
        /*
        if( version_compare( $db_version, 'x.y.z', '<' ) )
        {
            // Do stuff
        }
        */
    }

    /**
     * Checks that the object is in a correct state
     *
     * @mvc Model
     *
     * @param string $property An individual property to check, or 'all' to check all of them
     *
     * @return bool
     */
    protected function is_valid($property = 'all')
    {
        return true;
    }
}
