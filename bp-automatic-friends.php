<?php
/*
Plugin Name: BuddyPress Automatic Friends
Plugin URI: http://www.stevenword.com/bp-automatic-friends/
Description: Automatically create and accept friendships for specified users upon new user registration. * Requires BuddyPress
Version: 2.0.0
Author: Steven K. Word
Author URI: http://stevenword.com/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=DWK9EXNAHLZ42
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Copyright 2013 Steven K. Word

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class s8d_BuddyPress_Automatic_Friends_Core {

	const REVISION = '20131214';
	const NONCE    = 's8d_bpaf_nonce';
	const METAKEY  = 's8d_bpaf_global_friend';
	const OPTION   = 's8d_bpaf_options';

	/* Define and register singleton */
	private static $instance = false;
	public static function instance() {
		if( ! self::$instance ) {
			self::$instance = new self;
			self::$instance->setup();
		}
		return self::$instance;
	}

	/**
	 * Constructor
     *
	 * @since 2.0.0
	 */
	private function __construct() { }

	/**
	 * Clone
     *
	 * @since 2.0.0
	 */
	private function __clone() { }

	/**
	 * Add actions and filters
	 *
	 * @uses add_action, add_filter
	 * @since 2.0.0
	 */
	function setup() {
		add_action( 'bp_loaded', array( $this, 'action_bp_loaded' ) );
	}


	/**
	 * Loader function only fires if BuddyPress exists.
	 *
	 * @uses is_admin, add_action
	 * @action bp_loaded
	 * @return null
	 */
	function action_bp_loaded(){

		/* Load the admin */
		if ( is_admin() ){
			require_once( dirname(__FILE__) . '/includes/admin.php' );
		}

		/* Do this the first time a new user logs in */
		add_action( 'wp', array( $this, 's8d_bpaf_first_login' ) );
	}

	/**
	 * New method for creating friendships at first login.
	 *
	 * Prevents conflict with plugins such as "Disable Activation" that bypass the activation process.
	 *
	 * Hook into the 'wp' action and check if the user is logged in
	 * and if get_user_meta( $bp->loggedin_user->id, 'last_activity' ) is false.
	 * http://buddypress.trac.wordpress.org/ticket/3003
	 */
	function s8d_bpaf_first_login(){

		if( ! is_user_logged_in() )
			return;

		global $bp;

		$last_login = get_user_meta( $bp->loggedin_user->id, 'last_activity', true );

	// This needs to be re-added after debugging
	//	if( ! isset( $last_login ) || empty( $last_login ) )
			s8d_bpaf_create_friendships( $bp->loggedin_user->id );

	}

}
s8d_BuddyPress_Automatic_Friends_Core::instance();

// @return array|bool
function s8d_bpaf_get_global_friends() {
	// The Query
	$user_query = new WP_User_Query( array(
		'meta_key' => s8d_BuddyPress_Automatic_Friends_Core::METAKEY,
		'meta_value' => true,
		'fields' => 'ID'
	) );

	if ( ! empty( $user_query->results ) ) {
		return $user_query->results;
	} else {
		return false;
	}
}

/**
 * Create friendships automatically
 * When a initiator user registers for the blog, create initiator friendship with the specified user(s) and autoaccept those friendhips.
 * @global bp
 * @param initiator_user_id
 * @uses get_userdata, get_option, explode, friends_add_friend, get_friend_user_ids, total_friend_count
 * @return null
 */
function s8d_bpaf_create_friendships( $initiator_user_id ) {

	global $bp;

	/* Get the user data for the initiatorly registered user. */
	$initiator_user_info = get_userdata( $initiator_user_id );

	/* Get the friend users id(s) */
	//$options = get_option( s8d_BuddyPress_Automatic_Friends_Core::OPTION );
	//$global_friend_user_ids = $options[ 's8d_bpaf_user_ids' ];

	$global_friend_user_ids = s8d_bpaf_get_global_friends();

	/* Check to see if the admin options are set*/
	if ( isset( $global_friend_user_ids ) && ! empty( $global_friend_user_ids ) ){

		// @legacy
		//$friend_user_ids = explode( ',', $global_friend_user_ids );

		$friend_user_ids = $global_friend_user_ids;

		foreach ( $friend_user_ids as $friend_user_id ){
			// If a friendship between these people already exists, we don't want to do this again
			if( $initiator_user_id != $friend_user_id && 'not_friends' == BP_Friends_Friendship::check_is_friend( $initiator_user_id, $friend_user_id ) ) {
				/* Request the friendship */
				friends_add_friend( $initiator_user_id, $friend_user_id, $force_accept = true );
				s8d_bpaf_update_friendship_counts( $initiator_user_id );
			}
		}

	}
	return;
}

/**
 * Destroy Friendships
 *
 * @global bp
 * @param initiator_user_id
 * @uses get_userdata, get_option, explode, friends_add_friend, get_friend_user_ids, total_friend_count
 * @return null
 */
function s8d_bpaf_destroy_friendships( $initiator_user_id ) {
	BP_Friends_Friendship::delete_all_for_user( $initiator_user_id );
	s8d_bpaf_update_friendship_counts( $initiator_user_id );
}

function s8d_bpaf_update_friendship_counts( $initiator_user_id ) {
	/* Get friends of $user_id */
	$friend_ids = BP_Friends_Friendship::get_friend_user_ids( $initiator_user_id );

	/* Loop through the initiator's friends and update their friend counts */
	foreach ( (array) $friend_ids as $friend_id ) {
		BP_Friends_Friendship::total_friend_count( $friend_id );
	}

	/* Update initiator friend counts */
	BP_Friends_Friendship::total_friend_count( $initiator_user_id );
}