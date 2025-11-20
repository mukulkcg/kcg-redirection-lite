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

 class kcgred_error_logs{

    private $cache_group = 'redirects_manager_redirects';
    
	private $table_name_logs;

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

		$this->plugin_name = $plugin_name;
		$this->plugin_version = $plugin_version;

		$this->plugin_admin = new kcgred_admin( $plugin_name, $plugin_version );
		$this->table_name_logs = $this->plugin_admin->kcgred_get_table_name_logs();
	}




    /**
     * Redirects Managerion redirect 404 logs page callback function
     *
     * @since      1.0.1
     */
    public function kcgred_error_logs_callback_function(){
		global $wpdb;
            
        // Verify nonce
        if(isset($_GET['tab'])){
            if (!isset($_GET['_wpnonce']) && !wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'kcgred_tab_nonce')) {
                echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
                return;
            }
        }

        $menu_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : 'redirects-manager';
        $tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'redirects-manager-error-logs';

        // Get pagination parameters
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        
        // Get per page from URL or use default
        if (isset($_GET['per_page'])) {
            $per_page = max(1, intval($_GET['per_page']));
        } else {
            $per_page = get_option( 'posts_per_page' );
        }
        
        // Calculate offset
        $offset = ($current_page - 1) * $per_page;

        // Try to get total count from cache
        $cache_key_total = 'kcgred_total_logs_count';
        $total_logs = wp_cache_get($cache_key_total, $this->cache_group);

        if (false === $total_logs) {
            // Get total count
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table count query, result cached below
            $total_logs = $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM %i", $this->table_name_logs)
            );
            
            // Cache for 5 minutes
            wp_cache_set($cache_key_total, $total_logs, $this->cache_group, 5 * MINUTE_IN_SECONDS);
        }

        // if($total_logs > 200) {
        //     $this->kcgred_delete_old_logs_record();
        // }
        
        
        // Calculate total pages
        $total_pages = ceil($total_logs / $per_page);

        // Try to get logs from cache
        $cache_key_logs = 'kcgred_logs_page_' . $current_page . '_per_' . $per_page;
        $logs = wp_cache_get($cache_key_logs, $this->cache_group);
        
        if (false === $logs) {
            // Get redirects for current page
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table paginated query, result cached below
            $logs = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM %i ORDER BY id DESC LIMIT %d OFFSET %d", $this->table_name_logs, $per_page, $offset)
            );
            
            // Cache for 5 minutes
            wp_cache_set($cache_key_logs, $logs, $this->cache_group, 5 * MINUTE_IN_SECONDS);
        }
        ?>
        <div class="kcgred-tab-section-wrapper">
            <div class="kcgred-error-logs-wrapper">
                <?php if (empty($logs)): ?>
                    <div class="no-redirects">
                        <p>No logs found!</p>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th width="50%">URL</th>
                                <th width="30%">User Agent</th>
                                <th width="20%">Date</th>
                            </tr>
                        </thead>
                        <tbody id="redirects-list">
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td data-colname="URL">
                                        <a href="<?php echo esc_url($log->url); ?>"><?php echo esc_url($log->url); ?></a>
                                    </td>
                                    <td data-colname="User Agent">
                                    <?php echo esc_html($log->user_agent); ?>
                                    </td>
                                    <td data-colname="Date">
                                        <?php echo esc_html($log->created_at); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <span class="displaying-num">
                                <?php 
                                $start = $offset + 1;
                                $end = min($offset + $per_page, $total_pages);
                                printf('%d-%d of %d items', esc_html($start), esc_html($end), esc_html($total_pages));
                                ?>
                            </span>
                            <span class="pagination-links">
                                <?php echo wp_kses_post($this->plugin_admin->kcgred_get_paginations(esc_html($menu_page), esc_html($tab), esc_html($current_page), esc_html($total_pages))); ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }





	/**
	 * Redirects Manager Pro delete old logs record
	 *
     * @since      1.0.1
	 */
    // public function kcgred_delete_old_logs_record(){
    //     global $wpdb;

    //     // Delete all logs except the latest 100
    //     $wpdb->query("
    //     DELETE FROM {$this->table_name_logs} 
    //     WHERE ID NOT IN (
    //         SELECT ID FROM (
    //             SELECT ID FROM {$this->table_name_logs} 
    //             ORDER BY id DESC
    //             LIMIT 7
    //         ) AS keep_posts
    //     )
    //     ");
    // }




	/**
	 * Redirects Manager Pro schedule cron job
	 *
     * @since      1.0.1
	 */
    public function kcgred_add_three_days_schedule($schedules) {
        $schedules['kcgred_every_three_days'] = array(
            'interval' => 3 * DAY_IN_SECONDS, // 3 days = 259,200 seconds
            'display'  => __('Delete logs in evry 3 days', 'redirects-manager')
        );
        return $schedules;
    }

    

	/**
	 * Redirects Manager Pro The cleanup function — keeps only latest 100 logs
	 *
     * @since      1.0.1
	 */
    public function kcgred_delete_old_redirect_logs() {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table maintenance query to delete old logs, caches invalidated below
        $wpdb->query(
            $wpdb->prepare("DELETE FROM %i 
            WHERE ID NOT IN (
                SELECT ID FROM (
                    SELECT ID FROM %i 
                    ORDER BY id DESC
                    LIMIT 200
                ) AS keep_posts
            )", $this->table_name_logs, $this->table_name_logs)
        );

        // Invalidate all log-related caches after bulk deletion
        wp_cache_delete('kcgred_total_logs_count', $this->cache_group);
    }
}