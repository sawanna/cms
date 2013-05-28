<?php
defined('SAWANNA') or die();

class CBreadcrumbs
{
    var $sawanna;
    var $menu;
    
    function CBreadcrumbs(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->menu=false;
        
        $this->sawanna->cache->set_clear_on_change('menu','breadcrumbs');
    }
    
    function breadcrumbs($query)
    {
        if ($query == $this->sawanna->config->front_page_path) return;
        if ($this->sawanna->is_mobile) return;
        
         $html='';
         if (isset($GLOBALS['menu']) && is_object($GLOBALS['menu'])) $this->menu=&$GLOBALS['menu'];
         if (!$this->menu) return ;
         
         $html='';
         
         $path='';
         $elements_tree=array();
         
         $path_arr=explode('/',$query);
         foreach($path_arr as $path_piece)
         {
            if (!empty($path)) $path.='/';
            $path.=$path_piece;
            
            $row=$this->menu->get_element_row_by_path($path);
            if (!empty($row))
            {
                $elements_tree[]=$row;
            }
         }
    
         foreach ($elements_tree as $elem)
         {
             if ($elem->path == '[home]' || $elem->path == $this->sawanna->config->front_page_path) continue;
             
             if ($elem->path!=$query)
                $html.='<li>'.$this->sawanna->constructor->link($elem->path,$elem->title,'breadcrumb').'</li>';
             else 
                $html.='<li><span class="breadcrumb">'.$elem->title.'</span></li>';
         }
                 
        if (empty($html)) return ;
     
        return '<ul class="breadcrumbs"><li>'.$this->sawanna->constructor->link('[home]','[str]Home[/str]','breadcrumb').'</li>'.$html.'</ul>';
    }
}
