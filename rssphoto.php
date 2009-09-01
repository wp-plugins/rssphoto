<?php
/*
 * Plugin Name: RSSPhoto
 * Plugin URI: http://blog.spencerkellis.net/projects/rssphoto
 * Description: Display photos from an RSS or Atom feed
 * Version: 0.5
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


class RSSPhotoWidget extends WP_Widget
{
  /****************************
   * Internally used variables
   ****************************/
  private $images         = array();
  private $error_msg      = "";
  private $widget_id      = -1;

  /****************************
   * Wordpress variables
   ****************************/
  private $before_widget  = "";
  private $after_widget   = "";
  private $before_title   = "";
  private $after_title    = "";

  /****************************
   * RSSPhoto options
   ****************************/
  private $title          = 'RSSPhoto';
  private $url            = 'http://photography.spencerkellis.net/atom.php';
  private $fixed          = 'Max';
  private $size           = 150;
  private $img_sel        = 'Most Recent';
  private $num_img        = 1;
  private $min_size       = 10;
  private $item_sel       = 'Random';
  private $num_item       = 1;
  private $show_title     = 0;
  private $output         = 'Slideshow';

  /****************************
   * SimplePie variables
   ****************************/
  private $feed;
  private $cache_location = 'wp-content/cache';
  private $force_feed     = false;
  
  /**
  * Declares the RSSPhotoWidget class.
  *
  */
  function RSSPhotoWidget()
  {
    $widget_ops = array('classname' => 'widget_rssphoto', 'description' => __( "Display photos from an RSS or Atom feed") );
    $control_ops = array('width' => 500, 'height' => 300);

    $this->WP_Widget('rssphoto', __('RSSPhoto'), $widget_ops, $control_ops);
  }

  /**
  * Displays the Widget
  *
  */
  function widget($args, $instance)
  {
    $this->setup($args, $instance);

    $num_item = ($this->num_item < $this->feed->get_item_quantity()) ? $this->num_item : $this->feed->get_item_quantity();

    if($num_item > 0)
    {
      $item_idxs = $this->select_indices($this->item_sel,$this->feed->get_items(),$num_item);

      // choose feed item(s)
      foreach($item_idxs as $item_idx)
      {
        $item = $this->feed->get_item($item_idx);

        if($item != false)
        {
          // pull out image url, link
          $item_title = $item->get_title();
          $item_url = $item->get_link(0);
          if($this->feed->get_type() & SIMPLEPIE_TYPE_RSS_ALL)
          {
            $str = $item->get_description();
          }
          elseif ($this->feed->get_type() & SIMPLEPIE_TYPE_ATOM_ALL)
          {
            $str = $item->get_content();
          }
          preg_match_all('/img([^>]*)src="([^"]*)"/i', $str, $m);

          if(count($m[2])>0)
          {
            $num_img = ($this->num_img < count($m[2])) ? $this->num_img : count($m[2]);
            $img_idxs = $this->select_indices($this->img_sel,$m[2],$num_img);

            // choose image(s)
            foreach($img_idxs as $img_idx)
            {
              $image_url = htmlspecialchars_decode($m[2][$img_idx]);
              $thumb_url = $this->create_thumbnail($image_url,$fixed,$size,$min_size);
              if($thumb_url!=false)
              {
                $this->add_image($thumb_url,$item_url);
              }
            }
          }
        }
        else // item==false
        {
          $this->error_msg="<p>Tried to load item #$item_idx from <a href=\"$url\">$url</a> and couldn't!</p>";
          if($this->feed->error())
          {
            $this->error_msg.="<p>The SimplePie error was ";
            $this->error_msg.=$this->feed->error();
            $this->error_msg.="</p>";
          }
          $this->title=apply_filters('widget_title', 'Oops!');
        }
      }
    }

    switch($this->output)
    {
      case 'Slideshow':
        $this->print_rssphoto_slideshow_html();
        break;
      case 'Static':
      default:
        $this->print_static_html();
        break;
    }
  }

  /**
  * Saves the widgets settings.
  *
  */
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['rssphoto_title'] = strip_tags(stripslashes($new_instance['rssphoto_title']));
    $instance['rssphoto_url'] = strip_tags(stripslashes($new_instance['rssphoto_url']));
    $instance['rssphoto_fixed'] = strip_tags(stripslashes($new_instance['rssphoto_fixed']));
    $instance['rssphoto_size'] = strip_tags(stripslashes($new_instance['rssphoto_size']));
    $instance['rssphoto_img_sel'] = strip_tags(stripslashes($new_instance['rssphoto_img_sel']));
    $instance['rssphoto_num_img'] = strip_tags(stripslashes($new_instance['rssphoto_num_img']));
    $instance['rssphoto_item_sel'] = strip_tags(stripslashes($new_instance['rssphoto_item_sel']));
    $instance['rssphoto_num_item'] = strip_tags(stripslashes($new_instance['rssphoto_num_item']));
    $instance['rssphoto_show_title'] = strip_tags(stripslashes($new_instance['rssphoto_show_title']));
    $instance['rssphoto_output'] = strip_tags(stripslashes($new_instance['rssphoto_output']));

    return $instance;
  }

  /**
  * Creates the edit form for the widget.
  *
  */
  function form($instance)
  {
    //Defaults
    $instance = wp_parse_args( (array) $instance, array('rssphoto_title'=>'RSSPhoto',
                                                        'rssphoto_url'=>'http://photography.spencerkellis.net/atom.php',
                                                        'rssphoto_fixed'=>'Max',
                                                        'rssphoto_size'=>150,
                                                        'rssphoto_img_sel'=>'Most Recent',
                                                        'rssphoto_num_img'=>1,
                                                        'rssphoto_item_sel'=>'Random',
                                                        'rssphoto_num_item'=>1,
                                                        'rssphoto_show_title'=>0,
                                                        'rssphoto_output'=>'Slideshow'));

    $title    = htmlspecialchars($instance['rssphoto_title']);
    $url      = htmlspecialchars($instance['rssphoto_url']);
    $fixed    = htmlspecialchars($instance['rssphoto_fixed']);
    $size     = htmlspecialchars($instance['rssphoto_size']);
    $img_sel  = htmlspecialchars($instance['rssphoto_img_sel']);
    $num_img  = htmlspecialchars($instance['rssphoto_num_img']);
    $item_sel = htmlspecialchars($instance['rssphoto_item_sel']);
    $num_item = htmlspecialchars($instance['rssphoto_num_item']);
    $output   = htmlspecialchars($instance['rssphoto_output']);

    //Display form

    echo '<h3>General Settings</h3>';

    // Title
    echo '<div style="text-align:right; float:left; width:130px;"><label for="' . $this->get_field_name('rssphoto_title') . '">' . __('Title:') . '</label></div>';
    echo '<div style="text-align:left; float:left; width:350px; padding-left:5px;">';
    echo '<input style="width: 350px;" id="' . $this->get_field_id('rssphoto_title') . '" name="' . $this->get_field_name('rssphoto_title') . '" type="text" value="' . $title . '" />';
    echo '</div>';

    // Output
    echo '<div style="clear:both;">&nbsp;</div>';
    echo '<div style="text-align:right; float:left; width:130px;">' . __('Output:') . '</div>';
    echo '<div style="text-align:left; float:left; width:110px; padding-left:5px;">';
    echo '<label for="' . $this->get_field_name('rssphoto_output_rssphoto_slideshow') . '"><input ' . (($output=='Slideshow') ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_output_rssphoto_slideshow') . '" name="' . $this->get_field_name('rssphoto_output') . '" value="Slideshow">' . __('Slideshow') . '</label><br>';
    echo '<label for="' . $this->get_field_name('rssphoto_output_static')    . '"><input ' . (($output=='Static')    ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_output_static')    . '" name="' . $this->get_field_name('rssphoto_output') . '" value="Static">'    . __('Static')    . '</label><br>';
    echo '</div>';

    echo '<div style="clear:both;">&nbsp;</div>';
    echo '<h3>Feed Settings</h3>';

    // URL
    echo '<div style="text-align:right; float:left; width:130px;"><label for="' . $this->get_field_name('rssphoto_url')   . '">' . __('URL:')   . '</label></div>';
    echo '<div style="text-align:left; float:left; width:350px; padding-left:5px;">';
    echo '<input style="width: 350px;" id="' . $this->get_field_id('rssphoto_url')   . '" name="' . $this->get_field_name('rssphoto_url')   . '" type="text" value="' . $url   . '" />';
    echo '</div>';

    /*--------------------------------------------------------------------*/

    echo '<div style="clear:both;">&nbsp;</div>';
    echo '<div style="float:left; width:260px;">';

    // Item Selection
    echo '<div style="text-align:right; float:left; width:130px;">' . __('Item Selection:') . '</div>';
    echo '<div style="text-align:left; float:left; width:110px; padding-left:5px;">';
    echo '<label for="' . $this->get_field_name('rssphoto_item_sel_random')     . '"><input ' . (($item_sel=='Random') ? 'checked' : '')      . ' type="radio" id="' . $this->get_field_id('rssphoto_item_sel_random')     . '" name="' . $this->get_field_name('rssphoto_item_sel') . '" value="Random">'      . __('Random')      . '</label><br>';
    echo '<label for="' . $this->get_field_name('rssphoto_item_sel_mostrecent') . '"><input ' . (($item_sel=='Most Recent') ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_item_sel_mostrecent') . '" name="' . $this->get_field_name('rssphoto_item_sel') . '" value="Most Recent">' . __('Most Recent') . '</label><br>';
    echo '</div>';

    echo '</div>';
    echo '<div style="float:left; width:240px;">';

    // Number Items
    echo '<div style="text-align:right; float:left; width:130px;"><label for="' . $this->get_field_name('rssphoto_num_item')   . '">' . __('# Items:')   . '</label></div>';
    echo '<div style="text-align:left; float:left; width:70px; padding-left:5px;">';
    echo '<input style="width: 50px;" id="' . $this->get_field_id('rssphoto_num_item')   . '" name="' . $this->get_field_name('rssphoto_num_item')   . '" type="text" value="' . $num_item . '" />';
    echo '</div>';

    echo '</div>';

    /*--------------------------------------------------------------------*/

    echo '<div style="clear:both;">&nbsp;</div>';
    echo '<div style="float:left; width:260px;">';

    echo '&nbsp;';

    echo '</div>';
    echo '<div style="float:left; width:240px;">';

    // Number Images
    echo '<div style="text-align:right; float:left; width:130px;"><label for="' . $this->get_field_name('rssphoto_num_img')   . '">' . __('# Images per Item:')   . '</label></div>';
    echo '<div style="text-align:left; float:left; width:70px; padding-left:5px;">';
    echo '<input style="width: 50px;" id="' . $this->get_field_id('rssphoto_num_img')   . '" name="' . $this->get_field_name('rssphoto_num_img')   . '" type="text" value="' . $num_img . '" />';
    echo '</div>';

    echo '</div>';

    /*--------------------------------------------------------------------*/

    echo '<div style="clear:both;">&nbsp;</div>';
    echo '<h3>Image Settings</h3>';

    /*--------------------------------------------------------------------*/

    echo '<div style="clear:both;">&nbsp;</div>';
    echo '<div style="float:left; width:260px;">';

    // Image Selection
    echo '<div style="text-align:right; float:left; width:130px;">' . __('Image Selection:') . '</div>';
    echo '<div style="text-align:left; float:left; width:110px; padding-left:5px;">';
    echo '<label for="' . $this->get_field_name('rssphoto_img_sel_random') . '"><input ' . (($img_sel=='Random') ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_img_sel_random') . '" name="' . $this->get_field_name('rssphoto_img_sel') . '" value="Random">' . __('Random') . '</label><br>';
    echo '<label for="' . $this->get_field_name('rssphoto_img_sel_mostrecent')  . '"><input ' . (($img_sel=='Most Recent')  ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_img_sel_mostrecent')  . '" name="' . $this->get_field_name('rssphoto_img_sel') . '" value="Most Recent">'  . __('Most Recent')  . '</label><br>';
    echo '</div>';

    echo '</div>';
    echo '<div style="float:left; width:240px;">';

    // Dimension Size (px)
    echo '<div style="text-align:right; float:left; width:130px;"><label for="' . $this->get_field_name('rssphoto_size')   . '">' . __('Size (px):')   . '</label></div>';
    echo '<div style="text-align:left; float:left; width:70px; padding-left:5px;">';
    echo '<input style="width:50px;" id="' . $this->get_field_id('rssphoto_size')   . '" name="' . $this->get_field_name('rssphoto_size')   . '" type="text" value="' . $size   . '" />';
    echo '</div>';

    echo '</div>';

    /*--------------------------------------------------------------------*/

    echo '<div style="clear:both;">&nbsp;</div>';
    echo '<div style="float:left; width:260px;">';

    // Fixed Dimension
    echo '<div style="text-align:right; float:left; width:130px;">' . __('Fixed Dimension:') . '</div>';
    echo '<div style="text-align:left; float:left; width:110px; padding-left:5px;">';
    echo '<label for="' . $this->get_field_name('rssphoto_fix_width')  . '"><input ' . (($fixed=='Width')  ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_fix_width')  . '" name="' . $this->get_field_name('rssphoto_fixed') . '" value="Width">'  . __('Width')  . '</label><br>';
    echo '<label for="' . $this->get_field_name('rssphoto_fix_height') . '"><input ' . (($fixed=='Height') ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_fix_height') . '" name="' . $this->get_field_name('rssphoto_fixed') . '" value="Height">' . __('Height') . '</label><br>';
    echo '<label for="' . $this->get_field_name('rssphoto_fix_max')    . '"><input ' . (($fixed=='Max')    ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_fix_max')    . '" name="' . $this->get_field_name('rssphoto_fixed') . '" value="Max">'    . __('Max')    . '</label><br>';
    echo '</div>';

    echo '</div>';
    echo '<div style="float:left; width:240px;">';

    echo '&nbsp;';

    echo '</div>';

    /*--------------------------------------------------------------------*/

    // Bottom Spacing
    echo '<div style="clear:both;">&nbsp;</div>';
  }

  /**
  * Create thumbnails of a given image url in the local cache
  *
  */
  function create_thumbnail($image_url)
  {
    // attempt to get image dimensions using getimagesize
    list($width, $height, $type, $attr) = getimagesize($image_url);

    $thumb_url=false;
    
    // if that doesn't work, check for GD and use imagesx/imagesy
    if($height==false && $width==false)
    {
      if($type==1 && function_exists('imagecreatefromgif'))
        $image = @imagecreatefromgif($image_url);
      if($type==2 && function_exists('imagecreatefromjpeg'))
        $image = @imagecreatefromjpeg($image_url);
      elseif($type==3 && function_exists('imagecreatefrompng'))
        $image = @imagecreatefrompng($image_url);
    
      if($image!=false)
      {
        $height = @imagesy($image);
        $width = @imagesx($image);
      }
    }

    if($height<=$this->min_size|| $width<=$this->min_size)
      return false;
    
    // if we've got valid image dimensions, continue
    if($height!=false && $width!=false)
    {
      // default parameters
      switch($this->fixed)
      {
        case 'Width':
          $ratio = $this->size/$width;
          $thumb_width = $this->size;
          $thumb_height = $height * $ratio;
          break;
        case 'Height':
          $ratio = $this->size/$height;
          $thumb_height = $this->size;
          $thumb_width = $width * $ratio;
          break;
        case 'Max':
        default:
          $ratio = $this->size/max($height,$width);
          if($width>$height)
          {
            $thumb_width = $this->size;
            $thumb_height = $height * $ratio;
          }
          else
          {
            $thumb_height = $this->size;
            $thumb_width = $width * $ratio;
          }
          break;
      }
      $thumb_url = $image_url;

      // use GD library to create cached thumbnail if necessary
      if(($width>$thumb_width || $height>$thumb_height) &&
         function_exists('imagecreatefromjpeg'))
      {
        $image_filename = "rssphoto-".md5($image_url)."-{$this->fixed}-{$this->size}.jpg";
        $thumb_path = $this->cache_location."/".$image_filename;
        $thumb_url = get_bloginfo('wpurl')."/$thumb_path";

        if(!file_exists($thumb_path))
        {
          // create thumbnail
          if($image == false)
          {
            if($type==1 && function_exists('imagecreatefromgif'))
              $image = @imagecreatefromgif($image_url);
            if($type==2 && function_exists('imagecreatefromjpeg'))
              $image = @imagecreatefromjpeg($image_url);
            elseif($type==3 && function_exists('imagecreatefrompng'))
              $image = @imagecreatefrompng($image_url);
          }

          if($image != false)
          {
            $quality = 85;
            $thumb=imagecreatetruecolor($thumb_width,$thumb_height);
            @imagecopyresampled($thumb,$image,0,0,0,0,$thumb_width,$thumb_height,$width,$height);
            @imagejpeg($thumb,$thumb_path,$quality);
          }
        }
      }
    } // if($height!=false && width!=false)

    return $thumb_url;

  } // function create_thumbnail

  /**
  * Get indices of images from array
  *
  */
  function select_indices($method,$arr,$num)
  {
    switch($method)
    {
      case 'Most Recent':
        $idxs = range(0,$num-1);
        break;
      case 'Random':
      default:
        if($num==1)
          $idxs[0] = rand(0,count($arr)-1);
        else
          $idxs = array_rand($arr,$num);
        break;
    }
    return $idxs;
  }

  /**
  * Add image to array
  *
  */
  function add_image($url,$link)
  {
    $idx=count($this->images);
    $this->images[$idx]['url']=$url;
    $this->images[$idx]['link']=$link;
  }

  function print_rssphoto_slideshow_html()
  {
    # Before the widget
    echo $this->before_widget;

    # The title
    echo $this->before_title;
    echo $this->title;
    echo $this->after_title;

    $active=0;
    ?>
    <div id="rssphoto_slideshow" style="height:<?php echo $this->size; ?>px;">
    <?php
    foreach($this->images as $img)
    {
      ?>
      <div<?php if(!$active){echo ' class="active"'; $active=1;} ?>><a href="<?php echo $img['link']; ?>"><img src="<?php echo $img['url']; ?>" alt="" /></a></div>
      <?php 
    }
    echo '</div>';

    # After the widget
    echo $this->after_widget;
  }

  function print_static_html()
  {
    # Before the widget
    echo $this->before_widget;

    # The title
    echo $this->before_title;
    echo $this->title;
    echo $this->after_title;

    ?>
    <div id="rssphoto_static">
    <?php
    foreach($this->images as $img)
    {
      ?>
      <div><a href="<?php echo $img['link']; ?>"><img src="<?php echo $img['url']?>"></a></div>
      <?php
    }
    ?>
    </div>
    <?php

    # After the widget
    echo $this->after_widget;
  }

  function setup($args,$instance)
  {
    extract($args);
    $this->before_widget = $before_widget;
    $this->after_widget  = $after_widget;
    $this->before_title  = $before_title;
    $this->after_title   = $after_title;

    $this->widget_id = rand();

    $this->title = apply_filters('widget_title', empty($instance['rssphoto_title']) ? '&nbsp;' : $instance['rssphoto_title']);
    if(!empty($instance['rssphoto_url']))        $this->url        = $instance['rssphoto_url'];
    if(!empty($instance['rssphoto_fixed']))      $this->fixed      = $instance['rssphoto_fixed'];
    if(!empty($instance['rssphoto_size']))       $this->size       = $instance['rssphoto_size'];
    if(!empty($instance['rssphoto_img_sel']))    $this->img_sel    = $instance['rssphoto_img_sel'];
    if(!empty($instance['rssphoto_num_img']))    $this->num_img    = $instance['rssphoto_num_img'];
    if(!empty($instance['rssphoto_item_sel']))   $this->item_sel   = $instance['rssphoto_item_sel'];
    if(!empty($instance['rssphoto_num_item']))   $this->num_item   = $instance['rssphoto_num_item'];
    if(!empty($instance['rssphoto_show_title'])) $this->show_title = $instance['rssphoto_show_title'];
    if(!empty($instance['rssphoto_output']))     $this->output     = $instance['rssphoto_output'];

    /*
     * CHECK FOR SIMPLEPIE
     */
    // initialize SimplePie object
    $this->feed = new SimplePie();
    $this->feed->set_cache_location($this->cache_location);
    $this->feed->set_feed_url($this->url);
    $this->feed->force_feed($this->force_feed);
    $this->feed->init();
  }

}// END class

/**
* Register RSSPhoto Widget
*
* Calls 'widgets_init' action after the Hello World widget has been registered.
*/
function RSSPhotoWidgetInit() 
{
  register_widget('RSSPhotoWidget');
  wp_enqueue_script('rssphoto_javascript','/wp-content/plugins/rssphoto/rssphoto.js',array('jquery'));
  wp_enqueue_style('rssphoto_stylesheet','/wp-content/plugins/rssphoto/rssphoto.css');
}

add_action('widgets_init', 'RSSPhotoWidgetInit');
?>
