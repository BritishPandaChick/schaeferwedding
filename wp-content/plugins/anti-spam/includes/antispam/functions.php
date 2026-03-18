<?php
/**
 * Helper functions for anti-spam functionality.
 *
 * Contains utility functions for rendering honeypot fields, required form fields,
 * privacy notices, and license checking.
 *
 * @package    WBCR\Titan\Antispam 
 */

/**
 * Generates honeypot trap fields for bot detection.
 *
 * Creates hidden form fields designed to catch spam bots. Includes a year validation
 * field and an empty trap field that bots typically fill in.
 *
 * @return string HTML markup for the honeypot fields.
 */
function wantispam_get_honeypot_fields() {
	$rn   = "\r\n"; // .chr(13).chr(10).
	$html = '';

	$html .= '<div class="wantispam-group wantispam-group-q" style="clear: both;">
					<label>Current ye@r <span class="required">*</span></label>
					<input type="hidden" name="wantispam_a" class="wantispam-control wantispam-control-a" value="' . date( 'Y' ) . '" />
					<input type="text" name="wantispam_q" class="wantispam-control wantispam-control-q" value="' . WTITAN_PLUGIN_VERSION . '" autocomplete="off" />
				  </div>' . $rn; // Question (hidden with js).
	$html .= '<div class="wantispam-group wantispam-group-e" style="display: none;">
					<label>Leave this field empty</label>
					<input type="text" name="wantispam_e_email_url_website" class="wantispam-control wantispam-control-e" value="" autocomplete="off" />
				  </div>' . $rn; // Empty field (hidden with css); trap for spammers because many bots will try to put email or url here.

	return $html;
}

/**
 * Generates required anti-spam fields for the comment form.
 *
 * Outputs hidden fields including a timestamp and optional honeypot trap fields.
 * These fields are used to detect and block spam submissions.
 *
 * @param bool $render_honeypot_fields Whether to include honeypot trap fields. Default true.
 *
 * @return string HTML markup for the anti-spam fields.
 */
function wantispam_get_required_fields( $render_honeypot_fields = true ) {
	$html  = '<!-- Anti-spam plugin wordpress.org/plugins/anti-spam/ -->';
	$html .= '<div class="wantispam-required-fields">';
	$html .= '<input type="hidden" name="wantispam_t" class="wantispam-control wantispam-control-t" value="' . time() . '" />'; // Start time of form filling.
	if ( $render_honeypot_fields ) {
		$html .= wantispam_get_honeypot_fields();
	}
	$html .= '</div>';
	$html .= '<!-- End Anti-spam plugin -->';

	return $html;
}

/**
 * Displays a privacy notice for the comment form.
 *
 * Echoes a message informing users about spam data processing when the
 * privacy notice option is enabled and the site has a privacy policy page.
 *
 * @return void
 */
function wantispam_display_comment_form_privacy_notice() {
	if ( ! get_option( 'titan_comment_form_privacy_notice' ) ) {
		return;
	}

	$privacy_policy_url = get_privacy_policy_url();
	if ( empty( $privacy_policy_url ) ) {
		return;
	}

	?>
	<p class="wantispam-comment-form-privacy-notice" style="margin-top:10px;">
		<?php esc_html_e( 'This site uses Titan Security to reduce spam.', 'anti-spam' ); ?>
		<a href="<?php echo esc_url( $privacy_policy_url ); ?>">
			<?php esc_html_e( 'Learn how your comment data is processed', 'anti-spam' ); ?>
		</a>.
	</p>
	<?php
}

/**
 * Builds suggested privacy policy text for Titan Security.
 *
 * @return string HTML content.
 */
function wantispam_get_privacy_policy_content() {
	$items = [
		esc_html__( 'Comment content and submission metadata for spam detection checks.', 'anti-spam' ),
		esc_html__( 'Temporary anti-spam form fields used to distinguish human visitors from automated bots.', 'anti-spam' ),
		esc_html__( 'Comments identified as spam may be stored in the spam queue for moderation and review.', 'anti-spam' ),
		esc_html__( 'If Advanced Spam Filter is enabled, comment content, author name, author email, author IP address, and submission time may be sent to Titan cloud services for machine-learning spam analysis.', 'anti-spam' ),
	];

	return '<p>' . esc_html__( 'This site uses Titan Security to reduce spam in comments.', 'anti-spam' ) . '</p>'
		. '<p>' . esc_html__( 'When someone submits a comment, we may process the following data to detect spam:', 'anti-spam' ) . '</p>'
		. '<ul><li>' . implode( '</li><li>', $items ) . '</li></ul>';
}

/**
 * Registers Titan Security privacy policy text in WordPress Privacy Policy Guide.
 *
 * @return void
 */
function wantispam_add_privacy_policy_content() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}

	wp_add_privacy_policy_content(
		__( 'Titan Security', 'anti-spam' ),
		wp_kses_post( wantispam_get_privacy_policy_content() )
	);
}
add_action( 'admin_init', 'wantispam_add_privacy_policy_content' );

/**
 * Increments spam blocked statistics.
 *
 * Standalone function for incrementing blocked stats, usable from
 * both Protector and Advanced_Spam_Filter without re-instantiation.
 *
 * @param string $reason Spam detection reason.
 *
 * @return void
 */
function wantispam_increment_blocked_stat( $reason = '' ) {
	$stats = get_option( 'antispam_stats', [] );

	if ( ! is_array( $stats ) ) {
		$stats = [];
	}

	$stats['blocked_total'] = ( $stats['blocked_total'] ?? 0 ) + 1;
	$stats['last_blocked']  = current_time( 'mysql', true );

	if ( $reason ) {
		$reasons            = $stats['reasons'] ?? [];
		$reasons[ $reason ] = ( $reasons[ $reason ] ?? 0 ) + 1;
		$stats['reasons']   = $reasons;
	}

	// Track daily stats.
	$today = gmdate( 'Y-m-d' );
	if ( ! isset( $stats['by_date'] ) ) {
		$stats['by_date'] = [];
	}
	$stats['by_date'][ $today ] = ( $stats['by_date'][ $today ] ?? 0 ) + 1;

	// Prune dates older than 90 days.
	$cutoff_date = gmdate( 'Y-m-d', strtotime( '-90 days' ) );
	foreach ( $stats['by_date'] as $date => $count ) {
		if ( $date < $cutoff_date ) {
			unset( $stats['by_date'][ $date ] );
		}
	}

	update_option( 'antispam_stats', $stats );

	wantispam_flush_stats_cache();
}

/**
 * Checks whether the license is activated for the plugin or not. If the plugin is installed
 * in priorities checks its license.
 *
 * @return bool
 */
function wantispam_is_license_activate() {
	if ( class_exists( '\WBCR\Titan\Plugin' ) ) {
		return \WBCR\Titan\Plugin::app()->premium->is_active();
	}

	return false;
}
