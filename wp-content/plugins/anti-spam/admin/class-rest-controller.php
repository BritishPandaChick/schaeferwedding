<?php
/**
 * REST API Controller for Titan Security Dashboard
 * 
 * @package Titan_Security
 */

namespace WBCR\Titan;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Titan_Rest_Controller Class
 */
class Titan_Rest_Controller {
	/**
	 * Allowed storage types for backup configuration endpoints.
	 */
	private const ALLOWED_STORAGE_TYPES = [ 'local', 'ftp', 'dropbox' ];

	/**
	 * Singleton instance
	 *
	 * @var Titan_Rest_Controller|null
	 */
	private static $instance = null;

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes(): void {
		$namespace = 'titan-security/v1';

		// Two-Factor Authentication routes (only if premium 2FA module is loaded).
		if ( $this->is_two_factor_available() ) {
			register_rest_route(
				$namespace,
				'/two-factor/status',
				[
					'methods'             => \WP_REST_Server::READABLE,
					'permission_callback' => [ $this, 'check_logged_in' ],
					'callback'            => [ $this, 'get_two_factor_status' ],
				]
			);

			register_rest_route(
				$namespace,
				'/two-factor/setup',
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'permission_callback' => [ $this, 'check_logged_in' ],
					'callback'            => [ $this, 'setup_two_factor' ],
				]
			);

			register_rest_route(
				$namespace,
				'/two-factor/verify',
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'args'                => [
						'code' => [
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
					'permission_callback' => [ $this, 'check_logged_in' ],
					'callback'            => [ $this, 'verify_two_factor' ],
				]
			);

			register_rest_route(
				$namespace,
				'/two-factor/disable',
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'permission_callback' => [ $this, 'check_logged_in' ],
					'callback'            => [ $this, 'disable_two_factor' ],
				]
			);

			register_rest_route(
				$namespace,
				'/two-factor/regenerate-codes',
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'permission_callback' => [ $this, 'check_logged_in' ],
					'callback'            => [ $this, 'regenerate_backup_codes' ],
				]
			);

			register_rest_route(
				$namespace,
				'/two-factor/ip-whitelist',
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'args'                => [
						'ips' => [
							'type'     => 'array',
							'required' => true,
							'items'    => [ 'type' => 'string' ],
						],
					],
					'permission_callback' => [ $this, 'check_logged_in' ],
					'callback'            => [ $this, 'save_ip_whitelist' ],
				]
			);

			register_rest_route(
				$namespace,
				'/two-factor/users',
				[
					'methods'             => \WP_REST_Server::READABLE,
					'args'                => [
						'page'     => [
							'type'    => 'integer',
							'default' => 1,
						],
						'per_page' => [
							'type'    => 'integer',
							'default' => 20,
						],
						'search'   => [
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
					'permission_callback' => [ $this, 'check_permissions' ],
					'callback'            => [ $this, 'get_two_factor_users' ],
				]
			);

			register_rest_route(
				$namespace,
				'/two-factor/users/toggle',
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'args'                => [
						'user_id' => [
							'type'     => 'integer',
							'required' => true,
						],
						'enabled' => [
							'type'     => 'boolean',
							'required' => true,
						],
					],
					'permission_callback' => [ $this, 'check_permissions' ],
					'callback'            => [ $this, 'toggle_user_two_factor' ],
				]
			);

			register_rest_route(
				$namespace,
				'/two-factor/users/regenerate-codes',
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'args'                => [
						'user_id' => [
							'type'     => 'integer',
							'required' => true,
						],
					],
					'permission_callback' => [ $this, 'check_permissions' ],
					'callback'            => [ $this, 'regenerate_user_codes' ],
				]
			);
		}

		register_rest_route(
			$namespace,
			'/settings',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'permission_callback' => [ $this, 'check_permissions' ],
					'callback'            => [ $this, 'get_settings' ],
				],
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'args'                => [
						'data' => [
							'type'              => 'object',
							'required'          => true,
							'sanitize_callback' => [ $this, 'sanitize_settings' ],
							'validate_callback' => [ $this, 'validate_settings' ],
						],
					],
					'permission_callback' => [ $this, 'check_permissions' ],
					'callback'            => [ $this, 'save_settings' ],
				],
			] 
		);

		register_rest_route(
			$namespace,
			'/logs',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'get_logs' ],
			]
		);

		register_rest_route(
			$namespace,
			'/logs/clean',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'clean_logs' ],
			]
		);

		register_rest_route(
			$namespace,
			'/logs/export',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'export_logs' ],
			]
		);

		register_rest_route(
			$namespace,
			'/plugins/install-or-activate',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'args'                => [
					'plugin'      => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
					'plugin_slug' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'install_or_activate_plugin' ],
			]
		);

		// Backup routes.
		register_rest_route(
			$namespace,
			'/backup/start',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'start_backup' ],
			]
		);

		register_rest_route(
			$namespace,
			'/backup/abort',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'abort_backup' ],
			]
		);

		register_rest_route(
			$namespace,
			'/backup/progress',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'get_backup_progress' ],
			]
		);

		register_rest_route(
			$namespace,
			'/backup/list',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'get_backup_list' ],
			]
		);

		register_rest_route(
			$namespace,
			'/backup/delete',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'delete_backup' ],
				'args'                => [
					'date' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $param ) {
							return ! preg_match( '/[\/\\\\]|\.\.|\x00/', $param );
						},
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/backup/download',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'get_backup_download_url' ],
				'args'                => [
					'date' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $param ) {
							return ! preg_match( '/[\/\\\\]|\.\.|\x00/', $param );
						},
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/backup/storage',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'save_storage_config' ],
				'args'                => [
					'store'  => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $param ) {
							return in_array( $param, self::ALLOWED_STORAGE_TYPES, true );
						},
					],
					'config' => [
						'type'     => 'object',
						'required' => true,
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/backup/storage/delete',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'delete_storage_config' ],
				'args'                => [
					'store' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $param ) {
							return in_array( $param, self::ALLOWED_STORAGE_TYPES, true );
						},
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/backup/storage/oauth',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'get_storage_oauth_url' ],
				'args'                => [
					'storage' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $param ) {
							return in_array( $param, self::ALLOWED_STORAGE_TYPES, true );
						},
					],
				],
			]
		);

		register_rest_route(
			$namespace,
			'/bruteforce/log',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'get_bruteforce_log' ],
			]
		);

		register_rest_route(
			$namespace,
			'/bruteforce/unlock',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'args'                => [
					'ip' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $param ) {
							// Validate IP format (IPv4, IPv6, or IP range).
							return preg_match( '/^[0-9\.:a-fA-F\-]+$/', $param );
						},
					],
				],
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'unlock_bruteforce' ],
			]
		);

		register_rest_route(
			$namespace,
			'/bruteforce/log',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'get_bruteforce_log' ],
			]
		);

		register_rest_route(
			$namespace,
			'/bruteforce/unlock',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'args'                => [
					'ip' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $param ) {
							// Validate IP format (IPv4, IPv6, or IP range).
							return preg_match( '/^[0-9\.:a-fA-F\-]+$/', $param );
						},
					],
				],
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'unlock_bruteforce' ],
			]
		);

		register_rest_route(
			$namespace,
			'/spam-check/stats',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'get_spam_check_stats' ],
			]
		);

		register_rest_route(
			$namespace,
			'/bruteforce/log',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'get_bruteforce_log' ],
			]
		);

		register_rest_route(
			$namespace,
			'/bruteforce/unlock',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'args'                => [
					'ip' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $param ) {
							// Validate IP format (IPv4, IPv6, or IP range).
							return preg_match( '/^[0-9\.:a-fA-F\-]+$/', $param );
						},
					],
				],
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'unlock_bruteforce' ],
			]
		);

		register_rest_route(
			$namespace,
			'/license',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'args'                => [
					'data' => [
						'type'       => 'object',
						'required'   => true,
						'properties' => [
							'key'    => [
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_text_field',
								'validate_callback' => function ( $param ) {
									return is_string( $param );
								},
							],
							'action' => [
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_text_field',
								'validate_callback' => function ( $param ) {
									return in_array( $param, [ 'activate', 'deactivate' ], true );
								},
							],
						],
					],
				],
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'manage_license' ],
			]
		);

		register_rest_route(
			$namespace,
			'/audit/hide',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'args'                => [
					'id' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'hide_audit_item' ],
			]
		);

		register_rest_route(
			$namespace,
			'/audit/unhide',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'args'                => [
					'id' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'unhide_audit_item' ],
			]
		);

		register_rest_route(
			$namespace,
			'/database-prefix',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'args'                => [
					'new_prefix'      => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $param ) {
							return is_string( $param ) && ! empty( $param ) && preg_match( '/^[a-zA-Z0-9_]+$/', $param );
						},
					],
					'fixing_issue_id' => [
						'type'              => 'integer',
						'required'          => false,
						'sanitize_callback' => 'absint',
					],
				],
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'change_database_prefix' ],
			]
		);

		register_rest_route(
			$namespace,
			'/cache-tip/dismiss',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'permission_callback' => [ $this, 'check_permissions' ],
				'callback'            => [ $this, 'dismiss_cache_tip' ],
			]
		);
	}

	/**
	 * Check if user has permissions
	 *
	 * @return bool
	 */
	public function check_permissions(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check permissions for the install-or-activate plugin route.
	 *
	 * Requires manage_options, install_plugins, and activate_plugins capabilities,
	 * and restricts the operation to the specific allowed upsell plugin only.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request The REST request.
	 *
	 * @return bool|\WP_Error
	 */
	public function check_plugin_install_permissions( $request ) {
		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			return new \WP_Error( 'rest_forbidden', __( 'Sorry, you are not allowed to install or activate plugins.', 'anti-spam' ), [ 'status' => 403 ] );
		}

		$allowed_plugin      = 'wp-cloudflare-page-cache/wp-cloudflare-super-page-cache.php';
		$allowed_plugin_slug = 'wp-cloudflare-page-cache';

		if ( $request->get_param( 'plugin' ) !== $allowed_plugin || $request->get_param( 'plugin_slug' ) !== $allowed_plugin_slug ) {
			return new \WP_Error( 'rest_forbidden', __( 'Sorry, you are not allowed to install or activate this plugin.', 'anti-spam' ), [ 'status' => 403 ] );
		}

		return true;
	}

	/**
	 * Get settings schema (name => type mapping)
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_settings_schema(): array {
		return [
			'antispam_mode'                  => [
				'type'    => 'checkbox',
				'default' => true,
			],
			'save_spam_comments'             => [
				'type'    => 'checkbox',
				'default' => true,
			],
			'comment_form_privacy_notice'    => [
				'type'    => 'checkbox',
				'default' => false,
			],
			'advanced_spam_filter'           => [
				'type'    => 'checkbox',
				'default' => false,
			],
			'strong_password'                => [
				'type'    => 'checkbox',
				'default' => false,
			],
			'strong_password_min_role'       => [
				'type'    => 'dropdown',
				'default' => 'administrator',
				'options' => [ 'administrator', 'editor', 'author', 'contributor', 'subscriber' ],
			],
			'protect_author_get'             => [
				'type'    => 'checkbox',
				'default' => false,
			],
			'remove_x_pingback'              => [
				'type'    => 'checkbox',
				'default' => false,
			],
			'remove_html_comments'           => [
				'type'    => 'checkbox',
				'default' => false,
			],
			'remove_meta_generator'          => [
				'type'    => 'checkbox',
				'default' => false,
			],
			'remove_js_version'              => [
				'type'    => 'checkbox',
				'default' => false,
			],
			'remove_style_version'           => [
				'type'    => 'checkbox',
				'default' => false,
			],
			'remove_version_exclude'         => [
				'type'    => 'textarea',
				'default' => '',
			],
			'complete_uninstall'             => [
				'type'    => 'checkbox',
				'default' => false,
			],
			'schedule_backup'                => [
				'type'    => 'dropdown',
				'default' => 'off',
				'options' => [ 'off', '2h', '8h', '1d' ],
			],
			'remove_old_data'                => [
				'type'    => 'checkbox',
				'default' => false,
			],
			'backup_store'                   => [
				'type'    => 'dropdown',
				'default' => 'local',
				'options' => [ 'local', 'ftp', 'dropbox' ],
			],
			'backup_files_per_iteration'     => [
				'type'    => 'integer',
				'default' => 100,
			],
			'send_analytics'                 => [
				'type'    => 'checkbox',
				'default' => false,
			],
			'bruteforce_enabled'             => [
				'type'    => 'checkbox',
				'default' => false,
			],
			'bruteforce_gdpr'                => [
				'type'    => 'checkbox',
				'default' => false,
			],
			'bruteforce_allowed_retries'     => [
				'type'    => 'integer',
				'default' => 4,
				'min'     => 1,
				'max'     => 100,
			],
			'bruteforce_minutes_lockout'     => [
				'type'    => 'integer',
				'default' => 1200,
				'min'     => 60,
				'max'     => 86400,
			],
			'bruteforce_valid_duration'      => [
				'type'    => 'integer',
				'default' => 43200,
				'min'     => 3600,
				'max'     => 604800,
			],
			'bruteforce_whitelist_ips'       => [
				'type'    => 'array',
				'default' => [],
			],
			'bruteforce_whitelist_usernames' => [
				'type'    => 'array',
				'default' => [],
			],
			'bruteforce_blacklist_ips'       => [
				'type'    => 'array',
				'default' => [],
			],
			'bruteforce_blacklist_usernames' => [
				'type'    => 'array',
				'default' => [],
			],
		];
	}

	/**
	 * Validate settings data
	 *
	 * @param mixed $param Raw settings data.
	 * 
	 * @return bool|\WP_Error
	 */
	public function validate_settings( $param ) {
		if ( ! is_array( $param ) ) {
			return new \WP_Error( 'invalid_data', __( 'Settings data must be an array.', 'anti-spam' ), [ 'status' => 400 ] );
		}

		$schema = $this->get_settings_schema();

		foreach ( $param as $key => $value ) {
			if ( ! isset( $schema[ $key ] ) ) {
				return new \WP_Error( 
					'invalid_setting', 
					sprintf(
						// translators: %s: setting name.
						__( 'Unknown setting: %s', 'anti-spam' ),
						$key
					), 
					[ 'status' => 400 ] 
				);
			}

			$type = $schema[ $key ]['type'];

			if ( 'array' === $type && ! is_array( $value ) ) {
				return new \WP_Error( 
					'invalid_type', 
					sprintf(
						// translators: %s: setting name.
						__( 'Setting %s must be an array.', 'anti-spam' ),
						$key
					),
					[ 'status' => 400 ] 
				);
			}

			if ( 'integer' === $type ) {
				if ( ! is_numeric( $value ) ) {
					return new \WP_Error( 
						'invalid_type', 
						sprintf(
							// translators: %s: setting name.
							__( 'Setting %s must be a number.', 'anti-spam' ),
							$key
						),
						[ 'status' => 400 ] 
					);
				}

				$int_value = (int) $value;
				if ( isset( $schema[ $key ]['min'] ) && $int_value < $schema[ $key ]['min'] ) {
					return new \WP_Error( 
						'value_too_small', 
						sprintf(
							// translators: 1: setting name, 2: minimum value.
							__( 'Setting %1$s must be at least %2$d.', 'anti-spam' ),
							$key,
							$schema[ $key ]['min']
						),
						[ 'status' => 400 ] 
					);
				}

				if ( isset( $schema[ $key ]['max'] ) && $int_value > $schema[ $key ]['max'] ) {
					return new \WP_Error( 
						'value_too_large', 
						sprintf(
							// translators: 1: setting name, 2: maximum value.
							__( 'Setting %1$s must be at most %2$d.', 'anti-spam' ),
							$key,
							$schema[ $key ]['max']
						),
						[ 'status' => 400 ] 
					);
				}
			}

			if ( 'dropdown' === $type && isset( $schema[ $key ]['options'] ) ) {
				// Backward compatibility: allow empty dropdown values from legacy options/imports.
				if ( '' === $value || null === $value ) {
					continue;
				}

				if ( ! in_array( $value, $schema[ $key ]['options'], true ) ) {
					return new \WP_Error( 
						'invalid_option', 
						sprintf(
							// translators: %s: setting name.
							__( 'Invalid option for setting: %s', 'anti-spam' ),
							$key
						),
						[ 'status' => 400 ] 
					);
				}
			}
		}

		return true;
	}

	/**
	 * Sanitize settings data based on schema
	 *
	 * @param mixed $data Raw settings data.
	 * 
	 * @return array<string, mixed> Sanitized settings data.
	 */
	public function sanitize_settings( $data ) {
		if ( ! is_array( $data ) ) {
			return [];
		}

		$schema    = $this->get_settings_schema();
		$sanitized = [];

		foreach ( $data as $key => $value ) {
			if ( ! isset( $schema[ $key ] ) ) {
				continue;
			}

			switch ( $schema[ $key ]['type'] ) {
				case 'checkbox':
					if ( is_bool( $value ) ) {
						$sanitized[ $key ] = $value;
					} elseif ( is_numeric( $value ) ) {
						$sanitized[ $key ] = (bool) (int) $value;
					} elseif ( is_string( $value ) ) {
						$sanitized[ $key ] = in_array( strtolower( $value ), [ 'true', '1', 'yes', 'on' ], true );
					} else {
						$sanitized[ $key ] = (bool) $value;
					}
					break;

				case 'integer':
					$int_value = absint( $value );
					
					if ( isset( $schema[ $key ]['min'] ) ) {
						$int_value = max( $int_value, $schema[ $key ]['min'] );
					}
					if ( isset( $schema[ $key ]['max'] ) ) {
						$int_value = min( $int_value, $schema[ $key ]['max'] );
					}
					
					$sanitized[ $key ] = $int_value;
					break;

				case 'array':
					if ( ! is_array( $value ) ) {
						$sanitized[ $key ] = [];
						break;
					}

					if ( strpos( $key, 'ip' ) !== false ) {
						$sanitized[ $key ] = array_values(
							array_filter(
								array_map(
									function ( $item ) {
										$item = sanitize_text_field( trim( $item ) );
										// Allow single IPs or IP ranges (e.g., 1.2.3.4-5.6.7.8).
										if ( preg_match( '/^[0-9\.:a-fA-F\-]+$/', $item ) ) {
											return $item;
										}
										return '';
									},
									$value
								),
								function ( $item ) {
									return ! empty( $item );
								}
							)
						);
					} else {
						// Usernames: sanitize as text, max 50 items.
						$sanitized[ $key ] = array_values(
							array_filter(
								array_map(
									function ( $item ) {
										$item = sanitize_user( trim( $item ) );
										return strlen( $item ) <= 60 ? $item : '';
									},
									array_slice( $value, 0, 50 )
								),
								function ( $item ) {
									return ! empty( $item );
								}
							)
						);
					}
					break;

				case 'dropdown':
					$dropdown_value = sanitize_text_field( (string) $value );
					if (
						empty( $dropdown_value ) ||
						(
							isset( $schema[ $key ]['options'] ) &&
							! in_array( $dropdown_value, $schema[ $key ]['options'], true )
						)
					) {
						$dropdown_value = isset( $schema[ $key ]['default'] ) ? (string) $schema[ $key ]['default'] : '';
					}

					$sanitized[ $key ] = $dropdown_value;
					break;

				case 'textarea':
					$sanitized[ $key ] = sanitize_textarea_field( $value );
					break;

				case 'text':
				case 'textbox':
				default:
					$sanitized[ $key ] = sanitize_text_field( $value );
					break;
			}
		}

		return $sanitized;
	}

	/**
	 * Get all settings
	 *
	 * @return \WP_REST_Response
	 */
	public function get_settings(): \WP_REST_Response {
		$schema   = $this->get_settings_schema();
		$settings = [];

		foreach ( $schema as $key => $config ) {
			if ( 'send_analytics' === $key ) {
				$settings[ $key ] = ( 'yes' === get_option( WTITAN_PLUGIN_NAMESPACE . '_logger_flag', 'no' ) );
				continue;
			}

			$value = get_option( 'titan_' . $key, $config['default'] );

			// Self-heal legacy/invalid dropdown values stored in options.
			if (
				'dropdown' === $config['type'] &&
				isset( $config['options'] ) &&
				! in_array( $value, $config['options'], true )
			) {
				$value = $config['default'];
				update_option( 'titan_' . $key, $value );
			}

			// Convert stored integers back to booleans for checkboxes.
			if ( 'checkbox' === $config['type'] ) {
				$settings[ $key ] = (bool) $value;
			} else {
				$settings[ $key ] = $value;
			}
		}

		// Append read-only backup data (not user-configurable via schema).
		$settings['backup_store_data'] = get_option( 'titan_backup_store_data', [] );
		$settings['backup_status']     = get_option( 'titan_backup_status', 'stopped' );

		$privacy_policy_url                  = get_privacy_policy_url();
		$settings['privacy_policy_url']      = $privacy_policy_url;
		$settings['has_privacy_policy_page'] = ! empty( $privacy_policy_url );

		return new \WP_REST_Response(
			[
				'success'  => true,
				'settings' => $settings,
			],
			200
		);
	}

	/**
	 * Save settings
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Rest request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function save_settings( \WP_REST_Request $request ) {
		$data = $request->get_param( 'data' );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return new \WP_Error(
				'invalid_data',
				__( 'No settings data provided.', 'anti-spam' ),
				[ 'status' => 400 ]
			);
		}

		$schema  = $this->get_settings_schema();
		$updated = [];

		foreach ( $data as $key => $value ) {
			if ( 'send_analytics' === $key ) {
				update_option( WTITAN_PLUGIN_NAMESPACE . '_logger_flag', $value ? 'yes' : 'no' );
				$updated[] = $key;
				continue;
			}

			// Convert booleans to integers (1/0) for checkboxes to avoid WordPress get_option() false value bug.
			if ( isset( $schema[ $key ] ) && 'checkbox' === $schema[ $key ]['type'] && is_bool( $value ) ) {
				$value = $value ? 1 : 0;
			}

			update_option( 'titan_' . $key, $value );
			$updated[] = $key;
		}

		// Post-save hooks for cron side-effects.
		if ( in_array( 'schedule_backup', $updated, true ) ) {
			$this->sync_backup_schedule( $data['schedule_backup'] );
		}
		if ( in_array( 'remove_old_data', $updated, true ) ) {
			$this->sync_remove_old_data( $data['remove_old_data'] );
		}

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Settings saved successfully.', 'anti-spam' ),
				'updated' => $updated,
			],
			200
		);
	}

	/**
	 * Get logs content
	 *
	 * @return \WP_REST_Response
	 */
	public function get_logs(): \WP_REST_Response {
		require_once WTITAN_PLUGIN_DIR . '/includes/logger/class-logger-reader.php';
		require_once WTITAN_PLUGIN_DIR . '/includes/logger/class-logger-writter.php';

		$content = \WBCR\Titan\Logger\Reader::prettify();
		$size    = 0;

		try {
			$size = \WBCR\Titan\Logger\Writter::get_total_size();
		} catch ( \Exception $exception ) {
			\WBCR\Titan\Logger\Writter::error( sprintf( 'Failed to get total log size: %s', $exception->getMessage() ) );
		}

		return new \WP_REST_Response(
			[
				'success' => true,
				'content' => $content,
				'size'    => size_format( $size ),
			],
			200
		);
	}

	/**
	 * Clean up logs
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function clean_logs() {
		require_once WTITAN_PLUGIN_DIR . '/includes/logger/class-logger-writter.php';

		$cleaned = \WBCR\Titan\Logger\Writter::clean_up();

		if ( ! $cleaned ) {
			return new \WP_Error(
				'cleanup_failed',
				__( 'Failed to clean up logs.', 'anti-spam' ),
				[ 'status' => 500 ]
			);
		}

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Logs cleaned successfully.', 'anti-spam' ),
			],
			200
		);
	}

	/**
	 * Export logs as ZIP
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function export_logs() {
		require_once WTITAN_PLUGIN_DIR . '/includes/logger/class-logger-export.php';

		$export = new \WBCR\Titan\Logger\Export();

		if ( ! $export->prepare() ) {
			return new \WP_Error(
				'export_failed',
				__( 'Failed to prepare log export.', 'anti-spam' ),
				[ 'status' => 500 ]
			);
		}

		$uploads        = wp_get_upload_dir();
		$save_base_path = isset( $uploads['basedir'] ) ? $uploads['basedir'] : null;
		$zip_file_name  = sprintf( 'titan_debug_report-%s.zip', gmdate( 'Y-m-d' ) );
		$zip_file_path  = $save_base_path . DIRECTORY_SEPARATOR . $zip_file_name;

		if ( ! file_exists( $zip_file_path ) ) {
			return new \WP_Error(
				'export_not_found',
				__( 'Export file not found.', 'anti-spam' ),
				[ 'status' => 404 ]
			);
		}

		$upload_url = isset( $uploads['baseurl'] ) ? $uploads['baseurl'] : '';
		$file_url   = $upload_url . '/' . $zip_file_name;

		return new \WP_REST_Response(
			[
				'success' => true,
				'url'     => $file_url,
				'message' => __( 'Export file created successfully.', 'anti-spam' ),
			],
			200
		);
	}

	/**
	 * Get spam check statistics
	 *
	 * @return \WP_REST_Response
	 */
	public function get_spam_check_stats(): \WP_REST_Response {
		global $wpdb;

		$meta_key = \WBCR\Titan\Antispam\Advanced_Spam_Filter::META_STATUS;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value AS status, COUNT(*) AS count
				FROM {$wpdb->commentmeta}
				WHERE meta_key = %s
				GROUP BY meta_value",
				$meta_key
			)
		);

		$stats = [
			'enqueued'   => 0,
			'processing' => 0,
			'completed'  => 0,
			'failed'     => 0,
		];

		if ( $results ) {
			foreach ( $results as $row ) {
				$status = sanitize_text_field( $row->status );
				if ( isset( $stats[ $status ] ) ) {
					$stats[ $status ] = (int) $row->count;
				}
			}
		}

		return new \WP_REST_Response(
			[
				'success' => true,
				'stats'   => $stats,
			],
			200
		);
	}

	/**
	 * Manage license (activate/deactivate)
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request object.
	 * 
	 * @return \WP_REST_Response
	 */
	public function manage_license( \WP_REST_Request $request ) {
		$data = $request->get_param( 'data' );

		if ( ! isset( $data['key'] ) || ! isset( $data['action'] ) ) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'This action is no longer valid. Please refresh the page and try again.', 'anti-spam' ),
				],
				400
			);
		}

		require_once WTITAN_PLUGIN_DIR . '/includes/class-license.php';

		$license_manager = new \WBCR\Titan\WBCR_License();
		$response        = $license_manager->toggle_license( $data['action'], $data['key'] );

		if ( is_wp_error( $response ) ) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'message' => $response->get_error_message(),
				],
				400
			);
		}

		return new \WP_REST_Response( $response, 200 );
	}

	/**
	 * Check if the two-factor module is available
	 *
	 * @return bool
	 */
	private function is_two_factor_available(): bool {
		return class_exists( '\WBCR\TFA\TwoFactor' );
	}

	/**
	 * Get a safe HTTP host for QR code generation
	 *
	 * @return string
	 */
	private function get_safe_http_host(): string {
		$site_url  = get_bloginfo( 'url' );
		$http_host = '';

		if ( ! empty( $site_url ) ) {
			$parsed_host = wp_parse_url( $site_url, PHP_URL_HOST );
			if ( ! empty( $parsed_host ) ) {
				$http_host = $parsed_host;
			}
		}

		// Fallback to HTTP_HOST if site URL parsing fails.
		if ( empty( $http_host ) && isset( $_SERVER['HTTP_HOST'] ) ) {
			$http_host = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );
		}

		return $http_host;
	}

	/**
	 * Validate IP address or CIDR range
	 *
	 * @param string $ip IP address or CIDR range to validate.
	 *
	 * @return bool
	 */
	private function is_valid_ip_or_cidr( string $ip ): bool {
		// Check if it's a simple IP address (IPv4 or IPv6).
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return true;
		}

		// Check for CIDR notation (e.g., 192.168.1.0/24).
		if ( strpos( $ip, '/' ) !== false ) {
			$parts = explode( '/', $ip, 2 );
			if ( count( $parts ) === 2 ) {
				$ip_part   = $parts[0];
				$cidr_part = $parts[1];

				// Validate the IP part and CIDR part.
				if ( filter_var( $ip_part, FILTER_VALIDATE_IP ) && is_numeric( $cidr_part ) ) {
					$cidr = (int) $cidr_part;
					// Validate CIDR range (0-32 for IPv4, 0-128 for IPv6).
					if ( filter_var( $ip_part, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
						return $cidr >= 0 && $cidr <= 32;
					} elseif ( filter_var( $ip_part, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
						return $cidr >= 0 && $cidr <= 128;
					}
				}
			}
		}

		// Check for IP range notation (e.g., 192.168.1.1-192.168.1.255).
		if ( strpos( $ip, '-' ) !== false ) {
			$parts = explode( '-', $ip, 2 );
			if ( count( $parts ) === 2 ) {
				$start_ip = trim( $parts[0] );
				$end_ip   = trim( $parts[1] );

				// Both parts must be valid IP addresses.
				return filter_var( $start_ip, FILTER_VALIDATE_IP ) && filter_var( $end_ip, FILTER_VALIDATE_IP );
			}
		}

		return false;
	}

	/**
	 * Check if user is logged in
	 *
	 * @return bool
	 */
	public function check_logged_in(): bool {
		return is_user_logged_in();
	}

	/**
	 * Get a TwoFactor instance
	 *
	 * @return \WBCR\TFA\TwoFactor
	 */
	private function get_two_factor(): \WBCR\TFA\TwoFactor {
		return new \WBCR\TFA\TwoFactor();
	}

	/**
	 * Get two-factor data for a user (shared logic)
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_user_two_factor_data( int $user_id ): array {
		$available = class_exists( '\WBCR\TFA\TwoFactor' );

		$default_data = [
			'available'      => $available,
			'enabled'        => false,
			'setup_complete' => false,
			'qr_value'       => '',
			'secret_display' => '',
			'restore_codes'  => [],
			'ip_whitelist'   => [],
		];

		if ( ! $available ) {
			return $default_data;
		}

		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return $default_data;
		}

		$tf             = new \WBCR\TFA\TwoFactor();
		$enabled        = $tf->is_enabled_user_2fa( $user_id );
		$setup_complete = $tf->is_totp_key_saved( $user_id );

		$data = [
			'available'      => true,
			'enabled'        => $enabled,
			'setup_complete' => $setup_complete,
			'qr_value'       => '',
			'secret_display' => '',
			'restore_codes'  => [],
			'ip_whitelist'   => [],
		];

		if ( $enabled && $setup_complete ) {
			$data['restore_codes'] = $tf->get_user_restore_codes( $user_id );
			$data['ip_whitelist']  = $tf->get_ip_whitelist( $user_id );
		} elseif ( ! $setup_complete ) {
			$secret = $tf->get_user_totp_key( $user_id );
			if ( empty( $secret ) ) {
				$secret = $tf->generate_key();
				$tf->set_user_totp_key( $user_id, $secret );
			}

			// Get safe HTTP host for QR code.
			$site_url    = get_bloginfo( 'url' );
			$parsed_host = wp_parse_url( $site_url, PHP_URL_HOST );
			$http_host   = ! empty( $parsed_host ) ? $parsed_host : 'localhost';

			$data['qr_value']       = \WBCR\Titan\Plugin\Helper::build_totp_qr_value(
				sprintf( '%s@%s', $user->user_login, $http_host ),
				$secret,
				'Titan'
			);
			$data['secret_display'] = implode( ' ', str_split( $secret, 4 ) );
		}

		return $data;
	}

	/**
	 * Get current user's 2FA status
	 *
	 * @return \WP_REST_Response
	 */
	public function get_two_factor_status(): \WP_REST_Response {
		$user = wp_get_current_user();
		$data = self::get_user_two_factor_data( $user->ID );

		// Remove 'available' key for REST response (not needed in REST context).
		unset( $data['available'] );

		return new \WP_REST_Response( [ 'success' => true ] + $data, 200 );
	}

	/**
	 * Generate new QR/secret for setup
	 *
	 * @return \WP_REST_Response
	 */
	public function setup_two_factor(): \WP_REST_Response {
		$user   = wp_get_current_user();
		$tf     = $this->get_two_factor();
		$secret = $tf->generate_key();
		$tf->set_user_totp_key( $user->ID, $secret );

		$http_host = $this->get_safe_http_host();
		$qr_url    = \WBCR\TFA\TwoFactor::get_qr_code_url(
			sprintf( '%s@%s', $user->user_login, $http_host ),
			$secret,
			'Titan'
		);

		return new \WP_REST_Response(
			[
				'success'        => true,
				'qr_url'         => $qr_url,
				'qr_value'       => \WBCR\Titan\Plugin\Helper::build_totp_qr_value(
					sprintf( '%s@%s', $user->user_login, $http_host ),
					$secret,
					'Titan'
				),
				'secret_display' => implode( ' ', str_split( $secret, 4 ) ),
			],
			200
		);
	}

	/**
	 * Verify TOTP code and activate 2FA
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function verify_two_factor( $request ) {
		$user = wp_get_current_user();
		$tf   = $this->get_two_factor();
		$code = $request->get_param( 'code' );

		$secret = $tf->get_user_totp_key( $user->ID );

		if ( ! $tf->is_valid_key( $secret ) ) {
			return new \WP_Error(
				'invalid_secret',
				__( 'Invalid secret key. Please refresh and try again.', 'anti-spam' ),
				[ 'status' => 400 ]
			);
		}

		if ( ! $tf->is_valid_authcode( $secret, $code ) ) {
			return new \WP_Error(
				'invalid_code',
				__( 'Invalid authentication code. Please try again.', 'anti-spam' ),
				[ 'status' => 400 ]
			);
		}

		$tf->set_totp_saved( $user->ID, true );
		$tf->enable_user_2fa( $user->ID );
		$restore_codes = $tf->generate_user_restore_codes( $user );

		return new \WP_REST_Response(
			[
				'success'       => true,
				'message'       => __( 'Two-factor authentication activated successfully.', 'anti-spam' ),
				'restore_codes' => $restore_codes,
			],
			200
		);
	}

	/**
	 * Disable 2FA for current user
	 *
	 * @return \WP_REST_Response
	 */
	public function disable_two_factor(): \WP_REST_Response {
		$user = wp_get_current_user();
		$tf   = $this->get_two_factor();

		$tf->disable_user_2fa( $user->ID );
		$tf->set_totp_saved( $user->ID, false );

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Two-factor authentication has been disabled.', 'anti-spam' ),
			],
			200
		);
	}

	/**
	 * Regenerate backup codes for current user
	 *
	 * @return \WP_REST_Response
	 */
	public function regenerate_backup_codes(): \WP_REST_Response {
		$user = wp_get_current_user();
		$tf   = $this->get_two_factor();

		$codes = $tf->generate_user_restore_codes( $user );

		return new \WP_REST_Response(
			[
				'success'       => true,
				'message'       => __( 'Backup codes regenerated. New codes have been sent to your email.', 'anti-spam' ),
				'restore_codes' => $codes,
			],
			200
		);
	}

	/**
	 * Save IP whitelist for current user
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function save_ip_whitelist( $request ) {
		$user = wp_get_current_user();
		$tf   = $this->get_two_factor();
		$ips  = $request->get_param( 'ips' );

		if ( ! is_array( $ips ) ) {
			$ips = [];
		}

		$validated_ips = [];
		foreach ( $ips as $ip ) {
			$ip = sanitize_text_field( trim( $ip ) );
			if ( empty( $ip ) ) {
				continue;
			}

			// Validate IP address or CIDR range.
			if ( $this->is_valid_ip_or_cidr( $ip ) ) {
				$validated_ips[] = $ip;
			} else {
				return new \WP_Error(
					'invalid_ip',
					sprintf(
						// translators: %s: invalid IP address.
						__( 'Invalid IP address or IP range: %s', 'anti-spam' ),
						$ip
					),
					[ 'status' => 400 ]
				);
			}
		}

		$tf->set_ip_whitelist( $user->ID, $validated_ips );

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'IP whitelist saved successfully.', 'anti-spam' ),
			],
			200
		);
	}

	/**
	 * Get paginated users with 2FA status (admin only)
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_two_factor_users( $request ): \WP_REST_Response {
		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );
		$search   = $request->get_param( 'search' );

		// Ensure per_page is valid to prevent division by zero.
		if ( $per_page <= 0 ) {
			$per_page = 20;
		}

		$args = [
			'number' => $per_page,
			'paged'  => $page,
		];

		if ( ! empty( $search ) ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = [ 'user_login', 'user_email', 'display_name' ];
		}

		$user_query = new \WP_User_Query( $args );
		$users      = $user_query->get_results();
		$total      = $user_query->get_total();
		$tf         = $this->get_two_factor();

		$user_data = [];
		foreach ( $users as $user ) {
			$user_data[] = [
				'id'             => $user->ID,
				'username'       => $user->user_login,
				'email'          => $user->user_email,
				'display_name'   => $user->display_name,
				'enabled'        => $tf->is_enabled_user_2fa( $user->ID ),
				'setup_complete' => $tf->is_totp_key_saved( $user->ID ),
			];
		}

		return new \WP_REST_Response(
			[
				'success'     => true,
				'users'       => $user_data,
				'total'       => $total,
				'total_pages' => ceil( $total / $per_page ),
			],
			200
		);
	}

	/**
	 * Toggle 2FA for a specific user (admin only)
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function toggle_user_two_factor( $request ) {
		$user_id = $request->get_param( 'user_id' );
		$enabled = $request->get_param( 'enabled' );
		$user    = get_user_by( 'ID', $user_id );

		if ( ! $user ) {
			return new \WP_Error(
				'user_not_found',
				__( 'User not found.', 'anti-spam' ),
				[ 'status' => 404 ]
			);
		}

		$tf = $this->get_two_factor();

		if ( $enabled ) {
			// Check if user already has a TOTP key, if not generate one.
			$secret = $tf->get_user_totp_key( $user->ID );
			if ( empty( $secret ) ) {
				$secret = $tf->generate_key();
				$tf->set_user_totp_key( $user->ID, $secret );
			}
			$tf->enable_user_2fa( $user->ID );
			$tf->set_totp_saved( $user->ID, true );
		} else {
			$tf->disable_user_2fa( $user->ID );
			$tf->set_totp_saved( $user->ID, false );
		}

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => $enabled
					? __( 'Two-factor authentication enabled for user.', 'anti-spam' )
					: __( 'Two-factor authentication disabled for user.', 'anti-spam' ),
			],
			200
		);
	}

	/**
	 * Regenerate backup codes for a specific user (admin only)
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function regenerate_user_codes( $request ) {
		$user_id = $request->get_param( 'user_id' );
		$user    = get_user_by( 'ID', $user_id );

		if ( ! $user ) {
			return new \WP_Error(
				'user_not_found',
				__( 'User not found.', 'anti-spam' ),
				[ 'status' => 404 ]
			);
		}

		$tf    = $this->get_two_factor();
		$codes = $tf->generate_user_restore_codes( $user );

		return new \WP_REST_Response(
			[
				'success'       => true,
				'message'       => __( 'Backup codes regenerated for user.', 'anti-spam' ),
				'restore_codes' => $codes,
			],
			200
		);
	}

	/**
	 * Hide an audit item persistently.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function hide_audit_item( $request ): \WP_REST_Response {
		$id     = $request->get_param( 'id' );
		$hidden = get_option( 'titan_audit_hidden_items', [] );
		$hidden = is_array( $hidden ) ? $hidden : [];

		if ( ! in_array( $id, $hidden, true ) ) {
			$hidden[] = $id;
			update_option( 'titan_audit_hidden_items', $hidden, false );
		}

		return new \WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Unhide a previously hidden audit item.
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function unhide_audit_item( $request ): \WP_REST_Response {
		$id     = $request->get_param( 'id' );
		$hidden = get_option( 'titan_audit_hidden_items', [] );
		$hidden = is_array( $hidden ) ? $hidden : [];
		$hidden = array_values( array_filter( $hidden, fn( $v ) => (string) $v !== (string) $id ) );
		update_option( 'titan_audit_hidden_items', $hidden, false );

		return new \WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Change database prefix
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function change_database_prefix( \WP_REST_Request $request ) {
		$new_prefix      = $request->get_param( 'new_prefix' );
		$fixing_issue_id = $request->get_param( 'fixing_issue_id' );

		if ( empty( $new_prefix ) ) {
			return new \WP_Error(
				'empty_prefix',
				__( 'Prefix cannot be empty.', 'anti-spam' ),
				[ 'status' => 400 ]
			);
		}

		global $table_prefix, $wpdb;
		$old_prefix = $table_prefix;

		// Edit wp-config.php.
		if ( ! $this->edit_wp_config( $new_prefix ) ) {
			return new \WP_Error(
				'permission_error',
				__( 'The database prefix cannot be changed because the wp-config.php file is not writable.', 'anti-spam' ),
				[ 'status' => 500 ]
			);
		}

		// Rename all table names.
		$this->rename_table_names( $old_prefix, $new_prefix );

		// Update option names.
		$table_name = $new_prefix . 'options';
		$this->update_table_values( $table_name, 'option_name', $old_prefix, $new_prefix );

		// Update user meta keys.
		$table_name = $new_prefix . 'usermeta';
		$this->update_table_values( $table_name, 'meta_key', $old_prefix, $new_prefix );

		// Update the global table prefix and reinitialize $wpdb for the current request.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Necessary for database prefix change.
		$table_prefix = $new_prefix;
		$wpdb->set_prefix( $new_prefix );

		// Remove the audit issue if fixing_issue_id is provided.
		if ( ! empty( $fixing_issue_id ) && is_numeric( $fixing_issue_id ) ) {
			$issues = get_option( 'titan_audit_results', [] );

			if ( isset( $issues[ $fixing_issue_id ] ) ) {
				unset( $issues[ $fixing_issue_id ] );
				update_option( 'titan_audit_results', $issues, false );
			}
		}

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Database prefix has been changed.', 'anti-spam' ),
			],
			200
		);
	}

	/**
	 * Dismiss the caching performance tip banner
	 *
	 * @return \WP_REST_Response
	 */
	public function dismiss_cache_tip() {
		update_option( 'titan_cache_tip_dismissed', true );
		return new \WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * Install or activate a plugin
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request REST request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function install_or_activate_plugin( $request ) {
		$plugin      = $request->get_param( 'plugin' );
		$plugin_slug = $request->get_param( 'plugin_slug' );

		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Check if plugin is already installed.
		$is_installed = file_exists( WP_PLUGIN_DIR . '/' . $plugin );

		if ( ! $is_installed ) {
			// Plugin not installed, install it.
			if ( ! function_exists( 'plugins_api' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			}

			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/misc.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

			// Initialize the WP filesystem abstraction layer.
			if ( ! WP_Filesystem() ) {
				return new \WP_Error( 'filesystem_error', __( 'Could not initialize filesystem.', 'anti-spam' ), [ 'status' => 500 ] );
			}

			// Get plugin info from WordPress.org.
			$plugin_info = plugins_api( 'plugin_information', [ 'slug' => $plugin_slug ] );

			if ( is_wp_error( $plugin_info ) ) {
				return new \WP_Error( 'plugin_not_found', __( 'Plugin not found on WordPress.org.', 'anti-spam' ), [ 'status' => 404 ] );
			}

			if ( is_array( $plugin_info ) ) {
				$plugin_info = (object) $plugin_info;
			}

			if ( ! isset( $plugin_info->download_link ) || empty( $plugin_info->download_link ) ) {
				return new \WP_Error( 'missing_download_link', __( 'Plugin download URL could not be retrieved.', 'anti-spam' ), [ 'status' => 500 ] );
			}

			// Download and install the plugin.
			$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
			$result   = $upgrader->install( $plugin_info->download_link );

			if ( ! $result || is_wp_error( $result ) ) {
				$message = is_wp_error( $result ) ? $result->get_error_message() : __( 'Installation failed.', 'anti-spam' );
				return new \WP_Error( 'install_failed', $message, [ 'status' => 500 ] );
			}
		}

		// Activate the plugin if it is not already active.
		if ( ! is_plugin_active( $plugin ) ) {
			$activate_result = activate_plugin( $plugin );

			if ( is_wp_error( $activate_result ) ) {
				return new \WP_Error( 'activation_failed', $activate_result->get_error_message(), [ 'status' => 500 ] );
			}
		}

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => $is_installed ? __( 'Plugin activated successfully.', 'anti-spam' ) : __( 'Plugin installed and activated successfully.', 'anti-spam' ),
			],
			200
		);
	}

	/**
	 * Edit the wp-config.php file
	 *
	 * @param string $table_new_prefix The new database prefix.
	 *
	 * @return bool
	 */
	private function edit_wp_config( $table_new_prefix ) {
		$path = ABSPATH . 'wp-config.php';

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_is_writeable -- Necessary for wp-config.php editing.
		if ( ! file_exists( $path ) || ! is_writeable( $path ) ) {
			return false;
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPressVIPMinimum.Functions.RestrictedFunctions.chmod_chmod -- Necessary for wp-config.php editing.
		@chmod( $path, 0777 );
		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- Reading local wp-config.php file.
		$content = file_get_contents( $path );

		if ( false === $content ) {
			return false;
		}

		$content = preg_replace( '/\$table_prefix\s?=\s?\'[A-z0-9_-]+\'[\s\t]?;/i', "\$table_prefix = '{$table_new_prefix}';", $content );

		if ( null === $content || empty( $content ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Necessary for wp-config.php editing.
		$handle = fopen( $path, 'w+' );

		if ( false === $handle ) {
			return false;
		}

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite -- Necessary for wp-config.php editing.
		fwrite( $handle, $content );
		rewind( $handle );

		$file_size = filesize( $path );
		if ( false === $file_size || $file_size < 1 ) {
			fclose( $handle );
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Necessary for wp-config.php verification.
		$changed_file_content = fread( $handle, $file_size );

		fclose( $handle );
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPressVIPMinimum.Functions.RestrictedFunctions.chmod_chmod -- Restoring file permissions.
		@chmod( $path, 0644 );

		if ( false === $changed_file_content || false === strpos( $changed_file_content, "'$table_new_prefix'" ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Rename all the table names
	 *
	 * @param string $table_old_prefix The old database prefix.
	 * @param string $table_new_prefix The new database prefix.
	 *
	 * @return void
	 */
	private function rename_table_names( $table_old_prefix, $table_new_prefix ) {
		global $wpdb;

		$results = $wpdb->get_results( "SHOW TABLES LIKE '%'", ARRAY_N );

		foreach ( $results as $result ) {
			$table_old_name = $result[0];
			$table_new_name = $table_old_name;

			if ( strpos( $table_old_name, $table_old_prefix ) === 0 ) {
				$table_new_name = $table_new_prefix . substr( $table_old_name, strlen( $table_old_prefix ) );
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names cannot be prepared.
			$wpdb->query( "RENAME TABLE `{$table_old_name}` TO `{$table_new_name}`" );
		}
	}

	/**
	 * Update a table column with the new prefix
	 *
	 * @param string $table_name        The table name.
	 * @param string $field             The column name.
	 * @param string $table_old_prefix  The old prefix.
	 * @param string $table_new_prefix  The new prefix.
	 *
	 * @return void
	 */
	private function update_table_values( $table_name, $field, $table_old_prefix, $table_new_prefix ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table and field names cannot be prepared.
				"SELECT `{$field}` FROM `{$table_name}` WHERE `{$field}` LIKE %s",
				'%' . $wpdb->esc_like( $table_old_prefix ) . '%'
			),
			ARRAY_N
		);

		foreach ( $results as $result ) {
			$old_value = $result[0];

			if ( strpos( $old_value, $table_old_prefix ) === 0 ) {
				$new_value = $table_new_prefix . substr( $old_value, strlen( $table_old_prefix ) );

				$wpdb->query(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table and field names cannot be prepared.
						"UPDATE `{$table_name}` SET `{$field}` = %s WHERE `{$field}` = %s",
						$new_value,
						$old_value
					)
				);
			}
		}
	}

	/**
	 * Get brute force login attempts log
	 *
	 * @return \WP_REST_Response
	 */
	public function get_bruteforce_log(): \WP_REST_Response {
		require_once WTITAN_PLUGIN_DIR . '/includes/bruteforce/class-helpers.php';

		$lockouts     = (array) get_option( 'titan_bruteforce_lockouts', [] );
		$lockouts_log = get_option( 'titan_bruteforce_logged', [] );
		$log          = \WBCR\Titan\Bruteforce\Helpers::sorted_log_by_date( $lockouts_log );

		$formatted_log = [];
		foreach ( $log as $date => $user_info ) {
			$formatted_log[] = [
				'date'           => absint( $date ),
				'date_formatted' => date_i18n( 'F d, Y H:i', absint( $date ) ),
				'ip'             => sanitize_text_field( $user_info['ip'] ),
				'username'       => sanitize_text_field( $user_info['username'] ),
				'counter'        => absint( $user_info['counter'] ),
				'gateway'        => isset( $user_info['gateway'] ) ? sanitize_text_field( $user_info['gateway'] ) : 'Unknown',
				'is_locked'      => isset( $lockouts[ $user_info['ip'] ] ) && $lockouts[ $user_info['ip'] ] > time(),
				'unlocked'       => isset( $user_info['unlocked'] ) ? (bool) $user_info['unlocked'] : false,
			];
		}

		return new \WP_REST_Response(
			[
				'success' => true,
				'log'     => $formatted_log,
			],
			200
		);
	}

	/**
	 * Unlock a locked IP address and mark all usernames under it as unlocked
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * 
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function unlock_bruteforce( \WP_REST_Request $request ) {
		$ip = $request->get_param( 'ip' );

		if ( empty( $ip ) ) {
			return new \WP_Error(
				'invalid_params',
				__( 'IP address is required.', 'anti-spam' ),
				[ 'status' => 400 ]
			);
		}

		// Additional validation: ensure IP matches expected format.
		if ( ! preg_match( '/^[0-9\.:a-fA-F\-]+$/', $ip ) ) {
			return new \WP_Error(
				'invalid_ip',
				__( 'Invalid IP address format.', 'anti-spam' ),
				[ 'status' => 400 ]
			);
		}

		$lockouts     = (array) get_option( 'titan_bruteforce_lockouts', [] );
		$lockouts_log = (array) get_option( 'titan_bruteforce_logged', [] );

		// Remove IP-based lockout.
		if ( isset( $lockouts[ $ip ] ) ) {
			unset( $lockouts[ $ip ] );
			update_option( 'titan_bruteforce_lockouts', $lockouts );
		}

		// Mark all usernames under this IP as unlocked.
		if ( isset( $lockouts_log[ $ip ] ) && is_array( $lockouts_log[ $ip ] ) ) {
			foreach ( $lockouts_log[ $ip ] as $username => $data ) {
				$lockouts_log[ $ip ][ $username ]['unlocked'] = true;
			}
			update_option( 'titan_bruteforce_logged', $lockouts_log );
		}

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'IP unlocked successfully.', 'anti-spam' ),
			],
			200
		);
	}

	/**
	 * Sync backup schedule cron event
	 *
	 * @param string $schedule Schedule value (off, 2h, 8h, 1d).
	 */
	private function sync_backup_schedule( string $schedule ): void {
		wp_unschedule_hook( 'wbcr_bm_backup_time' );

		$intervals = [
			'2h' => 'every2hours',
			'8h' => 'every8hours',
			'1d' => 'everyday',
		];

		if ( 'off' !== $schedule && isset( $intervals[ $schedule ] ) ) {
			wp_schedule_event( time(), $intervals[ $schedule ], 'wbcr_bm_backup_time' );
		}
	}

	/**
	 * Sync remove old data cron event
	 *
	 * @param bool $enabled Whether auto-removal is enabled.
	 */
	private function sync_remove_old_data( bool $enabled ): void {
		wp_unschedule_hook( 'wbcr_bm_remove_old_data' );

		if ( $enabled ) {
			wp_schedule_event( time(), 'everyday', 'wbcr_bm_remove_old_data' );
		}
	}

	/**
	 * Check if premium backup classes are available
	 *
	 * @return true|\WP_Error
	 */
	private function check_backup_available() {
		if ( ! class_exists( '\WBCR\Titan\Backup\archive\Provider' ) ) {
			return new \WP_Error(
				'premium_required',
				__( 'Backup requires Pro.', 'anti-spam' ),
				[ 'status' => 403 ]
			);
		}
		return true;
	}

	/**
	 * Start a new backup
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function start_backup() {
		$check = $this->check_backup_available();
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$store = get_option( 'titan_backup_store', 'local' );
		if ( ! in_array( $store, self::ALLOWED_STORAGE_TYPES, true ) ) {
			$store = 'local';
			update_option( 'titan_backup_store', $store );
		}

		$provider = new \WBCR\Titan\Backup\archive\Provider();
		$provider->create( $store );

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Backup started.', 'anti-spam' ),
			],
			200
		);
	}

	/**
	 * Abort an in-progress backup
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function abort_backup() {
		$check = $this->check_backup_available();
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$archive_date = get_option( 'titan_current_archive' );

		$provider = new \WBCR\Titan\Backup\archive\Provider();
		$provider->stop_creating( $archive_date ? $archive_date : 'user_abort' );

		// Remove the partial backup entry and its files from disk.
		if ( $archive_date ) {
			$provider->remove_backup( $archive_date );
		}

		update_option( 'titan_backup_status', 'stopped' );

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Backup aborted.', 'anti-spam' ),
			],
			200
		);
	}

	/**
	 * Get backup progress
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_backup_progress() {
		$check = $this->check_backup_available();
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$state = get_option( 'titan_backup_status', 'stopped' );

		$progress = 0;
		$info     = [];

		if ( 'started' === $state ) {
			$total     = (int) get_option( 'titan_backup_total_files', 0 );
			$files     = get_option( 'titan_backup_file_list', [] );
			$remaining = is_array( $files ) ? count( $files ) : 0;

			if ( $total > 0 ) {
				$progress = 100 - ( $remaining / $total * 100 );
			}

			$archive = get_option( 'titan_current_archive' );

			// Sanitize archive name to prevent path traversal.
			$archive = $archive ? basename( $archive ) : '';

			$uploads = wp_get_upload_dir();
			$dir     = $uploads['basedir'] . '/TitanSecurityBackup/';

			if ( $archive && file_exists( $dir . $archive ) ) {
				$file_time = filemtime( $dir . $archive );
				if ( false === $file_time ) {
					$file_time = time();
				}
				
				$file_size = filesize( $dir . $archive );
				if ( false === $file_size ) {
					$file_size = 0;
				}

				$info = [
					'date'        => gmdate( 'Y-m-d H:i:s' ),
					'last_modify' => gmdate( 'Y-m-d H:i:s', $file_time ),
					'size'        => size_format( $file_size ),
				];
			}
		}

		return new \WP_REST_Response(
			[
				'success'  => true,
				'state'    => $state,
				'progress' => round( $progress, 2 ),
				'info'     => $info,
			],
			200
		);
	}

	/**
	 * Get list of existing backups
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_backup_list() {
		$check = $this->check_backup_available();
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$backup_list = get_option( 'titan_backup_list', [] );
		$backups     = [];

		if ( is_array( $backup_list ) ) {
			foreach ( $backup_list as $date => $backup ) {
				$backups[] = [
					'date'    => $date,
					'size'    => $this->get_backup_size( $backup ),
					'storage' => $this->get_backup_storage( $backup ),
				];
			}
		}

		return new \WP_REST_Response(
			[
				'success' => true,
				'backups' => $backups,
			],
			200
		);
	}

	/**
	 * Delete a backup
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request object.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_backup( $request ) {
		$check = $this->check_backup_available();
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$data = $request->get_json_params();
		$date = isset( $data['date'] ) ? sanitize_text_field( $data['date'] ) : '';

		if ( empty( $date ) ) {
			return new \WP_Error( 'missing_date', __( 'Backup date is required.', 'anti-spam' ), [ 'status' => 400 ] );
		}

		// Reject path traversal characters in the date parameter.
		if ( preg_match( '/[\/\\\\]|\.\.|\x00/', $date ) ) {
			return new \WP_Error( 'invalid_date', __( 'Invalid backup date format.', 'anti-spam' ), [ 'status' => 400 ] );
		}

		$provider = new \WBCR\Titan\Backup\archive\Provider();
		$provider->remove_backup( $date );

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Backup deleted.', 'anti-spam' ),
			],
			200
		);
	}

	/**
	 * Get backup download URL
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request object.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_backup_download_url( $request ) {
		$check = $this->check_backup_available();
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$date = sanitize_text_field( $request->get_param( 'date' ) );

		if ( empty( $date ) ) {
			return new \WP_Error( 'missing_date', __( 'Backup date is required.', 'anti-spam' ), [ 'status' => 400 ] );
		}

		// Reject path traversal characters in the date parameter.
		if ( preg_match( '/[\/\\\\]|\.\.|\x00/', $date ) ) {
			return new \WP_Error( 'invalid_date', __( 'Invalid backup date format.', 'anti-spam' ), [ 'status' => 400 ] );
		}

		$backup_list = get_option( 'titan_backup_list', [] );

		if ( ! isset( $backup_list[ $date ] ) ) {
			return new \WP_Error( 'not_found', __( 'Backup not found.', 'anti-spam' ), [ 'status' => 404 ] );
		}

		$backup    = $backup_list[ $date ];
		$uploads   = wp_get_upload_dir();
		$base_path = $uploads['basedir'] . '/TitanSecurityBackup/';
		$base_url  = $uploads['baseurl'] . '/TitanSecurityBackup/';

		$archives = isset( $backup->archives ) ? $backup->archives : [];
		$files    = [];
		$storage  = null;

		foreach ( $archives as $archive ) {
			if ( empty( $archive->file ) ) {
				continue;
			}

			// Sanitize filename to prevent path traversal.
			$safe_name = basename( $archive->file );
			$file_path = $base_path . $safe_name;

			if ( file_exists( $file_path ) ) {
				$file_size = filesize( $file_path );
				if ( false === $file_size ) {
					continue;
				}

				$files[] = [
					'url'  => $base_url . rawurlencode( $safe_name ),
					'name' => $safe_name,
					'size' => size_format( $file_size ),
					'part' => isset( $archive->part ) ? $archive->part : 1,
				];
				continue;
			}

			// File not on local disk — try remote storage.
			if ( empty( $archive->uploaded_file_info ) || ! is_array( $archive->uploaded_file_info ) ) {
				continue;
			}

			if ( ! class_exists( '\WBCR\Titan\Backup\Methods\Provider' ) ) {
				continue;
			}

			// Determine which remote storage holds this archive.
			$remote_storage_name = null;
			$remote_name         = '';
			foreach ( [ 'dropbox', 'ftp' ] as $candidate ) {
				if ( isset( $archive->uploaded_file_info[ $candidate ] ) ) {
					$info        = $archive->uploaded_file_info[ $candidate ];
					$remote_name = isset( $info['filename'] ) ? $info['filename'] : '';
					if ( ! empty( $remote_name ) ) {
						$remote_storage_name = $candidate;
						break;
					}
				}
			}

			if ( null === $remote_storage_name ) {
				continue;
			}

			// Lazily instantiate the remote storage method once.
			if ( null === $storage ) {
				$store_data = get_option( 'titan_backup_store_data', [] );
				if ( ! isset( $store_data[ $remote_storage_name ] ) ) {
					$storage = false;
					continue;
				}
				$storage = \WBCR\Titan\Backup\Methods\Provider::get_method( $remote_storage_name, $store_data[ $remote_storage_name ] );
				if ( ! $storage || ! $storage->connect() ) {
					$storage = false;
					continue;
				}
			}

			if ( false === $storage ) {
				continue;
			}

			if ( 'dropbox' === $remote_storage_name && method_exists( $storage, 'get_download_url' ) ) {
				// Dropbox: get a temporary download link directly.
				$download_url = $storage->get_download_url( $remote_name );
				if ( ! $download_url ) {
					continue;
				}

				$files[] = [
					'url'  => $download_url,
					'name' => $safe_name,
					'size' => isset( $archive->file_size ) ? size_format( $archive->file_size ) : '',
					'part' => isset( $archive->part ) ? $archive->part : 1,
				];
			} elseif ( 'ftp' === $remote_storage_name && method_exists( $storage, 'get' ) ) {
				// FTP/SFTP: download the file to local disk, then serve the local URL.
				if ( $storage->get( $remote_name, $base_path . $safe_name ) ) {
					$downloaded_size = filesize( $base_path . $safe_name );

					$files[] = [
						'url'  => $base_url . rawurlencode( $safe_name ),
						'name' => $safe_name,
						'size' => false !== $downloaded_size ? size_format( $downloaded_size ) : '',
						'part' => isset( $archive->part ) ? $archive->part : 1,
					];
				}
			}
		}

		if ( $storage && false !== $storage ) {
			$storage->close();
		}

		if ( empty( $files ) ) {
			return new \WP_Error( 'file_not_found', __( 'Backup file not found.', 'anti-spam' ), [ 'status' => 404 ] );
		}

		return new \WP_REST_Response(
			[
				'success' => true,
				'url'     => $files[0]['url'],
				'files'   => $files,
			],
			200
		);
	}

	/**
	 * Save storage configuration (FTP/Dropbox)
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request object.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function save_storage_config( $request ) {
		$check = $this->check_backup_available();
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$data   = $request->get_json_params();
		$store  = isset( $data['store'] ) ? sanitize_text_field( $data['store'] ) : '';
		$config = isset( $data['config'] ) ? $data['config'] : [];

		if ( empty( $store ) ) {
			return new \WP_Error( 'missing_store', __( 'Storage type is required.', 'anti-spam' ), [ 'status' => 400 ] );
		}

		if ( ! in_array( $store, self::ALLOWED_STORAGE_TYPES, true ) ) {
			return new \WP_Error( 'invalid_storage_type', __( 'Invalid storage type.', 'anti-spam' ), [ 'status' => 400 ] );
		}

		// Sanitize config values (preserve passwords that sanitize_text_field would corrupt).
		$sensitive_fields = [ 'ftp_password', 'dropbox_token' ];
		$sanitized        = [];
		foreach ( $config as $key => $value ) {
			$clean_key = sanitize_text_field( $key );
			if ( in_array( $clean_key, $sensitive_fields, true ) ) {
				$sanitized[ $clean_key ] = wp_unslash( $value );
			} else {
				$sanitized[ $clean_key ] = sanitize_text_field( $value );
			}
		}

		\WBCR\Titan\Backup\Methods\Provider::save_store_data( $store, $sanitized );

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Storage configuration saved.', 'anti-spam' ),
			],
			200
		);
	}

	/**
	 * Delete storage configuration
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request object.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_storage_config( $request ) {
		$check = $this->check_backup_available();
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$data  = $request->get_json_params();
		$store = isset( $data['store'] ) ? sanitize_text_field( $data['store'] ) : '';

		if ( empty( $store ) ) {
			return new \WP_Error( 'missing_store', __( 'Storage type is required.', 'anti-spam' ), [ 'status' => 400 ] );
		}

		if ( ! in_array( $store, self::ALLOWED_STORAGE_TYPES, true ) ) {
			return new \WP_Error( 'invalid_storage_type', __( 'Invalid storage type.', 'anti-spam' ), [ 'status' => 400 ] );
		}

		\WBCR\Titan\Backup\Methods\Provider::remove_store_data( $store );

		return new \WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Storage configuration removed.', 'anti-spam' ),
			],
			200
		);
	}

	/**
	 * Get OAuth URL for storage (e.g. Dropbox)
	 *
	 * @param \WP_REST_Request<array<string, mixed>> $request Request object.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_storage_oauth_url( $request ) {
		$check = $this->check_backup_available();
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$storage = sanitize_text_field( $request->get_param( 'storage' ) );

		if ( empty( $storage ) ) {
			return new \WP_Error( 'missing_storage', __( 'Storage type is required.', 'anti-spam' ), [ 'status' => 400 ] );
		}

		if ( ! in_array( $storage, self::ALLOWED_STORAGE_TYPES, true ) ) {
			return new \WP_Error( 'invalid_storage_type', __( 'Invalid storage type.', 'anti-spam' ), [ 'status' => 400 ] );
		}

		$url = \WBCR\Titan\Backup\Methods\Provider::get_oauth_link( $storage );

		return new \WP_REST_Response(
			[
				'success' => true,
				'url'     => $url,
			],
			200
		);
	}

	/**
	 * Get total formatted size of a backup from its archives.
	 *
	 * @param object $backup Backup object.
	 *
	 * @return string Formatted size or empty string.
	 */
	private function get_backup_size( $backup ) {
		$archives = isset( $backup->archives ) ? $backup->archives : [];
		if ( empty( $archives ) ) {
			return '';
		}

		$uploads   = wp_get_upload_dir();
		$base_path = $uploads['basedir'] . '/TitanSecurityBackup/';
		$total     = 0;

		foreach ( $archives as $archive ) {
			$file_name = isset( $archive->file ) ? basename( (string) $archive->file ) : '';
			$file_path = $base_path . $file_name;
			if ( ! empty( $archive->file ) && file_exists( $file_path ) ) {
				$file_size = filesize( $file_path );
				if ( false !== $file_size ) {
					$total += (int) $file_size;
				}
			} elseif ( ! empty( $archive->file_size ) ) {
				$total += (int) $archive->file_size;
			}
		}

		return $total > 0 ? size_format( $total ) : '';
	}

	/**
	 * Get storage type of a backup from its archives.
	 *
	 * @param object $backup Backup object.
	 *
	 * @return string Storage type identifier.
	 */
	private function get_backup_storage( $backup ) {
		$archives = isset( $backup->archives ) ? $backup->archives : [];
		if ( empty( $archives ) ) {
			return 'local';
		}

		$first = reset( $archives );
		if ( isset( $first->uploaded_file_info ) && is_array( $first->uploaded_file_info ) ) {
			$keys = array_keys( $first->uploaded_file_info );
			if ( ! empty( $keys ) ) {
				return $keys[0];
			}
		}

		return 'local';
	}

	/**
	 * Prevent cloning of the instance
	 *
	 * @throws \Exception An exception when trying to clone the instance.
	 */
	private function __clone() {
		throw new \Exception( 'Cannot clone singleton' );
	}

	/**
	 * Prevent unserialization of the instance
	 * 
	 * @throws \Exception An exception when trying to unserialize the instance.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}

	/**
	 * Get singleton instance
	 *
	 * @return Titan_Rest_Controller
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
