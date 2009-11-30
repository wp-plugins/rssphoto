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

class RSSPhotoWidget extends WP_Widget
{
  /****************************
   * Internal variables
   ****************************/
  var $error_msg      = "";
  var $widget_id      = -1;
  var $rssphoto;

  /****************************
   * Widget variables
   ****************************/
  var $before_widget  = "";
  var $after_widget   = "";
  var $before_title   = "";
  var $after_title    = "";
  
  /**
  * Declares the RSSPhotoWidget class.
  *
  */
  function RSSPhotoWidget()
  {
    $prefix = 'rssphoto';
    $name = __('RSSPhoto Widget');
    $widget_ops = array('classname' => 'widget_rssphoto', 'description' => __( "Display photos from an RSS or Atom feed") );
    $control_ops = array('width' => 500, 'height' => 300, 'id_base' => $prefix);

    $this->WP_Widget($prefix, $name, $widget_ops, $control_ops);
  }

  /**
  * Displays the Widget
  *
  */
  function widget($args, $instance)
  {
    $this->setup($args,$instance);

    echo $this->before_widget;

    if($this->rssphoto->ready())
    {
      if($this->rssphoto->show_title())
      {
        echo $this->before_title;
        echo apply_filters('widget_title', $this->rssphoto->title());
        echo $this->after_title;
      }
      echo $this->rssphoto->html();
    }
    else
    {
      echo $this->rssphoto->get_error();
    }

    echo $this->after_widget;
  }

  /**
  * Saves the widgets settings.
  *
  */
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['rssphoto_title']          = strip_tags(stripslashes($new_instance['rssphoto_title']));
    $instance['rssphoto_url']            = strip_tags(stripslashes($new_instance['rssphoto_url']));
    $instance['rssphoto_height']         = strip_tags(stripslashes($new_instance['rssphoto_height']));
    $instance['rssphoto_width']          = strip_tags(stripslashes($new_instance['rssphoto_width']));
    $instance['rssphoto_img_sel']        = strip_tags(stripslashes($new_instance['rssphoto_img_sel']));
    $instance['rssphoto_num_img']        = strip_tags(stripslashes($new_instance['rssphoto_num_img']));
    $instance['rssphoto_item_sel']       = strip_tags(stripslashes($new_instance['rssphoto_item_sel']));
    $instance['rssphoto_num_item']       = strip_tags(stripslashes($new_instance['rssphoto_num_item']));
    $instance['rssphoto_show_title']     = strip_tags(stripslashes($new_instance['rssphoto_show_title']));
    $instance['rssphoto_show_img_title'] = strip_tags(stripslashes($new_instance['rssphoto_show_img_title']));
    $instance['rssphoto_output']         = strip_tags(stripslashes($new_instance['rssphoto_output']));
    $instance['rssphoto_interval']       = strip_tags(stripslashes($new_instance['rssphoto_interval']));

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
                                                        'rssphoto_height'=>'150',
                                                        'rssphoto_width'=>120,
                                                        'rssphoto_img_sel'=>'Most Recent',
                                                        'rssphoto_num_img'=>1,
                                                        'rssphoto_item_sel'=>'Random',
                                                        'rssphoto_num_item'=>1,
                                                        'rssphoto_show_title'=>1,
                                                        'rssphoto_show_img_title'=>1,
                                                        'rssphoto_output'=>'Slideshow2',
                                                        'rssphoto_interval'=>6000));

    $title          = htmlspecialchars($instance['rssphoto_title']);
    $url            = htmlspecialchars($instance['rssphoto_url']);
    $height         = htmlspecialchars($instance['rssphoto_height']);
    $width          = htmlspecialchars($instance['rssphoto_width']);
    $img_sel        = htmlspecialchars($instance['rssphoto_img_sel']);
    $num_img        = htmlspecialchars($instance['rssphoto_num_img']);
    $item_sel       = htmlspecialchars($instance['rssphoto_item_sel']);
    $num_item       = htmlspecialchars($instance['rssphoto_num_item']);
    $show_title     = htmlspecialchars($instance['rssphoto_show_title']);
    $show_img_title = htmlspecialchars($instance['rssphoto_show_img_title']);
    $output         = htmlspecialchars($instance['rssphoto_output']);
    $interval       = htmlspecialchars($instance['rssphoto_interval']);

    //Display form

    echo '<h3>General Settings</h3>';

    // Title
    echo '<div style="text-align:right; float:left; width:130px;"><label for="' . $this->get_field_name('rssphoto_title') . '">' . __('Title:') . '</label></div>';
    echo '<div style="text-align:left; float:left; width:350px; padding-left:5px;">';
    echo '<input style="width: 350px;" id="' . $this->get_field_id('rssphoto_title') . '" name="' . $this->get_field_name('rssphoto_title') . '" type="text" value="' . $title . '" />';
    echo '</div>';

    // Show Title
    echo '<div style="clear:both;">&nbsp;</div>';
    echo '<div style="text-align:right; float:left; width:130px;">' . __('Show Title:') . '</div>';
    echo '<div style="text-align:left; float:left; width:110px; padding-left:5px;">';
    echo '<label for="' . $this->get_field_name('rssphoto_show_title_yes') . '"><input ' . (($show_title=='1') ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_show_title_yes') . '" name="' . $this->get_field_name('rssphoto_show_title') . '" value="1">' . __('Yes') . '</label><br>';
    echo '<label for="' . $this->get_field_name('rssphoto_show_title_no')  . '"><input ' . (($show_title=='0') ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_show_title_no')  . '" name="' . $this->get_field_name('rssphoto_show_title') . '" value="0">' . __('No')  . '</label><br>';
    echo '</div>';

    // Output
    echo '<div style="clear:both;">&nbsp;</div>';
    echo '<div style="text-align:right; float:left; width:130px;">' . __('Output:') . '</div>';
    echo '<div style="text-align:left; float:left; width:110px; padding-left:5px;">';
    echo '<label for="' . $this->get_field_name('rssphoto_output_slideshow2') . '"><input ' . (($output=='Slideshow2') ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_output_slideshow2') . '" name="' . $this->get_field_name('rssphoto_output') . '" value="Slideshow2">' . __('Slideshow v2') . '</label><br>';
    echo '<label for="' . $this->get_field_name('rssphoto_output_slideshow')  . '"><input ' . (($output=='Slideshow')  ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_output_slideshow')  . '" name="' . $this->get_field_name('rssphoto_output') . '" value="Slideshow">'  . __('Slideshow')    . '</label><br>';
    echo '<label for="' . $this->get_field_name('rssphoto_output_static')     . '"><input ' . (($output=='Static')     ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_output_static')     . '" name="' . $this->get_field_name('rssphoto_output') . '" value="Static">'     . __('Static')       . '</label><br>';
    echo '</div>';

    // Interval
    echo '<div style="clear:both;">&nbsp;</div>';
    echo '<div style="text-align:right; float:left; width:130px;"><label for="' . $this->get_field_name('rssphoto_interval') . '">' . __('Slideshow Time (ms):') . '</label></div>';
    echo '<div style="text-align:left; float:left; width:350px; padding-left:5px;">';
    echo '<input style="width: 350px;" id="' . $this->get_field_id('rssphoto_interval') . '" name="' . $this->get_field_name('rssphoto_interval') . '" type="text" value="' . $interval. '" />';
    echo '</div>';

    echo '<div style="clear:both;">&nbsp;</div>';
    echo '<p>Enter values for thumbnail width and height (in pixels), or enter \'variable\' in only one of the spaces to maintain the aspect ratio of the original image given the other dimension.</p>';

    /*--------------------------------------------------------------------*/

    echo '<div style="float:left; width:260px;">';

    // Height 
    echo '<div style="text-align:right; float:left; width:130px;"><label for="' . $this->get_field_name('rssphoto_height') . '">' . __('Height (px)') . '</label></div>';
    echo '<div style="text-align:left; float:left; width:110px; padding-left:5px;">';
    echo '<input style="width:100px;" id="' . $this->get_field_id('rssphoto_height') . '" name="' . $this->get_field_name('rssphoto_height') . '" type="text" value="' . $height . '" />';
    echo '</div>';

    echo '</div>';
    echo '<div style="float:left; width:240px;">';

    // Width 
    echo '<div style="text-align:right; float:left; width:130px;"><label for="' . $this->get_field_name('rssphoto_width') . '">' . __('Width (px)') . '</label></div>';
    echo '<div style="text-align:left; float:left; width:70px; padding-left:5px;">';
    echo '<input style="width:100px;" id="' . $this->get_field_id('rssphoto_width') . '" name="' . $this->get_field_name('rssphoto_width') . '" type="text" value="' . $width . '" />';
    echo '</div>';

    echo '</div>';

    /*--------------------------------------------------------------------*/

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

    // Image Selection
    echo '<div style="text-align:right; float:left; width:130px;">' . __('Image Selection:') . '</div>';
    echo '<div style="text-align:left; float:left; width:110px; padding-left:5px;">';
    echo '<label for="' . $this->get_field_name('rssphoto_img_sel_random') . '"><input ' . (($img_sel=='Random') ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_img_sel_random') . '" name="' . $this->get_field_name('rssphoto_img_sel') . '" value="Random">' . __('Random') . '</label><br>';
    echo '<label for="' . $this->get_field_name('rssphoto_img_sel_mostrecent')  . '"><input ' . (($img_sel=='Most Recent')  ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_img_sel_mostrecent')  . '" name="' . $this->get_field_name('rssphoto_img_sel') . '" value="Most Recent">'  . __('Most Recent')  . '</label><br>';
    echo '</div>';

    echo '</div>';
    echo '<div style="float:left; width:240px;">';

    // Number Images
    echo '<div style="text-align:right; float:left; width:130px;"><label for="' . $this->get_field_name('rssphoto_num_img')   . '">' . __('# Images per Item:')   . '</label></div>';
    echo '<div style="text-align:left; float:left; width:70px; padding-left:5px;">';
    echo '<input style="width: 50px;" id="' . $this->get_field_id('rssphoto_num_img')   . '" name="' . $this->get_field_name('rssphoto_num_img')   . '" type="text" value="' . $num_img . '" />';
    echo '</div>';

    echo '</div>';

    /*--------------------------------------------------------------------*/

    // Show Image Title
    echo '<div style="clear:both;">&nbsp;</div>';
    echo '<div style="text-align:right; float:left; width:130px;">' . __('Show Image Titles:') . '</div>';
    echo '<div style="text-align:left; float:left; width:110px; padding-left:5px;">';
    echo '<label for="' . $this->get_field_name('rssphoto_show_img_title_yes') . '"><input ' . (($show_img_title=='1') ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_show_img_title_yes') . '" name="' . $this->get_field_name('rssphoto_show_img_title') . '" value="1">' . __('Yes') . '</label><br>';
    echo '<label for="' . $this->get_field_name('rssphoto_show_img_title_no')  . '"><input ' . (($show_img_title=='0') ? 'checked' : '') . ' type="radio" id="' . $this->get_field_id('rssphoto_show_img_title_no')  . '" name="' . $this->get_field_name('rssphoto_show_img_title') . '" value="0">' . __('No')  . '</label><br>';
    echo '</div>';

    // Bottom Spacing
    echo '<div style="clear:both;">&nbsp;</div>';
  }

  function setup($args,$instance)
  {
    extract($args);
    $this->before_widget = $before_widget;
    $this->after_widget  = $after_widget;
    $this->before_title  = $before_title;
    $this->after_title   = $after_title;

    $this->widget_id = rand();

    $this->rssphoto = new RSSPhoto($instance);
    $this->rssphoto->init();
  }

}// END class
?>
