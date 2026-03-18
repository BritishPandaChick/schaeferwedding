<?php
/**
 * Antispam Module Class
 *
 * @package    WBCR\Titan
 */

namespace WBCR\Titan;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Antispam module class.
 *
 * Handles anti-spam mode management, AJAX toggling, and spam statistics
 * retrieval via the Premium API.
 *
 * @since 6.5.3
 */
class Antispam {

	/**
	 * @see self::app()
	 * @var Antispam
	 */
	private static $app;

	/**
	 * Request interval in hours
	 *
	 * @since 1.1
	 */
	const DEFAULT_REQUESTS_INTERVAL = 4;

	/**
	 * Request interval in hours, if server is unavailable
	 *
	 * @since 1.1
	 */
	const SERVER_UNAVAILABLE_INTERVAL = 1;

	/**
	 * @var bool
	 */
	public $mode;

	/**
	 * Initializes the Antispam module.
	 *
	 * Sets up module paths, loads the current anti-spam mode from options,
	 * and registers the AJAX handler for mode toggling.
	 */
	public function __construct() {
		self::$app  = $this;
		$this->mode = get_option( 'titan_antispam_mode', true );

		add_action( 'wp_ajax_wtitan-change-antispam-mode', [ $this, 'change_anti_spam_mode' ] );
	}

	/**
	 * @return Antispam
	 * @since  7.0
	 */
	public static function app() {
		return self::$app;
	}

	/**
	 * AJAX handler to enable or disable anti-spam protection.
	 *
	 * Validates the nonce and user capability, then updates the anti-spam
	 * mode option. Returns a JSON response with the result.
	 *
	 * @return void Outputs JSON response and terminates.
	 */
	public function change_anti_spam_mode() {
		check_ajax_referer( 'wtitan_change_antispam_mode' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json( [ 'error_message' => __( 'You don\'t have enough capability to edit this information.', 'anti-spam' ) ] );
		}

		if ( isset( $_POST['mode'] ) ) {

			$mode_name = sanitize_text_field( $_POST['mode'] );

			update_option( 'titan_antispam_mode', $mode_name );

			if ( (bool) $mode_name ) {
				wp_send_json(
					[
						'message' => __( 'Anti-spam successfully enabled', 'anti-spam' ),
						'mode'    => $mode_name,
					]
				);
			} else {
				wp_send_json( [ 'message' => __( 'Anti-spam successfully disabled', 'anti-spam' ) ] );
			}
		}
	}
}
