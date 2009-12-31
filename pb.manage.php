<?php
/**
PBox
Customizable content widgets able to display posts, pages, links and plain text in a custom style.
2.2
Authors: Aaron Berg, Dale Taylor, Nelson Lai, Yefei Tang, Xueyan Bai, Zafor Ahmed, Fran&ccedil;ois Fortin, Lindsay Newton
http://www.bankofcanada.ca/
*/

/** MAIN MANAGEMENT PAGE **/

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
 
// DEFINE CONSTANTS

define ( 'PBOX_PAGED', 20 ); // For pagination of PBox list
if ( !pbox_check_capabilities() ) {
	wp_die( __( 'You do not have sufficient permissions to access this page.', 'pb' ) );
}
$lower_bound = ( isset( $_GET['pagenum'] ) && is_numeric( $_GET['pagenum'] ) && $_GET['pagenum'] > 0 ) ? ( $_GET['pagenum'] - 1 ) * PBOX_PAGED : 0;

$messages= array(
	'box_deleted' => __( 'Successfully deleted PBox <em>%d</em>.', 'pb' ),
	'box_not_deleted' => __( 'Failed to delete PBox <em>%d</em>.', 'pb' ),
	'cloned' => __( "Created box '%s' with ID <em>%d</em>.", 'pb' ),
	'unknown_action' => __( 'Unknown action', 'pb' )
);
// EDITING AND ADDING

?>
<style type="text/css">
.alternate {
    background-color: #F5F5F5; }
</style>

<div class="wrap">	
<?php
if ( isset( $_REQUEST['message'] ) ) {
	$msgType = 'fade updated';
	$message = $_REQUEST['message'];
	if ( isset($messages[$message]) ) {
		$msgStr = $messages[$message];
		switch ( $message ) {
			case 'box_not_deleted': 
				$message = sprintf( $msgStr, intval( $_REQUEST['box_id'] ) );
				$msgType = 'error';
				break;
			case 'box_deleted':
				$message = sprintf( $msgStr, intval( $_REQUEST['box_id'] ) );
				break;
			case 'cloned':
				$message = sprintf( $msgStr, esc_html( $_REQUEST['box_new_title'] ), intval( $_REQUEST['box_new_id'] ) );
				break;
			default:
				$message = $msgStr;
		}
	?>
	<div class="<?php echo $msgType; ?>" id="message">
		<p><?php echo $message; ?></p>
	</div>
	<?php
	}
}

?>
<div id="icon-themes" class="icon32"><br /></div>
<h2><?php _e( 'Presentation Box (PBox) Management', 'pb' );?></h2>
<table width='90%'>
	<tr>
		<td width='50%' valign='top'>
		<h3 align='center'><?php _e( 'Create new presentation box', 'pb' );?></h3>
		<p><?php _e( 'To create new presentation boxes, enter the title of the new box below.', 'pb' ); ?></p>
		<form id='pbox-add-process' action="<?php echo PBox::get_admin_url( 'pbox/pb.edit.php', '&amp;action=add_process' );?>" method='post'>
		<?php wp_nonce_field( 'pbox-box-add' ); ?>
		<p><?php _e( 'Title:', 'pb' ); ?> <input type='text' name='box_title' /></p>
		<p><input type='submit' value='<?php _e( 'Create', 'pb' ) ?>' class='button' /></p>
	</form>
	</td><td width='50%' valign='top'>

  <h3 align='center'><?php _e( 'Search', 'pb' );?></h3>
	<?php
	$page = '';
	if( class_exists( 'XWidgets' ) ) {
		$page = ' page,';
	}?>
   <p><?php printf( __( 'Type in the ID of the item you wish to search for, and select what kind of item it is (box,%s content).', 'pb' ), $page );?></p>
   <form action='<?php echo Pbox::get_admin_url( 'include_pbox_manage', '&amp;action=search_by_id' );?>' method='post'>
	   <?php wp_nonce_field( 'pbox-box-search' ); ?>
	   <p><?php _e( 'Search Type: ', 'pb' );?><select name='mode'>
	   <option value='box_id'><?php _e( 'Box ID', 'pb' );?></option>
	   <?php 
	   // If XWidgets is installed, added functionality for different boxes per page
	   if( class_exists( 'XWidgets' ) ) { ?>
	   <option value='page_id'><?php _e( 'Page ID', 'pb' );?></option>
	   <?php
	   } ?>
	   <option value='content_id'><?php _e( 'Content ID', 'pb' );?></option>
	   </select></p>
	   <p><?php _e( 'Item ID: ', 'pb' );?><input type='text' name='item_id' /></p>
	   <p><input type='submit' value='<?php _e( 'Search', 'pb' );?>' class='button' /></p>
   </form></td></tr>
</table>

<?php

//List all boxes
$boxes = PBox::get_boxes_bound( $lower_bound, PBOX_PAGED );

if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'search_by_id' ) {
	check_admin_referer( 'pbox-box-search' );
	
	$item_id = intval( $_REQUEST['item_id'] );
	$boxes = array();

	if ( $_REQUEST['mode'] == 'page_id' ) {
		printf( __( '<h3>Searching for pboxes being used on page %d</h3>', 'pb' ), $item_id );
		echo '<p>';
		PBox::display_page( $item_id );
		echo '</p>';
		//find boxes on page
		$all_boxes = PBox::get_boxes_on_page( $item_id );
		
		$i = 1;
		if( is_array( $all_boxes ) && !empty( $all_boxes ) ) {
			foreach ( (array) $all_boxes as $option ) {
				if ( $option['pbox_id'] > 0 ) {
					$boxes[$i]['pbox_id'] = $option['pbox_id'];
					$the_box = PBox::get_box($option['pbox_id']);
					$boxes[$i]['title'] = $the_box['title'];
					$boxes[$i]['last_update_by'] = $the_box['last_update_by'];
					$boxes[$i]['last_update_time'] = $the_box['last_update_time'];
					$i++;
				}
			}
		}
	} elseif ( $_REQUEST['mode'] == 'box_id' ) {
		printf( __( '<h3>Searching for pbox id %d</h3>', 'pb' ), $item_id );
		//change this to check if the pbox ACTUALLY exists!
		$the_box = PBox::get_box( $item_id );
		if( $the_box != -1 && $item_id > 0 ) {
			echo "<p><a href='" . wp_nonce_url( PBox::get_admin_url( "pbox/pb.edit.php", "&amp;action=edit_view&amp;box_id=$item_id" ), "pbox-box-edit" ) . "' rel='permalink'>".__( 'Edit PBox', 'pb' )."</a> (ID $item_id)</p>";
		}
		if ( $the_box['pbox_id'] ) {
			//found pbox
			$boxes[0] = $the_box;
		}
	} elseif ( $_REQUEST['mode'] == 'content_id' ) {
		printf( __( '<h3>Searching for pboxes using content ID %d</h3>', 'pb' ), $item_id );
		$raw_boxes = PBox::get_usage_by_content( $item_id );
		if ( is_array( $raw_boxes ) ) {
			for ( $i = 0; $i < sizeof( $raw_boxes ); $i++ ) {
				$boxes[$i]['pbox_id'] = $raw_boxes[$i]['pbox_id'];
				$the_box = PBox::get_box( $raw_boxes[$i]['pbox_id'] );
				$boxes[$i]['title'] = $the_box['title'];
				$boxes[$i]['last_update_by'] = $the_box['last_update_by'];
				$boxes[$i]['last_update_time'] = $the_box['last_update_time'];
			}
		}
	}
}

/*
* Only necessary to check if XWidgets in installed. Obviously displaying
* the pages that a given box appears on is impractical with the default
* widget structure... Any displayed widget is dependant on all pages with 
* that sidebar.
*/

if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'view_dependencies' ) {
	check_admin_referer( 'pbox-boxdependencies-view' );
	
	$box_id = intval( $_REQUEST['box_id'] );
	$title = PBox::get_title( $box_id );
	$rows = PBox::get_pages_using_box( $box_id );
	
	?>
	<h3><?php printf( __( 'Dependencies for PBox ID <em>%d</em> with title <em>%s</em>', 'pb' ), $box_id, $title ); ?></h3>
	<ul>
	<?php
	if ( count( $rows ) == 0 ) {
		echo "<h4>" . __( 'No dependencies', 'pb' ) . '</h4>';
	} else {
		if ( strlen( $title ) >= 1 && is_array( $rows ) ) {	
			// if a valid box id, offer an edit pbox link (will direct to adding one if not already in use)
			if( $box_id > 0 ) {
				echo "<p><a href='" . wp_nonce_url( PBox::get_admin_url( "pbox/pb.edit.php", "&amp;action=edit_view&amp;box_id=$box_id" ), "pbox-box-edit" ) . "' rel='permalink'>".__( 'Edit PBox', 'pb' )."</a> (ID $box_id)</p>";
			}
			foreach ( (array) $rows as $row ) {
				unset( $edit_link );
				// getting the edit link
				ob_start();
				edit_post_link( __( 'Edit', 'pb' ), '<span style="display:inline">', '</span> / ', $row['page_id'] );
				$edit_link = ob_get_contents();
				ob_end_clean();
				// stripping tags from the link to ensure there is content in the tags
				$edit_test = strip_tags( $edit_link );
				// if there is content in the tags
				if( strlen( $edit_test ) > 0 ) {
					// print out the results with edit/preview links for the page mentioned.
					printf( __( '<li>Page ID %1$d on slot %2$d', 'pb' ), $row['page_id'], $row['slot_id'] );
					echo '&nbsp;&nbsp( '.$edit_link;
					echo '<a href="'.get_permalink( $row['page_id'] ).'" target="_blank">'.__('Preview').'</a> page '.$row['page_id'].' )</li>';
				}
			}
		}
	}
	?>
	</ul>
	<?php
}

?>

<?php 
if ( !isset( $_POST['item_id'] ) ) {  ?>

	<table width="100%">
	<tr> 
	<td width="100%">
	<form method="post" action=''>
	<?php
	// Paginate PBox list

	//get the current page number
	if( isset( $_GET['pagenum'] ) ) {
		$pagenum = absint( $_GET['pagenum'] );
	} else {
		$pagenum = 1;
	}
	if( !isset( $per_page ) || $pre_page < 0 ) {
		$per_page = PBOX_PAGED;
	}
	$page_links = paginate_links( array( 
		'base' => add_query_arg( 'pagenum', '%#%' ), 
		'format' => '', 
		'total' => ceil( PBox::count_boxes() / $per_page ), 
		'current' => $pagenum 
	) );
	
	if ( $page_links )
		echo "<div class='tablenav-pages' style='float:right;'>$page_links</div>";
	?>
		
	</form>
	</td> <td width="10%"> </td> 
	</tr>
	</table>
<?php 
} ?>
<br />
<table id='alternate' class="sortable-onload-2 rowstyle-alt no-arrow paginate-75 widefat">
	<thead>
		<tr>
			<th scope="col" style="text-align: center" class="sortable-numeric"><?php _e( 'ID', 'pb' );?></th>
			<th scope="col" class="sortable-text"><?php _e( 'Title', 'pb' );?></th>
			<th scope="col" class="sortable-numeric"><?php _e( 'Items', 'pb' );?></th>
			<th scope="col" class="sortable-text"><?php _e( 'Modified By', 'pb' );?></th>
			<th scope="col" class="sortable"><?php _e( 'Last Updated', 'pb' );?></th>
			<th scope="col" colspan="4" style="text-align: center"><?php _e( 'Action', 'pb' );?></th>
		</tr>
	</thead>
	<tbody id="the-list">
	<?php
	$num_items = 0;

if( !empty( $boxes ) ) {
	// Getting and displaying the content for the table
	$alternate = 'alternate'; // table row color alternate flag
	foreach ( (array) $boxes as $box ) {
		if( $box['last_update_time'] != 0 ) {
			$readable_time = date( 'Y-m-d H:i', $box['last_update_time'] );
		} else {
			$readable_time = 'unknown';
		}
		$user_info = get_userdata( $box['last_update_by'] );
		//get $num_items
		$num_items = PBox::get_num_items( $box['pbox_id'] );
		?>
		<tr id='pbox-<?php echo $box['pbox_id'] ?>' class='<?php echo $alternate ?>'>
			<td scope='row' style='text-align: center' width='3%'><?php echo $box['pbox_id'] ?></td>
			<td  width='33%'><?php echo stripslashes( $box['title'] ) ?></td>
			<td  width='3%'><?php echo $num_items ?></td>
			<td  width='5%'><?php if( is_object( $user_info ) ) echo $user_info->user_login ?></td>
			<td width='10%' ><?php echo $readable_time ?></td>
			<td width='3%'><a href='<?php echo wp_nonce_url( PBox::get_admin_url( 'pbox/pb.edit.php', "&amp;action=edit_view&amp;box_id=".$box['pbox_id'] ), 'pbox-box-edit' ) ?>' class='edit'><?php _e( 'Edit', 'pb' ) ?></a></td>
		<td width="3%">
			<a onclick="jQuery( '#pbox-clone-process' ).attr( 'action', this.href ).submit(); return false;"
			href=" <?php echo PBox::get_admin_url( 'include_pbox_manage', "&amp;action=clone_process&amp;box_id={$box['pbox_id']}" ); ?>" rel="permalink" class='edit'><?php _e( 'Clone', 'pb' ); ?></a>
		</td>
		<?php
		if( class_exists( 'XWidgets' ) ) {
			echo "<td width='3%'><a href='" . wp_nonce_url( PBox::get_admin_url( 'include_pbox_manage', "&amp;action=view_dependencies&amp;box_id={$box['pbox_id']}" ), 'pbox-boxdependencies-view' ) . "' rel='permalink' class='edit'>" . __( 'Dependencies', 'pb' ) . '</a></td>';
		}
		?>
		<td width='3%'>
			<a onclick="if (confirm('<?php echo esc_js( __( 'Are you sure you want to delete this box?', 'pb' ) ); ?>')) { jQuery('#pbox-delete-process').attr('action', this.href).submit(); }return false;;"
				href="<?php echo PBox::get_admin_url( 'include_pbox_manage', "&amp;action=delete_process&amp;box_id={$box['pbox_id']}" ); ?>" rel='permalink' class='delete'><?php _e( 'Delete', 'pb' ); ?></a>
		</td>
		</tr>
		<?php
		if ( $alternate == '' ) {
			$alternate = 'alternate';
		} else {
			$alternate = '';
		}
		
	}
} else {?>
	<tr><td colspan="10"><?php _e( 'There are no presentation boxes to display.', 'pb' ) ?></td></tr>
	<?php
}
	
?>	
  </tbody>
</table>
<table width="100%">
<tr> 
<td width="100%">
<form method="post" action=''>
<?php
if ( isset( $page_links ) ) { ?>
	<br /><div class='tablenav-pages' style='float:right;'><?php echo $page_links ?></div>
	<?php
}
?>
</form>
</td> <td width="10%"> </td> 
</tr>
</table>
</div>

<?php // Send link as post with nonce ?>
<form id="pbox-clone-process" method="post" action="">
	<?php wp_nonce_field( 'pbox-box-clone' ); ?>
</form>
<form id="pbox-delete-process" method="post" action="">
	<?php wp_nonce_field( 'pbox-box-delete' ); ?>
</form>
