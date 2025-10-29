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
	public static function kcgred_deactivate() {

	}
}