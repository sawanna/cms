<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$forms_module=array(
'name' => 'forms',
'title' => '[str]Forms[/str]',
'description' => '[str]Adds ability to display feedback forms on your website[/str]',
'root' => 'forms',
'enabled' => 1,
'package' => 'Forms',
'paths' => array('forms_name_autocheck','forms_field_name_autocheck'),
'permissions' => array('forms_create_permission'),
'schemas' => array('forms_schema','forms_elements_schema','forms_results_schema'),
'admin-area-class' => 'CFormsAdmin',
'version' => '3.2-1'
);

#################################################################################
# URL paths definition
#################################################################################

$forms_name_autocheck=array(
'path' => 'forms-widget-name-check',
'permission' => 'create forms',
'enabled' => 1,
'render' => 0 
);

$forms_field_name_autocheck=array(
'path' => 'forms-field-name-check',
'permission' => 'create forms',
'enabled' => 1,
'render' => 0 
);

#################################################################################
# Permissions definition
#################################################################################

$forms_create_permission=array(
'name' => 'create forms',
'title' => '[str]Create forms[/str]',
'description' => '[str]Create or edit feedback forms[/str]',
'access' => 0
);

#################################################################################
# Database tables definition
#################################################################################

$forms_schema=array(
'name' => 'pre_cforms',
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

$forms_elements_schema=array(
'name' => 'pre_cform_elements',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
    'fid' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'name' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'type' => array(
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

$forms_results_schema=array(
'name' => 'pre_cform_results',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
    'fid' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'data' => array(
        'type' => 'longtext',
        'not null' => true
    ),
    'tstamp' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'submission' => array(
        'type' => 'integer',
        'length' => 10,
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
