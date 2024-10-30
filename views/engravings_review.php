<?php 
	$form_url = get_site_url()."/checkout";
	$presentationdate = isset( $_SESSION[ 'engraving_setting_presentation_date' ] ) ?  $_SESSION[ 'engraving_setting_presentation_date' ] : '';
	$deliverydate = isset( $_SESSION[ 'engraving_setting_customer_date' ] ) ? $_SESSION[ 'engraving_setting_customer_date' ] : '';
?>
<div role="tabpanel" class="tab-pane active tmm-review-tab tmm-tab-3" id="review">
	<form id="tmmengravingreview" method="post" name="tmmengravingsettings" enctype="multipart/form-data" action="<?php echo $form_url; ?>"    >
	<input type="hidden" value="1" name="engraving_completion" />
		<div class="design-process-content">
			<div class="tmm-step1">
				<h3 class="tmm-step3-single">
					<span>For engravings, I would like to:</span>
					<span class="tmm-inputcontainer-outer">
					<?php if( isset( $_SESSION[ 'enter_engraving_details_online' ] )  && $_SESSION[ 'enter_engraving_details_online' ]  != '' ) { ?>
						<label class="tmm-inputcontainer">Enter my engraving details online 
							<input type="checkbox" checked="checked" disabled > <span class="checkmark"></span>
						</label>
					<?php }else if( isset( $_SESSION[ 'enter_engraving_details_email' ] ) && $_SESSION[ 'enter_engraving_details_email' ] != '' ){ ?>
							<label class="tmm-inputcontainer">Email engraving details later 
							<input type="checkbox" checked="checked" disabled > <span class="checkmark"></span>
						</label>
					<?php }else if( isset( $_SESSION[ 'no_engraving_details' ] ) && $_SESSION[ 'no_engraving_details' ] != '' ){ ?>
						<label class="tmm-inputcontainer">Forgo any engraving, I dont't need it 
							<input type="checkbox" checked="checked" disabled > <span class="checkmark"></span>
						</label>
					<?php } ?>
					</span>
				</h3>
			</div>
		<?php if( isset( $_SESSION[ 'enter_engraving_details_online' ] )  && $_SESSION[ 'enter_engraving_details_online' ]  != '' ) { ?>
			<div class="tmm-step1">
				<h3 class="tmm-step3-single">
					<span>For logo, I would like to:</span>
					<span class="tmm-inputcontainer-outer">
					<?php if( isset(  $_SESSION[ 'engraving_setting_uploaded_id' ] ) &&  $_SESSION[ 'engraving_setting_uploaded_id' ] != '' ) {?>
						<label class="tmm-inputcontainer">Upload a company logo for my engravings 
							<input type="checkbox" checked="checked" disabled >
								<span class="checkmark"></span>
						</label>
					<?php }else if( isset(  $_SESSION[ 'engraving_setting_existing_logo' ] ) &&  $_SESSION[ 'engraving_setting_existing_logo' ] != ''  ){ ?>
						<label class="tmm-inputcontainer">Use an existing logo, I am already a customer
							<input type="checkbox" checked="checked" disabled >
								<span class="checkmark"></span>
						</label>
					<?php }else if( isset( $_SESSION[ 'engraving_setting_forgo_logo' ] ) && $_SESSION[ 'engraving_setting_forgo_logo' ] != '' ) { ?>
							<label class="tmm-inputcontainer">Forgo any engraving, I dont't need it
								<input type="checkbox" checked="checked" disabled>
								<span class="checkmark"></span>
							</label>
					<?php } ?>
					</span>
					<?php if( count($trophy_logo_attacment) > 0 ) { ?>
					<span class="tmm-img-name">
					<!--<img src="<?php  //echo $trophy_logo_attacment;  ?>">  <?php //echo basename($trophy_logo_attacment); ?> -->
					<?php
					 foreach($trophy_logo_attacment as $k =>$logo ){
					?>
						<a href="<?php  echo $logo;  ?>" download > <?php echo basename($logo); ?> </a><br>
						<?php
					 }
					?>
					</span>
					<?php } ?>
				</h3>
			</div>
		<?php } ?>
			<div class="tmm-step1 tmm-order">
					<h3 class="padding_corrt">For MY Order, I would like to:</h3>
					<div class="tmm-step1-date">
						<label><!--Recieve by--> Customer Date <em style="color:red;">*</em></label>
						<input type="text" placeholder=" MM DD, YYYY"  value=" <?php echo $deliverydate; ?>"   name="receivedby" autocomplete="off" readonly >
					</div>
					<div class="tmm-step1-date">
						<label><!--Present by-->  Presentation Date   </label>
						<input type="text" placeholder=" MM DD, YYYY" value="<?php echo $presentationdate; ?>"   name="presentedby" autocomplete="off" readonly>
					</div>
				
			</div>

			<div class="tmm-button-outer tab-3">
				<button class="tmm-prev" onclick="return redirectUrl(this,'<?php echo $previous_page; ?>');" title="Prev Step"><big>&#x279D;</big> Prev Step</button>
				<button class="tmm-next"  title="Place My Order">Place My Order &#x279D;</button>
			</div>

		 </div>
 </form>
		</div>