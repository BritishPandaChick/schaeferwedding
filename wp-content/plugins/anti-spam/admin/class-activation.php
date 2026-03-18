<?php

namespace WBCR\Titan;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin activation and deactivation.
 */
class Activation {

	/**
	 * Runs activation actions.
	 *
	 * @since  6.0
	 */
	public static function activate() {
		$plugin_version_in_db   = self::get_plugin_version_in_db();
		$current_plugin_version = WTITAN_PLUGIN_VERSION;

		$tab          = "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t";
		$log_message  = "Plugin starts activation [START].\r\n";
		$log_message .= "{$tab}-Plugin Version in DB: {$plugin_version_in_db}\r\n";
		$log_message .= "{$tab}-Current Plugin Version: {$current_plugin_version}";

		require_once WTITAN_PLUGIN_DIR . '/includes/bruteforce/do_activate.php';

		\WBCR\Titan\Logger\Writter::info( $log_message );
	}

	/**
	 * Get previous plugin version.
	 *
	 * @return int|string
	 * @since  6.0
	 */
	public static function get_plugin_version_in_db() {
		if ( is_plugin_active_for_network( plugin_basename( WTITAN_PLUGIN_FILE ) ) ) {
			return get_site_option( 'titan_plugin_version', 0 );
		}

		return get_option( 'titan_plugin_version', 0 );
	}

	/**
	 * Run deactivation actions.
	 *
	 * @since  6.0
	 */
	public static function deactivate() {
		\WBCR\Titan\Logger\Writter::info( 'Plugin starts deactivate [START].' );

		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'titan_spam_batch_enqueue_spam' );
			as_unschedule_all_actions( 'titan_spam_batch_check_status' );
		}

		delete_transient( 'titan_spam_actions_scheduled' );

		\WBCR\Titan\Logger\Writter::info( 'Plugin has been deactivated [END]!' );
	}
}
