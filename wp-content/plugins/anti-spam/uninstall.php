<?php
// @formatter:off
// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

/**
 * Удаление кеша и опций
 */
function uninstall() {
	// Remove plugin options.
	global $wpdb;

	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'titan_%';" );

	// Clean up spam filter comment meta.
	$wpdb->query(
		"DELETE FROM {$wpdb->commentmeta} WHERE meta_key IN (
			'_titan_spam_check_status',
			'_titan_spam_check_public_id',
			'_titan_spam_check_sent_at',
			'_titan_spam_filtered',
			'_titan_spam_reason',
			'_titan_spam_date',
			'_titan_spam_restored',
			'_titan_spam_restored_date',
			'_titan_spam_restored_to_status'
		)"
	);
}

if ( get_option( 'titan_complete_uninstall', false ) ) {
	if ( is_multisite() ) {
		global $wpdb, $wp_version;

		$wpdb->query( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE 'titan_%';" );

		$blogs = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

		if ( ! empty( $blogs ) ) {
			foreach ( $blogs as $id ) {

				switch_to_blog( $id );

				uninstall();

				restore_current_blog();
			}
		}
	} else {
		uninstall();
	}
}
