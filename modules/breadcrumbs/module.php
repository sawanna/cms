<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$breadcrumbs_module=array(
'name' => 'breadcrumbs',
'title' => '[str]Breadcrumbs[/str]',
'description' => '[str]Displays the chain of parent links according to URL address[/str]',
'root' => 'breadcrumbs',
'enabled' => 1,
'package' => 'Navigation',
'depend' => 'content, menu',
'widgets' => array('breadcrumbs_widget'),
'version' => '3.2-1'
);

#################################################################################
# Widgets definition
#################################################################################

$breadcrumbs_widget=array(
'name' => 'breadcrumbs',
'title' => '[str]Breadcrumbs[/str]',
'description' => '[str]Displays the chain of parent links according to URL address[/str]',
'class' => 'CBreadcrumbs',
'handler' => 'breadcrumbs',
'argument' => '%query',
'path' => "*",
'component' => '',
'permission' => '',
'weight' => -1,
'cacheable' => 1,
'enabled' => 1,
'fix' => 0
);
