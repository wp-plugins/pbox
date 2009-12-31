<?php
/**
PBox
Customizable content widgets able to display posts, pages, links and plain text in a custom style.
2.2
Authors: Aaron Berg, Dale Taylor, Nelson Lai, Yefei Tang, Xueyan Bai, Zafor Ahmed, Fran&ccedil;ois Fortin, Lindsay Newton
http://www.bankofcanada.ca/
*/

/** STYLES MANAGEMENT PAGE **/

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

/* 
* This variable is a marker to determine whether or not the CSS field should be active;
* determines the writability of the pbox.css file and directory.
*/
$no_css = false;
?>
<style type="text/css">
.alternate {
    background-color: #F5F5F5; }
</style>
<div class="wrap">
<?php
if( !PBox::pb_is_writable( PBox::get_theme_location().'/pbox.css' ) ) { ?>
	<div class="error" id="message">	
			<p><?php printf( __( '<strong>NOTE:</strong> CSS information cannot be saved. <em>%s</em> is not writable.', 'pb' ), PBox::get_theme_location().'/pbox.css' ); ?></p>
		</div>
<?php 
	$no_css = true;
}
if( isset( $_REQUEST['action'] ) ) {
	$action = $_REQUEST['action'];
	if ( $action == 'add_style_process' ) { // Run when a new style is being created
		check_admin_referer( 'pbox-style-add' );
		if ( count( $_POST ) != 0 ) { // Make sure something was actually submitted to this page
			$style_id = $_POST['style_name'];
			if ( !PBox::verify_input( $style_id ) ) {
				// if the style name entered contains invalid characters, throw an error
				$error = new WP_Error( 'invalid_style', __( "<strong>ERROR:</strong> Invalid style ID. Style IDs must only contain alphanumeric characters, hyphens ( - ), and underscores ( _ ).", 'pb' ) );
				// catch and print the error message
				echo '<div class="error" id="message"><p>'.$error->get_error_message().'</p></div>';
				// identify the error is there to avoid redirecting the the style editting panel
				$in_error = true;
			} else {
				$style = Pbox::create_style( $style_id );
				// if the style threw an error, print it and mark the process as being in error.
				if ( is_wp_error( $style ) ) {
					echo '<div class="error" id="message"><p>'.$style->get_error_message().'</p></div>';
					$in_error = true;
				}
			}
		}
	}

	if( $action == 'delete_process' ) { // Run when a new style is being deleted
		$style_id = $_REQUEST['style_id'];
		if ( !PBox::verify_input( $style_id ) ) {
			$error = new WP_Error( 'invalid_style', __( "<strong>ERROR:</strong> Invalid style ID. Style IDs must only contain alphanumeric characters, hyphens ( - ), and underscores ( _ )." ) );
			echo '<div class="error" id="message"><p>'.$error->get_error_message().'</p></div>';
			$in_error = true;
		} else {
			Pbox::delete_style( $style_id );
			//print out the success message
			?>
			<div class="fade updated" id="message">	
				<p><?php printf( __( 'Presentation Box style <em>%s</em> deleted.', 'pb' ), $style_id ); ?></p>
			</div>
			<?php	
		}
	}
	
	if ( $action == 'edit_process' ) { // Run when Edit page submits
		check_admin_referer( 'pbox-style-editprocess' );
		if ( count( $_POST ) != 0 ) { // Make sure something was actually submitted to this page
			$style_id = $_REQUEST['style_id'];
			if ( !PBox::verify_input( $style_id ) ) {
				$error = new WP_Error( 'invalid_style', __( "<strong>ERROR:</strong> Invalid style ID. Style IDs must only contain alphanumeric characters, hyphens ( - ), and underscores ( _ ).", 'pb' ) );
				echo '<div class="error" id="message"><p>'.$error->get_error_message().'</p></div>';
				$in_error = true;
			} else {
				// Build the style information from the form that was submitted.
				$content_array = array();
				$content_array[] = $_POST['htmleditbox_top']; // HTML preceding
				$content_array[] = $_POST['htmleditbox_item_links']; // Links - item only
				$content_array[] = $_POST['htmleditbox_item_links_excerpt']; // Links - item and excerpt
				$content_array[] = $_POST['htmleditbox_item_posts']; // page/post/file - item only
				$content_array[] = $_POST['htmleditbox_item_posts_excerpt']; // page/post/file - item and excerpt
				$content_array[] = $_POST['htmleditbox_item_posts_content']; // page/post/file - item and content
				$content_array[] = $_POST['htmleditbox_item_text']; // text
				$content_array[] = $_POST['htmleditbox_bottom']; // HTML following
				$content_array[] = $_POST['csseditbox']; // styling information
				PBox::update_style( $style_id, $content_array );			
				?>
				<div class="fade updated" id="message">	
					<p><?php printf( __( 'Presentation Box style <em>%s</em> updated.', 'pb' ), $style_id ); ?></p>
				</div>
				<?php	
			}
		}
	}
	// if the styles edit page is necessary to be loaded (the page is not in error and is an edit/add action)
	if ( ( !isset( $in_error) || !$in_error ) && ($action == 'edit_view' || $action == 'edit_process' || $action == 'add_style_process') ) {
		// determine which referrer to check
		switch( $action ) {
			case 'edit_view':
				check_admin_referer( 'pbox-style-editview' );
				break;
			case 'edit_process':
				check_admin_referer( 'pbox-style-editprocess' );
				break;
			case 'add_style_process':
				check_admin_referer( 'pbox-style-add' );
				$style_id = $_POST['style_name'];
				break;
		}
		// if editing an existing style, grab the name from the REQUEST
		if( !isset( $style_id ) ) {
			$style_id = $_REQUEST['style_id'];
		}
		if ( !PBox::verify_input( $style_id ) && $_REQUEST['action'] != 'delete_process' ) {
			$error = new WP_Error( 'invalid_style', __( "<strong>ERROR:</strong> Invalid style ID. Style IDs must only contain alphanumeric characters, hyphens ( - ), and underscores ( _ ).", 'pb' ) );
			echo '<div class="error" id="message"><p>'.$error->get_error_message().'</p></div>';
			$in_error = true;
		}
		// If not a newly created style, get style data and check that the style exists
		if ( $action != 'add_style_process' && $action != 'delete_process' ) {
			$style_data = PBox::get_style_data( $style_id );
			if ( $style_data == -1 ) {
				$error = new WP_Error( 'invalid_style', __( "<strong>ERROR:</strong> Invalid style ID. Style IDs must only contain alphanumeric characters, hyphens ( - ), and underscores ( _ ).", 'pb' ) );
				echo '<div class="error" id="message"><p>'.$error->get_error_message().'</p></div>';
				$in_error = true;
				break;
			}
		}	
		// if the page is not in error, show the styles edit page
		if( !isset( $in_error ) || !$in_error ) {
			?>
			<div id="icon-themes" class="icon32"><br /></div>
			<h2><?php _e( 'PBox Styles', 'pb' );?></h2>
			<h3><?php printf( __( 'Modify Style - %s', 'pb' ), $style_id );?></h3>
			
			<h4><?php _e( 'Formatting', 'pb' );?></h4>
			<p><?php _e( 'Use HTML to create a template for this style. The following variables may be used:', 'pb' );?></p>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php _e( 'Variable name', 'pb' );?></th>
						<th><?php _e( 'Description', 'pb' );?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="2"><em><?php _e( "Any ITEM variables used in the 'HTML preceding PBox items' field will return the properties of the first item.", 'pb' );?></em></td>
					</tr>
					<tr>
						<td>%PBOX_TITLE%</td>
						<td><?php _e( 'Returns the title of the PBox.', 'pb' );?></td>
					</tr>
					<tr class="alternate">
						<td>%ITEM_TITLE%</td>
						<td><?php _e( 'Returns the title of the current item. Applies only to items of type link or page/post/file.', 'pb' );?></td>
					</tr>
					<tr>
						<td>%ITEM_URL%</td>
						<td><?php _e( 'Returns the URL of the current item. Applies only to items of type link or page/post/file. When used with a link, the URL of the link is returned. When used with a page/post/file, the URL for that page/post/file is returned.', 'pb' );?></td>				
					</tr>
					<tr class="alternate">
						<td>%ITEM_LINK%</td>
						<td><?php _e( "Returns %ITEM_TITLE% and %ITEM_URL% in a link tag. For example, &lt;a href='%ITEM_URL%' title='%ITEM_TITLE%'&gt;%ITEM_TITLE%&lt;/a&gt;. Applies only to items of type link or page/post/file.", 'pb' );?></td>
					</tr>
					<tr>
						<td>%ITEM_EXCERPT%</td>
						<td><?php _e( 'Returns the excerpt/summary/description of the current item. When used with a link, it returns the description. When used with a page/post/file, it returns the excerpt/summary.', 'pb' );?></td>
					</tr>
					<tr class="alternate">
						<td>%ITEM_CONTENT%</td>
						<td><?php _e( 'Returns the content of the current item. Applies only to items of type text or page/post/file.', 'pb' );?></td>
					</tr>
					<tr>
						<td>%ITEM_POSTMETA_name%</td>
						<td><?php _e( "Returns the item's postmeta entry with key equal to [name].", 'pb' );?></td>
					</tr>
					<tr class="alternate">
						<td>%WP_OPTION_name%</td>
						<td><?php _e( "Returns the WordPress option with key equal to [name].", 'pb' );?></td>
					</tr>
					<tr>
						<td>%PAGE_IMAGE_x%</td>
						<td><?php _e("Returns the URL of the first image attached to the current page/post, where X is the size of image you want returned: F for Full, L for Large, M for Medium, or T for Thumbnail.", 'pb' );?></td>
					</tr>
				</tbody>
			</table>
			<br />
			<form method="post" action="<?php echo PBox::get_admin_url( 'include_pbox_styles', '&amp;action=edit_process&amp;style_id='.$style_id ); ?>">
				<?php wp_nonce_field( 'pbox-style-editprocess' ); ?>
				<h5><?php _e( 'HTML preceding PBox items', 'pb' );?></h5>
				<textarea rows="10" cols="50" name="htmleditbox_top" id="htmleditbox_top"><?php echo ( isset( $style_data[1] ) ? $style_data[1] : '' ); ?></textarea>
				

				<h5><?php _e( 'HTML for individual PBox items', 'pb' );?></h5>
				<table>
				<tr>
					<td style="width:260px;"><h5><em><?php _e( 'Links', 'pb' );?></em></h5></td><td style="width:260px;"></td>
				</tr>
				<tr>
					<td><?php _e( 'Item only:', 'pb' );?></td><td><?php _e( 'Item and excerpt:', 'pb' );?></td>
				</tr>
				<tr>
					<td>
						<textarea rows="8" cols="30" name="htmleditbox_item_links" id="htmleditbox_item_links"><?php echo ( isset( $style_data[2] ) ? $style_data[2] : "" ); ?></textarea>
					</td>
					<td>
						<textarea rows="8" cols="30" name="htmleditbox_item_links_excerpt" id="htmleditbox_item_links_excerpt"><?php echo ( isset( $style_data[3]) ? $style_data[3] : "" ); ?></textarea>
					</td>
					
				</tr>
				<tr>
					<td><h5><em><?php _e( 'Pages/Posts/Files', 'pb' );?></em></h5></td><td></td>
				</tr>
				<tr>
					<td><?php _e( 'Item only:', 'pb' );?></td>
					<td><?php _e( 'Item and excerpt:', 'pb' );?></td>
				</tr>
				<tr>
					<td><textarea rows="10" cols="30" name="htmleditbox_item_posts" id="htmleditbox_item_posts"><?php echo ( isset( $style_data[4] ) ? $style_data[4] : "" ); ?></textarea></td>
					<td><textarea rows="10" cols="30" name="htmleditbox_item_posts_excerpt" id="htmleditbox_item_posts_excerpt"><?php echo ( isset( $style_data[5] ) ? $style_data[5] : "" ); ?></textarea></td>
				</tr>
				<tr><td></td><td></td></tr>
				<tr>
					<td><?php _e( 'Item and content:', 'pb' );?></td>
					<td></td>
				</tr>
				<tr>
					<td><textarea rows="10" cols="30" name="htmleditbox_item_posts_content" id="htmleditbox_item_posts_content"><?php echo ( isset( $style_data[6] ) ? $style_data[6] : "" ); ?></textarea></td>
					<td></td>
				</tr>
				</table>	
				
				<div>
					<h5><em><?php _e( 'Text', 'pb' );?></em></h5>
					<textarea rows="10" cols="30" name="htmleditbox_item_text" id="htmleditbox_item_text"><?php echo ( isset( $style_data[7] ) ? $style_data[7] : "" ); ?></textarea>
				</div>
					
				<br style="clear:left;" />
				<h5 style="clear:left;"><?php _e( 'HTML following PBox items', 'pb' );?></h5>
				<textarea rows="10" cols="50" name="htmleditbox_bottom" id="htmleditbox_bottom"><?php echo ( isset( $style_data[8] ) ? $style_data[8] : "" ); ?></textarea>
				
				<h4><?php _e( 'Styling', 'pb' );?></h4>
				<p><?php _e( 'CSS to be applied to style:', 'pb' );?></p>
	 <?php /**
				* Show the CSS textarea. This will be disabled if the CSS file or directory 
				* is not writable. Other information can be edited as normal
				*/?>
				<textarea <?php if( $no_css ) echo 'disabled'; ?> rows="15" cols="80" name="csseditbox" id="csseditbox"><?php if( !$no_css ) { echo PBox::get_style_css( $style_id ); } else{ _e( 'Your CSS file is not writable. Check your file permissions.', 'pb' ); } ?></textarea>
				<p><?php _e( '(NOTE: Please make sure that your CSS does not interfere with other stylesheets or PBox styles.)', 'pb' );?></p>
				
				<p class='submit'>
					<input type='button' onclick="window.location='<?php echo PBox::get_admin_url( "include_pbox_styles", "" );?>';" value='<?php _e( '&laquo; Cancel', 'pb' );?>' />
					<input type='submit' value='<?php _e( 'Save Changes &raquo;', 'pb' );?>' class='button' />
				</p>
			</form>
			<?php
		}
	}
}
/**
* if the page is in error or deleting a style display the standard 'Styles' 
* page that lists the styles and gives options
*/
if ( ( isset( $in_error) && $in_error ) || !isset( $action ) || $action == 'delete_process' ) { 
	?>
	<div id="icon-themes" class="icon32"><br /></div>
	<h2><?php _e( 'PBox Styles', 'pb' );?></h2>
	<h3><?php _e( 'Create New Style', 'pb' );?></h3>
	<form action="<?php echo PBox::get_admin_url( 'include_pbox_styles', '&amp;action=add_style_process' );?>" method='post'>
		<?php wp_nonce_field( "pbox-style-add" ); ?>
		<p><?php _e( 'To create a new PBox style, enter the name of the new style below.', 'pb' );?></p>
		<p><?php _e( 'Name:', 'pb' );?> <input type='text' name='style_name' /></p>
		<p><input type='submit' value='<?php _e( 'Create', 'pb' );?>' class='button' /></p>
	</form>
	<h3><?php _e( 'Existing Styles', 'pb' );?></h3>
	<?php
	$styles = PBox::get_styles();
	?>
	<table width="50%" class="widefat">
		<thead>
		<tr>
			<th scope="col"><?php _e( 'Style Name', 'pb' );?></th>
			<th scope="col" colspan="2"><?php _e( 'Action', 'pb' );?></th>
		</tr>
		</thead>
	<?php
	// print out the styles in the table
	$alternate = '';
	if ( count( $styles ) ) {
		foreach ( (array) $styles as $style ) { ?>
			<tr class="<?php echo $alternate ?>">
				<td><?php echo $style['style_id'] ?></td>
				<td><a href="<?php echo wp_nonce_url( PBox::get_admin_url( 'include_pbox_styles', "&amp;action=edit_view&style_id=".$style['style_id'] ), 'pbox-style-editview' ) ?>"><?php _e( 'Edit', 'pb' ) ?></a></td>
				<td><a onclick="return confirm(' <?php echo __( 'Are you sure you want to delete this style?', 'pb' ) . "');\" href='" . wp_nonce_url( PBox::get_admin_url( 'include_pbox_styles', "&amp;action=delete_process&style_id=".$style['style_id'] ), 'pbox-style-delete' ) . "'>" . __( 'Delete', 'pb' ) ?></a></td>
			</tr>
			<?php
			if ( $alternate == '' ) {
				$alternate = 'alternate';
			} else {
				$alternate = '';
			}
		}
	}
	?>
	</table>
	<?php
}

?>
</div>
