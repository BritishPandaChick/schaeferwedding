<?php
/**
 * Integrations meta box
 *
 * @package WP_Smush
 *
 * @var array  $basic_features    Basic features array.
 * @var array  $integration_group Integration group.
 * @var array  $settings          Settings array.
 *
 * @var Abstract_Page $this
 */

use Smush\Core\Helper;

if ( ! defined( 'WPINC' ) ) {
	die;
}

foreach ( $integration_group as $name ) {
	$is_pro_field             = $this->settings->is_pro_field( $name ); // Gray out row, disable setting.
	$is_integration_disabled = apply_filters( 'wp_smush_integration_status_' . $name, $is_pro_field ); // Disable setting.
	$is_disabled_field        = $is_integration_disabled || $is_pro_field;
	$value                   = $is_disabled_field || empty( $settings[ $name ] ) ? false : $settings[ $name ];
	$show_pro_tag            = $is_pro_field;
	$upsell                  = false;
	do_action( 'wp_smush_render_setting_row', $name, $value, $is_disabled_field, $upsell, $show_pro_tag );
}
?>
<div class="sui-upsell-notice sui-padding sui-padding-bottom__desktop--hidden">
	<div class="sui-upsell-notice__content">
		<div class="sui-notice sui-notice-purple">
			<div class="sui-notice-content">
				<div class="sui-notice-message">
					<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
					<p>
						<?php
							esc_html_e( 'Smush Pro supports hosting images on Amazon S3 and optimizating NextGen Gallery images directly through NextGen Gallery settings. Try it with a WPMU DEV membership today!', 'wp-smushit' );
						?>
					</p>
					<p>
						<a href="<?php echo esc_url( Helper::get_url( 'smush-nextgen-settings-upsell' ) ); ?>" target="_blank" class="sui-button sui-button-purple">
							<?php esc_html_e( 'UNLOCK NOW WITH PRO', 'wp-smushit' ); ?>
						</a>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
