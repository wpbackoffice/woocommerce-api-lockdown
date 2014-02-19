<?php
/*
Plugin Name: WooCommerce API Lockdown
Plugin URI: http://www.wpbackoffice.com/plugins/woocommerce-api-lockdown/
Description: Restrict the read/write access for those you've given API access to.
Version: 1.0.0
Author: WP BackOffice
Author URI: http://www.wpbackoffice.com
*/ 

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'API_Lockdown' ) ) :

class API_Lockdown {
	
	public function __construct() {
		
		// Activation Hook
		register_activation_hook( __FILE__, array( $this, 'activation_hook' ) );
		
		// Add Advanced Rules link under quantity rules
		add_action( 'admin_menu', array( $this, 'admin_page_init' ) );
		
	}
	
	/*
	*	Adds default option values
	*/	
	public function activation_hook() {

		$options = get_option( 'api_lockdown_options' );
	
		if ( $options == false ) {
		
			$defaults = array (		
				'api_lockdown_active'	=> '',
			);
		
			add_option( 'api_lockdown_options', $defaults, '', false );
		}

	}
	
	/*
	*	Adds 'API Lockdown' page under the WooCommerce tab
	*/	
	public function admin_page_init() {
		
		$slug = add_submenu_page(
			'woocommerce', 
			'API Lockdown', 
			'API Lockdown', 
			'edit_posts', 
			basename(__FILE__), 
			array( $this, 'admin_page_display')
		);
		
				
		// Load action, checks for posted form
		// add_action( "load-{$slug}", array( $this, 'admin_page') );

	}
	
	/**
	*	Advanced Rules Page Content
	*/
	public function admin_page_display() {
		//delete_option( 'api-lockdown-options' );
		//delete_option( 'api_lockdown_options' );
		$options = get_option( 'api_lockdown_options' );

		if ($options == false) {
			$options = array();
		}
		
		extract($options);
		var_dump($options);
		?>
		<h2>Advanced Rules</h2>
		<form method="post" action="<?php admin_url( 'admin.php?page=api-lockdown.php' ); ?>">
			<?php wp_nonce_field( "api-lockdown-admin-page" ); ?>
			
			<table class="form-table">
				<tr>
					<th>Activate Site Wide Rule?</th>
					<td><input type='checkbox' name='ipq_site_rule_active' id='ipq_site_rule_active'
						<?php if ( '' != '' ) echo 'checked'; ?>
					 /></td>
				</tr>

			</table>
			
			<p class="submit" style="clear: both;">
				<input type="submit" name="Submit"  class="button-primary" value="Update Settings" />
				<input type="hidden" name="ipq-advanced-rules-submit" value="Y" />
			</p>
		</form>
		
		<?php	
	}
}

new API_Lockdown();
endif;
