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

add_action('init','init_theme_rssphoto');

function init_theme_rssphoto()
{
  wp_enqueue_script('jquery');
  wp_enqueue_script('rssphoto_javascript','wp-content/plugins/rssphoto/rssphoto.js');
  wp_enqueue_style('rssphoto_stylesheet','wp-content/plugins/rssphoto/rssphoto.css');
  if( !class_exists('RSSPhoto') )
    require_once('RSSPhoto.class.php');
}

function display_rssphoto($input=array())
{
  if(!empty($input['title']))          $settings['rssphoto_title']          =$input['title'];
  if(!empty($input['url']))            $settings['rssphoto_url']            =$input['url'];
  if(!empty($input['height']))         $settings['rssphoto_height']         =$input['height'];
  if(!empty($input['width']))          $settings['rssphoto_width']          =$input['width'];
  if(!empty($input['img_sel']))        $settings['rssphoto_img_sel']        =$input['img_sel'];
  if(!empty($input['num_img']))        $settings['rssphoto_num_img']        =$input['num_img'];
  if(!empty($input['item_sel']))       $settings['rssphoto_item_sel']       =$input['item_sel'];
  if(!empty($input['num_item']))       $settings['rssphoto_num_item']       =$input['num_item'];
  if(!empty($input['show_title']))     $settings['rssphoto_show_title']     =$input['show_title'];
  if(!empty($input['show_img_title'])) $settings['rssphoto_show_img_title'] =$input['show_img_title'];
  if(!empty($input['output']))         $settings['rssphoto_output']         =$input['output'];
  if(!empty($input['interval']))       $settings['rssphoto_interval']       =$input['interval'];

  if(!isset($input['before_html']))    $input['before_html'] = "";
  if(!isset($input['after_html']))     $input['after_html'] = "";
  if(!isset($input['before_title']))   $input['before_title'] = "";
  if(!isset($input['after_title']))    $input['after_title'] = "";

  if( class_exists('RSSPhoto') )
  {
    $rssphoto = new RSSPhoto($settings);
    $rssphoto->init();

    echo $input['before_html'];
    if($rssphoto->ready())
    {
      echo $input['before_title'];
      echo $rssphoto->title();
      echo $input['after_title'];
      echo $rssphoto->html();
    }
    else
    {
      echo $rssphoto->get_error();
    }
    echo $input['after_html'];
  }
}
?>
