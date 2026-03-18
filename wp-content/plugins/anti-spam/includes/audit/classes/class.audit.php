<?php

namespace WBCR\Titan;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WBCR\Titan\Cert\Cert;

/**
 * Security audit class
 *
 * @version       1.0
 */
class Audit {

	/**
	 * @see self::app()
	 * @var Audit|null
	 */
	private static $app;

	/**
	 * @var AuditResult[]
	 */
	public $results = [];

	/**
	 * Audit constructor.
	 */
	public function __construct() {
		$this->get_audit();
	}

	/**
	 * @return Audit
	 */
	public static function app() {
		if ( null === self::$app ) {
			self::$app = new self();
		}
		return self::$app;
	}

	/**
	 * Get audit
	 *
	 * @return AuditResult[]|bool Results
	 */
	public function get_audit() {
		$this->results = get_transient( 'titan_audit_results' );
		if ( false === $this->results ) {
			return false;
		}

		if ( ! is_array( $this->results ) ) {
			$this->results = [];
		}

		return $this->results;
	}

	/**
	 * Do audit
	 *
	 * @return AuditResult[] Results
	 */
	public function do_audit() {
		$this->results = [];

		$this->check_versions();
		$this->check_wpconfig();
		$this->check_php_variables();
		$this->check_https();
		$this->check_users();
		$this->check_updates();
		$this->check_files();
		$this->check_fileEditor();
		$this->check_folders_access();

		set_transient( 'titan_audit_results', $this->results, 5 * MINUTE_IN_SECONDS );

		return $this->results;
	}

	/**
	 * Add result
	 *
	 * @param string $title
	 * @param string $description
	 * @param string $severity
	 * @param bool   $hided
	 */
	public function add( $title, $description, $severity, $fix = '', $hided = false ) {
		$this->results[] = new AuditResult( $title, $description, $severity, $fix, $hided );
	}

	/**
	 * Versions audit
	 *
	 * @return AuditResult[] Results
	 */
	public function check_versions() {
		// PHP.
		/* translators: 1: current PHP version, 2: recommended PHP version */
		$title       = sprintf( __( 'Your PHP version %1$1s is less than the recommended %2$2s', 'anti-spam' ), PHP_VERSION, '7.2.0' );
		$description = __( 'Older PHP versions have known security vulnerabilities and performance issues. Update to a newer version.', 'anti-spam' );
		if ( version_compare( PHP_VERSION, '7.2.0' ) < 0 ) {
			$this->add( $title, $description, 'medium' );
		}

		// MySQL.
		global $wpdb;
		/* translators: 1: current MySQL version, 2: recommended MySQL version */
		$title       = sprintf( __( 'Your MySQL version %1$1s is less than the recommended %2$2s', 'anti-spam' ), $wpdb->db_version(), '4.0.0' );
		$description = __( 'Older MySQL versions have known security vulnerabilities and performance issues. Update to a newer version.', 'anti-spam' );
		if ( version_compare( $wpdb->db_version(), '4.0.0' ) < 0 ) {
			$this->add( $title, $description, 'medium' );
		}

		// WordPress.
		global $wp_version;
		/* translators: 1: current WordPress version, 2: recommended WordPress version */
		$title       = sprintf( __( 'Your WordPress version %1$1s is less than the recommended %2$2s', 'anti-spam' ), $wp_version, '5.2.0' );
		$description = __( 'Older WordPress versions have known security vulnerabilities. Update to the latest version.', 'anti-spam' );
		if ( version_compare( $wp_version, '5.2.0' ) < 0 ) {
			$this->add( $title, $description, 'medium', admin_url( 'update-core.php' ) );
		}

		return $this->results;
	}

	/**
	 * Debug audit
	 *
	 * @return AuditResult[] Results
	 */
	public function check_wpconfig() {
		// WP_DEBUG.
		$title       = __( 'WordPress Debug mode is enabled on your site', 'anti-spam' );
		$description = __( 'Debug mode is intended for development only. When left enabled on a live site, it can expose error details that attackers may use to find vulnerabilities. Disable WP_DEBUG in wp-config.php.', 'anti-spam' );
		if ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			$this->add( $title, $description, 'high' );
		}

		// SAVEQUERIES.
		$title       = __( 'WordPress Database Debug mode is enabled on your site', 'anti-spam' );
		$description = __( 'When it\'s enabled, all SQL queries will be saved in the $wpdb->queries variable as an array. For security and performance reasons, this constant must be disabled on the production site.', 'anti-spam' );
		if ( ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) ) {
			$this->add( $title, $description, 'low' );
		}

		// SCRIPT_DEBUG.
		$title       = __( 'WordPress Script Debug Mode is enabled on your site', 'anti-spam' );
		$description = __( 'When enabled, WordPress will use non-compressed versions (dev versions) of JS and CSS files. The default is to use min versions of the files. For security and performance reasons, this constant must be disabled on the production site.', 'anti-spam' );
		if ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
			$this->add( $title, $description, 'low' );
		}

		// $table_prefix.
		$title       = __( "Database table prefix is empty or has a default value: 'wp_'", 'anti-spam' );
		$description = __( "Using the default 'wp_' prefix makes your database easier to target in automated attacks. Change it to a custom prefix.", 'anti-spam' );
		global $wpdb;
		if ( empty( $wpdb->prefix ) || 'wp_' == $wpdb->prefix ) {
			$fix_url = add_query_arg(
				[
					'page'   => 'titan-security',
					'action' => 'fix-database-prefix',
				],
				admin_url( 'admin.php' )
			);
			$this->add( $title, $description, 'medium', $fix_url );
		}

		// SALT/KEYS.
		$title       = __( 'Security keys are not configured in your wp-config.php file', 'anti-spam' );
		$description = __( "You can generate these using the <a href='https://api.wordpress.org/secret-key/1.1/salt/'>WordPress.org secret-key service</a>", 'anti-spam' );
		if ( ( ! defined( 'AUTH_KEY' ) || empty( AUTH_KEY ) ) || ( ! defined( 'SECURE_AUTH_KEY' ) || empty( SECURE_AUTH_KEY ) ) || ( ! defined( 'LOGGED_IN_KEY' ) || empty( LOGGED_IN_KEY ) ) || ( ! defined( 'NONCE_KEY' ) || empty( NONCE_KEY ) ) || ( ! defined( 'AUTH_SALT' ) || empty( AUTH_SALT ) ) || ( ! defined( 'SECURE_AUTH_SALT' ) || empty( SECURE_AUTH_SALT ) ) || ( ! defined( 'LOGGED_IN_SALT' ) || empty( LOGGED_IN_SALT ) ) || ( ! defined( 'NONCE_SALT' ) || empty( NONCE_SALT ) ) ) {
			$this->add( $title, $description, 'low' );
		}

		return $this->results;
	}

	/**
	 * PHP variables audit
	 *
	 * @return AuditResult[] Results
	 */
	public function check_php_variables() {
		// display_errors.
		$title       = __( "The 'display_errors' PHP directive is enabled", 'anti-spam' );
		$description = __( 'Displaying debugging information poses a serious security risk. If any PHP errors occur on your site, they must be registered in a secure location and not displayed to visitors or potential attackers.', 'anti-spam' );
		if ( ini_get( 'display_errors' ) ) {
			$this->add( $title, $description, 'high' );
		}

		// allow_url_include.
		$title       = __( "The 'allow_url_include' PHP directive is enabled", 'anti-spam' );
		$description = __( "Enabling 'allow_url_include' makes your site vulnerable to remote file inclusion attacks. Disable it in your PHP configuration.", 'anti-spam' );
		if ( ini_get( 'allow_url_include' ) ) {
			$this->add( $title, $description, 'high' );
		}

		// expose_php.
		$title       = __( 'Your server is revealing its PHP version publicly', 'anti-spam' );
		$description = __( "Enabling 'expose_php' PHP Directive exposes to the world that PHP is installed on the server, which includes the PHP version within the HTTP header.", 'anti-spam' );
		if ( ini_get( 'expose_php' ) ) {
			$this->add( $title, $description, 'low' );
		}

		return $this->results;
	}

	/**
	 * HTTPS audit
	 *
	 * @return AuditResult[] Results
	 */
	public function check_https() {
		$title       = __( 'Problems with the SSL certificate were detected on your site', 'anti-spam' );
		$description = '';
		$securedUrl  = get_site_url( null, '', 'https' );
		$cert        = Cert::get_instance();
		if ( $cert->is_available() ) {
			if ( ! $cert->is_lets_encrypt() ) {
				/* translators: %s: expiration date */
				$description = sprintf( __( 'The SSL certificate expires on %s', 'anti-spam' ), date( 'd-m-Y H:i:s', $cert->get_expiration_timestamp() ) );
			}
		} else {
			switch ( $cert->get_error() ) {
				case Cert::ERROR_UNAVAILABLE:
					$description = __( 'The PHP OpenSSL extension is not installed.', 'anti-spam' );
					break;
				case Cert::ERROR_ONLY_HTTPS:
					$description = sprintf( __( "Available only on <a href='%1\$1s'>%2\$2s</a>", 'anti-spam' ), $securedUrl, $securedUrl );
					break;
				case Cert::ERROR_HTTPS_UNAVAILABLE:
					$description = __( 'HTTPS is not available on this site', 'anti-spam' );
					break;
				case Cert::ERROR_UNKNOWN_ERROR:
					$description = __( 'Unknown error', 'anti-spam' );
					break;
				default:
					$description = __( 'Error', 'anti-spam' );
					break;
			}
		}

		$this->add( $title, $description, 'medium' );

		return $this->results;
	}

	/**
	 * Users audit
	 * Check if an user can be found by its ID
	 *
	 * @return AuditResult[] Results
	 */
	public function check_users() {
		$users = get_users(
			[
				'role' => 'administrator',
			] 
		);
		$admin = false;
		foreach ( $users as $user ) {
			if ( 'admin' == $user->user_login || 'administrator' == $user->user_login ) {
				$admin = true;
			}
		}

		$title       = __( "The standard administrator login 'admin' is used", 'anti-spam' );
		$description = __( 'Using \'admin\' as a username makes brute-force attacks easier since attackers already know half the credentials. Use a unique administrator username.', 'anti-spam' );
		if ( $admin ) {
			$this->add( $title, $description, 'medium' );
		}

		// User ID.
		$title       = __( 'Author URL by ID access', 'anti-spam' );
		$description = __( 'By knowing the username, you are one step closer to logging in using the username to brute-force the password, or to gain access in a similar way.', 'anti-spam' );

		$users    = get_users(
			[
				'number' => 5,
			]
		);
		$url      = home_url() . '/?author=';
		$home_url = home_url( '/' );
		foreach ( $users as $user ) {
			$response      = wp_remote_get(
				$url . $user->ID,
				[
					'redirection' => 0,
					'sslverify'   => 0,
				]
			);
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 301 === $response_code ) {
				$location = wp_remote_retrieve_header( $response, 'location' );
				if ( is_array( $location ) ) {
					$location = end( $location );
				}

				// If the redirect goes to the homepage, the protect_author_get tweak is active.
				if ( ! empty( $location ) && trailingslashit( $location ) === $home_url ) {
					break;
				}

				$this->add( $title, $description, 'medium' );
				break;
			}
		}

		return $this->results;
	}

	/**
	 * Updates audit
	 *
	 * @return AuditResult[] Results
	 */
	public function check_updates() {
		$plugins = get_plugins();

		// COMPATIBLE.
		$no_requirement = [];
		foreach ( (array) $plugins as $plugin_file => $plugin_data ) {
			$requirement = validate_plugin_requirements( $plugin_file );
			if ( is_wp_error( $requirement ) ) {
				$no_requirement[] = $plugin_data['Name'];
			}
		}

		$title       = __( 'Incompatible plugins found', 'anti-spam' );
		$description = __( 'Some plugins on your site are not compatible with PHP and WordPress versions: ', 'anti-spam' );
		if ( ! empty( $no_requirement ) ) {
			$description .= '\n' . implode( ', ', $no_requirement );
			$this->add( $title, $description, 'medium' );
		}

		// UPDATE Plugins.
		$current = get_site_transient( 'update_plugins' );
		foreach ( (array) $current->response as $plugin_file => $plugin_data ) {
			$plugins_update[] = $plugin_data->slug;
		}
		$i = 0;
		foreach ( (array) $plugins as $plugin_file => $plugin_data ) {
			if ( isset( $current->response[ $plugin_file ] ) ) {
				$plugins[ $plugin_file ]['update'] = true;
				++$i;
			}
		}
		/* translators: %1s: number of plugins */
		$title       = sprintf( __( 'You have %1s plugins that need to be updated', 'anti-spam' ), $i );
		$description = __( 'Need to update plugins, as previous versions may be vulnerable:', 'anti-spam' );
		if ( ! empty( $plugins_update ) ) {
			$description .= ' ' . implode( ', ', $plugins_update );
		}
		if ( $i ) {
			$this->add( $title, $description, 'medium', admin_url( 'update-core.php' ) );
		}

		// UPDATE Themes.
		$themes  = wp_get_themes();
		$current = get_site_transient( 'update_themes' );
		foreach ( (array) $current->response as $theme_file => $theme_data ) {
			$themes_update[] = $theme_data['theme'];
		}
		$i = 0;
		foreach ( (array) $themes as $key => $theme ) {
			if ( isset( $current->response[ $key ] ) ) {
				$themes[ $key ]->update = true;
				++$i;
			}
		}
		/* translators: %1s: number of themes */
		$title       = sprintf( __( 'You have %1s themes that need to be updated', 'anti-spam' ), $i );
		$description = __( 'Need to update themes, as previous versions may be vulnerable:', 'anti-spam' );
		if ( ! empty( $themes_update ) ) {
			$description .= ' ' . implode( ', ', $themes_update );
		}
		if ( $i ) {
			$this->add( $title, $description, 'medium', admin_url( 'update-core.php' ) );
		}

		return $this->results;
	}

	/**
	 * Check files audit
	 *
	 * @return AuditResult[] Results
	 */
	public function check_files() {
		// readme.html.
		$title       = __( 'Readme.html or readme.txt file is available in the site root', 'anti-spam' );
		$description = __( 'It is important to hide or delete the readme.html or readme.txt file, because it contains information about the WP version.', 'anti-spam' );
		if ( file_exists( ABSPATH . 'readme.html' ) || file_exists( ABSPATH . 'readme.txt' ) ) {
			$this->add( $title, $description, 'low' );
		}

		return $this->results;
	}

	/**
	 * Check database password
	 *
	 * @return AuditResult[] Results
	 */
	public function check_fileEditor() {
		$title       = __( 'The plugins and themes file editor is enabled on your site', 'anti-spam' );
		$description = __( 'The built-in file editor lets anyone with admin access modify PHP files directly, making it easier for attackers to inject malicious code.', 'anti-spam' );
		/* translators: %1$s: PHP code snippet to disable file editor */
		$description .= sprintf( __( 'Disable it for live websites in <b>wp_config.php:</b><br>%1$s', 'anti-spam' ), "<code>define('DISALLOW_FILE_EDIT', true);</code>" );
		if ( ! defined( 'DISALLOW_FILE_EDIT' ) || ! DISALLOW_FILE_EDIT ) {
			$this->add( $title, $description, 'low' );
		}

		return $this->results;
	}

	/**
	 * Check folders access
	 *
	 * @return AuditResult[] Results
	 */
	public function check_folders_access() {
		$title       = __( 'The Uploads folder is browsable.', 'anti-spam' );
		$description = __( 'Your Uploads folder is publicly browsable, allowing anyone to view and download its contents.', 'anti-spam' );

		$url      = wp_upload_dir();
		$url      = $url['baseurl'];
		$response = wp_remote_get(
			$url,
			[
				'redirection' => 0,
				'sslverify'   => 0,
			] 
		);
		if ( ! is_wp_error( $response ) ) {
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 200 == $response_code ) {
				$this->add( $title, $description, 'medium' );
			}
		}

		return $this->results;
	}

	/**
	 * @return int
	 */
	public function get_count() {
		return is_array( $this->results ) ? count( $this->results ) : 0;
	}
}
