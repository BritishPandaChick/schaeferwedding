<?php
/**
 * Vulnerabilities Class
 * 
 * @package Titan_Security
 */

namespace WBCR\Titan;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles vulnerability detection and management for WordPress installations.
 *
 * This class manages vulnerability data retrieval, caching, and cache invalidation
 * for WordPress core, plugins, and themes.
 */
class Vulnerabilities {

	/**
	 * @see self::app()
	 * @var Vulnerabilities|null
	 */
	private static $app;

	/**
	 * Vulnerability data storage.
	 * 
	 * @var array<string, array<string, mixed>>
	 */
	public $vulnerabilities_data = [];

	/**
	 * API Client instance
	 *
	 * @var \WBCR\Titan\Api\ApiClient
	 */
	private $api_client;

	/**
	 * Vulnerabilities constructor.
	 */
	public function __construct() {
		$this->api_client = \WBCR\Titan\Api\ApiClientFactory::create();

		$this->setup_cache_invalidation_hooks();
	}

	/**
	 * Setup hooks to clear cache when plugins/themes are updated.
	 * 
	 * @return void
	 */
	private function setup_cache_invalidation_hooks(): void {
		add_action( 'upgrader_process_complete', [ $this, 'clear_vulnerability_cache' ], 10, 0 );
		add_action( 'activated_plugin', [ $this, 'clear_vulnerability_cache' ] );
		add_action( 'deactivated_plugin', [ $this, 'clear_vulnerability_cache' ] );
		add_action( 'deleted_plugin', [ $this, 'clear_vulnerability_cache' ] );
		add_action( 'deleted_theme', [ $this, 'clear_vulnerability_cache' ] );
		add_action( 'switch_theme', [ $this, 'clear_vulnerability_cache' ] );
	}

	/**
	 * @return Vulnerabilities
	 */
	public static function app() {
		if ( null === self::$app ) {
			self::$app = new self();
		}
		return self::$app;
	}

	/**
	 * Get vulnerability data from modern API
	 *
	 * @param bool $force_refresh Force refresh from API instead of using cache.
	 * @return array<string, array<string, mixed>>
	 */
	public function get_vulnerability_data( $force_refresh = false ) {
		if ( ! \WBCR\Titan\Plugin::app()->is_premium() ) {
			return [];
		}

		$cache_key  = 'titan_vulnerability_data';
		$cache_time = 12 * HOUR_IN_SECONDS;

		if ( ! $force_refresh ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data ) {
				$this->vulnerabilities_data = $cached_data;
				return $this->vulnerabilities_data;
			}
		}

		$api_response = $this->api_client->check_vulnerabilities();
		
		if ( null === $api_response ) {
			$error = $this->api_client->get_last_error();
			if ( $error ) {
				\WBCR\Titan\Logger\Writter::error( 'Vulnerability API Error: ' . $error['message'] );
			}
			
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data ) {
				$this->vulnerabilities_data = $cached_data;
				return $this->vulnerabilities_data;
			}
			
			return [];
		}

		if ( ! isset( $api_response['success'] ) ) {
			\WBCR\Titan\Logger\Writter::error( 'Vulnerability API Error: Invalid response format - missing success flag' );
			return $this->get_cached_data_fallback( $cache_key );
		}

		if ( ! $api_response['success'] ) {
			$error_message = isset( $api_response['error'] ) ? $api_response['error'] : 'Unknown API error';
			\WBCR\Titan\Logger\Writter::error( 'Vulnerability API Error: ' . $error_message );
			return $this->get_cached_data_fallback( $cache_key );
		}

		if ( ! isset( $api_response['data'] ) ) {
			\WBCR\Titan\Logger\Writter::error( 'Vulnerability API Error: Invalid response format - missing data' );
			return $this->get_cached_data_fallback( $cache_key );
		}

		// Process and filter the data.
		$processed_data = $this->process_vulnerability_data( $api_response['data'] );
		
		set_transient( $cache_key, $processed_data, $cache_time );
		
		$this->vulnerabilities_data = $processed_data;
		return $this->vulnerabilities_data;
	}

	/**
	 * Process and filter vulnerability data
	 *
	 * @param array<string, mixed> $api_data Raw data from API.
	 * @return array<string, array<string, mixed>> Processed vulnerability data
	 */
	private function process_vulnerability_data( $api_data ) {
		$processed = [];

		foreach ( $api_data as $software_slug => $software_data ) {
			if ( ! isset( $software_data['vulnerabilities'] ) ) {
				continue;
			}

			// Filter out informational vulnerabilities.
			$filtered_vulnerabilities = array_filter( 
				$software_data['vulnerabilities'], 
				function ( $vuln ) {
					return empty( $vuln['informational'] ) || true !== $vuln['informational'];
				}
			);

			$filtered_vulnerabilities = array_values( $filtered_vulnerabilities );

			if ( ! empty( $filtered_vulnerabilities ) ) {
				$processed[ $software_slug ]                    = $software_data;
				$processed[ $software_slug ]['vulnerabilities'] = $filtered_vulnerabilities;
			}
		}

		return $processed;
	}

	/**
	 * Get cached data as fallback when API fails
	 *
	 * @param string $cache_key Cache key to check.
	 * @return array<string, array<string, mixed>> Cached data or empty array
	 */
	private function get_cached_data_fallback( $cache_key ) {
		$cached_data = get_transient( $cache_key );
		if ( false !== $cached_data ) {
			$this->vulnerabilities_data = $cached_data;
			return $this->vulnerabilities_data;
		}
		
		return [];
	}

	/**
	 * Clear vulnerability cache
	 * Called when plugins/themes are updated, activated, deactivated, etc.
	 * 
	 * @return void
	 */
	public function clear_vulnerability_cache(): void {
		$cache_key = 'titan_vulnerability_data';
		delete_transient( $cache_key );
		
		// Clear the local data as well.
		$this->vulnerabilities_data = [];
	}

	/**
	 * Get total count of vulnerabilities
	 *
	 * @return int
	 */
	public function get_count() {
		$vulnerability_data = $this->get_vulnerability_data();
		
		$total_count = 0;
		foreach ( $vulnerability_data as $software_data ) {
			if ( isset( $software_data['vulnerabilities'] ) ) {
				$total_count += count( $software_data['vulnerabilities'] );
			}
		}
		
		return $total_count;
	}
}
