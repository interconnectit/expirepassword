<?php

if( !class_exists( 'expirepasswordadmin') ) {

	class expirepasswordadmin {

		function __construct() {

			add_action( 'plugins_loaded', array(&$this, 'load_textdomain'));

			// Add the row actions
			add_filter( 'user_row_actions', array( &$this, 'add_user_action' ), 99, 2 );
			add_filter( 'ms_user_row_actions', array( &$this, 'add_user_action' ), 99, 2 );

			// Process any action clicks
			add_action( 'load-users.php', array( &$this, 'process_user_queue_action' ) );

			// Output a notice confirming operation
			add_action( 'admin_notices', array( &$this, 'output_admin_notices' ) );
			add_action( 'network_admin_notices', array( &$this, 'output_admin_notices' ) );

			// Add admin settings interface
			if( is_multisite() && is_network_admin() ) {
				add_action('network_admin_menu', array(&$this, 'add_admin_menu'));
			} else {
				add_action('admin_menu', array(&$this, 'add_admin_menu'));
			}

		}

		function expirepasswordadmin() {
			$this->__construct();
		}

		function load_textdomain() {

			$langpath = "/" . basename(dirname(plugin_dir_path(__FILE__))) . '/languages/';

			load_plugin_textdomain( 'expirepassword', false, $langpath);

		}

		function add_admin_menu() {

			if( !is_network_admin() && get_site_option( '_shrkey_limit_expirepasswords_to_networkadmin', 'no' ) == 'no' ) {
				$hook = add_options_page( __('Expire Password','expirepassword'), __('Expire Password','expirepassword'), 'manage_options', 'expirepassword', array( &$this, 'show_options_page' ) );
			} else {
				$hook = add_submenu_page( 'settings.php', __('Expire Password','expirepassword'), __('Expire Password','expirepassword'), 'manage_network_options', 'expirepassword', array( &$this, 'show_options_page' ) );
			}

			add_action( 'load-' . $hook, array( &$this, 'update_options_page' ) );

		}

		function update_options_page() {

			if( isset( $_POST['action'] ) ) {

				switch( $_POST['action'] ) {
					case 'updateexpirepassword':	check_admin_referer( 'update-expirespasswordsettings' );
													if( is_network_admin() ) {
														if( in_array( $_POST['expirepassword_limittonetworkadmin'], array( 'yes', 'no' ) ) ) {
															update_site_option( '_shrkey_limit_expirepasswords_to_networkadmin', $_POST['expirepassword_limittonetworkadmin'] );
														}
													}

													if( in_array( $_POST['expirepassword_expireimmediately'], array( 'yes', 'no' ) ) ) {
														shrkey_update_option( '_shrkey_expirepassword_expireimmediately', $_POST['expirepassword_expireimmediately'] );
													}

													if( (int) $_POST['expirepassword_expirationperiod'] == $_POST['expirepassword_expirationperiod'] ) {
														shrkey_update_option( '_shrkey_expirepassword_expirationperiod', $_POST['expirepassword_expirationperiod'] );
													}

													if( in_array( $_POST['expirepassword_autoauthenticate'], array( 'yes', 'no' ) ) ) {
														shrkey_update_option( '_shrkey_expirepassword_autoauthenticate', $_POST['expirepassword_autoauthenticate'] );
													}

													wp_safe_redirect( add_query_arg( 'msg', 1, wp_get_referer() ) );
													break;
				}

			}

		}

		function show_options_page() {

			$messages = array();
			$messages[1] = __('Settings updated.', 'expirepassword');
			$messages[2] = __('Settings could not be updated.', 'expirepassword');

			?>
			<div class="wrap">
			<div class="icon32" id="icon-options-general"><br></div>
			<h2><?php _e( 'Expire Password Settings', 'expirepassword' ); ?></h2>

			<?php
			if ( isset($_GET['msg']) ) {
				echo '<div id="message" class="updated fade"><p>' . $messages[ (int) $_GET['msg'] ] . '</p></div>';
				$_SERVER['REQUEST_URI'] = remove_query_arg(array('msg'), $_SERVER['REQUEST_URI']);
			}
			?>

			<form action="?page=<?php echo esc_attr($_GET['page']); ?>" method="post">
				<input type='hidden' name='action' value='updateexpirepassword' />
				<?php
				wp_original_referer_field( true, 'previous' );
				wp_nonce_field( 'update-expirespasswordsettings' );

				if( is_network_admin() ) {
					// Add in network admin specific options here
					?>
					<h3><?php _e('Restrict Settings Access','expirepassword'); ?></h3>
					<table class="form-table">
						<tbody>
							<tr valign="top">
								<th scope="row">
									<label for="expirepassword_limittonetworkadmin"><?php _e( 'Limit Settings to Network Admin','expirepassword' ); ?></label>
								</th>
								<td>
									<?php $limittonetworkadmin = get_site_option( '_shrkey_limit_expirepasswords_to_networkadmin', 'no' ); ?>
									<select name='expirepassword_limittonetworkadmin'>
										<option value='no' <?php selected( 'no', $limittonetworkadmin ); ?>><?php _e( 'No, thanks', 'expirepassword' ); ?></option>
										<option value='yes' <?php selected( 'yes', $limittonetworkadmin ); ?>><?php _e( 'Yes, please', 'expirepassword' ); ?></option>
									</select>
									<br>
									<?php _e( 'Set this to <strong>Yes, please</strong> if you do not want individual site admin pages.', 'expirepassword' ); ?>
								</td>
							</tr>
						</tbody>
					</table>
					<?php
				}
				?>
					<h3><?php _e('Registration Settings','expirepassword'); ?></h3>
					<p><?php _e('You can use this option to force a new user to change their password from the system generated one when they first sign in.','expirepassword'); ?></p>
					<table class="form-table">
						<tbody>
							<tr valign="top">
								<th scope="row">
									<label for="expirepassword_expireimmediately"><?php _e( 'Force change on first sign in','expirepassword' ); ?></label>
								</th>
								<td>
									<?php $expireimmediately = shrkey_get_option( '_shrkey_expirepassword_expireimmediately', 'no' ); ?>
									<select name='expirepassword_expireimmediately'>
										<option value='no' <?php selected( 'no', $expireimmediately ); ?>><?php _e( 'No, thanks', 'expirepassword' ); ?></option>
										<option value='yes' <?php selected( 'yes', $expireimmediately ); ?>><?php _e( 'Yes, please', 'expirepassword' ); ?></option>
									</select>
								</td>
							</tr>
						</tbody>
					</table>

					<h3><?php _e('Automatic Expiration Settings','expirepassword'); ?></h3>
					<p><?php _e('You can set passwords to automatically expire after a set period of time. The expiration timer for a user will start the next time the user logs in to the site.','expirepassword'); ?></p>
					<table class="form-table">
						<tbody>
							<tr valign="top">
								<th scope="row">
									<label for="expirepassword_expirationperiod"><?php _e( 'Expiration period','expirepassword' ); ?></label>
								</th>
								<td>
									<?php
										$expirationperiod = shrkey_get_option( '_shrkey_expirepassword_expirationperiod', 0 );
									?>
									<select name='expirepassword_expirationperiod'>
										<?php
										for( $n = 0; $n <= (int) EXPIREPASSWORD_MAXIMUM_PERIOD; $n++ ) {
											switch( $n ) {
												case 0:		?>
															<option value='<?php echo $n; ?>' <?php selected( $n, $expirationperiod ); ?>><?php _e( 'No automatic expiration', 'expirepassword' ); ?></option>
															<?php
															break;

												case 1:		?>
															<option value='<?php echo $n; ?>' <?php selected( $n, $expirationperiod ); ?>><?php echo sprintf( __( '%d day', 'expirepassword' ), $n ); ?></option>
															<?php
															break;

												default:	?>
															<option value='<?php echo $n; ?>' <?php selected( $n, $expirationperiod ); ?>><?php echo sprintf( __( '%d days', 'expirepassword' ), $n ); ?></option>
															<?php
															break;
											}
										}
										?>
									</select>
								</td>
							</tr>
						</tbody>
					</table>

					<h3><?php _e('Post Password Change Settings','expirepassword'); ?></h3>
					<p><?php _e('Once a password has been changed, you can either force the user to login again with the new details, or complete the authentication process and log them in automatically.','expirepassword'); ?></p>
					<table class="form-table">
						<tbody>
							<tr valign="top">
								<th scope="row">
									<label for="expirepassword_autoauthenticate"><?php _e( 'Force Re-Login','expirepassword' ); ?></label>
								</th>
								<td>
									<?php $autoauthenticate = shrkey_get_option( '_shrkey_expirepassword_autoauthenticate', 'no' ); ?>
									<select name='expirepassword_autoauthenticate'>
										<option value='yes' <?php selected( 'yes', $expireimmediately ); ?>><?php _e( 'No, let the user carry on to their destination', 'expirepassword' ); ?></option>
										<option value='no' <?php selected( 'no', $expireimmediately ); ?>><?php _e( 'Yes, send the user to the login page', 'expirepassword' ); ?></option>
									</select>
								</td>
							</tr>
						</tbody>
					</table>

				<p class="submit">
					<input type="submit" value="<?php _e( 'Save Changes', 'expirepassword' ); ?>" class="button button-primary" id="submit" name="submit">
				</p>

			</form>

			</div>
			<?php
		}

		function add_user_action( $actions, $user_object ) {

			if( !shrkey_has_usermeta_oncer( $user_object->ID, '_shrkey_password_expired' ) ) {
				$actions['userexpirepassword'] = "<a class='userexpirepassword' href='" . wp_nonce_url( "users.php?action=userexpirepassword&amp;user=" . $user_object->ID, 'userexpirepassword' ) . "' title='" . __('Expire users password', 'expirepassword') . "'>" . __( 'Expire Password', 'expirepassword' ) . "</a>";
			}

			return $actions;
		}

		function current_action() {

			if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
				return $_REQUEST['action'];

			if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
				return $_REQUEST['action2'];

			return false;

		}

		function process_user_queue_action() {

			$action = $this->current_action();

			if( $action ) {

				switch( $action ) {

					case 'userexpirepassword':				check_admin_referer( 'userexpirepassword' );
															if( isset($_GET['user']) && is_numeric($_GET['user']) ) {
																shrkey_set_usermeta_oncer( (int) $_GET['user'], '_shrkey_password_expired', time() );
																wp_safe_redirect( add_query_arg( 'passwordexpirationmsg', 1, wp_get_referer() ) );
															} else {
																wp_safe_redirect( add_query_arg( 'passwordexpirationmsg', 2, wp_get_referer() ) );
															}
															break;

					case 'bulkuserexpirepassword':			if( is_multisite() && is_network_admin() ) {
																check_admin_referer( 'bulk-users-network' );
																if( isset($_POST['allusers']) ) {
																	foreach( $_POST['allusers'] as $user ) {
																		shrkey_set_usermeta_oncer( (int) $user, '_shrkey_password_expired', time() );
																	}
																}
															} else {
																check_admin_referer( 'bulk-users' );

																if( isset($_GET['users']) ) {
																	foreach( $_GET['users'] as $user ) {
																		shrkey_set_usermeta_oncer( (int) $user, '_shrkey_password_expired', time() );
																	}
																}
															}
															wp_safe_redirect( add_query_arg( 'passwordexpirationmsg', 3, wp_get_referer() ) );
															exit;
															break;

				}

			}

			// This is to attempt to add in some bulk operations - bit of a hack, but no hook or filter to add in our own at the moment
			add_action( 'all_admin_notices',  array( &$this, 'start_object_to_modify_bulk'), 99 );

		}

		function add_modify_bulk( $content ) {

			$ouroption = "<option value='bulkuserexpirepassword'>" . __( 'Expire Password', 'expirepassword' ) . "</option>\n";

			if( is_multisite() && is_network_admin() ) {
				$content = preg_replace( "/<option value='notspam'>" . __( 'Not Spam', 'user' ) . "<\/option>/", "<option value='notspam'>" . __( 'Not Spam', 'user' ) . "</option>\n" . $ouroption, $content );
			} else {
				if( is_multisite() ) {
					$content = preg_replace( "/<option value='remove'>" . __( 'Remove' ) . "<\/option>/", "<option value='remove'>" . __( 'Remove' ) . "</option>\n" . $ouroption, $content );
				} else {
					$content = preg_replace( "/<option value='remove'>" . __( 'Delete' ) . "<\/option>/", "<option value='remove'>" . __( 'Delete' ) . "</option>\n" . $ouroption, $content );
				}
			}


			return $content;

		}

		function start_object_to_modify_bulk() {

			// Start the object cache
			ob_start( array( &$this, 'add_modify_bulk' ) );

		}

		function output_admin_notices() {

			if(isset( $_GET['passwordexpirationmsg'] )) {
				switch( (int) $_GET['passwordexpirationmsg'] ) {

					case 1:		echo '<div id="message" class="updated fade"><p>' . __('Users password has been expired.', 'expirepassword') . '</p></div>';
								break;

					case 2:		echo '<div id="message" class="error"><p>' . __('Users password could not be expired.', 'expirepassword') . '</p></div>';
								break;

					case 3:		echo '<div id="message" class="updated fade"><p>' . __('Users password has been expired.', 'expirepassword') . '</p></div>';
								break;

					case 4:		echo '<div id="message" class="error"><p>' . __('Users password could not be expired.', 'expirepassword') . '</p></div>';
								break;

				}

				$_SERVER['REQUEST_URI'] = remove_query_arg( array('passwordexpirationmsg'), $_SERVER['REQUEST_URI'] );
			}

		}

	}

}

$expirepasswordadmin = new expirepasswordadmin();