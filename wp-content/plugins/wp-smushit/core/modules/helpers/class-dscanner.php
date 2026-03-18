<?php
/**
 * Smush directory smush scanner: DScanner class
 *
 * @package Smush\Core\Modules\Helpers
 * @since 2.8.1
 *
 * @author Anton Vanyukov <anton@incsub.com>
 *
 * @copyright (c) 2018, Incsub (http://incsub.com)
 */

namespace Smush\Core\Modules\Helpers;

use WP_Smush;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DScanner
 *
 * @since 2.8.1
 */
class DScanner {

	/**
	 * Indicates if a scan is in process
	 *
	 * @var bool
	 */
	private $is_scanning = false;

	/**
	 * Indicates the current step being scanned
	 *
	 * @var int
	 */
	private $current_step = 0;

	/**
	 * Options names
	 */
	private static $is_scanning_slug = 'wp-smush-files-scanning';
	private static $current_step_option_id = 'wp-smush-scan-step';

	/**
	 * Refresh status variables.
	 */
	private function refresh_status() {
		$this->is_scanning  = get_transient( self::$is_scanning_slug );
		$this->current_step = (int) get_option( self::$current_step_option_id );
	}

	/**
	 * Initializes the scan.
	 */
	public function init_scan() {
		set_transient( self::$is_scanning_slug, true, 60 * 5 ); // 5 minutes max
		update_option( self::$current_step_option_id, 0 );
		$this->refresh_status();
	}

	/**
	 * Reset the scan as if it weren't being executed (on finish and cancel).
	 */
	public function reset_scan() {
		delete_transient( self::$is_scanning_slug );
		delete_option( self::$current_step_option_id );
		$this->refresh_status();
	}

	/**
	 * Update the current step being scanned.
	 *
	 * @param int $step  Current scan step.
	 */
	public function update_current_step( $step ) {
		update_option( self::$current_step_option_id, absint( $step ) );
		$this->refresh_status();
	}

	/**
	 * Get the current scan step being scanned.
	 *
	 * @return mixed
	 */
	public function get_current_scan_step() {
		$this->refresh_status();
		return $this->current_step;
	}

	/**
	 * Return the number of total steps to finish the scan.
	 *
	 * @return int
	 */
	public function get_scan_steps() {
		return count( WP_Smush::get_instance()->core()->mod->dir->get_scanned_images() );
	}

	/**
	 * Check if a scanning is in process
	 *
	 * @return bool
	 */
	public function is_scanning() {
		$this->refresh_status();
		return $this->is_scanning;
	}

}
