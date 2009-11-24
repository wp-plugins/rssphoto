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

class RSSPhoto
{
  /****************************
   * Internal variables
   ****************************/
  var $images         = array();
  var $error_msg      = "";
  var $id             = -1;
  var $show_desc      = false;
  var $mime_types     = array('image/jpeg','image/jpg','image/gif','image/png');
  var $mediums        = array('image');

  /****************************
   * RSSPhoto temp vars
   ****************************/
  var $rss_type_src   = 'Choose'; // 'Choose' or 'Enclosures' or 'Description' or 'Content'*

  /****************************
   * RSSPhoto settings
   ****************************/
  var $title          = 'RSSPhoto';
  var $url            = 'http://photography.spencerkellis.net/atom.php';
  var $fixed          = 'Max';
  var $size           = 150;
  var $img_sel        = 'Most Recent';
  var $num_img        = 1;
  var $min_size       = 10;
  var $item_sel       = 'Random';
  var $num_item       = 1;
  var $show_title     = 0;
  var $output         = 'Slideshow';
  var $interval       = 6000;

  /****************************
   * SimplePie settings
   ****************************/
  var $feed;
  var $cache_location = 'wp-content/cache';
  var $force_feed     = false;

  function RSSPhoto($settings=array())
  {
    $this->update($settings);
  }

  function init()
  {
    $this->id = rand();

    // initialize SimplePie object
    $this->feed = new SimplePie();
    $this->feed->set_cache_location($this->cache_location);
    $this->feed->set_feed_url($this->url);
    $this->feed->force_feed($this->force_feed);
    $this->feed->init();

    // get images, generate thumbnails, etc.
    if($this->feed->get_item_quantity() > 0)
    {
      $item_idxs = $this->select_indices($this->feed->get_items(),$this->item_sel,$this->num_item);

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
            switch($this->rss_type_src)
            {
              case 'Enclosures':
                $image_url = $this->get_img_urls($enclosures,$this->img_sel,$this->num_img,'Enclosures');
                break;
              case 'Description':
                $image_url = $this->get_img_urls($item,$this->img_sel,$this->num_img,'Description');
                break;
              case 'Content':
                $image_url = $this->get_img_urls($item,$this->img_sel,$this->num_img,'Content');
                break;
              case 'Choose':
                if($enclosures = $item->get_enclosures())
                  $image_url = $this->get_img_urls($enclosures,$this->img_sel,$this->num_img,'Enclosures');
                else
                  $image_url = $this->get_img_urls($item,$this->img_sel,$this->num_img,'Description');
                break;
            }
          }
          elseif ($this->feed->get_type() & SIMPLEPIE_TYPE_ATOM_ALL)
          {
            $image_url = $this->get_img_urls($item,$this->img_sel,$this->num_img,'Content');
          }

          if(is_array($image_url))
          {
            foreach($image_url as $url)
            {
              $thumb_url = $this->create_thumbnail($url,$fixed,$size,$min_size);
              if($thumb_url!=false)
              {
                $this->add_image($thumb_url,$item_url,$item->get_description());
              }
            }
          }
          else
          {
            $this->set_error('$image_url isn\'t an array');
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
  }

  function html()
  {
    switch($this->output)
    {
      case 'Slideshow':
        return $this->slideshow_html();
        break;
      case 'Static':
      default:
        return $this->static_html();
        break;
    }
  }

  /**
  * Create thumbnails of a given image url in the local cache
  *
  */
  function create_thumbnail($image_url)
  {
    // attempt to get image dimensions using getimagesize
    list($width, $height, $type, $attr) = @getimagesize($image_url);

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
  function select_indices($arr,$method,$num)
  {
    $num = ($num < count($arr)) ? $num : count($arr);
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
  * Check array elements
  *
  */
  function check_indices($arr)
  {
    for($k=count($arr)-1; $k>=0; $k--)
    {
      // check for empty object
      if(empty($arr[$k]))
      {
        unset($arr[$k]);
        break;
      }

      // check for url 
      $lnk=$arr[$k]->get_link(); // some bug that kills php if this isn't separated out
      if(empty($lnk))
      {
        unset($arr[$k]); 
        break;
      }

      // check for compatible image type
      $mime_flag=0;
      $medium_flag=0;
      foreach($this->mime_types as $mime)
      {
        if(!strcmp($arr[$k]->get_type(),$mime))
        {
          $mime_flag=1;
          break;
        }
      }
      foreach($this->mediums as $medium)
      {
        if(!strcmp($arr[$k]->get_medium(),$medium))
        {
          $medium_flag=1;
          break;
        }
      }
      
      // if neither mime or medium, discard
      if(!($mime_flag | $medium_flag))
      {
        unset($arr[$k]);
        break;
      }
    }

    $arr=array_values($arr);
    return $arr;
  }

  /**
  * Add image to array
  *
  */
  function add_image($url,$link,$desc)
  {
    $idx=count($this->images);
    $this->images[$idx]['url']=$url;
    $this->images[$idx]['link']=$link;
    $this->images[$idx]['desc']=$desc;
  }

  function slideshow_html()
  {
    $html = '<div class="rssphoto_slideshow" id="rssphoto-'.$this->id.'" style="height:'.$this->size.'px;">';

    $active=0;
    foreach($this->images as $img)
    {
      $html .= '<div';
      if(!$active)
      {
        $html .= ' class="active"'; 
        $active=1;
      }
      $html .= '><a href="'.$img['link'].'"><img src="'.$img['url'].'" alt="" /></a></div>';
      $html .= "\n";
    }
    $html .= '</div>';
    $html .= "\n";

    if(count($this->images)>1)
    {
      $html .= '<script type="text/javascript">setInterval( "slideSwitch('.$this->id.')", '.$this->interval.' );</script>';
      $html .= "\n";
    }

    return $html;
  }

  function static_html()
  {
    $html = '<div class="rssphoto_static" id="rssphoto-'.$this->id.'">';
    foreach($this->images as $img)
    {
      $html .= '<div><a href="'.$img['link'].'"><img src="'.$img['url'].'"></a></div>';
      if($this->show_desc)
      {
        $html .= '<p>'.$img['desc'].'</p>';
      }
      $html .= "\n";
    }
    $html .= '</div>';
    $html .= "\n";

    $html .= '<script type="text/javascript">expandStatic('.$this->id.');</script>';
    $html .= "\n";

    return $html;
  }

  function title()
  {
    return $this->title;
  }

  function update($settings)
  {
    if(!empty($settings['rssphoto_title']))      $this->title      = $settings['rssphoto_title'];
    if(!empty($settings['rssphoto_url']))        $this->url        = $settings['rssphoto_url'];
    if(!empty($settings['rssphoto_fixed']))      $this->fixed      = $settings['rssphoto_fixed'];
    if(!empty($settings['rssphoto_size']))       $this->size       = $settings['rssphoto_size'];
    if(!empty($settings['rssphoto_img_sel']))    $this->img_sel    = $settings['rssphoto_img_sel'];
    if(!empty($settings['rssphoto_num_img']))    $this->num_img    = $settings['rssphoto_num_img'];
    if(!empty($settings['rssphoto_item_sel']))   $this->item_sel   = $settings['rssphoto_item_sel'];
    if(!empty($settings['rssphoto_num_item']))   $this->num_item   = $settings['rssphoto_num_item'];
    if(!empty($settings['rssphoto_show_title'])) $this->show_title = $settings['rssphoto_show_title'];
    if(!empty($settings['rssphoto_output']))     $this->output     = $settings['rssphoto_output'];
    if(!empty($settings['rssphoto_interval']))   $this->interval   = $settings['rssphoto_interval'];
  }

  function get_img_urls($arr,$sel,$num,$field)
  {
    switch($field)
    {
      case 'Content':
        preg_match_all('/img([^>]*)src="([^"]*)"/i', $arr->get_content(), $m);
        if(count($m[2])>0)
        {
          $img_idxs = $this->select_indices($m[2],$sel,$num);
          foreach($img_idxs as $img_idx)
            $urls[count($urls)] = htmlspecialchars_decode($m[2][$img_idx]);
        }
        break;
      case 'Description':
        preg_match_all('/img([^>]*)src="([^"]*)"/i', $arr->get_description(), $m);
        if(count($m[2])>0)
        {
          $img_idxs = $this->select_indices($m[2],$sel,$num);
          foreach($img_idxs as $img_idx)
            $urls[count($urls)] = htmlspecialchars_decode($m[2][$img_idx]);
        }
        break;
      case 'Enclosures':
        $arr=$this->check_indices($arr);
        $img_idxs = $this->select_indices($arr,$sel,$num);
        foreach($img_idxs as $idx)
        {
          $urls[count($urls)] = htmlspecialchars_decode($arr[$idx]->get_link());
        }
        break;
    }
    return $urls;
  }

  function set_error($str)
  {
    $this->error_msg=$str;
  }
}
