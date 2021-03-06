<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>[element]head[/element]</head>
<body class="left">
[element]prepend_body[/element]
<div class="clear"></div>
<div class="header">
    <div class="logo">[element]logo[/element]</div>
    <div class="sitename">
        [element]sitename[/element]
        <div class="slogan">[element]slogan[/element]</div>
    </div>
    <div class="languages-panel">[element]language_switcher[/element]</div>
    [component]header[/component]
</div>
<div class="clear"></div>
<div class="shadow"></div>
<div class="parent-menu-block">
    [component]parent-menu[/component]
</div>
<div class="clear"></div>
<div class="leftbar">
    [component]child-menu[/component]
    [component]left[/component]
    [component]left-additional[/component]
</div>
<div class="body">
    [component]body[/component]
    <div class="clear"></div>
    <div class="footer">
        [component]footer[/component]
        <div class="promo">Powered by <a href="http://sawanna.org" target="_blank">Sawanna CMS</a></div>
        <div class="version_switcher">[element]mobile_version_switcher[/element]</div>
    </div>
</div>
[element]append_body[/element]
</body>
</html>
