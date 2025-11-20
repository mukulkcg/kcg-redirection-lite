<?php
/**
 * Fired during plugin core functions
 *
 * @link       https://kingscrestglobal.com/inc/admin
 * @since      1.0.1
 * @package    redirects-manager
 * @subpackage redirects-manager/inc/admin
 */

/**
 * Fired during plugin run.
 *
 * This class defines all code necessary to run during the plugin's features.
 *
 * @since      1.0.1
 * @package    redirects-manager
 * @subpackage redirects-manager/inc/admin/
 * @author    Kings Crest Global <info@kingscrestglobal.com>
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

 class kcgred_redirect_import_export {

    private $cache_group = 'redirects_manager_redirects';

	private $table_name;
	private $plugin_admin;

	/**
	 * The ID of this plugin.
	 *
     * @since      1.0.1
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
     * @since      1.0.1
	 * @access   private
	 * @var      string    $plugin_version    The current version of this plugin.
	 */
	private $plugin_version;


	/**
	 * Initialize the class and set its properties.
	 *
     * @since      1.0.1
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $plugin_version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $plugin_version ) {

        global $wpdb;
        $this->table_name = $wpdb->prefix . 'kcgred_redirects';

		$this->plugin_name = $plugin_name;
		$this->plugin_version = $plugin_version;

		$this->plugin_admin = new kcgred_admin( $plugin_name, $plugin_version );

	}




	/**
	 * Redirects Manager Pro redirect Import / Export callback function
	 *
     * @since      1.0.1
	 */
    public function kcgred_import_export_callback_function(){
        // Verify nonce
        if(isset($_GET['tab'])){
            if (!isset($_GET['_wpnonce']) && !wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'kcgred_tab_nonce')) {
                echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
                return;
            }
        }
        ?>
        <!-- Import/Export -->
        <div class="redirect-tools">
            <h2>Import/Export</h2>
            <div class="tool-buttons">
                <button type="button" class="button" id="import-btn">
                    <span class="dashicons dashicons-upload"></span> Import CSV
                </button>
                <button type="button" class="button" id="export-btn">
                    <span class="dashicons dashicons-download"></span> Export CSV
                </button>
            </div>
            
            <div id="import-form" style="display:none; margin-top:20px;">
                <form id="csv-import-form" enctype="multipart/form-data">
                    <input type="file" name="csv_file" id="csv_file" accept=".csv">
                    <button type="submit" class="button">Upload</button>
                    <p class="description">CSV format: redirect_from, redirect_to, redirect_type</p>
                </form>
            </div>
        </div>
        <?php
    }
    

	/**
	 * Redirects Manager Pro export redirects
	 *
     * @since      1.0.1
	 */
    public function kcgred_export_redirects() {
        check_ajax_referer('kcgred_ajax_nonce_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Export functionality requires full table read, no caching needed for one-time export
        $redirects = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM %i", $this->table_name), ARRAY_A
        );
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="redirects-' . gmdate('Y-m-d-H-i-s') . '.csv"');
        
        // Output header row
        echo wp_kses_post('"redirect_from","redirect_to","redirect_type","status","hits"') . "\n";
        
        foreach ($redirects as $redirect) {
            $row = array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, [
                $redirect['redirect_from'],
                $redirect['redirect_to'],
                $redirect['redirect_type'],
                $redirect['status'],
                $redirect['hits']
            ]);
            
            echo wp_kses_post(implode(',', $row)) . "\n";
        }
        
        exit;
    }
    

	/**
	 * Redirects Manager Pro import redirects
	 *
     * @since      1.0.1
	 */
    public function kcgred_import_redirects() {
        check_ajax_referer('kcgred_ajax_nonce_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        if (!isset($_FILES['csv_file'])) {
            wp_send_json_error('No file uploaded');
        }
        
        // Initialize WP_Filesystem
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        
        $file = isset($_FILES['csv_file']['tmp_name']) ? sanitize_text_field($_FILES['csv_file']['tmp_name']) : '';
        
        // Read file using WP_Filesystem
        $file_content = !empty($file) ? $wp_filesystem->get_contents($file) : '';
        
        if ($file_content === false) {
            wp_send_json_error('Could not read file');
        }
        
        global $wpdb;
        $imported = 0;
        
        // Parse CSV content
        $lines = explode("\n", $file_content);
        
        // Skip header row
        array_shift($lines);
        
        foreach ($lines as $line) {
            // Skip empty lines
            if (empty(trim($line))) {
                continue;
            }
            
            $data = str_getcsv($line);
            
            if (count($data) >= 3) {
                $entry_status = $this->plugin_admin->kcgred_check_redirect_status($data[0]);
                
                if ($entry_status < 1) {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk import to custom table, cache invalidated after completion
                    $wpdb->insert($this->table_name, [
                        'redirect_from' => $data[0],
                        'redirect_to' => $data[1],
                        'redirect_type' => $data[2],
                        'status' => isset($data[3]) ? $data[3] : 1
                    ]);
                    $imported++;
                }
            }
        }
        
        // Invalidate all redirect caches after import
        wp_cache_delete('kcgred_all_redirects', $this->cache_group);
        
        // If you cache redirect lists or counts, invalidate those too
        wp_cache_delete('kcgred_total_redirects', $this->cache_group);

        wp_send_json_success("Imported $imported redirects");
    }

}