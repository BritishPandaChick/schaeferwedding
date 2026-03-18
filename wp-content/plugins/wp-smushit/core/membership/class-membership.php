<?php

namespace Smush\Core\Membership;

use Smush\Core\Hub_Connector;
use WPMUDEV\Hub\Connector\Data;

class Membership {
	/**
	 * Static instance
	 *
	 * @var self
	 */
	private static $instance;

	protected function __construct() {
		$this->is_pro = false;
	}

	/**
	 * Static instance getter
	 */
	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @var boolean
	 */
	protected $is_pro;

	public function get_apikey() {
		return false;
	}

	/**
	 * Validate the installation.
	 *
	 * @param bool $force Force revalidation.
	 *
	 * @return void
	 */
	public function validate_install( $force = false ) {
		$this->is_pro = false;
	}

	/**
	 * Check if the membership is pro.
	 *
	 * @return bool
	 */
	public function is_pro() {
		return $this->is_pro;
	}

	/**
	 * Check if the user has access to the hub.
	 *
	 * Warning: This method do not support old free users.
	 *
	 * @return bool
	 */
	public function has_access_to_hub() {
		if ( $this->is_pro() ) {
			return true;
		}

		if ( class_exists( 'WPMUDEV_Dashboard' ) && method_exists( 'WPMUDEV_Dashboard_Api', 'get_membership_status' ) ) {
			// Possible values: full, single, free, expired, paused, unit.
			$plan = \WPMUDEV_Dashboard::$api->get_membership_status();
		} elseif ( Hub_Connector::has_access() && class_exists( '\WPMUDEV\Hub\Connector\Data' ) ) {
			$plan = Data::get()->membership_type();
		} else {
			return false;
		}

		return in_array( $plan, array( 'full', 'single', 'free', 'unit' ), true );
	}

	/**
	 * Check if access to the Hub access is required to use the API.
	 *
	 * @return bool
	 */
	public function is_api_hub_access_required() {
		$is_pre_3_22_site = get_site_option( 'wp_smush_pre_3_22_site' );
		if ( $is_pre_3_22_site ) {
			return false;
		}

		return ! $this->has_access_to_hub();
	}
}
