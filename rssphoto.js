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
 * Setup necessary bindings for Slideshow 2
 */
function setupSlideshow2(id,interval)
{
  setInterval( "slideSwitch2("+id+")", interval );
  jQuery("div.rssphoto_slideshow2#rssphoto-"+id)
    .bind('mouseenter',{'id':id},titleOverlayMouseEnter)
    .bind('mouseleave',{'id':id},titleOverlayMouseLeave);
}

/*
 * Transition images for Slideshow 2
 */
function slideSwitch2(id) 
{
  var $active = jQuery(".rssphoto_slideshow2#rssphoto-"+id+" div.active");
  if ( $active.length == 0 )
    $active = jQuery(".rssphoto_slideshow2#rssphoto-"+id+" div:last");

  var $next = $active.next("div.item").length ? $active.next("div.item") : jQuery(".rssphoto_slideshow2#rssphoto-"+id+" div:first");

  $active.addClass('last-active')
    .animate({opacity : 0.0}, 1000);

  $next.css({opacity: 0.0})
    .addClass('active')
    .animate({opacity: 1.0}, 1000, function() {
      $active.removeClass('active last-active');
    });
}

/*
 * Mouseenter code for Slideshow2 and show title
 */
function titleOverlayMouseEnter(e)
{
  jQuery(".rssphoto_slideshow2#rssphoto-"+e.data.id+" div.title_overlay").fadeIn();
}

/*
 * Mouseleave code for Slideshow2 and show title
 */
function titleOverlayMouseLeave(e)
{
  jQuery(".rssphoto_slideshow2#rssphoto-"+e.data.id+" div.title_overlay").fadeOut();
}

/*
 * Transition images for Slideshow 1
 */
function slideSwitch(id) 
{
  var $active = jQuery(".rssphoto_slideshow#rssphoto-"+id+" div.active");
  if ( $active.length == 0 )
    $active = jQuery(".rssphoto_slideshow#rssphoto-"+id+" div:last");

  var $next = $active.next("div.item").length ? $active.next("div.item") : jQuery(".rssphoto_slideshow#rssphoto-"+id+" div:first");

  $active.addClass('last-active')
    .animate({opacity : 0.0}, 1000);

  $next.css({opacity: 0.0})
    .addClass('active')
    .animate({opacity: 1.0}, 1000, function() {
      $active.removeClass('active last-active');
    });
}

/*
 * Show images for static display
 */
function expandStatic(id)
{
  jQuery(".rssphoto_static#rssphoto-"+id+" div").animate({opacity: 1.0}, 1000, function() {});
}
