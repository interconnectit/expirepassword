<?php

if( !class_exists( 'expirepasswordpublic') ) {

	class expirepasswordpublic {

		function __construct() {

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

		function check_password_reset_key($key, $login) {
			global $wpdb;

			$key = preg_replace('/[^a-z0-9]/i', '', $key);

			if ( empty( $key ) || !is_string( $key ) )
				return new WP_Error('invalid_key', __('Invalid key'));

			if ( empty($login) || !is_string($login) )
				return new WP_Error('invalid_key', __('Invalid key'));

			$user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE user_activation_key = %s AND user_login = %s", $key, $login));

			if ( empty( $user ) )
				return new WP_Error('invalid_key', __('Invalid key'));

			return $user;
		}

		function process_expired_password() {

		}

		function show_reset_password_form( $user, $oncerkey, $redirectto = false ) {

			// We are going to save our key to a oncer for later checking
			shrkey_set_usermeta_oncer( $user->ID, '_shrkey_password_expired_key', $oncerkey );

			login_header( __('Expired Password', 'expirepassword'), '<p class="message reset-pass">' . __('Your password has <strong>expired</strong>. Enter a new password below.', 'expirepassword') . '</p>' );

			?>
			<form name="expiredpasswordform" id="expiredpasswordform" method="post" action="<?php echo esc_url( site_url( 'wp-login.php?action=expiredpassword', 'login_post' ) ); ?>">
				<input type="hidden" name="user_login" id="user_login" value="<?php echo esc_attr( $user->user_login ); ?>" autocomplete="off" />
				<input type="hidden" name="key" id="key" value="<?php echo esc_attr( $oncerkey ); ?>" autocomplete="off" />

				<p>
					<label for="pass1"><?php _e('New password') ?><br />
					<input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" /></label>
				</p>
				<p>
					<label for="pass2"><?php _e('Confirm new password') ?><br />
					<input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" /></label>
				</p>

				<div id="pass-strength-result" class="hide-if-no-js"><?php _e('Strength indicator'); ?></div>
				<p class="description indicator-hint"><?php _e('Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).'); ?></p>

				<br class="clear" />
				<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Reset Password'); ?>" /></p>
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
					$this->show_reset_password_form( $authenticated, wp_generate_password(20, false), (isset($_POST['redirect_to'])) ? $_POST['redirect_to'] : false );

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