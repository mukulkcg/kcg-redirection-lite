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

 class kcgred_redirect_supports {

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

		$this->plugin_name = $plugin_name;
		$this->plugin_version = $plugin_version;

		$this->plugin_admin = new kcgred_admin( $plugin_name, $plugin_version );
	}



	/**
	 * Redirects Manager Pro redirect support page callback function
	 *
     * @since      1.0.1
	 */
    public function kcgred_support_callback_function(){
        // Verify nonce
        if(isset($_GET['tab'])){
            if (!isset($_GET['_wpnonce']) && !wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'kcgred_tab_nonce')) {
                echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
                return;
            }
        }
        ?>
        <div class="kcgred-tab-section-wrapper">
            <div class="kcgred-support-wrapper">
                <div class="kcgred-support-item">
                    <div class="kcgred-support-item-img">
                        <img src="<?php echo esc_url(KCGRED_URL . 'assets/img/suggest-feature.png'); ?>" alt="<?php esc_attr_e('Suggest a Feature', 'redirects-manager'); ?>" />
                    </div>
                    <div class="kcgred-support-item-content">
                        <h3><?php esc_html_e('Suggest a Feature', 'redirects-manager') ?></h3>
                        <p><?php esc_html_e('If you have a feature idea, integration request, addon concept, or any improvement in mind for Captura, we’d love to hear it. Your feedback helps shape the product, and we genuinely appreciate your input.', 'redirects-manager') ?></p>
                    </div>
                    <div class="kcgred-support-item-btn">
                        <a href="mailto:salman@kingscrestglobal.com" class="button button-primary"><?php esc_html_e('Suggest a Feature', 'redirects-manager'); ?></a>
                    </div>
                </div>
                <div class="kcgred-support-item">
                    <div class="kcgred-support-item-img">
                        <img src="<?php echo esc_url(KCGRED_URL . 'assets/img/contact-us.png'); ?>" alt="<?php esc_attr_e('Contact Us', 'redirects-manager'); ?>" />
                    </div>
                    <div class="kcgred-support-item-content">
                        <h3><?php esc_html_e('Contact Us', 'redirects-manager') ?></h3>
                        <p><?php esc_html_e('If you experience any issues while using our plugin, you can contact us using the button below.', 'redirects-manager') ?></p>
                    </div>
                    <div class="kcgred-support-item-btn">
                        <a href="mailto:salman@kingscrestglobal.com" class="button button-primary"><?php esc_html_e('Contact Us', 'redirects-manager'); ?></a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}