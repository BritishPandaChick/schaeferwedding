<?php
/**
 * HTTP request handling functionality.
 *
 * @package Titan_Security
 */

namespace WBCR\Titan;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WBCR_HTTP
 * 
 * Handles HTTP request parameter retrieval and sanitization
 */
class WBCR_HTTP {

	/**
	 * Get request parameter value
	 * 
	 * @param string      $param    Parameter name.
	 * @param mixed       $default  Default value if parameter not found.
	 * @param bool|string $sanitize Sanitize function name or true for default sanitization.
	 * @param string      $method   Request method: 'REQUEST', 'GET', or 'POST'.
	 * 
	 * @return mixed
	 */
	private static function get_body( $param, $sanitize = false, $default = false, $method = 'REQUEST' ) {
		if ( empty( $param ) ) {
			return null;
		}

		$sanitize_function_name = 'sanitize_text_field';

		switch ( strtoupper( $method ) ) {
			case 'GET':
				$source = $_GET;
				break;
			case 'POST':
				$source = $_POST;
				break;
			case 'REQUEST':
			default:
				$source = $_REQUEST;
				break;
		}

		if ( is_string( $sanitize ) && $sanitize !== $sanitize_function_name ) {
			$sanitize_function_name = $sanitize;
		}

		if ( isset( $source[ $param ] ) ) {
			if ( is_array( $source[ $param ] ) ) {
				return ! empty( $sanitize ) ? self::recursive_array_map( $sanitize_function_name, $source[ $param ] ) : $source[ $param ];
			} else {
				if ( ! empty( $sanitize ) && is_callable( $sanitize_function_name ) ) {
					return call_user_func( $sanitize_function_name, $source[ $param ] );
				}
				return $source[ $param ];
			}
		}

		return $default;
	}

	/**
	 * Recursive sanitation for an array
	 *
	 * @param string               $function_name Sanitization function name.
	 * @param array<string, mixed> $array         Array to sanitize.
	 * 
	 * @return array<string, mixed>
	 * @throws \Exception If function is not defined.
	 */
	public static function recursive_array_map( $function_name, $array ) {
		foreach ( $array as $key => &$value ) {
			if ( is_array( $value ) ) {
				$value = self::recursive_array_map( $function_name, $value );
			} else {
				if ( ! function_exists( $function_name ) ) {
					throw new \Exception( 'Function ' . $function_name . ' is undefined.' );
				}

				$value = $function_name( $value );
			}
		}

		return $array;
	}

	/**
	 * Get value from REQUEST superglobal
	 *
	 * @param string      $param    Parameter name.
	 * @param mixed       $default  Default value if parameter not found.
	 * @param bool|string $sanitize Sanitize function name or true for default sanitization.
	 * 
	 * @return mixed
	 */
	public static function request( $param, $default = false, $sanitize = false ) {
		return self::get_body( $param, $sanitize, $default, 'REQUEST' );
	}

	/**
	 * Get value from GET superglobal
	 *
	 * @param string      $param    Parameter name.
	 * @param mixed       $default  Default value if parameter not found.
	 * @param bool|string $sanitize Sanitize function name or true for default sanitization.
	 * 
	 * @return mixed
	 */
	public static function get( $param, $default = false, $sanitize = false ) {
		return self::get_body( $param, $sanitize, $default, 'GET' );
	}

	/**
	 * Get value from POST superglobal
	 *
	 * @param string      $param    Parameter name.
	 * @param mixed       $default  Default value if parameter not found.
	 * @param bool|string $sanitize Sanitize function name or true for default sanitization.
	 * 
	 * @return mixed
	 */
	public static function post( $param, $default = false, $sanitize = false ) {
		return self::get_body( $param, $sanitize, $default, 'POST' );
	}
}
