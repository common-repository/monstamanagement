<?php
$disablelogo = $readonly =  '';
 if( isset( $_SESSION[ 'engraving_setting_forgo_logo' ] ) &&  $_SESSION[ 'engraving_setting_forgo_logo' ] != '' ){
	 $disablelogo = 'disabled';
 }
 $product_details = array();
 if( isset( $_SESSION[ 'session_trophy_product_details' ]['product'] ) && count( $_SESSION[ 'session_trophy_product_details' ]['product'] )> 0  ){
	 $product_details = $_SESSION[ 'session_trophy_product_details' ]['product'];
 }
?>
<div role="tabpanel" class="tab-pane active" id="details">
			<form id="tmmengravingdetails" method="post" name="tmmengravingsettings" enctype="multipart/form-data" action="<?php echo get_site_url(); ?>/monsta-engravings-review">

				<div class="design-process-content">
					<div class="tmm-step1">
						<h3>I Would like the following to be engraved on my items: </h3>
						
						<div class="engraving-cont">
							<strong><?php echo $engraving_instruction; ?></strong>
						</div>
						<?php
							global $wpdb;
    						$items =  WC()->cart->get_cart();
    						$custompostmeta = '';
    						$itemCount = 0;
    						$process_chars = $self::DEFAULT_CHAR;
    						$process_lines = $self::DEFAULT_LINES;
							$monstavariants = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."woocommerce_attribute_taxonomies WHERE `attribute_name` like 'monsta%' and `attribute_name` not in ('monstasize', 'monstacolor', 'monstamaterial', 'monstaprocess')");
							$variation_array = array();
							$variation_detail = array();
							foreach($monstavariants as $k => $variation){
								$variation_array[] =  'attribute_pa_'.$variation->attribute_name;
							}
							foreach($items as $item => $item_array) {
								$keys = $item_array['key'];
								if( !isset( $variation_detail[ $keys ] ) ){
											$variation_detail[ $keys ] = array();
								}
								foreach( $variation_array as $variation_name ){
									if( isset( $item_array[ $variation_name ] ) &&  $item_array[ $variation_name ] != ''){
										$variation_detail[ $keys ][ $variation_name ] = $item_array[ $variation_name ];
									}
								}
							}

    						foreach($items as $item => $values) {
								$key = $values['key'];
    							$index = 0;
							$custompostmeta = get_post_meta( $values['product_id'], '_trophymonsta_text_field', true );
							if($custompostmeta == 'trophymonsta') {
								if( isset($values['variation_id']) && $values['variation_id'] != '' ){
    								$mainimage = wp_get_attachment_image_src( get_post_thumbnail_id( $values['variation_id'] ), 'single-post-thumbnail' );
    							}
    							$termidArray = $componentimg = array();
								$component_name = array();
    							$monstaengraving_present = '';
    							if( isset( $variation_detail[ $key ] ) && count( $variation_detail[ $key ] ) > 0 ){
    								foreach( $variation_detail[ $key ] as $k => $attribute ){
    									if( $k == 'attribute_pa_monstaengraving' && $attribute != '' ){
    										//$termdata = $wpdb->get_row( "SELECT term_id FROM ".$wpdb->prefix."terms WHERE `slug`  LIKE '".$attribute."' ");
											$termdata = get_term_by('slug', $attribute, 'pa_monstaengraving' );
    										if(  null !== $termdata ){
    											$monstaengraving_present = $termdata->term_id;
    										}
    									}else{
											//$termdata = $wpdb->get_results( "SELECT term_id, name FROM ".$wpdb->prefix."terms WHERE `slug`  LIKE '".$attribute."' ");
											$taxonomy_array = explode( '_',$k ) ;
											$taxonomy = array_pop( $taxonomy_array );
											$termdata = get_term_by('slug', $attribute, 'pa_'.$taxonomy );
    										if( isset( $termdata->term_id ) && $attribute != '' ){
    											$termidArray[] = $termdata->term_id;
												$component_name[ $termdata->term_id ] =  $termdata->name;
    										}
										}
    								}

    								if( count($termidArray) > 0 ){
    									$termids = join(',',$termidArray);
    									$componentimg = $wpdb->get_results( "SELECT meta_value, term_id FROM ".$wpdb->prefix."termmeta WHERE `term_id`  IN ( ".$termids.") AND  `meta_key` LIKE 'components_image'  ORDER BY `meta_key` ASC ");

    								}
    							}
    							$_product =  wc_get_product( $values['data']->get_id());
    							$product_variation_id = $values['variation_id'];
    							$product_parent_id = $values['product_id'];
								$product_name = get_post_meta($product_variation_id,'_trophymonsta_name',true) ;
    							$product_sku = get_post_meta( $product_variation_id, '_sku', true );
    							if($monstaengraving_present != '' ) {
    								$termid = $monstaengraving_present;
    								$process_chars = get_term_meta($termid, 'process_chars',true);
    								$process_lines = get_term_meta($termid, 'process_lines', true);
    							} else {
    								$process_dept_group = get_post_meta( $product_variation_id , 'process_departments_grouping', false);
    								if( count( $process_dept_group ) > 0 ){
    									foreach( $process_dept_group as $keys => $value ){
											$termdata = $wpdb->get_row( "SELECT term_id FROM ".$wpdb->prefix."terms WHERE slug= 'process-".join('-', explode('___',$value) )."' ");
										if(  null !== $termdata ) {
											  $process_chars = get_term_meta($termdata->term_id, 'process_chars',true);
											  $process_lines = get_term_meta($termdata->term_id, 'process_lines', true);
											  break;
											}
    									}
    								}
    							}
    							$no_of_lines = $process_lines != '' ? $process_lines : $self::DEFAULT_LINES ;
    							$maxlength = $process_chars != '' ? $process_chars : $self::DEFAULT_CHAR;
								$current_product_session = isset( $product_details[$product_sku."##".$key] ) ? $product_details[$product_sku."##".$key] : array();
								$product_size = isset( $values[ 'variation' ][ 'attribute_pa_monstasize' ] ) ?  $values[ 'variation' ][ 'attribute_pa_monstasize' ] : '';
						?>
						<div class="tmm-detail-slider-outer">
							<div class="logo-slider">
								<div  class="owl-carousel  tmm-logo-owl-carousel owl-theme owl-loaded">
									<div class="item">
									<div class="tmm-slider-logo-img" style="background-image:url( <?php echo isset($mainimage[0]) ? $mainimage[0] : 'img/logo.jpg'; ?>)"></div>
									<h5><?php //echo $_product->get_title();
											echo  $product_name ;
									?></h5>
									<small><?php echo $values['quantity'];?> qty</small>
									</div>
								 </div>

								 <div  class="owl-carousel  tmm-logo-owl-carousel owl-theme owl-loaded owl-logo-small owl-logo-slider " data-item="<?php if(isset( $componentimg )  && count( $componentimg ) > 0 ) { echo count($componentimg); } ?>">
									<?php
										if( isset( $componentimg ) ){
											foreach($componentimg as $k => $compimg ){
												?>
												<div class="item">
													<div class="tmm-slider-logo-img" style="background-image:url(<?php echo $compimg->meta_value; ?>)">
													</div>
													<?php if( isset( $component_name[$compimg->term_id] ) ){  ?>
														<h5>  <?php echo $component_name[$compimg->term_id]; ?> </h5>
													<?php } ?>
												</div>
												<?php
											}
										}
									?>
								</div>
							</div>
							<input type="hidden" value="<?php echo $key; ?>" id="product_key" name="product_key[]" />
							<input type="hidden" value="<?php echo $product_sku; ?>" id="product_id" name="product_sku[]" />
							<input type="hidden" value="<?php echo $itemCount; ?>" id="item_count" name="item_count[]" />
							<input type="hidden" value="<?php echo $values['quantity']; ?>" id="item_count" name="item_qty[]" />
							<input type="hidden" value="<?php echo $no_of_lines; ?>" id="item_count" name="no_of_lines[]" />
							<div id="owl-slider1" class="owl-carousel tmm-owl-carousel owl-theme owl-loaded ">
							<?php $lastCount = $values['quantity'] - 1;  for($i = 0; $i< $values['quantity'] ; $i++ ){ ?>
									<div class="item   engravedItems_<?php echo $itemCount; ?>">
									<h4><?php echo $product_size; ?>  #<?php echo $i + 1; ?></h4>
									<ul>
										<?php for( $y = 0; $y < $no_of_lines ; $y++ ){
											$line_char_values = isset( $current_product_session[$i][$y] ) ? $current_product_session[$i][$y] : '';
											$enable_logo = false;
											$line_val = '';
											$readonly = $disablelogo = '';
											if( $line_char_values != '' && $line_char_values == 'on' ){
												$enable_logo= true;
												$readonly = 'readonly';
												$disablelogo = '';
											}
											if( $line_char_values != '' && $line_char_values != 'on' ){
												$line_val= $line_char_values;
												$disablelogo = 'disabled';
												$readonly = '';
											}

										?>
											<li>
												<div class="tmm-inputouter">
													<input type="text" placeholder="Please enter line..." name="line_<?php echo $itemCount."_".$index; ?>[]" maxlength="<?php echo $maxlength; ?>"  class="engraving_process_chars_lines"  value="<?php echo $line_val; ?>"  <?php echo $readonly; ?> />
													<label class="tmm-inputcontainer">Logo

														<input type="checkbox" name="logo_<?php echo $itemCount."_".$index; ?>[<?php echo $y; ?>]" <?php echo $disablelogo; ?>  class="engraving_process_chars_lines"  <?php  if($enable_logo) { ?>  checked <?php } ?> />

														<span class="checkmark"></span>
													</label>
												</div>
												<?php if($i != $lastCount ) { ?>
													<em class="tmm-arrow" style="cursor: pointer;" onclick="copyToNext(this,<?php echo $y;?>,<?php echo $i;?>,<?php echo $itemCount;?> )"  >&#x279D;</em>
												<?php } ?>
										<?php  } $index++; ?>
										</li>
									</ul>
									</div>
							<?php } ?>

							</div>
						</div>
							<?php }
							$itemCount ++;
						}  ?>

					</div>
				</div>

				<div class="tmm-button-outer">
					<button class="tmm-prev" title="Prev Step" onclick="redirectUrl(this,'<?php echo $previous_page; ?>');return false;"><big>&#x279D;</big> Prev Step</button>
					<button id="tmmengravingdetailssubmit" class="tmm-next" title="Next Step">Next Step &#x279D;</button>
				</div>
			</form>
		</div>
