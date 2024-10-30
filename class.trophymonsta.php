<?php
class Trophymonsta {
	const API_HOST = 'https://grr.monstamanagement.com/';
	const API_PORT = 80;
	const MAX_DELAY_BEFORE_MODERATION_EMAIL = 86400; // One day in seconds

	private static $initiated = false;
	private static $is_rest_api_call = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	/**
	 * Initializes WordPress hooks
	 */
	private static function init_hooks() {
		self::$initiated = true;
		add_action('trophymonsta_product_import', array('Trophymonsta','import_trophymonsta_products'));
		add_action('trophymonsta_catgroup_import', array('Trophymonsta','import_trophymonsta_catgroup'));
		add_action('woocommerce_order_status_changed', array('Trophymonsta','trophymonsta_product_sales'), 10, 4);
		add_action('wp_enqueue_scripts', array( 'Trophymonsta', 'tropymonsta_wp_enqueue_scripts' ), 11);
		add_action( 'wp_print_scripts', array( 'Trophymonsta', 'tropymonsta_dequeue_unnecessary_scripts'), 11 );
		add_filter( 'wp_footer', array( 'Trophymonsta', 'monstamanagement_product_title_script' ));
		add_filter( 'woocommerce_checkout_fields' , array('Trophymonsta', 'tropymonsta_override_checkout_fields'));
		add_action( 'woocommerce_before_checkout_form', array( 'Trophymonsta', 'tropymonsta_engraving_process' ),9);
		add_action( 'woocommerce_cart_calculate_fees', array( 'Trophymonsta', 'tropymonsta_add_logofee' ),999);
		add_action( 'woocommerce_after_checkout_validation',  array( 'Trophymonsta','tropymonsta_checkout_validation' ), 9999, 2);
	}
	
	/**
	 * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
	 * @static
	 */
	public static function plugin_activation() {

		global $wpdb;

		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').' PLUGIN activation monstamanagement plugin'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		if ( version_compare( $GLOBALS['wp_version'], TROPHYMONSTA_MINIMUM_WP_VERSION, '<' ) ) {
			load_plugin_textdomain( 'trophymonsta' );
			$message = '<strong>'.sprintf(esc_html__( 'Trophymonsta %s requires WordPress %s or higher.' , 'trophymonsta'), TROPHYMONSTA_VERSION, TROPHYMONSTA_MINIMUM_WP_VERSION ).'</strong> '.sprintf(__('Please <a href="%1$s">upgrade WordPress</a> to a current version, or <a href="%2$s">downgrade to version 2.4 of the MonstaManagement plugin</a>.', 'trophymonsta'), 'https://codex.wordpress.org/Upgrading_WordPress', 'https://www.trophymonsta.com/plugins/download/');
			Trophymonsta::bail_on_activation( $message );
		}

		if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			$class = "error";
			$message = __("MonstaManagement plugin requires Woocommerce plugin to be activated.", 'trophymonsta');
			Trophymonsta::bail_on_activation( $message );
		}

		self::monsta_pages();
		self::monsta_menu();
		self::monsta_key_clear();
		self::register_taxonomy();
		self::monsta_attribute();
		self::monsta_import_log();
		wp_schedule_event(time(), '1min', 'trophymonsta_sync_status');
		wp_schedule_event(time(), 'daily', 'trophymonsta_catgroup_import');
		wp_schedule_event(time(), 'daily', 'trophymonsta_product_import');

		self::monsta_categories();
		self::monsta_shipping_zone();

	}

	/**
	 * activation all connection options
	 * @static
	 */
	public static function activation( ) {

		global $wpdb;

		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').'METHOD activation monstamanagement'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");

		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').' PLUGIN activation monstamanagement plugin'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		if ( version_compare( $GLOBALS['wp_version'], TROPHYMONSTA_MINIMUM_WP_VERSION, '<' ) ) {
			load_plugin_textdomain( 'trophymonsta' );
			$message = '<strong>'.sprintf(esc_html__( 'Trophymonsta %s requires WordPress %s or higher.' , 'trophymonsta'), TROPHYMONSTA_VERSION, TROPHYMONSTA_MINIMUM_WP_VERSION ).'</strong> '.sprintf(__('Please <a href="%1$s">upgrade WordPress</a> to a current version, or <a href="%2$s">downgrade to version 2.4 of the MonstaManagement plugin</a>.', 'trophymonsta'), 'https://codex.wordpress.org/Upgrading_WordPress', 'https://www.trophymonsta.com/plugins/download/');
			Trophymonsta::bail_on_activation( $message );
		}

		self::monsta_pages();
		self::monsta_menu();

		self::monsta_key_clear();
		self::register_taxonomy();
		self::monsta_attribute();
		self::monsta_import_log();
		
		wp_schedule_event(time(), '1min', 'trophymonsta_sync_status');
		wp_schedule_event(time(), 'daily', 'trophymonsta_catgroup_import');
		wp_schedule_event(time(), 'daily', 'trophymonsta_product_import');

		self::monsta_categories();
		self::monsta_shipping_zone();

		return array('status' => 'Monstamanagement activation successfully.');
	}


	/**
	 * Removes all connection options
	 * @static
	 */
	public static function plugin_deactivation( ) {
		global $wpdb;
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').'PLUGIN deactivation monstamanagement plugin via wordpress backend'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		self::deactivate_key( self::get_api_key() );

		self::deactivate_key( self::get_api_key() );
		self::monsta_clear_data();
		wp_clear_scheduled_hook('trophymonsta_sync_status');
		wp_clear_scheduled_hook('trophymonsta_catgroup_import');
		wp_clear_scheduled_hook('trophymonsta_product_import');
		self::http_post( json_encode(array('sync_status' => 1)) , 'store/updatesynchstatus', 'json' );
		delete_option('trophymonsta_api_key');
		self::monsta_clear_all_other_key();

	}

	/**
	 * Removes all connection options
	 * @static
	 */
	public static function deactivation( ) {
			global $wpdb;
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').'METHOD deactivation monstamanagement plugin via wordpress backend'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		self::deactivate_key( self::get_api_key() );

		//$menu_name   = 'Monsta Menu';
		//wp_delete_nav_menu($menu_exists);

		self::monsta_clear_data();
		wp_clear_scheduled_hook('trophymonsta_sync_status');
		wp_clear_scheduled_hook('trophymonsta_catgroup_import');
		wp_clear_scheduled_hook('trophymonsta_product_import');
		self::http_post( json_encode(array('sync_status' => 1)) , 'store/updatesynchstatus', 'json' );

		self::monsta_clear_all_other_key();

		return array('status' => 'All data are cleared from store.');
	}

	public static function monsta_pages() {

		/**
		 * Create monsta quote page and assign sort code for monsta quote
		 *
		 */
		/*$quote_page_title = ucfirst(str_replace('-',' ','monsta-quote'));
		$quote_page_content = '[_monsta-quote]';
		$quote_page_check = get_page_by_title($quote_page_title);
		$quote_page = array(
				'post_type' => 'page',
				'post_title' => $quote_page_title,
				'post_content' => $quote_page_content,
				'post_status' => 'publish',
				'comment_status' => 'closed',
        'ping_status'    => 'closed',
				'post_author' => 1,
				'post_slug' => 'monsta-quote'
		);
		if(!isset($quote_page_check->ID)){
				$quote_page_id = wp_insert_post($quote_page);
		}*/

		/**
		 * Create monsta engravings details page and assign sort code for monsta engravings details
		 *
		 */
		$engravings_settings_page_title = ucfirst(str_replace('-',' ','monsta-engravings-settings'));
		$engravings_settings_page_content = '[_monsta-engravings-settings]';
		$engravings_settings_page_check = get_page_by_title($engravings_settings_page_title);
		$engravings_settings_page = array(
				'post_type' => 'page',
				'post_title' => $engravings_settings_page_title,
				'post_content' => $engravings_settings_page_content,
				'post_status' => 'publish',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'menu_order'     => 0,
				'post_author' => 1,
				'post_slug' => 'monsta-engravings-settings'
		);
		if(!isset($engravings_settings_page_check->ID)){
				$engravings_settings_page_id = wp_insert_post($engravings_settings_page);
		}
		/**
		 * Create monsta engravings details page and assign sort code for monsta engravings details
		 *
		 */
		$engravings_details_page_title = ucfirst(str_replace('-',' ','monsta-engravings-details'));
		$engravings_details_page_content = '[_monsta-engravings-details]';
		$engravings_details_page_check = get_page_by_title($engravings_details_page_title);
		$engravings_details_page = array(
				'post_type' => 'page',
				'post_title' => $engravings_details_page_title,
				'post_content' => $engravings_details_page_content,
				'post_status' => 'publish',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'menu_order'     => 0,
				'post_author' => 1,
				'post_slug' => 'monsta-engravings-details'
		);
		if(!isset($engravings_details_page_check->ID)){
				$engravings_details_page_id = wp_insert_post($engravings_details_page);
		}

		/**
		 * Create monsta engravings details page and assign sort code for monsta engravings details
		 *
		 */
		$engravings_review_page_title = ucfirst(str_replace('-',' ','monsta-engravings-review'));
		$engravings_review_page_content = '[_monsta-engravings-review]';
		$engravings_review_page_check = get_page_by_title($engravings_review_page_title);
		$engravings_review_page = array(
				'post_type' => 'page',
				'post_title' => $engravings_review_page_title,
				'post_content' => $engravings_review_page_content,
				'post_status' => 'publish',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'menu_order'     => 0,
				'post_author' => 1,
				'post_slug' => 'monsta-engravings-review'
		);
		if(!isset($engravings_review_page_check->ID)){
				$engravings_review_page_id = wp_insert_post($engravings_review_page);
		}
	}

	public static function monsta_attribute() {
		global $wpdb;

		$plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE `attribute_name` = 'monstasize'" );
		if ( null === $plugin_exists) {
			Trophymonsta::monsta_add_attribute(array('attribute_name' => 'monstasize', 'attribute_label' => 'Size'));
		}

		$plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE `attribute_name` = 'monstacolor'" );
		if ( null === $plugin_exists) {
			Trophymonsta::monsta_add_attribute(array('attribute_name' => 'monstacolor', 'attribute_label' => 'Color'));
		}

		$plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE `attribute_name` = 'monstaengraving'" );
		if ( null === $plugin_exists) {
			Trophymonsta::monsta_add_attribute(array('attribute_name' => 'monstaengraving', 'attribute_label' => 'Engraving'));
		}

		$plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE `attribute_name` = 'monstamaterial'" );
		if ( null === $plugin_exists) {
			Trophymonsta::monsta_add_attribute(array('attribute_name' => 'monstamaterial', 'attribute_label' => 'Material'));
		}

		$plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE `attribute_name` = 'monstaprocess'" );
		if ( null === $plugin_exists) {
			Trophymonsta::monsta_add_attribute(array('attribute_name' => 'monstaprocess', 'attribute_label' => 'Process'));
		}
	}

	public static function monsta_import_log() {
		global $wpdb;
		$table_name = $wpdb->prefix.'trophymonsta_import_log';
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		`ID` int(11) NOT NULL AUTO_INCREMENT,
		`sync` datetime NOT NULL,
		`type` varchar(150) NOT NULL,
		`status` VARCHAR(50) NOT NULL,
		`total_count` int(11) NOT NULL,
		`page` int(11) NOT NULL,
		`delete_count` int(11) NOT NULL,
		`create_count` int(11) NOT NULL,
		`update_count` int(11) NOT NULL,
		PRIMARY KEY (`ID`)) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
		$wpdb->query($sql);

		$sync_date = date('Y-m-d H:i:s');

		$sql = "INSERT INTO ".$wpdb->prefix."trophymonsta_import_log (`sync`, `type`, `status`, `total_count`, `page`, `delete_count`, `create_count`, `update_count`) VALUES
		('".$sync_date."', 'grouping', 'Yet to Start', 0, 1, 0, 0, 0),
		('".$sync_date."', 'noprocesses', 'Yet to Start', 0, 1, 0, 0, 0),
		('".$sync_date."', 'processes', 'Yet to Start', 0, 1, 0, 0, 0),
		('".$sync_date."', 'accessories', 'Yet to Start', 0, 1, 0, 0, 0),
		('".$sync_date."', 'brand', 'Yet to Start', 0, 1, 0, 0, 0),
		('".$sync_date."', 'material', 'Yet to Start', 0, 1, 0, 0, 0),
		('".$sync_date."', 'product', 'Yet to Start', 0, 1, 0, 0, 0)";
		$wpdb->query($sql);
	}

	public static function monsta_key_clear() {
		delete_option('trophymonsta_suppliers_last_sync_date');
		delete_option('trophymonsta_grouping_last_sync_date');
		delete_option('trophymonsta_accessories_last_sync_date');
		delete_option('trophymonsta_material_last_sync_date');
		delete_option('trophymonsta_noprocesses_last_sync_date');
		delete_option('trophymonsta_processes_last_sync_date');
		delete_option('trophymonsta_product_last_sync_date');
		add_option('monsta_accessories_type', 0);
	}

	/**
	 * Create monsta menu management
	 *
	 */
	public static function monsta_menu() {

		$menu_name   = 'Monsta Menu';
		$menu_exists = wp_get_nav_menu_object($menu_name);
		// If it doesn't exist, let's create it.
		if ( ! $menu_exists ) {
				$menu_id = wp_create_nav_menu($menu_name);
		}
	}

	public static function monsta_categories() {
		$term = term_exists( 'monsta-categories', 'product_cat', null );
		if ( is_array( $term ) ) {
			$parent	= $term['term_id'];
		} else {
			$term = wp_insert_term( 'Monsta Categories', 'product_cat', array( 'parent' => intval( null ), 'slug' => 'monsta-categories') );
			$parent = $term['term_id'];
			add_term_meta($term['term_id'], 'category_mode', 'trophymonsta', true);
		}

		$term = term_exists( 'ungrouped', 'product_cat', $parent );
		if ( !is_array( $term ) ) {
			$term = wp_insert_term('Ungrouped', 'product_cat', array( 'parent' => intval( $parent ), 'slug' => 'ungrouped') );
			add_term_meta($term['term_id'], 'category_mode', 'trophymonsta', true);
		}
	}

	/**
	 * Create monstamanagement shipping zone
	 *
	 */
	public static function monsta_shipping_zone() {
			global $wpdb;
			if ( class_exists( 'WC_Shipping_Zones' ) ) {
				$available_zones = WC_Shipping_Zones::get_zones();
				// Get all WC Countries
				$all_countries  = WC()->countries->get_countries();
				//Array to store available names
				$available_zones_names = array();
				// Add each existing zone name into our array
				foreach ($available_zones as $zone ) {
					if ( !in_array( $zone['zone_name'], $available_zones_names ) ) {
						$available_zones_names[] = $zone['zone_name'];
						$wpdb->query("UPDATE ".$wpdb->prefix."woocommerce_shipping_zones SET zone_order = zone_order+1 WHERE zone_id =".$zone['zone_id']);
					}
				}
				if ( !in_array( 'Monsta Australia', $available_zones_names ) ) {
					// Instantiate a new shipping zone with our object
					$new_zone_cro = new WC_Shipping_Zone();
					$new_zone_cro->set_zone_name( 'Monsta Australia');
					// Add Australia as location
					$new_zone_cro->add_location( 'AU', 'country' );
					// Set Zone order
					$new_zone_cro->set_zone_order(0);
					// Save the zone, if non existent it will create a new zone
					$new_zone_cro->save();

					$ip 		= null;
					$api_key 	= get_option( 'trophymonsta_api_key' );
					$response 	= Trophymonsta::http_get( Trophymonsta::build_query( array( 'key' => $api_key, 'site_url' => site_url() ) ), 'store/checkFreightCharge', $ip );
					$flat_fee 	= 16.50;
					$response	= json_decode($response);
					if ($response->code == '200') {
						if( $response->success->flat_fee>0 ) {
							$flat_fee = $response->success->flat_fee;
						}
					}
					// Add our shipping method to that zone
					$minstanceid = $new_zone_cro->add_shipping_method( 'flat_rate' );
					$new_zone_cro->add_shipping_method( 'free_shipping' );
					$new_zone_cro->add_shipping_method( 'local_pickup' );
					$monsta_flatrate = array(
										'title' 		=> 'Flat Rate',
										'tax_status' 	=> 'taxable',
										'cost' 			=> $flat_fee,
									);
					$monstaoptionkey = "woocommerce_flat_rate_".$minstanceid."_settings";
					add_option( $monstaoptionkey, $monsta_flatrate );
				}
			}
	}

	public static function monsta_clear_data() {
		global $wpdb;
		$trophyproducts = array();
		$attachmentids = array();
		$monstamenuitems = array();
		$productids = $wpdb->get_results("SELECT postmeta.`post_id`, posts.`post_type` FROM ".$wpdb->prefix."postmeta as postmeta left join ".$wpdb->prefix."posts as posts on (posts.ID=postmeta.post_id) WHERE postmeta.`meta_key` LIKE '_trophymonsta_text_field' and postmeta.meta_value LIKE 'trophymonsta'");
		foreach ( $productids as $productid )	{
		  if ($productid->post_type == 'nav_menu_item'){
		    $monstamenuitems[] = $productid->post_id;
		  } else {
		    if ($productid->post_type == 'attachment') {
		      //$attachmentids[] = $productid->post_id;
					wp_delete_attachment($productid->post_id);
		    }
		    $trophyproducts[] = $productid->post_id;
		  }
		}

		if (!empty($monstamenuitems)) {
		  foreach ( $monstamenuitems as $item )	{
		    wp_delete_post($item);
		  }
		}

		/*if (!empty($attachmentids)) {
		  $attachmentidstring = implode(',', $attachmentids);
		  $attachmentposts = $wpdb->get_results("SELECT `meta_value` FROM ".$wpdb->prefix."postmeta WHERE `meta_key` LIKE '_wp_attached_file' and post_id in (".$attachmentidstring.")");
		  foreach ( $attachmentposts as $attachment )	{
		    $wordpress_upload_dir = wp_upload_dir();
		    $new_file_path = unlink($wordpress_upload_dir['basedir'] . '/' . $productid->meta_value);
		  }
		}*/
		if (TROPHYMONSTA_DEBUG)
		  error_log(date('Y-m-d H:i:s').' Start clear all data import from monstamanagement'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		if (!empty($trophyproducts)) {
		  $implodeids = implode(',', $trophyproducts);
		  $wpdb->query("DELETE FROM ".$wpdb->prefix."term_relationships WHERE object_id IN (".$implodeids.")");
		  $wpdb->query("DELETE FROM ".$wpdb->prefix."postmeta WHERE post_id IN (".$implodeids.")");
		  $wpdb->query("DELETE FROM ".$wpdb->prefix."posts WHERE (post_type = 'product' or post_type = 'product_variation' or post_type = 'attachment') and ID IN (".$implodeids.")");
		}
		$wpdb->query("DELETE termmeta.*, taxes.*, terms.* FROM ".$wpdb->prefix."terms AS terms
		INNER JOIN ".$wpdb->prefix."term_taxonomy AS taxes ON taxes.term_id=terms.term_id
		INNER JOIN ".$wpdb->prefix."termmeta AS termmeta ON termmeta.term_id=terms.term_id
		WHERE termmeta.meta_key = 'category_mode' and termmeta.meta_value = 'trophymonsta'");

		$wpdb->query("DELETE FROM ".$wpdb->prefix."trophymonsta_import_log");
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').' End clear all data import from monstamanagement'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
	}

	public static function monsta_clear_all_other_key() {

		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').' Clear other all config option variables.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");

		//delete_option('trophymonsta_available_servers');
		//delete_option('trophymonsta_connectivity_time');
		delete_option('trophymonsta_total_sync_count');
		delete_option('trophymonsta_last_sync_date');
		delete_option('trophymonsta_ssl_disabled');
		delete_option('trophymonsta_suppliers_last_sync_date');
		delete_option('trophymonsta_grouping_last_sync_date');
		delete_option('trophymonsta_accessories_last_sync_date');
		delete_option('trophymonsta_material_last_sync_date');
		delete_option('trophymonsta_noprocesses_last_sync_date');
		delete_option('trophymonsta_processes_last_sync_date');
		delete_option('trophymonsta_product_last_sync_date');
		delete_option('monsta_accessories_type');
		delete_option('monstamanagement_sync_status');
		
		add_option( 'trophymonsta_total_sync_count', 1); // it is active plugin
		add_option( 'trophymonsta_last_sync_date', '1');

	}

	public static function trophymonsta_sync_status() {
	    global $wpdb;
		$last_sync_status = get_option( 'monstamanagement_sync_status' );
		if ($last_sync_status == 'grrstart') {
			wp_schedule_event(time(), 'daily', 'trophymonsta_catgroup_import');
		} else if ((($last_sync_status == 'monstacatend' && $last_sync_status != 'monstaproductstart') || $last_sync_status == 'monstaproductend') &&  $last_sync_status != 'monstaproductinprogress') {
		    error_log(date('Y-m-d H:i:s').' ELSE IF trophymonsta_sync_status ::'.$last_sync_status.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		    $cron_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` LIKE 'cron' AND `option_value` LIKE '%trophymonsta_product_import%'" );
		    
		    if ( null === $cron_exists) {
			    wp_schedule_event(time(), 'daily', 'trophymonsta_product_import');
		    } else {
		        error_log(date('Y-m-d H:i:s').' ELSE IF trophymonsta_sync_status :: clear scheduled'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		        wp_clear_scheduled_hook('trophymonsta_product_import');
		        wp_schedule_event(time(), 'daily', 'trophymonsta_product_import');
		    }
		}
		
	}
	
	public static function import_trophymonsta_catgroup() {
		global $wpdb;
		$plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` = 'active_plugins' and option_value like '%/monstamanagement.php%' " );
		if ( null === $plugin_exists)
			return;
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').' Overall trophymonsta_catgroup_import import process start.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		
		$last_sync_status = get_option( 'monstamanagement_sync_status' );
		if ($last_sync_status) {
			update_option( 'monstamanagement_sync_status', 'monstacatstart');
		} else {
			add_option( 'monstamanagement_sync_status', 'monstacatstart');
		}
		
		$cron_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` LIKE 'cron' AND `option_value` LIKE '%trophymonsta_catgroup_import%'" );
		$api_key = get_option( 'trophymonsta_api_key' );
		if ( null !== $cron_exists && $api_key) {
			$response = self::http_post(json_encode(array('sync_status' => 0)) , 'store/updatesynchstatus', 'json' );
			if (taxonomy_exists( 'trophymonsta_brand' )) {
				Trophymonsta::import_products_suppliers($api_key);
			}
			Trophymonsta::import_grouping($api_key);
			Trophymonsta::import_material($api_key);
			Trophymonsta::import_process($api_key);
			Trophymonsta::import_no_process($api_key);
			Trophymonsta::import_accessories($api_key);
			wp_clear_scheduled_hook('trophymonsta_catgroup_import');
			wp_schedule_event(time(), 'daily', 'trophymonsta_product_import');
			$nexttime = wp_next_scheduled ( 'trophymonsta_product_import' );
			wp_schedule_event($nexttime, 'daily', 'trophymonsta_product_import');
			$last_sync_status = get_option( 'monstamanagement_sync_status' );
			if ($last_sync_status) {
				update_option( 'monstamanagement_sync_status', 'monstacatend');
			} else {
				add_option( 'monstamanagement_sync_status', 'monstacatend');
			}
			//Trophymonsta::http_get_self( Trophymonsta::build_query( array( 'key' => $api_key) ), 'wp-json/monsta/syncstatus', null );
		} else {
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Overall trophymonsta_catgroup_import import either cron already running or api key is invalid.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			wp_clear_scheduled_hook('trophymonsta_catgroup_import');
			wp_clear_scheduled_hook('trophymonsta_product_import');
		}
	}

	public static function import_trophymonsta_products() {
		global $wpdb;
		$plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` = 'active_plugins' and option_value like '%/monstamanagement.php%' " );
		if ( null === $plugin_exists)
			return;
		if (TROPHYMONSTA_DEBUG)
			error_log(date('Y-m-d H:i:s').' Overall import process start.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");

		$cron_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` LIKE 'cron' AND `option_value` LIKE '%trophymonsta_product_import%'" );
		$api_key = get_option( 'trophymonsta_api_key' );
		if ( null !== $cron_exists && $api_key) {
			$total_sync_count = (int) get_option( 'trophymonsta_total_sync_count' );
			if($total_sync_count >= 1) {
				$cron_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` LIKE 'cron' AND `option_value` LIKE '%trophymonsta_catgroup_import%'" );
				if ( null === $cron_exists) {
					$products = Trophymonsta::import_products($api_key);
				}
			} else {
				add_option( 'trophymonsta_total_sync_count', 1); // it is active plugin
				add_option( 'trophymonsta_last_sync_date', '1');
				wp_clear_scheduled_hook('trophymonsta_product_import');
				//wpdb::close();
			}
		} else {
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Overall import either cron already running or api key is invalid.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			add_option( 'trophymonsta_total_sync_count', 1); // it is active plugin
			add_option( 'trophymonsta_last_sync_date', '1');
			wp_clear_scheduled_hook('trophymonsta_product_import');
			//wpdb::close();
		}

	}

	private static function bail_on_activation( $message, $deactivate = true ) {
	?>
		<!doctype html>
		<html>
		<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		" />
		<style>
		* {
			text-align: center;
			margin: 0;
			padding: 0;
			font-family: "Lucida Grande",Verdana,Arial,"Bitstream Vera Sans",sans-serif;
		}
		p {
			margin-top: 1em;
			font-size: 18px;
		}
		</style>
		</head>
		<body>
		<p><?php echo esc_html( $message ); ?></p>
		</body>
		</html>
	<?php
			if ( $deactivate ) {
				$plugins = get_option( 'active_plugins' );
				$trophymonsta = plugin_basename( TROPHYMONSTA_PLUGIN_DIR . 'monstamanagement.php' );
				$update  = false;
				foreach ( $plugins as $i => $plugin ) {
					if ( $plugin === $trophymonsta ) {
						$plugins[$i] = false;
						$update = true;
					}
				}

				if ( $update ) {
					update_option( 'active_plugins', array_filter( $plugins ) );
				}
			}
			exit;
		}

		public static function get_api_key() {
			return apply_filters( 'trophymonsta_get_api_key', defined('WPCOM_API_KEY') ? constant('WPCOM_API_KEY') : get_option('trophymonsta_api_key') );
		}

		public static function deactivate_key( $key ) {
			$response = self::http_post( Trophymonsta::build_query( array( 'key' => $key, 'site_url' => site_url() ) ), 'deactivate' );

			if ( $response[1] != 'deactivated' )
				return 'failed';

			return $response[1];
		}

		/**
		 * Make a POST request to the Trophymonsta API.
		 *
		 * @param string $request The body of the request.
		 * @param string $path The path for the request.
		 * @param string $ip The specific IP address to hit.
		 * @return array A two-member array consisting of the headers and the response body, both empty in the case of a failure.
		 */
		public static function http_post( $request, $path, $content_type = 'x-www-form-urlencoded', $ip=null ) {

			$trophymonsta_ua = sprintf( 'WordPress/%s | Trophymonsta/%s', $GLOBALS['wp_version'], constant( 'TROPHYMONSTA_VERSION' ) );
			$trophymonsta_ua = apply_filters( 'trophymonsta_ua', $trophymonsta_ua );

			$content_length = strlen( $request );

			$api_key   = self::get_api_key();
			$host      = self::API_HOST;

			if ( empty( $api_key ) ) {
				$temp_request = explode("&",$request);
				foreach($temp_request as $param) {
					$temp_param = explode("=",$param);
					if (isset($temp_param[0]) && isset($temp_param[1]) && $temp_param[0] == 'key') {
						$api_key = $temp_param[1];
					}
				}
			}

			$http_host = $host;
			// use a specific IP if provided
			// needed by Trophymonsta_Admin::check_server_connectivity()
			if ( $ip && long2ip( ip2long( $ip ) ) ) {
				$http_host = $ip;
			}

			$http_args = array(
				'body' => $request,
				'method'  => 'POST',
				'headers' => array(
					'Content-Type' => 'application/'.$content_type.'x-www-form-urlencoded; charset=' . get_option( 'blog_charset' ),
					'x-api-key' => $api_key,
					'User-Agent' => $trophymonsta_ua,
				),
				'httpversion' => '1.0',
				'timeout' => 180,
				'data_format' => 'body'
			);

			$trophymonsta_url = $http_trophymonsta_url = "{$http_host}api/{$path}";

			/**
			 * Try SSL first; if that fails, try without it and don't try it again for a while.
			 */

			$ssl = $ssl_failed = false;

			// Check if SSL requests were disabled fewer than X hours ago.
			$ssl_disabled = get_option( 'trophymonsta_ssl_disabled' );

			if ( $ssl_disabled && $ssl_disabled < ( time() - 60 * 60 * 24 ) ) { // 24 hours
				$ssl_disabled = false;
				delete_option( 'trophymonsta_ssl_disabled' );
			}
			else if ( $ssl_disabled ) {
				do_action( 'trophymonsta_ssl_disabled' );
			}

			if ( ! $ssl_disabled && ( $ssl = wp_http_supports( array( 'ssl' ) ) ) ) {
				$trophymonsta_url = set_url_scheme( $trophymonsta_url, 'https' );

				do_action( 'trophymonsta_https_request_pre' );
			}

			$response = wp_remote_post( $trophymonsta_url, $http_args );

			Trophymonsta::log( compact( 'trophymonsta_url', 'http_args', 'response' ) );

			if ( $ssl && is_wp_error( $response ) ) {
				do_action( 'trophymonsta_https_request_failure', $response );

				// Intermittent connection problems may cause the first HTTPS
				// request to fail and subsequent HTTP requests to succeed randomly.
				// Retry the HTTPS request once before disabling SSL for a time.
				$response = wp_remote_post( $trophymonsta_url, $http_args );

				Trophymonsta::log( compact( 'trophymonsta_url', 'http_args', 'response' ) );

				if ( is_wp_error( $response ) ) {
					$ssl_failed = true;

					do_action( 'trophymonsta_https_request_failure', $response );

					do_action( 'trophymonsta_http_request_pre' );

					// Try the request again without SSL.
					$response = wp_remote_post( $http_trophymonsta_url, $http_args );

					Trophymonsta::log( compact( 'http_trophymonsta_url', 'http_args', 'response' ) );
				}
			}

			if ( is_wp_error( $response ) ) {
				do_action( 'trophymonsta_request_failure', $response );

				return array( '', '' );
			}

			if ( $ssl_failed ) {
				// The request failed when using SSL but succeeded without it. Disable SSL for future requests.
				update_option( 'trophymonsta_ssl_disabled', time() );

				do_action( 'trophymonsta_https_disabled' );
			}

			return $response['body'];

		}

		/**
		 * Make a GET request to the Trophymonsta API.
		 *
		 * @param string $request The body of the request.
		 * @param string $path The path for the request.
		 * @param string $ip The specific IP address to hit.
		 * @return array A two-member array consisting of the headers and the response body, both empty in the case of a failure.
		 */
		public static function http_get( $request, $path, $ip=null ) {

			$trophymonsta_ua = sprintf( 'WordPress/%s | Trophymonsta/%s', $GLOBALS['wp_version'], constant( 'TROPHYMONSTA_VERSION' ) );
			$trophymonsta_ua = apply_filters( 'trophymonsta_ua', $trophymonsta_ua );

			$content_length = strlen( $request );

			$api_key   = self::get_api_key();
			$host      = self::API_HOST;


			if ( empty( $api_key ) ) {
				$temp_request = explode("&",$request);
				foreach($temp_request as $param) {
					$temp_param = explode("=",$param);
					if (isset($temp_param[0]) && isset($temp_param[1]) && $temp_param[0] == 'key') {
						$api_key = $temp_param[1];
					}
				}
			}

			$http_host = $host;
			// use a specific IP if provided
			// needed by Trophymonsta_Admin::check_server_connectivity()
			if ( $ip && long2ip( ip2long( $ip ) ) ) {
				$http_host = $ip;
			}

			$http_args = array(
				//'body' => $request,
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded; charset=' . get_option( 'blog_charset' ),
					'x-api-key' => $api_key,
					'User-Agent' => $trophymonsta_ua,
				),
				'httpversion' => '1.0',
				'timeout' => 180
			);

			$trophymonsta_url = $http_trophymonsta_url = "{$http_host}api/{$path}";
			/**
			 * Try SSL first; if that fails, try without it and don't try it again for a while.
			 */
			$ssl = $ssl_failed = false;
			// Check if SSL requests were disabled fewer than X hours ago.
			$ssl_disabled = get_option( 'trophymonsta_ssl_disabled' );
			if ( $ssl_disabled && $ssl_disabled < ( time() - 60 * 60 * 24 ) ) { // 24 hours
				$ssl_disabled = false;
				delete_option( 'trophymonsta_ssl_disabled' );
			}
			else if ( $ssl_disabled ) {
				do_action( 'trophymonsta_ssl_disabled' );
			}

			if ( ! $ssl_disabled && ( $ssl = wp_http_supports( array( 'ssl' ) ) ) ) {
				$trophymonsta_url = set_url_scheme( $trophymonsta_url, 'https' );
				do_action( 'trophymonsta_https_request_pre' );
			}
			$response = wp_remote_get( $trophymonsta_url.'?'.$request, $http_args );
			Trophymonsta::log( compact( 'trophymonsta_url', 'http_args', 'response' ) );

			if ( $ssl && is_wp_error( $response ) ) {
				do_action( 'trophymonsta_https_request_failure', $response );

				// Intermittent connection problems may cause the first HTTPS
				// request to fail and subsequent HTTP requests to succeed randomly.
				// Retry the HTTPS request once before disabling SSL for a time.
				$response = wp_remote_get( $trophymonsta_url.'?'.$request, $http_args );
				Trophymonsta::log( compact( 'trophymonsta_url', 'http_args', 'response' ) );

				if ( is_wp_error( $response ) ) {
					$ssl_failed = true;
					do_action( 'trophymonsta_https_request_failure', $response );
					do_action( 'trophymonsta_http_request_pre' );
					// Try the request again without SSL.
					$response = wp_remote_get( $http_trophymonsta_url.'?'.$request, $http_args );
					Trophymonsta::log( compact( 'http_trophymonsta_url', 'http_args', 'response' ) );
				}
			}

			if ( is_wp_error( $response ) ) {
				do_action( 'trophymonsta_request_failure', $response );
				return array( '', '' );
			}

			if ( $ssl_failed ) {
				// The request failed when using SSL but succeeded without it. Disable SSL for future requests.
				update_option( 'trophymonsta_ssl_disabled', time() );
				do_action( 'trophymonsta_https_disabled' );
			}

			return $response['body'];

		}
		
		public static function http_get_self( $request, $path, $ip=null ) {

			$trophymonsta_ua = sprintf( 'WordPress/%s | Trophymonsta/%s', $GLOBALS['wp_version'], constant( 'TROPHYMONSTA_VERSION' ) );
			$trophymonsta_ua = apply_filters( 'trophymonsta_ua', $trophymonsta_ua );

			$content_length = strlen( $request );

			$api_key   = self::get_api_key();
			$host      = site_url();


			if ( empty( $api_key ) ) {
				$temp_request = explode("&",$request);
				foreach($temp_request as $param) {
					$temp_param = explode("=",$param);
					if (isset($temp_param[0]) && isset($temp_param[1]) && $temp_param[0] == 'key') {
						$api_key = $temp_param[1];
					}
				}
			}

			$http_host = $host;
			// use a specific IP if provided
			// needed by Trophymonsta_Admin::check_server_connectivity()
			if ( $ip && long2ip( ip2long( $ip ) ) ) {
				$http_host = $ip;
			}

			$http_args = array(
				//'body' => $request,
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded; charset=' . get_option( 'blog_charset' ),
					'x-api-key' => $api_key,
					'User-Agent' => $trophymonsta_ua,
				),
				'httpversion' => '1.0',
				'timeout' => 180
			);

			$trophymonsta_url = $http_trophymonsta_url = "{$http_host}/{$path}";
			/**
			 * Try SSL first; if that fails, try without it and don't try it again for a while.
			 */
			$ssl = $ssl_failed = false;
			// Check if SSL requests were disabled fewer than X hours ago.
			$ssl_disabled = get_option( 'trophymonsta_ssl_disabled' );
			if ( $ssl_disabled && $ssl_disabled < ( time() - 60 * 60 * 24 ) ) { // 24 hours
				$ssl_disabled = false;
				delete_option( 'trophymonsta_ssl_disabled' );
			}
			else if ( $ssl_disabled ) {
				do_action( 'trophymonsta_ssl_disabled' );
			}

			if ( ! $ssl_disabled && ( $ssl = wp_http_supports( array( 'ssl' ) ) ) ) {
				$trophymonsta_url = set_url_scheme( $trophymonsta_url, 'https' );
				do_action( 'trophymonsta_https_request_pre' );
			}
			//error_log(date('Y-m-d H:i:s').' http_get_self.'.$trophymonsta_url.'?'.$request.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			//error_log(date('Y-m-d H:i:s').' http_get_self.'.print_r($http_args,true).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			$response = wp_remote_get( $trophymonsta_url.'?'.$request, $http_args );
			//error_log(date('Y-m-d H:i:s').' http_get_self.'.print_r($response,true).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			Trophymonsta::log( compact( 'trophymonsta_url', 'http_args', 'response' ) );

			if ( $ssl && is_wp_error( $response ) ) {
				do_action( 'trophymonsta_https_request_failure', $response );

				// Intermittent connection problems may cause the first HTTPS
				// request to fail and subsequent HTTP requests to succeed randomly.
				// Retry the HTTPS request once before disabling SSL for a time.
				$response = wp_remote_get( $trophymonsta_url.'?'.$request, $http_args );
				Trophymonsta::log( compact( 'trophymonsta_url', 'http_args', 'response' ) );

				if ( is_wp_error( $response ) ) {
					$ssl_failed = true;
					do_action( 'trophymonsta_https_request_failure', $response );
					do_action( 'trophymonsta_http_request_pre' );
					// Try the request again without SSL.
					$response = wp_remote_get( $http_trophymonsta_url.'?'.$request, $http_args );
					Trophymonsta::log( compact( 'http_trophymonsta_url', 'http_args', 'response' ) );
				}
			}

			if ( is_wp_error( $response ) ) {
				do_action( 'trophymonsta_request_failure', $response );
				return array( '', '' );
			}

			if ( $ssl_failed ) {
				// The request failed when using SSL but succeeded without it. Disable SSL for future requests.
				update_option( 'trophymonsta_ssl_disabled', time() );
				do_action( 'trophymonsta_https_disabled' );
			}

			return $response['body'];

		}

		/**
		 * Log debugging info to the error log.
		 *
		 * Enabled when WP_DEBUG_LOG is enabled (and WP_DEBUG, since according to
		 * core, "WP_DEBUG_DISPLAY and WP_DEBUG_LOG perform no function unless
		 * WP_DEBUG is true), but can be disabled via the trophymonsta_debug_log filter.
		 *
		 * @param mixed $trophymonsta_debug The data to log.
		 */
		public static function log( $trophymonsta_debug ) {
			if (TROPHYMONSTA_DEBUG) {
				//error_log(print_r(compact( 'trophymonsta_debug' ),true).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			}
		}


		/**
		 * Essentially a copy of WP's build_query but one that doesn't expect pre-urlencoded values.
		 *
		 * @param array $args An array of key => value pairs
		 * @return string A string ready for use as a URL query string.
		 */
		public static function build_query( $args ) {
			return _http_build_query( $args, '', '&' );
		}

		public static function view( $name, array $args = array() ) {
			$args = apply_filters( 'trophymonsta_view_arguments', $args, $name );

			foreach ( $args AS $key => $val ) {
				$$key = $val;
			}

			load_plugin_textdomain( 'trophymonsta' );

			$file = TROPHYMONSTA_PLUGIN_DIR . 'views/'. $name . '.php';

			include( $file );
		}

		public static function predefined_api_key() {
			if ( defined( 'WPCOM_API_KEY' ) ) {
				return true;
			}

			return apply_filters( 'trophymonsta_predefined_api_key', false );
		}

		public static function verify_key( $key, $ip = null ) {
			$response = self::check_key_status( $key, $ip );
			$response	= json_decode($response);

			if ($response->code == '400' || $response->code == '403' || $response->code == '404' || $response->code == '405')
				return $response->error->message;

			return $response->sucess->notifications;
		}

		public static function check_key_status( $key, $ip = null ) {
			return self::http_post( Trophymonsta::build_query( array( 'key' => $key, 'site_url' => site_url() ) ), 'verifykey', $ip );
		}

		public static function get_products_suppliers( $key, $limit, $page, $ip = null ) {
			$last_sync_date = get_option( 'trophymonsta_suppliers_last_sync_date' );
			$flag = 1;

			if ($last_sync_date) {
				$flag = 2;
			}
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Sent request suppliers data to grr with following parameter. Page No:'.$page.' key: '.$key.' limit:'.$limit.' lastDateSync:'.$last_sync_date.' flag:'.$flag.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			return self::http_get( Trophymonsta::build_query( array( 'key' => $key, 'site_url' => site_url(), 'page' => $page, 'limit' => $limit, 'lastDateSync' => $last_sync_date, 'flag' => $flag ) ), 'products/suppliers', $ip );
		}

		public static function import_products_suppliers($key, $page = 1, $ip = null) {
			global $wpdb;
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Start import suppliers process.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			$plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` = 'active_plugins' and option_value like '%/monstamanagement.php%' " );
			if ( null === $plugin_exists)
				return;
			$limit = 200;
			$total_count = 0;
			$suppliers_sync_date = '';

			do {
				$plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` = 'active_plugins' and option_value like '%/monstamanagement.php%' " );
				if ( null === $plugin_exists)
					break;
				$response	= self::get_products_suppliers($key, $limit, $page);
				if (TROPHYMONSTA_DEBUG) {
					error_log(date('Y-m-d H:i:s').' Responce received from grr for suppliers data :'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
					error_log($response.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
				}
				$response	= json_decode($response);

				if ($response->code == '400' || $response->code == '403' || $response->code == '404' || $response->code == '405'){
					if (TROPHYMONSTA_DEBUG)
						error_log(date('Y-m-d H:i:s')." suppliers PAGENO: ".$page." Error Message: ".$response->error->message.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
				}

				if ($response->code == '200') {
						$total_count = $response->success->total_count;
						$suppliers_sync_date = $response->success->sync_date;

						$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET total_count = ".$total_count.", page = ".$page.", status = 'In progress', sync = '".date('Y-m-d H:i:s')."' WHERE type = 'brand'");
						$page = $page + 1;
						$parent = null;

						foreach ($response->success->suppliers as $brands) {
							$plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` = 'active_plugins' and option_value like '%/monstamanagement.php%' " );
							if ( null === $plugin_exists)
								break;
							$term = term_exists(sanitize_title($brands->name), 'trophymonsta_brand', $parent );
							if ( is_array( $term ) ) {
								$term_id = $term['term_id'];
								if ($brands->status == 3) {

									$trophyproducts = array();
									$attachmentids = array();
									$productids = $wpdb->get_results("SELECT rel.`object_id`, posts.`post_type` FROM ".$wpdb->prefix."term_relationships as rel left join ".$wpdb->prefix."posts as posts on (rel.`object_id`=posts.ID) WHERE rel.`term_taxonomy_id` = ".$term_id);

									foreach ( $productids as $productid )	{
										$attachmentids[] = $productid->object_id;
										$trophyproducts[] = $productid->object_id;
										if ($productid->object_id != 0 && $productid->object_id != '0') {
											$product_variationids = $wpdb->get_results("SELECT ID, `post_type` FROM ".$wpdb->prefix."posts WHERE post_parent = ".$productid->object_id);

											foreach ( $product_variationids as $product_variation )	{
													if ($product_variation->post_type == 'attachment') {
														$attachmentids[] = $productid->ID;
														wp_delete_attachment($productid->ID);
													}
													$trophyproducts[] = $product_variation->ID;
											}
										}

									}

									/*if (!empty($attachmentids)) {
										$attachmentids = array_filter($attachmentids);
										$attachmentidstring = implode(',', $attachmentids);
										$attachmentposts = $wpdb->get_results("SELECT `meta_value` FROM ".$wpdb->prefix."postmeta WHERE `meta_key` LIKE '_wp_attached_file' and post_id in (".$attachmentidstring.")");
										foreach ( $attachmentposts as $attachment )	{
											$wordpress_upload_dir = wp_upload_dir();
											$new_file_path = unlink($wordpress_upload_dir['basedir'] . '/' . $productid->meta_value);
										}
									}*/

									if (!empty($trophyproducts)) {
										$implodeids = implode(',', $trophyproducts);
										$wpdb->query("DELETE FROM ".$wpdb->prefix."term_relationships WHERE object_id IN (".$implodeids.")");
										$wpdb->query("DELETE FROM ".$wpdb->prefix."postmeta WHERE post_id IN (".$implodeids.")");
										$wpdb->query("DELETE FROM ".$wpdb->prefix."posts WHERE (post_type = 'product' or post_type = 'product_variation' or post_type = 'attachment') and ID IN (".$implodeids.")");
									}
									wp_delete_term($term_id, 'trophymonsta_brand');
									$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET delete_count = (delete_count + 1), sync = '".date('Y-m-d H:i:s')."' WHERE type = 'brand'");

								} else {
									//wp_update_term($term_id, 'trophymonsta_brand', array( 'name' => sanitize_title($brands->name) ) );
									//update_term_meta($term_id, 'category_mode', 'trophymonsta');
									update_term_meta($term['term_id'], 'ranking', $brands->ranking);
									$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET update_count = (update_count + 1), sync = '".date('Y-m-d H:i:s')."' WHERE type = 'brand'");
								}
							} else {
								$term = wp_insert_term(trim(preg_replace('/\s+/', ' ', $brands->name)), 'trophymonsta_brand', array( 'parent' => intval( $parent )) );
								add_term_meta($term['term_id'], 'category_mode', 'trophymonsta', true);
								add_term_meta($term['term_id'], 'ranking', $brands->ranking, true);
								if ( is_wp_error( $term ) ) {
									if (TROPHYMONSTA_DEBUG)
										error_log(date('Y-m-d H:i:s').' suppliers '.is_wp_error( $term ).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
								}
								$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET create_count = (create_count + 1), sync = '".date('Y-m-d H:i:s')."' WHERE type = 'brand'");
								$term_id = $term['term_id'];
							}

						}
				}
				$lastpage = ceil($total_count/$limit);
			} while ($lastpage > ($page - 1));

			$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET total_count = ".$total_count.", status = 'Completed', sync = '".date('Y-m-d H:i:s')."' WHERE type = 'brand'");

			$last_sync_date = get_option( 'trophymonsta_suppliers_last_sync_date' );
			if ($suppliers_sync_date != '') {
				if ($last_sync_date) {
					update_option( 'trophymonsta_suppliers_last_sync_date', $suppliers_sync_date);
				} else {
					add_option( 'trophymonsta_suppliers_last_sync_date', $suppliers_sync_date);
				}
			}
			update_option('trophymonsta_last_sync_date', get_option( 'trophymonsta_suppliers_last_sync_date' ));
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Completed import suppliers process.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			return true;
		}

		public static function get_grouping( $key, $limit, $page, $ip = null ) {
			//$last_sync_date = get_option( 'trophymonsta_grouping_last_sync_date' );
			$last_sync_date = '';
			$flag = 1;

			/*if ($last_sync_date) {
				$flag = 2;
			}*/

			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Sent request grouping data to grr with following parameter. Page No:'.$page.' key: '.$key.' limit:'.$limit.' lastDateSync:'.$last_sync_date.' flag:'.$flag.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");

			return self::http_get( Trophymonsta::build_query( array( 'key' => $key, 'site_url' => get_option( 'home' ), 'page' => $page, 'limit' => $limit, 'lastDateSync' => $last_sync_date, 'flag' => $flag ) ), 'products/grouping', $ip );

		}

		public static function import_grouping($key, $page = 1, $ip = null) {

			global $wpdb;

			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Start import grouing process.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			$plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` = 'active_plugins' and option_value like '%/monstamanagement.php%' " );
			if ( null === $plugin_exists)
				return;

			$limit = 200;
			$total_count = 0;
			$grouping_sync_date = '';

			do {
				$plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` = 'active_plugins' and option_value like '%/monstamanagement.php%' " );
				if ( null === $plugin_exists)
					break;

				$response	= self::get_grouping($key, $limit, $page,  $ip = null);

				if (TROPHYMONSTA_DEBUG) {
					error_log(date('Y-m-d H:i:s').' Responce received from grr for grounig data :'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
					error_log($response.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
				}

				$response	= json_decode($response);

				if ($response->code == '400' || $response->code == '403' || $response->code == '404' || $response->code == '405') {
					if (TROPHYMONSTA_DEBUG)
						error_log(date('Y-m-d H:i:s').' Grouping No record fond :'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
				}

				if ($response->code == '200') {
						$total_count = $response->success->total_count;
						$grouping_sync_date = $response->success->sync_date;
						$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET total_count = ".$total_count.", page = ".$page.", status = 'In progress', sync = '".date('Y-m-d H:i:s')."' WHERE type = 'grouping'");
						$page = $page + 1;

						$monsta_categories_id = null;
						$term = term_exists( 'monsta-categories', 'product_cat', null );
						if ( is_array( $term ) ) {
						  $monsta_categories_id	= $term['term_id'];
						}

						foreach($response->success->grouping as $grouping) {
					    $term = term_exists(sanitize_title('grouping-'.$monsta_categories_id.'-'.$grouping->master_id), 'product_cat', $monsta_categories_id);
							//error_log(date('Y-m-d H:i:s').' Grouping OUT has_product :'.$grouping->has_product.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
							if ($grouping->status == 3 || $grouping->has_product == 0) {
								//error_log(date('Y-m-d H:i:s').' Grouping has_product :'.$grouping->has_product.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
								if (is_array( $term )) {
									$term_id = $term['term_id'];

									$term_children = get_term_children( $term_id, 'product_cat' );
									$child_term_ids = array();
									$child_term_ids[] = $term_id;
							    if ( !empty( $term_children ) ) {
							        foreach ( $term_children as $term_child ) {
							            wp_delete_term( $term_child, 'product_cat' );
							        }
							    }

									$group_exists = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."term_relationships WHERE term_taxonomy_id = ".$term_id );
									if ( null !== $group_exists) {
										//$pterm = get_term_by('term_id', $term_id, 'product_cat' );
										//error_log(date('Y-m-d H:i:s').' Grouping zero term :'.$term_id.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");

										$wpdb->query("DELETE FROM ".$wpdb->prefix."term_relationships WHERE term_taxonomy_id in (".implode(',',$child_term_ids).")");
										$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET create_count = (create_count - 1), delete_count = (delete_count + 1), sync = '".date('Y-m-d H:i:s')."' WHERE  type = 'grouping'");
									}
									wp_delete_term($term_id, 'product_cat');

									// create new grouping menu and assign to monsta menu
									$mansta_menu = term_exists('Monsta Menu', 'nav_menu');
									if ( is_array( $mansta_menu ) ) {
										$menu_items = wp_get_nav_menu_items($mansta_menu['term_id'], array('post_status' => 'publish,draft'));
										foreach ($menu_items as $menu_item) {
											// Item already in menu?
											$_group_id = get_post_meta( $menu_item->ID, '_group_id', true );
											if ($_group_id == $grouping->master_id) {
												wp_delete_post($menu_item->ID);
											}
										}
									}
								}
							} else {
								if (is_array( $term )) {
									$grouping_term_id = $term['term_id'];
									wp_update_term( $grouping_term_id, 'product_cat', array('name' => trim(preg_replace('/\s+/', ' ', $grouping->name))));
									self::upload_term_image($grouping->image, $grouping_term_id);
									// create new grouping menu and assign to monsta menu
									$mansta_menu = term_exists('Monsta Menu', 'nav_menu');
									if ( is_array( $mansta_menu ) ) {
										$menu_items = wp_get_nav_menu_items($mansta_menu['term_id'], array('post_status' => 'publish,draft'));

										foreach ($menu_items as $menu_item) {
										 // Item already in menu?
										 $_group_id = get_post_meta( $menu_item->ID, '_group_id', true );
										 if ($_group_id == $grouping->master_id) {
												$updatemenuid = wp_update_nav_menu_item($mansta_menu['term_id'], $menu_item->ID, array(
														'menu-item-title'   =>  __($grouping->name, 'trophymonsta' ),
														'menu-item-classes' => $grouping->name,
														'menu-item-url'     => home_url( 'product-category/monsta-categories/'.sanitize_title('grouping-'.$monsta_categories_id.'-'.$grouping->master_id) ),
														'menu-item-status'  => 'publish'
												));
												update_post_meta($updatemenuid, '_trophymonsta_text_field', 'trophymonsta', true );
												update_post_meta($updatemenuid, '_group_id', $grouping->master_id, true );
										}
									}
								}
								$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET update_count = (update_count + 1), sync = '".date('Y-m-d H:i:s')."' WHERE type = 'grouping'");
								} else {
									// create new grouping
									$gterm = wp_insert_term(trim(preg_replace('/\s+/', ' ', $grouping->name)), 'product_cat', array('parent' => intval($monsta_categories_id), 'slug' => sanitize_title('grouping-'.$monsta_categories_id.'-'.$grouping->master_id)));
									$grouping_term_id = $gterm['term_id'];
									self::upload_term_image($grouping->image, $grouping_term_id);
									add_term_meta($gterm['term_id'], 'category_mode', 'trophymonsta', true);
									$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET create_count = (create_count + 1), sync = '".date('Y-m-d H:i:s')."' WHERE type = 'grouping'");
								}
							}
					  }
				}
				$lastpage = ceil($total_count/$limit);
			} while ($lastpage > ($page - 1));

			$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET total_count = ".$total_count.", status = 'Completed', sync = '".date('Y-m-d H:i:s')."' WHERE type = 'grouping'");

			if ($grouping_sync_date != '') {
				$last_sync_date = get_option( 'trophymonsta_grouping_last_sync_date' );
				if ($last_sync_date) {
					update_option( 'trophymonsta_grouping_last_sync_date', $grouping_sync_date);
				} else {
					add_option( 'trophymonsta_grouping_last_sync_date', $grouping_sync_date);
				}
			}

			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Completed import grouing process.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");

			return true;

		}

		public static function get_process( $key, $ip = null ) {
			$last_sync_date = get_option( 'trophymonsta_processes_last_sync_date' );
			$flag = 1;

			if ($last_sync_date) {
				$flag = 2;
			}
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Get process data from grr with following parameter. key: '.$key.' flag:'.$flag.' last_sync_date:'.$last_sync_date .PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			return self::http_get( Trophymonsta::build_query( array( 'key' => $key, 'site_url' => site_url(), 'lastDateSync' => $last_sync_date, 'flag' => $flag) ), 'store/getEngravingProcess', $ip );

		}

		public static function import_process($key, $ip = null) {
			global $wpdb;
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Start import process data.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			$plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` = 'active_plugins' and option_value like '%/monstamanagement.php%' " );
			if ( null === $plugin_exists)
				return;

			$response	= self::get_process($key);

			if (TROPHYMONSTA_DEBUG) {
				error_log(date('Y-m-d H:i:s').' Responce received from grr for process data :'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
				error_log($response.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			}
			$response	= json_decode($response);

			if ($response->code == '400' || $response->code == '403' || $response->code == '404' || $response->code == '405') {
				if (TROPHYMONSTA_DEBUG)
					error_log(date('Y-m-d H:i:s')." process Error Message: ".$response->error->message.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			}

			if ($response->code == '200') {
				$monsta_categories_id = null;
				$term = term_exists( 'monsta-categories', 'product_cat', null );
				if ( is_array( $term ) ) {
				  $monsta_categories_id	= $term['term_id'];
				}

				$total_count = $response->success->process_total_count;
				$processes_sync_date = $response->sync_date;
				$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET total_count = ".$total_count.", page = 1, status = 'In progress', sync = '".date('Y-m-d H:i:s')."' WHERE type = 'processes'");

				// grouping
				foreach ($response->success->groupings as $grouping) {
					foreach ($grouping->departments as $departments) {
						//processes
						if (!empty((array)$departments->process)) {
							foreach($departments->process as $processes) {
									$taxonomy = 'pa_monstaprocess';
									$termslug = sanitize_title('process-'.$departments->id.'-'.$grouping->master_id.'-'.$processes->id);
									if ($processes->status == 3) {
										// Check if the Term name exist and if not we create it.
										$term = term_exists($termslug, $taxonomy, null );
										if (is_array( $term )) {
											$term_id = $term['term_id'];
											$pterm = get_term_by('term_id', $term_id, $taxonomy );
											$wpdb->query("DELETE FROM ".$wpdb->prefix."postmeta WHERE meta_key = '".$taxonomy."' and meta_value = '".$pterm->slug."'");
											wp_delete_term($term_id, $taxonomy);
											$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET create_count = (create_count - 1), delete_count = (delete_count + 1), sync = '".date('Y-m-d H:i:s')."' WHERE  type = 'processes'");
										}
									} else {
										// Check if the Term name exist and if not we create it.
										$term = term_exists($termslug, $taxonomy, null );
										if (is_array( $term )) {
											$term_id = $term['term_id'];
											wp_update_term( $term_id, $taxonomy, array('name' => trim(preg_replace('/\s+/', ' ', $processes->name))));
											update_term_meta($term['term_id'], 'process_chars', $processes->chars);
											update_term_meta($term['term_id'], 'process_lines', $processes->lines);
											update_term_meta($term['term_id'], 'process_departments_grouping', $departments->id.'___'.$grouping->master_id.'___'.$processes->id);
											update_term_meta($term['term_id'], '_trophymonsta_grouping_id', $grouping->master_id);
											update_term_meta($term['term_id'], '_trophymonsta_department_id', $departments->id);
											update_term_meta($term['term_id'], '_trophymonsta_department_name', $departments->name);
											update_term_meta($term['term_id'], '_trophymonsta_processes_id', $processes->id);
											$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET update_count = (update_count + 1), sync = '".date('Y-m-d H:i:s')."' WHERE type = 'processes'");
										} else {
											$term = wp_insert_term(trim(preg_replace('/\s+/', ' ', $processes->name)), $taxonomy, array('slug' => $termslug) );
											add_term_meta($term['term_id'], 'category_mode', 'trophymonsta', true);
											add_term_meta($term['term_id'], 'process_chars', $processes->chars, true);
											add_term_meta($term['term_id'], 'process_lines', $processes->lines, true);
											add_term_meta($term['term_id'], 'process_departments_grouping', $departments->id.'___'.$grouping->master_id.'___'.$processes->id, true);
											add_term_meta($term['term_id'], '_trophymonsta_grouping_id', $grouping->master_id, true);
											add_term_meta($term['term_id'], '_trophymonsta_department_id', $departments->id, true);
											add_term_meta($term['term_id'], '_trophymonsta_department_name', $departments->name, true);
											add_term_meta($term['term_id'], '_trophymonsta_processes_id', $processes->id, true);
											$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET create_count = (create_count + 1), sync = '".date('Y-m-d H:i:s')."' WHERE type = 'processes'");
											
											/*$existpostmetas = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."postmeta WHERE `meta_value` = '".$departments->id."___".$grouping->master_id."___".$processes->id."' and `meta_key` = 'process_departments_grouping'" );
											$replacequerystring = '';
											foreach ( $existpostmetas as $postmeta ) {
												$replacequerystring .= "(".$postmeta->post_id.", '".$taxonomy."', '".$termslug."'),";
											}
											if ($replacequerystring != '')
												$wpdb->query("INSERT INTO ".$wpdb->prefix."postmeta (post_id, meta_key, meta_value) values ".rtrim($replacequerystring,','));*/
										}

										/*$existpostmetas = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."postmeta WHERE `meta_value` = '".$departments->id."___".$grouping->master_id."___".$processes->id."' and `meta_key` = 'process_departments_grouping'" );
										foreach ( $existpostmetas as $postmeta ) {
											// Get the post Terms names from the parent variable product.
											//$post_term_names =  wp_get_post_terms( $postmeta->post_id, $taxonomy, array('fields' => 'slug') );
											$post_term_names =  get_post_meta( $postmeta->post_id, $taxonomy, false );
											// Check if the post term exist and if not we set it in the parent variable product.
											if(!in_array($termslug, $post_term_names)) {
												add_post_meta($postmeta->post_id, $taxonomy, $termslug, false);
											}
										}*/
										
										
								}
							}
						}
					}
				}
				$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET total_count = ".$total_count.", status = 'Completed', sync = '".date('Y-m-d H:i:s')."' WHERE type = 'processes'");
			}

			$last_sync_date = get_option( 'trophymonsta_processes_last_sync_date' );
			if ($processes_sync_date != '') {
				if ($last_sync_date) {
					update_option( 'trophymonsta_processes_last_sync_date', $processes_sync_date);
				} else {
					add_option( 'trophymonsta_processes_last_sync_date', $processes_sync_date);
				}
			}
			update_option('trophymonsta_last_sync_date', get_option( 'trophymonsta_processes_last_sync_date' ));

			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Completed processes .'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			return true;
		}

		public static function get_no_process( $key, $ip = null ) {
			$last_sync_date = get_option( 'trophymonsta_noprocesses_last_sync_date' );
			$flag = 1;

			if ($last_sync_date) {
				$flag = 2;
			}
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Get no process data from grr with following parameter. key: '.$key.' flag:'.$flag.' last_sync_date:'.$last_sync_date .PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			return self::http_get( Trophymonsta::build_query( array( 'key' => $key, 'site_url' => site_url(), 'lastDateSync' => $last_sync_date, 'flag' => $flag) ), 'store/getEngravingNoProcess', $ip );

		}

		public static function import_no_process($key, $ip = null) {
			global $wpdb;
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Start import no process data.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			$plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` = 'active_plugins' and option_value like '%/monstamanagement.php%' " );
			if ( null === $plugin_exists)
				return;

			$response	= self::get_no_process($key);

			if (TROPHYMONSTA_DEBUG) {
				error_log(date('Y-m-d H:i:s').' Responce received from grr for no process data :'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
				error_log($response.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			}
			$response	= json_decode($response);

			if ($response->code == '400' || $response->code == '403' || $response->code == '404' || $response->code == '405') {
				if (TROPHYMONSTA_DEBUG)
					error_log(date('Y-m-d H:i:s')." no process Error Message: ".$response->error->message.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			}

			if ($response->code == '200') {
				$monsta_categories_id = null;
				$term = term_exists( 'monsta-categories', 'product_cat', null );
				if ( is_array( $term ) ) {
				  $monsta_categories_id	= $term['term_id'];
				}

				$total_count = $response->success->noprocess_total_count;
				$noprocesses_sync_date = $response->sync_date;
				$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET total_count = ".$total_count.", page = 1, status = 'In progress', sync = '".date('Y-m-d H:i:s')."' WHERE type = 'noprocesses'");

				// grouping
				foreach ($response->success->groupings as $grouping) {
					foreach ($grouping->departments as $departments) {
						$taxonomy = 'pa_monstaengraving';
						foreach ($departments->process as $process) {
							$termslug = sanitize_title('noprocess-'.$departments->id.'-'.$grouping->master_id.'-'.$process->id);
							if ($process->status == 3) {
								// Check if the Term name exist and if not we create it.
								$term = term_exists($termslug, $taxonomy, null );
								if (is_array( $term )) {
									$term_id = $term['term_id'];
									$pterm = get_term_by('term_id', $term_id, 'pa_monstaengraving' );
									$wpdb->query("DELETE FROM ".$wpdb->prefix."postmeta WHERE meta_key = 'pa_monstaengraving' and meta_value = '".$pterm->slug."'");
									wp_delete_term($term_id, $taxonomy);
									$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET create_count = (create_count - 1), delete_count = (delete_count + 1), sync = '".date('Y-m-d H:i:s')."' WHERE  type = 'noprocesses'");
								}
							} else {
								// Check if the Term name exist and if not we create it.
								$term = term_exists($termslug, $taxonomy, null);
								if (is_array( $term )) {
									$term_id = $term['term_id'];
									wp_update_term( $term_id, $taxonomy, array('name' => trim(preg_replace('/\s+/', ' ', $process->title))));
									update_term_meta($term['term_id'], 'process_name', $process->name);
									update_term_meta($term['term_id'], 'process_chars', $process->chars);
									update_term_meta($term['term_id'], 'process_lines', $process->lines);
									update_term_meta($term['term_id'], 'process_price', $process->price);
									update_term_meta($term['term_id'], 'process_departments_grouping_taxonomy', $departments->id.'___'.$grouping->master_id.'___'.$taxonomy);
									update_term_meta($term['term_id'], '_trophymonsta_grouping_id', $grouping->master_id);
									update_term_meta($term['term_id'], '_trophymonsta_department_id', $departments->id);
									update_term_meta($term['term_id'], '_trophymonsta_department_name', $departments->name);
									update_term_meta($term['term_id'], '_trophymonsta_no_processes_id', $process->id);
								} else {
								    $term = wp_insert_term(trim(preg_replace('/\s+/', ' ', $process->title)), $taxonomy, array('slug' => $termslug) ); // Create the term
									add_term_meta($term['term_id'], 'category_mode', 'trophymonsta', true);
									add_term_meta($term['term_id'], 'process_name', $process->name, true);
									add_term_meta($term['term_id'], 'process_chars', $process->chars, true);
									add_term_meta($term['term_id'], 'process_lines', $process->lines, true);
									add_term_meta($term['term_id'], 'process_price', $process->price, true);
									add_term_meta($term['term_id'], 'process_departments_grouping_taxonomy', $departments->id.'___'.$grouping->master_id.'___'.$taxonomy, true);
									add_term_meta($term['term_id'], '_trophymonsta_grouping_id', $grouping->master_id, true);
									add_term_meta($term['term_id'], '_trophymonsta_department_id', $departments->id, true);
									add_term_meta($term['term_id'], '_trophymonsta_department_name', $departments->name, true);
									add_term_meta($term['term_id'], '_trophymonsta_no_processes_id', $process->id, true);
									$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET create_count = (create_count + 1), sync = '".date('Y-m-d H:i:s')."' WHERE type = 'noprocesses'");
									$existpostmetas = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."postmeta WHERE `meta_value` = '".$departments->id."___".$grouping->master_id."___".$taxonomy."' and `meta_key` = 'process_departments_grouping_taxonomy'" );
									/*$replacequerystring = '';
									foreach ( $existpostmetas as $postmeta ) {
										$replacequerystring .= "(".$postmeta->post_id.", '".$taxonomy."', '".$termslug."'),";
									}
									if ($replacequerystring != '') {
										error_log(date('Y-m-d H:i:s')." no process QUERY: ".$replacequerystring.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
										$wpdb->query("INSERT INTO ".$wpdb->prefix."postmeta (post_id, meta_key, meta_value) values ".rtrim($replacequerystring,','));
									}*/
								}

								/*$existpostmetas = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."postmeta WHERE `meta_value` = '".$departments->id."___".$grouping->master_id."___".$taxonomy."' and `meta_key` = 'process_departments_grouping_taxonomy'" );
								foreach ( $existpostmetas as $postmeta ) {
									// Get the post Terms names from the parent variable product.
									//$post_term_names =  wp_get_post_terms( $postmeta->post_id, $taxonomy, array('fields' => 'slug') );
									$post_term_names =  get_post_meta( $postmeta->post_id, $taxonomy, false );
									// Check if the post term exist and if not we set it in the parent variable product.
									if(!in_array($termslug, $post_term_names)) {
										add_post_meta($postmeta->post_id, $taxonomy, $termslug, false);
									}
								}*/
								
							}

						}
					}
				}
				$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET total_count = ".$total_count.", status = 'Completed', sync = '".date('Y-m-d H:i:s')."' WHERE type = 'noprocesses'");
			}

			$last_sync_date = get_option( 'trophymonsta_noprocesses_last_sync_date' );
			if ($noprocesses_sync_date != '') {
				if ($last_sync_date) {
					update_option( 'trophymonsta_noprocesses_last_sync_date', $noprocesses_sync_date);
				} else {
					add_option( 'trophymonsta_noprocesses_last_sync_date', $noprocesses_sync_date);
				}
			}
			update_option('trophymonsta_last_sync_date', get_option( 'trophymonsta_noprocesses_last_sync_date' ));

			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Completed no processes .'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			return true;
		}

		public static function get_material( $key, $ip = null ) {
			$last_sync_date = get_option( 'trophymonsta_material_last_sync_date' );
			$flag = 1;

			if ($last_sync_date) {
				$flag = 2;
			}
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Get material data from grr with following parameter. key: '.$key.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");

			return self::http_get( Trophymonsta::build_query( array( 'key' => $key, 'site_url' => site_url(), 'lastDateSync' => $last_sync_date, 'flag' => $flag) ), 'products/materials', $ip );
		}

		public static function import_material($key, $ip = null) {
			global $wpdb;
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Start import material data.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			$plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` = 'active_plugins' and option_value like '%/monstamanagement.php%' " );
			if ( null === $plugin_exists)
				return;

			$response	= self::get_material($key, $ip = null);

			if (TROPHYMONSTA_DEBUG) {
				error_log(date('Y-m-d H:i:s').' Responce received from grr for material data :'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
				error_log($response.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			}
			$response	= json_decode($response);
            $material_sync_date = '';
			if ($response->code == '400' || $response->code == '403' || $response->code == '404' || $response->code == '405'){
				if (TROPHYMONSTA_DEBUG)
					error_log(date('Y-m-d H:i:s')." material Error Message: ".$response->error->message.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			}

			if ($response->code == '200') {
				$monsta_categories_id = null;
				$term = term_exists( 'monsta-categories', 'product_cat', null );
				if ( is_array( $term ) ) {
				  $monsta_categories_id	= $term['term_id'];
				}
				$total_count = $response->success->total_count;
				$material_sync_date = $response->success->sync_date;
				$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET total_count = ".$total_count.", page = 1, status = 'In progress', sync = '".date('Y-m-d H:i:s')."' WHERE type = 'material'");
				$taxonomy = 'pa_monstamaterial';
				foreach ($response->success->materials as $materials) {
					// Check if the Term name exist and if not we create it.
					$term = term_exists(sanitize_title($materials->name.'-'.$monsta_categories_id), $taxonomy, null );
					if (is_array( $term )) {
						$term_id = $term['term_id'];
						wp_update_term( $term_id, $taxonomy, array('name' => trim(preg_replace('/\s+/', ' ', $materials->name))));
						update_term_meta($term['term_id'], 'category_mode', 'trophymonsta');
						update_term_meta($term['term_id'], 'material_id', $materials->id);
					} else {
						$term = wp_insert_term(trim(preg_replace('/\s+/', ' ', $materials->name)), $taxonomy, array('slug' => sanitize_title($materials->name.'-'.$monsta_categories_id)) ); // Create the term
						add_term_meta($term['term_id'], 'category_mode', 'trophymonsta', true);
						add_term_meta($term['term_id'], 'material_id', $materials->id, true);
						$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET create_count = (create_count + 1), sync = '".date('Y-m-d H:i:s')."' WHERE type = 'material'");
					}

				}

				$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET total_count = ".$total_count.", status = 'Completed', sync = '".date('Y-m-d H:i:s')."' WHERE type = 'material'");
			}

			$last_sync_date = get_option( 'trophymonsta_material_last_sync_date' );
			if ($material_sync_date != '') {
				if ($last_sync_date) {
					update_option( 'trophymonsta_material_last_sync_date', $material_sync_date);
				} else {
					add_option( 'trophymonsta_material_last_sync_date', $material_sync_date);
				}
			}
			update_option('trophymonsta_last_sync_date', get_option( 'trophymonsta_material_last_sync_date' ));

			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Completed accessories .'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			return true;
		}

		public static function get_accessories( $key, $limit, $page, $ip = null ) {

			$last_sync_date = get_option( 'trophymonsta_accessories_last_sync_date' );
			$flag = 1;

			if ($last_sync_date) {
				$flag = 2;
			}
			
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Sent request accessories data to grr with following parameter. Page No:'.$page.' key: '.$key.' limit:'.$limit.' lastDateSync:'.$last_sync_date.' flag:'.$flag.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");

			return self::http_get( Trophymonsta::build_query( array( 'key' => $key, 'site_url' => site_url(), 'page' => $page, 'limit' => $limit, 'lastDateSync' => $last_sync_date, 'flag' => $flag ) ), 'store/getAccessorySettings', $ip );
		}

		public static function import_accessories($key, $page = 1, $ip = null) {
			global $wpdb;
			
			$limit = 1;
			$total_count = 0;
			$accessories_sync_date = '';
			
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Start import accessories data.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			
			do {
				$plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` = 'active_plugins' and option_value like '%/monstamanagement.php%' " );
				if ( null === $plugin_exists)
					return;
				
				$import_log = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."trophymonsta_import_log WHERE type = 'accessories'" );
			
				if (isset($import_log->page) >= 1) {
					$page = $import_log->page;
				}
				$response	= self::get_accessories($key, $limit, $page, $ip = null);
				
				if (TROPHYMONSTA_DEBUG) {
					error_log(date('Y-m-d H:i:s').' Responce received from grr for accessories data :'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
					error_log(print_r($response, true).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
				}
				$response	= json_decode($response);

				if ($response->code == '400' || $response->code == '403' || $response->code == '404' || $response->code == '405'){
					if (TROPHYMONSTA_DEBUG)
						error_log(date('Y-m-d H:i:s')." accessories Error Message: ".$response->error->message.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
				}
				
				if ($response->code == '200') {
				
					$monsta_categories_id = null;
					$term = term_exists( 'monsta-categories', 'product_cat', null );
					if ( is_array( $term ) ) {
					  $monsta_categories_id	= $term['term_id'];
					}
					$total_count = $response->accessory_settings_total_count;
					$accessories_sync_date = $response->sync_date;
					$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET total_count = ".$total_count.", page = ".$page.", status = 'In progress', sync = '".date('Y-m-d H:i:s')."' WHERE type = 'accessories'");
					$page = $page + 1;
						
					
					foreach ($response->success->groupings as $grouping) {
						foreach ($grouping->accessories as $accessories) {
							if ($accessories->name == 'CC1' || $accessories->name == 'CC2') {
								$taxonomy = 'pa_monsta'.sanitize_title($accessories->name);
							} else {
								$taxonomy = 'pa_monsta'.sanitize_title($accessories->id);
							}
							foreach ($accessories->components as $components) {
								$termslug = sanitize_title($grouping->master_id.'-'.$components->id);
								if ($components->status == 3) {
									// Check if the Term name exist and if not we create it.
									$term = term_exists($termslug, $taxonomy, null );
									if (is_array( $term )) {
										$term_id = $term['term_id'];
										$pterm = get_term_by('term_id', $term_id, $taxonomy );
										$wpdb->query("DELETE FROM ".$wpdb->prefix."postmeta WHERE meta_key = '".$taxonomy."' and meta_value = '".$pterm->slug."'");
										wp_delete_term($term_id, $taxonomy);
										$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET create_count = (create_count - 1), delete_count = (delete_count + 1), sync = '".date('Y-m-d H:i:s')."' WHERE  type = 'accessories'");
									}
								} else {
									// Check if the Term name exist and if not we create it.
									$term = term_exists($termslug, $taxonomy, null );
									if (is_array( $term )) {
										$term_id = $term['term_id'];
										wp_update_term( $term_id, $taxonomy, array('name' => trim(preg_replace('/\s+/', ' ', $components->name))));
										if ($components->is_image_updated == 1) {
											if(isset($components->medium_image) && $components->medium_image != '') {
												$components_image_id = self::upload_term_image($components->medium_image, $term_id);
												$components_image_url = wp_get_attachment_url( $components_image_id );
												update_term_meta($term['term_id'], 'components_image', $components_image_url);
											} else {
												$imageexist = $wpdb->get_row("SELECT ID FROM ".$wpdb->prefix."posts WHERE `post_type` = 'attachment' and `post_parent` = '".$term_id."'");
												// if image is exists
												if(isset($imageexist) && isset($imageexist->ID)) {
													wp_delete_attachment( $imageexist->ID );
												}
											}
										}
										update_term_meta($term['term_id'], 'category_mode', 'trophymonsta');
										update_term_meta($term['term_id'], 'components_grouping_taxonomy', $grouping->master_id.'___'.$taxonomy);
										update_term_meta($term['term_id'], 'components_code', $components->code);
										update_term_meta($term['term_id'], 'components_price', $components->price);
										update_term_meta($term['term_id'], 'components_id', $components->id);
										update_term_meta($term['term_id'], 'components_suppliers_name', $components->suppliers->name);
										update_term_meta($term['term_id'], 'components_suppliers_id', $components->suppliers->id);
										update_term_meta($term['term_id'], 'components_order_id', $components->order_id);
										$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET update_count = (update_count + 1), sync = '".date('Y-m-d H:i:s')."' WHERE type = 'accessories'");
									} else {
										$term = wp_insert_term(trim(preg_replace('/\s+/', ' ', $components->name)), $taxonomy, array('slug' => $termslug) ); // Create the term

										//error_log(date('Y-m-d H:i:s')." accessories Error LOGS: ".print_r($term, true).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
										$components_image_id = self::upload_term_image($components->medium_image, $term['term_id']);
										$components_image_url = wp_get_attachment_url( $components_image_id );
										add_term_meta($term['term_id'], 'category_mode', 'trophymonsta', true);
										add_term_meta($term['term_id'], 'components_grouping_taxonomy', $grouping->master_id.'___'.$taxonomy, true);
										add_term_meta($term['term_id'], 'components_image', $components_image_url, true);						
										add_term_meta($term['term_id'], 'components_code', $components->code, true);
										add_term_meta($term['term_id'], 'components_price', $components->price, true);
										add_term_meta($term['term_id'], 'components_id', $components->id, true);
										add_term_meta($term['term_id'], 'components_suppliers_name', $components->suppliers->name, true);
										add_term_meta($term['term_id'], 'components_suppliers_id', $components->suppliers->id, true);
										add_term_meta($term['term_id'], 'components_order_id', $components->order_id, true);
										$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET create_count = (create_count + 1), sync = '".date('Y-m-d H:i:s')."' WHERE type = 'accessories'");
										$existpostmetas = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."postmeta WHERE `meta_value` = '".$grouping->master_id."___".$taxonomy."' and `meta_key` = 'components_grouping_taxonomy'" );
										$replacequerystring = '';
										foreach ( $existpostmetas as $postmeta ) {
											$replacequerystring .= "(".$postmeta->post_id.", '".$taxonomy."', '".$termslug."'),";
										}
										if ($replacequerystring != '')
											$wpdb->query("INSERT INTO ".$wpdb->prefix."postmeta (post_id, meta_key, meta_value) values ".rtrim($replacequerystring,','));
									}

									/*$existpostmetas = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."postmeta WHERE `meta_value` = '".$grouping->master_id."___".$taxonomy."' and `meta_key` = 'components_grouping_taxonomy'" );
									foreach ( $existpostmetas as $postmeta ) {
										// Get the post Terms names from the parent variable product.
										//$post_term_names =  wp_get_post_terms( $postmeta->post_id, $taxonomy, array('fields' => 'slug') );
										$post_term_names =  get_post_meta( $postmeta->post_id, $taxonomy, false );
										//error_log(date('Y-m-d H:i:s').' post_term_names.'.print_r($post_term_names,true).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
										// Check if the post term exist and if not we set it in the parent variable product.
										if(!in_array($termslug, $post_term_names)) {
											add_post_meta($postmeta->post_id, $taxonomy, $termslug, false);
										}
									}*/
								}

							}
						}
					}
					
					$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET total_count = ".$total_count.", page = ".$page.", status = 'In progress', sync = '".date('Y-m-d H:i:s')."' WHERE type = 'accessories'");
					if (TROPHYMONSTA_DEBUG)
						error_log(date('Y-m-d H:i:s')." accessories PAGENO: ".($page - 1)." Import Completed".PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
					
				}
				
				$lastpage = ceil($total_count/$limit);
			} while ($lastpage > ($page - 1));
			
			$last_sync_date = get_option( 'trophymonsta_accessories_last_sync_date' );
			if ($accessories_sync_date != '') {
				if ($last_sync_date) {
					update_option( 'trophymonsta_accessories_last_sync_date', $accessories_sync_date);
				} else {
					add_option( 'trophymonsta_accessories_last_sync_date', $accessories_sync_date);
				}
			}
			update_option('trophymonsta_last_sync_date', get_option( 'trophymonsta_accessories_last_sync_date' ));

			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Completed accessories .'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET total_count = ".$total_count.", page = '1', status = 'Completed', sync = '".date('Y-m-d H:i:s')."' WHERE type = 'accessories'");
			
			
			return true;
		}

		public static function get_products( $key, $limit, $page, $ip = null ) {
			$last_sync_date = get_option( 'trophymonsta_product_last_sync_date' );
			$flag = 1;

			if ($last_sync_date) {
				$flag = 2;
			}
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Sent request Products data to grr with following parameter. Page No:'.$page.' key: '.$key.' limit:'.$limit.' lastDateSync:'.$last_sync_date.' flag:'.$flag.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");

			return self::http_get( Trophymonsta::build_query( array( 'key' => $key, 'site_url' => site_url(), 'page' => $page, 'limit' => $limit, 'lastDateSync' => $last_sync_date, 'flag' => $flag ) ), 'products', $ip );
		}

		public static function import_products($mm_key, $page = 1, $ip = null) {
			global $wpdb;
			if (TROPHYMONSTA_DEBUG)
				error_log(date('Y-m-d H:i:s').' Start import products process.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			$plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` = 'active_plugins' and option_value like '%/monstamanagement.php%' " );
			if ( null === $plugin_exists)
				return;
			$limit = 10;
			$total_count = 0;
			$parsed_data = array();
			$product_sync_date = '';
			$sync_page = $page;
			$new_product_count = 0;
			$update_product_count = 0;
			$delete_product_count = 0;
			$monsta_categories_id = null;
			$monsta_ungrouped_id = null;

			$import_log = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."trophymonsta_import_log WHERE type = 'product'" );

			$term = term_exists( 'monsta-categories', 'product_cat', null );
			if ( is_array( $term ) ) {
			  $monsta_categories_id	= $term['term_id'];
			}

			$term = term_exists( 'ungrouped', 'product_cat', $monsta_categories_id );
			if ( is_array( $term ) ) {
				$monsta_ungrouped_id = $term['term_id'];
			}

			if (isset($import_log->page) >= 1) {
				$page = $import_log->page;
				$new_product_count = $import_log->create_count;
				$update_product_count = $import_log->update_count;
				$delete_product_count = $import_log->delete_count;
			}

			$response	= self::get_products($mm_key, $limit, $page,  $ip = null);
			if (TROPHYMONSTA_DEBUG) {
				error_log(date('Y-m-d H:i:s').' Responce received from grr for Products data :'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
				error_log($response.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			}

			$response	= json_decode($response);

			if ($response->code == '400' || $response->code == '403' || $response->code == '404' || $response->code == '405') {
				if (TROPHYMONSTA_DEBUG)
					error_log(date('Y-m-d H:i:s')." Products PAGENO: ".$page." Error Message: ".$response->error->message.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
				$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET total_count = 0, page = ".$page.", status = 'Completed', sync = '".date('Y-m-d H:i:s')."' WHERE  type = 'product'");

				$last_sync_date = get_option( 'trophymonsta_product_last_sync_date' );

				$total_sync_count = (int) get_option( 'trophymonsta_total_sync_count' );
				update_option('trophymonsta_total_sync_count', ($total_sync_count+1));
				update_option('trophymonsta_last_sync_date', get_option( 'trophymonsta_product_last_sync_date' ));
				$response = self::http_post( json_encode(array('sync_status' => 1)) , 'store/updatesynchstatus', 'json' );
				wp_clear_scheduled_hook('trophymonsta_product_import');
				$last_sync_status = get_option( 'monstamanagement_sync_status' );
				if ($last_sync_status) {
					update_option( 'monstamanagement_sync_status', 'monstaerror');
				} else {
					add_option( 'monstamanagement_sync_status', 'monstaerror');
				}
				//wpdb::close();
				if (TROPHYMONSTA_DEBUG)
					error_log(date('Y-m-d H:i:s').' Completed import products process.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			}

			if ($response->code == '200') {
				$total_count = $response->success->total_count;
				$product_sync_date = $response->success->sync_date;
				$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET total_count = ".$total_count.",  status = 'In progress', sync = '".date('Y-m-d H:i:s')."' WHERE type = 'product'");
				$page = $page + 1;
				$deletesubcategories = array();
				
				update_option( 'monstamanagement_sync_status', 'monstaproductstart');
        		
				foreach ($response->success->products as $key => $elements) {
					$plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` = 'active_plugins' and option_value like '%/monstamanagement.php%' " );
					if ( null === $plugin_exists)
						break;
					$post_parent = 0;
					$mastercode = 'M'.$key;
					$category_ids = array();
					foreach ($elements->products as $index => $product) {
					    
					    //error_log(date('Y-m-d H:i:s').' Product Iteration ::'.$product->supplier_id.'_'.$product->code.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
					    
					    update_option( 'monstamanagement_sync_status', 'monstaproductinprogress');
				        $plugin_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` = 'active_plugins' and option_value like '%/monstamanagement.php%' " );
						if ( null === $plugin_exists)
							break;
						$currentdate = date('Y-m-d H:i:s');
						$mastercodeexist = $wpdb->get_row( "SELECT post_id FROM ".$wpdb->prefix."postmeta WHERE `meta_key` = '_sku' and meta_value = '".$mastercode."'");
						
						if (isset($mastercodeexist->post_id)) {
							$post_parent = $mastercodeexist->post_id;
						}
						// Add new products and update existing products
						if ($product->status == 1 || $product->status == 2) {
							// grouping
							$grouping_term_id = null; // set grouping is null
							$mm_product_data['grouping'] = array();
							if (empty((array)$product->grouping)) { // if groupiong is empty then assign as ungrouped
								$grouping_term_id = $monsta_ungrouped_id;
								$category_ids[] = $monsta_categories_id; // monsta categories
								$category_ids[] = $grouping_term_id;

								// create new grouping menu and assign to monsta menu
								$mansta_menu = term_exists('Monsta Menu', 'nav_menu');
								if ( is_array( $mansta_menu ) ) {
									$menu_items = wp_get_nav_menu_items($mansta_menu['term_id'], array('post_status' => 'publish,draft'));
									$menu_items_exist = false;

									foreach ($menu_items as $menu_item) {
										 // Item already in menu?
										 if ($menu_item->post_name == 'ungrouped') {
												$menu_items_exist = true;
										 }
									}

									if ($menu_items_exist === false) {
										$updatemenuid = wp_update_nav_menu_item($mansta_menu['term_id'], 0, array(
												'menu-item-title'   =>  __('ungrouped', 'trophymonsta' ),
												'menu-item-classes' => 'ungrouped',
												'menu-item-url'     => home_url( 'product-category/monsta-categories/ungrouped' ),
												'menu-item-status'  => 'publish'
										));
										update_post_meta($updatemenuid, '_trophymonsta_text_field', 'trophymonsta', true );
									}
								}
							} else {
								foreach($product->grouping as $grouping) {
									$pgtermslug = sanitize_title('grouping-'.$monsta_categories_id.'-'.$grouping->master_id);
									$term = term_exists($pgtermslug, 'product_cat', $monsta_categories_id);
									if (is_array( $term )) {
										$grouping_term_id = $term['term_id'];
										wp_update_term( $grouping_term_id, 'product_cat', array('name' => trim(preg_replace('/\s+/', ' ', $grouping->name))));

										// create new grouping menu and assign to monsta menu
										$mansta_menu = term_exists('Monsta Menu', 'nav_menu');
										if ( is_array( $mansta_menu ) ) {
											$menu_items = wp_get_nav_menu_items($mansta_menu['term_id'], array('post_status' => 'publish,draft'));
											$menu_items_exist = false;
											foreach ($menu_items as $menu_item) {
											 // Item already in menu?
											 $_group_id = get_post_meta( $menu_item->ID, '_group_id', true );
											 if ($_group_id == $grouping->master_id) {
												$menu_items_exist = true;
											 }
											}

										if ($menu_items_exist === false) {
											$updatemenuid = wp_update_nav_menu_item($mansta_menu['term_id'], 0, array(
													'menu-item-title'   =>  __($grouping->name, 'trophymonsta' ),
													'menu-item-classes' => $grouping->name,
													'menu-item-url'     => home_url( 'product-category/monsta-categories/'.$pgtermslug ),
													'menu-item-status'  => 'publish'
											));
											update_post_meta($updatemenuid, '_trophymonsta_text_field', 'trophymonsta', true );
											update_post_meta($updatemenuid, '_group_id', $grouping->master_id, true );
										}
										}
									}
									$category_ids[] = $monsta_categories_id;
									$category_ids[] = $grouping_term_id;
									$mm_product_data['grouping'][] = $grouping->master_id;
								}
							}

							//departments
							$mm_product_data['departments'] = array();
							if (!empty((array)$product->departments)) {
								$mm_product_data['departments'] = $product->departments;
							}

							//Categories
							$categories_term_id = null; // set categories is null
							if (!empty((array)$product->categories)) {
								foreach($product->categories as $categories) { // create new categories
									$term = term_exists(sanitize_title('cat-'.$grouping_term_id.'-'.$categories->id), 'product_cat', $grouping_term_id);
									if (is_array( $term )) {
										$categories_term_id = $term['term_id'];
										wp_update_term( $categories_term_id, 'product_cat', array('name' => trim(preg_replace('/\s+/', ' ', $categories->name))));
									} else {
										$cterm = wp_insert_term(trim(preg_replace('/\s+/', ' ', $categories->name)), 'product_cat', array('parent' => intval($grouping_term_id), 'slug' => sanitize_title('cat-'.$grouping_term_id.'-'.$categories->id)) );
										$categories_term_id = $cterm['term_id'];
										add_term_meta($cterm['term_id'], 'category_mode', 'trophymonsta', true);
									}
									$category_ids[] = $categories_term_id;
								}
							} else { // if categories is empty then assign as under as grouping
								$categories_term_id = $grouping_term_id;
							}

							//SubCategories
							$subcategories_term_id = null; // set sub categories is null
							if (!empty((array)$product->subcategories)) {
								foreach($product->subcategories as $subcategories) { // create new sub categories
									$term = term_exists(sanitize_title('sub-cat-'.$categories_term_id.'-'.$subcategories->id), 'product_cat', $categories_term_id);
									if (is_array( $term )) {
										$subcategories_term_id = $term['term_id'];
										wp_update_term( $subcategories_term_id, 'product_cat', array('name' => trim(preg_replace('/\s+/', ' ', $subcategories->name))));
									} else {
										$scterm = wp_insert_term(trim(preg_replace('/\s+/', ' ', $subcategories->name)), 'product_cat', array('parent' => intval($categories_term_id), 'slug' => sanitize_title('sub-cat-'.$categories_term_id.'-'.$subcategories->id)) );
										$subcategories_term_id = $scterm['term_id'];
										add_term_meta($scterm['term_id'], 'category_mode', 'trophymonsta', true);
									}
									$category_ids[] = $subcategories_term_id;
								}
							}

							$attributes_item = array();
							if ($product->size != '')
								$attributes_item['monstasize'] = $product->code .' - '.$product->size;
							else
								$attributes_item['monstasize'] = $product->code;

							// new process for variation
							$monsta_variation_data =  array(
								'attributes' 			=> $attributes_item,
								'sku'           		=> $product->supplier_id.'_'.$product->code,
								'regular_price' 		=> $product->sale_price,
								'sale_price'    		=> '',
								'stock_qty'     		=> 999,
								'image'         		=> $product->image,
								'mastercode'    		=> $mastercode,
								'new_product_count'		=> $new_product_count,
								'update_product_count'	=> $update_product_count,
								'multi_images'			=> $product->multi_images
							);

							$mm_product_data['monsta_variation_data'] = $monsta_variation_data;
							// end process for variation

							// NEW Fields
							$mm_product_data['id']  = $product->id;
							$mm_product_data['info_new'] = $product->new ? ucfirst($product->new) : ''; // set as NEW product from grr
							$mm_product_data['info_communique'] = $product->info_communique ? ucfirst($product->info_communique) : '';
							$mm_product_data['info_weight']         = $product->weight ? $product->weight : '';
							$mm_product_data['info_width']          = $product->width ? $product->width : '';
							$mm_product_data['info_height']         = $product->height ? $product->height : '';
							$mm_product_data['info_length']         = $product->length ? $product->length : '';
							$mm_product_data['info_presentation']   = $product->presentation ? $product->presentation : '';
							$mm_product_data['info_material']       = isset($product->material->name) ? sanitize_title($product->material->name) : '';
							$mm_product_data['info_parent_title']   = $product->parent_title ? $product->parent_title : '';
							$mm_product_data['info_year']           = $product->year ? $product->year : '';
							$mm_product_data['info_center1']         = $product->center1 ? ucfirst($product->center1) : 'No';
							$mm_product_data['info_center2']         = $product->center2 ? ucfirst($product->center2) : 'No';
							$mm_product_data['info_center1_component_price'] = $product->center1_component_price ? $product->center1_component_price : '';
							$mm_product_data['info_center2_component_price'] = $product->center2_component_price ? $product->center2_component_price : '';
							$mm_product_data['info_supplier_id']    = $product->supplier_id ? $product->supplier_id : '0';
							$mm_product_data['info_description_1']  = $product->description_1 ? $product->description_1 : '';
							$mm_product_data['info_cost_price']  		= $product->cost_price ? $product->cost_price : '';
							$mm_product_data['name'] = $product->name;
							$mm_product_data['description'] = $product->description_2;
							$mm_product_data['suppliers_name'] = $product->suppliers->name;
							$mm_product_data['monstacolor'] = $product->colour;
							$mm_product_data['monst_product_cat'] = implode(',', $category_ids);
							$mm_product_data['is_image_updated'] = $product->is_image_updated;
							
							// END NEW Fiedls
							if ($index == 0 && $post_parent == 0 ) {
								$mm_product_data['sku'] = $mastercode;
								/*
								Delete child product if assocate with any other parent product.
								*/
								$childcodeexist = $wpdb->get_row( "SELECT post_id FROM ".$wpdb->prefix."postmeta WHERE `meta_key` = '_sku' and meta_value = '".$product->supplier_id.'_'.$product->code."'");
								if (isset($childcodeexist->post_id)) {
									wp_delete_post($childcodeexist->post_id);
								}
								
								$product_count	=	self::insert_product($mm_product_data);
								$post_parent = $product_count['parent_id'];
							} else {
								$product_count	=	self::insert_product($mm_product_data,$post_parent);
							}
							if(isset($product_count) && !empty($product_count)) {
								if(isset($product_count['insert'])){
									$new_product_count = $new_product_count + $product_count['insert'];
									$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET create_count = ".$new_product_count.", sync = '".date('Y-m-d H:i:s')."' WHERE  type = 'product'");
								}
								if(isset($product_count['update'])){
									$update_product_count = $update_product_count + $product_count['update'];
									$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET update_count = ".$update_product_count.", sync = '".date('Y-m-d H:i:s')."' WHERE  type = 'product'");
								}
							}
						} else {
							$parsed_data['delete'][] = $product->supplier_id.'_'.$product->code;
							$grouping_term_id = null;
							if (empty((array)$product->grouping)) { // if groupiong is empty then assign as ungrouped
								$grouping_term_id = $monsta_ungrouped_id;
							} else {
								foreach($product->grouping as $grouping) {
									$pgtermslug = sanitize_title('grouping-'.$monsta_categories_id.'-'.$grouping->master_id);
									$term = term_exists($pgtermslug, 'product_cat', $monsta_categories_id);
									if (is_array( $term )) {
										$grouping_term_id = $term['term_id'];
									}
								}
							}
							
							//Categories
							$categories_term_id = null; // set categories is null
							if (!empty((array)$product->categories)) {
								foreach($product->categories as $categories) { // create new categories
									$term = term_exists(sanitize_title('cat-'.$grouping_term_id.'-'.$categories->id), 'product_cat', $grouping_term_id);
									if (is_array( $term )) {
										$categories_term_id = $term['term_id'];
										$deletesubcategories[] = $term['term_id'];
									}
								}
							} else { // if categories is empty then assign as under as grouping
								$categories_term_id = $grouping_term_id;
							}

							//SubCategories
							if (!empty((array)$product->subcategories)) {
								foreach($product->subcategories as $subcategories) { // create new sub categories
									$term = term_exists(sanitize_title('sub-cat-'.$categories_term_id.'-'.$subcategories->id), 'product_cat', $categories_term_id);
									if (is_array( $term )) {
										$deletesubcategories[] = $term['term_id'];
									}
								}
							}
						}
					}

					// delete existing grouping, categories, subcategories
					$wpdb->query("DELETE rel.* FROM ".$wpdb->prefix."term_relationships as rel left join ".$wpdb->prefix."term_taxonomy as tax  on (rel.`term_taxonomy_id`= tax.`term_taxonomy_id`) WHERE rel.`object_id` = ".$post_parent." and tax.`taxonomy` = 'product_cat'");

					// Get children product variation IDs in an array
					$product = wc_get_product($post_parent);
					if ($product) {
						$product_children_ids = $product->get_children();
						foreach($product_children_ids as $children_id) {
						    $update_product_cat = get_post_meta($children_id, '_monst_product_cat', true);
							foreach(explode(',', $update_product_cat) as $children_cat_id) {
								$category_ids[] = $children_cat_id;
							}
                            
						}

						// insert grouping, categories, subcategories;
						if (!empty($category_ids)) {
							$category_ids = array_unique($category_ids);
							foreach($category_ids as $category_id) {
								$wpdb->query("REPLACE INTO ".$wpdb->prefix."term_relationships (`object_id`, `term_taxonomy_id`, `term_order`) VALUES	(".$post_parent.", ".$category_id.", '0')");
							}
						}
					}

				}

				// DELETE PRODUCT AND it's related things
				if (isset($parsed_data['delete']) && !empty($parsed_data['delete'])) {
					$deletedSkus = implode("','", $parsed_data['delete']);
					$productIds = array();
					$attachmentids = array();
					$productsParentids = array();
					$results = $wpdb->get_results("SELECT postmeta.`post_id`, posts.`post_type`, posts.`post_parent` FROM ".$wpdb->prefix."postmeta as postmeta left join ".$wpdb->prefix."posts as posts on (posts.ID=postmeta.post_id) WHERE (postmeta.`meta_key` LIKE '_sku' and postmeta.meta_value in ('".$deletedSkus."')) or (postmeta.`meta_key` LIKE '_sku_image' and postmeta.meta_value in ('".$deletedSkus."'))");

					foreach( $results as $result ) {
						if ($result->post_parent == 'attachment')
							$attachmentids[] = $result->post_id;

						$productIds[] = $result->post_id;
						$productsParentids[] = $result->post_parent;
					}

					if (!empty($attachmentids)) {
						$attachmentidstring = implode(',', $attachmentids);
						$attachmentposts = $wpdb->get_results("SELECT `meta_value` FROM ".$wpdb->prefix."postmeta WHERE `meta_key` LIKE '_wp_attached_file' and post_id in (".$attachmentidstring.")");
						foreach ( $attachmentposts as $attachment )	{
							$wordpress_upload_dir = wp_upload_dir();
							$new_file_path = unlink($wordpress_upload_dir['basedir'] . '/' . $productid->meta_value);
						}
					}

					if (!empty($productIds)) {
						$implodeids = implode(',', $productIds);
						$wpdb->query("DELETE FROM ".$wpdb->prefix."term_relationships WHERE object_id IN (".$implodeids.")");
						$wpdb->query("DELETE FROM ".$wpdb->prefix."postmeta WHERE post_id IN (".$implodeids.")");
						$wpdb->query("DELETE FROM ".$wpdb->prefix."posts WHERE (post_type = 'product_variation' or post_type = 'attachment') and ID IN (".$implodeids.")");

						$productsParentids = array_unique($productsParentids);
						foreach($productsParentids as $parent_id ) {
							$mResults = $wpdb->get_results("SELECT ID FROM " . $wpdb->prefix . "posts WHERE post_type = 'product_variation' and post_parent = '" . $parent_id . "'");
							if ($wpdb->num_rows < 1) {
								$wpdb->query("DELETE FROM ".$wpdb->prefix."term_relationships WHERE object_id = ".$parent_id);
								$wpdb->query("DELETE FROM ".$wpdb->prefix."postmeta WHERE post_id = ".$parent_id);
								$wpdb->query("DELETE FROM ".$wpdb->prefix."posts WHERE ID = ".$parent_id);
							}
						}
						$delete_product_count = $delete_product_count + count($productsParentids);
						$new_product_count = $new_product_count - count($productsParentids);
						$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET create_count = ".$new_product_count.", delete_count = ".$delete_product_count.", sync = '".date('Y-m-d H:i:s')."' WHERE  type = 'product'");
					}
					
					foreach ($deletesubcategories as $catid) {
						$emptytermexist = $wpdb->get_row("SELECT object_id FROM ".$wpdb->prefix."term_relationships WHERE `term_taxonomy_id` = ".$catid);
						if(!isset($emptytermexist->object_id)) {
							wp_delete_term($catid, 'product_cat');
						}
					}
				}
				
				update_option( 'monstamanagement_sync_status', 'monstaproductend');
				
				$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET total_count = ".$total_count.", page = ".$page.", status = 'In progress', sync = '".date('Y-m-d H:i:s')."' WHERE type = 'product'");

				if (TROPHYMONSTA_DEBUG)
					error_log(date('Y-m-d H:i:s')." Products PAGENO: ".($page - 1)." Import Completed".PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");

				$lastpage = ceil($total_count/$limit);
				if ($lastpage <= ($page - 1)) {
					$wpdb->query("UPDATE ".$wpdb->prefix."trophymonsta_import_log SET total_count = ".$total_count.", page = '1', status = 'Completed', sync = '".date('Y-m-d H:i:s')."' WHERE  type = 'product'");

					$last_sync_date = get_option( 'trophymonsta_product_last_sync_date' );

					if ($product_sync_date != '') {
						if ($last_sync_date) {
							update_option( 'trophymonsta_product_last_sync_date', $product_sync_date);
						} else {
							add_option('trophymonsta_product_last_sync_date', $product_sync_date);
						}
					}
					$total_sync_count = (int) get_option( 'trophymonsta_total_sync_count' );
					update_option('trophymonsta_total_sync_count', ($total_sync_count+1));
					update_option('trophymonsta_last_sync_date', get_option( 'trophymonsta_product_last_sync_date' ));
					$response = self::http_post( json_encode(array('sync_status' => 1)) , 'store/updatesynchstatus', 'json' );
					wp_clear_scheduled_hook('trophymonsta_product_import');
					$last_sync_status = get_option( 'monstamanagement_sync_status' );
					if ($last_sync_status) {
						update_option( 'monstamanagement_sync_status', 'monstacomplete');
					} else {
						add_option( 'monstamanagement_sync_status', 'monstacomplete');
					}
					//wpdb::close();
					if (TROPHYMONSTA_DEBUG)
						error_log(date('Y-m-d H:i:s').' Completed import products process.'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
										
					$orderby = 'name';
					$order = 'asc';
					$hide_empty = false ;
					$cat_args = array(
						'orderby'    => $orderby,
						'order'      => $order,
						'hide_empty' => $hide_empty,
					);
					 
					$product_categories = get_terms( 'product_cat', $cat_args );
					if( !empty($product_categories) ){
						foreach ($product_categories as $cat_key => $category) {
						    if (substr($category->slug,0,4) === 'cat-') {
							    $emptytermexist = $wpdb->get_row("SELECT object_id FROM ".$wpdb->prefix."term_relationships WHERE `term_taxonomy_id` = ".$category->term_id);
        						if(!isset($emptytermexist->object_id)) {
        							wp_delete_term($category->term_id, 'product_cat');
        						}
						    } else if (substr($category->slug,0,8) === 'sub-cat-') {
							    $emptytermexist = $wpdb->get_row("SELECT object_id FROM ".$wpdb->prefix."term_relationships WHERE `term_taxonomy_id` = ".$category->term_id);
        						if(!isset($emptytermexist->object_id)) {
        							wp_delete_term($category->term_id, 'product_cat');
        						}
							}
						}
					}
										
					$ids = wc_get_products( array( 'return' => 'ids', 'parent' => null, 'limit' => -1 ) );
				    if(!empty($ids)) {
						foreach ($ids as $parent_product_id) {
						    $product = new WC_Product($parent_product_id);
						    $is_trophy_parent = get_post_meta($parent_product_id, '_trophymonsta_text_field', true); 
						    if ($is_trophy_parent == 'trophymonsta') {
						        $childproduct = $wpdb->get_row("SELECT ID FROM ".$wpdb->prefix."posts WHERE `post_parent` = ".$parent_product_id);
        						if(!isset($childproduct->ID)) {
        							wp_delete_post($parent_product_id);
        						}
						    }
						}
					}
					
					$transientsposts = $wpdb->get_results("SELECT `ID` FROM ".$wpdb->prefix."posts WHERE `post_type` = 'product'");
				    //if (TROPHYMONSTA_DEBUG)
				//	error_log(date('Y-m-d H:i:s')." transientsposts : ".print_r($transientsposts,true)." Import Completed".PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");

					foreach ($transientsposts as $post ) {
					    error_log(date('Y-m-d H:i:s')." transientsposts : ".$post->ID." Import Completed".PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
						wc_delete_product_transients($post->ID);
						$productVariable = new WC_Product_Variable($post->ID);
						$productVariableDS = new WC_Product_Variable_Data_Store_CPT();
						$productVariableDS->read_children($productVariable, true);
						$productVariableDS->read_price_data($productVariable, true);
						$productVariableDS->read_variation_attributes($productVariable);
					}
					
				} else {
				    wp_clear_scheduled_hook('trophymonsta_product_import');
					wp_schedule_event(time(), 'daily', 'trophymonsta_product_import');
					$nexttime = wp_next_scheduled ( 'trophymonsta_product_import' );
					wp_schedule_event($nexttime, 'daily', 'trophymonsta_product_import');
					Trophymonsta::http_get_self( Trophymonsta::build_query( array( 'key' => $mm_key) ), 'wp-json/monsta/syncstatus', null );
					//wpdb::close();
				}
			}
			return true;
		}

		public static function trophymonsta_product_sales ($this_get_id, $this_status_transition_from, $this_status_transition_to, $instance ) {
			global $wpdb;
            //error_log(date('Y-m-d H:i:s').' Trophymonsta trophymonsta_product_sales '.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			$trophy_product_order = get_post_meta( $this_get_id, 'trophy_product_order_meta_key', true );
			$trophy_allow_web_order = get_option( 'trophymonsta_allow_web_order' );
			$monsta_product_total = 0;
			if ($trophy_product_order != 'trophymonsta' && ($this_status_transition_to == 'wc-processing' || $this_status_transition_to == 'wc-on-hold' || $this_status_transition_to == 'processing' || $this_status_transition_to == 'on-hold') && $trophy_allow_web_order == 1 ) {
				$saleinfo = array();
				update_post_meta($this_get_id, 'trophy_product_order_meta_key', 'trophymonsta');
				if ( count( $instance->get_items() ) > 0 ) {
					$product_count = 0;
					foreach ( $instance->get_items() as $key=>$item ) {
						//$test_order = new WC_Order($this_get_id);
						//$test_order_key = $test_order->order_key;
						//$order_detail = wc_get_order($this_get_id);
						$termidarray = array();
						if ( ! is_object( $item ) ) {
							continue;
						}

						if ( $item->is_type( 'line_item' ) ) {
							$product = $item->get_product();
							if ( ! $product ) {
								continue;
							}
							$item_data = $item->get_data();
							$product_id = get_post_meta( $product->get_id(), '_trophymonsta_product_id_text_field', true );
							$custompostmeta = get_post_meta( $product->get_id(), '_trophymonsta_text_field', true );
							$trophy_product_id = get_post_meta( $product->get_id(), '_trophymonsta_product_id_text_field', true );
							$product_key = $product_id."_".$key;
							if ($custompostmeta == 'trophymonsta') {
								$item_meta = $item->get_meta_data();
								$line_items_meta_data = array();
								foreach($item_meta as $key => $meta){
									$meta_arr = $meta->get_data();
									$line_items_meta_data[ $meta_arr['key'] ] = $meta_arr['value'];
								}
								if( !isset( $saleinfo['products'][$product_key] ) ){
									$saleinfo['products'][$product_key] = array();
								}
								$saleinfo['products'][ $product_key ] = array(
																'id' => $trophy_product_id,
																'code' => $product->get_sku(),
																'qty' => $item->get_quantity(),
																'price' => isset( $line_items_meta_data[ 'Price' ] ) ? $line_items_meta_data[ 'Price' ] : 0  ,
																'subtotal' => isset( $line_items_meta_data[ 'Line Subtotal' ] ) ? $line_items_meta_data[ 'Line Subtotal' ] : 0,
																'cc1' => array(),
																'cc2' => array(),
																'accessories' => array(),
																'engraving' => array(),

																);
								$monsta_product_total = $monsta_product_total + $item->get_total();
								$monstavariants = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE `attribute_name` like 'monsta%' and `attribute_name` not in ('monstasize', 'monstacolor', 'monstamaterial', 'monstaengraving', 'monstaprocess')");
								foreach ( $monstavariants as $variant ) {
									if ($variant->attribute_name == 'monstacc1') {
										//$attr_monstacenter = get_post_meta( $this_get_id, 'attribute_pa_'.$variant->attribute_name.'_'.$product_id."_".$product_count, true );
										$term_center_data = null;
										if( isset( $line_items_meta_data['attribute_pa_monstacc1'] ) ){
											$attr_monstacenter = $line_items_meta_data['attribute_pa_monstacc1'];
											$term_center_data = get_term_by('slug', $attr_monstacenter, 'pa_'.$variant->attribute_name );
										}
										if(  $term_center_data != null    ){
											$term_id = $term_center_data->term_id;
											$saleinfo['products'][$product_key] ['cc1'][] = array(
																		'id' 	=>	get_term_meta( $term_id,'components_id',true),
																		'code'	=>	get_term_meta( $term_id,'components_code',true),
																		'price' =>	get_term_meta( $term_id,'components_price',true),
																		);
										}

									} else if ($variant->attribute_name == 'monstacc2') {
										//$attr_monstacenter = get_post_meta( $this_get_id, 'attribute_pa_'.$variant->attribute_name.'_'.$product_id."_".$product_count, true );
										$term_center_data = null;
										if( isset( $line_items_meta_data['attribute_pa_monstacc2'] ) ){
											$attr_monstacenter = $line_items_meta_data['attribute_pa_monstacc2'];
											$term_center_data = get_term_by('slug', $attr_monstacenter, 'pa_'.$variant->attribute_name );
										}
										if(  $term_center_data != null    ){
											$term_id = $term_center_data->term_id;
											$saleinfo['products'][$product_key] ['cc2'][] = array(
																		'id' 	=>	get_term_meta( $term_id,'components_id',true),
																		'code'	=>	get_term_meta( $term_id,'components_code',true),
																		'price' =>	get_term_meta( $term_id,'components_price',true),
																		);
										}

									} else {
										$monstaattr_term_data = null;
										if( isset( $line_items_meta_data[ 'attribute_pa_'.$variant->attribute_name ] ) ){
											$attr_monstaattr = $line_items_meta_data[ 'attribute_pa_'.$variant->attribute_name ];
											$monstaattr_term_data = get_term_by('slug', $attr_monstaattr, 'pa_'.$variant->attribute_name );
										}

										if(  $monstaattr_term_data != null  ){
											$term_id = $monstaattr_term_data->term_id;
											$saleinfo['products'][$product_key]['accessories'][] = array(
																		'id' 	=>	get_term_meta( $term_id,'components_id',true),
																		'code'	=>	get_term_meta( $term_id,'components_code',true),
																		'price' =>	get_term_meta( $term_id,'components_price',true),
																		);
										}
									}
								}

								$monstaengraving_term_data = null;
								if( isset( $line_items_meta_data[ 'attribute_pa_monstaengraving' ] ) ){
									$attr_monstaengraving =   $line_items_meta_data[ 'attribute_pa_monstaengraving' ];
									$monstaengraving_term_data = get_term_by('slug', $attr_monstaengraving, 'pa_monstaengraving' );
								}
								if(  $monstaengraving_term_data != null    ){
									$term_id = $monstaengraving_term_data->term_id;
									$saleinfo['products'][$product_key]['engraving'][] = array(
																'process_id' 	=>	get_term_meta( $term_id,'_trophymonsta_no_processes_id',true),
																'departments_id'	=>	get_term_meta( $term_id,'_trophymonsta_department_id',true),
																'price' =>	get_term_meta( $term_id,'process_price',true),
																);
								}
								$product_count++;
							}
						}

					}
				}



				$order_detail = wc_get_order($this_get_id);
				$order_data = $order_detail->get_data();
				$customer_billing = $instance->get_address();
				$customer_shipping = $instance->get_address('shipping');

				$saleinfo['customer_details'] = array(array('company_name' => $customer_billing['company'],
																								'first_name' => $customer_billing['first_name'],
																								'last_name' => $customer_billing['last_name'],
																								'email' => $customer_billing['email'],
																								'phone' => $customer_billing['phone'],
																								'address' => $customer_billing['address_1'],
																								'address1' => $customer_billing['address_2'],
																								'suburb' => $customer_billing['city'],
																								'postcode' => $customer_billing['postcode'],
																								'country' => $customer_billing['country'],
																								'state' => $customer_billing['state']));

				$saleinfo['shipping_details'] = array(array('shipping_address' => $customer_shipping['address_1'],
																								'shipping_address1' => $customer_shipping['address_2'],
																								'shipping_suburb' => $customer_shipping['city'],
																								'shipping_postcode' => $customer_shipping['postcode'],
																								'shipping_country' => $customer_shipping['country'],
																								'shipping_state' => $customer_shipping['state'],
																								'shipping_frieght' => isset( $order_data['shipping_total'] ) ? $order_data['shipping_total'] : 0,
																								));
                
                $presentationdate = isset( $_SESSION[ 'engraving_setting_presentation_date' ] ) ?  date( 'd/m/Y',strtotime($_SESSION[ 'engraving_setting_presentation_date' ]) ) : get_post_meta($this_get_id, 'engraving_setting_presentation_date',true);
		        $deliverydate = isset( $_SESSION[ 'engraving_setting_customer_date' ] ) ? date('d/m/Y',strtotime($_SESSION[ 'engraving_setting_customer_date' ] ) ) : get_post_meta($this_get_id, 'engraving_setting_customer_date',true);
		
		
				$logo_fee = 0;
				$order = wc_get_order($this_get_id);
				foreach( $order->get_items('fee') as $item_id => $item_fee ){
					$fees = $item_fee->get_data();
					if( isset( $fees['name'] ) && $fees['name'] == 'Logo Fee' ){
						$logo_fee = isset( $fees[ 'total' ] ) ? $fees[ 'total' ] : 0 ;
					}
				}
				$required_date = '';
				$delivery_date_field_name = get_post_meta( $this_get_id, 'delivery_date_field_name', true );
				if( trim($delivery_date_field_name) != '' ){
					$required_date =  Date('Y-m-d', strtotime(get_post_meta( $this_get_id, 'delivery_date_field_name', true )));
				}
				$order_notes ='';
				if( isset( $instance->get_data()['customer_note'] ) ){
					$order_notes = $instance->get_data()['customer_note'];
				}elseif( isset( $_POST['order_comments'] ) ){
					$order_notes = $_POST['order_comments'];
				}
				$shipping_details_obj = $instance->get_items( 'shipping' );
				$delivery_method = '';
				foreach( $shipping_details_obj as $k => $shipping_details){
					if( $shipping_details->get_method_id()  == 'local_pickup' ){
						$delivery_method = 1;
					}
					if( $shipping_details->get_method_id()  == 'free_shipping' ){
						$delivery_method = 2;
					}
				}
				$monsta_product_total = $monsta_product_total + $logo_fee + $order_data['shipping_total'];
				$saleinfo['order_details'] = array(
												array(
												//'required_date' => $required_date,
												'required_date' => $deliverydate,
												'delivery_method' => $delivery_method,
												'payment_term' => isset( $order_data['payment_method'] ) ? $order_data['payment_method'] : '',
												//'customer_date' => $deliverydate,
												'presentation_date' =>$presentationdate,
												//'order_total' => $instance->get_total(),
												'order_total' => $monsta_product_total,
												'logo_fee' =>$logo_fee,
												'wp_order_id' => $this_get_id,
												'order_notes'=> $order_notes,
												));
				$saleinfo['prices_include_tax'] = isset($order_data['prices_include_tax']) ? $order_data['prices_include_tax'] : 0;
				$trophy_logo_attacment = '';
				$attachment_id =  isset( $_SESSION[ 'engraving_setting_uploaded_id' ] ) && $_SESSION[ 'engraving_setting_uploaded_id' ] != 'on' ? $_SESSION[ 'engraving_setting_uploaded_id' ] : '' ;
				//if( is_numeric($attachment_id) && $attachment_id != 0 ){
					//$trophy_logo_attacment = wp_get_attachment_url( $attachment_id );
				//}
				if($attachment_id != ''){
					$attachment_ids = explode(',',$attachment_id);
					$logo_img = array();
					foreach($attachment_ids as $k => $postid ){
						$logo_img[] = wp_get_attachment_url( $postid );
					}
					$trophy_logo_attacment = join(', ', $logo_img );
					
				}
				$is_existing = '';
				if( isset( $_SESSION['engraving_setting_forgo_logo'] ) && $_SESSION['engraving_setting_forgo_logo'] != ''  ){
					$is_existing = 0;
				}
				if( isset( $_SESSION['engraving_setting_existing_logo'] ) && $_SESSION['engraving_setting_existing_logo'] != ''  ){
					$is_existing = 1;
				}
				$trophy_xls_attachment = get_post_meta($this_get_id, '_monsta_engravings_xls',true);
				$wordpress_upload_dir = wp_upload_dir();
				$trophy_excel_attacment = '';
				if( file_exists( $wordpress_upload_dir['path'].'/'.$trophy_xls_attachment ) && $trophy_xls_attachment != '' ){
					$trophy_excel_attacment = $wordpress_upload_dir['url'].'/'.$trophy_xls_attachment;
				}
				$saleinfo['attachments']	=	array(array('engraving_xls'	=>	$trophy_excel_attacment,
															'logo_image'	=>	$trophy_logo_attacment,
															'is_existing'	=>  $is_existing
															));
			//echo "<pre>";print_r($saleinfo);echo "</pre>";die;
				unset( $_SESSION[ 'engraving_setting_existing_logo' ] );
				unset( $_SESSION[ 'engraving_setting_forgo_logo' ] );
				unset( $_SESSION[ 'engraving_setting_presentation_date' ] );
				unset( $_SESSION[ 'engraving_setting_customer_date' ] );
				unset( $_SESSION[ 'enter_engraving_details_online' ] );
				unset( $_SESSION[ 'enter_engraving_details_email' ] );
				unset( $_SESSION[ 'no_engraving_details' ] );
				unset( $_SESSION[ 'engraving_setting_uploaded_id' ] );
				unset( $_SESSION[ 'session_trophy_product_details' ] );
				unset( $_SESSION[ 'engraving_step' ] );
				if (TROPHYMONSTA_DEBUG){
					error_log(date('Y-m-d H:i:s').'==========> ORDER DETAILS TEST <==============='.print_r($saleinfo,true).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
				}
				$response = self::http_post( json_encode($saleinfo) , 'sales', 'json' );
				if (TROPHYMONSTA_DEBUG){
					error_log(date('Y-m-d H:i:s').'==========> ORDER RESPONCE <==============='.print_r($response,true).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
				}


			}

		}
		
		public static function tropymonsta_dequeue_unnecessary_scripts(){
            if ( is_page( 'monsta-engravings-settings' ) || is_page( 'monsta-engravings-details' ) || is_page( 'monsta-engravings-review' ) ) {
				wp_deregister_script('owlcarousel');
				wp_dequeue_script( 'owlcarousel');
			}
        }
		
		public static function tropymonsta_wp_enqueue_scripts ($order_id) {

			wp_register_style( 'trophymonsta.css', plugin_dir_url( __FILE__ ) . '_inc/trophymonsta.css', array(), TROPHYMONSTA_VERSION );
			wp_enqueue_style( 'trophymonsta.css');

			if ( is_checkout() || is_page( 'monsta-engravings-settings' ) || is_page( 'monsta-engravings-details' ) || is_page( 'monsta-engravings-review' ) ) {
				
				wp_register_style( 'style.css', plugin_dir_url( __FILE__ ) . 'css/style.css', array(), TROPHYMONSTA_VERSION );
				wp_enqueue_style( 'style.css');
                wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_register_style( 'owl.carousel.min.css', plugin_dir_url( __FILE__ ) . 'css/owl.carousel.min.css', array(), TROPHYMONSTA_VERSION );
				wp_enqueue_style( 'owl.carousel.min.css');

				wp_enqueue_script( 'bootstrap.min.js', plugin_dir_url( __FILE__ ) . 'js/bootstrap.min.js', array(), TROPHYMONSTA_VERSION );
				wp_enqueue_script( 'owl.carousel.min.js', plugin_dir_url( __FILE__ ) . 'js/owl.carousel.min.js', array(), TROPHYMONSTA_VERSION );
				wp_enqueue_script( 'tm-custom-script.js', plugin_dir_url( __FILE__ ) . 'js/tm-custom-script.js', array(), TROPHYMONSTA_VERSION );
	        }
			wp_enqueue_script( 'tm-custom-hide-category.js', plugin_dir_url( __FILE__ ) . 'js/tm-custom-hide-category.js', array(), TROPHYMONSTA_VERSION );
		}

		public static function monsta_add_attribute($attribute)	{
		    global $wpdb;

		    if (empty($attribute['attribute_type'])) { $attribute['attribute_type'] = 'select';}
		    if (empty($attribute['attribute_orderby'])) { $attribute['attribute_orderby'] = 'menu_order';}
		    if (empty($attribute['attribute_public'])) { $attribute['attribute_public'] = false;}

		    if ( empty( $attribute['attribute_name'] ) || empty( $attribute['attribute_label'] ) ) {
		            return new WP_Error( 'error', __( 'Please, provide an attribute name and slug.', 'woocommerce' ) );
		    } elseif ( ( $valid_attribute_name = Trophymonsta::valid_attribute_name( $attribute['attribute_name'] ) ) && is_wp_error( $valid_attribute_name ) ) {
		            return $valid_attribute_name;
		    } elseif ( taxonomy_exists( wc_attribute_taxonomy_name( $attribute['attribute_name'] ) ) ) {
		            return new WP_Error( 'error', sprintf( __( 'Slug "%s" is already in use. Change it, please.', 'woocommerce' ), sanitize_title( $attribute['attribute_name'] ) ) );
		    }

		    $wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );

		    do_action( 'woocommerce_attribute_added', $wpdb->insert_id, $attribute );

		    flush_rewrite_rules();
		    delete_transient( 'wc_attribute_taxonomies' );

		    return true;
		}

		public static function monsta_update_attribute($attribute)	{
		    global $wpdb;

		    if (empty($attribute['attribute_type'])) { $attribute['attribute_type'] = 'select';}
		    if (empty($attribute['attribute_orderby'])) { $attribute['attribute_orderby'] = 'menu_order';}
		    if (empty($attribute['attribute_public'])) { $attribute['attribute_public'] = false;}

		    if ( empty( $attribute['attribute_name'] ) || empty( $attribute['attribute_label'] ) ) {
		            return new WP_Error( 'error', __( 'Please, provide an attribute name and slug.', 'woocommerce' ) );
		    } elseif ( ( $valid_attribute_name = Trophymonsta::valid_attribute_name( $attribute['attribute_name'] ) ) && is_wp_error( $valid_attribute_name ) ) {
		            return $valid_attribute_name;
		    }

		    $wpdb->update($wpdb->prefix . 'woocommerce_attribute_taxonomies', array('attribute_name'=>$attribute['attribute_name'], 'attribute_label'=>$attribute['attribute_label']), array('attribute_name'=>$attribute['attribute_name']));
				$attribute_id = $attribute['attribute_id'];
				$attribute_old_name = $attribute['attribute_old_name'];
				unset($attribute['attribute_id']);
				unset($attribute['attribute_old_name']);

		    do_action( 'woocommerce_attribute_updated', $attribute_id, $attribute, $attribute_old_name);
		    flush_rewrite_rules();
		    delete_transient( 'wc_attribute_taxonomies' );

		    return true;
		}

		public static function valid_attribute_name( $attribute_name ) {
		    if ( strlen( $attribute_name ) >= 28 ) {
		            return new WP_Error( 'error', sprintf( __( 'Slug "%s" is too long (28 characters max). Shorten it, please.', 'woocommerce' ), sanitize_title( $attribute_name ) ) );
		    } elseif ( wc_check_if_attribute_name_is_reserved( $attribute_name ) ) {
		            return new WP_Error( 'error', sprintf( __( 'Slug "%s" is not allowed because it is a reserved term. Change it, please.', 'woocommerce' ), sanitize_title( $attribute_name ) ) );
		    }

		    return true;
		}

		public static function upload_term_image($image, $term_id) {
			global $wpdb;
			$image = preg_replace('/\s/i', '%20', $image);
			$baseimag = str_replace(' ', '_',basename($image));
			$baseimag = str_replace('%20', '_',basename($baseimag));
			$imageexist = $wpdb->get_row("SELECT ID FROM ".$wpdb->prefix."posts WHERE `post_type` = 'attachment' and `post_parent` = '".$term_id."'");
			// if image is exists
			if(isset($imageexist) && isset($imageexist->ID)) {
				wp_delete_attachment( $imageexist->ID );
			}
			
			// Check the type of tile. We'll use this as the 'post_mime_type'.
			$file_type = wp_check_filetype(basename($image), null);
			$response = wp_remote_get($image, array( 'timeout' => 8 ) );
			if( !is_wp_error( $response ) ){
				$bits = wp_remote_retrieve_body( $response );
				$filename = $baseimag;
				$upload = wp_upload_bits( $filename, null, $bits );
				$data['guid'] = $upload['url'];
				$data['post_mime_type'] = $file_type['type'];
				$upload_id = wp_insert_attachment( $data, $upload['file'], 0 );
				add_post_meta( $upload_id, '_trophymonsta_text_field', 'trophymonsta', true );
				update_term_meta( $term_id,'thumbnail_id',$upload_id);
				return $upload_id;
			}
			return false;
		}

		public static function upload_product_images($image, $post_id, $parent_id, $_sku) {
			global $wpdb;
			$image = preg_replace('/\s/i', '%20', $image);
			$baseimag = str_replace(' ', '_',basename($image));
			$baseimag = str_replace('%20', '_',basename($baseimag));
			$imageexist = $wpdb->get_row( "SELECT post_id FROM ".$wpdb->prefix."postmeta WHERE `meta_key` = '_sku_image' and meta_value = '".$_sku."'");
			// if image is exists
			if(isset($imageexist) && isset($imageexist->post_id)) {
				wp_delete_attachment( $imageexist->post_id );
			}
			
			// Check the type of tile. We'll use this as the 'post_mime_type'.
			$file_type = wp_check_filetype(basename($image), null);
			$response = wp_remote_get($image, array( 'timeout' => 8 ) );
			if( !is_wp_error( $response ) ){
				$bits = wp_remote_retrieve_body( $response );
				$filename = $baseimag;
				$upload = wp_upload_bits( $filename, null, $bits );
				$data['guid'] = $upload['url'];
				$data['post_mime_type'] = $file_type['type'];
				$upload_id = wp_insert_attachment( $data, $upload['file'], $parent_id );
				add_post_meta( $upload_id, '_trophymonsta_text_field', 'trophymonsta', true );
				add_post_meta( $upload_id, '_sku_image', $_sku, true );
				set_post_thumbnail( $post_id, $upload_id );
				return $upload_id;
			}
			return false;
		}

		public static function upload_product_imagegallery($image, $post_id, $parent_id, $_sku) {
			global $wpdb;
			$image = preg_replace('/\s/i', '%20', $image);
			$baseimag = str_replace(' ', '_',basename($image));
			$baseimag = str_replace('%20', '_',basename($baseimag));
			
			// Check the type of tile. We'll use this as the 'post_mime_type'.
			$file_type = wp_check_filetype(basename($image), null);
			$response = wp_remote_get($image, array( 'timeout' => 8 ) );
			if( !is_wp_error( $response ) ){
				$bits = wp_remote_retrieve_body( $response );
				$filename = $baseimag;
				$upload = wp_upload_bits( $filename, null, $bits );
				$data['guid'] = $upload['url'];
				$data['post_mime_type'] = $file_type['type'];
				$upload_id = wp_insert_attachment( $data, $upload['file'], $parent_id );
				add_post_meta( $upload_id, '_trophymonsta_text_field', 'trophymonsta', true );
				add_post_meta( $upload_id, '_sku_image', $_sku, true );
				return $upload_id;
			}
			return false;
		}

		public static function get_accessories_type() {
            global $wpdb;
            $ip = null;
			$key = get_option( 'trophymonsta_api_key' );
			$monsta_accessories_type = get_option( 'monsta_accessories_type' );
			if ($key != '' && $monsta_accessories_type != 1) {
    			if (TROPHYMONSTA_DEBUG)
    				error_log(date('Y-m-d H:i:s').' Get accessories type from grr with following parameter. key: '.$key .PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");

    			 $response	= self::http_get( Trophymonsta::build_query( array( 'key' => $key, 'site_url' => site_url()) ), 'store/getAccessoryType', $ip );
    			 //error_log(date('Y-m-d H:i:s').' Get accessories type from grr with following parameter. key: '.print_r($response, true) .PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
    			 $response	= json_decode($response);

    			 foreach ($response->success->accessory_types as $accessory_types) {
    					if ($accessory_types->name == 'CC1' || $accessory_types->name == 'CC2') {
    				    $taxonomies_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE `attribute_name` = '".sanitize_title('monsta'.$accessory_types->name)."'" );
		            if( null === $taxonomies_exists) {
									Trophymonsta::monsta_add_attribute(array('attribute_name' => sanitize_title('monsta'.$accessory_types->name), 'attribute_label' => ucfirst($accessory_types->web_title)));
  		          } else {
									Trophymonsta::monsta_update_attribute(array('attribute_name' => sanitize_title('monsta'.$accessory_types->name), 'attribute_label' => ucfirst($accessory_types->web_title), 'attribute_id' => $taxonomies_exists->attribute_id, 'attribute_old_name' => $taxonomies_exists->attribute_name));
								}
							} else {
								$taxonomies_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE `attribute_name` = '".sanitize_title('monsta'.$accessory_types->id)."'" );
		            if( null === $taxonomies_exists) {
									Trophymonsta::monsta_add_attribute(array('attribute_name' => sanitize_title('monsta'.$accessory_types->id), 'attribute_label' => ucfirst($accessory_types->web_title)));
  		          } else {
									Trophymonsta::monsta_update_attribute(array('attribute_name' => sanitize_title('monsta'.$accessory_types->id), 'attribute_label' => ucfirst($accessory_types->web_title), 'attribute_id' => $taxonomies_exists->attribute_id, 'attribute_old_name' => $taxonomies_exists->attribute_name));
								}
							}
    			}
					update_option( 'monsta_accessories_type', 1);
			}
		}

		public static function register_taxonomy () {
			$permalink_option = get_option( 'trophymonsta_brands_permalink' );
			if( function_exists('wc_get_page_id') ) {
			    $shop_page_id = wc_get_page_id( 'shop' );
			} else {
			    $shop_page_id = woocommerce_get_page_id( 'shop' );
			}
			$base_slug = $shop_page_id > 0 && get_page( $shop_page_id ) ? get_page_uri( $shop_page_id ) : 'shop';
			$category_base = get_option('woocommerce_prepend_shop_page_to_urls') == "yes" ? trailingslashit( $base_slug ) : '';

			register_taxonomy( 'trophymonsta_brand',
			array('product'),
			array(
			'hierarchical'          => true,
			'update_count_callback' => '_update_post_term_count',
			'label'                 => __( 'Brands', 'trophymonsta'),
			'labels'                => array(
        'name'                  => __( 'Brands', 'trophymonsta' ),
        'singular_name'         => __( 'Brand', 'trophymonsta' ),
        'search_items'          => __( 'Search Brands', 'trophymonsta' ),
        'all_items'             => __( 'All Brands', 'trophymonsta' ),
        'parent_item'           => __( 'Parent Brand', 'trophymonsta' ),
        'parent_item_colon'     => __( 'Parent Brand:', 'trophymonsta' ),
        'edit_item'             => __( 'Edit Brand', 'trophymonsta' ),
        'update_item'           => __( 'Update Brand', 'trophymonsta' ),
        'add_new_item'          => __( 'Add New Brand', 'trophymonsta' ),
        'new_item_name'         => __( 'New Brand Name', 'trophymonsta' )
			),
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_admin_column'     => true,
			'show_in_nav_menus'     => true,
			'show_in_quick_edit'    => true,
			'meta_box_cb'           => 'post_categories_meta_box',
			'capabilities'          => array(
				'manage_terms'          => 'manage_product_terms',
				'edit_terms'            => 'edit_product_terms',
				'delete_terms'          => 'delete_product_terms',
				'assign_terms'          => 'assign_product_terms'
			),

			'rewrite' => array(
	        'slug' => $category_base . ( empty($permalink_option) ? __( 'brands', 'trophymonsta' ) : $permalink_option ),
	        'with_front' => true,
	        'hierarchical' => true
			        )
			)
			);
    }

		public static function monstamanagement_product_title_script() {
				global $wpdb;
		    global $post;

				$data = array();
		    // Only single product pages
		    if( ! is_product() ) return;
		    // get an instance of the WC_Product Object
		    $product = wc_get_product($post->ID);
		    // Only for variable products
		    if( ! $product->is_type( 'variable' ) ) return;
		    // Here set your specific product attributes in this array (coma separated):
		    $attributes = array('pa_monstasize');
			$ip = null;
			$api_key = get_option( 'trophymonsta_api_key' );
			$response = Trophymonsta::http_get( Trophymonsta::build_query( array( 'key' => $api_key, 'site_url' => site_url() ) ), 'store/getDiscounts', $ip );
			$response	= json_decode($response);
			$mmproductdiscounts =  array();
			//$mmaccessoriesdiscounts =  array();
			//$mmprocessesdiscounts =  array();
			$flat_discount = $response->success->flat_discount;
			if (isset($response->success->product_discount) && isset($response->success->component_discount) && isset($response->success->process_discount)) {
				$mmproductdiscounts[] =  $response->success->product_discount;
				//$mmaccessoriesdiscounts[] =  $response->success->component_discount;
				//$mmprocessesdiscounts[] =  $response->success->process_discount;
			}
			$variationprice = $variationcenter_1_price = array();
		    // The 1st loop for variations IDs
		    foreach($product->get_visible_children( ) as $variation_id ) {
		        // The 2nd loop for attribute(s)/value
		        foreach($product->get_available_variation( $variation_id )['attributes'] as $key => $value_id ){
		            $taxonomy = str_replace( 'attribute_', '', $key ); // Get the taxonomy of the product attribute

		            // Just for defined attributes
		            if( in_array( $taxonomy, $attributes) && $value_id){
									  // Set and structure data in an array( variation ID => product attribute => term name )
		                $data[ $variation_id ][$taxonomy] = get_term_by( 'slug', $value_id, $taxonomy )->name;
		            }
		        }

					$value_id = $product->get_available_variation( $variation_id )['attributes']['attribute_pa_monstasize'];
					$monstasizeslug = get_term_by( 'slug', $value_id, 'pa_monstasize' )->slug;
					$data[$monstasizeslug] = get_post_meta($variation_id, '_trophymonsta_name', true );
					$variationprice[$monstasizeslug] = get_post_meta($variation_id, '_regular_price', true);
				}

				$monstavariants = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE `attribute_name` like 'monsta%' and `attribute_name` not in ('monstasize', 'monstamaterial', 'monstaprocess')");
				$monstra_to_hide = $monstra_to_disable = $attribute_name_array = array();

				foreach ( $monstavariants as $variant ) {
					$monstra_to_hide[] = '_attribute_pa_'.$variant->attribute_name;
					$monstra_to_disable[] = '_pa_'.$variant->attribute_name;
				}
				$mmvariants = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE `attribute_name` like 'monsta%' and `attribute_name` not in ('monstasize', 'monstamaterial', 'monstaprocess', 'monstacolor', 'monstaengraving', 'monstacc1','monstacc2' )");
				foreach ( $mmvariants as $variant ) {
					$attribute_name_array[] = 'attribute_pa_'.$variant->attribute_name;
				}

				if (!empty($data)) {
		    ?>
		        <script type="text/javascript">
					// variables initialization
					var variationsData = <?php echo json_encode($data); ?>;
					var mmproductdiscounts = <?php echo json_encode($mmproductdiscounts); ?>;
					var flat_discount = <?php echo $flat_discount; ?>;
					//var mmaccessoriesdiscounts = <?php //echo json_encode($mmaccessoriesdiscounts); ?>;
					//var mmprocessesdiscounts = <?php //echo json_encode($mmprocessesdiscounts); ?>;
					var variationprice = <?php echo json_encode($variationprice); ?>;
					var attribute_name_array = <?php echo '["' . implode('", "', $attribute_name_array) . '"]' ?>;
					var woocommerce_currency_symbol = '<?php echo  get_woocommerce_currency_symbol(); ?>';
		            (function($){
						var  productTitle = $('.product_title').text(),  color = 'pa_monstasize';
						var product_variation = $( '.variations_form ' ).attr( 'data-product_variations' );
						if( typeof product_variation === 'string' ){
							product_variation = JSON.parse( product_variation );
						}
						$( '.monsta_attribute_pa_monstabox' ).hide();
						$( '.monsta_pa_monstabox' ).attr('disabled','disabled');
		                // function that get the selected variation and change title
		                function update_the_title( productTitle, variationsData, color ,self ) {
											//console.log(variationsData);
											var id = $( self ).attr( 'id' );
											if( typeof id !== 'undefined' && id != 'attribute_pa_monstacolor' ){
												var monstasize = $( '#pa_monstasize' ).val();

												if($('#attribute_pa_monstacolor option[data-attr="'+monstasize+'"]').length > 0){
													$('#attribute_pa_monstacolor option[data-attr="'+monstasize+'"]').attr('selected','selected');
												}else{
													$('#attribute_pa_monstacolor:enabled').prop('selectedIndex',0);
												}
											}
											if ($('#pa_monstasize').children('option').length > 1 && typeof variationsData[$('#pa_monstasize').val()] !== 'undefined') {
				                  //$('.product_title').text(productTitle+' - '+$('#pa_monstasize').val());
													var product_size = $('#pa_monstasize').val().split('-');
													var size_of_product = '';
													var product_length = parseInt(product_size.length - 1);
													if( typeof product_size[product_length] != 'undefined' && product_size[product_length] != '' ){
														size_of_product = ' - ' +product_size[product_length];
													}
													$('.product_title').text(variationsData[$('#pa_monstasize').val()] +  size_of_product );
											}

										 var monstra_to_hide = <?php echo '["' . implode('", "', $monstra_to_hide) . '"]' ?>;
										 var monstra_to_disable = <?php echo '["' . implode('", "', $monstra_to_disable) . '"]' ?>;
											for( var i = 0; i < monstra_to_hide.length; i++ ){
															var ele_id = ".monsta"+monstra_to_hide[i];
															var select_id = ".monsta"+monstra_to_disable[i];
															$( ele_id ).hide();
															$( select_id ).attr('disabled','disabled');
											}
											var pa_monstasize = $( '#pa_monstasize' ).val();
											if( typeof product_variation !== 'undefined' && product_variation.length > 0 ){
												product_variation.forEach( elements =>{

													if( elements.attributes.attribute_pa_monstasize == pa_monstasize ){
														var variation_id = elements.variation_id;
														for( var i = 0; i < monstra_to_hide.length; i++ ){
															var ele_id = "#"+variation_id+monstra_to_hide[i];
															var select_id = "#"+variation_id+monstra_to_disable[i];
															$( ele_id ).show();
															$( select_id ).removeAttr('disabled');
														}

													}
												});
											}
		                }
										// Once all loaded
		                setTimeout(function(){
		                    update_the_title( productTitle, variationsData, color ,$('#pa_monstasize') );
		                    mm_quantity_based_pricing();
		                }, 500);
						$( document ).on('change','#attribute_pa_monstacolor',function(){
							var option = $("option:selected", this).attr('data-attr');
							$( '#pa_monstasize' ).val( option );
							$( '#pa_monstasize' ).trigger( 'change' );
						});
		                // On live event: select fields
		                $('select').change( function(){
							var self = $(this);
		                    update_the_title( productTitle, variationsData, color ,self );
							mm_quantity_based_pricing();
		                });
		            })(jQuery);
					//Calculation
					jQuery( document ).on( 'keyup change','input[name="quantity"]',function(){
						mm_quantity_based_pricing();
					} );

					function mm_quantity_based_pricing(){
						var mm_other_price = new Array();
						var mm_noprocess_price = 0;
						var mm_centes_price_remove = new Array();
						var mm_centes_price_add = new Array();
						var quantity = parseFloat( jQuery( 'input[name="quantity"]' ).val() );
						if( Number.isNaN( quantity )  ){
							return false;
						}
						var mm_size = jQuery( 'select[name="attribute_pa_monstasize"]' ).val();
						var monstaengraving = jQuery( 'select[name="attribute_pa_monstaengraving"]:enabled' ).find(':selected').attr('price-attr');
						if( typeof monstaengraving !== 'undefined' ){
							mm_noprocess_price = parseFloat( monstaengraving) ;
						}
						if( attribute_name_array.length > 0 ){
							for( var i = 0; i < attribute_name_array.length; i++ ){
								var monsta_attr_value = jQuery( 'select[name="'+jQuery.trim(attribute_name_array[i])+'"]:enabled' ).find(':selected').attr('price-attr');
								if( typeof monsta_attr_value !== 'undefined' ){
									mm_other_price.push( parseFloat( monsta_attr_value) );
								}
							}
						}
						var cc1_price = jQuery( 'select[name="attribute_pa_monstacc1"]:enabled' ).find(':selected').attr('price-attr');
						var cc1_default_price = jQuery( 'select[name="attribute_pa_monstacc1"]:enabled' ).find(':selected').attr('default-price-cc1');
						if( typeof cc1_price !== 'undefined' && typeof cc1_default_price !== 'undefined' ){
							mm_centes_price_remove.push( cc1_default_price );
							mm_centes_price_add.push( cc1_price );
						}
						var cc2_price = jQuery( 'select[name="attribute_pa_monstacc2"]:enabled' ).find(':selected').attr('price-attr');
						var cc2_default_price = jQuery( 'select[name="attribute_pa_monstacc2"]:enabled' ).find(':selected').attr('default-price-cc2');
						if( typeof cc2_price !== 'undefined' && typeof cc2_default_price !== 'undefined' ){
							mm_centes_price_remove.push( cc2_default_price );
							mm_centes_price_add.push( cc2_price );
						}
						var product_discount_unit = 0;
						var product_discount_precent = 0;
						var accessories_discount_precent = 0;
						//var processes_discount_precent = 0;
						var product_discount_price = 0;
						var accessories_discount_price = 0;
						if( typeof variationprice !== 'undefined'  ){
							var mm_product_price = parseFloat( variationprice[ mm_size ] );
							if( mm_centes_price_remove.length > 0 ){
								for( var i =0;i<mm_centes_price_remove.length;i++){
									if( mm_centes_price_remove[i] != '' ){
										mm_product_price = parseFloat( mm_product_price ) - parseFloat(mm_centes_price_remove[i]);
									}
								}
							}
							if( mm_centes_price_add.length > 0 ){
								for( var i =0;i<mm_centes_price_add.length;i++){
									if( mm_centes_price_add[i] != '' ){
										mm_product_price = parseFloat( mm_product_price ) + parseFloat(mm_centes_price_add[i]);
									}
								}
							}
							
							if (flat_discount != 0 && flat_discount != '') {
								product_discount_price =   ( mm_product_price - ( mm_product_price * ( flat_discount / 100 ) ) ).toFixed(2);
							} else {
								if( typeof mmproductdiscounts !== 'undefined' && mmproductdiscounts.length > 0 ){
									for( var i = 0; i < mmproductdiscounts.length ; i++ ){
										jQuery.each( mmproductdiscounts[i], function(i,discount){
											if( quantity >= discount['unit']){
												product_discount_unit = parseFloat(discount['unit']);
												product_discount_precent = parseFloat(discount['precent']);
											}
										});
									}
								}
								if ( product_discount_unit != 0) {
									product_discount_price =   ( mm_product_price - ( mm_product_price * ( product_discount_precent / 100 ) ) ).toFixed(2);
								} else {
									product_discount_price = mm_product_price;
								}
							}
							/*if( typeof mmaccessoriesdiscounts !== 'undefined' && mmaccessoriesdiscounts.length > 0 ){
								for( var i = 0; i < mmaccessoriesdiscounts.length ; i++ ){
									jQuery.each( mmaccessoriesdiscounts[i], function(i,discount){
										if( quantity > discount['unit']){
											accessories_discount_precent = parseFloat(discount['precent']);
										}
									});
								}
							}*/
							/*if( typeof mmprocessesdiscounts !== 'undefined' && mmprocessesdiscounts.length > 0 ){
								for( var i = 0; i < mmprocessesdiscounts.length ; i++ ){
									jQuery.each( mmprocessesdiscounts[i], function(i,discount){
										if( quantity > discount['unit']){
											processes_discount_precent = parseFloat(discount['precent']);
										}
									});
								}
							}*/
							if ( product_discount_unit != 0) {
								product_discount_price =   ( mm_product_price - ( mm_product_price * ( product_discount_precent / 100 ) ) ).toFixed(2);;
							}else{
								product_discount_price = mm_product_price;
							}
							if( mm_other_price.length > 0   ){
								for( var i=0;i< mm_other_price.length;i++){
									/*if( accessories_discount_precent != 0 ){
										var attribute_qty_price =  ( mm_other_price[i] - (mm_other_price[i] * (accessories_discount_precent / 100) ) ).toFixed(2);
										attribute_qty_price = parseFloat( attribute_qty_price );
									}else{*/
										var attribute_qty_price = parseFloat(mm_other_price[i]);
									//}
									accessories_discount_price = accessories_discount_price + attribute_qty_price;
								}
							}
							if( mm_noprocess_price > 0 ){
								//var engraving_qty_price =  ( parseFloat( mm_noprocess_price ) - ( parseFloat( mm_noprocess_price) * (processes_discount_precent / 100) ) ).toFixed(2);
								var engraving_qty_price =  parseFloat( mm_noprocess_price ).toFixed(2);
								accessories_discount_price = accessories_discount_price + parseFloat(engraving_qty_price);
							}
							var total_price = ( parseFloat( product_discount_price ) + parseFloat( accessories_discount_price ) ).toFixed(2);
							if( !isNaN(total_price) && typeof total_price !== 'undefined'  ){
							    total_price  = total_price * quantity;
								var html_data = '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">'+woocommerce_currency_symbol+'</span>'+total_price.toFixed(2)+'</span>';
								jQuery( '.monsta_price_value:eq(0)' ).html( html_data );
								/*setTimeout(function(){
									var total_size = jQuery( '#pa_monstasize' ).find( 'option' ).length - 1;
									if( total_size > 1 ){
										jQuery( '.monsta_price_value:eq(1)' ).html( html_data );
									}else{
										jQuery( '.monsta_price_value:eq(0)' ).html( html_data );
									}

								},100);*/
							}
						}
					}
		        </script>
		    <?php
				}
		}

		public static function monstamanagement_cron_schedules($schedules){
			if(!isset($schedules["1min"])){
		        $schedules["1min"] = array(
		            'interval' => 1*60,
		            'display' => __('Once every 1 minutes'));
		    }
		    if(!isset($schedules["5min"])){
		        $schedules["5min"] = array(
		            'interval' => 5*60,
		            'display' => __('Once every 5 minutes'));
		    }
		    if(!isset($schedules["30min"])){
		        $schedules["30min"] = array(
		            'interval' => 30*60,
		            'display' => __('Once every 30 minutes'));
		    }
		    return $schedules;
		}

		public static function import_status() {
			global $wpdb;
			$import_logs = $wpdb->get_results("SELECT ID, sync, type, status, total_count, page, delete_count, create_count, update_count FROM ".$wpdb->prefix."trophymonsta_import_log	WHERE type in ('grouping', 'brand', 'accessories', 'product', 'noprocesses', 'processes', 'material') and status != 'Yet to Start' order by ID desc limit 7");
			wp_send_json($import_logs);
			exit();
		}

		public static function insert_product ($product_data, $webmonsta_productid=0)	{
			global $wpdb;
			$grr_productid  = $product_data['id'];
			
			// add new parent product
			if($webmonsta_productid==0) {
				$post = array( // Set up the basic post data to insert for our product
					'post_author'  => 1,
					'post_content' => $product_data['description'],
					'post_status'  => 'publish',
					'post_title'   => trim(preg_replace('/\s+/', ' ', $product_data['info_parent_title'])),
					'post_name'		 => sanitize_title($product_data['info_parent_title']),
					'post_parent'  => 0,
					'post_type'    => 'product'
				);
				$post_id                = wp_insert_post($post); // Insert the post returning the new post id
				$webmonsta_productid    = $post_id;

				if (!$post_id) { // If there is no post id something has gone wrong so don't proceed
					return false;
				}
				update_post_meta($post_id, '_sku', $product_data['sku']); // Set its SKU
				update_post_meta($post_id,'_visibility','visible'); // Set the product to visible, if not it won't show on the front end
				update_post_meta($post_id, '_trophymonsta_text_field', 'trophymonsta'); // Set trophymonsta_text_field
				update_post_meta($post_id, '_trophymonsta_product_id_text_field', $grr_productid); // Set trophymonsta product id
				update_post_meta($post_id, '_trophymonsta_info_communique', $product_data['info_communique']);
				update_post_meta($post_id, '_trophymonsta_info_new', $product_data['info_new']); // Set as NEW product from GRR
				update_post_meta($post_id, '_weight', $product_data['info_weight']);
				update_post_meta($post_id, '_width', $product_data['info_width']);
				update_post_meta($post_id, '_height', $product_data['info_height']);
				update_post_meta($post_id, '_length', $product_data['info_length']);
				update_post_meta($post_id, '_trophymonsta_presentation', $product_data['info_presentation']);
				update_post_meta($post_id, '_trophymonsta_material', $product_data['info_material']);
				update_post_meta($post_id, '_trophymonsta_supplier_id', $product_data['info_supplier_id']);
				update_post_meta($post_id, '_trophymonsta_description_1', $product_data['info_description_1']);
				update_post_meta($post_id, '_trophymonsta_cost_price', $product_data['info_cost_price']);
				update_post_meta($post_id, '_trophymonsta_year', $product_data['info_year']);
				update_post_meta($post_id, '_trophymonsta_name', trim(preg_replace('/\s+/', ' ', $product_data['info_parent_title'])));
				wp_set_object_terms($post_id, 'variable', 'product_type'); // Set it to a variable product type
			} else if ($webmonsta_productid != 0) {
				$post = array( // Set up the basic post data to insert for our product
					'ID' =>  $webmonsta_productid,
					'post_status'  => 'publish',
					'post_title'   => trim(preg_replace('/\s+/', ' ', $product_data['info_parent_title'])),
					'post_name'		 => sanitize_title($product_data['info_parent_title']),
				);
				wp_update_post( $post );
			}

			// create new supplier(brand)
			$term = term_exists(sanitize_title($product_data['suppliers_name']), 'trophymonsta_brand');

			if ( is_array( $term ) ) {
				$wpdb->query("REPLACE INTO ".$wpdb->prefix."term_relationships (`object_id`, `term_taxonomy_id`, `term_order`) VALUES	(".$webmonsta_productid.", ".$term['term_id'].", '0')");
			}

			// The variation data
			$variation_data  = $product_data['monsta_variation_data'];
			$additonal_variationdata_array   = array( // Set up the basic post data to insert for our variation product
				'grr_productid'     => $grr_productid,
				'info_communique'   => $product_data['info_communique'],
				'info_new'			=> $product_data['info_new'],
				'info_weight'       => $product_data['info_weight'],
				'info_width'        => $product_data['info_width'],
				'info_height'       => $product_data['info_height'],
				'info_length'       => $product_data['info_length'],
				'info_presentation' => $product_data['info_presentation'],
				'info_material'     => $product_data['info_material'],
				'info_parent_title' => $product_data['info_parent_title'],
				'info_year'         => $product_data['info_year'],
				'info_center1' => $product_data['info_center1'],
				'info_center2' => $product_data['info_center2'],
				'info_center1_component_price' => $product_data['info_center1_component_price'],
				'info_center2_component_price' => $product_data['info_center2_component_price'],
				'post_content' => $product_data['description'],
				'post_title' => $product_data['name'],
				'grouping' => $product_data['grouping'],
				'departments' => $product_data['departments'],
				'info_supplier_id' => $product_data['info_supplier_id'],
				'monstacolor'  => $product_data['monstacolor'],
				'monst_product_cat' => $product_data['monst_product_cat'],
				'is_image_updated' => $product_data['is_image_updated']
			);

			$product_count	=	self::create_product_variation($webmonsta_productid, $variation_data, $additonal_variationdata_array);
			return $product_count;
		}

		public static function create_product_variation( $product_id, $variation_data, $additonal_variationdata_array) {
			global $wpdb;
			$product_count	=	array();
			$first_children_id = 0;
			// (if needed) Get an instance of the WC_product object (from a dynamic product ID)
		    $product = wc_get_product($product_id);

			$product_count['parent_id']	= $product_id;
	        if(!empty($variation_data['sku'])) {
	            $variationexist = $wpdb->get_row( "SELECT post_id FROM ".$wpdb->prefix."postmeta WHERE `meta_key` = '_sku' and meta_value = '".$variation_data['sku']."'");
	        }
			$is_product_updated = 0;
			// if variant is exists
    	    if(isset($variationexist) && isset($variationexist->post_id)) {
    	    	$variation_id   =   $variationexist->post_id;
    			$product_count['update']	= 1;
				$is_product_updated = 1;
    	    } else { // add new variant
                $variation_post = array(
    		        'post_title'  => trim(preg_replace('/\s+/', ' ', $additonal_variationdata_array['post_title'])),
    		        'post_name'   => 'product-'.$product_id.'-variation',
    		        'post_status' => 'publish',
    		        'post_parent' => $product_id,
    		        'post_type'   => 'product_variation',
    		        //'guid'        => $product->get_permalink()
    		    );
    	        // Creating the product variation
    		    $variation_id = wp_insert_post( $variation_post );
    			$product_count['insert']	= 1;
    	    }

			update_post_meta($variation_id, '_variation_description', $additonal_variationdata_array['post_content']); // Set trophymonsta_text_field
            update_post_meta($variation_id, '_trophymonsta_text_field', 'trophymonsta'); // Set trophymonsta_text_field
            update_post_meta($variation_id, '_trophymonsta_product_id_text_field', $additonal_variationdata_array['grr_productid']); // Set trophymonsta product id
            update_post_meta($variation_id, '_trophymonsta_info_communique', $additonal_variationdata_array['info_communique']);
			update_post_meta($variation_id, '_trophymonsta_info_new', $additonal_variationdata_array['info_new']); // Set as NEW product from GRR
            update_post_meta($variation_id, '_weight', $additonal_variationdata_array['info_weight']);
            update_post_meta($variation_id, '_width', $additonal_variationdata_array['info_width']);
            update_post_meta($variation_id, '_height', $additonal_variationdata_array['info_height']);
            update_post_meta($variation_id, '_length', $additonal_variationdata_array['info_length']);
            update_post_meta($variation_id, '_trophymonsta_presentation', $additonal_variationdata_array['info_presentation']);
            update_post_meta($variation_id, '_trophymonsta_material', $additonal_variationdata_array['info_material']);
            update_post_meta($variation_id, '_trophymonsta_year', $additonal_variationdata_array['info_year']);
			update_post_meta($variation_id, '_trophymonsta_name', trim(preg_replace('/\s+/', ' ', $additonal_variationdata_array['post_title'])));
			update_post_meta($variation_id, '_monst_product_cat', $additonal_variationdata_array['monst_product_cat']);


			if ($additonal_variationdata_array['info_center1'] == 'Yes') {
				foreach($additonal_variationdata_array['info_center1_component_price'] as $component) {
					update_post_meta($variation_id, '_trophymonsta_center1_component_price', $component->price, false);
				}
			}

			if ($additonal_variationdata_array['info_center2'] == 'Yes') {
				foreach($additonal_variationdata_array['info_center2_component_price'] as $component) {
					update_post_meta($variation_id, '_trophymonsta_center2_component_price', $component->price, false);
				}
			}

			if ($additonal_variationdata_array['monstacolor'] != '') {
				self::product_color($variation_id, $product_id, $additonal_variationdata_array['monstacolor']);
			}

			self::product_accessories($variation_id, $product_id, $additonal_variationdata_array['grouping'], $additonal_variationdata_array['info_supplier_id'], $additonal_variationdata_array['info_center1'], $additonal_variationdata_array['info_center2']);
			self::product_noprocesses($variation_id, $product_id, $additonal_variationdata_array['grouping'], $additonal_variationdata_array['departments']);

	        // Get an instance of the WC_Product_Variation object
	        $variation = new WC_Product_Variation( $variation_id );

			$product_attributes_data = $variations_default_attributes = array(); // Setup array to hold our product attributes data

	        // Iterating through the variations attributes
	        foreach ($variation_data['attributes'] as $attribute => $term_name ) {
				$product_attributes_data['pa_'.$attribute] = array( // Set this attributes array to a key to using the prefix 'pa'
						'name'         => 'pa_'.$attribute,
						'value'        => '',
						'is_visible'   => '1',
						'is_variation' => '1',
						'is_taxonomy'  => '1'
				);
				if($term_name != '') {
					$taxonomy = 'pa_'.$attribute; // The attribute taxonomy

					// Check if the Term name exist and if not we create it.
					$term = term_exists(sanitize_title($term_name), $taxonomy, null );
					if (is_array( $term )) {
						$term_id = $term['term_id'];
					} else {
						$term = wp_insert_term(trim(preg_replace('/\s+/', ' ', $term_name)), $taxonomy, array( 'slug' => sanitize_title($term_name)) ); // Create the term
						add_term_meta($term['term_id'], 'category_mode', 'trophymonsta', true);
					}

					$attributeslug = get_term_by('slug', sanitize_title($term_name), $taxonomy );  // Get the term slug
					// Get the post Terms names from the parent variable product.
					$post_term_names =  wp_get_post_terms( $product_id, $taxonomy, array('fields' => 'all') );
                    //error_log(date('Y-m-d H:i:s').' post_term_names 11111'.print_r($post_term_names,true).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
                    
                    if(!empty($post_term_names)) {
                        foreach($post_term_names as $posttermname) {
                            //error_log(date('Y-m-d H:i:s').' post_term_names 2222 ::: '.$posttermname->slug.print_r($posttermname,true).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
    						// Check if the post term exist and if not we set it in the parent variable product.
    						if(isset($posttermname->slug) && (sanitize_title($term_name) != $posttermname->slug)) {
						        //error_log(date('Y-m-d H:i:s').' post_term_names 3333 ::: '.sanitize_title($term_name).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
								wp_set_post_terms( $product_id, sanitize_title($term_name), $taxonomy, true );
    						} else if (!isset($posttermname->slug)) {
    						    //error_log(date('Y-m-d H:i:s').' post_term_names 5555 ::: '.sanitize_title($term_name).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
    							wp_set_post_terms( $product_id, sanitize_title($term_name), $taxonomy, true );
    						}
                        }
                    } else {
                        //error_log(date('Y-m-d H:i:s').' post_term_names 4444 ::: '.sanitize_title($term_name).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
    					wp_set_post_terms( $product_id, sanitize_title($term_name), $taxonomy, true );
                    }
                    
					// Set/save the attribute data in the product variation
					if ($taxonomy == 'pa_monstasize') {
						update_post_meta( $variation_id, 'attribute_'.$taxonomy, $attributeslug->slug );

						// Get children product variation IDs in an array
						$children_ids = $product->get_children();
						// Get the first ID value
						$first_children_id = reset($children_ids);
						$variations_default_attributes[$taxonomy] = get_post_meta( $first_children_id, 'attribute_pa_monstasize', true );
						// Save the variation default attributes to variable product meta data
						update_post_meta($product_id, '_default_attributes', $variations_default_attributes );
					}
				}
	        }

			update_post_meta($product_id, '_product_attributes', $product_attributes_data); // Attach the above array to the new posts meta data key '_product_attributes'
	        ## Set/save all other data

    	    // SKU
    	    if( ! empty( $variation_data['sku'] ) )
    	        $variation->set_sku( $variation_data['sku'] );
    
    	    // Prices
    	    if( empty( $variation_data['sale_price'] ) ){
    	        $variation->set_price( $variation_data['regular_price'] );
    	    } else {
    	        $variation->set_price( $variation_data['sale_price'] );
    	        $variation->set_sale_price( $variation_data['sale_price'] );
    	    }
    	    $variation->set_regular_price( $variation_data['regular_price'] );
    
    	    // Stock
    	    if(!empty($variation_data['stock_qty']) ){
    	        $variation->set_stock_quantity( $variation_data['stock_qty'] );
    	        $variation->set_manage_stock(true);
    	        $variation->set_stock_status('');
    	    } else {
    	        $variation->set_manage_stock(false);
    	    }
    
    	    $variation->set_weight(''); // weight (reseting)
    	    $variation->save(); // Save the data
    	    
    	    $productVariable = new WC_Product_Variable( $product_id );
            // add images to parent product based on variant too.
			
			if ($is_product_updated == 0 || ($is_product_updated == 1 && $additonal_variationdata_array['is_image_updated'] == 1)) {
				if(isset($variation_data['image']) && $variation_data['image'] != '') {
					$upload_id = self::upload_product_images($variation_data['image'], $variation_id, $product_id,$variation_data['sku']);
					$is_parent_desc = $wpdb->get_row( "SELECT `meta_value` FROM ".$wpdb->prefix."postmeta WHERE `post_id` = '".$product_id."' and `meta_key` = '_sku' ORDER by `meta_id` desc limit 1 " );

					if (isset($is_parent_desc->meta_value) && $is_parent_desc->meta_value == $variation_data['mastercode']) {
						$featured_image = $wpdb->get_row( "SELECT `meta_value` FROM ".$wpdb->prefix."postmeta WHERE `post_id` = '".$product_id."' and `meta_key` = '_thumbnail_id' ORDER by `meta_id` desc limit 1 " );
						if (isset($featured_image->meta_value)) {
								$wpdb->query("UPDATE ".$wpdb->prefix."postmeta SET meta_value = '".$upload_id."' WHERE  meta_key = '_thumbnail_id' and post_id = ".$product_id);
						} else {
								$wpdb->query("INSERT INTO ".$wpdb->prefix."postmeta (post_id, meta_key, meta_value) VALUES  (".$product_id.", '_thumbnail_id', '".$upload_id."')");
						}
					}
				} else {
					$imageexist = $wpdb->get_row( "SELECT post_id FROM ".$wpdb->prefix."postmeta WHERE `meta_key` = '_sku_image' and meta_value = '".$variation_data['sku']."'");
					// if image is exists
					if(isset($imageexist) && isset($imageexist->post_id)) {
						wp_delete_attachment( $imageexist->post_id );
					}
				}
			

				// add multiple images
				$imagegalleryexist = get_post_meta($variation_id, 'monsta_variation_gallery_images');
				if(isset($imagegalleryexist[0])) {
					foreach ( $imagegalleryexist[0] as $gimage ) {
						wp_delete_attachment( $gimage );
					}
				}
				if(isset($variation_data['multi_images']) && is_array($variation_data['multi_images']) && count($variation_data['multi_images'])>=1) {
					$image_id_array = array();

					foreach ($variation_data['multi_images'] as $ikey => $imageValues) {
							$imageupload_id = self::upload_product_imagegallery($imageValues->image, $variation_id, $product_id, $variation_data['sku']);
							if(isset($imageupload_id) && !empty($imageupload_id))
								$image_id_array[] = $imageupload_id;
					}
					if(count($image_id_array)>0) {
						update_post_meta($variation_id, 'monsta_variation_gallery_images', maybe_unserialize($image_id_array));
					}
				}
				// set default image for monsta products
				//$productVariable = new WC_Product_Variable( $product_id );
				$monsta_variations = $productVariable->get_available_variations();
				$default_variations = array();
				$first_variation_id = '';
				if(isset($monsta_variations) && is_array($monsta_variations) && count($monsta_variations)>0) {
					foreach ( $monsta_variations as $variation ) {
						if(isset($variation['image_id']) && $variation['image_id']!='') {
							$default_variations[$variation['variation_id']] = $variation['image_id'];
							if($first_variation_id=='') {
								$first_variation_id = $variation['image_id'];
							}
						}
					}
				}
				$default_attachment_id 	= '';
				if($first_children_id && isset($default_variations[$first_children_id])) {
					$default_attachment_id = $default_variations[$first_children_id];
				} else {
					$default_attachment_id = $first_variation_id;
				}

				if($default_attachment_id!='') {
					set_post_thumbnail( $product_id, $default_attachment_id );
				}
			//end process
			}
			return $product_count;
	    }

	public static function product_color ($variation_id, $product_id, $monstacolor) {

		$taxonomy = 'pa_monstacolor'; // The attribute taxonomy

		// Check if the Term name exist and if not we create it.
		$term = term_exists(sanitize_title($monstacolor), $taxonomy, null );
		if (is_array( $term )) {
			$term_id = $term['term_id'];
		} else {
			$term = wp_insert_term(trim(preg_replace('/\s+/', ' ', $monstacolor)), $taxonomy, array( 'slug' => sanitize_title($monstacolor)) ); // Create the term
			add_term_meta($term['term_id'], 'category_mode', 'trophymonsta', true);
		}
        
        delete_post_meta($variation_id, $taxonomy);
        add_post_meta($variation_id, $taxonomy, sanitize_title($monstacolor), true);
        
		//$attributeslug = get_term_by('slug', sanitize_title($monstacolor), $taxonomy );  // Get the term slug
		// Get the post Terms names from the parent variable product.
		//$post_term_names =  wp_get_post_terms( $variation_id, $taxonomy, array('fields' => 'all') );
		
		
		
		//error_log(date('Y-m-d H:i:s').' product_color 11111'.print_r($post_term_names,true).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
                        
        /*if(!empty($post_term_names)) {
            foreach($post_term_names as $posttermname) {
                //error_log(date('Y-m-d H:i:s').' product_color 2222 ::: '.$posttermname->slug.print_r($posttermname,true).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
				// Check if the post term exist and if not we set it in the parent variable product.
				if(isset($posttermname->slug) && (sanitize_title($monstacolor) != $posttermname->slug)) {
			        //error_log(date('Y-m-d H:i:s').' product_color 3333 ::: '.sanitize_title($monstacolor).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
					add_post_meta($variation_id, $taxonomy, sanitize_title($monstacolor), true);
				} else if (!isset($posttermname->slug)) {
				    //error_log(date('Y-m-d H:i:s').' product_color 5555 ::: '.sanitize_title($monstacolor).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
					add_post_meta($variation_id, $taxonomy, sanitize_title($monstacolor), true);
				}
            }
        } else {
            //error_log(date('Y-m-d H:i:s').' product_color 4444 ::: '.sanitize_title($monstacolor).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			add_post_meta($variation_id, $taxonomy, sanitize_title($monstacolor), true);
        }*/
        
        
		/*// Check if the post term exist and if not we set it in the parent variable product.
		if(!in_array(sanitize_title($monstacolor), $post_term_names)) {
			//$post_term_names = wp_set_post_terms( $variation_id, sanitize_title($monstacolor), $taxonomy, true );
			add_post_meta($variation_id, $taxonomy, sanitize_title($monstacolor), true);
		}*/

	}

	public static function product_accessories ($variation_id, $product_id, $grouping, $brandid, $info_center1, $info_center2) {

		global $wpdb;
		delete_post_meta($variation_id, 'components_grouping_taxonomy');
		$monstavariants = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE `attribute_name` like 'monsta%' and `attribute_name` not in ('monstasize', 'monstacolor', 'monstamaterial', 'monstaengraving', 'monstaprocess')");
		$product_monstaattributes_data = array();
		$variations_default_attributes = array();
		foreach ( $monstavariants as $variant ) {
			$taxonomy = 'pa_'.$variant->attribute_name;
			delete_post_meta($variation_id, $taxonomy);
			
			if ($info_center1 == 'No' && $variant->attribute_name == 'monstacc1') {
			    continue;
			} else if ($info_center2 == 'No'  && $variant->attribute_name == 'monstacc2') {
			    continue;
			}

			$acce_group = array();
			foreach($grouping as $accgroup) {
				$acce_group[] = $accgroup.'___pa_'.$variant->attribute_name;
				add_post_meta($variation_id, 'components_grouping_taxonomy', $accgroup.'___pa_'.$variant->attribute_name, false);
			}

			$acce_group_string = "'".implode("', '", array_unique($acce_group))."'";

			// ORDER BY `meta_id` ASC
			$accessoriesgroup = $wpdb->get_results("SELECT ".$wpdb->prefix."terms.term_id, slug, name  FROM ".$wpdb->prefix."termmeta  join ".$wpdb->prefix."terms on (".$wpdb->prefix."termmeta.term_id = ".$wpdb->prefix."terms.term_id) WHERE (`meta_value` in (".$acce_group_string.") and `meta_key` = 'components_grouping_taxonomy')" );
			foreach ( $accessoriesgroup as $accesterm ) {
				// Get the post Terms names from the parent variable product.
				$post_term_names =  get_post_meta( $variation_id, $taxonomy, false );
				// Check if the post term exist and if not we set it in the parent variable product.
				if(!in_array($accesterm->slug, $post_term_names)) {
					add_post_meta($variation_id, $taxonomy, $accesterm->slug, false);
				}
			}
		}
	}

	public static function product_noprocesses ($variation_id, $product_id, $grouping, $departments) {
		global $wpdb;

		$taxonomy = 'pa_monstaengraving';
		$noprocess_group_dept = array();

		delete_post_meta($variation_id, '_trophymonsta_grouping_id');
		delete_post_meta($variation_id, '_trophymonsta_department_id');
		delete_post_meta($variation_id, '_trophymonsta_processes_id');
		delete_post_meta($variation_id, '_trophymonsta_no_processes_id');
		delete_post_meta($variation_id, 'process_departments_grouping');
		delete_post_meta($variation_id, 'process_departments_grouping_taxonomy');
		delete_post_meta($variation_id, $taxonomy);

		if (!empty((array)$grouping)) {
			foreach($grouping as $goup_id) {
				add_post_meta($variation_id, '_trophymonsta_grouping_id', $goup_id, false);
			}
		}

		if (!empty((array)$departments)) {
			foreach($departments as $department) {
				add_post_meta($variation_id, '_trophymonsta_department_id', $department->id, false);
				if (!empty((array)$department->process)) {
					foreach($department->process as $processes) {
						if($processes->is_no_process == 0) {
							add_post_meta($variation_id, '_trophymonsta_processes_id', $processes->id, false);
						} else {
							add_post_meta($variation_id, '_trophymonsta_no_processes_id', $processes->id, false);
						}
					}
				}
			}
		}

		if (!empty((array)$grouping)) {
			foreach($grouping as $goup_id) {
				if (!empty((array)$departments)) {
					foreach($departments as $department) {
						if (!empty((array)$department->process)) {
							foreach($department->process as $processes) {
								if($processes->is_no_process == 0) {
									add_post_meta($variation_id, 'process_departments_grouping', $department->id.'___'.$goup_id.'___'.$processes->id, false);
								} else {
									$noprocess_group_dept[] = $department->id.'___'.$goup_id.'___'.$taxonomy;
									add_post_meta($variation_id, 'process_departments_grouping_taxonomy', $department->id.'___'.$goup_id.'___'.$taxonomy, false);
								}
							}
						}
					}
				}
			}

			if (!empty($noprocess_group_dept)) {
				
				$noprocess_group_string = "'".implode("', '", array_unique($noprocess_group_dept))."'";
				$noprocessgroup = $wpdb->get_results("SELECT ".$wpdb->prefix."terms.term_id, slug, name  FROM ".$wpdb->prefix."termmeta join ".$wpdb->prefix."terms on (".$wpdb->prefix."termmeta.term_id = ".$wpdb->prefix."terms.term_id) WHERE (`meta_value` in (".$noprocess_group_string.") and `meta_key` = 'process_departments_grouping_taxonomy') " );
				
				foreach ( $noprocessgroup as $noprocessterm ) {
					add_post_meta($variation_id, $taxonomy, $noprocessterm->slug, false);
				}
			}
		}
	}

	public static function tropymonsta_override_checkout_fields( $checkout ){
		 $checkout['billing']['billing_address_1'] =  array(
														"label" => __('Address'),
														"placeholder" => __('Address'),
														"required" => true,
														"class" => array("form-row-wide","address-field"),
														"autocomplete" => "address-line1",
														'type' => 'text',
														'clear'     => true,
														);
		 return $checkout;

	}

	public static function tropymonsta_add_logofee() {
        $ip 		= null;
		$api_key 	= get_option( 'trophymonsta_api_key' );
		$response 	= Trophymonsta::http_get( Trophymonsta::build_query( array( 'key' => $api_key, 'site_url' => site_url() ) ), 'store/getLogoFee', $ip );
		$logo_fee 	= 0.00;
		$logo_fee_array = array();
		$response	= json_decode($response);
		if ($response->code == '200') {
			$logo_fee =  $response->success->logo_fee;
			$logo_fee_array = $response->success->logo_price;
		}
		$attachment_id =  isset( $_SESSION[ 'engraving_setting_uploaded_id' ] ) && $_SESSION[ 'engraving_setting_uploaded_id' ] != 'on' ? $_SESSION[ 'engraving_setting_uploaded_id' ] : '' ;
		if($attachment_id != ''){
			$attachment_ids = explode(',',$attachment_id);
			$logo_img = array();
			foreach($attachment_ids as $k => $postid ){
				$logo_img[] = wp_get_attachment_url( $postid );
			}
			//error_log(date('Y-m-d H:i:s').' Trophymonsta tropymonsta_add_logofee : '.count($logo_img).PHP_EOL, 3, get_template_directory()."/reg-errors.log");
			
			foreach ($logo_fee_array as $logo_price) {
				if ((count($logo_img) >= $logo_price->logo_count) && $logo_price->price > 0) {
					$logo_fee =  $logo_price->price;
				}
			}
			WC()->cart->add_fee( 'Logo Fee', ($logo_fee * count($logo_img)), true, 'standard' );
		}
	}

	public static function tropymonsta_existing_customer_logofee() {
		// check
		$user 				= wp_get_current_user();
		$monsta_newcustomer	= $existing_logofee = 0;
		$current_userid 	= isset( $user->ID ) ? (int) $user->ID : 0;
		if ( isset( $_SESSION[ 'session_monsta_newcustomer' ] ) &&  $_SESSION[ 'session_monsta_newcustomer' ] == 1 ) {
			return $_SESSION[ 'session_monsta_newcustomer' ];
		} else {
			if($current_userid>0) {
				$current_user_orders = get_posts( array(
										'numberposts' => -1,
										'meta_key'    => '_customer_user',
										'meta_value'  => $current_userid,
										'post_type'   => wc_get_order_types(),
										'post_status' => array_keys( wc_get_order_statuses() ),
									) );
				if ( isset($current_user_orders) && count( $current_user_orders )>0 ) {
					foreach($current_user_orders as $orderkey=>$odervalue) {
						$monstaorder = wc_get_order($odervalue->ID);
						foreach( $monstaorder->get_items('fee') as $item_id => $item_fee ) {
							$fees = $item_fee->get_data();
							if( isset( $fees['name'] ) && $fees['name'] == 'Logo Fee' ) {
								$existing_logofee = isset( $fees[ 'total' ] ) ? $fees[ 'total' ] : 0 ;
							}
						}
						if($existing_logofee>0) {
							$monsta_newcustomer = 1;
							$_SESSION[ 'session_monsta_newcustomer' ] = 1;
							break;
						}
					}
				}
			}
			return $monsta_newcustomer;
		}
	}

	public static function tropymonsta_engraving_process() {
		$trophyProductInfo = Monstaengravings::trophyProducts();
		if( !isset($_SESSION['engraving_step']) && isset($trophyProductInfo['trophyProductExist'])  && $trophyProductInfo['trophyProductExist'] ){
			$url =  get_site_url().'/monsta-engravings-settings/';
			wp_redirect( $url );
			exit;
		}
		if ( isset( $_POST[ 'receivedby' ] ) && $_POST[ 'receivedby' ] != '' ){
			$_SESSION['session_trophy_received_by'] =  $_POST[ 'receivedby' ];
		}
		if ( isset( $_POST[ 'presentedby' ] ) && $_POST[ 'presentedby' ] != '' ){
			$_SESSION['session_trophy_presented_by'] =  $_POST[ 'presentedby' ];
		}
		if ( isset( $_POST[ 'engraving_completion' ] ) && $_POST[ 'engraving_completion' ] == 1 ){
			return false;
		}
		if( isset($_SESSION[ 'validation_error' ] ) && $_SESSION[ 'validation_error' ] ){ //if validation error is present
			unset( $_SESSION[ 'validation_error' ] );
			return false;
		}
		/*if ( !is_user_logged_in() ) {
			$redirectTo = wp_login_url().'?redirect_to=monsta-engravings-settings';
			wp_redirect( $redirectTo );
			exit;
		}*/

		$url =  get_site_url().'/monsta-engravings-settings/';
		$items = WC()->cart->get_cart();
			$custompostmeta = '';
			foreach($items as $item => $values) {
				$_product =  wc_get_product( $values['data']->get_id());
				$custompostmeta = get_post_meta( $values['product_id'], '_trophymonsta_text_field', true );
				if($custompostmeta == 'trophymonsta' && (!isset($_SESSION['engraving_completed_status']) && $_SESSION['engraving_completed_status'] != 1)){
					wp_redirect( $url );
					exit;
				}
			}
	}
	public static function tropymonsta_checkout_validation( $data, $errors ){
		if( count( $errors->get_error_codes() ) > 0 ) {
			$_SESSION[ 'validation_error' ] = true;
		}
	}

}
