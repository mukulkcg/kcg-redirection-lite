<?php
/**
 * Fired during plugin core functions
 *
 * @link       https://kingscrestglobal.com/inc/admin/
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
 * @subpackage redirects-manager/inc/admin/
 * @author    Kings Crest Global <info@kingscrestglobal.com>
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

 class kcgred_admin{

	private $table_name;
	private $table_name_logs;

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
        $this->table_name_logs = $wpdb->prefix . 'kcgred_redirects_logs';

		$this->plugin_name = $plugin_name;
		$this->plugin_version = $plugin_version;

		$this->create_table();

	}


	/**
	 * Create table for Redirects Manager Pro
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

        $sql_logs = "CREATE TABLE IF NOT EXISTS {$this->table_name_logs} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            url TEXT NOT NULL,
            user_agent TEXT NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql_logs);
    }


	/**
	 * Register the stylesheets for the admin area.
	 *
     * @since      1.0.1
	 */
	public function kcgred_enqueue_admin_styles() {
	    wp_enqueue_style( $this->plugin_name, KCGRED_URL . 'assets/css/redirects-manager-style.css', array(), $this->plugin_version, 'all' );

        wp_enqueue_script( 'chartjs', KCGRED_URL . 'assets/js/chart.js', array(), $this->plugin_version, false);
		wp_enqueue_script( $this->plugin_name, KCGRED_URL . 'assets/js/redirects-manager-script.js', array(), $this->plugin_version, false);

        wp_enqueue_script( 'kcgred-report', KCGRED_URL . 'assets/js/kcgred-report.js', array( 'chartjs' ), $this->plugin_version, false);

		
		// Localize the script with new data
	    $siteurl = array(
	        'ajax_url' 	=> admin_url('admin-ajax.php'),
			'nonce'     => wp_create_nonce('kcgred_ajax_nonce_action')
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
            'Redirects Manager',                // Menu title
            'manage_options',
            'redirects-manager',                // Menu slug
            [$this, 'kcg_redirect_admin_page'], // Callback function
            KCGRED_URL . 'assets/img/128x128.png',
        );

	}






	/**
	 * Redirects Manager Pro navigation Callback
	 *
     * @since      1.0.1
	 */
	public function kcg_redirect_admin_page(){
        $nonce = wp_create_nonce('kcgred_tab_nonce');
            
        // Verify nonce
        if(isset($_GET['tab'])){
            if (!isset($_GET['_wpnonce']) && !wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'kcgred_tab_nonce')) {
                echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
                return;
            }
        }

        $nav = array(
            'redirects-manager'                 => 'Settings', 
            'redirects-manager-reports'         => 'Reports', 
            'redirects-manager-error-logs'      => '404 Logs', 
            'redirects-manager-import-export'   => 'Import / Export',
            'redirects-manager-support'         => 'Support', 
        );

        $navigation = '';

        $menu_page = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'redirects-manager';

        $navigation .= '<div class="kcgred-navigation-wrapper">';
            $navigation .= '<ul>';
                foreach($nav as $key => $menu) {
                    $active = ($menu_page == $key) ? 'active' : '';
                    $navigation .= '<li><a class="'.esc_html($active).'" href="'.esc_url(add_query_arg( array( 'page' => 'redirects-manager', 'tab' => $key, '_wpnonce' => $nonce ), admin_url( 'admin.php' ) )).'">'.esc_html($menu).'</a></li>';
                }
            $navigation .= '</ul>';
        $navigation .= '</div>';
		?>
        <div class="wrap kcgred-redirect-manager">
            <h1><?php echo esc_html($nav[$menu_page]); ?></h1>
            <?php 
            // Navigation
            echo wp_kses_post($navigation);

            

            switch($menu_page) {

                case 'redirects-manager-reports';
                    $redirect_reports = new kcgred_redirect_reports( $this->plugin_name, $this->plugin_version );
                    $redirect_reports->kcgred_redirect_reports_settings();
                    break;
                case 'redirects-manager-error-logs';
                    $redirect_logs = new kcgred_error_logs( $this->plugin_name, $this->plugin_version );
                    $redirect_logs->kcgred_error_logs_callback_function();
                    break;
                case 'redirects-manager-import-export';
                    $imports = new kcgred_redirect_import_export( $this->plugin_name, $this->plugin_version );
                    $imports->kcgred_import_export_callback_function();
                    break;
                case 'redirects-manager-support';
                    $supports = new kcgred_redirect_supports( $this->plugin_name, $this->plugin_version );
                    $supports->kcgred_support_callback_function();
                    break;
                default:
                    $redirect_settings = new kcgred_redirect_settings( $this->plugin_name, $this->plugin_version );
                    $redirect_settings->kcgred_manager_settings();
                    break;

            }
            ?>
        </div>
		<?php
	}



	/**
	 * Redirects Manager Pro add view details button on plugin
	 *
     * @since      1.0.1
	 */
    public function kcgred_add_view_details_button( $links, $file ) {
        $plugin_host = $this->kcgred_is_plugin_from_wporg($file);
        
        if ( (strpos( $file, 'redirects-manager.php' ) !== false) && ($plugin_host === false) ) {
            $links[] = '<a href="https://kingscrestglobal.com/kcg-redirects" target="_blank">View details</a>';
        }

        return $links;
    }





	/**
	 * Redirects Manager Pro check plugin from wp.org or custom hosted
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
	 * Redirects Manager Pro add pagination link function
	 *
     * @since      1.0.1
	 */
    private function kcgred_get_pagination_links( $page, $tab, $current_page, $total_pages ) {
        $nonce = wp_create_nonce('kcgred_tab_nonce');
            
        // Verify nonce
        if(isset($_GET['tab'])){
            if (!isset($_GET['_wpnonce']) && !wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'kcgred_tab_nonce')) {
                echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
                return;
            }
        }

        $links = '';
        $base_url = admin_url('admin.php?page='.esc_html($page).'&tab=' . $tab);
        
        // Add per_page parameter if set
        if (isset($_GET['per_page'])) {
            $base_url .= '&per_page=' . intval($_GET['per_page']);
        }

        if (isset($nonce)) {
            $base_url .= '&_wpnonce=' . sanitize_key($nonce);
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
	 * Redirects Manager Pro return pagination
	 *
     * @since      1.0.1
	 */
    public function kcgred_get_paginations( $page, $tab, $current_page, $total_pages ) {
        return $this->kcgred_get_pagination_links( $page, $tab, $current_page, $total_pages );
    }





	/**
	 * Redirects Manager Pro redirect status exists or not
	 *
     * @since      1.0.1
	 */
	public function kcgred_check_redirect_status( $url ){
        global $wpdb;

        $cache_key = 'redirect_from_' . md5( $sanitized_url );
        $status = wp_cache_get( $cache_key, $this->cache_group );

        if ( false === $status ) {
            // Data not found in cache, perform database query

            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Querying a custom plugin table where no higher-level API exists.
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching -- Caching logic is implemented above this query.
            $status = $wpdb->query( 
                $wpdb->prepare(
                    "SELECT * FROM %i WHERE redirect_from = %s", 
                    $this->table_name,
                    $url
                )
            );
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery

            // 4. Store the result in cache
            // Cache for a reasonable amount of time, e.g., 1 hour (3600 seconds).
            // Adjust the duration based on how often your redirects might change.
            wp_cache_set( $cache_key, $status, $this->cache_group, 3600 );
        }

		return $status;
	}

    


	/**
	 * Redirects Manager Pro get error logs table
	 *
     * @since      1.0.1
	 */
    public function kcgred_get_table_name_logs() {
        return $this->table_name_logs;
    }

 }