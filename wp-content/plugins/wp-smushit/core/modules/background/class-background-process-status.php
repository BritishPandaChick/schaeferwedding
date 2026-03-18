<?php

namespace Smush\Core\Modules\Background;

class Background_Process_Status {
	private static $processing = 'in_processing';
	private static $cancelled = 'is_cancelled';
	private static $completed = 'is_completed';
	private static $dead = 'is_dead';
	private static $total_items = 'total_items';
	private static $processed_items = 'processed_items';
	private static $failed_items = 'failed_items';

	private $identifier;
	/**
	 * @var Background_Utils
	 */
	private $utils;

	public function __construct( $identifier ) {
		$this->identifier = $identifier;
		$this->utils      = new Background_Utils();
	}

	public function get_data() {
		$option_value = $this->utils->get_site_option(
			$this->get_option_id(),
			array()
		);

		return wp_parse_args(
			$option_value,
			array(
				self::$processing      => false,
				self::$cancelled       => false,
				self::$completed       => false,
				self::$total_items     => 0,
				self::$processed_items => 0,
				self::$failed_items    => 0,
			)
		);
	}

	public function to_array() {
		return $this->get_data();
	}

	private function set_data( $updated ) {
		$data = $this->get_data();

		update_site_option( $this->get_option_id(), array_merge( $data, $updated ) );
	}

	private function get_value( $key ) {
		$data = $this->get_data();

		return isset( $data[ $key ] )
			? $data[ $key ]
			: false;
	}

	private function set_value( $key, $value ) {
		$this->mutex( function () use ( $key, $value ) {
			$updated_data = array_merge(
				$this->get_data(),
				array( $key => $value )
			);
			update_site_option( $this->get_option_id(), $updated_data );
		} );
	}

	private function get_option_id() {
		return $this->identifier . '_status';
	}

	public function is_in_processing() {
		return $this->get_value( self::$processing );
	}

	public function set_in_processing( $in_processing ) {
		$this->set_value( self::$processing, $in_processing );
	}

	public function get_total_items() {
		return $this->get_value( self::$total_items );
	}

	public function set_total_items( $total_items ) {
		$this->set_value( self::$total_items, $total_items );
	}

	public function get_processed_items() {
		return $this->get_value( self::$processed_items );
	}

	public function set_processed_items( $processed_items ) {
		$this->set_value( self::$processed_items, $processed_items );
	}

	public function get_failed_items() {
		return $this->get_value( self::$failed_items );
	}

	public function set_failed_items( $failed_items ) {
		$this->set_value( self::$processed_items, $failed_items );
	}

	public function is_cancelled() {
		return $this->get_value( self::$cancelled );
	}

	public function set_is_cancelled( $is_cancelled ) {
		$this->set_value( self::$cancelled, $is_cancelled );
	}

	public function is_dead() {
		return $this->get_value( self::$dead );
	}

	public function is_completed() {
		return $this->get_value( self::$completed );
	}

	public function set_is_completed( $is_completed ) {
		$this->set_value( self::$completed, $is_completed );
	}

	private function mutex( $operation ) {
		$mutex = new Mutex( $this->get_option_id() );
		$mutex->execute( $operation );
	}

	public function start( $total_items ) {
		$this->mutex( function () use ( $total_items ) {
			$this->set_data( array(
				self::$processing      => true,
				self::$cancelled       => false,
				self::$dead            => false,
				self::$completed       => false,
				self::$total_items     => $total_items,
				self::$processed_items => 0,
				self::$failed_items    => 0,
			) );
		} );
	}

	public function complete() {
		$this->mutex( function () {
			$this->set_data( array(
				self::$processing => false,
				self::$cancelled  => false,
				self::$dead       => false,
				self::$completed  => true,
			) );
		} );
	}

	public function cancel() {
		$this->mutex( function () {
			$this->set_data( array(
				self::$processing => false,
				self::$cancelled  => true,
				self::$dead       => false,
				self::$completed  => false,
			) );
		} );
	}

	public function mark_as_dead() {
		$this->mutex( function () {
			$this->set_data( array(
				self::$processing => false,
				self::$cancelled  => false,
				self::$dead       => true,
				self::$completed  => false,
			) );
		} );
	}

	public function task_successful() {
		$this->mutex( function () {
			$this->set_data( array(
				self::$processed_items => $this->get_processed_items() + 1,
			) );
		} );
	}

	public function task_failed() {
		$this->mutex( function () {
			$this->set_data( array(
				self::$processed_items => $this->get_processed_items() + 1,
				self::$failed_items    => $this->get_failed_items() + 1,
			) );
		} );
	}
}
