$(document).ready(function(){
	// deprecated
	//$('body').append('<div id="gallery-loader"></div><div id="gallery-preloader"></div><div id="gallery-zoomer"><div id="gallery-zoom-links"><div id="gallery-zoom-dlink"></div><div id="gallery-zoom-plink"></div></div><div id="gallery-zoom-title"></div><div id="gallery-zoom-prev"></div><div id="gallery-zoom-next"></div><div id="gallery-html-layer"></div><img id="gallery-zoom-image" /><div id="gallery-zoom-description"></div></div>');
    
	gallery_init();
});

function gallery_init(){
	
	if (!$('#gallery-loader').length) return;
    
    fix_gallery_align();
    
    window.onresize=fix_gallery_align;
    
    var delta=0;
    if ($.browser.msie) { delta=25; }
    
    $('#gallery-loader').css({'display':'none','position':'absolute','z-index':99998,'background-color':'#000000',opacity:.4,'left':0,'top':0,'width':$(document).width()-delta,'height':$(document).height()});
    $('#gallery-preloader').css({'display':'none','position':'absolute','z-index':999999,'left':0,'top':0});
    $('#gallery-zoomer').css({'display':'none','position':'absolute','z-index':99999,'left':0,'top':0});
    $('#gallery-zoom-image').css({'cursor':'pointer'});
    
    $('#gallery-zoom-image').get(0).onload=function()
    {
        gallery_show_image();
    }
    
    $('#gallery-zoom-image').click(function(){
        gallery_hide_image();
        gallery_hide_loader();
    });
    
    $('#gallery-loader').click(function(){
        gallery_hide_image();
        gallery_hide_loader();
    });
    
    $('span.node-image-zoomer').each(function(){
        var image=$('img[rel='+$(this).attr('rel')+']');
        if ($(image).length)
        {
            $(this).appendTo('body');
            $(this).css({'top':$(image).offset().top+$(image).height()-21,'left':$(image).offset().left+$(image).width()-21});
        }
    });
	
    gallery_init_thumbs();
	
	gallery_current_image_id=null;
}

function fix_gallery_align()
{
    $('.gallery-node-block').find('.gallery').each(function(){
        var node=$('#body-component').width();
        var image=$(this).find('img.gallery-thumb').eq(0).width();
        var item=image+14;
        var co=Math.floor(node/item);
        
        $(this).css({'width':co*item,'margin-left':'auto','margin-right':'auto'});
    });
}

function gallery_init_thumbs()
{
    $('a.gallery-thumb-link').click(function(e){
		e.preventDefault();
        gallery_thumb_click($(this).children('img'));
    });
}

function gallery_fix_links() {
	$('#gallery-zoom-prev').css('display','none');
	$('#gallery-zoom-next').css('display','none');
			
	if (gallery_current_image_id) {
		var g=$('#'+gallery_current_image_id).parents('.gallery');
		if (g && g.find('.gallery-thumb') && g.find('.gallery-thumb').length>1) {
			$('#gallery-zoom-prev').click(gallery_find_prev);
			$('#gallery-zoom-next').click(gallery_find_next);
			$('#gallery-zoom-prev').css('display','block');
			$('#gallery-zoom-next').css('display','block');
		}
	}
}

function gallery_zoom_next(selector)
{
    var elem=$(selector).next('img');
    gallery_thumb_click(elem);
}

function gallery_thumb_click(elem) {
	var im=$(elem).attr('rel');
	var psrc=im;
	if (im.length)
	{
		$('#gallery-zoom-image').get(0).src=im;
		$('#gallery-zoom-title').html($(elem).attr('alt'));
		$('#gallery-zoom-description').html($(elem).attr('title').replace("\n",'<br />'));
		var dlink=$(elem).parent('a').attr('href');
		if (dlink.length) {
			$('#gallery-zoom-dlink').html('<a href="'+dlink+'" title="'+gallery_dlink_str+'"></a>');
			psrc=dlink;
		} else {
			$('#gallery-zoom-dlink').html('');
		}
		$('#gallery-zoom-plink').html('<a href="javascript:void(0)" onclick="gallery_print_image(\''+psrc+'\')" title="'+gallery_plink_str+'"></a>');
		gallery_show_loader();
		gallery_current_image_id=$(elem).attr('id');
		gallery_fix_links();
	}
}

function gallery_find_prev() {
	if (gallery_current_image_id) {
		var prev=$('#'+gallery_current_image_id).parent('a').parent('div').prev('.gallery-item');
		var found_image=null;
		if (prev.length) {
			found_image=prev.children('a').children('img');
		} else {
			found_image=$('.gallery-thumb:last');
		}
		if (found_image) {
			gallery_show_loader();	
			gallery_thumb_click(found_image);
		}
	}
}

function gallery_find_next() {
	if (gallery_current_image_id) {
		var next=$('#'+gallery_current_image_id).parent('a').parent('div').next('.gallery-item');
		var found_image=null;
		if (next.length) {
			found_image=next.children('a').children('img');
		} else {
			found_image=$('.gallery-thumb:first');
		}
		if (found_image) {
			gallery_show_loader();
			gallery_thumb_click(found_image);
		}
	}
}

function gallery_show_loader()
{
    $('#gallery-loader').fadeIn();
    $('#gallery-preloader').css({'left':($(window).width()-$('#gallery-preloader').width())/2+$(window).scrollLeft(),'top':($(window).height()-$('#gallery-preloader').height())/2+$(window).scrollTop(),'display':'block'});
}

function gallery_hide_loader()
{
    $('#gallery-loader').fadeOut();
}

function gallery_show_image()
{
    $('#gallery-preloader').css('display','none');
    $('#gallery-zoomer').css({'left':($(window).width()-$('#gallery-zoomer').width())/2+$(window).scrollLeft(),'top':($(window).height()-$('#gallery-zoomer').height())/2+$(window).scrollTop()})
	$('#gallery-zoom-prev').css('top',($('#gallery-zoomer').height()-$('#gallery-zoom-prev').height())/2);
	$('#gallery-zoom-next').css('top',($('#gallery-zoomer').height()-$('#gallery-zoom-next').height())/2);
	$('#gallery-zoomer').fadeIn(400,gallery_fix_zoomer_pos);
}

function gallery_fix_zoomer_pos() {
	if ($('#gallery-zoomer').offset().left<0) {
		$('#gallery-zoomer').css('left',0);	
	}
	if ($('#gallery-zoomer').offset().top<0) {
		$('#gallery-zoomer').css('top',0);	
	}
}

function gallery_hide_image()
{
    $('#gallery-zoomer').fadeOut(200,function() {$('#gallery-zoom-image').get(0).src=''});
    $('#gallery-preloader').css('display','none');
}

function gallery_load(gal,start)
{
    $('.ajax-pager').animate({opacity:.5},500);
    try
    {
        $.post(site+'/'+'gallery-ajax-load',{gallery:gal,offset:start},function(data){$('#gallery-'+gal).html(data); $('.ajax-pager').animate({opacity:1},200); $('#gallery-'+gal).css('display','none'); $('#gallery-'+gal).fadeIn(500); gallery_init_thumbs();});
    } catch (e) {}
}

function gallery_print_image(img_src) {
	if (img_src.length) {
		var w = window.open("about:blank", "_new","width=400,height=400,location=no,scrollbars=yes,menubar=no");
		w.document.open();
		w.document.write('<html><head></head><body style="margin:0"><img style="display:block;margin:0 auto" src="'+img_src+'" /></body></html>');
		w.document.close();
		w.onload=function() {
			w.print();
		}
	}
}
