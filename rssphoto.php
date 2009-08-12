<?php
/*
 * Plugin Name: RSSPhoto
 * Plugin URI: http://blog.spencerkellis.net/projects/rssphoto
 * Description: Display photos from an RSS or Atom feed
 * Version: 0.3.2
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
    $img_sel = empty($instance['rssphoto_img_sel']) ? 'First' : $instance['rssphoto_img_sel'];
    $item_sel = empty($instance['rssphoto_item_sel']) ? 'Random' : $instance['rssphoto_item_sel'];
    $src = empty($instance['rssphoto_src']) ? 'Content' : $instance['rssphoto_src'];
    $show_title = empty($instance['rssphoto_show_title']) ? 0 : $instance['rssphoto_show_title'];

    // initialize SimplePie object
    $feed = new SimplePie();
    $feed->set_cache_location('wp-content/cache');
    $feed->set_feed_url($url);
    $feed->init();

    if($feed->get_item_quantity() > 0)
    {
      // choose random feed item
      switch($item_sel)
      {
        case 'Most Recent':
          $item_idx = 0;
          break;
        case 'Random':
        default:
          $item_idx = rand(0,$feed->get_item_quantity()-1);
          break;
      }
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
          preg_match_all('/img([^>]*)src="([^"]*)"/i', $content, $m);
        elseif($src == 'Description')
          preg_match_all('/img([^>]*)src="([^"]*)"/i', $description, $m);
     
        // pick random image from feed item
        switch($img_sel)
        {
          case 'First':
            $image_idx = 0;
            break;
          case 'Random':
          default:
            $image_idx = rand(0,count($m[2])-1);
            break;
        }
        $image_url = htmlspecialchars_decode($m[2][$image_idx]);

        // attempt to get image dimensions using getimagesize
        list($width, $height, $type, $attr) = getimagesize($image_url);
     
        // if that doesn't work, check for GD and use imagesx/imagesy
        if($height==false && $width==false && function_exists('imagecreatefromjpeg'))
        {
          $image = @imagecreatefromjpeg($image_url);
          $height = @imagesy($image);
          $width = @imagesx($image);
        }
     
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
                $image = @imagecreatefromjpeg($image_url);
         
              if($image != false)
              {
                $quality = 85;
                $thumb=imagecreatetruecolor($thumb_width,$thumb_height);
                @imagecopyresampled($thumb,$image,0,0,0,0,$thumb_width,$thumb_height,$width,$height);
                @imagejpeg($thumb,$thumb_path,$quality);
              }
            }
          }
        
          # Before the widget
          echo $before_widget;
          
          # The title
          if ( $title )
          echo $before_title . $title . $after_title;
          
          ?>
          <div id="rssphoto_<?php echo $widget_id; ?>_imageDiv">
          <?php
          if($show_title)
          {
            echo "<h3>$item_title</h3>";
          }
          ?>
          <a href="<?php echo $item_url; ?>"><img id="rssphoto_<?php echo $widget_id; ?>_image" src=""></a>
          </div>
          <script type="text/javascript">
          // when the DOM is ready
          if(typeof jQuery != 'undefined')
          {
            jQuery(function () {
              jQuery('#rssphoto_<?php echo $widget_id; ?>_image').hide();
            
              var main_img = new Image();
              jQuery(main_img).load(function () {
                jQuery('#rssphoto_<?php echo $widget_id; ?>_image')
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
            document.getElementById('rssphoto_<?php echo $widget_id; ?>_image').src = '<?php echo $thumb_url; ?>';
            document.getElementById('rssphoto_<?php echo $widget_id; ?>_image').style.border = 'solid black 1px';
          }
          </script>
          <?php
          
          # After the widget
          echo $after_widget;
        }
      }
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
    $instance['rssphoto_item_sel'] = strip_tags(stripslashes($new_instance['rssphoto_item_sel']));
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
    $instance = wp_parse_args( (array) $instance, array('rssphoto_title'=>'RSSPhoto','rssphoto_url'=>'http://photography.spencerkellis.net/atom.php','rssphoto_size'=>150,'rssphoto_fixed'=>'Max','rssphoto_item_sel'=>'Random','rssphoto_img_sel'=>'First','rssphoto_src'=>'Content') );

    $title = htmlspecialchars($instance['rssphoto_title']);
    $url = htmlspecialchars($instance['rssphoto_url']);
    $fixed = htmlspecialchars($instance['rssphoto_fixed']);
    $size = htmlspecialchars($instance['rssphoto_size']);
    $img_sel = htmlspecialchars($instance['rssphoto_img_sel']);
    $item_sel = htmlspecialchars($instance['rssphoto_item_sel']);
    $src = htmlspecialchars($instance['rssphoto_src']);

    //Display form

    echo '<h3>General Settings</h3>';

    // Title
    echo '<div style="text-align:right; float:left; width:130px;"><label for="' . $this->get_field_name('rssphoto_title') . '">' . __('Title:') . '</label></div>';
    echo '<div style="text-align:left; float:left; width:350px; padding-left:5px;">';
    echo '<input style="width: 350px;" id="' . $this->get_field_id('rssphoto_title') . '" name="' . $this->get_field_name('rssphoto_title') . '" type="text" value="' . $title . '" />';
    echo '</div>';

    // URL
    echo '<div style="text-align:right; float:left; width:130px;"><label for="' . $this->get_field_name('rssphoto_url')   . '">' . __('URL:')   . '</label></div>';
    echo '<div style="text-align:left; float:left; width:350px; padding-left:5px;">';
    echo '<input style="width: 350px;" id="' . $this->get_field_id('rssphoto_url')   . '" name="' . $this->get_field_name('rssphoto_url')   . '" type="text" value="' . $url   . '" />';
    echo '</div>';

    echo '<div style="clear:both;">&nbsp;</div>';
    echo '<h3>Selection Settings</h3>';

    // Feed Item Selection
    echo '<div style="text-align:right; float:left; width:130px;">' . __('Feed Item Selection:') . '</div>';
    echo '<div style="text-align:left; float:left; width:350px; padding-left:5px;">';
    echo '<label for="' . $this->get_field_name('rssphoto_item_sel_random')     . '"><input ' . (($item_sel=='Random') ? 'checked' : '')       . ' type="radio" id="' . $this->get_field_id('rssphoto_item_sel_random')     . '" name="' . $this->get_field_name('rssphoto_item_sel') . '" value="Random">'       . __('Random')       . '</label><br>';
    echo '<label for="' . $this->get_field_name('rssphoto_item_sel_mostrecent') . '"><input ' . (($item_sel=='Most Recent')  ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_item_sel_mostrecent') . '" name="' . $this->get_field_name('rssphoto_item_sel') . '" value="Most Recent">'  . __('Most Recent')  . '</label><br>';
    echo '</div>';

    // Image Selection
    echo '<div style="clear:both;">&nbsp;</div>';
    echo '<div style="text-align:right; float:left; width:130px;">' . __('Image Selection:') . '</div>';
    echo '<div style="text-align:left; float:left; width:350px; padding-left:5px;">';
    echo '<label for="' . $this->get_field_name('rssphoto_img_sel_random') . '"><input ' . (($img_sel=='Random') ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_img_sel_random') . '" name="' . $this->get_field_name('rssphoto_img_sel') . '" value="Random">' . __('Random') . '</label><br>';
    echo '<label for="' . $this->get_field_name('rssphoto_img_sel_first')  . '"><input ' . (($img_sel=='First')  ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_img_sel_first')  . '" name="' . $this->get_field_name('rssphoto_img_sel') . '" value="First">'  . __('First')  . '</label><br>';
    echo '</div>';

    // Pull Images From
    echo '<div style="clear:both;">&nbsp;</div>';
    echo '<div style="text-align:right; float:left; width:130px;">' . __('Pull Images From:') . '</div>';
    echo '<div style="text-align:left; float:left; width:350px; padding-left:5px;">';
    echo '<label for="' . $this->get_field_name('rssphoto_src_content')     . '"><input ' . (($src=='Content')     ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_src_content')     . '" name="' . $this->get_field_name('rssphoto_src') . '" value="Content">' . __('Content') . '</label><br>';
    echo '<label for="' . $this->get_field_name('rssphoto_src_description') . '"><input ' . (($src=='Description') ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_src_description') . '" name="' . $this->get_field_name('rssphoto_src') . '" value="Description">' . __('Description') . '</label><br>';
    echo '</div>';

    echo '<div style="clear:both;">&nbsp;</div>';
    echo '<h3>Display Settings</h3>';

    // Fixed Dimension
    echo '<div style="text-align:right; float:left; width:130px;">' . __('Fixed Dimension:') . '</div>';
    echo '<div style="text-align:left; float:left; width:350px; padding-left:5px;">';
    echo '<label for="' . $this->get_field_name('rssphoto_fix_width')  . '"><input ' . (($fixed=='Width')  ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_fix_width')  . '" name="' . $this->get_field_name('rssphoto_fixed') . '" value="Width">'  . __('Width')  . '</label><br>';
    echo '<label for="' . $this->get_field_name('rssphoto_fix_height') . '"><input ' . (($fixed=='Height') ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_fix_height') . '" name="' . $this->get_field_name('rssphoto_fixed') . '" value="Height">' . __('Height') . '</label><br>';
    echo '<label for="' . $this->get_field_name('rssphoto_fix_max')    . '"><input ' . (($fixed=='Max')    ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_fix_max')    . '" name="' . $this->get_field_name('rssphoto_fixed') . '" value="Max">'    . __('Max')    . '</label><br>';
    echo '</div>';

    // Dimension Size (px)
    echo '<div style="clear:both;">&nbsp;</div>';
    echo '<div style="text-align:right; float:left; width:130px;"><label for="' . $this->get_field_name('rssphoto_size')   . '">' . __('Dimension Size (px):')   . '</label></div>';
    echo '<div style="text-align:left; float:left; width:350px; padding-left:5px;">';
    echo '<input style="width: 350px;" id="' . $this->get_field_id('rssphoto_size')   . '" name="' . $this->get_field_name('rssphoto_size')   . '" type="text" value="' . $size   . '" />';
    echo '</div>';

    // Bottom Spacing
    echo '<div style="clear:both;">&nbsp;</div>';
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
