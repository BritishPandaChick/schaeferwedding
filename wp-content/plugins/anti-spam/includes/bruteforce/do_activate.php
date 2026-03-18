<?php

if ( ! get_option( 'titan_bruteforce_set_default_options' ) ) {
	update_option( 'titan_bruteforce_set_default_options', 1 );
	update_option( 'titan_bruteforce_gdpr', 0 );
	update_option( 'titan_bruteforce_logged', '' );
	update_option( 'titan_bruteforce_lockouts_total', 0 );
	update_option( 'titan_bruteforce_minutes_lockout', 1200 );
	update_option( 'titan_bruteforce_valid_duration', 43200 );
	update_option( 'titan_bruteforce_allowed_retries', 4 );
	update_option( 'titan_bruteforce_lockouts', [] );
	update_option( 'titan_bruteforce_whitelist_ips', [] );
	update_option( 'titan_bruteforce_whitelist_usernames', [] );
	update_option( 'titan_bruteforce_blacklist_ips', [] );
	update_option( 'titan_bruteforce_blacklist_usernames', [] );
}
