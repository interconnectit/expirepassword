<?php
/*
* Oncer functions - a oncer is a piece of data that is deleted from the database as soon as it is retrieved
*/

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

/*
* Timed Oncers - a timed oncer is a oncer that only exists until a set time, and then it is removed
*/

if( !function_exists( 'shrkey_has_usermeta_timed_oncer') ) {
	function shrkey_has_usermeta_timed_oncer( $user_id, $meta ) {

		$value = get_user_meta( $user_id, $meta, true );
		if(!empty($value)) {
			return true;
		} else {
			return false;
		}

	}
}

if( !function_exists( 'shrkey_get_usermeta_timed_oncer') ) {
	function shrkey_get_usermeta_timed_oncer( $user_id, $meta ) {

		$value = get_user_meta( $user_id, $meta, true );
		if(!empty($value)) {
			// 1. remove it as we only want it readable once
			delete_user_meta( $user_id, $meta );
			// 2. Split the oncer into it's parts
			$storage = explode( '##', $value );
			// Array map the arrays to get rid of rogue spaces / characters
			if( is_array( $storage) ) {
				$storage = array_map( 'trim', $storage );
			}
			// 3. Check it has the correct number of parts
			if( count($storage) == 3 ) {
				// 4. Rebuild the hash
				$newhash = md5( 'SHRKEY' . $storage[0] . $storage[1] );
				// 5. Check the hash is correct and it hasn't expired
				if( $newhash == $storage[2] && time() <= $storage[1] ) {
					// 6. return it
					return $storage[0];
				} else {
					return '';
				}
			} else {
				return '';
			}
		}

		// Our catch all drop out return empty string return :)
		return '';

	}
}

if( !function_exists( 'shrkey_set_usermeta_timed_oncer') ) {
	function shrkey_set_usermeta_timed_oncer( $user_id, $meta, $value, $expires = '+1 day' ) {

		$expirytime = strtotime( $expires );
		$storage = array( 	$value,
							$expirytime,
							md5( 'SHRKEY' . $value . $expirytime )
						);

		update_user_meta( $user_id, $meta, implode( '##', $storage ) );

	}
}

if( !function_exists( 'shrkey_delete_usermeta_timed_oncer') ) {
	function shrkey_delete_usermeta_timed_oncer( $user_id, $meta ) {

		delete_user_meta( $user_id, $meta );

	}
}

/*
* Options functions that get the information from the options or sitemeta table depending a setting
*/

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