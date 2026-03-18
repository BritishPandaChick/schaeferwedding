<?php

namespace Smush\Core;

class Shim implements \Countable {
	private static function is_string_item( $name ) {
		return self::starts_with_get( $name ) && self::ends_with_string_type( $name );
	}

	private static function is_array_type( $name ) {
		return $name === 'to_array';
	}

	private static function starts_with_get( $name ) {
		return str_starts_with( $name, 'get' );
	}

	private static function ends_with_string_type( $name ) {
		return (bool) preg_match( '/(?:_name|_key|_option_id)$/', $name );
	}

	public function __call( $name, $arguments ) {
		if ( self::log_all() ) {
			error_log( sprintf( 'Smush Shim: Missing method %s called with args: %s', $name, json_encode( $arguments ) ) );
		}

		if ( self::is_boolean_item( $name ) ) {
			return false;
		}

		if ( self::is_string_item( $name ) ) {
			return '';
		}

		if ( self::is_array_type( $name ) ) {
			return [];
		}

		if ( self::log_risky() ) {
			error_log( sprintf( 'Smush Shim: Returning Shim object for method %s. This could be risky.', $name ) );
		}

		return new self();
	}

	public function __get( $name ) {
		if ( self::is_boolean_item( $name ) ) {
			return false;
		}

		return new self();
	}

	public static function __callStatic( $name, $arguments ) {
		if ( self::log_all() ) {
			error_log( sprintf( 'Smush Shim: Missing static method %s called with args: %s', $name, json_encode( $arguments ) ) );
		}

		if ( self::is_boolean_item( $name ) ) {
			return false;
		}

		if ( self::is_string_item( $name ) ) {
			return '';
		}

		if ( self::log_risky() ) {
			error_log( sprintf( 'Smush Shim: Returning Shim object for static method %s. This could be risky.', $name ) );
		}

		return new self();
	}

	#[\ReturnTypeWillChange]
	public function count() {
		return 0;
	}

	public function __toString() {
		return '';
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	private static function is_boolean_item( $name ) {
		return str_starts_with( $name, 'is_' );
	}

	private static function log_all() {
		return self::get_log_type() === 'all';
	}

	private static function log_risky() {
		return self::get_log_type() === 'risky';
	}

	private static function get_log_type() {
		return defined( 'WP_SMUSH_LOG_SHIM_CALLS' )
			? constant( 'WP_SMUSH_LOG_SHIM_CALLS' )
			: '';
	}
}
