<?php
/**
 * Functions for Titan Security plugin.
 *
 * @package Titan_Security
 * @version 1.0
 */

use WBCR\Titan\Plugin;

add_action( 'init', 'titan_init_https_redirect' );
function titan_init_https_redirect() {
	$strict_https = get_option( 'titan_strict_https', false );
	if ( ! is_ssl() && $strict_https ) {
		wp_redirect( home_url( add_query_arg( $_GET, $_SERVER['REQUEST_URI'] ), 'https' ) );
		die;
	}
}

/**
 * Get anti-spam statistics
 *
 * @return array {
 *     Anti-spam statistics data
 *
 *     @type int   $blocked_total Total number of spam blocked all time
 *     @type array $by_date       Daily breakdown (last 90 days) with date as key and count as value
 *     @type int   $blocked_7_days  Total blocked in last 7 days
 *     @type int   $blocked_30_days Total blocked in last 30 days
 *     @type int   $blocked_90_days Total blocked in last 90 days
 * }
 */
function wantispam_get_stats() {
	$cached = get_transient( 'wantispam_stats_cache' );
	if ( false !== $cached ) {
		return $cached;
	}
	
	$stats = get_option( 'antispam_stats', [] );
	
	$result = [
		'blocked_total'   => isset( $stats['blocked_total'] ) ? (int) $stats['blocked_total'] : 0,
		'by_date'         => isset( $stats['by_date'] ) ? $stats['by_date'] : [],
		'blocked_today'   => 0,
		'blocked_7_days'  => 0,
		'blocked_30_days' => 0,
		'blocked_90_days' => 0,
	];
	
	if ( ! empty( $result['by_date'] ) ) {
		$now         = time();
		$today       = gmdate( 'Y-m-d', $now );
		$seven_days  = gmdate( 'Y-m-d', strtotime( '-7 days', $now ) );
		$thirty_days = gmdate( 'Y-m-d', strtotime( '-30 days', $now ) );
		$ninety_days = gmdate( 'Y-m-d', strtotime( '-90 days', $now ) );
		
		foreach ( $result['by_date'] as $date => $count ) {
			if ( $date === $today ) {
				$result['blocked_today'] += $count;
			}
			if ( $date >= $seven_days ) {
				$result['blocked_7_days'] += $count;
			}
			if ( $date >= $thirty_days ) {
				$result['blocked_30_days'] += $count;
			}
			if ( $date >= $ninety_days ) {
				$result['blocked_90_days'] += $count;
			}
		}
	}
	
	set_transient( 'wantispam_stats_cache', $result, DAY_IN_SECONDS );
	
	return $result;
}

/**
 * Flush anti-spam statistics cache
 */
function wantispam_flush_stats_cache() {
	delete_transient( 'wantispam_stats_cache' );
}
