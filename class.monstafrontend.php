<?php
class Monstafrontend {

	private static $initiated = false;

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
		add_action( 'woocommerce_before_shop_loop_item_title', array( 'Monstafrontend', 'monstamanagement_productlist_newtag' ), 10, 0 );
		add_action( 'woocommerce_single_product_summary', array( 'Monstafrontend', 'monstamanagement_product_newtag' ), 10);
		add_filter( 'woocommerce_product_tabs', array( 'Monstafrontend', 'monstamanagement_woo_remove_reviews_tab' ), 98 );
		add_filter( 'woocommerce_get_breadcrumb', array( 'Monstafrontend', 'monstamanagement_change_breadcrumb' ) );
		add_action( 'woocommerce_single_variation', array( 'Monstafrontend', 'monstamanagement_attribute_list' ), 2 );
		add_action( 'woocommerce_product_meta_end', array( 'Monstafrontend', 'monstamanagement_quantity_based_pricing_table' ), 2 );
		add_action( 'woocommerce_before_calculate_totals', array( 'Monstafrontend', 'monstamanagement_quantity_based_pricing' ), 9999 );
		add_filter( 'woocommerce_get_item_data', array( 'Monstafrontend', 'monstamanagement_cart_display_bulk_price' ), 10, 2 );
		add_filter( 'woocommerce_add_cart_item_data', array( 'Monstafrontend', 'monstamanagement_add_custom_field_item_data'), 10, 4 );
		add_filter( 'woocommerce_cart_item_name', array( 'Monstafrontend', 'monstamanagement_cart_item_name'), 10, 3 );
		add_action('wp_head',  array( 'Monstafrontend', 'monstamanagement_wpb_hook_javascript') );
		add_action( 'woocommerce_checkout_create_order_line_item', array( 'Monstafrontend', 'monstamanagement_checkout_create_order_line_item') , 20, 4);
		add_filter( 'woocommerce_get_price_html', array('Monstafrontend','monstamanagement_change_price_html'), 9998, 2 );
		//add_filter( 'woocommerce_email_order_items_args', array('Monstafrontend','monstamanagement_email_order_items_args'), 10, 1 );
		//add_filter( 'woocommerce_email_customer_details', array('Monstafrontend','attach_engraving_xls_to_email'), 100, 3);
		add_filter( 'woocommerce_email_order_items_table', array( 'Monstafrontend', 'monstamanagement_add_images_woocommerce_emails'), 10, 990 );
		add_filter( 'woocommerce_email_classes', array( 'Monstafrontend', 'monstamanagement_woocommerce_email_classes'), 10, 991 );
		add_filter( 'woocommerce_order_item_visible', array('Monstafrontend','monstamanagement_order_item_visible'),1,992 );
		add_action( 'trophy_email_order_details', array( 'Monstafrontend', 'order_details' ), 10, 993 );
		add_action( 'trophy_email_customer_details', array( 'Monstafrontend', 'monstamanagement_customer_details' ), 10, 994 );
		add_action( 'trophy_email_product_listing',array('Monstafrontend', 'monstamanagement_email_product_listing'),10,995 );
		add_action( 'trophy_email_engraving_details',array('Monstafrontend', 'monstamanagement_email_engraving_details'),10,996 );
		add_action( 'woocommerce_after_order_object_save', array('Monstafrontend', 'custom_export_pending_order_data'), 10, 2 );
		add_filter( 'trophy_email_generate_excel', array('Monstafrontend','attach_engraving_xls_to_email'), 100, 997);
		add_filter( 'trophy_email_footer', array('Monstafrontend','monstamanagement_email_footer'), 100, 998);
		add_filter( 'trophy_email_header', array('Monstafrontend','monstamanagement_email_header'), 100, 998);
		add_action( 'woocommerce_email', array('Monstafrontend','trophy_unhook_default_emails'),1 ,999);
		//Multiple variation Image Hooks
		if ( class_exists( 'Trophy_Custom_Image_Gallery' ) ) {
			remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
			add_action( 'woocommerce_before_single_product_summary', array('Monstafrontend','trophy_show_product_images'), 1 );
			add_filter( 'wc_get_template', array('Monstafrontend','monstamanagement_wvg_gallery_template_override'), 30, 2 );
			add_filter( 'wc_get_template_part', array('Monstafrontend','monstamanagement_wvg_gallery_template_part_override'), 30, 2 );
			add_action( 'wp_enqueue_scripts', array( 'Trophy_Custom_Image_Gallery', 'enqueue_scripts' ), 25 );
			add_action( 'wp_ajax_nopriv_wvg_get_default_gallery', array( 'Trophy_Custom_Image_Gallery','wvg_get_default_gallery') );
			add_action( 'wp_ajax_wvg_get_default_gallery', array( 'Trophy_Custom_Image_Gallery','wvg_get_default_gallery') );
			add_action( 'wp_ajax_wvg_get_available_variation_images', array('Trophy_Custom_Image_Gallery','wvg_get_available_variation_images') );
			add_action( 'wp_ajax_nopriv_wvg_get_available_variation_images', array('Trophy_Custom_Image_Gallery','wvg_get_available_variation_images') );
			add_action( 'wp_footer', array( 'Trophy_Custom_Image_Gallery', 'slider_template_js' ) );
			add_action( 'wp_footer', array( 'Trophy_Custom_Image_Gallery', 'thumbnail_template_js' ) );
			add_filter( 'woocommerce_available_variation', array( 'Trophy_Custom_Image_Gallery','wvg_available_variation_gallery'), 90, 3 );
		}

		//End
	}
	public static function trophy_show_product_images(){
	    //error_log(date('Y-m-d H:i:s').' Monstafrontend trophy_show_product_images'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		wc_get_template( '/product-images.php',array(),'',TROPHYMONSTA_PLUGIN_DIR.'templates' );
	}
	public static function monstamanagement_wvg_gallery_template_override( $located, $template_name ) {
	    //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_wvg_gallery_template_override'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		if ( $template_name == '/product-images.php' ) {
			$located = TROPHYMONSTA_PLUGIN_DIR.'templates/product-images.php';
		}
		return apply_filters( 'wvg_gallery_template_override_location', $located, $template_name );
	}


	public static function monstamanagement_wvg_gallery_template_part_override( $template, $slug ) {
	   // error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_wvg_gallery_template_part_override'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		if ( $slug == '/product-image' ) {
			$template = TROPHYMONSTA_PLUGIN_DIR.'templates/product-images.php';
		}
		return apply_filters( 'wvg_gallery_template_part_override_location', $template, $slug );
	}
	public static function trophy_unhook_default_emails($email_class){
	    //error_log(date('Y-m-d H:i:s').' Monstafrontend trophy_unhook_default_emails'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		//worked for issue
		//add_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $email_class->emails['Trophy_Custom_Email_Order'] , 'trigger' ), 10, 2 );
		//add_action( 'woocommerce_order_status_failed_to_on-hold_notification', array( $email_class->emails['Trophy_Custom_Email_Order'], 'trigger' ), 10, 2 );
		//add_action( 'woocommerce_order_status_cancelled_to_on-hold_notification', array( $email_class->emails['Trophy_Custom_Email_Order'], 'trigger' ), 10, 2 );
		
		//add_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $email_class->emails['Trophy_Custom_Admin_Email_Order'], 'trigger' ), 10, 2 );
		//add_action( 'woocommerce_order_status_failed_to_on-hold_notification', array( $email_class->emails['Trophy_Custom_Admin_Email_Order'], 'trigger' ), 10, 2 );
		//add_action( 'woocommerce_order_status_cancelled_to_on-hold_notification', array( $email_class->emails['Trophy_Custom_Admin_Email_Order'], 'trigger' ), 10, 2 );
		//end
		/**
		 * Hooks for sending emails during store events
		 **/
		remove_action( 'woocommerce_low_stock_notification', array( $email_class, 'low_stock' ) );
		remove_action( 'woocommerce_no_stock_notification', array( $email_class, 'no_stock' ) );
		remove_action( 'woocommerce_product_on_backorder_notification', array( $email_class, 'backorder' ) );

		// New order emails
		remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
		remove_action( 'woocommerce_order_status_pending_to_completed_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
		remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
		remove_action( 'woocommerce_order_status_failed_to_processing_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
		remove_action( 'woocommerce_order_status_failed_to_completed_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
		remove_action( 'woocommerce_order_status_failed_to_on-hold_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
		remove_action( 'woocommerce_order_status_on-hold_to_processing_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );

		// Processing order emails
		remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( $email_class->emails['WC_Email_Customer_Processing_Order'], 'trigger' ) );
		remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $email_class->emails['WC_Email_Customer_Processing_Order'], 'trigger' ) );
		remove_action( 'woocommerce_order_status_on-hold_to_processing_notification', array( $email_class->emails['WC_Email_Customer_Processing_Order'], 'trigger' ) );

		// Completed order emails
		remove_action( 'woocommerce_order_status_completed_notification', array( $email_class->emails['WC_Email_Customer_Completed_Order'], 'trigger' ) );

		// Note emails
		remove_action( 'woocommerce_new_customer_note_notification', array( $email_class->emails['WC_Email_Customer_Note'], 'trigger' ) );

		//on 20-02-2020
		remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $email_class->emails['WC_Email_Customer_On_Hold_Order'], 'trigger' ) );
		remove_action( 'woocommerce_order_status_failed_to_on-hold_notification', array( $email_class->emails['WC_Email_Customer_On_Hold_Order'], 'trigger' ) );
		remove_action( 'woocommerce_order_status_cancelled_to_on-hold_notification', array( $email_class->emails['WC_Email_Customer_On_Hold_Order'], 'trigger' ) );
		//end
	}

	public static function order_details( $order, $sent_to_admin = false, $plain_text = false, $email = '' ) {
	    //error_log(date('Y-m-d H:i:s').' Monstafrontend order_details'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		if ( $plain_text ) {
			wc_get_template(
					'emails/plain/custom-email-order-details.php',
				array(
					'order'         => $order,
					'sent_to_admin' => $sent_to_admin,
					'plain_text'    => $plain_text,
					'email'         => $email,
				), '', TROPHYMONSTA_PLUGIN_DIR.'templates/'
			);
		} else {
			wc_get_template(
				'emails/custom-email-order-details.php',
				array(
					'order'         => $order,
					'sent_to_admin' => $sent_to_admin,
					'plain_text'    => $plain_text,
					'email'         => $email,
				), '', TROPHYMONSTA_PLUGIN_DIR.'templates/'
			);
		}
	}

	public static function monstamanagement_start_session() {
	    //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_start_session'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		if(!headers_sent() && session_status() == PHP_SESSION_NONE ) {
			session_start();
			//session_start( ['read_and_close' => true] );
		}
	}

	public static function monstamanagement_clear_session() {
	   //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_clear_session'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
	   session_destroy ();
	}
	public static function monstamanagement_wpb_hook_javascript() {
	  //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_wpb_hook_javascript'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
	  if (is_page ('checkout')) {
		?>
			<script type="text/javascript" src="https://maps.google.com/maps/api/js?key=AIzaSyB_AwxRpMzP3gdqVtpAPQeH1VkUs4826UY&v=3&libraries=places&callback=initAutocomplete&language=en-AU">
			</script>

		<?php
	  }
	}

	// define the monstamanagement_productlist_newtag callback
	public static function monstamanagement_productlist_newtag(  ) {
	  //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_productlist_newtag'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
	  global $product;
		$custompostmeta = get_post_meta( $product->get_id(), '_trophymonsta_text_field', true );
		if ($custompostmeta == 'trophymonsta') {
			$infocommunique = get_post_meta( $product->get_id(), '_trophymonsta_info_new', true );
		  if($infocommunique =='Yes') echo '<div class="monsta-new">NEW</div>';
		}
	}

	// define the monstamanagement_product_newtag callback
	public static function monstamanagement_product_newtag() {
	  //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_product_newtag'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
	  global $product;
		$custompostmeta = get_post_meta( $product->get_id(), '_trophymonsta_text_field', true );
		if ($custompostmeta == 'trophymonsta') {
			$infocommunique = get_post_meta( $product->get_id(), '_trophymonsta_info_new', true );
		  if($infocommunique =='Yes') echo '<div class="monsta-detail-new">NEW</div>';
		}
	}

	public static function monstamanagement_change_breadcrumb( $crumbs ) {
	    //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_change_breadcrumb'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		$monsta_crumbs = array(	);
		foreach ($crumbs as $key => $crumb) {
			if (($crumb[0] != 'Monsta Categories') && ($crumb[0] != 'Local Groupings')){
				$monsta_crumbs[] = $crumb;
			}
		}
	    return $monsta_crumbs;
	}

	public static function monstamanagement_woo_remove_reviews_tab($tabs) {
	    //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_woo_remove_reviews_tab'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		global $product;
		$custompostmeta = get_post_meta( $product->get_id(), '_trophymonsta_text_field', true );
		if ($custompostmeta == 'trophymonsta') {
	    unset($tabs['reviews']);
		}
	    return $tabs;
	}

	public static function monstamanagement_quantity_based_pricing( $cart ) {
	    //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_quantity_based_pricing'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		global $wpdb;
	  if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

	  if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) return;
	  $ip = null;
		$api_key = get_option( 'trophymonsta_api_key' );
		$response = Trophymonsta::http_get( Trophymonsta::build_query( array( 'key' => $api_key, 'site_url' => site_url() ) ), 'store/getDiscounts', $ip );
		$response	= json_decode($response);

		if ($response->code == '400' || $response->code == '403' || $response->code == '404' || $response->code == '405') {
			return;
		}

		if ($response->code == '200' && wp_doing_ajax() ) {
			//unset Session for engraving
			//if( isset( $_SESSION[ 'engraving_step' ] ) && !isset( $_POST[ 'engraving_completion' ] ) ){
				//unset( $_SESSION[ 'engraving_step' ] );
			//}
			//end
			$flat_discount = $response->success->flat_discount;
			$mmproductdiscounts =  $response->success->product_discount;
			$mmaccessoriesdiscounts =  $response->success->component_discount;
			$mmprocessesdiscounts =  $response->success->process_discount;

			foreach ($cart->get_cart() as $cart_item_key => $cart_item ) {

				$product_id = $cart_item['product_id'];
				$product_discount_unit = 0;
				$product_discount_precent = 0;
				$processes_discount_precent = $accessories_discount_precent = 0;

				$custompostmeta = get_post_meta( $product_id, '_trophymonsta_text_field', true );
				if ($custompostmeta == 'trophymonsta') {

					$variationprice = get_post_meta($cart_item['variation_id'], '_regular_price', true);
					$cart_item['data']->set_name( get_post_meta($cart_item['variation_id'], '_trophymonsta_name', true) );

					// sub exist componets price from product variations and Add additional accessories center price for product variations
					$center_component_price = 0;
					if(isset($cart_item['attribute_pa_monstacc1']) && $cart_item['attribute_pa_monstacc1'] !=''){
						$component_price = get_post_meta($cart_item['variation_id'], '_trophymonsta_center1_component_price', true);

						$center_component_price = $center_component_price + $component_price;

						$getmonsta_center_termid 	= get_term_by( 'slug', $cart_item['attribute_pa_monstacc1'], 'pa_monstacc1')->term_id;
						$attribute_center_price	= 0;
						if(isset($getmonsta_center_termid) && $getmonsta_center_termid>0) {
							$attribute_center_price 	= get_term_meta($getmonsta_center_termid, 'components_price', true);
							//if($attribute_center_price>0) {
								$variationprice = $variationprice - (float) $center_component_price;
								$variationprice = $variationprice + $attribute_center_price;
							//}
						}
					}
					$center_component_price = 0;
					if(isset($cart_item['attribute_pa_monstacc2']) && $cart_item['attribute_pa_monstacc2'] !=''){
						$component_price = get_post_meta($cart_item['variation_id'], '_trophymonsta_center2_component_price', true);
						$center_component_price = $center_component_price + $component_price;

						$getmonsta_center_termid 	= get_term_by( 'slug', $cart_item['attribute_pa_monstacc2'], 'pa_monstacc2')->term_id;
						$attribute_center_price	= 0;
						if(isset($getmonsta_center_termid) && $getmonsta_center_termid>0) {
							$attribute_center_price 	= get_term_meta($getmonsta_center_termid, 'components_price', true);
							//if($attribute_center_price>0) {
								$variationprice = $variationprice - (float) $center_component_price;
								$variationprice = $variationprice + $attribute_center_price;
							//}
						}
					}
					$product_discount_price = $variationprice;
					// calculate product discount price
					if ($flat_discount != 0 && $flat_discount != '') {
						$product_discount_price = round(  ($variationprice - ($variationprice * ($flat_discount / 100))), 2 );
					} else {
						foreach ($mmproductdiscounts as $key => $discount) {
							if ($cart_item['quantity'] >= $discount->unit) {
								$product_discount_unit = $discount->unit;
								$product_discount_precent = $discount->precent;
							}
						}
						// Add discount price for product variations
						if ($product_discount_unit != 0) {
							$product_discount_price = round(  ($variationprice - ($variationprice * ($product_discount_precent / 100))), 2 );
							//$cart_item['data']->set_price( $price );
						}
					}
					
					// calculate accessories discount price
					/*foreach ($mmaccessoriesdiscounts as $key => $discount) {
						if ($cart_item['quantity'] > $discount->unit) {
							$accessories_discount_precent = $discount->precent;
						}
					}*/

					// Add additional accessories discount price for product variations
					$accessories_discount_price = 0;
					$monstavariants = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE `attribute_name` like 'monsta%' and `attribute_name` not in ('monstasize', 'monstacolor', 'monstaengraving', 'monstamaterial', 'monstaprocess', 'monstacc1', 'monstacc2')");
					foreach ( $monstavariants as $variant ) {

						if (isset($cart_item['attribute_pa_'.$variant->attribute_name]) && $cart_item['attribute_pa_'.$variant->attribute_name] !='') {
							$monsta_termname 	= $cart_item['attribute_pa_'.$variant->attribute_name];

							$getmonsta_termid 	= get_term_by( 'slug', $monsta_termname, 'pa_'.$variant->attribute_name )->term_id;
							$attribute_price	= $attribute_qty_price = 0;
							if(isset($getmonsta_termid) && $getmonsta_termid>0) {
								$attribute_price 	= get_term_meta($getmonsta_termid, 'components_price', true);
								if(isset($attribute_price) && $attribute_price>0) {
									//$attribute_qty_price = round(($attribute_price - ($attribute_price * ($accessories_discount_precent / 100))),2);
									$attribute_qty_price = round($attribute_price,2);
									$attribute_qty_price = $attribute_qty_price;
								}
								$accessories_discount_price = $accessories_discount_price + $attribute_qty_price;
							}
						}
					}

					// calculate processes discount price //process_price
					/*foreach ($mmprocessesdiscounts as $key => $discount) {
						if ($cart_item['quantity'] > $discount->unit) {
							$processes_discount_precent = $discount->precent;
						}
					}*/

					// Add additional no processes discount price for product variations
					$processes_discount_price = 0;
					$monstaengraving = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE `attribute_name` = 'monstaengraving'");
					if ( null !== $monstaengraving) {
						if (isset($cart_item['attribute_pa_monstaengraving']) && $cart_item['attribute_pa_monstaengraving'] !='') {
							$monsta_termname 	= $cart_item['attribute_pa_monstaengraving'];
							$getmonsta_termid 	= get_term_by( 'slug', $monsta_termname, 'pa_monstaengraving')->term_id;
							$pro_attribute_price	= $pro_attribute_qty_price = 0;
							if(isset($getmonsta_termid) && $getmonsta_termid>0) {
								$pro_attribute_price 	= get_term_meta($getmonsta_termid, 'process_price', true);
								if(isset($pro_attribute_price) && $pro_attribute_price>0) {
									//$pro_attribute_qty_price = round(($pro_attribute_price - ($pro_attribute_price * ($processes_discount_precent / 100))),2);
									$pro_attribute_qty_price = round($pro_attribute_price,2);
									$pro_attribute_qty_price = $pro_attribute_qty_price;
								}
								$processes_discount_price = $pro_attribute_qty_price;
							}
						}
					}
					$cart_item['data']->set_price( $product_discount_price + $accessories_discount_price + $processes_discount_price );
				}
			}
		}
	}

	public static function monstamanagement_attribute_list() {
	    //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_attribute_list'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		global $wpdb;
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
		global $product;
		$custompostmeta = get_post_meta($product->get_id(), '_trophymonsta_text_field', true );
		if( $product->is_type( 'variable' ) && $custompostmeta == 'trophymonsta') {
			$pamonstacolor = array();
			foreach($product->get_visible_children( ) as $variation_id ) {
				$psmonstasize = get_post_meta($variation_id, 'attribute_pa_monstasize', true);
				$pacolor = get_post_meta( $variation_id, 'pa_monstacolor', true );
				if ($pacolor != '') {
					$pamonstacolor[$psmonstasize] = get_post_meta( $variation_id, 'pa_monstacolor', true );
				}
			}

			if (!empty($pamonstacolor)) {
				?>
				<div>
					<div class="label"><label for="pa_monstacolor">Color</label></div>
					<div class="value">
						<select name="attribute_pa_monstacolor" id="attribute_pa_monstacolor">
							<option value="" class="attached enabled">Select</option>
							<?php
								foreach ($pamonstacolor as $colorkey => $pacolor) {
									if ($pacolor != '') {
										$term = get_term_by('slug', $pacolor, 'pa_monstacolor');
										echo '<option value="'.$term->slug.'" data-attr="'.$colorkey.'" class="attached enabled">'.$term->name.'</option>';
									}
								}
							?>
						</select>
						</div>
				</div>
			<?php
			}
			foreach($product->get_visible_children() as $variation_id ) {

				$monstavariants = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE `attribute_name` like 'monsta%' and `attribute_name` not in ('monstasize' , 'monstamaterial', 'monstaprocess', 'monstacolor')");
				foreach($monstavariants as $k => $variation){
					$variation_name = $variation->attribute_name;

					/*$pamonstaattrarray = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."postmeta WHERE `post_id` = ".$variation_id." and `meta_key` = 'pa_".$variation_name."' ORDER BY `meta_id` ASC");
					$pamonstaattr = array();
					foreach ($pamonstaattrarray as $pamattribute) {
						$pamonstaattr[] = $pamattribute->meta_value;
					}
					*/

					$pamonstaattr = get_post_meta( $variation_id, 'pa_'.$variation_name, false );

					$center1_component_price = 0;
					if ($variation_name == 'monstacc1'){
						$center1_component_price = get_post_meta($variation_id, '_trophymonsta_center1_component_price', true);
						if ($center1_component_price == '') {
							continue;
						}
					}

					$center2_component_price = 0;
					if ($variation_name == 'monstacc2'){
						$center2_component_price = get_post_meta($variation_id, '_trophymonsta_center2_component_price', true);
						if ($center2_component_price == '') {
							continue;
						}
					}

					if (!empty($pamonstaattr)) {
						?>
						<div style="clear:both;display:none;" class="monsta_attribute_pa_<?php echo $variation_name; ?>" id="<?php echo $variation_id; ?>_attribute_pa_<?php echo $variation_name; ?>">
						<div class="label"><label for="<?php echo 'pa_'.$variation_name; ?>"><?php echo $variation->attribute_label; ?></label></div>
						<div class="value">
							<select id="<?php echo $variation_id.'_pa_'.$variation_name; ?>" class="monsta_pa_<?php echo $variation_name; ?>" disabled="disabled" name="<?php echo 'attribute_pa_'.$variation_name; ?>" >
								<option value="" class="attached enabled">Select</option>
								<?php
									$pamonstaattrorderlist = array();
									foreach ($pamonstaattr as $mattribute) {
										$term = get_term_by('slug', $mattribute, 'pa_'.$variation_name);
										if ($variation_name == 'monstaengraving') {
											$pamonstaattrorderlist[] = $term;
										} else {
											$orderlist = get_term_meta($term->term_id, 'components_order_id', true);
											$pamonstaattrorderlist[$orderlist] = $term;
										}
									}
									ksort($pamonstaattrorderlist);
									foreach ($pamonstaattrorderlist as $term) {
										//print_r($term);
										$componentprice = 0;
										//$componentorder = '';
										//$term = get_term_by('slug', $term->name, 'pa_'.$variation_name);
										if ($variation_name == 'monstaengraving') {
											$componentprice = get_term_meta($term->term_id, 'process_price', true);
										} else {
											$componentprice = get_term_meta($term->term_id, 'components_price', true);
											//$orderlist = get_term_meta($term->name, 'components_order_id', true);
											//$componentorder = 'order-attr="'.$orderlist.'"';
										}
										$html_attr = '';
										if( $variation_name == 'monstacc1' ){
											$component_price = get_post_meta($variation_id, '_trophymonsta_center1_component_price', true);
											$html_attr = "default-price-cc1='".$component_price."'";
										}
										if( $variation_name == 'monstacc2' ){
											$component_price = get_post_meta($variation_id, '_trophymonsta_center2_component_price', true);
											$html_attr = "default-price-cc2='".$component_price."'";
										}

										echo '<option value="'.$term->slug.'" '.$html_attr.' price-attr="'.$componentprice.'" class="attached enabled">'.$term->name.'</option>';
									}
								?>
							</select>
							</div>
						</div>
					<?php
					}
				}
			}
		}
	}

	public static function monstamanagement_quantity_based_pricing_table() {
        //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_quantity_based_pricing_table'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
	 		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
	     global $product;

	 		$custompostmeta = get_post_meta($product->get_id(), '_trophymonsta_text_field', true );

	     if( $product->is_type( 'variable' ) && $custompostmeta == 'trophymonsta') {
	     ?>

	         <table class="monstaprice" cellspacing="0" cellpadding="0" width="100%">
	 				<tbody>
	 					<tr>
	 						<th colspan="7"><div><svg fill="#009442" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 484.5 484.5" width="25" height="25"><path d="M449.9 178.2h-8.1c-11.7-30.6-31.6-58.3-58.3-80.7v-.2c-4-13.9-1-28.8 8.7-44.3 5-8 5.3-17.7.7-25.9-4.5-8.1-13-13.2-22.2-13.2-1.1 0-2.2.1-3.2.2-37.1 4.6-59.3 21.5-72.1 37.2-22.2-6.1-45.2-9.2-68.6-9.2-60.3 0-117 20.6-159.8 58C23.8 137.9 0 188.3 0 242c0 13.9 1.6 27.8 4.9 41.4 0 .2.1.5.2.7 0 0 .1.2.2.6 3.4 13.7 8.4 27.1 14.9 39.8 10.7 23.3 23.6 42.7 38.1 57.7 20.6 21.2 24.1 48 24.6 58.8v6.3c0 .5 0 1 .1 1.4 1.2 12.3 11.7 21.8 24.1 21.8h74.5c13.3 0 24.2-10.9 24.2-24.2v-5c11.6.9 23.3 1.1 35 .5v4.6c0 13.3 10.9 24.2 24.2 24.2h74.5c13.3 0 24.2-10.9 24.2-24.2v-18.2c.1-4.3 1.8-24.7 21.1-42.3.9-.8 1.9-1.6 2.8-2.4l.2-.2c.2-.2.5-.4.7-.6 25-22.4 43.7-49.7 54.5-79.5h7c19 0 34.5-15.5 34.5-34.5v-55.6c-.1-19.4-15.6-34.9-34.6-34.9zm10.5 90.1c0 5.8-4.7 10.5-10.5 10.5h-15.6c-5.3 0-9.9 3.4-11.5 8.5-8.9 29-26.5 55.8-50.8 77.5l-.2.2-.1.1-2.7 2.4c-.1.1-.2.2-.3.2-26.2 23.8-28.9 51.8-29.1 59.8v18.7c0 .1-.1.2-.2.2h-74.5c-.1 0-.2-.1-.2-.2v-17.5c0-3.4-1.4-6.6-4-8.9-2.2-2-5.1-3.1-8-3.1-.4 0-.8 0-1.3.1-8.2.9-16.5 1.3-24.7 1.3-10.5 0-21.1-.7-31.4-2.1-3.4-.5-6.9.6-9.5 2.9s-4.1 5.6-4.1 9v18.3c0 .1-.1.2-.2.2H107c-.1 0-.2-.1-.2-.2v-6.1c-.6-13.4-4.8-47.4-31.4-74.7-12.7-13.1-24.1-30.3-33.8-51.2-.1-.2-.1-.3-.2-.4-5.7-11.2-10.2-22.9-13.1-34.9 0-.1-.1-.2-.1-.3v-.2c-2.9-11.9-4.3-24.1-4.3-36.2 0-97 91-176 202.8-176 23.9 0 47.2 3.6 69.4 10.6 5.1 1.6 10.6-.3 13.6-4.7 8.9-13.2 26.6-29.7 60.6-34 .8-.1 1.3.4 1.5.8.4.7.1 1.3-.1 1.5-13.3 21.2-17.2 43.2-11.4 63.6 0 .1.1.3.1.4.1 3.5 1.7 6.8 4.5 9 27.2 22 46.9 49.9 57 80.7 1.6 4.9 6.2 8.3 11.4 8.3h16.5c5.8 0 10.5 4.7 10.5 10.5v55.4h.1z"></path><path d="M239.8 230H213c-9.6 0-17.4-7.8-17.4-17.4s7.8-17.4 17.4-17.4h47.4c6.6 0 12-5.4 12-12s-5.4-12-12-12h-21.9v-16.1c0-6.6-5.4-12-12-12s-12 5.4-12 12v16.1h-1.4c-22.8 0-41.4 18.6-41.4 41.4s18.6 41.4 41.4 41.4h26.8c9.6 0 17.4 7.8 17.4 17.4s-7.8 17.4-17.4 17.4h-48.2c-6.6 0-12 5.4-12 12s5.4 12 12 12h22.8v16.4c0 6.6 5.4 12 12 12s12-5.4 12-12v-16.4h2c22.6-.3 40.9-18.8 40.9-41.4-.2-22.8-18.8-41.4-41.6-41.4z"></path></svg>&nbsp;&nbsp;Buy in bulk and save!</div></th>
	 					</tr>
	 					<?php
	 					foreach ( $product->get_attributes() as $attribute_name => $options ) {

	 						if ($attribute_name == 'pa_monstasize') {
	 							$ip = null;
	 							$api_key = get_option( 'trophymonsta_api_key' );
	 							$response = Trophymonsta::http_get( Trophymonsta::build_query( array( 'key' => $api_key, 'site_url' => site_url() ) ), 'store/checkProductDiscount', $ip );
	 							$response	= json_decode($response);

	 							if ($response->code == '400' || $response->code == '403' || $response->code == '404' || $response->code == '405') {
	 								return;
	 							}

	 							if ($response->code == '200') {
	 								$mmdiscounts =  $response->success->product_discount;
									$flat_discount = $response->success->flat_discount;
	 							}
								if (0 >= $flat_discount) {
								?>
								<tr>
									<th><?php echo ucfirst(wc_attribute_label( $attribute_name )); ?>:</th>
									<th><?php echo 1; ?>+</th>
									<?php foreach ($mmdiscounts as $key => $discount) { ?>
											<th><?php echo $discount->unit; ?>+</th>
									<?php	} ?>
								</tr>

								<?php $mm_price_key = 0;
									foreach($options['options'] as $att_term) {
										if (isset($product->get_available_variations()[$mm_price_key]['attributes']['attribute_pa_monstasize'])) {
								$att_term_object = get_term_by('slug', $product->get_available_variations()[$mm_price_key]['attributes']['attribute_pa_monstasize'], 'pa_monstasize' );
									?>
									<tr>
										<td><?php echo $att_term_object->name; ?></td>
										<td><?php	echo '$'. $product->get_available_variations()[$mm_price_key]['display_price']; ?></td>
										<?php	foreach ($mmdiscounts as $key => $discount) { ?>
													<td><?php
														echo '$'.$price = round(  ($product->get_available_variations()[$mm_price_key]['display_price'] - ($product->get_available_variations()[$mm_price_key]['display_price'] * ($discount->precent / 100))), 2 );
													 ?></td>
											<?php	} $mm_price_key++; ?>
									</tr>
									<?php
										}
									}
								} else {
									?>
									<tr>
										<th>Flat Discount:</th>
										<th><?php echo $flat_discount; ?></th>
									</tr>
									<?php
								}
	 							
	 						}
	 					}
	 				?>
	 				</tbody>
	 			</table>
	 	<?php
	 	}
  }

	public static function monstamanagement_cart_display_bulk_price( $item_data, $cart_item ) {
	    //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_cart_display_bulk_price'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		global $wpdb;
		$monstavariants = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE `attribute_name` like 'monsta%' and `attribute_name` not in ('monstasize' , 'monstamaterial', 'monstaprocess')");


		foreach($monstavariants as $k => $variation) {
			$variation_name = $variation->attribute_name;
			if( isset($cart_item[ 'attribute_pa_'.$variation_name ]) && $cart_item[ 'attribute_pa_'.$variation_name ] != '') {
				$term = get_term_by('slug', $cart_item['attribute_pa_'.$variation_name], 'pa_'.$variation_name );
				$cart_item['variation']['attribute_pa_'.$variation_name] = esc_html( $term->name);
				if( null != $term ){
					$item_data[] = array(
						'key'     => __( $variation->attribute_label, 'trophymonsta' ),
						'value'   => esc_html( $term->name),
						'display' => '',
					);

				}
			}
		}


		$ip = null;
		$api_key = get_option( 'trophymonsta_api_key' );
		$response = Trophymonsta::http_get( Trophymonsta::build_query( array( 'key' => $api_key, 'site_url' => site_url() ) ), 'store/checkProductDiscount', $ip );
		$response	= json_decode($response);

		if ($response->code == '400' || $response->code == '403' || $response->code == '404' || $response->code == '405') {
			return $item_data;
		}

		if ($response->code == '200') {
			$mmdiscounts =  $response->success->product_discount;
			$mmdiscounts_price = array();

				$product_id = $cart_item['product_id'];
				$discount_unit = 0;
				$discount_precent = 0;

				$custompostmeta = get_post_meta( $product_id, '_trophymonsta_text_field', true );
				if ($custompostmeta == 'trophymonsta') {
					foreach ($mmdiscounts as $key => $discount) {
						if ($cart_item['quantity'] > $discount->unit) {
							$discount_unit = $discount->unit;
							$discount_precent = $discount->precent;
						}
					}

					if ($discount_unit != 0) {
						$item_data[] = array(
							'key'     => __( 'Bulk Buy Savings', 'trophymonsta' ),
							'value'   => wc_clean( $discount_precent.'%' ),
							'display' => '',
						);
						$item_data[] = array(
							'key'     => __( 'Original Price', 'trophymonsta' ),
							'value'   => wc_clean(get_post_meta($cart_item['variation_id'], '_price', true)),
							'display' => '',
						);
					}
				}
		}
		return $item_data;
	}

	/**
	 * Add the text field as item data to the cart object
	 * @since 1.0.0
	 * @param Array $cart_item_data Cart item meta data.
	 * @param Integer $product_id Product ID.
	 * @param Integer $variation_id Variation ID.
	 * @param Boolean $quantity Quantity
	 */
	public static function monstamanagement_add_custom_field_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {
	    // error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_add_custom_field_item_data'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		$custompostmeta = get_post_meta($product_id, '_trophymonsta_text_field', true );
		if ($custompostmeta == 'trophymonsta') {
			global $wpdb;
			$monstavariants = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE `attribute_name` like 'monsta%' and `attribute_name` not in ('monstasize' , 'monstamaterial', 'monstaprocess')");
			foreach($monstavariants as $k => $variation){
				$variation_name = 'attribute_pa_'.$variation->attribute_name;
				if( isset( $_POST[ $variation_name ] ) ){
					$cart_item_data[ $variation_name ] = $_POST[ $variation_name ];
				}
			}
		}

	 return $cart_item_data;
	}

	/**
	 * Display the custom field value in the cart
	 * @since 1.0.0
	 */
	public static function monstamanagement_cart_item_name( $name, $cart_item, $cart_item_key ) {
        //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_cart_item_name'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		$custompostmeta = get_post_meta($cart_item['product_id'], '_trophymonsta_text_field', true );
		if ($custompostmeta == 'trophymonsta') {
			if($name != strip_tags($name)) {
				preg_match_all('~<a(.*?)href="([^"]+)"(.*?)>~', $name, $matches);
				if (isset($matches[2][0]))
					$name = sprintf( '<a href="%s">%s</a>',$matches[2][0],get_post_meta($cart_item['variation_id'], '_trophymonsta_name', true ) );
				else
					$name = get_post_meta($cart_item['variation_id'], '_trophymonsta_name', true );
			} else {
				$name = get_post_meta($cart_item['variation_id'], '_trophymonsta_name', true );
			}
	 }
	 return $name;

	}

	/**
 	* Add order item meta
 	*/
	public static function monstamanagement_checkout_create_order_line_item ( $item, $cart_item_key, $values, $order ) {
	    
	    $ip 		= null;
		$api_key 	= get_option( 'trophymonsta_api_key' );
		$response 	= Trophymonsta::http_get( Trophymonsta::build_query( array( 'key' => $api_key, 'site_url' => site_url() ) ), 'store/getWeborderEmail', $ip );
		$response	= json_decode($response);
		//error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_checkout_create_order_line_item'.print_r($response, true).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		if ($response->code == '200') {
		    //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_checkout_create_order_line_item ----'.$response->success->logo.'>>>'.$response->success->contact_details.'>>>'.$response->success->message.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
	        
	        $header_image = get_option( 'trophy_email_header_image' );
    		if ($header_image !== null) {
    			update_option( 'trophy_email_header_image', $response->success->logo);
    		} else {
    			add_option( 'trophy_email_header_image', $response->success->logo);
    		}
    		
    		
    		$header_phone = get_option( 'trophy_email_header_phone' );
    		if ($header_phone !== null) {
    			update_option( 'trophy_email_header_phone', $response->success->contact_details);
    		} else {
    			add_option( 'trophy_email_header_phone', $response->success->contact_details);
    		}
    		
    		$header_message = get_option( 'trophy_email_header_message' );
    		if ($header_message !== null) {
    			update_option( 'trophy_email_header_message', $response->success->message);
    		} else {
    			add_option( 'trophy_email_header_message', $response->success->message);
    		}
    		
    		//error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_checkout_create_order_line_item ----'.$header_image.'>>>'.$header_phone.'>>>'.$header_message.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		}
		
		
		//error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_checkout_create_order_line_item'.get_option( 'trophy_email_header_image' ).'>>>'.get_option( 'trophy_email_header_phone' ).'>>>'.get_option( 'trophy_email_header_message' ).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
	     //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_checkout_create_order_line_item'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		global $wpdb;
		$monstavariants = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE `attribute_name` like 'monsta%' and `attribute_name` not in ('monstasize', 'monstamaterial', 'monstaprocess')");
		foreach ( $monstavariants as $variant ) {
			$attribute_name = 'attribute_pa_'.$variant->attribute_name;
			if( isset( $values[$attribute_name] ) && $values[$attribute_name] != '' ) {
				$item->update_meta_data( $attribute_name, $values[$attribute_name] );
			}
		}

		$price = $values['data']->get_changes();
		if( count( $price ) > 0 && isset($price['price']) && $price['price'] != '') {
			$item->update_meta_data( 'Price', sanitize_text_field($price['price']));
		} else if( isset( $values['line_subtotal'] ) ) {
			$price_cal = $values['line_subtotal'] / (int)$values['quantity'];
			$price_cal = number_format($price_cal, 2, '.', "");
			$item->update_meta_data('Price', sanitize_text_field($price_cal) );
		}

		if( isset($values[ 'line_subtotal' ])  && $values[ 'line_subtotal' ] != '' ){
			$item->update_meta_data ('Line Subtotal', sanitize_text_field($values['line_subtotal']));
		}
		if( isset( $_SESSION['engraving_setting_forgo_logo'] ) && $_SESSION['engraving_setting_forgo_logo'] != ''  ){
			$order->update_meta_data ('Logo_content', "Forgo adding a logo, I don't need it" );
		}
		if( isset( $_SESSION['engraving_setting_existing_logo'] ) && $_SESSION['engraving_setting_existing_logo'] != ''  ){
			$order->update_meta_data ('Logo_content', "Use an existing logo, I am already a customer");
		}
		if( isset( $_SESSION['engraving_setting_uploaded_id'] ) && $_SESSION['engraving_setting_uploaded_id'] != ''  ){
			$order->update_meta_data ('Logo_content',  '' );
			$order->update_meta_data ('engraving_logo_user_id',  $_SESSION['engraving_setting_uploaded_id'] );
		}
		if( isset( $_SESSION[ 'enter_engraving_details_email' ]  ) && $_SESSION[ 'enter_engraving_details_email' ]  != '' ){
			$order->update_meta_data ('engraving_by_email','1');
		}
		if( isset( $_SESSION[ 'no_engraving_details' ]  ) && $_SESSION[ 'no_engraving_details' ]  != '' ){
			$order->update_meta_data ('forget_engraving','1');
		}
		//error_log(date('Y-m-d H:i:s').' Monstafrontend engraving_setting_presentation_date :'.$_SESSION[ 'engraving_setting_presentation_date' ].PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		if( isset( $_SESSION[ 'engraving_setting_presentation_date' ]  ) && $_SESSION[ 'engraving_setting_presentation_date' ]  != '' ){
			$order->update_meta_data ('engraving_setting_presentation_date', date( 'd/m/Y',strtotime($_SESSION[ 'engraving_setting_presentation_date' ]) ));
		}
		//error_log(date('Y-m-d H:i:s').' Monstafrontend engraving_setting_customer_date :'.$_SESSION[ 'engraving_setting_customer_date' ].PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		if( isset( $_SESSION[ 'engraving_setting_customer_date' ]  ) && $_SESSION[ 'engraving_setting_customer_date' ]  != '' ){
			$order->update_meta_data ('engraving_setting_customer_date', date( 'd/m/Y',strtotime($_SESSION[ 'engraving_setting_customer_date' ]) ));
		}
		
		$order->save();

	}

	public static function monstamanagement_add_images_woocommerce_emails ( $output, $order) {
	    //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_add_images_woocommerce_emails'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		// set a flag so we don't recursively call this filter
		static $run = 0;

		// if we've already run this filter, bail out
		if ( $run ) {
			return $output;
		}

		$args = array(
			'show_image'   	=> true,
			'image_size'    => array( 300, 300 ),
			'show_sku'		=> true,
		);

		// increment our flag so we don't run again
		$run++;

		// if first run, give WooComm our updated table
		return $order->email_order_items_table( $args );

	}
	public static function monstamanagement_order_item_visible( $items ){
	    //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_order_item_visible'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		return false;
	}
	public static function monstamanagement_email_product_listing( $order ){
	     //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_email_product_listing'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		$currency_symbol = get_woocommerce_currency_symbol();
		$items  = $order->get_items();
		$text_align  = is_rtl() ? 'right' : 'left';
		$margin_side = is_rtl() ? 'left' : 'right';
		$image_size = array( 300, 300 );
		foreach ( $items as $item_id => $item ) :
			$product       = $item->get_product();
			$sku           = '';
			$purchase_note = '';
			$image         = '';
			if ( is_object( $product ) ) {
				$sku           = $product->get_sku();
				$purchase_note = $product->get_purchase_note();
				$image         = $product->get_image( $image_size );
				if( $sku != '' ){
					$sku_array = explode( "_", $sku );
					$sku = array_pop($sku_array);
				}
			}
	?>
	<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $order ) ); ?>">
		<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
		<?php
			$image = str_replace("width=\"1\"","width=\"100\"",$image);
            $image = str_replace("height=\"1\"","height=\"100\"",$image);
			echo wp_kses_post( apply_filters( 'woocommerce_order_item_thumbnail', $image, $item ) );
			// Product name.
			echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ) );
			// SKU.
			echo wp_kses_post( ' (#' . $sku . ')' );
			// allow other plugins to add additional product information here.
			do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, false );
			/*wc_display_item_meta(
				$item,
				array(
					'label_before' => '<strong class="wc-item-meta-label" style="float: ' . esc_attr( $text_align ) . '; margin-' . esc_attr( $margin_side ) . ': .25em; clear: both">',
				)
			);*/
			$meta_data = $item->get_formatted_meta_data();
			$price = '';
			if( count( $meta_data ) > 0 ){
			?> <ul class="wc-item-meta">
			<?php
				foreach ( $meta_data as $meta_id => $meta ) {
					if(  $meta->display_key == 'Price' ){
						$price = $meta->value;
					}
					if( $meta->display_key == 'Price' ||  $meta->display_key == 'Line Subtotal' ){
						continue;
					}
			?>
				   <li>
					<?php
					$display_val =  $meta->display_value;
					$product_code = '';
					$product_image = '';
					if( $meta->display_key != 'Size' ){
					?>
					<table cellpadding="0" cellspacing="0" border="0" width="100%">
						<tr>
							<td style="padding:0">
								<strong class="wc-item-meta-label">
									<?php echo $meta->display_key;  ?> :
								</strong>
							</td>
						</tr>
						<tr>
					<?php
						if( $meta->key == 'attribute_pa_monstaengraving' && $meta->value != ''){
							$termdata = get_term_by('slug', $meta->value, 'pa_monstaengraving' );
							if( isset( $termdata->term_id )  ){
								$product_code = get_term_meta( $termdata->term_id, 'components_code', true);
								$product_image = get_term_meta( $termdata->term_id, 'components_image', true);
								if( $product_code != '' ){
									$product_code = "(".$product_code.")";
								}
							}
						}else{
							$taxonomy_array = explode( '_',$meta->key ) ;
							$taxonomy = array_pop( $taxonomy_array );
							$termdata = get_term_by('slug', $meta->value, 'pa_'.$taxonomy );
							if( isset( $termdata->term_id )  ){
								$product_code 	= get_term_meta( $termdata->term_id, 'components_code', true);
								$product_image 	= get_term_meta( $termdata->term_id, 'components_image', true);
								if( $product_code != '' ){
									$product_code = "(".$product_code.")";
								}
							}
						}
						if( $product_image != '' ){
							echo  '<td style="padding:0"><img src="'.$product_image.'" width="75" height="75" style="border:0"></td>';
						}
						echo '<td style="padding:0">'. $display_val.''.$product_code .'</td>';
					}else {
						?>
						<table cellpadding="0" cellspacing="0" border="0" width="100%">
						<tr>
							<td colspan="2" style="padding:0">
								<strong class="wc-item-meta-label">
									<?php echo $meta->display_key;  ?> :
								</strong>
							</td>
						</tr>
						<tr>
						<?php
						if( $product_image != '' ){
							echo  '<td style="padding:0"><img src="'.$product_image.'" width="75" height="75" style="border:0"></td>';
						}
						echo '<td style="padding:0">'.$display_val.'</td>';
					}

					?>
					</tr>
				 </table>
				 </li>
			<?php
				}
			?>
			</ul>
			<?php
			}

			// allow other plugins to add additional product information here.
			do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, false );

		?>
		</td>
		<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
			<?php
			echo $currency_symbol.''.$price;
			//echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) );
			?>
		</td>
		<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">
			<?php
			$qty          = $item->get_quantity();
			$refunded_qty = $order->get_qty_refunded_for_item( $item_id );

			if ( $refunded_qty ) {
				$qty_display = '<del>' . esc_html( $qty ) . '</del> <ins>' . esc_html( $qty - ( $refunded_qty * -1 ) ) . '</ins>';
			} else {
				$qty_display = esc_html( $qty );
			}

			echo wp_kses_post( apply_filters( 'woocommerce_email_order_item_quantity', $qty_display, $item ) );
			?>
		</td>
		<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align:middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" >
		 <?php
			$qty      = $item->get_quantity();
			//$price    = isset( $item->get_data()[ 'subtotal' ] ) ? $item->get_data()[ 'subtotal' ] : 0 ;
			$total	  = $qty * $price;

			echo $currency_symbol.''.number_format($total, 2, '.', "");
		 ?>
		</td>

	</tr>
	<?php endforeach;

	}

	public static function monstamanagement_customer_details( $order, $sent_to_admin, $plain_text, $email ){
	     //error_log(date('Y-m-d H:i:s').' Monstafrontend monstamanagement_customer_details'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		$fields = array();
		if ( $order->get_customer_note() ) {
			$fields[]  =  array( 'label' => 'Note', 'value' => nl2br(  $order->get_customer_note()  ) ) ;
		}else{
			$fields[]  =  array( 'label' => 'Note', 'value' => 'N/A' ) ;
		}
		if ( $order->get_billing_email() ) {
			$fields[]  =  array( 'label' => 'Email', 'value' =>   $order->get_billing_email()  ) ;
		}else{
			$fields[]  =  array( 'label' => 'Email', 'value' => 'N/A' ) ;
		}
		if ( $order->get_billing_phone() ) {
			$fields[]  =  array( 'label' => 'Tel', 'value' =>   $order->get_billing_phone()  ) ;
		}else{
			$fields[]  =  array( 'label' => 'Tel', 'value' => 'N/A' ) ;
		}
		//echo "<pre>";print_r($order);echo "</pre>";
		if ( ! empty( $fields ) ) :
			?> <div style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;">
				<h2><?php esc_html_e( 'Customer details', 'woocommerce' ); ?></h2>
				<ul style="list-style-type: none;">
					<?php foreach ( $fields as $field ) : ?>
						<li style="list-style-type:none"><strong><?php echo wp_kses_post( $field['label'] ); ?>:</strong> <span class="text"><?php echo wp_kses_post( $field['value'] ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			</div>
	 <?php endif;
	}
	public static function monstamanagement_email_engraving_details(  $order, $sent_to_admin, $plain_text, $email  ){
	    $engraving_details = apply_filters( 'trophy_email_generate_excel', $order, $sent_to_admin, $plain_text );
		$presentationdate = isset( $_SESSION[ 'engraving_setting_presentation_date' ] ) ?  date( 'd/m/Y',strtotime($_SESSION[ 'engraving_setting_presentation_date' ]) ) : get_post_meta($order->get_id(), 'engraving_setting_presentation_date',true);
		$deliverydate = isset( $_SESSION[ 'engraving_setting_customer_date' ] ) ? date('d/m/Y',strtotime($_SESSION[ 'engraving_setting_customer_date' ] ) ) : get_post_meta($order->get_id(), 'engraving_setting_customer_date',true);
		$image_content = $order->get_meta('Logo_content');
		if ( $image_content == ''  && $engraving_details[ 'trophy_logo_attacment' ] != '' ) {
			$image_content = $engraving_details[ 'trophy_logo_attacment' ];
		} else if( $image_content == '' ) {
			$image_content = 'N/A';
		}
		?>
			<div style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;">
				<ul style="list-style-type: none;">
					<?php if( isset( $engraving_details[ 'trophy_excel_attacment' ] )  && $engraving_details[ 'trophy_excel_attacment' ] != '' ){ ?>
						<li> <strong>Engraving Details: </strong> Engraving details entered online. <?php echo $engraving_details[ 'trophy_excel_attacment' ] ?> </li>
					<?php }else{ ?>
						<li> <strong>Engraving Details: </strong>N/A</li>
					<?php } ?>
						<li> <strong>Club/Company Logo: </strong> <?php echo $image_content ?> </li>
					<?php if( isset( $deliverydate )  && $deliverydate != '' ){?>
						<li> <strong>Due Date:</strong> <?php echo $deliverydate; ?> </li>
					<?php }else{ ?>
						<li> <strong>Due Date:</strong>N/A</li>
					<?php } ?>
					<?php if( isset( $presentationdate )  && $presentationdate != '' ){ ?>
						<li> <strong>Presentation Date:</strong> <?php echo $presentationdate; ?> </li>
					<?php }else{ ?>
						<li> <strong>Presentation Date:</strong>N/A</li>
					<?php } ?>
				</ul>
			</div>
		<?php
	}
	public static function monstamanagement_email_footer(){
	    wc_get_template( 'emails/custom-email-footer.php' ,array(), '', TROPHYMONSTA_PLUGIN_DIR.'templates/');
	}
	public static function monstamanagement_email_header(){
		wc_get_template( 'emails/custom-email-header.php' ,array(), '', TROPHYMONSTA_PLUGIN_DIR.'templates/');
	}
	public static function monstamanagement_woocommerce_email_classes( $emails ){
	    if (  class_exists( 'Trophy_Custom_Email_Order' ) ) {
			$emails['Trophy_Custom_Email_Order'] = new Trophy_Custom_Email_Order();
		}
		if (  class_exists( 'Trophy_Custom_Admin_Email_Order' ) ) {
			$emails['Trophy_Custom_Admin_Email_Order'] = new Trophy_Custom_Admin_Email_Order();
		}
		return $emails;
	}

	public static function monstamanagement_change_price_html( $price, $trophy_product ){
	    return '<span  class="monsta_price_value" > '.$price.' </span>';
	}

	public static function attach_engraving_xls_to_email ( $order, $sent_to_admin, $plain_text ) {

		$trophy_xls_attachment = $order->get_id().'.xls';
		$wordpress_upload_dir = wp_upload_dir();
		$engraving_details = array();
		$engraving_details[ 'trophy_excel_attacment' ] = '';
		$engraving_details[ 'trophy_logo_attacment' ] = '';
		if( file_exists( $wordpress_upload_dir['path'].'/'.$trophy_xls_attachment ) ){
			$trophy_excel_attacment = $wordpress_upload_dir['url'].'/'.$trophy_xls_attachment;
			$engraving_details[ 'trophy_excel_attacment' ] = '<p><a href="'.$trophy_excel_attacment.'" >File Provided.</a></p>';
		}

		$trophy_logo_attacment = '';
		if( isset( $_SESSION['engraving_setting_existing_logo'] ) && $_SESSION['engraving_setting_existing_logo'] != ''  ){
			$trophy_logo_attacment = $_SESSION['engraving_setting_existing_logo'];
		}
		if( trim($trophy_logo_attacment) == '' && isset( $_SESSION[ 'engraving_setting_uploaded_id' ] ) && $_SESSION[ 'engraving_setting_uploaded_id' ] != '' ){
			
			$attachment_id = explode(',',$_SESSION[ 'engraving_setting_uploaded_id' ]);
			foreach($attachment_id as $k => $postid){
				$trophy_logo_attacment = wp_get_attachment_url( $postid );
				
				$engraving_details[ 'trophy_logo_attacment' ] .= '<a href="'.$trophy_logo_attacment.'" download >Logo</a> ';
			}
			
			
		}
		
		return $engraving_details;
	}
	
	public static function custom_export_pending_order_data( $order, $data_store ) {
    	error_log(date('Y-m-d H:i:s').' Monstafrontend custom_export_pending_order_data'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
	    error_log(date('Y-m-d H:i:s').' Monstafrontend custom_export_pending_order_data '.$_SESSION['session_trophy_product_details'].PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		error_log(date('Y-m-d H:i:s').' Monstafrontend custom_export_pending_order_data product '.print_r($_SESSION['session_trophy_product_details']['product'],true).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		
		
		
	    if(!get_post_meta($order->get_id(), '_monsta_engravings_xls',true) && isset($_SESSION['session_trophy_product_details']) && !empty($_SESSION['session_trophy_product_details'])) {
	        error_log(date('Y-m-d H:i:s').' Monstafrontend FIRST IF'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
	        
			if(isset($_SESSION['session_trophy_product_details']['product']) && !empty($_SESSION['session_trophy_product_details']['product'])) {
			    
			    error_log(date('Y-m-d H:i:s').' Monstafrontend SECOND IF'.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
			    
				$wordpress_upload_dir = wp_upload_dir();
				require_once(__DIR__ .'/lib/vendor/autoload.php');
				$objPHPExcel 	= new \PhpOffice\PhpSpreadsheet\Spreadsheet();

				//$filename	=	$order->id.'.xls';
				$filename	=	$order->get_id().'.xls';

				$cells	=	array(1=>"A",2=>"B",3=>"C",4=>"D",5=>"E",6=>"F",7=>"G",8=>"H",9=>"I",10=>"J",11=>"K",12=>"L",13=>"M",14=>"N",15=>"O",16=>"P",17=>"Q",18=>"R",19=>"S",20=>"T",21=>"U",22=>"V",23=>"W",24=>"X",25=>"Y",26=>"Z");

				$sheet	=	$objPHPExcel->getActiveSheet();
				$sheet->setTitle('Engraving Details');
				$sheet->setCellValue("A1","Product Code");
				$sheet1	=	$objPHPExcel->createSheet();
				$sheet1->setTitle('CSV Info');
				for($i=1; $i<=20; $i++) {
					$j = $i+1;
					$sheet->setCellValue($cells[$j].'1',"Line ".$i);
				}

				$k = 2;
				$x = 1;
				foreach($_SESSION['session_trophy_product_details']['product'] as $prodKey => $prodVal) {
					$getsku_array	=	explode('##',$prodKey);
					$getsku = isset( $getsku_array[0] ) ? $getsku_array[0] : '';
					if( $getsku != '' ){
						$prodArr	=	explode('_',$getsku);
						$sheet1->setCellValue("A".$x,$prodArr[1]);
					}else{
						$sheet1->setCellValue("A".$x,'');
					}
					$x++;
					foreach($prodVal as $prKey => $prVal) {
						$engraving	=	array();
						$sheet->setCellValue("A".$k,$prodArr[1]);
						for($i=0; $i<20; $i++) {
							if(isset($prVal[$i]) && $prVal[$i] != '' && $prVal[$i] == 'on') {
								$sheet->setCellValue($cells[$i+2].$k,"LOGO");
								$engraving[] =	'LOGO';
							} else if(isset($prVal[$i]) && $prVal[$i] != '') {
								$sheet->setCellValue($cells[$i+2].$k,$prVal[$i]);
								$engraving[] =	$prVal[$i];
							} else {
								$sheet->setCellValue($cells[$i+2].$k,"");
							}
						}
						$k++;
						$sheet1->setCellValue("A".$x, join( ', ', $engraving ) );
						$x++;
					}
					$k++;
				}
				$objWriter	=	new \PhpOffice\PhpSpreadsheet\Writer\Xls($objPHPExcel);
				error_log(date('Y-m-d H:i:s').' Monstafrontend THIRD '.$wordpress_upload_dir['path'] .'/'.$filename.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
				$objWriter->save($wordpress_upload_dir['path'] .'/'.$filename);
				update_post_meta($order->get_id(), '_monsta_engravings_xls',$filename);
			}
		}
		// END: Worked for engraving details xls on 04-01-2020
    }

}
