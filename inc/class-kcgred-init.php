<?php
/**
 * Fired during plugin core functions
 *
 * @link       https://kingscrestglobal.com/
 * @since      1.0.1
 * @package    kcg-redirection
 * @subpackage kcg-redirection/inc
 */

/**
 * Fired during plugin run.
 *
 * This class defines all code necessary to run during the plugin's features.
 *
 * @since      1.0.1
 * @package    kcg-redirection
 * @subpackage kcg-redirection/inc
 * @author     Kings Crest Global <info@kingscrestglobal.com>
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class kcgred_features_init{
	protected $loader;
	protected $plugin_name;
	protected $plugin_version;

	public function __construct() {
		if ( defined( 'KCGRED_VERSION' ) ) {
			$this->plugin_version = KCGRED_VERSION;
		} else {
			$this->plugin_version = '1.0.1';
		}
		$this->plugin_name = 'kcg-redirection';
		$this->load_dependencies();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - kcgred_loader. Orchestrates the hooks of the plugin.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.1
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once KCGRED_DIR . 'inc/class-kcgred-loader.php';


		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once KCGRED_DIR . 'inc/admin/class-kcgred-admin.php';

		$this->loader = new kcgred_loader();

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.1
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new kcgred_admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'kcgred_enqueue_admin_styles' );

         // Admin menu
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'kcgred_add_admin_menu' );
		$this->loader->add_action( 'wp_ajax_kcgred_save_redirect', $plugin_admin, 'kcgred_save_redirect' );
		$this->loader->add_action( 'wp_ajax_kcgred_delete_redirect', $plugin_admin, 'kcgred_delete_redirect' );
		$this->loader->add_action( 'wp_ajax_kcgred_toggle_redirect', $plugin_admin, 'kcgred_toggle_redirect' );
		$this->loader->add_action( 'template_redirect', $plugin_admin, 'kcgred_process_redirects', 1 );
		$this->loader->add_action( 'wp_ajax_kcgred_export_redirects', $plugin_admin, 'kcgred_export_redirects' );
		$this->loader->add_action( 'wp_ajax_kcgred_import_redirects', $plugin_admin, 'kcgred_import_redirects' );
		$this->loader->add_action( 'wp_ajax_kcgred_get_redirect_stats', $plugin_admin, 'kcgred_get_redirect_stats' );
		$this->loader->add_action( 'plugin_row_meta', $plugin_admin, 'kcgred_add_view_details_button', 10, 2 );
	}

	
	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 * @since    1.0.1
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since    1.0.1
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since    1.0.1
	 * @return    kcgred_loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since    1.0.1
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->plugin_version;
	}
}