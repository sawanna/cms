<?php
defined('SAWANNA') or die();

class CNavigator
{
    var $db;
    var $access;
    var $config;
    var $current;
    var $query;
    var $arg;
    var $required;
    var $permission;
    
    function CNavigator(&$db,&$config,&$access)
    {
        $this->db=$db;
        $this->config=$config;
        $this->access=$access;
        
        $this->current=new stdClass();
        $this->current->id=0;
        $this->current->path='';
        $this->current->permission='';
        $this->current->module=0;
        $this->current->enabled=1;
        $this->current->render=1;
        $this->current->mark='';
        
        $this->query='';
        $this->arg='';
        
        $this->required=array();
        
        $defaults=$this->defaults();
        foreach($defaults as $default)
        {
            $this->required[]=$default['path'];
        }
    }
    
    function init(&$path,&$arg)
    {
        if (empty($path)) return 200;
        if (is_numeric($path))
        {
            $arg=$path;
            $path='';
            return 200;
        }
        
        $this->db->query("select * from pre_navigation where path='$1' and enabled<>'0'",$path);
        $row=$this->db->fetch_object();
        
    //    if (empty($row) && preg_match('/^([a-zA-Z0-9\-_]+)\/([a-zA-Z0-9\-_\/]+)$/',$path,$matches))  // adding utf8 chars
        if (empty($row) && preg_match('/^([^\.\?&=\/]+)\/([^\.\?&=]+)$/',$path,$matches))
        {
            $vars=explode('/',$matches[2]);
            
            $_path=$matches[1];
            $_pathes=array($_path);
            $_queries=array($_path);
            
            foreach ($vars as $iter=>$var)
            {
                foreach ($_pathes as $k=>$_p)
                {
                    $_pathes[$k].= (is_numeric($var)) ? '/d' : '/s';
                }
                
                if ($iter < count($vars)-1)
                {
                    $_path.= '/'.$var;
                    $_pathes[]=$_path;
                    $_queries[]=$_path;
                }
            }
            
            foreach ($_pathes as $k=>$_p)
            {
                $_pathes[$k]="'".$this->db->escape_string($_p)."'";
            }

            $this->db->query("select * from pre_navigation where path in ( ".implode(', ',$_pathes)." ) and enabled<>'0'");
            $row=$this->db->fetch_object();
            
            if (!empty($row))
            {
                $_queries=array_reverse($_queries);
                foreach ($_queries as $k=>$_p)
                {
                    if (strpos($row->path,$_p.'/')===0)
                    {
                        $arg=sawanna_substr($path,sawanna_strlen($_p)+1);
                        $path=$_p;
                        break;
                    }
                }
                
                $this->arg=$arg;
            }
        }
        
        if (empty($row)) {
            trigger_error('404 Not found. URL path '.htmlspecialchars($path).' not registered',E_USER_NOTICE);
            return 404;
        }
        
        $this->permission=$row->permission;
        
        $this->current=$row;
        $this->query=$path;
        return 200;
    }
    
    function check_access()
    {
        if (!$this->access->has_access($this->permission)) 
        {
            // trigger_error('403 Forbidden. Access denied to '.htmlspecialchars($path),E_USER_NOTICE);
            return 403;
        }
        
        return 200;
    }
    
    function set($navigator)
    {
        if (empty($navigator) || !is_array($navigator)) return false;
        if (empty($navigator['path']))
        {
            trigger_error('Cannot set navigation. Empty path given',E_USER_WARNING);
            return false;
        }
        
        if (!$this->check_path($navigator['path'])) 
        {
            if ($navigator['path'] != '[home]' && $navigator['path'] != '[home]/d') trigger_error('Invalid path given: '.htmlspecialchars($navigator['path']),E_USER_WARNING);
            return false;
        }
        
        if (!isset($navigator['permission'])) $navigator['permission']='';
        if (!isset($navigator['module'])) $navigator['module']=0;
        if (!isset($navigator['enabled'])) $navigator['enabled']=0;
        if (!isset($navigator['render'])) $navigator['render']=1;
        if (!isset($navigator['mark'])) $navigator['mark']='';
        
        if (in_array($navigator['path'],$this->required)) $navigator['enabled']=1;
        
        if (isset($navigator['id']))
        {
            if (!$this->db->query("update pre_navigation set path='$1', permission='$2', module='$3', enabled='$4', render='$5', mark='$6' where id='$7'",$navigator['path'],$navigator['permission'],$navigator['module'],$navigator['enabled'],$navigator['render'],$navigator['mark'],$navigator['id']))
                return false;
        } else 
        {
            $this->db->query("select id from pre_navigation where path='$1'",$navigator['path']);
             if (!$this->db->num_rows())
             {
                 if (!$this->db->query("insert into pre_navigation (path,permission,module,enabled,render,mark) values ('$1','$2','$3','$4','$5','$6')",$navigator['path'],$navigator['permission'],$navigator['module'],$navigator['enabled'],$navigator['render'],$navigator['mark']))
                    return false;
                    
             } else
             {
                 trigger_error('Cannot set navigation path '.htmlspecialchars($navigator['path']).'. Path already exists',E_USER_WARNING);
                 return false;
             }
        }
        
        return true;
    }
    
    function delete($path='',$module='')
    {
        if (empty($path) && empty($module)) return false;
        
        if (!empty($path) && !empty($module))
        {
            if (in_array($path,$this->required)) return false;
            if (!$this->db->query("delete from pre_navigation where path='$1' and module='$2'",$path,$module))
                return false;
        } elseif (!empty($path))
        {
            if (in_array($path,$this->required)) return false;
            if (!$this->db->query("delete from pre_navigation where path='$1'",$path))
                return false;
        } elseif (!empty($module))
        {
            if (empty($module)) return false;
            if (!$this->db->query("delete from pre_navigation where module='$1'",$module))
                return false;
        }
        
        return true;
    }
    
    function check_path($path)
    {
        if (!preg_match('/^[a-zа-яё0-9\/_-]+$/iu',$path)) return false;
       
        return true;
    }
    
    function enable($id='',$path='')
    {
        if (empty($id) && empty($path)) return false;
        
        if (!empty($id) && !empty($path))
        {
            if (!$this->db->query("update pre_navigation set enabled='1' where id='$1' and path='$2'",$id,$path))
                return false;
        } elseif (!empty($id))
        {
             if (!$this->db->query("update pre_navigation set enabled='1' where id='$1'",$id))
                return false;
        } elseif(!empty($path))
        {
             if (!$this->db->query("update pre_navigation set enabled='1' where path='$1'",$path))
                return false;
        }
        
        return true;
    }
    
    function get($path='',$id='',$check_access=true,$check_enabled=true)
    {
        $enabled_cond='';
        if ($check_enabled) $enabled_cond=" and enabled<>'0'";
        
        if (empty($id) && empty($path))
        {
            return $this->current;
        } elseif(!empty($id) && !empty($path))
        {
            $this->db->query("select * from pre_navigation where id='$1' and path='$2'".$enabled_cond,$id,$path);
            $row=$this->db->fetch_object();
        } elseif(!empty($id))
        {
            $this->db->query("select * from pre_navigation where id='$1'".$enabled_cond,$id);
            $row=$this->db->fetch_object();
        } elseif(!empty($path))
        {
            $this->db->query("select * from pre_navigation where path='$1'".$enabled_cond,$path);
            $row=$this->db->fetch_object();
        }
        
        if (!empty($row))
        {
            if ($check_access && $this->access->has_access($row->permission)) return $row;
            elseif (!$check_access) return $row;
        }
    }
    
    function disable($id='',$path='')
    {
        if (empty($id) && empty($path)) return false;
        
        if (!empty($id) && !empty($path))
        {
            if (in_array($navigator['path'],$this->required)) return false;
            if (!$this->db->query("update pre_navigation set enabled='0' where id='$1' and path='$2'",$id,$path))
                return false;
        } elseif (!empty($id))
        {
            $this->db->query("select path from pre_navigation where id='$1'",$id);
            $row=$this->db->fetch_object();
            if (in_array($row->path,$this->required)) return false;
            if (!$this->db->query("update pre_navigation set enabled='0' where id='$1'",$id))
                return false;
        } elseif(!empty($path))
        {
            if (in_array($navigator['path'],$this->required)) return false;
            if (!$this->db->query("update pre_navigation set enabled='0' where path='$1'",$path))
                return false;
        }
        
        return true;
    }

    function schema()
    {
        $table=array(
            'name' => 'pre_navigation',
            'fields' => array(
                'id' => array(
                    'type' => 'id',
                    'length' => 10,
                    'not null' => true
                ),
                'path' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => true
                ),
                'permission' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => true
                ),
                'module' => array(
                    'type' => 'integer',
                    'length' => 4,
                    'not null' => false
                ),
                'enabled' => array(
                    'type' => 'integer',
                    'length' => 4,
                    'not null' => true
                ),
                'render' => array(
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
            'primary key' => 'id',
            'unique' => 'path',
            'index' =>'enabled'
        );
        
        return $table;
    }
    
    function defaults()
    {
        $values=array(
            array(
            'path' => $this->config->admin_area,
            'permission' => 'access admin area',
            'module' => 0,
            'enabled' => 1,
            'render' => 0
            ),
            array(
            'path' => $this->config->admin_area.'/s',
            'permission' => 'access admin area',
            'module' => 0,
            'enabled' => 1,
            'render' => 0
            ),
            array(
            'path' => $this->config->admin_area.'/s/d',
            'permission' => 'access admin area',
            'module' => 0,
            'enabled' => 1,
            'render' => 0
            ),
           array(
            'path' => $this->config->admin_area.'/s/s',
            'permission' => 'access admin area',
            'module' => 0,
            'enabled' => 1,
            'render' => 0
            ),
           array(
            'path' => $this->config->admin_area.'/s/s/d',
            'permission' => 'access admin area',
            'module' => 0,
            'enabled' => 1,
            'render' => 0
            ),
            array(
            'path' => $this->config->admin_area.'/s/s/s',
            'permission' => 'access admin area',
            'module' => 0,
            'enabled' => 1,
            'render' => 0
            ),
            array(
            'path' => $this->config->admin_area.'/s/s/s/d',
            'permission' => 'access admin area',
            'module' => 0,
            'enabled' => 1,
            'render' => 0
            ),
            array(
            'path' => $this->config->login_area,
            'permission' => '',
            'module' => 0,
            'enabled' => 1,
            'render' => 1
            ),
            array(
            'path' => $this->config->logout_area,
            'permission' => '',
            'module' => 0,
            'enabled' => 1,
            'render' => 0
            ),
            array(
            'path' => $this->config->pwd_recover_area,
            'permission' => '',
            'module' => 0,
            'enabled' => 1,
            'render' => 1
            ),
            array(
            'path' => 'captcha',
            'permission' => '',
            'module' => 0,
            'enabled' => 1,
            'render' => 0
            ),
           array(
            'path' => 'file/d',
            'permission' => 'download files',
            'module' => 0,
            'enabled' => 1,
            'render' => 1
            ),
           array(
            'path' => 'file/s',
            'permission' => 'download files',
            'module' => 0,
            'enabled' => 1,
            'render' => 1
            ),
           array(
            'path' => 'image/d',
            'permission' => '',
            'module' => 0,
            'enabled' => 1,
            'render' => 1
            ),
           array(
            'path' => 'image/s',
            'permission' => '',
            'module' => 0,
            'enabled' => 1,
            'render' => 1
            ),
           array(
            'path' => 'image/d/s',
            'permission' => '',
            'module' => 0,
            'enabled' => 1,
            'render' => 1
            ),
           array(
            'path' => 'image/s/s',
            'permission' => '',
            'module' => 0,
            'enabled' => 1,
            'render' => 1
            ),
            array(
            'path' => 'ping',
            'permission' => '',
            'module' => 0,
            'enabled' => 1,
            'render' => 0
            ),
           array(
            'path' => 'cron',
            'permission' => '',
            'module' => 0,
            'enabled' => 1,
            'render' => 1
            ),
           array(
            'path' => 'css',
            'permission' => '',
            'module' => 0,
            'enabled' => 1,
            'render' => 1
            ),
           array(
            'path' => 'css/s',
            'permission' => '',
            'module' => 0,
            'enabled' => 1,
            'render' => 1
            )
        );
        
        return $values;
    }
    
    function install(&$db)
    {
        if (!$db->create_table($this->schema())) return false;
        
        $defaults=$this->defaults();
        foreach($defaults as $default)
        {
            if (!$db->insert('pre_navigation',$default)) return false;
        }
        
        return true;
    }
    
    function uninstall(&$db)
    {
        if (!$db->query("DROP TABLE IF EXISTS pre_navigation")) return false;
        
        return true;
    }
}

