<?php

class RSSPhotoShortcode 
{
  var $rssphoto;

  function RSSPhotoShortcode() 
  {
    if ( !function_exists('add_shortcode') ) return;
    add_shortcode('rssphoto',array($this, 'shortcode_handler'));
  }

  function shortcode_handler($atts=array(), $content=NULL) 
  {
    $this->setup($atts);
    echo $this->rssphoto->html();
  }

  function setup($atts)
  {
    /* user-defined options */
    extract( shortcode_atts( array(
      'url' => 'http://photography.spencerkellis.net/rss.php',
      'fixed' => 'Height',
      'size' => 120,
      'img_sel' => 'Random',
      'num_img' => 1,
      'item_sel' => 'Random',
      'num_item' => 10,
      'show_title' => false,
      'output' => 'Slideshow'
    ), $atts ) );

    $settings['rssphoto_url']        = $url;
    $settings['rssphoto_fixed']      = $fixed;
    $settings['rssphoto_size']       = $size;
    $settings['rssphoto_img_sel']    = $img_sel;
    $settings['rssphoto_num_img']    = $num_img;
    $settings['rssphoto_item_sel']   = $item_sel;
    $settings['rssphoto_num_item']   = $num_item;
    $settings['rssphoto_show_title'] = $show_title;
    $settings['rssphoto_output']     = $output;

    $this->rssphoto = new RSSPhoto($settings);
    $this->rssphoto->init();
  }
}
