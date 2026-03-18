<?php
/**
 * Disconnect site modal.
 *
 * @since 3.22.0
 * @package Smush
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="sui-modal sui-modal-sm">
	<div
			role="dialog"
			id="smush-disconnect-site-modal"
			class="sui-modal-content smush-disconnect-site-modal"
			aria-modal="true"
			aria-labelledby="smush-disconnect-site-modal-title"
	>
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
				<button class="sui-button-icon sui-button-float--right" id="dialog-close-div" data-modal-close="">
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this dialog window', 'wp-smushit' ); ?></span>
				</button>
				<h3 id="smush-disconnect-site-modal-title" class="sui-box-title sui-lg" style="white-space: inherit">
					<?php esc_html_e( 'Disconnect Site?', 'wp-smushit' ); ?>
				</h3>
				<p class="sui-description">
					<?php esc_html_e( 'Disconnecting this site will disable key Bulk Smush features and other connected WPMU DEV tools and services.', 'wp-smushit' ); ?>
				</p>
			</div>

			<div class="sui-box-body sui-spacing-top--20">
				<div class="smush-disconnect-notice">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<h4><?php esc_html_e( 'You’ll lose the following key Smush features:', 'wp-smushit' ); ?></h4>
							<ul>
								<li><span class="sui-icon-cross-close" aria-hidden="true"></span><?php esc_html_e( 'Bulk Smush', 'wp-smushit' ); ?></li>
								<li><span class="sui-icon-cross-close" aria-hidden="true"></span><?php esc_html_e( 'Image Optimization', 'wp-smushit' ); ?></li>
								<li><span class="sui-icon-cross-close" aria-hidden="true"></span><?php esc_html_e( 'Automatic Compression', 'wp-smushit' ); ?></li>
								<li style="display:flex"><span class="sui-icon-cross-close" aria-hidden="true"></span><?php esc_html_e( 'Premium WPMU DEV services and site management tools', 'wp-smushit' ); ?></li
							</ul>
						</div>
					</div>
				</div>
				<div class="sui-form-field" style="margin-top: 20px;">
					<textarea
						placeholder="<?php esc_attr_e( 'Mind sharing why you’re disconnecting?', 'wp-smushit' ); ?>"
						id="smush-disconnect-site-message"
						class="sui-form-control"
						style="height:40px"
					></textarea>
				</div>
			</div>
			<div class="sui-box-footer sui-flatten sui-content-center sui-spacing-bottom--40">
				<button type="button" class="sui-button sui-button-ghost" data-modal-close=""><?php esc_html_e( 'Cancel', 'wp-smushit' ); ?></button>

				<button type="button" class="sui-button sui-button-gray" onclick="WP_Smush.adminAjax.disconnectSite(this);">
					<span class="sui-button-text-default">
						<span class="sui-icon-plug-disconnected" aria-hidden="true"></span>
						<?php esc_html_e( 'Disconnect site', 'wp-smushit' ); ?>
				</span>
					<span class="sui-button-text-onload">
						<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
						<?php esc_html_e( 'Disconnect site', 'wp-smushit' ); ?>
					</span>
				</button>
			</div>
		</div>
	</div>
</div>
