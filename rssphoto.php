<?php
/*
 * Plugin Name: RSSPhoto
 * Plugin URI: http://blog.spencerkellis.net/projects/rssphoto
 * Description: Display photos from an RSS or Atom feed
 * Version: 0.8.2
 * Author: Spencer Kellis
 * Author URI: http://blog.spencerkellis.net
 *
 */

/*  Copyright 2009 Spencer Kellis (email : spencerkellis *AT* gmail)

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

if( !class_exists('RSSPhoto') )
  require_once('RSSPhoto.class.php');

if( !class_exists('RSSPhotoWidget') )
  require_once('RSSPhotoWidget.class.php');

if( !class_exists('RSSPhotoShortcode') )
  require_once('RSSPhotoShortcode.class.php');

/**
* Register RSSPhoto Widget
*
* Calls 'widgets_init' action after the Hello World widget has been registered.
*/
function RSSPhotoWidgetInit() 
{
  register_widget('RSSPhotoWidget');
  wp_enqueue_script('jquery');
  wp_enqueue_script('rssphoto_javascript','/wp-content/plugins/rssphoto/rssphoto.js');
  wp_enqueue_style('rssphoto_stylesheet','/wp-content/plugins/rssphoto/rssphoto.css');
}

function RSSPhotoShortcodeInit()
{
  if( class_exists('RSSPhotoShortcode') )
    $rssphoto_shortcode = new RSSPhotoShortcode();
}

add_action('widgets_init', 'RSSPhotoWidgetInit');
add_action('init', 'RSSPhotoShortcodeInit');
?>
