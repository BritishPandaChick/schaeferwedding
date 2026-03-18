<?php
/**
 * Plugin Name: Titan Anti-spam & Security
 * Plugin URI: http://wordpress.org/plugins/anti-spam/
 * Description: Titan Security - Anti-spam, Anti-virus, Firewall and Malware Scan
 * Version: 7.5.0
 * Author: Themeisle
 * Requires PHP: 7.4
 * WordPress Available:  yes
 * Requires License:    no
 * Text Domain: anti-spam
 * Author URI: https://themeisle.com
 * License: GPLv3
 * 
 * @package Titan_Security
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	add_action(
		'admin_notices',
		function () {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'Titan Anti-spam & Security requires PHP 7.4 or higher. Please upgrade your PHP version.', 'anti-spam' ); ?></p>
			</div>
			<?php
		}
	);

	return;
}

/**
 * -----------------------------------------------------------------------------
 * CONSTANTS
 * Install frequently used constants and constants for debugging, which will be
 * removed after compiling the plugin.
 * -----------------------------------------------------------------------------
 */

// This plugin is activated.
define( 'WTITAN_PLUGIN_ACTIVE', true );
define( 'WTITAN_PLUGIN_VERSION', '7.5.0' );
define( 'WTITAN_PLUGIN_DIR', __DIR__ );
define( 'WTITAN_PLUGIN_BASE', plugin_basename( __FILE__ ) );
define( 'WTITAN_PLUGIN_URL', plugins_url( '', __FILE__ ) );
define( 'WTITAN_PLUGIN_FILE', __FILE__ );
define( 'WTITAN_PLUGIN_SLUG', basename( dirname( WTITAN_PLUGIN_FILE ) ) );
define( 'WTITAN_PLUGIN_NAMESPACE', str_replace( '-', '_', strtolower( trim( WTITAN_PLUGIN_SLUG ) ) ) );
define( 'WTITAN_UPGRADE', 'https://titansitescanner.com/upgrade' );
define(
	'WTITAN_COMPATIBILITY',
	[
		'sdk' => true,
	] 
);

if ( ! defined( 'WTITAN_PLUGIN_API' ) ) {
	define( 'WTITAN_PLUGIN_API', 'https://api.titansitescanner.com/api/v2/' );
}

// START: Related to premium version compatibility check for Titan Security Pro.
add_action(
	'plugins_loaded',
	function () {
		$premium_plugin_file = 'wp-plugin-titan-premium/titan-premium.php';
		$premium_plugin_path = WP_PLUGIN_DIR . '/' . $premium_plugin_file;

		if ( ! file_exists( $premium_plugin_path ) ) {
			return;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active( $premium_plugin_file ) ) {
			return;
		}

		if ( ! defined( 'WTITANP_COMPATIBILITY' ) ) {
			deactivate_plugins( $premium_plugin_file );
			set_transient( 'wtitan_premium_version_incompatible', true, WEEK_IN_SECONDS );
		}
	},
	5
);

add_action(
	'admin_init',
	function () {
		if ( isset( $_GET['wtitan_dismiss_premium_notice'] ) && check_admin_referer( 'wtitan_dismiss_premium_notice' ) ) {
			delete_transient( 'wtitan_premium_version_incompatible' );
			wp_safe_redirect( remove_query_arg( 'wtitan_dismiss_premium_notice' ) );
			exit;
		}
	}
);

add_action(
	'admin_notices',
	function () {
		if ( get_transient( 'wtitan_premium_version_incompatible' ) ) {
			$dismiss_url = wp_nonce_url(
				add_query_arg( 'wtitan_dismiss_premium_notice', '1' ),
				'wtitan_dismiss_premium_notice'
			);
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'Titan Anti-spam & Security Pro has been deactivated.', 'anti-spam' ); ?></strong>
				</p>
				<p>
					<?php esc_html_e( 'The installed premium version is not compatible with the current version of Titan Anti-spam & Security. Please update the premium plugin to the latest version.', 'anti-spam' ); ?>
				</p>
				<p>
					<a href="<?php echo esc_url( $dismiss_url ); ?>" class="button button-secondary">
						<?php esc_html_e( 'Dismiss', 'anti-spam' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}
);
// END: Related to premium version compatibility check for Titan Security Pro.

add_action(
	'plugins_loaded',
	function () {
		load_plugin_textdomain( 'anti-spam', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);

require_once WTITAN_PLUGIN_DIR . '/admin/class-activation.php';
register_activation_hook( __FILE__, [ 'WBCR\Titan\Activation', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'WBCR\Titan\Activation', 'deactivate' ] );

add_filter( WTITAN_PLUGIN_NAMESPACE . '_about_us_metadata', 'titan_sdk_about_page' );

/**
 * About page metadata
 *
 * @return array<string, mixed>
 */
function titan_sdk_about_page() {
	$upgrade_url = 'https://titansitescanner.com/upgrade';
	
	if ( function_exists( 'tsdk_utmify' ) ) {
		$upgrade_url = tsdk_utmify( $upgrade_url, 'admin-menu', 'upgrade' );
	}
	
	return [
		'location'         => 'titan-security',
		'logo'             => WTITAN_PLUGIN_URL . '/admin/assets/img/logo.svg',
		'review_link'      => false,
		'has_upgrade_menu' => ! defined( 'WTITANP_PLUGIN_VERSION' ),
		'upgrade_link'     => $upgrade_url,
		'upgrade_text'     => __( 'Upgrade to Pro', 'anti-spam' ),
	];
}

/**
 * Register compatibility using SDK.
 *
 * @param array<string, array<string, string>> $compatibilities All compatibilities.
 *
 * @return array<string, array<string, string>> Registered compatibility.
 */
function titan_sdk_register_compatibility( $compatibilities ) {
	$compatibilities['TitanPro'] = [
		'basefile' => defined( 'WTITANP_PLUGIN_FILE' ) ? WTITANP_PLUGIN_FILE : '',
		'required' => '1.6.0',
	];

	return $compatibilities;
}

add_filter( 'themeisle_sdk_compatibilities/' . basename( WTITAN_PLUGIN_DIR ), 'titan_sdk_register_compatibility' );

/**
 * -----------------------------------------------------------------------------
 * PLUGIN INIT
 * -----------------------------------------------------------------------------
 */
require_once WTITAN_PLUGIN_DIR . '/includes/class-http.php';
require_once WTITAN_PLUGIN_DIR . '/includes/antispam/functions.php';
require_once WTITAN_PLUGIN_DIR . '/includes/helpers.php';
require_once WTITAN_PLUGIN_DIR . '/includes/class-titan-security-plugin.php';

try {
	require_once WTITAN_PLUGIN_DIR . '/vendor/autoload.php';
	new \WBCR\Titan\Plugin();

	require_once WTITAN_PLUGIN_DIR . '/includes/functions.php';

	// Initialize frontend spam protection.
	add_action(
		'init',
		function () {
			new \WBCR\Titan\Antispam\Protector();
			new \WBCR\Titan\Antispam\Advanced_Spam_Filter();
		}
	);
} catch ( Exception $e ) {
	// Plugin wasn't initialized due to an error.
	define( 'WTITAN_PLUGIN_THROW_ERROR', true );

	$wtitan_plugin_error_func = function () use ( $e ) {
		$error = sprintf( 'Titan Security plugin has stopped. <b>Error:</b> %s Code: %s', $e->getMessage(), $e->getCode() );
		echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
	};

	add_action( 'admin_notices', $wtitan_plugin_error_func );
	add_action( 'network_admin_notices', $wtitan_plugin_error_func );
}
