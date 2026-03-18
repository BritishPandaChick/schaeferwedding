<?php
namespace Smush\Core\Backups;

use Smush\Core\Media\Media_Item_Cache;
use Smush\Core\Media\Media_Item_Optimizer;

/**
 * Class Bulk_Restore
 *
 * Handles the bulk restoration of media items.
 *
 * @package Smush\Core\Backups
 */

class Bulk_Restore {
	/**
	 * @var array
	 */
	private $attachment_ids;

	/**
	 * Restored attachments count.
	 *
	 * @var int $restored_count
	 */
	private $restored_count = 0;

	/**
	 * Error counts by error code.
	 *
	 * @var array
	 */
	private $error_counts = array();

	/**
	 * Bulk_Restore constructor.
	 *
	 * @param array $attachment_ids Array of attachment IDs to restore.
	 */
	public function __construct( $attachment_ids ) {
		$this->attachment_ids = array_filter( $attachment_ids, 'is_numeric' );
	}

	/**
	 * Restore media items in bulk.
	 */
	public function bulk_restore() {
		foreach ( $this->attachment_ids as $index => $id ) {
			$media_item = Media_Item_Cache::get_instance()->get( $id );
			$optimizer  = new Media_Item_Optimizer( $media_item );
			$restored   = $optimizer->restore();
			if ( ! $restored ) {
				$error_code = $optimizer->get_restoration_errors()->get_error_code();
				if ( $error_code ) {
					$this->error_counts[ $error_code ] = ( $this->error_counts[ $error_code ] ?? 0 ) + 1;
				} else {
					// The media item could not be restored because the image is in a processing state.
					// We skip restoration for items in this state.
					unset( $this->attachment_ids[ $index ] );
				}
			} else {
				++$this->restored_count;
			}
		}

		do_action(
			'wp_smush_bulk_restore_completed',
			array(
				'type'                 => 'Bulk',
				'restored_count'       => $this->get_restored_count(),
				'total_count'          => $this->get_total_count(),
				'missing_backup_count' => $this->get_error_count( 'missing_backup' ),
			)
		);
	}

	/**
	 * Get the total number of attachments to restore.
	 *
	 * @return int
	 */
	public function get_total_count() {
		return count( $this->attachment_ids );
	}

	/**
	 * Get the total number of restored attachments.
	 *
	 * @return int
	 */
	public function get_restored_count() {
		return $this->restored_count;
	}

	/**
	 * Get the error count for a specific error code.
	 *
	 * @param string $error_code Error code.
	 *
	 * @return int
	 */
	public function get_error_count( $error_code ) {
		return $this->error_counts[ $error_code ] ?? 0;
	}
}
