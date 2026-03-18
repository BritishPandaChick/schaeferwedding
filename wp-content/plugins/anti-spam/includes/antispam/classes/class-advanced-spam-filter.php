<?php
/**
 * Advanced Spam Filter Class
 *
 * Async ML-based spam detection using the Titan API and Action Scheduler.
 *
 * @package WBCR\Titan\Antispam
 */

namespace WBCR\Titan\Antispam;

use WBCR\Titan\Plugin;
use WBCR\Titan\Api\ApiClient;
use WBCR\Titan\Logger\Writter;
use WP_Comment;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Advanced Spam Filter
 *
 * Enqueues comments for async ML analysis, sends batches to the API,
 * polls for results, and moves detected spam to the spam folder.
 */
class Advanced_Spam_Filter {

	/**
	 * Comment meta key for spam check status.
	 */
	public const META_STATUS = '_titan_spam_check_status';

	/**
	 * Comment meta key for the API public ID.
	 */
	public const META_PUBLIC_ID = '_titan_spam_check_public_id';

	/**
	 * Comment meta key for when the comment was sent to the API.
	 */
	private const META_SENT_AT = '_titan_spam_check_sent_at';

	/**
	 * Number of comments to process per batch.
	 */
	private const BATCH_SIZE = 100;

	/**
	 * Interval between send batches in seconds.
	 */
	private const SEND_INTERVAL = 30 * MINUTE_IN_SECONDS;

	/**
	 * Interval between poll batches in seconds.
	 */
	private const POLL_INTERVAL = 30 * MINUTE_IN_SECONDS;

	/**
	 * Seconds a per-comment send lock is held (5 minutes).
	 */
	private const SEND_LOCK_TTL = 5 * MINUTE_IN_SECONDS;

	/**
	 * Seconds before a processing comment is considered stale (1 hour).
	 */
	private const STALE_THRESHOLD = 3600;

	/**
	 * Action Scheduler hook for sending batches.
	 */
	private const ACTION_SEND = 'titan_spam_batch_enqueue_spam';

	/**
	 * Action Scheduler hook for polling batches.
	 */
	private const ACTION_POLL = 'titan_spam_batch_check_status';

	/**
	 * Spam reason used for stats tracking.
	 */
	private const SPAM_REASON = 'ml_spam_detection';

	/**
	 * Constructor.
	 *
	 * Registers hooks only when the feature is enabled.
	 */
	public function __construct() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		add_action( 'comment_post', [ $this, 'enqueue_comment' ], 10, 3 );
		add_action( self::ACTION_SEND, [ $this, 'process_send_batch' ] );
		add_action( self::ACTION_POLL, [ $this, 'process_poll_batch' ] );

		if ( is_admin() ) {
			add_filter( 'comment_row_actions', [ $this, 'add_status_badge' ], 10, 2 );
			add_action( 'admin_head-edit-comments.php', [ $this, 'inject_badge_css' ] );
		}

		$this->ensure_scheduled_actions();
	}

	/**
	 * Check if the advanced spam filter is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return Plugin::app()->is_premium()
			&& (bool) get_option( 'titan_advanced_spam_filter', false );
	}

	/**
	 * Ensure recurring Action Scheduler actions are scheduled.
	 *
	 * @return void
	 */
	private function ensure_scheduled_actions(): void {
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}

		// Avoid DB queries on every page load — check once per hour.
		$transient_key = 'titan_spam_actions_scheduled';
		if ( get_transient( $transient_key ) ) {
			return;
		}

		$needs_update = false;

		if ( ! as_has_scheduled_action( self::ACTION_SEND ) ) {
			as_schedule_recurring_action( time(), self::SEND_INTERVAL, self::ACTION_SEND );
			$needs_update = true;
		}

		if ( ! as_has_scheduled_action( self::ACTION_POLL ) ) {
			// Offset by ~7 minutes so send and poll don't run simultaneously.
			as_schedule_recurring_action( time() + 420, self::POLL_INTERVAL, self::ACTION_POLL );
			$needs_update = true;
		}

		if ( ! $needs_update ) {
			set_transient( $transient_key, 1, HOUR_IN_SECONDS );
		}
	}

	/**
	 * Enqueue a new comment for spam analysis.
	 *
	 * @param int                  $comment_id       The comment ID.
	 * @param int|string           $comment_approved  1 if approved, 0 if not, 'spam' if spam.
	 * @param array<string, mixed> $comment_data       The comment data array.
	 *
	 * @return void
	 */
	public function enqueue_comment( $comment_id, $comment_approved, $comment_data ): void {
		require_once WTITAN_PLUGIN_DIR . '/includes/logger/class-logger-writter.php';

		// Skip if already marked as spam.
		if ( 'spam' === $comment_approved ) {
			Writter::debug( sprintf( 'Comment #%d skipped — already spam', $comment_id ) );
			return;
		}

		$comment_type = $comment_data['comment_type'] ?? '';

		// Skip pingbacks and trackbacks.
		if ( in_array( $comment_type, [ 'pingback', 'trackback' ], true ) ) {
			Writter::debug( sprintf( 'Comment #%d skipped — %s', $comment_id, $comment_type ) );
			return;
		}

		// Skip logged-in users.
		$user_id = $comment_data['user_id'] ?? 0;
		if ( $user_id && get_userdata( $user_id ) ) {
			Writter::debug( sprintf( 'Comment #%d skipped — logged-in user', $comment_id ) );
			return;
		}

		// Only enqueue if not already enqueued.
		if ( add_comment_meta( $comment_id, self::META_STATUS, 'enqueued', true ) ) {
			Writter::info( sprintf( 'Comment #%d enqueued for ML spam analysis', $comment_id ) );
		}
	}

	/**
	 * Process a batch of enqueued and failed comments — send to API for analysis.
	 *
	 * Failed comments are automatically retried without manual intervention.
	 *
	 * @return void
	 */
	public function process_send_batch(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		require_once WTITAN_PLUGIN_DIR . '/includes/logger/class-logger-writter.php';

		/**
		 * The list of comments to process (prioritizing enqueued, then failed).
		 * 
		 * @var WP_Comment[] $comments The comments to process.
		 */
		$comments = get_comments(
			[
				'meta_key'   => self::META_STATUS,
				'meta_value' => 'enqueued',
				'number'     => self::BATCH_SIZE,
				'orderby'    => 'comment_ID',
				'order'      => 'ASC',
			]
		);

		if ( empty( $comments ) ) {
			/**
			 * The list of comments to process (failed retries).
			 * 
			 * @var WP_Comment[] $comments The comments to process.
			 */
			$comments = get_comments(
				[
					'meta_key'   => self::META_STATUS,
					'meta_value' => 'failed',
					'number'     => self::BATCH_SIZE,
					'orderby'    => 'comment_ID',
					'order'      => 'ASC',
				]
			);
		}

		Writter::info( sprintf( '[Send Batch] Started — found %d comments to process', count( $comments ) ) );

		if ( empty( $comments ) ) {
			Writter::info( '[Send Batch] No comments to process' );
			return;
		}

		$api     = new ApiClient();
		$sent    = 0;
		$failed  = 0;
		$skipped = 0;

		foreach ( $comments as $comment ) {
			$comment_id = (int) $comment->comment_ID;

			// Verify comment still exists.
			if ( ! get_comment( $comment_id ) ) {
				Writter::warning( sprintf( 'Comment #%d no longer exists, cleaning up meta', $comment_id ) );
				delete_comment_meta( $comment_id, self::META_STATUS );
				++$skipped;
				continue;
			}

			// Short-lived transient lock prevents duplicate sends when
			// overlapping Action Scheduler runners pick up the same comment.
			$lock_key = 'titan_spam_sending_' . $comment_id;
			if ( get_transient( $lock_key ) ) {
				Writter::debug( sprintf( 'Comment #%d already being processed by another worker, skipping', $comment_id ) );
				++$skipped;
				continue;
			}
			set_transient( $lock_key, true, self::SEND_LOCK_TTL );

			$data = [
				'message'         => $comment->comment_content,
				'sender_nickname' => $comment->comment_author,
				'sender_email'    => $comment->comment_author_email,
				'sender_ip'       => $comment->comment_author_IP,
				'submit_time'     => strtotime( $comment->comment_date_gmt ),
			];

			$result        = $api->check_spam( $data );
			$processing_id = $result && ! empty( $result['data']['id'] ) ? $result['data']['id'] : false;

			if ( $processing_id ) {
				update_comment_meta( $comment_id, self::META_PUBLIC_ID, sanitize_text_field( $processing_id ) );
				update_comment_meta( $comment_id, self::META_SENT_AT, time() );
				update_comment_meta( $comment_id, self::META_STATUS, 'processing' );
				Writter::info( sprintf( 'Comment #%d sent to API — processing_id: %s', $comment_id, $processing_id ) );
				++$sent;
			} else {
				update_comment_meta( $comment_id, self::META_STATUS, 'failed' );
				$error     = $api->get_last_error();
				$error_msg = $error ? ( $error['message'] ?? 'Unknown' ) : 'Unknown';
				Writter::warning(
					sprintf( 'Comment #%d send failed, will retry next cycle. Error: %s', $comment_id, $error_msg )
				);
				++$failed;
			}
		}

		Writter::info( sprintf( '[Send Batch] Complete — %d sent, %d failed, %d skipped', $sent, $failed, $skipped ) );
	}

	/**
	 * Process a batch of processing comments — poll API for results.
	 *
	 * @return void
	 */
	public function process_poll_batch(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		require_once WTITAN_PLUGIN_DIR . '/includes/logger/class-logger-writter.php';

		/**
		 * The list of comments to check.
		 * 
		 * @var WP_Comment[] $comments The comments to check.
		 */
		$comments = get_comments(
			[
				'meta_key'   => self::META_STATUS,
				'meta_value' => 'processing',
				'number'     => self::BATCH_SIZE,
				'orderby'    => 'comment_date_gmt',
				'order'      => 'ASC',
			]
		);

		Writter::info( sprintf( '[Poll Batch] Started — found %d processing comments', count( $comments ) ) );

		if ( empty( $comments ) ) {
			Writter::info( '[Poll Batch] No comments to poll' );
			return;
		}

		$api       = new ApiClient();
		$completed = 0;
		$spam      = 0;
		$failed    = 0;
		$pending   = 0;

		foreach ( $comments as $comment ) {
			$comment_id = (int) $comment->comment_ID;

			// Verify comment still exists.
			if ( ! get_comment( $comment_id ) ) {
				Writter::warning( sprintf( 'Comment #%d no longer exists, cleaning up meta', $comment_id ) );
				delete_comment_meta( $comment_id, self::META_STATUS );
				delete_comment_meta( $comment_id, self::META_PUBLIC_ID );
				delete_comment_meta( $comment_id, self::META_SENT_AT );
				continue;
			}

			$public_id = get_comment_meta( $comment_id, self::META_PUBLIC_ID, true );

			if ( empty( $public_id ) ) {
				Writter::warning( sprintf( 'Comment #%d missing public_id, marked failed', $comment_id ) );
				update_comment_meta( $comment_id, self::META_STATUS, 'failed' );
				++$failed;
				continue;
			}

			$result = $api->get_spam_status( $public_id );
			$result = ! empty( $result['data'] ) ? $result['data'] : null;

			if ( null === $result ) {
				// API unreachable — check if stale.
				$this->check_stale( $comment );
				++$pending;
				continue;
			}

			$status = $result['status'] ?? '';

			if ( 'completed' === $status ) {
				$is_spam = ! empty( $result['is_spam'] );

				Writter::info( sprintf( 'Comment #%d completed — spam: %s', $comment_id, $is_spam ? 'yes' : 'no' ) );

				if ( $is_spam ) {
					wp_set_comment_status( $comment_id, 'spam' );

					// Add Protector-compatible meta for tracking.
					add_comment_meta( $comment_id, '_titan_spam_filtered', 'yes' );
					add_comment_meta( $comment_id, '_titan_spam_reason', self::SPAM_REASON );
					add_comment_meta( $comment_id, '_titan_spam_date', current_time( 'mysql', true ) );

					wantispam_increment_blocked_stat( self::SPAM_REASON );
					Writter::info( sprintf( 'Comment #%d marked as spam, moved to spam folder', $comment_id ) );
					++$spam;
				}

				update_comment_meta( $comment_id, self::META_STATUS, 'completed' );
				++$completed;
			} elseif ( 'failed' === $status ) {
				Writter::warning( sprintf( 'Comment #%d failed on API side', $comment_id ) );
				update_comment_meta( $comment_id, self::META_STATUS, 'failed' );
				++$failed;
			} else {
				// Still pending/processing on server — check if stale.
				Writter::debug( sprintf( 'Comment #%d still processing on API, checking staleness', $comment_id ) );
				$this->check_stale( $comment );
				++$pending;
			}
		}

		Writter::info(
			sprintf(
				'[Poll Batch] Complete — %d completed (%d spam), %d failed, %d still pending',
				$completed,
				$spam,
				$failed,
				$pending
			)
		);
	}

	/**
	 * Check if a processing comment has become stale and mark as failed.
	 *
	 * @param \WP_Comment $comment The comment object.
	 *
	 * @return void
	 */
	private function check_stale( $comment ): void {
		$comment_id = (int) $comment->comment_ID;
		$sent_at    = (int) get_comment_meta( $comment_id, self::META_SENT_AT, true );

		// Fall back to comment date if sent_at is missing (legacy entries).
		if ( ! $sent_at ) {
			$sent_at = strtotime( $comment->comment_date_gmt );
		}

		if ( ! $sent_at ) {
			return;
		}

		$age_seconds = time() - $sent_at;

		if ( $age_seconds > self::STALE_THRESHOLD ) {
			$hours = round( $age_seconds / 3600, 1 );
			update_comment_meta( $comment_id, self::META_STATUS, 'failed' );
			Writter::warning(
				sprintf( 'Comment #%d marked stale after %sh — marked as failed', $comment_id, $hours )
			);
		}
	}

	/**
	 * Add a spam check status badge to the comment row actions in admin.
	 *
	 * @param array<string, mixed> $actions The existing comment row actions.
	 * @param \WP_Comment          $comment The comment object.
	 *
	 * @return array<string, mixed> Modified actions with badge appended.
	 */
	public function add_status_badge( $actions, $comment ): array {
		$status = get_comment_meta( (int) $comment->comment_ID, self::META_STATUS, true );

		if ( ! $status ) {
			return $actions;
		}

		$badges = [
			'enqueued'   => [
				'label' => __( 'Titan: Queued', 'anti-spam' ),
				'class' => 'titan-badge-queued',
			],
			'processing' => [
				'label' => __( 'Titan: Analyzing', 'anti-spam' ),
				'class' => 'titan-badge-analyzing',
			],
			'failed'     => [
				'label' => __( 'Titan: Failed', 'anti-spam' ),
				'class' => 'titan-badge-failed',
			],
		];

		if ( isset( $badges[ $status ] ) ) {
			$badge               = $badges[ $status ];
			$actions['titan_ml'] = sprintf(
				'<span class="titan-ml-badge %s">%s</span>',
				esc_attr( $badge['class'] ),
				esc_html( $badge['label'] )
			);
		}

		return $actions;
	}

	/**
	 * Inject CSS for the spam check badges in the admin comments list.
	 *
	 * @return void
	 */
	public function inject_badge_css(): void {
		?>
		<style>
			.titan-ml-badge {
				display: inline-block;
				padding: 2px 8px;
				border-radius: 3px;
				font-size: 12px;
				font-weight: 600;
				line-height: 1.4;
			}
			.titan-badge-queued { background: #fff3cd; color: #856404; }
			.titan-badge-analyzing { background: #cce5ff; color: #004085; }
			.titan-badge-completed { background: #d4edda; color: #155724; }
			.titan-badge-failed { background: #f8d7da; color: #721c24; }
		</style>
		<?php
	}
}
