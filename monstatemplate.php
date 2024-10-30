<?php
/**
Template name: monstatemplate
*/
//  contact  form  content  goes  here. ( contact.php)

get_header();

$trophyProductInfo = Monstaengravings::trophyProducts();
if(isset( $trophyProductInfo['trophyProductExist'] ) && $trophyProductInfo['trophyProductExist'] == false ){
	$checkout = get_permalink( get_page_by_path( 'checkout' ) );
	 ?>
	<script>
		window.location.href = '<?php echo $checkout; ?>';
	</script>
	<?php
 exit;
}

$site_url = site_url().'/';
$url = home_url( $wp->request );
$link = str_replace($site_url,'',$url);
$step1 = get_permalink( get_page_by_path( 'monsta-engravings-settings' ) );
$step2 = get_permalink( get_page_by_path( 'monsta-engravings-details' ) );
$step3 = get_permalink( get_page_by_path( 'monsta-engravings-review' ) );
$from_email = isset($_SESSION[ 'enter_engraving_details_email' ]) ? $_SESSION[ 'enter_engraving_details_email' ] : '';
$no_engraving = isset($_SESSION[ 'no_engraving_details' ]) ? $_SESSION[ 'no_engraving_details' ] : '';
$styles='';
if( $from_email != '' || $no_engraving != '' ){
	$styles = 'pointer-events: none;';
}
if( isset($_POST['tmmengravingemail']) && $_POST['tmmengravingemail'] != '' ){
	$styles = 'pointer-events: none;';
}
if( isset($_POST['tmmnoengraving']) && $_POST['tmmnoengraving'] != '' ){
	$styles = 'pointer-events: none;';
}
if (  !is_user_logged_in()  ) {
	$styles = null;
	//$redirectTo = wp_login_url().'?redirect_to=monsta-engravings-settings';
}
 ?>
<div class="tmm-bg">
	<div class="tmm-container">
		<h2>Engraving</h2>
		<section class="design-process-section" id="process-tab">
				<!-- design process steps-->
				<!-- Nav tabs -->
				<ul class="nav nav-tabs process-model more-icon-preocess" role="tablist">
					<li role="presentation" <?php if($link == 'monsta-engravings-settings' ) { ?>class="active"<?php } ?> id="step_1" >
						<a href="#settings" aria-controls="settings" role="tab" data-toggle="tab"><i aria-hidden="true" onclick="redirectUrl(this,'<?php echo $step1; ?>');">1</i>
							<i class="fa-check"></i>
							<p>Settings</p>
						</a>
					</li>
					<li role="presentation" style="<?php echo $styles; ?>" <?php if($link == 'monsta-engravings-details' ) { ?>class="active"<?php } ?> id="step_2" >
						<a   href="#details" aria-controls="details" role="tab" data-toggle="tab" data-link="<?php echo $step2; ?>" ><i aria-hidden="true"  onclick="stepSubmit(this,2);"  >2</i>
							<i class="fa-check"></i>
							<p>Details</p>
						</a>
					</li>
					<li role="presentation" <?php if($link == 'monsta-engravings-review' ) { ?>class="active"<?php } ?> id="step_3" >
						<a href="#review" aria-controls="review" role="tab" data-toggle="tab" data-link="<?php echo $step3; ?>" ><i aria-hidden="true" onclick="stepSubmit(this,3);"  >3</i>
							<i class="fa-check"></i>
							<p>Review</p>
						</a>
					</li>
				</ul>
				<!-- end design process steps-->
				<!-- Tab panes -->
				<div class="tab-content">
				<!-- tab 1 -->
				<?php
				while ( have_posts() ) :
					the_post();

					//get_template_part( 'content', 'page' );
					the_content();

				endwhile; // End of the loop.
				?>


				</div>

		</section>
	</div>
</div>
<script>
jQuery( document ).ready(function() {
   jQuery('.tmm-detail-slider-outer').css({ 'width': 'calc(100% - ' + 40+ 'px)' });
});
</script>
<?php get_footer(); ?>
