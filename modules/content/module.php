<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$content_module=array(
'name' => 'content',
'title' => '[str]Content[/str]',
'description' => '[str]Create and edit website pages[/str]',
'root' => 'content',
'enabled' => 1,
'package' => 'Content',
'widgets' => array('content_node_widget'),
'paths' => array('content_node_path','content_node_offset_path','content_node_path_autocomplete_path','content_node_keyword_autocomplete_path','content_file_alias_check_path'),
'permissions' => array('content_create_node_permission','content_publish_node_permission','content_front_node_permission'),
'schemas' => array('content_node_pathes_schema','content_node_keywords_schema'),
'admin-area-class' => 'CContentAdmin',
'version' => '3.2-1'
);

#################################################################################
# Widgets definition
#################################################################################

$content_node_widget=array(
'name' => 'node',
'title' => '[str]Node[/str]',
'description' => '[str]Displays node content[/str]',
'class' => 'CContent',
'handler' => 'node',
'argument' => '%path',
'path' => "*",
'component' => '',
'permission' => '',
'weight' => 1,
'cacheable' => 1,
'enabled' => 1,
'fix' => 1
);

#################################################################################
# URL paths definition
#################################################################################

$content_node_path=array(
'path' => 'node',
'permission' => '',
'enabled' => 1,
'render' => 1 
);

$content_node_offset_path=array(
'path' => 'node/d',
'permission' => '',
'enabled' => 1,
'render' => 1 
);

$content_node_path_autocomplete_path=array(
'path' => 'nodes/path-complete',
'permission' => 'create nodes',
'enabled' => 1,
'render' => 0 
);

$content_node_keyword_autocomplete_path=array(
'path' => 'nodes/keyword-complete',
'permission' => 'create nodes',
'enabled' => 1,
'render' => 0 
);

$content_file_alias_check_path=array(
'path' => 'nodes/file-alias-check',
'permission' => 'upload files',
'enabled' => 1,
'render' => 0 
);

#################################################################################
# Permissions definition
#################################################################################

$content_create_node_permission=array(
'name' => 'create nodes',
'title' => '[str]Create nodes[/str]',
'description' => '[str]Create or edit nodes[/str]',
'access' => 0
);

$content_publish_node_permission=array(
'name' => 'publish nodes',
'title' => '[str]Publish nodes[/str]',
'description' => '[str]Node is invisible until published[/str]',
'access' => 0
);

$content_front_node_permission=array(
'name' => 'publish nodes on front page',
'title' => '[str]Publish nodes on front page[/str]',
'description' => '[str]Published nodes can be displayed on front page[/str]',
'access' => 0
);

#################################################################################
# Database tables definition
#################################################################################

$content_node_pathes_schema=array(
'name' => 'pre_node_pathes',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
    'nid' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'path' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'mark' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => false
    )
),
'primary key' => 'id',
'index' => 'nid'
);

$content_node_keywords_schema=array(
'name' => 'pre_node_keywords',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
    'nid' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'keyword' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'mark' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => false
    )
),
'primary key' => 'id',
'index' => 'nid'
);
