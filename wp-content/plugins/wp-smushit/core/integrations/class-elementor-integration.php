<?php

namespace Smush\Core\Integrations;

use Smush\Core\Cache\Cache_Helper;
use Smush\Core\Controller;
use Smush\Core\Server_Utils;
use Smush\Core\Url_Utils;
use Smush\Core\Parser\Image_URL;
use Smush\Core\Transform\Transformer;
use Smush\Core\Media\Media_Item_Size;
use Smush\Core\Parser\Parser;

/**
 * Elementor_Integration
 */
class Elementor_Integration extends Controller {

	/**
	 * Utility for URL operations.
	 *
	 * @var Url_Utils
	 */
	private $url_utils;

	/**
	 * @var string
	 */
	private $current_url;

	/**
	 * @var Transformer
	 */
	private $transformer;

	public function __construct() {
		$this->url_utils   = new Url_Utils();
		$this->transformer = new Transformer();

		$this->register_filter( 'elementor/frontend/the_content', array( $this, 'transform_elementor_content' ) );
		$this->register_filter( 'elementor/css-file/post/parse', array( $this, 'transform_elementor_post_css' ) );
		$this->register_filter( 'wp_smush_media_item_size', array( $this, 'initialize_elementor_custom_size' ), 10, 4 );
		$this->register_action( Cache_Helper::get_clear_cache_action(), array( $this, 'clear_elementor_cache' ) );
	}

	public function should_run() {
		return class_exists( '\\Elementor\Plugin' );
	}

	public function initialize_elementor_custom_size( $size, $key, $metadata, $media_item ) {
		if ( false === strpos( $key, 'elementor_custom_' ) ) {
			return $size;
		}

		$uploads_dir = wp_get_upload_dir();
		if ( ! isset( $uploads_dir['basedir'], $uploads_dir['baseurl'] ) ) {
			return $size;
		}

		$base_dir = $uploads_dir['basedir'];
		$base_url = $uploads_dir['baseurl'];

		return new Media_Item_Size( $key, $media_item->get_id(), $base_dir, $base_url, $metadata );
	}

	public function clear_elementor_cache() {
		$elementor = $this->get_elementor_instance();
		if (
			! $elementor ||
			! isset( $elementor->files_manager ) ||
			! is_callable( array( $elementor->files_manager, 'clear_cache' ) )
		) {
			return;
		}

		$elementor->files_manager->clear_cache();
	}

	/**
	 * Safely retrieve the Elementor plugin instance.
	 *
	 * @return \Elementor\Plugin|null
	 */
	private function get_elementor_instance() {
		if (
			! isset( \Elementor\Plugin::$instance ) ||
			! is_object( \Elementor\Plugin::$instance )
		) {
			return null;
		}
		return \Elementor\Plugin::$instance;
	}

	/**
	 * Transforms Elementor content by replacing URLs with CDN URLs.
	 *
	 * This function processes Elementor's content to identify image URLs
	 * (e.g., JPEG, PNG, GIF, WebP) hosted on the site's content or site URL,
	 * and replaces them with the corresponding CDN URLs.
	 *
	 * @param string $element_data The Elementor settings data containing URLs
	 *                             that may need transformation.
	 *
	 * @return string Transformed Elementor content with URLs replaced by CDN URLs.
	 */
	public function transform_elementor_content( $element_data ) {

		$content_url = $this->prepare_url( content_url() );
		// Replace URLs in the data.
		return preg_replace_callback(
			"#(?:https?:)?{$content_url}[^'|,;\"]*\.(?:jpe?g|png|gif|webp)#m",
			function ( $matches ) {
				return addcslashes( $this->transform_url( $this->sanitize_json_url( $matches[0] ) ), '/' );
			},
			$element_data
		);
	}

	private function transform_url( $url ) {
		if ( empty( $url ) || ! is_string( $url ) ) {
			return $url;
		}

		$extension = $this->url_utils->get_extension( $url );
		$image_url = new Image_URL( $url, $extension, $this->get_current_url() );
		return $this->transformer->transform_url( $image_url->get_absolute_url() );
	}

	private function get_current_url() {
		if ( ! $this->current_url ) {
			$this->current_url = ( new Server_Utils() )->get_current_url();
		}
		return $this->current_url;
	}

	/**
	 * Prepare a URL for use in a regular expression.
	 *
	 * @param string $url The URL to prepare.
	 * @return string The escaped URL for use in regex.
	 */
	private function prepare_url( $url ) {
		$url = untrailingslashit( preg_replace( '/https?:/', '', $url ) );
		return addcslashes( preg_quote( $url, '/' ), '/' );
	}

	/**
	 * Cleans JSON-encoded URLs by removing extra slashes.
	 * Returns original string if decoding fails.
	 *
	 * @param string $url The JSON-encoded URL string to process
	 * @return string The decoded URL with slashes normalized, or original string on failure
	 * @since 3.8.0
	 */
	private function sanitize_json_url( $url ) {
		try {
			$decoded = json_decode( '"' . str_replace( '"', '\"', $url ) . '"' );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				throw new \Exception( 'Invalid JSON' );
			}

			return str_replace( '\/', '/', $decoded );
		} catch ( \Exception $e ) {
			return $url;
		}
	}

	/**
	 * Transform Elementor post CSS.
	 *
	 * @param \Elementor\Core\Files\CSS\Post $post_css Post CSS object.
	 *
	 * @return void
	 */
	public function transform_elementor_post_css( $post_css ) {
		if ( ! is_object( $post_css ) || ! is_callable( array( $post_css, 'get_stylesheet' ) ) ) {
			return;
		}

		$stylesheet = $post_css->get_stylesheet();

		if (
			! is_object( $stylesheet ) ||
			! is_callable( array( $stylesheet, 'get_rules' ) ) ||
			! is_callable( array( $stylesheet, 'add_rules' ) )
		) {
			return;
		}

		$post_css_rules = $stylesheet->get_rules();
		if ( empty( $post_css_rules ) || ! is_array( $post_css_rules ) ) {
			return;
		}

		foreach ( $post_css_rules as $query_hash => $style_rules ) {
			if ( empty( $style_rules ) ) {
				continue;
			}

			$query = array();
			if ( 'all' !== $query_hash ) {
				$query               = $this->hash_to_query( $query_hash );
				$computed_query_hash = $this->query_to_hash( $query );
				$is_query_valid      = $computed_query_hash === $query_hash;
				if ( ! $is_query_valid ) {
					continue;
				}
			}

			foreach ( $style_rules as $selector => $rules ) {
				if ( empty( $rules ) ) {
					continue;
				}

				$transformed_rules = $this->transform_selector_rules( (array) $rules );
				if ( $transformed_rules !== $rules ) {
					$stylesheet->add_rules( $selector, $transformed_rules, $query );
				}
			}
		}
	}

	/**
	 * Transform image URLs in selector rules.
	 *
	 * @param array $css_rules The CSS rules to transform.
	 *
	 * @return array
	 */
	private function transform_selector_rules( $css_rules ) {
		$css_image_properties = $this->get_css_image_properties();
		$parser               = new Parser();
		$updated_rules        = $css_rules;

		foreach ( $css_rules as $property => $value ) {
			if ( ! in_array( $property, $css_image_properties, true ) ) {
				continue;
			}

			$image_urls = $parser->get_image_urls( $value, $this->get_current_url() );
			if ( empty( $image_urls ) ) {
				continue;
			}

			foreach ( $image_urls as $image_url ) {
				$transformed_url = $this->transformer->transform_url( $image_url->get_absolute_url() );
				if ( $transformed_url && is_string( $transformed_url ) ) {
					$transformed_value = str_replace( $image_url->get_url(), $transformed_url, $value );
					if ( $transformed_value !== $value ) {
						$updated_rules[ $property ] = $transformed_value;
					}
				}
			}
		}

		return $updated_rules;
	}

	/**
	 * Returns an array of CSS properties that can contain image URLs.
	 *
	 * @return array
	 */
	private function get_css_image_properties() {
		$properties = array(
			'background',
			'background-image',
			'image-set',
			'mask-image',
			'mask',
		);

		/**
		 * Filter the list of CSS properties that can contain image URLs.
		 *
		 * @param array $properties
		 */
		return apply_filters( 'wp_smush_css_image_properties', $properties );
	}

	/**
	 * Hash to query.
	 *
	 * Turns the hashed string to an array that contains the data of the query
	 * endpoint.
	 *
	 * @param string $hash Hashed string of the query.
	 *
	 * @see \Elementor\Stylesheet::hash_to_query()
	 *
	 * @return array Media query data.
	 */
	private function hash_to_query( $hash ) {
		$query     = array();
		$elementor = $this->get_elementor_instance();

		if (
			! $elementor ||
			! isset( $elementor->breakpoints ) ||
			! is_object( $elementor->breakpoints ) ||
			! is_callable( array( $elementor->breakpoints, 'get_active_breakpoints' ) ) ||
			! is_callable( array( $elementor->breakpoints, 'get_device_min_breakpoint' ) )
		) {
			return $query;
		}

		$breakpoints = $elementor->breakpoints;
		$hash        = array_filter( explode( '-', $hash ) );

		foreach ( $hash as $single_query ) {
			preg_match( '/(min|max)_(.*)/', $single_query, $query_parts );

			$end_point   = $query_parts[1];
			$device_name = $query_parts[2];

			if ( 'max' === $end_point ) {
				$breakpoint = $breakpoints->get_active_breakpoints( $device_name );
				if ( is_object( $breakpoint ) && is_callable( array( $breakpoint, 'get_value' ) ) ) {
					$max_breakpoint_value = $breakpoint->get_value();
					if ( is_numeric( $max_breakpoint_value ) ) {
						$query[ $end_point ] = $max_breakpoint_value;
					}
				}
			} else {
				$min_breakpoint_value = $breakpoints->get_device_min_breakpoint( $device_name );
				if ( is_numeric( $min_breakpoint_value ) ) {
					$query[ $end_point ] = $min_breakpoint_value;
				}
			}
		}

		return $query;
	}


	/**
	 * Query to hash.
	 *
	 * Turns the media query into a hashed string that represents the query
	 * endpoint in the rules list.
	 *
	 * @param array $query CSS media query.
	 *
	 * @see \Elementor\Stylesheet::query_to_hash()
	 *
	 * @return string Hashed string of the query.
	 */
	private function query_to_hash( $query ) {
		$hash = array();

		foreach ( $query as $endpoint => $value ) {
			$hash[] = $endpoint . '_' . $value;
		}

		return implode( '-', $hash );
	}
}
