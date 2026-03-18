<?php

namespace Smush\Core\Resize;

use Smush\Core\Media\Media_Item_Stats;

class Resize_Media_Item_Stats extends Media_Item_Stats {
	/**
	 * Resize Width.
	 *
	 * @var int
	 */
	private $resize_width;

	/**
	 * Resize Height
	 *
	 * @var int
	 */
	private $resize_height;

	public function has_no_savings() {
		return 0 === $this->get_bytes() && ! $this->is_empty();
	}

	/**
	 * @param int $resize_width
	 *
	 * @return Resize_Media_Item_Stats
	 */
	public function set_resize_width( $resize_width ) {
		$this->resize_width = (int) $resize_width;

		return $this;
	}

	public function get_resize_width() {
		return $this->resize_width;
	}

	/**
	 * @param int $resize_height
	 *
	 * @return Resize_Media_Item_Stats
	 */
	public function set_resize_height( $resize_height ) {
		$this->resize_height = (int) $resize_height;

		return $this;
	}

	public function get_resize_height() {
		return $this->resize_height;
	}

	public function to_array() {
		$stats                  = parent::to_array();
		$stats['resize_width']  = $this->get_resize_width();
		$stats['resize_height'] = $this->get_resize_height();

		return $stats;
	}

	public function from_array( $stats ) {
		parent::from_array( $stats );

		$this->set_resize_width( $this->get_array_value( $stats, 'resize_width' ) );
		$this->set_resize_height( $this->get_array_value( $stats, 'resize_height' ) );
	}
}
