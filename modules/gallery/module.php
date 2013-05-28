<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$gallery_module=array(
'name' => 'gallery',
'title' => '[str]Gallery[/str]',
'description' => '[str]Displays the image gallery on your website[/str]',
'root' => 'gallery',
'enabled' => 1,
'package' => 'Content',
'paths' => array('gallery_name_autocheck','gallery_ajax_load'),
'permissions' => array('gallery_create_permission'),
'schemas' => array('gallery_schema','gallery_image_schema'),
'admin-area-class' => 'CGalleryAdmin',
'version' => '3.3-1'
);

#################################################################################
# URL paths definition
#################################################################################

$gallery_name_autocheck=array(
'path' => 'gallery-widget-name-check',
'permission' => 'create gallery',
'enabled' => 1,
'render' => 0 
);

$gallery_ajax_load=array(
'path' => 'gallery-ajax-load',
'permission' => '',
'enabled' => 1,
'render' => 0 
);

#################################################################################
# Permissions definition
#################################################################################

$gallery_create_permission=array(
'name' => 'create gallery',
'title' => '[str]Create gallery[/str]',
'description' => '[str]Create or edit galleries[/str]',
'access' => 0
);

#################################################################################
# Database tables definition
#################################################################################

$gallery_schema=array(
'name' => 'pre_galleries',
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

$gallery_image_schema=array(
'name' => 'pre_gallery_images',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
    'gid' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
   'original' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'thumb' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'image' => array(
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
