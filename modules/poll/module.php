<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$poll_module=array(
'name' => 'poll',
'title' => '[str]Poll[/str]',
'description' => '[str]Adds ability to create polls on your website[/str]',
'root' => 'poll',
'enabled' => 1,
'package' => 'Forms',
'paths' => array('poll_name_autocheck','poll_field_name_autocheck','poll_ajax'),
'permissions' => array('poll_create_permission'),
'schemas' => array('poll_schema','poll_elements_schema','poll_results_schema'),
'admin-area-class' => 'CPollAdmin',
'version' => '3.3-1'
);

#################################################################################
# URL paths definition
#################################################################################

$poll_name_autocheck=array(
'path' => 'poll-widget-name-check',
'permission' => 'create poll',
'enabled' => 1,
'render' => 0 
);

$poll_field_name_autocheck=array(
'path' => 'poll-field-name-check',
'permission' => 'create poll',
'enabled' => 1,
'render' => 0 
);

$poll_ajax=array(
'path' => 'poll-ajax',
'permission' => '',
'enabled' => 1,
'render' => 0 
);

#################################################################################
# Permissions definition
#################################################################################

$poll_create_permission=array(
'name' => 'create poll',
'title' => '[str]Create poll[/str]',
'description' => '[str]Create or edit polls[/str]',
'access' => 0
);

#################################################################################
# Database tables definition
#################################################################################

$poll_schema=array(
'name' => 'pre_polls',
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

$poll_elements_schema=array(
'name' => 'pre_poll_elements',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
    'pid' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'name' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'weight' => array(
        'type' => 'integer',
        'length' => 4,
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

$poll_results_schema=array(
'name' => 'pre_poll_results',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
    'pid' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'data' => array(
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
