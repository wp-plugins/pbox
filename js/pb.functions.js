/**
Presentation Boxes
http://bankofcanada.wordpress.com/
Customizable content widgets able to display posts, pages, links and plain text in a custom style.
2.2
Authors: Aaron Berg, Dale Taylor, Nelson Lai, Yefei Tang, Xueyan Bai, Zafor Ahmed, Fran&ccedil;ois Fortin, Lindsay Newton
http://www.bankofcanada.ca/
*/

/** EXTERNAL JAVASCRIPT **/

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

/**
* Shows the appropriate drop down menus depending
* on the content type that is currently selected.
*
* @param int content_id_num - the numerical index of the content type
* @return void
*/
function item_content_prompt( content_id_num ) {
	var textbox;
	var selectbox; // Item type box
	var selectbox2; // Display options (show summary, content, etc)
	var option; // 'Item and content' option

	textbox = document.getElementById( 'content_' + content_id_num );
	selectbox = document.getElementById( 'type_' + content_id_num );
	selectbox2 = document.getElementById( 'callout_id_' + content_id_num );
	option = document.getElementById( 'item_and_content_' + content_id_num );

	// Determines whether to hide or show the style drop down
	if ( selectbox.selectedIndex == 2 ) {
		textbox.value = 'Enter text here';
		selectbox2.style.display = 'none';
	} else {	
		textbox.value = 'Enter ID here';
		selectbox2.style.display = '';
	}
	// Determines which set of style options
	if( selectbox.selectedIndex == 1 ) {
		option.style.display = '';
	} else {
		option.style.display = 'none';
		selectbox2.selectedIndex = 0;
	}
	textbox.select();
}

function clearValue( number ) {
	itemName = 'content_'+number;
	elem = document.getElementById( itemName );
	elem.value = '';
}

/**
* Determines whether or not the text box to display
* a custom number of pboxes is necessary.
*
* @return void
*/
function updatePBoxAmount() {
	if ( jQuery( '#pboxamount' ).val() == 'other' ) {
		jQuery( '#manual_pbox_size' ).show();
	} else {
		jQuery( '#manual_pbox_size' ).hide();
	}
}

/**
* Accepts the array representation of a Sortable
* object extracts all the information from its DOM
* elements and returns the values to the server
* for the AJAX request carried out by the jQuery
* method seen in pb.edit.php.
*
* @param Array newOrdering - array of the order the Sortable field is in
* @return Array - an array of serialized strings containing all form info
*/
function bindElement( newOrdering ) {
	var newOrder; // the order of the fields as they been 'sorted'
	var newContent; // the values of the content boxes
	var newCallout; // the style/display property of the element
	var newType; // the type (text/page/attachmentpost/link) of the element
	
	//serialize everything
	newContent = jQuery( 'input.contentField' ).serialize();
	newOrder= jQuery( 'input.orderField' ).serialize();
	newCallout = jQuery( '.calloutField' ).serialize();
	newType = jQuery( '.typeField' ).serialize();
	
	//build and return the resultant array
	return_array = new Array();
	return_array[0] = '&' + newOrder;
	return_array[1] = '&' + newContent;
	return_array[2] = '&' + newCallout;
	return_array[3] = '&' + newType;
	return return_array;
}

/**
* Removes elements from the edit form
* on the PBox page
*
* @return void
*/
function removeFormField(id) {
	jQuery(id).remove();
}