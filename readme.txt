=== PBox ===
Contributors: bankofcanada, ncrawford, jairus
Tags: presentation, widget, formatting, display, sidebar, style, template, text, theme, external content, images, links, posts, pages, media, custom, CSS, CMS
Requires at least: 2.8
Tested up to: 2.9.2
Stable tag: 2.3

Customizable presentation widget to display posts, pages, external content, links, images, and plain text in a customized style.

== Description ==

PBox lets you create widgets to display content from pages, posts, and external URLs; you can also display customizable links, text, and images. Easily stylize your presentation box widget with your own custom HTML and CSS. Incorporate your styles as part of your theme for multi-themed projects or make it part of the plugin for modularity.

With PBox, you can **standardize the display of custom content in the sidebar** with your own themes.

**Bring attention to important notices** or information with the provided example "Alert" style!

* For pages/posts/files, display a link with (optionally) the content or excerpt.
* Display (optionally filtered) external content from another page or site with the External URI type.
* Display thumbnails of your media library images, with the option to display in full size with Fancybox!

Credit to [Mark James](http://famfamfam.com/) for some of the icons used for link views.

= Tips =

 * PBox is fully compatible with the [XWidgets](http://wordpress.org/extend/plugins/xwidgets/) plugin, allowing users to add PBoxes (and other widgets) to their page sidebars on a per page basis.

 * You can add a filter to the content extracted from External URIs with 'pbox-external-content'
 
 * If you have a PBox with a media image, it will use the [FancyBox](http://wordpress.org/extend/plugins/fancybox-for-wordpress/) plugin to enable full-view of the image if you click on it.

== Installation ==

1. Download the zip file, unzip it, and place it in your /wp-content/plugins directory.  
2. Activate the plugin via the 'Plugins' menu in WordPress.  
3. To change the location of the CSS styles to your theme directory, selct "PBox Styles" from the PBox menu on the left and check "Store stylesheet in theme directory".
4. Select "Manage PBoxes" from the PBox menu. Here you can create and manage your PBoxes.  
5. Select "PBox Styles" to add a style or customize existing styles.  In order to customize an external CSS file, the PBox plugin directory must be writable.
6. Add or remove PBoxes from your template by adding them in the Widgets panel. 

== Screenshots ==

1. Example view of a PBox using pre-loaded "Alert" style with a custom text field and page link with exceprt.

== Frequently Asked Questions ==

= How do I include icons representing custom file extensions for links? =

At present this requires some knowledge of PHP.  Open the file in the PBox directory called pb.config.php.

In this file there is an array called extra_icons.  Each extra icon requires a name, a pattern to match and the name of the icon image that you've saved in the 'images' directory.  The pattern field is a regular expression using the PCRE pattern syntax.  

For examples and instructions on how to write PCRE patterns, See [Pattern Syntax](http://ca2.php.net/manual/en/reference.pcre.pattern.syntax.php "Pattern Syntax information at PHP.Net") and [Pattern Modifiers](http://ca2.php.net/manual/en/reference.pcre.pattern.modifiers.php "Pattern Modifier information at PHP.Net").  

= Does this plugin work in versions of WordPress before 2.8? =

No. PBox relies on the new Widget API introduced with the release of 2.8. It will not function correctly on older installs of WordPress.

= How do I store the CSS styles in my theme directory instead of the plugin directory? =

To change the location of the CSS styles to your theme directory, selct "PBox Styles" from the PBox menu on the left and check "Store stylesheet in theme directory".

= How do I make my own PBox style? =

*In order to customize an external CSS file, the PBox plugin directory must be writable.*

Select "PBox Styles" to add a style or customize existing styles.  Enter the name of the new style then click "Create".

Fill in the styling option text boxes as necessary, using the short codes provided at the top of the page.  If you run into any troubles, take a look at the options and CSS of any one of the three provided styles (Default-Style, Alert, and InfoByte).

= How do I make a PBox like the example in "screenshots"? =

Let's assume you're using WP 2.9.2 and have downloaded/installed/activated the plugin.

On the left-hand navigation menu, look for "PBox", click the drop down, and then click "Manage PBoxes".  Then, in the top left, there is a section called "Create new presentation box". Enter a title (in the demo we used "PBox Demo") and click "Create".  You'll then be taken to a page where you can modify the PBox contents. The demo provided shows one "text" item and one "page" item.

To do this, select "text" for the first row, and beside it, enter the text you wish to appear (in the demo, we used "You can have multiple types of items in your PBox!".  In the second row, select "page/post/file", and enter the ID of the page you wish to link to. For the demo, we also chose "item and excerpt" to have an excerpt of the page appear.  Now, click "Save Changes" at the bottom. (Note the ID of the PBox that was created.)

Finally, go to your Widget manager and drag a "Presentation Box" widget onto your sidebar.  Enter the PBox ID, select a style (in the demo we used "Alert"), and click "Save".

That's all!  When viewing your blog, you should now have a PBox similar to our example!

== Changelog ==
= 2.3 =
* Fixed a bug that caused postmea key name change not to update from old versions
* Fixed a bug where PBoxes couldn't be deleted
* Fixed a bug that caused PHP/WP error on installation
* Fixed a bug that caused "Delete" link in PBox Styles manager to display incorrectly
* Fixed a bug that caused the "Edit this PBox" link to break when using different permalink formats
* Fixed a bug that caused the pbox.css style sheet to not be included
* Fixed a bug that caused some PBox content to be duplicated when logged in as administrator
* PBox can have any plugin directory name now (not just "pbox")
* Made an option to allow pbox.css stylesheet to be in either theme or plugin directory. Default is plugin dir
* Added new content types (external URI, image)
= 2.2 =
* Initial public version

== Upgrade Notice ==
= 2.3 =
Functional bug fixes, new example styles, and new content types. Upgrade strongly recommended.