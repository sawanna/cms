<?php
defined('SAWANNA') or dir();

#################################################################################
# Post-install callback
#################################################################################

function content_install($module_id)
{
    global $sawanna;
    
    $welcome='<div>Sawanna CMS ver.'.VERSION.'</div><div align="center" style="margin: 100px 0px"><img src="'.SITE.'/misc/logo.png" /></div><div align="right"><a href="http://sawanna.org" target="_blank">http://sawanna.org</a></div>';
    
    $node=array(
    'module'=>$module_id,
    'content'=>$welcome,
    'meta'=>array('title'=>$sawanna->locale->get_string('Welcome to Sawanna CMS'),'teaser'=>$sawanna->locale->get_string('Welcome to Sawanna CMS')),
    'front'=>1,
    'enabled'=>1
    );
   
    $sawanna->node->set($node);
    $sawanna->db->last_insert_id('nodes','id');
    $row=$sawanna->db->fetch_object();

    $sawanna->db->query("insert into pre_node_pathes (nid,path) values ('$1','$2')",$row->id,'welcome');
    $sawanna->navigator->set(array('path'=>'welcome','enabled'=>1,'render'=>1,'module'=>$module_id));
    $sawanna->navigator->set(array('path'=>'welcome/d','enabled'=>1,'render'=>1,'module'=>$module_id));
}
