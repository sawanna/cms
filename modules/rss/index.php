<?php
defined('SAWANNA') or die();

class CRss
{
    var $sawanna;
    var $separator;
    var $tree;
    var $pathes;
    var $menu_element_id;
    var $limit;
    var $front;
    var $query;
    var $menu;
    var $exclude_perms;
    
    function CRss(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->separator='-r-s-s-';
        
        $limit=$this->sawanna->get_option('rss_limit');
        $this->limit=$limit && is_numeric($limit) && $limit>0 ? ceil($limit) : 10;
        
        // deprecated
        //$this->tree=array();
        //$this->pathes=array();
        //$this->menu_element_id=0;
        
        $this->query='';
        $this->front=false;
        
        $this->sawanna->cache->set_clear_on_change('content','rss');
        $this->sawanna->cache->set_clear_on_change('menu','rss');
        
        $this->menu=&$GLOBALS['menu'];
    }
    
    function ready()
    {
        $suff='';
        $path=$this->sawanna->get_option('rss_path');
        if (!empty($path)) $suff='/'.$path;
        
        $this->sawanna->tpl->add_link('<link rel="alternate" type="application/rss+xml"  href="'.$this->sawanna->constructor->url('rss'.$suff).'" title="RSS">');
        
        if ($this->sawanna->query=='rss') $this->init();
    }
    
    function init()
    {
        if ($this->sawanna->query=='rss') 
        {
            $this->query=!empty($this->sawanna->arg) ? str_replace($this->separator,'/',$this->sawanna->arg) : '';
        } else 
        {
            $this->query=$this->sawanna->query;    
        }
        
        // deprecated
        //$this->get_menu_element_by_path($this->query); 
        //$this->get_nodes_tree($this->sawanna->locale->current,$this->menu_element_id);
        
        $this->front=(empty($this->query)) ? true : false;
        
        $this->exclude_perms=array();
        
        if (isset($GLOBALS['user']) && is_object($GLOBALS['user']))
        {
            $groups=$GLOBALS['user']->get_user_groups();
            
            foreach($groups as $group_id=>$group_name)
            {
                $perm='user group '.$group_id.' access';
                
                if (!$this->sawanna->has_access($perm))
                {
                    $this->exclude_perms[]=$perm;
                }
            }
        }
    }
    
     function icon($query)
    {
        $this->init();
        
        // deprecated
        //if (empty($this->tree)) return ;
        
        if (!$this->show_icon()) return;
        
        $path=$this->sawanna->get_option('rss_path');
        if (!empty($path)) $query=$path;
        
        if (!empty($query)) return array('link'=>$this->sawanna->constructor->link('rss/'.str_replace('/',$this->separator,$query),' ','rss',array('title'=>'RSS')));
        else return array('link'=>$this->sawanna->constructor->link('rss',' ','rss',array('title'=>'RSS')));
    }
    
    function rss($arg='used by caching')
    {
         $xml='';
         $limit=$this->limit;
         $query=$this->query;

         if (!empty($query))
         {
            // deprecated
            //$this->sawanna->db->query("select id,title from pre_menu_elements where path='$1' and language='$2'",$query,$this->sawanna->locale->current);
            //$row=$this->sawanna->db->fetch_object();
            //if (empty($row)) return ;
            
            $row=$this->menu->get_element_row_by_path($query);
         } 
         
         if (empty($row))
         {
             $row=new stdClass();
             $row->id=0;
             $row->title=$this->sawanna->config->site_name;
         }
        
        $xml.='<?xml version="1.0" encoding="utf-8"?>'."\n";
        $xml.='<rss version="2.0">'."\n";
        $xml.='<channel>'."\n";
        $xml.='<title>'.$row->title.'</title>'."\n";
        $xml.='<link>'.$this->sawanna->constructor->url($query).'</link>'."\n";
        $xml.='<description>'.$this->sawanna->config->site_name.' :: '.$this->sawanna->config->site_slogan.'</description>'."\n";
        $xml.='<generator>Sawanna CMS</generator>'."\n";

       $items=$this->nodes_list();
       foreach ($items as $item)
       {
           if (empty($item)) continue;
           
           $this->get_item($item,$xml);
       }
        
        $xml.='</channel>'."\n";
        $xml.='</rss>';

        header('Content-type: application/xml');
        echo $xml;
    }
    
    function get_item(&$output,&$xml)
    {
        $description=$output['content'];
        $description=html_entity_decode($description);
        $description=preg_replace('/&[^\x20]+;/','',$description);
        $description=str_replace('&','',$description);
        $description=preg_replace('/ ( [\x00-\x7F] | [\xC0-\xDF][\x80-\xBF] | [\xE0-\xEF][\x80-\xBF]{2} | [\xF0-\xF7][\x80-\xBF]{3} ) | . /x','$1',$description);
        
        $xml.='<item>'."\n";
        $xml.='<title>'.$output['title'].'</title>'."\n";
        $xml.='<link>'.$output['url'].'</link>'."\n";
        $xml.='<description>'.$description.'</description>'."\n";
        $xml.='<guid isPermaLink="true">'.$output['url'].'</guid>'."\n";
        $xml.='<pubDate>'.date('r',$output['tstamp']).'</pubDate>'."\n";
        $xml.='</item>'."\n";
    }

    function nodes_list()
    {
        if ($this->query=='node') return;

        $items=array();

        // deprecated
        //if (empty($this->tree) && !$this->front) return ;

        $snids=$this->get_child_nodes(true,$this->front);
        $onids=$this->get_child_nodes(false,$this->front,0,$this->limit);
        
        if (!empty($snids) && is_array($snids))
        {
            foreach ($snids as $nid)
            {
                $output=array();
                
                $this->get_node_teaser($nid,$output);
                
                $items[]=$output;
            }
        }
        
        if (!empty($onids) && is_array($onids))
        {
            foreach ($onids as $nid)
            {
                $output=array();
                
                $this->get_node_teaser($nid,$output);
                
                $items[]=$output;
            }
        }
       
        return $items;
    }
    
    function get_node_teaser($nid,&$output)
    {
        $node=$this->sawanna->node->get($nid);
        if (!empty($node) && is_object($node))
        {
            if (empty($node->meta['teaser'])) return;
            
            $output['tstamp']=$node->pub_tstamp;
            $output['title']=!empty($node->meta['title']) ? strip_tags($node->meta['title']) : '';
            $output['content']=strip_tags($node->meta['teaser']);
            $output['keywords']=!empty($node->meta['keywords']) ? $node->meta['keywords'] : '';
            $output['url']=isset($this->pathes[$nid]) ? $this->sawanna->constructor->url($this->pathes[$nid],false,false,true) : $this->sawanna->constructor->url('node/'.$nid,false,false,true) ;
        }
    }
    
    // deprecated
    function _get_child_nodes($sticky=false,$front=false,$offset=0,$limit=10)
    {
        if (empty($this->tree) && !$this->front) return ;
        
        $nids=array();

        if (!$front)
        {
            if (!$sticky)
                $this->sawanna->db->query("select id from pre_nodes where id in ( $1 ) and sticky='0' and language='$2' order by weight asc, created desc limit $3 offset $4",implode(',',$this->tree),$this->sawanna->locale->current,$limit,$offset);
            else 
                $this->sawanna->db->query("select id from pre_nodes where id in ( $1 ) and sticky<>'0' and language='$2' order by weight asc, created desc",implode(',',$this->tree),$this->sawanna->locale->current);
        } else
         {      
              if (!$sticky)
                $this->sawanna->db->query("select id from pre_nodes where front<>'0' and sticky='0' and language='$1' order by weight asc, created desc limit $2 offset $3",$this->sawanna->locale->current,$limit,$offset);
            else 
                $this->sawanna->db->query("select id from pre_nodes where front<>'0' and sticky<>'0' and language='$1' order by weight asc, created desc",$this->sawanna->locale->current);
         }
             
        while ($row=$this->sawanna->db->fetch_object())
        {
            $nids[]=$row->id;
        }
        
        return $nids;
    }
    
    function get_child_nodes($sticky=false,$front=false,$offset=0,$limit=10)
    {
        
        $nids=array();
        
        $perm_not_in='';
        
        if (!empty($this->exclude_perms))
        {
            foreach($this->exclude_perms as $perm)
            {
                $perm_not_in.="and n.permission <> '".$this->sawanna->db->escape_string($perm)."' ";
            }
        }

        if (!$front)
        {
            if (!$sticky)
                $this->sawanna->db->query("select n.id, p.path from pre_nodes n left join pre_node_pathes p on n.id=p.nid where p.path like '$1/%' and n.sticky='0' and n.language='$2' ".$perm_not_in." order by n.weight asc, n.pub_tstamp desc, n.created desc limit $3 offset $4",$this->query,$this->sawanna->locale->current,$limit,$offset);
            else 
                $this->sawanna->db->query("select n.id, p.path from pre_nodes n left join pre_node_pathes p on n.id=p.nid where p.path like '$1/%' and n.sticky<>'0' and n.language='$2' ".$perm_not_in." order by n.weight asc, n.pub_tstamp desc, n.created desc",$this->query,$this->sawanna->locale->current);
        } else
         {      
              if (!$sticky)
                $this->sawanna->db->query("select n.id, p.path from pre_nodes n left join pre_node_pathes p on n.id=p.nid where n.front<>'0' and n.sticky='0' and n.language='$1' ".$perm_not_in." order by n.weight asc, n.pub_tstamp desc, n.created desc limit $2 offset $3",$this->sawanna->locale->current,$limit,$offset);
            else 
                $this->sawanna->db->query("select n.id, p.path from pre_nodes n left join pre_node_pathes p on n.id=p.nid where n.front<>'0' and n.sticky<>'0' and n.language='$1' ".$perm_not_in." order by n.weight asc, n.pub_tstamp desc, n.created desc",$this->sawanna->locale->current);
         }
             
        while ($row=$this->sawanna->db->fetch_object())
        {
            $nids[]=$row->id;
            
            $this->pathes[$row->id]=$row->path;
        }
        
        return $nids;
    }
    
    // deprecated
    function _get_nodes_tree($language,$parent=0)
    {
        $res=$this->sawanna->db->query("select * from pre_menu_elements where parent='$1' and language='$2' and enabled<>'0' order by weight",$parent,$language);
        while ($row=$this->sawanna->db->fetch_object($res))
        {
           if (!empty($row->nid))
           {
                $this->tree[]=$row->nid;
                $this->pathes[$row->nid]=$row->path;
           }
            
            $this->get_nodes_tree($language,$row->id);
        }

        return;
    }
    
    // deprecated
    function _get_menu_element_by_path($path)
    {
        if (empty($path)) return false;
        
        $this->sawanna->db->query("select id from pre_menu_elements where path='$1' and language='$2' and enabled<>'0'",$path,$this->sawanna->locale->current);
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row))
        {
            $this->menu_element_id=$row->id;
        } else 
        {
            $this->menu_element_id=-1;
        }
    }
    
    function show_icon()
    {
        $row=$this->menu->get_element_row_by_path($this->sawanna->query);
        if (empty($row)) return false;
        
        return true;
    }
}
