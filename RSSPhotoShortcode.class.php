<?php

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

class RSSPhotoShortcode 
{
  var $rssphoto;

  function RSSPhotoShortcode() 
  {
    if ( !function_exists('add_shortcode') ) return;
    add_shortcode('rssphoto',array($this, 'shortcode_handler'));
  }

  function shortcode_handler($atts=array(), $content=NULL) 
  {
    $this->setup($atts);
    if($this->rssphoto->ready())
      return $this->rssphoto->html();
    else
      return $this->rssphoto->get_error();
  }

  function setup($atts)
  {
    /* user-defined options */
    extract( shortcode_atts( array(
      'title' => 'RSSPhoto',
      'url' => 'http://photography.spencerkellis.net/rss.php',
      'height' => 120,
      'width' => 150,
      'img_sel' => 'Random',
      'num_img' => 1,
      'item_sel' => 'Random',
      'num_item' => 10,
      'show_title' => 1,
      'show_img_title' => 1,
      'output' => 'Slideshow2',
      'interval' => 6000,
      'min_size' => 10,
      'max_size' => 500
    ), $atts ) );

    $settings['rssphoto_title']          = $title;
    $settings['rssphoto_url']            = $url;
    $settings['rssphoto_height']         = $height;
    $settings['rssphoto_width']          = $width;
    $settings['rssphoto_img_sel']        = $img_sel;
    $settings['rssphoto_num_img']        = $num_img;
    $settings['rssphoto_item_sel']       = $item_sel;
    $settings['rssphoto_num_item']       = $num_item;
    $settings['rssphoto_show_title']     = $show_title;
    $settings['rssphoto_show_img_title'] = $show_img_title;
    $settings['rssphoto_output']         = $output;
    $settings['rssphoto_interval']       = $interval;
    $settings['rssphoto_min_size']       = $min_size;
    $settings['rssphoto_max_size']       = $max_size;

    $this->rssphoto = new RSSPhoto($settings);
    $this->rssphoto->init();
  }
}
