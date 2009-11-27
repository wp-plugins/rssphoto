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
  var $mime_types     = array('image/jpeg','image/jpg','images/jpeg','image/gif','image/png');
  var $medium_types   = array('image');
  var $parser         = 'built-in';
  var $status         = 0; // -1 for error, 0 for uninitialized, 1 for initialized

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

  /**
   *  Constructor.  
   *  Call with array of settings where keys are the setting names, values are the settings.
   */
  function RSSPhoto($settings=array())
  {
    $this->update($settings);
  }

  /**
   *  Initialize RSSPhoto.  
   *  Call after construction (and settings established), before display (e.g., before calling html()).
   */
  function init()
  {
    // check for thumbnail cache directory
    if(!file_exists($this->cache_location))
    {
      $this->set_error("The thumbnail cache specified as ".$this->cache_location." does not exist");
      return;
    }

    $this->id = rand();

    // set up the SimplePie feed
    $this->init_feed($this->url,$this->parser,$this->cache_location,$this->force_feed);

    if(empty($this->feed))
    {
      $this->ignominious_death();
      return;
    }

    if(is_wp_error($this->feed))
    {
      $this->set_error($this->feed->get_error_message());
      $this->ignominious_death();
      return;
    }

    // get images, generate thumbnails, etc.
    // consider limiting this to some reasonable number for feeds that have >100s of items?
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
                $this->add_image($thumb_url,$item->get_link(0),$item->get_description(),$item->get_title());
            }
          }
          else
          {
            $this->set_error('Bad image urls');
            $this->ignominious_death();
            return;
          }
        }
        else // item==false
        {
          if($this->feed->error())
            $this->set_error($this->feed->error());
          else
            $this->set_error("Tried to load item #$item_idx from $url and couldn't!");

          $this->ignominious_death();
          return;
        }
      }
    }

    // read to display
    $this->status=1;
  }

  /**
  *  Chooser function to use either the SimplePie Core or built-in feed parser
  */
  function init_feed($url='http://www.spencerkellis.net/atom.php',$parser='built-in',$cacheloc='',$forcefeed=false)
  {
    switch($parser)
    {
      case 'simplepie-core':
        if(class_exists('SimplePie'))
        {
          $this->feed = new SimplePie();
          $this->feed->set_cache_location($cacheloc);
          $this->feed->set_feed_url($url);
          $this->feed->force_feed($forcefeed);
          $this->feed->init();
        }
        else
          $this->set_error('SimplePie class does not exist but was selected to parse feeds');
        break;
      case 'built-in':
      default:
        if(function_exists('fetch_feed'))
        {
          $this->feed = fetch_feed($url);
        }
        else
          $this->set_error('The built-in Wordpress fetch_feed() function does not exist: upgrade to WP 2.8 or later');
        break;
    }
  }

  /**
  *  Chooser function drill down to either the slideshow or static thumbnail display
  */
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
  *  Create thumbnails of a given image url in the local cache
  */
  function create_thumbnail($image_url)
  {
    $image_filename = "rssphoto-".md5($image_url)."-{$this->fixed}-{$this->size}.jpg";
    $thumb_path = $this->cache_location."/".$image_filename;
    $thumb_url = get_bloginfo('wpurl')."/$thumb_path";
    if(!file_exists($thumb_path))
    {
      // attempt to get image dimensions using getimagesize
      list($width, $height, $type, $attr) = @getimagesize($image_url);
      
      // if that doesn't work, check for GD and use imagesx/imagesy
      if($height==false || $width==false)
      {
        if($type==1 && function_exists('imagecreatefromgif'))
          $image = @imagecreatefromgif($image_url);
        if($type==2 && function_exists('imagecreatefromjpeg'))
          $image = @imagecreatefromjpeg($image_url);
        elseif($type==3 && function_exists('imagecreatefrompng'))
          $image = @imagecreatefrompng($image_url);

        if($image==false)
          return false;
        else
        {
          $height = @imagesy($image);
          $width = @imagesx($image);
        }
      }
     
      if($height<=$this->min_size || $width<=$this->min_size)
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
     
        // use GD library to create cached thumbnail if necessary
        if(function_exists('imagecreatefromjpeg'))
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
        else
        {
          $this->set_error("GD Library doesn't exist, so thumbnails won't be created");
        }
      } // if($height!=false && width!=false)
    } // if(!file_exists($thumb_path))

    return $thumb_url;

  } // function create_thumbnail

  /**
  *  Get indices of images from array
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
  *  Check array elements
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

      // if it's an enclosure... anything else?
      if(is_object($arr[$k]))
      {
        // check for url 
        if(method_exists($arr[$k],'get_link'))
        {
          $lnk=$arr[$k]->get_link(); // some bug that kills php if this isn't separated out
          if(empty($lnk))
          {
            unset($arr[$k]); 
            break;
          }
        }
       
        // check for compatible image type
        if(method_exists($arr[$k],'get_type') || method_exists($arr[$k],'get_medium'))
        {
          $mime_flag=0;
          $medium_flag=0;
          if(method_exists($arr[$k],'get_type'))
          {
            foreach($this->mime_types as $mime)
            {
              if(!strcmp($arr[$k]->get_type(),$mime))
              {
                $mime_flag=1;
                break;
              }
            }
          }
          if(method_exists($arr[$k],'get_medium'))
          {
            foreach($this->medium_types as $medium)
            {
              if(!strcmp($arr[$k]->get_medium(),$medium))
              {
                $medium_flag=1;
                break;
              }
            }
          }
          // if neither mime or medium, discard
          if(!($mime_flag | $medium_flag))
          {
            unset($arr[$k]);
            break;
          }
        }
      }
    }

    $arr=array_values($arr);
    return $arr;
  }

  /**
  *  Add image to array
  */
  function add_image($url=false,$link="",$desc="",$title="")
  {
    if($url!=false)
    {
      $idx=count($this->images);
      $this->images[$idx]['url']=$url;
      $this->images[$idx]['link']=$link;
      $this->images[$idx]['desc']=$desc;
      $this->images[$idx]['title']=$title;
    }
  }

  /**
   *  Display a jQuery-powered slideshow of thumbnails
   */
  function slideshow_html()
  {
    $html = '<div class="rssphoto_slideshow" id="rssphoto-'.$this->id.'" style="height:'.$this->size.'px;">';

    $active=0;
    foreach($this->images as $img)
    {
      $html .= '<div class="item ';
      if(!$active)
      {
        $html .= 'active'; 
        $active=1;
      }
      $html .= '">';
      if($this->show_title && !empty($img['title']))
      {
        $html .= '<div class="rssphoto_item_title">'.$img['title'].'</div>';
      }
      $html .= '<a href="'.$img['link'].'"><img src="'.$img['url'].'" alt="" /></a>';
      $html .= '</div>';
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

  /**
   *  For displaying all thumbnails at once
   */
  function static_html()
  {
    $html = '<div class="rssphoto_static" id="rssphoto-'.$this->id.'">';
    foreach($this->images as $img)
    {
      $html .= '<div>';
      if($this->show_title && !empty($img['title']))
      {
        $html .= '<div class="rssphoto_item_title">'.$img['title'].'</div>';
      }
      $html .= '<a href="'.$img['link'].'"><img src="'.$img['url'].'"></a></div>';
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

  /**
   *  Return the RSSPhoto title
   */
  function title()
  {
    return $this->title;
  }

  /**
   *  Update settings
   */
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

  /**
   *  Pull image URLs out of content, descriptions, or enclosures
   */
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
          if(method_exists($arr[$idx],'get_thumbnail') && $thumbnail=$arr[$idx]->get_thumbnail(0))
            $urls[count($urls)] = htmlspecialchars_decode($thumbnail);
          elseif(method_exists($arr[$idx],'get_link') && $lnk=$arr[$idx]->get_link())
            $urls[count($urls)] = htmlspecialchars_decode($lnk);
        }
        break;
    }
    return $urls;
  }

  /**
   *  Establish an error message
   */
  function set_error($str)
  {
    $this->error_msg=$str;
  }

  /**
   *  Return the established error message
   */
  function get_error()
  {
    return $this->error_msg;
  }

  /**
   *  Simple test whether RSSPhoto is ready to display
   */
  function ready()
  {
    return ($this->status==1);
  }

  /**
   *  Simple error handler 
   */
  function ignominious_death()
  {
    if(empty($this->error_msg))
      $this->set_error('RSSPhoto died an unknown but ignominious death');

    $this->status = -1;
  }
}
