/**
 * Image Zoomer
 * (C) 2011 Sawanna Team (http://sawanna.org) 
 */

teaser_thumbnail_zoomer_width=250;
teaser_thumbnail_zoomer_height=180;
teaser_thumbnail_zoomer_locked=false;

$(document).ready(function(){
    $('body').append('<div id="teaser-thumbnail-zoomer"><img id="teaser-thumbnail-zoomer-image" /></div>');
    
    $('#teaser-thumbnail-zoomer').css({'display':'none','position':'absolute','z-index':99999,'left':0,'top':0,'width':'auto','height':'auto'});
    $('#teaser-thumbnail-zoomer-image').css({'cursor':'pointer'});
    
    $('.teaser-item').children('.teaser-body').children('.teaser-thumbnail').children('img').hover(
        function(){
            if (teaser_thumbnail_zoomer_locked)
            {
                return;
            }
            $('#teaser-thumbnail-zoomer-image').get(0).src=$(this).get(0).src;
            teaser_show_image($(this));
        },
        function(){
            teaser_thumbnail_zoomer_locked=false;
        }
    );
    
    $('.teaser-item').children('.teaser-body').children('.teaser-thumbnail').children('img').click(
        function(){
            $('#teaser-thumbnail-zoomer-image').get(0).src=$(this).get(0).src;
            teaser_show_image($(this));
        }
    );
    
    $('#teaser-thumbnail-zoomer-image').click(
        function(){
            teaser_thumbnail_zoomer_locked=true;
            teaser_hide_image();
        }
    )
    $('#teaser-thumbnail-zoomer-image').mouseout(
        function(){
            if ($('#teaser-thumbnail-zoomer').css('display')!='none')
            {
                teaser_thumbnail_zoomer_locked=false;
            }
        }
    );
});


function teaser_show_image(elem)
{
    teaser_thumbnail_zoomer_last_elem=$(elem);
    
    var width=teaser_thumbnail_zoomer_width;
    var height=teaser_thumbnail_zoomer_height;
    
    if (width<=$(elem).width() && height<=$(elem).height())
    {
        return;
    }

    var offsetX=$(elem).offset().left-Math.floor((width-$(elem).width())/2);
    var offsetY=$(elem).offset().top-Math.floor((height-$(elem).height())/2);
    
    if (offsetX<$(window).scrollLeft()) { offsetX=$(window).scrollLeft();}
    if (offsetY<$(window).scrollTop()) { offsetY=$(window).scrollTop();}
    if (offsetX>$(window).width()+$(window).scrollLeft()-width) { offsetX=$(window).width()+$(window).scrollLeft()-width;}
    if (offsetY>$(window).height()+$(window).scrollTop()-height) { offsetY=$(window).height()+$(window).scrollTop()-height;}

    if ($('#teaser-thumbnail-zoomer').css('display')=='none')
    {
        $('#teaser-thumbnail-zoomer-image').css({'width':$(elem).width(),'height':$(elem).height()});
        $('#teaser-thumbnail-zoomer').css({'left':$(elem).offset().left,'top':$(elem).offset().top});
        $('#teaser-thumbnail-zoomer').css({'display':'block'});
    }
    $('#teaser-thumbnail-zoomer').stop(true,true).animate({'left':offsetX,'top':offsetY},200);
    $('#teaser-thumbnail-zoomer-image').stop(true,true).animate({'width':width,'height':height},200);
}

function teaser_hide_image()
{
    if (typeof(teaser_thumbnail_zoomer_last_elem) == 'undefined')
    {
        return;
    }
    
    var elem=$(teaser_thumbnail_zoomer_last_elem);
    
    var width=$('#teaser-thumbnail-zoomer').width();
    var height=$('#teaser-thumbnail-zoomer').height();
    
    if (width<=$(elem).width() && height<=$(elem).height())
    {
        return;
    }

    $('#teaser-thumbnail-zoomer').stop(true,true).animate({'left':$(elem).offset().left,'top':$(elem).offset().top},200,function(){$('#teaser-thumbnail-zoomer').css({'display':'none'});});
    $('#teaser-thumbnail-zoomer-image').stop(true,true).animate({'width':$(elem).width(),'height':$(elem).height()},200);
}
