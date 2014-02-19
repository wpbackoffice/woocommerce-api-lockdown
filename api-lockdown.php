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
	
	/* API Lockdown Options */
	public $options; 
	
	/* API Lockdown Options Label */
	public $options_label = 'api_lockdown_options';
	
	/* API Enabled Users List */
	public $users;
	
	public function __construct() {
		
		// Activation Hook
		register_activation_hook( __FILE__, array( $this, 'activation_hook' ) );
		
		// Add Advanced Rules link under quantity rules
		add_action( 'admin_menu', array( $this, 'admin_page_init' ) );
		
		// Load Options
		$this->options = get_option( $this->options_label );
		
		// Get a list of all API Users
		$this->get_api_users();		
		
		// Removes API Classes from access based on the user accessing it
		add_filter( 'woocommerce_api_classes', array( $this, 'lockdown_api_resources' ), 1, 1 );
		
		// Filter Requests on a Per User basis
		add_filter( 'woocommerce_api_check_authentication', array( $this, 'lockdown_api_auth' ), 1, 1 );
	}
	
	/*
	*	Adds default option values
	*/	
	public function activation_hook() {

		$options = get_option( 'api_lockdown_options' );
	
		if ( $options == false ) {
		
			$defaults = array (		
				'apil_site_basic'		=> '',
				'apil_site_products'	=> '',
				'apil_site_orders'		=> '',
				'apil_site_customers'	=> '',
				'apil_site_reports'		=> '',
				'apil_site_coupons'		=> '',
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
		add_action( "load-{$slug}", array( $this, 'check_for_update') );

  	}
	
	/**
	*	Advanced Rules Page Content
	*/
	public function admin_page_display() {

		$options = $this->options;

		if ($options == false) {
			$options = array();
		}

		extract($options);

		?>
		<h2>WooCommerce API Lockdown</h2>
		
		<?php if ( isset( $_GET['updated'] ) and $_GET['updated'] == true ): ?>
			<div class="updated"><p><strong>Updated Successfully</strong></p></div>
		<?php endif; ?>
		
		<h3>Check the API sections you'd like to prevent users from accessing.</h3>
		<p>*Note any 'Site Wide Rules' will overwrite any user rule.</p>
		<form method="post" action="<?php admin_url( 'admin.php?page=api-lockdown.php' ); ?>">
			<?php wp_nonce_field( "api-lockdown-admin-page" ); ?>
			
			<table class="wp-list-table widefat fixed posts">
				<thead>
					<tr>
						<th></th>
						<!-- <th>Basic Details</th> -->
						<th>Products</th>
						<th>Orders</th>
						<th>Customers</th>
						<th>Reports</th>
						<th>Coupons</th>
						<th>Access</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<th>Site Wide Rules</th>
						
						<!--
						<td><input type='checkbox' name='apil_site_basic' id='apil_site_basic'
							<?php if ( $apil_site_basic != '' ) echo 'checked'; ?>
						 /></td>
						 -->						
						
						<td><input type='checkbox' name='apil_site_products' id='apil_site_products'
							<?php if ( $apil_site_products != '' ) echo 'checked'; ?>
						 /></td>	
						 
						 <td><input type='checkbox' name='apil_site_orders' id='apil_site_orders'
							<?php if ( $apil_site_orders != '' ) echo 'checked'; ?>
						 /></td>	
						 
						 <td><input type='checkbox' name='apil_site_customers' id='apil_site_customers'
							<?php if ( $apil_site_customers != '' ) echo 'checked'; ?>
						 /></td>	
						 
						 <td><input type='checkbox' name='apil_site_reports' id='apil_site_reports'
							<?php if ( $apil_site_reports != '' ) echo 'checked'; ?>
						 /></td>	
						 
						 <td><input type='checkbox' name='apil_site_coupons' id='apil_site_coupons'
							<?php if ( $apil_site_coupons != '' ) echo 'checked'; ?>
						 /></td>	
						 
						 <td></td>
					</tr>
					
					<?php if ( $this->users != false ): ?>
						<?php foreach( $this->users as $user ): ?>
							<tr>
								<th><?php echo $user->nickname ?></th>
								<!--
<td><input type='checkbox' name='apil_user_basic_<?php echo $user->ID ?>' id='apil_user_basic_<?php echo $user->ID ?>'
									<?php if ( get_user_meta( $user->ID, 'apil_user_basic', true ) != '' ) echo 'checked'; ?>
								 /></td>
-->
								<td><input type='checkbox' name='apil_user_products_<?php echo $user->ID ?>' id='apil_user_products_<?php echo $user->ID ?>'
									<?php if ( get_user_meta( $user->ID, 'apil_user_products', true ) != '' ) echo 'checked'; ?>
								 /></td>
								 <td><input type='checkbox' name='apil_user_orders_<?php echo $user->ID ?>' id='apil_user_orders_<?php echo $user->ID ?>'
									<?php if ( get_user_meta( $user->ID, 'apil_user_orders', true ) != '' ) echo 'checked'; ?>
								 /></td>
								 <td><input type='checkbox' name='apil_user_customers_<?php echo $user->ID ?>' id='apil_user_customers_<?php echo $user->ID ?>'
									<?php if ( get_user_meta( $user->ID, 'apil_user_customers', true ) != '' ) echo 'checked'; ?>
								 /></td>
								 <td><input type='checkbox' name='apil_user_reports_<?php echo $user->ID ?>' id='apil_user_reports_<?php echo $user->ID ?>'
									<?php if ( get_user_meta( $user->ID, 'apil_user_reports', true ) != '' ) echo 'checked'; ?>
								 /></td>
								 <td><input type='checkbox' name='apil_user_coupons_<?php echo $user->ID ?>' id='apil_user_coupons_<?php echo $user->ID ?>'
									<?php if ( get_user_meta( $user->ID, 'apil_user_coupons', true ) != '' ) echo 'checked'; ?>
								 /></td>
								 <td><?php echo get_user_meta( $user->ID, 'woocommerce_api_key_permissions', true ) ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
					
				</tbody>
			</table>
			
			<p class="submit" style="clear: both;">
				<input type="submit" name="Submit"  class="button-primary" value="Update" />
				<input type="hidden" name="apil-advanced-rules-submit" value="Y" />
			</p>
		</form>
		
		<?php	
	}
	
	/*
	*	Check if the API Lockdown Page has been updated
	*/	
  	public function check_for_update() {
	  				
	  	if ( isset( $_POST["apil-advanced-rules-submit"] ) and $_POST["apil-advanced-rules-submit"] == 'Y' ) {
			
			check_admin_referer( "api-lockdown-admin-page" );
			$this->save_settings();
			$url_parameters = '&updated=true';
			wp_redirect( admin_url( 'admin.php?page=api-lockdown.php' . $url_parameters ) );
			exit;
		}
  	}
	
	/*
	*	Save API Lockdown Settings
	*/
	public function save_settings() {
		
		if ( $this->options != false ){
			
			// Create Site Wide Setting Array
			$site_wide_options_label = array(		
				'apil_site_basic',
				'apil_site_products',
				'apil_site_orders',
				'apil_site_customers',
				'apil_site_reports',
				'apil_site_coupons',
			);
			
			// Update Site Wide Setting 
			foreach ( $site_wide_options_label as $set ) {
				
				if ( isset( $_POST[ $set ] ) and $_POST[ $set ] == 'on' ) {
					$this->options[ $set ] = 'on';
				} else {
					$this->options[ $set ] = '';
				}
				
			}
				
			// Update Settings
			$updated = update_option( $this->options_label, $this->options );
			
		}
		
		// Look for and update any user meta
		if ( $this->users != false ) {
			
			// Create User Label Array
			$user_options_labels = array(		
				'apil_user_basic',
				'apil_user_products',
				'apil_user_orders',
				'apil_user_customers',
				'apil_user_reports',
				'apil_user_coupons',
			);
			
			// Loop through all API Users
			foreach ( $this->users as $user ) {
				
				// Loop through all labels
				foreach ( $user_options_labels as $set ) {
					
					$label = $set . '_' . $user->ID; 
					
					// Update the User's meta 
					if ( isset( $_POST[ $label ] ) and $_POST[ $label ] == 'on' ) {
						update_user_meta( $user->ID, $set, 'on' );
					} else {
						update_user_meta( $user->ID, $set, '' );
					}
					
				}
			}
		}
	}
	
	/**
	*	Get all site users with API Credentials
	*
	* 	@return		mixed 	Array of Users or False if none exist
	*/
	public function get_api_users() {
		
		$args = array(
			'meta_query' => array(
				array(
					'key' => 'woocommerce_api_consumer_key',
					'value' => '',
					'compare' => '!='
				)
			)
		); 
		
		$users = get_users( $args );
		
		if ( count( $users ) > 0 ) {
			$this->users = $users;
		} else {
			$this->users = false;
		}
	}
	
	/**
	*	Get all site users with API Credentials
	*
	*	@param 		array 	Array of Included API Classes
	* 	@return		mixed 	Restricted Array of Users 
	*/
	public function lockdown_api_resources( $classes ) {

		extract( $this->options );
		
		// Products
		if ( $apil_site_products == 'on' ) {
			$pos = array_search( 'WC_API_Products', $classes );
			unset( $classes[$pos] );
		}
		
		// Orders
		if ( $apil_site_orders == 'on' ) {
			$pos = array_search( 'WC_API_Orders', $classes );
			unset( $classes[$pos] );
		}
		
		// Customers
		if ( $apil_site_customers == 'on' ) {
			$pos = array_search( 'WC_API_Customers', $classes );
			unset( $classes[$pos] );
		}
		
		// Reports
		if ( $apil_site_reports == 'on' ) {
			$pos = array_search( 'WC_API_Reports', $classes );
			unset( $classes[$pos] );
		}
		
		// Coupons
		if ( $apil_site_coupons == 'on' ) {
			$pos = array_search( 'WC_API_Coupons', $classes );
			unset( $classes[$pos] );
		}

		return $classes;
	}
	
	public function lockdown_api_auth( $user ) {
		
		// Check if user has passed authentication already
		if ( isset( $user->ID ) ) {
		
			// Get Current End Point
			$endpoint = WC()->api->server->path;
			
			// Index Request @todo Limit this request current not working.
			/*
			if ( $endpoint === '/'  ) {
				if ( get_user_meta( $user->ID, 'apil_user_basic', true ) == 'on' )
					return 0;
			}
			*/
			
			// Product Request
			if ( strpos($endpoint, '/products' ) !== false  ) {
				if ( get_user_meta( $user->ID, 'apil_user_products', true ) == 'on' )
					return 0;
			}
			
			// Order Request
			if ( strpos($endpoint, '/order' ) !== false  ) {
				if ( get_user_meta( $user->ID, 'apil_user_orders', true ) == 'on' )
					return 0;
			}
			
			// Product Request
			if ( strpos($endpoint, '/customers' ) !== false  ) {
				if ( get_user_meta( $user->ID, 'apil_user_customers', true ) == 'on' )
					return 0;
			}
			
			// Product Request
			if ( strpos($endpoint, '/reports' ) !== false  ) {
				if ( get_user_meta( $user->ID, 'apil_user_reports', true ) == 'on' )
					return 0;
			}
		
			// Product Request
			if ( strpos($endpoint, '/coupons' ) !== false  ) {
				if ( get_user_meta( $user->ID, 'apil_user_coupons', true ) == 'on' )
					return 0;
			}
			
		} 
		
		return $user;
	}
}

new API_Lockdown();
endif;
