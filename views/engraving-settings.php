<?php 
$fromonline = isset($_SESSION[ 'enter_engraving_details_online' ]) ? $_SESSION[ 'enter_engraving_details_online' ] : '';
$from_email = isset($_SESSION[ 'enter_engraving_details_email' ]) ? $_SESSION[ 'enter_engraving_details_email' ] : '';
$no_engraving = isset($_SESSION[ 'no_engraving_details' ]) ? $_SESSION[ 'no_engraving_details' ] : '';

/*if( trim($redirectTo) != '' ) {
	$form_url = $redirectTo;
}else{
	$form_url = get_site_url()."/monsta-engravings-details";
}*/
$form_url = get_site_url()."/monsta-engravings-details";
$step1 = get_permalink( get_page_by_path( 'monsta-engravings-settings' ) );
$step2 = get_permalink( get_page_by_path( 'monsta-engravings-details' ) );
$step3 = get_permalink( get_page_by_path( 'monsta-engravings-review' ) );
//$wordpress_upload_dir = wp_upload_dir();
//$download_url = TROPHYMONSTA_PLUGIN_URL.'/engravings_template.xlsx';
$login_status = 0;
//if (  is_user_logged_in()  ) {
//	$login_status = 1;
//}
?>
<div role="tabpanel" class="tab-pane active tmm-setting-tab" id="settings">
	<input type="hidden" value="<?php echo $login_status; ?>" id="login_status" />
	<input type="hidden" value="<?php echo $step1; ?>" id="step_1_url" />
	<input type="hidden" value="<?php echo $step2; ?>" id="step_2_url" />
	<input type="hidden" value="<?php echo $step3; ?>" id="step_3_url" />
	<input type="hidden" value="0" id="upload_issue" />
	<form id="tmmengravingsettings" method="post" name="tmmengravingsettings" enctype="multipart/form-data" action="<?php echo $form_url; ?>"    >
	<input type="hidden" value="<?php echo join(',',$existinglogo_attachment) ?>" id="existinglogo" name="existinglogo" />
	<input type="hidden" value="<?php echo $attachment_id ?>" id="trophy_upload_id" name="trophy_upload_id" />
		<div class="design-process-content">
			<div  class="tmm-step1">
				<h3>For engraving, I would like to:</h3>
				<ul class="tmm-inputcontainer-outer">
					<li>
						<label class="tmm-inputcontainer "><?php echo $engraving_online; ?>
						<input type="radio" <?php if( $fromonline != '' ){ ?> checked="checked" <?php }else if( $from_email == '' && $no_engraving == '' ){ ?> checked="checked" <?php } ?> id="tmmengravingdetail" name="tmmengravingdetail"> 
						<span class="checkmark"></span></label>
						<div class="engraving-cont">
							<strong><?php echo $engraving_online_desc; ?></strong>
						</div>
					</li>
					<li>
						<label class="tmm-inputcontainer"><?php echo $engraving_later; ?>
						<input type="radio" <?php if( $from_email != '' ){ ?> checked="checked" <?php } ?> id="tmmengravingemail" name="tmmengravingemail">
						<span class="checkmark"></span></label>
						<div class="engraving-cont">
							<strong><?php echo $engraving_later_desc; ?></strong>
						</div>
					</li>
					<li>
						<label class="tmm-inputcontainer"><?php echo $engraving_forgo; ?>
						<input type="radio" <?php if( $no_engraving != '' ){ ?> checked="checked" <?php } ?> id="tmmnoengraving" name="tmmnoengraving">
						<span class="checkmark"></span></label>
						<div class="engraving-cont">
							<strong><?php echo $engraving_forgo_desc; ?></strong>
						</div>
					</li>
				</ul>

			</div>
			<div style="display:none;" id="email_engraving_process"  class="tmm-download">
					<span> Please download excel to send engraving details via email </span> <a href="<?php echo $engraving_xls; ?>"  download ><i class="fas fa-download"></i></a>
			</div>
			<div <?php if( $from_email != '' || $no_engraving != '') { ?>style="display:none;" <?php } ?> id="tmm-logo-upload" class="tmm-step1">
				<h3>For logo, I would like to:</h3>
				<ul class="tmm-inputcontainer-outer">
					<li>
						<label class="tmm-inputcontainer">
							<?php echo $upload_logo; ?> <br><strong>Per logo Fee is <?php echo '$'.$logo_fee; ?></strong>
								<input type="radio" id="tmmengravingwithlogo" name="tmmengravinglogo" <?php if( $newlogo == true ) { ?>  checked="checked" <?php } ?> > 
									<span class="checkmark"></span>
						</label>
						
				</li>
					<li>
						<label class="tmm-inputcontainer">
							<?php echo $use_existing_logo; ?>
									<input type="radio" id="tmmengravingexistinglogo" name="tmmexistingengravinglogo"  <?php if( $existinglogo == true ) { ?>  checked="checked" <?php }  ?> >
									<span class="checkmark"></span> 
						</label>
					</li>
					<li>
						<label class="tmm-inputcontainer">
							<?php echo $forgo_logo; ?>
								<input type="radio" id="tmmengravingnologo" name="tmmforgoengravinglogo" <?php if( $forgetLogo == true ) { ?>  checked="checked" <?php }  ?> >
									<span class="checkmark"></span>
						</label>
					</li>
				</ul>
				<div  style="display:none;" id="tmmengravinglogoupload" > 
					<div class="tmm-company-logo" title="Upload Your Company Logo">
						<input type="file" name="tmmengravinglogoupload[]" value="" class="" accept='image/*' onchange="previewImage(this,'#company_logo')" multiple="multiple" />
						Upload Your Company Logo 
					</div>
					<small style="position: absolute;left: 16%;margin-top: 10%;margin-bottom: 10%;"> To upload multi logo press Ctrl button and choose logos. </br> Maximum 5mb allowed.Allowed format (.jpg, .png, .jpeg, .cdr, .gif, .ai, .eps) </small>
					
					<div id="company_logo" class="company_logo"  >
						<span class="tmm-img-name">
						<?php if( count($company_logo_attacment) > 0 ){ 
								foreach( $company_logo_attacment as $k=> $logoimg){
						?>
									<img  src="<?php echo $logoimg; ?>" alt="company logo" style="display:none;" />
									<span id='downloadable'> <a href="<?php echo $logoimg; ?>"  download > download </a> 
									<!--</span> -->
									<!--<span class='company_logo_name'> -->
									<?php 
									$file_name =  basename( $logoimg );
									if( $file_name != '' ){
										echo $file_name;
									}
								?>
									<!--</span> -->
									<br>
								<?php
								}
							?> 
						<?php } else{?>
						<span id='downloadable'> </span>
						<span class='company_logo_name'> </span>
						<?php } ?>
						
						</span>
					</div>
					
					<?php if (!empty($logo_price_array)) {?>
					<div class="tmm-step1">
						<div class="tmm-step1-logo-price">
							Logo
						</div>
						<div class="tmm-step1-logo-price presentation-d">
							Price
						</div>
						<?php foreach($logo_price_array as $logo_price) {  ?>
							<div class="tmm-step1-logo-price">
								<?php echo $logo_price->logo_count; ?>
							</div>
							<div class="tmm-step1-logo-price presentation-d">
								<?php echo $logo_price->price; ?>
							</div>
						<?php
						}
						?>	
					</div>
					<?php } ?>
				</div>
				
				
				
			</div>
			<?php if( isset( $_SESSION[ 'engraving_error' ]['logo'] ) ): ?>
				<div class='no_existing_img_alert errors_holder' style='text-aligm:center;color:red;width:100%;'> 
						<span> <?php echo $_SESSION[ 'engraving_error' ]['logo'];?> </span> 
				</div>
			<?php endif; ?>
			
			<div class="tmm-step1">
				<h3>For my orders, I would like to:</h3>
				<div class="tmm-step1-date">
					<label>Customer Date Required <span style="color:red;">*</span> </label>
					<input type="text" id="deliverydate" name="deliverydate" placeholder=" MM DD, YYYY" autocomplete="off" value="<?php echo $customer_date; ?>"/>
					<?php if( isset( $_SESSION[ 'engraving_error' ]['date'] ) ): ?>
					<div class='deliverydate_alert errors_holder' style='text-aligm:center;color:red;width:100%;'> <span> <?php echo $_SESSION[ 'engraving_error' ]['date'];?> </span> </div>
					<?php endif; ?>
				</div>
				<div class="tmm-step1-date presentation-d">
					<label>Presentation Date</label>
					<input type="text" id="presentationdate" name="presentationdate" placeholder=" MM DD, YYYY"  autocomplete="off"value="<?php echo $presentation_date; ?>" >
				</div>
			</div>
			<div class="tmm-button-outer">
					<button id="tmmengravingsettingssubmit" class="tmm-next" title="Next Step"  >Next Step &#x279D;</button>
			</div>
		 </div>
	</form>
</div>