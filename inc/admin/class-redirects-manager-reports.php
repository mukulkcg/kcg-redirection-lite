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

 class kcgred_redirect_reports {

	private $table_name;

    private $cache_group = 'redirects_manager_redirects';

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

	}


    
	/**
	 * Redirects Manager Pro redirect report management
	 *
     * @since      1.0.1
	 */
	public function kcgred_redirect_reports_settings() {
        // Verify nonce
        if(isset($_GET['tab'])){
            if (!isset($_GET['_wpnonce']) && !wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'kcgred_tab_nonce')) {
                echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
                return;
            }
        }
        
		global $wpdb;

        // Try to get redirects from cache
        $cache_key = 'kcgred_all_redirects_report';
        $redirects = wp_cache_get($cache_key, $this->cache_group);

        if (false === $redirects) {
            // Get all redirects
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Report data from custom table, result cached below
            $redirects = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM %i ORDER BY id DESC", $this->table_name)
            );
            
            // Cache for 5 minutes (reports don't need real-time data)
            wp_cache_set($cache_key, $redirects, $this->cache_group, 5 * MINUTE_IN_SECONDS);
        }
        
        // Get statistics
        $total_redirects = count($redirects);
        $active_redirects = count(array_filter($redirects, function($r) { return $r->status == 1; }));
        $total_hits = array_sum(array_column($redirects, 'hits'));
        ?>
        <div class="wrap kcgred-redirect-manager">
            <!-- Statistics -->
            <div class="redirect-stats">
                <div class="stat-box">
                    <div class="stat-number"><?php echo esc_html($total_redirects); ?></div>
                    <div class="stat-label">Total Redirects</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo esc_html($active_redirects); ?></div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-box hits-count">
                    <div class="stat-number"><?php echo number_format($total_hits); ?></div>
                    <div class="stat-label">Total Hits</div>
                </div>
            </div>


            <!-- Statistics Chart -->
            <div class="kcgred-redirects-chart-wrapper">
                <div class="kcgred-redirects-chart-canvas">
                    <canvas id="kcgred-redirects-chart" style="max-width: 600px; height: 600px;" data-reports="<?php echo esc_html($total_redirects.', '.$active_redirects.', ').number_format($total_hits); ?>"></canvas>
                </div>
            </div>
        </div>
        <?php
    }
}
