<?php

if ( ! class_exists( 'Speedwapp_Settings' ) ) {

    /**
     * Handles plugin settings and user profile meta fields
     */
    require_once plugin_dir_path(dirname(__FILE__)).'includes/SpeedwappModule.php';

    class Speedwapp_Settings extends Speedwapp_Module {
        protected $settings;
        protected static $default_settings;
        protected static $readable_properties  = array( 'settings' );
        protected static $writeable_properties = array( 'settings' );

        const REQUIRED_CAPABILITY = 'administrator';


        protected $fields;
        protected $settings_saved;
        /*
         * General methods
         */

        /**
         * Constructor
         *
         * @mvc Controller
         */
        protected function __construct() {
            $this->settings = array();
            $this->fields = array();
            $this->settings_saved = false;
        }

        /**
         * Public setter for protected variables
         *
         * Updates settings outside of the Settings API or other subsystems
         *
         * @mvc Controller
         *
         * @param string $variable
         * @param array  $value This will be merged with SPEEDWAPP_Settings->settings, so it should mimic the structure of the SPEEDWAPP_Settings::$default_settings. It only needs the contain the values that will change, though. See WordPress_Plugin_Skeleton->upgrade() for an example.
         */
        public function __set( $variable, $value ) {
            // Note: SPEEDWAPP_Module::__set() is automatically called before this

            if ( $variable != 'settings' ) {
                return;
            }
            //$this->settings = self::validate_settings( $value );
            update_option( 'Speedwapp_Settings', $this->settings );
        }

        /**
         * Register callbacks for actions and filters
         *
         * @mvc Controller
         */
        public function register_hook_callbacks() {
            $this->loader->add_action('admin_menu', $this, 'getting_started_page');
            $this->loader->add_action('admin_enqueue_scripts', $this, 'admin_scripts');
        }

        /**
         * @return wp_speedwapp_Settings
         */
        static function single(){
            static $single = false;
            if (empty($single)) {
                $single = new Speedwapp_Settings();
            }

            return $single;
        }

        function clear_cache() {
            $this->settings = array();
        }

        /**
         * Get a settings value
         *
         * @param string $key
         *
         * @return array|bool|mixed|null|void
         */
        function get($key = ''){

            if( empty($this->settings) ){

                // Get the settings, attempt to fetch new settings first.
                $current_settings = get_option( 'wp_speedwapp_settings', false );

                if( $current_settings === false ) {
                    // We can't find the settings, so try access old settings
                    $current_settings = get_option( 'wp_speedwapp_display', array() );
                    $post_types = get_option( 'wp_speedwapp_post_types' );
                    if( !empty($post_types) ) $current_settings['post-types'] = $post_types;

                    // Store the old settings in the new field
                    update_option('wp_speedwapp_settings', $current_settings);
                }

                // Get the settings provided by the theme
                $theme_settings = get_theme_support('sw-speedwapp');
                if( !empty($theme_settings) ) $theme_settings = $theme_settings[0];
                else $theme_settings = array();

                $this->settings = wp_parse_args( $theme_settings, apply_filters( 'wp_speedwapp_settings_defaults', array() ) );
                $this->settings = wp_parse_args( $current_settings, $this->settings);

                // Filter these settings
                $this->settings = apply_filters('wp_speedwapp_settings', $this->settings);
            }

            if( !empty( $key ) ) return isset( $this->settings[$key] ) ? $this->settings[$key] : null;
            return $this->settings;
        }

        /**
         * Set a settings value
         *
         * @param $key
         * @param $value
         */
        function set($key, $value) {
            $current_settings = get_option( 'wp_speedwapp_settings', array() );
            $current_settings[$key] = $value;
            update_option( 'wp_speedwapp_settings', $current_settings );
        }

        /**
         * Enqueue admin scripts
         *
         * @param $prefix
         */
        function admin_scripts() {
            wp_register_style(
                'speedwapp-settings',
                plugin_dir_url(__FILE__) . 'css/speedwapp-settings.css',
                array(),
                SPEEDWAPP_VERSION
            );

            wp_enqueue_style('speedwapp-settings');
        }

        function getting_started_page() {
            add_menu_page(
                'speedwapp',
                'Speedwapp',
                'manage_options',
                'speedwapp_menu',
                array( $this, 'display_getting_started' ),
                'dashicons-speedwapp__admin_menu',
            );

            /* add_submenu_page(
                'speedwapp_menu',
                __( 'Getting Started', 'speedwapp' ),
                __( 'Getting Started', 'speedwapp' ),
                'manage_options',
                'speedwapp-getting-started',
                [ $this, 'display_getting_started' ]
            ); */
        }

        /**
         * Display the Page Builder settings page
        */
        function display_getting_started() {

            $create_new_page = __( 'Create Your First Page' );
            $post_type = 'page'

            ?>
            <div class="wrap">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

                <div class="sw-getting-started">
                    <div class="sw-getting-started-heading">
                        <h2><?php echo __('Welcome to Speedwapp!', 'speedwapp') ?></h2>
                        <p><?php echo __('If you are a first-time user, then Congratulations! Before you get started, get helpful tips to design and build amazing websites with our Free Ebook.', 'speedwapp') ?></p>
                    </div>

                    <div class="sw-getting-started-actions sw-getting-started-free-ebook">
                        <a
                            href="https://www.speedwebsitecreators.com/"
                            target="_blank"
                            class="button button-primary button-hero"
                        >
                            <?php echo __( 'Get our Free Ebook', 'speedwapp' ); ?>
                        </a>
                    </div>


                    <div class="sw-getting-started-heading">
                        <p><?php echo __('Learn how to use Speedwapp by watching our "Getting Started" video series. This will help you discover more tips to build websites faster. Start by creating your first page.', 'speedwapp') ?></p>
                    </div>

                    <div class="sw-getting-started-video">
                        <iframe
                            width="650"
                            height="375"
                            src="https://www.youtube.com/embed/6JQBI2PUNKU"
                            title="YouTube video player"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                        ></iframe>
                    </div>

                    <div class="sw-getting-started-actions">
                        <a
                            href="<?php echo esc_url( Util::get_new_post_link( $post_type ) ) ?>"
                            class="button button-primary button-hero"
                        >
                            <?php echo esc_html( $create_new_page ) ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php
        }

        /**
         * Get a post type array
         *
         * @return array
         */
        function get_post_types(){
            $types = array_merge(
                array( 'page' => 'page', 'post' => 'post' ),
                get_post_types( array( '_builtin' => false ) )
            );

            unset( $types['ml-slider'] );

            foreach( $types as $type_id => $type ) {
                $post_type_object = get_post_type_object( $type_id );

                if( !$post_type_object->show_ui ) {
                    unset($types[$type_id]);
                    continue;
                }

                $types[$type_id] = $post_type_object->label;
            }

            return $types;
        }

        /**
         * Prepares site to use the plugin during activation
         *
         * @mvc Controller
         *
         * @param bool $network_wide
         */
        public function activate( $network_wide ) {
        }

        /**
         * Rolls back activation procedures when de-activating the plugin
         *
         * @mvc Controller
         */
        public function deactivate() {
        }

        /**
         * Initializes variables
         *
         * @mvc Controller
         */
        public function init() {
            self::$default_settings = self::get_default_settings();
            $this->settings         = self::get_settings();
            $this->register_hook_callbacks();
        }

        /**
         * Executes the logic of upgrading from specific older versions of the plugin to the current version
         *
         * @mvc Model
         *
         * @param string $db_version
         */
        public function upgrade( $db_version = 0 ) {
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
         * @return bool
         */
        protected function is_valid( $property = 'all' ) {
            // Note: __set() calls validate_settings(), so settings are never invalid

            return true;
        }

        /**
         * Establishes initial values for all settings
         *
         * @mvc Model
         *
         * @return array
         */
           protected  function get_default_settings() {
               return array(
                   'db-version' => '0',
               );
           }

        /**
         * Retrieves all of the settings from the database
         *
         * @mvc Model
         *
         * @return array
         */
        protected  function get_settings() {
            $settings = shortcode_atts(
                self::$default_settings,
                get_option( 'Speedwapp_Settings', array() )
            );

            return $settings;
        }

        /**
         * Adds links to the plugin's action link section on the Plugins page
         *
         * @mvc Model
         *
         * @param array $links The links currently mapped to the plugin
         * @return array
         */
        /*   public  function add_plugin_action_links( $links ) {
               array_unshift( $links, '<a href="http://wordpress.org/extend/plugins/wordpress-plugin-skeleton/faq/">Help</a>' );
               array_unshift( $links, '<a href="options-general.php?page=' . 'Speedwapp_Settings">Settings</a>' );

               return $links;
           }*/


    } // end SPEEDWAPP_Settings

    // Create the single instance
    Speedwapp_Settings::single();

    /**
     * Get a setting with the given key.
     *
     * @param string $key
     *
     * @return array|bool|mixed|null
     */
    function speedwapp_setting($key = '') {
        $speedwappSettings = Speedwapp_Settings::single();
        return $speedwappSettings->get($key);
    }
}
