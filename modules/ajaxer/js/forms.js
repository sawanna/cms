$(document).ready(function(){
	ajaxer_init_forms();
});


function ajaxer_init_forms()
{
	$('form').each(function(){
		if ($(this).attr('class') != 'noajaxer')
		{
			$(this).unbind('submit');
			
			$(this).submit(function(){
				return ajaxer_load_form($(this));
			});
		}
	});
}

function ajaxer_disable_forms()
{
	$('form').each(function(){
		if ($(this).attr('class') != 'noajaxer')
		{
			$(this).unbind('submit');
		}
	});
}

function ajaxer_load_form(form)
{
	var page=$(form).attr('action');
	
	if (page.length == 0)
	{
		page=window.location.href;
	}
	
	if (page.substr(0,1)=='/')
	{
		page=site+page;
	}
	
	if (typeof(ajaxerComponents) == "undefined" || page.indexOf(site) != 0)
	{
		return true;
	}
	
	var elems='';
	
	var canceled=false;
	
	$(form).find('input').each(function(){
		
		if ($(this).attr('type')=='file')
		{
			canceled=true;
			return false;
		}
		
		if ($(this).attr('type')=='radio' || $(this).attr('type')=='checkbox')
		{
			if (!$(this).get(0).checked)
			{
				return true;
			}
		}
		
		if ($(this).attr('type')=='checkbox')
		{
			if ($(this).get(0).value.length==0)
			{
				$(this).get(0).value='on';
			}
		}
		
		elems+=$(this).attr('name')+'='+$(this).val()+';';
	});
	
	if (canceled)
	{
		return true;
	}
	
	$(form).find('textarea').each(function(){
		elems+=$(this).attr('name')+'='+$(this).val()+';';
	});
	$(form).find('select').each(function(){
		elems+=$(this).attr('name')+'='+$(this).val()+';';
	});

	$.post(ajaxer_path,{ajaxer_components:ajaxerComponents.join(),ajaxer_path:page,ajaxer_form_elements:elems},function(data){
		ajaxer_response(data);
	},'json');
	
	ajaxer_show_loader();
	
	return false;
}
