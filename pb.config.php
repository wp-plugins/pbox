<?php
/**
PBox
Customizable content widgets able to display posts, pages, links and plain text in a custom style.
2.3
Authors: Aaron Berg, Dale Taylor, Nelson Lai, Yefei Tang, Xueyan Bai, Zafor Ahmed, Fran&ccedil;ois Fortin, Lindsay Newton, Nicholas Crawford
http://www.bankofcanada.ca/
*/

/** CUSTOM CONFIG FILE **/

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
*
* Authors: Aaron Berg, Dale Taylor, Nelson Lai, Yefei Tang, Xueyan Bai, Zafor Ahmed, Fran&ccedil;ois Fortin, Lindsay Newton
* Version: 2.2
*
* In this config you can set icons that will either overwrite the default icons 
* or add support for site specific file extensions. 
*
* Three components need to be present to match a file extension to a custom icon:
* name = the alt tag to be associated to the icon
* regex = the regular expression representing the URLs your icon should match ( see 
* 			   http://www.php.net/manual/en/reference.pcre.pattern.syntax.php for information
*  			   about regular expression patterns.
* filename = the name of the image saved in the images directory within the PBox plugin directory.
*/

/**
* Example (RAR archive) :
*
* $extra_icons[] = array( 'name' => 'RAR Archive', 'regex' => '/.\.rar$/', 'filename' => 'raricon.gif' );
*/