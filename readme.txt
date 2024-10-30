=== Plugin Name ===
Contributors: ukthebunny
Link: http://www.x2labs.com/wp-dev/instructables-plugin/
Tags: instructables, feed, rss, xml, list, projects
Requires at least: 3.0.1
Tested up to: 4.8.1
Stable tag: 1
License: GPL
License URI: https://raw.githubusercontent.com/MrRedBeard/Instructables-Wordpress-Plugin/master/LICENSE

== Description ==

Display previews of Instructables Projects on your site linking to the source. Projects can be retrieved from Instructables by username or keyword. You can display the title, thumbnail (optional) and description or in tiles which display the thumbnail and title. In a list of a user's Instructables or a list of Instructables by keyword on your site.

Improved the layout and style - Should now be more compatible with themes written to WordPress Standards
Users can now define and store feed definitions
Multiple keywords can be used
A groups Instructables option has been added
Other feed types added

[Working Demo http://www.x2labs.com/wp-dev/instructables-plugin/](http://www.x2labs.com/wp-dev/instructables-plugin/ "Demo")

== Installation ==

1. Upload the contents of instructables.zip to the `/wp-content/plugins/instructables/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

If your hosting solution does not support simplexml_load_file() then please contact me. I may need to develop a curl method if this is a widespread issue.

== Frequently Asked Questions ==

None yet so ask your questions.

= How do I use this plugin? =
Define Feeds under Settings
Edit or Create page use Instructables icon on editor to place new feed

Legacy ShortCodes Method
Display a user's projects:
[instructablesUP username="MrRedBeard" num="2" thumb="true" tileview="true"]

Display a list of projects by keyword:
[instructablesKW keyword="tent" num="3" thumb="true" tileview="true"]

Display a list of a user's favorite projects
[instructablesFP username="MrRedBeard" num="5" thumb="true" tileview="false"]

== Screenshots ==

1. Display Instructables Projects as Posts
2. Display Instructables Projects as Tiles
3. Instructables Feeds from Editor
4. Instructables Feed Editor
5. Instructables Feed List
6. Screenshots of the latest features 

== Changelog ==
= 2.0.4 =
* ShortCode mistake - Found that I echoed the content instead of returning it.

= 2.0.3 =
* Corrected an issue for servers that have case sensitive issues with paths

= 2.0.2 =
* Corrected a path issue

= 2.0.1 =
* Corrected an issue caused by GitHub Desktop which reverted the stylesheet

= 2.0.0 =
* Almost completly re-written
* Should now be more compatible with themes written to WordPress Standards
* Users can now define and store feed definitions
* Multiple keywords can be used
* A groups Instructables option has been added
* Other feed types added
* Improved the layout and style
* Backwards compatibility
* Verified working on latest WP version

= 1.2.1 =
* Updated info
* Verified working on latest WP version

= 1.2.0 =
* Added function to display a user's favorites
* Made functions more efficient and removed redundancies
* Minor corrections 

= 1.1.0 =
* Add the function to display items in tiles.
* I think I have corrected the screenshots and added header image.
* Added GPLv2 license file.

= 1.0.0 =
* New Plugin.
* This is the initial release.
* Displays by username and by keyword.

== Upgrade Notice ==

= 1.1.1 =
* Minor corrections

= 1.1.0 =
* Add the function to display items in tiles.
* I think I have corrected the screenshots and added header image.
* Added GPLv2 license file.

= 1.0.0 =
* This is the initial release.
