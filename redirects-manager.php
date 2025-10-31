<?php
/*
Plugin Name: Redirects Manager
Plugin URI: https://kingscrestglobal.com/redirects-manager
Description: Efficiently manage 301 redirects to improve site performance and SEO.
Version: 1.0.1
Author: Kings Crest Global
Author URI:  https://kingscrestglobal.com
License:     GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: redirects-manager
Domain Path: /languages
Requires PHP: 7.0
Requires at least: 6.4
*/


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}




// Plugin version.
if ( ! defined( 'KCGRED_VERSION' ) ) {
    define( 'KCGRED_VERSION', '1.0.1' );
}

// Plugin Folder Path.
if ( ! defined( 'KCGRED_DIR' ) ) {
    define( 'KCGRED_DIR', plugin_dir_path( __FILE__ ) );
}

// Plugin Folder URL.
if ( ! defined( 'KCGRED_DIR_URL' ) ) {
    define( 'KCGRED_DIR_URL', plugin_dir_url( __DIR__ ) );
}

// Plugin Root File URL.
if ( ! defined( 'KCGRED_URL' ) ) {
    define( 'KCGRED_URL', plugins_url( '/', __FILE__ ) );
}

// Plugin Root File Path.
if ( ! defined( 'KCGRED_PATH' ) ) {
    define( 'KCGRED_PATH', plugin_basename( __FILE__ ) );
}

/**
 * The code that runs during plugin activation.
 */
if (!function_exists('kcgred_plugin_load_textdomain')) {
  function kcgred_plugin_load_textdomain() {
    load_plugin_textdomain( 'redirects-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
  }
  add_action( 'init', 'kcgred_plugin_load_textdomain' );
}



/**
 * The code that runs during plugin activation.
 * This action is documented in inc/class-kcgred-activation.php
 */
if (!function_exists('kcgred_activation_function')) {
    function kcgred_activation_function() {
        require_once KCGRED_DIR . 'inc/class-kcgred-activation.php';
        kcgred_activations_init::kcgred_activate();
    }
    register_activation_hook( __FILE__, 'kcgred_activation_function' );
}


/**
 * The code that runs during plugin deactivation.
 * This action is documented in inc/class-kcgred-deactivator.php
 */
if (!function_exists('kcgred_deactivator_plugin')) {
    function kcgred_deactivator_plugin() {
        require_once KCGRED_DIR. 'inc/class-kcgred-deactivator.php';
        kcgred_deactivate_init::kcgred_deactivate();
    }
    register_deactivation_hook( __FILE__, 'kcgred_deactivator_plugin' );
}

/**
 * The code that runs during plugin uninstall.
 * This action is documented in uninstall.php
 */
if (!function_exists('kcgred_uninstall_plugin')) {
    function kcgred_uninstall_plugin() {
        require_once KCGRED_DIR. 'uninstall.php';
    }
    register_uninstall_hook( __FILE__, 'kcgred_uninstall_plugin' );
}


/**
 * The code that runs during plugin activation.
 * This action is documented in inc/class-kcgred-init.php
 */
require_once KCGRED_DIR . 'inc/class-kcgred-init.php';


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.1
 */
function kcgred_run() {
    $plugin = new kcgred_features_init();
    $plugin->run();
}
kcgred_run();