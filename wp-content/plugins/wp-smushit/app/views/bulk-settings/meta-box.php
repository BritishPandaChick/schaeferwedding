<?php
/**
 * Settings meta box.
 *
 * @package WP_Smush
 *
 * @var array $basic_features    Basic features list.
 * @var bool  $cdn_enabled       CDN status.
 * @var array $settings          Settings values.
 * @var bool  $backup_exists     Number of attachments with backups.
 */

use Smush\Core\Settings;

if ( ! defined( 'WPINC' ) ) {
	die;
}

// Original Images Move Notice.
$smush_admin        = WP_Smush::get_instance()->admin();
$notice_hidden      = $smush_admin->is_notice_dismissed( 'original-images-move' );
$should_show_notice = false;
if ( ! $notice_hidden ) {
	$event_times        = get_site_option( 'wp_smush_event_times' );
	$should_show_notice = ! empty( $event_times['plugin_upgraded'] );
}

if ( $should_show_notice ) :
	?>
	<div class="is-dismissible smush-dismissible-notice" data-key="original-images-move" style="margin-bottom: 30px;">
		<div class="sui-notice sui-notice-info">
			<div class="sui-notice-content">
				<div class="sui-notice-message">
					<i class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></i>
					<p>
						<?php
						printf(
						/* translators: 1: <strong> 2: </strong> */
							esc_html__( 'We\'ve moved the %1$sOriginal Images settings%2$s to %1$sAdvanced Settings section%2$s below.', 'wp-smushit' ),
							'<strong>',
							'</strong>'
						);
						?>
					</p>
				</div>
				<div class="sui-notice-actions">
					<button class="sui-button-icon smush-dismiss-notice-button">
						<i class="sui-icon-close" aria-hidden="true"></i>
						<span class="sui-screen-reader-text">
							<?php esc_html_e( 'Dismiss', 'wp-smushit' ); ?>
						</span>
					</button>
				</div>
			</div>
		</div>
	</div>
	<?php
endif;

do_action( 'wp_smush_bulk_smush_settings', $grouped_settings );

do_action_deprecated(
	'wp_smush_after_basic_settings',
	array(),
	'3.21.0',
	'wp_smush_after_advanced_settings',
	__( 'The wp_smush_after_basic_settings hook is deprecated. Use wp_smush_after_advanced_settings instead.', 'wp-smushit' )
);
