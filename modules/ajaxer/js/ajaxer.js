$(document).ready(function(){
	$('#ajaxerLoader').prependTo('body');
	$('#ajaxerBG').prependTo('body');
	
	$(window).scroll(ajaxer_fix_loader);
	
	ajaxer_init();
	
	$('html').css('visibility','visible');
	ajaxer_hide_loader();
	
	$(document).ajaxError(ajaxer_error);
	
	try {
		$(window).bind('popstate',function(){
			ajaxer_load(base+window.location.href.replace(site,''));
		});
	} catch(e) {}
});


function ajaxer_show_loader()
{
	ajaxer_fix_loader();
	$('#ajaxerLoader').css('display','block');
	$('#ajaxerBG').css({'display':'block','opacity':0});
	
	$('#ajaxerBG').animate({'opacity':.5},400);
}

function ajaxer_hide_loader()
{
	$('#ajaxerLoader').css('display','none');
	
	$('#ajaxerBG').animate({'opacity':0},400,function(){ $('#ajaxerBG').css('display','none'); });
}

function ajaxer_fix_loader()
{
	var delta=0;
	
	if ($.browser.msie)
	{
		delta=25;
	}
	
	$('#ajaxerBG').css({
		'position':'absolute',
		'left':0,
		'top':0,
		'width':$(document).width()-delta,
		'height':$(document).height()
	});
	
	$('#ajaxerLoader').css({
		'position':'absolute',
		'left':($(window).width()-$('#ajaxerLoader').width())/2+$(window).scrollLeft(),
		'top':($(window).height()-$('#ajaxerLoader').height())/2+$(window).scrollTop()
	});
}

function ajaxer_init()
{
	ajaxer_fix_loader();

	$('a').each(function(){
		var href=new String($(this).attr('href'));

		if (typeof(ajaxerExclude) != "undefined")
		{
			for(var ex in ajaxerExclude)
			{
				if (href.indexOf(ajaxerExclude[ex])==0)
				{
					return true;
				}
			}
		}
		
		if (href.substr(0,4)=='http') {
			return true;
		}
		
		/*
		if (href.substr(0,1)=='/')
		{
			href=ajaxer_host+href;
		}
		
		if (href.indexOf(site)==0)
		*/
		if (href.substr(0,1)=='/')
		{
			$(this).unbind('click');
			
			$(this).click(function(){
				$('a').removeClass('active').parent('li').removeClass('active');
				$(this).addClass('active').parent('li').addClass('active');
				
				if ($(this).hasClass('noajaxer'))
				{
					return true;
				}
				
				return ajaxer_load(href);
			});
		}
	});
	
	var location=window.location.hash;
	if (location.substr(0,1)=='#') {
		location=location.substr(1);
	}
	var r=/^location=(.+)$/;
	var m=r.exec(location);
	if (m && m.length && (typeof(ajaxer_load.page)=="undefined" || ajaxer_load.page!=m[1])) {
		ajaxer_load(m[1]);
	}
}

function ajaxer_disable()
{
	$('a').each(function(){
		var href=new String($(this).attr('href'));
		
		if (href.substr(0,1)=='/')
		{
			href=ajaxer_host+href;
		}

		if (typeof(ajaxerExclude) != "undefined")
		{
			for(var ex in ajaxerExclude)
			{
				if (href.indexOf(ajaxerExclude[ex])==0)
				{
					return true;
				}
			}
		}
		
		if (href.indexOf(site)==0)
		{
			$(this).unbind('click');
		}
	});
}

function ajaxer_load(page)
{
	//if (typeof(ajaxerComponents) == "undefined" || page.indexOf(site) != 0)
	if (typeof(ajaxerComponents) == "undefined" || page.substr(0,1) != '/')
	{
		return true;
	}
	
	ajaxer_load.page=page;

	$.post(ajaxer_path,{ajaxer_components:ajaxerComponents.join(),ajaxer_path:page},function(data){
		ajaxer_response(data);
	},'json');
	
	if (page.indexOf('#')!=-1) {
		page=page.substr(0,page.indexOf('#'));
	}
	
	try {
		history.pushState({},'',page);
	} catch(e) {
		window.location.hash='location='+page;
	}
	
	ajaxer_show_loader();
	
	return false;
}

function ajaxer_response(data)
{
	if (typeof data.redirect != "undefined")
	{
		ajaxer_load(data.redirect);
		return;
	}
	
	try { 
		for(var c in ajaxerComponents)
		{
			ajaxer_update_data(ajaxerComponents[c],data[ajaxerComponents[c]]);
		}
	} catch(e) 
	{ 
		ajaxer_error();
		return;
	}	
		
	ajaxer_init();
	
	try {
		ajaxer_init_forms();
	} catch(e) {}

	try {
		if(data.script.length > 0) {
			eval(data.script);
		}
	
	} catch(e) {}
	
	try {
		if (data.css.length >0)
		{
			$('body').append('<style>'+data.css+'</style>');
		}
	} catch (e) {}
	
	$(window).scrollTop(0);
	ajaxer_hide_loader();
}

function ajaxer_update_data(component,data)
{
	$('#'+component+'-component').html(data);
}

function ajaxer_error()
{
	//alert(ajaxer_error_msg); 
	ajaxer_disable();
	
	try {
		ajaxer_disable_forms();
	} catch(e) {}
	
	ajaxer_hide_loader();
}

$('html').css('visibility','hidden');
