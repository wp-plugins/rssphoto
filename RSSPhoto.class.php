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

/*
 *  TO DO LIST
 *
 *  1. Allow empty fields for width or height to mean "variable"
 *  2. Make shortcode attributes not have any spaces, e.g., "Most Recent" should be "MostRecent"
 *  3. Make a "quiet mode" where no errors or debug is output in case something goes wrong on a live site
 *  4. Fix layout issues with variable height and Hamad's feed
 */

class RSSPhoto
{
  /****************************
   * Internal variables
   ****************************/
  var $version        = '0.8.2'; // current version of RSSPhoto
  var $debug          = 1; // '0' for normal; '1' to print debug comments (hidden by default by <!-- and --> tags)
  var $images         = array(); // will store images to display
  var $error_msg      = ""; // most recent error message
  var $debug_msgs     = array(); // array of debug messages: see print_debug()
  var $id             = -1; // random id to allow multiple instantiations
  var $mime_types     = array('image/jpeg','image/jpg','images/jpeg','image/gif','image/png'); // mime types allowed
  var $medium_types   = array('image'); // medium types allowed (for enclosures in RSS feeds)
  var $parser         = 'built-in'; // which parser to use for interpreting feeds: 'built-in' or 'simplepie-core'
  var $status         = 0; // -1 for error, 0 for uninitialized, 1 for initialized: see ready()
  var $div_height     = -1; // height of the RSSPhoto div, based on max thumbnail height encountered
  var $div_width      = -1; // height of the RSSPhoto div, based on max thumbnail height encountered
  var $cache_dir      = 'wp-content/cache'; // always start this with wp-content; code below will determine what goes before wp-content

  /*
   * FOR ADVANCED USERS ONLY 
   * If you're having problems with cache directory location, try modifying the values of these variables.  If you 
   * change one, you must change the other.  Some examples of a $user_cache_dir:
   * 
   * $user_cache_dir = '/var/www/your.domain.com/wordpress/wp-content/cache'   // absolute path
   * $user_cache_Dir = 'blog/wp-content/cache'  // relative path
   *
   * An example of a $user_thumb_url might be
   *
   * $user_thumb_url = 'http://your.domain.com/wordpress/wp-content/cache';
   *
   */
  var $user_cache_dir = -1;
  var $user_thumb_url = -1;

  /****************************
   * RSSPhoto temp vars
   ****************************/
  var $rss_type_src   = 'Choose'; // 'Choose' or 'Enclosures' or 'Description' or 'Content'*

  /****************************
   * RSSPhoto settings
   ****************************/
  var $title          = 'RSSPhoto'; // title shown above the widget
  var $url            = 'http://photography.spencerkellis.net/atom.php'; // url of feed to parse
  var $height         = 120; // [H] in px or 'variable' (only one may have the value 'variable')
  var $width          = 150; // [W] in px or 'variable' (only one may have the value 'variable')
  var $img_sel        = 'Most Recent'; // method of selecting images from feed items.  'Most Recent' or 'Random'
  var $num_img        = 1; // how many images to pull out per feed item
  var $item_sel       = 'Random'; // method of selecting feed items.  'Most Recent' or 'Random'
  var $num_item       = 1; // number of feed items (i.e., articles) to pull out of the feed
  var $show_title     = 1; // whether to show RSSPhoto title
  var $show_img_title = 1; // whether to show titles for each image
  var $output         = 'Slideshow2'; // output style: 'Slideshow2' or 'Slideshow' or 'Static'
  var $interval       = 6000; // interval in milliseconds between image transitions
  var $min_size       = 10; // minimum size of image (discarded if less)
  var $max_size       = 500; // thumbnail will shrink to this hard limit in either dimension

  /****************************
   * SimplePie settings
   ****************************/

  var $feed;
  var $force_feed     = false;

  /*************************
   * Private Functions
   *************************/

  /**
   *  Chooser function to use either the SimplePie Core or built-in feed parser
   */
  function init_feed($url='http://www.spencerkellis.net/atom.php',$parser='built-in',$cachedir='',$usercachedir=-1,$forcefeed=false)
  {
    switch($parser)
    {
      case 'simplepie-core':
        $this->add_debug('Using SimplePie Core plugin to parse feeds');
        if(class_exists('SimplePie'))
        {
          $this->feed = new SimplePie();
          if($usercachedir!=-1)
            $this->feed->set_cache_location($usercachedir);
          else
            $this->feed->set_cache_location(WP_CONTENT_DIR . "/" . $cachedir);
          $this->feed->set_feed_url($url);
          $this->feed->force_feed($forcefeed);
          $this->feed->init();
        }
        else
          $this->add_debug('[*] SimplePie class does not exist but was selected to parse feeds');
        break;
      case 'built-in':
      default:
        $this->add_debug('Using built-in Wordpress functions to parse feeds');
        if(function_exists('fetch_feed'))
        {
          $this->feed = fetch_feed($url);
        }
        else
          $this->add_debug('[*] The built-in Wordpress fetch_feed() function does not exist: upgrade to WP 2.8 or later');
        break;
    }
  }

  /**
   *  Save images locally using cURL (for when allow_url_fopen is off)
   *  Idea and first implementation from http://www.edmondscommerce.co.uk/blog/php/php-save-images-using-curl/
   */
   
  function save_image($img,$fullpath)
  {
    $ch = curl_init ($img);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
    $rawdata=curl_exec($ch);
    curl_close ($ch);
    if(file_exists($fullpath))
    {
      unlink($fullpath);
    }
    $fp = fopen($fullpath,'x');
    fwrite($fp, $rawdata);
    fclose($fp);
  }
   


  /**
   *  Create thumbnails of a given image url in the local cache
   */
  function create_thumbnail($image_url)
  {
    if(!function_exists('imagecreatefromjpeg'))
    {
      $this->add_debug('[*] GD Library doesn\'t exist, so thumbnails won\'t be created: returning false from function create_thumbnail()');
      return false;
    }

    // resolve path to the cache directory and check whether it exists
    $rel_wp_path = str_replace(get_bloginfo('url'),"",get_bloginfo('wpurl'));
    $this->add_debug("Relative wordpress path found to be $rel_wp_path");
    if($this->user_cache_dir==-1)
    {
      $cachedir = $rel_wp_path . "/" . $this->cache_dir;
      $cachedir = substr($cachedir,1);
      $this->add_debug("Automatically-resolved cache directory is $cachedir");
    }
    else
    {
      $cachedir = $this->user_cache_dir;
      $this->add_debug("User-specified cache directory is $cachedir");
    }
    if(!file_exists(realpath($cachedir)))
    {
      $this->add_debug("[*] The resolved cache directory $cachedir does not exist according to file_exists() function in create_thumbnail(): returning false");
      return false;
    }

    // construct path and URL to thumbnail
    $image_filename = "rssphoto-".md5($image_url)."-{$this->width}x{$this->height}.jpg";
    if($this->user_cache_dir==-1)
    {
      $thumb_path = $cachedir . "/" . $image_filename;
      $thumb_url = get_bloginfo('wpurl')."/". $this->cache_dir ."/$image_filename";
    }
    else
    {
      $thumb_path = $cachedir ."/". $image_filename;
      $thumb_url = $this->user_thumb_url ."/". $image_filename;
      $this->add_debug("User-specified thumbnail URL is {$this->user_thumb_url}");
    }
    $this->add_debug("Final thumbnail path is $thumb_path");
    $this->add_debug("Final thumbnail URL is $thumb_url");

    if(!file_exists($thumb_path))
    {
      if(!ini_get('allow_url_fopen')) // save image locally and update $image_url
      {
        $this->add_debug("URLs not allowed for getimagesize or GD: saving file to $thumb_path");
        $this->save_image($image_url,$thumb_path);
        $image_url=$thumb_path;
      }

      $imginfo = @getimagesize($image_url);
      if(!$imginfo)
      {
        $this->add_debug("[*] Failed to open image at $image_url using getimagesize() in function create_thumbnail(): returning false");
        return false;
      }

      $width = $imginfo[0];
      $height = $imginfo[1];

      switch($imginfo[2])
      {
        case IMAGETYPE_GIF:  $image = @imagecreatefromgif($image_url);  break;
        case IMAGETYPE_JPEG: $image = @imagecreatefromjpeg($image_url); break;
        case IMAGETYPE_PNG:  $image = @imagecreatefrompng($image_url);  break;
      }

      if($image==false)
      {
        $this->add_debug("[*] Failed to open image at $image_url with imagecreatefromXXX: returning false from function create_thumbnail()");
        return false;
      }

      if($height<=$this->min_size || $width<=$this->min_size)
      {
        $this->add_debug("[*] Height or width are below the minimum size (which is ".$this->min_size."px): returning false from function create_thumbnail()");
        return false;
      }
      
      // if we've got valid image dimensions, continue
      if($height!=false && $width!=false)
      {
        // aspect ratio of original image
        $old_ratio = $width/$height;

        // determine thumbnail dimensions from settings
        if(!strcasecmp($this->width,'variable') && !strcasecmp($this->height,'variable'))
        {
          $thumb_width = $width;
          $thumb_height = $height;
        }
        elseif(!strcasecmp($this->width,'variable')) // variable width: hold orig aspect ratio
        {
          $thumb_width = floor($old_ratio*$this->height);
          $thumb_height = $this->height;
        }
        elseif(!strcasecmp($this->height,'variable')) // variable height: hold orig aspect ratio
        {
          $thumb_width = $this->width;
          $thumb_height = floor($this->width/$old_ratio);
        }
        else // fixed new dimensions
        {
          $thumb_width = $this->width;
          $thumb_height = $this->height;
        }

        // hard limit to $this->max_size
        if($thumb_width>$this->max_size)
        {
          $ratio = $this->max_size/$thumb_width;
          $thumb_width = $this->max_size;
          $thumb_height = $thumb_height*$ratio;
        }
        else if($thumb_height>$this->max_size)
        {
          $ratio = $this->max_size/$thumb_height;
          $thumb_height = $this->max_size;
          $thumb_width = $thumb_width*$ratio;
        }

        // account for border taking up 1px on all sides
        $thumb_height = $thumb_height-2;
        $thumb_width = $thumb_width-2;

        // determine aspect ratio of thumbnail
        $new_ratio = $thumb_width/$thumb_height;

        // dimensions of original image that crop to aspect ratio of thumbnail
        if($new_ratio>1 && $old_ratio>1) // both wide
        {
          if($new_ratio>$old_ratio) // new is wider
          {
            $crop_height = floor($width / $new_ratio);
            $crop_width  = $width;
          }
          else // old is wider
          {
            $crop_height = $height;
            $crop_width  = floor($height * $new_ratio);
          }
        }
        elseif($new_ratio<1 && $old_ratio>1) // old wide, new tall
        {
          $crop_height = $height;
          $crop_width  = floor($height * $new_ratio);
        }
        elseif($new_ratio>1 && $old_ratio<1) // old tall, new wide
        {
          $crop_height = floor($width / $new_ratio);
          $crop_width  = $width;
        }
        elseif($new_ratio<1 && $old_ratio<1) // both tall
        {
          if($new_ratio>$old_ratio) // old is taller
          {
            $crop_height = floor($width / $new_ratio);
            $crop_width  = $width;
          }
          else // new is taller
          {
            $crop_height = $height;
            $crop_width  = floor($height * $new_ratio);
          }
        }
        else // no cropping needed: same aspect ratio
        {
          $crop_height = $height;
          $crop_width  = $width;
        }
        $crop_left = floor(($width/2) - ($crop_width/2));
        $crop_top = floor(($height/2) - ($crop_height/2));

        $this->add_debug("Evaluated thumbnail: orig {$height}x{$width}; crop {$crop_height}x{$crop_width}; thumb {$thumb_height}x{$thumb_width}");
     
        if($image != false)
        {
          $quality = 85;
          $thumb=imagecreatetruecolor($thumb_width,$thumb_height);
          $result=@imagecopyresampled($thumb,$image,0,0,$crop_left,$crop_top,$thumb_width,$thumb_height,$crop_width,$crop_height);
          if(!$result)
          {
            $this->add_debug("[*] Call to function imagecopyresample failed");
            return false;
          }
          $result=@imagejpeg($thumb,$thumb_path,$quality);
          if(!$result)
          {
            $this->add_debug("[*] Call to imagejpeg([resource],$thumb_path,$quality) failed");
            return false;
          }
        }
      } // if($height!=false && width!=false)
      else
      {
        $this->add_debug("[*] Height or width or both were false: {$height}x{$width}");
      }
    } // if(!file_exists($thumb_path))
    else
    {
      $this->add_debug("Thumbnail has already been cached: $thumb_path");
    }

    return array($thumb_url,$thumb_path);

  } // function create_thumbnail

  /**
   *  Check to make sure thumbnails exist; update height
   */
  function check_thumbnails()
  {
    for($k=count($this->images)-1; $k>=0; $k--)
    {
      if(!ini_get('allow_url_fopen')) // only attempt getimagesize on URL if allow_url_fopen is enabled
        $uri = $this->images[$k]['path'];
      else
        $uri = $this->images[$k]['url'];

      $imginfo=@getimagesize($uri);
      
      if(!$imginfo)
      {
        $this->add_debug("[*] In check_thumbnails(), unsetting index $k of images ({$this->images[$k]['url']}) because getimagesize('url') returned false");
        unset($this->images[$k]);
      }
      else
      {
        if($imginfo[1] > $this->div_height)
          $this->div_height = $imginfo[1]+2;
      
        if($imginfo[0] > $this->div_width)
          $this->div_width  = $imginfo[0]+2;
      }
    }
    if($this->div_height>$this->height)
      $this->div_height=$this->height;
    if($this->div_width>$this->width)
      $this->div_width=$this->width;

    // read to display
    if(count($this->images)>0)
    {
      $this->add_debug("In function check_thumbnails(), there are ".count($this->images)." images in ".'$this->images'."; setting status to 1");
      return 1;
    }
    else
    {
      $this->add_debug('[*] In function check_thumbnails(), there are no images in $this->images; setting status to -1');
      return -1;
    }
  }

  /**
   *  Select indices from an array
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
              if(!strcasecmp($arr[$k]->get_type(),$mime))
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
              if(!strcasecmp($arr[$k]->get_medium(),$medium))
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
  function add_image($url=false,$path="",$link="",$desc="",$title="")
  {
    if($url!=false)
    {
      $idx=count($this->images);
      $this->images[$idx]['url']=$url;
      $this->images[$idx]['path']=$path;
      $this->images[$idx]['link']=$link;
      $this->images[$idx]['desc']=$desc;
      $this->images[$idx]['title']=$title;
    }
  }

  /**
   *  Display a jQuery-powered slideshow of thumbnails with adv controls
   */
  function slideshow2_html()
  {
    $html = '<div class="rssphoto_slideshow2" id="rssphoto-'.$this->id.'" style="height:'.$this->div_height.'px; width:'.$this->div_width.'px;">';
    $html .= "\n";

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
      if($this->show_img_title && !empty($img['title']))
      {
        $html .= '<div class="title_overlay"><a href="'.$img['link'].'">'.$img['title'].'</a></div>';
      }
      $html .= '<a href="'.$img['link'].'"><img src="'.$img['url'].'" alt="" /></a>';
      $html .= '</div>';
      $html .= "\n";
    }
    $html .= '<div class="rssphoto_clear"></div>';
    $html .= '</div>';
    $html .= "\n";

    if(count($this->images)>1)
    {
      $html .= "<script type='text/javascript'>\n";
      $html .= "setupSlideshow2(".$this->id.",".$this->interval.");\n";
      $html .= "</script>\n";
      $html .= "\n";
    }

    return $html;
  }

  /**
   *  Display a jQuery-powered slideshow of thumbnails
   */
  function slideshow_html()
  {
    $html = '<div class="rssphoto_slideshow" id="rssphoto-'.$this->id.'" style="height:'.$this->div_height.'px; width:'.$this->div_width.'px;">';
    $html .= "\n";

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
      if($this->show_img_title && !empty($img['title']))
      {
        $html .= '<div class="title_overlay"><a href="'.$img['link'].'">'.$img['title'].'</a></div>';
      }
      $html .= '<a href="'.$img['link'].'"><img src="'.$img['url'].'" alt="" /></a>';
      $html .= '</div>';
      $html .= "\n";
    }
    $html .= '<div class="rssphoto_clear"></div>';
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
      if($this->show_img_title && !empty($img['title']))
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
   *  Update settings
   */
  function update($settings)
  {
    if(isset($settings['rssphoto_title']))          $this->title          = $settings['rssphoto_title'];
    if(isset($settings['rssphoto_url']))            $this->url            = $settings['rssphoto_url'];
    if(isset($settings['rssphoto_height']))         $this->height         = $settings['rssphoto_height'];
    if(isset($settings['rssphoto_width']))          $this->width          = $settings['rssphoto_width'];
    if(isset($settings['rssphoto_img_sel']))        $this->img_sel        = $settings['rssphoto_img_sel'];
    if(isset($settings['rssphoto_num_img']))        $this->num_img        = $settings['rssphoto_num_img'];
    if(isset($settings['rssphoto_item_sel']))       $this->item_sel       = $settings['rssphoto_item_sel'];
    if(isset($settings['rssphoto_num_item']))       $this->num_item       = $settings['rssphoto_num_item'];
    if(isset($settings['rssphoto_show_title']))     $this->show_title     = $settings['rssphoto_show_title'];
    if(isset($settings['rssphoto_show_img_title'])) $this->show_img_title = $settings['rssphoto_show_img_title'];
    if(isset($settings['rssphoto_output']))         $this->output         = $settings['rssphoto_output'];
    if(isset($settings['rssphoto_interval']))       $this->interval       = $settings['rssphoto_interval'];
    if(isset($settings['rssphoto_min_size']))       $this->min_size       = $settings['rssphoto_min_size'];
    if(isset($settings['rssphoto_max_size']))       $this->max_size       = $settings['rssphoto_max_size'];
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
   *  Simple error handler 
   */
  function ignominious_death()
  {
    if(empty($this->error_msg))
      $this->add_debug('RSSPhoto died an ignominious death');

    $this->status = -1;
  }

  /**
   *  Track debug messages
   */
  function add_debug($msg="",$file=false,$line=false)
  {
    $this->debug_msgs[]=$msg.($file?" [in file $file]":"").($line?" [on line $line]":"");;
  }

  /**
   *  Print out the debug messages
   */
  function print_debug($hide=true)
  {
    if($hide)
    {
      print "<!--\n";
      print "RSSPhoto v".$this->version." debug output START\n";
      for($k=0; $k<count($this->debug_msgs); $k++)
      {
        print $this->debug_msgs[$k]."\n";
      }
      print "RSSPhoto v".$this->version." debug output END\n";
      print "-->\n";
    }
    else
    {
      print "<h2>RSSPhoto v".$this->version." debug output</h2>\n";
      print "<ul>\n";
      for($k=0; $k<count($this->debug_msgs); $k++)
      {
        print "<li>";
        print $this->debug_msgs[$k];
        print "</li>\n";
      }
      print "</ul>\n";
    }
  }

  /*
   * Error handler to add debug messages
   */
  function RSSPhotoErrorHandler($errno, $errstr, $errfile, $errline)
  {
    $this->add_debug("[*] PHP ERROR [$errno] $errstr [in $errfile on line $errline]");

    // don't execute PHP Error Handler
    return true;
  }


  /*********************************
   *  Publically exposed functions
   *********************************/

  /**
   *  Determine whether or not to show the Widget title
   */
  function show_title()
  {
    return ($this->show_title==1);
  }

  /**
   *  Return the RSSPhoto title
   */
  function title()
  {
    return $this->title;
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
        return $this->static_html();
        break;
      case 'Slideshow2':
      default:
        return $this->slideshow2_html();
        break;
    }
  }

  /**
   *  Simple test whether RSSPhoto is ready to display
   */
  function ready()
  {
    return ($this->status==1);
  }

  /**
   *  Return the established error message
   */
  function get_error()
  {
    return $this->debug_msgs[count($this->debug_msgs)-1];
  }

  /**
   *  Initialize RSSPhoto.  
   *  Call after construction (and settings established), before display (e.g., before calling html()).
   */
  function init()
  {
    // we want to capture any errors
    $old_error_handler = set_error_handler(array($this, "RSSPhotoErrorHandler"),E_WARNING|E_USER_ERROR|E_USER_WARNING);

    // set up pseudo-random ID for this instance of RSSPhoto
    $this->id = rand();

    // set up the SimplePie feed
    $this->init_feed($this->url,$this->parser,$this->cache_dir,$this->user_cache_dir,$this->force_feed);

    // error if feed wasn't set properly
    if(empty($this->feed))
    {
      $this->add_debug('[*] Dying in function init() because $this->feed is empty');
      $this->ignominious_death();
      if($this->debug)
        $this->print_debug();
      return;
    }

    // error if feed wasn't set properly
    if(is_wp_error($this->feed))
    {
      $this->add_debug('[*] Dying in function init() because $this->feed is a Wordpress WP_Error object');
      $this->add_debug("[*]".$this->feed->get_error_message());
      $this->ignominious_death();
      if($this->debug)
        $this->print_debug();
      return;
    }

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
          if($this->feed->get_type() & SIMPLEPIE_TYPE_RSS_ALL)
          {
            $this->add_debug('Feed is of type RSS');
            switch($this->rss_type_src)
            {
              case 'Enclosures':
                $this->add_debug('Forced to look in enclosures for images');
                $image_url = $this->get_img_urls($enclosures,$this->img_sel,$this->num_img,'Enclosures');
                break;
              case 'Description':
                $this->add_debug('Forced to look in description for images');
                $image_url = $this->get_img_urls($item,$this->img_sel,$this->num_img,'Description');
                break;
              case 'Content':
                $this->add_debug('Forced to look in content for images');
                $image_url = $this->get_img_urls($item,$this->img_sel,$this->num_img,'Content');
                break;
              case 'Choose':
                if($enclosures = $item->get_enclosures())
                {
                  $this->add_debug('There are enclosures in the feed; looking for images in the enclosures');
                  $image_url = $this->get_img_urls($enclosures,$this->img_sel,$this->num_img,'Enclosures');
                }
                else
                {
                  $this->add_debug('There are no enclosures in the feed; looking for images in the description');
                  $image_url = $this->get_img_urls($item,$this->img_sel,$this->num_img,'Description');
                }
                break;
            }
          }
          elseif ($this->feed->get_type() & SIMPLEPIE_TYPE_ATOM_ALL)
          {
            $this->add_debug('Feed is of type Atom; looking for images in content');
            $image_url = $this->get_img_urls($item,$this->img_sel,$this->num_img,'Content');
          }

          if(is_array($image_url))
          {
            foreach($image_url as $url)
            {
              $this->add_debug("Evaluating $url");
              list($thumb_url,$thumb_path) = $this->create_thumbnail($url);
              if($thumb_url!=false)
              {
                $this->add_debug("Adding locally stored file $thumb_url");
                $this->add_image($thumb_url,$thumb_path,$item->get_link(0),$item->get_description(),$item->get_title());
              }
              else
                $this->add_debug("[*] Failed to add image at $url because create_thumbnail() returned false");
            }
          }
          else
          {
            $this->add_debug("[*] In function init(), get_image_urls returned '$image_url' which is not an array; skipping");
            next;
          }
        }
        else // item==false
        {
          if($this->feed->error())
            $this->add_debug($this->feed->error());
          else
            $this->add_debug("Tried to load item #$item_idx from {$this->url} and couldn't!");

          next;
        }
      }
    }
    else // feed->get_item_quantity() == 0
    {
      $this->add_debug("There were no items found in the feed at {$this->url} (feed->get_item_quantity()=".$feed->get_item_quantity());
      if($this->debug)
        $this->print_debug();
      return;
    }

    $this->status = $this->check_thumbnails();

    if($this->debug)
      $this->print_debug();

    // return control to whatever it was previously
    set_error_handler($old_error_handler);
  }

  /**
   *  Constructor.  
   *  Call with array of settings where keys are the setting names, values are the settings.
   */
  function RSSPhoto($settings=array())
  {
    $this->update($settings);
  }
}
