<?php
defined('SAWANNA') or die();

class CModule
{
    var $modules;
    var $widgets;
    var $widget_fixes;
    var $admin_area_handlers;
    var $db;
    var $config;
    var $locale;
    var $tpl;
    var $access;
    var $navigator;
    var $path;
    var $root_dir;
    var $themes_root;
    var $languages_root;
    var $module_css_file;
    var $argument_tokens;
    var $duty_paths;
    var $try_install_anyway;
    var $is_mobile;
    
    function CModule(&$db,&$config,&$locale,&$tpl,&$access,&$navigator,$path,$is_mobile)
    {
        $this->db=$db;
        $this->config=$config;
        $this->locale=$locale;
        $this->tpl=$tpl;
        $this->modules=array();
        $this->widgets=array();
        $this->widget_fixes=array();
        $this->options=array();
        $this->access=$access;
        $this->navigator=$navigator;
        $this->path=$path;
        $this->root_dir='modules';
        $this->admin_area_handlers=array();
        $this->themes_root='themes';
        $this->languages_root='languages';
        $this->try_install_anyway=true;
        
        $this->argument_tokens=array();
        
        $this->duty_paths=array($this->config->admin_area,
                                                $this->config->login_area,
                                                $this->config->logout_area,
                                                $this->config->pwd_recover_area,
                                                'captcha',
                                                'file',
                                                'image',
                                                'ping',
                                                'cron',
                                                'css'
                                                );
                                                
        $this->is_mobile=$is_mobile;
        
        $this->module_css_file=$this->is_mobile ? 'mobile' : 'style';
    }
    
    function init()
    {
        $this->modules=$this->get_modules_table();
        $this->widgets=$this->get_widgets_table();
        $this->options=$this->get_options_table();
        
        $this->check_modules_install();
    }
    
    function check_modules_install()
    {
        if (empty($this->modules)) return ;
        
        foreach ($this->modules as $id=>$module)
        {
            if (!$module->enabled) continue;
            
            if (!file_exists(ROOT_DIR.'/'.$this->root_dir.'/'.$module->root)) 
            {
                trigger_error('Module '.htmlspecialchars($module->name).' not found. Deactivating it ...',E_USER_WARNING);
                $this->deactivate($module,'module');
            }
            
            if (!empty($module->depend))
            {
                $depend=explode(',',$module->depend);
                foreach ($depend as $dependency)
                {
                    $dependency_id=$this->get_module_id_by_root(trim($dependency));
                    if (!array_key_exists($dependency_id,$this->modules) || !$this->modules[$dependency_id]->enabled)
                    {
                        trigger_error('Module`s dependency `'.htmlspecialchars($dependency).'` not found. Deactivating module '.htmlspecialchars($module->name).' ...',E_USER_WARNING);
                        $this->deactivate($module,'module');
                        break;
                    }
                }
            }
        }
    }
    
    function get_modules_table()
    {
        $table=array();

        if (is_object($this->db))
        {
            $this->db->query("select * from pre_modules order by package asc, title asc");
            while($row=$this->db->fetch_object())
            {
                $table[$row->id]=$row;
            }
        }
        
        return $table;
    }
    
    function get_module_id($module_name)
    {
        if (empty($module_name)) return 0;
        
        foreach ($this->modules as $id=>$module)
        {
            if ($module->name==$module_name)
            {
                return $module->id;
            }
        }
        
        return 0;
    }
    
    function get_module_id_by_root($module_root)
    {
        if (empty($module_root)) return 0;
        
        foreach ($this->modules as $id=>$module)
        {
            if ($module->root==$module_root)
            {
                return $module->id;
            }
        }
        
        return 0;
    }
    
    function get_widgets_table()
    {
        $weights=array();
        $table=array();
        
        $d=new CData();
        
        if (is_object($this->db))
        {
            $this->db->query("select w.id, w.name, w.class, w.handler, w.argument, w.title, w.description, w.path, w.component, w.weight, w.permission, w.module, w.enabled, w.cacheable, w.fix, w.mark from pre_widgets w left join pre_modules m on w.module=m.id where m.enabled<>'0' order by w.weight");
            while($row=$this->db->fetch_object())
            {
                $table[$row->id]=$row;
                $weights[$row->id]=$row->weight;
            }
            
            $this->db->query("select * from pre_widgets where module='0' order by weight");
            while($row=$this->db->fetch_object())
            {
                $table[$row->id]=$row;
                $weights[$row->id]=$row->weight;
            }
        }
        
        asort($weights);
        
        $res_table=array();
        foreach ($weights as $id=>$weight)
        {
            if (!empty($table[$id]->mark) && $d->is_serialized($table[$id]->mark))
            {
                $table[$id]->meta=$d->do_unserialize($table[$id]->mark);
            }
            
            $res_table[$id]=$table[$id];
        }
        
        return $res_table;
    }
    
    function get_module_widgets_table_by_name()
    {
        $table=array();
        
        $d=new CData();
        
        if (is_object($this->db))
        {
            $this->db->query("select w.id, w.name, w.class, w.handler, w.argument, w.title, w.description, w.path, w.component, w.weight, w.permission, w.module, w.enabled, w.cacheable, w.fix, w.mark from pre_widgets w left join pre_modules m on w.module=m.id where m.enabled<>'0' order by w.name");
            while($row=$this->db->fetch_object())
            {
				if (!empty($row->mark) && $d->is_serialized($row->mark))
				{
					$row->meta=$d->do_unserialize($row->mark);
				}
            
                $table[$row->id]=$row;
            }
        }
        
        return $table;
    }
    
    function load_widgets()
    {
        $this->widgets=array();
        
        $widgets=$this->get_widgets_table();
        
        foreach($widgets as $wid=>$widget)
        {
            if (isset($this->widget_fixes[$widget->name]) && is_array($this->widget_fixes[$widget->name]))
            {
                foreach ($this->widget_fixes[$widget->name] as $property=>$new_value)
                {
                    $widget->{$property}=$new_value;
                }
            }
             if (in_array($this->path,$this->duty_paths) && $widget->module && !$this->strict_path($widget->path)) continue;
            
            if (!$this->check_path($widget->path)) continue;
            if (!$this->access->has_access($widget->permission)) continue;
            
            if (count($this->config->languages)>1 && !empty($widget->meta['language']))
            {
                if ($widget->meta['language'] != $this->locale->current) continue;
            }
            
            $this->widgets[$wid]=$widget;
        }
    }
    
    function get_widget_id($widget_name)
    {
        if (empty($widget_name)) return 0;
        
        foreach ($this->widgets as $id=>$widget)
        {
            if ($widget->name==$widget_name)
            {
                return $widget->id;
            }
        }
        
        return 0;
    }
    
    function get_options_table()
    {
        $table=array();
        
        $language_options=array();
        $current_language_options=array();
        $admin_area_handlers_options=array();
        
        foreach ($this->modules as $module)
        {
            foreach ($this->locale->languages as $language=>$language_name)
            {
                $language_options[]=$module->name.'_language_'.$language;
                if ($language==$this->locale->current) $current_language_options[]=$module->name.'_language_'.$language;
            }
            $admin_area_handlers_options[]=$module->name.'_admin_area_class';
        }
        
        if (is_object($this->db))
        {
            $this->db->query("select o.id, o.name, o.value, o.module, o.mark from pre_options o left join pre_modules m on o.module=m.id where m.enabled<>'0' or m.id is null");
            while($row=$this->db->fetch_object())
            {
                if (in_array($row->name,$current_language_options))
                {
                    $this->import_language($row);
                    continue;
                }
                if (in_array($row->name,$language_options)) continue;
                
                if (in_array($row->name,$admin_area_handlers_options)) $this->admin_area_handlers[$row->module]=$row->value;
                if ($row->mark == 'store') continue;
                
                $table[$row->name]=$row;
            }
        }
        
        return $table;
    }
    
    function refresh_options()
    {
        $this->options=$this->get_options_table();
    }
    
    function get_option($name)
    {
        if (empty($name)) return;
        
        if(isset($this->options[$name]))
        {
            $d=new CData();
            if ($d->is_serialized($this->options[$name]->value))
                return $d->do_unserialize($this->options[$name]->value);
            else
                return $this->options[$name]->value;
        } else return;
    }
    
    function set_option($name,$value,$module_id)
    {
        if (empty($name)) return false;
        
        if (is_numeric($value) || is_string($value)) 
        {
            $this->options[$name]=new stdClass();
            $this->options[$name]->value=$value;
            
            return $this->set(array('name'=>$name,'value'=>$value,'module'=>$module_id),'option');
        } elseif (is_array($value))
        {
            $this->options[$name]=new stdClass();
            $this->options[$name]->value=$value;
            
            $d=new CData();
            return $this->set(array('name'=>$name,'value'=>$d->do_serialize($value),'module'=>$module_id),'option');
        } else 
        {
            trigger_error('Could not set '.htmlspecialchars($name).' option. Invalid data type given',E_USER_WARNING);
            return false;
        }
    }
    
    function remove_option($name)
    {
        return $this->delete($name,'option');
    }
    
    function get_component_widgets($component)
    {
        if (empty($component)) return;
        
        $widgets=array();
        foreach ($this->widgets as $widget)
        {
            if (!isset($widget->component)) continue;
            if (!$widget->enabled) continue;

            if ($widget->component == $component)
                $widgets[]=$widget;
                
            if ($component==$this->tpl->duty && empty($widget->component))
                $widgets[]=$widget;
                
            if (strpos($widget->component,';')!==false)
            {
                $wc=explode(';',$widget->component);
                if (in_array($component,$wc)) 
                    $widgets[]=$widget;
                 elseif ($component==$this->tpl->duty && in_array('',$wc))
                    $widgets[]=$widget;
            }
        }
        
        return $widgets;
    }
    
    function get_module_widgets($module_name)
    {
        $module_id=0;
        foreach ($this->modules as $id=>$module)
        {
            if ($module->name==$module_name)
            {
                $module_id=$id;
                break;
            }
        }
        
        if (empty($module_id)) return array();
        
        $widgets=array();
        foreach ($this->widgets as $wid=>$widget)
        {
            if ($widget->module==$module_id)
            {
                $widgets[$wid]=$widget;
            }
        }
        
        return $widgets;
    }
    
    function get_widgets_by_module_id(&$widgets,$module_id,$show_fixed=true)
    {
        if (empty($module_id)) return array();
        
        $_widgets=array();
        foreach ($widgets as $wid=>$widget)
        { 
            if (!$show_fixed && !$widget->module) continue;
            if (!$show_fixed && $widget->fix) continue;
            
            if ($widget->module==$module_id)
            {
                $_widgets[$wid]=$widget;
            }
        }
        
        return $_widgets;
    }
    
    function check_path($path)
    {  
         if (strpos($path,"\n")!==false)
        {
            $path=str_replace("\r",'',$path);
            $pathes=explode("\n",$path);
            foreach ($pathes as $_path)
            {
                if ($_path=='[home]') $_path=$this->config->front_page_path;
                if ($_path=='![home]') $_path='!'.$this->config->front_page_path;
                
                if ($_path=='*') return true;
                if ($this->path == $_path) return true;
                if (substr($_path,0,1)=='!' && ($this->path==substr($_path,1) || substr($_path,1)=='*')) return false;
                if (!empty($_path) && substr($_path,-2)=='/*')
                {
                    $_path=substr($_path,0,strlen($_path)-1);
                    if (strpos($this->path,$_path)===0) return true;
                    if (substr($_path,0,1)=='!' && strpos($this->path,substr($_path,1))===0) return false;
                }
            }
        } else 
        {
            if ($path=='[home]') $path=$this->config->front_page_path;
            if ($path=='![home]') $path='!'.$this->config->front_page_path;
            
            if ($path=='*') return true;
            if ($this->path == $path) return true;
            if (substr($path,0,1)=='!' && ($this->path==substr($path,1) || substr($path,1)=='*')) return false;
            if (!empty($path) && substr($path,-2)=='/*')
            {
                $_path=substr($path,0,strlen($path)-1);
                if (strpos($this->path,$_path)===0) return true;
                if (substr($_path,0,1)=='!' && strpos($this->path,substr($_path,1))===0) return false;
            }
        }
        
        return false;
    }
    
    function strict_path($path)
    {  
         if (strpos($path,"\n")!==false)
        {
            $path=str_replace("\r",'',$path);
            $pathes=explode("\n",$path);
            foreach ($pathes as $_path)
            {
                if ($_path=='[home]') $_path='';

                if ($this->path == $_path) return true;
                if (!empty($_path) && substr($_path,-2)=='/*')
                {
                    $_path=substr($_path,0,strlen($_path)-1);
                    if (strpos($this->path,$_path)===0) return true;
                }
            }
        } else 
        {
            if ($path=='[home]') $path='';

            if ($this->path == $path) return true;
            if (!empty($path) && substr($path,-2)=='/*')
            {
                $_path=substr($path,0,strlen($path)-1);
                if (strpos($this->path,$_path)===0) return true;
            }
        }
        
        return false;
    }
    
    function fix($path)
    {
        $this->path=$path;
    }
    
    function get_widget_template_name(&$widget)
    {
        if (!empty($widget->tpl)) return $widget->tpl;
        
        return $widget->name;
    }
    
    function include_modules($modules)
    {
        if (empty($modules) || !is_array($modules)) return false;
        
        foreach ($modules as $module)
        {
            if (!isset($module->root)) continue;
            if (!$module->enabled) continue;
            
            if (file_exists(ROOT_DIR.'/'.$this->root_dir.'/'.quotemeta($module->root).'/index.php'))
            {
                include_once(ROOT_DIR.'/'.$this->root_dir.'/'.quotemeta($module->root).'/index.php');
                
                $this->preload_module_language($module->root);
            } else 
                trigger_error('Module '.htmlspecialchars($module->root).' not found',E_USER_WARNING);
        }
        
        return true;
    }
    
    function include_admin_handlers($modules)
    {
        if (empty($modules) || !is_array($modules)) return false;
        
        foreach ($modules as $module)
        {
            if (!isset($module->root)) continue;
            if (!$module->enabled) continue;
            
             if (file_exists(ROOT_DIR.'/'.$this->root_dir.'/'.quotemeta($module->root).'/admin.php'))
             {
                 include_once(ROOT_DIR.'/'.$this->root_dir.'/'.quotemeta($module->root).'/admin.php');
             }
        }
        
        return true;
    }

    function add_modules_css($modules)
    {
        if (empty($modules) || !is_array($modules)) return false;

        $css_output='';
        
        foreach ($modules as $module)
        {
            $theme=$this->config->theme;
            if ($this->config->theme!=$this->tpl->default_theme && !file_exists(ROOT_DIR.'/'.$this->root_dir.'/'.quotemeta($module->root).'/'.quotemeta($this->themes_root).'/'.quotemeta($this->config->theme).'/'.quotemeta($this->module_css_file).'.css'))
                $theme=$this->tpl->default_theme;
            
            if ($this->path!=$this->config->admin_area && file_exists(ROOT_DIR.'/'.$this->root_dir.'/'.quotemeta($module->root).'/'.quotemeta($this->themes_root).'/'.quotemeta($theme).'/'.quotemeta($this->module_css_file).'.css'))
                $this->tpl->add_css($this->root_dir.'/'.quotemeta($module->root).'/'.quotemeta($this->themes_root).'/'.quotemeta($theme).'/'.quotemeta($this->module_css_file));
           
            if ($this->config->cache_css && $this->path!=$this->config->admin_area && file_exists(ROOT_DIR.'/'.$this->root_dir.'/'.quotemeta($module->root).'/'.quotemeta($this->themes_root).'/'.quotemeta($theme).'/'.quotemeta($this->module_css_file).'.css')) 
            {
                $output=file_get_contents(ROOT_DIR.'/'.$this->root_dir.'/'.quotemeta($module->root).'/'.quotemeta($this->themes_root).'/'.quotemeta($theme).'/'.quotemeta($this->module_css_file).'.css')."\n";
                $this->tpl->fix_css_urls($output,'/'.$this->root_dir.'/'.quotemeta($module->root).'/'.quotemeta($this->themes_root).'/'.quotemeta($theme).'/'.quotemeta($this->module_css_file).'.css');
                $css_output.=$output;
            }
        }
          
         return $css_output;       
    }
    
    function get_module_by_class($class)
    {
        foreach ($this->widgets as $id=>$widget)
        {
            if (!$widget->module) continue;
            
            if ($widget->class==$class) 
            {
                return $this->modules[$widget->module];
            }
        }
        
        return false;
    }
    
    function get_widget_object_name($widget)
    {
        $name='';
        
        if (is_object($widget))
        {
            if (!isset($widget->class)) return false;
            
            $name=$widget->class;
        } elseif (is_string($widget)) 
        {
            $name=$widget;
        }
        
        if (substr($name,0,1)=='C') 
        {
            $name=substr($name,1);
            return strtolower(substr($name,0,1)).substr($name,1);
        }
            
        return $name;
    }
    
    function create_modules_objects($modules)
    {
        if (empty($modules) || !is_array($modules)) return false;
        
        foreach ($modules as $module)
        {
           if (!isset($module->root)) continue;
           if (!$module->enabled) continue;
            
           if (empty($module->name)) continue;
            
            if (isset($GLOBALS[$module->name]) && is_object($GLOBALS[$module->name])) continue;
            
            $module_class_name='C'.ucwords($module->name);
            
            if (class_exists($module_class_name))
                $GLOBALS[$module->name]=new $module_class_name($GLOBALS['sawanna']);
        }
        
        return true;
    }
    
    function modules_ready($modules)
    {
        if (empty($modules) || !is_array($modules)) return false;
        
        foreach ($modules as $module)
        {
           if (!isset($module->root)) continue;
           if (!$module->enabled) continue;
            
           if (empty($module->name)) continue;
            
            if (!isset($GLOBALS[$module->name]) || !is_object($GLOBALS[$module->name]) || !method_exists($GLOBALS[$module->name],'ready')) continue;
            
            $GLOBALS[$module->name]->ready();
        }
        
        return true;
    }
    
    function create_widgets_objects($widgets)
    {
        if (empty($widgets) || !is_array($widgets)) return false;
        
        foreach ($widgets as $widget)
        {
           // if (empty($widget->module)) continue;
           
           $name=$this->get_widget_object_name($widget);
           if ($name===false) continue;
            
            if (isset($GLOBALS[$name]) && is_object($GLOBALS[$name])) continue;
            
            if (class_exists($widget->class))
                $GLOBALS[$name]=new $widget->class($GLOBALS['sawanna']);
            else 
                trigger_error('Cannot create widget object. Class '.htmlspecialchars($widget->class).' not found',E_USER_WARNING);
        }
        
        return true;
    }
    
    function create_admin_handlers($modules)
    {
        foreach ($modules as $module)
        {
            if (!$module->enabled) continue;
            
            if (!array_key_exists($module->id,$this->admin_area_handlers)) continue;
            
            if (class_exists($this->admin_area_handlers[$module->id]))
                    $GLOBALS[$module->name.'AdminHandler']=new $this->admin_area_handlers[$module->id]($GLOBALS['sawanna']);
                else 
                    trigger_error('Cannot create module admin-handler object. Class '.htmlspecialchars($this->admin_area_handlers[$module->id]).' not found',E_USER_WARNING);
        }
        
        return true;
    }
    
    // deprecated
    function import_language(&$option)
    {
        return;
        
        if (empty($option) || !is_object($option)) return false;
        if (!isset($option->name) || !isset($option->value)) return false;
        
        $d=new CData();
        $phrases=$d->do_unserialize($option->value);
        if (empty($phrases) || !is_array($phrases)) return false;
        
        $this->locale->phrases=array_merge($this->locale->phrases,$phrases);
    }
    
    function exec_widget($widget)
    {
        if (!is_object($widget) || !isset($widget->handler)) return false;
        $name=$this->get_widget_object_name($widget);
        if ($name===false) return false;
        if (!isset($GLOBALS[$name]) || !is_object($GLOBALS[$name])) return false;
        if (method_exists($GLOBALS[$name],$widget->handler))
        {
            $html='';
            
            if (empty($widget->argument))
                $result=$GLOBALS[$name]->{$widget->handler}();
            else 
            {
                if (array_key_exists($widget->argument,$this->argument_tokens))
                    $result=$GLOBALS[$name]->{$widget->handler}($this->argument_tokens[$widget->argument]);
                else
                    $result=$GLOBALS[$name]->{$widget->handler}($widget->argument);
            }
            
            if (!empty($result) && is_array($result))
            {
                $widget_tpl=$this->get_widget_template_name($widget);
                
                if (!isset($result['widget_items']) || !is_array($result['widget_items']))
                    $html.=$this->render_template($result,$widget_tpl,$this->modules[$widget->module]->root);
                else 
                {
                    if (isset($result['widget_prefix'])) $html.=$result['widget_prefix'];
                    
                    foreach ($result['widget_items'] as $item_num=>$wresult)
                    {
                        $_widget_tpl=(isset($result['widget_tpls']) && isset($result['widget_tpls'][$item_num])) ? $result['widget_tpls'][$item_num] : '';
                        
                        if (!empty($wresult) && is_array($wresult))
                        {
                            $html.=$this->render_template($wresult,!empty($_widget_tpl) ? $_widget_tpl : $widget_tpl,$this->modules[$widget->module]->root);
                        } elseif (!is_array($wresult)) 
                            $html.=$wresult;
                    }
                    
                    if (isset($result['widget_suffix'])) $html.=$result['widget_suffix'];
                }
            } elseif (!is_array($result)) 
                $html.=$result;

             return !empty($html) ? "\n<!--".$widget->name.'-->'."\n".$html."\n<!--/".$widget->name.'-->'."\n" : $html;
        } else 
        {
            trigger_error('Cannot execute widget '.htmlspecialchars($widget->name).'. Handler method '.htmlspecialchars($widget->handler).' not found',E_USER_WARNING);
            return false;
        }
    }
    
    function set($item,$type)
    {
        switch ($type)
        {
            case 'widget':    
                if (empty($item) || !is_array($item)) return false;
                if (!isset($item['name']) || !isset($item['class']) || !isset($item['handler']) || !isset($item['title']) || !isset($item['description']) || !isset($item['component'])|| !isset($item['module'])) return false;
        
                if (!isset($item['path'])) $item['path']='*';
                if (!isset($item['weight']) || !is_numeric($item['weight'])) $item['weight']=0;
                if (!isset($item['permission'])) $item['permission']='';
                if (!isset($item['enabled'])) $item['enabled']=0;  
                if (!isset($item['argument'])) $item['argument']=''; 
                if (!isset($item['cacheable'])) $item['cacheable']=0; 
                if (!isset($item['fix'])) $item['fix']=0; 
                
                $d=new CData();
                if (isset($item['meta']) && is_array($item['meta'])) 
                    $item['meta']=$d->do_serialize($item['meta']);
                else 
                    $item['meta']='';
                
                $this->db->query("select class from pre_widgets where name='$1'",$item['name']);
                if ($this->db->num_rows())
                {
                    if (isset($item['id']))
                    {
                        if (!$this->db->query("update pre_widgets set class='$1', handler='$2', title='$3', description='$4', path='$5', component='$6', weight='$7', permission='$8', enabled='$9', cacheable='$10', argument='$11', fix='$12', mark='$13' where id='$14'",$item['class'],$item['handler'],$item['title'],$item['description'],$item['path'],$item['component'],$item['weight'],$item['permission'],$item['enabled'],$item['cacheable'],$item['argument'],$item['fix'],$item['meta'],$item['id']))
                            return false;
                    } else 
                    {
                         if (!$this->db->query("update pre_widgets set class='$1', handler='$2', title='$3', description='$4', path='$5', component='$6', weight='$7', permission='$8', enabled='$9', cacheable='$10', argument='$11', fix='$12', mark='$13' where name='$14' and module='$15'",$item['class'],$item['handler'],$item['title'],$item['description'],$item['path'],$item['component'],$item['weight'],$item['permission'],$item['enabled'],$item['cacheable'],$item['argument'],$item['fix'],$item['meta'],$item['name'],$item['module']))
                            return false;
                    }
                } else 
                {
                    if (!$this->db->query("insert into pre_widgets (name,class,handler,title,description,path,component,weight,permission,enabled,module,cacheable,argument,fix,mark) values ('$1','$2','$3','$4','$5','$6','$7','$8','$9','$10','$11','$12','$13','$14','$15')",$item['name'],$item['class'],$item['handler'],$item['title'],$item['description'],$item['path'],$item['component'],$item['weight'],$item['permission'],$item['enabled'],$item['module'],$item['cacheable'],$item['argument'],$item['fix'],$item['meta']))
                        return false;
                }
                break;
            case 'module':
                if (empty($item) || !is_array($item)) return false;
                if (!isset($item['name']) || !isset($item['root']) || !isset($item['title']) || !isset($item['description'])) return false;
        
                if (!isset($item['enabled'])) $item['enabled']=0;
                if (!isset($item['depend'])) $item['depend']='';
                if (!isset($item['package'])) $item['package']='';
                
                $this->db->query("select root from pre_modules where name='$1'",$item['name']);
                if ($this->db->num_rows())
                {
                     if (isset($item['id']))
                    {
                        if (!$this->db->query("update pre_modules set root='$1', title='$2', description='$3', enabled='$4', depend='$5', package='$6' where id='$7'",$item['root'],$item['title'],$item['description'],$item['enabled'],$item['depend'],$item['package'],$item['id']))
                            return false;
                    } else 
                    {
                         if (!$this->db->query("update pre_modules set root='$1', title='$2', description='$3', enabled='$4', depend='$5', package='$6' where name='$7'",$item['root'],$item['title'],$item['description'],$item['enabled'],$item['depend'],$item['package'],$item['name']))
                            return false;
                    }
                } else 
                {
                    if (!$this->db->query("insert into pre_modules (name,root,title,description,enabled,depend,package) values ('$1','$2','$3','$4','$5','$6','$7')",$item['name'],$item['root'],$item['title'],$item['description'],$item['enabled'],$item['depend'],$item['package']))
                        return false;
                }
                break;
            case 'option':
                if (empty($item) || !is_array($item)) return false;
                if (!isset($item['name']) || !isset($item['value'])) return false;
                
                if (!isset($item['mark'])) $item['mark']='';
                
                if (is_array($item['value']))
                {
					$d=new CData();
					$item['value']=$d->do_serialize($item['value']);
				}
                
                $this->db->query("select id from pre_options where name='$1'",$item['name']);
                if ($this->db->num_rows())
                {
                    if (!$this->db->query("update pre_options set value='$1' where name='$2'",$item['value'],$item['name']))
                            return false;
                } else 
                {
                    if (!isset($item['module'])) return false;

                    if (!$this->db->query("insert into pre_options (name,value,module,mark) values ('$1','$2','$3','$4')",$item['name'],$item['value'],$item['module'],$item['mark']))
                        return false;
                }
                break;
        }
        
        return true;
    }
    
    function activate($item,$type)
    {
        if (!is_object($item))
        {
            trigger_error('Cannot activate '.htmlspecialchars($type).'. Item must be an object',E_USER_WARNING);
            return false;
        }
        
        switch ($type)
        {
            case 'widget':  
                if (!$this->db->query("update pre_widgets set enabled='1' where name='$1'",$item->name))
                    return false;
                break;
            case 'module':  
                if (!$this->check_required_modules($item->depend)) return false;
                if (!$this->db->query("update pre_modules set enabled='1' where name='$1'",$item->name))
                    return false;
                break;
        }
         
        return true;
    }
    
    function deactivate($item,$type)
    {
        if (!is_object($item))
        {
            trigger_error('Cannot deactivate '.htmlspecialchars($type).'. Item must be an object',E_USER_WARNING);
            return false;
        }
        
        switch ($type)
        {
            case 'widget':  
                if (!$this->db->query("update pre_widgets set enabled='0' where name='$1'",$item->name))
                    return false;
                break;
            case 'module':  
                if (!$this->check_dependent_modules($item->name)) return false;
                if (!$this->db->query("update pre_modules set enabled='0' where name='$1'",$item->name))
                    return false;
                break;
        }
         
        $item->enabled=0;
        
        return true;
    }
    
    function delete($item_name,$type)
    {
        if (empty($item_name)) return false;
        
        switch ($type)
        {
            case 'widget':  
                if (!$this->db->query("delete from pre_widgets where name='$1'",$item_name))
                    return false;
                break;
            case 'module':  
                $this->db->query("select id from pre_modules where name='$1'",$item_name);
                $row=$this->db->fetch_object();
                if (empty($row)) return false;
                
                $mid=$row->id;
            
                if (!$this->db->query("delete from pre_modules where name='$1'",$item_name))
                    return false;
                    
                $this->db->query("delete from pre_widgets where module='$1'",$mid);
                $this->db->query("delete from pre_options where module='$1'",$mid);    
                $this->db->query("delete from pre_permissions where module='$1'",$mid);    
                $this->db->query("delete from pre_navigation where module='$1'",$mid);  
                $this->db->query("delete from pre_nodes where module='$1'",$mid);  
                break;
             case 'option':  
                if (!$this->db->query("delete from pre_options where name='$1'",$item_name))
                    return false;
                break;
        }
        return true;
    }
    
    function render_template(&$components,$tpl,$dirname)
    {
       
        if (empty($dirname) || empty($tpl) || empty($components) || !is_array($components)) return false;
        
        if (file_exists(ROOT_DIR.'/'.quotemeta($this->tpl->root_dir).'/'.quotemeta($this->config->theme).'/'.quotemeta($tpl).'.tpl'))
        {
            $html=file_get_contents(ROOT_DIR.'/'.quotemeta($this->tpl->root_dir).'/'.quotemeta($this->config->theme).'/'.quotemeta($tpl).'.tpl');
        
            $this->tpl->parseComponents($components,$html);
            
            return $html;
        }
        
        $theme=$this->config->theme;
        
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($dirname).'/'.quotemeta($this->themes_root).'/'.quotemeta($this->config->theme).'/'.quotemeta($tpl).'.tpl'))
        {
            if ($this->config->theme==$this->tpl->default_theme)
            {
                if (DEBUG_MODE) trigger_error('Cannot render '.htmlspecialchars($dirname).' module`s widget template. File '.htmlspecialchars($tpl).'.tpl not found',E_USER_WARNING);
                return false;
            } elseif (file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($dirname).'/'.quotemeta($this->themes_root).'/'.quotemeta($this->tpl->default_theme).'/'.quotemeta($tpl).'.tpl')) 
            {
                if (DEBUG_MODE) trigger_error('Could not render '.htmlspecialchars($dirname).' module`s widget template using current theme. File '.htmlspecialchars($tpl).'.tpl not found. Using default theme',E_USER_NOTICE);
                $theme=$this->tpl->default_theme;
            } else 
            {
                trigger_error('Cannot render '.htmlspecialchars($dirname).' module`s widget template. File '.htmlspecialchars($tpl).'.tpl not found',E_USER_WARNING);
                return false;
            }
        }
        
        $html=file_get_contents(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($dirname).'/'.quotemeta($this->themes_root).'/'.quotemeta($theme).'/'.quotemeta($tpl).'.tpl');
        
        $this->tpl->parseComponents($components,$html);
        
        return $html;
    }
    
    function get_existing_modules()
    {
       
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir)))
        {
            trigger_error('Modules root directory '.htmlspecialchars($this->root_dir).' not found',E_USER_WARNING);
            return;
        }
        if (!($dir=opendir(ROOT_DIR.'/'.quotemeta($this->root_dir)))) 
        {
            trigger_error('Cannot open modules root directory '.htmlspecialchars($this->root_dir),E_USER_WARNING);
            return;
        }
        
        $folders=array();
        while (($file=readdir($dir))!==false)
        {
            if ($file=='.' || $file=='..') continue;
            if (!is_dir(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.$file)) continue;
            
            if (!file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.$file.'/index.php')) continue;
            if (!file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.$file.'/module.php')) continue;
            
            $folders[]=$file;
        }
        
        closedir($dir);
        
        return $folders;
    }
    
    function get_existing_packs($packages_only=true)
    {
        $modules=array();
        $packages=array();

        $dir=opendir(ROOT_DIR.'/'.$this->root_dir);
        while(($module_dir=readdir($dir)) !== false)
        {
            if (!is_dir(ROOT_DIR.'/'.$this->root_dir.'/'.$module_dir) || $module_dir=='.' || $module_dir=='..') continue;
            
            $data=$this->get_module_data($module_dir);
            if (!$data || !is_array($data)) continue;
            
            list($module,$widgets,$schemas,$options,$permissions,$paths,$admin_area_handler)=$data;
        
            if (!empty($module['package']))
                $packages[$module_dir]=$module['package'];
            else 
                $packages[$module_dir]='[str]Others[/str]';
                
            $modules[$module_dir]=$module;
        }
        
        if ($packages_only) return $packages;
        else return array($packages,$modules);
    }
    
    function preload_module_language($module_root)
    {
        if (file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($module_root).'/'.quotemeta($this->locale->root_dir).'/'.quotemeta($this->locale->current).'/lng.php'))
        {
            $this->locale->load(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($module_root).'/'.quotemeta($this->locale->root_dir).'/'.quotemeta($this->locale->current).'/lng.php');
        }
    }
    
    function get_module_data($module)
    {
       
        if (empty($module)) return false;
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($module).'/module.php')) return false;
        
        include(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($module).'/module.php');
        
        $arrinfo=$module.'_module';
        if (!isset($$arrinfo) || !is_array($$arrinfo)) return false;
        
        $meta=array();
        $meta['name']=isset(${$arrinfo}['name']) ? ${$arrinfo}['name'] : '';
        $meta['title']=isset(${$arrinfo}['title']) ? ${$arrinfo}['title'] : '';
        $meta['description']=isset(${$arrinfo}['description']) ? ${$arrinfo}['description'] : '';
        $meta['root']=isset(${$arrinfo}['root']) ? ${$arrinfo}['root'] : '';
        $meta['enabled']=isset(${$arrinfo}['enabled']) ? ${$arrinfo}['enabled'] : '';
        $meta['depend']=isset(${$arrinfo}['depend']) ? ${$arrinfo}['depend'] : '';
        $meta['package']=isset(${$arrinfo}['package']) ? ${$arrinfo}['package'] : '';
        $meta['version']=isset(${$arrinfo}['version']) ? ${$arrinfo}['version'] : '';
        
        $widgets=array();
        if (isset(${$arrinfo}['widgets']) && is_array(${$arrinfo}['widgets']))
        {
            foreach (${$arrinfo}['widgets'] as $widget)
            {
                if (isset($$widget) && is_array($$widget)) $widgets[]=$$widget;
            }
        }
        
        $schemas=array();
        if (isset(${$arrinfo}['schemas']) && is_array(${$arrinfo}['schemas']))
        {
            foreach (${$arrinfo}['schemas'] as $schema)
            {
                if (isset($$schema) && is_array($$schema)) $schemas[]=$$schema;
            }
        }
        
        $permissions=array();
        if (isset(${$arrinfo}['permissions']) && is_array(${$arrinfo}['permissions']))
        {
            foreach (${$arrinfo}['permissions'] as $permission)
            {
                if (isset($$permission) && is_array($$permission)) $permissions[]=$$permission;
            }
        }
        
        $paths=array();
        if (isset(${$arrinfo}['paths']) && is_array(${$arrinfo}['paths']))
        {
            foreach (${$arrinfo}['paths'] as $path)
            {
                if (isset($$path) && is_array($$path)) $paths[]=$$path;
            }
        }

        $options=isset(${$arrinfo}['options']) && is_array(${$arrinfo}['options']) ? ${$arrinfo}['options'] : array();
        
        $admin_area_handler=isset(${$arrinfo}['admin-area-class']) ? ${$arrinfo}['admin-area-class'] : '';
        
        return array($meta,$widgets,$schemas,$options,$permissions,$paths,$admin_area_handler);
    }
    
    // deprecated
    function install_module_languages($dirname,$module_name,$module_id)
    {
        return;
        
        if (empty($dirname) || empty($module_name) || empty($module_id)) return false;
        
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($dirname).'/'.quotemeta($this->languages_root)) || !is_dir(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($dirname).'/'.quotemeta($this->languages_root))) return false;
        
        $d=new CData();
        foreach ($this->locale->languages as $language=>$language_name)
        {
            if (file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($dirname).'/'.quotemeta($this->languages_root).'/'.quotemeta($language).'/lng.php'))
            {
                $options=array();
                include(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($dirname).'/'.quotemeta($this->languages_root).'/'.quotemeta($language).'/lng.php');
                if (isset($phrases) && is_array($phrases) && !empty($phrases))
                {
                    $options['name']=$module_name.'_language_'.$language;
                    $options['value']=$d->do_serialize($phrases);
                    $options['module']=$module_id;
                    $options['mark']='store';
                    
                    $this->set($options,'option');
                    unset($phrases);
                }
            }
        }
        return true;
    }
    
    // deprecated
    function install_all_modules_languages()
    {
        return;
        
        foreach($this->modules as $id=>$module)
        {
            $this->install_module_languages($module->root,$module->name,$id);  
        }
    }
    
    function get_required_modules($depend)
    {
        if (empty($depend)) return array();
        
        $required=array();
        
        $dependencies=explode(',',$depend);
        foreach ($dependencies as $dependency)
        {
            $this->db->query("select root,title from pre_modules where root='$1'",trim($dependency));
            $row=$this->db->fetch_object();
            if (!empty($row))
                $required[$row->root]=$row->title;
            else
                $required[trim($dependency)]=trim($dependency);
        }
        
        return $required;
    }
    
    function check_required_modules($depend)
    {
        if (empty($depend)) return true;
        
        $dependencies=explode(',',$depend);
        foreach ($dependencies as $dependency)
        {
            $dependency=trim($dependency);
            
            $this->db->query("select id from pre_modules where root='$1' and enabled<>'0'",trim($dependency));
            if (!$this->db->num_rows()) 
            {
                
                //trigger_error('Required module '.htmlspecialchars($dependency).' not found. Make sure, that it is installed and enabled',E_USER_WARNING);
                if ($this->try_install_anyway && isset($_POST['module-installed-'.$dependency]) && $_POST['module-installed-'.$dependency]=='on') continue;
                else 
                {
                    //trigger_error('Required module '.htmlspecialchars($dependency).' not found and is not going to be installed.',E_USER_WARNING);
                    return false;
                }
            }
        }
        
        return true;
    }
    
    function get_dependent_modules($module_name)
    {
        if (empty($module_name)) return array();
        
        $dependent=array();
        
        foreach ($this->modules as $module)
        {
            if (!$module->enabled) continue;
            if (empty($module->depend)) continue;
            
            $depend=explode(',',$module->depend);
            foreach ($depend as $dependency)
            {
                if ($module_name==trim($dependency)) 
                {
                    if (!array_key_exists($module->root,$dependent)) $dependent[$module->root]=$module->title;
                    break;
                }
            }
        }
        
        return $dependent;
    }
    
    function check_dependent_modules($module_name)
    {
        if (empty($module_name))
        {
            trigger_error('Cannot check dependent modules. Empty name given',E_USER_WARNING);
            return false;
        }
        
        foreach ($this->modules as $module)
        {
            if (!$module->enabled) continue;
            if (empty($module->depend)) continue;
            
            $depend=explode(',',$module->depend);
            foreach ($depend as $dependency)
            {
                if ($module_name==trim($dependency)) 
                {
                    trigger_error('Module '.htmlspecialchars($module_name).' required by '.htmlspecialchars($module->name),E_USER_WARNING);
                    return false;
                }
            }
        }
        
        return true;
    }
    
    function install_module($dirname)
    {
        $data=$this->get_module_data($dirname);
        if (!$data || !is_array($data)) return false;
        
        list($module,$widgets,$schemas,$options,$permissions,$paths,$admin_area_handler)=$data;
        
        if (!empty($module['depend']) && !$this->check_required_modules($module['depend'])) 
        {
            $module['enabled']=0;
        }
        
        if ($this->set($module,'module'))
        {
            $this->db->query("select id from pre_modules where name='$1'",$module['name']);
            $row=$this->db->fetch_object();
            $mid=$row->id;
            
             if (!empty($schemas) && is_array($schemas))
            {
                foreach ($schemas as $schema)
                {
                    if (!$this->db->create_table($schema)) return false;
                }
            }
            
            if (!empty($widgets) && is_array($widgets))
            {
                foreach ($widgets as $widget)
                {
                    $widget['module']=$mid;
                    $this->set($widget,'widget');
                }
            }
            
            if (!empty($options) && is_array($options))
            {
                foreach ($options as $option_name=>$option_value)
                {
                    $this->set(array('name'=>$option_name,'value'=>$option_value,'module'=>$mid),'option');
                }
            }
            
            if (!empty($permissions) && is_array($permissions))
            {
                foreach ($permissions as $permission)
                {
                    $permission['module']=$mid;
                    $this->access->set($permission);
                }
            }
            
            if (!empty($paths) && is_array($paths))
            {
                foreach ($paths as $path)
                {
                    $path['module']=$mid;
                    if (!$this->navigator->set($path)) trigger_error('Cannot set module path '.htmlspecialchars($path).'. Is it already in use ?',E_USER_WARNING);
                }
            }
            
           $this->install_module_languages($dirname,$module['name'],$mid);
           
           if (!empty($admin_area_handler))
           {
                $options['name']=$module['name'].'_admin_area_class';
                $options['value']=$admin_area_handler;
                $options['module']=$mid;
                $options['mark']='store';
                $this->set($options,'option');
           }
            
            if (file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($dirname).'/install.php'))
            {
                include_once(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($dirname).'/install.php');
                $install_func=$module['name'].'_install';
                if (function_exists($install_func)) 
                {
                    $install_func($mid);
                }
            }
            
            return true;
            
        } else return false;
    }
    
    function uninstall_module($dirname)
    {
        $data=$this->get_module_data($dirname);
        if (!$data || !is_array($data)) return false;
        
        list($module,$widgets,$schemas,$options,$permissions,$paths,$admin_area_handler)=$data;
        
        if (!isset($module['name'])) return false;
        if (!$this->check_dependent_modules($module['root'])) return false;

        $this->db->query("select id from pre_modules where name='$1'",$module['name']);
        $row=$this->db->fetch_object();
        $mid=$row->id;
            
        if ($this->delete($module['name'],'module'))
        {
            
            if (!empty($schemas) && is_array($schemas))
            {
                foreach ($schemas as $schema)
                {
                    if (!isset($schema['name'])) continue;
                    
                    if (!$this->db->query("drop table ".$this->db->escape_string($schema['name']))) return false;
                }
            }
            
            if (file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($dirname).'/uninstall.php'))
            {
                include_once(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($dirname).'/uninstall.php');
                $uninstall_func=$module['name'].'_uninstall';
                if (function_exists($uninstall_func)) 
                {
                    $uninstall_func($mid);
                }
            }
            
            return true;
            
        } else return false;
    }
    
    function module_exists($name)
    {
        if (empty($name)) return false;
        $this->db->query("select id from pre_modules where name='$1' and enabled<>'0'",$name);
        return $this->db->num_rows();
    }
    
    function widget_exists($name)
    {
        if (empty($name)) return false;
        $this->db->query("select id from pre_widgets where name='$1' and enabled<>'0'",$name);
        return $this->db->num_rows();
    }
    
    function schema($type)
    {
        switch ($type)
        {
            case 'widgets':
                $table=array(
                    'name' => 'pre_widgets',
                    'fields' => array(
                        'id' => array(
                            'type' => 'id',
                            'length' => 10,
                            'not null' => true
                        ),
                        'name' => array(
                            'type' => 'string',
                            'length' => 255,
                            'not null' => true
                        ),
                        'class' => array(
                            'type' => 'string',
                            'length' => 255,
                            'not null' => true
                        ),
                        'handler' => array(
                            'type' => 'string',
                            'length' => 255,
                            'not null' => true
                        ),
                        'argument' => array(
                            'type' => 'string',
                            'length' => 255,
                            'not null' => false
                        ),
                        'title' => array(
                            'type' => 'string',
                            'length' => 255,
                            'not null' => true
                        ),
                        'description' => array(
                            'type' => 'text',
                            'not null' => true
                        ),
                        'path' => array(
                            'type' => 'string',
                            'length' => 255,
                            'not null' => true
                        ),
                        'component' => array(
                            'type' => 'string',
                            'length' => 255,
                            'not null' => true
                        ),
                        'weight' => array(
                            'type' => 'integer',
                            'length' => 4,
                            'not null' => true
                        ),
                        'permission' => array(
                            'type' => 'string',
                            'length' => 255,
                            'not null' => true
                        ),
                         'module' => array(
                            'type' => 'integer',
                            'length' => 10,
                            'not null' => true
                        ),
                        'enabled' => array(
                            'type' => 'integer',
                            'length' => 4,
                            'not null' => false
                        ),
                        'cacheable' => array(
                            'type' => 'integer',
                            'length' => 4,
                            'not null' => false,
                            'default' => 0
                        ),
                        'fix' => array(
                            'type' => 'integer',
                            'length' => 4,
                            'not null' => false,
                            'default' => 0
                        ),
                        'mark' => array(
                            'type' => 'string',
                            'length' => 255,
                            'not null' => false
                        )
                    ),
                    'unique' => 'name',
                    'primary key' => 'id',
                    'index' => array('enabled','path')
                );
                break;
            case 'modules':
                $table=array(
                    'name' => 'pre_modules',
                    'fields' => array(
                        'id' => array(
                            'type' => 'id',
                            'length' => 10,
                            'not null' => true
                        ),
                        'name' => array(
                            'type' => 'string',
                            'length' => 255,
                            'not null' => true
                        ),
                        'root' => array(
                            'type' => 'string',
                            'length' => 255,
                            'not null' => true
                        ),
                        'title' => array(
                            'type' => 'string',
                            'length' => 255,
                            'not null' => true
                        ),
                        'description' => array(
                            'type' => 'text',
                            'not null' => true
                        ),
                        'enabled' => array(
                            'type' => 'integer',
                            'length' => 4,
                            'not null' => false
                        ),
                        'depend' => array(
                            'type' => 'string',
                            'length' => 255,
                            'not null' => false
                        ),
                        'package' => array(
                            'type' => 'string',
                            'length' => 255,
                            'not null' => false
                        ),
                        'mark' => array(
                            'type' => 'string',
                            'length' => 255,
                            'not null' => false
                        )
                    ),
                    'unique' => 'name',
                    'primary key' => 'id',
                    'index' => 'enabled'
                );
                break;
            case 'options':
                $table=array(
                    'name' => 'pre_options',
                    'fields' => array(
                        'id' => array(
                            'type' => 'id',
                            'length' => 10,
                            'not null' => true
                        ),
                        'name' => array(
                            'type' => 'string',
                            'length' => 255,
                            'not null' => true
                        ),
                        'value' => array(
                            'type' => 'longtext',
                            'not null' => true
                        ),
                        'module' => array(
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
                    'unique' => 'name',
                    'primary key' => 'id',
                    'index' => 'module'
                );
                break;
        }
        
        return $table;
    }
    
    function defaults()
    {
        $values=array();
        $values[]=array(
                   'name' => 'signInForm',
                    'class' => 'CSawanna',
                    'handler' => 'su_login_form',
                    'title' => 'Sign In Form',
                    'description' => 'Required, do not remove !',
                    'path' => $this->config->login_area,
                    'component' => '',
                    'weight' => -99,
                    'permission' => '',
                    'module' => 0,
                    'cacheable' => 0,
                    'enabled' => 1,
                    'fix' => 1,
                    'mark' => 'Created during installation'
        );
        
        $values[]=array(
                    'name' => 'pwdRecoveryForm',
                    'class' => 'CSawanna',
                    'handler' => 'su_pwd_recover_form',
                    'title' => 'Password Recovery Form',
                    'description' => 'Required, do not remove !',
                    'path' => $this->config->pwd_recover_area,
                    'component' => '',
                    'weight' => -99,
                    'permission' => '',
                    'module' => 0,
                    'cacheable' => 0,
                    'enabled' => 1,
                    'fix' => 1,
                    'mark' => 'Created during installation'
        );
        
        $values[]=array(
                    'name' => 'file',
                    'class' => 'CSawanna',
                    'handler' => 'file',
                    'title' => 'Process file downloads',
                    'description' => 'Required, do not remove !',
                    'path' => 'file',
                    'component' => '',
                    'weight' => -99,
                    'permission' => 'download files',
                    'module' => 0,
                    'cacheable' => 0,
                    'enabled' => 1,
                    'fix' => 1,
                    'mark' => 'Created during installation'
        );
        
       $values[]=array(
                    'name' => 'image',
                    'class' => 'CSawanna',
                    'handler' => 'image',
                    'title' => 'Image output',
                    'description' => 'Required, do not remove !',
                    'path' => 'image',
                    'component' => '',
                    'weight' => -99,
                    'permission' => '',
                    'module' => 0,
                    'cacheable' => 0,
                    'enabled' => 1,
                    'fix' => 1,
                    'mark' => 'Created during installation'
        );
        
        $values[]=array(
                    'name' => 'cron',
                    'class' => 'CSawanna',
                    'handler' => 'cron',
                    'argument' => 1,
                    'title' => 'Cron Jobs executer',
                    'description' => 'Required, do not remove !',
                    'path' => 'cron',
                    'component' => '',
                    'weight' => -99,
                    'permission' => '',
                    'module' => 0,
                    'cacheable' => 0,
                    'enabled' => 1,
                    'fix' => 1,
                    'mark' => 'Created during installation'
        );
        
        $values[]=array(
                    'name' => 'css',
                    'class' => 'CSawanna',
                    'handler' => 'css',
                    'argument' => '%arg',
                    'title' => 'CSS cached output',
                    'description' => 'Required, do not remove !',
                    'path' => "css\ncss/*",
                    'component' => '',
                    'weight' => -99,
                    'permission' => '',
                    'module' => 0,
                    'cacheable' => 0,
                    'enabled' => 1,
                    'fix' => 1,
                    'mark' => 'Created during installation'
        );
        
        return $values;
    }
    
    function install(&$db)
    {
        if (!$db->create_table($this->schema('modules'))) return false;

        if (!$db->create_table($this->schema('widgets'))) return false;

        if (!$db->create_table($this->schema('options'))) return false;
        
        $defaults=$this->defaults();
        foreach($defaults as $default)
        {
            if (!$db->insert('pre_widgets',$default)) return false;
        }
        
        return true;
    }
    
    function uninstall(&$db)
    {
        if (!$db->query("DROP TABLE IF EXISTS pre_modules")) return false;
        if (!$db->query("DROP TABLE IF EXISTS pre_widgets")) return false;
        if (!$db->query("DROP TABLE IF EXISTS pre_options")) return false;
        
        return true;
    }
}
