<?php
/*
Plugin Name: Act As User
Plugin URI: http://wmpl.org
Description: Allows admin to act as other user.
Version: 0.0.2
Author: Joshua Vandercar
Author URI: http://ua.vandercar.net
*/

/*  Copyright 2013 Joshua Vandercar (joshua.vandercar@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**************************************
* INITIALIZE SETTINGS AND INCLUDES
**************************************/

define( 'AAU_VERSION', '0.0.2' );
define( 'AAU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once ABSPATH . WPINC . '/pluggable.php';
require_once ABSPATH . WPINC . '/link-template.php';
require_once ABSPATH . WPINC . '/capabilities.php';
wp_register_style( 'aau_styles', AAU_PLUGIN_URL . 'aau_styles.css' );

/**************************************
* SWITCH TO USER (RESET WP AUTH COOKIE)
**************************************/

get_currentuserinfo();

if ( $_GET['user'] && $_GET['user'] != $current_user->ID ) {
    wp_set_auth_cookie( $_GET['user'] );
    aau_redirect();
}

global $aauser;
$aauser = $current_user->ID;

/**************************************
* SET PRIMARY USER COOKIE ON LOGIN
**************************************/

function aau_set_primary_user_cookie() {
	$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	$string = '';
	for ($i = 0; $i < 33; $i++) {
		$string .= $characters[rand(0, strlen($characters) - 1)];
	}
	!isset( $_COOKIE['aau_primary_user'] ) ? setcookie( 'aau_primary_user', $string , time() + 172800, '/', COOKIE_DOMAIN, FALSE, TRUE ) : FALSE;
}
add_action( 'set_logged_in_cookie', 'aau_set_primary_user_cookie' ); 

/**************************************
* SET ADMIN AUTHORIZATION FOR PLUGIN USE
**************************************/

function aau_set_admin_auth() {
	if ( isset( $_COOKIE['aau_primary_user'] ) && current_user_can( 'delete_users' ) ) {
		add_option( 'aau_primary_user', $_COOKIE['aau_primary_user'] );
	}
	aau_select_user();
}
add_action( 'init', 'aau_set_admin_auth' );

/**************************************
* DISPLAY USER SWITCH FORM FOR AUTHORIZED ADMIN
**************************************/

function aau_select_user() {
	$_GET['action'] == 'logout' ? aau_unset_admin_auth() : FALSE; // CALL CLEARING FUNCTION ON LOGOUT
	if ( isset( $_COOKIE['aau_primary_user'] ) && $_COOKIE['aau_primary_user'] == get_option( 'aau_primary_user' ) && did_action( 'init' ) === 1 && $_POST['action'] != 'logout' ) { 
		global $aauser;
		aau_include_styles();
		?>
		<form action="#" method="get" id="aau" style="position: fixed; bottom: 0px; left: 0px; z-index: 999;">
			<?php wp_dropdown_users( array( 'selected' => $aauser ) ); ?>
			<input type="submit" name="submit" value="Act As User"/>
		</form>
	<?php }
}

/**************************************
* RELOAD CURRENT PAGE ON USER SWITCH
**************************************/

function aau_redirect() {
	wp_redirect( aau_curPageURL() );
	exit;
}

/**************************************
* CLEAR AAU COOKIE AND ADMIN AUTHORIZATION ON LOGOUT
**************************************/

function aau_unset_admin_auth() {
	setcookie( 'aau_primary_user', '', time() - 3600 );
	$_COOKIE['aau_primary_user'] == get_option( 'aau_primary_user' ) ? delete_option( 'aau_primary_user' ) : FALSE;
}

/**************************************
* GET CURRENT PAGE URL
**************************************/

function aau_curPageURL() {
 $pageURL = 'http';
 if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 return $pageURL;
}

function aau_include_styles() {
	wp_enqueue_style( 'aau_styles' );
}

?>
