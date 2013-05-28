<?php
defined('SAWANNA') or die();

class CAccess
{
    var $db;
    var $session;
    var $config;
    var $table;
    var $logged;
    
    function CAccess(&$db,&$session,&$config)
    {
        $this->db=$db;
        $this->session=$session;
        $this->config=$config;
        $this->table=array();
        $this->logged=false;
    }
    
    function init()
    {
        $table=$this->get_access_table();
        foreach ($table as $access)
        {
            $this->table[$access->name]=$access->access;
        }
        
        if ($this->check_su()) 
        {
            $this->table['su']=1;
            $this->logged=true;
        }
    }
    
    function get_access_table()
    {
        $table=array();
        
        if (is_object($this->db))
        {
            $this->db->query("select * from pre_permissions order by id");
            while($row=$this->db->fetch_object())
            {
                $table[]=$row;
            }
        }
        
        return $table;
    }
    
    function get_module_permissions(&$permissions,$module_id)
    {
        $perms=array();
        
        foreach ($permissions as $pkey=>$permission)
        {
            if ($permission->module == $module_id) $perms[$pkey]=$permission;
        }
        
        return $perms;
    }
    
    function perm_exists($id)
    {
        $perms=$this->get_access_table();
        foreach ($perms as $perm)
        {
            if (is_numeric($id) && $perm->id==$id) return true;
            elseif ($perm->name==$id) return true;
        }
        
        return false;
    }
    
    function get_current_access($id)
    {
        $perms=$this->get_access_table();
        foreach ($perms as $perm)
        {
            if (is_numeric($id) && $perm->id==$id) return $perm->access;
            elseif ($perm->name==$id) return $perm->access;
        }
        
        return false;
    }
    
    function check_su()
    { 
        $su_user=$this->session->get('su_user');
        $su_password=$this->session->get('su_password');
        
        if (!empty($su_user) && !empty($su_password) && $su_user==$this->config->su['user'] && $su_password==$this->config->su['password']) return true;
        
        return false;
    }
    
    function has_access($permission)
    {
        if (empty($permission)) return true;
        if(isset($this->table[$permission]) && $this->table[$permission]) return true;
        if (isset($this->table['su']) && $this->table['su']) return true;
        
        return false;
    }
    
    function set($access)
    {
        if (empty($access) || !is_array($access)) return false;
        if (!isset($access['name']) || !isset($access['title']) || !isset($access['description']) || !isset($access['access']) || !isset($access['module'])) return false;

        $this->db->query("select module from pre_permissions where name='$1'",$access['name']);
        if ($this->db->num_rows())
        {
            $row=$this->db->fetch_object();
            if ($row->module!=$access['module']) 
            {
                trigger_error('Cannot set access permission '.htmlspecialchars($access['name']).'. Permission used by another module',E_USER_WARNING);
                return false;
            }
            
            if (!$this->db->query("update pre_permissions set title='$1', description='$2', access='$3' where name='$4'",$access['title'],$access['description'],$access['access'],$access['name']))
                return false;
        } else 
        {
            if (!$this->db->query("insert into pre_permissions (name,title,description,access,module) values ('$1','$2','$3','$4','$5')",$access['name'],$access['title'],$access['description'],$access['access'],$access['module']))
                return false;
        }
        
        return true;
    }
    
    function delete($permission)
    {
        if (empty($permission)) return false;
        if (!$this->db->query("delete from pre_permissions where name='$1'",$permission))
            return false;
            
        return true;
    }
    
    function is_su()
    {   
        return $this->has_access('su');
    }
    
    function pwd_hash($password,$key='')
    {
        if (empty($password)) return false;
        if (empty($key)) $key=PRIVATE_KEY;
        
        return md5($password.$key);
    }
    
    function schema()
    {
        $table=array(
            'name' => 'pre_permissions',
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
                'title' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => true
                ),
                'description' => array(
                    'type' => 'text',
                    'not null' => true
                ), 
                'access' => array(
                    'type' => 'integer',
                    'length' => 4,
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
            'primary key' => 'id',
            'unique' => 'name'
        );
        
        return $table;
    }
    
    function defaults()
    {
        $values=array(
            array(
            'name' => 'su',
            'title' => '[str]Super User[/str]',
            'description' => '[str]Unrestricted access[/str]',
            'access' => 0,
            'module' => 0
            ),
           array(
            'name' => 'access admin area',
            'title' => '[str]Access Admin-Area[/str]',
            'description' => '[str]Privileged users will be able to access Admin-Area[/str]',
            'access' => 0,
            'module' => 0
            ),
           array(
            'name' => 'manage widgets',
            'title' => '[str]Manage widgets[/str]',
            'description' => '[str]Privileged users will be able to manage widgets.[/str]',
            'access' => 0,
            'module' => 0
            ),
           array(
            'name' => 'download files',
            'title' => '[str]Download files[/str]',
            'description' => '[str]Privileged users will be able to download any uploaded file.[/str]',
            'access' => 1,
            'module' => 0
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
            if (!$db->insert('pre_permissions',$default)) return false;
        }
        
        return true;
    }
    
    function uninstall(&$db)
    {
        if (!$db->query("DROP TABLE IF EXISTS pre_permissions")) return false;
        
        return true;
    }
}
