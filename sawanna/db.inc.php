<?php
defined('SAWANNA') or die();

class CDatabase
{
    var $db_host;
    var $db_port;
    var $db_name;
    var $db_user;
    var $db_pass;
    var $db_prefix;
    
    var $last_query;
    
    function CDatabase($db_host,$db_port,$db_name,$db_user,$db_pass,$db_prefix)
    {
        $this->db_host=$db_host;
        $this->db_port=$db_port;
        $this->db_name=$db_name;
        $this->db_user=$db_user;
        $this->db_pass=$db_pass;
        $this->db_prefix=$db_prefix;
        
        $this->last_query='';
    }
    
    function _parse_sql($query,$args=null)
    {
        $this->last_query=preg_replace('/([\x20\x27\x22\x60]{1})pre_/','$1'.$this->db_prefix.'_',$query);
        
        if (!empty($args) && is_array($args))
        {
            preg_match_all('/([^\$]*)\$([\d]+)([^\$]*)/',$this->last_query,$matches);
            if (!empty($matches))
            {
                $this->last_query='';
                for ($i=0;$i<count($matches[0]);$i++)
                {
                    $this->last_query.=$matches[1][$i];
                    $this->last_query.=array_key_exists($matches[2][$i]-1,$args) ? $this->escape_string($args[$matches[2][$i]-1]) : $this->escape_string('$'.$matches[2][$i]);
                    $this->last_query.=$matches[3][$i];
                }
            }
        }
        
        return $this->last_query;
    }
    
    function parse_sql($query,$args=null)
    {
		$this->last_query=preg_replace('/([\x20\x27\x22\x60]{1})pre_/','$1'.$this->db_prefix.'_',$query);

        if (!empty($args) && is_array($args))
        {
            for ($i=count($args);$i>0;$i--)
			{
				$this->last_query=str_replace('$'.$i,$this->escape_string($args[$i-1]),$this->last_query);
			}
        }
        
        return $this->last_query;
    }
    
    function insert($table,$vals)
    {
        if (empty($table) || !is_array($vals) || empty($vals)) return false;
        
        $keys=array();
        $values=array();
        foreach($vals as $field=>$val)
        {
            $keys[]=$this->escape_string($field);
            $values[]="'".$this->escape_string($val)."'";
        }
        
        $query='INSERT INTO '.$this->escape_string($table).' ('.implode(', ',$keys).') VALUES ('.implode(', ',$values).')';
            
        if (!$this->query($query)) return false;
        return true;
    }
    
    function update($table,$vals,$where=array())
    {
        if (empty($table) || !is_array($vals) || empty($vals)) return false;
        
        $updates=array();
        foreach($vals as $field=>$val)
        {
            $updates[]=$this->escape_string($field)."='".$this->escape_string($val)."'";
        }
        
        $query='UPDATE '.$this->escape_string($table).' SET '.implode(', ',$updates);
        
        if (is_array($where) && !empty($where))
        {
            $conditions=array();
            foreach ($where as $operation=>$cond)
            {
                if (!is_array($cond)) return false;
                foreach ($cond as $wfield=>$wval)
                {
                    $conditions[]=$this->escape_string($wfield).' '.$this->escape_string($operation)." '".$this->escape_string($wval)."'";
                }
            }
            $query.=" WHERE ".implode(', ',$conditions);
        }
        
        if (!$this->query($query)) return false;

        return true;
    }
    
    function delete($table,$where=array())
    {
        if (empty($table)) return false;
        
        $query="DELETE FROM ".$this->escape_string($table);
        
        if (is_array($where) && !empty($where))
        {
            $conditions=array();
            foreach ($where as $operation=>$cond)
            {
                if (!is_array($cond)) return false;
                foreach ($cond as $wfield=>$wval)
                {
                    $conditions[]=$this->escape_string($wfield).' '.$this->escape_string($operation)." '".$this->escape_string($wval)."'";
                }
            }
            $query.=" WHERE ".implode(', ',$conditions);
        }
        
        if (!$this->query($query)) return false;

        return true;
    }
    
    function count($table,$where=array())
    {
        if (empty($table)) return false;
        
        $query="SELECT COUNT(*) FROM ".$this->escape_string($table)." AS co";
        
        if (is_array($where) && !empty($where))
        {
            $conditions=array();
            foreach ($where as $operation=>$cond)
            {
                if (!is_array($cond)) return false;
                foreach ($cond as $wfield=>$wval)
                {
                    $conditions[]=$this->escape_string($wfield).' '.$this->escape_string($operation)." '".$this->escape_string($wval)."'";
                }
            }
            $query.=" WHERE ".implode(', ',$conditions);
        }
        
        if (!$this->query($query)) return false;
        $row=$this->fetch_object();

        return $row->co;
    }
}
