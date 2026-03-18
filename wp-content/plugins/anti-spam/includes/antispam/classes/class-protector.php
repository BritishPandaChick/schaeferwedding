<?php
/**
 * Protector Class
 *
 * @package    WBCR\Titan\Antispam
 */

namespace WBCR\Titan\Antispam;

use WBCR\Titan\Plugin;
use WBCR\Titan\WBCR_HTTP;
use WP_Comment;
use WP_Error;

/**
 * Frontend spam protection handler.
 *
 * Implements multiple spam detection methods for comment forms:
 * - JavaScript-based field validation (detects bots without JS)
 * - Honeypot trap fields (catches bots that fill hidden fields)
 * - Year validation challenge
 * - Trackback/pingback blocking
 *
 * Spam comments can optionally be stored for review rather than discarded.
 */
class Protector {

	/**
	 * Meta key for marking comments filtered by this plugin.
	 */
	private const META_SPAM_FILTERED = '_titan_spam_filtered';

	/**
	 * Meta key for storing the spam detection reason.
	 */
	private const META_SPAM_REASON = '_titan_spam_reason';

	/**
	 * Meta key for storing when comment was marked as spam.
	 */
	private const META_SPAM_DATE = '_titan_spam_date';

	/**
	 * Meta key for marking restored comments.
	 */
	private const META_SPAM_RESTORED = '_titan_spam_restored';

	/**
	 * Meta key for storing restoration timestamp.
	 */
	private const META_SPAM_RESTORED_DATE = '_titan_spam_restored_date';

	/**
	 * Meta key for storing the status comment was restored to.
	 */
	private const META_SPAM_RESTORED_STATUS = '_titan_spam_restored_to_status';

	/**
	 * Stat key for total restored comments.
	 */
	private const STAT_RESTORED = 'restored_total';

	/**
	 * Spam reason: year validation failed.
	 */
	private const REASON_YEAR_VALIDATION = 'year_validation_failed';

	/**
	 * Spam reason: honeypot field triggered.
	 */
	private const REASON_HONEYPOT = 'honeypot_triggered';

	/**
	 * Spam reason: trackback blocked.
	 */
	private const REASON_TRACKBACK = 'trackback';

	/**
	 * Initializes the spam protection hooks.
	 *
	 * Registers actions for script enqueueing, form field injection,
	 * privacy notice display, and comment preprocessing.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_script' ] );
		add_action( 'comment_form', [ $this, 'form_part' ] );
		add_action( 'comment_form_after', 'wantispam_display_comment_form_privacy_notice' );

		if ( ! is_admin() ) {
			add_filter( 'preprocess_comment', [ $this, 'check_comment' ], 1 );
		}

		add_action( 'transition_comment_status', [ $this, 'track_comment_restoration' ], 10, 3 );
	}

	/**
	 * We enqueue js script required for the plugin to work. The script overwrites the values
	 * of hidden fields or determines whether the user uses javascript or not.
	 * 
	 * @return void
	 */
	public function enqueue_script() {
		if ( ( is_singular() || ! empty( $GLOBALS['withcomments'] ) ) && comments_open() ) {
			wp_enqueue_script(
				'anti-spam-script',
				WTITAN_PLUGIN_URL . '/assets/js/anti-spam.js',
				[],
				WTITAN_PLUGIN_VERSION,
				true
			);
		}
	}

	/**
	 * Outputs anti-spam fields in the comment form.
	 *
	 * Injects hidden honeypot and validation fields for non-logged-in users.
	 * Logged-in users are trusted and don't receive these fields.
	 *
	 * @return void
	 */
	public function form_part() {
		if ( ! is_user_logged_in() ) { // Add anti-spam fields only for not logged in users.
			echo wantispam_get_required_fields();
		}
	}

	/**
	 * Filters incoming comments for spam before processing.
	 *
	 * Hooked to 'preprocess_comment'. Checks non-logged-in user comments
	 * against spam detection rules. Trackbacks are always blocked.
	 * Detected spam can be stored for review or silently discarded.
	 *
	 * @param array<string, mixed> $comment_data Comment data array from WordPress.
	 *
	 * @return array<string, mixed> The unmodified comment data if not spam.
	 */
	public function check_comment( $comment_data ) {
		$comment_type = $comment_data['comment_type'] ?? null;

		if ( ! is_user_logged_in() && ! in_array( $comment_type, [ 'pingback', 'trackback' ], true ) ) {
			$spam_reason = $this->check_for_spam();
			if ( $spam_reason ) {
				$this->block_spam( $comment_data, $spam_reason, 'Comment is a spam.' );
			}
		}

		if ( 'trackback' === $comment_type ) {
			$this->block_spam( $comment_data, self::REASON_TRACKBACK, 'Trackbacks are disabled.' );
		}

		return $comment_data;
	}

	/**
	 * Blocks a spam comment by storing it (optionally), updating stats, and terminating.
	 *
	 * @param array<string, mixed> $comment_data Comment data array.
	 * @param string               $reason       Spam detection reason.
	 * @param string               $message      Message to display via wp_die().
	 *
	 * @return void
	 */
	private function block_spam( $comment_data, $reason, $message ) {
		$save_spam = get_option( 'titan_save_spam_comments', true );

		if ( $save_spam ) {
			$this->store_comment( $comment_data, $reason );
		}

		wantispam_increment_blocked_stat( $reason );
		wp_die( esc_html( $message ) );
	}

	/**
	 * Increments the restored-comments counter in antispam stats.
	 *
	 * @return void
	 */
	private function increment_restored_stat(): void {
		$stats = get_option( 'antispam_stats', [] );

		if ( ! is_array( $stats ) ) {
			$stats = [];
		}

		$stats[ self::STAT_RESTORED ] = ( $stats[ self::STAT_RESTORED ] ?? 0 ) + 1;
		$stats['last_restored']       = current_time( 'mysql', true );

		update_option( 'antispam_stats', $stats );

		wantispam_flush_stats_cache();
	}

	/**
	 * Checks POST data for spam indicators.
	 *
	 * Validates three spam detection mechanisms:
	 * 1. Year validation field (wantispam_q) - must match current year
	 * 2. JavaScript field (wantispam_d) - fallback check for JS-disabled bots
	 * 3. Honeypot field (wantispam_e_email_url_website) - must be empty
	 *
	 * @return string|false Spam reason string if spam detected, false otherwise.
	 */
	public function check_for_spam() {
		$anti_spam_q = WBCR_HTTP::post( 'wantispam_q', '', 'trim' );
		$anti_spam_d = WBCR_HTTP::post( 'wantispam_d', '', 'trim' );
		$anti_spam_e = WBCR_HTTP::post( 'wantispam_e_email_url_website', '', 'trim' );

		$current_year = gmdate( 'Y' );

		if ( $current_year !== $anti_spam_q && $current_year !== $anti_spam_d ) {
			return self::REASON_YEAR_VALIDATION;
		}

		if ( ! empty( $anti_spam_e ) ) {
			return self::REASON_HONEYPOT;
		}

		return false;
	}

	/**
	 * Adds multiple meta entries to a comment.
	 *
	 * @param int                  $comment_id Comment ID.
	 * @param array<string, mixed> $meta       Associative array of meta key => value pairs.
	 *
	 * @return void
	 */
	private function add_spam_meta( $comment_id, $meta ) {
		foreach ( $meta as $key => $value ) {
			update_comment_meta( $comment_id, $key, sanitize_text_field( (string) $value ) );
		}
	}

	/**
	 * Stores a spam comment in the database for review.
	 *
	 * Normalizes comment data, validates parent comment status,
	 * sets author IP and user agent if missing, then inserts the
	 * comment and marks it as spam. Includes retry logic for
	 * invalid text encoding issues.
	 *
	 * @param array<string, mixed> $comment_data Comment data array.
	 * @param string               $spam_reason  Reason why comment was marked as spam.
	 *
	 * @return int|false|WP_Error Comment ID on success, false or WP_Error on failure.
	 */
	public function store_comment( $comment_data, $spam_reason = '' ) {
		global $wpdb;

		// Normalize user_ID to user_id (WordPress uses both).
		if ( isset( $comment_data['user_ID'] ) ) {
			$comment_data['user_ID'] = (int) $comment_data['user_ID'];
			$comment_data['user_id'] = $comment_data['user_ID'];
		}

		$pre_filtered_user_id = ( isset( $comment_data['user_id'] ) ) ? (int) $comment_data['user_id'] : 0;

		// Ensure IDs are integers.
		$comment_data['comment_post_ID'] = (int) $comment_data['comment_post_ID'];
		if ( isset( $comment_data['user_ID'] ) && $pre_filtered_user_id !== (int) $comment_data['user_ID'] ) {
			$comment_data['user_ID'] = (int) $comment_data['user_ID'];
			$comment_data['user_id'] = $comment_data['user_ID'];
		} elseif ( isset( $comment_data['user_id'] ) ) {
			$comment_data['user_id'] = (int) $comment_data['user_id'];
		}

		// Validate parent comment exists and is in an allowed status.
		$comment_data['comment_parent'] = isset( $comment_data['comment_parent'] ) ? absint( $comment_data['comment_parent'] ) : 0;
		$parent_status                  = ( 0 < $comment_data['comment_parent'] ) ? wp_get_comment_status( $comment_data['comment_parent'] ) : '';
		$comment_data['comment_parent'] = ( 'approved' === $parent_status || 'unapproved' === $parent_status ) ? $comment_data['comment_parent'] : 0;

		// Set author IP from server if not provided.
		if ( ! isset( $comment_data['comment_author_IP'] ) ) {
			$remote_addr                       = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			$comment_data['comment_author_IP'] = filter_var( $remote_addr, FILTER_VALIDATE_IP ) ? $remote_addr : '';
		}
		$comment_data['comment_author_IP'] = preg_replace( '/[^0-9a-fA-F:., ]/', '', $comment_data['comment_author_IP'] );

		// Set user agent from server if not provided.
		if ( ! isset( $comment_data['comment_agent'] ) ) {
			// phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__
			$comment_data['comment_agent'] = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		}
		$comment_data['comment_agent'] = substr( $comment_data['comment_agent'], 0, 254 );

		// Set timestamps if not provided.
		if ( empty( $comment_data['comment_date'] ) ) {
			$comment_data['comment_date'] = current_time( 'mysql' );
		}

		if ( empty( $comment_data['comment_date_gmt'] ) ) {
			$comment_data['comment_date_gmt'] = current_time( 'mysql', true );
		}

		// Apply WordPress comment filters and check if comment is allowed.
		$comment_data = wp_filter_comment( $comment_data );

		$comment_data['comment_approved'] = wp_allow_comment( $comment_data );
		if ( is_wp_error( $comment_data['comment_approved'] ) ) {
			return $comment_data['comment_approved'];
		}

		// Attempt to insert the comment.
		$comment_id = wp_insert_comment( $comment_data );

		// Retry with sanitized text if insert failed (likely encoding issue).
		if ( ! $comment_id ) {
			$fields = [ 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content' ];

			foreach ( $fields as $field ) {
				if ( isset( $comment_data[ $field ] ) ) {
					$comment_data[ $field ] = $wpdb->strip_invalid_text_for_column( $wpdb->comments, $field, $comment_data[ $field ] );
				}
			}

			$comment_data = wp_filter_comment( $comment_data );

			$comment_data['comment_approved'] = wp_allow_comment( $comment_data );
			if ( is_wp_error( $comment_data['comment_approved'] ) ) {
				return $comment_data['comment_approved'];
			}

			$comment_id = wp_insert_comment( $comment_data );
			if ( ! $comment_id ) {
				return false;
			}
		}

		wp_set_comment_status( $comment_id, 'spam' );
		
		$this->add_spam_meta(
			$comment_id,
			[
				self::META_SPAM_FILTERED => 'yes',
				self::META_SPAM_REASON   => $spam_reason,
				self::META_SPAM_DATE     => current_time( 'mysql', true ),
			]
		);

		return $comment_id;
	}

	/**
	 * Tracks when a comment is restored from spam status.
	 *
	 * Hooked to 'transition_comment_status'. When a comment moves from
	 * 'spam' status to another status, checks if it was marked as spam
	 * by Titan and records the restoration in stats and comment metadata.
	 *
	 * @param string     $new_status New comment status.
	 * @param string     $old_status Old comment status.
	 * @param WP_Comment $comment    The comment object.
	 *
	 * @return void
	 */
	public function track_comment_restoration( $new_status, $old_status, $comment ) {
		if ( 'spam' !== $old_status || 'spam' === $new_status ) {
			return;
		}

		$comment_id     = (int) $comment->comment_ID;
		$was_titan_spam = get_comment_meta( $comment_id, self::META_SPAM_FILTERED, true );

		if ( 'yes' !== $was_titan_spam ) {
			return;
		}

		$this->increment_restored_stat();

		$this->add_spam_meta(
			$comment_id,
			[
				self::META_SPAM_RESTORED        => 'yes',
				self::META_SPAM_RESTORED_DATE   => current_time( 'mysql', true ),
				self::META_SPAM_RESTORED_STATUS => $new_status,
			]
		);
	}
}
