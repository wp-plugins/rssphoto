<?php
/*
 * Plugin Name: RSSPhoto
 * Version: 1.0
 * Plugin URI: http://photography.spencerkellis.net
 * Description: Display photos from an RSS or Atom feed
 * Author: Spencer Kellis
 * Author URI: http://www.spencerkellis.net
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
    extract($args);

    // defaults
    $title = apply_filters('widget_title', empty($instance['title']) ? '&nbsp;' : $instance['title']);
    $url = empty($instance['rssphoto_url']) ? 'http://photography.spencerkellis.net/atom.php' : $instance['rssphoto_url'];
    $max = empty($instance['rssphoto_max']) ? 150 : $instance['rssphoto_max'];
    $sel = empty($instance['rssphoto_sel']) ? 'Random' : $instance['rssphoto_sel'];

    // initialize SimplePie object
    $feed = new SimplePie();
    $feed->set_cache_location('wp-content/cache');
    $feed->set_feed_url($url);
    $feed->init();

    if($feed->get_item_quantity() > 0)
    {
      // choose random feed item
      switch($sel)
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
        $item_url = $item->get_link(0);
        preg_match_all('/<img([^>]*)src="([^"]*)"([^>]*)>/i', $content, $m);
     
        // pick random image from feed item
        switch($sel)
        {
          case 'Most Recent':
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
          $ratio = $max/max($height,$width);
          if($width>$height)
          {
            $thumb_width = $max;
            $thumb_height = $height * $ratio;
          }
          else
          {
            $thumb_height = $max;
            $thumb_width = $width * $ratio;
          }
          $thumb_url = $image_url;
     
          // use GD library to create cached thumbnail if necessary
          if(max($width,$height)>$max && function_exists('imagecreatefromjpeg'))
          {
            $image_filename = md5($image_url).".jpg";
            $thumb_path = "wp-content/cache/$image_filename";
            $thumb_url = "http://blog.spencerkellis.net/$thumb_path";
          
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
          <div id="rssphoto_imageDiv">
          <a href="<?php echo $item_url; ?>"><img id="rssphoto_image" src=""></a>
          </div>
          <script type="text/javascript">
          // when the DOM is ready
          if(typeof jQuery != 'undefined')
          {
            jQuery(function () {
              jQuery('#rssphoto_image').hide();
            
              var main_img = new Image();
              jQuery(main_img).load(function () {
                jQuery('#rssphoto_image')
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
            document.getElementById('rssphoto_image').src = '<?php echo $thumb_url; ?>';
            document.getElementById('rssphoto_image').style.border = 'solid black 1px';
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
    $instance['rssphoto_max'] = strip_tags(stripslashes($new_instance['rssphoto_max']));
    $instance['rssphoto_sel'] = strip_tags(stripslashes($new_instance['rssphoto_sel']));

    return $instance;
  }

  /**
  * Creates the edit form for the widget.
  *
  */
  function form($instance)
  {
    //Defaults
    $instance = wp_parse_args( (array) $instance, array('rssphoto_title'=>'RSSPhoto','rssphoto_url'=>'http://photography.spencerkellis.net/atom.php','rssphoto_max'=>150,'rssphoto_sel','Random') );

    $title = htmlspecialchars($instance['rssphoto_title']);
    $url = htmlspecialchars($instance['rssphoto_url']);
    $max = htmlspecialchars($instance['rssphoto_max']);
    $sel = htmlspecialchars($instance['rssphoto_sel']);

    //Display form
    echo '<div style="text-align:right;"><label for="' . $this->get_field_name('rssphoto_title') . '">' . __('Title:') . '<input style="width: 350px;" id="' . $this->get_field_id('rssphoto_title') . '" name="' . $this->get_field_name('rssphoto_title') . '" type="text" value="' . $title . '" /></label></div>';
    echo '<div style="text-align:right;"><label for="' . $this->get_field_name('rssphoto_url')   . '">' . __('URL:')   . '<input style="width: 350px;" id="' . $this->get_field_id('rssphoto_url')   . '" name="' . $this->get_field_name('rssphoto_url')   . '" type="text" value="' . $url   . '" /></label></div>';
    echo '<div style="text-align:right;"><label for="' . $this->get_field_name('rssphoto_max')   . '">' . __('URL:')   . '<input style="width: 350px;" id="' . $this->get_field_id('rssphoto_max')   . '" name="' . $this->get_field_name('rssphoto_max')   . '" type="text" value="' . $max   . '" /></label></div>';
    echo '<div style="text-align:right; float:left; width:150px;">' . __('Image Selection:') . '</div>';
    echo '<div style="text-align:left; float:left; width:350px; padding-left:5px;">';
    echo '<label for="' . $this->get_field_name('rssphoto_sel_random')     . '"><input ' . (($sel=='Random')      ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_sel_random')     . '" name="' . $this->get_field_name('rssphoto_sel') . '" value="Random">' . __('Random') . '</label><br>';
    echo '<label for="' . $this->get_field_name('rssphoto_sel_mostrecent') . '"><input ' . (($sel=='Most Recent') ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_sel_mostrecent') . '" name="' . $this->get_field_name('rssphoto_sel') . '" value="Most Recent">' . __('Most Recent') . '</label><br>';
    echo '</div><div style="clear:both;">&nbsp;</div>';
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
