=== RSSPhoto ===
Contributor: spencerkellis
Donate link: http://www.spencerkellis.net/
Tags: RSS, Atom, photoblog, photo, widget
Requires at least: 2.8
Tested up to: 2.8.3
Stable tag: N/A

A simple widget to display photos from an RSS or Atom feed.

== Description ==

RSSPhoto is a Wordpress widget to display photos from RSS and Atom feeds. It is very easy to configure.  The 
images load with an animated slide down (if jQuery is available).

RSSPhoto requires the SimplePie Core Wordpress plugin to parse RSS and Atom feeds.  It also requires a cache 
directory in /wp-content/cache, writable by the server, to store thumbnails in.

== Installation ==

Here are the basic installation instructions:

   1. If it doesn't already exist, create the directory `/wp-content/cache` and give it permissions of 755
   2. Upload rssphoto.php to the `/wp-content/plugins/` directory
   3. Activate the plugin through the 'Plugins' menu in Wordpress
   4. Drag the widget to the sidebar in the 'Widgets' section of the 'Appearance' menu in Wordpress
   5. Configure the widget by specifying title, url, max dimensions, and image selection.
         1. Title: text that appears over the image in the sidebar
         2. URL: address of the RSS or Atom feed
         3. Max dimensions: the size in pixels of the largest side of the thumbnail

Probably the easiest way to accomplish step 1 is through an FTP program.  If you're interested, here's how to do it on the command line:

   1. cd {blog-dir}/wp-content
   2. mkdir cache
   3. chmod 755 cache

== Frequently Asked Questions ==

= How do I change the title, feed URL, or dimensions? =

After your widget appears in the sidebar, go to the 'Widgets' section under the 'Appearance' menu in Wordpress and open
the settings for the widget (click the down arrow in the widget titlebar and the form will appear).  Modify the fields as 
needed and click save.

== Screenshots ==

   1. http://blog.spencerkellis.net/wp-content/uploads/2009/08/screen1.jpg
   2. http://blog.spencerkellis.net/wp-content/uploads/2009/08/screen2.jpg

== Changelog ==

  v0.1
    * Original version
    * Specify widget title
    * Accepts any RSS or Atom feed URL
    * Customize thumbnail size
    * Select random or most recent image selection method
    * jQuery image sliding effects with graceful degradation to plain Javascript

