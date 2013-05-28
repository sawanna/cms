<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$menu_module=array(
'name' => 'menu',
'title' => '[str]Menu[/str]',
'description' => '[str]Create and edit menu elements[/str]',
'root' => 'menu',
'enabled' => 1,
'package' => 'Navigation',
'widgets' => array('menu_menu_widget','menu_drop_down_menu_widget','menu_parent_menu_widget','menu_child_menu_widget','menu_child_menu_expanded_widget'),
'paths' => array('menu_path_autocheck','menu_load_tree','menu_load_path','menu_load_tpl'),
'permissions' => array('menu_create_permission'),
'schemas' => array('menu_elements_schema'),
'admin-area-class' => 'CMenuAdmin',
'version' => '3.2-1'
);

#################################################################################
# Widgets definition
#################################################################################

$menu_menu_widget=array(
'name' => 'menu',
'title' => '[str]Menu[/str]',
'description' => '[str]Displays all menu elements[/str]',
'class' => 'CMenu',
'handler' => 'menu',
'argument' => '%query',
'path' => "*",
'component' => '',
'permission' => '',
'weight' => 0,
'cacheable' => 1,
'enabled' => 0
);

$menu_drop_down_menu_widget=array(
'name' => 'drop_down_menu',
'title' => '[str]Drop-down menu[/str]',
'description' => '[strf]Displays all menu elements in drop-down style[/strf]',
'class' => 'CMenu',
'handler' => 'drop_down_menu',
'argument' => '%query',
'path' => "*",
'component' => 'parent-menu',
'permission' => '',
'weight' => 0,
'cacheable' => 1,
'enabled' => 0
);

$menu_parent_menu_widget=array(
'name' => 'parent_menu',
'title' => '[str]Parent menu[/str]',
'description' => '[str]Displays menu root elements[/str]',
'class' => 'CMenu',
'handler' => 'parent_menu',
'argument' => '%query',
'path' => "*",
'component' => 'parent-menu',
'permission' => '',
'weight' => 0,
'cacheable' => 1,
'enabled' => 1
);

$menu_child_menu_widget=array(
'name' => 'child_menu',
'title' => '[str]Child menu[/str]',
'description' => '[str]Displays menu child elements[/str]',
'class' => 'CMenu',
'handler' => 'child_menu',
'argument' => '%query',
'path' => "*",
'component' => 'child-menu',
'permission' => '',
'weight' => 0,
'cacheable' => 1,
'enabled' => 1
);

$menu_child_menu_expanded_widget=array(
'name' => 'child_menu_expanded',
'title' => '[str]Expanded child menu[/str]',
'description' => '[str]Displays menu child elements in the expanded form[/str]',
'class' => 'CMenu',
'handler' => 'child_menu_expanded',
'argument' => '%query',
'path' => "*",
'component' => 'child-menu',
'permission' => '',
'weight' => 0,
'cacheable' => 1,
'enabled' => 0
);

#################################################################################
# URL paths definition
#################################################################################

$menu_path_autocheck=array(
'path' => 'menu-path-check',
'permission' => 'create menu elements',
'enabled' => 1,
'render' => 0 
);

$menu_load_tree=array(
'path' => 'menu-tree-load',
'permission' => 'create menu elements',
'enabled' => 1,
'render' => 0 
);

$menu_load_path=array(
'path' => 'menu-path-load',
'permission' => 'create menu elements',
'enabled' => 1,
'render' => 0 
);

$menu_load_tpl=array(
'path' => 'menu-tpl-load',
'permission' => 'create menu elements',
'enabled' => 1,
'render' => 0 
);

#################################################################################
# Permissions definition
#################################################################################

$menu_create_permission=array(
'name' => 'create menu elements',
'title' => '[str]Create menu elements[/str]',
'description' => '[str]Create or edit menu elements[/str]',
'access' => 0
);

#################################################################################
# Database tables definition
#################################################################################

$menu_elements_schema=array(
'name' => 'pre_menu_elements',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
   'parent' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
   'path' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'title' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'language' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'nid' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => false
    ),
     'weight' => array(
        'type' => 'integer',
        'length' => 4,
        'not null' => true
    ),
    'enabled' => array(
        'type' => 'integer',
        'length' => 4,
        'not null' => true
    ),
    'mark' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => false
    )
),
'primary key' => 'id',
'index' => array('parent','nid')
);
