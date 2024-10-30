<?php
 /**
  * Plugin Name: MonstaManagement
  * Plugin URI: https://www.monstamanagement.com/
  * Description: To Import and sell MonstaManagement products
  * Version: 1.1.36
  * Requires at least: 5.2
  * Tested up to: 5.3
  * Requires PHP: 7.2
  * Author: MonstaManagement
  * Author URI: https://www.monstamanagement.com/
  */
// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}
set_time_limit(0);
define( 'TROPHYMONSTA_VERSION', '1.1.37' );
define( 'TROPHYMONSTA_MINIMUM_WP_VERSION', '5.0' );
define( 'TROPHYMONSTA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TROPHYMONSTA_PLUGIN_URL',plugin_dir_url( __FILE__ ) );
define( 'TROPHYMONSTA_API_URL', 'https://grr.monstamanagement.com/' );
define( 'TROPHYMONSTA_DEBUG', true );

require_once( TROPHYMONSTA_PLUGIN_DIR . 'class.trophymonsta.php' );
//require_once( TROPHYMONSTA_PLUGIN_DIR . 'class.monstaquote.php' );
require_once( TROPHYMONSTA_PLUGIN_DIR . 'class.monstafrontend.php' );
require_once( TROPHYMONSTA_PLUGIN_DIR . 'class.monstaengravings.php' );
require_once( TROPHYMONSTA_PLUGIN_DIR . 'class.monstashipping.php' );
require_once( TROPHYMONSTA_PLUGIN_DIR . 'class.monstaexcell.php' );

//CUSTOM EMAIL
$WOOCOMMERCE_DIR = dirname( plugin_dir_path( __FILE__ ) );
$WOOCOMMERCE_DIR_EMAIL = $WOOCOMMERCE_DIR."/woocommerce/includes/emails/";
$WOOCOMMERCE_DIR_INCLUDES = $WOOCOMMERCE_DIR."/woocommerce/includes/";
$WOOCOMMERCE_DIR_LEGACY = $WOOCOMMERCE_DIR."/woocommerce/includes/legacy/";
$WOOCOMMERCE_DIR_ABSTRACTS = $WOOCOMMERCE_DIR."/woocommerce/includes/abstracts/";
if( is_dir( $WOOCOMMERCE_DIR_ABSTRACTS ) ){
	require_once($WOOCOMMERCE_DIR_ABSTRACTS.'abstract-wc-settings-api.php');
}
if( is_dir( $WOOCOMMERCE_DIR_EMAIL ) ){
	require_once($WOOCOMMERCE_DIR_EMAIL.'class-wc-email.php');
}
if( is_dir( $WOOCOMMERCE_DIR_INCLUDES ) ){
	require_once($WOOCOMMERCE_DIR_INCLUDES.'class-wc-emails.php');
	require_once($WOOCOMMERCE_DIR_INCLUDES.'class-wc-structured-data.php');
}

require_once(TROPHYMONSTA_PLUGIN_DIR.'class-trophy-custom-customer-email.php');
require_once(TROPHYMONSTA_PLUGIN_DIR.'class-trophy-custom-admin-email.php');
//require_once(TROPHYMONSTA_PLUGIN_DIR.'class.trophy-custom-template-order.php');
//CUSTOM EMAIL END

//CUSTOM GALLERY
require_once(TROPHYMONSTA_PLUGIN_DIR.'class.trophy-custom-image-gallery.php');
//END
register_activation_hook( __FILE__, array( 'Trophymonsta', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Trophymonsta', 'plugin_deactivation' ) );

add_action( 'init', array( 'Trophymonsta', 'init' ) );
add_action ( 'init', array( 'Trophymonsta', 'register_taxonomy' ) );
add_action ( 'init', array( 'Trophymonsta', 'get_accessories_type' ) );
//add_action( 'init', array( 'Monstaquote', 'init' ) );
add_action( 'init', array( 'Monstaengravings', 'init' ) );
add_action( 'init', array( 'Monstashipping', 'init' ) );
add_action( 'init', array( 'Monstafrontend', 'init' ) );
add_action('init', array( 'Monstafrontend', 'monstamanagement_start_session'), 1 );
add_filter( 'cron_schedules', array( 'Trophymonsta', 'monstamanagement_cron_schedules' ));
add_action('wp_logout', array( 'Monstafrontend', 'monstamanagement_clear_session'));
remove_action( 'woocommerce_email_order_details', array( 'WC_Emails', 'order_details' ), 10, 4 );

if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	require_once( TROPHYMONSTA_PLUGIN_DIR . 'class.trophymonsta-admin.php' );
	add_action( 'init', array( 'Trophymonsta_Admin', 'init' ) );
}

/**
 * Check if WooCommerce is active
 **/
function monstamanagement_check_if_woo_is_active(){
	if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		$class = "error";
		$message = __("MonstaManagement plugin requires Woocommerce plugin to be activated.", 'trophymonsta');
		echo"<div class=\"$class\"> <p>$message</p></div>";
	}

}
add_action('admin_init','monstamanagement_check_if_woo_is_active');


include_once dirname( __FILE__ ) . '/../woocommerce/woocommerce.php';

/**
 * Rest route for import cron and deactivation
 **/
add_action( 'rest_api_init', 'my_register_route' );
function my_register_route() {
	register_rest_route( 'monsta', 'sync', array(
	                'methods' => 'GET',
	                'callback' => 'sync_execute_import_cron',
	            )
	        ); // /wp-json/monsta/sync?key=XXXXX
	register_rest_route( 'monsta', 'resync', array(
	                'methods' => 'GET',
	                'callback' => 'resync_execute_import_cron',
	            )
	        ); // /wp-json/monsta/resync?key=XXXXX
	register_rest_route( 'monsta', 'deactivation', array(
	                'methods' => 'GET',
	                'callback' => 'execute_deactivation_cron',
	            )
	        ); // /wp-json/monsta/deactivation?key=XXXXX
	register_rest_route( 'monsta', 'activation', array(
	                'methods' => 'GET',
	                'callback' => 'execute_activation_cron',
	            )
	        ); // /wp-json/monsta/activation?key=XXXXX
  register_rest_route( 'monsta', 'recall', array(
                'methods' => 'GET',
                'callback' => 'execute_cron',
            )
        ); // /wp-json/monsta/recall?key=XXXXX
	register_rest_route( 'monsta', 'syncstatus', array(
                'methods' => 'GET',
                'callback' => 'syncstatus',
            )
        ); // /wp-json/monsta/syncstatus?key=XXXXX
	register_rest_route( 'monsta', 'syncaccessoriestype', array(
                'methods' => 'GET',
                'callback' => 'syncaccessoriestype',
            )
        ); // /wp-json/monsta/syncaccessoriestype?key=XXXXX
}

function execute_cron() {
    if (isset($_GET['key']) && esc_attr($_GET['key']) == get_option( 'trophymonsta_api_key' )) {
		$response = new \WP_REST_Response(array('status' => 'Cron running successfully.'));
		$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').'IF wp-json/monsta/recall'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		return rest_ensure_response($response);
	} else {
		$response = new \WP_REST_Response(array('status' => 'Please check your MonstaManagement key.'));
		$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').'ELSE wp-json/monsta/recall'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		return rest_ensure_response($response);
	}
}
function resync_execute_import_cron() {
	global $wpdb;
	if (isset($_GET['key']) && esc_attr($_GET['key']) == get_option( 'trophymonsta_api_key' )) {
		
		$last_sync_status = get_option( 'monstamanagement_sync_status' );
		if ($last_sync_status) {
			update_option( 'monstamanagement_sync_status', 'grrstart');
		} else {
			add_option( 'monstamanagement_sync_status', 'grrstart');
		}
		
		$cron_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` LIKE 'cron' AND `option_value` LIKE '%trophymonsta_sync_status%'" );
	    if ( null === $cron_exists) {
	        error_log(date('Y-m-d H:i:s').' resync_execute_import_cron trophymonsta_sync_status :'.$last_sync_status.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		    wp_schedule_event(time(), '1min', 'trophymonsta_sync_status');
	    }
		
		$response = new \WP_REST_Response(array('status' => 'Data Resynchronise will start few within mins.', 'error' => ''));
		$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').' Data Resynchronise will start few within mins.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			if ( function_exists('w3tc_flush_url') ) {
			w3tc_flush_url( site_url('wp-json/monsta/sync') );
		  }
		return rest_ensure_response($response);
		
		/*wp_clear_scheduled_hook('trophymonsta_catgroup_import');
		wp_clear_scheduled_hook('trophymonsta_product_import');
		wp_schedule_event(time(), 'daily', 'trophymonsta_catgroup_import');

		global $wpdb;
		//$cron_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` LIKE 'cron' AND (`option_value` LIKE '%trophymonsta_catgroup_import%')" );
		//if ( null !== $cron_exists) {
		if  (wp_next_scheduled ( 'trophymonsta_catgroup_import' )) {
			$nexttime = wp_next_scheduled ( 'trophymonsta_catgroup_import' );
			wp_schedule_event($nexttime, 'daily', 'trophymonsta_catgroup_import');
			
			Trophymonsta::http_get_self( Trophymonsta::build_query( array( 'key' => $_GET['key']) ), 'wp-json/monsta/syncstatus', null );
			$response = new \WP_REST_Response(array('status' => 'Data Synchronise is in progress.', 'error' => ''));
			$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').'Data ReSynchronise is in progress.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
				if ( function_exists('w3tc_flush_url') ) {
			    w3tc_flush_url( site_url('wp-json/monsta/resync') );
			  }
			return rest_ensure_response($response);
		}
		$response = new \WP_REST_Response(array('error' => 'oop! Unable to Synchronise please try again.', 'status' => ''));
		$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').'oop! Unable to ReSynchronise please try again.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			if ( function_exists('w3tc_flush_url') ) {
		    w3tc_flush_url( site_url('wp-json/monsta/resync') );
		  }
		return rest_ensure_response($response);*/

	} else {
		$response = new \WP_REST_Response(array('status' => 'Please check your MonstaManagement key.', 'error' => ''));
		$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').'Please check your MonstaManagement key.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			if ( function_exists('w3tc_flush_url') ) {
		    w3tc_flush_url( site_url('wp-json/monsta/resync') );
		  }
		return rest_ensure_response($response);
	}
}
function sync_execute_import_cron() {
	global $wpdb;
	
	if (isset($_GET['key']) && esc_attr($_GET['key']) == get_option( 'trophymonsta_api_key' )) {
		
		$last_sync_status = get_option( 'monstamanagement_sync_status' );
		if ($last_sync_status) {
			update_option( 'monstamanagement_sync_status', 'grrstart');
		} else {
			add_option( 'monstamanagement_sync_status', 'grrstart');
		}
		
		$cron_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` LIKE 'cron' AND `option_value` LIKE '%trophymonsta_sync_status%'" );
	    if ( null === $cron_exists) {
	        error_log(date('Y-m-d H:i:s').' sync_execute_import_cron trophymonsta_sync_status :'.$last_sync_status.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		    wp_schedule_event(time(), '1min', 'trophymonsta_sync_status');
	    }
		
		$response = new \WP_REST_Response(array('status' => 'Data Synchronise will start few within mins.', 'error' => ''));
		$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').' Data Synchronise will start few within mins.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			if ( function_exists('w3tc_flush_url') ) {
			w3tc_flush_url( site_url('wp-json/monsta/sync') );
		  }
		return rest_ensure_response($response);
			
		/*if ((! wp_next_scheduled ( 'trophymonsta_product_import' )) && (!wp_next_scheduled ( 'trophymonsta_catgroup_import' ))) {

  		  if ( function_exists('w3tc_flush_url') ) {
					if (TROPHYMONSTA_DEBUG)
  			  error_log(date('Y-m-d H:i:s').'Cache option '.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
  		    w3tc_flush_url(site_url('wp-json/monsta/sync') );
  		  }
			wp_schedule_event(time(), 'daily', 'trophymonsta_catgroup_import');

			global $wpdb;
			//$cron_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` LIKE 'cron' AND (`option_value` LIKE '%trophymonsta_catgroup_import%')" );
			//error_log(date('Y-m-d H:i:s').print_r($cron_exists, true).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			//if ( null !== $cron_exists) {
			if  (wp_next_scheduled ( 'trophymonsta_catgroup_import' )) {
			    $nexttime = wp_next_scheduled ( 'trophymonsta_catgroup_import' );
			    wp_schedule_event($nexttime, 'daily', 'trophymonsta_catgroup_import');
			    error_log(date('Y-m-d H:i:s').'i am here.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			    Trophymonsta::http_get_self( Trophymonsta::build_query( array( 'key' => $_GET['key']) ), 'wp-json/monsta/syncstatus', null );
			
				$response = new \WP_REST_Response(array('status' => 'Data Synchronise is in progress.', 'error' => '', 'rand' => rand(10, 100)));
				$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
				if (TROPHYMONSTA_DEBUG)
					error_log(date('Y-m-d H:i:s').'Data Synchronise is in progress.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");

				return rest_ensure_response($response);
			}
			$response = new \WP_REST_Response(array('error' => 'oop! Unable to Synchronise please try again.', 'status' => ''));
			$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').'oop! Unable to Synchronise please try again.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
				if ( function_exists('w3tc_flush_url') ) {
			    w3tc_flush_url( site_url('wp-json/monsta/sync') );
			  }
			return rest_ensure_response($response);

		} else {
			$response = new \WP_REST_Response(array('status' => 'Data Synchronise already in progress.', 'error' => ''));
			$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').'Data Synchronise already in progress.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
				if ( function_exists('w3tc_flush_url') ) {
			    w3tc_flush_url( site_url('wp-json/monsta/sync') );
			  }
			return rest_ensure_response($response);
		}*/
	} else {
		$response = new \WP_REST_Response(array('status' => 'Please check your MonstaManagement key.', 'error' => ''));
		$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').'Please check your MonstaManagement key.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			if ( function_exists('w3tc_flush_url') ) {
		    w3tc_flush_url( site_url('wp-json/monsta/sync') );
		  }
		return rest_ensure_response($response);
	}
}

function execute_activation_cron() {
	if (isset($_GET['key']) && esc_attr($_GET['key']) == get_option( 'trophymonsta_api_key' )) {
		if(in_array( plugin_basename( __FILE__ ), (array) get_option( 'active_plugins', array() ) ) || is_plugin_active_for_network( plugin_basename( __FILE__ ) )) {
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').'Alreay activation /wp-json/monsta/activation'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			return array('status' => 'Monstamanagement Plugin Already activation.');
		} else {
			$response = Trophymonsta::activation();
			$response = new \WP_REST_Response($response);
			$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').'IF /wp-json/monsta/activation'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			return rest_ensure_response($response);
		}
	} else {
		$response = new \WP_REST_Response(array('status' => 'Please check your MonstaManagement key.'));
		$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').'ELSE /wp-json/monsta/activation'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		return rest_ensure_response($response);
	}
}

function syncstatus() {
	if (isset($_GET['key']) && esc_attr($_GET['key']) == get_option( 'trophymonsta_api_key' )) {
		$response = Trophymonsta::import_status();
		$response = new \WP_REST_Response($response);
		$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').'IF /wp-json/monsta/syncstatus'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		return rest_ensure_response($response);
	} else {
		$response = new \WP_REST_Response(array('status' => 'Please check your MonstaManagement key.'));
		$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').'ELSE /wp-json/monsta/syncstatus'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		return rest_ensure_response($response);
	}
}

function syncaccessoriestype() {
	if (isset($_GET['key']) && esc_attr($_GET['key']) == get_option( 'trophymonsta_api_key' )) {
		update_option( 'monsta_accessories_type', 0);
		$response = new \WP_REST_Response(array('status' => 'accessories type sync is process.'));
		$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').'IF /wp-json/monsta/syncaccessoriestype'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		return rest_ensure_response($response);
	} else {
		$response = new \WP_REST_Response(array('status' => 'Please check your MonstaManagement key.'));
		$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').'ELSE /wp-json/monsta/syncaccessoriestype'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		return rest_ensure_response($response);
	}
}

function execute_deactivation_cron() {
	if (isset($_GET['key']) && esc_attr($_GET['key']) == get_option( 'trophymonsta_api_key' )) {
		$response = Trophymonsta::deactivation();
		$response = new \WP_REST_Response($response);
		$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').'IF /wp-json/monsta/deactivation'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		return rest_ensure_response($response);
	} else {
		$response = new \WP_REST_Response(array('status' => 'Please check your MonstaManagement key.'));
		$response->set_headers(array('Cache-Control' => 'no-cache, must-revalidate, max-age=0'));
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').'ELSE /wp-json/monsta/deactivation'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		return rest_ensure_response($response);
	}
}

function monstamanagement_validate_array($val) {
    foreach($val as $key=>$key_val){
        if($key == "product_id" || $key == "product_quantity" || $key == "variation_id" ) {
            $val[$key] = intval($key_val);
        }
        else {
            $val[$key] = sanitize_text_field($key_val);
        }
    }
    return $val;
}

//add_shortcode('_monsta-quote', array( 'Monstaquote', 'monstamanagement_get_quote' ));
add_shortcode('_monsta-engravings-settings', array( 'Monstaengravings', 'monstamanagement_engravings_settings' ));
add_shortcode('_monsta-engravings-details', array( 'Monstaengravings', 'monstamanagement_engravings_details' ));
add_shortcode('_monsta-engravings-review', array( 'Monstaengravings', 'monstamanagement_engravings_review' ));
add_action( 'upgrader_process_complete', 'monstamanagement_upgrade_completed', 10, 2 );
add_action('trophymonsta_sync_status', array('Trophymonsta','trophymonsta_sync_status'));
/**
 * This function runs when WordPress completes its upgrade process
 * It iterates through each plugin updated to see if ours is included
 * @param $upgrader_object Array
 * @param $options Array
 */
function monstamanagement_upgrade_completed( $upgrader_object, $options ) {
 // The path to our plugin's main file
 $our_plugin = plugin_basename( __FILE__ );
 // If an update has taken place and the updated type is plugins and the plugins element exists
 if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
  // Iterate through the plugins being updated and check if ours is there
  foreach( $options['plugins'] as $plugin ) {
	
	if( $plugin == $our_plugin ) {
		if  (wp_next_scheduled ( 'trophymonsta_sync_status' ) === false) {
			wp_schedule_event(time(), '1min', 'trophymonsta_sync_status');
		}
	}
  }
 }
}
