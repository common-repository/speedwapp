<?php

/**
 * Created by PhpStorm.
 * User: Ideal
 * Date: 13/06/2016
 * Time: 11:54
 */
if (!class_exists('SPEEDWAPP_Ajax')) {

    //  require_once plugin_dir_path(__FILE__).'includes/SpeedwappModule.php';
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/SpeedwappModule.php';

    class Speedwapp_Ajax extends Speedwapp_Module {

        protected static $readable_properties = array();    // These should really be constants, but PHP doesn't allow class constants to be arrays
        protected static $writeable_properties = array();

        /*
         * Magic methods
         */

        /**
         * Constructor
         *
         * @mvc Controller
         */
        public function __construct() {
            require_once plugin_dir_path(dirname(__FILE__)) . 'modules/Utilitaire.php';
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/Util.php';

            //   require_once plugin_dir_path(dirname(__FILE__)).'public/SpeedwappPublic.php';
        }

        /**
         * Initializes variables
         *
         * @mvc Controller
         */
        public function init() {
            $this->register_hook_callbacks();
        }

        public function register_hook_callbacks() {

            $this->loader->add_action('wp_ajax_get_all_widgets', $this, 'get_all_widgets'); //get_calendar
            $this->loader->add_action('wp_ajax_get_post_content', $this, 'get_post_content');
            $this->loader->add_action('wp_ajax_save_post_content', $this, 'save_post_content');
            $this->loader->add_action('wp_ajax_get_widget_template', $this, 'get_widget_template');
            $this->loader->add_action('wp_ajax_get_shortcode_template', $this, 'get_shortcode_template');
            $this->loader->add_action('wp_ajax_get_preview_url', $this, 'get_preview_url');
            $this->loader->add_action('wp_ajax_get_widget_calendar_form', $this, 'get_widget_calendar_form');
            $this->loader->add_action('wp_ajax_get_post_template', $this, 'get_post_template');
            $this->loader->add_action('wp_ajax_get_current_wp_users', $this, 'get_current_wp_users');
            $this->loader->add_action('wp_ajax_save_speedwapp_api_token', $this, 'save_speedwapp_api_token');
            $this->loader->add_action('wp_ajax_get_all_posts', $this, 'get_all_posts');
            $this->loader->add_action('wp_ajax_get_page_list', $this, 'get_page_list');

            //  $this->loader->add_action('wp_ajax_save_post', $this, 'save_post');
            //  $this->loader->add_action('wp_ajax_get_calendar', $this, 'get_calendar');
        }

        public function get_all_posts() {
            $all_post = get_posts();
            $new_all_post = array();
            foreach ($all_post as $post) {

                $newPost = array();
                $post_id = $post->ID;
                $post_author = $post->post_author;
                $post_date = $post->post_date;
                $post_title = $post->post_title;
                $post_content = get_post_meta($post_id, '_speedwapp_html_data', true);
                if ( empty($post_content) ) {
                    $post_content = $post->post_content;
                }

                $newPost['post_content'] = $post_content;
                $newPost['post_author'] = $post_author;
                $newPost['post_date'] = $post_date;
                $newPost['post_title'] = $post_title;

                $new_all_post[] = $newPost;
            }
            echo json_encode($new_all_post);
            wp_die();
        }

        public function get_page_list() {
            $all_post = get_posts();
            echo json_encode($all_post);
            wp_die();
        }

        /**
         * get all widgets
         * @since    0.9.0
         */
        public function get_all_widgets() {
            $widgetManager = new Widget();
            $widgets = $widgetManager->get_widgets();
            $allWidgets = array();
            foreach ($widgets as $widget) {
                $newWidgets = array();
                $newWidgets['title'] = $widget['title'];
                $newWidgets['name'] = $widget['class'];
                $newWidgets['type'] = 'wordpressWidget';
                $file_path = plugin_dir_path(__FILE__) . 'img/' . $widget['class'] . '.png';
                if (file_exists($file_path)) {
                    $newWidgets['image'] = plugin_dir_url(__FILE__) . 'img/' . $widget['class'] . '.png';
                } else {
                    $newWidgets['image'] = null;
                }
                $newWidgets['description'] = $widget['description'];
                $allWidgets[] = $newWidgets;
            }
            echo json_encode($allWidgets);

            wp_die();
        }

        /**
         * get post content
         * @since    0.9.0
         */
        public function get_post_content() {
            $post_id = sanitize_text_field($_POST['post_id']);
            if (!$post_id) {
                return '';
            }

            $post_json_data = get_post_meta($post_id, '_speedwapp_json_data', true);
            echo json_encode($post_json_data);
            wp_die();
        }

        public function save_post_content()
        {
            if (
                ! array_key_exists('post_ID', $_POST)
                || ! array_key_exists('json_data', $_POST)
                || !$_POST['post_ID']
            ) {
                wp_die();
            }

            $post_id = sanitize_text_field($_POST['post_ID']);
            Util::init_global_post($post_id);
            do_action('speedwapp_save_post_json_data', $post_id, sanitize_text_field($_POST['json_data']) );
            wp_die();
        }

        /**
         * get  widgets calendar
         * @since    0.9.0
         */
        public function get_calendar() {
            echo self::render_template('WP_Widget_Calendar.html.twig');

            wp_die();
        }

        public function get_shortcode_template() {
            if (
                !array_key_exists('post_id', $_POST) ||
                !array_key_exists('shortcode', $_POST)
            ) {
                echo 'Invalid shortcode';
                wp_die();
            }

            $post_id = sanitize_text_field($_POST["post_id"]);
            $shortcode = sanitize_text_field($_POST["shortcode"]);

            if (!$post_id || !$shortcode) {
                echo '';
                wp_die();
            }

            Util::init_global_post($post_id);

            echo do_shortcode( shortcode_unautop( $shortcode ) );
            wp_die();
        }

        /**
         * get HTML template for a widget
         * @since    0.9.0
         */
        public function get_widget_template() {
            $the_issue_key = sanitize_text_field($_POST["widget_type"]);
            if (!class_exists($the_issue_key)) {
                wp_die();
            }

            $args = array(
                'before_title' => '<h2 class ="widget-title">',
                'after_title' => '</h2>',
                'after_widget' => '',
                'before_widget' => '',
            );

            $values = [];
            foreach ($_POST as $key => $value) {
                if (in_array($key, ['widget_type', 'action'])) {
                    continue;
                }

                $values[$key] = sanitize_text_field($value);
            }

            $widget = new $the_issue_key();
            $widgets = $widget->widget($args, $values);
            echo self::render_template($widgets);

            wp_die();
        }

        /**
         * get widgets calendar form
         * @since    0.9.0
         */
        public function get_widget_calendar_form() {
            $the_issue_key = sanitize_text_field( $_GET["widget_model"] );
            $widget = new $the_issue_key();
            ob_start();
            $widget_data = array();
            $widget->form( $widget_data );
            $widgets = stripslashes(ob_get_contents());
            ob_end_clean();

            echo json_encode(array('html' => $widgets));

            wp_die();
        }

        /**
        * Get post content
        */
        public function get_post_template() {
            $post_id = sanitize_text_field( $_POST["post_id"] );
            if (!$post_id) {
                die();
            }

            $post_content = get_post($post_id);
            $content = $post_content->post_content;

            echo json_encode(array('html' => $content));
            wp_die();
        }

        /**
         * save api token
         *
         * @mvc Model
         *
         * @return bool
         */
        public function save_speedwapp_api_token() {
            $current_user = wp_get_current_user();
            $userId = $current_user->ID;
            $apiToken = sanitize_text_field( $_POST["apiToken"] );

            update_option( 'speedwapp_api_token' , $apiToken );
            update_user_meta($userId, 'speedwapp_api_token', $apiToken);

            wp_die();
        }

        /**
         * get the url for preview
         *
         * @mvc Model
         *
         * @return bool
         */
        public function get_current_wp_users() {
            $current_user = wp_get_current_user();
            wp_send_json_success($current_user, 200);
        }

        /**
         * Prepares site to use the plugin during activation
         *
         * @mvc Controller
         *
         * @param bool $network_wide
         */
        public function activate($network_wide) {

        }

        /**
         * Rolls back activation procedures when de-activating the plugin
         *
         * @mvc Controller
         */
        public function deactivate() {

        }

        /**
         * Executes the logic of upgrading from specific older versions of the plugin to the current version
         *
         * @mvc Model
         *
         * @param string $db_version
         */
        public function upgrade($db_version = 0) {
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
        protected function is_valid($property = 'all') {
            return true;
        }
    }
}
