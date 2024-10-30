<?php

class Monstaengravings {

	private static $initiated = false;
	const DEFAULT_LINES = 4;
	const DEFAULT_CHAR = 30;

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
		add_filter( 'template_include', array( 'Monstaengravings', 'monsta_engravings_template' ));
	}

	public static function monsta_woo_custom_checkout_button_text() {
	  ?>
	       <a href="<?php echo get_site_url(); ?>/monsta-engravings-settings" class="checkout-button button alt wc-forward"><?php  _e( 'Check On Out', 'woocommerce' ); ?></a>
	  <?php
	}

	public static function monstamanagement_engravings_settings() {
	    //error_log(date('Y-m-d H:i:s').' Monstaengravings monstamanagement_engravings_settings '.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		global $wpdb;
		//$redirectTo = '';
		$newlogo = false;
		$existinglogo = false;
		$forgetLogo = false;
		$hide_upload = '';
		$show_preview = false;
		$existinglogo_attachment = array();
		
		$engraving_online = 'Enter my engraving details online';
		$engraving_online_desc = 'Select this option to enter your engraving details in checkout.';
		$engraving_later = 'Email engraving details later';
		$engraving_later_desc = 'You will be advised on the email address to send the details to. Use the order number as reference.';
		$engraving_forgo = 'Forgo any engraving, I don\'t need it';
		$engraving_forgo_desc = 'There is no engraving required for your entire order.';
		$upload_logo = 'Upload a company logo for my engraving';
		$use_existing_logo = 'Use an existing logo, I am already a customer';
		$forgo_logo = 'Forgo adding a logo, I don\'t need it';
		
		$attachment_id = '';
		$_SESSION[ 'engraving_completion_status' ] = 0;
		Trophy_Custom_Email_Order::$mail_count = 0;
		Trophy_Custom_Admin_Email_Order::$mail_count = 0;
		if ( isset( $_SESSION[ 'engraving_step' ] ) == false ) {
			$_SESSION[ 'engraving_step' ] = 1;
		}
		$customer_date = $presentation_date  =  '';
		$company_logo_attacment = array();
		if ( isset( $_SESSION[ 'engraving_setting_customer_date' ] ) ) {
			$customer_date = $_SESSION[ 'engraving_setting_customer_date' ];
		}

		if ( isset( $_SESSION[ 'engraving_setting_presentation_date' ] ) ) {
			$presentation_date = $_SESSION[ 'engraving_setting_presentation_date' ];
		}

		if ( isset( $_SESSION[ 'engraving_setting_uploaded_id' ] ) &&  $_SESSION[ 'engraving_setting_uploaded_id' ] != '' ) {
			$attachment_id = $_SESSION[ 'engraving_setting_uploaded_id' ];
			$attachment_id_arr = explode( ',', $attachment_id );
			foreach($attachment_id_arr as $k=>$id){
				$company_logo_attacment[] = wp_get_attachment_url( $id );
			}
			$newlogo = true;
		}
		//echo "<pre>";print_r($_SESSION[ 'engraving_setting_uploaded_id' ]);echo "</pre>";
		if ( isset($_SESSION[ 'engraving_setting_existing_logo' ]) && $_SESSION[ 'engraving_setting_existing_logo' ] != '' ) {
			$existinglogo = true;
			$newlogo = $forgetLogo = false;
		}
		if ( isset($_SESSION[ 'engraving_setting_forgo_logo' ]) && $_SESSION[ 'engraving_setting_forgo_logo' ] != '' ) {
			$forgetLogo = true;
			$existinglogo = $newlogo = false;
		}
		//if ( !is_user_logged_in() ) {
			//$redirectTo = wp_login_url().'?redirect_to=monsta-engravings-settings';
		//}

		/*if (  is_user_logged_in()  ) {
			$userid = get_current_user_id();
			$get_latest_user_posts = $wpdb->get_results( "SELECT `ID` FROM ".$wpdb->prefix."posts WHERE `post_author` = ".$userid." and post_parent = 0 and post_type = 'attachment' ORDER by `ID` ASC " );
			if ( count($get_latest_user_posts) > 0 ) {
				foreach($get_latest_user_posts as $k=> $posts){
					$existinglogo_attachment[] = wp_get_attachment_url( $posts->ID );
				}
			}
		}*/
		//Download Sample .xls file
		$ip 		= null;
		$logo_fee 	= 0.00;
		$logo_price_array = array();
		$api_key 	= get_option( 'trophymonsta_api_key' );
		$response 	= Trophymonsta::http_get( Trophymonsta::build_query( array( 'key' => $api_key, 'site_url' => site_url() ) ), 'store/getLogoFee', $ip );
		$response	= json_decode($response);
		$engraving_xls = TROPHYMONSTA_PLUGIN_URL.'engravings_template.xlsx';
		if ($response->code == '200') {
			if (isset($response->success->engraving_xls) && $response->success->engraving_xls != '') {
				$engraving_xls =  $response->success->engraving_xls;
			}
			if (isset($response->success->logo_price)) {
				$logo_price_array = $response->success->logo_price;
			}
			if (isset($response->success->dynamic_text->engraving_online) && $response->success->dynamic_text->engraving_online != '') {
				$engraving_online =  $response->success->dynamic_text->engraving_online;
			}
			
			if (isset($response->success->dynamic_text->engraving_online_desc) && $response->success->dynamic_text->engraving_online_desc != '') {
				$engraving_online_desc =  $response->success->dynamic_text->engraving_online_desc;
			}
			
			if (isset($response->success->dynamic_text->engraving_later) && $response->success->dynamic_text->engraving_later != '') {
				$engraving_later =  $response->success->dynamic_text->engraving_later;
			}
			
			if (isset($response->success->dynamic_text->engraving_later_desc) && $response->success->dynamic_text->engraving_later_desc != '') {
				$engraving_later_desc =  $response->success->dynamic_text->engraving_later_desc;
			}
			
			if (isset($response->success->dynamic_text->engraving_forgo) && $response->success->dynamic_text->engraving_forgo != '') {
				$engraving_forgo =  $response->success->dynamic_text->engraving_forgo;
			}
			
			if (isset($response->success->dynamic_text->engraving_forgo_desc) && $response->success->dynamic_text->engraving_forgo_desc != '') {
				$engraving_forgo_desc =  $response->success->dynamic_text->engraving_forgo_desc;
			}
			
			if (isset($response->success->dynamic_text->upload_logo) && $response->success->dynamic_text->upload_logo != '') {
				$upload_logo =  $response->success->dynamic_text->upload_logo;
			}
			
			if (isset($response->success->dynamic_text->use_existing_logo) && $response->success->dynamic_text->use_existing_logo != '') {
				$use_existing_logo =  $response->success->dynamic_text->use_existing_logo;
			}
			
			if (isset($response->success->dynamic_text->forgo_logo) && $response->success->dynamic_text->forgo_logo != '') {
				$forgo_logo =  $response->success->dynamic_text->forgo_logo;
			}
			
			if (isset($response->success->dynamic_text->engraving_instruction) && $response->success->dynamic_text->engraving_instruction != '') {
				$_SESSION[ 'engraving_instruction' ] =  $response->success->dynamic_text->engraving_instruction;
			} else {
				$_SESSION[ 'engraving_instruction' ] = '';
			}
			
			$logo_fee =  $response->success->logo_fee;
			foreach ($response->success->logo_price as $logo_price) {
				if ($logo_price->price_level == 1 && $logo_price->price > 0) {
					$logo_fee =  $logo_price->price;
				}
			}
		}
		//tab -1
		if (is_page( 'monsta-engravings-settings' ) ) {
			Trophymonsta::view( 'engraving-settings',compact( 'company_logo_attacment','existinglogo','customer_date','presentation_date','existinglogo_attachment','forgetLogo','newlogo','attachment_id','engraving_xls', 'logo_fee', 'engraving_online',  'engraving_online_desc', 'engraving_later', 'engraving_later_desc', 'engraving_forgo', 'engraving_forgo_desc', 'upload_logo', 'use_existing_logo', 'forgo_logo', 'logo_price_array') ); //'show_preview','redirectTo'
		}
	}

	public static function monstamanagement_engravings_details() {
	    //error_log(date('Y-m-d H:i:s').' Monstaengravings monstamanagement_engravings_details '.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		global $wpdb;
		
		$engraving_instruction = '';
		if (isset($_SESSION[ 'engraving_instruction' ]) && $_SESSION[ 'engraving_instruction' ] !== '') {
			$engraving_instruction = $_SESSION[ 'engraving_instruction' ];
		}
		$step1_url = get_permalink( get_page_by_path( 'monsta-engravings-settings' ) );
		if (is_page( 'monsta-engravings-details' ) ) {
			if( $_POST ){
				extract($_POST);
				extract($_FILES);
				$error = 0;
				$_SESSION[ 'engraving_error' ] = array();
				if( isset($deliverydate) && empty($deliverydate) ){
					$error ++;
					$_SESSION[ 'engraving_error' ]['date'] = "Customer Date Required .";
					unset(  $_SESSION[ 'engraving_setting_customer_date' ] );
				}
				if( isset($deliverydate) && !empty($deliverydate) ){
					$date = date( 'Y-m-d', strtotime($deliverydate));
					$is_valid_date = self::validateDate($date);
					if( $date == "1970-01-01" || !$is_valid_date ){
						$error ++;
						$_SESSION[ 'engraving_error' ]['date'] = "Invalid Customer Date .";
						 $_SESSION[ 'engraving_setting_customer_date' ] = $deliverydate;
					}
				}
				if( isset($tmmengravinglogo) && $tmmengravinglogo != '' && isset($tmmengravinglogoupload) && empty($tmmengravinglogoupload) ){
					$error ++;
					$_SESSION[ 'engraving_error' ]['logo'] =  "Company Logo is Mandatory";
				}
				if( $error > 0 ){
					header("Location: ".$step1_url);
					exit();
					//echo "<meta http-equiv='refresh' content='0;url=".$step1_url."'>";exit;
						/*<script>
 						window.location.href = '<?php echo $step1_url; ?>';
 					 </script>*/
				}
				$_SESSION[ 'engraving_step' ] = 2;
				unset( $_SESSION[ 'engraving_setting_existing_logo' ] );
				unset( $_SESSION[ 'engraving_setting_forgo_logo' ] );
				//unset( $_SESSION[ 'engraving_setting_presentation_date' ] );
				//unset( $_SESSION[ 'engraving_setting_customer_date' ] );
				unset( $_SESSION[ 'enter_engraving_details_online' ] );
				unset( $_SESSION[ 'enter_engraving_details_email' ] );
				unset( $_SESSION[ 'no_engraving_details' ] );
				unset( $_SESSION[ 'engraving_setting_uploaded_id' ] );
				//echo "<pre>";print_r($tmmengravinglogoupload);echo "</pre>";
				if(  isset($tmmengravinglogo) && $tmmengravinglogo != '' ) {
					if( isset($tmmengravinglogoupload) &&  isset($tmmengravinglogoupload['name'][0]) && $tmmengravinglogoupload['name'][0] != '' ) {
							/*$userid = get_current_user_id();
							$postId = array();
							$get_latest_user_posts = $wpdb->get_results( "SELECT `ID` FROM ".$wpdb->prefix."posts WHERE `post_author` = ".$userid." and post_parent = 0 and post_type = 'attachment' ORDER by `ID` ASC " );
							foreach($get_latest_user_posts as $k => $usersId_attachment){
								$postId[] = $usersId_attachment->ID;
							}*/
							//echo "SELECT `ID` FROM ".$wpdb->prefix."posts WHERE `post_author` = ".$userid." and post_parent = 0 and post_type = 'attachment' ORDER by `ID` DESC limit 1 " ;die;
							//echo "<pre>";print_r($postId);echo "</pre>";
								//if( count($postId) > 0 ){
									//$existing_attachment_id = $get_latest_user_posts->ID;
								//	$upload_id = self::upload_product_images( $tmmengravinglogoupload,0,$postId );
								//}else{
									$upload_id = self::upload_product_images( $tmmengravinglogoupload );
								//}
							//	echo "<pre>";print_r($upload_id);echo "</pre>";
							if( count($upload_id) > 0 ){
								$_SESSION[ 'engraving_setting_uploaded_id' ] = join(',',$upload_id);
							}
							//echo "<pre>";print_r($_SESSION[ 'engraving_setting_uploaded_id' ]);echo "</pre>";
					}
				}
				//echo "<pre>";print_r( $_SESSION[ 'engraving_setting_uploaded_id' ] );echo "</pre>";die;
				if(  isset($tmmexistingengravinglogo) && $tmmexistingengravinglogo != ''  ) {
					 $_SESSION[ 'engraving_setting_existing_logo' ] = $existinglogo != '' ? $existinglogo : 'on';
					 unset( $_SESSION[ 'engraving_setting_uploaded_id' ] );
				}
				if( isset($trophy_upload_id) && trim( $trophy_upload_id ) != '' && isset($tmmengravinglogoupload['name'][0]) && $tmmengravinglogoupload['name'][0] == ''  ){
					$_SESSION[ 'engraving_setting_uploaded_id' ] = $trophy_upload_id;
				}
				if(  isset($tmmforgoengravinglogo) && $tmmforgoengravinglogo != '' ) {
					 $_SESSION[ 'engraving_setting_forgo_logo' ] = $tmmforgoengravinglogo;
					 unset( $_SESSION[ 'engraving_setting_uploaded_id' ] );
				}
				if( isset($deliverydate) && $deliverydate != '' ) {
					$_SESSION[ 'engraving_setting_customer_date' ] = $deliverydate;
				}
				if( isset($presentationdate) && $presentationdate != '' ) {
					$_SESSION[ 'engraving_setting_presentation_date' ] = $presentationdate;
				}
				if( isset($tmmengravingdetail) && $tmmengravingdetail != '' ){
					$_SESSION[ 'enter_engraving_details_online' ] = $tmmengravingdetail;
				}
				if( isset($tmmengravingemail) && $tmmengravingemail != '' ){
					$_SESSION[ 'enter_engraving_details_email' ] = $tmmengravingemail;
					unset( $_SESSION[ 'engraving_setting_existing_logo' ] );
					unset( $_SESSION[ 'engraving_setting_forgo_logo' ] );
				}
				if( isset($tmmnoengraving) && $tmmnoengraving != '' ){
					$_SESSION[ 'no_engraving_details' ] = $tmmnoengraving;
					unset( $_SESSION[ 'engraving_setting_existing_logo' ] );
					unset( $_SESSION[ 'engraving_setting_forgo_logo' ] );
				}
			}else{
				if( ( isset( $_SESSION[ 'engraving_step' ] ) && $_SESSION[ 'engraving_step' ] < 2 ) || !isset( $_SESSION[ 'engraving_step' ] )  ) {
					header("Location: ".$step1_url);
					exit();
					 /*<script>
						window.location.href = '<?php echo $step1_url; ?>';
					 </script>
					 */
				}

			}
			$previous_page = get_permalink( get_page_by_path( 'monsta-engravings-settings' ) );
			/* tab 2 */
			$self = new Monstaengravings;
			Trophymonsta::view('engraving-details',compact('previous_page','self', 'engraving_instruction'));
		}
	}

	public static function monstamanagement_engravings_review() {
	    //error_log(date('Y-m-d H:i:s').' Monstaengravings monstamanagement_engravings_review '.PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
		global $wp_session;
		if($_POST){
			if( isset($_POST['tmmengravingemail']) && $_POST['tmmengravingemail'] != '' ){
						$_SESSION[ 'enter_engraving_details_email' ] = $_POST['tmmengravingemail'];
						unset( $_SESSION[ 'engraving_setting_existing_logo' ] );
						unset( $_SESSION[ 'engraving_setting_forgo_logo' ] );
						unset( $_SESSION[ 'enter_engraving_details_online' ] );
						unset( $_SESSION[ 'no_engraving_details' ] );
						unset( $_SESSION[ 'session_trophy_product_details' ] );
			}
			if( isset($_POST['tmmnoengraving']) && $_POST['tmmnoengraving'] != '' ){
				$_SESSION[ 'no_engraving_details' ] = $_POST['tmmnoengraving'];
				unset( $_SESSION[ 'enter_engraving_details_email' ] );
				unset( $_SESSION[ 'enter_engraving_details_online' ] );
				unset( $_SESSION[ 'engraving_setting_existing_logo' ] );
				unset( $_SESSION[ 'engraving_setting_forgo_logo' ] );
				unset( $_SESSION[ 'session_trophy_product_details' ] );
			}
			if( isset($_POST['deliverydate']) && $_POST['deliverydate'] != '' ) {
					$_SESSION[ 'engraving_setting_customer_date' ] = $_POST['deliverydate'];
				}
				if( isset($_POST['presentationdate']) && $_POST['presentationdate'] != '' ) {
					$_SESSION[ 'engraving_setting_presentation_date' ] = $_POST['presentationdate'];
				}
		}
		if( ( isset( $_SESSION[ 'engraving_step' ] ) && $_SESSION[ 'engraving_step' ] < 2  && !isset( $_SESSION[ 'enter_engraving_details_email' ] ) && !isset( $_SESSION[ 'no_engraving_details' ] )  ) || !isset( $_SESSION[ 'engraving_step' ] ) ){
					$step1_url = get_permalink( get_page_by_path( 'monsta-engravings-settings' ) );
					header("Location: ".$step1_url);
					exit();

			/* <script>
				window.location.href = '<?php echo $step1_url; ?>';
			 </script>*/

		}
		if ( is_page( 'monsta-engravings-review' ) ) {
			$_SESSION[ 'engraving_step' ] = 3;
			$trophy_logo_attacment = array();
			$checkout_url = get_permalink( get_page_by_path( 'checkout' ) );
			$products_count	 = isset($_POST[ 'item_count' ]) ?  count( $_POST[ 'item_count' ] ) : 0;
			$products_qty	 = isset($_POST[ 'item_qty' ]) ?   $_POST[ 'item_qty' ]  : array();
			$no_of_lines = isset($_POST[ 'no_of_lines' ]) ?  $_POST[ 'no_of_lines' ]  : array();
			$product_sku = isset($_POST[ 'product_sku' ]) ?  $_POST[ 'product_sku' ]  : array();
			$product_key = isset($_POST[ 'product_key' ]) ?  $_POST[ 'product_key' ]  : array();
			if( $products_count > 0 ){
				$product_details = array();
				$product_details[ 'product' ] = array();
				for( $i = 0; $i < $products_count ; $i++ ){
					if( isset( $product_sku[ $i ] )  && $product_sku[ $i ] != ''){
						$sku = $product_sku[ $i ]."##". $product_key[ $i ];
						$product_details[ 'product' ][ $sku ] = array();
					}
					if( isset( $products_qty[$i] ) && (int)$products_qty[$i] > 0 ){
							for( $j = 0; $j < (int)$products_qty[$i] ; $j++ ){
								if( isset( $product_details[ 'product' ][ $sku ][ $j ] ) == false ){
									$product_details[ 'product' ][ $sku ][ $j ] = array();
								}
								if( isset( $_POST['line_'.$i.'_'.$j] ) ){
									foreach( $_POST['line_'.$i.'_'.$j] as $key => $v ){
										if( $v != ''){
											$product_details[ 'product' ][ $sku ][ $j ][$key] = $v;
										}else{
											$product_details[ 'product' ][ $sku ][ $j ][$key] = null;
										}
									}
								}
								if( isset( $_POST['logo_'.$i.'_'.$j] ) ){
									foreach( $_POST['logo_'.$i.'_'.$j] as $key => $v ){
										if( $v != ''){
											$product_details[ 'product' ][ $sku ][ $j ][$key] = $v;
										}
									}
								}

							}
					}
				}
				$_SESSION[ 'session_trophy_product_details' ] = $product_details;
			}
			if( isset( $_SESSION[ 'engraving_setting_uploaded_id' ] ) && $_SESSION[ 'engraving_setting_uploaded_id' ] != ''){
				$attachment_id = explode(',',$_SESSION[ 'engraving_setting_uploaded_id' ]);
				foreach($attachment_id as $k=>$postid){
					$trophy_logo_attacment[] = wp_get_attachment_url( $postid );
				}
			}
			//echo "<pre>";print_r($_SESSION['engraving_setting_existing_logo']);echo "</pre>";
			if( isset( $_SESSION['engraving_setting_existing_logo'] ) && $_SESSION['engraving_setting_existing_logo'] != '' && $_SESSION['engraving_setting_existing_logo'] != 'on'  && $_SESSION['engraving_setting_existing_logo'] != 0 ){
				$trophy_logo_attacment = $_SESSION['engraving_setting_existing_logo'];
			}
			$engraving_email = isset($_SESSION[ 'enter_engraving_details_email' ]) ? $_SESSION[ 'enter_engraving_details_email' ] : '';
			$no_engraving = isset($_SESSION[ 'no_engraving_details' ]) ? $_SESSION[ 'no_engraving_details' ] : '';
			if( $engraving_email != '' || $no_engraving != '' ){
				 $previous_page = get_permalink( get_page_by_path( 'monsta-engravings-settings' ) );
			}else{
				$previous_page = get_permalink( get_page_by_path( 'monsta-engravings-details' ) );
			}
			Trophymonsta::view('engravings_review',compact('trophy_logo_attacment','previous_page','checkout_url'));
		}
	}


	public static function monsta_engravings_template($page_template )
	{
			if ( is_page( 'monsta-engravings-settings' ) || is_page( 'monsta-engravings-details' ) || is_page( 'monsta-engravings-review' ) ) {
				$page_template  = dirname( __FILE__ ) . '/monstatemplate.php';
	    }
			return $page_template ;
	}

	public static function upload_product_images($imageArr, $parent_id = 0, $upload_id = array() ) {
		global $wpdb;
		$upload_id_arr = array();
		//$image = preg_replace('/\s/i', '%20', $image);
		$wordpress_upload_dir = wp_upload_dir();
		$mimetype = array('gif' => 'image/gif', 'GIF' => 'image/gif',
										'jpeg' => 'image/jpeg', 'jpg' => 'image/jpeg',
										'JPEG' => 'image/jpeg', 'JPG' => 'image/jpeg',
										'tiff' => 'image/tiff', 'TIFF' => 'image/tiff',
										'bmp' => 'image/bmp', 'BMP' => 'image/bmp',
										'png' => 'image/png', 'PNG' => 'image/png',
										'ai' => 'application/postscript', 'AI' => 'application/postscript',
										'eps' => 'application/postscript', 'EPS' => 'application/postscript',
										'cdr' => '','CDR' => ''

										);
			
		if(isset($imageArr['name']) && count( $imageArr['name'] ) > 0 ){
			if( count($upload_id) > 0 ) {
				foreach($upload_id as $k=>$postid){
					if( isset( $imageArr['name'][$k]  ) == false ){
						wp_delete_post($postid, true);
						wp_delete_attachment($postid, true);
						unset($upload_id[$k]);
					}
				}
			}
			//echo "<pre>";print_r($upload_id);echo "</pre>";
			//echo "<pre>";print_r($imageArr['name']);echo "</pre>";
			
			foreach( $imageArr['name'] as $index => $file_name){
				$baseimag = str_replace(' ', '_',$file_name );
				$baseimag = str_replace('%20', '_',$file_name);
				$new_file_path = $wordpress_upload_dir['path'] . '/' . time(). '_' . $baseimag;
				$ext = pathinfo($baseimag, PATHINFO_EXTENSION);
				$new_file_mime = $mimetype[$ext];
				if ( file_exists( $new_file_path ) ) {
					$new_file_path = $wordpress_upload_dir['path'] . '/' . time() . '1_' . $baseimag;
				}
				if( move_uploaded_file( $imageArr['tmp_name'][$index], $new_file_path ) ) {
						if( count($upload_id) == 0 ) {
							$upload_post_id = wp_insert_attachment( array(
							'guid'           => $new_file_path,
							'post_mime_type' => $new_file_mime,
							'post_title'     => preg_replace( '/\.[^.]+$/', '', $baseimag ),
							'post_content'   => '',
							'post_status'    => 'inherit'
							), $new_file_path, $parent_id );
						} else if( count($upload_id) > 0 && isset($upload_id[$index])) {
							//echo "<pre>";echo "==========1=============".$upload_id[$index];echo "</pre>";
							update_attached_file($upload_id[$index], $new_file_path );
							$upload_post_id  = $upload_id[$index]; 
						}else if( isset($upload_id[$index]) == false &&  isset( $imageArr['name'][$index]  ) ) {
							//echo "<pre>";echo "=============2==========".$imageArr['name'][$index];echo "</pre>";
							$upload_post_id = wp_insert_attachment( array(
							'guid'           => $new_file_path,
							'post_mime_type' => $new_file_mime,
							'post_title'     => preg_replace( '/\.[^.]+$/', '', $baseimag ),
							'post_content'   => '',
							'post_status'    => 'inherit'
							), $new_file_path, $parent_id );
						}
						// wp_generate_attachment_metadata() won't work if you do not include this file
						require_once( ABSPATH . 'wp-admin/includes/image.php' );
						// Generate and save the attachment metas into the database
						$generate_attachment = wp_generate_attachment_metadata( $upload_post_id, $new_file_path );
						$attach_data = wp_update_attachment_metadata( $upload_post_id, $generate_attachment );
						 $upload_id_arr[]=$upload_post_id;
					}
			}
		}
		
		//die;
		return $upload_id_arr;
	}


	public static function trophyProducts(){
		global $woocommerce;
		$items = $woocommerce->cart->get_cart();
		$itemProductId = array_column($items,'product_id');
		$torphyProduct = 0 ;
		$returnArr = array();
		foreach($itemProductId as $product_id){
			$custompostmeta = get_post_meta( $product_id, '_trophymonsta_text_field', true );
			if($custompostmeta == 'trophymonsta'){
				$torphyProduct++;
			}
		}
		if($torphyProduct > 0){
			$returnArr['trophyProductExist'] = true ;
		}else{
			$returnArr['trophyProductExist'] = false ;
		}
		return $returnArr;
	}
	public static function validateDate($date, $format = 'Y-m-d')
	{
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) === $date;
	}

}
