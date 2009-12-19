=== RSSPhoto ===
Contributor: spencerkellis
Donation Link: http://blog.spencerkellis.net/projects/rssphoto
Tags: RSS, Atom, photoblog, photo, photography, widget, jQuery, slideshow, multi-widget, shortcode
Requires at least: 2.8
Tested up to: 2.9
Stable tag: 0.8.1

A customizable plugin to display photos from an RSS or Atom feed as a widget or shortcode.

== Description ==

RSSPhoto is a Wordpress plugin to display photos from RSS and Atom feeds. It includes a widget for easy addition to a sidebar, or it can be configured by shortcode to display on any page.  Easy theme integration is also possible.  RSSPhoto includes jQuery-powered, cross-browser compatible slideshow as well as static image display.

RSSPhoto uses the built-in Wordpress functions to parse RSS and Atom feeds (which are based on SimplePie).  A cache directory, writable by the server, is required for thumbnail storage.  The GD library is required for generating thumbnails.


== Installation ==

RSSPhoto install simply requires creating a writable directory in `wp-content/cache` to store thumbnails, and installing/activating the plugin itself:

  1. If it doesn't already exist, create the directory `wp-content/cache` and give it permissions of 755
  2. Upload all files to the `wp-content/plugins/rssphoto` directory
  3. Activate the plugin through the 'Plugins' menu in Wordpress

To use the widget:

   1. Navigate to the 'Widgets' section of the 'Appearance' menu in Wordpress
   2. Drag the widget to the sidebar
   3. Configure the widget as needed

To use the shortcode:

   1. Edit the page you want to display the images
   2. Add the following text to the page:

      [rssphoto url="http://your.url.com/feed.xml"]

   3. Include any of the following attributes (see descriptions of these settings below)

      * title="Title"
      * url="http://your.url.com/feed.xml"
      * height=150
      * width=185
      * img_sel="Most Recent"
      * num_img=1
      * item_sel="Random"
      * num_item=10
      * show_title=1
      * show_img_title=1
      * output="Slideshow2"
      * interval=6000
      * min_size=10
      * max_size=500

To integrate with a theme:

   1. The plugin needs to be installed and activated.
   2. Copy and paste the contents of the file `RSSPhotoTheme.functions.php` to the end of the file `functions.php` in your theme directory.
   3. Declare RSSPhoto settings (multiple instances are supported).
   4. Call the function `display_rssphoto()` from your theme (e.g., `sidebar.php`). An example of the last two steps:

      `<?php
      $settings[0]['title']='RSSPhoto';
      $settings[0]['url']='http://photography.spencerkellis.net/rss.php';
      $settings[0]['height']=150;
      $settings[0]['width']=185;
      $settings[0]['img_sel']='Random';
      $settings[0]['num_img']=1;
      $settings[0]['item_sel']='Random';
      $settings[0]['num_item']=10;
      $settings[0]['show_title']=1;
      $settings[0]['show_img_title']=1;
      $settings[0]['output']='Slideshow2';
      $settings[0]['interval']=6000;
      $settings[0]['before_title']='<h2>';
      $settings[0]['after_title']='</h2>';
      $settings[0]['before_html']='<li>';
      $settings[0]['after_html']='</li>';
      display_rssphoto($settings[0]);
      ?>`

Here's a quick description of the settings:

   1. (title) *Title*: Text that appears over the RSSPhoto image(s).  *"String in quotes"*
   2. (url) *URL*: address of the RSS or Atom feed. *"http://your.url.com/feed.xml"*
   3. (height) *Height*: height of the RSSPhoto images in pixels; or, 'variable' to maintain the aspect ratio given the specified width (see #4). *[Number]|"variable"*
   4. (width) *Width*: width of the RSSPhoto images in pixels; or, 'variable' to maintain the aspect ratio given the specified height (see #3). *[Number]|"variable"*
   5. (img_sel) *Image selection*: the script can randomly select images or just display the first image in the feed item. *"Random"|"Most Recent"*
   6. (num_img) *# Images per Item*: how many images embedded in each feed item to display. *[Number]*
   7. (item_sel) *Item Selection*: RSSPhoto will choose a random feed item, or the most recent feed item. *"Random"|"Most Recent"*
   8. (num_item) *# Items*: choose how many feed items to display. *[Number]*
   9. (show_title) *Show Title*: whether to display the main RSSPhoto title over the images. *1|0*
   10. (show_img_title) *Show Image Titles*: whether to display the titles of each displayed thumbnail. *1|0*
   11. (output) *Output*: images will load as a slideshow or can be displayed statically.  *"Slideshow2"|"Slideshow"|"Static"*
   12. (interval) *Slideshow Interval*: the amount of time in milliseconds to wait between image transitions for the slideshows. *[Number]*

Note that for the `height` and `width` properties you should only set one or the other to "variable", since RSSPhoto will use the other integer-valued dimension to fix the aspect ratio.


== Frequently Asked Questions ==

= How do I change the title, feed URL, or dimensions for a widget? =

After your widget appears in the sidebar, go to the 'Widgets' section under the 'Appearance' menu in Wordpress and open the settings for the widget (click the down arrow in the widget titlebar and the form will appear).  Modify the fields as needed and click save.

= Is there a way to prevent very small images from being displayed? =

Yes, you can set a variable to require a minimum size (in pixels) of either width or height.  In `RSSPhoto.class.php`, look for 

`var $min_size = 10;`

And change the value as needed (default is 10 pixels).

= My feed doesn't display any photos and there are no problems with the feed validation =

RSS feeds can be implemented in numerous ways.  RSSPhoto attempts to intelligently find the pictures in an RSS feed, but sometimes you need to point it in the right direction.  In `RSSPhoto.class.php`, try changing the value of the variable `$rss_type_src` to one of the following values: 'Choose' (default), 'Description', 'Content', or 'Enclosures'.

= My feed doesn't display any photos; W3C Feed Validation says it's valid but has a warning about wrong media type =

If you get a warning from the [W3C Feed Validation Service](http://validator.w3.org "W3C Feed Validation Service") about your feed being served with the wrong media type, and RSSPhoto doesn't display your images, you may need to install the SimplePie Core plugin and force RSSPhoto to use it.  The integrated SimplePie refuses to parse the feed because of the incorrect media type.  Follow the FAQ point below to enable SimplePie Core.  Then, open `RSSPhoto.class.php` and set the `$force_feed` variable to `true`:

`var $force_feed = true;`

= How do I force RSSPhoto to use the SimplePie Core plugin so I have access to more feed-level options for troubleshooting? =

To be clear, this option is available for fringe cases and is not expected to be commonly used (hence getting down and dirty with the code).  An example is where the XML feed does not set its headers correctly and the SimplePie "force_feed" option must be set.

First, de-activate RSSPhoto.  Next, install and activate the SimplePie Core plugin (*not* the full SimplePie plugin).  Here's a link to the [SimplePie Core plugin](http://wordpress.org/extend/plugins/simplepie-core/ "SimplePie Core plugin").

Now, edit `RSSPhoto.class.php` and change the value of the variable `$parser` from 'built-in' to 'simplepie-core':

`var $parser = 'simplepie-core';`

Re-activate RSSPhoto.  That's it!

= I'm getting an error about the SimplePie class not being found.  What's wrong? =

This should *only* happen if you have followed the directions above to force RSSPhoto to use the SimplePie Core plugin.  

Here's what the error might look like:

Fatal error: Class .SimplePie. not found in /home/username/public_html/wp-content/plugins/rssphoto/rssphoto.php on line 40

If you receive this error, the most likely problem is that the SimplePie Core plugin is not installed or activated.  Here's a link to the [SimplePie Core plugin](http://wordpress.org/extend/plugins/simplepie-core/ "SimplePie Core plugin").

= I'm still having problems.  What should I do? =

Please feel free to leave a comment at the [plugin's website](http:/blog.spencerkellis.net/projects/rssphoto "RSSPhoto Website").  I usually respond fairly quickly.

If you're interested in getting your hands dirty, there's a debug mode you can enable.  In `RSSPhoto.class.php`, set the `$debug` variable to 1:

`var $debug = 1;`

By default, the debug output is hidden in HTML comments (&lt;!-- and --&gt;), so view the page source to find the debug messages.  This could offer some useful information and could help in leaving detailed comments for troubleshooting.


== Screenshots ==

1.  Slideshow v2 with image title shown [Sidebar](http://blog.spencerkellis.net "RSSPhoto loaded in my sidebar").
2.  The plugin settings in WP Admin -> Appearances -> Widgets.


== Changelog ==

v0.8.2

* Bug fix to avoid using absolute paths when saving thumbnails to the cache directory


v0.8.1

* Improved display of Slideshow v2 in IE; known issue with opacity but there are no black or white bars any more
* Fixed a layout glitch to clear after the RSSPhoto div so elements below will align properly


v0.8

* Introduction of Slideshow v2: principal improvement is the ability to fade in and out image titles when the user moves the cursor over the image.
* Simplified method for specifying dimensions: set the width and height directly, and RSSPhoto will automatically general thumbnails without distortion from incorrect aspect ratio.
* Introduction of a debug mode to simplify troubleshooting
* Bug fix: previous versions could generate incorrect paths to the cache when the blog URL and wordpress URL were different, resulting in no thumbnails being displayed.
* Various stability, performance, and bug fixes.


v0.7.1

* Bug fix: extra HTML tags which distorted the sidebar layout
* Bug fix: removed check for thumbnail cache directory which caused problems in some installs

v0.7

* RSSPhoto *no longer requires* the SimplePie Core plugin! (but support for using SimplePie Core plugin is preserved).  Instead, the Wordpress built-in `fetch_feed` function is used.
* Improved error messages and feedback


v0.6.8

* Various stability improvements
* Added the (beta-version) capability to show image titles although the option has not been extended to the widget settings UI yet (see FAQ)


v0.6.7

* Compatibility update for yet another different mime type that means "jpeg"


v0.6.6

* Stability update: works "out-of-the-box" with all the feeds I've encountered so far in troubleshooting, except where SimplePie settings need to be changed
* Performance improvements: removed an http request from the critical path for thumbnail generation
* When embedded images are small, RSSPhoto will now allow upscaled locally cached thumbnails (previously, images would not be displayed at all)
* Added support for wordpress.com feeds


v0.6.5

* Improved RSS parsing code to handle more implementation varieties (specifically better handling of enclosures)


v0.6.4

* Added support for MobileMe (Apple) RSS feeds


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

