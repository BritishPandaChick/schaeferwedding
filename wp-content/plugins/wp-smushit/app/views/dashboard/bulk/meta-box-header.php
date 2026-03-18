<?php
/**
 * CDN meta box header.
 *
 * @since 3.8.6
 * @package WP_Smush
 *
 * @var string $title  Meta box title.
 */

use Smush\Core\Membership\Membership;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<h3 class="sui-box-title"><?php echo esc_html( $title ); ?></h3>

<?php if ( Membership::get_instance()->is_api_hub_access_required() ) : ?>
	<div class="sui-actions-left">
		<span class="sui-tag sui-tag-ghost smush-sui-tag-blue"><?php esc_html_e( 'Free Plan', 'wp-smushit' ); ?></span>
	</div>
<?php endif; ?>
