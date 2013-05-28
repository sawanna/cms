<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$jcloud_module=array(
'name' => 'jcloud',
'title' => '[str]Tags cloud[/str]',
'description' => '[str]Displays widget with tags cloud[/str]',
'root' => 'jcloud',
'enabled' => 1,
'depend' => 'content, search',
'package' => 'Navigation',
'widgets' => array('jcloud_widget'),
'admin-area-class' => 'CJcloudAdmin',
'version' => '3.2-1'
);

#################################################################################
# Widgets definition
#################################################################################

$jcloud_widget=array(
'name' => 'jcloud',
'title' => '[str]Tags cloud[/str]',
'description' => '[str]Displays widget with tags cloud[/str]',
'class' => 'CJcloud',
'handler' => 'jcloud',
'path' => "*",
'component' => 'left',
'permission' => '',
'weight' => 1,
'cacheable' => 1,
'enabled' => 1,
'fix' => 0
);
