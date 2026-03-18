<?php

namespace Smush\Core\Modules;

use Smush\Core\Array_Utils;
use Smush\Core\Helper;
use Smush\Core\Hub_Connector;
use Smush\Core\Media\Media_Item_Cache;
use Smush\Core\Media\Media_Item_Query;
use Smush\Core\Media_Library\Background_Media_Library_Scanner;
use Smush\Core\Media_Library\Media_Library_Last_Process;
use Smush\Core\Media_Library\Media_Library_Scan_Background_Process;
use Smush\Core\Media_Library\Media_Library_Scanner;
use Smush\Core\Membership\Membership;
use Smush\Core\Modules\Background\Background_Pre_Flight_Controller;
use Smush\Core\Modules\Background\Background_Process;
use Smush\Core\Product_Analytics;
use Smush\Core\Server_Utils;
use Smush\Core\Settings;
use Smush\Core\Stats\Global_Stats;
use WP_Smush;

class Product_Analytics_Controller {
	/**
	 * @var Settings
	 */
	protected $settings;
	/**
	 * @var Media_Library_Scan_Background_Process
	 */
	protected $scan_background_process;
	protected $scanner_slice_size;

	/**
	 * @var Media_Library_Last_Process
	 */
	protected $media_library_last_process;

	/**
	 * @var bool
	 */
	protected $scan_background_process_dead = false;
	/**
	 * @var Product_Analytics
	 */
	protected $product_analytics;

	/**
	 * @var Array_Utils
	 */
	protected $array_utils;

	public function __construct() {
		$this->settings                   = Settings::get_instance();
		$this->scan_background_process    = Background_Media_Library_Scanner::get_instance()->get_background_process();
		$this->media_library_last_process = Media_Library_Last_Process::get_instance();
		$this->product_analytics          = Product_Analytics::get_instance();
		$this->array_utils                = new Array_Utils();

		$this->hook_actions();
	}

	public static function get_instance() {
		return new self();
	}

	public function __call( $method_name, $arguments ) {
		_deprecated_function( esc_html( $method_name ), '3.24.0' );
	}

	private function hook_actions() {
		// Setting events.
		add_action( 'wp_smush_settings_updated', array( $this, 'track_opt_toggle' ), 10, 2 );
		add_action( 'wp_smush_settings_updated', array( $this, 'intercept_settings_update' ), 10, 2 );
		add_action( 'wp_smush_settings_deleted', array( $this, 'intercept_reset' ) );
		add_action( 'wp_smush_settings_updated', array( $this, 'track_integrations_saved' ), 10, 2 );
		add_action( 'wp_smush_settings_updated', array( $this, 'track_resizing_setting_update' ), 10, 2 );

		add_action( 'wp_ajax_smush_track_deactivate', array( $this, 'ajax_track_deactivation_survey' ) );
		add_action( 'wp_ajax_smush_analytics_track_event', array( $this, 'ajax_handle_track_request' ) );

		if ( ! $this->is_usage_tracking_enabled() ) {
			return;
		}

		// Other events.
		add_action( 'wp_smush_directory_smush_start', array( $this, 'track_directory_smush' ) );
		add_action( 'wp_smush_bulk_smush_start', array( $this, 'track_bulk_smush_start' ), 20 );
		add_action( 'wp_smush_config_applied', array( $this, 'track_config_applied' ) );

		$identifier          = $this->scan_background_process->get_identifier();
		$scan_started_action = "{$identifier}_started";
		$scan_dead_action    = "{$identifier}_dead";

		add_action( "{$identifier}_before_start", array( $this, 'record_scan_death' ), 10, 2 );
		add_action( $scan_started_action, array( $this, 'track_background_scan_start' ), 10, 2 );
		add_action( "{$identifier}_completed", array( $this, 'track_background_scan_end' ), 10, 2 );

		add_action( $scan_dead_action, array( $this, 'track_background_scan_process_death' ) );

		add_action( 'wp_smush_plugin_activated', array( $this, 'track_plugin_activation' ) );
		if ( defined( 'WP_SMUSH_BASENAME' ) ) {
			$plugin_basename = WP_SMUSH_BASENAME;
			add_action( "deactivate_$plugin_basename", array( $this, 'track_plugin_deactivation' ) );
		}

		add_action( 'wp_smush_bulk_smush_stuck', array( $this, 'track_bulk_smush_progress_stuck' ) );

		add_action( 'wp_smush_lazy_load_updated', array( $this, 'track_lazy_load_settings_updated' ), 10, 2 );

		add_action( 'wp_smush_bulk_restore_completed', array( $this, 'track_bulk_restore_completed' ) );
	}

	protected function is_usage_tracking_enabled() {
		return $this->settings->get( 'usage' );
	}

	protected function track( $event, $properties = array() ) {
		$this->product_analytics->track( $event, $properties );
	}

	public function intercept_settings_update( $old_settings, $settings ) {
		if ( empty( $settings['usage'] ) ) {
			// Use the most up-to-data value of 'usage'
			return;
		}

		$settings = $this->remove_unchanged_settings( $old_settings, $settings );
		$handled  = $this->maybe_track_feature_toggle( $settings );

		if ( ! $handled ) {
			$this->maybe_track_cdn_update( $settings );
		}
	}

	private function maybe_track_feature_toggle( $settings ) {
		$has_tracked = false;
		foreach ( $settings as $setting_key => $setting_value ) {
			$handler = "track_{$setting_key}_feature_toggle";
			if ( method_exists( $this, $handler ) ) {
				call_user_func( array( $this, $handler ), $setting_value );

				$has_tracked = true;
			}
		}

		return $has_tracked;
	}

	protected function remove_unchanged_settings( $old_settings, $settings ) {
		$default_settings  = $this->settings->get_defaults();
		$not_null_callback = function ( $value ) {
			return ! is_null( $value );
		};

		$old_settings = array_filter( $old_settings, $not_null_callback );
		$old_settings = array_merge( $default_settings, $old_settings );

		$settings = array_filter( $settings, $not_null_callback );
		$settings = array_merge( $default_settings, $settings );

		$changed = array();
		foreach ( $settings as $setting_key => $setting_value ) {
			$old_setting_value = isset( $old_settings[ $setting_key ] ) ? $old_settings[ $setting_key ] : '';
			if ( $old_setting_value !== $setting_value ) {
				$changed[ $setting_key ] = $setting_value;
			}
		}

		return $changed;
	}

	public function get_bulk_properties() {
		$bulk_property_labels = array(
			'auto'             => 'Automatic Compression',
			'strip_exif'       => 'Metadata',
			'resize'           => 'Resize Original Images',
			'original'         => 'Compress original images',
			'backup'           => 'Backup original images',
			'png_to_jpg'       => 'Auto-convert PNGs to JPEGs (lossy)',
			'no_scale'         => 'Disable scaled images',
			'background_email' => 'Email notification',
		);

		$image_sizes     = Settings::get_instance()->get_setting( 'wp-smush-image_sizes' );
		$bulk_properties = array(
			'Image Sizes'         => empty( $image_sizes ) ? 'All' : 'Custom',
			'Mode'                => $this->get_current_lossy_level_label(),
			'Parallel Processing' => $this->get_parallel_processing_status(),
			'Smush Type'          => $this->get_smush_type(),
		);

		foreach ( $bulk_property_labels as $bulk_setting => $bulk_property_label ) {
			$property_value                          = Settings::get_instance()->get( $bulk_setting )
				? 'Enabled'
				: 'Disabled';
			$bulk_properties[ $bulk_property_label ] = $property_value;
		}

		return $bulk_properties;
	}

	private function get_parallel_processing_status() {
		return defined( 'WP_SMUSH_PARALLEL' ) && WP_SMUSH_PARALLEL ? 'Enabled' : 'Disabled';
	}

	protected function get_smush_type() {
		if ( $this->settings->is_webp_module_active() ) {
			return 'WebP';
		}

		if ( $this->settings->is_avif_module_active() ) {
			return 'AVIF';
		}

		return 'Classic';
	}

	protected function get_current_lossy_level_label() {
		$lossy_level = $this->settings->get_lossy_level_setting();
		$smush_modes = array(
			Settings::get_level_lossless()    => 'Basic',
			Settings::get_level_super_lossy() => 'Super',
			Settings::get_level_ultra_lossy() => 'Ultra',
		);
		if ( ! isset( $smush_modes[ $lossy_level ] ) ) {
			$lossy_level = Settings::get_level_lossless();
		}

		return $smush_modes[ $lossy_level ];
	}

	private function track_detection_feature_toggle( $setting_value ) {
		return $this->track_feature_toggle( $setting_value, 'Image Resize Detection' );
	}

	private function track_lazy_load_feature_toggle( $setting_value ) {
		$this->track_lazy_load_feature_updated_on_toggle( $setting_value );

		return $this->track_feature_toggle( $setting_value, 'Lazy Load' );
	}

	private function track_lazy_load_feature_updated_on_toggle( $activate ) {
		$this->track_lazy_load_updated(
			array(
				'update_type'       => $activate ? 'activate' : 'deactivate',
				'modified_settings' => 'na',
			),
			$this->settings->get_setting( 'wp-smush-lazy_load', array() )
		);
	}

	protected function track_feature_toggle( $active, $feature ) {
		$event = $active
			? 'Feature Activated'
			: 'Feature Deactivated';

		$this->track(
			$event,
			array(
				'Feature'        => $feature,
				'Triggered From' => $this->identify_referrer(),
			)
		);

		return true;
	}

	protected function identify_referrer() {
		$wizard_setup_actions = array( 'smush_setup', 'smush_free_setup' );
		$onboarding_request   = ! empty( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], $wizard_setup_actions, true );
		if ( $onboarding_request ) {
			return 'Wizard';
		}

		$page           = $this->get_referer_page();
		$triggered_from = array(
			'smush'              => 'Dashboard',
			'smush-bulk'         => 'Bulk Smush',
			'smush-lazy-preload' => 'Lazy Load',
			'smush-cdn'          => 'CDN',
			'smush-next-gen'     => 'Next-Gen Formats',
			'smush-integrations' => 'Integrations',
			'smush-settings'     => 'Settings',
		);

		return empty( $triggered_from[ $page ] )
			? ''
			: $triggered_from[ $page ];
	}

	protected function maybe_track_cdn_update( $settings ) {
		return false;
	}

	public function track_directory_smush() {
		$this->track( 'Directory Smushed' );
	}

	public function track_bulk_smush_start() {
		$properties = $this->get_bulk_properties();
		$properties = array_merge(
			$properties,
			array(
				'process_id'              => $this->get_process_id(),
				'Background Optimization' => $this->get_background_optimization_status(),
				'Cron'                    => $this->get_cron_healthy_status(),
			)
		);
		$this->track( 'Bulk Smush Started', $properties );
	}

	protected function get_process_id() {
		return md5( $this->media_library_last_process->get_process_start_time() );
	}

	/**
	 * Add extra properties to the bulk smush completed event for Bulk Smush include ajax method.
	 *
	 * @param array $properties Bulk Smush completed properties.
	 */
	protected function filter_bulk_smush_completed_properties( $properties ) {
		return array_merge(
			$properties,
			array(
				'process_id'              => $this->get_process_id(),
				'Background Optimization' => $this->get_background_optimization_status(),
				'Cron'                    => $this->get_cron_healthy_status(),
				'Time Elapsed'            => $this->media_library_last_process->get_process_elapsed_time(),
				'Smush Type'              => $this->get_smush_type(),
				'Mode'                    => $this->get_current_lossy_level_label(),
			)
		);
	}

	public function track_config_applied( $config_name ) {
		$properties = $config_name
			? array( 'Config Name' => $config_name )
			: array();

		$properties['Triggered From'] = $this->identify_referrer();

		$this->track( 'Config Applied', $properties );
	}

	public function track_opt_toggle( $old_settings, $settings ) {
		$settings = $this->remove_unchanged_settings( $old_settings, $settings );

		if ( isset( $settings['usage'] ) ) {
			// Following the new change, the location for Opt In/Out is lowercase and none whitespace.
			// @see SMUSH-1538.
			$location = str_replace( ' ', '_', $this->identify_referrer() );
			$location = strtolower( $location );
			$this->track(
				$settings['usage'] ? 'Opt In' : 'Opt Out',
				array(
					'Location'       => $location,
					'active_plugins' => $this->get_active_plugins(),
				)
			);
		}
	}

	public function track_integrations_saved( $old_settings, $settings ) {
		if ( empty( $settings['usage'] ) ) {
			return;
		}

		$settings = $this->remove_unchanged_settings( $old_settings, $settings );
		if ( empty( $settings ) ) {
			return;
		}

		$this->maybe_track_integrations_toggle( $settings );
	}

	private function maybe_track_integrations_toggle( $settings ) {
		$integrations = array(
			'gutenberg'  => 'Gutenberg',
			'gform'      => 'Gravity Forms',
			'js_builder' => 'WP Bakery',
			's3'         => 'Amazon S3',
			'nextgen'    => 'NextGen Gallery',
		);

		foreach ( $settings as $integration_slug => $is_activated ) {
			if ( ! array_key_exists( $integration_slug, $integrations ) ) {
				continue;
			}

			if ( $is_activated ) {
				$this->track(
					'Integration Activated',
					array(
						'Integration' => $integrations[ $integration_slug ],
					)
				);
			} else {
				$this->track(
					'Integration Deactivated',
					array(
						'Integration' => $integrations[ $integration_slug ],
					)
				);
			}
		}
	}

	public function intercept_reset() {
		if ( $this->is_usage_tracking_enabled() ) {
			$this->track(
				'Opt Out',
				array(
					'Location'       => 'reset',
					'active_plugins' => $this->get_active_plugins(),
				)
			);
		}
	}

	public function record_scan_death() {
		$this->scan_background_process_dead = $this->scan_background_process->get_status()->is_dead();
	}

	public function track_background_scan_start( $identifier, $background_process ) {
		$type = $this->scan_background_process_dead
			? 'Retry'
			: 'New';

		$this->_track_background_scan_start( $type, $background_process );
	}

	private function _track_background_scan_start( $type, $background_process ) {
		$properties = array(
			'Scan Type' => $type,
		);

		$this->track(
			'Scan Started',
			array_merge(
				$properties,
				$this->get_bulk_properties(),
				$this->get_scan_properties()
			)
		);
	}

	/**
	 * @param $identifier
	 * @param $background_process Background_Process
	 *
	 * @return void
	 */
	public function track_background_scan_end( $identifier, $background_process ) {
		$properties = array(
			'Retry Attempts' => $background_process->get_revival_count(),
			'Time Elapsed'   => $this->media_library_last_process->get_process_elapsed_time(),
		);
		$this->track(
			'Scan Ended',
			array_merge(
				$properties,
				$this->get_bulk_properties(),
				$this->get_scan_properties()
			)
		);
	}

	public function track_background_scan_process_death() {
		$this->track(
			'Background Process Dead',
			array_merge(
				array(
					'Process Type' => 'Scan',
					'Slice Size'   => $this->get_scanner_slice_size(),
					'Time Elapsed' => $this->media_library_last_process->get_process_elapsed_time(),
					'Smush Type'   => $this->get_smush_type(),
					'Mode'         => $this->get_current_lossy_level_label(),
				),
				$this->get_scan_background_process_properties()
			)
		);
	}

	private function get_scan_properties() {
		$global_stats       = Global_Stats::get();
		$global_stats_array = $global_stats->to_array();
		$properties         = array(
			'process_id' => $this->get_process_id(),
			'Slice Size' => $this->get_scanner_slice_size(),
		);

		$labels = array(
			'image_attachment_count' => 'Image Attachment Count',
			'optimized_images_count' => 'Optimized Images Count',
			'optimize_count'         => 'Optimize Count',
			'reoptimize_count'       => 'Reoptimize Count',
			'ignore_count'           => 'Ignore Count',
			'animated_count'         => 'Animated Count',
			'error_count'            => 'Error Count',
			'percent_optimized'      => 'Percent Optimized',
			'size_before'            => 'Size Before',
			'size_after'             => 'Size After',
			'savings_percent'        => 'Savings Percent',
		);

		$savings_keys = array(
			'size_before',
			'size_after',
		);

		foreach ( $labels as $key => $label ) {
			if ( isset( $global_stats_array[ $key ] ) ) {
				$properties[ $label ] = $global_stats_array[ $key ];

				if ( in_array( $key, $savings_keys, true ) ) {
					$properties[ $label ] = $this->convert_to_megabytes( $properties[ $label ] );
				}
			}
		}

		return $properties;
	}

	protected function get_bulk_background_process_properties() {
		$process_id = $this->get_process_id();

		return array(
			'process_id' => $process_id,
		);
	}

	protected function get_scan_background_process_properties() {
		$query                  = new Media_Item_Query();
		$total_enqueued_images  = $query->get_image_attachment_count();
		$total_items            = $this->scan_background_process->get_status()->get_total_items();
		$processed_items        = $this->scan_background_process->get_status()->get_processed_items();
		$scanner_slice_size     = $this->get_scanner_slice_size();
		$total_processed_images = $processed_items * $scanner_slice_size;
		$total_processed_images = min( $total_processed_images, $total_enqueued_images );

		return array(
			'process_id'             => $this->get_process_id(),
			'Retry Attempts'         => $this->scan_background_process->get_revival_count(),
			'Total Enqueued Images'  => $total_enqueued_images,
			'Completion Percentage'  => $this->get_background_process_completion_percentage( $total_items, $processed_items ),
			'Total Processed Images' => $total_processed_images,
		);
	}

	protected function get_background_process_completion_percentage( $total_items, $processed_items ) {
		if ( $total_items < 1 ) {
			return 0;
		}

		return ceil( $processed_items * 100 / $total_items );
	}

	protected function convert_to_megabytes( $size_in_bytes ) {
		if ( empty( $size_in_bytes ) ) {
			return 0;
		}
		$unit_mb = pow( 1024, 2 );
		return round( $size_in_bytes / $unit_mb, 2 );
	}

	protected function get_scanner_slice_size() {
		if ( is_null( $this->scanner_slice_size ) ) {
			$this->scanner_slice_size = ( new Media_Library_Scanner() )->get_slice_size();
		}

		return $this->scanner_slice_size;
	}

	protected function get_referer_page() {
		$path       = parse_url( wp_get_referer(), PHP_URL_QUERY );
		$query_vars = array();
		parse_str( $path, $query_vars );

		return empty( $query_vars['page'] ) ? '' : $query_vars['page'];
	}

	public function track_plugin_activation() {
		$this->track(
			'Opt In',
			array(
				'Location'       => 'reactivate',
				'active_plugins' => $this->get_active_plugins(),
			)
		);
	}

	public function track_plugin_deactivation() {
		$location = $this->get_deactivation_location();
		$this->track(
			'Opt Out',
			array(
				'Location'       => $location,
				'active_plugins' => $this->get_active_plugins(),
			)
		);
	}

	private function get_deactivation_location() {
		$is_hub_request = ! empty( $_REQUEST['wpmudev-hub'] );
		if ( $is_hub_request ) {
			return 'deactivate_hub';
		}

		$is_dashboard_request = wp_doing_ajax() &&
								! empty( $_REQUEST['action'] ) &&
								'wdp-project-deactivate' === wp_unslash( $_REQUEST['action'] );

		if ( $is_dashboard_request ) {
			return 'deactivate_dashboard';
		}

		return 'deactivate_pluginlist';
	}

	private function get_active_plugins() {
		$active_plugins      = array();
		$active_plugin_files = $this->get_active_and_valid_plugin_files();
		foreach ( $active_plugin_files as $plugin_file ) {
			$plugin_name = $this->get_plugin_name( $plugin_file );
			if ( $plugin_name ) {
				$active_plugins[] = $plugin_name;
			}
		}

		return $active_plugins;
	}

	private function get_active_and_valid_plugin_files() {
		$active_plugins = is_multisite() ? wp_get_active_network_plugins() : array();
		$active_plugins = array_merge( $active_plugins, wp_get_active_and_valid_plugins() );

		return array_unique( $active_plugins );
	}

	private function get_plugin_name( $plugin_file ) {
		$plugin_data = get_plugin_data( $plugin_file );

		return ! empty( $plugin_data['Name'] ) ? $plugin_data['Name'] : '';
	}

	private function get_cron_healthy_status() {
		$is_cron_healthy = Background_Pre_Flight_Controller::get_instance()->is_cron_healthy();
		return $is_cron_healthy ? 'Enabled' : 'Disabled';
	}

	protected function get_background_optimization_status() {
		return 'Disabled';
	}

	public function ajax_handle_track_request() {
		$event_name = $this->get_event_name();
		if ( ! check_ajax_referer( 'wp-smush-ajax' ) || ! Helper::is_user_allowed() || empty( $event_name ) ) {
			wp_send_json_error();
		}

		$properties = $this->get_event_properties( $event_name );

		if ( ! $this->allow_to_track( $event_name, $properties ) ) {
			wp_send_json_error();
		}

		$this->track(
			$event_name,
			$properties
		);

		wp_send_json_success();
	}

	private function allow_to_track( $event_name, $properties ) {
		$trackable_events   = array(
			'Setup Wizard'     => true,
			'Setup Wizard New' => true,
			'smush_pro_upsell' => isset( $properties['Location'] ) && 'wizard' === $properties['Location'],
			'Disconnect Site'  => true,
		);
		$is_trackable_event = ! empty( $trackable_events[ $event_name ] );

		return $is_trackable_event || $this->is_usage_tracking_enabled();
	}

	private function get_event_name() {
		return isset( $_POST['event'] ) ? sanitize_text_field( wp_unslash( $_POST['event'] ) ) : '';
	}

	private function get_event_properties( $event_name ) {
		$properties = isset( $_POST['properties'] ) && is_array( $_POST['properties'] ) ? wp_unslash( $_POST['properties'] ) : array();
		$properties = map_deep( $properties, 'sanitize_text_field' );

		$filter_callback = $this->get_filter_properties_callback( $event_name );
		if ( method_exists( $this, $filter_callback ) ) {
			$properties = call_user_func( array( $this, $filter_callback ), $properties );
		}

		return $properties;
	}

	private function get_filter_properties_callback( $event_name ) {
		$event_name = str_replace( ' ', '_', $event_name );
		$event_name = sanitize_key( $event_name );
		return "filter_{$event_name}_properties";
	}

	/**
	 * Filter properties for Scan Interrupted event.
	 *
	 * @param array $properties JS properties.
	 */
	protected function filter_scan_interrupted_properties( $properties ) {
		return array_merge(
			$properties,
			array(
				'Slice Size'              => $this->get_scanner_slice_size(),
				'Background Optimization' => $this->get_background_optimization_status(),
				'Cron'                    => $this->get_cron_healthy_status(),
				'Time Elapsed'            => $this->media_library_last_process->get_process_elapsed_time(),
				'Smush Type'              => $this->get_smush_type(),
				'Mode'                    => $this->get_current_lossy_level_label(),
				'WP Loopback Status'      => $this->get_wp_loopback_status( $properties ),
			),
			$this->get_scan_background_process_properties(),
			$this->get_last_image_process_properties()
		);
	}


	private function get_last_image_process_properties() {
		$last_image_id = $this->media_library_last_process->get_last_process_attachment_id();
		if ( ! $last_image_id ) {
			return array();
		}

		$media_item              = Media_Item_Cache::get_instance()->get( $last_image_id );
		$last_image_time_elapsed = $this->media_library_last_process->get_last_process_attachment_elapsed_time();
		$properties              = array(
			'Last Image Time Elapsed' => $last_image_time_elapsed,
		);

		if ( ! $media_item->is_valid() ) {
			return $properties;
		}

		$full_size = $media_item->get_full_or_scaled_size();
		if ( ! $full_size ) {
			return $properties;
		}

		$file_size    = $this->convert_to_megabytes( $full_size->get_filesize() );
		$image_width  = $full_size->get_width();
		$image_height = $full_size->get_height();
		$image_type   = strtoupper( $full_size->get_extension() );

		return array(
			'Last Image Time Elapsed' => $last_image_time_elapsed,
			'Last Image Size'         => $file_size,
			'Last Image Width'        => $image_width,
			'Last Image Height'       => $image_height,
			'Last Image Type'         => $image_type,
		);
	}

	/**
	 * Filter properties for Bulk Smush interrupted event.
	 *
	 * @param array $properties JS properties.
	 */
	protected function filter_bulk_smush_interrupted_properties( $properties ) {
		return array_merge(
			$properties,
			array(
				'Background Optimization' => $this->get_background_optimization_status(),
				'Cron'                    => $this->get_cron_healthy_status(),
				'Parallel Processing'     => $this->get_parallel_processing_status(),
				'Time Elapsed'            => $this->media_library_last_process->get_process_elapsed_time(),
				'Smush Type'              => $this->get_smush_type(),
				'Mode'                    => $this->get_current_lossy_level_label(),
				'WP Loopback Status'      => $this->get_wp_loopback_status( $properties ),
			),
			$this->get_bulk_background_process_properties(),
			$this->get_last_image_process_properties()
		);
	}

	public function ajax_track_deactivation_survey() {
		$event_name = $this->get_event_name();
		if ( ! check_ajax_referer( 'wp-smush-ajax' ) || ! Helper::is_user_allowed() || empty( $event_name ) ) {
			wp_send_json_error();
		}

		$properties = $this->get_event_properties( $event_name );
		$properties = array_merge(
			$properties,
			array(
				'active_features'      => $this->get_active_features(),
				'active_plugins'       => $this->get_active_plugins(),
				'Smush API Connection' => $this->get_api_connection_status(),
			)
		);

		$this->track(
			$event_name,
			$properties
		);

		wp_send_json_success();
	}

	private function get_api_connection_status() {
		if ( Hub_Connector::is_logged_in() ) {
			return 'connected';
		}

		if ( Membership::get_instance()->is_api_hub_access_required() ) {
			return 'disconnected';
		}

		return 'na';
	}

	private function get_active_features() {
		$lossy_level = $this->settings->get_lossy_level_setting();

		$features = array(
			'lazy_load'        => $this->settings->is_lazyload_active(),
			'smush_basic'      => Settings::get_level_lossless() === $lossy_level,
			'smush_super'      => Settings::get_level_super_lossy() === $lossy_level,
			'wp_bakery'        => $this->settings->get( 'js_builder' ),
			'gravity_forms'    => $this->settings->get( 'gform' ),
			'gutenberg_blocks' => $this->settings->get( 'gutenberg' ),
		);

		// Merge in pro features.
		$features = array_merge(
			$features,
			$this->get_active_pro_features()
		);

		return array_keys( array_filter( $features ) );
	}

	protected function get_active_pro_features() {
		return array();
	}

	private function get_wp_loopback_status( $properties ) {
		$is_loopback_error = ! empty( $properties['Trigger'] ) && 'loopback_error' === $properties['Trigger'];
		if ( $is_loopback_error ) {
			$loopback_status = Helper::loopback_supported() ? 'Pass' : 'Fail';
		} else {
			$loopback_status = 'na';
		}

		return $loopback_status;
	}

	public function track_bulk_smush_progress_stuck() {
		$properties = array(
			'Trigger'      => 'stuck_notice',
			'Modal Action' => 'na',
			'Troubleshoot' => 'na',
		);

		$properties = $this->filter_bulk_smush_interrupted_properties( $properties );

		$this->track( 'Bulk Smush Interrupted', $properties );
	}

	public function track_lazy_load_settings_updated( $old_settings, $settings ) {
		$changed_settings = $this->remove_unchanged_settings( (array) $old_settings, (array) $settings );

		$modified_settings = 'na';
		if ( ! empty( $changed_settings ) ) {
			$modified_settings_map = array(
				'format'            => 'media_type',
				'output'            => 'output_location',
				'animation'         => 'display_animation',
				'include'           => 'include_exclude_posttype',
				'exclude-pages'     => 'include_exclude_url',
				'exclude-classes'   => 'include_exclude_keyword',
				'footer'            => 'script_method',
				'native'            => 'native_lazyload',
				'noscript_fallback' => 'noscript',
			);

			$modified_settings = array_intersect_key( $modified_settings_map, $changed_settings );
			$modified_settings = ! empty( $modified_settings ) ? array_values( $modified_settings ) : 'na';
		}

		$this->track_lazy_load_updated(
			array(
				'update_type'       => 'modify',
				'modified_settings' => $modified_settings,
			),
			$settings
		);
	}

	public function track_resizing_setting_update( $old_settings, $settings ) {
		if ( empty( $settings['usage'] ) ) {
			return;
		}

		$changed_settings  = $this->remove_unchanged_settings( $old_settings, $settings );
		if ( 'Lazy Load' !== $this->identify_referrer() ) {
			return;
		}

		$modified_settings = 'na';

		if ( ! empty( $changed_settings ) ) {
			$modified_settings_map = array(
				'auto_resizing'    => 'auto_resizing',
				'image_dimensions' => 'image_dimensions',
			);

			$modified_settings = array_intersect_key( $modified_settings_map, $changed_settings );
			$modified_settings = ! empty( $modified_settings ) ? array_values( $modified_settings ) : 'na';
		}

		$properties = array(
			'update_type'             => 'modify',
			'modified_settings'        => $modified_settings,
			'auto_resizing_status'    => $settings['auto_resizing'] ? 'Enabled' : 'Disabled',
			'image_dimensions_status' => $settings['image_dimensions'] ? 'Enabled' : 'Disabled',
		);
		$this->track_lazy_load_updated(
			$properties,
			$this->settings->get_setting( 'wp-smush-lazy_load' )
		);
	}

	private function track_lazy_load_updated( $properties, $settings ) {
		$exclusion_enabled         = $this->is_lazy_load_exclusion_enabled( $settings );
		$native_lazyload_enabled   = ! empty( $settings['native'] );
		$noscript_fallback_enabled = ! empty( $settings['noscript_fallback'] );
		$embed_content             = empty( $settings['format']['iframe'] )
			? 'Disabled'
			: ( empty( $settings['format']['embed_video'] ) ? 'Enabled' : 'Preview Images' );
		$properties                = array_merge(
			array(
				'Location'                => $this->identify_referrer(),
				'embed_content'           => $embed_content,
				'exclusions'              => $exclusion_enabled ? 'Enabled' : 'Disabled',
				'native_lazy_status'      => $native_lazyload_enabled ? 'Enabled' : 'Disabled',
				'noscript_status'         => $noscript_fallback_enabled ? 'Enabled' : 'Disabled',
				'auto_resizing_status'    => $this->settings->get( 'auto_resizing' ) ? 'Enabled' : 'Disabled',
				'image_dimensions_status' => $this->settings->get( 'image_dimensions' ) ? 'Enabled' : 'Disabled',
			),
			$properties
		);

		$this->track( 'lazy_load_updated', $properties );
	}

	private function is_lazy_load_exclusion_enabled( $settings ) {
		if ( ! empty( $settings['exclude-pages'] ) || ! empty( $settings['exclude-classes'] ) ) {
			return true;
		}

		if ( empty( $settings['include'] ) || ! is_array( $settings['include'] ) ) {
			return false;
		}

		$included_post_types = $settings['include'];

		// By default, we activated for all post types, so this option is changed when any post type is unchecked.
		return in_array( false, $included_post_types, true );
	}

	/**
	 * Track the completion of a bulk restore process.
	 *
	 * @param array $args Restore arguments.
	 */
	public function track_bulk_restore_completed( $args ) {
		$this->track(
			'Bulk Restore Triggered',
			$this->filter_bulk_restore_triggered_properties(
				array(
					'Type'                  => 'Bulk',
					'Total images restored' => (int) $this->array_utils->get_array_value( $args, 'restored_count', 0 ),
					'Total images'          => (int) $this->array_utils->get_array_value( $args, 'total_count', 0 ),
					'Backup not found'      => (int) $this->array_utils->get_array_value( $args, 'missing_backup_count', 0 ),
				)
			)
		);
	}

	/**
	 * Filter the properties for the bulk restore triggered event.
	 *
	 * @param mixed $properties Properties.
	 *
	 * @return array
	 */
	public function filter_bulk_restore_triggered_properties( $properties ) {
		return array_merge(
			$properties,
			array(
				'Backup Status' => $this->settings->is_backup_active() ? 'Enabled' : 'Disabled',
			)
		);
	}
}
