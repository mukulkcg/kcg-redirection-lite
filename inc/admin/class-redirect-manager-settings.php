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

 class kcgred_redirect_settings{

    private $cache_group = 'redirects_manager_redirects';

	private $table_name;
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
        $this->table_name = $wpdb->prefix . 'kcgred_redirects';

		$this->plugin_name = $plugin_name;
		$this->plugin_version = $plugin_version;

		$this->plugin_admin = new kcgred_admin( $plugin_name, $plugin_version );
		$this->table_name_logs = $this->plugin_admin->kcgred_get_table_name_logs();
	}





	/**
	 * Redirects Manager Pro redirect settings
	 *
     * @since      1.0.1
	 */
	public function kcgred_manager_settings() {
		global $wpdb;
		
        // Verify nonce
        if(isset($_GET['tab'])){
            if (!isset($_GET['_wpnonce']) && !wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'kcgred_tab_nonce')) {
                echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
                return;
            }
        }

        $menu_page = isset($_GET['page']) ? sanitize_text_field( wp_unslash($_GET['page']) ) : 'redirects-manager';
        $tab = isset($_GET['tab']) ? sanitize_text_field( wp_unslash($_GET['tab']) ) : 'redirects-manager';

        // Get pagination parameters
        $current_page = isset($_GET['paged']) ? max(1, intval( wp_unslash($_GET['paged']) )) : 1;
        
        // Get per page from URL or use default
        if (isset($_GET['per_page'])) {
            $per_page = max(1, intval($_GET['per_page']));
        } else {
            $per_page = get_option( 'posts_per_page' );
        }

        
        // Calculate offset
        $offset = ($current_page - 1) * $per_page;
        
		// Get total count with caching
		$cache_key = 'kcgred_total_redirects';
		$total_redirects = wp_cache_get($cache_key, $this->cache_group);
		if (false === $total_redirects) {
			// Get total count
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, caching implemented
			$total_redirects = $wpdb->get_var(
				$wpdb->prepare("SELECT COUNT(*) FROM %i", $this->table_name)
			);
			wp_cache_set($cache_key, $total_redirects, $this->cache_group, 3600);
		}
        
        // Calculate total pages
        $total_pages = ceil($total_redirects / $per_page);

		// Get redirects for current page with caching
		$cache_key_page = 'kcgred_redirects_page_' . $current_page . '_' . $per_page;
		$redirects = wp_cache_get($cache_key_page, $this->cache_group);

		if (false === $redirects) {
			// Get redirects for current page
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, caching implemented
			$redirects = $wpdb->get_results(
				$wpdb->prepare("SELECT * FROM %i ORDER BY id DESC LIMIT %d OFFSET %d", 
					$this->table_name, 
					$per_page, 
					$offset
				)
			);
			wp_cache_set($cache_key_page, $redirects, $this->cache_group, 3600);
		}
		?>
		<!-- Add New Redirect Form -->
		<div class="kcgred-redirects-form-wrapper">
			<div class="redirect-form-container">
				<h2>Add New Redirect</h2>
				<form id="add-redirect-form">
					<div class="kcgred-form-wrapper">
						<div class="kcgred-field">
							<label for="redirect_from">Redirect From</label>
							<div class="kcgred-input">
								<input type="text" id="redirect_from" name="redirect_from" class="regular-text" placeholder="<?php echo esc_url(get_site_url('', 'old-page')); ?>" required>
								<p class="description">
									Enter the old URL <br>
									Example: <code><?php echo esc_url(get_site_url('', 'old-page')); ?></code>
								</p>
							</div>
						</div>
						
						<div class="kcgred-field">
							<label for="redirect_to">Redirect To</label>
							<div class="kcgred-input">
								<input type="text" id="redirect_to"  name="redirect_to" class="regular-text" placeholder="<?php echo esc_url(get_site_url('', 'new-page')); ?> or https://example.com" required>
								<p class="description">Enter the new URL<br> Can be internal (<code><?php echo esc_url(get_site_url('', 'new-page')); ?></code>)<br> or external (<code>https://example.com</code>)</p>
							</div>
						</div>
						
						<div class="kcgred-field">
							<label for="redirect_type">Redirect Type</label>
							<div class="kcgred-input">
								<select id="redirect_type" name="redirect_type">
									<option value="301">301 - Permanent</option>
									<option value="302">302 - Temporary</option>
									<option value="307">307 - Temporary (Preserve Method)</option>
								</select>
								<p class="description">
									<strong>301:</strong> Permanent redirect (SEO-friendly)<br>
									<strong>302:</strong> Temporary redirect <code style="color: #d63638;">Pro</code><br>
									<strong>307:</strong> Temporary redirect (preserves POST data) <code style="color: #d63638;">Pro</code>
								</p>
							</div>
						</div>
						
						<!-- <div class="kcgred-field">
							<label for="redirect_status">Status</label>
							<div class="kcgred-input">
								<input type="checkbox" id="redirect_status" name="redirect_status" value="1" checked> Active
							</div>
						</div> -->
					
						<div class="kcgred-field">
							<label></label>
							<p class="submit">
								<button type="submit" class="button button-primary button-large"> <span class="dashicons dashicons-plus-alt"></span> Add Redirect</button>
							</p>
						</div>
					</div>
				</form>
			</div>
			<div class="kcgred-premium-notice-wrapper">
				<div class="kcgred-redirects-content">
					<p>Upgrade to Redirects Manager Pro and take full control of your site’s redirects. Manage 302 and 307 redirects, delete in bulk, and track 404 errors in real time — all from one powerful dashboard.</p>
					<div class="kcgred-button-wrapper">
						<a href="#" class="button">Buy Pro Version</a>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Redirects List -->
		<div class="redirect-list-container">
			<h2>All Redirects (<?php echo esc_html($total_redirects); ?>)</h2>
			
			<?php if (empty($redirects)): ?>
			<div class="no-redirects">
				<p>No redirects found. Add your first redirect above!</p>
			</div>
			<?php else: ?>
				<div class="alignleft actions bulkactions" style="margin-bottom: 10px;">
					<form method="get">
						<label for="bulk-action-selector-top">Select bulk action <code style="color: #d63638;">Pro</code></label><br/>
						<select name="action" id="bulk-action-selector-top">
							<option value="-1">Bulk actions</option>
							<option value="delete">Delete</option>
						</select>
						<input type="submit" name="bulk_action" id="doaction" class="button action" value="Apply">
					</form>
				</div>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th width="2%" style="padding: 0 2px;"><input type="checkbox" name="kcgred_select_all"></th>
							<th width="25%">Redirect From</th>
							<th width="25%">Redirect To</th>
							<th width="10%">Type</th>
							<th width="8%">Hits</th>
							<th width="10%">Status</th>
							<th width="20%">Actions</th>
						</tr>
					</thead>
					<tbody id="redirects-list">
						<?php foreach ($redirects as $redirect): ?>
						<tr data-id="<?php echo esc_html($redirect->id); ?>" class="<?php echo ($redirect->status == 1) ? 'active' : 'inactive'; ?>">
							<td><input type="checkbox" name="kcgred_redirect_ids[]" value="<?php echo esc_attr($redirect->id); ?>"></td>
							<td class="kcgred-redirect-from">
								<code><?php echo esc_html($redirect->redirect_from); ?></code>
								<?php if (strpos($redirect->redirect_from, '*') !== false): ?>
									<span class="wildcard-badge">Wildcard</span>
								<?php endif; ?>
							</td>
							<td class="kcgred-redirect-to">
								<code><?php echo esc_html($redirect->redirect_to); ?></code>
							</td>
							<td>
								<span class="redirect-type type-<?php echo esc_attr($redirect->redirect_type); ?>">
									<?php echo esc_html($redirect->redirect_type); ?>
								</span>
							</td>
							<td>
								<span class="hits-count"><?php echo number_format($redirect->hits); ?></span>
							</td>
							<td>
								<label class="switch">
									<input type="checkbox" class="toggle-status" data-id="<?php echo esc_attr($redirect->id); ?>" <?php checked($redirect->status, 1); ?>>
									<span class="slider"></span>
								</label>
							</td>
							<td>
								<button class="button button-small edit-redirect" data-id="<?php echo esc_attr($redirect->id); ?>">
									<span class="dashicons dashicons-edit"></span> Edit
								</button>
								<button class="button button-small button-link-delete delete-redirect" data-id="<?php echo esc_attr($redirect->id); ?>">
									<span class="dashicons dashicons-trash"></span> Delete
								</button>
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
		
		<!-- Edit Modal -->
		<div id="edit-modal" class="redirect-modal" style="display:none;">
			<div class="modal-content">
				<span class="close-modal">&times;</span>
				<h2>Edit Redirect</h2>
				<form id="edit-redirect-form">
					<input type="hidden" id="edit_id" name="id">
					
					<table class="form-table">
						<tr>
							<th><label for="edit_redirect_from">Redirect From</label></th>
							<td>
								<input type="text" id="edit_redirect_from" name="redirect_from" class="regular-text" required>
							</td>
						</tr>
						<tr>
							<th><label for="edit_redirect_to">Redirect To</label></th>
							<td>
								<input type="text" id="edit_redirect_to" name="redirect_to" class="regular-text" required>
							</td>
						</tr>
						<tr>
							<th><label for="edit_redirect_type">Redirect Type</label></th>
							<td>
								<select id="edit_redirect_type" name="redirect_type">
									<option value="301">301 - Permanent</option>
									<option value="302">302 - Temporary</option>
									<option value="307">307 - Temporary (Preserve Method)</option>
								</select>
							</td>
						</tr>
					</table>
					
					<p class="submit">
						<button type="submit" class="button button-primary">Update Redirect</button>
						<button type="button" class="button close-modal">Cancel</button>
					</p>
				</form>
			</div>
		</div>
		<?php
	}


	/**
	 * Redirects Manager Pro save redirect data
	 *
     * @since      1.0.1
	 */
	public function kcgred_save_redirect() {
		// Check for nonce security      
        check_ajax_referer('kcgred_ajax_nonce_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        $data = isset($_REQUEST['form-data']) ? array_map('sanitize_text_field', wp_unslash($_REQUEST['form-data'])) : '';
        $id = isset($data['id']) ? intval($data['id']) : 0;
        $redirect_from = isset($data['redirect_from']) ? sanitize_text_field($data['redirect_from']) : '';
        $redirect_to = isset($data['redirect_to']) ? sanitize_text_field($data['redirect_to']) : '';
        $redirect_type = isset($data['redirect_type']) ? sanitize_text_field($data['redirect_type']) : '';
        $status = isset($data['status']) ? sanitize_text_field($data['status']) : '';

        if($id > 0) {
            $status = 1;
        }
        

		$entry_status = $this->plugin_admin->kcgred_check_redirect_status($redirect_from);
		
		if( ($entry_status == 1) && ($id == 0) ) {
			$args = array(
				'status'	=> false,
				'message'  	=> $redirect_from . ' already added',
			);

			wp_send_json($args);
			wp_die();
		}
        
        $data = [
            'redirect_from' => $redirect_from,
            'redirect_to' => $redirect_to,
            'redirect_type' => $redirect_type,
            'status' => $status
        ];
        
        if ($id > 0) {
            // Update existing
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table requires direct query
            $post_id = $wpdb->update($this->table_name, $data, ['id' => $id]);

			// Invalidate cache for this specific redirect
			wp_cache_delete('kcgred_redirect_' . $id, $this->cache_group);
        
			// Invalidate the list cache
			wp_cache_delete('kcgred_all_redirects', $this->cache_group);

			$args = array(
				'status'	=> true,
				'id'		=> $post_id,
				'message'  	=> 'Redirect updated successfully',
			);
        } else {
            // Insert new
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table requires direct query
            $post_id = $wpdb->insert($this->table_name, $data);

			wp_cache_set('kcgred_redirect_' . $post_id, $data, $this->cache_group, HOUR_IN_SECONDS);
        
			// Invalidate the list cache
			wp_cache_delete('kcgred_all_redirects', $this->cache_group);
			
			$args = array(
				'status'	=> true,
				'id'		=> $post_id,
				'message'  	=> 'Redirect created successfully',
			);
        }

		wp_send_json($args);
		wp_die();
    }


	/**
	 * Redirects Manager Pro sanitize array data
	 *
     * @since      1.0.1
	 */
	private function kcgred_sanitize_array($data) {
		if (is_array($data)) {
			return array_map(array($this, 'kcgred_sanitize_array'), $data);
		} else {
			return sanitize_text_field(wp_unslash($data));
		}
	}
	
    

	/**
	 * Redirects Manager Pro delete redirect data
	 *
     * @since      1.0.1
	 */
    public function kcgred_delete_redirect() {
		// Check for nonce security      
        check_ajax_referer('kcgred_ajax_nonce_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : '';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table requires direct query, cache invalidated below
        $wpdb->delete($this->table_name, ['id' => $id]);

		// Invalidate cache for this specific redirect
		wp_cache_delete('kcgred_redirect_' . $id, $this->cache_group);
    
		// Invalidate the list cache
		wp_cache_delete('kcgred_all_redirects', $this->cache_group);
        
		$args = array(
			'status'	=> true,
			'message'  	=> 'Redirect deleted successfully',
		);

		wp_send_json($args);
		wp_die();
    }

	/**
	 * Redirects Manager Pro change redirect status
	 *
     * @since      1.0.1
	 */
    public function kcgred_toggle_redirect() {
        check_ajax_referer('kcgred_ajax_nonce_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : '';
        $status = isset($_REQUEST['status']) ? intval($_REQUEST['status']) : '';
        
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table requires direct query, cache invalidated below
        $wpdb->update($this->table_name, ['status' => $status], ['id' => $id]);

		// Invalidate cache for this specific redirect
		wp_cache_delete('kcgred_redirect_' . $id, $this->cache_group);
    
		// Invalidate the list cache
		wp_cache_delete('kcgred_all_redirects', $this->cache_group);

		if($status == 1) {
			$args = array(
				'status'	=> true,
				'message'  	=> 'Status updated',
			);
		} else {
			$args = array(
				'status'	=> false,
				'message'  	=> 'Status updated',
			);
		}

		wp_send_json($args);
		wp_die();
    }
    
    

	/**
	 * Redirects Manager Pro process redirect
	 *
     * @since      1.0.1
	 */
    public function kcgred_process_redirects() {
        global $wpdb, $wp;


        // Get current URL path
        $current_path = home_url( $wp->request );

        if (is_404()) {
            $agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';

            $this->kcgred_save_redirect_logs($current_path, $agent);
        }
        
		$url_with_slash = trailingslashit($current_path);
		$url_without_slash = untrailingslashit($current_path);

		 // Create a unique cache key based on the URL
		$cache_key = 'kcgred_redirect_' . md5($url_with_slash . $url_without_slash);
		
		// Try to get from cache first
		$redirects = wp_cache_get($cache_key, $this->cache_group);
		
		if (false === $redirects) {
			// Get all active redirects
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query, result cached below
			$redirects = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM %i
					WHERE redirect_from IN (%s, %s)",
					$this->table_name,
					$url_with_slash,
					$url_without_slash
				)
			);

			wp_cache_set($cache_key, $redirects, $this->cache_group, HOUR_IN_SECONDS);
		}

		if(!empty($redirects)) {
			foreach ($redirects as $redirect) {
				$from = rtrim($redirect->redirect_from, '/');
				$to = $redirect->redirect_to;

				
				// Check for exact match
				if ( strtolower(wp_parse_url($current_path, PHP_URL_HOST)) === strtolower(wp_parse_url($redirect->redirect_from, PHP_URL_HOST)) ) {
					$this->kcgred_do_redirect($redirect->id, $to, $redirect->redirect_type);
					return;
				}
	
				
				// Check for wildcard match
				if (strpos($from, '*') !== false) {
					$pattern = str_replace('*', '(.*)', preg_quote($from, '/'));
					$pattern = '/^' . $pattern . '$/';
					
					if (preg_match($pattern, $current_path, $matches)) {
						// Replace wildcards in destination
						$final_to = $to;
						if (count($matches) > 1) {
							for ($i = 1; $i < count($matches); $i++) {
								$final_to = str_replace('$' . $i, $matches[$i], $final_to);
							}
						}
						
						$this->kcgred_do_redirect($redirect->id, $final_to, $redirect->redirect_type);
						return;
					}
				}
			}
		}
    }
    

	/**
	 * Redirects Manager Pro redirect to the destination
	 *
     * @since      1.0.1
	 */
    private function kcgred_do_redirect($id, $url, $type = '301') {
        global $wpdb;
        
        // Update hit count
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Counter increment for custom table, cache invalidated below
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE %i SET hits = hits + 1 WHERE id = %d",
				$this->table_name,
				$id
			)
		);

		// Invalidate cache for this specific redirect since hit count changed
		wp_cache_delete('kcgred_redirect_' . $id, $this->cache_group);
        
        // Ensure absolute URL
        if (strpos($url, 'http') !== 0) {
            $url = home_url($url);
        }

		// Allow all redirect hosts from our database
		add_filter('allowed_redirect_hosts', function($hosts) use ($url) {
			$parsed = wp_parse_url($url);
			if (isset($parsed['host'])) {
				$hosts[] = $parsed['host'];
			}
			return $hosts;
		});
        
        // Perform redirect
		wp_safe_redirect( $url, intval( $type ) );
        exit();
    }
    

	/**
	 * Redirects Manager Pro bulk delete redirect data
	 *
     * @since      1.0.1
	 */
    public function kcgred_delete_selected_redirects_init() {
		// Check for nonce security      
        check_ajax_referer('kcgred_ajax_nonce_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        
        $redirect_ids = isset($_REQUEST['form-data']) ? array_map('sanitize_text_field', wp_unslash($_REQUEST['form-data'])) : array();

        if(!empty($redirect_ids)) {
            foreach($redirect_ids as $id) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table requires direct query, cache invalidated below
                $wpdb->delete($this->table_name, ['id' => $id]);

				// Invalidate cache for this specific redirect
				wp_cache_delete('kcgred_redirect_' . $id, $this->cache_group);
            }
        
			// Invalidate the list cache
			wp_cache_delete('kcgred_all_redirects', $this->cache_group);

            $args = array(
                'status'	=> true,
                'message'  	=> 'Redirect deleted successfully',
            );
        } else {
            $args = array(
                'status'	=> false,
                'message'  	=> 'No redirects selected',
            );
        }

		wp_send_json($args);
		wp_die();
    }
    
    

    

	/**
	 * Redirects Manager Pro update stats
	 *
     * @since      1.0.1
	 */
    public function kcgred_get_redirect_stats() {
        check_ajax_referer('kcgred_ajax_nonce_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;

		// Try to get from cache first
		$cache_key = 'kcgred_total_hits';
		$hits = wp_cache_get($cache_key, $this->cache_group);

		if (false === $hits) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table aggregate query, result cached below
			$hits = $wpdb->get_var( $wpdb->prepare("SELECT SUM(hits) FROM %i", $this->table_name) );
			
			// Cache for 5 minutes (stats don't need to be real-time)
			wp_cache_set($cache_key, $hits, $this->cache_group, 5 * MINUTE_IN_SECONDS);
		}

        $args = array(
            'status'	=> true,
            'total'  	=> $hits ? intval($hits) : 0,
        );

		wp_send_json($args);
		wp_die();
    }



	/**
	 * Redirects Manager Pro save 404 logs data
	 *
     * @since      1.0.1
	 */
    public function kcgred_save_redirect_logs($url, $agent){
        global $wpdb;

        $data = array(
            'url'           => $url,
            'user_agent'    => $agent,
        );

        // Insert new log
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Logging to custom table, no caching needed for write-only logs
        $wpdb->insert($this->table_name_logs, $data);

		// If you have cached log counts or stats, invalidate them
		wp_cache_delete('kcgred_total_logs', $this->cache_group);
		wp_cache_delete('kcgred_recent_logs', $this->cache_group);
    }
}
