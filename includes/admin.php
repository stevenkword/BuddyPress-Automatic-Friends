<?php
/**
 * @since 2.0.0
 */
class s8d_BPAF_Admin {

	/* Post Type */
	public $post_type_slug = 'slide';

	public $plugins_url;

	/* Option Name */
	const OPTION          = 's8d_bpaf_options';
	const VERSION  = '2.0.0';

	/* Define and register singleton */
	private static $instance = false;
	public static function instance() {
		if( ! self::$instance ) {
			self::$instance = new s8d_BPAF_Admin;
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
		//add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ), 11 );
		add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', array( $this, 'action_admin_menu' ), 11 );
	}

	/**
	 * Setup the Admin
	 * @uses register_setting, add_settings_section, add_settings_field
	 * @action admin_init
	 * @return null
	 */
	function action_admin_init() {

		/* Register Settings */
		register_setting( self::OPTION, self::OPTION, array( $this, 's8d_bpaf_settings_validate_options' ) );

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
		wp_enqueue_script( 'bpaf-admin', $this->plugins_url. '/js/admin.js', '', self::VERSION, true );
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
		echo '<h3>Global Friends<a href="user-new.php" class="add-new-h2">Add New</a></h3>';

		$options = get_option( self::OPTION );
		$s8d_bpaf_user_ids = $options['s8d_bpaf_user_ids'];
		$friend_user_ids = explode(',', $s8d_bpaf_user_ids);

		echo '<table class="wp-list-table widefat fixed users" cellspacing="0">';
		?>
		<thead>
			<tr>
			  <th scope="col" id="username" class="manage-column column-username sortable desc" style=""><a><span> Username</span></a></th>
			  <th scope="col" id="name" class="manage-column column-name sortable desc" style=""><a><span>Name</span></a></th>
			  <th scope="col" id="email" class="manage-column column-email sortable desc" style=""><a><span>E-mail</span></a></th>
			  <th scope="col" id="role" class="manage-column column-role" style=""><a><span>Role</span></a></th>
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
				  <td class="email column-email">
					<a href="mailto:<?php echo $friend_userdata->user_email;?>" title="E-mail: <?php echo $friend_userdata->user_email;?>">
					  <?php echo $friend_userdata->user_email;?>
					</a>
				  </td>
				  <td class="role column-role">
					<?php
						global $wpdb;
						$capabilities = $friend_userdata->{$wpdb->prefix . 'capabilities'};

						if ( !isset( $wp_roles ) )
							$wp_roles = new WP_Roles();

						foreach ( $wp_roles->role_names as $role => $name ) :

							if ( array_key_exists( $role, $capabilities ) )
								echo $role;

						endforeach;
					?>
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
			  <th scope="col" id="email" class="manage-column column-email sortable desc" style=""><a><span>E-mail</span></a></th>
			  <th scope="col" id="role" class="manage-column column-role" style=""><a><span>Role</span></a></th>
			</tr>
		</tfoot>
		<?php

?>
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
		$options = get_option( self::OPTION );
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
} // Class
s8d_BPAF_Admin::instance();

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


// This will show below the color scheme and above username field
add_action( 'personal_options', 'extra_profile_fields' );

function extra_profile_fields( $user ) {
    // get the value of a single meta key
    $meta_value = get_user_meta( $user->ID, 'meta_key', true ); // $user contains WP_User object
    // do something with it.
    ?>
    	</table>
		<table class="form-table">
		<h3>BuddyPress Automatic Friends</h3>
		<tr>
			<th scope="row">Global Friend</th>
			<td>
				<label for="global_friend">
				<input type="checkbox" id="global_friend" name="global_friend"<?php checked( $meta_value ); ?> /> Automatically create friendships with all new users
				</label>
			</td>
		</tr>
    <?php
}