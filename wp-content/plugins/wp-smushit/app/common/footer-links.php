<?php
/**
 * Links in the footer.
 *
 * @package WP_Smush
 */
use Smush\Core\Helper;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$hide_footer = false;
$footer_text = sprintf( /* translators: %s - icon */
	esc_html__( 'Made with %s by WPMU DEV', 'wp-smushit' ),
	'<span aria-hidden="true" class="sui-icon-heart"></span>'
);

?>

<div class="sui-footer">
	<?php echo wp_kses_post( $footer_text ); ?>
</div>

<ul class="sui-footer-nav">
	<?php
	$roadmap_link = Helper::get_utm_link(
		array(
			'utm_campaign' => 'smush_footer_roadmap',
		),
		'https://wpmudev.com/roadmap/'
	);

	$docs_link = Helper::get_utm_link(
		array(
			'utm_campaign' => 'smush_footer_docs',
		),
		'https://wpmudev.com/docs/'
	);

	$terms_link = Helper::get_utm_link(
		array(
			'utm_campaign' => 'smush_footer_tos',
		),
		'https://wpmudev.com/terms-of-service/'
	);

	?>
	<?php
		$hub_link = Helper::get_utm_link(
			array( 'utm_campaign' => 'smush_footer_hub' ),
			'https://wpmudev.com/hub-welcome/'
		);
		?>
	<li><a href="https://profiles.wordpress.org/wpmudev#content-plugins" target="_blank">
			<?php esc_html_e( 'Free Plugins', 'wp-smushit' ); ?>
		</a></li>
	<li><a href="<?php echo esc_url( $roadmap_link ); ?>" target="_blank">
			<?php esc_html_e( 'Roadmap', 'wp-smushit' ); ?>
		</a></li>
	<li><a href="https://wordpress.org/support/plugin/wp-smushit" target="_blank">
			<?php esc_html_e( 'Support', 'wp-smushit' ); ?>
		</a></li>
	<li><a href="<?php echo esc_url( $docs_link ); ?>" target="_blank">
			<?php esc_html_e( 'Docs', 'wp-smushit' ); ?>
		</a></li>
	<li><a href="<?php echo esc_url( $hub_link ); ?>" target="_blank">
			<?php esc_html_e( 'The Hub', 'wp-smushit' ); ?>
		</a></li>
	<li><a href="<?php echo esc_url( $terms_link ); ?>" target="_blank">
			<?php esc_html_e( 'Terms of Service', 'wp-smushit' ); ?>
		</a></li>
	<li><a href="https://incsub.com/privacy-policy/" target="_blank">
			<?php esc_html_e( 'Privacy Policy', 'wp-smushit' ); ?>
		</a></li>
</ul>
<?php if ( ! $hide_footer ) : ?>
<ul class="sui-footer-social">
	<li>
		<a href="https://www.facebook.com/wpmudev" target="_blank">
			<i class="sui-icon-social-facebook" aria-hidden="true"></i>
			<span class="sui-screen-reader-text">Facebook</span>
		</a>
	</li>
	<li>
		<a href="https://twitter.com/wpmudev" target="_blank">
			<i class="sui-icon-social-twitter" aria-hidden="true"></i>
		</a>
		<span class="sui-screen-reader-text">Twitter</span>
	</li>
	<li>
		<a href="https://www.instagram.com/wpmu_dev/" target="_blank">
			<i class="sui-icon-instagram" aria-hidden="true"></i>
			<span class="sui-screen-reader-text">Instagram</span>
		</a>
	</li>
</ul>
<?php endif; ?>
