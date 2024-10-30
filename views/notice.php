<?php if ( $type == 'plugin' ) :?>
<div class="updated" id="trophymonsta_setup_prompt">
	<form name="trophymonsta_activate" action="<?php echo esc_url( Trophymonsta_Admin::get_page_url() ); ?>" method="POST">
		<div class="trophymonsta_activate">
			<div class="aa_a">MM</div>
			<div class="aa_button_container">
				<div class="aa_button_border">
					<input type="submit" class="aa_button" value="<?php esc_attr_e( 'Set up your MonstaManagement account', 'trophymonsta' ); ?>" />
				</div>
			</div>
			<div class="aa_description"><?php _e('<strong>Almost Done</strong> - Configure MonstaManagement to import the products and start selling from your site.', 'trophymonsta');?></div>
		</div>
	</form>
</div>
<?php elseif ( $type == 'servers-be-down' ) :?>
<div class="trophymonsta-alert trophymonsta-critical">
	<h3 class="trophymonsta-key-status failed"><?php esc_html_e("Your site can&#8217;t connect to the MonstaManagement servers.", 'trophymonsta'); ?></h3>
	<p class="trophymonsta-description"><?php printf( __('Your firewall may be blocking MonstaManagement from connecting to its API. Please contact your host and refer to <a href="%s" target="_blank">our guide about firewalls</a>.', 'trophymonsta'), TROPHYMONSTA_API_URL.'trophymonsta-hosting-faq/'); ?></p>
</div>
<?php elseif ( $type == 'new-key-valid' ) :	?>
<div class="trophymonsta-alert trophymonsta-active">
	<h3 class="trophymonsta-key-status"><?php esc_html_e( 'MonstaManagement is now ready to import product to your site.', 'trophymonsta' ); ?></h3>
</div>
<?php elseif ( $type == 'new-key-invalid' ) :?>
<div class="trophymonsta-alert trophymonsta-critical">
	<h3 class="trophymonsta-key-status"><?php esc_html_e( 'The key you entered is invalid. Please double-check it.' , 'trophymonsta'); ?></h3>
</div>
<?php elseif ( $type == 'existing-key-invalid' ) :?>
<div class="trophymonsta-alert trophymonsta-critical">
	<h3 class="trophymonsta-key-status"><?php esc_html_e( 'Please enter a new key or contact support@monstamanagement.com.' , 'trophymonsta'); ?></h3>
</div>
<?php endif;?>
