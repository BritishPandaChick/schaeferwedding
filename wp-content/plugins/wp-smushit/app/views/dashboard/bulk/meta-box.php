<?php
/**
 * Bulk compress dashboard meta box.
 *
 * @since 3.8.6
 * @package WP_Smush
 *
 * @var int    $uncompressed                  Number of uncompressed attachments.
 * @var string $upsell_url                    Upsell URL.
 * @var bool   $scan_background_process_dead  Whether Scan background process is dead or not.
 * @var int    $total_count                   Total count.
 */

use Smush\Core\Membership\Membership;
use Smush\Core\Hub_Connector;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$msg = __( 'Bulk smush detects images that can be optimized and allows you to compress them in bulk. You can also smush non WordPress images that are outside of your uploads directory.', 'wp-smushit' );
?>
<p><?php echo esc_html( $msg ); ?></p>

<?php
if ( Membership::get_instance()->is_api_hub_access_required() ) {
	$this->view( 'dashboard/bulk/connect-free', array( 'hub_connect_url' => Hub_Connector::get_connect_site_url( 'smush-bulk', 'smush_dashboard_bulk_smush_widget_connect' ) ) );
} elseif ( $scan_background_process_dead ) {
	$this->view( 'scan-background-process-dead', array(), 'views/dashboard/bulk' );
} elseif ( 0 === $total_count ) {
	$this->view( 'media-lib-empty', array(), 'views/dashboard/bulk' );
} elseif ( 0 === $uncompressed ) {
	$this->view( 'all-images-smushed-notice', array( 'all_done' => true ), 'common' );
} else {
	$this->view(
		'exists-uncompressed',
		array(
			'uncompressed' => $uncompressed,
			'upsell_url'   => $upsell_url,
		),
		'views/dashboard/bulk'
	);
}
