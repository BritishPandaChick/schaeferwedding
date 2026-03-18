<?php
/**
 * License management functionality.
 *
 * @package Titan_Security
 */

namespace WBCR\Titan;

/**
 * License management class
 *
 * Handles premium license activation, deactivation, and validation.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WBCR_License
 *
 * Standalone license provider with dummy methods for initial implementation.
 * All methods return hardcoded values to be replaced with real API calls later.
 */
class WBCR_License {

	/**
	 * Get the license data.
	 *
	 * @return bool|\stdClass
	 */
	public static function get_data() {
		if ( ! defined( 'WTITANP_PLUGIN_NAMESPACE' ) ) {
			return false;
		}

		return get_option( WTITANP_PLUGIN_NAMESPACE . '_license_data' );
	}

	/**
	 * Check if Pro is available
	 *
	 * @return  bool
	 */
	public function is_pro_active() {
		return defined( 'WTITANP_PLUGIN_VERSION' );
	}

	/**
	 * Get license key
	 *
	 * @return string License key
	 */
	public function get_key() {
		$license = self::get_data();

		if ( false === $license ) {
			return 'free';
		}

		if ( ! isset( $license->key ) ) {
			return 'free';
		}

		return $license->key;
	}

	/**
	 * Check if license is currently active and valid
	 *
	 * @return bool True if active
	 */
	public function is_active() {
		$status = self::get_data();

		if ( ! $status ) {
			return false;
		}

		if ( ! isset( $status->license ) ) {
			return false;
		}

		if ( 'valid' !== $status->license ) {
			return false;
		}

		return true;
	}

	/**
	 * Backward-compatible alias for older premium code.
	 *
	 * @return bool
	 * @deprecated Use is_active.
	 */
	public function is_activate() {
		return $this->is_active();
	}

	/**
	 * Backward-compatible accessor expected by legacy premium integrations.
	 *
	 * @return self
	 * @deprecated Use the class itself.
	 */
	public function get_license() {
		return $this;
	}

	/**
	 * Get a setting value
	 *
	 * @param string $key Setting key.
	 *
	 * @return mixed Setting value or null
	 */
	public function get_setting( $key ) {
		$data = self::get_data();

		if ( false === $data ) {
			return null;
		}

		if ( 'plugin_id' === $key ) {
			return $data->download_id ?? null;
		}

		return null;
	}

	/**
	 * Toggle license.
	 *
	 * @param string $action License action.
	 * @param string $key    License key.
	 *
	 * @return array<string, mixed>|\WP_Error Response.
	 */
	public function toggle_license( $action, $key ) {
		if ( 'deactivate' === $action ) {
			$key = apply_filters( 'product_titan_license_key', 'free' );
		}

		$response = apply_filters( 'themeisle_sdk_license_process_titan', $key, $action );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return [
			'success' => true,
			'message' => 'activate' === $action ? __( 'Activated.', 'anti-spam' ) : __( 'Deactivated', 'anti-spam' ),
			'license' => [
				'key'    => apply_filters( 'product_titan_license_key', 'free' ),
				'status' => apply_filters( 'product_titan_license_status', false ),
			],
		];
	}
}
