<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$rss_module=array(
'name' => 'rss',
'title' => '[str]RSS[/str]',
'description' => '[str]Displays the RSS output according to URL address[/str]',
'root' => 'rss',
'enabled' => 1,
'package' => 'Content',
'depend' => 'content, menu',
'widgets' => array('rss_icon_widget','rss_widget'),
'paths' => array('rss_path','rss_offset_path'),
'admin-area-class' => 'CRssAdmin',
'version' => '3.2-1'
);

#################################################################################
# Widgets definition
#################################################################################

$rss_icon_widget=array(
'name' => 'rssIcon',
'title' => '[str]RSS icon[/str]',
'description' => '[str]Displays the RSS icon[/str]',
'class' => 'CRss',
'handler' => 'icon',
'argument' => '%query',
'path' => "*",
'component' => '',
'permission' => '',
'weight' => 10,
'cacheable' => 1,
'enabled' => 1,
'fix' => 0
);

$rss_widget=array(
'name' => 'rssOutput',
'title' => 'RSS',
'description' => 'RSS output',
'class' => 'CRss',
'handler' => 'rss',
'argument' => '%arg',
'path' => "rss",
'component' => '',
'permission' => '',
'weight' => -1,
'cacheable' => 0,
'enabled' => 1,
'fix' => 1
);

#################################################################################
# URL paths definition
#################################################################################

$rss_path=array(
'path' => 'rss',
'permission' => '',
'enabled' => 1,
'render' => 1 
);

$rss_offset_path=array(
'path' => 'rss/s',
'permission' => '',
'enabled' => 1,
'render' => 1 
);
