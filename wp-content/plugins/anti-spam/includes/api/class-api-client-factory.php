<?php
/**
 * API Client Factory.
 * 
 * @package Titan_Security
 */

namespace WBCR\Titan\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API Client Factory.
 */
class ApiClientFactory {

	/**
	 * Cached API client instance
	 *
	 * @var ApiClient|null
	 */
	private static $instance = null;

	/**
	 * Get a configured API client instance
	 *
	 * @param bool $force_new Force creation of new instance.
	 * @return ApiClient
	 */
	public static function create( $force_new = false ) {
		if ( $force_new || null === self::$instance ) {
			self::$instance = new ApiClient();
		}

		return self::$instance;
	}
}
