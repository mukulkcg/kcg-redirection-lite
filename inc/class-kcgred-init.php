<?php
/**
 * Fired during plugin core functions
 *
 * @link       https://kingscrestglobal.com/
 * @since      1.0.1
 * @package    redirects-manager
 * @subpackage redirects-manager/inc
 */

/**
 * Fired during plugin run.
 *
 * This class defines all code necessary to run during the plugin's features.
 *
 * @since      1.0.1
 * @package    redirects-manager
 * @subpackage redirects-manager/inc
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

		$this->plugin_name = 'redirects-manager';
		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_redirect_manager_settings();
		$this->define_redirect_logs_settings();
		$this->define_redirect_import_export_settings();

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


		/**
		 * The class responsible for defining all actions that occur in the redirect manager settings.
		 */
		require_once KCGRED_DIR . 'inc/admin/class-redirect-manager-settings.php';

		/**
		 * The class responsible for defining all actions that occur in the redirect manager reports.
		 */
		require_once KCGRED_DIR . 'inc/admin/class-redirects-manager-reports.php';

		/**
		 * The class responsible for defining all actions that occur in the redirect manager 404 logs.
		 */
		require_once KCGRED_DIR . 'inc/admin/class-redirects-manager-error-logs.php';

		/**
		 * The class responsible for defining all actions that occur in the redirect manager import/export.
		 */
		require_once KCGRED_DIR . 'inc/admin/class-redirects-manager-import-export.php';

		/**
		 * The class responsible for defining all actions that occur in the redirect manager suport.
		 */
		require_once KCGRED_DIR . 'inc/admin/class-redirects-manager-support.php';

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
		$this->loader->add_action( 'plugin_row_meta', $plugin_admin, 'kcgred_add_view_details_button', 10, 2 );

	}



	/**
	 * Register all of the hooks related to the redirect manager settings
	 * of the plugin.
	 *
	 * @since    1.0.1
	 * @access   private
	 */
	public function define_redirect_manager_settings(){
		// Redirect Manager Settings
		$redirect_settings = new kcgred_redirect_settings( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'wp_ajax_kcgred_save_redirect', $redirect_settings, 'kcgred_save_redirect' );
		$this->loader->add_action( 'template_redirect', $redirect_settings, 'kcgred_process_redirects', 1 );
		$this->loader->add_action( 'wp_ajax_kcgred_get_redirect_stats', $redirect_settings, 'kcgred_get_redirect_stats' );
        $this->loader->add_action( 'wp_ajax_kcgred_delete_redirect', $redirect_settings, 'kcgred_delete_redirect' );
        $this->loader->add_action( 'wp_ajax_kcgred_toggle_redirect', $redirect_settings, 'kcgred_toggle_redirect' );
        $this->loader->add_action( 'wp_ajax_kcgred_delete_selected_redirects_init', $redirect_settings, 'kcgred_delete_selected_redirects_init' );
	}




	/**
	 * Register all of the hooks related to the redirect manager logs settings
	 * of the plugin.
	 *
	 * @since    1.0.1
	 * @access   private
	 */
	public function define_redirect_logs_settings() {
		// Redirect Manager logs Settings
		$redirect_logs = new kcgred_error_logs( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'cron_schedules', $redirect_logs, 'kcgred_add_three_days_schedule' );
		$this->loader->add_action( 'kcgred_cleanup_old_logs', $redirect_logs, 'kcgred_delete_old_redirect_logs' );
	}



	/**
	 * Register all of the hooks related to the redirect manager import export settings
	 * of the plugin.
	 *
	 * @since    1.0.1
	 * @access   private
	 */
	public function define_redirect_import_export_settings(){
		// Redirect Manager Import Export
		$redirect_settings = new kcgred_redirect_import_export( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_ajax_kcgred_export_redirects', $redirect_settings, 'kcgred_export_redirects' );
		$this->loader->add_action( 'wp_ajax_kcgred_import_redirects', $redirect_settings, 'kcgred_import_redirects' );
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