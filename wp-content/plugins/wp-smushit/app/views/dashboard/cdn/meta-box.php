<?php
/**
 * CDN meta box.
 *
 * @since 3.8.6
 * @package WP_Smush
 *
 * @var string $cdn_status         CDN status.
 * @var string $upsell_url         Upsell URL.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<?php $this->view( 'cdn/header-description' ); ?>

<a href="<?php echo esc_url( $upsell_url ); ?>" target="_blank" class="sui-button sui-button-purple">
	<?php esc_html_e( 'Upgrade to Pro', 'wp-smushit' ); ?>
</a>
