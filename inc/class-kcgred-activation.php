<?php
/**
 * Fired during plugin activation
 *
 * @link       https://kingscrestglobal.com/
 * @since      1.0.1
 * @package    redirects-manager
 * @subpackage redirects-manager/inc
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
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

class kcgred_activations_init{
	public function __construct() {
		$this->kcgred_schedule_cleanup_cron();
	}

	public static function kcgred_activate() {
		
	}


	/**
	 * Redirects Manager Pro Schedule the cron job on plugin activation
	 *
     * @since      1.0.1
	 */
	public static function kcgred_schedule_cleanup_cron() {
        if (!wp_next_scheduled('kcgred_cleanup_old_logs')) {
            wp_schedule_event(time(), 'kcgred_every_three_days', 'kcgred_cleanup_old_logs');
        }
	}
}