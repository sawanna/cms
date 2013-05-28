<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$teasers_module=array(
'name' => 'teasers',
'title' => '[str]Teasers[/str]',
'description' => '[str]Displays node teasers[/str] ([str]creates widgets[/str])',
'root' => 'teasers',
'enabled' => 1,
'package' => 'Content',
'depend' => 'content',
'paths' => array('teaser_name_autocheck'),
'permissions' => array('teaser_create_permission'),
'schemas' => array('teasers_schema'),
'admin-area-class' => 'CTeasersAdmin',
'version' => '3.2-1'
);

#################################################################################
# URL paths definition
#################################################################################

$teaser_name_autocheck=array(
'path' => 'teasers-widget-name-check',
'permission' => 'create teasers',
'enabled' => 1,
'render' => 0 
);

#################################################################################
# Permissions definition
#################################################################################

$teaser_create_permission=array(
'name' => 'create teasers',
'title' => '[str]Create teasers[/str]',
'description' => '[str]Create or edit the teasers widgets[/str]',
'access' => 0
);

#################################################################################
# Database tables definition
#################################################################################

$teasers_schema=array(
'name' => 'pre_teasers',
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
    'path' => array(
        'type' => 'string',
        'length' => 255,
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
