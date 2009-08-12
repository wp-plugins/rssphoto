=== RSSPhoto ===
Contributor: spencerkellis
Donation Link: http://blog.spencerkellis.net/projects/rssphoto
Tags: RSS, Atom, photoblog, photo, widget
Requires at least: 2.8
Tested up to: 2.8.4
Stable tag: 0.3.2

A customizable widget to display photos from an RSS or Atom feed.

== Description ==

RSSPhoto is a Wordpress widget to display photos from RSS and Atom feeds. It is very easy to configure.  The 
images load with an animated slide down (if jQuery is available).

RSSPhoto requires the SimplePie Core Wordpress plugin to parse RSS and Atom feeds.  It also requires a cache 
directory in /wp-content/cache, writable by the server, to store thumbnails in.

The GD library is required for generating thumbnails.  If the GD library is not present, the script will default
to displaying images with the img width/height attributes forced to thumbnail size.


== Installation ==

Here are the basic installation instructions:

   1. Install the SimplePie Core plugin if it's not already installed ([Link](http://wordpress.org/extend/plugins/simplepie-core/ "SimplePie Core plugin")).
   2. If it doesn't already exist, create the directory `/wp-content/cache` and give it permissions of 755
   3. Upload rssphoto.php to the `/wp-content/plugins/` directory
   4. Activate the plugin through the 'Plugins' menu in Wordpress
   5. Drag the widget to the sidebar in the 'Widgets' section of the 'Appearance' menu in Wordpress
   6. Configure the widget by specifying title, url, max dimensions, and image selection.

Probably the easiest way to accomplish step 1 is through an FTP program.  If you're interested, here's how to do it on the command line:

   1. cd {blog-dir}/wp-content
   2. mkdir cache
   3. chmod 755 cache

Here's a quick description of the settings:

   1. Title: text that appears over the image in the sidebar.
   2. URL: address of the RSS or Atom feed.
   3. Feed Item Selection: RSSPhoto will choose a random feed item, or the most recent feed item
   4. Image selection: the script can randomly select images or just display the first image in the feed item
   5. Pull images from: some feeds have thumbnail previews in the item description rather than the item content.
   6. Fixed dimension: select whether the width, height, or longest side should be fixed.
   7. Dimension size: size in pixels of the width, height, or longest side (previously selected).


== Frequently Asked Questions ==

= How do I change the title, feed URL, or dimensions? =

After your widget appears in the sidebar, go to the 'Widgets' section under the 'Appearance' menu in Wordpress and open
the settings for the widget (click the down arrow in the widget titlebar and the form will appear).  Modify the fields as 
needed and click save.

= I'm getting an error about the SimplePie class not being found.  What's wrong? =

Here's what the error might look like:

Fatal error: Class .SimplePie. not found in /home/username/public_html/wp-content/plugins/rssphoto/rssphoto.php on line 40

If you receive this error, the most likely problem is that the SimplePie Core plugin is not installed or activated.  Here's
a link to the [SimplePie Core plugin](http://wordpress.org/extend/plugins/simplepie-core/ "SimplePie Core plugin").

= Why won't any images load from my feed, or why do strange images load? =

Some feeds have thumbnails in a `description` tag instead of the `content` tag.  In the Widget settings, change "Pull image from" to 
Description and see if it makes a difference.


== Screenshots ==

1.  What the plugin looks like in my [Sidebar](http://blog.spencerkellis.net "RSSPhoto loaded in my sidebar").
2.  The plugin settings in WP Admin -> Appearances -> Widgets.


== Changelog ==

v0.3.2

* Improvement on previous bug fix to use bloginfo 'wpurl' variable to form thumbnail URL
* Separate out image selection from feed item selection (so you can select a random image from the most recent
  feed item or vice versa)
* Improve the display of the Widgets settings as more options were becoming available


v0.3.1

* Major bug fix with thumbnail URLs pointing to the wrong location


v0.3

* Allow multiple RSSPhoto widgets to be present simultaneously
* Selection of fixed width, height, or max dimension for thumbnails
* Selection of pulling images from Content tags or Description tags from feeds
* Fixed a bug where changing the thumbnail size wouldn't be reflected due to cached thumbnails with the same filename


v0.2

* Rolled up all other bug fixes from 0.1.* plus fixed a bug where there was no default value for the Image Selection field.


v0.1.2

* Fixed a bug where the title may not have been saved properly


v0.1.1

* Fixed an issue where the Max dimensions field had the wrong label


v0.1

* Original version
* Specify widget title
* Accepts any RSS or Atom feed URL
* Customize thumbnail size
* Select random or most recent image selection method
* jQuery image sliding effects with graceful degradation to plain Javascript

