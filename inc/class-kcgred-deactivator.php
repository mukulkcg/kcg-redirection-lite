<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://kingscrestglobal.com/
 * @since      1.0.1
 * @package    redirects-manager
 * @subpackage redirects-manager/inc
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
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

class kcgred_deactivate_init {
	public function __construct() {
		$this->kcgred_unschedule_cleanup_cron();
	}


	public static function kcgred_deactivate() {
        
	}



	/**
	 * Redirects Manager Pro Unschedule the cron job when plugin deactivates
	 *
     * @since      1.0.1
	 */
    public function kcgred_unschedule_cleanup_cron() {
        $timestamp = wp_next_scheduled('kcgred_cleanup_old_logs');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'kcgred_cleanup_old_logs');
        }
    }
}