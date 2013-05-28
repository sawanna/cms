<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$top_module=array(
'name' => 'top',
'title' => '[str]Top-rating[/str]',
'description' => '[str]Displays most popular pages[/str]',
'root' => 'top',
'enabled' => 1,
'package' => 'Content',
'depend' => 'content,menu,user',
'widgets' => array('top_most_visited_widget','top_most_rated_widget'),
'paths' => array('vote_path'),
'permissions' => array('vote_permission'),
'schemas' => array('top_visits_schema','top_rates_schema'),
'admin-area-class' => 'CTopAdmin',
'version' => '3.4-1'
);

#################################################################################
# Widgets definition
#################################################################################

$top_most_visited_widget=array(
'name' => 'most_visited',
'title' => '[str]Most visited[/str]',
'description' => '[str]Displays the list of most visited pages[/str]',
'class' => 'CTop',
'handler' => 'most_visited',
'path' => "*",
'component' => 'left',
'weight' => 5,
'cacheable' => 0,
'enabled' => 1,
'fix' => 0
);

$top_most_rated_widget=array(
'name' => 'top_rated',
'title' => '[str]Top rated[/str]',
'description' => '[str]Displays the list of top rated nodes[/str]',
'class' => 'CTop',
'handler' => 'top_rated',
'path' => "*",
'component' => 'left',
'weight' => 5,
'cacheable' => 0,
'enabled' => 1,
'fix' => 0
);

#################################################################################
# URL paths definition
#################################################################################

$vote_path=array(
'path' => 'vote',
'permission' => '',
'enabled' => 1,
'render' => 0 
);

#################################################################################
# Permissions definition
#################################################################################

$vote_permission=array(
'name' => 'rate',
'title' => '[str]Rate[/str]',
'description' => '[str]Rate nodes[/str]',
'access' => 1
);

#################################################################################
# Database tables definition
#################################################################################

$top_visits_schema=array(
'name' => 'pre_top_visits',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
    'path' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'language' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'co' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'mark' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => false
    )
),
'primary key' => 'id',
'index' => array('path','language')
);

$top_rates_schema=array(
'name' => 'pre_top_rates',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
    'uid' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'nid' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'rate' => array(
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
'index' => array('uid','nid')
);
