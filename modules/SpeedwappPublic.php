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
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @author     akambi <akambi@speedwapp.com>
 */
class Speedwapp_Public
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
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    0.9.0
     *
     * @var Speedwapp_Loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * Initialize the class and set its properties.
     *
     * @since    0.9.0
     *
     * @param string $plugin_name The name of the plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct($plugin_name, $version, $loader)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->loader = $loader;

        $this->loader->add_action('init', $this, 'init');
        $this->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_styles', 99);
        $this->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_scripts', 99);
    }

    private function is_preview_mode() {
      return (isset($_REQUEST['speedwapp_preview']) && $_REQUEST['speedwapp_preview']);
    }

    public function init() {
      $preview = $this->is_preview_mode();
      if ( $preview
           && ( !isset( $_GET['preview_nonce'] )
                || !wp_verify_nonce( $_GET['preview_nonce'], 'speedwapp_preview_post' )
           )
      ) {
        wp_die('Invalid Nonce', 'Invalid Nonce', [
          'response' => 403,
					'back_link' => true,
        ] );
      }
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    0.9.0
     */
    public function enqueue_styles()
    {
      global $post;

      if (empty($post)) {
          return;
      }

      if (!Util::is_speedwapp_editor_active($post->ID)) {
        return;
      }

      $preview = $this->is_preview_mode() ? 1 : 0;

      if ($preview) {
        $cssLibs = get_option('_speedwapp_css_libs', false);
      } else {
        $cssLibs = get_post_meta($post->ID, '_speedwapp_css_libs', true);
      }

      if (is_array($cssLibs)) {
        foreach ($cssLibs as $cssKey => $cssLib) {
          wp_register_style($cssKey, $cssLib);
          wp_enqueue_style($cssKey);
        }
      }

      if ($preview) {
        $swCssBasename = "post-preview.css";
      } else {
        $swCssBasename = "post-$post->ID.css";
      }

      $upload_dir = wp_upload_dir();
      $swCssPath = $upload_dir['basedir'] . "/speedwapp/css/$swCssBasename";
      if ( !is_file($swCssPath) ) {
        return;
      }

      $swPublicCss = $upload_dir['baseurl'] . "/speedwapp/css/$swCssBasename";

      wp_register_style(
        'speedwapp-public',
        $swPublicCss,
        array(),
        null,
        false
      );

      wp_enqueue_style('speedwapp-public');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    0.9.0
     */
    public function enqueue_scripts()
    {
      global $post;

      if (empty($post)) {
          return;
      }

      if (!Util::is_speedwapp_editor_active($post->ID)) {
        return;
      }

      $preview = $this->is_preview_mode() ? 1 : 0;
      if ($preview) {
        $jsLibs = get_option('_speedwapp_js_libs', false);
      } else {
        $jsLibs = get_post_meta($post->ID, '_speedwapp_js_libs', true);
      }

      if (is_array($jsLibs)) {
        foreach ($jsLibs as $jsKey => $jsLib) {
          wp_register_script($jsKey, $jsLib, array(), null, true);
          wp_enqueue_script($jsKey);
        }
      }

      if ($preview) {
        $swJsBasename = "post-preview.js";
      } else {
        $swJsBasename = "post-$post->ID.js";
      }

      $upload_dir = wp_upload_dir();
      $swJsPath = $upload_dir['basedir'] . "/speedwapp/js/$swJsBasename";
      if ( !is_file($swJsPath) ) {
        return;
      }

      $swPublicJs = $upload_dir['baseurl'] . "/speedwapp/js/$swJsBasename";

      wp_register_script(
        'speedwapp-public',
        $swPublicJs,
        array('jquery'),
        null,
        true
      );

      wp_enqueue_script('speedwapp-public');
    }
}
