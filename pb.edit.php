<?php
/**
PBox
Customizable content widgets able to display posts, pages, links and plain text in a custom style.
2.3
Authors: Aaron Berg, Dale Taylor, Nelson Lai, Yefei Tang, Xueyan Bai, Zafor Ahmed, Fran&ccedil;ois Fortin, Lindsay Newton, Nicholas Crawford
http://www.bankofcanada.ca/
*/

/** EDIT ADMIN PAGE **/

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

if ( !pbox_check_capabilities() ) {
	wp_die( __( 'You do not have sufficient permissions to access this page.', 'pb' ) );
}
include_once( 'pb.inc.php' );
// Add the CSS for the Sortable elements in the header.
add_action( 'admin_print_styles', create_function( '', '
	echo "<style type=\'text/css\'>
				#sortable { list-style-type: none; margin: 0; padding: 0; width: 100%; }
				#sortable li { margin: 0 3px 3px 3px; padding: 0.4em; padding-left: 0em; font-size: 0.9em; height: 14px; }
				#sortable li span { position: absolute; margin-left: -1.3em; }
			</style>";' ) );
global $wpdb;

// If the user wants to display a different number of PBoxes
if ( isset( $_REQUEST['manual_pbox_size'] ) && is_numeric( $_REQUEST['manual_pbox_size'] )
	&& $_REQUEST['manual_pbox_size'] && $_REQUEST['size'] == 'other' ) {
	$_REQUEST['size'] = $_REQUEST['manual_pbox_size']; //override with the value from the text box
}
if( isset( $_REQUEST['box_id'] ) ) {
	$box_id = intval( $_REQUEST['box_id'] );
} else {
	$box_id = 0;
}
$box_title = stripslashes( PBox::get_title( $box_id ) );

// The hidden divs that are toggled viewable on update via jQuery (see JavaScript just above the footer)

?>
<div class="fade updated updateMessage" id="successfulUpdate" style="display:none">
	<p><?php printf( __( 'Updated PBox %s successfully.', 'pb' ) , '<em>'.$box_id.'</em>' ) ?></p>
</div>
<div class="error updateMessage" id="failedUpdate" style="display:none">
	<p><?php printf( __( 'PBox %s could not be updated due to a server error. Please try again.', 'pb' ) , '<em>'.$box_id.'</em>' ) ?></p>
</div>
<div class="error updateMessage" id="failedData" style="display:none">
	<p><?php printf( __( 'PBox %s could not be updated due to a database error. Please check your permissions and try again.', 'pb' ) , '<em>'.$box_id.'</em>' ) ?></p>
</div>
<div class="wrap">
<?

if ( $action = $_REQUEST['action'] ) {
	// Handling the edit view of new or existing PBoxes
	$contents = PBox::get_contents( $box_id );
	if ( $action == 'edit_view' || $action == 'edit_process' || $action == 'add_process' ) {
		if( $action == 'edit_view' )
			check_admin_referer( 'pbox-box-edit' );
		if( $action == 'edit_process' )
			check_admin_referer( 'pbox-box-editprocess' );
		if( $action == 'add_process' )
			check_admin_referer( 'pbox-box-add' );
		?>
		<div id="icon-themes" class="icon32"><br /></div>
		<h2><?php printf( __( 'PBox Contents for %s (%d)', 'pb' ), '<span id="titleUpdate">'.$box_title.'</span>', $box_id); ?></h2>
		<form action='<?php echo PBox::get_admin_url( 'include_pbox_manage', '' );?>' method='post' id='pboxUpdate'>
		<input type='hidden' id='pbox_action' name='action' value='edit_process' />
		<input type='hidden' id='pbox_id'  name='box_id' value='<?php echo $box_id;?>' />
		<p class='submit'>
			<input type='submit' value='<?php echo _e( 'Save Changes &raquo;', 'pb' );?>' class='button' />
		</p>
		<p>
			<?php _e( 'Title:', 'pb' );?>
			 <input type='text' size='50' name='box_title' id='box_title' value='<?php echo  esc_attr( $box_title );?>' />
		</p>
		<div id='item_list' style='margin:10px 10px 10px 0px; padding:10px;'>
		<?php
		// Determine the number of input elements that should be displayed as decided by the user
		$counter = 0;
		if ( $counter < count( $contents ) )
			$counter = count( $contents );
		if ( $counter <= 15 )
			$counter = 15;

		// Sortable ul below is the container for the JavaScript sortable list functionality
?>
		<div class="edit-pbox">
			<ul id="sortable">
			<?php
			for ( $i = 0; $i < $counter; $i++ ) {
				//get from DB; if does not exist, then leave blank
				if ( isset( $contents[$i]['content'] ) ) {
					//content needs to be escaped and decoded to display properly in the input field
					$content = stripslashes( esc_attr( html_entity_decode( $contents[$i]['content'] ) ) );
					$type_id = intval( $contents[$i]['type_id'] );
					$callout_id = intval( $contents[$i]['callout_id'] );

					if ( $type_id == 2 ) {
						$post_type = get_post_type( $content );
					} else {
						$post_type = 'link';
					}
				} else {
					// Setting the default cases
					$callout_id = 0;
					$content = '';
					$type_id = 3; // default type is 'text'
				}

				// Echo the items to be contained in the jQuery sortable class.?>
				<li id='item_<?php echo $i ?>' class='lineitem'>
					<img src='<?php echo PBox::get_base_url( 'images/move.gif' ) ?>' class='move' alt='Move this field' />
					<a href="javascript:clearValue('<?php echo $i ?>')" >
						<img src='<?php echo PBox::get_base_url( 'images/x.png' ) ?>' class='cancelButton' alt='Clear this field' />
					</a>
					<select id='type_<?php echo $i ?>' class='typeField' name='type_<?php echo $i ?>' onchange='item_content_prompt(<?php echo $i ?>)'>
							<?php PBox::display_type_dropdown( $type_id ); ?>
					</select>
					<input type='text' name='content_<?php echo $i ?>' id='content_<?php echo $i ?>' value='<?php echo $content ?>' class='contentField' />
					<select id='callout_id_<?php echo $i ?>' name='callout_id_<?php echo $i ?>'  <?php echo ( ( $type_id != PBOX_TYPE_PAGE && $type_id != PBOX_TYPE_LINK ) ? "style='display:none;'" : '' ) ?> class='calloutField'>
						<option value='0' <?php echo ( ( $callout_id == 0 ) ? "selected='selected'" : '' ) ?>><?php _e( 'Item only', 'pb' ) ?></option>
						<option value='1' <?php echo ( ( $callout_id == 1 ) ? "selected='selected'" : '' ) ?>><?php _e( 'Item and excerpt', 'pb' ) ?></option>
						<option <?php echo ( ( $type_id == PBOX_TYPE_LINK) ? "style='display:none'" : "" ) ?> id='item_and_content_<?php echo $i ?>' value='2' <?php echo ( ( $callout_id == 2 ) ? "selected='selected'" : "" ) ?>><?php _e( 'Item and content', 'pb' ) ?></option>
					</select>
					<input type='text' name='order_<?php echo $i ?>' id='order_<?php echo $i ?>' value='<? echo $i ?>' class='orderField' style='display:none' />
					<a href='#' onClick='removeFormField("#item_<?php echo $i ?>"); return false;'><?php _e( 'Remove' ) ?></a>
					<span class='linksUpdate'>
					<?php
					if( $content > 0 && $type_id == 1 ) {?>
						&nbsp;&nbsp;|&nbsp;&nbsp;<?php PBox::display_link( $content, 40 );
					} else if( $content > 0 && $type_id == 2 ) { ?>
						&nbsp;&nbsp;|&nbsp;&nbsp;<?php PBox::display_page( $content, 40 );
					} ?>
					</span>
				</li>
			<?php
				$max_field = $i;
			}?>
			<input type="hidden" id="id" value="1">
			<span id="divTxt"></span>
		</ul>
		<p><a href="#" onClick="addFormField('<?php echo PBOX_PLUGIN_URL ?>' ); return false;"><?php _e( 'Add an additional item' ) ?></a></p>
		</div>
		<?php
		wp_nonce_field( 'pbox-box-editprocess' );
		?>
			<p class='submit'>
				<input type='submit' value='<?php _e( 'Save Changes &raquo;', 'pb' );?>' class='button' />
			</p>
			</div>
		</form>
		</div>
		<script type="text/javascript">
			//<!--
			// Initialize the Sortable object
			jQuery( function() {
				jQuery( '#sortable' ).sortable( {
					opacity: 0.5, // lower opacity on drag
					handle: '.move', // only sortable on the move icon
					revert: true, // if dragged to an invalid position will revert to old position
					axis: 'y', // only moveable on the y axis
					containment: '.wrap' // only moveable within the wrap outer div
				} );
				jQuery( '#pboxUpdate' ).bind( 'submit', function( event ) {
					event.preventDefault(); // prevent the default submit action of the form
					var newOrdering = jQuery( '#sortable' ).sortable( 'toArray' );
					var title = jQuery( '#box_title' ).val();
					// get all the information from the form
					all_elements = bindElement( newOrdering );
					//now the ajax
					jQuery.ajax( {
						//call WP admin-ajax
						url: ajaxurl,
						type: 'POST',
						//build data string for the header
						data: 'action=edit_process' + '&box_title=' + encodeURIComponent( title ) + '&box_id=<?php echo $box_id ?>' +  all_elements[0] +  all_elements[1] +  all_elements[2] +  all_elements[3],
						success: function( result ) {
							// If successful report the success and update the title
							jQuery( '.updateMessage' ).hide();
							var new_result = eval('(' + result + ')');
							if( !new_result.error ) {
								jQuery( '#successfulUpdate' ).fadeIn( 1000 );
								var updatedTitle= jQuery( '#box_title' ).val();
								jQuery( '#titleUpdate' ).html( updatedTitle );
							} else {
								jQuery( '#failedData' ).fadeIn( 1000 );
							}
						},
						error: function( result ) {
							// If there was a server error report the error
							jQuery( '#successfulUpdate' ).hide();
							jQuery( '#failedUpdate' ).fadeIn( 1000 );
						}
					} );
				} );
				jQuery( '.move' ).css( 'cursor', 'move' );
				jQuery( '.cancelButton' ).css( 'cursor', 'pointer' );

		} );
		function addFormField( base_url ) {
			var id = document.getElementById('id').value;
			max = '<?php echo $max_field ?>';
			new_id = Number( max ) + Number( id );
			jQuery("#sortable").append("<li id='item_" + new_id + "' class='lineitem'>"+
					"<img src='" + base_url + "images/move.gif' class='move' alt='Move this field' /> "+
					"<a href=javascript:clearValue('" + new_id + "') ><img src='" + base_url + "images/x.png' class='cancelButton' alt='Clear this field' /></a> "+
					"<select id='type_" + new_id + "' class='typeField' name='type_" + new_id + "' onchange='item_content_prompt(" + new_id + ")'>"+
						"<option value='1'>link</option><option value='2'>page/post/file</option><option value='3' selected='selected'>text</option><option value='4'>external content</option><option value='5'>image</option></select> "+
					"<input type='text' name='content_" + new_id + "' id='content_" + new_id + "' value='' class='contentField' />"+
					"<select id='callout_id_" + new_id + "' name='callout_id_" + new_id + "'  style='display:none;' class='calloutField'>"+
						"<option value='0' selected='selected'>Item only</option>"+
						"<option value='1' >Item and excerpt</option>"+
						"<option  id='item_and_content_" + new_id + "' value='2' >Item and content</option>"+
					"</select>"+
					"<input type='text' name='order_" + new_id + "' id='order_" + new_id + "' value='" + new_id + "' class='orderField' style='display:none' />"+
					"<span class='linksUpdate'></span>"+
					" <a href='#' onClick='removeFormField(\"#item_" + new_id + "\"); return false;'>Remove</a></li>"
					);
			id = (new_id - 1) + 2;
			document.getElementById('id').value = id;
		}
		//-->
		</script>
		<?php
	}
}