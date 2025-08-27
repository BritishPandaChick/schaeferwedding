<?php
/**
 * Show Updated Features modal.
 *
 * @package WP_Smush
 *
 * @since 3.7.0
 *
 * @var string $cta_url URL for the modal's CTA button.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="smush-updated-dialog"
		class="sui-modal-content smush-updated-dialog wp-smush-modal-dark-background"
		aria-modal="true"
		data-esc-close="false"
		aria-labelledby="smush-title-updated-dialog"
	>
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-sides--20">
				<figure class="sui-box-banner" aria-hidden="true">
					<img src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/updated/updated.png' ); ?>"
						srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/updated/updated.png' ); ?> 1x, <?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/updated/updated' ); ?>@2x.png 2x"
						alt="<?php esc_attr_e( 'Smush Updated Modal', 'wp-smushit' ); ?>" class="sui-image sui-image-center">
				</figure>

				<button class="sui-button-icon sui-button-float--right sui-button-grey" style="box-shadow:none!important" onclick="WP_Smush.onboarding.hideUpgradeModal(event, this)">
					<i class="sui-icon-close sui-md" aria-hidden="true"></i>
				</button>
			</div>

			<div class="sui-box-body sui-content-center sui-spacing-sides--30 sui-spacing-top--40 sui-spacing-bottom--50">
				<h3 class="sui-box-title sui-lg" id="smush-title-updated-dialog" style="white-space: normal">
					<?php esc_html_e( 'New: Lazy Loading for Videos', 'wp-smushit' ); ?>
				</h3>

				<p class="sui-description">
					<?php esc_html_e( 'Speed up your site with smarter video loading! YouTube and Vimeo embeds now load only when needed—boosting initial page speed and helping you pass audits like Defer offscreen images and Reduce render-blocking resources.', 'wp-smushit' ); ?>
				</p>
				<?php
				if ( $cta_url ) {
					$cta_label   = __( 'Go to Lazy Load', 'wp-smushit' );
					$target      = '_self';
					$class_names = array(
						'sui-button',
						'wp-smush-upgrade-modal-cta',
						'sui-button-grey',
					);
					?>
						<a href="<?php echo esc_js( $cta_url ); ?>"
							target="<?php echo esc_attr( $target ); ?>"
							class="<?php echo esc_attr( join( ' ', $class_names ) ); ?>"
							onclick="WP_Smush.onboarding.hideUpgradeModal(event, this)">
						<?php echo esc_html( $cta_label ); ?>
						</a>
					<?php
				}
				?>
			</div>
		</div>
	</div>
</div>
