<?php

namespace Smush\Core\Cache;

class Cache_Helper {
	private static $clear_cache_action = 'wp_smush_clear_page_cache';
	private static $show_cache_notice_transient = 'wp_smush_show_cache_notice';

	/**
	 * Static instance
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Static instance getter
	 */
	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function clear_post_cache( $post_id ) {
		do_action( 'wp_smush_post_cache_flush_required', $post_id );
	}

	public function clear_home_cache( $url ) {
		do_action( 'wp_smush_home_cache_flush_required', $url );
	}

	public function clear_full_cache( $notice_key = 'generic' ) {
		if ( ! has_action( self::$clear_cache_action ) && ! empty( $notice_key ) ) {
			// If no one is handling the cache clearing then show a notice
			set_transient( self::$show_cache_notice_transient, $notice_key );
		} else {
			do_action( self::$clear_cache_action );
		}
	}

	public function delete_notice_key() {
		delete_transient( self::$show_cache_notice_transient );
	}

	public function get_notice_key() {
		return get_transient( self::$show_cache_notice_transient );
	}

	/**
	 * Get clear_cache_action.
	 *
	 * @return string
	 */
	public static function get_clear_cache_action() {
		return self::$clear_cache_action;
	}

}
