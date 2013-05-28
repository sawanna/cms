<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$list_module=array(
'name' => 'nodesList',
'title' => '[str]Nodes list[/str]',
'description' => '[str]Displays the list of recent child nodes[/str]',
'root' => 'list',
'enabled' => 1,
'package' => 'Content',
'depend' => 'content',
'widgets' => array('list_node_list_widget'),
'admin-area-class' => 'CNodesListAdmin',
'version' => '3.3-1'
);

#################################################################################
# Widgets definition
#################################################################################

$list_node_list_widget=array(
'name' => 'nodesList',
'title' => '[str]Nodes list[/str]',
'description' => '[str]Displays the list of recent child nodes[/str]',
'class' => 'CNodesList',
'handler' => 'nodes_list',
'argument' => '%path',
'path' => "[home]",
'component' => '',
'permission' => '',
'weight' => 2,
'cacheable' => 1,
'enabled' => 1,
'fix' => 0
);
