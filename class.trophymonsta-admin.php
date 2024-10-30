<?php
class Trophymonsta_Admin {
	const NONCE = 'trophymonsta-update-key';

	private static $initiated = false;
	private static $notices   = array();


	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}

		if ( isset( $_POST['action'] ) && esc_attr($_POST['action']) == 'enter-key' ) {
			self::enter_api_key();
		}

		if ( isset( $_POST['action'] ) && (esc_attr($_POST['action']) == 'import-all') ) {
			if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( esc_attr($_POST['_wpnonce']), self::NONCE ) ) {
				self::execute_import_cron();
			}
		}
		
		if ( isset( $_POST['action'] ) && (esc_attr($_POST['action']) == 'transientsposts') ) {
			if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( esc_attr($_POST['_wpnonce']), self::NONCE ) ) {
				self::transientsposts();
			}
		}
	}

	public static function init_hooks() {
		self::$initiated = true;
		add_action( 'admin_init', array( 'Trophymonsta_Admin', 'admin_init' ) );
		add_action( 'admin_menu', array( 'Trophymonsta_Admin', 'admin_menu' ), 5 ); # Priority 5, so it's called before Jetpack's admin_menu.
		add_action( 'admin_notices', array( 'Trophymonsta_Admin', 'display_notice' ) );
		add_action( 'admin_enqueue_scripts', array( 'Trophymonsta_Admin', 'load_resources' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( 'Trophymonsta_Admin', 'wc_trophymonsta_add_custom_fields' ));
		add_action( 'woocommerce_process_product_meta', array( 'Trophymonsta_Admin', 'wc_trophymonsta_save_custom_fields' ) );
		add_action('admin_notices', array( 'Trophymonsta_Admin', 'product_update_notice' ));
		add_filter( 'plugin_action_links_'.plugin_basename( plugin_dir_path( __FILE__ ) . 'monstamanagement.php'), array( 'Trophymonsta_Admin', 'admin_plugin_settings_link' ) );
		add_filter( 'all_plugins', array( 'Trophymonsta_Admin', 'modify_plugin_description' ) );
		add_filter( 'post_row_actions', array( 'Trophymonsta_Admin', 'post_row_actions' ), 11, 2 );
		add_filter( 'product_cat_row_actions', array( 'Trophymonsta_Admin', 'product_cat_row_actions' ), 11, 2 );
		add_filter( 'trophymonsta_brand_row_actions', array( 'Trophymonsta_Admin', 'product_cat_row_actions' ), 11, 2 );
		add_action( 'wp_ajax_import_status', array( 'Trophymonsta_Admin', 'import_status' ));
		add_action( 'woocommerce_admin_order_data_after_order_details', array( 'Trophymonsta_Admin', 'monstamanagement_display_order_data_in_admin') );
	}

	public static function product_update_notice(){
		global $pagenow;
		global $wpdb;
		
		$cron_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` LIKE 'cron' AND `option_value` LIKE '%trophymonsta_sync_status%'" );
	    if ( null === $cron_exists) {
			$multiple_recipients = array(
				'james@monstamanagement.com',
				'senthil@eventurers.com'
			);
			$subject = 'MonstaManagement 1 min cron is not running in '. get_bloginfo( 'name' );
			$body = 'MonstaManagement 1 min cron is not running, While sending this mail we have called this 1 mint cron anyway please check it and confim wethere cron is running.';
			$headers = array('Content-Type: text/html; charset=UTF-8');
			 
			wp_mail( $multiple_recipients, $subject, $body, $headers );
	        wp_schedule_event(time(), '1min', 'trophymonsta_sync_status');
	    }
		
		if ( ($pagenow == 'post.php' && (isset($_GET['action']) && esc_attr($_GET['action']) == 'edit'))) {
			 echo "<div class='notice notice-warning' id='permission_denied' style='display:none'>
				 <p>You don't have permission to update this product.</p>
			 </div>";
		}
		if (($pagenow == 'term.php' && (isset($_GET['tag_ID']) && esc_attr($_GET['tag_ID']) != ''))) {
			 echo "<div class='notice notice-warning' id='permission_denied' style='display:none'>
				 <p>You don't have permission to update this term.</p>
			 </div>";
		}
	}

	public static function admin_init() {
		load_plugin_textdomain( 'trophymonsta' );
	}

	public static function admin_menu() {
		self::load_menu();
	}

	public static function display_notice() {
		global $hook_suffix;

		if ( $hook_suffix == 'plugins.php' && !Trophymonsta::get_api_key() ) {
			self::display_api_key_warning();
		}
	}

	public static function display_api_key_warning() {
		Trophymonsta::view( 'notice', array( 'type' => 'plugin' ) );
	}

	public static function admin_plugin_settings_link( $links ) {
  		$settings_link = '<a href="'.esc_url( self::get_page_url() ).'">'.__('Settings', 'trophymonsta').'</a>';
  		array_unshift( $links, $settings_link );
  		return $links;
	}

	public static function load_menu() {
		$hook = add_options_page( __('MonstaManagement', 'trophymonsta'), __('MonstaManagement', 'trophymonsta'), 'manage_options', 'trophymonsta-key-config', array( 'Trophymonsta_Admin', 'display_page' ) );

		if ( $hook ) {
			add_action( "load-$hook", array( 'Trophymonsta_Admin', 'admin_help' ) );
		}
	}

	public static function import_status() {
		global $wpdb;
		$import_logs = $wpdb->get_results("SELECT ID, sync, type, status, total_count, page, delete_count, create_count, update_count FROM ".$wpdb->prefix."trophymonsta_import_log	WHERE type in ('grouping', 'brand', 'accessories', 'product', 'noprocesses', 'processes', 'material') and status != 'Yet to Start' order by ID desc limit 7");
		wp_send_json($import_logs);
		exit();
	}

	public static function load_resources() {
		global $hook_suffix;

		if ( in_array( $hook_suffix, apply_filters( 'trophymonsta_admin_page_hook_suffixes', array(
			'index.php', # dashboard
			'settings_page_trophymonsta-key-config',
			'plugins.php',
		) ) ) ) {
			wp_register_style( 'trophymonsta.css', plugin_dir_url( __FILE__ ) . '_inc/trophymonsta.css', array(), TROPHYMONSTA_VERSION );
			wp_enqueue_style( 'trophymonsta.css');

			wp_register_script( 'trophymonsta.js', plugin_dir_url( __FILE__ ) . '_inc/trophymonsta.js', array('jquery'), TROPHYMONSTA_VERSION );
			wp_enqueue_script( 'trophymonsta.js' );

			$inline_js = array(
				'comment_author_url_nonce' => wp_create_nonce( 'comment_author_url_nonce' ),
				'strings' => array(
					'Remove this URL' => __( 'Remove this URL' , 'trophymonsta'),
					'Removing...'     => __( 'Removing...' , 'trophymonsta'),
					'URL removed'     => __( 'URL removed' , 'trophymonsta'),
					'(undo)'          => __( '(undo)' , 'trophymonsta'),
					'Re-adding...'    => __( 'Re-adding...' , 'trophymonsta'),
				)
			);

			if ( isset( $_GET['trophymonsta_recheck'] ) && wp_verify_nonce( esc_attr($_GET['trophymonsta_recheck']), 'trophymonsta_recheck' ) ) {
				$inline_js['start_recheck'] = true;
			}

			wp_localize_script( 'trophymonsta.js', 'WPTrophymonsta', $inline_js );
		}

		$customtermmeta = '';
		$custompostmeta = '';
		$post_type = '';

		if (isset($_GET['post']))
			$custompostmeta = get_post_meta( esc_attr($_GET['post']), '_trophymonsta_text_field', true );
		if (isset($_GET['tag_ID']))
			$customtermmeta = get_term_meta(esc_attr($_GET['tag_ID']), 'category_mode', true);

		if (isset($_GET['post_type']))
		 	$post_type = esc_attr($_GET['post_type']);

		if( $custompostmeta == 'trophymonsta' || $customtermmeta == 'trophymonsta' && isset($_GET['post']) ) {
			wp_enqueue_script( 'titlescript', plugin_dir_url( __FILE__ ) . 'js/restrict-post.js', array( 'jquery' ) );
		} else if( $custompostmeta == 'trophymonsta' || $customtermmeta == 'trophymonsta' && isset($_GET['tag_ID']) ) {
			wp_enqueue_script( 'titlescript', plugin_dir_url( __FILE__ ) . 'js/restrict-edit.js', array( 'jquery' ) );
		} else if($post_type == 'product'  && isset($_GET['page']) && $_GET['page'] == 'product_attributes') {
			wp_enqueue_script( 'titlescript', plugin_dir_url( __FILE__ ) . 'js/restrict-attributes-list.js', array( 'jquery' ) );
		} else if($post_type == 'product' ) {
			wp_enqueue_script( 'titlescript', plugin_dir_url( __FILE__ ) . 'js/restrict-list.js', array( 'jquery' ) );
		}
	}

	/**
	 * When Trophymonsta is active, remove the "Activate Trophymonsta" step from the plugin description.
	 */
	public static function modify_plugin_description( $all_plugins ) {
		if ( isset( $all_plugins[plugin_dir_path( __FILE__ ) .'/monstamanagement.php'] ) ) {
			if ( Trophymonsta::get_api_key() ) {
				$all_plugins[plugin_dir_path( __FILE__ ) . '/monstamanagement.php']['Description'] = __( 'To Import and sell MonstaManagement products.', 'trophymonsta' );
			}
			else {
				$all_plugins[plugin_dir_path( __FILE__ ) .'/monstamanagement.php']['Description'] = __( 'To Import and sell MonstaManagement products. To get started, just go to <a href="admin.php?page=trophymonsta-key-config">your MonstaManagement Settings page</a> to set up your API key.', 'trophymonsta' );
			}
		}

		return $all_plugins;
	}

	public static function enter_api_key() {

		if ( ! current_user_can( 'manage_options' ) ) {
			die( __( 'Cheatin&#8217; uh?', 'trophymonsta' ) );
		}

		if ( !wp_verify_nonce( esc_attr($_POST['_wpnonce']), self::NONCE ) )
			return false;

		if ( Trophymonsta::predefined_api_key() ) {
			return false; //shouldn't have option to save key if already defined
		}

		//$new_key = preg_replace( '/[^a-f0-9]/i', '', $_POST['key'] );
		$new_key = esc_attr($_POST['key']);
		$old_key = Trophymonsta::get_api_key();

		if ( empty( $new_key ) ) {
			if ( !empty( $old_key ) ) {
				delete_option( 'trophymonsta_api_key' );
				self::$notices[] = 'new-key-empty';
			}
		}
		elseif ( $new_key != $old_key ) {
			self::save_key( $new_key );
		}

		return true;
	}

	public static function save_key( $api_key ) {

		$key_status = Trophymonsta::verify_key( $api_key );

		if ( $key_status == 'Verified successfully') {
			$trophymonsta_user = self::get_trophymonsta_user( $api_key );

			if ( $trophymonsta_user ) {

					update_option( 'trophymonsta_api_key', $api_key );
					update_option( 'trophymonsta_allow_web_order', $trophymonsta_user->subscription->allow_web_order );

				if ( $trophymonsta_user->subscription->status == 'active' )
					self::$notices['status'] = 'new-key-valid';
				elseif ( $trophymonsta_user->subscription->status == 'notice' )
					self::$notices['status'] = $trophymonsta_user;
				else
					self::$notices['status'] = $trophymonsta_user->subscription->status;
			}
			else {
				self::$notices['status'] = 'new-key-invalid';
			}
		}
		else {
			self::$notices['status'] = 'new-key-invalid';
		}
	}

	public static function get_page_url( $page = 'config' ) {

		$args = array( 'page' => 'trophymonsta-key-config' );

		if ( $page == 'delete_key' )
			$args = array( 'page' => 'trophymonsta-key-config', 'view' => 'start', 'action' => 'delete-key', '_wpnonce' => wp_create_nonce( self::NONCE ) );

		$url = add_query_arg( $args, admin_url( 'options-general.php' ) );

		return $url;
	}

	/**
	 * Add help to the Trophymonsta page
	 *
	 * @return false if not the Trophymonsta page
	 */
	public static function admin_help() {
		$current_screen = get_current_screen();

		// Screen Content
		if ( current_user_can( 'manage_options' ) ) {
			if ( !Trophymonsta::get_api_key() || ( isset( $_GET['view'] ) && esc_attr($_GET['view']) == 'start' ) ) {
				//setup page
				$current_screen->add_help_tab(
					array(
						'id'		=> 'overview',
						'title'		=> __( 'Overview' , 'trophymonsta'),
						'content'	=>
							'<p><strong>' . esc_html__( 'MonstaManagement Setup' , 'trophymonsta') . '</strong></p>' .
							'<p>' . esc_html__( 'To Import and sell MonstaManagement products.' , 'trophymonsta') . '</p>' .
							'<p>' . esc_html__( 'On this page, you are able to set up the MonstaManagement plugin.' , 'trophymonsta') . '</p>',
					)
				);

				$current_screen->add_help_tab(
					array(
						'id'		=> 'setup-signup',
						'title'		=> __( 'New to MonstaManagement' , 'trophymonsta'),
						'content'	=>
							'<p><strong>' . esc_html__( 'MonstaManagement Setup' , 'trophymonsta') . '</strong></p>' .
							'<p>' . esc_html__( 'You need to enter an API key to activate the MonstaManagement service on your site.' , 'trophymonsta') . '</p>' .
							'<p>' . sprintf( __( 'Sign up for an account on %s to get an API Key.' , 'trophymonsta'), '<a href="'.TROPHYMONSTA_API_URL.'freetrial/add" target="_blank">monstamanagement.com</a>' ) . '</p>',
					)
				);

				$current_screen->add_help_tab(
					array(
						'id'		=> 'setup-manual',
						'title'		=> __( 'Enter an API Key' , 'trophymonsta'),
						'content'	=>
							'<p><strong>' . esc_html__( 'MonstaManagement Setup' , 'trophymonsta') . '</strong></p>' .
							'<p>' . esc_html__( 'If you already have an API key' , 'trophymonsta') . '</p>' .
							'<ol>' .
								'<li>' . esc_html__( 'Copy and paste the API key into the text field.' , 'trophymonsta') . '</li>' .
								'<li>' . esc_html__( 'Click the Use this Key button.' , 'trophymonsta') . '</li>' .
							'</ol>',
					)
				);
			}
			else {
				//configuration page
				$current_screen->add_help_tab(
					array(
						'id'		=> 'overview',
						'title'		=> __( 'Overview' , 'trophymonsta'),
						'content'	=>
							'<p><strong>' . esc_html__( 'MonstaManagement Configuration' , 'trophymonsta') . '</strong></p>' .
							'<p>' . esc_html__( 'To Import and sell MonstaManagement products.' , 'trophymonsta') . '</p>' .
							'<p>' . esc_html__( 'On this page, you are able to update your MonstaManagement settings.' , 'trophymonsta') . '</p>',
					)
				);

				$current_screen->add_help_tab(
					array(
						'id'		=> 'settings',
						'title'		=> __( 'Settings' , 'trophymonsta'),
						'content'	=>
							'<p><strong>' . esc_html__( 'MonstaManagement Configuration' , 'trophymonsta') . '</strong></p>' .
							( Trophymonsta::predefined_api_key() ? '' : '<p><strong>' . esc_html__( 'API Key' , 'trophymonsta') . '</strong> - ' . esc_html__( 'Enter/remove an API key.' , 'trophymonsta') . '</p>' ),
					)
				);

				if ( ! Trophymonsta::predefined_api_key() ) {
					$current_screen->add_help_tab(
						array(
							'id'		=> 'account',
							'title'		=> __( 'Account' , 'trophymonsta'),
							'content'	=>
								'<p><strong>' . esc_html__( 'MonstaManagement Configuration' , 'trophymonsta') . '</strong></p>' .
								'<p><strong>' . esc_html__( 'Subscription Type' , 'trophymonsta') . '</strong> - ' . esc_html__( 'The MonstaManagement subscription plan' , 'trophymonsta') . '</p>' .
								'<p><strong>' . esc_html__( 'Status' , 'trophymonsta') . '</strong> - ' . esc_html__( 'The subscription status - active, cancelled or suspended' , 'trophymonsta') . '</p>',
						)
					);
				}
			}
		}

		// Help Sidebar
		$current_screen->set_help_sidebar(
			'<p><strong>' . esc_html__( 'For more information:' , 'trophymonsta') . '</strong></p>' .
			'<p><a href="'.TROPHYMONSTA_API_URL.'faq/" target="_blank">'     . esc_html__( 'MonstaManagement FAQ' , 'trophymonsta') . '</a></p>' .
			'<p><a href="'.TROPHYMONSTA_API_URL.'support/" target="_blank">' . esc_html__( 'MonstaManagement Support' , 'trophymonsta') . '</a></p>'
		);

	}

	public static function display_page() {
		self::display_configuration_page();
	}

	public static function display_configuration_page() {
		global $wpdb;
		$api_key      = Trophymonsta::get_api_key();

		$trophymonsta_user = self::get_trophymonsta_user( $api_key );
		$last_sync_date = get_option( 'trophymonsta_last_sync_date' );
		if ( ! $trophymonsta_user ) {
			// This could happen if the user's key became invalid after it was previously valid and successfully set up.
			if (isset(self::$notices['status']) && self::$notices['status'] == '')
				self::$notices['status'] = 'existing-key-invalid';

			self::display_start_page();
			return;
		}

		$synchronise_button_display = '0';
		$response	= Trophymonsta::http_get( Trophymonsta::build_query( array( 'key' => $api_key, 'site_url' => site_url() ) ), 'store/checksynchronise' );
		$response	= json_decode($response);

		if ($response->code == '200' && $response->success->result == '1') {
				$synchronise_button_display = '1';
		}
		$import_cron_logs = '0';
		$importcronlogs = $wpdb->get_row("SELECT option_name FROM ".$wpdb->prefix."options	WHERE option_name = 'cron' and (option_value like '%trophymonsta_catgroup_import%' or
		option_value like '%trophymonsta_product_import%')");

		if (isset($importcronlogs->option_name))
			$import_cron_logs = '1';

		$import_logs = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."trophymonsta_import_log	WHERE type in ('grouping', 'brand', 'accessories', 'product', 'noprocesses', 'processes', 'material') and status != 'Yet to Start' order by ID desc limit 7");

		Trophymonsta::view( 'config', compact( 'api_key', 'trophymonsta_user' , 'last_sync_date', 'import_logs', 'import_cron_logs', 'synchronise_button_display') );
	}

	public static function get_trophymonsta_user( $api_key ) {
		$trophymonsta_user = false;

		$response = Trophymonsta::http_get(Trophymonsta::build_query( array( 'key' => $api_key, 'site_url' => site_url() ) ), 'getsubscription' );

		$response = json_decode( $response );

		if($response->code == '200') {
			$trophymonsta_user = $response->success;
		}

		return $trophymonsta_user;
	}

	public static function display_start_page() {

		if ( isset( $_GET['action'] ) ) {
			if ( $_GET['action'] == 'delete-key' ) {
				if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( esc_attr($_GET['_wpnonce']), self::NONCE ) )
					delete_option( 'trophymonsta_api_key' );
			}
		}

		if ( $api_key = Trophymonsta::get_api_key() && ( empty( self::$notices['status'] ) || 'existing-key-invalid' != self::$notices['status'] ) ) {
			self::display_configuration_page();
			return;
		}
		Trophymonsta::view( 'start');

	}

	public static function execute_import_cron() {
		global $wpdb;
		$cron_exists = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."options WHERE `option_name` LIKE 'cron' AND `option_value` LIKE '%trophymonsta_product_import%'" );
		if ( null === $cron_exists) {
				wp_schedule_event(time(), 'daily', 'trophymonsta_catgroup_import');
		}
	}
	
	public static function transientsposts() {
		global $wpdb;
		$transientsposts = $wpdb->get_results("SELECT `ID` FROM ".$wpdb->prefix."posts WHERE `post_type` = 'product'");
		//if (TROPHYMONSTA_DEBUG)
	    //	error_log(date('Y-m-d H:i:s')." transientsposts : ".print_r($transientsposts,true)." Import Completed".PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");

		foreach ($transientsposts as $post ) {
		    error_log(date('Y-m-d H:i:s')." Transientsposts ADMIN : ".$post->ID." Import Completed".PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			wc_delete_product_transients($post->ID);
			$productVariable = new WC_Product_Variable($post->ID);
			$productVariableDS = new WC_Product_Variable_Data_Store_CPT();
			$productVariableDS->read_children($productVariable, true);
			$productVariableDS->read_price_data($productVariable, true);
			$productVariableDS->read_variation_attributes($productVariable);
		}
	}

	public static function verify_wpcom_key( $api_key, $user_id, $extra = array() ) {
		$trophymonsta_account = Trophymonsta::http_post( Trophymonsta::build_query( array_merge( array(
			'user_id'          => $user_id,
			'api_key'          => $api_key,
			'get_account_type' => 'true'
		), $extra ) ), 'verify-wpcom-key' );

		if ( ! empty( $trophymonsta_account[1] ) )
			$trophymonsta_account = json_decode( $trophymonsta_account[1] );

		Trophymonsta::log( compact( 'trophymonsta_account' ) );

		return $trophymonsta_account;
	}

	public static function display_status() {
		if ( ! self::get_server_connectivity() ) {
			Trophymonsta::view( 'notice', array( 'type' => 'servers-be-down' ) );
		}
		else if ( ! empty( self::$notices ) ) {
			foreach ( self::$notices as $index => $type ) {
				if ( is_object( $type ) ) {
					$notice_header = $notice_text = '';

					if ( property_exists( $type, 'notice_header' ) ) {
						$notice_header = wp_kses( $type->notice_header, self::$allowed );
					}

					if ( property_exists( $type, 'notice_text' ) ) {
						$notice_text = wp_kses( $type->notice_text, self::$allowed );
					}

					if ( property_exists( $type, 'status' ) ) {
						$type = wp_kses( $type->status, self::$allowed );
						Trophymonsta::view( 'notice', compact( 'type', 'notice_header', 'notice_text' ) );

						unset( self::$notices[ $index ] );
					}
				}
				else {
					Trophymonsta::view( 'notice', compact( 'type' ) );

					unset( self::$notices[ $index ] );
				}
			}
		}
	}

	// Check the server connectivity and store the available servers in an option.
	public static function get_server_connectivity($cache_timeout = 86400) {
		return self::check_server_connectivity( $cache_timeout );
	}

	// Simpler connectivity check
	public static function check_server_connectivity($cache_timeout = 86400) {

		$debug = array();
		$debug[ 'PHP_VERSION' ]         = PHP_VERSION;
		$debug[ 'WORDPRESS_VERSION' ]   = $GLOBALS['wp_version'];
		$debug[ 'TROPHYMONSTA_VERSION' ]     = TROPHYMONSTA_VERSION;
		$debug[ 'TROPHYMONSTA_PLUGIN_DIR' ] = TROPHYMONSTA_PLUGIN_DIR;
		$debug[ 'SITE_URL' ]            = site_url();
		$debug[ 'HOME_URL' ]            = home_url();

		/*$servers = get_option('trophymonsta_available_servers');
		if ( (time() - get_option('trophymonsta_connectivity_time') < $cache_timeout) && $servers !== false ) {
			$servers = self::check_server_ip_connectivity();
			update_option('trophymonsta_available_servers', $servers);
			update_option('trophymonsta_connectivity_time', time());
		}*/

		if ( wp_http_supports( array( 'ssl' ) ) ) {
			$response = wp_remote_get(TROPHYMONSTA_API_URL.'api/test' );
		}
		else {
			$response = wp_remote_get(TROPHYMONSTA_API_URL.'api/test' );
		}

		$debug[ 'gethostbynamel' ]  = function_exists('gethostbynamel') ? 'exists' : 'not here';
		$debug[ 'Servers' ]         = $servers;
		$debug[ 'Test Connection' ] = $response;

		Trophymonsta::log( $debug );
		//if (isset($response['body'])) {
		if (wp_remote_retrieve_body($response)) {
			$response = json_decode($response['body']);
			if ( $response->code == '200' && 'connected successfully' == $response->success->notifications)
				return true;
		}

		return false;
	}

	// Check connectivity between the WordPress blog and Trophymonsta's servers.
	// Returns an associative array of server IP addresses, where the key is the IP address, and value is true (available) or false (unable to connect).
	public static function check_server_ip_connectivity() {

		$servers = $ips = array();

		// Some web hosts may disable this function
		if ( function_exists('gethostbynamel') ) {

			$ips = gethostbynamel( TROPHYMONSTA_API_URL );
			if ( $ips && is_array($ips) && count($ips) ) {
				$api_key = Trophymonsta::get_api_key();

				foreach ( $ips as $ip ) {
					$response = Trophymonsta::verify_key( $api_key, $ip );
					// even if the key is invalid, at least we know we have connectivity
					if ( $response == 'valid' || $response == 'invalid' )
						$servers[$ip] = 'connected';
					else
						$servers[$ip] = $response ? $response : 'unable to connect';
				}
			}
		}

		return $servers;
	}

	public static function post_row_actions($actions, $post) {

			$custompostmeta = get_post_meta( $post->ID, '_trophymonsta_text_field', true );
			if( $custompostmeta == 'trophymonsta') {
					if( isset($actions['edit']) ) {
							unset($actions['edit']);
					}
					if( isset($actions['inline hide-if-no-js']) ) {
							unset($actions['inline hide-if-no-js']);
					}
					if( isset($actions['delete']) ) {
							unset($actions['delete']);
					}
					if( isset($actions['duplicate']) ) {
							unset($actions['duplicate']);
					}
					if( isset($actions['trash']) ) {
							unset($actions['trash']);
					}
					$actions['duplicate'] = '<input type="hidden" class="trophymonstahidden" name="trophymonsta" value="trophymonsta">';
			}
			return $actions;
	}

	public static function product_cat_row_actions($actions, $term) {
			$custompostmeta = get_term_meta($term->term_id, 'category_mode', true);
			if( $custompostmeta == 'trophymonsta') {
					if( isset($actions['edit']) ) {
							unset($actions['edit']);
					}

					if( isset($actions['inline hide-if-no-js']) ) {
							unset($actions['inline hide-if-no-js']);
					}

					if( isset($actions['delete']) ) {
							unset($actions['delete']);
					}

					if( isset($actions['duplicate']) ) {
							unset($actions['duplicate']);
					}
					$actions['duplicate'] = '<input type="hidden" class="trophymonstahidden" name="trophymonsta" value="trophymonsta">';

			}
			return $actions;
	}

	public static function wc_trophymonsta_add_custom_fields() {
		global $woocommerce, $post;

		$ismonstaproduct = get_post_meta( $post->ID, '_trophymonsta_text_field', true );

		if ($ismonstaproduct == 'trophymonsta') {
			echo '<div class="product_monsta_field">';

			echo '<div id="email_engraving_process"  class="tmm-download">
					<span> Please download excel to send engraving details via email </span> <a href="'.TROPHYMONSTA_PLUGIN_URL.'engravings_template.xlsx"  download >download xlsx</a>
			</div>';
			// Print a custom text field
	    woocommerce_wp_hidden_input( array(
				'value' => 'trophymonsta',
				'id' => '_trophymonsta_text_field'
	       ) );

			 woocommerce_wp_select(
				array(
					'id'          => '_trophymonsta_new_field',
					'label'       => __( 'Monsta New', 'trophymonsta' ),
					'description' => __( 'Choose a value.', 'trophymonsta' ),
					'selected' => true,
					'value'       => get_post_meta( $post->ID, '_trophymonsta_new_field', true ),
					'options' => array(
						''   => __( 'select', 'trophymonsta' ),
						'Yes'   => __( 'Yes', 'trophymonsta' ),
						'No'   => __( 'No', 'trophymonsta' )
						)
					)
				);

			woocommerce_wp_select(
			 array(
				 'id'          => '_trophymonsta_presentation',
				 'label'       => __( 'Monsta Presentation', 'trophymonsta' ),
				 'description' => __( 'Choose a value.', 'trophymonsta' ),
				 'selected' => true,
				 'value'       => get_post_meta( $post->ID, '_trophymonsta_presentation', true ),
				 'options' => array(
					 ''   => __( 'select', 'trophymonsta' ),
					 'Yes'   => __( 'Yes', 'trophymonsta' ),
					 'No'   => __( 'No', 'trophymonsta' )
					 )
				 )
			 );

			$taxonomy_name = 'pa_monstamaterial';
	 		$materialoptions = array();
	 		$terms = get_terms( array(
	 		  'taxonomy' => $taxonomy_name,
	 		  'hide_empty' => false
	 		) );
	 		$materialoptions[''] = __( 'select', 'trophymonsta' );
	 		foreach ( $terms as $term ) {
	 		  $materialoptions[$term->name] = __( $term->name, 'trophymonsta' );
	 		}

		 woocommerce_wp_select(
			 array(
				 'id'          => '_trophymonsta_material',
				 'label'       => __( 'Monsta Material', 'trophymonsta' ),
				 'description' => __( 'Choose a value.', 'trophymonsta' ),
				 'selected' => true,
				 'value'       => get_post_meta($post->ID, '_trophymonsta_material', true ),
				 'options' => $materialoptions
				 )
			 );

			//Custom Product  Textarea
	    woocommerce_wp_textarea_input(
	        array(
	            'id' => '_trophymonsta_info_communique',
	            'placeholder' => 'Monsta Info Communique',
	            'label' => __('Monsta Info Communique', 'trophymonsta'),
							'value' => get_post_meta( $post->ID, '_trophymonsta_info_communique', true ),
	        )
	    );

			// Custom Product Text Field
	    woocommerce_wp_text_input(
	        array(
	            'id' => '_trophymonsta_year',
	            'placeholder' => 'Monsta Year',
	            'label' => __('Monsta Year', 'trophymonsta'),
	            'desc_tip' => 'true',
							'value' => get_post_meta( $post->ID, '_trophymonsta_year', true ),
	        )
	    );
			 echo '</div>';
		}

	}

	public static function wc_trophymonsta_save_custom_fields( $post_id ) {
		$ismonstaproduct = get_post_meta($post_id, '_trophymonsta_text_field', true );

		if ($ismonstaproduct == 'trophymonsta') {
	    if (!empty($_POST['_trophymonsta_text_field'])) {
					update_post_meta( esc_attr($post_id), '_trophymonsta_text_field', 'trophymonsta', true );
	    }

			if (isset($_POST['_trophymonsta_new_field'])) {
				update_post_meta($post_id, '_trophymonsta_new_field', esc_attr($_POST['_trophymonsta_new_field']));
	    }
			if (isset($_POST['_trophymonsta_info_communique']) ) {
				update_post_meta($post_id, '_trophymonsta_info_communique', esc_attr($_POST['_trophymonsta_info_communique']));
	    }
			if (isset($_POST['_trophymonsta_presentation']) ) {
				update_post_meta($post_id, '_trophymonsta_presentation', esc_attr($_POST['_trophymonsta_presentation']));
	    }
			if (isset($_POST['_trophymonsta_material']) ) {
				update_post_meta($post_id, '_trophymonsta_material', esc_attr($_POST['_trophymonsta_material']));
	    }
			if (isset($_POST['_trophymonsta_year']) ) {
				update_post_meta($post_id, '_trophymonsta_year', esc_attr($_POST['_trophymonsta_year']));
	    }
		}
	}

	// display the extra data in the order admin panel
public static function monstamanagement_display_order_data_in_admin( $order ){
	global $wpdb;
	 ?>
    <div class="order_data_column">
        <h4><?php _e( 'Extra Details' ); ?></h4>
        <?php
				$logo_content = $order->get_meta('Logo_content');
				$forget_engraving = $order->get_meta('forget_engraving');
				$engraving_logo_user_id = $order->get_meta('engraving_logo_user_id');
				$engraving_by_email = $order->get_meta('engraving_by_email');
				$trophy_xls_attachment = get_post_meta($order->get_id(), '_monsta_engravings_xls',true);
				$wordpress_upload_dir = wp_upload_dir();
				if($trophy_xls_attachment && file_exists( $wordpress_upload_dir['path'].'/'.$trophy_xls_attachment ) && $forget_engraving == '' &&  $engraving_by_email == '' ){
					$trophy_excel_attacment = $wordpress_upload_dir['url'].'/'.$trophy_xls_attachment;
					echo '<p><strong>' . __( 'Monsta Engravings xls' ) . ': </strong> <a href="'.$trophy_excel_attacment.'" >download</a></p>';
				}

				$customer_date = get_post_meta($order->get_id(), 'engraving_setting_customer_date',true);
				$presentation_date = get_post_meta($order->get_id(), 'engraving_setting_presentation_date',true);

				echo '<p><strong>Customer Date: </strong>'.$customer_date.'</p>';
				echo '<p><strong>Presentation Date: </strong>'.$presentation_date.'</p>';

				// Get the user ID from WC_Order methods
				if( isset($logo_content) && $logo_content != '' ){
					echo '<p><strong>Logo: </strong> '.$logo_content.'</p>';
				}else if($engraving_logo_user_id != ''){
					$userid = $order->get_user_id();
					$get_latest_user_posts = $wpdb->get_results( "SELECT `ID` FROM ".$wpdb->prefix."posts WHERE `ID` in (".$engraving_logo_user_id.") and post_type = 'attachment' ORDER by `ID` DESC" );
					if ( count($get_latest_user_posts) > 0 ) {
						echo '<p><strong>Logo: </strong>';
						
						foreach($get_latest_user_posts as $k=> $posts){
							$existinglogo_attachment = wp_get_attachment_url( $posts->ID );
							echo '<a href="'.$existinglogo_attachment.'" download > download </a> ';
						}
						echo '</p>';
					}
					
					/*if ( $get_latest_user_posts !== null ) {
						$existinglogo_attachment = wp_get_attachment_url( $get_latest_user_posts->ID );
						//echo '<p><strong>Logo: </strong> <img src="'. $existinglogo_attachment.'" width="191" height="70" style="border:0"></p>';
						echo '<p><strong>Logo: </strong><a href="'.$existinglogo_attachment.'" download > download </a> </p>';
					}*/
				}


        ?>
    </div>
<?php }


}
