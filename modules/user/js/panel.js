$(document).ready(function(){
    user_panel_init();
});


function user_panel_init()
{
	$('.user_login_panel').find('.hide').slideUp();
	$('#user_login_panel_show').find('a').css('display','block');
    
    $('#user_login_panel_show').find('a').unbind('click');
    
    $('#user_login_panel_show').find('a').click(function(){
        $('.user_login_panel').find('.hide').slideDown();
        $(this).css('display','none');
    });
}
