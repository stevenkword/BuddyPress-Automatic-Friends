<?php
/**
 * Setup the Admin
 * @uses register_setting, add_settings_section, add_settings_field
 * @action admin_init
 * @return null
 */
function s8d_bpaf_admin_init() {

	/* Register Settings */
	register_setting( 's8d_bpaf_options', 's8d_bpaf_options', 's8d_bpaf_settings_validate_options' );

	/* Settings - General Section */
	add_settings_section (
		's8d_bpaf_settings_general',
		'General Options',
		's8d_bpaf_settings_text',
		's8d_bpaf_settings_page'
	);

	add_settings_field( 's8d_bpaf_user_ids', 'User ID(s)', 's8d_bpaf_settings_user_ids_input', 's8d_bpaf_settings_page', 's8d_bpaf_settings_general' );

}
add_action( 'admin_init', 's8d_bpaf_admin_init' );

/**
 * Setup Admin Menu Options & Settings
 * @uses is_super_admin, add_submenu_page
 * @action network_admin_menu, admin_menu
 * @return null
 */
function s8d_bpaf_admin_menu() {

	if ( !is_super_admin() )
		return false;
	add_submenu_page( 'bp-general-settings', __( 'BuddyPress Automatic Friends', 's8d-bpaf-settings'), __( 'Automatic Friends', 's8d-bpaf-settings' ), 'manage_options', 's8d-bpaf-settings', 's8d_bpaf_settings_page' );

}
/* @since v1.1 */
add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 's8d_bpaf_admin_menu', '11' );

/**
 * Display the friends automatically added in the admin options
 * @since v1.5
 * @return null
 */
function s8d_bpaf_display_auto_friend_users() {
	echo "<h3>Selected Users</h3>";

	$options = get_option( 's8d_bpaf_options' );
	$s8d_bpaf_user_ids = $options['s8d_bpaf_user_ids'];
	$friend_user_ids = explode(',', $s8d_bpaf_user_ids);

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

function s8d_bpaf_settings_page() {
	?>
	<div class="wrap">
		<?php //screen_icon(); ?>
		<h2>BuddyPress Automatic Friends</h2>
		<form method="post" action="<?php echo get_admin_url(); ?>/options.php">
		<?php settings_fields('s8d_bpaf_options');?>
		<?php do_settings_sections('s8d_bpaf_settings_page');?>
		<input name="Submit" type="submit" value="Save Changes" />
		</form>
		<?php s8d_bpaf_display_auto_friend_users();?>
	</div><!--/.wrap-->
	<?php
}

/**
 * Instructions
 * @return null
 */
function s8d_bpaf_settings_text() {
	echo "<p>Enter the user id(s) you would like to autofriend upon new user registration.</p>";
}

/**
 * Form Inputs
 * @uses get_option
 * @return null
 */
function s8d_bpaf_settings_user_ids_input() {
	$options = get_option( 's8d_bpaf_options' );
	$user_ids = $options['s8d_bpaf_user_ids'];

	echo "<p>";
	echo "<input class='regular-text' id='s8d_bpaf_user_ids' name='s8d_bpaf_options[s8d_bpaf_user_ids]' type='text' value='$user_ids' />";
	echo "<span class='description'>* comma separated</span>";
	echo "</p>";
}

/**
 * Form Validation
 * @uses is_array
 * @return array, false
 */
function s8d_bpaf_settings_validate_options( $input ) {
	$valid = array();
	$valid['s8d_bpaf_user_ids'] = preg_replace(
		'/[^0-9,]/',
		'',
		$input['s8d_bpaf_user_ids']
	);
	return is_array( $valid ) ? $valid : false;
}
?>