<?php
/**
Plugin Name: PBox
Description: Customizable presentation widget to display posts, pages, external content, links, media images, and plain text in a customized style.
Version: 2.3
Author: Aaron Berg, Dale Taylor, Nelson Lai, Yefei Tang, Xueyan Bai, Zafor Ahmed, Fran&ccedil;ois Fortin, Lindsay Newton, Nicholas Crawford
Author URI: http://www.bankofcanada.ca/
Plugin URI: http://bankofcanada.wordpress.com/
*/

/*  Copyright 2009 Bank of Canada (Aaron Berg, Dale Taylor, Nelson Lai, Yefei Tang, Xueyan Bai, Zafor Ahmed, Fran&ccedil;ois Fortin, Lindsay Newton)

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

global $wpdb;

//Include the PBox object and its functionality
require_once( 'pb.inc.php' );

// Register filter/action hooks
register_activation_hook( __FILE__, 'pb_check_version' ); // Check compatibility with WordPress version first.

//registering and enqueueing the pbox.css file in the header initialization (front end)
add_action( 'wp_print_styles', 'pb_style_init' );

// add multilingual support
add_action('init', 'pb_plugin_localization');
//registering the menu items (admin end)
add_action( 'admin_menu', 'pb_menu' );
//Registering and enqueuing javascript functionality for pb.edit.php (admin end)
add_action( 'admin_print_scripts', 'pb_js' );
//updating the usage table when a WordPress page/post is deleted (admin end)
add_action( 'delete_post', 'pb_page_deleted' );
//updating the usage table when a WordPress attachment is deleted (admin end)
add_action( 'delete_attachment', 'pb_attachment_deleted' );
//updating the usage table when a WordPress link is deleted (admin end)
add_action( 'delete_link', 'pb_link_deleted' );
//checking request when it happens to avoid requests being sent multiple times (admin end)
add_action('load-'.PBOX_DIR_NAME.'pb.manage.php', 'pbox_admin_actions');
//checking request for redirect to edit page
add_action('load-'.PBOX_DIR_NAME.'pb.manage.php', 'pb_edit_support');
//modifying request for redirect to edit page from add_process
add_action('load-'.PBOX_DIR_NAME.'pb.edit.php', 'pb_add_support');
//modifying the pbox tables as necessary when changing content/order in individual boxes (admin end)
add_action('wp_ajax_edit_process', 'pb_ajax_response', 10, 1);
//adding the time for the last update of the css file
add_option( 'pb_csstimestamp', '', '', 'yes' );

// Used for roles
add_filter( 'capabilities_list', 'pbox_capabilities' );

/**
* Returns an error message if enabled with an older version of
* WordPress (before the new 2.8 API)
*
* @return void
*/
function pb_check_version() {
	// If the plugin is compatible with the site ( i.e. the new Widget API in 2.8)
	if( class_exists( 'WP_Widget' ) ) {
		//then install
		pb_install();
	}
	else {
		//otherwise stop the activation request and deactivate
		$active_plugins = get_option( 'active_plugins' );
		$pbox_loc = array_search( PBOX_DIR_NAME.'pb.php', $active_plugins );
		if( false !== $pbox_loc ) {
			// remove PBox from the list of active plugins
			unset( $active_plugins[$pbox_loc] );
			update_option( 'active_plugins', $active_plugins );
			unset( $_GET['activate'] );
			// add it to the recently activated section with the correct info
			$recently_activated = get_option( 'recently_activated' );
			if( !isset( $recently_activated[PBOX_DIR_NAME.'pb.php'] ) ) {
				$recently_activated[PBOX_DIR_NAME.'pb.php'] = time();
				update_option( 'recently_activated', $recently_activated );
			}
			// kill the processing and report the error.
			wp_die( '<p>' . __( 'Presentation Boxes could not be activated. Your WordPress install is outdated and Presentation Boxes will not function properly. Please upgrade your install to WordPress version 2.8 or higher.', 'pb' ) . '</p><p>' .
									sprintf( __( 'Click %shere</a> to return to the plugin admin page.', 'pb' ), '<a href="plugins.php">' ) . '</p>' );
		}
	}
}

/**
* Sets up multilingual support for the plugin. Translation
* files are contained in the locale directory of the PBox
* install.
*
* Note: This function uses load_plugin_textdomain which
* instead of being replaced by a new function in the WordPress
* core was simply modified to have a third argument that would
* replace the second deprecated argument as of 2.6. The value
* of the second argument is irrelevant as long as there is a third.
*
* @return void
*/
function pb_plugin_localization() {
    load_plugin_textdomain( 'pbox', '', dirname( plugin_basename( __FILE__ ) ) );
}

/*
* Enqueues the required JavaScript for pb.edit.php
* Used for saving page/post/files/links correctly. Relies
* on jQuery and the jQuery Sortable class packaged with
* WordPress.
*
* @return void
*/
function pb_js() {
	if ( preg_match( '/.*'.trim(trim(PBOX_DIR_NAME, '/'), '\\').'.*/', $_SERVER['REQUEST_URI'] ) ) {
		// Include jQuery/Sortable for the PBox edit panel
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		// Registering the pbox exclusive javascript file to load in the footer with everything else
		wp_register_script( 'pbox-js', PBOX_PLUGIN_URL . 'js/pb.functions.js', 'jquery' );
		wp_enqueue_script( 'pbox-js' );
		return;
	}
}

/*
* When a page is deleted, this will also remove it from every PBox
* keeping your usage table up-to-date for support of the dependencies
* feature available when XWidgets is enabled. Also keeps from having
* PBoxes with nonexistent content.
*
* @param int $page_id
* @return void
*/
function pb_page_deleted( $page_id ) {
	global $wpdb;
	$page_id = intval( $page_id );
	//usage table updates not necessary unless xwidgets is active.
	if( class_exists( 'XWidgets' ) ) {
		$wpdb->query( 'DELETE FROM '.PBOX_USAGE_TABLE."
								WHERE page_id=$page_id" );
	}
	$wpdb->query( 'DELETE FROM '.PBOX_ITEM_TABLE."
							WHERE content=$page_id AND type_id=2" );
}

/*
* When a file is deleted, this will also remove the references to it
* in each PBox. Meaning that deleting a file will not result in
* in broken links; the clean-up happens automatically.
*
* @param int $attachment_id
* @return void
*/
function pb_attachment_deleted( $attachment_id ) {
	global $wpdb;
	$attachment_id = intval( $attachment_id );
	$wpdb->query( 'DELETE FROM '.PBOX_ITEM_TABLE."
							WHERE content=$attachment_id AND type_id=2" );
}

/*
* When a link is deleted, this will also remove every use of it
* from any PBox.
*
* @param int $link_id
* @return void
*/
function pb_link_deleted( $link_id ) {
	global $wpdb;
	$link_id = intval( $link_id );
	$wpdb->query( "DELETE FROM ".PBOX_ITEM_TABLE."
							WHERE content=$link_id AND type_id=1" );
}

/*
* Registers and enqueues the PBox CSS file to ensure
* that the file is not included twice when properly called.
*
* @return void
*/
function pb_style_init() {
	if ( file_exists( PBOX_CSS_LOC ) ) {
		wp_register_style( 'pbox_style', PBOX_CSS_URL );
		wp_enqueue_style( 'pbox_style');
	}
}

function include_pbox_manage() {
//	pbox_admin_actions();
	include(dirname(__FILE__).'/pb.manage.php');
}

function include_pbox_styles() {
	include(dirname(__FILE__).'/pb.styles.php');
}

function include_pbox_uninstall() {
	include(dirname(__FILE__).'/pb.uninstall.php');
}

/*
* Builds PBox left menu for the admin side of things for
* users who have the proper capabilities to edit PBoxes.
*
* @return void
*/
function pb_menu() {
/*
add_options_page("WP-Optimize", "WP-Optimize", 10, "WP-Optimize", "optimize_menu");
    add_submenu_page("index.php", "WP-Optimize", "WP-Optimize", 10, "WP-Optimize", "optimize_menu");
*/
	if( pbox_check_capabilities() ) {
		if ( function_exists( 'add_menu_page' ) ) {
			add_object_page( __( 'Presentation Box Management' , 'pb' ), 'PBox', 5, 'include_pbox_manage' , 'include_pbox_manage');
		}
		
		if ( function_exists( 'add_submenu_page' ) ) {
			add_submenu_page( 'include_pbox_manage', __( 'Manage PBoxes', 'pb' ), __( 'Manage PBoxes', 'pb' ), 5,  'include_pbox_manage' , 'include_pbox_manage');
			add_submenu_page( 'include_pbox_manage', __( 'PBox Styles', 'pb' ), __( 'PBox Styles', 'pb' ), 5, 'include_pbox_styles' , 'include_pbox_styles' );
			add_submenu_page( 'include_pbox_manage', __( 'Uninstall PBox', 'pb' ), __( 'Uninstall PBox', 'pb' ), 5, 'include_pbox_uninstall' , 'include_pbox_uninstall'  );
			add_submenu_page( 'include_pbox_manage', '', '', 5, PBOX_DIR_NAME.'pb.edit.php' );
			add_submenu_page( 'include_pbox_manage', '', '', 5, PBOX_DIR_NAME.'pb.manage.php' );
		}
		
	}
}

/*
* Adds 'Manage PBox' to the capabilities list.
*
* @param array $caps
* @return array capabilities with 'Manage PBox' added
*/
function pbox_capabilities( $caps ) {
	$caps[] = 'Manage PBox';
	return $caps;
}

/*
* Checks the current user's capabilities. This function
* checks for an installation of the plugin Role Manager,
* which can be found at:
*
* http://www.im-web-gefunden.de/wordpress-plugins/role-manager/
*
* If Role Manager is installed it will return the permissions
* of the current user laid out in the Role Manager control
* panel. If Role Manager is NOT installed, the ability to
* edit PBoxes will be available to anyone able to edit pages
* and posts.
*
* If you have a role organization plugin other than Role Manager
* that you prefer, feel free to submit a patch to help with support.
*
* @return boolean
*/
function pbox_check_capabilities() {
	//Check to see if Role Manager is installed
	if( class_exists( 'IWG_RoleManagement' ) ) {
		return current_user_can ( 'Manage PBox' );
	} else {
		return current_user_can( 'edit_pages' ) && current_user_can( 'edit_posts' );
	}
}

/**
* Checks the current state of the management admin page and
* carries out the appropriate request.
*
* @return void
*/
function pbox_admin_actions() {
	if ( !pbox_check_capabilities() ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.', 'pb' ) );
	}
	if ( isset( $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];
		// If in the following array, no action is necessary on load.
		if (in_array( $action, array( 'edit_view', 'add_process', 'edit_process', 'search_by_id' ) ) ) {
			return;
		}
		// Checking the request method
		$is_post = 'post' == strtolower( $_SERVER['REQUEST_METHOD'] );
		if ( $is_post ) {
			$argToRemove = array( 'action' );
			$argToAdd = array();
			// Determine which actions to take depending on request
			switch ( $action ) {
				case 'clone_process':
					//check if the clone request is coming from a reputable source
					check_admin_referer( 'pbox-box-clone' );
					$box1_id = intval( $_REQUEST['box_id'] );
					$title = PBox::get_title( $box1_id );
					$box2_id = PBox::create_box( $title );
					PBox::update_items( $box2_id, PBox::get_contents( $box1_id ) );
					PBox::update_info( $box2_id );
					$argToAdd['box_new_title'] = $title;
					$argToAdd['box_new_id'] = $box2_id;
					$message = 'cloned';
					break;
				case 'delete_process':
					check_admin_referer( 'pbox-box-delete' );
					$box_id = intval( $_REQUEST['box_id'] );
					/*
					* Check to see if the process succeeds (not
					* trying to delete something that's not there)
					*/
					if (  PBox::delete_box( $box_id ) ) {
						$message = 'box_deleted';
					} else {
						$message = 'box_not_deleted';
					}
					break;
				case 'search_by_id':
					$message = 'search_by_id';
					break;
				default:
					$message = 'unknown_action';
			}
			// redirect accoring to the action carried out. Keeps pboxes from cloning/adding on refresh.
			wp_redirect( add_query_arg( $argToAdd, add_query_arg( 'message', $message, remove_query_arg( $argToRemove ) ) ) );
		}
	}
}

/*
* Initialize setup when first activated. Sets up the
* database tables and adds the necessary options
* to the wp_options table.
*
* @return void
*/
function pb_install() {
	include_once( 'pb.inc.php' );
	return PBox::init_setup();
}

/**
* Used to retrieve a page parent (for inheritance)
* Added functionality for the XWidgets plugin.
*
* @param int $pid - the ID of the referenced page/post
* @return int  - the id of the page/post parent
*/
function pb_get_page_parent( $pid ) {
	global $wpdb;
	$pid = intval( $pid );
	$results = $wpdb->get_results( "SELECT post_parent FROM $wpdb->posts
													WHERE ID=$pid" );
	return $results[0]->post_parent;
}

/**
* Actions carried out when a user edits the
* content of a PBox. Hooking into the WordPress
* AJAX support via their wp_ajax_[action] hook.
* The AJAX call itself is made from pb.edit.php
* with some auxiliary functions in pb.functions.js
*
* @return void
*/
function pb_ajax_response() {
	global $wpdb;
	$box_id = intval( $_REQUEST['box_id'] );
	/*
	* Note the decoding of the title. The title is encoded in the pb.edit.php
	* in the AJAX call... Almost all special characters are handled well with
	* the exception of the ampersand (in the data string used for the AJAX call
	* any text after the ampersand in the title will be seen as a new REQUEST
	* component in the string).
	*/
	$box_title = esc_attr( urldecode( $_REQUEST['box_title'] ) );
	if ( count( $_POST ) != 0 ) { // Make sure something was actually submitted to this page
		$box_title = $_REQUEST['box_title'];
		//Build the array of the item order (which will evaluate the value of form elements in order)
		foreach( (array) $_REQUEST as $key => $value ) {
			if( strripos( $key, 'order_' ) !== FALSE ) {
				$item_order[] = $value;
			}
		}
		$box_content = array(); //will be a 2D array
		foreach ( (array) $item_order as $item ) {
			// If there is content, build a line for the content array
			if ( strlen( $_REQUEST['content_'.$item] ) > 0 ) {
				$line['type_id'] = intval( $_REQUEST['type_' . $item] );
				if ( $line['type_id'] != PBOX_TYPE_TEXT && $line['type_id'] != PBOX_TYPE_EXTERNAL ) {
					$line['content'] = intval( $_REQUEST['content_' . $item] );
				} else {
					$line['content'] = $_REQUEST['content_' . $item];
				}
				if ( isset( $_REQUEST['callout_id' . "_$item"] ) && is_numeric( $_REQUEST['callout_id' . "_$item"]) && $_REQUEST['callout_id' . "_$item"] < 3 ) {
					$line['callout_id'] = intval( $_REQUEST["callout_id_$item"] );
				} else {
					$line['callout_id'] = 0;
				}
				$box_content[] = $line; //array
			}
		}
		// Actual updates to the database, store previous data for rollback on error
		$older_items = PBox::get_contents( $box_id );
		$db_check = PBox::update_items( $box_id, $box_content );
		if( $db_check === FALSE ) {
			// Not necessary to roll back anything since no writes have occurred yet
			die( json_encode( array( 'error' => 1 ) ) );
		}
		// Format old contents in a way to give back to the DB
		foreach ( (array) $older_items as $items ) {
			$old_items[$items['sort_order']] = array( 'type_id' => $items['type_id'], 
																		'content' => $items['content'], 
																		'callout_id' => $items['callout_id'] );
		}
		$old_title = PBox::get_title( $box_id );
		$db_check = PBox::update_title( $box_id, $box_title );
		if( $db_check === FALSE ) {
			// Need to rollback the item changes that have been written
			PBox::update_items( $box_id, $old_items );
			die( json_encode( array( 'error' => 1 ) ) );
		}
		$db_check = PBox::update_info( $box_id );
		if( $db_check === FALSE ) {
			// Need to rollback the item/title changes that have been written
			PBox::update_items( $box_id, $old_items );
			PBox::update_title( $box_id, $old_title );
			die( json_encode( array( 'error' => 1, 'box_id' => $box_id ) ) );
		} else {
			die( json_encode( array( 'error' => 0, 'box_id' => $box_id ) ) );
		}
	}
}

/**
* Forces a redirect to the edit page from the management screen.
*
* @return void
*/
function pb_edit_support() {
	if ( isset ( $_REQUEST['action'] ) && ( $_REQUEST['action'] == 'edit_view' || $_REQUEST['action'] == 'add_process' || $_REQUEST['action'] == 'edit_process' ) ) {
		wp_redirect( html_entity_decode( wp_nonce_url( PBox::get_admin_url( PBOX_DIR_NAME.'pb.edit.php', "&action=edit_process&box_id={$_REQUEST['box_id']}" ), 'pbox-box-edit' ) ) );
	}
}

/**
* Creates a PBox and ensures consistency of behaviour between
* addition and editting actions. 
*
* @return void
*/
function pb_add_support() {
	if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'add_process' ) {
		check_admin_referer( 'pbox-box-add' );
		$box_id = PBox::create_box( $_REQUEST['box_title'] );
		PBox::update_info( $box_id );
		wp_redirect( html_entity_decode( wp_nonce_url( PBox::get_admin_url( PBOX_DIR_NAME.'pb.edit.php', "&action=edit_view&box_id=$box_id" ), 'pbox-box-edit' )) );
	}
}

// Checking the version of the WordPress installation (pre-2.8 installs will not instantiate this class)
if( class_exists( 'WP_Widget' ) ) {
	/**
	* Little check to intercept pbox deletes on the widget admin panel
	* to keep the usage table up to date (if xwidgets is installed)
	*/
	if ( isset( $_REQUEST['xwidgets_id'] ) && isset( $_POST['delete_widget'] ) && $_POST['delete_widget'] ) {
		if( isset( $_POST['multi_number'] ) && $_POST['multi_number'] > 0 ) {
			$slot_id = $_POST['multi_number'];
		} else {
			$slot_id = $_POST['widget_number'];
		}
		PBox::remove_box_usage( $_REQUEST['xwidgets_id'],  $slot_id );
	}

	/**
	* Definition of the actual PBox Widget. The widget stores the
	* ID of the PBox to load as well as the Style ID that box should
	* use to style the output. A "slot id" for each widget indentifies
	* widgets from one another on a given page. Page ID (if applicable)
	* is taken from the XWidgets widgets admin panel if XWidgets is
	* installed.
	*
	*/
	class PBox_Widget extends WP_Widget {

		/**
		* Widget actual processes, definitions for Widget Dashboard
		*
		* @return void;
		*/
		function PBox_Widget()  {
			// Name and description for the Widget Dashboard
			$widget_ops = array( 'description' => __( 'Customizable content boxes', 'pb' ) );
			// Dimensions of the widget control panel
			$control_ops = array( 'width' => 400, 'height' => 350 );
			// Important! 'pb' is the prefix to the instance ids in the postmeta data.
			$this->WP_Widget( 'pb', __( 'Presentation Box', 'pb' ), $widget_ops, $control_ops );
		}

		/**
		* Outputs the content of the widget
		*
		* @param array $args
		* @param WP_Widget $instance
		* @return void
		*/
		function widget( $args, $instance ) {
			//Calls on the necessary functions in PBox to generate the box output
			PBox::display_box( $instance['box_id'],  $instance['style_id']  );
		}

		/**
		* Processes widget options to be saved
		*
		* @param WP_Widget $new_instance
		* @param WP_Widget $old_instance
		* @return WP_Widget
		*/
		function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['box_id'] = intval( $new_instance['box_id'] );
			$instance['style_id'] = esc_attr( $new_instance['style_id'] );

			// Keeping the usage table up to date. This is only really necessary if XWidgets is installed
			if( class_exists( 'XWidgets' ) ) {
				$page_id = intval( $_REQUEST['xwidgets_id'] );
				$meta = get_post_meta( $page_id , '_xwidgets_widget_pb' );
				$meta = $meta[0];
				$found = false; // Flag to insure that data is not added twice to the database
				foreach( (array) $meta as $key => $box ) {
					if( $instance['instance_id'] > 0 ) {
						// If the slot_id is already set from a previous update, we don't want to change it.
						$slot_id = $instance['instance_id'];
					} else {
						// Otherwise we want to make sure it's assigned!
						$slot_id = $key;
					}
					if( !$found && $box['box_id'] == $old_instance['box_id'] && $box['style_id'] == $old_instance['style_id'] ) {
						PBox::update_usage( $page_id, $instance['box_id'], $instance['style_id'], $slot_id );
						$found = true;
					}
				}
			}
			return $instance;
		}

		/**
		* Displays the entry form on the widget interface
		*
		* @param WP_Widget $instance
		* @return void
		*/
		function form( $instance ) {
			if( isset( $instance['box_id'] ) ) {
				$box_id = intval( $instance['box_id'] );
			} else {
				$box_id = '';
			}
			if( isset( $instance['style_id'] ) ) {
				$style_id = esc_attr( $instance['style_id'] );
			} else {
				$style_id = '';
			}
			?>
			<p>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>-title" name="<?php echo $this->get_field_name( 'title' ); ?>-title" style="display:none" type="text" value="<?php echo stripslashes( PBox::get_title( $box_id ) ); ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'box_id' ); ?>"><?php _e( 'Box ID:', 'pb' ); ?>
				<input id="<?php echo $this->get_field_id( 'box_id' ); ?>" name="<?php echo $this->get_field_name( 'box_id' ); ?>" type="text" value="<?php echo $box_id; ?>" />
				</label>
			</p>
			<?php

			// Generate the styles drop-down
			$styles = PBox::get_styles();
			if ( count( $styles ) > 0 ) {
				$style_select = "<select name='" . $this->get_field_name('style_id') . " id='" . $this->get_field_id('style_id') . "'>";
				foreach ( (array) $styles as $style ) {
					$style_select .= "<option value='" . $style['style_id'] ."'" . ( ( $style_id == $style['style_id'] ) ? "SELECTED" : '' ) . '>' . $style['style_id'] . '</option>';
				}
				$style_select .= '</select>';
				echo "<p><label for='pb-style_id-". $this->get_field_id( 'box_id' ) . "'>" . __( 'Style:  ', 'pb' ) . $style_select . '</label></p>';
			} else {
				echo "<p>" . __( 'No styles found!', 'pb' ) . "</p>";
			}

			// Edit link, if there is a box_id to access for edit
			if( PBox::get_box( $box_id ) != -1 || $box_id == 0 ) {
				if( pbox_check_capabilities() && $box_id > 0 ) {
					echo "<p><a href='" . wp_nonce_url( PBox::get_admin_url( PBOX_DIR_NAME.'pb.edit.php', "&amp;action=edit_view&amp;box_id=$box_id" ), "pbox-box-edit" ) . "' rel='permalink'>".__( 'Edit PBox', 'pb' )."</a>";
				}
			} else {
				printf( "<p><strong>NOTE:</strong> PBox %d does not exist. Please enter a valid PBox ID.</p>", $box_id );
			}
		}
	}
	// Register the PBox_Widget
	add_action( 'widgets_init', create_function( '', 'return register_widget( "PBox_Widget" );' ) );
}

function pbox_css_moved () {
	printf ('<div class="error"><p><strong>%s %s %s %s. %s.</strong></p></div>', __('PBox moved your CSS Stylesheet from', 'pbox'), PBox::get_theme_location() . '/pbox.css' , __('to', 'pbox'), PBOX_PLUGIN_DIR.'css/pbox.css', __('Please move any other required files (ie images) to their proper location. Note that the old css file was not deleted', 'pbox') );
}
function pbox_css_not_moved () {
	printf ('<div class="error"><p><strong>%s %s %s %s %s</strong></p></div>', __('PBox was unable to copy your CSS Stylesheet from', 'pbox'), PBox::get_theme_location() . '/pbox.css', __('to', 'pbox'), PBOX_PLUGIN_DIR.'css/pbox.css', __('. Please move this file and any other required files to their proper location.') );
}
function pbox_reactivate () {
	printf ('<div class="error"><p><strong>%s.</strong></p></div>', __('PBox needs to be deactivated and then re-activated in order to work properly', 'pbox') );
}

// check to see if an admin notice needs to be displayed for css files
$pbox_css_moved = get_option('pbox_css_moved');
if ( $pbox_css_moved !== false )
{
	if ( $pbox_css_moved == 0 )
		add_action( 'admin_notices', 'pbox_css_not_moved' );
	elseif ( $pbox_css_moved == 1 )
		add_action( 'admin_notices', 'pbox_css_moved' );
	
	delete_option('pbox_css_moved');
}
// check if admin notice needs to be displayed for re-activation
if ( get_option('PBox_DB') != PBOX_CUR_VERSION )
	add_action( 'admin_notices', 'pbox_reactivate' );