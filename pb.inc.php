<?php
/**
PBox
Customizable content widgets able to display posts, pages, links and plain text in a custom style.
2.2
Authors: Aaron Berg, Dale Taylor, Nelson Lai, Yefei Tang, Xueyan Bai, Zafor Ahmed, Fran&ccedil;ois Fortin, Lindsay Newton
http://www.bankofcanada.ca/
*/

/** INCLUDE FILE & PBOX CLASS **/

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
global $box_data; // Used to store box data for use with the display functions

// DEFINE CONSTANTS

define( 'PBOX_TABLE', $wpdb->prefix . 'pbox' );
define( 'PBOX_ITEM_TABLE', $wpdb->prefix . 'pbox_items' );
define( 'PBOX_USAGE_TABLE', $wpdb->prefix . 'pbox_usage' );
define( 'PBOX_STYLE_TABLE', $wpdb->prefix . 'pbox_styles' );
define( 'PBOX_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/pbox/' );
define( 'PBOX_PLUGIN_URL', get_bloginfo( 'wpurl' ) . '/wp-content/plugins/pbox/' );

define( 'POST', $wpdb->prefix . 'posts' );
define( 'PBOX_TYPE_LINK', '1' );
define( 'PBOX_TYPE_PAGE', '2' );
define( 'PBOX_TYPE_TEXT', '3' );

/**
* PBox class
*
* Used mainly for code organization
*/

class PBox {
	private $box_id;
	private $title;
	private $style_id;
	private $items;
	private $theme_location;

	/**
	* Initial setup of databases
	*
	* @return void
	*/
	function init_setup() {
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );
		// Generates the table to store 'accounting' information about each box. ( {prefix}_pbox )
		$sql = "CREATE TABLE `".PBOX_TABLE."` (
		`pbox_id` bigint(20) NOT NULL auto_increment,
		`title` tinytext NOT NULL,
		`last_update_by` int(12) NOT NULL,
		`last_update_time` int(12) NOT NULL,
		PRIMARY KEY  (`pbox_id`)
		)  AUTO_INCREMENT=1;";
		dbDelta( $sql );
		// Generates table storing the content of each pbox ( {prefix}_pbox_items )
		$sql = "CREATE TABLE `".PBOX_ITEM_TABLE."` (
		pbox_id int(11) NOT NULL,
		type_id int(11) NOT NULL,
		content text NOT NULL,
		sort_order int(10) NOT NULL,
		callout_id int(3) NOT NULL DEFAULT '0'
		) ;";
		dbDelta( $sql );
		// Generates table storing relationships between pages, styles, slots and box ids ( {prefix}_pbox_usage )
		$sql = "CREATE TABLE `".PBOX_USAGE_TABLE."` (
		page_id bigint(18) NOT NULL,
		pbox_id bigint(18) NOT NULL,
		slot_id int(3) NOT NULL,
		style_id text NOT NULL
		) ;";
		dbDelta( $sql );
		// Generates table storing style information ( {prefix}_pbox_styles )
		$sql = "CREATE TABLE  `".PBOX_STYLE_TABLE."` (
		 `style_id` varchar(255) NOT NULL,
		 `above_items_template` text NOT NULL,
		 `links_template` text NOT NULL,
		 `links_excerpt_template` text NOT NULL,
		 `posts_template` text NOT NULL,
		 `posts_excerpt_template` text NOT NULL,
		 `posts_content_template` text NOT NULL,
		 `text_template` text NOT NULL,
		 `below_items_template` text NOT NULL,
		 PRIMARY KEY  (`style_id`)
		) ;";
		dbDelta( $sql );
		// Inserting the initial default style.
		$wpdb->query("insert into ".PBOX_STYLE_TABLE." VALUES ('Default-Style',
			'<div class=\'pbox_default-style\'><div class=\'pbox_default-style_head\'><h2>%PBOX_TITLE%</h2></div><div class=\'pbox_default-style_body\'><div id=\'content\'>',
			'<p>%ITEM_LINK%</p>',
			'<p>%ITEM_LINK% - %ITEM_EXCERPT%</p>',
			'<p>%ITEM_LINK%</p>',
			'<p>%ITEM_LINK% - %ITEM_EXCERPT%</p>',
			'<p>%ITEM_LINK%</p><p>%ITEM_CONTENT%</p>',
			'%ITEM_CONTENT%<br />',
			'</div></div></div>')");
		dbDelta( $sql );

		// Recording the current version of the plugin/database.
		add_option('PBox_DB', '2.2');
	}
	
	/**
	* Records usage of box in a slot on a page. Slot refers
	* to its instance number in regards to the WordPress 2.8
	* widget API.
	*
	* @param int $page_id
	* @param int $slot_id
	* @param int $box_id
	* @return mixed Result of database insert with $wpdb->query
	*/
	function add_box_usage( $page_id, $box_id, $slot_id = 0 ) {
		global $wpdb;
		$box_id = intval( $box_id );
		$page_id = intval( $page_id );
		$slot_id = intval( $slot_id );
		return $wpdb->query( 'INSERT INTO ' . PBOX_USAGE_TABLE . " (page_id,slot_id,pbox_id) 
										VALUES ('$page_id','$slot_id','$box_id')" );
	}

	/**
	* Removes box usage based on page_id and slot_id
	*
	* @param int $page_id
	* @param int $slot_id
	* @return mixed Result of database delete with $wpdb->query
	*/
	function remove_box_usage( $page_id, $slot_id ) {
		global $wpdb;
		$page_id = intval( $page_id );
		$slot_id = intval( $slot_id );
		return $wpdb->query( 'DELETE FROM ' . PBOX_USAGE_TABLE . "
										WHERE page_id='$page_id' AND slot_id='$slot_id'" );
	}

	/**
	* Get boxes and pages on which a specific content ID is used
	*
	* @param int $content_id
	* @return 2Darray Array of items in array format arranged by sort order
	*/
	function get_usage_by_content( $content_id ) {
		global $wpdb;
		$content_id = intval( $content_id );
		return $wpdb->get_results( 'SELECT * FROM ' . PBOX_ITEM_TABLE . "
												WHERE content='$content_id' order by sort_order", ARRAY_A );
	}
	
	/**
	* Create presentation box
	*
	* @param string $title
	* @return mixed Result of insert
	*/
	function create_box( $title ) {
		global $wpdb, $user_ID;
		$title = $wpdb->escape( $title );
		get_currentuserinfo(); //populate the global $user_ID
		$ct = time();
		$wpdb->query( 'INSERT INTO ' . PBOX_TABLE . " (title) VALUES ('$title')" );
		return $wpdb->insert_id;
	}

	/**
	* Return pbox data from wp_pbox table
	*
	* @param int $box_id
	* @return array $boxes[0] or -1 if box does not exist
	*/
	function get_box( $box_id ) {
		global $wpdb;
		$box_id = intval( $box_id );
		$boxes = $wpdb->get_results( 'SELECT * FROM ' . PBOX_TABLE . "
													WHERE pbox_id='$box_id'", ARRAY_A );
		if( count( $boxes ) < 1 ) {
			return -1;
		}
		return $boxes[0];
	}

	/**
	* Return an array of all PBoxes contained on a page.
	*
	* @param int $page_id
	* @return array $boxes
	*/
	function get_boxes_on_page( $page_id ) {
		global $wpdb;
		$page_id = intval( $page_id );
		$query = 'SELECT DISTINCT ' . PBOX_USAGE_TABLE . '.pbox_id, ' . PBOX_TABLE . '.title, '.PBOX_TABLE . '.last_update_by, ' . PBOX_TABLE . '.last_update_time
						FROM ' . PBOX_TABLE . ', ' . PBOX_USAGE_TABLE . '
						WHERE ' . PBOX_USAGE_TABLE . ".page_id=$page_id
						AND " . PBOX_USAGE_TABLE . '.pbox_id = ' . PBOX_TABLE . '.pbox_id';
		return $wpdb->get_results( $query, ARRAY_A );
	}
	
	/*
	* Removes box, items inside box, and associations to that box (set to box ID 0).
	* Note that box isn't guaranteed to exist, simply calls to delete if it does
	*
	* @param int $box_id
	* @return bool $val
	*/
	function delete_box( $box_id ) {
		global $wpdb;
		$box_id = intval( $box_id );
		$delcheck = $wpdb->get_results( 'SELECT pbox_id FROM ' . PBOX_TABLE . "
															WHERE pbox_id=$box_id" );
		// If there's something to delete
		if ( is_array( $delcheck ) && $delcheck[0]->pbox_id > 0) {
			$val = true;
		} else {
			$val = false;
		}
		if( $val ) {
			// Need to delete it from all pages it's used on if XWidgets is enabled
			if( class_exists( 'XWidgets' ) ) {
				$usage = PBox::get_pages_using_box( $box_id );
				foreach( (array) $usage as $pages ) {
					$page_id = $pages['page_id'];
					$meta = get_post_meta( $page_id, '_xwidgets_widget_pb' );
					// unset all references to the deleted box
					$found_one = false;
					foreach( (array) $meta[0] as $key => $value ) {
						if( isset( $value['box_id'] ) ) {
							if( $value['box_id'] == $box_id ) {
								unset( $meta[0][$key] );
							}
						// If there is no valid box ID, unset.
						} else {
							unset($meta[0][$key] );
						}
					}
					update_post_meta( $page_id, '_xwidgets_widget_pb', maybe_serialize( $meta ) );
				}
			} 
			// handle global widgets no matter what
			$option = get_option( 'widget_pb' );
			foreach( (array) $option as $key => $value ) {
				if( isset( $value['box_id'] ) && $value['box_id'] == $box_id ) {
					unset( $option[$key] );
				}
			}
			update_option( 'widget_pb', $option );
			// Delete all info about the box
			$wpdb->query( 'DELETE FROM ' . PBOX_TABLE . "
									WHERE pbox_id='$box_id'" );
			// Delete all items associated with the box
			$wpdb->query( 'DELETE FROM ' . PBOX_ITEM_TABLE . "
									WHERE pbox_id='$box_id'" );
			// Delete all page-box links in the usage table
			$wpdb->query( 'DELETE FROM ' . PBOX_USAGE_TABLE . "
									WHERE pbox_id='$box_id'" );
		}
		return $val;
	}

	/**
	* Get title of a box
	*
	* @param int $box_id ID of box to get title of
	* @return string Title of the box
	*/
	function get_title( $box_id ) {
		global $wpdb;
		$box_id = intval( $box_id );
		$contents = $wpdb->get_results( 'SELECT pbox_id, title FROM ' . PBOX_TABLE . "
															WHERE pbox_id = '$box_id'" );
		if( count( $contents ) > 0 ) {
			return  $contents[0]->title;
		} else {
			return '';
		}
	}

	/**
	* Update title of a box
	* Sanitize input BEFORE using this function!
	*
	* @param int $box_id ID of box to get title of
	* @param string $title New title
	* @return mixed Result of wpdb->query
	*/
	function update_title( $box_id, $title ) {
		global $wpdb;
		$box_id = intval( $box_id );
		$title = $wpdb->escape( $title );
		// If the pbox with that ID does not exist, create it
		$pboxes = $wpdb->get_results( 'SELECT pbox_id, title, last_update_by, last_update_time 
														FROM ' . PBOX_TABLE . "
														WHERE pbox_id='$box_id'", ARRAY_A );
		if ( count( $pboxes ) < 1 ) {
			return $wpdb->query( 'INSERT INTO ' . PBOX_TABLE . " 
											VALUES ('$box_id', '$title', '', '')");
		// otherwise just update the existing pbox
		} else {
			return $wpdb->query( 'UPDATE ' . PBOX_TABLE . " 
											SET title='$title' where pbox_id='$box_id'" );
		}
	}

	/**
	* Get items info based on box ID
	*
	* @param int $box_id ID of box
	* @return 2Darray of rows from pbox items table
	*/
	function get_contents( $box_id ) {
		global $wpdb;
		$box_id = intval( $box_id );
		return $wpdb->get_results( 'SELECT * FROM ' . PBOX_ITEM_TABLE . "
												WHERE pbox_id='$box_id'
												ORDER BY sort_order", ARRAY_A );
	}

	/**
	* Updates items in pbox items table.
	* Works by removing all items associated to the box ID, and then re-adding them
	* Sanitize input BEFORE using this function!
	*
	* @param int $box_id
	* @param array $content_array is a 2D array; numerically indexed array of arrays with index type and content (eg: $array[2][type]=1)
	* @return boolean
	*/
	function update_items( $box_id, $content_array ) {
		global $wpdb;
		$valid = TRUE;
		$box_id = intval( $box_id );
		/**
		* Delete all content of the pbox to then re-add it. This
		* isn't ideal, but for now pbox items don't have a unique
		* identifier and pretty much all their content can be duplicated
		* and changed so there's no way to ensure the correct one
		* is being updated.
		*/
		$wpdb->query( 'DELETE FROM ' . PBOX_ITEM_TABLE . "
								WHERE pbox_id='$box_id'" );
		for( $i = 0; $i < sizeof( $content_array ); $i++ ) {
			$type_id = intval( $content_array[$i]['type_id'] );
			// content must be escaped before being added to the DB (fix for single quotes)
			$content = $wpdb->escape( $content_array[$i]['content'] );
			$callout_id = intval( $content_array[$i]['callout_id'] );
			//re-adding the contents of the box
			$success = $wpdb->query( 'INSERT INTO ' . PBOX_ITEM_TABLE . " (pbox_id,type_id,content,sort_order,callout_id) 
									VALUES ('$box_id','$type_id','$content','$i','$callout_id')" );
			// returning an empty boolean on SQL error. Why and how?
			if( $success === FALSE ) {
				$valid = FALSE;
			}
		}
		return $valid;
	}

	/**
	* Updates presentation box with time and author of last modification
	*
	* @param string $box_id
	* @return mixed Result of insert
	*/
	function update_info( $box_id ) {
		global $wpdb, $user_ID;
		get_currentuserinfo(); //populate the global $user_ID
		$ct = time();
		$box_id = intval( $box_id );
		return $wpdb->query( 'UPDATE ' . PBOX_TABLE . " 
										SET last_update_by='$user_ID',last_update_time='$ct'
										WHERE pbox_id='$box_id'" );
	}

	/**
	* Get number of items in PBox
	*
	* @param int $box_id ID of box
	* @return int Number of items in PBox
	*/
	function get_num_items( $box_id ) {
		global $wpdb;
		$box_id = intval( $box_id );
		return $wpdb->query( 'SELECT sort_order FROM ' . PBOX_ITEM_TABLE . "
										WHERE pbox_id='$box_id'", ARRAY_A );
	}

	/**
	* Count all rows in pbox table
	*
	* @return the number of rows
	*/
	function count_boxes() {
		global $wpdb;
		$contents = $wpdb->get_results( 'SELECT COUNT(*) 
															FROM ' . PBOX_TABLE, ARRAY_N );
		return $contents[0][0];
	}

	/**
	* Get all rows in pbox table as an associative array
	*
	* @return 2DArray of boxes
	*/
	function get_boxes() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . PBOX_TABLE . '
													ORDER BY pbox_id asc', ARRAY_A );
	}

	/**
	* Get all rows in pbox table as an associative array
	*
	* @param int $lower_bound
	* @param int $paged
	* @return 2DArray of boxes
	*/
	function get_boxes_bound( $lower_bound = 0, $paged = 30 ) {
		global $wpdb;
		$lower_bound = intval( $lower_bound );
		$paged = intval( $paged );
		return $wpdb->get_results( 'SELECT * FROM ' . PBOX_TABLE . "
												ORDER BY pbox_id desc
												LIMIT $lower_bound, $paged ", ARRAY_A );
	}

	/**
	* Get base URL for presentation boxes
	*
	* @param string $filename is a string containing the filename to acess
	* @return string URL to be used
	*/
	function get_base_url( $filename = '' ) {
		return PBOX_PLUGIN_URL . $filename;
	}

	/**
	* Get admin URL for presentation boxes
	*
	* @param string plugin page (e.g. pb.edit.php)
	* @param string $url_args, already formated (e.g. &arg1=value1&arg2=value2)
	* @return string URL to be used
	*/
	function get_admin_url( $plugin_page, $url_args ) {
		return 'admin.php?page=' . $plugin_page . $url_args;
	}

	/**
	* Displays link given ID of a visible link
	* Sanitize input BEFORE using this function!
	*
	* @param int $link_id
	* @param int $truncate_amount, will truncate the link title
	* @return bool true if link found, false if link not found
	*/
	function display_link( $link_id = 0, $truncate_amount = 0 ) {
		global $wpdb;
		$link_id = intval( $link_id );
		if ( $link_result = $wpdb->get_results( "SELECT link_id,link_url,link_name,link_description
																	FROM $wpdb->links
																	WHERE link_id='$link_id' and link_visible='Y'" ) ) {
			$lid = $link_result[0]->link_url;
			// If the link is a relative internal link
			if( !preg_match( '/^https?:\/\//', $lid ) )
				// Convert to an absolute internal link
				$lid = get_bloginfo( 'url' ) . ( $lid{0} != '/' ? '/' : '' ) . $lid;
			if ( preg_match( '/\?tiny_id=[0-9]+$/', $lid ) ) {
				if  (class_exists( 'Lilurl' ) ) { // Check for the presence of the tinyurl plugin. If it exists, grab the real URL. // Maybe add a hook to lil url?
					$lilurl = new Lilurl( $wpdb->prefix );
					//get URL from the tiny id substring
					$lid = $lilurl->get_url( substr( $lid, strpos( $lid, "?tiny_id=" ) + 9 ) );
				}
			}
			if ( $truncate_amount ) { // Take the first truncate_amount chars of the link name and tack on "..."
				if ( strlen( $link_result[0]->link_name ) > $truncate_amount ) {
					$link_result[0]->link_name = substr( $link_result[0]->link_name, 0, $truncate_amount ) . '...';
				}
			}
			echo "<a target='_blank' href='{$lid}' title='". sprintf( __( "Link with ID of %d", 'pb' ), $link_id ) . "'>{$link_result[0]->link_name}</a> " . self::iconit( $link_result[0]->link_url );
			return true;
		} else {
			return false;
		}
	}

	/**
	* Displays link to an internal page.
	* Sanitize input BEFORE using this function!
	*
	* @param int $page_id
	* @return bool True if page found, false otherwise
	*/
	function display_page( $page_id = 0, $truncate_amount = 0 ) {
		global $wpdb;
		$page_id = intval( $page_id );
		$checkpage = $wpdb->get_results( "SELECT post_type
																FROM $wpdb->posts
																WHERE ID='$page_id'" );
		$page = get_page( $page_id, ARRAY_A );

		if ( $id = $page['ID'] ) {
			if ( $checkpage[0]->post_type == 'page' || $checkpage[0]->post_type == 'post' ) {
				edit_post_link( __( 'Edit', 'pb' ), '', '&nbsp;&nbsp;|&nbsp;&nbsp;', $page['ID'] );
				$url = get_permalink( $id );
			} else {
				$url = wp_get_attachment_url( $id );
				$url = substr( $url, strpos( $url, 'wp-content' ) );
				$url = get_bloginfo( 'url' ) . ( $url{0} != '/' ? '/' : ' ') . $url;
			}
			if ( $truncate_amount && strlen( $page['post_title'] ) > $truncate_amount ) {
				$page['post_title'] = substr( $page['post_title'], 0, $truncate_amount ) . "...";
			}
			echo "<a target='_blank' href='{$url}' title='". __( 'Preview this item', 'pb' ) . "'>" . __( 'Preview', 'pb' ) . "</a> ".self::iconit( $url )."&nbsp;&nbsp;|&nbsp;&nbsp;{$page['post_title']}";
			return true;
		} else {
			return false;
		}
	}
	/**
	* Displays the PBox
	*
	* @param int $box_id - ID of box
	* @param string $style_id - ID of box
	* @return void
	*/
	function display_box( $box_id, $style_id ) {
		$box_id = intval( $box_id );
		$style_id = esc_attr( $style_id );
		self::fetch_box_data( $box_id, $style_id );
		self::display_box_top();
		self::display_box_items();
		self::display_box_bottom();
	}

	/**
	* Fetches the box data for use with the display functions;
	* populates the global variable $box_data for each box/style combo
	*
	* @param int $box_id - ID of box
	* @param string $style_id - ID of box
	* @return void
	*/
	function fetch_box_data( $box_id, $style_id ) {
		global $wpdb, $box_data;
		$box_id = intval( $box_id );
		$style_id = esc_attr( $style_id );
		$box_data['style_data'] = self::get_style_data( $style_id );
		if ( $box_data['style_data'] == -1 ) {
			wp_die( printf( __( '<strong>ERROR:</strong> Style %s does not exist.', 'pb' ), $style_id ) );
		}
		//STRIP SLASHES FROM THESE
		$box_data['box_data'] = self::get_box( $box_id );
		$box_data['items'] = self::get_contents( $box_id );
		foreach( (array) $box_data['box_data']  as $b_key => $b_data ) {
			if( is_array( $box_data['box_data'] ) ) {
				$box_data['box_data'][$b_key] = stripslashes( $b_data);
			}
		}
		foreach( (array) $box_data['items'] as $b_key => $b_data ) {
			foreach( (array) $b_data as $key => $val ) {
				$b_data[$key] = stripslashes( $val );
			}
			$box_data['items'][$b_key] = $b_data;
		}

		// Replace all %WP_OPTION_name% variables
		$str = implode( "|glue|", $box_data['style_data'] );
		$numMatches = preg_match_all( "/%WP_OPTION_(.*?)%/", $str, $tmpAry );

		foreach ( (array) $tmpAry[1] as $optName ){
			$str = preg_replace( "/%WP_OPTION_" . $optName . "%/", get_option( $optName ), $str );
		}
		$ary = explode( "|glue|", $str );
		$box_data['style_data'] = $ary;
	}

	/**
	* Fetches item options
	*
	* @param int $item_id - ID of the item
	* @param string $style_data_index - The index of the desired style data field in $box_data['style_data']
	*
	* @return array - key => option name, value => option value
	*/
	function fetch_item_options( $item_id, $style_data_index ) {
		global $box_data;
		$numMatches = preg_match_all( "/%ITEM_POSTMETA_(.*?)%/", $box_data['style_data'][$style_data_index], $tmpAry );
		$item_options = array();
		foreach ( (array) $tmpAry[1] as $optName ) {
			$item_options[$optName] = get_post_meta( $item_id, $optName, TRUE );
		}
		return $item_options;
	}

	/**
	* Get the URL of the thumbnail associated with the post/page.
	* Supports WordPress 2.9 or selects the first image associated
	* to a post/page in WordPress 2.8.
	*
	* @param int $parent_id - The ID of the thumbnail's parent
	* @param string $size - the size of the thumbnail to be displayed in the PBox
	* @param bool $return - returns boolean when true, void otherwise.
	* @return mixed - returns either boolean or void
	*/
	function get_image_url( $parent_id, $size='thumbnail', $return = false ) {
		$parent_id = intval( $parent_id );
		$size = esc_attr( $size );
		// If the 2.9 backport or 2.9 are active:
		if( function_exists( 'get_post_image_id' ) ) {
			$attachment_id = get_post_image_id( $parent_id );
			$result = wp_get_attachment_image_src( $attachment_id, $size );
			$result = $result[0];
		// Otherwise use the first image or the page_header_image
		} else {
			$images =& get_children( 'post_parent=' . $parent_id .'&post_type=attachment&post_mime_type=image&order=ASC&orderby=menu_order' );
			if ( is_array( $images ) ) {
				foreach ( (array) $images as $attachment_id => $attachment ) {
					$result = wp_get_attachment_image_src( $attachment_id, $size );
					$result = $result[0];
					break;
				}
			}
			if ( !isset( $result ) ) {
				$result= get_post_meta( $parent_id, 'page_header_image', true );
				if ( empty( $result ) ) {
					$result = false;
				}
			}
		}
		if ( $return ) {
			return $result;
		} else if ( $result !== false ) {
			echo $result;
		}
	}

	/**
	* Fetches page image
	*
	* @param int $item_id - ID of the item
	* @param string $style_data_index - The index of the desired style data field in $box_data['style_data']
	*
	* @return array - key => option name, value => option value
	*/
	function fetch_page_image( $item_id, $style_data_index ) {
		global $box_data;
		$item_id = intval( $item_id );
		$style_data_index = esc_attr( $style_data_index );
		$numMatches = preg_match_all( "/%PAGE_IMAGE_(.)%/", $box_data['style_data'][$style_data_index], $tmpAry );

		$item_options = array();

		foreach ( (array) $tmpAry[1] as $optName ) {
			switch ( $optName ) {
				case 'F': $size= 'full'; break;
				case 'L': $size= 'large'; break;
				case 'M': $size= 'medium'; break;
				case 'T': // fallthrough
				default:
					$size = 'thumbnail';
			}
			$optValue = self::get_image_url( $item_id, $size, TRUE );
			if ( $optValue === false ) {
				$optValue= '';
			}
			$item_options[$optName] = $optValue;
		}
		return $item_options;
	}

	/**
	* Displays the 'top' portion of the PBox
	*
	* @return void
	*/
	function display_box_top() {
		global $wpdb, $box_data;
		$style_data = $box_data['style_data'];
		$box_top_template = $style_data[1];
		$pbox_data = $box_data['box_data'];
		$items = $box_data['items'];
		$first_item_title = '';
		$first_item_url = '';
		$first_item_link = '';
		$link_image = '';
		$first_item_excerpt = '';
		$box_link_template = '';
		
		if ( is_array( $items ) || is_object( $items ) ) {
			// Get the first item
			reset( $items );
			$item = $items[key( $items )];
			if ( $item['type_id'] == PBOX_TYPE_TEXT ) {
				// Replace variables in template
				$first_item_content = $item['content'];
			} elseif ( $item['type_id'] == PBOX_TYPE_PAGE ) {
				$page_id = $item['content'];
				$page = get_post( $page_id, ARRAY_A );
				if( !is_user_logged_in() && $page['post_status'] == 'draft' ) {
					return;
				}
				// Page type
				$page_type =self::get_page_type( $page_id );
				if ( $page_type != -1 ) {
					// Item title
					$first_item_title = $page['post_title'];
					// Item excerpt
					if ( isset( $page['post_excerpt'] ) ) {
						$first_item_excerpt = trim($page['post_excerpt']);
					} 
					// If excerpt not stored in $post->post_excerpt, check content for <!--more--> tag
					if ( empty( $first_item_excerpt ) ) {
						if( ( $split = strpos( $page['post_content'], '<!--more-->' ) ) !== false ) {
							$first_item_excerpt= substr( $page['post_content'], 0, $split );
						} else {
							$first_item_excerpt = substr( $page['post_content'], 0, 500 ); // If no excerpt is specified, use the first 500 chars
							$dotPos= strrpos( $first_item_excerpt, '.' );
							if ( $dotPos !== false ) {
								$item_excerpt = substr( $first_item_excerpt, 0, $dotPos + 1 );
							}
						}
						$first_item_excerpt= do_shortcode( wpautop( force_balance_tags( $first_item_excerpt ), true ) );
					}
					// Item link
					$first_item_url = get_permalink( $page_id );
					$first_item_link = '<a href="' . $first_item_url . '" title="' . strip_tags( strip_shortcodes( $first_item_excerpt ) ) . '">' . $first_item_title . '</a> ';
					if ( !isset( $page['post_excerpt'] ) ) {
						$first_item_excerpt = wpautop( $first_item_excerpt, true );
					}
					// Item content
					$first_item_content = do_shortcode( wpautop( $page['post_content'], true ) );
					//Item options
					$first_item_options = self::fetch_item_options( $page_id, 1 ); // the '1' indicates the index into the box_data['style_data'] array, 1 is the box_top field
					// Image
					$first_item_image = self::fetch_page_image($page_id, 1);
				}
			} elseif ( $item['type_id'] == PBOX_TYPE_LINK ) {
				$link_id = $item['content'];
				$link_data = get_bookmark( $link_id, ARRAY_A );
				if ( $link_data['link_id'] != 0 ) {
					$first_item_title = $link_data['link_name'];
					$first_item_url = $link_data['link_url'];
					// If the bookmark is a relative internal link
					if( !preg_match('/^https?:\/\//', $first_item_url ) ) {
						// Convert to an absolute internal link
						$first_item_url = get_bloginfo( 'url' ) . ( $first_item_url{0} != '/' ? '/' : '' ) . $first_item_url;
					}
					$first_item_excerpt = $link_data['link_description'];
				}
				// Tiny URL support
				if ( preg_match( '/\?tiny_id=[0-9]+$/', $first_item_url ) ) {
					if  (class_exists( 'Lilurl' ) ) { // Check for the presence of the tinyurl plugin. If it exists, grab the real URL. // Maybe add a hook to lil url?
						$lilurl = new Lilurl( $wpdb->prefix );
						$first_item_url = $lilurl->get_url( substr( $first_item_url, strpos( $first_item_url, "?tiny_id=" ) + 9 ) );
					}
				}
				$first_item_link = '<a href="' . $first_item_url . '" title="' . strip_tags( $first_item_excerpt ) . '">' . $first_item_title . '</a>';
				// Replace variables in template
				switch( $item['callout_id'] ) {
					case 0:
						$template = $box_link_template;
						break;
					case 1:
						$template = $box_link_ss_template;
						break;
					default:
						$template = $box_link_template;
				}
			}
		}
		// Replace variables in style template
		$box_top_template = preg_replace( "/%PBOX_TITLE%/", $pbox_data['title'], $box_top_template );
		$box_top_template = preg_replace( "/%ITEM_TITLE%/", $first_item_title, $box_top_template );
		$box_top_template = preg_replace( "/%ITEM_URL%/", $first_item_url, $box_top_template );
		$box_top_template = preg_replace( "/%ITEM_LINK%/", $first_item_link, $box_top_template );
		$box_top_template = preg_replace( "/%ITEM_EXCERPT%/", $first_item_excerpt, $box_top_template );
	
		if ( isset( $item ) && $item['type_id'] == PBOX_TYPE_PAGE ) { // Replace PAGE-type specific variables
			$box_top_template = preg_replace( "/%ITEM_CONTENT%/", $first_item_content, $box_top_template );
			// Replace item options
			foreach( (array) $first_item_options as $optName => $optValue ) {
				$box_top_template = preg_replace( "/%ITEM_POSTMETA_" . $optName . "%/", $optValue, $box_top_template );
			}
		}
		echo $box_top_template;
	}

	/**
	* Displays the items of the PBox
	*
	* @return void
	*/
	function display_box_items() {
		global $wpdb;
		global $box_data;
		$style_data = $box_data['style_data'];
		$pbox_data = $box_data['box_data'];
		$items = $box_data['items'];
		$box_link_template = $style_data[2];
		$box_link_ss_template = $style_data[3];
		$box_post_template = $style_data[4];
		$box_post_ss_template = $style_data[5];
		$box_post_sc_template = $style_data[6];
		$box_text_template = $style_data[7];
		$item_title = '';
		$item_url = '';
		$item_link = '';
		$item_excerpt = '';
		$item_content = '';
		$item_target = '_none'; //default to no target. Will be overwritten if this has been defined as necessary
		$link_image= '';
		
		if ( is_array( $items ) || is_object( $items ) ) {
			foreach ( (array) $items as $item ) {
				if ( $item['type_id'] == PBOX_TYPE_TEXT ) {
					// Replace variables in template
					$template = preg_replace( "/%PBOX_TITLE%/", $pbox_data['title'], $box_text_template );
					$template = preg_replace( "/%ITEM_CONTENT%/", $item['content'], $template );
				} elseif ( $item['type_id'] == PBOX_TYPE_PAGE ) {
					$page_id = $item['content'];
					$page = get_post( $page_id, ARRAY_A );
					/**
					* If the item is a page or post in draft/pending status 
					* and the user is not logged in it should not appear.
					*/
					if( !is_user_logged_in() && $page['post_status'] == 'draft' || $page['post_status'] == 'pending' ) {
						return;
					}
					// Page type
					$page_type = self::get_page_type( $page_id );
					if ( $page_type != -1 ) {
						// Item title
						$item_title = $page['post_title'];
						// Item URL
						if ( $page_type == 'page' || $page_type == 'post' ) {
							$item_url = get_permalink( $page_id );
						} else { // Attachment
							$type_url = wp_get_attachment_url( $page_id );
							$item_url = $type_url;
							$item_url = substr( $item_url, strpos( $item_url, 'wp-content' ) );
							$item_url = get_bloginfo('url') . '/' . $item_url;
							$link_image = self::iconit( $type_url );
						}
						// Item excerpt
						if ( isset($page['post_excerpt']) ) {
						$item_excerpt = trim($page['post_excerpt']);
						}
						// If excerpt not stored in $post->post_excerpt, check content for <!--more--> tag
						if ( empty( $item_excerpt ) ) {
							if( ( $split = strpos( $page['post_content'], '<!--more-->' ) ) !== false ) {
								$item_excerpt= substr( $page['post_content'], 0, $split );
							} else {
								$item_excerpt = substr( $page['post_content'], 0, 500 ); // If no excerpt is specified, use the first 500 chars
								$dotPos= strrpos($item_excerpt, '.');
								if ($dotPos !== false) {
									$item_excerpt = substr($item_excerpt, 0, $dotPos+1);
								}
							}
							$item_excerpt = do_shortcode( wpautop( force_balance_tags( $item_excerpt ), true ) );
						}
						// Item link
						$item_link = '<a href="' . $item_url . '" title="' . strip_tags( $item_excerpt ).'...' . '">' . $item_title . '</a> ';
						if( isset( $link_image ) ) {
							echo $item_link .= $link_image;
						}
						if ( isset( $page['post_excerpt'] ) ) {
							$item_excerpt= wpautop( $item_excerpt, true );
						}
						//Item content
						$item_content = do_shortcode( wpautop( $page['post_content'], true ) );
						// Select the appropriate style field
						switch( $item['callout_id'] ) {
							case 0:
								$template = $box_post_template;
								$style_index = 4; // Index into box_data['style_data'], for use with fetch_item_options
								break;
							case 1:
								$template = $box_post_ss_template;
								$style_index = 5;
								break;
							case 2:
								$template = $box_post_sc_template;
								$style_index = 6;
								break;
							default:
								$template = $box_post_template;
								$style_index = 4;
						}
						//Item options
						$item_options = self::fetch_item_options( $page_id, $style_index );
						// Image
						$item_images = self::fetch_page_image( $page_id, $style_index );
					}
				} elseif ( $item['type_id'] == PBOX_TYPE_LINK ) {
					$link_data = get_bookmark( $item['content'], ARRAY_A );
					if ( $link_data['link_id'] != 0 ) {
						$item_target = $link_data['link_target'];
						$item_title = $link_data['link_name'];
						$item_url = $link_data['link_url'];
						// If the bookmark is a relative internal link
						if( !preg_match( '/^https?:\/\//', $item_url ) ) {
							// Convert to an absolute internal link
							$item_url = get_bloginfo( 'url' ) . ( $item_url{0} != '/' ? '/' : '' ) . $item_url;
						}
						$link_image = self::iconit( $item_url );
						$item_excerpt = $link_data['link_description'];
					}
					if ( preg_match( '/\?tiny_id=[0-9]+$/', $item_url ) ) {
						if ( class_exists( 'Lilurl' ) ) { // Check for the presence of the tinyurl plugin. If it exists, grab the real URL
							$lilurl = new Lilurl( $wpdb->prefix );
							$item_url = $lilurl->get_url( substr( $item_url, strpos( $item_url, "?tiny_id=" ) + 9 ) );
							$link_image = self::iconit( $item_url );
						}
					}
					// Item link
					$item_link = '<a href="' . $item_url . '" title="' . strip_tags( $item_excerpt ) . '" target="' . $item_target . '">' . $item_title . '</a> ' . $link_image;
					// Replace variables in template
					switch( $item['callout_id'] ) {
						case 0:
							$template = $box_link_template;
							break;
						case 1:
							$template = $box_link_ss_template;
							break;
						default:
							$template = $box_link_template;
					}
				}
				// PAGE
				$edit_image = '';
				if( isset( $item ) && $item['type_id'] == PBOX_TYPE_PAGE && pbox_check_capabilities() ) {
						ob_start();
						edit_post_link( '<img style="height:12px;width:12px;float:right;" src="' . PBOX_PLUGIN_URL . 'images/page_edit.png" alt="' . __( 'Edit this content', 'pb' ) .'" />', '', '', $page_id );
						$edit_image = ob_get_contents();
						ob_end_clean();
				}
				$template = preg_replace( "/%PBOX_TITLE%/", $pbox_data['title'], $template );
				$template = preg_replace( "/%ITEM_TITLE%/", $item_title, $template );
				$template = preg_replace( "/%ITEM_URL%/", $item_url, $template );
				$template = preg_replace( "/%ITEM_LINK%/", $item_link, $template );
				$previous_template = $template;
				$template = preg_replace( "/%ITEM_EXCERPT%/", $edit_image . $item_excerpt , $template );
				if( $previous_template != $template ) {
					$edit_image = '';
				}
				if ( isset( $item ) && $item['type_id'] == PBOX_TYPE_PAGE ) { // Replace PAGE-type specific variables
					$template = preg_replace( "/%ITEM_CONTENT%/", $edit_image . $item_content, $template );
					// Replace item images
					foreach( (array) $item_images as $optName => $optValue ) {
						$template = preg_replace( "/%PAGE_IMAGE_" . $optName . "%/", $optValue, $template );
					}
					// Replace item options
					foreach( (array) $item_options as $optName => $optValue ) {
						$template = preg_replace( "/%ITEM_POSTMETA_" . $optName . "%/", $optValue, $template );
					}
				}
				echo $template;
				unset( $link_image );
			}
		}
	}

	/**
	* Displays the 'bottom' portion of the PBox
	*
	* @return void
	*/
	function display_box_bottom() {
		global $box_data;
		$box_id = $box_data['box_data']['pbox_id'];
		$items = $box_data['items'];
		if( pbox_check_capabilities() && $box_id > 0 ) {
			echo "<p style='font-size:smaller;font-weight:bold'><a href='" . wp_nonce_url( 'wp-admin/'.self::get_admin_url( "pbox/pb.edit.php", "&action=edit_view&box_id=$box_id" ), "pbox-box-edit" ) . "' rel='permalink'>".__( 'Edit this PBox', 'pb' )."</a>";
		}
		if ( is_array( $items ) || is_object( $items ) ) {
			// Get the first item
			reset( $items );
			$item = $items[key( $items )];
			if ( $item['type_id'] == PBOX_TYPE_PAGE ) {
				$page_id = $item['content'];
				$page = get_post( $page_id, ARRAY_A );
				// If the page is a draft, warn the user the page will not appear in the pbox
				if( $page['post_status'] == 'draft' ) {
					if( is_user_logged_in() ) {
						echo "<p style='background-color: #ffebe8;border: solid #c00 1px;padding:5px;'><strong>".__( "NOTE: </strong> <br/>This presentation box contains one element or more in 'draft' status. It will not appear to non-logged in users.", 'pb' ) ."</p>";
					}
					else {
						return;
					}
				}
			}
		}
		$style_data = $box_data['style_data'];
		$pbox_data = $box_data['box_data'];
		if( isset( $pbox_data[1] ) ) {
			$data = $pbox_data[1];
		} else {
			$data = '';
		}
		// Replace variables in style template and return
		echo preg_replace( "/%PBOX_TITLE%/", $data, $style_data[8] );
	}

	/**
	* Gets list of style names from database
	*
	* @return array containing style names
	*/
	function get_styles() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT style_id FROM ' . PBOX_STYLE_TABLE, ARRAY_A );
	}

	/**
	* Get all of the style data of the given style ID
	* Sanitize input BEFORE using this function!
	*
	* @param string $style_id
	* @return array of strings or -1 if style does not exist
	*/
	function get_style_data( $style_id ) {
		global $wpdb;
		$style_id = esc_attr( $style_id );
		if ( !self::verify_input( $style_id ) ) {
			wp_die( __( '<strong>ERROR:</strong> Invalid style ID.', 'pb' ) );
		}
		$style_data = $wpdb->get_results( 'SELECT * FROM ' . PBOX_STYLE_TABLE . "
																WHERE style_id='$style_id'", ARRAY_N);
		if ( count( $style_data ) < 1 ) {
			return -1;
		}
		return $style_data[0];
	}

	/**
	* Creates a new PBox style
	* Sanitize input BEFORE using this function!
	*
	* @param string $style_id
	* @return void
	*/
	function create_style( $style_id ) {
		global $wpdb;
		$style_id = esc_attr( $style_id );
		// Check if style name already exists
		if ( self::get_style_data( $style_id ) != -1 ) {
			return new WP_Error( 'duplicate_style', __( '<strong>ERROR:</strong> Style ID already exists.', 'pb' ) );
		}
		/* Add to database */
		$wpdb->query( 'INSERT INTO ' . PBOX_STYLE_TABLE . " 
								VALUES ('$style_id','', '', '', '', '', '', '', '')" );
	}

	/**
	* Updates a pbox style in the style table and then calls
	* an auxiliary function to handle the CSS styling changes
	*
	* @param string $style_id
	* @param array $content_array contains the style data fields
	* @return void
	*/
	function update_style( $style_id, $content_array ) {
		global $wpdb;
		$style_id = $wpdb->escape( $style_id );
		// Update template in database
		$wpdb->query("UPDATE ". PBOX_STYLE_TABLE . " SET
									above_items_template = '{$content_array[0]}' , 
									links_template = '{$content_array[1]}' , 
									links_excerpt_template = '{$content_array[2]}' , 
									posts_template = '{$content_array[3]}' , 
									posts_excerpt_template = '{$content_array[4]}' , 
									posts_content_template = '{$content_array[5]}' , 
									text_template = '{$content_array[6]}' , 
									below_items_template = '{$content_array[7]}'
								WHERE
									style_id = '$style_id'"
								);
		// Update CSS file
		self::update_style_css( $style_id, $content_array[8] );
	}

	/**
	* Deletes a PBox style style information within
	* the style table itself and calls an auxiliary function
	* to get rid of the CSS for the style.
	*
	* @param string $style_id
	* @return void
	*/
	function delete_style( $style_id ) {
		global $wpdb;
		$style_id = esc_attr( $style_id );
		// delete template in database
		$wpdb->query( 'DELETE FROM ' . PBOX_STYLE_TABLE . "
								WHERE style_id='$style_id'" );
		// delete section from the CSS file
		self::delete_style_css( $style_id );
	}

	/**
	* Returns the locaton of the current theme where
	* the writable CSS file is located.
	*
	* @return string - the location of the theme
	*/
	function get_theme_location() {
		return  str_replace( '\\', '/', ( get_theme_root() . '/' . get_template() ) );
	}

	/**
	* Alternative to PHP's is_writable since it only checks if a file
	* is read-only on Windows, not whether or not it is actually writable. This 
	* function can probably be removed  and instances of its use changed back 
	* to is_writable in PHP 5.3 as the bug is supposedly fixed.
	*
	* @param string $path - The path to check for writeability
	* @return bool - True if writable, false otherwise
	*
	*/
	function pb_is_writable( $path ) {
		if ( $path{ strlen( $path ) - 1 } == '/' ) {
			return self::pb_is_writable( $path . uniqid( mt_rand() ) . '.tmp' );
		}
		if ( file_exists( $path ) ) {
			if ( !( $f = @fopen( $path, 'r+' ) ) ) {
				return false;
			}
			fclose( $f );
			return true;
		}
		if ( !( $f = @fopen( $path, 'w' ) ) ) {
			return false;
		}
		fclose( $f );
		unlink( $path );
		return true;
	}
	
	/**
	* Wrapper function mimicking the naming for PHP's native is_writable functions
	*
	* @param string $path - The path to be checked for writability
	* @return bool - true if writable, flase otherwise
	*/
	function pb_is_writeable( $path ) {
		return self::pb_is_writable( $path );
	}

	/**
	* Pulls the CSS sections from the saved file to display on the editing page
	* for styles. Returns an array of CSS element if the directory and file are 
	* writable, returns an empty string if the file doesn't yet exist or is empty and
	* returns false if the CSS file and/or directory are not writable.
	*
	* @return array - returns an array of all PBox associated CSS fragments
	*/
	function load_style_css() {
		$style_dir = self::get_theme_location();
		$css_file_data = '';
		/**
		* Note that nothing is loaded if the CSS file cannot be written.
		* This function sets the content of the CSS file to the styles edit
		* page for modification. This is not necessary if the directory or 
		* file is not writable as that field will be deactivated.
		*/
		if ( !self::pb_is_writable( $style_dir . '/pbox.css' ) ) {
			if ( file_exists( $style_dir . '/pbox.css' ) ) {
				return false;
			} else {
				// If the directory is not writable (no new file can be written
				if( !self::pb_is_writable( $style_dir . '/' ) ) {
					return false;
				}
			}
		} else {
			// If the file exists and is writable
			if ( file_exists( $style_dir . '/pbox.css' ) ) {
				$css_file_data = file_get_contents( $style_dir . '/pbox.css' );
			}
		}
		/**
		* The 182 is added in an attempt to make the separator unique so 
		* that user CSS is not interpreted as a separator.
		*/
		return explode( '/*---separator--182---*/', $css_file_data );
	}

	/**
	* Determines the order in which the CSS section appears in the CSS file
	* with respect to the sections generated by other styles
	*
	* @param string $style_id
	* @param array $css_sections
	* @return int - the order of the CSS chunks as they appear in the document
	*/
	function find_style_index( $style_id, $css_sections ) {
		$style_id = esc_attr( $style_id );
		// Skip entry 0 - it is always empty
		for( $i = 1; $i < count( $css_sections ); $i++ ) { 
			if ( preg_match( ":^/\*BEGIN $style_id\*/:", $css_sections[$i] ) ) {
				return $i;
			}
		}
	}

	/**
	* Save the updates to the style to the CSS file if possible
	* throws an error if there is a filesystem issue.
	*
	* @param string $css
	* @return void
	*/
	function save_style_css( $css ) {
		/**
		* the 182 is added in an attempt to make the separator unique so 
		* that user CSS is not interpreted as a separator.
		*/
		$css = implode( '/*---separator--182---*/', $css );
		$style_dir = self::get_theme_location();	
		// Ensure there is something to write before checking the filesystem
		if( strlen( $css ) > 0 ) {
			/**
			* Update CSS timestamp (this is used to force browsers to refresh 
			* the stylesheet in the cache when it has changed)
			*/
			update_option( 'pb_csstimestamp', time() );
			if ( !self::pb_is_writable( $style_dir . '/pbox.css' ) ) {
				if ( file_exists( $style_dir . '/pbox.css' ) ) {
					// If the file exists and isn't writable
					return new WP_Error( 'css_unwritable', sprintf( __( '<strong>ERROR:</strong> %s/pbox.css is not writable.', 'pb' ), $style_dir ) );
				} else {
					if( !self::pb_is_writable( $style_dir . '/' ) ) {
						// If the directory isn't writable (new file can't be created)
						return new WP_Error( 'dir_unwritable', sprintf( __( '<strong>ERROR:</strong> %s/ is not writable.', 'pb' ), $style_dir ) );
					} else {
						// If the file doesn't exist and the directory is writable, make it.
						file_put_contents( $style_dir . '/pbox.css', $css );
					}
				}
			} else {
				// if writable and existing, just append the new CSS
				file_put_contents( $style_dir . '/pbox.css', $css );
			} 
		}
	}
	
	/**
	* Gets the CSS portion of a PBox style from your
	* currently active theme directory. Note that the file
	* must be in that file and the name must remain 
	* unchanged.
	*
	* @param string $style_id
	* @return string - returns the CSS chunk for style_id's style
	*/
	function get_style_css( $style_id ) {
		$style_id = esc_attr( $style_id );
		$css_sections = self::load_style_css();
		if( false !== $css_sections ) {
			$cur_index = self::find_style_index( $style_id, $css_sections );
			if ( !isset($cur_index) || $cur_index == -1 ) { // Check if style has a section in the CSS file yet
				return '';
			}
			return substr( $css_sections[$cur_index], strlen( '/*BEGIN ' . $style_id . '*/' ) );
		}
		return '';
	}

	/**
	* Updates the CSS portion of a PBox style
	*
	* @param string $style_id
	* @param string $css
	* @return void
	*/
	function update_style_css( $style_id, $css ) {
		$style_id = esc_attr( $style_id );
		$css_sections = self::load_style_css();
		if( false !== $css_sections ) {
			$cur_index = self::find_style_index( $style_id, $css_sections );
			// Check if style has a section in the CSS file yet
			if ( $cur_index == -1 ) { 
				// If not, create section
				$css_sections[] = '/*BEGIN ' . $style_id . '*/';
				$cur_index = count( $css_sections ) - 1;
			}
			$css_sections[$cur_index] = '/*BEGIN ' . $style_id . '*/' . $css;
			self::save_style_css( $css_sections );
		}
	}

	/**
	* Deletes the CSS portion of a PBox style
	*
	* @param string $style_id
	* @return void
	*/
	function delete_style_css( $style_id ) {
		$style_id = esc_attr( $style_id );
		$css_sections = self::load_style_css();
		if( false !== $css_sections ) {
			$cur_index = self::find_style_index( $style_id, $css_sections );
			if ( $cur_index == -1 ) { // Check if style has a section in the CSS file
				// If not, we're done
				return;
			}
			self::array_delete( $css_sections, "$cur_index" );
			self::save_style_css( $css_sections );
		}
	}

	/**
	* Updates the usage table with new style info. 
	*
	* @param int $page_id
	* @param int $box_id
	* @param int $slot_id
	* @param string $style_id
	* @return void
	*/
	function update_style_usage( $page_id, $box_id, $slot_id, $style_id ) {
		global $wpdb;
		$page_id = intval( $page_id );
		$box_id = intval( $box_id );
		$style_id = esc_attr( $style_id );
		$slot_id = intval( $slot_id );
		$wpdb->query( 'UPDATE '.PBOX_USAGE_TABLE." 
								SET style_id='$style_id' 
								WHERE page_id='$page_id' 
								AND pbox_id='$box_id' 
								AND slot_id='$slot_id'" );
	}

	/**
	* Updates the usage table with ALL information. Added
	* for compatibility with the new 2.8 widget WPI.
	*
	* @param int $page_id
	* @param int $box_id
	* @param string $style_id
	* @param int $instance_id
	* @return void
	*/
	function update_usage( $page_id, $box_id, $style_id, $instance_id ) {
		global $wpdb;
		$page_id = intval( $page_id );
		$box_id = intval( $box_id );
		$style_id = esc_attr( $style_id );
		$instance_id = intval( $instance_id );
		// If it's already in the database, remove it (safeguard to avoid duplicates)
		if( $wpdb->get_var( 'SELECT slot_id FROM '.PBOX_USAGE_TABLE."
									WHERE slot_id = '$instance_id' AND page_id='$page_id' " ) ) {
			self::remove_box_usage( $page_id , $instance_id );
		}
		// Add the box usage to the usage table with the new information.
		self::add_box_usage( $page_id , $box_id, $instance_id );
		// Assign the style usage appropriately
		self::update_style_usage( $page_id, $box_id, $instance_id, $style_id );
	}

	/**
	* Check user input for illegal characters. Returns 1 if 
	* input is legal. Legal characters are  a-z A-Z 0-9 - _
	*
	* @param string input
	* @return int
	*/
	function verify_input( $input ) {
		return preg_match( "/^([a-zA-Z0-9_\-]+)$/", $input );
	}

	/**
	* Get page type (post or page)
	*
	* @param int $page_id
	* @return string
	*/
	function get_page_type( $page_id ) {
		global $wpdb;
		$page_id = intval( $page_id );
		$checkpage = get_page( $page_id );
		return $checkpage->post_type;
	}

	/**
	* Get array of types.
	*
	* @return array Array of content types indexed by their type ID
	*/
	function get_type_array() {
		return array( PBOX_TYPE_LINK => __( 'link', 'pb' ), PBOX_TYPE_PAGE => __( 'page/post/file', 'pb' ), PBOX_TYPE_TEXT => __( 'text', 'pb' ) );
	}

	/**
	* Displays dropdown box (for use in a form, etc.) of the different content types, with the option to have one selected
	*
	* @param int $selected_id ID of option to be selected
	* @return void
	*/
	function display_type_dropdown( $selected_id = 0 ) {
		$selected_id = intval( $selected_id );
		foreach ( (array) self::get_type_array() as $key => $type ) {
			if ( $selected_id == $key ) {
				echo "<option value='$key' selected='selected'>$type</option>";
			} else {
				echo "<option value='$key'>$type</option>";
			}
		}
	}

	/**
	* Get pbox usage based on box ID
	*
	* @param int $box_id ID of box
	* @return array 2D Array of rows from pbox usage table
	*/
	function get_pages_using_box( $box_id ) {
		global $wpdb;
		$box_id = intval( $box_id );
		return $wpdb->get_results( 'SELECT * FROM '.PBOX_USAGE_TABLE."
													WHERE pbox_id='$box_id'", ARRAY_A );
	}

	/**
	* Get box ID based on page ID and slot ID
	*
	* @param int $page_id
	* @param int $slot_id
	* @return int ID of presentation box in that slot on the page
	*/
	function get_box_by_slot( $page_id, $slot_id ) {
		global $wpdb;
		$page_id = intval( $page_id );
		$slot_id = intval( $slot_id );
		$result = $wpdb->get_results( 'SELECT * FROM '.PBOX_USAGE_TABLE."
														WHERE page_id='$page_id'
														AND slot_id='$slot_id'", ARRAY_A );
		return $result[0];
	}

	/**
	* Deletes the specified key/value pair from an array
	*
	* @param array &$ary
	* @param string $key_to_be_deleted
	* @return void
	*/
	function array_delete( &$ary, $key_to_be_deleted ) {
		if( is_string( $key_to_be_deleted ) && isset( $ary[$key_to_be_deleted] ) ) {
			unset( $ary[$key_to_be_deleted] );
		} else
		if( is_array( $key_to_be_deleted ) ) {
			foreach( (array) $key_to_be_deleted as $del ) {
				self::array_delete( &$ary, $del );
			}
		}
	}

	/**
	* Gets HTML code for embedding an image icon based on type being linked to
	* Modified 2009-10-08 to remove deprecated POSIX in favour of PCRE
	*
	* @param string $link_url
	* @return string HTML code for embedding the icon for the link
	*/
	function iconit( $link_url ) {
		// Get additional custom icon configurations
		require( 'pb.config.php' );
		if ( preg_match( '/.\.pdf$/i', $link_url ) ) {
			$link_image = "<img style='height:12px;width:12px' class='pbox_link_icon' alt='PDF' src='".PBOX_PLUGIN_URL."images/page_white_acrobat.png' />";
		} elseif ( preg_match( '/.\.docx?$/i', $link_url ) ) {
			$link_image = "<img style='height:12px;width:12px' class='pbox_link_icon' alt='DOC' src='".PBOX_PLUGIN_URL."images/page_white_word.png' />";
		} elseif ( preg_match( '/.\.ppt$/i', $link_url ) ) {
			$link_image = "<img style='height:12px;width:12px' class='pbox_link_icon' alt='PPT' src='".PBOX_PLUGIN_URL."images/page_white_powerpoint.png'  />";
		} elseif ( preg_match( '/.\.xls$/i', $link_url ) ) {
			$link_image = "<img style='height:12px;width:12px' class='pbox_link_icon' alt='XLS' src='".PBOX_PLUGIN_URL."images/page_white_excel.png'  />";
		} elseif ( preg_match( '/.\.ram$/i', $link_url ) ) {
			$link_image = "<img style='height:12px;width:12px' class='pbox_link_icon' alt='RAM' src='".PBOX_PLUGIN_URL."images/page_white_ram.png' />";
		} elseif ( preg_match( '/.\.wma$/i', $link_url ) ) {
			$link_image = "<img style='height:12px;width:12px' class='pbox_link_icon' alt='WMP' src='".PBOX_PLUGIN_URL."images/page_white_wma.png'  />";
		} elseif ( preg_match( '/.\.(jpg|bmp|gif|tiff|png|psd|tif|jpeg)$/i', $link_url ) ) {
			$link_image = "<img style='height:12px;width:12px' class='pbox_link_icon' alt='" . __( 'Image' ) . "' src='".PBOX_PLUGIN_URL."images/picture.png'  />";
		} else {
			//since all links are unmanaged and should hence open to a new window; internal managed pages should be using the page_id plugin
			$link_image = "<img style='height:12px;width:12px' class='pbox_link_icon' alt='WWW' src='".PBOX_PLUGIN_URL."images/world_link.png' />";
		}
		// Check the extra icons set in pb.config.php
		foreach( (array) $extra_icons as $extra ) {
			if( $extra['regex'] && $extra['filename'] && preg_match( $extra['regex'], $link_url ) ) {
				$link_image = "<img class='pbox_link_icon' alt='{$extra['name']}' src='" . PBOX_PLUGIN_URL . "images/{$extra['filename']}' />";
			}
		}
		return $link_image;
	}
}
