function slideSwitch(id) 
{
  var $active = jQuery(".rssphoto_slideshow#rssphoto-"+id+" div.active");
  if ( $active.length == 0 )
    $active = jQuery(".rssphoto_slideshow#rssphoto-"+id+" div:last");

  var $next = $active.next().length ? $active.next() : jQuery(".rssphoto_slideshow#rssphoto-"+id+" div:first");

  $active.addClass('last-active')
    .animate({opacity : 0.0}, 1000);

  $next.css({opacity: 0.0})
    .addClass('active')
    .animate({opacity: 1.0}, 1000, function() {
      $active.removeClass('active last-active');
    });
}

function expandStatic(id)
{
  jQuery(".rssphoto_static#rssphoto-"+id+" div").animate({opacity: 1.0}, 1000, function() {});
}
