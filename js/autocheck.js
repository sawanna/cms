$(document).ready(function() {
    $(document).find('form').each(function(){
        $(this).get(0).onsubmit=function()
        {
             try {
                 clearTimeout(autocheck_timer);
             } catch(e) {}
        }
    });
});

function autocheck(elem,url) {
     $('#'+elem+'_autocheck_loader').appendTo('body');
     $('#'+elem+'_autocheck_loader').css({'position':'absolute','display':'none'});
     
     $('#'+elem).get(0).onkeypress=function(){
        try_autocheck(elem,url);
     }
     $('#'+elem).get(0).onblur=function(){
        try_autocheck(elem,url);
     }
}

function fix_autocheck_loader_position(elem)
{
    $('#'+elem+'_autocheck_loader').css({'left':$('#'+elem).offset().left+$('#'+elem).width()-$('#'+elem+'_autocheck_loader').width()+1+'px'});
    $('#'+elem+'_autocheck_loader').css({'top':$('#'+elem).offset().top+2+'px'});
    $('#'+elem+'_autocheck_loader').css({'height':$('#'+elem).height()+'px'});
}

function try_autocheck(elem,url)
{
     try {
         clearTimeout(autocheck_timer);
     } catch(e) {}
     autocheck_timer=window.setTimeout("do_autocheck('"+elem+"','"+url+"')",800);
}

function do_autocheck(elem,url)
{
    if ($('#'+elem).get(0).value.length>0)
    {
        fix_autocheck_loader_position(elem);
        
        $.post(url,{q:$('#'+elem).get(0).value},function(data){
             $('#'+elem+'_autocheck_loader').css('display','none');
             if (data.length){
                $('#'+elem).removeClass('checked');
                $('#'+elem).addClass('failed');
                 if (document.getElementById(elem+'_description')) {
                    document.getElementById(elem+'_description').innerHTML='<span class=\"autocheck_error\">'+data+'</span>';
                 }
             }else{
                 $('#'+elem).removeClass('failed');
                 $('#'+elem).addClass('checked');
                 if (document.getElementById(elem+'_description')) {
                    document.getElementById(elem+'_description').innerHTML='<span class=\"autocheck_checked\">&nbsp;&radic;&nbsp;ok</span>';
                 }
         };});
         $('#'+elem+'_autocheck_loader').css('display','block');
    }
 }