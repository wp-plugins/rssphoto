=== RSSPhoto ===
Contributor: spencerkellis
Tags: RSS, Atom, photoblog, photo, widget
Requires at least: 2.8
Tested up to: 2.8.3
Stable tag: 0.1.2

A simple widget to display photos from an RSS or Atom feed.

== Description ==

RSSPhoto is a Wordpress widget to display photos from RSS and Atom feeds. It is very easy to configure.  The 
images load with an animated slide down (if jQuery is available).

RSSPhoto requires the SimplePie Core Wordpress plugin to parse RSS and Atom feeds.  It also requires a cache 
directory in /wp-content/cache, writable by the server, to store thumbnails in.

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

   1. Title: text that appears over the image in the sidebar
   2. URL: address of the RSS or Atom feed
   3. Max dimensions: the size in pixels of the largest side of the thumbnail
   4. Image selection: you can choose to have the script randomly select images or just display the first image in the most recent feed entry.


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

== Screenshots ==

1.  What the plugin looks like in my [Sidebar](http://blog.spencerkellis.net "RSSPhoto loaded in my sidebar").
2.  The plugin settings in WP Admin -> Appearances -> Widgets.


== Changelog ==

v0.1

* Original version
* Specify widget title
* Accepts any RSS or Atom feed URL
* Customize thumbnail size
* Select random or most recent image selection method
* jQuery image sliding effects with graceful degradation to plain Javascript

v0.1.1
* Fixed an issue where the Max dimensions field had the wrong label
