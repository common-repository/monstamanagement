<?php

class Monstaquote {

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
		add_action('woocommerce_after_add_to_cart_form', array('Monstaquote','monstamanagement_add_quote_button'));
		add_action('wp_enqueue_scripts', array('Monstaquote','quote_scripts'));
		add_action('wp_ajax_quote_remove', array('Monstaquote','monstamanagement_quote_ajax_callback'));
		add_action('wp_ajax_nopriv_quote_remove', array('Monstaquote','monstamanagement_quote_ajax_callback'));
		add_action('wp_head', array('Monstaquote','monstamanagement_quote_js'));
		add_filter('woocommerce_login_redirect', array( 'Monstaquote', 'monstamanagement_quote_login_redirect' ) );
		add_filter('woocommerce_registration_redirect', array( 'Monstaquote', 'monstamanagement_quote_register_redirect' ) );
	}

	public static function monstamanagement_add_quote_button() {
	    $class = '';
	    $product_type = '';
	    $product_ = '';
	    if( function_exists('get_product') ) {
					$product_id = get_the_ID();
					$custompostmeta = get_post_meta( $product_id, '_trophymonsta_text_field', true );
					if ($custompostmeta != 'trophymonsta') {
						return;
					}

	        $product_ = wc_get_product(get_the_ID());
	        $product_type = ($product_->get_type()) ? "variation" : $product_->get_type();
	        if($product_->is_type('variable')){
	            $class = "_hide";
	        }

	    }

	    $product_image_url = wp_get_attachment_image_src(get_post_thumbnail_id(get_the_ID()),'full');
	    $return = '<form class="_add_to_quote" id="_add_to_quote_form_wrapper" method="POST" action="'.get_the_permalink().'">';
	    $return .= '<input type="hidden" name="product_id" value="'.get_the_ID().'"  />';
	    $return .= '<input type="hidden" name="product_title" value="'.get_the_title().'"  />';
	    $return .= '<input type="hidden" name="product_image" value="'.$product_image_url[0].'"  />';
	    $return .= '<input type="hidden" name="product_quantity" value="" class="quantity" />';
	    $return .= '<input type="hidden" name="product_type" class="product_type" value="'.$product_type.'" />';
	    if($product_->is_type('variable')) {
	        $return .= '<input type="hidden" name="variations_attr" class="variations_attr" value="" />';
	    }
	    $return .= '<input type="hidden" name="variation_id" class="variation_id" value="" />';
	    $return .= '<input type="hidden" name="action" value="monsta_quote_submission" />';
	    $return .= '<button type="submit" class="_add_to_quote_submit button '.$class.'" id="_add_to_quote_">'.__('ADD TO QUOTE', 'trophymonsta').'</button>';
	    $return .= '</form>';
	    echo $return;
	}

	public static function monstamanagement_quote_submission() {
		  if($_POST) {
					if(isset($_POST['action']) && $_POST['action'] == "_clear_quotes") {
						setcookie('manasta_quotes_elem', '', time()-3600, COOKIEPATH, COOKIE_DOMAIN, false);
						echo '<META HTTP-EQUIV="refresh" content="0;URL='.get_permalink(get_the_ID()).'">';
						exit();
					} else if(isset($_POST['action']) && $_POST['action'] == "send_quote") {

						$submit_data = array_map('monstamanagement_validate_array', $_POST['data']);
            $validate_email = explode(',', $_POST['_to_send_email']);
            $sanitize_email = array();
            $validation_result = true;
            foreach($validate_email as $vali_email) {
                $validated_email = sanitize_email($vali_email);
                $validated = (!empty($validated_email) ? true : false);
                if($validated) {
                    $sanitize_email[] = $validated_email;
                }
                else {
                    $validation_result = false;
                    break;
                }
            }

						$quoteinfo = array();
						$current_user = wp_get_current_user();
						if ( ! $current_user->exists() ) {
								 return;
						}



            $validated_all_emails = implode(",", $sanitize_email);
            if($validation_result) {
                $to_send = str_replace(' ', '', $validated_all_emails);
                $attachments = array();
                $before_quote = get_option('wc_settings_quote_email_before_message');
                if (!empty($before_quote)) {
                    $message = $before_quote;
                } else {
                    $message = '';
                }
                $message .= '<div style="width:90%;margin:0 auto;border: 1px solid #e5e5e5;">';
                $message .= '<table style="width: 100%;border-collapse: collapse;">';
                $message .= '<thead>';
                $message .= '<tr style="border-bottom: 1px solid #e5e5e5;">';
                $message .= '<th style="width: 16.66%;text-align: center;border-right:1px solid #e5e5e5;padding:10px;">';
                $message .= __('Product Image', 'trophymonsta');
                $message .= '</th>';
                $message .= '<th style="width: 16.66%;text-align: center;border-right:1px solid #e5e5e5;padding:10px;">';
                $message .= __('Product Title', 'trophymonsta');
                $message .= '</th>';
                $message .= '<th style="width: 16.66%;text-align: center;border-right:1px solid #e5e5e5;padding:10px;">';
                $message .= __('Product Price', 'trophymonsta');
                $message .= '</th>';
                $message .= '<th style="width: 16.66%;text-align: center;border-right:1px solid #e5e5e5;padding:10px;">';
                $message .= __('Product Quantity', 'trophymonsta');
                $message .= '</th>';
                $message .= '<th style="width: 16.66%;text-align: center;padding:10px;">';
                $message .= __('Total', 'trophymonsta');
                $message .= '</th>';
                $message .= '</tr>';
                $message .= '</thead>';
                $message .= '<tbody>';
                //$quote_post = array();
                $gett = null;
								$ip = null;
								$api_key = get_option( 'trophymonsta_api_key' );
								$response = Trophymonsta::http_get( Trophymonsta::build_query( array( 'key' => $api_key, 'site_url' => site_url() ) ), 'store/checkProductDiscount', $ip );
								$response	= json_decode($response);

                foreach ($submit_data as $sub_data) {
										$product_id = $sub_data['product_id'];
										$product = wc_get_product($product_id);
                    //$quote_post[] = array('product_id' => $sub_data['product_id'], 'product_image' => $sub_data['product_image'], 'product_title' => $sub_data['product_title'], 'product_price' => $sub_data['product_price'], 'product_quantity' => $sub_data['product_quantity'], 'product_type' => $sub_data['product_type'], 'variation_id' => $sub_data['variation_id'], 'sub_total' => $sub_data['sub_total'], 'quote_total' => $_POST['quote_total']);
                    $message .= '<tr style="border-bottom: 1px solid #e5e5e5;">';
                    $message .= '<td style="width: 16.66%;padding:10px;text-align: center;border-right:1px solid #e5e5e5;">';
                    $message .= '<a href="'.get_permalink($product_id) .'" ><img src="' . $sub_data['product_image'] . '" width="100" /></a>';
                    $message .= '</td>';
                    $message .= '<td style="width: 16.66%;padding:10px;text-align: center;border-right:1px solid #e5e5e5;">';
                    $message .= '<a href="'.get_permalink($product_id) .'" >'.$sub_data['product_title'];
                    /*get variable product send to admin email*/
										$product_price = $sub_data['product_price'];
                    //$product = wc_get_product ( $sub_data['product_id'] );
                    if ( $product->is_type( 'variable' ) ){
											$variation = wc_get_product($sub_data['variation_id']);
											foreach( $variation->get_variation_attributes() as $key => $val ) {
													if(is_array($val))
															$message .= '&nbsp;'.$val[0];
													else
															$message .= '&nbsp;'.$val;
											}
											$message .= '</a>';
                      //$message .=  ' : <b>'.get_post_meta($sub_data['variation_id'],'attribute_size',true).'</b>';
											if ($response->code == '200') {
												$mmdiscounts =  $response->success->product_discount;
												$mmdiscounts_price = array();
												$product_id = $product_variation_id;
												$discount_unit = 0;
												$discount_precent = 0;

												foreach ($mmdiscounts as $key => $discount) {
													if ($sub_data['product_quantity'] > $discount->unit) {
														$discount_unit = $discount->unit;
														$discount_precent = $discount->precent;
													}
												}
												$product_price = round(  ($product_price - ($product_price * ($discount_precent / 100))), 2 );
											}
											if ($discount_precent > 1) {
												$message .= '<div><b>Bulk Buy Savings:</b> '.wc_clean( $discount_precent.'%' ).'</div>';
												$message .= '<div><b>Original Price:</b> '.wc_clean($product->get_price()).'</div>';
											}
                  	}
                  $message .= '</td>';
                  $message .= '<td style="width: 16.66%;padding:10px;text-align: center;border-right:1px solid #e5e5e5;">';
                  $message .= wc_price($product_price);
                  $message .= '</td>';
                  $message .= '<td style="width: 16.66%;padding:10px;text-align: center;border-right:1px solid #e5e5e5;">';
                  $message .= $sub_data['product_quantity'];
                  $message .= '</td>';
                  $message .= '<td style="width: 16.66%;padding:10px;text-align: center;">';
                  $message .= wc_price($product_price * $sub_data['product_quantity']);
                  $message .= '</td>';
                  $message .= '</tr>';
                  $product_id = $sub_data['product_id'];
                  $product = wc_get_product($product_id);
                  $quantity = (int)$sub_data['product_quantity'];
                  $sale_price = $product->get_price();
                  //$gett += $sub_data['sub_total'];
									$gett += $product_price * $sub_data['product_quantity'];

									//$custompostmeta = get_post_meta( $product_id, '_trophymonsta_text_field', true );
									//$trophy_product_id = get_post_meta( $product_id, '_trophymonsta_product_id_text_field', true );
									//if ($custompostmeta == 'trophymonsta') {
										$quoteinfo['products'][] = array('id' => $trophy_product_id,
																											'code' => $product->get_sku(),
																											'qty' => (int)$sub_data['product_quantity']);
									//}
              }
              $message .= '</tbody>';
              $message .= '<tfoot>';
              $message .= '<tr>';
              $message .= '<td></td>';
              $message .= '<td></td>';
              $message .= '<td></td>';
              $message .= '<td style="width: 16.66%;padding:10px;text-align: center;border-left:1px solid #e5e5e5;">' . __('Sub Total', 'trophymonsta') . '</td>';
              $message .= '<td style="width: 16.66%;padding:10px;text-align: center;border-left:1px solid #e5e5e5;">'. wc_price($gett). '</td>';
              $message .= '</tr>';
              $message .= '</tfoot>';
              $message .= '</table>';
              $message .= '</div>';
              $after_quote = get_option('wc_settings_quote_email_after_message');
              if (!empty($after_quote)) {
                $message .= $after_quote;
            }
            $admin_email = null;
            $quote_admin_email = get_option('wc_settings_quote_admin_email');
            if ($quote_admin_email != '') {
                $admin_email = $quote_admin_email;
            } else {
                $admin_email = get_option('admin_email');
            }
            $current_user_id = '';
            if (is_user_logged_in()) {
                $current_user_id = get_current_user_id();
            }


						$quoteinfo['customer_details'] = array(array('company_name' => $current_user->company,
																										'first_name' => $current_user->user_firstname,
																										'last_name' => $current_user->user_lastname,
																										'email' => $current_user->user_email,
																										'phone' => $current_user->phone,
																										'address' => '',
																										'address1' => '',
																										'suburb' => '',
																										'postcode' => '',
																										'country' => '',
																										'state' => ''));
						//error_log(date('Y-m-d H:i:s').'METHOD SALESQUOTE:'.print_r($quoteinfo, true).PHP_EOL, 3, TROPHYMONSTA_PLUGIN_DIR."/logs/".date('d-m-Y')."_reg-errors.log");
						$response = Trophymonsta::http_post(json_encode($quoteinfo) , 'sales/quote', 'json');

            $quotes_send_to = $to_send;

            $site_title = get_bloginfo('name');
            $admin_email = get_option('admin_email');
            $headers = array('Content-Type: text/html; charset=UTF-8','From: '.$site_title.' <'.$admin_email.'>' );
            $quote_email_title = get_option('wc_settings_quote_email_subject');
            $email_title = (!empty($quote_email_title) ? $quote_email_title : __('Quote', 'trophymonsta'));
            if (wp_mail($to_send, $email_title, $message, $headers, $attachments)) {
                $remove_quote_after_email = (boolean)get_option('wc_settings_empty_quote_after_email');
                if ($remove_quote_after_email) {
                    setcookie('manasta_quotes_elem', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, false);
                }
                $message .= '<p>' . __('Quote has been sent to', 'trophymonsta') . ' ' . str_replace(',', ', ', $to_send) . '</p>';
                wp_mail($admin_email, __('Quote Enquiry', 'trophymonsta'), $message, $headers, $attachments);

                $success_message = null;
                $quote_success_email = get_option('wc_settings_quote_success_email');
                if ($quote_success_email != '') {
                    $success_message = $quote_success_email;
                } else {
                    $success_message = __('Check Your Mail', 'trophymonsta');
                }
                $_woo_message = array('status' => 'success', 'message' => $success_message);
                setcookie('_woo_message', json_encode($_woo_message), time() + 3600, COOKIEPATH, COOKIE_DOMAIN, false);

                echo '<META HTTP-EQUIV="refresh" content="0;URL=' . get_permalink(get_the_ID()) . '">';
                exit();
            } else {
                $error_message = null;
                $quote_error_email = get_option('wc_settings_quote_error_email');
                if ($quote_error_email != '') {
                    $error_message = $quote_error_email;
                } else {
                    $error_message = __('Try Again', 'trophymonsta');
                }
                $_woo_message = array('status' => 'error', 'message' => $error_message);
                setcookie('_woo_message', json_encode($_woo_message), time() + 3600, COOKIEPATH, COOKIE_DOMAIN, false);
                echo '<META HTTP-EQUIV="refresh" content="0;URL=' . get_permalink(get_the_ID()) . '">';
                exit();
            }
	        } else {
	            $error_message = null;
	            $quote_error_user_email = get_option('wc_settings_error_email_user_input');
	            if ($quote_error_user_email != '') {
	                $error_message = $quote_error_user_email;
	            } else {
	                $error_message = __('Try Again', 'trophymonsta');
	            }
	            $_woo_message = array('status' => 'error', 'message' => $error_message);
	            setcookie('_woo_message', json_encode($_woo_message), time() + 3600, COOKIEPATH, COOKIE_DOMAIN, false);
	            echo '<META HTTP-EQUIV="refresh" content="0;URL=' . get_permalink(get_the_ID()) . '">';
	            exit();
	        }

	    } else if(isset($_POST['action']) && $_POST['action'] == "monsta_quote_submission") {

              $product_id = intval($_POST['product_id']);
              $product_image = sanitize_text_field($_POST['product_image']);
              $product_title = sanitize_text_field($_POST['product_title']);
              $product_quantity = intval($_POST['product_quantity']);
              //$product_quantity =2;
              $product_type = sanitize_text_field($_POST['product_type']);
              if(array_key_exists('variations_attr', $_POST)) {
                  $product_variations = new WC_Product_Variable( $product_id );
                  $variation_attr_array = $product_variations->get_available_variations();
                  $variation_data = json_decode(stripslashes($_POST['variations_attr']), true);
                  $variation_attr_array = self::monstamanagement_get_product_variations($variation_data);
                  $product_variation_attr = $variation_attr_array;
              }
              else {
                  $product_variation_attr = '';
              }
              if(array_key_exists('variation_id', $_POST)) {
                  $product_variation_id = intval($_POST['variation_id']);
              }
              $expire = time()+3600*24*100;
              $set_array = array(
                  "product_id" => $product_id,
                  "product_image" => $product_image,
                  "product_title" => $product_title,
                  "product_quantity" => $product_quantity,
                  "product_type" => $product_type,
                  "variations_attr" => $product_variation_attr,
                  "product_variation_id" => ((isset($product_variation_id) && !empty($product_variation_id)) ?  $product_variation_id : $product_id),
              );
              $updated_checked = self::monstamanagement_quote_exists($set_array["product_variation_id"], $set_array["product_quantity"]);
              if($updated_checked !== false) {
                  if(!$updated_checked[0]) {
                      $update_quote = $updated_checked[1];
                      $update_quote[] = $set_array;
                  }
                  else {
                      $update_quote = $updated_checked[1];
                  }
              }
              else {
                  $update_quote = array($set_array);
              }
              $result_id = setcookie('manasta_quotes_elem', json_encode($update_quote), $expire, COOKIEPATH, COOKIE_DOMAIN, false);
              if($result_id) {
                  $message = "<div class='_quote_message_'>".$product_title." ".__('has been added to your quote', 'trophymonsta')." <a href='".get_permalink(get_page_by_path('monsta-quote'))."'>".__('View Quote', 'trophymonsta')."</a></div>";
                  wc_add_notice( $message, $notice_type = 'success' );
              }
              else {
                  $message = "<div class='_quote_message_'>".__('Please try again. ', 'trophymonsta')."</div>";
                  wc_add_notice( $message, $notice_type = 'error' );
              }
	        }
	    }
	}

	public static function monstamanagement_quote_exists($product_id, $quantity) {
	    $cookie_data = isset($_COOKIE['manasta_quotes_elem']) ? $_COOKIE['manasta_quotes_elem'] : '';
	    $return = false;
	    if (!empty($cookie_data)) {
	        $exists_quote = json_decode(stripslashes($cookie_data), true);
	        $unique_num = 0;
	        $update_quote = null;
	        $increase_exists = false;
	        if(is_array($exists_quote)) {
	            foreach ($exists_quote as $quote) {
	                $increase_count = false;
	                if(in_array($product_id, $quote)) {
	                    $increase_count = true;
	                    $increase_exists = $increase_count;
	                }
	                $update_param = array(
	                    "product_id" => $quote["product_id"],
	                    "product_image" => $quote["product_image"],
	                    "product_title" => $quote["product_title"],
	                    "product_quantity" => ($increase_count) ? $quote["product_quantity"] + $quantity : $quote["product_quantity"],
	                    "product_type" => isset($quote["product_type"]) ? $quote["product_type"] : '',
	                    "variations_attr" => (array_key_exists('variations_attr', $quote) ? $quote["variations_attr"] : ''),
	                    "product_variation_id" => (array_key_exists('product_variation_id', $quote) ? $quote["product_variation_id"] : $quote["product_id"]),
	                );
	                if(!empty($update_quote)) {
	                    $update_quote[] = $update_param;
	                }
	                else {
	                    $update_quote = array($update_param);
	                }
	                $unique_num++;
	            }
	        }
	        if($increase_exists) {
	            $return = true;
	        }
	        if($return) {
	            return array(true,$update_quote );
	        }
	        else {
	            return array(false,$update_quote);
	        }
	    }
	    else {
	        return false;
	    }
	}

	public static function monstamanagement_get_product_variations($variation_array, $html=false) {
	    $available_variations = $variation_array;
	    $result = null;
	    if($html) {
	        $result = '<dl class="variation _quote_variations">';
	        if (is_array($available_variations) || is_object($available_variations)) {
	            foreach ( $available_variations as $av_key=>$av_value){
	                $to_replace = array('attribute_pa_', ':');
	                $with_replace = array('', '');
	                $result .= '<dt class="variation-' . str_replace('attribute_pa_', '', $av_key) . '">' . ucfirst(str_replace($to_replace, $with_replace, $av_key)) . ' </dt>';
	                $result .= '<dd class="variation-' . str_replace('attribute_pa_', '', $av_key) . '"> <p>' . $av_value . '</p></dd>';
	            }
	        }
	        $result .= '</dl>';
	    }
	    else {
	        $result = array();
	        if (is_array($available_variations) || is_object($available_variations)) {
	            foreach ($available_variations as $av_key => $av_value) {
	                $result[$av_value[0]] = $av_value[1];
	            }
	        }
	    }
	    return $result;
	}

	public static function monstamanagement_quote_login_redirect( $redirect ) {
	    if(array_key_exists('rq', $_GET) && $_GET['rq'] == 'login') {
	        $redirect = get_permalink(get_page_by_path('monsta-quote'));
	    }
	    return $redirect;
	}

	public static function monstamanagement_quote_register_redirect( $redirect ) {
	    if(array_key_exists('rq', $_GET) && ($_GET['rq'] == 'login')) {
	        $redirect = get_permalink(get_page_by_path('monsta-quote'));
	        return $redirect;
	    }
	}

	public static function monstamanagement_get_quote($atts) {
		extract(shortcode_atts(array(
		    'manasta_quotes_elem' => isset($_COOKIE['manasta_quotes_elem']) ? $_COOKIE['manasta_quotes_elem'] : '',
		), $atts));
		if(!isset($manasta_quotes_elem)) {
		    $manasta_quotes_elem = '';
		}
		?>
		<div class="woocommerce quote">
		    <?php
				$ip = null;
				$api_key = get_option( 'trophymonsta_api_key' );
				$response = Trophymonsta::http_get( Trophymonsta::build_query( array( 'key' => $api_key, 'site_url' => site_url() ) ), 'store/checkProductDiscount', $ip );
				$response	= json_decode($response);
		    if(!empty($manasta_quotes_elem)) {
		        if(count(json_decode(stripslashes($manasta_quotes_elem))) > 0) {?>
		            <div class="quote_data_wrapper">
		                <form method="post" id="monstamanagement_send_quote_form_wrapper" action="<?php echo get_the_permalink(); ?>">
		                    <table class="shop_table cart" cellpadding="0">
		                        <thead>
		                            <tr>
		                                <th class="product-remove"><?php echo __('remove', 'trophymonsta'); ?></th>
		                                <th class="product-thumbnail"><?php echo __('Image', 'trophymonsta'); ?></th>
		                                <th class="product-name"><?php echo __('product', 'trophymonsta'); ?></th>
		                                <th class="product-price"><?php echo __('price', 'trophymonsta'); ?></th>
		                                <th class="product-quantity"><?php echo __('Quantity', 'trophymonsta'); ?></th>
		                                <th class="product-subtotal"><?php echo __('Total', 'trophymonsta'); ?></th>
		                            </tr>
		                        </thead>
		                        <tbody>
		                            <?php
		                            $cookie_data = json_decode(stripslashes($manasta_quotes_elem), true);
		                            /*this fuction define to get for variar*/
		                            function get_product_variation_price($product_variation_id, $with_html ) {
		                                global $woocommerce;
		                                $product = new WC_Product_Variation($product_variation_id);
		                                $price = '';

		                                if($with_html==1)
		                                    $price = $product->get_price_html(); // Works. Use this if you want the formatted price
		                                else
		                                    $price = $product->get_price();
		                                return $price;
		                            }
		                            if(is_array($cookie_data)) {
		                                global $woocommerce;
		                                $gett = null;
		                                $whole_quote_sub_total = null;
		                                foreach($cookie_data as $data) {
		                                    $product_obj = '';
		                                    $prod_id = $data['product_id'];
		                                    $_product = wc_get_product( $prod_id );
		                                    if( $_product->is_type( 'simple' ) ) {
		                                        $product_obj = wc_get_product($data['product_id']);
		                                    }
		                                    else {
		                                        $product_obj = new WC_Product_Variable( $prod_id );
		                                    }
		                                    $price_currency = self::monstamanagement_get_product_price($data['product_variation_id'], $data['product_type']);
		                                    //$price_currency = 37;
		                                    $id = 'product_id';
		                                    $image = 'product_image';
		                                    $title = 'product_title';
		                                    $price = 'product_price';
		                                    $quantity = 'product_quantity';
		                                    $type = 'product_type';
		                                    $variation_id = 'variation_id';
		                                    $total_price = 'sub_total';
		                                    $product_variation = 'product_variation';
		                                    ?>
		                                    <tr>
		                                        <td class="product-remove" data-delete-id="" id="product_<?php echo $data['product_id']; ?>"><span>X<span></td>
		                                            <input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][<?php echo $id; ?>]" class="" value="<?php echo $data['product_id']; ?>" />
		                                            <td class="product-image"><a href="<?php echo get_permalink($data['product_id']); ?>" ><img src="<?php echo $data['product_image']; ?>" /></a></td>
		                                            <input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][<?php echo $image; ?>]" class="" value="<?php echo $data['product_image']; ?>" />
		                                            <td class="product-title">
		                                                <a href="<?php echo get_permalink($data['product_id']); ?>" ><?php echo $data['product_title']; ?>
		                                                <?php
		                                                $product = wc_get_product ( $data['product_id'] );
		                                                $product_variation_id = $data['product_id'];
		                                                $_product = wc_get_product( $product_variation_id );
		                                                if( $_product->is_type( 'simple' ) ) {
		                                                    $product_price = $product->get_price();
		                                                } else {
		                                                    /*use to get variation price*/
		                                                    $product_variations = $product->get_available_variations();
		                                                    $arr_variations_id = array();
		                                                    foreach ($product_variations as $variation) {
		                                                        $product_variation_id = wc_get_product($data['product_variation_id']);
		                                                        $product_price = get_product_variation_price($product_variation_id,0);
		                                                    }
		                                                    $variation = wc_get_product($data['product_variation_id']);
		                                                    foreach( $variation->get_variation_attributes() as $key => $val ) {
		                                                        if(is_array($val))
		                                                            echo $val[0];
		                                                        else
		                                                            echo $val;
		                                                    }
		                                                }
																									echo '</a>';

																										if ($response->code == '200') {
																											$mmdiscounts =  $response->success->product_discount;
																											$mmdiscounts_price = array();
																											$product_id = $product_variation_id;
																											$discount_unit = 0;
																											$discount_precent = 0;

																											foreach ($mmdiscounts as $key => $discount) {
																												if ($data['product_quantity'] > $discount->unit) {
																													$discount_unit = $discount->unit;
																													$discount_precent = $discount->precent;
																												}
																											}
																											$product_price = round(  ($product_price - ($product_price * ($discount_precent / 100))), 2 );
																										}
																										if ($discount_precent > 1) {
																											echo "<div><b>Bulk Buy Savings:</b> ".wc_clean( $discount_precent.'%' )."</div>";
																											echo "<div><b>Original Price:</b> ".wc_clean($product->get_price())."</div>";
																										}
																										?>
		                                            </td>
		                                            <input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][<?php echo $title; ?>]" class="" value="<?php echo $data['product_title']; ?>" />
		                                            <td class="product-price"><?php echo wc_price($product_price); ?></td>
		                                            <input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][<?php echo $price; ?>]" class="" value="<?php echo esc_html($price_currency['price']); ?>" />
		                                            <td class="product-quantity">
		                                                <input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][product_quantity]" class="" value="<?php echo $data['product_quantity'] ?>" />
		                                                <?php echo $data['product_quantity']; ?></td>
		                                                <td class="product-total test"><?php echo wc_price($product_price * $data['product_quantity']); ?></td>
		                                                <?php
		                                                $product_sub_total = WC()->cart->get_product_subtotal( $product_obj, $data['product_quantity']);
		                                                $currency = get_woocommerce_currency_symbol();
		                                                $price_with_currency = strrchr($product_sub_total,$currency);
		                                                $price_num = str_replace($currency, '', $price_with_currency);
		                                                $product_id = $data['product_id'];
		                                                $product = wc_get_product($product_id);
		                                                $quantity = (int)$data['product_quantity'];
		                                                $sale_price = $product->get_price();
		                                                $gett += $product_price*$quantity;
		                                                ?>
		                                                <input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][<?php echo $total_price; ?>]" class="" value="<?php echo $product_price * $data['product_quantity']; ?>" />
		                                                <input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][<?php echo $quantity; ?>]" class="" value="<?php echo $data['product_quantity']; ?>" />
		                                                <input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][<?php echo $type; ?>]" class="" value="<?php echo $data['product_type']; ?>" />
		                                                <input type="hidden" name="data[<?php echo $data['product_variation_id']; ?>][<?php echo $variation_id; ?>]" class="variation_id" value="<?php echo $data['product_variation_id']; ?>" />
		                                                <input type="hidden" name="quote_total" class="quote_total" value="<?php echo $whole_quote_sub_total; ?>" />
		                                            </tr>
		                                            <?php
		                                        }
		                                        ?>
		                                    </tbody>

		                                    <tfoot>
		                                        <tr>
		                                            <td></td>
		                                            <td></td>
		                                            <td></td>
		                                            <td colspan="2"><?php echo __('Sub Total', 'trophymonsta'); ?></td>
		                                            <td><?php echo wc_price($gett ); ?></td>
		                                        </tr>
		                                    </tfoot>
		                                </table>
		                                <div id="_send_quote_popup" style="display:none;">
		                                    <?php
		                                    if(is_user_logged_in()) {
		                                        ?>
		                                        <div class="_send_quote_form_wrapper">
		                                            <label>
		                                                <?php echo apply_filters( 'monstamanagement_email_field_popup_logged_in', __('Write comma separated email addresses.', 'trophymonsta') ); ?>
		                                            </label>
		                                            <?php
		                                            $current_user = wp_get_current_user();
		                                            $user_email = $current_user->user_email;
		                                            ?>
		                                            <input type="text" name="_to_send_email" id="_to_send_email" value="<?php echo $user_email; ?>">
		                                            <button class="button" id="send_trigger" ><?php echo __('Send', 'trophymonsta'); ?></button>
		                                        </div>
		                                    </div>
		                                    <a href="#TB_inline?width=350&height=250&inlineId=_send_quote_popup" id="_send_quote_email_" class="thickbox"><?php echo __('Send', 'trophymonsta'); ?></a>
		                                    <?php
		                                }
		                                else {
		                                    if((boolean)get_option( 'wc_settings_allow_guest_user' )) {
		                                        ?>
		                                        <div class="_send_quote_form_wrapper">
		                                            <label>
		                                                <?php echo apply_filters( 'monstamanagement_email_field_popup_guest', __('Write comma separated email addresses.', 'trophymonsta') ); ?>
		                                            </label>

		                                            <input type="text" name="_to_send_email" id="_to_send_email" value="">
		                                            <button class="button" id="send_trigger" ><?php echo __('Send', 'trophymonsta'); ?></button>
		                                        </div>
		                                    </div>
		                                    <a href="#TB_inline?width=350&height=250&inlineId=_send_quote_popup" id="_send_quote_email_" class="thickbox"><?php echo __('Send', 'trophymonsta'); ?></a>
		                                    <?php
		                                }
		                                else {
		                                    ?>
		                                </div>
		                                <a href="<?php echo get_permalink(get_page_by_path('my-account')).'?rq=login'; ?>" id="_send_quote_email_"><?php echo __('Send', 'trophymonsta'); ?></a>
		                                <?php
		                            }
		                        }
		                        ?>
		                        <input type="hidden" name="_to_send_email" class="_to_send_email" value="" />
		                        <input type="hidden" name="action" value="send_quote" />
		                        <input type="submit" value="email quote" class="_submit" />
		                    </form>
		                </div>

		                <div class="_quoteall_buttons_wrapper">
		                    <?php
		                    if(get_option( 'wc_settings_quote_to_cart_select' ) == "true") {
		                        ?>
		                        <form method="post" id="_add_quote_to_cart" action="<?php echo get_the_permalink(); ?>">
		                            <?php
		                            foreach($cookie_data as $data_c) {
		                                $id = 'product_id';
		                                $quantity = 'product_quantity';
		                                $type = 'product_type';
		                                $variation_id = 'variation_id';
		                                $variation_attr = 'variation_attr';
		                                ?>
		                                <input type="hidden" name="data[<?php echo $data_c['product_variation_id']; ?>][<?php echo $id; ?>]" class="" value="<?php echo $data_c['product_id']; ?>" />
		                                <input type="hidden" name="data[<?php echo $data_c['product_variation_id']; ?>][<?php echo $quantity; ?>]" class="" value="<?php echo $data_c['product_quantity']; ?>" />
		                                <input type="hidden" name="data[<?php echo $data_c['product_variation_id']; ?>][<?php echo $type; ?>]" class="" value="<?php echo $data_c['product_type']; ?>" />
		                                <input type="hidden" name="data[<?php echo $data_c['product_variation_id']; ?>][<?php echo $variation_id; ?>]" class="" value="<?php echo $data_c['product_variation_id']; ?>" />
		                                <input type="hidden" name="data[<?php echo $data_c['product_variation_id']; ?>][<?php echo $variation_attr; ?>]" class="" value="<?php echo esc_html(json_encode($data_c['variations_attr'])); ?>" />
		                                <?php
		                            }
		                            ?>
		                            <input type="hidden" name="action" value="add_to_cart_q">
		                            <input type="submit" value="<?php echo __('Add to Cart', 'trophymonsta'); ?>" class="_submit button" />
		                        </form>
		                        <?php
		                    }
		                    ?>
		                    <form method="post" id="clear_quotes" action="<?php echo get_the_permalink(); ?>">
		                        <input type="hidden" name="action" value="_clear_quotes" />
		                        <input type="submit" value="<?php echo __('Empty Quote', 'trophymonsta'); ?>" class="_submit button" />
		                    </form>
		                    <button id="_email_quote_trigger" class="button"><?php echo __('Email', 'trophymonsta'); ?></button>
		                </div>
		                <?php
		            }
		        }
		    }
		    else {
		        ?>
		        <p><?php echo __('Your Current Quote is empty', 'trophymonsta'); ?></P>
		            <a href="<?php echo get_permalink(get_page_by_path('shop')); ?>" class="return_shop_quote"><?php echo __('Return To Shop', 'trophymonsta'); ?></a>
		            <?php
		        }
		        ?>
		    </div>
		    <?php
		}
		public static function monstamanagement_get_product_price($product_id, $product_type='simple') {
		    $price = array();
		    $temp = null;
		    $_product = wc_get_product( $product_id );
		    if( $_product->is_type( 'simple' ) ) {
		        $temp = $_product->get_price_html();
		    } else {
					$temp = $_product->get_price_html();
		    }
		    $currency = get_woocommerce_currency_symbol();
		    $price_with_currency = strrchr($temp,$currency);
		    $price_num = str_replace($currency, '', $price_with_currency);
		    $price['formated_price'] = $price_with_currency;
		    $price['price'] = str_replace(',','',$price_num);
		    return $price;
		}


		public static function quote_scripts() {
		    add_thickbox();
		    wp_register_script( 'quote-script-js', plugins_url( '/js/quote.js', __FILE__ ), array('jquery'), true );
		    // Localize the script with new data
		    $translation_array = array(
		        'plugin_url' => TROPHYMONSTA_PLUGIN_URL,
		    );
		    wp_localize_script( 'quote-script-js', 'plugin_object', $translation_array );
				// Enqueued script with localized data.
		    wp_enqueue_script( 'quote-script-js' );
		    wp_enqueue_style('quote-style', plugins_url( '/css/quote.css', __FILE__ ));
		}

		public static function monstamanagement_quote_ajax_callback() {
		    $product_id = (array_key_exists('product_id', $_POST) ? $_POST['product_id'] :'');
		    $cookie_data = isset($_COOKIE['manasta_quotes_elem']) ? $_COOKIE['manasta_quotes_elem'] : '';
		    $update_quote =array();
		    if(!empty($cookie_data)) {
		        $exists_quote = json_decode(stripslashes($cookie_data), true);
		        if(is_array($exists_quote)) {
		            foreach ($exists_quote as $quote_array) {
		                if (!in_array($product_id, $quote_array)) {
		                    $update_param = array(
		                        "product_id" => $quote_array["product_id"],
		                        "product_image" => $quote_array["product_image"],
		                        "product_title" => $quote_array["product_title"],
		                        "product_quantity" => $quote_array["product_quantity"],
		                        "product_type" => $quote_array["product_type"],
		                        "product_variation_id" => $quote_array["product_variation_id"],
		                    );
		                    if (!empty($update_quote)) {
		                        $update_quote[] = $update_param;
		                    }
		                    else{
		                        $update_quote = array($update_param);
		                    }
		                }
		            }
		        }
		        if(!empty($update_quote)) {
		            $expire = time() + 3600 * 24 * 100;
		        }
		        else{
		            $expire = time() - 3600 * 24 * 100;
		        }
		        $result_id = setcookie('manasta_quotes_elem', json_encode($update_quote), $expire, COOKIEPATH, COOKIE_DOMAIN, false);
		        echo json_encode(array(true, $product_id));
		        die();
		    }
		}

		public static function monstamanagement_quote_js() {
		    global $product;?>
		    <script>
		        jQuery(document).ready(
		            function($) {
		                $('._add_to_quote_submit').click(function(e) {
		                    e.preventDefault();
		                    <?php
		                    if( function_exists('get_product') ) {
		                        $product_ = wc_get_product(get_the_ID());
		                        // if($product_->is_type( 'variable' )){
		                        if((isset($_POST['product_type']) && $_POST['product_type'] == "variation")){ ?>
		                            var $variation = {};
		                            var $count = 0;
		                            var $product_variation_id = $('table.variations').find('tr').each(
		                            function() {
		                            var $var_value = $(this).find('td.value').find('select').val();
		                            var $var_key = $(this).find('td.value').find('select').attr('name');
		                            $variation[$count] = [$var_key, $var_value];
		                            $count++;
		                        }
		                        );
		                        $('.single-product').find('._add_to_quote').find('input.variations_attr').val(JSON.stringify($variation)).change();
		                        <?php
		                    }
		                }?>
		                var $product_quantity = $('.single-product').find('form.cart').find('.quantity').find('input[type="number"]').val();
		                var $product_variation_id = $('.single-product').find('form.variations_form').find('input.variation_id').val();
		                $('.single-product').find('._add_to_quote').find('input.quantity').val($product_quantity).change();
		                $('.single-product').find('._add_to_quote').find('input.variation_id').val($product_variation_id).change();
		                var $elem = document.getElementById('_add_to_quote_form_wrapper');
		                $elem.submit();
		            });
		            $('.woocommerce.quote').find('tbody').find('td.product-remove').click(function() {
		              var $to_delete_id = $(this).siblings('.variation_id').val();
		              /* In front end of WordPress we have to define ajaxurl */
		              var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
		              var data = {
		              'action': 'quote_remove',
		              'product_id' : $to_delete_id
		              };
		              $.post(ajaxurl, data, function(response) {
		                var responseArray = $.parseJSON(response);
		              //location.reload();
		              });
		            });
		       	});
		    </script>
		    <?php
		}
}
