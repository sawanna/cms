<?php
defined('SAWANNA') or die();

class CMenu
{
    var $sawanna;
    var $menu;
    var $html;
    var $root_id;
    var $elements_tree;
    var $module_id;
    var $exclude;
    var $highlight_parent;
    var $unlink;
    
    function CMenu(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('menu');
        $this->menu=array();
        $this->html='';
        $this->root_id=0;
        $this->elements_tree=array();
        
        if ($this->sawanna->query != $this->sawanna->config->admin_area) $this->sawanna->tpl->add_script(quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/js/fix');
        
        $this->exclude=array();
        $exclude=$this->sawanna->get_option('menu_exclude_pathes');
        if ($exclude) 
        {
            $_exclude=explode("\n",$exclude);
            foreach ($_exclude as $path)
            {
                $this->exclude[]=trim($path);
            }
        }
        
        $this->unlink=array();
        $unlink=$this->sawanna->get_option('menu_unlink_pathes');
        if ($unlink) 
        {
            $_unlink=explode("\n",$unlink);
            foreach ($_unlink as $path)
            {
                $this->unlink[]=trim($path);
            }
        }
        
        $this->highlight_parent=$this->sawanna->get_option('menu_highlight_parents');

        $effect=$this->sawanna->get_option('menu_drop_effect');
        $drop_down_menu_widget_id=$this->sawanna->module->get_widget_id('drop_down_menu');
        if ($this->sawanna->query != $this->sawanna->config->admin_area && $drop_down_menu_widget_id && isset($this->sawanna->module->widgets[$drop_down_menu_widget_id]) && $this->sawanna->module->widgets[$drop_down_menu_widget_id]->enabled)
        {
            if ($effect && $effect=='slide')
            {
                $this->sawanna->tpl->add_script(quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/js/jquery-droppy');
                $this->sawanna->tpl->add_header('<script type="text/javascript">$(document).ready(function() { $(function() {$(".drop-down-menu-list").droppy();});}); </script>');
               
                if (file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/'.quotemeta($this->sawanna->module->themes_root).'/'.quotemeta($this->sawanna->config->theme).'/droppy.css'))
                    $this->sawanna->tpl->add_css(quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/'.quotemeta($this->sawanna->module->themes_root).'/'.quotemeta($this->sawanna->config->theme).'/droppy');
                else 
                    $this->sawanna->tpl->add_css(quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/'.quotemeta($this->sawanna->module->themes_root).'/'.quotemeta($this->sawanna->tpl->default_theme).'/droppy');
            } else 
            {
                $this->sawanna->tpl->add_script(quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/js/fade-menu');
           
                if (file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/'.quotemeta($this->sawanna->module->themes_root).'/'.quotemeta($this->sawanna->config->theme).'/fade.css'))
                    $this->sawanna->tpl->add_css(quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/'.quotemeta($this->sawanna->module->themes_root).'/'.quotemeta($this->sawanna->config->theme).'/fade');
                else 
                    $this->sawanna->tpl->add_css(quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/'.quotemeta($this->sawanna->module->themes_root).'/'.quotemeta($this->sawanna->tpl->default_theme).'/fade');
            }
        }
        
        if ($this->sawanna->query != $this->sawanna->config->admin_area) $this->register_title();
    }
    
    function parent_menu($query,$class='parent-menu-list')
    {
        if (empty($this->root_id)) $this->get_element_tree($query);
        
        $this->menu=array();
        $this->html='';
        $html='';
        
        $this->get_elements_tree($this->sawanna->locale->current,'',0,0,1,true);
        if (!empty($this->html))
        {
            $html.='<ul class="'.strip_tags($class).'">';
            $html.=$this->html."\n";
            $html.='</ul>';
        }
        
        return array('parent-menu'=>$html);
    }
    
    function child_menu($query,$class='child-menu-list')
    {
        if (empty($query) || $query==$this->sawanna->config->front_page_path) $query='[home]';
        
        if (empty($this->root_id)) $this->get_element_tree($query);
        
        $this->menu=array();
        $this->html='';
        $html='';
        
        if (empty($this->root_id)) return;

        $this->get_elements_tree($this->sawanna->locale->current,'',$this->root_id,0,0,true,true);
        if (!empty($this->html))
        {
            $html.='<ul class="'.strip_tags($class).'">';
            $html.=$this->html."\n";
            $html.='</ul>';
        }

        return array('child-menu'=>$html);
    }
    
    function child_menu_expanded($query,$class='child-menu-list')
    {
        if (empty($query)) $query='[home]';
        
        if (empty($this->root_id)) $this->get_element_tree($query);
        
        $this->menu=array();
        $this->html='';
        $html='';
        
        if (empty($this->root_id)) return;

        $this->get_elements_tree($this->sawanna->locale->current,'',$this->root_id,0,0,true,false);
        if (!empty($this->html))
        {
            $html.='<ul class="'.strip_tags($class).'">';
            $html.=$this->html."\n";
            $html.='</ul>';
        }
        
        return array('child-menu'=>$html);
    }
    
    function fetch_elements($language,$parent=null,$id=null,$path=null)
    {
        static $elements=array();
        static $elements_by_parent=array();
        static $elements_by_id=array();
        static $elements_by_path=array();
        
        if (empty($elements))
        {
            $co=0;
            $mem_limit=(int) ini_get('memory_limit');
           
            if (empty($this->exclude))
            {
                $this->sawanna->db->query("select m.id,m.parent,m.path,m.title,m.language,m.nid,m.weight,m.enabled,n.permission from pre_menu_elements m left join pre_navigation n on m.path=n.path where m.language='$1' and m.enabled<>'0' order by m.weight,m.id",$language);
            } else
            {
                $not_like='';
                foreach($this->exclude as $exclude)
                {
                    $not_like.="and m.path not like '".$this->sawanna->db->escape_string(str_replace('*','%',trim($exclude)))."' ";
                }
                
                $this->sawanna->db->query("select m.id,m.parent,m.path,m.title,m.language,m.nid,m.weight,m.enabled,n.permission from pre_menu_elements m left join pre_navigation n on m.path=n.path where m.language='$1' and m.enabled<>'0' ".$not_like." order by m.weight,m.id",$language);
            }
            
            while ($row=$this->sawanna->db->fetch_object())
            {
                if ($this->exclude($row->path)) continue;
                
                $elements[$co]=$row;
                
                if (!array_key_exists($row->parent,$elements_by_parent)) $elements_by_parent[$row->parent]=array();
                $elements_by_parent[$row->parent][]=$co;
                
                $elements_by_id[$row->id]=$co;
                
                $elements_by_path[$row->path]=$co;
                
                $co++;
                
                if (function_exists('memory_get_usage'))
                {
                    $memory=ceil(intval(memory_get_usage())/1024/1024);
                    if ($memory >= $mem_limit-4 && $co>500)
                    {
                        trigger_error('Could not load menu tree. Memory limit reached. Breaking...',E_USER_WARNING);
                        break;
                    }
                }
            }
        }
   
        $return=array();
        
        if ($parent!==null && $id===null && $path===null && array_key_exists($parent,$elements_by_parent))
        {
            foreach($elements_by_parent[$parent] as $ind)
            {
                $return[]=$elements[$ind];
            }
        }
        
        if ($id!==null && $parent===null && $path===null && array_key_exists($id,$elements_by_id))
        {
                $return=$elements[$elements_by_id[$id]];
        }
        
        if ($path!==null && $parent===null && $id===null && array_key_exists($path,$elements_by_path))
        {
                $return=$elements[$elements_by_path[$path]];
        }
        
        if ($parent===null && $id===null && $path===null)
        {
            $return=&$elements;
        }
        
        return $return;
    }
    
    function get_element_row_by_path($query)
    {
        $row=$this->fetch_elements($this->sawanna->locale->current,null,null,$query);
        
        return $row;
    }
    
    function get_element_tree($query)
    {
        $row=$this->get_element_row_by_path($query);
        
        if (empty($row)) 
        {
            if ($p=strrpos($query,'/'))
            {
                $query=substr($query,0,$p);
                $this->get_element_tree($query);
            }
            
            return;
        }
        
        $this->get_parent_element($row->id,$row->parent);
    }
    
    function get_parent_element($id,$parent)
    {
        $this->elements_tree[]=$id;
  
        if ($parent==0) 
        {
            $this->root_id=$id;
            return;
        }
        
        $row=$this->fetch_elements($this->sawanna->locale->current,null,$parent);
        if (!empty($row)) 
        {
            $this->get_parent_element($row->id,$row->parent);
        }
    }
    
    function get_element($id)
    {
        static $rows=array();
        
        if (empty($id)) return;
        
        if (!array_key_exists($id,$rows))
        {
            $this->sawanna->db->query("select * from pre_menu_elements where id='$1'",$id);
            $row=$this->sawanna->db->fetch_object();
            
             if (in_array($row->path,$this->unlink))
             {
                 $row->path='#';
             }
             
             $rows[$id]=$row;
        }
        
        return array_key_exists($id,$rows) ? $rows[$id] : false;
    }
    
    function children_menu($query,$class='child-menu-list')
    {
        if (empty($query)) $query='[home]';
        
        if (empty($this->root_id)) $this->get_element_tree($query);
        
        $this->menu=array();
        $this->html='';
        $html='';
        
        $row=$this->get_element_row_by_path($query);
        
        if (empty($row)) return ;
        
        $this->get_elements_tree($this->sawanna->locale->current,'',$row->id,0,1,true);
        if (!empty($this->html))
        {
            $html.='<ul class="'.strip_tags($class).'">';
            $html.=$this->html."\n";
            $html.='</ul>';
        }
        
        return array('child-menu'=>$html);
    }
    
    function menu($query,$class='menu-list',$offset=0,$limit=0)
    {
        if (empty($this->root_id)) $this->get_element_tree($query);
        
        $this->menu=array();
        $this->html='';
        $html='';
        
        $this->get_elements_tree($this->sawanna->locale->current,'',0,$offset,$limit,true,false,true);
        if (!empty($this->html))
        {
            $html.='<ul class="'.strip_tags($class).'">';
            $html.=$this->html."\n";
            $html.='</ul>';
        }
        
        return array('main-menu'=>$html);
    }
    
    function drop_down_menu($query,$class='drop-down-menu-list')
    {
        $html=$this->menu($query,$class);

        return array('drop-down-menu'=>$html['main-menu']);
    }

    function get_elements_tree($language,$offset_str='-',$parent=0,$offset=0,$limit=0,$list=true,$filter=false,$addspan=true)
    {
        if ($limit && $offset>=$limit) return;
        
        $co=0;
        
        $elements=$this->fetch_elements($language,$parent);
        foreach ($elements as $row)
        {
            // now in fetch_elements
            //if ($this->exclude($row->path)) continue;
            
            if ($filter && !in_array($row->parent,$this->elements_tree) && $row->parent!=$this->root_id) continue;
            
            if (!empty($row->permission) && !$this->sawanna->has_access($row->permission)) continue;
            
            if ($offset && !$co) $this->html.="\n".str_repeat("    ",$offset).'<ul>';
            
            $active='';
            if ($this->sawanna->query==$row->path) $active=' class="active"';
            // fixed
            //elseif (empty($this->sawanna->query) && $row->path=='[home]') $active=' class="active"';
            elseif ($this->sawanna->query == $this->sawanna->config->front_page_path && $row->path=='[home]') $active=' class="active"';
            elseif($this->highlight_parent && in_array($row->id,$this->elements_tree)) $active=' class="parent"';
            
            $row->title=str_repeat($offset_str,$offset).$row->title;
            
            $attr='';
            /*
            if (!in_array($row->path,$this->unlink))
            {
                if (!preg_match('/^(http|https):\/\/.+$/',$row->path))
                {
                    $row->query=$row->path;
                    
                    $attr='';
                } else 
                {
                    $attr=' target="_blank"';
                }
            } else 
            */
            if (in_array($row->path,$this->unlink))
            {
                $row->path='#';
            }
            
            $row->offset=$offset;
            
            // deprecated
            //$this->menu[]=$row;
            $this->html.="\n".str_repeat("    ",$offset).'<li'.$active.' id="menu-element-li-'.$row->id.'">';
            
            if ($addspan)
                $this->html.="\n".str_repeat("    ",$offset+1).'<a id="menu-element-a-'.$row->id.'" href="'.$this->sawanna->constructor->url($row->path).'"'.$active.$attr.'><span>'.$row->title.'</span></a>';
            else
                $this->html.="\n".str_repeat("    ",$offset+1).'<a id="menu-element-a-'.$row->id.'" href="'.$this->sawanna->constructor->url($row->path).'"'.$active.$attr.'>'.$row->title.'</a>';
                
            $this->get_elements_tree($language,$offset_str,$row->id,$offset+1,$limit,$list,$filter,$addspan);
            $this->html.="\n".str_repeat("    ",$offset).'</li>';
            
            $co++;
        }
        if ($offset && $co) $this->html.="\n".str_repeat("    ",$offset).'</ul>';
        
        return;
    }
    
    function exclude($path)
    {
        if (empty($this->exclude)) return false;
        
        if (in_array($path,$this->exclude)) return true;
        
        foreach ($this->exclude as $exclude)
        {
            if (substr($exclude,-2)=='/*') $exclude=substr($exclude,0,strlen($exclude)-1);
            if (strpos($path,$exclude)===0) return true;
        }
        
        return false;
    }
    
    function register_title()
    {
        if (empty($this->sawanna->query)) return ;
        
        //$this->sawanna->db->query("select * from pre_menu_elements where path='$1' and language='$2'",$this->sawanna->query,$this->sawanna->locale->current);
        //$row=$this->sawanna->db->fetch_object();
        $row=$this->get_element_row_by_path($this->sawanna->query);
        
        if (empty($row)) return ;
        
        if (!empty($row->nid)) return ;
        
        $this->sawanna->tpl->default_title=$row->title;
    }
}
