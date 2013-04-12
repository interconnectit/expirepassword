<?php
/*
Plugin Name: Expire Password
Version: 1.2
Plugin URI: https://github.com/shrkey/expirepassword
Description: Forces a user to change their password at their netx login, can be set to force a password change for a new user on first sign in
Author: Shrkey
Author URI: http://shrkey.com
Text_domain: shrkey

Copyright 2013 (email: team@shrkey.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Include the plugin config and functions files
require_once('includes/config.php');
require_once('includes/functions.php');

if( is_admin() ) {
	require_once('classes/admin.expirepassword.php');
}

// We need to add this part all the time
require_once('classes/public.expirepassword.php');