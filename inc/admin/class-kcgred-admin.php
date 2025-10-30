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
 * @author    Kings Crest Global <info@kingscrestglobal.com>
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

 class kcgred_admin{

	private $table_name;

    private $current_page;

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

		$this->create_table();

        $this->current_page = isset($_GET['page']) ? esc_html($_GET['page']) : 'redirects-manager';

	}


	/**
	 * Create table for Redirects Managerion
	 *
     * @since      1.0.1
	 */
	public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            redirect_from varchar(500) NOT NULL,
            redirect_to varchar(500) NOT NULL,
            redirect_type varchar(10) DEFAULT '301',
            status tinyint(1) DEFAULT 1,
            hits int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY redirect_from (redirect_from(191))
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

	/**
	 * Register the stylesheets for the admin area.
	 *
     * @since      1.0.1
	 */
	public function kcgred_enqueue_admin_styles() {
	    wp_enqueue_style( $this->plugin_name, KCGRED_URL . 'assets/css/redirects-manager-style.css', array(), $this->plugin_version, 'all' );
		wp_enqueue_script( $this->plugin_name, KCGRED_URL . 'assets/js/redirects-manager-script.js', array(), $this->plugin_version, false);

		
		// Localize the script with new data
	    $siteurl = array(
	        'ajax_url' 	=> admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('ajax-nonce')
	    );


	    wp_localize_script( $this->plugin_name, 'object_kcgred', $siteurl );
    }



	/**
	 * Add Redirects Managerion navigation
	 *
     * @since      1.0.1
	 */
	public function kcgred_add_admin_menu() {
        add_menu_page( 
            'Settings',        // Page title
            'Redirects Manager Manager',                // Menu title
            'manage_options',
            'redirects-manager',                // Menu slug
            [$this, 'kcg_redirect_admin_page'], // Callback function
            'dashicons-randomize',
        );

        add_submenu_page( 
            'redirects-manager', 
            'Reports', 
            'Reports', 
            'manage_options', 
            'redirects-manager-reports', 
            [$this, 'kcgred_reports_callback_function'] 
        );

        add_submenu_page( 
            'redirects-manager', 
            'Import / Export', 
            'Import / Export', 
            'manage_options', 
            'redirects-manager-import-export', 
            [$this, 'kcgred_import_export_callback_function'] 
        );

        add_submenu_page( 
            'redirects-manager', 
            'Support', 
            'Support', 
            'manage_options', 
            'redirects-manager-support', 
            [$this, 'kcgred_support_callback_function'] 
        );
	}




	/**
	 * Add Redirects Managerion navigation tab 
	 *
     * @since      1.0.1
	 */
    public function kcgred_navigation_tab() {
		$redirect_link = admin_url( 'admin.php?page=' );

        $nav = array(
            'redirects-manager'                 => 'Settings', 
            'redirects-manager-reports'         => 'Reports', 
            'redirects-manager-import-export'   => 'Import / Export',
            'redirects-manager-support'         => 'Support', 
        );
        ?>
        <div class="kcgred-navigation-wrapper">
            <ul>
                <?php 
                foreach($nav as $key => $menu) {
                    $active = ($this->current_page == $key) ? 'active' : '';
                    echo '<li><a class="'.esc_html($active).'" href="'.esc_url($redirect_link . $key).'">'.esc_html($menu).'</a></li>';
                }
                ?>
            </ul>
        </div>
        <?php
    }



	/**
	 * Redirects Managerion navigation Callback
	 *
     * @since      1.0.1
	 */
	public function kcg_redirect_admin_page(){
		global $wpdb;
        $tableName = $this->table_name;

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
        

        // Get total count
        // $cache_key = 'kcgred_get_total';
        // $total_redirects = wp_cache_get($cache_key);
        $total_redirects = $wpdb->get_var("SELECT COUNT(*) FROM {$tableName}");
        // wp_cache_set($cache_key, $total_redirects);
        
        // Calculate total pages
        $total_pages = ceil($total_redirects / $per_page);

        // Get redirects for current page
        // $redirects_key = 'kcgred_get_redirects';
        // $redirects = wp_cache_get($cache_key);
        $redirects = $wpdb->get_results(
            "SELECT * FROM {$tableName} ORDER BY id DESC LIMIT {$per_page} OFFSET {$offset}"
        );
        // wp_cache_set($redirects_key, $redirects);
		?>
        <div class="wrap kcgred-redirect-manager">
            <h1><span class="dashicons dashicons-randomize"></span> Redirect Manager</h1>
            <?php echo wp_kses_post($this->kcgred_navigation_tab()); ?>
            
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
                                        <option disabled>302 - Temporary</option>
                                        <option disabled>307 - Temporary (Preserve Method)</option>
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
                        <p>Unlock advanced features with Redirects Managers Pro — manage bulk redirects, monitor 404s in real time, and boost your SEO performance.</p>
                        <p>Upgrade now to take full control of your site’s redirections.</p>
                        <div class="kcgred-button-wrapper">
                            <a href="#" class="button">Buy Now</a>
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
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="30%">Redirect From</th>
                                <th width="30%">Redirect To</th>
                                <th width="10%">Type</th>
                                <th width="10%">Hits</th>
                                <th width="10%">Status</th>
                                <th width="15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="redirects-list">
                            <?php foreach ($redirects as $redirect): ?>
                                <tr data-id="<?php echo esc_attr($redirect->id); ?>" class="<?php echo ($redirect->status == 1) ? 'active' : 'inactive'; ?>">
                                    <td><?php echo esc_html($redirect->id); ?></td>
                                    <td>
                                        <code><?php echo esc_html($redirect->redirect_from); ?></code>
                                        <?php if (strpos($redirect->redirect_from, '*') !== false): ?>
                                            <span class="wildcard-badge">Wildcard</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
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
                                            <input type="checkbox" 
                                                   class="toggle-status" 
                                                   data-id="<?php echo esc_attr($redirect->id); ?>"
                                                   <?php checked($redirect->status, 1); ?>>
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
                                <?php echo wp_kses_post($this->kcgred_get_pagination_links(esc_html($current_page), esc_html($total_pages))); ?>
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
        </div>
		<?php
	}





	/**
	 * Redirects Managerion add view details button on plugin
	 *
     * @since      1.0.1
	 */
    public function kcgred_add_view_details_button( $links, $file ) {
        $plugin_host = $this->kcgred_is_plugin_from_wporg($file);
        
        if ( (strpos( $file, 'redirects-manager.php' ) !== false) && ($plugin_host === false) ) {
            $links[] = '<a href="https://kingscrestglobal.com/redirects-manager" target="_blank">View details</a>';
        }

        return $links;
    }





	/**
	 * Redirects Managerion check plugin from wp.org or custom hosted
	 *
     * @since      1.0.1
	 */
    public function kcgred_is_plugin_from_wporg( $plugin_file ) {
        $update_plugins = get_site_transient( 'update_plugins' );
    
        if ( !empty($update_plugins->no_update[$plugin_file]) ) {
            if( !empty($update_plugins->no_update[$plugin_file]->url) ) {
                return true; // ✅ Installed from WordPress.org
            }
        }
    
        return false; // ❌ Installed manually or custom source
    }



	/**
	 * Redirects Managerion add pagination link function
	 *
     * @since      1.0.1
	 */
    private function kcgred_get_pagination_links($current_page, $total_pages) {
        $links = '';
        $base_url = admin_url('admin.php?page=redirects-manager&tab=' . $current_page);
        
        // Add per_page parameter if set
        if (isset($_GET['per_page'])) {
            $base_url .= '&per_page=' . intval($_GET['per_page']);
        }
        
        // Add search parameter if set
        if (isset($_GET['s']) && !empty($_GET['s'])) {
            $base_url .= '&s=' . urlencode($_GET['s']);
        }
        
        // First page
        if ($current_page > 1) {
            $links .= '<a class="first-page button" href="' . esc_url($base_url . '&paged=1') . '">&laquo;</a>';
        } else {
            $links .= '<span class="first-page button disabled">&laquo;</span>';
        }
        
        // Previous page
        if ($current_page > 1) {
            $links .= '<a class="prev-page button" href="' . esc_url($base_url . '&paged=' . ($current_page - 1)) . '">&lsaquo;</a>';
        } else {
            $links .= '<span class="prev-page button disabled">&lsaquo;</span>';
        }
        
        // Page numbers
        $range = 2; // Show 2 pages on each side of current page
        $start = max(1, $current_page - $range);
        $end = min($total_pages, $current_page + $range);
        
        // Show first page if not in range
        if ($start > 1) {
            $links .= '<a class="button" href="' . esc_url($base_url . '&paged=1') . '">1</a>';
            if ($start > 2) {
                $links .= '<span class="button disabled">...</span>';
            }
        }
        
        // Page number links
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $current_page) {
                $links .= '<span class="button current">' . $i . '</span>';
            } else {
                $links .= '<a class="button" href="' . esc_url($base_url . '&paged=' . $i) . '">' . $i . '</a>';
            }
        }
        
        // Show last page if not in range
        if ($end < $total_pages) {
            if ($end < $total_pages - 1) {
                $links .= '<span class="button disabled">...</span>';
            }
            $links .= '<a class="button" href="' . esc_url($base_url . '&paged=' . $total_pages) . '">' . $total_pages . '</a>';
        }
        
        // Next page
        if ($current_page < $total_pages) {
            $links .= '<a class="next-page button" href="' . esc_url($base_url . '&paged=' . ($current_page + 1)) . '">&rsaquo;</a>';
        } else {
            $links .= '<span class="next-page button disabled">&rsaquo;</span>';
        }
        
        // Last page
        if ($current_page < $total_pages) {
            $links .= '<a class="last-page button" href="' . esc_url($base_url . '&paged=' . $total_pages) . '">&raquo;</a>';
        } else {
            $links .= '<span class="last-page button disabled">&raquo;</span>';
        }
        
        return $links;
    }



	/**
	 * Redirects Managerion redirect reports callback function
	 *
     * @since      1.0.1
	 */
    public function kcgred_reports_callback_function(){
		global $wpdb;

        // Get all redirects
        $redirects = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY id DESC");
        
        // Get statistics
        $total_redirects = count($redirects);
        $active_redirects = count(array_filter($redirects, function($r) { return $r->status == 1; }));
        $total_hits = array_sum(array_column($redirects, 'hits'));
        ?>
        <div class="wrap kcgred-redirect-manager">
            <h1><span class="dashicons dashicons-chart-area"></span> Reports</h1>

            <?php echo wp_kses_post($this->kcgred_navigation_tab()); ?>

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
        </div>
        <?php
    }




	/**
	 * Redirects Managerion redirect support page callback function
	 *
     * @since      1.0.1
	 */
    public function kcgred_support_callback_function(){
        ?>
        <div class="wrap kcgred-redirect-manager">
            <h1><span class="dashicons dashicons-microphone"></span> Support</h1>

            <?php echo wp_kses_post($this->kcgred_navigation_tab()); ?>
            <div class="kcgred-tab-section-wrapper">
                This feature is coming soon!
            </div>
        </div>
        <?php
    }

	/**
	 * Redirects Managerion redirect Import / Export callback function
	 *
     * @since      1.0.1
	 */
    public function kcgred_import_export_callback_function(){
        ?>
        <div class="wrap kcgred-redirect-manager">
            <h1><span class="dashicons dashicons-plugins-checked"></span> Import / Export</h1>
            
            <?php echo wp_kses_post($this->kcgred_navigation_tab()); ?>
            
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
                        <p class="description">CSV format: redirect_from,redirect_to,redirect_type</p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }





	/**
	 * Redirects Managerion save redirect data
	 *
     * @since      1.0.1
	 */
	public function kcgred_save_redirect() {
		// Check for nonce security      
        check_ajax_referer('ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        $data = isset($_REQUEST['form-data']) ? $_REQUEST['form-data'] : '';
        $id = isset($data['id']) ? intval($data['id']) : 0;
        $redirect_from = isset($data['redirect_from']) ? sanitize_text_field($data['redirect_from']) : '';
        $redirect_to = isset($data['redirect_to']) ? sanitize_text_field($data['redirect_to']) : '';
        $redirect_type = isset($data['redirect_type']) ? sanitize_text_field($data['redirect_type']) : '';
        $status = isset($data['status']) ? sanitize_text_field($data['status']) : '';

        if($id > 0) {
            $status = 1;
        }
        

		$entry_status = $this->kcgred_check_redirect_status($redirect_from);
		
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
            $post_id = $wpdb->update($this->table_name, $data, ['id' => $id]);

			$args = array(
				'status'	=> true,
				'id'		=> $post_id,
				'message'  	=> 'Redirect updated successfully',
			);
        } else {
            // Insert new
            $post_id = $wpdb->insert($this->table_name, $data);
			
			$args = array(
				'status'	=> true,
				'id'		=> $post_id,
				'message'  	=> 'Redirect created successfully',
			);
        }

        // wp_cache_delete( 'kcgred_get_total' );
        // wp_cache_delete( 'kcgred_get_redirects' );

		wp_send_json($args);
		wp_die();
    }


	public function kcgred_check_redirect_status( $url ){
        global $wpdb;
		$status = $wpdb->query( $wpdb->prepare("SELECT * FROM $this->table_name WHERE redirect_from = %s", $url) );

		return $status;
	}
    

	/**
	 * Redirects Managerion delete redirect data
	 *
     * @since      1.0.1
	 */
    public function kcgred_delete_redirect() {
		// Check for nonce security      
        check_ajax_referer('ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        
        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : '';
        $wpdb->delete($this->table_name, ['id' => $id]);
        
		$args = array(
			'status'	=> true,
			'message'  	=> 'Redirect deleted successfully',
		);

		wp_send_json($args);
		wp_die();
    }
    

	/**
	 * Redirects Managerion change redirect status
	 *
     * @since      1.0.1
	 */
    public function kcgred_toggle_redirect() {
        check_ajax_referer('ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        
        $id = intval($_REQUEST['id']);
        $status = intval($_REQUEST['status']);
        
        $wpdb->update($this->table_name, ['status' => $status], ['id' => $id]);

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
	 * Redirects Managerion process redirect
	 *
     * @since      1.0.1
	 */
    public function kcgred_process_redirects() {
        global $wpdb, $wp;
        
        // Get current URL path
        $current_path = home_url( $wp->request );
        
        // Get all active redirects
        $redirects = $wpdb->get_results("SELECT * FROM {$this->table_name} WHERE status = 1 ORDER BY id ASC");

        
        foreach ($redirects as $redirect) {
            $from = rtrim($redirect->redirect_from, '/');
            $to = $redirect->redirect_to;

            
            // Check for exact match
            if ($current_path === $from) {
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
    

	/**
	 * Redirects Managerion redirect to the destination
	 *
     * @since      1.0.1
	 */
    private function kcgred_do_redirect($id, $url, $type = '301') {
        global $wpdb;
        
        // Update hit count
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_name} SET hits = hits + 1 WHERE id = %d",
            $id
        ));
        
        // Ensure absolute URL
        if (strpos($url, 'http') !== 0) {
            $url = home_url($url);
        }
        
        // Perform redirect
        wp_redirect($url, intval($type));
        exit;
    }
    

	/**
	 * Redirects Managerion export redirects
	 *
     * @since      1.0.1
	 */
    public function kcgred_export_redirects() {
        check_ajax_referer('ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $redirects = $wpdb->get_results("SELECT * FROM {$this->table_name}", ARRAY_A);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="redirects-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['redirect_from', 'redirect_to', 'redirect_type', 'status', 'hits']);
        
        foreach ($redirects as $redirect) {
            fputcsv($output, [
                $redirect['redirect_from'],
                $redirect['redirect_to'],
                $redirect['redirect_type'],
                $redirect['status'],
                $redirect['hits']
            ]);
        }
        
        fclose($output);
        exit;
    }
    

	/**
	 * Redirects Managerion import redirects
	 *
     * @since      1.0.1
	 */
    public function kcgred_import_redirects() {
        check_ajax_referer('ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        if (!isset($_FILES['csv_file'])) {
            wp_send_json_error('No file uploaded');
        }
        
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        
        if (!$handle) {
            wp_send_json_error('Could not read file');
        }
        
        global $wpdb;
        $imported = 0;
        
        // Skip header row
        fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= 3) {
                
                $entry_status = $this->kcgred_check_redirect_status( $data[0] );
                
                if($entry_status < 1) {
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
        
        fclose($handle);
        
        wp_send_json_success("Imported $imported redirects");
    }

    

	/**
	 * Redirects Managerion update stats
	 *
     * @since      1.0.1
	 */
    public function kcgred_get_redirect_stats() {
        check_ajax_referer('ajax-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
		$hits = $wpdb->get_var( $wpdb->prepare("SELECT SUM(hits) FROM $this->table_name") );

        $args = array(
            'status'	=> true,
            'total'  	=> $hits,
        );

		wp_send_json($args);
		wp_die();
    }
 }