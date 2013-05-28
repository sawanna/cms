<?php
defined('SAWANNA') or die();

class CDB extends CDatabase 
{
    var $conn;
    var $result;
    
    function open()
    {
        $this->conn=@sqlite_open($this->db_name);
        if (!$this->conn)
        {
            trigger_error('Failed to open SQLite database.',E_USER_ERROR);
            return false;
        }
        
        return true;
    }
    
    function query()
    {
        $args=func_get_args();
        $query=array_shift($args);
        
        $query=$this->parse_sql($query,$args);
        $this->result=@sqlite_query($query,$this->conn);
       
        if (!$this->result)
        {
            if (DEBUG_MODE) trigger_error('SQL-query was: '.htmlspecialchars($query),E_USER_NOTICE);
            trigger_error(sqlite_error_string(sqlite_last_error($this->conn)),E_USER_ERROR);
            return false;
        }
        
        return $this->result;
    }
    
    function debug_query()
    {
        $args=func_get_args();
        $query=array_shift($args);
        
        $query=$this->parse_sql($query,$args);
        $this->result=@sqlite_query($query,$this->conn);
        
        trigger_error('SQL-query was: '.htmlspecialchars($query),E_USER_NOTICE);
        
        if (!$this->result)
        {
            trigger_error(sqlite_error_string(sqlite_last_error($this->conn)),E_USER_ERROR);
            return false;
        }
        
        return $this->result;
    }
    
    function fetch_array($result=false,$type='ASSOC')
    {
        if (!$result) $result=$this->result;
        $type=$this->get_valid_fetch_type($type);
        
        $fetch_array=@sqlite_fetch_array($result,$type);
        
        if (is_array($fetch_array) && !empty($fetch_array))
        {
            foreach ($fetch_array as $key=>$value)
            {
                $dot=strpos($key,'.');
                if ($dot!==false && strlen($key)>$dot+1)
                {
                    unset($fetch_array[$key]);
                    $new_key=substr($key,$dot+1);
                    $fetch_array[$new_key]=$value;
                }
            }
        }
        
        return $fetch_array;
    }
    
    function fetch_object($result=false)
    {
        if (!$result) $result=$this->result;
        
        $fetch_object=@sqlite_fetch_object($result);
        
        $fetch_array=is_object($fetch_object) ? get_object_vars($fetch_object) : false;
        
        if (is_array($fetch_array) && !empty($fetch_array))
        {
            foreach ($fetch_array as $key=>$value)
            {
                $dot=strpos($key,'.');
                if ($dot!==false && strlen($key)>$dot+1)
                {
                    unset($fetch_object->$key);
                    $new_key=substr($key,$dot+1);
                    $fetch_object->$new_key=$value;
                }
            }
        }
        
        return $fetch_object;
    }
    
    function num_rows($result=false)
    {
        if (!$result) $result=$this->result;
        
        return @sqlite_num_rows($result);
    }
    
    function affected_rows()
    {
        return @sqlite_changes($this->conn);
    }
    
    function last_insert_id($table,$column)
    {
        return $this->query("select last_insert_rowid() as id");
    }
    
    function escape_string($string)
    {
        return sqlite_escape_string($string);
    }
    
    function close()
    {
        @sqlite_close($this->conn);
    }
    
    function get_valid_fetch_type($name)
    {
        switch ($name)
        {
            case 'ASSOC':
                $type=SQLITE_ASSOC;
                break;
            case 'NUM':
                $type=SQLITE_NUM;
                break;
            case 'BOTH':
                $type=SQLITE_BOTH;
                break;
            default:
                $type=SQLITE_ASSOC;
        }
        
        return $type;
    }
    
    function last_error()
    {
        return sqlite_error_string(sqlite_last_error($this->conn));
    }
    
    function version()
    {
        $this->query("select sqlite_version() as version");
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
                    $q_str.=$this->escape_string($field).' INTEGER PRIMARY KEY';
                    break;
                case 'integer':
                    $q_str.=$this->escape_string($field).' INTEGER';        
                    break;
                case 'float':
                    $q_str.=$this->escape_string($field).' REAL';
                    break;
                case 'string':
                    $q_str.=$this->escape_string($field).' CHAR';
                    break;
                case 'text':
                    $q_str.=$this->escape_string($field).' TEXT';
                    break;
                case 'mediumtext':
                    $q_str.=$this->escape_string($field).' TEXT';
                    break;
                case 'longtext':
                    $q_str.=$this->escape_string($field).' TEXT';
                    break;
                case 'binary':
                    $q_str.=$this->escape_string($field).' BLOB';
                    break;
                default:
                    $q_str.=$this->escape_string($field).' '.$this->escape_string($params['type']);
                    break;
            }
            
            if (isset($params['length']) && is_numeric($params['length']) && $params['type']!='id'  && $params['type']!='text' && $params['type']!='binary') 
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
            
            $fields_str[]=$q_str;
        }
        
        $query='CREATE TABLE "'.$this->escape_string($table['name']). '" ( ';
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
        
        $query.=' )';
        
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
