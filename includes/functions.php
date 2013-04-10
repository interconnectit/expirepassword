<?php

if( !function_exists( 'shrkey_has_usermeta_oncer') ) {
	function shrkey_has_usermeta_oncer( $user_id, $meta ) {

		$value = get_user_meta( $user_id, $meta, true );
		if(!empty($value)) {
			return true;
		} else {
			return false;
		}

	}
}

if( !function_exists( 'shrkey_get_usermeta_oncer') ) {
	function shrkey_get_usermeta_oncer( $user_id, $meta ) {

		$value = get_user_meta( $user_id, $meta, true );
		if(!empty($value)) {
			// remove it as we only want it readable once
			delete_user_meta( $user_id, $meta );
		}

		return $value;

	}
}

if( !function_exists( 'shrkey_set_usermeta_oncer') ) {
	function shrkey_set_usermeta_oncer( $user_id, $meta, $value ) {

		update_user_meta( $user_id, $meta, $value );

	}
}

if( !function_exists( 'shrkey_delete_usermeta_oncer') ) {
	function shrkey_delete_usermeta_oncer( $user_id, $meta ) {

		delete_user_meta( $user_id, $meta );

	}
}

if( !function_exists( 'shrkey_get_option') ) {
	function shrkey_get_option($key, $default = false) {

		$network = get_site_option( '_shrkey_limit_expirepasswords_to_networkadmin', 'no' );

		if( $network == 'yes' ) {
			return get_site_option( $key, $default );
		} else {
			return get_option( $key, $default );
		}

	}
}

if( !function_exists( 'shrkey_update_option') ) {
	function shrkey_update_option($key, $value) {

		$network = get_site_option( '_shrkey_limit_expirepasswords_to_networkadmin', 'no' );

		if( $network == 'yes' ) {
			return update_site_option( $key, $value );
		} else {
			return update_option( $key, $value );
		}

	}
}

if( !function_exists( 'shrkey_delete_option') ) {
	function shrkey_delete_option($key) {

		$network = get_site_option( '_shrkey_limit_expirepasswords_to_networkadmin', 'no' );

		if( $network == 'yes' ) {
			return delete_site_option( $key );
		} else {
			return delete_option( $key );
		}

	}
}