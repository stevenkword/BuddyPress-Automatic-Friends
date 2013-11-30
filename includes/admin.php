<?php
/**
 * @since 2.0.0
 */
class s8d_BuddyPress_Automatic_Friends_Admin {

	/* Post Type */
	public $post_type_slug = 'slide';

	public $plugins_url;

	/* Option Name */
	const SCRIPTS_VERSION    = '1';

	/* Define and register singleton */
	private static $instance = false;
	public static function instance() {
		if( ! self::$instance ) {
			self::$instance = new s8d_BuddyPress_Automatic_Friends_Admin;
		}
		return self::$instance;
	}

	/**
	 * Gene manipulation algorithms go here
	 */
	private function __clone() { }

	/**
	 * Register actions and filters
	 *
	 * @uses add_action()
	 * @return null
	 */
	public function __construct() {
		// Setup
		$this->plugins_url = plugins_url( '/bp-automatic-friends' );

		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ), 11 );
		add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', array( $this, 'action_admin_menu' ), 11 );

		// User options
		add_action( 'personal_options', array( $this, 'action_personal_options' )  );
		add_action( 'personal_options_update', array( $this, 'action_personal_options_update' ) );
		add_action( 'edit_user_profile_update', array( $this, 'action_personal_options_update' ) );

		// AJAX
		add_action( 'wp_ajax_bpaf_global_friend_suggest', array( $this, 'action_ajax_bpaf_global_friend_suggest' ) );
		//add_action( 'wp_ajax_update_slide', array( $this, 'action_wp_ajax_update_slide' ) );
	}

	/**
	 * Setup the Admin
	 * @uses register_setting, add_settings_section, add_settings_field
	 * @action admin_init
	 * @return null
	 */
	function action_admin_init() {

		/* Register Settings */
		register_setting( s8d_BuddyPress_Automatic_Friends_Core::OPTION, s8d_BuddyPress_Automatic_Friends_Core::OPTION, array( $this, 's8d_bpaf_settings_validate_options' ) );

		/* Settings - General Section */
		add_settings_section (
			's8d_bpaf_settings_general',
			'General Options',
			array( $this, 's8d_bpaf_settings_text' ),
			's8d_bpaf_settings_page'
		);

		add_settings_field (
			's8d_bpaf_user_ids',
			'User ID(s)',
			array( $this, 's8d_bpaf_settings_user_ids_input' ),
			's8d_bpaf_settings_page',
			's8d_bpaf_settings_general'
		);
	}


	/**
	 * Enqueue necessary scripts
	 *
	 * @uses wp_enqueue_script
	 * @return null
	 */
	public function action_admin_enqueue_scripts() {
		wp_enqueue_script( 'bpaf-admin', $this->plugins_url. '/js/admin.js', array( 'jquery', 'jquery-ui-autocomplete' ), self::SCRIPTS_VERSION, true );
	}

	/**
	 * Setup Admin Menu Options & Settings
	 * @uses is_super_admin, add_submenu_page
	 * @action network_admin_menu, admin_menu
	 * @return null
	 */
	function action_admin_menu() {

		if ( !is_super_admin() )
			return false;

		//add_submenu_page( 'bp-general-settings', __( 'BuddyPress Automatic Friends', 's8d-bpaf-settings'), __( 'Automatic Friends', 's8d-bpaf-settings' ), 'manage_options', 's8d-bpaf-settings', array( $this, 's8d_bpaf_settings_page' ) );
		add_users_page( __( 'BuddyPress Automatic Friends', 's8d-bpaf-settings'), __( 'Automatic Friends', 's8d-bpaf-settings' ), 'manage_options', 's8d-bpaf-settings', array( $this, 's8d_bpaf_settings_page' ) );
	}

	/**
	 * Display the friends automatically added in the admin options
	 * @since v1.5
	 * @return null
	 */
	function s8d_bpaf_display_auto_friend_users() {
		echo '<p>When new user accounts are registered, friendships between the new user and each of the following global friends will be created automatically.</p>';
		echo '<h3 style="float: left">Global Friends';
		echo '<input style="margin-left:20px"type="text" id="other" name="other" />';
		echo '<button id="add-global-friend" class="button" disabled="disabled">Add New</button>';
		echo '</h3>';

		$options = get_option( s8d_BuddyPress_Automatic_Friends_Core::OPTION );
		$s8d_bpaf_user_ids = $options['s8d_bpaf_user_ids'];
		$friend_user_ids = explode(',', $s8d_bpaf_user_ids);

		$friend_user_ids = $global_friend_user_ids = s8d_bpaf_get_global_friends();

		echo '<table class="wp-list-table widefat fixed users" cellspacing="0">';
		?>
		<thead>
			<tr>
			  <th scope="col" id="username" class="manage-column column-username sortable desc" style=""><a><span> Username</span></a></th>
			  <th scope="col" id="name" class="manage-column column-name sortable desc" style=""><a><span>Name</span></a></th>
			  <th scope="col" id="friends" class="manage-column column-friends sortable desc" style=""><a><span>Friends</span></a></th>
			</tr>
		</thead>
		<?php
		$i = 0;
		foreach($friend_user_ids as $friend_user_id){

			$friend_userdata = get_userdata( $friend_user_id );

			if( $friend_userdata ){
				/* Avatar */
				?>
				<tr id="user-<?php echo $friend_user_id;?>" <?php if( 0 == $i % 2 ) echo 'class="alternate"'; ?>>
				  <td class="username column-username">
					<?php echo get_avatar( $friend_user_id, 32 ); ?>
					<strong><?php echo $friend_userdata->user_login;?></strong>
					<br>
					<div class="row-actions">
					  <span class="edit">
						<a href="<?php echo get_edit_user_link( $friend_user_id ); ?>">
						  Edit
						</a>
					  </span>
					</div>
				  </td>

				  <td class="name column-name">
					<?php echo $friend_userdata->display_name;?>
				  </td>

				  <td class="friends column-friends">
					  <?php echo BP_Friends_Friendship::total_friend_count( $friend_user_id );?>
				  </td>
				</tr>
				<?php
			}//if
			$i++;
		}//foreach
		unset( $i );

		?>
		<tfoot>
			<tr>
			  <th scope="col" id="username" class="manage-column column-username sortable desc" style=""><a><span> Username</span></a></th>
			  <th scope="col" id="name" class="manage-column column-name sortable desc" style=""><a><span>Name</span></a></th>
			  <th scope="col" id="friends" class="manage-column column-friends sortable desc" style=""><a><span>Friends</span></a></th>
			</tr>
		</tfoot>
		</table>
		<?php
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
			<?php $this->s8d_bpaf_display_auto_friend_users();?>
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
		$options = get_option( s8d_BuddyPress_Automatic_Friends_Core::OPTION );
		$user_ids = $options['s8d_bpaf_user_ids'];

		echo "<p>";
		echo "<input class='regular-text' id='s8d_bpaf_user_ids' name='s8d_bpaf_options[s8d_bpaf_user_ids]' type='text' value='$user_ids' />";
		echo "<span class='description'>* comma separated</span>";
		echo "</p>";
	}

	function action_personal_options( $user ) {
		$meta_value = get_user_meta( $user->ID, s8d_BuddyPress_Automatic_Friends_Core::METAKEY, true );
		?>
			</table>
			<table class="form-table">
			<h3>BuddyPress Automatic Friends</h3>
			<tr>
				<th scope="row">Global Friend</th>
				<td>
					<label for="global-friend">
						<input type="checkbox" id="global-friend" name="global-friend" <?php checked( $meta_value ); ?> />
						<span> Automatically create friendships with all new users</span>
					</label>
				</td>
			</tr>
		<?php
	}

	function action_personal_options_update( $user_id ) {
		// @TODO: nonce check
		//if ( !current_user_can( 'edit_user', $user_id ) )
		//	return false;

		$meta_value = isset( $_POST[ 'global-friend' ] ) ? true : false;
		update_usermeta( $user_id, s8d_BuddyPress_Automatic_Friends_Core::METAKEY, $meta_value );

		// Update the friend counts
		BP_Friends_Friendship::total_friend_count( $user_id );
	}

	function action_ajax_bpaf_global_friend_suggest() {
		// Nonce check
		//if ( ! wp_verify_nonce( $_REQUEST[ 'nonce' ], $this->nonce_field ) ) {
		//	wp_die( $this->nonce_fail_message );
		//}

		$global_friend_user_ids = s8d_bpaf_get_global_friends();

		$users = get_users( array(
		//	'fields' => 'user_nicename'
			'exclude' => $global_friend_user_ids
		 ) );


		$user_ids = array();
		foreach( $users as $user ) {
			$user_ids[] = $user->data->user_login;
		}

		header('Content-Type: application/x-json');
		echo $json = json_encode( $user_ids );
		die;
	}

} // Class
s8d_BuddyPress_Automatic_Friends_Admin::instance();

/*
// Register the column
function price_column_register( $columns ) {
    $columns['price'] = __( 'Global Friend', 'my-plugin' );

    return $columns;
}
add_filter( 'manage_users_columns', 'price_column_register' );

// Display the column content
function price_column_display( $column_name, $post_id ) {
    if ( 'price' != $column_name )
        return;

    $price = get_post_meta($post_id, 'price', true);
    if ( !$price )
        $price = '<em>' . __( 'undefined', 'my-plugin' ) . '</em>';

    echo $price;
}
add_action( 'manage_users_custom_column', 'price_column_display', 10, 2 );

// Register the column as sortable
function price_column_register_sortable( $columns ) {
    $columns['price'] = 'price';

    return $columns;
}
add_filter( 'manage_edit-user_sortable_columns', 'price_column_register_sortable' );

function price_column_orderby( $vars ) {
    if ( isset( $vars['orderby'] ) && 'price' == $vars['orderby'] ) {
        $vars = array_merge( $vars, array(
            'meta_key' => 'price',
            'orderby' => 'meta_value_num'
        ) );
    }

    return $vars;
}
add_filter( 'request', 'price_column_orderby' );
*/