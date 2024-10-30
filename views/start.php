<div id="trophymonsta-plugin-container">
	<div class="trophymonsta-masthead">
		<div class="trophymonsta-masthead__inside-container">
			<div class="trophymonsta-masthead__logo-container">
				<img class="trophymonsta-masthead__logo" src="<?php echo esc_url( plugins_url( '../_inc/img/monstamanagement.png', __FILE__ ) ); ?>" alt="Trophymonsta" />
			</div>
		</div>
	</div>
	<div class="trophymonsta-lower">
		<?php Trophymonsta_Admin::display_status(); ?>
		<div class="trophymonsta-box">
			<h2><?php esc_html_e( 'To Import and sell MonstaManagement products from your site', 'trophymonsta' ); ?></h2>
			<p><?php esc_html_e( 'Select one of the options below to get started.', 'trophymonsta' ); ?></p>
		</div>
		<div class="trophymonsta-boxes">
			<?php if ( ! Trophymonsta::predefined_api_key() ) { ?>
				<div class="trophymonsta-box">
					<h3><?php esc_html_e( 'Activate MonstaManagement' , 'trophymonsta' );?></h3>
					<div class="trophymonsta-right">
						<?php Trophymonsta::view( 'get', array( 'text' => __( 'Get your API key' , 'trophymonsta' ), 'classes' => array( 'trophymonsta-button', 'trophymonsta-is-primary' ) ) ); ?>
					</div>
					<p></p>
				</div>
				<div class="trophymonsta-box">
					<h3><?php esc_html_e( 'Or enter an API key', 'trophymonsta' ); ?></h3>
					<p><?php esc_html_e( 'Already have your key? Enter it here.', 'trophymonsta' ); ?> <a href="<?php echo TROPHYMONSTA_API_URL; ?>settings" target="_blank"><?php esc_html_e( '(What is an API key?)', 'trophymonsta' ); ?></a></p>
					<form action="<?php echo esc_url( Trophymonsta_Admin::get_page_url() ); ?>" method="post">
						<?php wp_nonce_field( Trophymonsta_Admin::NONCE ) ?>
						<input type="hidden" name="action" value="enter-key">
						<p style="width: 100%; display: flex; flex-wrap: nowrap; box-sizing: border-box;">
							<input id="key" name="key" type="text" size="15" value="" class="regular-text code" style="flex-grow: 1; margin-right: 1rem;">
							<input type="submit" name="submit" id="submit" style="background: #39bec7;color:#fff;" class="trophymonsta-button" value="<?php esc_attr_e( 'Connect with API key', 'trophymonsta' );?>">
						</p>
					</form>
				</div>
			<?php } else { ?>
				<div class="trophymonsta-box">
					<h2><?php esc_html_e( 'Manual Configuration', 'trophymonsta' ); ?></h2>
					<p><?php echo sprintf( esc_html__( 'An MonstaManagement API key has been defined in the %s file for this site.', 'trophymonsta' ), '<code>wp-config.php</code>' ); ?></p>
				</div>
			<?php } ?>
		</div>

	</div>


</div>
