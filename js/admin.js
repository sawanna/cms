jQuery.fn.outerHTML = function() {
    return jQuery("<p>").append(this.clone()).html();
}

$(document).ready(function(){
    if ($('#notification').html().length>0)
    {
        $('#notification').fadeIn(1000,function(){window.setTimeout("$('#notification').fadeOut(1000)",10000)});
    }
    
    if (typeof admin_low_theme == 'undefined' || !admin_low_theme)
    {
        $('.button, input[type=submit], input[type=button]').each(function(){
            $(this).addClass('ui-state-default ui-corner-all');
            $(this).hover(
                function(){
                    $(this).addClass('ui-state-hover')
                },
                function(){
                    $(this).removeClass('ui-state-hover')
                }
            );
        });
        
        $('#admin-area').css({'display':'block','visibility':'hidden'});
        window.setTimeout("$('#admin-area').css('visibility','visible')",200);
        
        $('#adminbar').show('slide',800);
        
        window.setTimeout("convert_select($('select'))",100);
        window.setTimeout("convert_slider($('select.slider'))",800);
    } else
    {
        $('#admin-area').css({'display':'block'});
        $('#adminbar').css({'display':'block'});
    }
});

function convert_select(elem)
{
    $(elem).each(function(){
        $(this).selectmenu({maxHeight: 300});
    });
}

function refresh_select(elem)
{
    var select = $(elem).outerHTML();
    var index=$(elem).get(0).selectedIndex;
    
    $(elem).selectmenu('destroy');
    
    $(elem).selectmenu({maxHeight: 300,selectedIndex:index});
}

function convert_slider(elem)
{
    $(elem).each(function(){
        $(this).selectToUISlider({labels:0}); 
        $(this).css('display','none');
        $('#'+$(this).attr('name')+'-button').css('display','none');
    });
}
