<?php

namespace Smush\Core\Membership;

use Smush\Core\Controller;
use Smush\Core\Helper;

class Membership_Controller extends Controller {
	/**
	 * @var Membership
	 */
	private $membership;

	public function __construct() {
		$this->membership = Membership::get_instance();

	}

}
