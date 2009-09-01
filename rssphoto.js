function slideSwitch() 
{
  var $active = jQuery('#rssphoto_slideshow div.active');
  if ( $active.length == 0 )
    $active = jQuery('#rssphoto_slideshow div:last');

  var $next = $active.next().length ? $active.next() : jQuery('#rssphoto_slideshow div:first');

  $active.addClass('last-active')
    .animate({opacity : 0.0}, 1000);

  $next.css({opacity: 0.0})
    .addClass('active')
    .animate({opacity: 1.0}, 1000, function() {
      $active.removeClass('active last-active');
    });
}

function expandStatic()
{
  jQuery('#rssphoto_static div').animate({opacity: 1.0}, 1000, function() {});
}

if(typeof jQuery != 'undefined')
{
  jQuery(function() 
  {
    if(jQuery('#rssphoto_slideshow').length > 0)
    {
      setInterval( "slideSwitch()", 6000 );
    }
    else if(jQuery('#rssphoto_static').length > 0)
    {
      expandStatic();
    }
  });
}
