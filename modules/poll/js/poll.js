$(document).ready(function(){
    poll_init();
});

function poll_init()
{
	if (typeof(poll_ajax_url)=='undefined')
    {
        return true;
    }
    
    $('.poll > .pform').each(function(){
        $(this).find('form').get(0).onsubmit=function()
        {
            var pid=$(this).find('input[name=poll-pid]').val();

            var elems=new Array();
            
            $(this).find('input[type=checkbox]').each(function(){
                if ($(this).get(0).checked)
                {
                    elems[$(this).attr('name')]='on';
                }
            });
            
            $(this).find('input[type=radio]').each(function(){
                if ($(this).get(0).checked)
                {
                    elems[$(this).attr('name')]=$(this).val();
                }
            });
            
            var query='';
            var co=0;
            
            for (var elem in elems)
            {
                if (co>0)
                {
                    query+=',';
                }
                
                query+=elem+':'+elems[elem];
                
                co++;
            }
            
            if (query.length==0)
            {
                return false;
            }
            
            poll_process($(this),pid,query);
            
            return false;
        }
    });
}

function poll_process(form,pid,query)
{
    $(form).find('input').attr('disabled','disabled');
    
    poll_send(pid,query);
}

function poll_send(pid,query)
{
    if (typeof(poll_ajax_url)=='undefined')
    {
        return true;
    }
    
    $.post(poll_ajax_url,{poll_pid:pid,poll_results:query},function(data){
        $('.poll-'+data.poll_name).html(data.results);
    },'json');
}
