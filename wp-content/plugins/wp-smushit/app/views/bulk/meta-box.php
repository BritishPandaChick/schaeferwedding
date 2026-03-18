<?php
/**
 * Bulk Smush meta box.
 *
 * @since 2.9.0
 * @package WP_Smush
 *
 * @var Smush\Core\Core $core                           Instance of Smush\Core\Core
 * @var bool            $total_count
 * @var integer         $unsmushed_count                Count of the images that need smushing.
 * @var integer         $resmush_count                  Count of the images that need re-smushing.
 * @var integer         $remaining_count                Remaining count of all images to smush. Unsmushed images + images to re-smush.
 * @var string          $bulk_upgrade_url               Bulk Smush upgrade to PRO url.
 * @var string          $upsell_cdn_url                 Upsell CDN URL.
 * @var string          $in_processing_notice
 */
use Smush\Core\Stats\Global_Stats;

if ( ! defined( 'WPINC' ) ) {
	die;
}


if ( 0 !== absint( $total_count ) ) :
	$msg = __( 'Bulk smush detects images that can be optimized and allows you to compress them in bulk.', 'wp-smushit' );
	?>
<p><?php echo esc_html( $msg ); ?></p>
<?php endif; ?>

<?php
$this->view(
	'loopback-error-dialog',
	array(),
	'modals'
);

// If there are no images in media library.
if ( 0 === absint( $total_count ) ) {
	$this->view( 'media-lib-empty', array(), 'views/bulk' );
	return;
}

$this->view(
	'auto-bulk-smush-notification',
	array(),
	'views/bulk'
);

$this->view(
	'limit-reached-notice',
	array(),
	'views/bulk'
);
// Progress bar.
$this->view(
	'progress-bar',
	array(
		'count'                => $remaining_count,
		'in_processing_notice' => $in_processing_notice,
	),
	'common'
);

// All images are smushed.
$this->view( 'all-images-smushed-notice', array( 'all_done' => empty( $remaining_count ) ), 'common' );

// List errors.
$this->view( 'list-errors', array(), 'views/bulk' );

?>
<div class="wp-smush-bulk-wrapper sui-border-frame<?php echo empty( $remaining_count ) ? ' sui-hidden' : ''; ?>">
	<div id="wp-smush-bulk-content">
		<?php WP_Smush::get_instance()->admin()->print_pending_bulk_smush_content( $remaining_count, $resmush_count, $unsmushed_count ); ?>
	</div>
	<?php
	$bulk_smush_class = 'wp-smush-all';

	if ( Global_Stats::get()->is_outdated() ) {
		$bulk_smush_class .= ' wp-smush-scan-and-bulk-smush';
	}

	?>
	<button type="button" class="<?php echo esc_attr( $bulk_smush_class ); ?> sui-button sui-button-blue" title="<?php esc_attr_e( 'Click to start Bulk Smushing images in Media Library', 'wp-smushit' ); ?>">
		<?php esc_html_e( 'BULK SMUSH', 'wp-smushit' ); ?>
	</button>
</div>
<?php
$global_upsell_desc = __( 'Smush Pro lets you optimize in the background. Unlock Ultra Smush, blazing-fast CDN, and more.', 'wp-smushit' );

$this->view(
	'global-upsell',
	array(
		'bulk_upgrade_url'   => $bulk_upgrade_url,
		'global_upsell_desc' => $global_upsell_desc,
	),
	'views/bulk'
);
