<?php
/*
Plugin Name: BuddyPress Automatic Friends
Plugin URI: http://www.stevenword.com/bp-automatic-friends/
Description: Automatically create and accept friendships for specified users upon new user registration. * Requires BuddyPress
Version: 2.0.0
Author: Steven K. Word
Author URI: http://www.stevenword.com
*/

/*
 Copyright 2009-2013  Steven K Word  (email : stevenword@gmail.com)

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
 Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Loader function only fires if BuddyPress exists
 * @uses is_admin, add_action
 * @action bp_loaded
 * @return null
 */
function s8d_bpaf_loader(){

	/* Load the admin */
	if ( is_admin() ){
		require_once( dirname(__FILE__) . '/includes/admin.php' );
	}

	/* A Hook into BP Core Activated User */
	//add_action( 'bp_core_activated_user', 's8d_bpaf_create_friendships' );

	/* Do this if the activated user process is bypassed */
	//add_action( 'bp_core_signup_user', 's8d_bpaf_create_friendships' );

	/* Do this the first time a new user logs in */
	add_action( 'wp', 's8d_bpaf_first_login' );

}
add_action( 'bp_loaded', 's8d_bpaf_loader' );

/**
 * New method for creating friendships at first login
 * Prevents conflict with plugins such as "Disable Activation" that bypass the activation process
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

	if( ! isset( $last_login ) || empty( $last_login ) )
		s8d_bpaf_create_friendships( $bp->loggedin_user->id );

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
	$options = get_option( 's8d_bpaf_options' );
	$s8d_bpaf_user_ids = $options[ 's8d_bpaf_user_ids' ];

	/* Check to see if the admin options are set*/
	if ( isset( $s8d_bpaf_user_ids ) && ! empty( $s8d_bpaf_user_ids ) ){

		$friend_user_ids = explode( ',', $s8d_bpaf_user_ids );
		foreach ( $friend_user_ids as $friend_user_id ){

			/* Request the friendship */
			if ( !friends_add_friend( $initiator_user_id, $friend_user_id, $force_accept = true ) ) {
				return false;
			}
			else {
				/* Get friends of $user_id */
				$friend_ids = BP_Friends_Friendship::get_friend_user_ids( $initiator_user_id );

				/* Loop through the initiator's friends and update their friend counts */
				foreach ( (array) $friend_ids as $friend_id ) {
					BP_Friends_Friendship::total_friend_count( $friend_id );
				}

				/* Update initiator friend counts */
				BP_Friends_Friendship::total_friend_count( $initiator_user_id );
			}

		}

	}
	return;
}