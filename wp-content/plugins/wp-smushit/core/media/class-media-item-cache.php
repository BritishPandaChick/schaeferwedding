<?php

namespace Smush\Core\Media;

/**
 * TODO: maybe reset the media item when:
 * - a new size is added
 */
class Media_Item_Cache {
	private static $cache_group = 'wp-smushit';
	/**
	 * Static instance
	 *
	 * @var self
	 */
	private static $instance;
	/**
	 * @var Media_Item[]
	 */
	private $media_items;

	/**
	 * Static instance getter
	 */
	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function has( $id ) {
		$media_item = $this->get_from_cache( $id );

		return ! empty( $media_item );
	}

	/**
	 * @param $id
	 *
	 * @return Media_Item|null
	 */
	public function get( $id ) {
		$media_item = $this->get_from_cache( $id );
		if ( ! $media_item ) {
			$media_item = new Media_Item( $id );
			$this->save_to_cache( $id, $media_item );
		}

		return $media_item;
	}

	/**
	 * @param $id
	 *
	 * @return Media_Item|null
	 */
	private function get_from_cache( $id ) {
		return $this->get_array_value(
			$this->media_items,
			$this->make_key( $id )
		);
	}

	private function make_key( $id ) {
		return "wp-smush-media-item-$id";
	}

	private function save_to_cache( $id, $media_item ) {
		$this->media_items[ $this->make_key( $id ) ] = $media_item;
	}

	public function remove( $id ) {
		unset( $this->media_items[ $this->make_key( $id ) ] );
	}

	private function get_array_value( $array, $key ) {
		return $array && isset( $array[ $key ] )
			? $array[ $key ]
			: null;
	}

	public function reset_all() {
		foreach ( $this->media_items as $media_item ) {
			$media_item->reset();
		}
	}

	/**
	 * Get animated_meta_key.
	 *
	 * @return mixed
	 */
	public static function get_animated_meta_key() {
		return self::$animated_meta_key;
	}


	/**
	 * Get backup_sizes_meta_key.
	 *
	 * @return mixed
	 */
	public static function get_backup_sizes_meta_key() {
		return self::$backup_sizes_meta_key;
	}


	/**
	 * Get default_backup_key.
	 *
	 * @return mixed
	 */
	public static function get_default_backup_key() {
		return self::$default_backup_key;
	}


	/**
	 * Get ignored_meta_key.
	 *
	 * @return mixed
	 */
	public static function get_ignored_meta_key() {
		return self::$ignored_meta_key;
	}


	/**
	 * Get size_key_full.
	 *
	 * @return mixed
	 */
	public static function get_size_key_full() {
		return self::$size_key_full;
	}


	/**
	 * Get size_key_scaled.
	 *
	 * @return mixed
	 */
	public static function get_size_key_scaled() {
		return self::$size_key_scaled;
	}


	/**
	 * Get transparent_meta_key.
	 *
	 * @return mixed
	 */
	public static function get_transparent_meta_key() {
		return self::$transparent_meta_key;
	}

}
