<?php

namespace Smush\Core\Media_Library;

use Smush\Core\Media\Media_Item_Query;

/**
 * An un-opinionated scanner.
 * All it does is traverse attachments, the real work is supposed to be done by other controllers through actions and filters.
 * Supposed to handle parallel requests, each request handling a 'slice' of the total media items.
 */
class Media_Library_Scanner {
	private static $slice_size_max = 2500;
	private static $slice_size_min = 500;
	private static $slice_size_factor = 40;
	private static $slice_size_option_id = 'wp_smush_scan_slice_size';

	public function before_scan_library() {
		do_action( 'wp_smush_before_scan_library' );
	}

	public function scan_library_slice( $slice ) {
		$slice_size     = $this->get_slice_size();
		$query          = new Media_Item_Query();
		$attachment_ids = $query->fetch_slice_ids( $slice, $slice_size );
		$slice_data     = apply_filters( 'wp_smush_before_scan_library_slice', array(), $slice, $slice_size );

		foreach ( $attachment_ids as $attachment_id ) {
			$slice_data = apply_filters( 'wp_smush_scan_library_slice_handle_attachment', $slice_data, $attachment_id, $slice, $slice_size );
		}

		return apply_filters( 'wp_smush_after_scan_library_slice', $slice_data, $slice, $slice_size );
	}

	public function after_scan_library() {
		do_action( 'wp_smush_after_scan_library' );
	}

	public function get_slice_size() {
		$constant_value = $this->get_slice_size_constant();
		if ( $constant_value ) {
			return $constant_value;
		}

		$option_value = $this->get_slice_size_option();
		if ( $option_value ) {
			return $option_value;
		}

		return $this->calculate_default_slice_size();
	}

	private function calculate_default_slice_size() {
		$query              = new Media_Item_Query();
		$attachment_count   = $query->get_image_attachment_count();
		$default_slice_size = (int) ceil( $attachment_count / self::$slice_size_factor );
		if ( $default_slice_size > self::$slice_size_max ) {
			$default_slice_size = self::$slice_size_max;
		} elseif ( $default_slice_size < self::$slice_size_min ) {
			$default_slice_size = self::$slice_size_min;
		}

		return $default_slice_size;
	}

	public function reduce_slice_size_option() {
		$this->set_slice_size( self::$slice_size_min );
	}

	private function get_slice_size_option() {
		$option_value = (int) get_option( self::$slice_size_option_id, 0 );

		return max( $option_value, 0 );
	}

	private function get_slice_size_constant() {
		if ( ! defined( 'WP_SMUSH_SCAN_SLICE_SIZE' ) ) {
			return 0;
		}

		$constant_value = (int) WP_SMUSH_SCAN_SLICE_SIZE;

		return max( $constant_value, 0 );
	}

	/**
	 * @param $value
	 *
	 * @return void
	 */
	private function set_slice_size( $value ) {
		update_option( self::$slice_size_option_id, $value );
	}

	/**
	 * Get slice_size_option_id.
	 *
	 * @return string
	 */
	public static function get_slice_size_option_id() {
		return self::$slice_size_option_id;
	}

}
