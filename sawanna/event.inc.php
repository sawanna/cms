<?php
defined('SAWANNA') or die();

class CEvent
{
    var $db;
    var $session;
    
    function CEvent(&$db,&$session)
    {
        $this->db=$db;
        $this->session=$session;
    }
    
    function register($event)
    {
        if (!is_array($event)) return false;
        
        $url=!empty($event['url']) ? $event['url'] : '*';
        $caller=!empty($event['caller']) ? $event['caller'] : '';
        $weight=!empty($event['weight']) ? $event['weight'] : 0;
        $job=!empty($event['job']) ? $event['job'] : '';
        $argument=!empty($event['argument']) ? $event['argument'] : '';
        $constant=!empty($event['constant']) ? $event['constant'] : 0;
        
        if (!is_object($this->db)) return false;

        $this->db->query("select id from pre_events where url='$1' and caller='$2' and weight='$3' and job='$4' and argument='$5' and constant='$6' and sid='$7'",$url,$caller,$weight,$job,$argument,$constant,$this->session->id);
        if ($this->db->num_rows())
            return false;
                
        if (!$this->db->query("insert into pre_events (url,caller,weight,job,argument,constant,sid) values ('$1','$2','$3','$4','$5','$6','$7')",$url,$caller,$weight,$job,$argument,$constant,$this->session->id))
            return false;
            
        return true;
    }
    
    function remove($event='',$id=0)
    {
        if (empty($id))
        {
            if (empty($event) || !is_array($event)) 
            {
                trigger_error('Cannot remove event. No data recieved',E_USER_WARNING);
                return false;
            }
            
            $url=!empty($event['url']) ? $event['url'] : '*'; 
            $caller=!empty($event['caller']) ? $event['caller'] : '';
            $weight=!empty($event['weight']) ? $event['weight'] : 0;
            $job=!empty($event['job']) ? $event['job'] : '';
            $argument=!empty($event['argument']) ? $event['argument'] : '';
            $constant=!empty($event['constant']) ? $event['constant'] : 0;
            
            if (!$this->db->query("delete from pre_events where url='$1' and caller='$2' and weight='$3' and job='$4' and argument='$5' and constant='$6' and sid='$7'",$url,$caller,$weight,$job,$argument,$constant,$this->session->id))
            {
                trigger_error('Cannot remove event {url:'.$url.',caller:'.$caller.',weight:'.$weight.',job:'.$job.',argument:'.$argument.',constant:'.$constant.',sid:'.$this->session->id.'}',E_USER_WARNING);
                return false;
            }
            
            if (!$this->db->affected_rows()) trigger_error('Cannot remove event {url:'.$url.',caller:'.$caller.',weight:'.$weight.',job:'.$job.',argument:'.$argument.',constant:'.$constant.',sid:'.$this->session->id.'}',E_USER_WARNING);
        } else 
        {
            if (!$this->db->query("delete from pre_events where id='$1' and sid='$2'",$id,$this->session->id))
            {
                trigger_error('Cannot remove event {id:'.$id.',sid:'.$this->session->id.'}',E_USER_WARNING);
                return false;
            }
            
            if (!$this->db->affected_rows()) trigger_error('Cannot remove event {id:'.$id.',sid:'.$this->session->id.'}',E_USER_NOTICE);
        }
                
         return true;
    }
    
    function event_exists($event)
    {
        if (!is_array($event)) return false;
        
        $url=!empty($event['url']) ? $event['url'] : '*';
        $caller=!empty($event['caller']) ? $event['caller'] : '';
        $weight=!empty($event['weight']) ? $event['weight'] : 0;
        $job=!empty($event['job']) ? $event['job'] : '';
        $argument=!empty($event['argument']) ? $event['argument'] : '';
        $constant=!empty($event['constant']) ? $event['constant'] : 0;
        
        if (!is_object($this->db)) return false;

        $this->db->query("select id from pre_events where url='$1' and caller='$2' and weight='$3' and job='$4' and argument='$5' and constant='$6' and sid='$7'",$url,$caller,$weight,$job,$argument,$constant,$this->session->id);
        if ($this->db->num_rows())
            return true;

        return false;
    }
    
    function get_callers($location)
    {
        $callers=array();
        
        if (is_object($this->db))
        {
            $res=$this->db->query("select * from pre_events where (url='$1' or url='*') and sid='$2' order by weight",$location,$this->session->id);
            while ($row=$this->db->fetch_object($res))
            {
                $callers[$row->caller]=$row;
            }
        }
        
        return $callers;
    }
    
    function remove_url($url)
    {
        if (!$this->db->query("delete from pre_events where url='$1' and sid='$2'",$url,$this->session->id))
            return false;
            
        return true;
    }
    
    function remove_caller($caller)
    {
        if (!$this->db->query("delete from pre_events where caller='$1' and sid='$2'",$caller,$this->session->id))
            return false;
            
        return true;
    }
    
    function empty_trash()
    {
        $esid=array();
        $this->db->query('select distinct sid from pre_events');
        while($row=$this->db->fetch_object())
        {
            $esid[]=$row->sid;
        }
        
        $sid=array();
        $this->db->query('select id from pre_session');
        while($row=$this->db->fetch_object())
        {
            $sid[]=$row->id;
        }
        
        $insid=array_diff($esid,$sid);
        
        $this->db->query("delete from pre_events where sid in('".implode("','",$insid)."')");
    }
    
    function cron()
    {
        $this->empty_trash();
    }
    
    function schema()
    {
        $table=array(
            'name' => 'pre_events',
            'fields' => array(
                'id' => array(
                    'type' => 'id',
                    'length' => 10,
                    'not null' => true
                ),
                'url' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => true
                ), 
                'caller' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => true
                ),
                'weight' => array(
                    'type' => 'integer',
                    'length' => 4,
                    'not null' => true
                ),
                'job' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => false
                ),
                'argument' => array(
                    'type' => 'text',
                    'not null' => false
                ),
                'constant' => array(
                    'type' => 'integer',
                    'length' => 4,
                    'not null' => false
                ),
                'sid' => array(
                    'type' => 'string',
                    'length' => 32,
                    'not null' => true
                )
            ),
            'primary key' => 'id'
        );
        
        return $table;
    }
    
    function install(&$db)
    {
        if (!$db->create_table($this->schema())) return false;
       
        return true;
    }
    
    function uninstall(&$db)
    {
        if (!$db->query("DROP TABLE IF EXISTS pre_events")) return false;
        
        return true;
    }
}
