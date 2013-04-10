<?php

if( !class_exists( 'expirepasswordpublic') ) {

	class expirepasswordpublic {

		var $db;

		function __construct() {

			global $wpdb;

			$this->db =& $wpdb;

			// Load the translation file
			add_action( 'plugins_loaded', array(&$this, 'load_textdomain'));

			// We need to override the authentication method to check for expired passwords
			add_filter( 'authenticate', array( &$this, 'override_authentication' ), 5, 3);

			// The hook to take over the expired password processing
			add_action( 'login_form_expiredpassword', array( &$this, 'process_expired_password' ) );

		}

		function expirepasswordpublic() {
			$this->__construct();
		}

		function load_textdomain() {

			$langpath = "/" . basename(dirname(plugin_dir_path(__FILE__))) . '/languages/';

			load_plugin_textdomain( 'expirepassword', false, $langpath);

		}

		function reset_expired_password( $user, $new_password ) {

			// Allow other actions to be fired - mirroring the standard reset password functionality
			do_action( 'expired_password_reset', $user, $new_password );

			// Reset the password for the user
			wp_set_password( $new_password, $user->ID );

		}

		function get_users_password_hash( $user_id ) {

			$sql = $this->db->prepare( "SELECT user_pass FROM {$this->db->users} WHERE ID = %d", $user_id );

			return $this->db->get_var( $sql );

		}

		function process_expired_password() {

			// Process the expired password

			// Create an errors object for us to use
			$errors = new WP_Error();

			$user_name = sanitize_user( $_POST['user_login'] );

			// 1. Check the user exists
			if ( $user = get_user_by('login', $user_name) ) {
				// User exists - move forward

				// 2. Check the passwords have been entered and that they match
				if ( isset($_POST['pass1']) && $_POST['pass1'] != $_POST['pass2'] ) {
					$errors->add( 'password_reset_mismatch', __( 'The passwords do not match.', 'expirepassword' ) );
				} else {
					// 3. Check the password isn't the same as the last one
					$existingpassword = $this->get_users_password_hash( $user->ID );
					if( wp_check_password( $_POST['pass1'], $existingpassword ) ) {
						// The password matches - we don't want them setting the same password as before...
						$errors->add( 'password_reset_sameh', __( 'Please choose a different password from your previous one.', 'expirepassword' ) );
					} else {
						// Get the stored key
						$thekey = shrkey_get_usermeta_oncer( $user->ID, '_shrkey_password_expired_key' );
						// Get and parse the passed key
						$passedkey = preg_replace('/[^a-z0-9]/i', '', $_POST['key']);

						// 4. Check the key matches
						if( !empty( $thekey) && !empty($passedkey) && $thekey == $passedkey ) {
							// The key is valid as well - so we need to reset these passwords
							$this->reset_expired_password( $user, $_POST['pass1'] );
							// Remove the expired key setting
							shrkey_delete_usermeta_oncer( $user->ID, '_shrkey_password_expired' );

							// Send the user back to the login
							login_header( __( 'Password Reset' ), '<p class="message reset-pass">' . __( 'Your password has been reset, please login again with your <strong>new</strong> password.', 'expirepassword' ) . ' <a href="' . esc_url( wp_login_url() ) . '">' . __( 'Log in', 'expirepassword' ) . '</a></p>' );
							login_footer();
							exit();

						} else {
							// The key either doesn't exist or doesn't match - possible security issue here, we want to return the user to the login page
							// So we also blank the user out to force a re-login
							unset( $user );
							// Add in our error message
							$errors->add( 'password_expired_nokey', __( 'Could not change password, please try again.', 'expirepassword' ) );
						}
					}
				}
			} else {
				// The key either doesn't exist or doesn't match
				$errors->add( 'password_expired_nouser', __( 'Could not change password, please try again.', 'expirepassword' ) );
			}

			// If we have errors then we need to display the form again
			if( $errors->get_error_code() ) {
				// If we don't have a user record create a fake one
				if( !isset($user) || is_wp_error( $user ) ) {
					$user = '';
				}
				// show the reset form again
				$this->show_reset_password_form( $user, wp_generate_password(35, false), (isset($_POST['redirect_to'])) ? $_POST['redirect_to'] : false, $errors );

			}

			exit();

		}

		function show_reset_password_form( $user, $oncerkey, $redirectto = false, $errors = false ) {

			if ( !is_a($user, 'WP_User') ) {
				// Ooops we don't have a user to use :( return to the login form as this shouldn't happen except in hack attempts
				wp_safe_redirect( wp_login_url() );
				exit();
			}

			// We are going to save our key to a oncer for later checking
			shrkey_set_usermeta_oncer( $user->ID, '_shrkey_password_expired_key', $oncerkey );

			login_header( __('Expired Password', 'expirepassword'), '<p class="message reset-pass">' . __('Your password has <strong>expired</strong>. Enter a new password below.', 'expirepassword') . '</p>', $errors );

			?>
			<form name="expiredpasswordform" id="expiredpasswordform" method="post" action="<?php echo esc_url( site_url( 'wp-login.php?action=expiredpassword', 'login_post' ) ); ?>">
				<input type="hidden" name="user_login" id="user_login" value="<?php echo esc_attr( $user->user_login ); ?>" autocomplete="off" />
				<input type="hidden" name="key" id="key" value="<?php echo esc_attr( $oncerkey ); ?>" autocomplete="off" />
				<input type="hidden" name="redirect_to" id="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" autocomplete="off" />

				<p>
					<label for="pass1"><?php _e('New password') ?><br />
					<input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" /></label>
				</p>
				<p>
					<label for="pass2"><?php _e('Confirm new password') ?><br />
					<input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" /></label>
				</p>

				<div id="pass-strength-result" class="hide-if-no-js"><?php _e('Strength indicator'); ?></div>
				<p class="description indicator-hint"><?php _e('Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).', 'expirepassword'); ?></p>

				<br class="clear" />
				<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Reset Password', 'expirepassword'); ?>" /></p>
			</form>
			<?php

			// Show the standard footer
			login_footer( 'pass1' );

		}

		function override_authentication( $user, $username, $password ) {

			// Mirror standard WP authentication
			if ( is_a($user, 'WP_User') ) {
				return $user;
			}

			if( !empty($username) ) {
				// We have a login attempt so we are going to take over the authentication here
				// 1. Check the user exists
				if ( $user = get_user_by('login', $username) ) {
					// 2. We have a user so check if they have an expired password.
					if( !shrkey_has_usermeta_oncer( $user->ID, '_shrkey_password_expired' ) ) {
						// No expired password setting for this user so fall through to original authentication
						return;
					}

					// 3. We now need to authentication this user ourselves before we can continue
					$authenticated = wp_authenticate_username_password( '', $username, $password );

					if( is_wp_error( $authenticated ) ) {
						// The credentials are not valid, so we'll return and fall through to the original function
						return;
					}

					// We are still here so remove the original authentication method as we no longer need it
					remove_action( 'authenticate', 'wp_authenticate_username_password', 20, 3 );

					// 4. Show the change password form as we want to force a password change at this point
					$this->show_reset_password_form( $authenticated, wp_generate_password(35, false), (isset($_POST['redirect_to'])) ? $_POST['redirect_to'] : false );

					// Exit because we don't want to continue processing or pass anything along the chain at this point
					exit();

				} else {
					// Invalid username - return and fall through to the original authentication function handle it
					return;
				}


			}

			return;

		}



	}

}

$expirepasswordpublic = new expirepasswordpublic();