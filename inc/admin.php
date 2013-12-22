<?php
/**
 * Admin Options
 *
 * All of the administrative functionality for BuddyPress Automatic Friends.
 *
 * @link http://wordpress.org/plugins/bp-automatic-friends/
 * @since 2.0.0
 *
 * @package BuddyPress Automatic Friends
 * @subpackage Admin
 */

/**
 * BuddPress Automatic Friends Admin
 */
class BPAF_Admin {

	public $plugins_url;

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
		global $pagenow;

		// Setup
		$this->plugins_url = plugins_url( '/bp-automatic-friends' );

		// Admin Menu
		add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', array( $this, 'action_admin_menu' ), 11 );

		// AJAX
		add_action( 'wp_ajax_bpaf_suggest_global_friend', array( $this, 'action_ajax_bpaf_suggest_global_friend' ) );
		add_action( 'wp_ajax_bpaf_add_global_friend', array( $this, 'action_ajax_bpaf_add_global_friend' ) );
		add_action( 'wp_ajax_bpaf_delete_global_friend', array( $this, 'action_ajax_bpaf_delete_global_friend' ) );

		// User options
		add_action( 'personal_options', array( $this, 'action_personal_options' )  );
		add_action( 'personal_options_update', array( $this, 'action_personal_options_update' ) );
		add_action( 'edit_user_profile_update', array( $this, 'action_personal_options_update' ) );

		/* We don't need any of these things in other places */
		if( 'users.php' != $pagenow || ! isset( $_REQUEST['page'] ) || 's8d-bpaf-settings' != $_REQUEST['page'] ) {
			return;
		}

		// Init
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ), 11 );

	}

	/**
	 * Setup the Admin.
	 *
	 * @uses register_setting, add_settings_section, add_settings_field
	 * @action admin_init
	 * @return null
	 */
	function action_admin_init() {

		/* Register Settings */
		register_setting( BPAF_Core::LEGACY_OPTION, BPAF_Core::LEGACY_OPTION, array( $this, 's8d_bpaf_settings_validate_options' ) );


	}


	/**
	 * Enqueue necessary scripts.
	 *
	 * @uses wp_enqueue_script
	 * @return null
	 */
	public function action_admin_enqueue_scripts() {
		wp_enqueue_script( 'bpaf-admin', $this->plugins_url. '/js/admin.js', array( 'jquery', 'jquery-ui-autocomplete' ), BPAF_Core::REVISION, true );

		wp_enqueue_style( 'bpaf-genericons', $this->plugins_url . '/fonts/genericons/genericons.css', '', BPAF_Core::REVISION );
		wp_enqueue_style( 'bpaf-admin', $this->plugins_url . '/css/admin.css', array( 'bpaf-genericons' ), BPAF_Core::REVISION );
	}

	/**
	 * Setup Admin Menu Options & Settings.
	 *
	 * @uses is_super_admin, add_submenu_page
	 * @action network_admin_menu, admin_menu
	 * @return null
	 */
	function action_admin_menu() {
		if ( ! is_super_admin() )
			return false;

		add_users_page( __( 'BuddyPress Automatic Friends', 's8d-bpaf-settings'), __( 'Automatic Friends', 's8d-bpaf-settings' ), 'manage_options', 's8d-bpaf-settings', array( $this, 'settings_page' ) );
	}

	/**
	 * Display the friends automatically added in the admin options.
	 *
	 * @since 1.5.0
	 * @return null
	 */
	function display_auto_friend_users() {
		?>
		<p><?php _e( 'When new user accounts are registered, friendships between the new user and each of the following global friends will be created automatically.', BPAF_Core::TEXT_DOMAIN );?></p>
		<h3 style="float: left; margin:1em 0;padding:0; line-height:2em;"><?php _e( 'Global Friends', BPAF_Core::TEXT_DOMAIN );?></h3>
		<div style="padding: 1em 0;">
			<?php $search_text = __('Search by Username', BPAF_Core::TEXT_DOMAIN );?>
			<input type="text" name="add-global-friend-field" id="add-global-friend-field" style="margin-left: 1em; color: #aaa;"value="<?php echo $search_text;?>" onfocus="if (this.value == '<?php echo $search_text;?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo $search_text;?>';}" size="40" maxlength="128">
			<button id="add-global-friend-button" class="button" disabled="disabled"><?php _e( 'Add User', BPAF_Core::TEXT_DOMAIN );?></button>
			<span class="spinner"></span>
		</div>
		<?php
		// Legacy Support
		$options = get_option( BPAF_Core::LEGACY_OPTION );
		if( isset( $options[ BPAF_Core::LEGACY_OPTION . '_ids'] ) ) {
			$s8d_bpaf_user_ids = $options[ BPAF_Core::LEGACY_OPTION . '_ids'];
			$friend_user_ids = explode(',', $s8d_bpaf_user_ids);
		}

		// Modern
		$friend_user_ids = $global_friend_user_ids = bpaf_get_global_friends();
		?>
		<form id="global-friends-form">
		<?php wp_nonce_field( BPAF_Core::NONCE, BPAF_Core::NONCE, false ); ?>
		<table class="wp-list-table widefat fixed users" cellspacing="0" style="clear:left;">
			<thead>
				<tr>
				  <th scope="col" id="username" class="manage-column column-username sortable desc" style=""><a><span><?php _e( 'Username', BPAF_Core::TEXT_DOMAIN );?></span></a></th>
				  <th scope="col" id="name" class="manage-column column-name sortable desc" style=""><a><span><?php _e( 'Name', BPAF_Core::TEXT_DOMAIN );?></span></a></th>
				  <th scope="col" id="friends" class="manage-column column-friends sortable desc" style=""><a><span><?php _e( 'Friends', BPAF_Core::TEXT_DOMAIN );?></span></a></th>
				</tr>
			</thead>
			<?php
			$i = 1;
			if( is_array( $friend_user_ids ) && 0 < count( $friend_user_ids ) ) {
				foreach( $friend_user_ids as $friend_user_id ){
					$friend_userdata = get_userdata( $friend_user_id );
					if( $friend_userdata ){
						// Add a row to the table
						$this->render_global_friend_table_row( $friend_user_id, $i );
					}//if
					$i++;
				}//foreach
				unset( $i );
			} else {
				echo '<tr class="bpaf-empty-table-row"><td colspan="3">No Global Friends found.</td></tr>';
			}
			?>
			<tfoot>
				<tr>
				  <th scope="col" id="username" class="manage-column column-username sortable desc" style=""><a><span><?php _e( 'Username', BPAF_Core::TEXT_DOMAIN );?></span></a></th>
				  <th scope="col" id="name" class="manage-column column-name sortable desc" style=""><a><span><?php _e( 'Name', BPAF_Core::TEXT_DOMAIN );?></span></a></th>
				  <th scope="col" id="friends" class="manage-column column-friends sortable desc" style=""><a><span><?php _e( 'Friends', BPAF_Core::TEXT_DOMAIN );?></span></a></th>
				</tr>
			</tfoot>
		</table>
		</form>
		<?php
	}

	/**
	 * Settings Page.
	 *
	 * @uses get_admin_url, settings_fields, do_settings_sections
	 * @return null
	 */
	function settings_page() {
		?>
		<div class="wrap">
			<?php //screen_icon(); ?>
			<h2><?php _e( 'BuddyPress Automatic Friends', BPAF_Core::TEXT_DOMAIN );?></h2>
			<div id="poststuff" class="metabox-holder has-right-sidebar">
				<div class="inner-sidebar" id="side-info-column">
					<div id="side-sortables" class="meta-box-sortables ui-sortable">
						<div id="bpaf_display_optin" class="postbox ">
							<h3 class="hndle"><span><?php _e( 'Help Improve BP Automatic Friends', BPAF_Core::TEXT_DOMAIN );?></span></h3>
							<div class="inside">
								<p><?php _e( 'We would really appreciate your input to help us continue to improve the product.', BPAF_Core::TEXT_DOMAIN );?></p>
								<p>
								<?php printf( __( 'Find us on %1$s or donate to the project using the button below.', BPAF_Core::TEXT_DOMAIN ), '<a href="https://github.com/stevenkword/BuddyPress-Automatic-Friends" target="_blank">GitHub</a>' ); ?>
								</p>
								<div style="width: 100%; text-align: center;">
									<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
										<input type="hidden" name="cmd" value="_s-xclick">
										<input type="hidden" name="hosted_button_id" value="DWK9EXNAHLZ42">
										<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
										<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
									</form>
								</div>
							</div>
						</div>
						<div id="bpaf_display_contact" class="postbox ">
							<h3 class="hndle"><span><?php _e( 'Contact BP Automatic Friends', BPAF_Core::TEXT_DOMAIN );?></span></h3>
							<div class="inside">
								<ul class="bpaf-contact-links">
									<li><a class="link-bpaf-forum" href="http://wordpress.org/support/plugin/bp-automatic-friends" target="_blank"><?php _e( 'Support Forums', BPAF_Core::TEXT_DOMAIN );?></a></li>
									<li><a class="link-bpaf-web" href="http://stevenword.com/plugins/bp-automatic-friends/" target="_blank"><?php _e( 'BP Automatic Friends on the Web', BPAF_Core::TEXT_DOMAIN );?></a></li>
									<li><a class="link-bpaf-github" href="https://github.com/stevenkword/BuddyPress-Automatic-Friends" target="_blank"><?php _e( 'GitHub Project', BPAF_Core::TEXT_DOMAIN );?></a></li>
									<li><a class="link-bpaf-review" href="http://wordpress.org/support/view/plugin-reviews/bp-automatic-friends" target="_blank"><?php _e( 'Review on WordPress.org', BPAF_Core::TEXT_DOMAIN );?></a></li>
								</ul>
							</div>
						</div>
					</div>
				</div>
				<div id="post-body-content">
					<?php $this->display_auto_friend_users();?>
				</div>
			</div>
		</div><!--/.wrap-->
		<?php
	}

	/**
	 * Personal Options.
	 *
	 * @return null
	 */
	function action_personal_options( $user ) {
		$meta_value = get_user_meta( $user->ID, BPAF_Core::METAKEY, true );
		?>
			</table>
			<table class="form-table">
			<h3><?php _e( 'BuddyPress Automatic Friends', BPAF_Core::TEXT_DOMAIN );?></h3>
			<tr>
				<th scope="row"><?php _e( 'Global Friend', BPAF_Core::TEXT_DOMAIN );?></th>
				<td>
					<label for="global-friend">
						<input type="checkbox" id="global-friend" name="global-friend" <?php checked( $meta_value ); ?> />
						<span> <?php _e( 'Automatically create friendships with all new users', BPAF_Core::TEXT_DOMAIN );?></span>
					</label>
				</td>
			</tr>
		<?php
	}

	/**
	 * Update personal options.
	 *
	 * @since 2.0.0
	 */
	function action_personal_options_update( $user_id ) {
		// @TODO: nonce check
		//if ( !current_user_can( 'edit_user', $user_id ) )
		//	return false;

		$meta_value = isset( $_REQUEST['global-friend'] ) ? true : false;
		update_usermeta( $user_id, BPAF_Core::METAKEY, $meta_value );

		// Update the friend counts
		BP_Friends_Friendship::total_friend_count( $user_id );
	}

	/**
	 * Admin Ajax for finding users.
	 *
	 * @since 2.0.0
	 */
	function action_ajax_bpaf_suggest_global_friend() {
		// Nonce check
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], BPAF_Core::NONCE ) ) {
			wp_die( BPAF_Core::NONCE_FAIL_MSG );
		}

		global $bp;
		$global_friend_user_ids = bpaf_get_global_friends();

		$users = get_users( array(
			//'fields' => 'user_nicename' // This is returning numeric, wtf?
			'exclude' => array_merge( $global_friend_user_ids, array( $bp->loggedin_user->id ) )
		 ) );

		$user_ids = array();
		foreach( $users as $user ) {
			$user_ids[] = $user->data->user_login;
		}

		header('Content-Type: application/x-json');
		echo $json = json_encode( $user_ids );
		die;
	}

	/**
	 * Admin Ajax for adding users.
	 *
	 * @since 2.0.0
	 */
	function action_ajax_bpaf_add_global_friend() {
		// Nonce check
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], BPAF_Core::NONCE ) ) {
			wp_die( BPAF_Core::NONCE_FAIL_MSG );
		}

		if( ! isset( $_REQUEST['username'] ) && empty( $_REQUEST['username'] ) ) {
		 	die;
		}

		// Add Global Friend status
		$user = get_user_by( 'login', $_REQUEST['username'] );
		if( isset( $user->data->ID ) ) {
			// Update the user and related friendships
			update_usermeta( $user->data->ID, BPAF_Core::METAKEY, true );

			// This is wrong MMMMMKay! This is looking for a newly registred user, not a global friend
			bpaf_create_friendships( $user->data->ID );

			// Add a new row to the table
			$this->render_global_friend_table_row( $user->data->ID );
		}
		die;
	}

	/**
	 * Render the global friends table.
	 *
	 * @since 2.0.0
	 */
	function render_global_friend_table_row( $friend_user_id, $i = '' ) {
		if( ! isset( $i ) || '' == $i ) {
			$i = count( bpaf_get_global_friends() );
			echo $i;
		}
		$friend_userdata = get_userdata( $friend_user_id );
		?>
		<tr <?php if( 0 == $i % 2 ) echo 'class="alternate"'; ?>>
		  <td class="username column-username">
		  	<input class="bpaf-user-id" id="bpaf-user-<?php echo $friend_user_id;?>" type="hidden" value="<?php echo $friend_user_id; ?>"></input>
			<?php echo get_avatar( $friend_user_id, 32 ); ?>
			<strong><?php echo $friend_userdata->user_login;?></strong>
			<br>
			<div class="row-actions">
				<span class="edit"><a href="<?php echo get_edit_user_link( $friend_user_id ); ?>" title="Edit this item"><?php _e( 'Edit', BPAF_Core::TEXT_DOMAIN );?></a> | </span>
				<span id="remove-<?php echo $friend_userdata->user_login;?>" class="trash"><a class="submitdelete" title="Move this item to the Trash" href="javascript:void(0);"><?php _e( 'Remove', BPAF_Core::TEXT_DOMAIN );?></a></span>
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
	}

	/**
	 * Admin Ajax for removing users.
	 *
	 * @since 2.0.0
	 */
	function action_ajax_bpaf_delete_global_friend() {
		// Nonce check
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], BPAF_Core::NONCE ) ) {
			wp_die( BPAF_Core::NONCE_FAIL_MSG );
		}

		if( ! isset( $_REQUEST['ID'] ) && empty( $_REQUEST['ID'] ) ) {
		 	die;
		}

		// Remove Global Friend status
		update_usermeta( $_REQUEST['ID'], BPAF_Core::METAKEY, false );
		bpaf_destroy_friendships( $_REQUEST['ID'] );

		// Return the number of friends remaning
		echo $global_friends_remaining = count( bpaf_get_global_friends() );
		die;
	}

} // Class
BPAF_Admin::instance();