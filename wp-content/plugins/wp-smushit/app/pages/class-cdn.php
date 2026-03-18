<?php
/**
 * CDN page.
 *
 * @package Smush\App\Pages
 */

namespace Smush\App\Pages;

use Smush\App\Abstract_Summary_Page;
use Smush\App\Interface_Page;
use Smush\Core\CDN\CDN_Helper;
use WP_Smush;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class CDN
 */
class CDN extends Abstract_Summary_Page implements Interface_Page {
	/**
	 * Register meta boxes.
	 */
	public function register_meta_boxes() {
		parent::register_meta_boxes();

		$this->add_meta_box(
			'cdn/upsell',
			__( 'CDN', 'wp-smushit' )
		);
	}
}
