<?php
/**
 * Local WebP meta box.
 *
 * @since 3.8.6
 * @package WP_Smush
 */

use Smush\Core\Next_Gen\Next_Gen_Manager;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$next_gen_manager = Next_Gen_Manager::get_instance();
$upsell_url       = $this->get_utm_link( array( 'utm_campaign' => 'smush-dashboard-next-gen-upsell' ) );
/* translators: %s: Next-Gen format name */
$next_gen_description = sprintf( __( 'Serve %1$s versions of your images to supported browsers, and gracefully fall back on JPEGs and PNGs for browsers that don\'t support %1$s.', 'wp-smushit' ), $next_gen_manager->get_active_format_name() );
?>

<p>
	<?php echo esc_html( $next_gen_description ); ?>
</p>

<a href="<?php echo esc_url( $upsell_url ); ?>" target="_blank" class="sui-button sui-button-purple">
	<?php esc_html_e( 'Upgrade to Pro', 'wp-smushit' ); ?>
</a>
