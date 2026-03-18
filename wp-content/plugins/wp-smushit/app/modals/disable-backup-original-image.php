<?php
/**
 * Disable backup original images modal.
 *
 * @since 3.24.0
 * @package WP_Smush
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="sui-modal sui-modal-sm">
	<div
			role="dialog"
			id="smush-backup-original-images-dialog"
			class="sui-modal-content smush-backup-original-images-dialog"
			aria-modal="true"
			aria-labelledby="smush-backup-original-images-dialog-title"
			aria-describedby="smush-backup-original-images-dialog-description"
	>
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
				<h3 class="sui-box-title sui-lg">
					<?php esc_html_e( 'Turn off backups?', 'wp-smushit' ); ?>
				</h3>

				<button type="button" class="sui-button-icon sui-button-float--right" data-modal-close="">
					<i class="sui-icon-close sui-md" aria-hidden="true"></i>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this modal', 'wp-smushit' ); ?></span>
				</button>
			</div>

			<div class="sui-box-body sui-flatten sui-content-center sui-spacing-top--20 sui-spacing-bottom--50">
				<p class="sui-description">
					<?php esc_html_e( 'If you turn this off, Smush won’t save backups of your originals, and you won’t be able to restore them later.', 'wp-smushit' ); ?>
				</p>

				<div class="sui-block-content-center">
					<button type="button" class="sui-button sui-button-ghost" data-modal-close="">
						<?php esc_html_e( 'Cancel', 'wp-smushit' ); ?>
					</button>
					<button class="sui-button" onclick="document.getElementById('backup').checked=false;" data-modal-close="">
						<?php esc_html_e( 'Proceed', 'wp-smushit' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>
