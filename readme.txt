=== RSSPhoto ===
Contributor: spencerkellis
Donation Link: http://blog.spencerkellis.net/projects/rssphoto
Tags: RSS, Atom, photoblog, photo, photography, widget, jQuery, slideshow, multi-widget, shortcode
Requires at least: 2.8
Tested up to: 2.8.6
Stable tag: 0.6.3

A customizable plugin to display photos from an RSS or Atom feed as a widget or shortcode.

== Description ==

RSSPhoto is a Wordpress plugin to display photos from RSS and Atom feeds. It includes a widget for easy addition to a sidebar, or it can be configured by shortcode to display on any page.  Easy theme integration is also possible.  RSSPhoto includes jQuery-powered, cross-browser compatible slideshow as well as static image display.

RSSPhoto requires the SimplePie Core Wordpress plugin to parse RSS and Atom feeds.  A cache directory, writable by the server, is required for thumbnail storage.  The GD library is required for generating thumbnails.  If the GD library is not present, the script will default to displaying images with the img width/height attributes forced to thumbnail size.


== Installation ==

Here are the basic installation instructions:

   1. Install the SimplePie Core plugin if it's not already installed ([Link](http://wordpress.org/extend/plugins/simplepie-core/ "SimplePie Core plugin")).
   2. If it doesn't already exist, create the directory `/wp-content/cache` and give it permissions of 755
   3. Upload all files to the `/wp-content/plugins/` directory (consider creating an rssphoto subdirectory to hold the plugin files)
   4. Activate the plugin through the 'Plugins' menu in Wordpress

Probably the easiest way to accomplish step 1 is through an FTP program.  If you're interested, here's how to do it on the command line:

   1. cd {blog-dir}/wp-content
   2. mkdir cache
   3. chmod 755 cache

To use the widget:

   1. Navigate to the 'Widgets' section of the 'Appearance' menu in Wordpress
   2. Drag the widget to the sidebar
   3. Configure the widget as needed

To use the shortcode:

   1. Edit the page you want to display the images
   2. Add the following text to the page:

      [rssphoto url="your.url.com"]

   3. Include any of the following attributes (see descriptions of these settings below)

      * output="Slideshow|Static"
      * url="your.url.com"
      * item_sel="Random|Most Recent"
      * num_item="[Number of feed items]"
      * num_img="[Number of images per feed item]"
      * img_sel="Random|Most Recent"
      * fixed="Width|Height|Max"
      * size="[size in pixels]"

To integrate with a theme:

   1. The plugin needs to be installed and activated.
   2. Copy and paste the contents of the file RSSPhotoTheme.functions.php to the end of the file functions.php in your theme directory.
   3. Declare RSSPhoto settings (multiple instances are supported).
   4. Call the function display_rssphoto() from your theme (e.g., sidebar.php). An example of the last two steps:

      `<?php
      $settings[0]['title']='RSSPhoto';
      $settings[0]['url']='http://photography.spencerkellis.net/rss.php';
      $settings[0]['output']='Slideshow';
      $settings[0]['num_item']=10;
      $settings[0]['interval']=10000;
      $settings[0]['fixed']='Height';
      $settings[0]['size']=80;
      $settings[0]['before_title']='<h2>';
      $settings[0]['after_title']='</h2>';
      $settings[0]['before_html']='<li>';
      $settings[0]['after_html']='</li>';
      display_rssphoto($settings[0]);
      ?>`

Here's a quick description of the settings:

   1. Title: text that appears over the image in the sidebar.
   2. Output: images will load as a slideshow or can be displayed statically
   3. URL: address of the RSS or Atom feed.
   4. Item Selection: RSSPhoto will choose a random feed item, or the most recent feed item
   5. # Items: choose how many feed items to display
   6. # Images per Item: how many images embedded in each feed item to display
   7. Image selection: the script can randomly select images or just display the first image in the feed item
   8. Fixed dimension: select whether the width, height, or longest side should be fixed.
   9. Size (px): size in pixels of the width, height, or longest side (previously selected).


== Frequently Asked Questions ==

= How do I change the title, feed URL, or dimensions? =

After your widget appears in the sidebar, go to the 'Widgets' section under the 'Appearance' menu in Wordpress and open the settings for the widget (click the down arrow in the widget titlebar and the form will appear).  Modify the fields as needed and click save.

= I'm getting an error about the SimplePie class not being found.  What's wrong? =

Here's what the error might look like:

Fatal error: Class .SimplePie. not found in /home/username/public_html/wp-content/plugins/rssphoto/rssphoto.php on line 40

If you receive this error, the most likely problem is that the SimplePie Core plugin is not installed or activated.  Here's a link to the [SimplePie Core plugin](http://wordpress.org/extend/plugins/simplepie-core/ "SimplePie Core plugin").

= I can't seem to get thumbnails larger than X by Y pixels =

Check to make sure the photos in your feed are larger than X by Y pixels - RSSPhoto uses the image actually embedded in the feed to generate locally cached thumbnails.

= Is there a way to prevent very small images from being displayed? =

Yes, you can set a variable to require a minimum size (in pixels) of either width or height.  In `RSSPhoto.class.php`, look for 

  var $min_size = 10;

And change the value as needed (default is 10 pixels).

= My feed doesn't display any photos; W3C Feed Validation says it's valid but has a warning about wrong media type =

If you get a warning from the [W3C Feed Validation Service](http://validator.w3.org "W3C Feed Validation Service") about your feed being served with the wrong media type, and RSSPhoto doesn't display your images, it may be an issue where SimplePie refuses to parse the feed because of the incorrect media type.  Open `RSSPhoto.class.php` and set the `$force_feed` variable to `true`:

var $force_feed = true;


== Screenshots ==

1.  What the plugin looks like in my [Sidebar](http://blog.spencerkellis.net "RSSPhoto loaded in my sidebar").
2.  The plugin settings in WP Admin -> Appearances -> Widgets.


== Changelog ==

v0.6.3

* Added support for theme integration


v0.6.2

* Added a method for retrieving images from different portions of an RSS 2.0 feed (Atom feeds remain the same)


v0.6.1

* Add support for images contained in RSS enclosure tags
* Fixed an annoyance where slideshow would play with only one image


v0.6

* Add shortcode support allowing RSSPhoto to be displayed on any page
* Further improved code organization, separating RSSPhoto code from shortcode and widget code.
* Re-introduced multi-widget support


v0.5.1

* Bug fix; v0.5 required PHP5. v0.5.1 should restore PHP4 support.


v0.5

* Added jQuery slideshow option (default)
* Intelligently selects content or description tag based on feed type
* Streamlined options to make widget configuration simpler
* Improved code organization with better class structure and readability
* Separated Javascript, CSS, and PHP; separated HTML internally
* !Important: Removed multi-widget support!
* Added support for solution to display images from a feed with incorrectly identified media type


v0.4

* Added support for multiple images and multiple feed items
* Added support for filtering out images smaller than a user-defined pixel value (height or width)
* Reconfigured to the Widget options panel to reduce the height


v0.3.3

* Added support for PNGs and GIFs


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

