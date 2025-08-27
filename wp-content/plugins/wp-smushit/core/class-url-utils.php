<?php

namespace Smush\Core;

class Url_Utils {
	/**
	 * @var Upload_Dir
	 */
	private $upload_dir;

	/**
	 * @var string
	 */
	private $content_url;

	public function __construct() {
		$this->upload_dir = new Upload_Dir();
	}

	public function get_extension( $url ) {
		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( empty( $path ) ) {
			return false;
		}

		return strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
	}

	public function get_url_scheme( $url ) {
		$url_parts = wp_parse_url( $url );

		return empty( $url_parts['scheme'] )
			? false
			: $url_parts['scheme'];
	}

	/**
	 * @param $url
	 *
	 * @return string
	 * @see attachment_url_to_postid()
	 */
	public function make_media_url_relative( $url ) {
		$upload_url = $this->upload_dir->get_upload_url();
		$path       = $url;

		$site_url   = parse_url( $upload_url );
		$image_path = parse_url( $path );

		// Force the protocols to match if needed.
		if ( isset( $image_path['scheme'] ) && ( $image_path['scheme'] !== $site_url['scheme'] ) ) {
			$path = str_replace( $image_path['scheme'], $site_url['scheme'], $path );
		}

		if ( str_starts_with( $path, $upload_url . '/' ) ) {
			$path = substr( $path, strlen( $upload_url . '/' ) );
		}

		return $path;
	}

	public function guess_dimensions_from_image_url( $url ) {
		$width_height_string = array();

		if ( preg_match( '#-(\d+)x(\d+)\.(?:jpe?g|png|gif|webp|svg)#i', $url, $width_height_string ) ) {
			$width  = (int) $width_height_string[1];
			$height = (int) $width_height_string[2];

			if ( $width && $height ) {
				return array( $width, $height );
			}
		}

		return array( false, false );
	}

	public function get_query_vars( $url ) {
		// Decode HTML entities in the URL.
		$url = html_entity_decode( $url );

		$parse_url  = wp_parse_url( $url );
		$query_vars = array();
		if ( ! empty( $parse_url['query'] ) ) {
			wp_parse_str( $parse_url['query'], $query_vars );
		}

		return $query_vars;
	}

	public function get_image_dimensions( $image_url ) {
		$default = array( false, false );

		if ( empty( $image_url ) ) {
			return $default;
		}

		list( $width, $height ) = $this->guess_dimensions_from_image_url( $image_url );

		if ( ! empty( $width ) && ! empty( $height ) ) {
			return array( $width, $height );
		}

		if ( $this->is_external_url( $image_url ) ) {
			if ( ! $this->should_fetch_external_image_dimensions( $image_url ) ) {
				return $default;
			}

			if ( ! $this->url_has_200_response( $image_url ) ) {
				return $default;
			}

			return $this->_get_image_dimensions( $image_url );
		}

		$local_path = $this->url_to_path( $image_url );
		if ( ! file_exists( $local_path ) ) {
			return $default;
		}

		return $this->_get_image_dimensions( $local_path );
	}

	public function get_url_content_type_header( $image_url ) {
		// Use url headers to check content type
		if ( $this->is_external_url( $image_url ) ) {
			$headers = get_headers( $image_url, 1 );

			if ( empty( $headers['content-type'] ) ) {
				return false;
			}

			return $headers['content-type'];
		} else {
			return false;
		}
	}

	private function should_fetch_external_image_dimensions( $image_url ) {
		return ini_get( 'allow_url_fopen' ) && apply_filters( 'wp_smush_should_fetch_external_image_dimensions', false, $image_url );
	}

	private function _get_image_dimensions( $image_url ) {
		$sizes = wp_getimagesize( $image_url );

		if ( empty( $sizes ) ) {
			return array( false, false );
		}

		return array( $sizes[0], $sizes[1] );
	}

	public function url_has_200_response( $url ) {
		if ( ! $url || ! ini_get( 'allow_url_fopen' ) ) {
			return false;
		}

		$file_headers = get_headers( $url );

		if ( empty( $file_headers[0] ) ) {
			return false;
		}

		return false !== strstr( $file_headers[0], '200' );
	}

	public function is_external_url( $absolute_url ) {
		if ( str_starts_with( $absolute_url, $this->get_content_url() ) ) {
			return false;
		}

		if ( str_starts_with( $absolute_url, $this->upload_dir->get_upload_url() ) ) {
			return false;
		}

		return true;
	}

	public function url_to_path( $absolute_url ) {
		if ( str_starts_with( $absolute_url, $this->get_content_url() ) ) {
			return str_replace( $this->get_content_url(), WP_CONTENT_DIR, $absolute_url );
		}

		$upload_url = $this->upload_dir->get_upload_url();
		$upload_dir = $this->upload_dir->get_upload_path();

		if ( str_starts_with( $absolute_url, $upload_url ) ) {
			return str_replace( $upload_url, $upload_dir, $absolute_url );
		}

		return false;
	}

	private function get_content_url() {
		if ( ! $this->content_url ) {
			$this->content_url = content_url();
		}

		return $this->content_url;
	}

	/**
	 * Get full size image url from resized one.
	 *
	 * @param string $src Image URL.
	 *
	 * @return string
	 * @since 3.0
	 *
	 */
	public function get_url_without_dimensions( $src ) {
		$extensions = array(
			'gif',
			'jpg',
			'jpeg',
			'png',
			'webp',
		);
		if ( ! preg_match( '/(-\d+x\d+)\.(' . implode( '|', $extensions ) . ')(?:\?.+)?$/i', $src, $src_parts ) ) {
			return $src;
		}

		// Remove WP's resize string to get the original image.
		$original_src = str_replace( $src_parts[1], '', $src );

		// Extracts the file path to the image minus the base url.
		$file_path = substr( $original_src, strlen( $this->upload_dir->get_upload_url() ) );

		// Continue only if the file exists.
		if ( file_exists( $this->upload_dir->get_upload_path() . $file_path ) ) {
			return $original_src;
		}

		// Revert to source if file does not exist.
		return $src;
	}

	public function normalize_url( $url ) {
		$url = str_replace( array( 'http://', 'https://', 'www.' ), '', $url );

		return untrailingslashit( $url );
	}

	public function is_relative( string $url ): bool {
		return ! empty( preg_match( '/^\./', $url ) ) || empty( wp_parse_url( $url, PHP_URL_HOST ) );
	}
}
