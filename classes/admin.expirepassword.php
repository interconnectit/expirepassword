<?php

if( !class_exists( 'expirepassword') ) {

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

		}

		function expirepasswordadmin() {
			$this->__construct();
		}

		function load_textdomain() {

			$langpath = "/" . basename(dirname(plugin_dir_path(__FILE__))) . '/languages/';

			load_plugin_textdomain( 'expirepassword', false, $langpath);

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
			}

		}

	}

}

$expirepasswordadmin = new expirepasswordadmin();