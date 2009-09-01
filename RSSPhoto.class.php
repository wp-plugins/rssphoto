<?php

class RSSPhoto
{
  /****************************
   * Internally used variables
   ****************************/
  private $images         = array();
  private $error_msg      = "";

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

  function RSSPhoto($instance=array())
  {
    $this->update($instance);
  }

  function init()
  {
    // initialize SimplePie object
    $this->feed = new SimplePie();
    $this->feed->set_cache_location($this->cache_location);
    $this->feed->set_feed_url($this->url);
    $this->feed->force_feed($this->force_feed);
    $this->feed->init();

    // get images, generation thumbnails, etc.
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

  function slideshow_html()
  {
    $html = '<div id="rssphoto_slideshow" style="height:'.$this->size.'px;">';

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
    }
    $html .= '</div>';

    return $html;
  }

  function static_html()
  {
    $html = '<div id="rssphoto_static">';
    foreach($this->images as $img)
    {
      $html .= '<div><a href="'.$img['link'].'"><img src="'.$img['url'].'"></a></div>';
    }
    $html .= '</div>';

    return $html;
  }

  function title()
  {
    return $this->title;
  }

  function update($instance)
  {
    if(!empty($instance['rssphoto_title']))      $this->title      = $instance['rssphoto_title'];
    if(!empty($instance['rssphoto_url']))        $this->url        = $instance['rssphoto_url'];
    if(!empty($instance['rssphoto_fixed']))      $this->fixed      = $instance['rssphoto_fixed'];
    if(!empty($instance['rssphoto_size']))       $this->size       = $instance['rssphoto_size'];
    if(!empty($instance['rssphoto_img_sel']))    $this->img_sel    = $instance['rssphoto_img_sel'];
    if(!empty($instance['rssphoto_num_img']))    $this->num_img    = $instance['rssphoto_num_img'];
    if(!empty($instance['rssphoto_item_sel']))   $this->item_sel   = $instance['rssphoto_item_sel'];
    if(!empty($instance['rssphoto_num_item']))   $this->num_item   = $instance['rssphoto_num_item'];
    if(!empty($instance['rssphoto_show_title'])) $this->show_title = $instance['rssphoto_show_title'];
    if(!empty($instance['rssphoto_output']))     $this->output     = $instance['rssphoto_output'];
  }
}
