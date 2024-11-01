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
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      0.9.0
 *
 * @author     akambi <akambi@speedwapp.com>
 */
if (!class_exists('Speedwapp_Manager')) {

    require_once plugin_dir_path(dirname(__FILE__)).'includes/SpeedwappModule.php';

    class Speedwapp_Manager extends Speedwapp_Module
    {
        protected static $readable_properties  = array();    // These should really be constants, but PHP doesn't allow class constants to be arrays
        protected static $writeable_properties = array();
        protected $modules;
        /**
         * The loader that's responsible for maintaining and registering all hooks that power
         * the plugin.
         *
         * @since    0.9.0
         *
         * @var Speedwapp_Loader Maintains and registers all hooks for the plugin.
         */
        protected $loader;

        /**
         * @since    0.9.0
         *
         * @var Speedwapp_Admin
         */
        protected $admin;

        /**
         * The unique identifier of this plugin.
         *
         * @since    0.9.0
         *
         * @var string The string used to uniquely identify this plugin.
         */
        protected $plugin_name;

        /**
         * The current version of the plugin.
         *
         * @since    0.9.0
         *
         * @var string The current version of the plugin.
         */
        protected $version;

        protected $swPublic;

        /**
         * Define the core functionality of the plugin.
         *
         * Set the plugin name and the plugin version that can be used throughout the plugin.
         * Load the dependencies, define the locale, and set the hooks for the admin area and
         * the public-facing side of the site.
         *
         * @since    0.9.0
         */
        public function __construct()
        {
            $this->plugin_name = 'speedwapp';
            $this->version = '1.0.0';

            $this->load_dependencies();
            $this->set_locale();
            $this->register_hook_callbacks();
            $this->swPublic = new Speedwapp_Public($this->get_plugin_name(), $this->get_version(), $this->loader);

            $this->modules = array(
               'Speedwapp_Settings' => Speedwapp_Settings::get_instance(),
                'Speedwapp_Ajax' => Speedwapp_Ajax::get_instance(),
                'Post' => Post::get_instance(),
            );

            foreach ($this->modules as $module) {
                $module->setLoader($this->loader);
                $module->init();
            }
        }

        /**
         * Enable Speedwapp custom meta values for access through the REST API.
         * @link https://developer.wordpress.org/rest-api/extending-the-rest-api/modifying-responses/
        */
        public function register_meta_fields_in_rest_api() {
            register_meta( 'post', '_is_speedwapp_editor_active', array(
                'sanitize_callback' => 'rest_sanitize_boolean',
                'type' => 'boolean',
                'show_in_rest' => true,
                'single' => true,
                'auth_callback' => function () {
                   return current_user_can('edit_posts');
                }
            ) );
        }

        public function init() {

        }

        /**
         * Load the required dependencies for this plugin.
         *
         * Include the following files that make up the plugin:
         *
         * - Speedwapp_Loader. Orchestrates the hooks of the plugin.
         * - Speedwapp_i18n. Defines internationalization functionality.
         * - Speedwapp_Admin. Defines all hooks for the admin area.
         * - Speedwapp_Public. Defines all hooks for the public side of the site.
         *
         * Create an instance of the loader which will be used to register the hooks
         * with WordPress.
         *
         * @since    0.9.0
         */
        private function load_dependencies()
        {
            require_once plugin_dir_path(__FILE__).'SpeedwappLoader.php';
            require_once plugin_dir_path(__FILE__).'SpeedwappI18n.php';
            require_once plugin_dir_path(dirname(__FILE__)).'includes/Util.php';
            require_once plugin_dir_path(dirname(__FILE__)).'modules/SpeedwappSettings.php';
            require_once plugin_dir_path(dirname(__FILE__)).'modules/SpeedwappAjax.php';
            require_once plugin_dir_path(dirname(__FILE__)).'modules/SpeedwappAdmin.php';
            require_once plugin_dir_path(dirname(__FILE__)).'modules/SpeedwappPublic.php';
            require_once plugin_dir_path(dirname(__FILE__)).'modules/Post.php';

            /*
                if (defined('SPEEDWAPP_DEV') && SPEEDWAPP_DEV) {
                    include plugin_dir_path(__FILE__) . 'inc/debug.php';
                }
            */

            $this->loader = new Speedwapp_Loader();
        }

        /**
         * Define the locale for this plugin for internationalization.
         *
         * Uses the Speedwapp_i18n class in order to set the domain and to register the hook
         * with WordPress.
         *
         * @since    0.9.0
         */
        private function set_locale()
        {
            $plugin_i18n = new Speedwapp_i18n();
            $this->loader->add_action('init', $plugin_i18n, 'load_plugin_textdomain');
        }

        /**
         * Clears caches of content generated by caching plugins like WP Super Cache
         *
         * @mvc Model
         */
        protected static function clear_caching_plugins() {
            // WP Super Cache
            if ( function_exists( 'wp_cache_clear_cache' ) ) {
                wp_cache_clear_cache();
            }
            // W3 Total Cache
            if ( class_exists( 'W3_Plugin_TotalCacheAdmin' ) ) {
                $w3_total_cache = w3_instance( 'W3_Plugin_TotalCacheAdmin' );
                if ( method_exists( $w3_total_cache, 'flush_all' ) ) {
                    $w3_total_cache->flush_all();
                }
            }
        }
        /*
         * Instance methods
         */
        /**
         * The code that runs during plugin activation.
         * Prepares sites to use the plugin during single or network-wide activation
         *
         * @mvc Controller
         *
         * @param bool $network_wide
         */
        public function activate( $network_wide ) {
            add_option('speedwapp_initial_version', SPEEDWAPP_VERSION, '', 'no');

            if ( $network_wide && is_multisite() ) {
                $sites = wp_get_sites( array( 'limit' => false ) );
                foreach ( $sites as $site ) {
                    switch_to_blog( $site['blog_id'] );
                    $this->single_activate( $network_wide );
                    restore_current_blog();
                }
            } else {
                $this->single_activate( $network_wide );
            }
        }
        /**
         * Runs activation code on a new WPMS site when it's created
         *
         * @mvc Controller
         *
         * @param int $blog_id
         */
        public function activate_new_site( $blog_id ) {
            switch_to_blog( $blog_id );
            $this->single_activate( true );
            restore_current_blog();
        }
        /**
         * Prepares a single blog to use the plugin
         *
         * @mvc Controller
         *
         * @param bool $network_wide
         */
        protected function single_activate( $network_wide ) {
            foreach ( $this->modules as $module ) {
                $module->activate( $network_wide );
            }
            flush_rewrite_rules();
        }
        /**
         * Rolls back activation procedures when de-activating the plugin
         * The code that runs during plugin deactivation.
         *
         * @mvc Controller
         */


        public function deactivate() {
            foreach ( $this->modules as $module ) {
                $module->deactivate();
            }
            flush_rewrite_rules();
        }

        /**
         * Register callbacks for actions and filters
         *
         * @mvc Controller
         */
        public function register_hook_callbacks() {
            $this->define_admin_hooks();
            $this->define_public_hooks();

            $this->loader->add_action('wpmu_new_blog', $this, 'activate_new_site');
            $this->loader->add_action('init', $this, 'init');
            $this->loader->add_action('rest_api_init', $this, 'register_meta_fields_in_rest_api');
            $this->loader->add_action('init', $this, 'upgrade', 11);
        }

        /**
         * Register all of the hooks related to the admin area functionality
         * of the plugin.
         *
         * @since    0.9.0
         */
        private function define_admin_hooks()
        {
            if (!is_admin()) {
                return;
            }

            $admin = new SpeedwappAdmin();
            $admin->initPlugin($this->get_plugin_name(), $this->get_version());
            $this->admin = $admin;

            $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
            $this->loader->add_action('admin_enqueue_scripts', $admin, 'admin_enqueue_scripts');

            $this->loader->add_action('enqueue_block_editor_assets', $admin, 'enqueue_block_editor_assets');
            $this->loader->add_action('admin_footer', $admin, 'enqueue_block_editor_js_templates');

            $this->loader->add_filter('display_post_states', $admin, 'add_post_state', 11, 2);

            $this->loader->add_filter('post_row_actions', $admin, 'add_edit_with_speedwapp_link', 11, 2);
            $this->loader->add_filter('page_row_actions', $admin, 'add_edit_with_speedwapp_link', 11, 2);

            $this->loader->add_action( 'admin_action_edit_with_speedwapp', $this, 'init_editor' );
            $this->loader->add_action( 'admin_action_new_with_speedwapp', $this, 'init_editor_with_new_post_or_page' );
            $this->loader->add_action( 'admin_action_load_speedwapp_editor', $this, 'load_editor' );

            /*
            * TODO: Activate the following actions
            $this->loader->add_action('admin_init', $admin, 'speedwapp_save_home_page');
            $this->loader->add_action('after_switch_theme', $admin, 'speedwapp_update_home_on_theme_change');
            $this->loader->add_action('plugins_loaded', $admin, 'speedwapp_init');
            $this->loader->add_action('plugin_action_links_'.plugin_basename(__FILE__), $admin, 'speedwapp_plugin_action_links');
            */
        }

        public function init_editor_with_new_post_or_page() {
            check_admin_referer( 'new_with_speedwapp', 'new_nonce' );

            $post_type = 'page';
            if (array_key_exists('post_type', $_GET) && $_GET['post_type'] ) {
                $post_type = sanitize_text_field( $_GET['post_type'] );
            }

            if ( ! Util::is_current_user_can_edit_post_type( $post_type ) ) {
                return;
            }

            $post_id = Util::createNewPost( $post_type );

            $edit_link = Util::get_edit_post_link( $post_id );

            wp_redirect( $edit_link );
            die();
        }

        public function init_editor() {
            $post_id = sanitize_text_field($_REQUEST['post']);
            if (!$post_id) {
                return;
            }

            Util::init_global_post($post_id);

            global $wp_styles, $wp_scripts;

            $content_type = get_option( 'html_type' );
            $charset = get_option( 'blog_charset' );
            @header( "Content-Type: $content_type; charset=$charset" );

            $post_id = Util::get_the_main_ID($post_id);

            Util::set_speedwapp_editor_active($post_id, true);
            $page_html_data = NULL;
            $pageData = get_post_meta($post_id, '_speedwapp_json_data', true);
            $this->loader->add_filter( 'show_admin_bar', null, '__return_false' );
            remove_action( 'wp_head', 'wp_admin_bar_header' );
			remove_action( 'wp_head', '_admin_bar_bump_cb' );

            if (!$pageData) {
                $page_html_data = $this->admin->get_page_data($post_id);
            }

            remove_all_actions( 'wp_head' );
            remove_all_actions( 'wp_footer' );
            remove_all_actions( 'wp_print_styles' );
            remove_all_actions( 'wp_print_head_scripts' );
            remove_all_actions( 'wp_print_footer_scripts' );
            remove_all_actions( 'wp_auth_check_load' );
            remove_all_actions( 'wp_enqueue_scripts' );

            $wp_styles = new \WP_Styles();
            $wp_scripts = new \WP_Scripts();

            remove_all_actions( 'after_wp_tiny_mce' );
            add_action('wp_enqueue_scripts', array( $this->admin, 'editor_fullscreen_enqueue_scripts'), 999999 );

            add_action( 'wp_head', 'wp_enqueue_scripts', 1 );
            add_action( 'wp_head', 'wp_print_styles', 8 );
            add_action( 'wp_head', 'wp_print_head_scripts', 9 );
            add_action( 'wp_head', 'wp_site_icon' );
            add_action( 'wp_head', array( $this, 'sw_editor_after_head' ), 30 );

            add_action( 'wp_footer', 'wp_print_footer_scripts', 20 );
            add_action( 'wp_footer', 'wp_auth_check_load', 30 );

            echo self::render_template('editor-full-screen.php', array(
                'postId' => $post_id,
                'page_html_data' => $page_html_data
            ));

            die();
        }

        public function load_editor() {
            check_admin_referer( 'load_speedwapp_editor', 'editor_nonce' );

            global $wp_styles, $wp_scripts;

            $this->loader->add_filter( 'show_admin_bar', null, '__return_false' );
            remove_action( 'wp_head', 'wp_admin_bar_header' );
			remove_action( 'wp_head', '_admin_bar_bump_cb' );


            remove_all_actions( 'wp_head' );
            remove_all_actions( 'wp_footer' );
            remove_all_actions( 'wp_print_styles' );
            remove_all_actions( 'wp_print_head_scripts' );
            remove_all_actions( 'wp_print_footer_scripts' );
            remove_all_actions( 'wp_auth_check_load' );
            remove_all_actions( 'wp_enqueue_scripts' );

            $wp_styles = new \WP_Styles();
            $wp_scripts = new \WP_Scripts();

            remove_all_actions( 'after_wp_tiny_mce' );
            add_action('wp_enqueue_scripts', array( $this->admin, 'load_editor_enqueue_scripts'), 999999 );

            add_action( 'wp_head', 'wp_enqueue_scripts', 1 );
            add_action( 'wp_head', 'wp_print_styles', 8 );
            add_action( 'wp_head', 'wp_print_head_scripts', 9 );
            add_action( 'wp_head', 'wp_site_icon' );
            add_action( 'wp_head', array( $this, 'sw_editor_after_head' ), 30 );

            add_action( 'wp_footer', 'wp_print_footer_scripts', 20 );
            add_action( 'wp_footer', 'wp_auth_check_load', 30 );

            echo self::render_template('load-editor.php', array());

            die();
        }

        public function sw_editor_after_head() {

        }

        /**
         * Register all of the hooks related to the public-facing functionality
         * of the plugin.
         *
         * @since    0.9.0
         */
        private function define_public_hooks()
        {
        }

        /**
         * The name of the plugin used to uniquely identify it within the context of
         * WordPress and to define internationalization functionality.
         *
         * @since     0.9.0
         *
         * @return string The name of the plugin.
         */
        public function get_plugin_name()
        {
            return $this->plugin_name;
        }

        /**
         * The reference to the class that orchestrates the hooks with the plugin.
         *
         * @since     0.9.0
         *
         * @return Speedwapp_Loader Orchestrates the hooks of the plugin.
         */
        public function get_loader()
        {
            return $this->loader;
        }

        /**
         * Retrieve the version number of the plugin.
         *
         * @since     0.9.0
         *
         * @return string The version number of the plugin.
         */
        public function get_version()
        {
            return $this->version;
        }

        /**
         * Checks if the plugin was recently updated and upgrades if necessary
         *
         * @mvc Controller
         *
         * @param string $db_version
         */
        public function upgrade( $db_version = 0 ) {
            if ( version_compare( $this->modules['Speedwapp_Settings']->settings['db-version'],  $this->version = '1.0.0', '==' ) ) {
                return;
            }
            foreach ( $this->modules as $module ) {
                $module->upgrade( $this->modules['Speedwapp_Settings']->settings['db-version'] );
            }
            $this->modules['Speedwapp_Settings']->settings = array( 'db-version' =>  $this->version = '1.0.0' );
            self::clear_caching_plugins();
        }
        /**
         * Checks that the object is in a correct state
         *
         * @mvc Model
         *
         * @param string $property An individual property to check, or 'all' to check all of them
         * @return bool
         */
        protected function is_valid( $property = 'all' ) {
            return true;
        }

        /**
         * Run the loader to execute all of the hooks with WordPress.
         *
         * @since    0.9.0
         */
        public function run()
        {
            $this->loader->run();
        }
    }
}
