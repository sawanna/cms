<?php
defined('SAWANNA') or die();

class CMenuAdmin
{
    var $sawanna;
    var $module_id;
    var $menu_widget_name;
    var $menu_widget_class;
    var $menu_widget_handler;
    var $click_menu_widget_name;
    var $click_menu_widget_handler;
    var $parent_menu_widget_name;
    var $parent_menu_widget_handler;
    var $child_menu_widget_name;
    var $child_menu_widget_handler;
    var $exclude;
    var $start;
    var $limit;
    var $count;
    var $iter;
    
    function CMenuAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('menu');
        $this->menu_widget_name='menu';
        $this->menu_widget_class='CMenu';
        $this->menu_widget_handler='menu';
        $this->click_menu_widget_name='click_menu';
        $this->click_menu_widget_handler='click_menu';
        $this->parent_menu_widget_name='parent_menu';
        $this->parent_menu_widget_handler='parent_menu';
        $this->child_menu_widget_name='child_menu';
        $this->child_menu_widget_handler='child_menu';
        $this->start=0;
        $this->limit=10;
        $this->count=0;
        $this->iter=0;
        
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
    }
    
    function adminbar()
    {
        if (!$this->sawanna->has_access('create menu elements')) return;
            
        return array(
                        array(
                        'menu/create'=>'[str]New element[/str]',
                        'menu'=>'[str]Elements[/str]'
                        ),
                        'menu'
                        );
    }
    
    function optbar()
    {
        return  array(
                        'menu/settings'=>'[str]Menu[/str]'
                        );
    }
    
    function admin_area()
    {
        if (!$this->sawanna->has_access('create menu elements')) return ;
        
        $this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
        
        switch (true)
        {
            case $this->sawanna->arg=='menu/create':
                $this->menu_form();
                break;
            case $this->sawanna->arg=='menu/settings':
                $this->settings_form();
                break;
            case preg_match('/^menu\/edit\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->menu_form($matches[1]);
                break;
            case preg_match('/^menu\/delete\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->menu_delete($matches[1]);
                break;
			case preg_match('/^menu\/remove\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->menu_remove($matches[1]);
                break;
            case preg_match('/^menu\/bulk$/',$this->sawanna->arg,$matches):
                $this->bulk_action();
                break;
            case $this->sawanna->arg=='menu':
                $this->menu_list('all');
                break;
            case preg_match('/^menu\/([\d]+)$/',$this->sawanna->arg,$matches):
                if ($matches[1]>0) $this->start=floor($matches[1]);
                $this->menu_list('all');
                break;
            case preg_match('/^menu\/([a-zA-Z0-9_-]+)$/',$this->sawanna->arg,$matches) && (array_key_exists($matches[1],$this->sawanna->config->languages) || $matches[1]=='all'):
                $this->menu_list($matches[1]);
                break;
            case preg_match('/^menu\/([a-zA-Z0-9_-]+)\/([\d]+)$/',$this->sawanna->arg,$matches) && (array_key_exists($matches[1],$this->sawanna->config->languages) || $matches[1]=='all'):
                if ($matches[2]>0) $this->start=floor($matches[2]);
                $this->menu_list($matches[1]);
                break;
        }
    }
    
     function menu_path_check()
    {
        if (!isset($_POST['q'])) return;

        if (substr($_POST['q'],0,1)=='/') $_POST['q']=substr($_POST['q'],1);
        if (substr($_POST['q'],-1)=='/') $_POST['q']=substr($_POST['q'],0,strlen($_POST['q'])-1);
        
        if (!valid_path($_POST['q']) && !preg_match('/^(http|https):\/\/.+$/',$_POST['q']))
           echo $this->sawanna->locale->get_string('Invalid path');
    }
    
    function menu_path_exists($path,$language='')
    {
        $this->sawanna->db->query("select * from pre_menu_elements where path='$1' and language='$2'",$path,$language);
        
        return $this->sawanna->db->num_rows();
    }
    
    function element_exists($id,$language='')
    {
        if (empty($language))
            $this->sawanna->db->query("select * from pre_menu_elements where id='$1'",$id);
        else 
            $this->sawanna->db->query("select * from pre_menu_elements where id='$1' and language='$2'",$id,$language);
            
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return true;
        return false;
    }
    
    function get_existing_element($id)
    {
        $this->sawanna->db->query("select * from pre_menu_elements where id='$1'",$id);
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return $row;
    }
    
    function fetch_elements($language,$parent=null,$clear=false)
    {
        static $elements=array();
        static $elements_by_parent=array();
        
        if ($clear) 
        {
            $elements=array();
            $elements_by_parent=array();
        }
       
        if (empty($elements))
        {
            $co=0;
            $mem_limit=(int) ini_get('memory_limit');
     
            if (empty($this->exclude))
            {
                $this->sawanna->db->query("select * from pre_menu_elements where language='$1' order by weight, id",$language);
            } else
            {
                $not_like='';
                foreach($this->exclude as $exclude)
                {
                    $not_like.="and path not like '".$this->sawanna->db->escape_string(str_replace('*','%',trim($exclude)))."' ";
                }
                
                $this->sawanna->db->query("select * from pre_menu_elements where language='$1' ".$not_like." order by weight, id",$language);
            }
            while ($row=$this->sawanna->db->fetch_object())
            {
                if ($this->exclude($row->path)) continue;
                
                $elements[$co]=$row;
                
                if (!array_key_exists($row->parent,$elements_by_parent)) $elements_by_parent[$row->parent]=array();
                $elements_by_parent[$row->parent][]=$co;
                
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
        
        if ($parent!==null && array_key_exists($parent,$elements_by_parent))
        {
            foreach($elements_by_parent[$parent] as $ind)
            {
                $return[]=$elements[$ind];
            }
        }
        
        if ($parent===null)
        {
            $return=&$elements;
        }
        
        return $return;
    }
    
    // deprecated
    function _get_elements_tree($language,$appendix='',$suffix='',$offset_str='-',$parent=0,$offset=0,$clear=false)
    {
        static $menu=array();
        
        if ($clear) $menu=array();
        
        $_appendix='';
        $_suffix='';
        
        $res=$this->sawanna->db->query("select * from pre_menu_elements where parent='$1' and language='$2' order by weight",$parent,$language);
        while ($row=$this->sawanna->db->fetch_object($res))
        {
            if ($this->exclude($row->path)) continue;
            
            if (!empty($appendix))
            {
                $_appendix=str_replace('%id',$row->id,$appendix);
                $_appendix=str_replace('%path',$row->path,$_appendix);
                $_appendix=str_replace('%title',$row->title,$_appendix);
            }
            if (!empty($suffix))
            {
                $_suffix=str_replace('%id',$row->id,$suffix);
                $_suffix=str_replace('%path',$row->path,$_suffix);
                $_suffix=str_replace('%title',$row->title,$_suffix);
            }
            
            $menu[$row->id]=$_appendix.(str_repeat($offset_str,$offset)).$row->title.$_suffix;
            $this->get_elements_tree($language,$appendix,$suffix,$offset_str,$row->id,$offset+1);
        }
        
        return $menu;
    }
    
    function get_elements_tree($language,$appendix='',$suffix='',$offset_str='-',$parent=0,$offset=0,$clear=false)
    {
        static $menu=array();
        
        if ($clear) $menu=array();
        
        $_appendix='';
        $_suffix='';
        
        $elements=$this->fetch_elements($language,$parent,$clear);
        foreach ($elements as $row)
        {
            // now in fetch elements
            //if ($this->exclude($row->path)) continue;
            
            if (!empty($appendix))
            {
                $_appendix=str_replace('%id',$row->id,$appendix);
                $_appendix=str_replace('%path',$row->path,$_appendix);
                $_appendix=str_replace('%title',$row->title,$_appendix);
            }
            if (!empty($suffix))
            {
                $_suffix=str_replace('%id',$row->id,$suffix);
                $_suffix=str_replace('%path',$row->path,$_suffix);
                $_suffix=str_replace('%title',$row->title,$_suffix);
            }
            
            if ($row->enabled)
                $menu[$row->id]=$_appendix.(str_repeat($offset_str,$offset)).$row->title.$_suffix;
            else
                $menu[$row->id]=$_appendix.(str_repeat($offset_str,$offset)).'[str]Disabled[/str] ['.$row->title.']'.$_suffix;
            
            $this->get_elements_tree($language,$appendix,$suffix,$offset_str,$row->id,$offset+1);
        }
        
        return $menu;
    }
    
    function load_menu_tree()
    {
        if (empty($_POST['l'])) return;
        if (!array_key_exists($_POST['l'],$this->sawanna->config->languages)) return;
        
        echo '<option value="0">'.$this->sawanna->locale->get_string('Root').'</option>';
        
        $tree=$this->get_elements_tree($_POST['l'],'<option value="%id">','</option>','--',0,1);
        if (!empty($tree))
        {
            echo implode("",$tree);
        }
    }
    
    function load_menu_path()
    {
        if (empty($_POST['i'])) return;
        if (!$this->element_exists($_POST['i'])) return;
        
        $this->sawanna->db->query("select path from pre_menu_elements where id='$1'",$_POST['i']);
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row) && $row->path!='[home]') echo $row->path;
    }
    
    function load_tpl()
    {
        if (empty($_POST['i'])) return;
        if (!$this->element_exists($_POST['i'])) return;
        
        $this->sawanna->db->query("select nid, path from pre_menu_elements where id='$1'",$_POST['i']);
        $row=$this->sawanna->db->fetch_object();
        
        if (empty($row->nid) && !empty($row->path) && $row->path!='[home]')
        {
            $this->sawanna->db->query("select nid from pre_node_pathes where path like '$1/%'",$row->path);
            $row=$this->sawanna->db->fetch_object();
        }
        
        if (!empty($row->nid))
        {
            $node=$this->sawanna->node->get_existing_node($row->nid);
            if (!empty($node) && is_object($node))
            {
                $d=new CData();
                if (!empty($node->meta) && $d->is_serialized($node->meta)) $node->meta=$d->do_unserialize($node->meta);
                if (isset($node->meta['tpl'])) echo $node->meta['tpl'];
            }
        }
    }
    
    function menu_form($id=0)
    {
        if (!$this->sawanna->has_access('create menu elements')) 
        {
            trigger_error('Permission denied',E_USER_ERROR);
            return ;
        }
        
        $menu=new stdClass();
        
        if (!empty($id) && !is_numeric($id)) 
        {
            trigger_error('Invalid element ID: '.$id,E_USER_ERROR);
            return;
        }
        if (!empty($id))
        {
            if (!$this->element_exists($id)) 
            {
                trigger_error('Element not found',E_USER_ERROR);
                return;    
            }
            
            $menu=$this->get_existing_element($id);
        }
        
        $fields=array();
        
         $fields['menu-tab']=array(
            'type' => 'html',
            'value' => '<h1 class="tab">'.(!isset($menu->id) ? '[str]New element[/str]' : '[str]Edit element[/str]').'</h1>'
        );
        
        if (count($this->sawanna->config->languages)>1)
        {
            $fields['menu-language']=array(
                'type' => 'select',
                'options' => $this->sawanna->config->languages,
                'title' => '[str]Language[/str]',
                'description' => '[str]Please choose element language[/str]',
                'default' => isset($menu->language) ? $menu->language : $this->sawanna->locale->current,
                'attributes' => array('onchange'=>'menu_update_tree()'),
                'required' => true
            );
        }
        
        $fields['menu_tree_script']=array(
            'type' => 'html',
            'value' => '<script language="JavaScript">function menu_update_tree() {var lang=$("#menu-language").get(0).options[$("#menu-language").get(0).selectedIndex].value; $.post("'.$this->sawanna->constructor->url('menu-tree-load',true).'",{l:lang},function (data) {$("#menu-parent").html(data); $("#menu-parent").get(0).disabled=false; $("#submit").get(0).disabled=false;});  $("#menu-parent").get(0).disabled=true; $("#submit").get(0).disabled=true;}</script>'
        );
        
        $event=array(
            'url' => 'menu-tree-load',
            'caller' => 'menuAdminHandler',
            'job' => 'load_menu_tree',
            'constant' => 1
        );
        
        $this->sawanna->event->register($event);
        
        $fields['menu-title']=array(
            'type' => 'text',
            'title' => '[str]Title[/str]',
            'description' => '[str]Specify menu element title here[/str]',
            'attributes' => array('style'=>'width: 100%'),
            'default' => isset($menu->title) ? $menu->title : '',
            'required' => true
        );
        
        $menu_elements=$this->get_elements_tree(isset($menu->language) ? $menu->language : $this->sawanna->locale->current,'','','--',0,1);
        $menu_elements=array('0'=>'[str]Root[/str]')+$menu_elements;
        
        $fields['menu-parent']=array(
            'type' => 'select',
            'options' => $menu_elements,
            'title' => '[str]Parent[/str]',
            'description' => '[str]Choose menu parent element[/str]',
            'attributes' => array('onchange'=>'menu_update_path()'),
            'default' => isset($menu->parent) ? $menu->parent : '0' 
        );
        
        $fields['menu_path_script']=array(
            'type' => 'html',
            'value' => '<script language="JavaScript">function menu_update_path() {var parent=$("#menu-parent").get(0).options[$("#menu-parent").get(0).selectedIndex].value; $.post("'.$this->sawanna->constructor->url('menu-path-load',true).'",{i:parent},function (data) {var path_append=""; if (data.length>0) {path_append="/"}; $("#menu-path").get(0).value=data+path_append; $("#menu-path").get(0).disabled=false; $("#menu-parent").get(0).disabled=false; $("#submit").get(0).disabled=false; $("#menu-path").get(0).focus();});  $("#menu-path").get(0).disabled=true; $("#menu-parent").get(0).disabled=true; $("#submit").get(0).disabled=true;}</script>'
        );
        
        $event=array(
            'url' => 'menu-path-load',
            'caller' => 'menuAdminHandler',
            'job' => 'load_menu_path',
            'constant' => 1
        );
        
        $this->sawanna->event->register($event);
        
        $fields['menu-path']=array(
            'type' => 'text',
            'title' => '[str]URL path[/str]',
            'description' => '[str]Specify menu element URL path.[/str] [strf]Internal path example: %page/subpage%.[/strf] [strf]External path example: %http://somesite.com%.[/strf]',
            'autocheck' => array('url'=>'menu-path-check','caller'=>'menuAdminHandler','handler'=>'menu_path_check'),
            'default' => isset($menu->path) ? $menu->path : '',
            'attributes' => array('style'=>'width: 100%'),
            'required' => true
        );

        $fields['menu-published']=array(
            'type' => 'checkbox',
            'title' => '[str]Enabled[/str]',
            'description' => '[str]Element not shown on the website until enabled[/str]',
            'attributes' => !isset($menu->enabled) || $menu->enabled ? array('checked'=>'checked','class'=>'checkbox') : array('class'=>'checkbox'),
            'required' => false
        );

        /*
        $fields['menu-weight']=array(
            'type' => 'text',
            'title' => '[str]Weight[/str]',
            'description' => '[str]Specify the element weight here.[/str] [str]Lighter elements appear first[/str]',
            'default' => isset($menu->weight) ? $menu->weight :  '0',
            'required' => false
        );
        */
        
        $middle_weight=isset($menu->weight) ? $menu->weight : 0;
        
        $fields['menu-weight']=array(
            'type' => 'select',
            'options' => array_combine(range($middle_weight-30,$middle_weight+30,1),range($middle_weight-30,$middle_weight+30,1)),
            'title' => '[str]Weight[/str]',
            'description' => '[str]Specify the element weight here.[/str] [str]Lighter elements appear first[/str]',
            'default' => isset($menu->weight) ? $menu->weight :  '0',
            'attributes' => array('class'=>'slider'),
            'required' => false
        );
        
        $extra=$this->sawanna->form->get_form_extra_fields('menu_create_form_extra_fields',isset($menu->id) ? $menu->id : 0);
        
        if (!empty($extra))
        {
            $fields=array_merge($fields,$extra);
        }

        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('menu-form',$fields,'menu_form_process',$this->sawanna->config->admin_area.'/menu',empty($id) ? $this->sawanna->config->admin_area.'/menu/create' : $this->sawanna->config->admin_area.'/menu/edit/'.$id,'','CMenuAdmin');
    }
    
    function node_form_menu_title_extra_field($nid)
    {
        if (!$this->sawanna->has_access('create menu elements')) return;
        
        if (!empty($nid))
        {
            $this->sawanna->db->query("select * from pre_menu_elements where nid='$1'",$nid);
            $menu=$this->sawanna->db->fetch_object();
        }
        
        if (count($this->sawanna->config->languages)>1)
        {
            $fields['menu_tree_script']=array(
                'type' => 'html',
                'value' => '<script language="JavaScript">document.getElementById("node-language").disabled=true; $(document).ready( function () { document.getElementById("node-language").disabled=false; $("#node-language").get(0).onchange=function () { var lang=$("#node-language").get(0).options[$("#node-language").get(0).selectedIndex].value; $.post("'.$this->sawanna->constructor->url('menu-tree-load',true).'",{l:lang},function (data) {$("#menu-parent").html(data); $("#menu-parent").get(0).disabled=false; $("#submit").get(0).disabled=false;});  $("#menu-parent").get(0).disabled=true; $("#submit").get(0).disabled=true; }});</script>'
            );
        }

        $event=array(
            'url' => 'menu-tree-load',
            'caller' => 'menuAdminHandler',
            'job' => 'load_menu_tree',
            'constant' => 1
        );
        
        $this->sawanna->event->register($event);
        
        $fields['menu-title']=array(
            'type' => 'text',
            'title' => '[str]Menu title[/str]',
            'description' => '[str]Specify menu element title here[/str]',
            'attributes' => array('style'=>'width: 100%'),
            'default' =>  isset($menu->title) ? $menu->title : '',
            'required' => false
        );
        
        $menu_elements=$this->get_elements_tree(isset($menu->language) ? $menu->language : $this->sawanna->locale->current,'','','--',0,1);
        $menu_elements=array('0'=>'[str]Root[/str]')+$menu_elements;
        
        $fields['menu-parent']=array(
            'type' => 'select',
            'options' => $menu_elements,
            'title' => '[str]Parent[/str]',
            'description' => '[str]Choose menu parent element[/str]',
            'attributes' => array('onchange'=>'menu_update_path()'),
            'default' => isset($menu->parent) ? $menu->parent : '0' 
        );
        
        $fields['menu_path_script']=array(
            'type' => 'html',
            'value' => '<script language="JavaScript">function menu_update_path() {var parent=$("#menu-parent").get(0).options[$("#menu-parent").get(0).selectedIndex].value; $.post("'.$this->sawanna->constructor->url('menu-path-load',true).'",{i:parent},function (data) {var path_append=""; if (data.length>0) {path_append="/"}; $("#node-path").get(0).value=data+path_append; menu_update_tpl(); });  $("#node-path").get(0).disabled=true; $("#menu-parent").get(0).disabled=true; $("#submit").get(0).disabled=true; $("#node-tpl").get(0).disabled=true;}</script>'
        );
        
        $event=array(
            'url' => 'menu-path-load',
            'caller' => 'menuAdminHandler',
            'job' => 'load_menu_path',
            'constant' => 1
        );
        
        $this->sawanna->event->register($event);
        
        $fields['menu_tpl_script']=array(
            'type' => 'html',
            'value' => '<script language="JavaScript">function menu_update_tpl() {var parent=$("#menu-parent").get(0).options[$("#menu-parent").get(0).selectedIndex].value; $.post("'.$this->sawanna->constructor->url('menu-tpl-load',true).'",{i:parent},function (data) { if (data.length>0) { $("#node-tpl").val(data); $("#node-tpl").css({\'display\':\'block\'}); $("#node-tpl").get(0).disabled=false; $(\'#admin-area\').tabs(\'select\',1); refresh_select($(\'#node-tpl\')); $(\'#admin-area\').tabs(\'select\',0); }; $("#node-tpl").get(0).disabled=false; $("#node-path").get(0).disabled=false; $("#menu-parent").get(0).disabled=false; $("#submit").get(0).disabled=false; $("#node-path").get(0).focus();}); }</script>'
        );
        
        $event=array(
            'url' => 'menu-tpl-load',
            'caller' => 'menuAdminHandler',
            'job' => 'load_tpl',
            'constant' => 1
        );
        
        $this->sawanna->event->register($event);
        
        return $fields;
    }
    
    function node_form_menu_title_extra_field_validate($nid)
    {
        if (!$this->sawanna->has_access('create menu elements')) return;
        
        if (!empty($_POST['menu-title']))
        {
            $node_pathes=array();
        
            if (!empty($_POST['node-path'])) 
            {
                 $node_pathes[]=$_POST['node-path'];
            }
            
            foreach ($_POST as $key=>$value)
            {
                if (preg_match('/^node-path-([\d]+)$/',$key,$matches) && !in_array($matches[1],$node_pathes))
                {
                    $node_pathes[]=$value;
                }
            }
            
            if (empty($node_pathes))
            {
                return array('[str]URL path not specified[/str]',array('node-path'));
            }
            
            $id=0;
            if (!empty($nid))
            {
                $this->sawanna->db->query("select * from pre_menu_elements where nid='$1'",$nid);
                $menu=$this->sawanna->db->fetch_object();
                if (!empty($menu)) $id=$menu->id;
            }
            
            if (!empty($id) && $_POST['menu-parent']==$id)
            {
                return array('[str]Invalid parent element[/str]',array('menu-parent'));
            }
        }
    }
    
    function node_form_menu_title_extra_field_process($nid)
    {
        if (!$this->sawanna->has_access('create menu elements')) return;
        
        if (!empty($_POST['menu-title']))
        {
            $node_pathes=array();

            foreach ($_POST as $key=>$value)
            {
                if (preg_match('/^node-path-([\d]+)$/',$key,$matches) && !in_array($matches[1],$node_pathes))
                {
                    $node_pathes[]=$value;
                }
            }
            
            if (!empty($_POST['node-path'])) 
            {
                 $node_pathes[]=$_POST['node-path'];
            }

            $_POST['menu-path']=$node_pathes[0];
            
            if (isset($_POST['node-language'])) $_POST['menu-language']=$_POST['node-language'];
            if (isset($_POST['node-weight'])) $_POST['menu-weight']=$_POST['node-weight'];
            if (isset($_POST['node-published']) && $_POST['node-published']=='on') $_POST['menu-published']='on';
            
            $_POST['nid']=$nid;
            
            $id=0;
            if (!empty($nid))
            {
                $this->sawanna->db->query("select * from pre_menu_elements where nid='$1'",$nid);
                $menu=$this->sawanna->db->fetch_object();
                if (!empty($menu)) 
                {
                    $id=$menu->id;
                    
                    $_POST['menu-weight']=$menu->weight;
                    
                    if (in_array($menu->path,$node_pathes))  $_POST['menu-path']=$menu->path;
                    else 
                    {
                        $this->delete($id);
                        $id=0;
                    }
                }
            }
            
            $event=array(
                'url' => 'menu-tpl-load',
                'caller' => 'menuAdminHandler',
                'job' => 'load_tpl',
                'constant' => 1
            );
            
            $this->sawanna->event->remove($event);
        
            $this->menu_form_process($id);
        } else 
        {
            if (!empty($nid))
            {
                $this->sawanna->db->query("select * from pre_menu_elements where nid='$1'",$nid);
                $menu=$this->sawanna->db->fetch_object();
                if (!empty($menu)) 
                    $this->delete($menu->id);
            }
        }
    }
    
    function menu_form_process($id=0)
    {
        if (!$this->sawanna->has_access('create menu elements')) 
        {
            return array('[str]Permission denied[/str]',array('create-menu'));
        }

        if (empty($id) && preg_match('/^menu\/edit\/(.+)$/',$this->sawanna->arg,$matches)) $id=$matches[1];
        
        if (!empty($id) && !is_numeric($id)) return array('[strf]Invalid element ID: %'.$id.'%[/strf]',array('edit-menu'));
        if (!empty($id) && !$this->element_exists($id)) return array('[str]Element not found[/str]',array('edit-menu'));    
        
        if (substr($_POST['menu-path'],0,1)=='/') $_POST['menu-path']=substr($_POST['menu-path'],1);
        if (substr($_POST['menu-path'],-1)=='/') $_POST['menu-path']=substr($_POST['menu-path'],0,strlen($_POST['menu-path'])-1);
        
        $_POST['menu-path']=trim(str_replace(' ','-',$_POST['menu-path']),'-');
        
        if (!valid_path($_POST['menu-path']) && !preg_match('/^(http|https):\/\/.+$/',$_POST['menu-path']))
        {
            return array('[str]Invalid path[/str]',array('menu-path'));
        }
        
        $menu=array();
        
        if (!empty($id)) 
        {
            $menu['id']=$id;
            
            $row=$this->get_existing_element($id);
            $menu['nid']=$row->nid;
            $menu['path']=$row->path;
        }
        
        if ((empty($id) || $menu['path']!=$_POST['menu-path']) && !preg_match('/^(http|https):\/\/.+$/',$_POST['menu-path']) && $this->menu_path_exists($_POST['menu-path'],!empty($_POST['menu-language']) ? $_POST['menu-language'] : $this->sawanna->locale->current))
        {
            return array('[str]URL path already in use[/str]',array('menu-path'));
        }
     
        if (!empty($_POST['menu-parent']) && !$this->element_exists($_POST['menu-parent'],!empty($_POST['menu-language']) ? $_POST['menu-language'] : $this->sawanna->locale->current))
        {
            return array('[str]Invalid parent element[/str]',array('menu-parent'));
        }
        
        if (!empty($id) && $_POST['menu-parent']==$id)
        {
            return array('[str]Invalid parent element[/str]',array('menu-parent'));
        }

        $menu['title']=strip_tags($_POST['menu-title']);
        $menu['path']=$_POST['menu-path'];
        if (!empty($_POST['menu-parent'])) $menu['parent']=$_POST['menu-parent'];
        
        if (!empty($_POST['menu-language']) && array_key_exists($_POST['menu-language'],$this->sawanna->config->languages))
            $menu['language']=$_POST['menu-language'];
        
        if (isset($_POST['menu-published']) && $_POST['menu-published']=='on') 
            $menu['enabled']=1;
        
        if (isset($_POST['menu-weight']) && is_numeric($_POST['menu-weight'])) $menu['weight']=floor($_POST['menu-weight']);

        if (empty($id) && empty($menu['weight']))
        {
            $menu['weight']=$this->get_next_weight($menu);
        }

        if (!empty($_POST['nid']))
            $menu['nid']=$_POST['nid'];

        if (!$this->set($menu))
        {
            return array('[str]Menu element could not be saved[/str]',array('new-menu'));
        }
        
        if (empty($id))
        {
            $this->sawanna->db->last_insert_id('menu_elements','id');
            $row=$this->sawanna->db->fetch_object();
            $menu['id']=$row->id;
        }
        
        $event=array(
            'url' => 'menu-tree-load',
            'caller' => 'menuAdminHandler',
            'job' => 'load_menu_tree',
            'constant' => 1
        );
        
        $this->sawanna->event->remove($event);
        
        $event=array(
            'url' => 'menu-path-load',
            'caller' => 'menuAdminHandler',
            'job' => 'load_menu_path',
            'constant' => 1
        );
        
        $this->sawanna->event->remove($event);
        
        // clearing cache
        if ($this->sawanna->cache->clear_on_change('menu'))
        {
            foreach ($this->sawanna->cache->clear_on_change['menu'] as $type)
            {
                $this->sawanna->cache->clear_all_by_type($type);
            }
        }
        
        $this->sawanna->cache->clear_all_by_type('menu');
        
        // processing extra fields
        $res=$this->sawanna->form->process_form_extra_fields('menu_create_form_extra_fields',$menu['id']);
        if (is_array($res) && !empty($res)) return $res;
        
        return array('[str]Menu element saved[/str]');
    }
    
    function get_next_weight($menu)
    {
        if (!isset($menu['language'])) $menu['language']=$this->sawanna->locale->current;
        if (!isset($menu['parent'])) $menu['parent']=0;
        
        $this->sawanna->db->query("select weight from pre_menu_elements where parent='$1' and language='$2' order by weight desc",$menu['parent'],$menu['language']);
        $row=$this->sawanna->db->fetch_object();
        
        if (empty($row)) return 0;
        
        return $row->weight+1;
    }
    
    function set($menu)
    {
        if (empty($menu) || !is_array($menu)) return false;
        
        if (empty($menu['path']))
        {
            trigger_error('Cannot set menu element. Empty path given',E_USER_WARNING);
            return false;
        }
        
        if (!isset($menu['language'])) $menu['language']=$this->sawanna->locale->current;
        if (!isset($menu['title'])) $menu['title']='&nbsp;';
        if (!isset($menu['parent'])) $menu['parent']=0;
        if (!isset($menu['weight'])) $menu['weight']=0;
        if (!isset($menu['enabled'])) $menu['enabled']=0;
        if (!isset($menu['nid'])) $menu['nid']=0;
        
        if (!preg_match('/^http:\/\/.+$/',$menu['path']))
        {
            $path_ex=$this->sawanna->navigator->get($menu['path'],'',false,false);
            if (empty($path_ex)) 
            {
                $this->sawanna->navigator->set(array('path'=>$menu['path'],'enabled'=>1,'render'=>1,'module'=>$this->module_id));
                $this->sawanna->navigator->set(array('path'=>$menu['path'].'/d','enabled'=>1,'render'=>1,'module'=>$this->module_id));
            }
        }
              
        if (isset($menu['id']))
        {
            return $this->sawanna->db->query("update pre_menu_elements set path='$1', language='$2', title='$3', parent='$4', weight='$5', enabled='$6', nid='$7' where id='$8'",$menu['path'],$menu['language'],$menu['title'],$menu['parent'],$menu['weight'],$menu['enabled'],$menu['nid'],$menu['id']);
        } else 
        {
            if ($menu['nid']>0) $this->sawanna->db->query("delete from pre_menu_elements where path='$1' and nid>'0' and language='$2'",$menu['path'],$menu['language']);
            return $this->sawanna->db->query("insert into pre_menu_elements (path,language,title,parent,weight,enabled,nid) values ('$1','$2','$3','$4','$5','$6','$7')",$menu['path'],$menu['language'],$menu['title'],$menu['parent'],$menu['weight'],$menu['enabled'],$menu['nid']);
        }
    }
    
    function delete($id)
    {
        if (empty($id)) return false;

        $this->sawanna->db->query("select parent,path from pre_menu_elements where id='$1'",$id);
        $row=$this->sawanna->db->fetch_object();
        
        if ($this->sawanna->db->query("delete from pre_menu_elements where id='$1'",$id))
        {
            $this->sawanna->db->query("update pre_menu_elements set parent='$1' where parent='$2'",$row->parent,$id);
            return true;
        }
        
        return false;
    }
    
    function enable($id)
    {
        if (empty($id)) return false;

        return $this->sawanna->db->query("update pre_menu_elements set enabled='1' where id='$1'",$id);
    }
    
    function disable($id)
    {
        if (empty($id)) return false;

        return $this->sawanna->db->query("update pre_menu_elements set enabled='0' where id='$1'",$id);
    }
    
    function swap($id1,$id2)
    {
        $this->sawanna->db->query("select parent,weight from pre_menu_elements where id='$1'",$id1);
        $row=$this->sawanna->db->fetch_object();
        if (!isset($row->weight)) return false;
        
        $p1=$row->parent;
        $w1=$row->weight;
        
        $this->sawanna->db->query("select parent,weight from pre_menu_elements where id='$1'",$id2);
        $row=$this->sawanna->db->fetch_object();
        if (!isset($row->weight)) return false;
        
        $p2=$row->parent;
        $w2=$row->weight;
        
        if ($p1!=$p2 || $w1==$w2) return false;
        
        $this->sawanna->db->query("update pre_menu_elements set weight='$1' where id='$2'",$w2,$id1);
        $this->sawanna->db->query("update pre_menu_elements set weight='$1' where id='$2'",$w1,$id2);
        
        return true;
    }
    
    function menu_delete($id)
    {
        if (!$this->sawanna->has_access('create menu elements')) 
        {
            trigger_error('Could not delete menu element. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not delete menu element. Invalid element ID: '.$id,E_USER_WARNING);
            return ;
        }
        if (!$this->element_exists($id)) 
        {
            trigger_error('Could not delete menu element. Element not found',E_USER_WARNING);    
            return;
        }
        
        $event=array();
        
        if (!$this->delete($id))
        {
            $message='[str]Could not delete menu element. Database error[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
            $message='[strf]Menu element with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
            $event['job']='sawanna_message';
            
            // clearing cache
            if ($this->sawanna->cache->clear_on_change('menu'))
            {
                foreach ($this->sawanna->cache->clear_on_change['menu'] as $type)
                {
                    $this->sawanna->cache->clear_all_by_type($type);
                }
            }
            
            $this->sawanna->cache->clear_all_by_type('menu');
            
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);
        
        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/menu');
    }
    
    function menu_remove($id)
    {
        if (!$this->sawanna->has_access('create menu elements')) 
        {
            trigger_error('Could not delete menu element. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not delete menu element. Invalid element ID: '.$id,E_USER_WARNING);
            return ;
        }
        if (!$this->element_exists($id)) 
        {
            trigger_error('Could not delete menu element. Element not found',E_USER_WARNING);    
            return;
        }
        
        $event=array();
        $element=$this->get_existing_element($id);
        
        if (!$this->delete($id))
        {
            $message='[str]Could not delete menu element. Database error[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
			$this->sawanna->navigator->delete($element->path);
			$this->sawanna->navigator->delete($element->path.'/d');
		
            $message='[strf]Menu element with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
            $event['job']='sawanna_message';
            
            // clearing cache
            if ($this->sawanna->cache->clear_on_change('menu'))
            {
                foreach ($this->sawanna->cache->clear_on_change['menu'] as $type)
                {
                    $this->sawanna->cache->clear_all_by_type($type);
                }
            }
            
            $this->sawanna->cache->clear_all_by_type('menu');
            
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);
        
        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/menu');
    }
    
    function bulk_action()
    {
        if (empty($_POST['action']) || !$this->sawanna->has_access('create menu elements'))
        {
            echo 'Permission denied';
            die();
        }
        
        switch ($_POST['action'])
        {
            case 'del':
                $this->bulk_delete();
                break;
			case 'remove':
                $this->bulk_remove();
                break;
            case 'enable':
                $this->bulk_enable();
                break;
            case 'disable':
                $this->bulk_disable();
                break;
            case 'swap':
                $this->swap_elements();
                break;
        }
    }
    
    function bulk_delete()
    {
        if (!$this->sawanna->has_access('create menu elements')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
                $this->delete(trim($id));
            }
            
            // clearing cache
            if ($this->sawanna->cache->clear_on_change('menu'))
            {
                foreach ($this->sawanna->cache->clear_on_change['menu'] as $type)
                {
                    $this->sawanna->cache->clear_all_by_type($type);
                }
            }
            
            $this->sawanna->cache->clear_all_by_type('menu');
            
            echo $this->sawanna->locale->get_string('Menu elements were deleted');
        } else
        {
            echo $this->sawanna->locale->get_string('Menu elements were not deleted');
        }
        
        die();
    }
    
    function bulk_remove()
    {
        if (!$this->sawanna->has_access('create menu elements')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
				$id=trim($id);
				$element=$this->get_existing_element($id);
				
                $this->delete($id);
                
                $this->sawanna->navigator->delete($element->path);
				$this->sawanna->navigator->delete($element->path.'/d');
            }
            
            // clearing cache
            if ($this->sawanna->cache->clear_on_change('menu'))
            {
                foreach ($this->sawanna->cache->clear_on_change['menu'] as $type)
                {
                    $this->sawanna->cache->clear_all_by_type($type);
                }
            }
            
            $this->sawanna->cache->clear_all_by_type('menu');
            
            echo $this->sawanna->locale->get_string('Menu elements were deleted');
        } else
        {
            echo $this->sawanna->locale->get_string('Menu elements were not deleted');
        }
        
        die();
    }
    
    function bulk_enable()
    {
        if (!$this->sawanna->has_access('create menu elements')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
                $this->enable(trim($id));
            }
            
            // clearing cache
            if ($this->sawanna->cache->clear_on_change('menu'))
            {
                foreach ($this->sawanna->cache->clear_on_change['menu'] as $type)
                {
                    $this->sawanna->cache->clear_all_by_type($type);
                }
            }
            
            $this->sawanna->cache->clear_all_by_type('menu');
            
            echo $this->sawanna->locale->get_string('Menu elements were enabled');
        } else
        {
            echo $this->sawanna->locale->get_string('Menu elements were not enabled');
        }
        
        die();
    }
    
    function bulk_disable()
    {
        if (!$this->sawanna->has_access('create menu elements')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
                $this->disable(trim($id));
            }
            
            // clearing cache
            if ($this->sawanna->cache->clear_on_change('menu'))
            {
                foreach ($this->sawanna->cache->clear_on_change['menu'] as $type)
                {
                    $this->sawanna->cache->clear_all_by_type($type);
                }
            }
            
            $this->sawanna->cache->clear_all_by_type('menu');
            
            echo $this->sawanna->locale->get_string('Menu elements were disabled');
        } else
        {
            echo $this->sawanna->locale->get_string('Menu elements were not disabled');
        }
        
        die();
    }
    
    function swap_elements()
    {
        if (!$this->sawanna->has_access('create menu elements')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            if (count($ids) != 2)
            {
                echo $this->sawanna->locale->get_string('Two elements required');
                die();
            }
            
            if (!$this->swap(trim($ids[0]),trim($ids[1])))
            {
                echo $this->sawanna->locale->get_string('Could not swap elements. Maybe they have the same weight or different parents.');
                die();
            }
            
            // clearing cache
            if ($this->sawanna->cache->clear_on_change('menu'))
            {
                foreach ($this->sawanna->cache->clear_on_change['menu'] as $type)
                {
                    $this->sawanna->cache->clear_all_by_type($type);
                }
            }
            
            $this->sawanna->cache->clear_all_by_type('menu');
            
            echo $this->sawanna->locale->get_string('Menu elements were swaped');
        } else
        {
            echo $this->sawanna->locale->get_string('Menu elements were not swaped');
        }
        
        die();
    }
    
    function menu_list($language)
    {
        if (!$this->sawanna->has_access('create menu elements')) 
        {
            trigger_error('Permission denied',E_USER_WARNING);
            return ;
        }
        
        $html='<h1 class="tab">[str]Menu elements[/str]</h1>';
        $html.='<div id="menu-list">';
        
        if (count($this->sawanna->config->languages)>1)
        {
            $html.='<div id="menu-lang">';
            $html.='<div class="form_title">[str]Language[/str]:</div>';
            $html.='<select name="language" id="menu-language" onchange="menu_select_lang()">';
            
            if ($language=='all')
                $html.='<option value="all" selected>[str]All languages[/str]</option>';
            else 
                $html.='<option value="all">[str]All languages[/str]</option>';
                
            foreach ($this->sawanna->config->languages as $lcode=>$lname)
            {
                if ($lcode==$language)
                    $html.='<option value="'.$lcode.'" selected>'.$lname.'</option>';
                else 
                    $html.='<option value="'.$lcode.'">'.$lname.'</option>';
            }
            $html.= '</select>';
            $html.='<script language="JavaScript">function menu_select_lang() { window.location.href="'.$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/menu').'/"+$("#menu-language").get(0).options[$("#menu-language").get(0).selectedIndex].value; }</script>';
            $html.='</div>';
        }
        
        $html.='<div class="menu-list-item head">';
        $html.='<div class="id"></div>';
        $html.='<div class="title">[str]Title[/str]</div>';
        $html.='</div>';
            
        $menu=array();
        
        if ($language=='all')
        {
            foreach ($this->sawanna->config->languages as $lcode=>$lname)
            {
                $_menu=$this->get_elements_tree($lcode,'','','-- ',0,0,true);
                $this->count+=count($_menu);
                $menu+=$_menu;
            }
        } else
        {
            $menu=$this->get_elements_tree($language,'','','-- ',0,0,true);
            $this->count=count($menu);
        }
        
        $this->build_elements_tree($lcode,$html,$menu);
        
        if ($this->count)
        {
            $html.='<div class="clear">&nbsp;</div>';
            $html.='<div class="bulk_block">';
            $html.='<a href="#" id="bulk_id_selector" class="bulk_select_all">[str]Select all[/str]</a>';
            $html.='<select id="bulk_action_selector" name="bulk_action_selector" class="bulk_select" onchange="exec_bulk_action()"><option value="">[str]Choose action[/str]</option><option value="swap">[str]Swap elements[/str]</option><option value="enable">[str]Enable[/str]</option><option value="disable">[str]Disable[/str]</option><option value="del">[str]Delete[/str]</option><option value="remove">[str]Delete with URL-addresses[/str]</option></select>';
            $html.='</div>';
            
            $html.="<script language=\"javascript\">$(document).ready(function(){ $('a#bulk_id_selector').toggle(function() { $('input[type=checkbox].bulk_id_checkbox').attr('checked','checked'); }, function() { $('input[type=checkbox].bulk_id_checkbox').removeAttr('checked'); }); });</script>";
            $html.="<script language=\"javascript\">function exec_bulk_action(){ var act=$('#bulk_action_selector').val(); var ids=''; if (act.length>0) { $('input[type=checkbox].bulk_id_checkbox').each(function(){ if ($(this).get(0).checked==true) { if (ids.length>0){ids=ids+',';}; ids=ids+$(this).val(); } }); if (ids.length>0 && confirm('[str]Are you sure ?[/str]')) { $.post('".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/menu/bulk')."',{bulk_ids:ids,action:act},function(data){ alert(data); window.location.href='".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/'.$this->sawanna->arg)."'; }); $('#bulk_action_selector').get(0).disabled=true; } } $('#bulk_action_selector').get(0).selectedIndex=0; refresh_select($('#bulk_action_selector')); }</script>";
        
			$html.='<div id="delete-prompt-wnd"><div><input type="checkbox" name="url-inaccessible" id="url-inaccessible" checked="checked" /><label for="url-inaccessible">[str]Make URL-address inaccessible[/str]</label></div></div>';
			$html.='<script language="JavaScript">$(document).ready(function() { window.setTimeout(\'try { $("#delete-prompt-wnd").dialog({ autoOpen: false, modal: false, resizable: false, title: "[str]Are you sure ?[/str]", buttons: { "[str]Cancel[/str]": function() { $(this).dialog("close"); },"[str]Delete[/str]": do_delete_menu } }); } catch (e) { alert ("Error. Prompt window not initialized");} \',300); });function prompt_delete(mid){prompted_menu_id=mid;$("#delete-prompt-wnd").dialog("open");};function do_delete_menu(){var mid=prompted_menu_id;var checked=$("#url-inaccessible").get(0).checked;if(checked){window.location.href="'.$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/menu/remove').'/"+mid;}else{window.location.href="'.$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/menu/delete').'/"+mid;}};</script>';
		}
        
        $html.=$this->sawanna->constructor->pager($this->start,$this->limit,$this->count,$this->sawanna->config->admin_area.'/menu/'.$language);
        
        $html.='</div>';
        
        echo $html;
    }
    
    function build_elements_tree($language,&$html,&$menu)
    {
        static $co=0;
        
        foreach($menu as $id=>$title)
        {
            $this->iter++;
            if ($this->iter-1 < $this->start*$this->limit) continue;
            if ($this->iter > $this->start*$this->limit+$this->limit) break;
            
            if ($co%2===0)
            {
               $html.='<div class="menu-list-item">';
            } else 
            {
               $html.='<div class="menu-list-item mark highlighted">';
            }
            
            $html.='<div class="id"><input type="checkbox" name="bulk_id[]" value="'.$id.'" class="bulk_id_checkbox" /></div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/menu/delete/'.$id,'[str]Delete[/str]','button',array('onclick'=>'prompt_delete('.$id.');return false')).'</div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/menu/edit/'.$id,'[str]Edit[/str]','button').'</div>';
            $html.='<div class="title">'.htmlspecialchars($title).'</div>';
            $html.='</div>';
            
            $co++;
        }
    }
    
     function settings_form()
    {
        if (!$this->sawanna->has_access('su')) 
        {
            trigger_error('Permission denied',E_USER_ERROR);
            return ;
        }
        
        $fields=array();
        
         $fields['settings-tab']=array(
            'type' => 'html',
            'value' => '<h1 class="tab">[str]Menu settings[/str]</h1>'
        );
        
        $highlight=$this->sawanna->get_option('menu_highlight_parents');
        
        $fields['settings-highlight']=array(
            'type' => 'checkbox',
            'title' => '[str]Highlight parent elements[/str]',
            'description' => '[str]Menu parent elements will be highlighted if enabled[/str]',
            'attributes' => $highlight ? array('checked'=>'checked') : array()
        );
        
        $effect=$this->sawanna->get_option('menu_drop_effect');
        
        $fields['settings-effect']=array(
            'type' => 'select',
            'options' => array('slide'=>'Slide','fade'=>'Fade'),
            'title' => '[str]Drop down menu effect[/str]',
            'description' => '[str]Choose effect for drop down menu[/str]',
            'default' => $effect ? $effect : 'fade'
        );
        
        $unlink=$this->sawanna->get_option('menu_unlink_pathes');
        
        $fields['settings-unlink']=array(
            'type' => 'textarea',
            'title' => '[str]Empty links[/str]',
            'description' => '[str]Specify the pathes that should not be displayed as links[/str] ([strf]For example: %page/subpage%[/strf])',
            'attributes' => array('style'=>'width: 100%','rows'=>10,'cols'=>40),
            'default' => $unlink ? $unlink : ''
        );
        
        $exclude=$this->sawanna->get_option('menu_exclude_pathes');
        
        $examples='<div class="examples-title">[str]Examples[/str]:</div>';
        $examples.='<ul class="examples">';
        $examples.='<li class="example"><span class="path">page/subpage</span> - [strf]exclude element with following path: %page/subpage%[/strf]</li>';
        $examples.='<li class="example"><span class="path">page/*</span> - [strf]exclude all elements which pathes start from: %page/%[/strf]</li>';
        $examples.='</ul>';
        
        $fields['settings-exclude']=array(
            'type' => 'textarea',
            'title' => '[str]Exclude pathes[/str]',
            'description' => '[str]Specify the pathes that should not be displayed in menu[/str]'.$examples,
            'attributes' => array('style'=>'width: 100%','rows'=>10,'cols'=>40),
            'default' => $exclude ? $exclude : ''
        );

        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('settings-form',$fields,'settings_form_process',$this->sawanna->config->admin_area.'/menu/settings',$this->sawanna->config->admin_area.'/menu/settings','','CMenuAdmin');
    }
    
    function settings_form_process()
    {
        if (!$this->sawanna->has_access('su')) 
        {
            return array('[str]Permission denied[/str]',array('create-menu'));
        }
        
        if (isset($_POST['settings-highlight']) && $_POST['settings-highlight']=='on')
        {
            $this->sawanna->set_option('menu_highlight_parents',1,$this->module_id);
        } else 
        {
            $this->sawanna->set_option('menu_highlight_parents',0,$this->module_id);
        }
        
         if (isset($_POST['settings-effect']) && ($_POST['settings-effect']=='slide' || $_POST['settings-effect']=='fade'))
        {
            $this->sawanna->set_option('menu_drop_effect',$_POST['settings-effect'],$this->module_id);
        } else 
        {
            $this->sawanna->set_option('menu_drop_effect','fade',$this->module_id);
        }
        
        if (isset($_POST['settings-exclude']))
        {
            $_POST['settings-exclude']=strip_tags($_POST['settings-exclude']);
            $this->sawanna->set_option('menu_exclude_pathes',$_POST['settings-exclude'],$this->module_id);
        }
        
        if (isset($_POST['settings-unlink']))
        {
            $_POST['settings-unlink']=strip_tags($_POST['settings-unlink']);
            $this->sawanna->set_option('menu_unlink_pathes',$_POST['settings-unlink'],$this->module_id);
        }
        
        return array('[str]Settings saved[/str]');
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
}
