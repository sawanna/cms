<?php
defined('SAWANNA') or die();

class CDB extends CDatabase 
{
    var $conn;
    var $result;
    
    function open()
    { 
        $this->conn=@mysql_connect($this->db_host.':'.$this->db_port,$this->db_user,$this->db_pass);
        if (!$this->conn)
        {
            trigger_error('Failed to connect to MySQL server. '.mysql_error(),E_USER_ERROR);
            return false;
        }
        
        if (!@mysql_select_db($this->db_name,$this->conn))
        {
            trigger_error('Failed to change database.',E_USER_ERROR);
            return false;
        }
        
        $this->query("SET NAMES utf8");
        
        return true;
    }
    
    function query()
    {
        $args=func_get_args();
        $query=array_shift($args);
        
        $query=$this->parse_sql($query,$args);
        $this->result=@mysql_query($query,$this->conn);
        
        if (!$this->result)
        {
            if (DEBUG_MODE) trigger_error('SQL-query was: '.htmlspecialchars($query),E_USER_NOTICE);
            trigger_error(mysql_error($this->conn),E_USER_ERROR);
            return false;
        }
        
        return $this->result;
    }
    
     function debug_query()
    {
        $args=func_get_args();
        $query=array_shift($args);
        
        $query=$this->parse_sql($query,$args);
        $this->result=@mysql_query($query,$this->conn);
        
        trigger_error('SQL-query was: '.htmlspecialchars($query),E_USER_NOTICE);
        
        if (!$this->result)
        {
            trigger_error(mysql_error($this->conn),E_USER_ERROR);
            return false;
        }
        
        return $this->result;
    }
    
    function fetch_array($result=false,$type='ASSOC')
    {
        if (!$result) $result=$this->result;
        $type=$this->get_valid_fetch_type($type);
        
        return @mysql_fetch_array($result,$type);
    }
    
    function fetch_object($result=false)
    {
        if (!$result) $result=$this->result;
        
        return @mysql_fetch_object($result);
    }
    
    function num_rows($result=false)
    {
        if (!$result) $result=$this->result;
        
        return @mysql_num_rows($result);
    }
    
    function affected_rows()
    {
        return @mysql_affected_rows($this->conn);
    }
    
     function last_insert_id($table,$column)
    {
        return $this->query("select last_insert_id() as id");
    }
    
    function escape_string($string)
    { 
        return mysql_real_escape_string($string,$this->conn);
    }
    
    function close()
    {
        if (!@mysql_close($this->conn))
        {
            trigger_error('Failed to close MySQL connection.',E_USER_ERROR);
        }
    }
    
    function get_valid_fetch_type($name)
    {
        switch ($name)
        {
            case 'ASSOC':
                $type=MYSQL_ASSOC;
                break;
            case 'NUM':
                $type=MYSQL_NUM;
                break;
            case 'BOTH':
                $type=MYSQL_BOTH;
                break;
            default:
                $type=MYSQL_ASSOC;
        }
        
        return $type;
    }
    
    function last_error()
    {
        return mysql_error($this->conn);
    }
    
    function version()
    {
        $this->query("select version() as version");
        $row=$this->fetch_object();
        
        return $row->version;
    }
    
    function create_table($table)
    {
        if (!is_array($table) || empty($table) || !isset($table['name']) || empty($table['name']) || !isset($table['fields']) || empty($table['fields'])) return false;
        
        $fields_str=array();
        
        foreach ($table['fields'] as $field=>$params)
        {
            if (!is_array($params) || !isset($params['type']) || empty($params['type'])) continue;
            
            $q_str='';
            
            switch ($params['type'])
            {
                case 'id':
                    $q_str.=$this->escape_string($field).' INT';
                    break;
                case 'integer':
                    $q_str.=$this->escape_string($field).' INT';        
                    break;
                case 'float':
                    $q_str.=$this->escape_string($field).' FLOAT';
                    break;
                case 'string':
                    $q_str.=$this->escape_string($field).' VARCHAR';
                    break;
                case 'text':
                    $q_str.=$this->escape_string($field).' TEXT';
                    break;
                case 'mediumtext':
                    $q_str.=$this->escape_string($field).' MEDIUMTEXT';
                    break;
                case 'longtext':
                    $q_str.=$this->escape_string($field).' LONGTEXT';
                    break;
                case 'binary':
                    $q_str.=$this->escape_string($field).' BLOB';
                    break;
                default:
                    $q_str.=$this->escape_string($field).' '.$this->escape_string($params['type']);
                    break;
            }
            
            if (isset($params['length']) && is_numeric($params['length']) && $params['type']!='text' && $params['type']!='binary') 
            {
                $q_str.=' ('.$this->escape_string($params['length']).')';
            }
           
            if (isset($params['default']))
            {
                if (is_numeric($params['default']))
                    $q_str.=' DEFAULT '.$this->escape_string($params['default']);
                else
                    $q_str.=" DEFAULT '".$this->escape_string($params['default'])."'";
            }
                
            if (isset($params['not null']))
            {
                if ($params['not null']) $q_str.=' NOT NULL';
                else $q_str.=' NULL';
            }    
            
            if ($params['type']=='id')
            {
                $q_str.=' AUTO_INCREMENT';
            }
            
            $fields_str[]=$q_str;
        }
        
        $query='CREATE TABLE `'.$this->escape_string($table['name']). '` ( ';
        $query.=implode(', ',$fields_str);
        
        if (isset($table['unique']))
        {
            if (!is_array($table['unique']))
                $query.=', UNIQUE ( '.$this->escape_string($table['unique']).' )';
            else 
            {
                foreach ($table['unique'] as $unique)
                {
                    $query.=', UNIQUE ( '.$this->escape_string($unique).' )';
                }
            }
        }
        
        if (isset($table['primary key']))
        {
            $query.=', PRIMARY KEY ( '.$this->escape_string($table['primary key']).' )';
        }
         
        $query.=' ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci';
        
        $return= $this->query($query);
        
        if (isset($table['index']))
        {
            if (!is_array($table['index']))
                $this->query('CREATE INDEX '.$this->escape_string($table['name'].'_'.$table['index']).' ON '.$this->escape_string($table['name']).' ( '.$this->escape_string($table['index']).' )');
            else 
            {
                foreach ($table['index'] as $index)
                {
                    $this->query('CREATE INDEX '.$this->escape_string($table['name'].'_'.$index).' ON '.$this->escape_string($table['name']).' ( '.$this->escape_string($index).' )');
                }
            }
        }
        
        return $return;
    }
}
