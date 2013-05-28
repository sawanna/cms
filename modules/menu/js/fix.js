$(document).ready(function(){
    $('ul.parent-menu-list').find('li').each(function(){
        menu_fix_element($(this));
    });
    
    $('ul.child-menu-list').find('li').each(function(){
        menu_fix_element($(this));
    });
});

function menu_fix_element(elem)
{
    $(elem).hover(
        function()
        {
            $(this).addClass('hover').children('a').addClass('hover').children('span').addClass('hover');
        },
        function()
        {
            $(this).removeClass('hover').children('a').removeClass('hover').children('span').removeClass('hover');
        }
    );
    
    $(elem).children('a').click(function(){
        $(this).parent('li').parent('ul').find('li').each(function(){
            $(this).removeClass('active').children('a').removeClass('active');
            $(this).removeClass('parent').children('a').removeClass('parent');
        });
        
        $(this).addClass('active').parent('li').addClass('active');
    });
}
