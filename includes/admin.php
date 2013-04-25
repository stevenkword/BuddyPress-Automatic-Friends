<?php
/**
 * Setup the Admin
 * @uses register_setting, add_settings_section, add_settings_field
 * @action admin_init
 * @return null
 */
function skw_bpaf_admin_init() {

	/* Register Settings */
	register_setting( 'skw_bpaf_options', 'skw_bpaf_options', 'skw_bpaf_settings_validate_options' );

	/* Settings - General Section */
	add_settings_section (
		'skw_bpaf_settings_general',
		'General Options',
		'skw_bpaf_settings_text',
		'skw_bpaf_settings_page'
	);

	add_settings_field( 'skw_bpaf_user_ids', 'User ID(s)', 'skw_bpaf_settings_user_ids_input', 'skw_bpaf_settings_page', 'skw_bpaf_settings_general' );

}
add_action( 'admin_init', 'skw_bpaf_admin_init' );

/**
 * Setup Admin Menu Options & Settings
 * @uses is_super_admin, add_submenu_page
 * @action network_admin_menu, admin_menu
 * @return null
 */
function skw_bpaf_admin_menu() {

	if ( !is_super_admin() )
		return false;
	add_submenu_page( 'bp-general-settings', __( 'BuddyPress Automatic Friends', 'skw-bpaf-settings'), __( 'Automatic Friends', 'skw-bpaf-settings' ), 'manage_options', 'skw-bpaf-settings', 'skw_bpaf_settings_page' );

}
/* @since v1.1 */
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'skw_bpaf_admin_menu', '11' );

/**
 * Display the friends automatically added in the admin options
 * @since v1.5 
 * @return null
 */
function skw_bpaf_display_auto_friend_users() {
	echo "<h3>Selected Users</h3>";

	$options = get_option( 'skw_bpaf_options' );
	$skw_bpaf_user_ids = $options['skw_bpaf_user_ids'];
	$friend_user_ids = explode(',', $skw_bpaf_user_ids);

	foreach($friend_user_ids as $friend_user_id){
		
		$friend_userdata = get_userdata( $friend_user_id );

		if( $friend_userdata ){
			/* Avatar */
			?>
			<div style='width:200px; clear:both; border:0px solid red; padding:4px;'>
				<div style='float:left; border:0px solid blue;margin-right:10px;'><?php echo get_avatar( $friend_user_id, 32 ); ?></div>

				<div style='float: left; border:0px solid cyan;'>
					<div><?php echo $friend_userdata->display_name;?></div>
				</div>
				<div style='clear:both; border:0px solid lime;'></div>
			</div>
			<?php
		}//if
	}//foreach
}

/**
 * Settings Page
 * @uses get_admin_url, settings_fields, do_settings_sections
 * @return null
 */

function skw_bpaf_settings_page() {
	?>
	<div class="wrap">
		<?php //screen_icon(); ?>
		<h2>BuddyPress Automatic Friends</h2>
		<form method="post" action="<?php echo get_admin_url(); ?>/options.php">
		<?php settings_fields('skw_bpaf_options');?>
		<?php do_settings_sections('skw_bpaf_settings_page');?>
		<input name="Submit" type="submit" value="Save Changes" />
		</form>
		<?php skw_bpaf_display_auto_friend_users();?>
	</div><!--/.wrap-->
	<?php
}

/**
 * Instructions
 * @return null
 */
function skw_bpaf_settings_text() {
	echo "<p>Enter the user id(s) you would like to autofriend upon new user registration.</p>";
}

/** 
 * Form Inputs 
 * @uses get_option
 * @return null
 */
function skw_bpaf_settings_user_ids_input() {
	$options = get_option( 'skw_bpaf_options' );
	$user_ids = $options['skw_bpaf_user_ids'];
	
	echo "<p>";
	echo "<input class='regular-text' id='skw_bpaf_user_ids' name='skw_bpaf_options[skw_bpaf_user_ids]' type='text' value='$user_ids' />";
	echo "<span class='description'>* comma separated</span>";
	echo "</p>";
}

/**
 * Form Validation
 * @uses is_array
 * @return array, false
 */
function skw_bpaf_settings_validate_options( $input ) {
	$valid = array();
	$valid['skw_bpaf_user_ids'] = preg_replace(
		'/[^0-9,]/',
		'',
		$input['skw_bpaf_user_ids']
	);	
	return is_array( $valid ) ? $valid : false;
}
?>