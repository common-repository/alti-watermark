<?php
if( !is_admin() ) exit;
?>
<div id="wrap" class="<?php echo 'alti-watermark' ?>">
	<?php
		$plugin = new Alti_Watermark_Admin('alti-watermark', $this->version);
		$plugin->check_gd_library();
		$plugin->check_modrewrite();
		$plugin->check_previous_htaccess();
		$plugin->check_watermark_folder();
		$plugin->get_watermark_width();
		$plugin->check_uploads_url();
		if( isset($_POST['submit']) && check_admin_referer( 'submit_form', 'alti-watermark'. '_nonce' ) ) {
			$plugin->save_settings();
			$plugin->generate_htaccess();
		}
		$plugin->display_messages();
	?>
	<h2>Watermark <span><?php _e('by', 'alti-watermark'); ?> <a href="http://alticreation.com/en">alticreation.com</a></span></h2>
	<p class="description"><?php _e('Apply a watermark on all your photographies. This action is cancelable just by deactivating the plugin. <br>The watermark will be applied even in your photos already uploaded.', 'alti-watermark'); ?></p>
	<div class="alti-watermark-main-container">
	<form action="" method="POST" enctype="multipart/form-data">

		<?php wp_nonce_field( 'submit_form', 'alti-watermark'. '_nonce' ); ?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="size"><span class="dashicons dashicons-format-gallery"></span> <?php _e('Images format', 'alti-watermark'); ?></label>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text">
								<span><?php _e('Images format', 'alti-watermark'); ?></span>
							</legend>
							<?php foreach($plugin->get_image_sizes() as $image_size) { $plugin->render_image_sizes($image_size); } ?>
							<label for="size_fullsize">
								<input type="checkbox" value="fullsize" name="sizes[]" id="size_fullsize" <?php if( in_array('fullsize', $plugin->get_watermark_width()) ) { ?>checked<?php } ?>>
								<strong><?php _e('fullsize', 'alti-watermark'); ?></strong> <span class="small">(<?php _e('original image not resized by Wordpress', 'alti-watermark'); ?>)</span>
							</label>
						</fieldset>

					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="watermarkFile"><span class="dashicons dashicons-tag"></span> <?php _e('Choose a watermark', 'alti-watermark'); ?></label>
					</th>
					<td>
						<input type="file" name="watermarkFile" id="watermarkFile">
						<p class="description">*<?php _e('For transparent background watermark, use a PNG image.', 'alti-watermark'); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for=""><span class="dashicons dashicons-welcome-view-site"></span> <?php _e('Preview', 'alti-watermark'); ?></label>
					</th>
					<td>
						<?php $watermark = getimagesize(WP_PLUGIN_URL . '/' . 'alti-watermark' . '-data' . '/watermark.png?' . rand(1,10000)); ?>
						<?php if($watermark[0] > 200) { $width = '200'; } else { $width = $watermark[0]; } ?>
						<img class="watermark" width="<?php echo $width; ?>" src="<?php echo WP_PLUGIN_URL . '/' . 'alti-watermark' . '-data' . '/watermark.png?' . rand(1,10000); ?>" alt="">
						<p class="description"> <?php _e('Real size', 'alti-watermark'); ?> : <?php $watermarkSize = $this->get_watermark_size(); echo $watermarkSize[0]; ?> x <?php echo $watermarkSize[1]; ?>px</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for=""><span class="dashicons dashicons-welcome-learn-more"></span> <?php _e('Support', 'alti-watermark'); ?></label>
					</th>
					<td>
						<p><?php _e('alti Watermark Plugin <a href="http://www.alticreation.com/en/alti-watermark/" target="_blank">support page</a>.', 'alti-watermark'); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
					</th>
					<td>
						<input type="submit" id="submit" value="<?php _e('Update', 'alti-watermark'); ?>" name="submit" class="button button-primary">
					</td>
				</tr>
			</tbody>
		</table>
	</form>
	</div>

	<?php require_once dirname( __FILE__ ) . '/includes/alti-watermark-admin-sidebar.php'; ?>
</div>

