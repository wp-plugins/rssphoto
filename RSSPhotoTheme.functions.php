<?php 
add_action('init','init_theme_rssphoto');

function init_theme_rssphoto()
{
  wp_enqueue_script('jquery');
  wp_enqueue_script('rssphoto_javascript','wp-content/plugins/rssphoto/rssphoto.js');
  wp_enqueue_style('rssphoto_stylesheet','wp-content/plugins/rssphoto/rssphoto.css');
  if( !class_exists('RSSPhoto') )
    require_once('RSSPhoto.class.php');
}

function display_rssphoto($input=array())
{
  if(!empty($input['title']))      $settings['rssphoto_title']    =$input['title'];
  if(!empty($input['url']))        $settings['rssphoto_url']      =$input['url'];
  if(!empty($input['fixed']))      $settings['rssphoto_fixed']    =$input['fixed'];
  if(!empty($input['size']))       $settings['rssphoto_size']     =$input['size'];
  if(!empty($input['img_sel']))    $settings['rssphoto_img_sel']  =$input['img_sel'];
  if(!empty($input['num_img']))    $settings['rssphoto_num_img']  =$input['num_img'];
  if(!empty($input['item_sel']))   $settings['rssphoto_item_sel'] =$input['item_sel'];
  if(!empty($input['num_item']))   $settings['rssphoto_num_item'] =$input['num_item'];
  if(!empty($input['output']))     $settings['rssphoto_output']   =$input['output'];
  if(!empty($input['interval']))   $settings['rssphoto_interval'] =$input['interval'];

  if( class_exists('RSSPhoto') )
  {
    $rssphoto = new RSSPhoto($settings);
    $rssphoto->init();
    echo $input['before_html'];
    echo $input['before_title'];
    echo $rssphoto->title();
    echo $input['after_title'];
    echo $rssphoto->html();
    echo $input['after_html'];
  }
}
?>
