<?php

/**
 * Created by PhpStorm.
 * User: akambi
 * Date: 11/06/2016
 * Time: 16:48.
 */

if ( ! class_exists( 'Post' ) ) {

    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/SpeedwappModule.php';

    class Post extends Speedwapp_Module
    {
        protected static $readable_properties = array();    // These should really be constants, but PHP doesn't allow class constants to be arrays
        protected static $writeable_properties = array();

        /**
         * Constructor
         *
         * @mvc Controller
         */
        public function __construct()
        {
            // $this->register_hook_callbacks();
        }

        /**
         * Switch to Wordpress editor
         *
         * @param $post_id
         * @param $post
         *
         * @action save_post
         */
        public function save_post($post_id)
        {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            if (
                ! isset( $_POST['_is_speedwapp_save_post_nonce'] )
                || ! wp_verify_nonce( $_POST['_is_speedwapp_save_post_nonce'], 'Speedwapp_Save_Post' )
            ) {
                return;
            }

            if (isset ($_POST['_is_speedwapp_editor_active']) ) {
                Util::set_speedwapp_editor_active( $post_id, ! empty( $_POST['_is_speedwapp_editor_active'] ) );
            }
        }

        /**
         * Save the post json data to database.
         *
         * @param $postId
         * @param $post
         *
         * @action save_post
         */
        public function speedwapp_save_post_json_data($post_id, $json_data = null)
        {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            if (
                ! isset( $_POST['_is_speedwapp_save_post_nonce'] )
                || ! wp_verify_nonce( $_POST['_is_speedwapp_save_post_nonce'], 'Speedwapp_Save_Post' )
            ) {
                return;
            }
    
            Util::init_global_post($post_id);

            $post = get_post($post_id);
            $json_data = apply_filters('speedwapp_json_data_pre_save', $json_data, $post, $post_id);
            if ($json_data) {
                update_post_meta($post_id, '_speedwapp_json_data', $json_data);
            } else {
                // There are no pages, so delete the page data
                delete_post_meta($post_id, '_speedwapp_json_data');
            }
        }

        private function is_preview_mode() {
            return (isset($_REQUEST['speedwapp_preview']) && $_REQUEST['speedwapp_preview']);
        }

        /**
         * Filter the content of the post
         *
         * @param $content
         *
         * @return string
         *
         * @filter the_content
         */
        public function filter_content($content)
        {
            global $post;

            if (empty($post)) {
                return $content;
            }

            if (!apply_filters('speedwapp_filter_content_enabled', true)) {
                return $content;
            }

            if ($this->is_preview_mode()) {
                $postPreviewId = get_option('_speedwapp_ID_preview', false);
                $pageData = get_option('_speedwapp_html_preview_data', false);
                if ($post->ID != $postPreviewId || empty($pageData)) {
                    return $content;
                }
            } else {
                $pageData = get_post_meta($post->ID, '_speedwapp_html_data', true);
            }

            if (empty($pageData)) {
                return $content;
            }

            $pageData = htmlspecialchars_decode($pageData);

            return $pageData;
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
            $this->loader->add_action('wp_ajax_save_swapp_zip', $this, 'download_swapp_zip_and_save_html');
            // $this->loader->add_action('wp_ajax_save_swapp_zip_in_theme', $this, 'download_swapp_zip_and_putin_theme');

            $this->loader->add_action('save_post', $this, 'save_post');
            $this->loader->add_action('speedwapp_save_post_json_data', $this, 'speedwapp_save_post_json_data', 10, 2);

            $this->loader->add_action('edit_form_after_title', $this, 'wp_speedwapp_render_switch_editor');
            $this->loader->add_action('the_content', $this, 'filter_content');

            if ($this->is_preview_mode()) {
                $this->loader->add_filter('show_admin_bar', null, '__return_false');
            }
        }

        /**
         * Callback to render the switch editor button
         * @param $post
         */
        public function wp_speedwapp_render_switch_editor($post)
        {
            if (Util::is_gutenberg_active()) {
                return;
            }

            wp_nonce_field( 'Speedwapp_Save_Post', '_is_speedwapp_save_post_nonce' );

            echo self::render_template('switch-block-editor.php', array());
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

        private function downloadZipFile($url, $passcode, $filepath) {
                $speedwapp_api_token = get_option('speedwapp_api_token');
                $body = array(
                    'passcode' => $passcode
                );

                $args = array(
                    'body'        => $body,
                    'timeout'     => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking'    => true,
                    'headers'     => array(
                        'Authentication' => $speedwapp_api_token,
                    ),
                    'cookies'     => array(),
                );

                $response = wp_remote_post( $url, $args );

                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                    trigger_error("Something went wrong: $error_message", E_USER_ERROR);
                }

                $http_code = wp_remote_retrieve_response_code( $response );
                $raw_file_data = wp_remote_retrieve_body( $response );
                file_put_contents($filepath, $raw_file_data);
                /* Check for 404 (file not found). */
                if ($http_code === 404) {
                    return false;
                }

                return (filesize($filepath) > 0)? true : false;
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
        public function download_swapp_zip_and_save_html()
        {
            $url = esc_url_raw($_POST['url']);
            $passcode = sanitize_text_field($_POST['passcode']);
            $postId = sanitize_text_field($_POST['postId']);
            $published = sanitize_text_field($_POST['published']) === 'true';

            $dirTmp = plugin_dir_path(__FILE__) . 'templates/tmp';
            if (self::deleteDir($dirTmp)) {
                mkdir($dirTmp, 0777, true);
            }


            $zipFile = $dirTmp . "Tmpfile.zip";
            $success = $this->downloadZipFile($url, $passcode, $zipFile);

            if (!$success) {
                return;
            }

            $zip = new ZipArchive;
            if ($zip->open($zipFile) === true) {
                $zip->extractTo($dirTmp);
                $zip->close();
                unlink($zipFile);
            } else {
                wp_die( 'Error: Preview not available' );
            }

            $this->save_page_content($postId, $published);
            wp_die();
        }

        public function save_page_content($postId, $published)
        {
            $html = $this-> scan_file(plugin_dir_path(__FILE__) . 'templates/tmp/index.html');

            $this->move_assets_to_upload_dir(
                'css/swapp_style.css',
                'css',
                $postId,
                $published
            );

            $this->move_assets_to_upload_dir(
                'js/index_js.js',
                'js',
                $postId,
                $published
            );

            $html = $this->move_shared_assets_to_upload_dir($html, 'img');
            $html = $this->move_shared_assets_to_upload_dir($html, 'js');
            $html = $this->move_shared_assets_to_upload_dir($html, 'css');

            update_option('_speedwapp_ID_preview', $postId);
            $cssLibs = [];
            $jsLibs = [];

            // common to all posts / page
            $result = preg_match_all('#<link\s.*?(?:href=[\'"](.*?)[\'"]).*?>#is', $html, $matches);
            if (count($matches) === 2) {
                foreach ($matches[1] as $index => $cssLib) {
                    $result = preg_match('#\/([\d\w\.-]+?)(?:\.min)?\.css(?:\?[^\"]*)?#is', $cssLib, $matches);
                    if (count($matches) === 2) {
                      $cssKey = $matches[1];
                    } else {
                      $cssKey = 'speedwapp-css-lib' . ($index + 1);
                    }

                    $cssLibs[$cssKey] = $cssLib;
                }
                $html = preg_replace('#<link\s.*?(?:href=[\'"].*?[\'"]).*?>#is', '', $html);
            }

            $result = preg_match_all('#<script\s.*?(?:src=[\'"](.*?)[\'"]).*?>\s*</script>#is', $html, $matches);
            if (count($matches) === 2) {
                foreach ($matches[1] as $index => $jsLib) {
                    $result = preg_match('#\/([\d\w\.-]+?)(?:\.min)?\.js(?:\?[^\"]*)?#is', $jsLib, $matches);
                    if (count($matches) === 2) {
                      $jsKey = $matches[1];
                    } else {
                      $jsKey = 'speedwapp-js-lib' . ($index + 1);
                    }

                    $jsLibs[$jsKey] = $jsLib;
                }
                $html = preg_replace('#<script\s.*?(?:src=[\'"].*?[\'"]).*?>\s*</script>#is', '', $html);
            }

            $html = trim($html);

            update_option('_speedwapp_html_preview_data', $html);
            update_option('_speedwapp_css_libs', $cssLibs);
            update_option('_speedwapp_js_libs', $jsLibs);

            if ($published) {
                wp_update_post(
                    [
                        'ID' => $postId,
                        'post_content' => $html,
                    ]
                );

                update_post_meta($postId, '_speedwapp_html_data', $html);
                update_post_meta($postId, '_speedwapp_css_libs', $cssLibs);
                update_post_meta($postId, '_speedwapp_js_libs', $jsLibs);
            }

            $dirTmp = plugin_dir_path(__FILE__) . 'templates/tmp';
            self::deleteDir($dirTmp);
        }

        /**
         * TODO: Move into utils
         */
        public static function deleteDir($dirPath) {
            if (!file_exists($dirPath)) {
                return true;
            }

            if (!is_dir($dirPath)) {
                return unlink($dirPath);
            }

            foreach (scandir($dirPath) as $item) {
                if ($item == '.' || $item == '..') {
                    continue;
                }

                if (!self::deleteDir($dirPath . DIRECTORY_SEPARATOR . $item)) {
                    return false;
                }
            }

            rmdir($dirPath);
            return true;
        }

        public function scan_file($path)
        {
            if(is_file($path)){

                $content =  file_get_contents($path);
                return $content;
            }

            $dir =  $path;
            if (!is_dir($dir)) {
                return '';
            }

            $files = scandir($dir);
            $content = '';
            foreach($files as $file)
            {
                if(is_file($dir.$file)){
                    $content .=  file_get_contents($dir.$file);
                }
            }
            return $content;
        }

        //////////////

        public function move_shared_assets_to_upload_dir($html, $baseFolder)
        {
            require_once get_home_path(). 'wp-admin/includes/file.php';
            global $wp_filesystem;

            $upload_dir = wp_upload_dir();

            if ( !is_dir( $upload_dir['basedir'] . '/speedwapp/' ) ) {
                mkdir( $upload_dir['basedir'] . '/speedwapp/' );
            }

            if ( !is_dir( $upload_dir['basedir'] . '/speedwapp/' . $baseFolder ) ) {
                mkdir( $upload_dir['basedir'] . '/speedwapp/' . $baseFolder );
            }

            $dir =  plugin_dir_path(__FILE__) . "templates/tmp/$baseFolder/";
            $files = scandir( $dir );
            foreach ( $files as $file )
            {
                if (is_file( $dir . $file ) ) {
                    rename(
                        $dir . $file,
                        $upload_dir['basedir'] . "/speedwapp/$baseFolder/" . $file
                    );
                }

                $html = str_replace(
                    array(
                        "'$baseFolder/$file",
                        "\"$baseFolder/$file"
                    ),
                    array(
                        "'" . $upload_dir['baseurl'] . "/speedwapp/$baseFolder/" . $file,
                        '"' . $upload_dir['baseurl'] . "/speedwapp/$baseFolder/" . $file
                    ),
                    $html
                );
            }
            return $html;
        }

        public function move_assets_to_upload_dir( $cssFile, $baseFolder, $post_id, $published )
        {
            require_once get_home_path(). 'wp-admin/includes/file.php';
            global $wp_filesystem;

            $cssFilePath = plugin_dir_path(__FILE__) . "templates/tmp/$cssFile";
            $upload_dir = wp_upload_dir();

            if ( !is_dir( $upload_dir['basedir'] . '/speedwapp/' ) ) {
                mkdir( $upload_dir['basedir'] . '/speedwapp/' );
            }

            if ( !is_dir( $upload_dir['basedir'] . "/speedwapp/$baseFolder" ) ) {
                mkdir( $upload_dir['basedir'] . "/speedwapp/$baseFolder" );
            }

            if ( !is_file( $cssFilePath ) ) {
                return;
            }

            $baseName = "post-preview.$baseFolder";
            if ($published) {
                $baseName = "post-$post_id.$baseFolder";
            }

            $css = $this->scan_file($cssFilePath);
            $destinationPath = $upload_dir['basedir'] . "/speedwapp/$baseFolder/$baseName";
            if ( !$css && is_file( $destinationPath ) ) {
                unlink($destinationPath);
                return;
            }

            rename( $cssFilePath, $upload_dir['basedir'] . "/speedwapp/$baseFolder/$baseName" );
        }

        public function  download_swapp_zip_and_putin_theme() {
            if (!current_user_can('edit_theme_options')) {
                return;
            }

            $url = sanitize_text_field($_POST['url']);
            $passcode = sanitize_text_field($_POST['passcode']);

            $dirTmp = sys_get_temp_dir() . '/speedwapp/';
            $dirDownloadTemplate = sys_get_temp_dir() . '/wp-download-speedwapp/';
            $newTemplateDir = get_theme_root() . '/speedwapp/';
            self::deleteDir($dirTmp);
            self::deleteDir($dirDownloadTemplate);

            if (file_exists($dirTmp)) {
                self.deleteDir($dirTmp);
            }
            mkdir($dirTmp, 0777, true);

            if (!file_exists($dirDownloadTemplate)) {
                mkdir($dirDownloadTemplate, 0777, true);
            }
            $zipFile = $dirTmp . "Tmpfile.zip";
            file_put_contents($zipFile, fopen($url, 'r'));

            $this->copyRecursive(get_template_directory(), $dirTmp);

            $zip = new ZipArchive;
            if ($zip->open($zipFile) === true) {
                $zip->extractTo($dirDownloadTemplate);
                $zip->close();
                unlink($zipFile);
            } else {
                echo 'failed';
            }

            $this->add_widget_to_siderbar($dirDownloadTemplate);
            $this->cleanAssetsPath($dirDownloadTemplate);

            $this->copyRecursive($dirDownloadTemplate, $dirTmp);
            self::deleteDir($newTemplateDir);
            $this->copyRecursive($dirTmp, $newTemplateDir);
            $this->rewrite_image_path_for_theme();

            $this->active_theme_automatically();
        }

        private function copyRecursive($source, $dest) {
            if (!is_dir($source)) {
                return;
            }

            if (!file_exists($dest)) {
                mkdir($dest, 0777, true);
            }

            foreach (
             $iterator = new \RecursiveIteratorIterator(
              new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
              \RecursiveIteratorIterator::SELF_FIRST) as $item
            ) {
              if ($item->isDir()) {
                @mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
              } else {
                copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
              }
            }
        }

        /**
        * Prefix relative path by thme directory
        */
        protected function cleanAssetsPath($dirTmp) {
            $file = $dirTmp. '/header.php';
            $fileContent = $this->scan_file($file);
            $regexp = '/<link\s*.*href=[\"\']\/?(css.*)[\"\']/';
            preg_match_all($regexp, $fileContent, $keys, PREG_PATTERN_ORDER);
            $aCssFiles = array_unique($keys[1]);
            if (count($aCssFiles)) {
                foreach ($aCssFiles as $cssPath) {
                    $wordpressCssPath = get_theme_root_uri() . '/speedwapp/' . $cssPath;
                    $fileContent = str_replace($cssPath, $wordpressCssPath, $fileContent);
                }

                // Replace the header file content
                $handle = fopen($file, "wb");
                $numbytes = fwrite($handle, $fileContent);
                fclose($handle);
            }
        }

        public function add_widget_to_siderbar($dirTmp)
        {
            $fileContent = $this->scan_file($dirTmp. '/sidebar.php');
            preg_match_all("/\[add_widget widget_name=WP_[^\]]*\]/", $fileContent, $matches);

            foreach ($matches[0] as $item) {
                preg_match_all("/WP_[^\s]+/", $item, $res);
                $widgetName =  strtolower(str_replace('WP_', '', $res[0][0]));
                $widgetNamePure =  strtolower(str_replace('WP_Widget_', '', $res[0][0]));
                preg_match_all("/instance=([^\]]*)\]/", $item, $instancematch);

               // var_dump($instancematch[1][0]);
                if(!preg_match('/\&/',$instancematch[1][0])) {
                    $id[1] = str_replace('wordpressId=','',$instancematch[1][0]);
                } else {
                    preg_match("/wordpressId=([^\&]*)\&/", $instancematch[1][0], $id);
                }

                $active_widgets = get_option('sidebars_widgets');

                $valueExist =  $this-> in_array_recursive($id[1],$active_widgets);

                if ($valueExist) {
                    continue;
                };

                $optionCalendar = get_option($widgetName);

                $newCalendar =
                    array(
                        'title' => '',
                    );
                array_push($optionCalendar, $newCalendar);

                update_option($widgetName, $optionCalendar);

                end($optionCalendar);
                $key = key($optionCalendar);

                $active_widgets = get_option('sidebars_widgets');

                array_push($active_widgets['sidebar-1'], $widgetNamePure.'-' . $key);

                update_option('sidebars_widgets', $active_widgets);
            }
            $this->remove_short_code($dirTmp. '/sidebar.php');
        }

        public function active_theme_automatically() {
            update_option('template', 'speedwapp');
            update_option('stylesheet', 'speedwapp');
        }

        public function rewrite_image_path_for_theme() {
            $dir = get_theme_root() . '/speedwapp';
            $files = scandir($dir);
            $content = '';
            foreach($files as $file)
            {
                if(is_file($dir.$file)){
                    $html = $this-> scan_file($dir.$file);
                    preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i',$html, $matches);

                    $handle = fopen($dir.$file, "wb");
                    $newstring = str_replace($matches[1], '<?php echo dirname(get_bloginfo(\'stylesheet_directory\'))."/'.$matches[1].'" ?>', $html);
                    $numbytes = fwrite($handle, $newstring);
                    fclose($handle);
                }
            }

        }
        public function remove_short_code($file) {
            if ( ! is_file( $file ) ) {
                return;
            }

            $html = $this->scan_file($file);
            preg_match_all("/\[add_widget widget_name=WP_[^\]]*\]/", $html, $matches);
            $handle = fopen($file, "wb");
            $newstring = $html;
            $i = 0;
            foreach ($matches[0] as $value) {
                $replacementString = ($i == 0) ? '<?php dynamic_sidebar(); ?>' : '';
                $i++;
                $newstring = str_replace($value, $replacementString, $newstring);
            }
            $numbytes = fwrite($handle, $newstring);
            fclose($handle);
        }

      public function in_array_recursive($item , $array) {
          return preg_match('/"'.$item.'"/i' , json_encode($array));
      }
        
    }
}
