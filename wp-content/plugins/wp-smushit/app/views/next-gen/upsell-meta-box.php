<?php
/**
 * Upsell NextGen meta box.
 *
 * @since 3.0
 * @package WP_Smush
 */

use Smush\Core\Helper;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="sui-block-content-center sui-message smush-box-next-gen-upsell">
	<img src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/graphic-smush-next-gen-free-tier.png' ); ?>"
		srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/graphic-smush-next-gen-free-tier@2x.png' ); ?> 2x"
		alt="<?php esc_html_e( 'Graphic NextGen', 'wp-smushit' ); ?>">
	<div class="sui-message-content">
		<p><?php esc_html_e( 'Fix the "Serve images in next-gen format" Google PageSpeed recommendation with a single click! Serve WebP and AVIF images directly from your server to supported browsers, while seamlessly switching to original images for those without WebP or AVIF support. All without relying on a CDN or any server configuration.', 'wp-smushit' ); ?></p>

		<ol class="sui-upsell-list">
			<li>
				<span class="sui-icon-check sui-sm" aria-hidden="true"></span>
				<?php esc_html_e( 'Activate the Next-Gen Formats feature with a single click; no server configuration required.', 'wp-smushit' ); ?>
			</li>
			<li>
				<span class="sui-icon-check sui-sm" aria-hidden="true"></span>
				<?php esc_html_e( 'Fix “Serve images in next-gen format" Google PageSpeed recommendation.', 'wp-smushit' ); ?>
			</li>
			<li>
				<span class="sui-icon-check sui-sm" aria-hidden="true"></span>
				<?php esc_html_e( 'Serve WebP and AVIF version of images in the browsers that support it and fall back to JPEGs and PNGs for unsupported browsers.', 'wp-smushit' ); ?>
			</li>
		</ol>

		<p class="sui-margin-top">
			<a href="<?php echo esc_url( Helper::get_url( 'smush_next-gen_upgrade_button' ) ); ?>" class="sui-button sui-button-purple" target="_blank">
				<?php esc_html_e( 'UNLOCK NEXT-GEN FORMATS WITH PRO', 'wp-smushit' ); ?>
			</a>
		</p>
	</div>
</div>
