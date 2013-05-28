<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$sitemap_module=array(
'name' => 'sitemap',
'title' => '[str]Sitemap[/str]',
'description' => '[str]Displays the website map[/str]',
'root' => 'sitemap',
'enabled' => 1,
'package' => 'Navigation',
'depend' => 'menu',
'widgets' => array('sitemap_icon_widget','sitemap_widget','sitemap_xml_widget'),
'paths' => array('sitemap_path','sitemap_offset_path','sitemap_xml_path'),
'admin-area-class' => 'CSitemapAdmin',
'version' => '3.2-1'
);

#################################################################################
# Widgets definition
#################################################################################

$sitemap_icon_widget=array(
'name' => 'sitemapIcon',
'title' => '[str]Sitemap icon[/str]',
'description' => '[str]Displays the sitemap icon[/str]',
'class' => 'CSitemap',
'handler' => 'icon',
'path' => "*",
'component' => 'header',
'permission' => '',
'weight' => 1,
'cacheable' => 0,
'enabled' => 1,
'fix' => 0
);

$sitemap_widget=array(
'name' => 'sitemap',
'title' => 'Sitemap',
'description' => 'Sitemap output',
'class' => 'CSitemap',
'handler' => 'sitemap',
'path' => "sitemap",
'argument' => '%arg',
'component' => '',
'permission' => '',
'weight' => -1,
'cacheable' => 1,
'enabled' => 1,
'fix' => 1
);

$sitemap_xml_widget=array(
'name' => 'sitemapXML',
'title' => 'Sitemap XML',
'description' => 'Sitemap XML output',
'class' => 'CSitemap',
'handler' => 'sitemap_xml',
'path' => "sitemap-xml",
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

$sitemap_path=array(
'path' => 'sitemap',
'permission' => '',
'enabled' => 1,
'render' => 1 
);

$sitemap_offset_path=array(
'path' => 'sitemap/d',
'permission' => '',
'enabled' => 1,
'render' => 1 
);

$sitemap_xml_path=array(
'path' => 'sitemap-xml',
'permission' => '',
'enabled' => 1,
'render' => 1 
);
