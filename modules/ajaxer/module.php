<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$ajaxer_module=array(
'name' => 'ajaxer',
'title' => '[str]AJAXer[/str]',
'description' => '[str]Access nodes without page reloading[/str]',
'root' => 'ajaxer',
'enabled' => 1,
'package' => 'Navigation',
'widgets' => array('ajaxer_widget'),
'paths' => array('ajaxer_path'),
'options' => array('ajaxer_components'=>array('child-menu','body')),
'admin-area-class' => 'CAjaxerAdmin',
'version' => '3.4-2'
);

#################################################################################
# Widgets definition
#################################################################################

$ajaxer_widget=array(
'name' => 'ajaxerLoader',
'title' => '[str]AJAXer loader[/str]',
'description' => '[str]Loads AJAXer scripts[/str]',
'class' => 'CAjaxer',
'handler' => 'load',
'path' => "[home]",
'component' => '',
'permission' => '',
'weight' => 0,
'cacheable' => 0,
'enabled' => 1,
'fix' => 0
);

#################################################################################
# URL paths definition
#################################################################################

$ajaxer_path=array(
'path' => 'ajaxer',
'permission' => '',
'enabled' => 1,
'render' => 0 
);
