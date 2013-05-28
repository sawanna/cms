<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$code_module=array(
'name' => 'code',
'title' => '[str]HTML widgets[/str]',
'description' => '[str]Adds ability to create custom widgets with specified HTML content[/str]',
'root' => 'code',
'enabled' => 1,
'package' => 'Content',
'paths' => array('code_name_autocheck'),
'permissions' => array('code_create_permission'),
'schemas' => array('code_schema'),
'admin-area-class' => 'CCodeAdmin',
'version' => '3.2-1'
);

#################################################################################
# URL paths definition
#################################################################################

$code_name_autocheck=array(
'path' => 'code-widget-name-check',
'permission' => 'create code',
'enabled' => 1,
'render' => 0 
);

#################################################################################
# Permissions definition
#################################################################################

$code_create_permission=array(
'name' => 'create code',
'title' => '[str]Create custom widgets[/str]',
'description' => '[str]Create or edit custom widgets with HTML content[/str]',
'access' => 0
);

#################################################################################
# Database tables definition
#################################################################################

$code_schema=array(
'name' => 'pre_cwidgets',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
    'name' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'content' => array(
        'type' => 'longtext',
        'not null' => true
    ),
    'meta' => array(
        'type' => 'longtext',
        'not null' => false
    ),
    'mark' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => false
    )
),
'primary key' => 'id'
);
