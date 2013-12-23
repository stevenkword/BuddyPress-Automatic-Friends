<?php
/**
 * Update Scripts
 *
 * Updates to the database if necessary.
 *
 * @link http://wordpress.org/plugins/bp-automatic-friends/
 * @since 2.0.0
 *
 * @package BuddyPress Automatic Friends
 * @subpackage Update
 */

/**
 * BuddPress Automatic Friends Update
 */
class BPAF_Update {

	const     OPTION_VERSION = 'bp-automatic-friends-version';
	protected $version       = false;
	public    $plugins_url;

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
		// Version Check
		if( $version = get_option( self::OPTION_VERSION, false ) ) {
			$this->version = $version;
		} else {
			$this->version = BPAF_Core::VERSION;
			add_option( self::OPTION_VERSION, $this->version );
		}

		// Perform updates if necessary
		add_action( 'init', array( $this, 'action_init_perform_updates' ) );

	}

	/**
	 * Do the updates.
	 *
	 * @since 2.0.0
	 */
	function action_init_perform_updates() {

		// Check if the version has changed and if so perform the necessary actions
		if ( ! isset( $this->version ) || $this->version < BPAF_Core::VERSION ) {

			// Perform updates here if necessary
			if( $this->version < '2.0.0' ) {

				// Get the friend users id(s)
				$options = get_option( BPAF_Core::LEGACY_OPTION );
				$global_friend_user_ids = $options['skw_bpaf_user_ids'];
				$friend_ids = explode( ',', $global_friend_user_ids );

				// Convert to user meta
				foreach ( $friend_ids as $friend_id ) {
					// Add Global Friend status
					$user = get_user_by( 'id', $friend_id );
					if( isset( $user->data->ID ) ) {
						// Update the user and related friendships
						update_usermeta( $user->data->ID, BPAF_Core::METAKEY, true );
					}
				}
			}
			// Update the version information in the database
			//update_option( self::OPTION_VERSION, BPAF_Core::VERSION );
			add_action('admin_notices', array( $this, 'admin_notice' ) );
		}
	}

	/**
	 * Notify the admin of the update.
	 *
	 * @since 2.0.0
	 */
	function admin_notice() {
		echo '<div class="updated"><p>BuddyPress Automatic Friends has been updated to version ' . BPAF_Core::VERSION . '.</p></div>';
	}

} // Class
BPAF_Update::instance();