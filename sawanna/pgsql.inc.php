<?php
defined('SAWANNA') or die();

class CDB extends CDatabase 
{
    var $conn;
    var $result;
    
    function open()
    {
        $this->conn=@pg_connect('host='.$this->db_host.' port='.$this->db_port.' dbname='.$this->db_name.' user='.$this->db_user.' password='.$this->db_pass);
        if (!$this->conn)
        {
            trigger_error('Failed to connect to PostgreSQL server. '.pg_last_error(),E_USER_ERROR);
            return false;
        }
       
        return true;
    }
    
    function query()
    {
        $args=func_get_args();
        $query=array_shift($args);
        
        $query=$this->parse_sql($query,$args);
        $this->result=@pg_query($this->conn,$query);
        
        if (!$this->result)
        {
            if (DEBUG_MODE) trigger_error('SQL-query was: '.htmlspecialchars($query),E_USER_NOTICE);
            trigger_error(pg_last_error($this->conn),E_USER_ERROR);
            return false;
        }
        
        return $this->result;
    }
    
    function debug_query()
    {
        $args=func_get_args();
        $query=array_shift($args);
        
        $query=$this->parse_sql($query,$args);
        $this->result=@pg_query($this->conn,$query);
        
        trigger_error('SQL-query was: '.htmlspecialchars($query),E_USER_NOTICE);
        
        if (!$this->result)
        {
            trigger_error(pg_last_error($this->conn),E_USER_ERROR);
            return false;
        }
        
        return $this->result;
    }
    
    function fetch_array($result=false,$type='ASSOC')
    {
        if (!$result) $result=$this->result;
        $type=$this->get_valid_fetch_type($type);
        
        return @pg_fetch_array($result,null,$type);
    }
    
    function fetch_object($result=false)
    {
        if (!$result) $result=$this->result;
        
        return @pg_fetch_object($result);
    }
    
    function num_rows($result=false)
    {
        if (!$result) $result=$this->result;
        
        return @pg_num_rows($result);
    }
    
    function affected_rows()
    {
        return @pg_affected_rows($this->result);
    }
    
    function last_insert_id($table,$column)
    {
        return $this->query("select currval(pg_get_serial_sequence('".$this->escape_string($this->db_prefix)."_$1','$2')) as id",$table,$column);
    }
    
    function escape_string($string)
    {
        return pg_escape_string($string);
    }
    
    function close()
    {
        if (!@pg_close($this->conn))
        {
            trigger_error('Failed to close PostgreSQL connection.',E_USER_ERROR);
        }
    }
    
    function get_valid_fetch_type($name)
    {
        switch ($name)
        {
            case 'ASSOC':
                $type=PGSQL_ASSOC;
                break;
            case 'NUM':
                $type=PGSQL_NUM;
                break;
            case 'BOTH':
                $type=PGSQL_BOTH;
                break;
            default:
                $type=PGSQL_ASSOC;
        }
        
        return $type;
    }
    
    function last_error()
    {
        return pg_last_error($this->conn);
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
                    $q_str.=$this->escape_string($field).' SERIAL';
                    break;
                case 'integer':
                    $q_str.=$this->escape_string($field).' INTEGER';        
                    break;
                case 'float':
                    $q_str.=$this->escape_string($field).' NUMERIC';
                    break;
                case 'string':
                    $q_str.=$this->escape_string($field).' CHARACTER VARYING';
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
                    $q_str.=$this->escape_string($field).' BYTEA';
                    break;
                default:
                    $q_str.=$this->escape_string($field).' '.$this->escape_string($params['type']);
                    break;
            }
            
            if (isset($params['length']) && is_numeric($params['length']) && $params['type']!='id' && $params['type']!='integer' && $params['type']!='text' && $params['type']!='binary') 
            {
                if ($params['type'] != 'float')
                    $q_str.=' ('.$this->escape_string($params['length']).')';
                else
                    $q_str.=' ('.$this->escape_string($params['length']).',2)';
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
        
        if (isset($table['primary key']))
        {
            $query.=', PRIMARY KEY ( '.$this->escape_string($table['primary key']).' )';
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
