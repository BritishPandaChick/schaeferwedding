<?php
/**
 * React Dashboard Page Class
 *
 * @package Titan_Security
 */

namespace WBCR\Titan;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Titan_Dashboard Class
 */
class Titan_Dashboard {
	/**
	 * Singleton instance
	 *
	 * @var Titan_Dashboard|null
	 */
	private static $instance = null;

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {
		add_action( 'admin_menu', [ $this, 'register_dashboard_page' ] );
		add_action( 'admin_head', [ $this, 'add_menu_icon_styles' ] );
	}

	/**
	 * Register React dashboard page
	 */
	public function register_dashboard_page(): void {
		$page_hook_suffix = add_menu_page(
			__( 'Titan Security', 'anti-spam' ),
			__( 'Titan Security', 'anti-spam' ),
			'manage_options',
			'titan-security',
			[ $this, 'render_dashboard_page' ],
			WTITAN_PLUGIN_URL . '/admin/assets/img/icon.svg'
		);

		// Replace auto-generated first submenu with "Dashboard".
		add_submenu_page(
			'titan-security',
			__( 'Dashboard', 'anti-spam' ),
			__( 'Dashboard', 'anti-spam' ),
			'manage_options',
			'titan-security'
		);

		add_action( "admin_print_scripts-$page_hook_suffix", [ $this, 'enqueue_dashboard_assets' ] );
	}

	/**
	 * Add custom styles for menu icon
	 */
	public function add_menu_icon_styles(): void {
		?>
		<style>
			#adminmenu li.toplevel_page_titan-security .wp-menu-image img {
				display: inline;
			}
		</style>
		<?php
	}

	/**
	 * Render React dashboard page content
	 */
	public function render_dashboard_page(): void {
		echo '<div id="titan-security-dashboard"></div>';
	}

	/**
	 * Load assets for React dashboard page.
	 */
	public function enqueue_dashboard_assets(): void {
		$asset_file_path = WTITAN_PLUGIN_DIR . '/admin/assets/dashboard/build/index.asset.php';

		if ( ! file_exists( $asset_file_path ) ) {
			return;
		}

		$asset_file = include $asset_file_path;

		wp_enqueue_style(
			'titan-security-dashboard-styles',
			WTITAN_PLUGIN_URL . '/admin/assets/dashboard/build/style-index.css',
			[],
			$asset_file['version']
		);

		wp_enqueue_script(
			'titan-security-dashboard-scripts',
			WTITAN_PLUGIN_URL . '/admin/assets/dashboard/build/index.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		wp_set_script_translations( 'titan-security-dashboard-scripts', 'titan-security' );

		wp_localize_script(
			'titan-security-dashboard-scripts',
			'titanSecurityObjects',
			[
				'api'                => 'titan-security/v1',
				'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
				'assetsUrl'          => WTITAN_PLUGIN_URL . '/admin/assets/',
				'privacySettingsUrl' => admin_url( 'options-privacy.php' ),
				'upgradeUrl'         => function_exists( 'tsdk_utmify' ) ? tsdk_utmify( WTITAN_UPGRADE, 'dashboard_audit', 'upgrade_button' ) : WTITAN_UPGRADE,
				'updateUrl'          => admin_url( 'update-core.php' ),
				'version'            => WTITAN_PLUGIN_VERSION,
				'userEmail'          => wp_get_current_user()->user_email,
				'siteUrl'            => home_url(),
				'license'            => [
					'key'    => apply_filters( 'product_titan_license_key', 'free' ),
					'status' => apply_filters( 'product_titan_license_status', false ),
				],
				'hasPremium'         => \WBCR\Titan\Plugin::app()->premium->is_pro_active(),
				'isRTL'              => is_rtl(),
				'stats'              => wantispam_get_stats(),
				'settings'           => $this->get_settings(),
				'audit'              => $this->get_audit_data(),
				'vulnerabilities'    => $this->get_vulnerability_data(),
				'twoFactor'          => $this->get_two_factor_data(),
				'tablePrefix'        => $GLOBALS['table_prefix'],
				'cachingTip'         => $this->get_caching_tip_data(),
			]
		);
	}

	/**
	 * Get plugin settings
	 */
	public function get_settings() {
		$privacy_policy_url = get_privacy_policy_url();

		return [
			'antispam_mode'               => (bool) get_option( 'titan_antispam_mode', true ),
			'send_analytics'              => ( 'yes' === get_option( WTITAN_PLUGIN_NAMESPACE . '_logger_flag', 'no' ) ),
			'privacy_policy_url'          => $privacy_policy_url,
			'has_privacy_policy_page'     => ! empty( $privacy_policy_url ),
			'comment_form_privacy_notice' => (bool) get_option( 'titan_comment_form_privacy_notice', false ),
		];
	}

	/**
	 * Get two-factor authentication data for current user
	 *
	 * @return array<string, mixed>
	 */
	public function get_two_factor_data(): array {
		$user = wp_get_current_user();
		return Titan_Rest_Controller::get_user_two_factor_data( $user->ID );
	}

	/**
	 * Get audit data
	 *
	 * @return array{
	 *     items: array<int, array{
	 *         id: string,
	 *         title: string,
	 *         description: string,
	 *         severity: string,
	 *         time: string,
	 *         fix: string
	 *     }>,
	 *     count: int,
	 *     vulnerabilities: int
	 * }
	 */
	public function get_audit_data(): array {
		$audit = \WBCR\Titan\Audit::app();

		$audit_results = $audit->get_audit();

		if ( false === $audit_results || empty( $audit_results ) ) {
			$audit_results = $audit->do_audit();
		}

		$hidden_ids   = get_option( 'titan_audit_hidden_items', [] );
		$hidden_ids   = is_array( $hidden_ids ) ? $hidden_ids : [];
		$audit_items  = [];
		$hidden_items = [];

		if ( is_array( $audit_results ) ) {
			foreach ( $audit_results as $id => $item ) {
				// Add issue ID to fix URL if present.
				$fix_url = $item->fix;
				if ( ! empty( $fix_url ) && strpos( $fix_url, 'action=' ) !== false ) {
					$fix_url = add_query_arg( 'wtitan_fixing_issue_id', $id, $fix_url );
				}

				$entry = [
					'id'          => $id,
					'title'       => $item->title,
					'description' => $item->description,
					'severity'    => $item->severity,
					'time'        => $item->timestamp,
					'fix'         => $fix_url,
				];

				if ( in_array( (string) $id, $hidden_ids, true ) ) {
					$hidden_items[] = $entry;
				} else {
					$audit_items[] = $entry;
				}
			}
		}

		$vulner = \WBCR\Titan\Vulnerabilities::app();

		$vulnerabilities_count = $vulner->get_count();

		return [
			'items'           => $audit_items,
			'count'           => count( $audit_items ),
			'hidden_items'    => $hidden_items,
			'hided_count'     => count( $hidden_items ),
			'vulnerabilities' => $vulnerabilities_count,
		];
	}

	/**
	 * Get vulnerability data using Vulnerabilities class
	 *
	 * @return array<string, mixed>
	 */
	public function get_vulnerability_data(): array {
		$vulner = \WBCR\Titan\Vulnerabilities::app();
		return $vulner->get_vulnerability_data();
	}

	/**
	 * Get caching tip data for the dashboard
	 *
	 * @return array<string, mixed>
	 */
	private function get_caching_tip_data(): array {
		if ( get_option( 'titan_cache_tip_dismissed', false ) ) {
			return [ 'show' => false ];
		}
		$install_time = get_option( WTITAN_PLUGIN_NAMESPACE . '_install', time() );
		if ( intval( ( time() - $install_time ) / DAY_IN_SECONDS ) < 7 ) {
			return [ 'show' => false ];
		}
		if ( $this->has_caching_plugin_active() ) {
			return [ 'show' => false ];
		}
		
		return [
			'show'                 => true,
			'installOrActivateUrl' => rest_url( 'titan-security/v1/plugins/install-or-activate' ),
			'pluginPath'           => 'wp-cloudflare-page-cache/wp-cloudflare-super-page-cache.php',
			'pluginSlug'           => 'wp-cloudflare-page-cache',
			'learnMoreUrl'         => 'https://wordpress.org/plugins/wp-cloudflare-page-cache/',
			'isInstalled'          => $this->is_plugin_installed( 'wp-cloudflare-page-cache/wp-cloudflare-super-page-cache.php' ),
		];
	}

	/**
	 * Check whether a known caching plugin is currently active
	 *
	 * @return bool
	 */
	private function has_caching_plugin_active(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$caching_plugins = [
			'wp-super-cache/wp-cache.php',
			'w3-total-cache/w3-total-cache.php',
			'wp-fastest-cache/wpFastestCache.php',
			'wp-rocket/wp-rocket.php',
			'litespeed-cache/litespeed-cache.php',
			'wp-cloudflare-page-cache/wp-cloudflare-super-page-cache.php',
			'autoptimize/autoptimize.php',
			'cache-enabler/cache-enabler.php',
			'comet-cache/comet-cache.php',
			'hummingbird-performance/wp-hummingbird.php',
			'sg-cachepress/sg-cachepress.php',
			'breeze/breeze.php',
		];
		foreach ( $caching_plugins as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check whether a plugin is installed (but not necessarily active)
	 *
	 * @param string $plugin Plugin path (e.g., 'wp-cloudflare-page-cache/wp-cloudflare-super-page-cache.php').
	 *
	 * @return bool
	 */
	private function is_plugin_installed( $plugin ): bool {
		return file_exists( WP_PLUGIN_DIR . '/' . $plugin );
	}

	/**
	 * Prevent cloning of the instance
	 *
	 * @throws \Exception An exception when trying to clone the instance.
	 */
	private function __clone() {
		throw new \Exception( 'Cannot clone singleton' );
	}

	/**
	 * Prevent unserialization of the instance
	 *
	 * @throws \Exception An exception when trying to unserialize the instance.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}

	/**
	 * Get singleton instance
	 *
	 * @return Titan_Dashboard
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
