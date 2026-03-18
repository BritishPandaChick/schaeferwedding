<?php
/**
 * API Client.
 * 
 * @package Titan_Security
 */

namespace WBCR\Titan\Api;

use WBCR\Titan\WBCR_License;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Modern API Client for Titan Site Scanner
 */
class ApiClient {

	/**
	 * License key for authentication
	 *
	 * @var string
	 */
	private $license_key;

	/**
	 * Site URL for requests
	 *
	 * @var string
	 */
	private $site_url;

	/**
	 * Last error from API request
	 *
	 * @var array<string, mixed>|null
	 */
	private $last_error;

	/**
	 * Constructor - automatically detects license and site URL
	 */
	public function __construct() {
		$this->license_key = $this->detect_license_key();
		$this->site_url    = get_site_url();
	}

	/**
	 * Get the last error from API request
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_last_error() {
		return $this->last_error;
	}

	/**
	 * Clear the last error
	 *
	 * @return void
	 */
	public function clear_last_error() {
		$this->last_error = null;
	}

	/**
	 * Check spam content
	 *
	 * @param array<string, mixed> $data Spam check data.
	 * @return array<string, mixed>|null
	 */
	public function check_spam( array $data ) {
		return $this->request( 'POST', 'spam', $data );
	}

	/**
	 * Get spam check status from queue
	 *
	 * @param string $uid Unique identifier for spam check.
	 * @return array<string, mixed>|null
	 */
	public function get_spam_status( $uid ) {
		return $this->request( 'GET', "spam/{$uid}" );
	}

	/**
	 * Check vulnerabilities for WordPress core, plugins, and themes
	 * 
	 * @return array<string, mixed>|null
	 */
	public function check_vulnerabilities() {
		$software_list = $this->get_software_list();
		return $this->request( 'POST', 'vulnerabilities/check', $software_list );
	}

	/**
	 * Get current WordPress software list for vulnerability checking
	 *
	 * @return array<int, array<string, mixed>> Array of WordPress core, plugins, and themes
	 */
	public function get_software_list() {
		global $wp_version;
		
		$software_list = [];
		
		// Add WordPress core.
		$software_list[] = [
			'slug'    => 'wordpress',
			'version' => $wp_version,
			'type'    => 'core',
		];
		
		// Add all plugins (active and inactive).
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		
		$all_plugins = get_plugins();
		
		foreach ( $all_plugins as $plugin_path => $plugin_data ) {
			$plugin_slug = dirname( $plugin_path );
			
			// Handle single-file plugins.
			if ( '.' === $plugin_slug ) {
				$plugin_slug = basename( $plugin_path, '.php' );
			}
			
			$software_list[] = [
				'slug'    => $plugin_slug,
				'version' => $plugin_data['Version'],
				'type'    => 'plugin',
			];
		}
		
		// Add all themes (active and inactive).
		$all_themes = wp_get_themes();
		
		foreach ( $all_themes as $theme_slug => $theme_obj ) {
			if ( ! $theme_obj->errors() ) {
				$software_list[] = [
					'slug'    => $theme_slug,
					'version' => $theme_obj->get( 'Version' ),
					'type'    => 'theme',
				];
			}
		}
		
		return $software_list;
	}

	/**
	 * Make HTTP request to API
	 *
	 * @param string                $method     HTTP method (GET, POST, PUT, DELETE).
	 * @param string                $endpoint   API endpoint path.
	 * @param array<mixed>          $data       Request data.
	 * @param array<string, string> $headers    Additional headers.
	 * @return array<string, mixed>|null Response data or null on error.
	 */
	public function request( $method, $endpoint, array $data = [], array $headers = [] ) {
		$this->clear_last_error();

		$url = WTITAN_PLUGIN_API . ltrim( $endpoint, '/' );

		$default_headers = [
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
			'X-Site-Url'   => $this->site_url,
			'User-Agent'   => $this->get_user_agent(),
		];

		$this->license_key = $this->detect_license_key();
		// Add authorization header (always present, either license or 'free').
		$default_headers['Authorization'] = 'Bearer ' . base64_encode( $this->license_key );

		$headers = array_merge( $default_headers, $headers );

		$args = [
			'method'  => strtoupper( $method ),
			'headers' => $headers,
			'timeout' => 30,
		];

		if ( in_array( strtoupper( $method ), [ 'GET', 'DELETE' ], true ) ) {
			if ( ! empty( $data ) ) {
				$url .= '?' . http_build_query( $data );
			}
		} elseif ( ! empty( $data ) ) {
				$json_body = wp_json_encode( $data );
			if ( false === $json_body ) {
				$this->last_error = [
					'type'    => 'encoding_error',
					'message' => 'Failed to encode request data as JSON',
					'code'    => 'json_encode_failed',
				];
				return null;
			}
				$args['body'] = $json_body;
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->last_error = [
				'type'    => 'wp_error',
				'message' => $response->get_error_message(),
				'code'    => $response->get_error_code(),
			];
			return null;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		$decoded_response = json_decode( $response_body, true );

		if ( $response_code < 200 || $response_code >= 300 ) {
			$this->last_error = [
				'type'     => 'http_error',
				'message'  => $decoded_response['message'] ?? __( 'HTTP Error', 'anti-spam' ),
				'code'     => $response_code,
				'response' => $decoded_response,
			];
			return null;
		}

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->last_error = [
				'type'    => 'json_error',
				'message' => __( 'Failed to decode JSON response', 'anti-spam' ),
				'code'    => json_last_error(),
			];
			return null;
		}

		if ( isset( $decoded_response['status'] ) && 'fail' === $decoded_response['status'] ) {
			$this->last_error = [
				'type'    => 'api_error',
				'message' => $decoded_response['error'] ?? __( 'Unknown API error', 'anti-spam' ),
				'code'    => $decoded_response['code'] ?? 'unknown',
			];
			return null;
		}

		return $decoded_response['response'] ?? $decoded_response;
	}

	/**
	 * Detect license key from License manager or default to 'free'
	 *
	 * @return string
	 */
	private function detect_license_key() {
		$license_manager = new WBCR_License();
		$license         = $license_manager->get_key();
		return $license;
	}

	/**
	 * Get User-Agent string for requests
	 *
	 * @return string
	 */
	private function get_user_agent() {
		global $wp_version;
		
		$plugin_version = defined( 'WTITAN_PLUGIN_VERSION' ) ? WTITAN_PLUGIN_VERSION : '1.0.0';
		
		return sprintf(
			'TitanSecurity/%s WordPress/%s PHP/%s',
			$plugin_version,
			$wp_version,
			PHP_VERSION
		);
	}

	/**
	 * Get current license key
	 *
	 * @return string
	 */
	public function get_license_key() {
		return $this->license_key;
	}

	/**
	 * Get current site URL
	 *
	 * @return string
	 */
	public function get_site_url() {
		return $this->site_url;
	}

	/**
	 * Check if client has valid license (not 'free')
	 *
	 * @return bool
	 */
	public function has_license() {
		return 'free' !== $this->license_key;
	}
}
