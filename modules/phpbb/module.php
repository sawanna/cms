<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$phpbb_module=array(
'name' => 'phpbb',
'title' => '[str]Forum[/str]',
'description' => '[str]phpBB3 integration module[/str]',
'root' => 'phpbb',
'enabled' => 1,
'package' => 'Content',
'depend' => 'user',
'widgets' => array('phpbb_last_posts_widget'),
'admin-area-class' => 'CPhpbbAdmin',
'version' => '3.4-1'
);

#################################################################################
# Widgets definition
#################################################################################

$phpbb_last_posts_widget=array(
'name' => 'phpbb_last_posts',
'title' => '[str]phpBB3 last posts[/str]',
'description' => '[str]Fetchs last posts from phpBB3[/str]',
'class' => 'CPhpbb',
'handler' => 'last_posts',
'path' => "*",
'component' => 'right;left-single',
'permission' => '',
'weight' => 10,
'cacheable' => 0,
'enabled' => 1,
'fix' => 0
);
