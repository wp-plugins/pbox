=== Plugin Name ===
Contributors: bankofcanada
Tags: widget, cms, content, formatting, links, menu, sidebar, style, template, text, theme
Requires at least: 2.8
Tested up to: 2.9 RC1
Stable tag: 2.2

Customizable content widgets able to display posts, pages, links and plain text in a custom style.

== Description ==

PBox allows the creation of custom content boxes containing pages, posts, links and text displayed in the way 
you want them to display. These content boxes can be styled using custom HTML and CSS and then can be 
dragged onto sidebars as widgets allowing you to create highly customizable dynamic sidebar content and display 
it in a way that best suits your site.

Credit to [Mark James](http://famfamfam.com/) for some of the icons used for link views.

= Tips =

 * PBox is fully compatible with the [XWidgets](http://wordpress.org/extend/plugins/xwidgets/) plugin, allowing users to add PBoxes (and other widgets) to their page sidebars on a per page basis.
 
= Future Plans =

 * French translations of the admin interface.
 * Custom link icons via the admin interface rather than a config file.

== Installation ==

1. Download the pb.zip file, unzip it and place it in your /wp-content/plugins directory.  
1. Activate the plugin via the 'Plugins' menu in WordPress.  
1. Select "Manage PBoxes" from the PBox menu. Here you can create your PBoxes.  
1. Select "PBox Styles" to add a style or customize existing styles.  In order to customize an external CSS file, the theme directory of your current directory must be writable.
1. Add or remove PBoxes from your template by adding them in the Widgets panel.  

== Frequently Asked Questions ==

= How do I include icons representing custom file extensions for links? =

As of right now this requires some knowledge of PHP.  Open the file in the PBox directory called pb.config.php.  
In this file there is an array called extra_icons.  Each extra icon requires a name, a pattern to match and the name
of the icon image that you've saved in the 'images' directory.  The pattern field is a regular expression using the PCRE 
pattern syntax.  For examples and instructions on how to write PCRE patterns, See [Pattern Syntax](http://ca2.php.net/manual/en/reference.pcre.pattern.syntax.php "Pattern Syntax information at PHP.Net") and 
[Pattern Modifiers](http://ca2.php.net/manual/en/reference.pcre.pattern.modifiers.php "Pattern Modifier information at PHP.Net").  

= Does this plugin work in versions of WordPress before 2.8? =

No. PBox relies on the new Widget API introduced with the release of 2.8. It will not function correctly on older installs of WordPress.

== Changelog ==

= 2.2 =
* Initial public version