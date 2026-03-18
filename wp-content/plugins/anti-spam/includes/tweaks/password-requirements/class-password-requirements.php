<?php

namespace WBCR\Titan\Tweaks;

/**
 * Class \WBCR\Titan\Tweaks\Password_Requirements
 */
class Password_Requirements {

	const META_KEY = '_titan_password_requirements';

	public function run() {

		add_action( 'user_profile_update_errors', [ $this, 'forward_profile_pass_update' ], 0, 3 );
		add_action( 'validate_password_reset', [ $this, 'forward_reset_pass' ], 10, 2 );

		add_action( 'profile_update', [ $this, 'handle_update_user' ], 10, 2 );
		add_action( 'password_reset', [ $this, 'handle_password_reset' ], 10, 2 );
		add_filter( 'wp_authenticate_user', [ $this, 'check_password_on_login' ], 999, 2 );

		add_action( 'add_user_role', [ $this, 'handle_role_change' ] );
		add_action( 'set_user_role', [ $this, 'handle_role_change' ] );
		add_action( 'remove_user_role', [ $this, 'handle_role_change' ] );

		add_action( 'titan_validate_password', [ $this, 'validate_password' ], 10, 4 );

		add_action( 'wp_login', [ $this, 'flag_check' ], 9, 2 );

		add_action( 'titan_login_interstitial_init', [ $this, 'register_interstitial' ] );
	}

	/**
	 * When a user's password is updated, or a new user created, verify that the new password is valid.
	 *
	 * @param \WP_Error          $errors
	 * @param bool               $update
	 * @param \WP_User|\stdClass $user
	 */
	public function forward_profile_pass_update( $errors, $update, $user ) {

		if ( $errors->get_error_message( 'pass' ) ) {
			return;
		}

		if ( isset( $user->user_pass ) ) {
			$this->handle_profile_update_password( $errors, $update, $user );
		} elseif ( $update && isset( $user->role ) ) {
			$this->handle_profile_update_role( $errors, $user );
		}
	}

	/**
	 * Handle the password being updated for a user.
	 *
	 * @param \WP_Error          $errors
	 * @param bool               $update
	 * @param \WP_User|\stdClass $user
	 */
	private function handle_profile_update_password( $errors, $update, $user ) {
		if ( ! $update ) {
			$context = 'admin-user-create';
		} elseif ( isset( $user->ID ) && get_current_user_id() === $user->ID ) {
			$context = 'profile-update';
		} else {
			$context = 'admin-profile-update';
		}

		$args = [
			'error'   => $errors,
			'context' => $context,
		];

		if ( isset( $user->role ) ) {
			$args['role'] = $user->role;
		}

		\WBCR\Titan\Tweaks\Password_Requirements_Base::validate_password( $user, $user->user_pass, $args );
	}

	/**
	 * Handle the user's role being updated.
	 *
	 * @param \WP_Error          $errors
	 * @param \WP_User|\stdClass $user
	 */
	private function handle_profile_update_role( $errors, $user ) {
		$settings = [
			'strength' => [
				'role' => get_option( 'titan_strong_password_min_role', 'administrator' ),
			],
		];

		foreach ( \WBCR\Titan\Tweaks\Password_Requirements_Base::get_registered() as $code => $requirement ) {

			if ( ! $requirement['validate'] || ! \WBCR\Titan\Tweaks\Password_Requirements_Base::is_requirement_enabled( $code ) ) {
				continue;
			}

			$evaluation = get_user_meta( $user->ID, $requirement['meta'], true );

			if ( '' === $evaluation ) {
				continue;
			}

			require_once WTITAN_PLUGIN_DIR . '/includes/tweaks/password-requirements/class-canonical-roles.php';

			$args = [
				'role'      => $user->role,
				'canonical' => \WBCR\Titan\Tweaks\Canonical_Roles::get_canonical_role_from_role_and_user( $user->role, $user ),
			];

			$validated = call_user_func( $requirement['validate'], $evaluation, $user, $settings[ $code ], $args );

			if ( true === $validated ) {
				continue;
			}

			$message = $validated ? $validated : esc_html__( "The provided password does not meet this site's requirements.", 'anti-spam' );
			$errors->add( 'pass', $message );
		}
	}

	/**
	 * When a user attempts to reset their password, verify that the new password is valid.
	 *
	 * @param \WP_Error $errors
	 * @param \WP_User  $user
	 */
	public function forward_reset_pass( $errors, $user ) {

		if ( ! isset( $_POST['pass1'] ) || is_wp_error( $user ) ) {
			// The validate_password_reset action fires when first rendering the reset page and when handling the form
			// submissions. Since the pass1 data is missing, this must be the initial page render. So, we don't need to
			// do anything yet.
			return;
		}

		\WBCR\Titan\Tweaks\Password_Requirements_Base::validate_password(
			$user,
			$_POST['pass1'],
			[
				'error'   => $errors,
				'context' => 'reset-password',
			] 
		);
	}

	/**
	 * Whenever a user object is updated, set when their password was last updated.
	 *
	 * @param int    $user_id
	 * @param object $old_user_data
	 */
	public function handle_update_user( $user_id, $old_user_data ) {

		$user = get_userdata( $user_id );

		if ( $user->user_pass === $old_user_data->user_pass ) {
			return;
		}

		$this->handle_password_updated( $user );
	}

	/**
	 * When a user resets their password, update the last change time.
	 *
	 * For some unknown reason, the password reset routine uses {@see wp_set_password()} instead of {@see wp_update_user()}.
	 *
	 * @param \WP_User $user
	 * @param string   $new_password
	 */
	public function handle_password_reset( $user, $new_password ) {
		$this->handle_password_updated( $user );
		$this->handle_plain_text_password_available( $user, $new_password );
	}

	/**
	 * When a user logs in, if their password hasn't been validated yet,
	 * validate it.
	 *
	 * @param \WP_User $user
	 * @param string   $password
	 *
	 * @return \WP_User
	 */
	public function check_password_on_login( $user, $password ) {

		if ( ! $user instanceof \WP_User ) {
			return $user;
		}

		if ( ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
			return $user;
		}

		$this->handle_plain_text_password_available( $user, $password );

		return $user;
	}

	/**
	 * When a password is updated, set the last updated time and delete any pending required change.
	 *
	 * @param \WP_User $user
	 */
	protected function handle_password_updated( $user ) {
		delete_user_meta( $user->ID, 'titan_password_change_required' );
		update_user_meta( $user->ID, 'titan_last_password_change', time() );
	}

	/**
	 * When a plain text password is available, we perform any evaluations that have not yet been performed for this password.
	 *
	 * @param \WP_User $user
	 * @param string   $password
	 */
	protected function handle_plain_text_password_available( $user, $password ) {

		$config = wp_parse_args(
			get_user_meta( $user->ID, self::META_KEY, true ),
			[
				'evaluation_times' => [],
			] 
		);

		$last_updated = \WBCR\Titan\Tweaks\Password_Requirements_Base::password_last_changed( $user );

		$settings = [
			'strength' => [
				'role' => get_option( 'titan_strong_password_min_role', 'administrator' ),
			],
		];

		foreach ( \WBCR\Titan\Tweaks\Password_Requirements_Base::get_registered() as $code => $requirement ) {

			if ( ! $requirement['evaluate'] ) {
				continue;
			}

			if ( ! $requirement['evaluate_if_not_enabled'] && ! \WBCR\Titan\Tweaks\Password_Requirements_Base::is_requirement_enabled( $code ) ) {
				continue;
			}

			if ( isset( $config['evaluation_times'][ $code ] ) && $config['evaluation_times'][ $code ] >= $last_updated ) {
				continue;
			}

			$evaluation = call_user_func( $requirement['evaluate'], $password, $user );

			if ( is_wp_error( $evaluation ) ) {
				continue;
			}

			$config['evaluation_times'][ $code ] = time();
			update_user_meta( $user->ID, $requirement['meta'], $evaluation );

			if ( ! \WBCR\Titan\Tweaks\Password_Requirements_Base::is_requirement_enabled( $code ) ) {
				continue;
			}

			$validated = call_user_func( $requirement['validate'], $evaluation, $user, $settings[ $code ], [] );

			if ( true === $validated ) {
				continue;
			}

			\WBCR\Titan\Tweaks\Password_Requirements_Base::flag_password_change_required( $user, $code );
		}

		update_user_meta( $user->ID, self::META_KEY, $config );
	}

	/**
	 * Validate password.
	 *
	 * @param \WP_Error          $error
	 * @param \WP_User|\stdClass $user
	 * @param string             $new_password
	 * @param array              $args
	 */
	public function validate_password( $error, $user, $new_password, $args ) {
		$settings = [
			'strength' => [
				'role' => get_option( 'titan_strong_password_min_role', 'administrator' ),
			],
		];

		foreach ( \WBCR\Titan\Tweaks\Password_Requirements_Base::get_registered() as $code => $requirement ) {

			if ( ! $requirement['evaluate'] || ! \WBCR\Titan\Tweaks\Password_Requirements_Base::is_requirement_enabled( $code ) ) {
				continue;
			}

			$evaluation = call_user_func( $requirement['evaluate'], $new_password, $user );

			if ( is_wp_error( $evaluation ) ) {
				continue;
			}

			$validated = call_user_func( $requirement['validate'], $evaluation, $user, $settings[ $code ], $args );

			if ( true === $validated ) {
				continue;
			}

			// The default error message is a safeguard that should never occur.
			$message = $validated ? $validated : esc_html__( "The provided password does not meet this site's requirements.", 'anti-spam' );

			switch ( $args['context'] ) {
				case 'admin-user-create':
					$message .= ' ' . __( 'The user has not been created.', 'anti-spam' );
					break;
				case 'admin-profile-update':
					$message .= ' ' . __( 'The user changes have not been saved.', 'anti-spam' );
					break;
				case 'profile-update':
					$message .= ' ' . __( 'Your profile has not been updated.', 'anti-spam' );
					break;
				case 'reset-password':
					$message .= ' ' . __( 'The password has not been updated.', 'anti-spam' );
					break;
			}

			$error->add( 'pass', $message );
		}
	}

	/**
	 * When a user logs in, run any flag checks to see if a password change should be forced.
	 *
	 * @param string        $username
	 * @param \WP_User|null $user
	 */
	public function flag_check( $username, $user = null ) {

		if ( ! $user && is_user_logged_in() ) {
			$user = wp_get_current_user();
		}

		if ( ! $user instanceof \WP_User || ! $user->exists() ) {
			return;
		}

		foreach ( \WBCR\Titan\Tweaks\Password_Requirements_Base::get_registered() as $code => $requirement ) {
			if ( ! \WBCR\Titan\Tweaks\Password_Requirements_Base::is_requirement_enabled( $code ) ) {
				continue;
			}

			$settings = \WBCR\Titan\Tweaks\Password_Requirements_Base::get_requirement_settings( $code );

			if ( $requirement['flag_check'] && call_user_func( $requirement['flag_check'], $user, $settings ) ) {
				\WBCR\Titan\Tweaks\Password_Requirements_Base::flag_password_change_required( $user, $code );

				return;
			}
		}
	}

	/**
	 * Is a given requirement enabled.
	 *
	 * @param string $requirement
	 *
	 * @return bool
	 */
	protected function is_requirement_enabled( $requirement ) {

		$requirements = \WBCR\Titan\Tweaks\Password_Requirements_Base::get_registered();

		if ( ! isset( $requirements[ $requirement ] ) ) {
			return false;
		}

		// If the requirement does not have any settings, than it is always enabled.
		if ( null === $requirements[ $requirement ]['settings_config'] ) {
			return true;
		}

		if ( ! get_option( 'titan_strong_password' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * When a user's role changes, clear all the evaluation times as evaluat
	 *
	 * @param int $user_id
	 */
	public function handle_role_change( $user_id ) {

		$config = get_user_meta( $user_id, self::META_KEY, true );

		if ( ! $config || ! is_array( $config ) ) {
			return;
		}

		$config['evaluation_times'] = [];

		update_user_meta( $user_id, self::META_KEY, $config );
	}

	/**
	 * Register the password change interstitial.
	 *
	 * @param \WBCR\Titan\Tweaks\Login_Interstitial $lib
	 */
	public function register_interstitial( $lib ) {
		$lib->register(
			'update-password',
			[ $this, 'render_interstitial' ],
			[
				'show_to_user' => [ '\WBCR\Titan\Tweaks\Password_Requirements_Base', 'password_change_required' ],
				'info_message' => [
					'\WBCR\Titan\Tweaks\Password_Requirements_Base',
					'get_message_for_password_change_reason',
				],
				'submit'       => [ $this, 'submit' ],
			] 
		);
	}

	/**
	 * Render the interstitial.
	 *
	 * @param \WP_User $user
	 */
	public function render_interstitial( $user ) {
		wp_enqueue_script( 'user-profile' );

		do_action( 'titan_password_requirements_change_form', $user );
		?>

		<div class="user-pass1-wrap">
			<p><label for="pass1"><?php _e( 'New Password', 'anti-spam' ); ?></label></p>
		</div>

		<div class="wp-pwd">
				<span class="password-input-wrapper">
					<input type="password" data-reveal="1"
							data-pw="<?php echo esc_attr( wp_generate_password( 16 ) ); ?>" name="pass1" id="pass1"
							class="input" size="20" value="" autocomplete="off" aria-describedby="pass-strength-result"/>
				</span>
			<div id="pass-strength-result" class="hide-if-no-js"
				aria-live="polite"><?php _e( 'Strength indicator', 'anti-spam' ); ?></div>
			<div class="pw-weak">
				<label>
					<input type="checkbox" name="pw_weak" class="pw-checkbox"/>
					<?php _e( 'Confirm use of weak password', 'anti-spam' ); ?>
				</label>
			</div>
		</div>

		<p class="user-pass2-wrap">
			<label for="pass2"><?php _e( 'Confirm new password', 'anti-spam' ); ?></label><br/>
			<input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off"/>
		</p>

		<p class="description indicator-hint"><?php echo wp_get_password_hint(); ?></p>
		<br class="clear"/>

		<p class="submit">
			<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large"
					value="<?php esc_attr_e( 'Update Password', 'anti-spam' ); ?>"/>
		</p>

		<?php
	}

	/**
	 * Handle the request to update the user's password.
	 *
	 * @param \WP_User $user
	 * @param array    $data POSTed data.
	 *
	 * @return \WP_Error|null
	 */
	public function submit( $user, $data ) {

		if ( empty( $data['pass1'] ) ) {
			return new \WP_Error( 'titan-password-requirements-empty-password', __( 'Please enter your new password.', 'anti-spam' ) );
		}

		$error = \WBCR\Titan\Tweaks\Password_Requirements_Base::validate_password(
			$user,
			$data['pass1'],
			[
				'context' => 'interstitial',
			] 
		);

		if ( $error->get_error_message() ) {
			return $error;
		}

		$error = wp_update_user(
			[
				'ID'        => $user->ID,
				'user_pass' => $data['pass1'],
			] 
		);

		if ( is_wp_error( $error ) ) {
			return $error;
		}

		return null;
	}
}
