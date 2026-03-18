<?php
/**
 * Restore images modal.
 *
 * @since 3.2.2
 * @package WP_Smush
 *
 * @var $restore_image_count   int Restore Image count.
 */
use Smush\Core\Helper;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$backup_link       = '<a onclick="window.location.reload();" href="' . esc_url( Helper::get_page_url( 'smush-bulk#backup' ) ) . '"><strong>';
$backup_link_close = '</strong></a>';
?>

<script type="text/template" id="smush-bulk-restore">
	<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
		<# if ( 'progress' === data.slide ) { #>
			<i class="sui-icon-loader sui-loading sui-lg" aria-hidden="true"></i>
		<# } else if ( 'finish' === data.slide ) { #>
			<i class="sui-icon-check sui-lg" aria-hidden="true"></i>
		<# } #>
		<h3 class="sui-box-title sui-lg" id="smush-restore-images-dialog-title">
			<# if ( 'start' === data.slide ) { #>
			<?php esc_html_e( 'Restore all images?', 'wp-smushit' ); ?>
			<# } else if ( 'progress' === data.slide ) { #>
			<?php esc_html_e( 'Restoring images...', 'wp-smushit' ); ?>
			<# } else if ( 'finish' === data.slide ) { #>
			<?php esc_html_e( 'Restore complete', 'wp-smushit' ); ?>
			<# } #>
		</h3>

		<button class="sui-button-icon sui-button-float--right" onclick="WP_Smush.restore.cancel()">
			<i class="sui-icon-close sui-md" aria-hidden="true"></i>
			<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this modal', 'wp-smushit' ); ?></span>
		</button>
	</div>

	<div class="sui-box-body sui-flatten sui-content-center sui-spacing-top--20 sui-spacing-bottom--50">
		<p class="sui-description" id="smush-restore-images-dialog-description">
			<# if ( 'start' === data.slide ) { #>
			<?php printf(
                /* translators: %s: strong tag */
				esc_html__( 'This will restore all %1$s %2$s  images%3$s thumbnails to their original, non-optimized states. Want more control? Use the Media Library to restore specific images.', 'wp-smushit' ),
                '<strong>',
				$restore_image_count,
                '</strong>'
            ); ?>
			<# } else if ( 'progress' === data.slide ) { #>
			<?php esc_html_e( 'Your bulk restore is still in progress, please leave this tab open while the process runs.', 'wp-smushit' ); ?>
			<# } else if ( 'finish' === data.slide ) { #>
			<?php esc_html_e( 'Your bulk restore has finished running.', 'wp-smushit' ); ?>
			<# } #>
		</p>

		<div class="sui-block-content-center">
			<# if ( 'start' === data.slide ) { #>
			<button class="sui-button sui-button-ghost" onclick="WP_Smush.restore.cancel()" data-modal-close="">
				<?php esc_html_e( 'Cancel', 'wp-smushit' ); ?>
			</button>
			<button class="sui-button" id="smush-bulk-restore-button">
				<?php esc_html_e( 'Restore', 'wp-smushit' ); ?>
			</button>
			<# } else if ( 'progress' === data.slide ) { #>
			<div class="sui-progress-block sui-progress-can-close">
				<div class="sui-progress">
					<span class="sui-progress-icon" aria-hidden="true">
						<i class="sui-icon-loader sui-loading"></i>
					</span>
					<div class="sui-progress-text">
						<span>0%</span>
					</div>
					<div class="sui-progress-bar" aria-hidden="true">
						<span style="width: 0"></span>
					</div>
				</div>
				<button class="sui-button-icon sui-tooltip" onclick="WP_Smush.restore.cancel()" type="button" data-tooltip="<?php esc_attr_e( 'Cancel', 'wp-smushit' ); ?>">
					<i class="sui-icon-close"></i>
				</button>
			</div>

			<div class="sui-progress-state">
				<span class="sui-progress-state-text">
					<?php esc_html_e( 'Initializing restore...', 'wp-smushit' ); ?>
				</span>
			</div>
			<# } else if ( 'finish' === data.slide ) { #>
				<# if ( 0 === data.errors.length ) { #>
				<div class="sui-notice sui-notice-success" style="text-align: left">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<i class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></i>
							<p>{{{ data.success }}}
								<?php esc_html_e( 'images were successfully restored.', 'wp-smushit' ); ?>
							</p>
						</div>
					</div>
				</div>
				<button class="sui-button" onclick="window.location.reload()" data-modal-close="" type="button">
					<?php esc_html_e( 'Finish', 'wp-smushit' ); ?>
				</button>
				<# } else { #>
				<div class="sui-notice sui-notice-warning" style="text-align: left">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<i class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></i>
							<p>{{{ data.success }}}/{{{ data.total }}}
								<?php esc_html_e( 'images were restored successfully.', 'wp-smushit' ); ?>
								<# if ( data.missingBackupCount > 0 && data.errorCopyCount > 0 ) { #>
									<?php echo ' '; ?>{{ data.missingBackupCount }}
									<?php esc_html_e( 'couldn\'t be restored as no backup exists.', 'wp-smushit' ); ?>
									<?php echo ' '; ?>{{ data.errorCopyCount }}
									<?php esc_html_e( 'due to a backup copy error.', 'wp-smushit' ); ?>
								<# } else if ( data.missingBackupCount > 0 ) { #>
									<?php echo ' '; ?>{{ data.missingBackupCount }}
									<?php esc_html_e( 'couldn\'t be restored as no backup exists.', 'wp-smushit' ); ?>
								<# } else if ( data.errorCopyCount > 0 ) { #>
									<?php echo ' '; ?>{{ data.errorCopyCount }}
									<?php esc_html_e( 'couldn\'t be restored due to a backup copy error.', 'wp-smushit' ); ?>
								<# } #>
								<?php
									echo ' ';
									/* translators: 1: link start tag, 2: link end tag */
									printf( esc_html__( 'Ensure %1$sBackup original images%2$s is enabled to keep copies of your originals.', 'wp-smushit' ), $backup_link, $backup_link_close );
								?>
							</p>
						</div>
					</div>
				</div>
				<# } #>
			<# } #>
		</div>
	</div>

	<# if ( 'finish' === data.slide && 0 < data.errors.length ) { #>
	<div class="smush-final-log">
		<div class="smush-bulk-errors" style="margin-top: -30px;">
			<# for ( let i = 0, len = data.errors.length; i < len; i++ ) { #>
			<div class="smush-bulk-error-row sui-no-margin">
				<div class="smush-bulk-image-data">
					<# if ( data.errors[i].thumb ) { #>
						{{{ data.errors[i].thumb }}}
					<# } else { #>
						<i class="sui-icon-photo-picture" aria-hidden="true"></i>
					<# } #>
					<span class="smush-image-name">{{{ data.errors[i].src }}}</span>
				</div>
				<div class="smush-bulk-image-actions">
					<a class="sui-button-icon" href="{{{ data.errors[i].link }}}">
						<i class="sui-icon-arrow-right" aria-hidden="true"></i>
					</a>
					<span class="sui-screen-reader-text">
						<?php esc_html_e( 'View item in Media Library', 'wp-smushit' ); ?>
					</span>
				</div>
			</div>
			<# } #>
		</div>
	</div>

	<p class="sui-description sui-margin-left sui-margin-right">
		<?php
		printf(
			/* translators: 1: Open a link <a>, 2: Close the link </a> */
			esc_html__( "Note: You can find all the images which couldn't be restored (still smushed) in your %1\$sMedia Library%2\$s.", 'wp-smushit' ),
			'<a href="' . esc_url( admin_url( 'upload.php' ) ) . '">',
			'</a>'
		);
		?>
	</p>
	<div class="sui-box-footer sui-flatten sui-no-padding-top">
		<div class="sui-actions-left">
			<button class="sui-button sui-button-ghost" onclick="WP_Smush.restore.cancel()" data-modal-close="">
				<?php esc_html_e( 'Cancel', 'wp-smushit' ); ?>
			</button>
		</div>

		<div class="sui-actions-right">
			<button class="sui-button" id="smush-bulk-restore-button">
				<i class="sui-icon-update" aria-hidden="true"></i>
				<?php esc_html_e( 'Retry', 'wp-smushit' ); ?>
			</button>
		</div>
	</div>
	<# } #>
</script>


<div class="sui-modal sui-modal-sm">
	<div
			role="dialog"
			id="smush-restore-images-dialog"
			class="sui-modal-content smush-restore-images-dialog"
			aria-modal="true"
			aria-labelledby="smush-restore-images-dialog-title"
			aria-describedby="smush-restore-images-dialog-description"
	>
		<div class="sui-box">
			<div id="smush-bulk-restore-content" aria-live="polite"></div>
			<?php wp_nonce_field( 'smush_bulk_restore' ); ?>
		</div>
	</div>
</div>
