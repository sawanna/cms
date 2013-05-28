$(document).ready(function(){
    dropdownmenuoffsetX=0;
    dropdownmenuoffsetY=0;
    
    if ($('.drop-down-menu').css('position')=='absolute')
    {
        dropdownmenuoffsetX=$('.drop-down-menu-list').offset().left;
        dropdownmenuoffsetY=$('.drop-down-menu-list').offset().top;
    }
        
    $('.drop-down-menu-list').find('li').each(function()
    {
        $(this).hover(
            function ()
            {
                if (!$(this).parent('ul').parent('li').length)
                {
                    $(this).children('ul').eq(0).css({'left':$(this).offset().left-dropdownmenuoffsetX});
                    $(this).children('ul').eq(0).css({'top':$(this).offset().top+$(this).height()-dropdownmenuoffsetY});
                }
                
                $(this).addClass('hover');
                $(this).children('a').addClass('hover');
                $(this).children('a').children('span').addClass('hover');
                $(this).children('ul').eq(0).stop(true,true).fadeIn();
            },
            function () 
            {
                $(this).removeClass('hover');
                $(this).children('a').removeClass('hover');
                $(this).children('a').children('span').removeClass('hover');
                $(this).find('ul').fadeOut();
            }
        );
    });
});
