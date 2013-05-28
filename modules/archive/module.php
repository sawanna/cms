<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$archive_module=array(
'name' => 'archive',
'title' => '[str]Archive[/str]',
'description' => '[str]Displays nodes by publish date[/str]',
'root' => 'archive',
'enabled' => 1,
'package' => 'Navigation',
'depend' => 'content',
'widgets' => array('archive_widget','archive_panel_widget'),
'paths' => array('archive_path','archive_date_path','archive_offset_path','archive_check_path'),
'admin-area-class' => 'CArchiveAdmin',
'version' => '3.2-1'
);

#################################################################################
# Widgets definition
#################################################################################

$archive_widget=array(
'name' => 'archive',
'title' => 'Archive',
'description' => 'Archive output',
'class' => 'CArchive',
'handler' => 'archive',
'argument' => '%arg',
'path' => "archive",
'component' => '',
'permission' => '',
'weight' => 0,
'cacheable' => 0,
'enabled' => 1,
'fix' => 1
);

$archive_panel_widget=array(
'name' => 'archiveCalendar',
'title' => '[str]Archive calendar[/str]',
'description' => '[str]Displays archive calendar[/str]',
'class' => 'CArchive',
'handler' => 'calendar',
'path' => "*",
'component' => 'left',
'permission' => '',
'weight' => 0,
'cacheable' => 0,
'enabled' => 1,
'fix' => 0
);

#################################################################################
# URL paths definition
#################################################################################

$archive_path=array(
'path' => 'archive',
'permission' => '',
'enabled' => 1,
'render' => 1 
);

$archive_date_path=array(
'path' => 'archive/s',
'permission' => '',
'enabled' => 1,
'render' => 1 
);

$archive_offset_path=array(
'path' => 'archive/s/d',
'permission' => '',
'enabled' => 1,
'render' => 1 
);

$archive_check_path=array(
'path' => 'archive-check-date',
'permission' => '',
'enabled' => 1,
'render' => 0
);
