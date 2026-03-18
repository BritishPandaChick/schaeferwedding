<?php

namespace WBCR\Titan;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class Plugin {

	/**
	 * Number of comments that will be sent for verification
	 *
	 * @since 6.2
	 */
	const COUNT_TO_CHECK = 30;

	/**
	 * @see self::app()
	 * @var Plugin
	 */
	private static $app;

	/**
	 * Custom license provider (overrides parent's premium property)
	 *
	 * @var WBCR_License
	 */
	public $premium;

	/**
	 * Конструктор
	 *
	 * Применяет конструктор родительского класса и записывает экземпляр текущего класса в свойство $app.
	 * Подробнее о свойстве $app см. self::app()
	 *
	 * @throws \Exception
	 * @since  6.0
	 */
	public function __construct() {
		self::$app = $this;

		require_once WTITAN_PLUGIN_DIR . '/includes/class-license.php';
		require_once WTITAN_PLUGIN_DIR . '/includes/api/boot.php';
		require_once WTITAN_PLUGIN_DIR . '/includes/vulnerabilities/boot.php';

		$this->premium = new WBCR_License();
		new Vulnerabilities();

		require_once WTITAN_PLUGIN_DIR . '/admin/class-rest-controller.php';
		\WBCR\Titan\Titan_Rest_Controller::get_instance();

		$this->global_scripts();

		if ( is_admin() ) {
			$this->admin_scripts();
		}

		add_filter( 'themeisle_sdk_products', [ __CLASS__, 'register_sdk' ] );
		add_filter( 'themeisle_sdk_ran_promos', [ __CLASS__, 'sdk_hide_promo_notice' ] );
		add_filter( WTITAN_PLUGIN_NAMESPACE . '_logger_data', [ $this, 'get_logger_data' ] );
	}

	/**
	 * Hide SDK promo notice for pro uses.
	 *
	 * @access public
	 */
	public static function sdk_hide_promo_notice() {
		return self::app()->premium->is_active();
	}
	/**
	 * Register product into SDK.
	 *
	 * @param array $products All products.
	 *
	 * @return array Registered product.
	 */
	public static function register_sdk( $products ) {
		$products[] = WTITAN_PLUGIN_FILE;

		return $products;
	}

	/**
	 * Статический метод для быстрого доступа к интерфейсу плагина.
	 *
	 * Позволяет разработчику глобально получить доступ к экземпляру класса плагина в любом месте
	 * плагина, но при этом разработчик не может вносить изменения в основной класс плагина.
	 *
	 * Используется для получения настроек плагина, информации о плагине, для доступа к вспомогательным
	 * классам.
	 *
	 * @return Plugin
	 * @since  6.0
	 */
	public static function app() {
		return self::$app;
	}

	/**
	 * Backward-compatible option prefix used by legacy premium integrations.
	 *
	 * @return string
	 */
	public function getPrefix() {
		return 'titan_';
	}

	/**
	 * Return a normalized option name for legacy premium calls.
	 *
	 * @param string $option_name Option key without/with prefix.
	 *
	 * @return string
	 * @deprecated Use the new options name.
	 */
	private function get_legacy_option_name( $option_name ) {
		$option_name = (string) $option_name;
		$prefix      = $this->getPrefix();

		if ( 0 === strpos( $option_name, $prefix ) ) {
			return $option_name;
		}

		return $prefix . $option_name;
	}

	/**
	 * Backward-compatible getter for premium <= 1.5.x.
	 *
	 * @param string $option_name Option key without prefix.
	 * @param mixed  $default_value     Default value.
	 *
	 * @return mixed
	 * @deprecated
	 */
	public function getPopulateOption( $option_name, $default_value = false ) {
		return get_option( $this->get_legacy_option_name( $option_name ), $default_value );
	}

	/**
	 * Backward-compatible option updater for premium <= 1.5.x.
	 *
	 * @param string    $option_name Option key without prefix.
	 * @param mixed     $value       Option value.
	 * @param bool|null $autoload   Optional autoload flag.
	 *
	 * @return bool
	 * @deprecated
	 */
	public function updatePopulateOption( $option_name, $value, $autoload = null ) {
		$normalized_name = $this->get_legacy_option_name( $option_name );

		if ( null === $autoload ) {
			return update_option( $normalized_name, $value );
		}

		return update_option( $normalized_name, $value, $autoload );
	}

	/**
	 * @throws \Exception
	 * @since  6.0
	 */
	private function admin_scripts() {
		require_once WTITAN_PLUGIN_DIR . '/admin/boot.php';

		require_once WTITAN_PLUGIN_DIR . '/admin/pages/class-dashboard.php';
		Titan_Dashboard::get_instance();

		add_filter( 'themeisle-sdk/survey/' . WTITAN_PLUGIN_DIR, [ $this, 'get_survey_data' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'mark_internal_page' ] );
	}

	/**
	 * @since  7.0
	 */
	private function global_scripts() {

		// Bruteforce.
		if ( get_option( 'titan_bruteforce_enabled' ) ) {
			require_once WTITAN_PLUGIN_DIR . '/includes/bruteforce/const.php';
			require_once WTITAN_PLUGIN_DIR . '/includes/bruteforce/class-helpers.php';
			require_once WTITAN_PLUGIN_DIR . '/includes/bruteforce/class-limit-login-attempts.php';
		}

		// Tweaks.
		require_once WTITAN_PLUGIN_DIR . '/includes/tweaks/class-security-tweaks.php';

		if ( get_option( 'titan_strong_password' ) ) {
			require_once WTITAN_PLUGIN_DIR . '/includes/tweaks/password-requirements/boot.php';
		}

		// Logger.
		require_once WTITAN_PLUGIN_DIR . '/includes/logger/class-logger-writter.php';
		new \WBCR\Titan\Logger\Writter();

		// Antispam.
		require_once WTITAN_PLUGIN_DIR . '/includes/antispam/boot.php';
	}

	/**
	 * @return bool
	 */
	public function is_premium() {
		if ( $this->premium->is_active() ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get the data to be logged.
	 *
	 * @param array<string, mixed> $data The data.
	 *
	 * @return array<string, mixed> The merged data.
	 */
	public function get_logger_data( $data = [] ) {
		$antispam_stats = get_option( 'antispam_stats', [] );
		$premium_active = $this->is_premium();

		$advanced_spam_filter_enabled = (bool) get_option( 'titan_advanced_spam_filter', false );
		$schedule_backup              = (string) get_option( 'titan_schedule_backup', 'off' );
		$remove_old_data              = (bool) get_option( 'titan_remove_old_data', false );
		$backup_store                 = (string) get_option( 'titan_backup_store', 'local' );
		$backup_files_per_iteration   = (int) get_option( 'titan_backup_files_per_iteration', 100 );

		$two_factor_enabled = false;
		if ( class_exists( '\\WBCR\\TFA\\TwoFactor' ) ) {
			$query              = new \WP_User_Query(
				[
					'meta_key'    => 'titan_2fa_enable',
					'meta_value'  => 'yes',
					'fields'      => 'ids',
					'number'      => 1,
					'count_total' => false,
				]
			);
			$two_factor_enabled = ! empty( $query->get_results() );
		}

		return array_merge(
			$data,
			[
				// Stats.
				'spam_blocked_total'          => isset( $antispam_stats['blocked_total'] ) ? (int) $antispam_stats['blocked_total'] : 0,

				// Anti-spam feature states.
				'antispam_mode'               => (bool) get_option( 'titan_antispam_mode', true ),
				'save_spam_comments'          => (bool) get_option( 'titan_save_spam_comments', true ),
				'comment_form_privacy_notice' => (bool) get_option( 'titan_comment_form_privacy_notice', false ),
				'advanced_spam_filter'        => $advanced_spam_filter_enabled,

				// Security tweaks.
				'strong_password'             => (bool) get_option( 'titan_strong_password', false ),
				'strong_password_min_role'    => (string) get_option( 'titan_strong_password_min_role', 'administrator' ),
				'protect_author_get'          => (bool) get_option( 'titan_protect_author_get', false ),
				'remove_x_pingback'           => (bool) get_option( 'titan_remove_x_pingback', false ),
				'remove_html_comments'        => (bool) get_option( 'titan_remove_html_comments', false ),
				'remove_meta_generator'       => (bool) get_option( 'titan_remove_meta_generator', false ),
				'remove_js_version'           => (bool) get_option( 'titan_remove_js_version', false ),
				'remove_style_version'        => (bool) get_option( 'titan_remove_style_version', false ),

				// Other features.
				'complete_uninstall'          => (bool) get_option( 'titan_complete_uninstall', false ),

				// Premium-only feature state.
				'premium_active'              => $premium_active,
				'schedule_backup'             => $schedule_backup,
				'backup_schedule_enabled'     => $premium_active && 'off' !== $schedule_backup,
				'remove_old_data'             => $remove_old_data,
				'remove_old_data_enabled'     => $premium_active && $remove_old_data,
				'backup_store'                => $backup_store,
				'backup_files_per_iteration'  => $backup_files_per_iteration,
				'two_factor_enabled'          => $premium_active && $two_factor_enabled,
			]
		);
	}

	/**
	 * Register survey data.
	 *
	 * @param array<string, mixed> $data The data in Formbricks format.
	 *
	 * @return array<string, mixed> The data in Formbricks format.
	 * @see survey.js in SDK.
	 */
	public function get_survey_data( $data ) {
		$install_days_number = intval( ( time() - get_option( WTITAN_PLUGIN_NAMESPACE . '_install', time() ) ) / DAY_IN_SECONDS );

		// TODO: Add license check with the new dashboard.
		$data = [
			'environmentId' => 'cmioooiur4v4uad01gmey9tnn',
			'attributes'    => [
				'install_days_number' => $install_days_number,
				'free_version'        => WTITAN_PLUGIN_VERSION,
			],
		];

		return $data;
	}

	/**
	 * Mark internal Titan Security pages.
	 *
	 * @param string $hook_suffix Hook sufix.
	 *
	 * @return void
	 */
	public function mark_internal_page( $hook_suffix ) {
		if ( false !== strpos( $hook_suffix, 'titan-' ) ) {
			do_action( 'themeisle_internal_page', WTITAN_PLUGIN_DIR, 'dashboard' );
		}
	}
}
