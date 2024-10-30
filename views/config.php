<div id="trophymonsta-plugin-container">
	<div class="trophymonsta-masthead">
		<div class="trophymonsta-masthead__inside-container">
			<div class="trophymonsta-masthead__logo-container">
				<img class="trophymonsta-masthead__logo" src="<?php echo esc_url( plugins_url( '../_inc/img/monstamanagement.png', __FILE__ ) ); ?>" alt="Trophymonsta" />
			</div>
		</div>
	</div>
	<div class="trophymonsta-lower">
		<?php if ( Trophymonsta::get_api_key() ) { ?>
			<?php Trophymonsta_Admin::display_status(); ?>
		<?php } ?>
		<?php if ( ! empty( $notices ) ) { ?>
			<?php foreach ( $notices as $notice ) { ?>
				<?php Trophymonsta::view( 'notice', $notice ); ?>
			<?php } ?>
		<?php } ?>
		<?php if ( $trophymonsta_user ):?>
			<div class="trophymonsta-card">
				<div class="trophymonsta-section-header">
					<div class="trophymonsta-section-header__label">
						<span><?php esc_html_e( 'Settings' , 'trophymonsta'); ?></span>
					</div>
				</div>

				<div class="inside">
					<form action="<?php echo esc_url( Trophymonsta_Admin::get_page_url() ); ?>" method="POST">
						<table cellspacing="0" class="trophymonsta-settings">
							<tbody>
								<?php if ( ! Trophymonsta::predefined_api_key() ) { ?>
								<tr>
									<td class="trophymonsta-api-key" width="15%" align="left" scope="row"><?php esc_html_e('API Key', 'trophymonsta');?></td>
									<td width="5%"/>
									<td align="left">
										<span class="api-key"><input id="key" name="key" type="text" size="15" value="<?php echo esc_attr( get_option('trophymonsta_api_key') ); ?>" class="<?php echo esc_attr( 'regular-text code '); ?>"></span>
									</td>
								</tr>
								<?php } ?>

								<?php if ( isset( $_GET['ssl_status'] ) ) { ?>
									<tr>
										<th align="left" scope="row"><?php esc_html_e( 'SSL Status', 'trophymonsta' ); ?></th>
										<td></td>
										<td align="left">
											<p>
												<?php
												if ( ! wp_http_supports( array( 'ssl' ) ) ) {
													?><b><?php esc_html_e( 'Disabled.', 'trophymonsta' ); ?></b> <?php esc_html_e( 'Your Web server cannot make SSL requests; contact your Web host and ask them to add support for SSL requests.', 'trophymonsta' ); ?><?php
												}
												else {
													$ssl_disabled = get_option( 'trophymonsta_ssl_disabled' );

													if ( $ssl_disabled ) {
														?><b><?php esc_html_e( 'Temporarily disabled.', 'trophymonsta' ); ?></b> <?php esc_html_e( 'MonstaManagement encountered a problem with a previous SSL request and disabled it temporarily. It will begin using SSL for requests again shortly.', 'trophymonsta' ); ?><?php
													}
													else {
														?><b><?php esc_html_e( 'Enabled.', 'trophymonsta' ); ?></b> <?php esc_html_e( 'All systems functional.', 'trophymonsta' ); ?><?php
													}
												}
												?>
											</p>
										</td>
									</tr>
								<?php } ?>

							</tbody>
						</table>
						<div class="trophymonsta-card-actions">
							<?php wp_nonce_field(Trophymonsta_Admin::NONCE) ?>
							<div id="publishing-action">
								<input type="hidden" name="action" value="enter-key">
								<input type="submit" name="submit" id="submit" style="background: #39bec7;color:#fff;" class="trophymonsta-button trophymonsta-could-be-primary" value="<?php esc_attr_e('Save Changes', 'trophymonsta');?>">
							</div>
							<div class="clear"></div>
						</div>
					</form>
				</div>
			</div>

			<?php if ( ! Trophymonsta::predefined_api_key() ) { ?>
				<div class="trophymonsta-card">
					<div class="trophymonsta-section-header">
						<div class="trophymonsta-section-header__label">
							<span><?php esc_html_e( 'Account' , 'trophymonsta'); ?></span>
						</div>
					</div>
					<div class="inside">
						<table cellspacing="0" border="0" class="trophymonsta-settings data-left">
							<tbody>
								<tr>
									<td width="25%" scope="row" align="left"><?php esc_html_e( 'Subscription Type' , 'trophymonsta');?></td>
									<td width="5%"/>
									<td align="left">
										<p><?php echo esc_html( ucfirst($trophymonsta_user->subscription->type) ); ?></p>
									</td>
								</tr>
								<tr>
									<td width="25%" scope="row" align="left"><?php esc_html_e( 'Sync Web orders' , 'trophymonsta');?></td>
									<td width="5%"/>
									<td align="left">
										<p><?php if($trophymonsta_user->subscription->allow_web_order==1) echo 'Yes'; else echo 'No'; ?></p>
									</td>
								</tr>
								<tr>
									<td width="25%" scope="row" align="left"><?php esc_html_e( 'Plan Expiry Date' , 'trophymonsta');?></td>
									<td width="5%"/>
									<td align="left">
										<p><?php if($trophymonsta_user->subscription->expiryDate!='0000-00-00 00:00:00') echo date_format( date_create( $trophymonsta_user->subscription->expiryDate ), "d-m-Y H:i:s" ); ?></p>
									</td>
								</tr>
								<tr>
									<td width="25%" scope="row" align="left"><?php esc_html_e( 'Status' , 'trophymonsta');?></td>
									<td width="5%"/>
									<td align="left">
										<p><?php
											//echo '<pre>';print_r($trophymonsta_user);
											if ( 'cancelled' == $trophymonsta_user->subscription->status ) :
												esc_html_e( 'Cancelled', 'trophymonsta' );
											elseif ( 'inactive' == $trophymonsta_user->subscription->status ) :
												esc_html_e( 'Inactive', 'trophymonsta' );
											elseif ( 'extend' == $trophymonsta_user->subscription->status ) :
												esc_html_e( 'Extend', 'trophymonsta' );
											elseif ( 'free' == $trophymonsta_user->subscription->status ) :
												esc_html_e( 'Free', 'trophymonsta' );
											else :
												esc_html_e( 'Active', 'trophymonsta' );
											endif; ?></p>
									</td>
								</tr>
								<?php if ($trophymonsta_user->subscription->expiryDate && 'free' != $trophymonsta_user->subscription->status
								&& 'extend' != $trophymonsta_user->subscription->status && 'cancelled' != $trophymonsta_user->subscription->status
								&& 'inactive' != $trophymonsta_user->subscription->status) {
								?>
								<tr>
									<th width="25%" scope="row" align="left"><?php esc_html_e( 'Next Billing Date' , 'trophymonsta');?></th>
									<td width="5%"/>
									<td align="left">
										<p><?php echo date( 'F j, Y', strtotime($trophymonsta_user->subscription->expiryDate) ); ?></p>
									</td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
        <?php if ('cancelled' != $trophymonsta_user->subscription->status && 'inactive' != $trophymonsta_user->subscription->status) { ?>
				<div class="trophymonsta-card">
					<div class="trophymonsta-section-header" style="background: #144444;color:#fff;">
						<div class="trophymonsta-section-header__label">
							<span style="color:#fff;"><b><?php esc_html_e( 'Status' , 'trophymonsta'); ?></b></span>
						</div>
						<form action="<?php echo esc_url( Trophymonsta_Admin::get_page_url() ); ?>" method="POST">
						<?php wp_nonce_field(Trophymonsta_Admin::NONCE) ?>
						<div id="publishing-action">
							<?php
							if (empty($import_logs) && $import_cron_logs == '0') {
								?>
								<input type="hidden" name="action" value="import-all"> <!--  import-all fortest --->
								<input type="submit" name="submit" id="submit" class="trophymonsta-button trophymonsta-could-be-primary" value="<?php	esc_attr_e('Import Products', 'trophymonsta');	?>">
								<?php
							} else {
								if (isset( $last_sync_date ) && $last_sync_date != '' && $import_cron_logs == '0') {
										$date = date_create( $last_sync_date );
										$display_date = date_format( $date, "d-m-Y H:i:s" );
										?>
										<label style="cursor:default;"> <b><?php esc_html_e( 'Last Synchronised on' , 'trophymonsta');?></b> <?php echo $display_date; ?></label>
										<?php if ($synchronise_button_display == '1') { ?>
										<input type="hidden" name="action" value="import-all">
										<input type="submit" name="submit" id="submit" class="trophymonsta-button trophymonsta-could-be-primary" value="<?php esc_attr_e('Synchronise Products', 'trophymonsta');	?>">
									<?php }
								} else if ($import_cron_logs == '1') { ?>
									<label id="last_synchronised_status" style="display:block;"><?php esc_attr_e('Synchronise is in progress', 'trophymonsta');	?></label>
									<div id="myProgress" style="width: 100%; background-color: grey;position:relative;display:block;" class="progress-outer">
									  <div id="myBar" style="width: 1%; height: 30px; background-color: green;"><span id="import_type" style="position: absolute;top: 50%;text-align: center;left: 50%;transform: translate(-50%,-50%);"></span></div>
									</div>

								<?php
								}
							}
							?>
						</div>

						<div class="clear"></div>
						</form>
					</div>

					<div class="inside">
						<?php if (!empty($import_logs)) { ?>
						<table id="trophymonsta-settings" cellspacing="0" cellpadding="0" border="0" bordercolor="#39bec7" style="border: 1px solid #39bec7;" class="trophymonsta-settings">
							<tbody >
								<tr style="background: #39bec7;color:#fff;"><!-- #144444 -->
									<th scope="row" align="left"><?php esc_html_e( 'Modules' , 'trophymonsta');?></th>
									<th scope="row" align="left"><?php esc_html_e( 'Synced Date' , 'trophymonsta');?></th>
									<th scope="row" align="left"><?php esc_html_e( 'Created' , 'trophymonsta');?></th>
									<!--th scope="row" align="left"></?php esc_html_e( 'Updated' , 'trophymonsta');?></th>
									<th scope="row" align="left"></?php esc_html_e( 'Deleted' , 'trophymonsta');?></th-->
									<th scope="row" align="left"><?php esc_html_e( 'Status' , 'trophymonsta');?></th>
									<!--th scope="row" align="left"></?php esc_html_e( 'Action' , 'trophymonsta');?></th-->
								</tr>
								<?php
								$table_row = 0;
								foreach ( $import_logs as $log )
								{ $colour = ($table_row%2 == 0)? '#fff': '#eefeff';?>
								<tr id="_<?php echo esc_html( $log->type ); ?>" style="background: <?php echo $colour;?>">
									<td align="left">
										<p><?php echo esc_html( ucfirst($log->type) ); ?></p>
									</td>
									<td align="left">
										<p id="<?php echo esc_html( $log->type ); ?>_sync"><?php echo date('d-m-Y H:i:s', strtotime( $log->sync )); ?></p>
									</td>
									<td align="left">
										<p id="<?php echo esc_html( $log->type ); ?>_create_count"><?php echo esc_html( $log->create_count ); ?></p>
									</td>
									<!--td align="left">
										<p id="</?php echo esc_html( $log->type ); ?>_update_count"></?php echo esc_html( $log->update_count ); ?></p>
									</td>
									<td align="left">
										<p id="</?php echo esc_html( $log->type ); ?>_delete_count"></?php echo esc_html( $log->delete_count ); ?></p>
									</td-->
									<td align="left">
										<p id="<?php echo esc_html( $log->type ); ?>_status"><?php echo esc_html( $log->status ); ?></p>
									</td>
									<!--td align="left">
										</?php
											$startTime = date("Y-m-d H:i:s");
											$cenvertedTime = date('Y-m-d H:i:s',strtotime('-5 minutes',strtotime($startTime)));
										if (($cenvertedTime >= date('Y-m-d H:i:s',strtotime($log->sync))) && $log->status != 'Completed' && $log->type != 'product') {  //&& $synchronise_button_display == '1' ?>
											<form action="</?php echo esc_url( Trophymonsta_Admin::get_page_url() ); ?>" method="POST">
											</?php wp_nonce_field(Trophymonsta_Admin::NONCE) ?>
											<div id="publishing-action">
											<input type="hidden" name="action" value="import-</?php echo esc_html( $log->type ); ?>">
											<input type="hidden" name="page" value="</?php echo esc_html( $log->page ); ?>">
											<input type="submit" name="submit" id="submit" class="trophymonsta-button trophymonsta-could-be-primary" value="</?php	esc_attr_e('Re-Sync', 'trophymonsta'); ?>">
											</div>
											<div class="clear"></div>
											</form>
									 </?php } ?>

								 </td-->
								</tr>
							<?php $table_row++; } ?>
							<tr><td colspan="4">
							    <form action="<?php echo esc_url( Trophymonsta_Admin::get_page_url() ); ?>" method="POST">
        						<?php wp_nonce_field(Trophymonsta_Admin::NONCE) ?>
        						<div id="publishing-action">
        							<input type="hidden" name="action" value="transientsposts"> <!--  import-all fortest --->
        							<input type="submit" name="submit" id="submit" class="trophymonsta-button trophymonsta-could-be-primary" value="<?php	esc_attr_e('Update Product Transients', 'trophymonsta');	?>">
        						</div>
        
        						<div class="clear"></div>
        						</form>
							</td></tr>
							</tbody>
						</table>
					<?php } else { ?>
						<table id="trophymonsta-settings" cellspacing="0" cellpadding="0" border="1" bordercolor="#39bec7" style="border: 1px solid #39bec7;" class="trophymonsta-settings">
							<tbody >
								<tr style="background: #39bec7;color:#fff;">
									<th scope="row" align="left"><?php esc_html_e( 'Modules' , 'trophymonsta');?></th>
									<th scope="row" align="left"><?php esc_html_e( 'Synced Date' , 'trophymonsta');?></th>
									<th scope="row" align="left"><?php esc_html_e( 'Created' , 'trophymonsta');?></th>
									<!--th scope="row" align="left"></?php esc_html_e( 'Updated' , 'trophymonsta');?></th>
									<th scope="row" align="left"></?php esc_html_e( 'Deleted' , 'trophymonsta');?></th-->
									<th scope="row" align="left"><?php esc_html_e( 'Status' , 'trophymonsta');?></th>
									<!--th scope="row" align="left"></?php esc_html_e( 'Action' , 'trophymonsta');?></th-->
								</tr>
							</tbody>
						</table>
					<?php } ?>
					</div>
				</div>
			<?php }
			    } ?>
		<?php endif;?>
	</div>
</div>
<script type="text/javascript">

importProgress();
setInterval(importProgress, 300000); //300000
function importProgress() {
	if (document.getElementById("myBar") === null )
		return false;

	jQuery.get('admin-ajax.php',{ action: "import_status"}, function(data) {
		var noinprogress = true;
		var lastsyncdate = '';
		jQuery.each(data, function(index, log) {

			if (log.type == 'product')
				var totalpage = Math.ceil(log.total_count/5);
			else //if (log.type == 'accessories')
				var totalpage = log.total_count;
			//else
				//var totalpage = Math.ceil(log.total_count/200);

			if (totalpage == 0)
				totalpage = 1;
				var module_name = log.type;
				var modulename 	= module_name.charAt(0).toUpperCase() + module_name.slice(1).toLowerCase();
			if (jQuery('#_'+log.type).length == 0 ) {
				var markup = '<tr id="_'+log.type+'"><td align="left"><p>'+modulename+'</p></td>';
				markup += '<td align="left"><p id="'+log.type+'_sync">'+log.sync+'</p></td>';
				markup += '<td align="left"><p id="'+log.type+'_create_count">'+log.create_count+'</p></td>';
				//markup += '<td align="left"><p id="'+log.type+'_update_count">'+log.update_count+'</p></td>';
				//markup += '<td align="left"><p id="'+log.type+'_delete_count">'+log.delete_count+'</p></td>';
				markup += '<td align="left"><p id="'+log.type+'_status">'+log.status+'</p></td></tr>';
				jQuery("#trophymonsta-settings").append(markup);
			}

			if (log.status == 'In progress') {
				jQuery('#import_type').text(log.type);
				var sync = new Date(log.sync);
				jQuery('#'+log.type+'_sync').text(log.sync);
				jQuery('#'+log.type+'_create_count').text(log.create_count);
				//jQuery('#'+log.type+'_update_count').text(log.update_count);
				//jQuery('#'+log.type+'_delete_count').text(log.delete_count);
				jQuery('#'+log.type+'_status').text(log.status);

				/*if (log.page == totalpage) {
					jQuery("#myProgress").hide();
					jQuery("#last_synchronised_status").html("<b>Last Synchronised on</b> "+ log.sync);
				} else {
					jQuery("#myProgress").show();
					jQuery("#myBar").css("width", Math.ceil((log.page/totalpage) * 100)+'%');
				}*/

				if (((log.type == 'grouping' || log.type == 'accessories' || log.type == 'brand' || log.type == 'processes' || log.type == 'noprocesses' || log.type == 'material')  && parseInt(log.create_count) <= parseInt(totalpage) )) {
					jQuery("#myProgress").show();
					jQuery("#myBar").css("width", Math.ceil((parseInt(log.create_count)/parseInt(totalpage)) * 100)+'%');
				} else if ((log.type == 'product' && parseInt(log.page) <= parseInt(totalpage))) {
					jQuery("#myProgress").show();
					jQuery("#myBar").css("width", Math.ceil((parseInt(log.page)/parseInt(totalpage)) * 100)+'%');
				} else {
					jQuery("#myProgress").hide();
					jQuery("#last_synchronised_status").html("<b>Last Synchronised on</b> "+ log.sync);

				}

				noinprogress = false;
			} else {
				var sync = new Date(log.sync);
				jQuery('#'+log.type+'_sync').text(log.sync);
				jQuery('#'+log.type+'_create_count').text(log.create_count);
				//jQuery('#'+log.type+'_update_count').text(log.update_count);
				//jQuery('#'+log.type+'_delete_count').text(log.delete_count);
				jQuery('#'+log.type+'_status').text(log.status);
				lastsyncdate = log.sync;
			}

		});
		if (noinprogress){
			jQuery("#myProgress").hide();
			jQuery("#last_synchronised_status").html("<b>Last Synchronised on</b> "+ lastsyncdate);
		}
   });

}
</script>
