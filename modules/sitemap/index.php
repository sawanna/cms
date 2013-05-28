<?php
defined('SAWANNA') or die();

class CSitemap
{
    var $sawanna;
    var $menu;
    var $start;
    var $limit;
    var $freq;
    var $change_priority;
    var $count;
    var $iter;
    var $item_string;
    var $offset_string;
    var $elements;
    
    function CSitemap(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->menu=false;
        $this->content=false;
        $this->start=0;
        $this->count=0;
        $this->elements=array();
        
        $limit=$this->sawanna->get_option('sitemap_limit');
        $this->limit=$limit && is_numeric($limit) && $limit>0 ? ceil($limit) : 100;
        
        $freqs=$this->get_freqs();
        $freq=$this->sawanna->get_option('sitemap_freq');
        $this->freq=$freq && in_array($freq,$freqs) ? $freq : 'daily';
        
        $prio=$this->sawanna->get_option('sitemap_decrease_priority');
        $this->change_priority=$prio ? true : false;
        
        $this->iter=0;
        
        $this->item_string='&rsaquo;&rsaquo;&nbsp;';
        $this->offset_string='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        
        $this->sawanna->cache->set_clear_on_change('content','sitemap');
        $this->sawanna->cache->set_clear_on_change('menu','sitemap');
    }
    
    function ready()
    {
        if (isset($GLOBALS['menu']) && is_object($GLOBALS['menu'])) $this->menu=&$GLOBALS['menu'];
        if($this->sawanna->query=='sitemap-xml' && $this->menu) 
        {
            $this->menu->exclude=array();
        }
        
        $this->start=!empty($this->sawanna->arg) && is_numeric($this->sawanna->arg) && $this->sawanna->arg>0 ? floor($this->sawanna->arg) : 0;
        
        if ($this->sawanna->module_exists('comments') && isset($GLOBALS['comments']) && is_object($GLOBALS['comments']) && isset($GLOBALS['comments']->disabled_pathes) && is_array($GLOBALS['comments']->disabled_pathes)) $GLOBALS['comments']->disabled_pathes[]='sitemap';
        
        if ($this->sawanna->query=='sitemap') $this->sawanna->tpl->default_title='[str]Sitemap[/str]';
    }
    
    function icon()
    {
        return array('link'=>$this->sawanna->constructor->link('sitemap',' ','sitemap',array('title'=>'[str]Sitemap[/str]')));
    }
    
    function get_elements_tree($language,$parent=0,$offset=0,$limit=0)
    {
        if ($limit && $offset>=$limit) return;
        
        $co=0;
        
        $elements=$this->menu->fetch_elements($language,$parent);
        foreach ($elements as $row)
        {
            if (!empty($row->permission) && !$this->sawanna->has_access($row->permission)) continue;

            if (in_array($row->path,$this->menu->unlink))
            {
                $row->path='#';
            }
            
            $row->offset=$offset;
            
            $this->elements[]=$row;
            
            $this->get_elements_tree($language,$row->id,$offset+1,$limit);
            $co++;
        }
        
        return;
    }
    
    function sitemap($arg='')
    {
         if (!$this->menu) return ;
         
        if (empty($this->elements))
            $this->get_elements_tree($this->sawanna->locale->current);
            
        $this->count=count($this->elements);
        
        $html='<ul class="sitemap">';
        
        foreach ($this->elements as $menu)
        {
            $this->iter++;
            if ($this->iter-1 < $this->start*$this->limit) continue;
            if ($this->iter > $this->start*$this->limit+$this->limit) break;
            
            $html.='<li><span>'.str_repeat($this->offset_string,$menu->offset).$this->item_string.'</span><a href="'.$this->sawanna->constructor->url($menu->path).'">'.$menu->title.'</a></li>';
        }
        
        $html.='</ul>';
        
        $pager=$this->sawanna->constructor->pager($this->start,$this->limit,$this->count,'sitemap');
        
        return array('sitemap'=>$html,'pager'=>$pager);
    }
    
    function sitemap_xml()
    {
        $xml='<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml.='<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($this->sawanna->config->languages as $language=>$lname)
        {
            $this->sawanna->db->query("select p.path, n.permission from pre_nodes n left join pre_node_pathes p on n.id=p.nid where n.language='$1' order by n.weight asc, n.pub_tstamp desc, n.created desc",$language);
            while ($menu=$this->sawanna->db->fetch_object())
            {
                if (!$this->sawanna->has_access($menu->permission)) continue;
                
                $menu->path=$this->sawanna->constructor->url($menu->path,false,false,true);
                
                if ($menu->path=='#') continue;
                if (!defined('BASE_PATH'))
                {
					if (!preg_match('/^'.addcslashes(quotemeta(SITE),'/').'/',$menu->path)) continue;
                } else
                {
					if (!preg_match('/^'.addcslashes(quotemeta(BASE_PATH),'/').'/',$menu->path)) continue;
				}
                
                $priority=1;
                
                $xml.='<url>'."\n";
                $xml.='<loc>';
                $xml.=htmlspecialchars($menu->path);
                $xml.='</loc>'."\n";
                $xml.='<changefreq>'.htmlspecialchars($this->freq).'</changefreq>'."\n";
                $xml.='<priority>'.number_format($priority,1).'</priority>'."\n";
                $xml.='</url>'."\n";
            }
        }
        
        $xml.='</urlset>';
        
        header('Content-type: application/xml');
        echo $xml;
    }
    
    function get_freqs()
    {
        return array(
            'always',
            'hourly',
            'daily',
            'weekly',
            'monthly',
            'yearly',
            'never'
        );
    }
}
