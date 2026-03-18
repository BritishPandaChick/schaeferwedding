<?php

use Smush\Core\Settings;
use Smush\Core\Membership\Membership;
use Smush\Core\Hub_Connector;

$lossy_level_setting = Settings::get_instance()->get_lossy_level_setting();
$is_super_active     = Settings::get_level_super_lossy() === $lossy_level_setting;
$class_names         = array();
$class_names[]       = 'smush-upsell-link wp-smush-upsell-ultra-compression';
$is_dashboard_page   = 'smush' === $this->get_slug();
$location            = $is_dashboard_page ? 'dashboard_summary' : 'bulksmush_summary';
$utm_link            = $this->get_utm_link(
	array(
		'utm_campaign' => "smush_ultra_{$location}",
	)
);
?>
<li class="smush-summary-row-compression-type">
	<span class="sui-list-label"><?php esc_html_e( 'Smush Mode', 'wp-smushit' ); ?></span>
	<span class="sui-list-detail">
		<?php if ( Membership::get_instance()->is_api_hub_access_required() ) : ?>
			<a class="sui-button sui-button-blue smush-button-dark-blue" href="<?php echo esc_url( Hub_Connector::get_connect_site_url( 'smush-bulk', 'smush_summary_mode_connect' ) ); ?>"><?php _e( 'Connect For free', 'wp-smushit' ); ?></a>
		<?php else : ?>
			<span class="wp-smush-current-compression-level sui-tag sui-tag-green"><?php echo esc_html( Settings::get_instance()->get_current_lossy_level_label() ); ?></span>
			<a target="_blank" href="<?php echo isset( $utm_link ) ? esc_url( $utm_link ) : esc_url( $this->get_url( 'smush-bulk' ) ) . '#lossy-settings-row'; ?>" class="<?php echo esc_attr( join( ' ', $class_names ) ); ?>" title="<?php esc_attr_e( 'Choose the level of compression that suits your needs.', 'wp-smushit' ); ?>">
				<?php esc_html_e( '5x your compression with Ultra', 'wp-smushit' ); ?>
				<span class="sui-icon-open-new-window" aria-hidden="true"></span>
			</a>
		<?php endif; ?>
	</span>
</li>
