<?php
/*
 * Plugin Name:       Speedwapp
 * Plugin URI:
 * Description:       Speedwapp for Wordpress 
 * Version:           0.9.0
 * Author:            Speedwapp
 * Author URI:        https://speedwapp.com
 * Text Domain:       speedwapp
 * Domain Path:       /languages
 */

if (!defined('WPINC')) {
    die('Access denied.');
}

define( 'SPEEDWAPP_NAME',                 'Speedwapp for Wordpress' );
define( 'SPEEDWAPP_VERSION', '0.9.0' );
define( 'SPEEDWAPP_REQUIRED_PHP_VERSION', '5.3' );
define( 'SPEEDWAPP_REQUIRED_WP_VERSION',  '3.1' );

/**
 * Checks if the system requirements are met
 *
 * @return bool True if system requirements are met, false if not
 */
function requirements_met_speedwapp() {
    global $wp_version;
    if ( version_compare( PHP_VERSION, SPEEDWAPP_REQUIRED_PHP_VERSION, '<' ) ) {
        return false;
    }
    if ( version_compare( $wp_version, SPEEDWAPP_REQUIRED_WP_VERSION, '<' ) ) {
        return false;
    }
    return true;
}



/**
 * Begins execution of Speedwapp.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.9.0
 */
function run_wp_speedwapp()
{
    require_once plugin_dir_path(__FILE__).'includes/SpeedwappManager.php';
    require_once plugin_dir_path(__FILE__).'includes/SpeedwappShortCodeWidgets.php';
    $shortCode = new Short_Code_Widgets();
    $shortCode->add_shortcode_for_widgets();
    $manager = new Speedwapp_Manager();
    register_activation_hook(__FILE__, array($manager, 'activate'));
    register_deactivation_hook(__FILE__, array($manager, 'deactivate'));
    $manager->run();
}

/*
 * Check requirements and load main class
 * The main program needs to be in a separate file that only gets loaded if the plugin requirements are met. Otherwise older PHP installations could crash when trying to parse it.
 */
if (requirements_met_speedwapp() ) {
    run_wp_speedwapp();
}


