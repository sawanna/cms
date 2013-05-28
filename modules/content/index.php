<?php
defined('SAWANNA') or die();

class CContent
{
    var $sawanna;
    var $node_widget_name;
    var $node_widget_class;
    var $node_widget_handler;
    var $related_nodes_limit;
    var $content_alters;
    
    function CContent(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->node_widget_name='node';
        $this->node_widget_class='CContent';
        $this->node_widget_handler='node';
        $this->node_tpl='';
        $this->content_alters=array();
        
        $this->related_nodes_limit=5;
        
        $this->sawanna->cache->set_clear_on_change('content','meta');
    }
    
    function ready()
    {
        if (empty($this->sawanna->query)) return;
        
        if ($this->sawanna->has_access('access admin area') && $this->sawanna->has_access('create nodes'))
        {
            if ($this->sawanna->query != 'node')
            {
                $snids=$this->get_nodes_by_path($this->sawanna->query,true,false);
                $onids=$this->get_nodes_by_path($this->sawanna->query,false,false);
                
                $nids=array_merge($snids,$onids);
                if (!empty($nids))
                {
                    $links='<div id="node-edit-links" class="configure-link" title="[str]Edit[/str]"></div>';
                    $links.='<ul id="edit-links">';
                    foreach ($nids as $nid)
                    {
                        $links.='<li>'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/nodes/edit/'.$nid,'[str]Edit[/str]: ID='.$nid).'</li>';
                    }
                    $links.='</ul>';
                    $links.='<script language="JavaScript">$(document).ready(function(){ $("#node-edit-links").click(function(){if ($("ul#edit-links").css("display")=="none") { $("ul#edit-links").fadeIn(); } else { $("ul#edit-links").fadeOut(); }}); });</script>';
                    
                    $this->sawanna->tpl->params['duty'].=$links;
                }
            } elseif (!empty($this->sawanna->arg) && is_numeric($this->sawanna->arg))
            {
                $links='<div id="node-edit-links" class="configure-link" title="[str]Edit[/str]"></div>';
                $links.='<ul id="edit-links">';
                $links.='<li>'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/nodes/edit/'.$this->sawanna->arg,'[str]Edit[/str]: ID='.$this->sawanna->arg).'</li>';
                $links.='</ul>';
                $links.='<script language="JavaScript">$(document).ready(function(){ $("#node-edit-links").click(function(){if ($("ul#edit-links").css("display")=="none") { $("ul#edit-links").fadeIn(); } else { $("ul#edit-links").fadeOut(); }}); });</script>';
                
                $this->sawanna->tpl->params['duty'].=$links;
            }
        }
    }
    
    function node($path='used by caching')
    { 
        $output_items=array('widget_items'=>array(),'widget_tpls'=>array());

        // fixed
        //$front=(empty($this->sawanna->query)) ? true : false;
        $front=($this->sawanna->query == $this->sawanna->config->front_page_path) ? true : false;
        
        if ($this->sawanna->query=='node')
        {
            if (!empty($this->sawanna->arg) && is_numeric($this->sawanna->arg))
            {
                $output=array();
                $tpl='';
                
                $nid=$this->sawanna->arg;
                $this->get_node_item($nid,$output,$tpl);
                if (!empty($output)) 
                {
                    if (!empty($tpl)) $this->sawanna->module->widgets[$this->sawanna->module->get_widget_id($this->node_widget_name)]->tpl=$tpl;
                    
                    return $output;
                } else 
                {
                    sawanna_error('[str]Node not found[/str]');
                    return;
                }
            } else 
            {
                return $this->latest_node();
            }
        }
        
        $snids=$this->get_nodes_by_path($this->sawanna->query,true,$front);
        $onids=$this->get_nodes_by_path($this->sawanna->query,false,$front);
        $nids=array_merge($snids,$onids);
        
        if (!empty($nids) && is_array($nids))
        {
            // checking path extension
            if (!empty($this->sawanna->query) 
				&& empty($this->sawanna->arg) 
				&& $this->sawanna->query != $this->sawanna->config->front_page_path
				&& $this->sawanna->query != 'node' 
				&& $this->sawanna->config->clean_url 
				&& (($this->sawanna->config->path_extension && !preg_match('/\.html$/',$_SERVER['REQUEST_URI'])) ||
                (!$this->sawanna->config->path_extension && preg_match('/\.html$/',$_SERVER['REQUEST_URI']))
                )) {
                    $this->sawanna->constructor->redirect($this->sawanna->query);
            }
        
            foreach ($nids as $nid)
            {
                $output=array();
                $tpl='';
                
                $this->get_node_item($nid,$output,$tpl,$front);
                
                $output_items['widget_items'][]=$output;
                $output_items['widget_tpls'][]=$tpl;
            }
        }
       
        return $output_items;
    }
    
    function get_node_item($nid,&$output,&$tpl,$front=false)
    {
        $node=$this->sawanna->node->get($nid);
        if (!empty($node) && is_object($node))
        {
            if (!empty($node->meta['title'])) $this->sawanna->tpl->add_title($node->meta['title']);
            if (!empty($node->meta['keywords'])) $this->sawanna->tpl->add_keywords($node->meta['keywords']);
            if (!empty($node->meta['description'])) $this->sawanna->tpl->add_description($node->meta['description']);
            
            if (!empty($node->meta['author']))
            {
                if (strpos($node->meta['author']['url'],$this->sawanna->config->admin_area)===0)
                    $node_author=$node->meta['author']['name'];
                else 
                    $node_author=$this->sawanna->constructor->link($node->meta['author']['url'],$node->meta['author']['name'],'profile');
            } else 
            {
                $node_author='[str]Unknown[/str]';
            }
            
            $wid=$this->sawanna->module->get_widget_id($this->node_widget_name);
            if (isset($node->meta['tpl'])) 
                $tpl=$node->meta['tpl'];
            else 
                $tpl='';
                
            $related='';
            $nodes=$this->get_related_nodes($node);
            if (!empty($nodes))
            {
                foreach ($nodes as $path=>$title)
                {
                    $related.='<li>'.$this->sawanna->constructor->link($path,$title).'</li>';
                }
            }
            
            $this->alter_content($node->content,$nid);
            
            $output['date']=date($this->sawanna->config->date_format,$node->pub_tstamp);
            $output['title']=!empty($node->meta['title']) ? $node->meta['title'] : '&nbsp;';
            $output['content']=$node->content;
            $output['author']=$node_author;
            $output['description']=!empty($node->meta['description']) ? $node->meta['description'] : strip_tags($node->content);
            $output['tstamp']=$node->pub_tstamp;
            $output['related']=!empty($related) ? '<h3 class="related-cap">[str]See also[/str]:</h3><ul class="related-links">'.$related.'</ul>' : '';
        }
    }
    
    function get_nodes_by_path($path='',$sticky=false,$front=false,$offset=0,$limit=10)
    {
        $nids=array();

        if (!$front)
        {
            if (!$sticky)
                $this->sawanna->db->query("select p.nid from pre_node_pathes p left join pre_nodes n on n.id=p.nid where p.path='$1' and n.language='$2' and n.sticky='0' and n.id is not null order by n.weight asc, n.created desc limit $3 offset $4",$path,$this->sawanna->locale->current,$limit,$offset);
            else 
                $this->sawanna->db->query("select p.nid from pre_node_pathes p left join pre_nodes n on n.id=p.nid where p.path='$1' and n.language='$2' and n.sticky<>'0' and n.id is not null order by n.weight asc, n.created desc limit $3 offset $4",$path,$this->sawanna->locale->current,$limit,$offset);
        } else
         {      
            if (!$sticky)
                $this->sawanna->db->query("select p.nid from pre_node_pathes p left join pre_nodes n on n.id=p.nid where ( p.path='[home]' or p.path='$1' ) and n.language='$2' and n.sticky='0' and n.id is not null order by n.weight asc, n.created desc limit $3 offset $4",$this->sawanna->config->front_page_path,$this->sawanna->locale->current,$limit,$offset);
            else 
                $this->sawanna->db->query("select p.nid from pre_node_pathes p left join pre_nodes n on n.id=p.nid where ( p.path='[home]' or p.path='$1' ) and n.language='$2' and n.sticky<>'0' and n.id is not null order by n.weight asc, n.created desc limit $3 offset $4",$this->sawanna->config->front_page_path,$this->sawanna->locale->current,$limit,$offset);
           /*     
             if (!$sticky)
                $this->sawanna->db->query("select id as nid from  pre_nodes where front<>'0' and sticky='0' and language='$1'  order by weight asc, created desc  limit $2 offset $3",$this->sawanna->locale->current,$limit,$offset);
            else 
                $this->sawanna->db->query("select id as nid from pre_nodes where front<>'0' and sticky<>'0' and language='$1' order by weight asc, created desc limit $2 offset $3",$this->sawanna->locale->current,$limit,$offset);
            */
         }
             
        while ($row=$this->sawanna->db->fetch_object())
        {
            $nids[]=$row->nid;
        }
        
        return $nids;
    }
    
    function get_related_nodes(&$node)
    {
        if (!is_object($node)) return ;
        
        $nodes=array();
        
        if (empty($node->meta['keywords'])) return ;
        
        $d=new CData();
        $limit=$this->related_nodes_limit;
        $co=0;
        $break=false;
        
        $tags=explode(',',$node->meta['keywords']);
        foreach ($tags as $tag)
        {
            if ($break) break;
            
            $this->sawanna->db->query("select p.path,n.meta from pre_node_keywords k left join pre_node_pathes p on p.nid=k.nid left join pre_nodes n on n.id=k.nid where k.keyword='$1' and k.nid<>'$2' order by n.created desc",trim($tag),$node->id);
            while ($row=$this->sawanna->db->fetch_object()) 
            {
                if ($co>=$limit) {
                    $break=true;
                    break;
                }
                
                $path=!empty($row->path) ? $row->path : 'node/'.$row->id;
                
                if (array_key_exists($path,$nodes)) continue;
                
                if (!empty($row->meta)) $row->meta=$d->do_unserialize($row->meta);
                $title=!empty($row->meta['title']) ? strip_tags($row->meta['title']) : $path;
                
                $nodes[$path]=$title;
                $co++;
            }
        }
        
        return $nodes;
    }
    
    function latest_node()
    {
        $output=array();
        $tpl='';
        
        $this->sawanna->db->query("select id from pre_nodes where sticky<>'0' and language='$1'  order by created desc",$this->sawanna->locale->current);
        $row=$this->sawanna->db->fetch_object();
        if (empty($row))
        {
            $this->sawanna->db->query("select id from pre_nodes where sticky='0' and language='$1'  order by created desc",$this->sawanna->locale->current);
            $row=$this->sawanna->db->fetch_object();
        }
        
        if (!empty($row))
        {
            $this->get_node_item($row->id,$output,$tpl);
        
            if (!empty($tpl)) $this->sawanna->module->widgets[$this->sawanna->module->get_widget_id($this->node_widget_name)]->tpl=$tpl;
        }
        
        return $output;
    }
    
    function alter_content(&$content,$nid=0)
    {
        if (!empty($this->content_alters))
        {
            foreach ($this->content_alters as $alter)
            {
                if (empty($alter['class']) || empty($alter['handler'])) continue;
                $object=$this->sawanna->module->get_widget_object_name($alter['class']);
                if (!isset($GLOBALS[$object]) || !is_object($GLOBALS[$object]) || !method_exists($GLOBALS[$object],$alter['handler'])) continue;
                
                $GLOBALS[$object]->{$alter['handler']}($content,$nid);
            }
        }
    }
}
