<?php
/*
 * Plugin Name: RSSPhoto
 * Plugin URI: http://blog.spencerkellis.net/projects/rssphoto
 * Description: Display photos from an RSS or Atom feed
 * Version: 0.4
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

    $widget_id = rand();

    extract($args);

    // defaults
    $title = apply_filters('widget_title', empty($instance['rssphoto_title']) ? '&nbsp;' : $instance['rssphoto_title']);
    $url = empty($instance['rssphoto_url']) ? 'http://photography.spencerkellis.net/atom.php' : $instance['rssphoto_url'];
    $fixed = empty($instance['rssphoto_fixed']) ? 'Max' : $instance['rssphoto_fixed'];
    $size = empty($instance['rssphoto_size']) ? 150 : $instance['rssphoto_size'];
    $img_sel = empty($instance['rssphoto_img_sel']) ? 'Most Recent' : $instance['rssphoto_img_sel'];
    $num_img = empty($instance['rssphoto_num_img']) ? 1 : $instance['rssphoto_num_img'];
    $min_size = empty($instance['rssphoto_min_size']) ? 10 : $instance['rssphoto_min_size'];
    $item_sel = empty($instance['rssphoto_item_sel']) ? 'Random' : $instance['rssphoto_item_sel'];
    $num_item = empty($instance['rssphoto_num_item']) ? 1 : $instance['rssphoto_num_item'];
    $src = empty($instance['rssphoto_src']) ? 'Content' : $instance['rssphoto_src'];
    $show_title = 0;//empty($instance['rssphoto_show_title']) ? 0 : $instance['rssphoto_show_title'];

    // initialize SimplePie object
    $feed = new SimplePie();
    $feed->set_cache_location('wp-content/cache');
    $feed->set_feed_url($url);
    $feed->init();

    $curr_num_item = $num_item;
    if($feed->get_item_quantity() < $curr_num_item)
    {
      $curr_num_item = $feed->get_item_quantity();
    }

    if($curr_num_item > 0)
    {

      $item_idxs = $this->select_indices($item_sel,$feed->get_items(),$curr_num_item);

      # Before the widget
      echo $before_widget;
      
      # The title
      if ( $title )
        echo $before_title . $title . $after_title;

      // choose feed item(s)
      $item_tracker = 0;
      foreach($item_idxs as $item_idx)
      {
        $item = $feed->get_item($item_idx);

        if($item != false)
        {
          // set up the "straw man" var
          $image = false;
       
          // pull out image url, link
          $content = $item->get_content();
          $description = $item->get_description();
          $item_title = $item->get_title();
          $item_url = $item->get_link(0);
          if($src == 'Content')
            $num_img_avail = preg_match_all('/img([^>]*)src="([^"]*)"/i', $content, $m);
          elseif($src == 'Description')
            $num_img_avail = preg_match_all('/img([^>]*)src="([^"]*)"/i', $description, $m);


          $curr_num_img=$num_img;
          if($num_img_avail < $curr_num_img)
          {
            $curr_num_img = $num_img_avail;
          }

          if($curr_num_img > 0)
          {
            $img_idxs = $this->select_indices($img_sel,$m[2],$curr_num_img);

            // choose image(s)
            $img_tracker = 0;
            foreach($img_idxs as $img_idx)
            {
              $image_url = htmlspecialchars_decode($m[2][$img_idx]);
              echo "<!--$image_url-->\n";
              $thumb_url = $this->create_thumbnail($image_url,$fixed,$size,$min_size);
              if($thumb_url!=false)
              {
                ?>
                <div id="rssphoto_<?php echo $widget_id; ?>_imageDiv_<?php echo $item_tracker; ?>_<?php echo $img_tracker; ?>">
                <?php
                if($show_title)
                {
                  echo "<h3>$item_title</h3>";
                }
                ?>
                <a href="<?php echo $item_url; ?>"><img id="rssphoto_<?php echo $widget_id; ?>_image_<?php echo $item_tracker; ?>_<?php echo $img_tracker; ?>" src=""></a>
                </div>
                <script type="text/javascript">
                // when the DOM is ready
                if(typeof jQuery != 'undefined')
                {
                  jQuery(function () {
                    jQuery('#rssphoto_<?php echo $widget_id; ?>_image_<?php echo $item_tracker; ?>_<?php echo $img_tracker; ?>').hide();
                  
                    var main_img = new Image();
                    jQuery(main_img).load(function () {
                      jQuery('#rssphoto_<?php echo $widget_id; ?>_image_<?php echo $item_tracker; ?>_<?php echo $img_tracker; ?>')
                        .attr('src',"<?php echo $thumb_url; ?>")
                        .css('border','solid black 1px')
                        .slideDown()
                        .fadeIn();
                    })
                    .attr('src', "<?php echo $thumb_url; ?>");
                  });
                }
                else
                {
                  document.getElementById('rssphoto_<?php echo $widget_id; ?>_image_<?php echo $item_tracker; ?>_<?php echo $img_tracker; ?>').src = '<?php echo $thumb_url; ?>';
                  document.getElementById('rssphoto_<?php echo $widget_id; ?>_image_<?php echo $item_tracker; ?>_<?php echo $img_tracker; ?>').style.border = 'solid black 1px';
                }
                </script>
                <?php
                $img_tracker = $img_tracker + 1;
              } // if thumb_url != false
            } // foreach images
          } // if curr_num_img > 0
        } // if item != false
        else
        {
          $curr_title = apply_filters('widget_title', 'Oops!');
          echo $before_widget;
          echo $before_title . $curr_title . $after_title;
          echo "<p>Tried to load item #$item_idx from <a href=\"$url\">$title</a> and couldn't!</p>";
          if($feed->error())
          {
            echo "<p>The SimplePie error was ";
            echo $feed->error();
            echo "</p>";
          }
        }
        $item_tracker = $item_tracker + 1;
      }
            
      # After the widget
      echo $after_widget;
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
    $instance['rssphoto_min_size'] = strip_tags(stripslashes($new_instance['rssphoto_min_size']));
    $instance['rssphoto_num_item'] = strip_tags(stripslashes($new_instance['rssphoto_num_item']));
    $instance['rssphoto_src'] = strip_tags(stripslashes($new_instance['rssphoto_src']));
    $instance['rssphoto_show_title'] = strip_tags(stripslashes($new_instance['rssphoto_src']));

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
                                                        'rssphoto_size'=>150,
                                                        'rssphoto_fixed'=>'Max',
                                                        'rssphoto_img_sel'=>'Most Recent',
                                                        'rssphoto_num_img'=>1,
                                                        'rssphoto_min_size'=>10,
                                                        'rssphoto_item_sel'=>'Random',
                                                        'rssphoto_num_item'=>1,
                                                        'rssphoto_src'=>'Content') 
                                                       );

    $title = htmlspecialchars($instance['rssphoto_title']);
    $url = htmlspecialchars($instance['rssphoto_url']);
    $fixed = htmlspecialchars($instance['rssphoto_fixed']);
    $size = htmlspecialchars($instance['rssphoto_size']);
    $img_sel = htmlspecialchars($instance['rssphoto_img_sel']);
    $num_img = htmlspecialchars($instance['rssphoto_num_img']);
    $min_size = htmlspecialchars($instance['rssphoto_min_size']);
    $item_sel = htmlspecialchars($instance['rssphoto_item_sel']);
    $num_item = htmlspecialchars($instance['rssphoto_num_item']);
    $src = htmlspecialchars($instance['rssphoto_src']);

    //Display form

    echo '<h3>General Settings</h3>';

    // Title
    echo '<div style="text-align:right; float:left; width:130px;"><label for="' . $this->get_field_name('rssphoto_title') . '">' . __('Title:') . '</label></div>';
    echo '<div style="text-align:left; float:left; width:350px; padding-left:5px;">';
    echo '<input style="width: 350px;" id="' . $this->get_field_id('rssphoto_title') . '" name="' . $this->get_field_name('rssphoto_title') . '" type="text" value="' . $title . '" />';
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

    // Feed Item Selection
    echo '<div style="text-align:right; float:left; width:130px;">' . __('Feed Item Selection:') . '</div>';
    echo '<div style="text-align:left; float:left; width:110px; padding-left:5px;">';
    echo '<label for="' . $this->get_field_name('rssphoto_item_sel_random')     . '"><input ' . (($item_sel=='Random') ? 'checked' : '')      . ' type="radio" id="' . $this->get_field_id('rssphoto_item_sel_random')     . '" name="' . $this->get_field_name('rssphoto_item_sel') . '" value="Random">'      . __('Random')      . '</label><br>';
    echo '<label for="' . $this->get_field_name('rssphoto_item_sel_mostrecent') . '"><input ' . (($item_sel=='Most Recent') ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_item_sel_mostrecent') . '" name="' . $this->get_field_name('rssphoto_item_sel') . '" value="Most Recent">' . __('Most Recent') . '</label><br>';
    echo '</div>';

    echo '</div>';
    echo '<div style="float:left; width:240px;">';

    // Number Feed Items
    echo '<div style="text-align:right; float:left; width:130px;"><label for="' . $this->get_field_name('rssphoto_num_item')   . '">' . __('# Items to Display:')   . '</label></div>';
    echo '<div style="text-align:left; float:left; width:70px; padding-left:5px;">';
    echo '<input style="width: 50px;" id="' . $this->get_field_id('rssphoto_num_item')   . '" name="' . $this->get_field_name('rssphoto_num_item')   . '" type="text" value="' . $num_item . '" />';
    echo '</div>';

    echo '</div>';

    /*--------------------------------------------------------------------*/

    echo '<div style="clear:both;">&nbsp;</div>';
    echo '<div style="float:left; width:260px;">';

    // Pull Images From
    echo '<div style="text-align:right; float:left; width:130px;">' . __('Pull Images From:') . '</div>';
    echo '<div style="text-align:left; float:left; width:110px; padding-left:5px;">';
    echo '<label for="' . $this->get_field_name('rssphoto_src_content')     . '"><input ' . (($src=='Content')     ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_src_content')     . '" name="' . $this->get_field_name('rssphoto_src') . '" value="Content">' . __('Content') . '</label><br>';
    echo '<label for="' . $this->get_field_name('rssphoto_src_description') . '"><input ' . (($src=='Description') ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_src_description') . '" name="' . $this->get_field_name('rssphoto_src') . '" value="Description">' . __('Description') . '</label><br>';
    echo '</div>';

    echo '</div>';
    echo '<div style="float:left; width:240px;">';

    // Minimum Image Size
    echo '<div style="text-align:right; float:left; width:130px;"><label for="' . $this->get_field_name('rssphoto_min_size')   . '">' . __('Minimum Size (px):')   . '</label></div>';
    echo '<div style="text-align:left; float:left; width:70px; padding-left:5px;">';
    echo '<input style="width: 50px;" id="' . $this->get_field_id('rssphoto_min_size')   . '" name="' . $this->get_field_name('rssphoto_min_size')   . '" type="text" value="' . $min_size   . '" />';
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

    // Number Images
    echo '<div style="text-align:right; float:left; width:130px;"><label for="' . $this->get_field_name('rssphoto_num_img')   . '">' . __('# Images to Display:')   . '</label></div>';
    echo '<div style="text-align:left; float:left; width:70px; padding-left:5px;">';
    echo '<input style="width: 50px;" id="' . $this->get_field_id('rssphoto_num_img')   . '" name="' . $this->get_field_name('rssphoto_num_img')   . '" type="text" value="' . $num_img . '" />';
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

    // Dimension Size (px)
    echo '<div style="text-align:right; float:left; width:130px;"><label for="' . $this->get_field_name('rssphoto_size')   . '">' . __('Size (px):')   . '</label></div>';
    echo '<div style="text-align:left; float:left; width:70px; padding-left:5px;">';
    echo '<input style="width:50px;" id="' . $this->get_field_id('rssphoto_size')   . '" name="' . $this->get_field_name('rssphoto_size')   . '" type="text" value="' . $size   . '" />';
    echo '</div>';

    echo '</div>';

    /*--------------------------------------------------------------------*/

    // Bottom Spacing
    echo '<div style="clear:both;">&nbsp;</div>';
  }

  /**
  * Create thumbnails of a given image url in the local cache
  *
  */
  function create_thumbnail($image_url,$fixed,$size,$min_size)
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

    if($height<=$min_size|| $width<=$min_size)
      return false;
    
    // if we've got valid image dimensions, continue
    if($height!=false && $width!=false)
    {
      // default parameters
      switch($fixed)
      {
        case 'Width':
          $ratio = $size/$width;
          $thumb_width = $size;
          $thumb_height = $height * $ratio;
          break;
        case 'Height':
          $ratio = $size/$height;
          $thumb_height = $size;
          $thumb_width = $width * $ratio;
          break;
        case 'Max':
        default:
          $ratio = $size/max($height,$width);
          if($width>$height)
          {
            $thumb_width = $size;
            $thumb_height = $height * $ratio;
          }
          else
          {
            $thumb_height = $size;
            $thumb_width = $width * $ratio;
          }
          break;
      }
      $thumb_url = $image_url;
      
      // use GD library to create cached thumbnail if necessary
      if(($width>$thumb_width || $height>$thumb_height) &&
         function_exists('imagecreatefromjpeg'))
      {
        $image_filename = md5($image_url)."-$fixed-$size.jpg";
        $thumb_path = "wp-content/cache/$image_filename";
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
  * Create thumbnails of a given image url in the local cache
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

}// END class

/**
* Register RSSPhoto Widget
*
* Calls 'widgets_init' action after the Hello World widget has been registered.
*/
function RSSPhotoWidgetInit() 
{
  register_widget('RSSPhotoWidget');
  wp_enqueue_script('jquery');
}

add_action('widgets_init', 'RSSPhotoWidgetInit');
?>
