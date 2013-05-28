<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$search_module=array(
'name' => 'search',
'title' => '[str]Search[/str]',
'description' => '[str]Content search on the website[/str]',
'root' => 'search',
'enabled' => 1,
'package' => 'Content',
'depend' => 'content',
'widgets' => array('search_widget','search_panel_widget','tags_widget'),
'paths' => array('search_path','search_offset_path','search_keyword_complete_path','search_tags_path'),
'permissions' => array('search_permission'),
'admin-area-class' => 'CSearchAdmin',
'version' => '3.2-1'
);

#################################################################################
# Widgets definition
#################################################################################

$search_widget=array(
'name' => 'search',
'title' => 'Search',
'description' => 'Search output',
'class' => 'CSearch',
'handler' => 'search',
'argument' => '%arg',
'path' => "search",
'component' => '',
'permission' => '',
'weight' => 0,
'cacheable' => 0,
'enabled' => 1,
'fix' => 1
);

$search_panel_widget=array(
'name' => 'quickSearch',
'title' => '[str]Quick search[/str]',
'description' => '[str]Displays the quick search panel[/str]',
'class' => 'CSearch',
'handler' => 'panel',
'path' => "!search\n!search/*\n*",
'component' => 'left;left-single',
'permission' => '',
'weight' => 0,
'cacheable' => 0,
'enabled' => 1,
'fix' => 0
);

$tags_widget=array(
'name' => 'tags',
'title' => 'Tags',
'description' => 'Tags output',
'class' => 'CSearch',
'handler' => 'tag',
'argument' => '%arg',
'path' => "tag",
'component' => '',
'permission' => '',
'weight' => 0,
'cacheable' => 0,
'enabled' => 1,
'fix' => 1
);

#################################################################################
# URL paths definition
#################################################################################

$search_path=array(
'path' => 'search',
'permission' => '',
'enabled' => 1,
'render' => 1 
);

$search_offset_path=array(
'path' => 'search/d',
'permission' => '',
'enabled' => 1,
'render' => 1 
);

$search_tags_path=array(
'path' => 'tag/s',
'permission' => '',
'enabled' => 1,
'render' => 1 
);

$search_keyword_complete_path=array(
'path' => 'search-text-complete',
'permission' => '',
'enabled' => 1,
'render' => 0 
);

#################################################################################
# Permissions definition
#################################################################################

$search_permission=array(
'name' => 'search content text',
'title' => '[str]Search content text[/str]',
'description' => '[str]By default only keywords search is performed[/str]',
'access' => 0
);

