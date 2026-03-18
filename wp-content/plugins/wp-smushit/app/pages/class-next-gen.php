<?php
/**
 * Local WebP page.
 *
 * @package Smush\App\Pages
 */

namespace Smush\App\Pages;

use Smush\App\Abstract_Summary_Page;
use Smush\App\Interface_Page;
use Smush\Core\Webp\Webp_Configuration;
use Smush\Core\Next_Gen\Next_Gen_Manager;
use Smush\Core\Next_Gen\Next_Gen_Settings_Ui_Controller;
use WP_Smush;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Next_Gen
 */
class Next_Gen extends Abstract_Summary_Page implements Interface_Page {
	/**
	 * Register meta boxes.
	 */
	public function register_meta_boxes() {
		parent::register_meta_boxes();

		$this->add_meta_box(
			'next-gen/upsell',
			__( 'Next-Gen Formats', 'wp-smushit' )
		);
	}

	/**
	 * Whether the wizard should be displayed.
	 *
	 * @since 3.9.0
	 *
	 * @return bool
	 */
	protected function is_wizard() {
		return false;
	}
}
