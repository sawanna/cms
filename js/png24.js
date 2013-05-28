$(document).ready(function() {
    if (typeof(ActiveXObject)!='undefined') {
        $('img').each(function(){
            var regex=/.+\.png$/;
            var m = regex.exec($(this).get(0).src);
            
            if (m!=null)
            {
                fixPNG24($(this));
            }
        });
        
        fixPNG24($('div.logo > a > img'));
    }
});

function fixPNG24(elem)
{
    var png24src=$(elem).get(0).src;
    
    if (png24src == site+"/misc/blank.gif")
    {
        return;
    }
    
    $(elem).css({'width':$(elem).width(),'height':$(elem).height()});
    
    $(elem).get(0).src=site+"/misc/blank.gif";
    
    $(elem).get(0).runtimeStyle.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='"+png24src+"',sizingMethod='scale')";
}
