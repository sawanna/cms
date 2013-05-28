<?php
defined('SAWANNA') or die();

class CJcloud
{
    var $sawanna;
    var $module_id;
    var $limit;
    var $radius;
    var $size;
    var $color;
    var $view;
    
    function CJcloud(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('jcloud');
        
        $this->limit=30;
        
        $this->radius=90;
        
        $radius=$this->sawanna->get_option('jcloud_radius');
        if ($radius && is_numeric($radius) && $radius>=50 && $radius<=300)
        {
            $this->radius=floor($radius);
        }
        
        $this->size=20;
        
        $size=$this->sawanna->get_option('jcloud_size');
        if ($size && is_numeric($size) && $size>=4 && $size<=50)
        {
            $this->size=ceil($size);
        }
        
        $color='#000000';
        $color=$this->sawanna->get_option('jcloud_color');
        if ($color && preg_match('/\#[a-f0-9]{3,6}/i',$color))
        {
            $this->color=$color;
        }
        
        $this->view='circle';
        $views=$this->get_cloud_views();
        
        $view=$this->sawanna->get_option('jcloud_view');
        if ($view && in_array($view,$views))
        {
            $this->view=$view;
        }
        
        if ($this->sawanna->query != $this->sawanna->config->admin_area) 
        {
            if ($this->view != 'cumulus')
            {
                $this->sawanna->tpl->add_script($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/js/jcloud');
            } else
            {
                $this->sawanna->tpl->add_script($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/js/swfobject');
            }
        }
    }
    
    function get_cloud_views()
    {
        return array(
            'circle',
            'sphere',
            'spiral',
            'chaos',
            'rings',
            'cumulus'
        );
    }
    
    function get_init_script($radius,$size,$flats=1,$areal=180,$splitX=0,$splitY=0)
    {
        $script='<script language="JavaScript">';
        $script.='$(document).ready(function(){';
        $script.='$(\'ul#jcloud-list\').jcloud({';
        $script.='radius:'.$radius.',';
        $script.='size:'.$size.',';
        $script.='step:4,';
        $script.='speed:50,';
        $script.='flats:'.$flats.',';
        $script.='clock:10,';
        $script.='areal:'.$areal.',';
        $script.='splitX:'.$splitX.',';
        $script.='splitY:'.$splitY.',';
        $script.='colors:[\''.$this->color.'\']';
        $script.='});';
        $script.='});';
        $script.='</script>';

        return $script;
    }
    
    function get_flash_script($tags){
        
        $tags='<tags>'.$tags.'</tags>';
        
        $script='<script type="text/javascript">';
        $script.='var rnumber = Math.floor(Math.random()*9999999);';
        $script.='var widget_so = new SWFObject("'.SITE.'/'.$this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/tagcloud.swf?r="+rnumber, "tagcloudflash", "'.(2*$this->radius).'", "'.(2*$this->radius).'", "9", "#ffffff");';
        $script.='widget_so.addParam("wmode", "transparent");';
        $script.='widget_so.addParam("allowScriptAccess", "always");';
        $script.='widget_so.addVariable("tcolor", "'.str_replace('#','0x',$this->color).'");';
        $script.='widget_so.addVariable("tspeed", "90");';
        $script.='widget_so.addVariable("distr", "true");';
        $script.='widget_so.addVariable("mode", "tags");';
        $script.='widget_so.addVariable("tagcloud",encodeURIComponent("'.addcslashes($tags,'"').'"));';
        $script.='widget_so.write("cumulus");';
        $script.='</script>';

        return $script;
    }
    
    function jcloud()
    {
        $html='';
        
        //$this->sawanna->db->query("select count(*) as count from (select distinct keyword from pre_node_keywords) k");
        $this->sawanna->db->query("select count(*) as count from (select distinct k.keyword from pre_node_keywords k left join pre_nodes n on k.nid=n.id where n.language='$1') d",$this->sawanna->locale->current);
        $row=$this->sawanna->db->fetch_object();
        
        if (empty($row)) return;
        
        $offset=0;
        
        $count=$row->count;
        if ($count > $this->limit) $offset=mt_rand(0,$count-$this->limit);

        //$this->sawanna->db->query("select distinct keyword from pre_node_keywords limit $1 offset $2",$this->limit,$offset);
        $this->sawanna->db->query("select distinct k.keyword from pre_node_keywords k left join pre_nodes n on k.nid=n.id where n.language='$1' limit $2 offset $3",$this->sawanna->locale->current,$this->limit,$offset);
        
        while ($tag=$this->sawanna->db->fetch_object())
        {
            $tag->keyword=strip_tags($tag->keyword);
            
            if (preg_match('/[^a-zа-я0-9\x20\-_]/iu',$tag->keyword)) continue;
            
            if ($this->view != 'cumulus')
            {
                $html.='<li>'.$this->sawanna->constructor->link('tag/'.$tag->keyword,$tag->keyword,'jcloud-link').'</li>'."\n";
            } else
            {
                $html.=$this->sawanna->constructor->link('tag/'.urlencode($tag->keyword),$tag->keyword,'',array('style'=>'font-size:'.$this->size.'px'));
            }
        }
        
        if (empty($html)) return ;
        
        switch ($this->view)
        {
            case 'circle':
                $flats=1;
                $areal=180;
                $splitX=0;
                $splitY=0;
                break;
            case 'sphere':
                $flats=8;
                $areal=180;
                $splitX=0;
                $splitY=0;
                break;
            case 'spiral':
                $flats=18;
                $areal=180;
                $splitX=10;
                $splitY=10;
                break;
            case 'chaos':
                $flats=4;
                $areal=180;
                $splitX=50;
                $splitY=50;
                break;
            case 'rings':
                $flats=1;
                $areal=180;
                $splitX=50;
                $splitY=50;
                break;
            default:
                $flats=1;
                $areal=180;
                $splitX=0;
                $splitY=0;
                break;
        }
        
        if ($this->view != 'cumulus')
        {
            $html='<ul id="jcloud-list">'.$html.'</ul>'.$this->get_init_script($this->radius,$this->size,$flats,$areal,$splitX,$splitY);
        } else
        {
            $html='<div id="cumulus"></div>'.$this->get_flash_script($html);
        }
        
        return array(
            'content'=>$html,
            'title'=>'[str]Tags cloud[/str]'
            );
    }
}
