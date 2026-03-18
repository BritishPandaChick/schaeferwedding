<?php
/**
 * Helpers functions
 *
 * @package Titan_Security
 */

namespace WBCR\Titan\Plugin;

// Exit if accessed directly.
use WBCR\Titan\Plugin;
use WBCR\Titan\WBCR_HTTP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Helper {

	/**
	 * Should show a page about the plugin or not.
	 *
	 * @return bool
	 */
	public static function is_need_show_setup_page() {
		$need_show_about = (int) get_option( 'titan_setup_wizard' );

		$is_ajax = self::doing_ajax();
		$is_cron = self::doing_cron();
		$is_rest = self::doing_rest_api();

		if ( $need_show_about && ! $is_ajax && ! $is_cron && ! $is_rest ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if the current request is a WP REST API request.
	 *
	 * Case #1: After WP_REST_Request initialisation
	 * Case #2: Support "plain" permalink settings
	 * Case #3: URL Path begins with wp-json/ (your REST prefix)
	 *          Also supports WP installations in subfolders
	 *
	 * @author matzeeable https://wordpress.stackexchange.com/questions/221202/does-something-like-is-rest-exist
	 * @since  2.1.0
	 * @return boolean
	 */
	public static function doing_rest_api() {
		$prefix     = rest_get_url_prefix();
		$rest_route = WBCR_HTTP::get( 'rest_route', null );
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST // (#1)
			|| ! is_null( $rest_route ) // (#2)
			&& strpos( trim( $rest_route, '\\/' ), $prefix, 0 ) === 0 ) {
			return true;
		}

		// (#3)
		$rest_url    = wp_parse_url( site_url( $prefix ) );
		$current_url = wp_parse_url( add_query_arg( [] ) );

		return strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
	}

	/**
	 * @return bool
	 * @since 2.1.0
	 */
	public static function doing_ajax() {
		if ( function_exists( 'wp_doing_ajax' ) ) {
			return wp_doing_ajax();
		}

		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	/**
	 * @return bool
	 * @since 2.1.0
	 */
	public static function doing_cron() {
		if ( function_exists( 'wp_doing_cron' ) ) {
			return wp_doing_cron();
		}

		return defined( 'DOING_CRON' ) && DOING_CRON;
	}

	/**
	 * Build a TOTP provisioning URL for QR rendering.
	 *
	 * @param string      $name User label (for example user@host).
	 * @param string      $secret_key Base32 secret.
	 * @param string|null $title Issuer name.
	 *
	 * @return string
	 */
	public static function build_totp_qr_value( $name, $secret_key, $title = null ) {
		$url = 'otpauth://totp/' . rawurlencode( $name ) . '?secret=' . $secret_key;
		if ( null !== $title ) {
			$url .= '&issuer=' . rawurlencode( $title );
		}

		return $url;
	}
}
