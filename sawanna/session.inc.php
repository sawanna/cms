<?php
defined('SAWANNA') or die();

class CSession
{
    var $db;
    var $ip;
    var $ua;
    var $hash;
    var $id;
    var $value;
    var $life_time;
    var $query_var;
    var $tstamp;
    var $bind_ip;
    var $bind_ua;
    
    function CSession(&$db,&$config)
    {
        $this->db=$db;
        $this->life_time=60*$config->session_life_time;
        $this->query_var='sess';
        $this->tstamp=0;
        $this->bind_ip=$config->session_ip_bind;
        $this->bind_ua=$config->session_ua_bind;
    }
    
    function open($restore=true)
    {
        $ip=getenv('REMOTE_ADDR');
        $pip=getenv('HTTP_X_FORWARDED_FOR');
        if ($pip)
        {
            $ip=$pip;
        } elseif (!$ip)
        {
            $ip='Hidden';
        }
        
        $this->ip=$ip;
        $this->ua=isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
        
        $this->hash=$this->generate_hash();
        $this->value=array();
        
        if (!$this->restore($restore))
        {
            do
                $this->id=$this->generate_id();
            while (!$this->update(true,'locked'));
        } else 
        {
            // need to update to increase timestamp
            if (!$this->update(false,'locked'))
            {
                $this->open(false);
                return;
            }
        }
        
        //setcookie($this->query_var,$this->id,time()+$this->life_time,'/');
        setcookie($this->query_var,$this->id,time()+$this->life_time,'/',null,PROTOCOL=='https',true);
        
        if (isset($_COOKIE[$this->query_var])) $_COOKIE[$this->query_var]=$this->id;
        elseif (isset($_GET[$this->query_var])) $_GET[$this->query_var]=$this->id;
    }
    
    function generate_hash()
    {
        $hash='';
        if ($this->bind_ip) $hash.=$this->ip;
        if ($this->bind_ua) $hash.=$this->ua;
        $hash.=PRIVATE_KEY;

        $hash=md5($hash);

        return $hash;
    }
    
    function generate_id()
    {
       $id=md5($this->hash.time(true).random_chars(32).PRIVATE_KEY);
       
       return $id;
    }
    
    function restore($try=true)
    {
        if (!$try) return false;
        
       $id=isset($_COOKIE[$this->query_var]) ? $_COOKIE[$this->query_var] : false;
       if (!$id && isset($_GET[$this->query_var])) $id=$_GET[$this->query_var];
       
       if ($id)
       {
           $locked=true;
           $start_time=time();
           $exec_time=5; // if session is still locked after 5 seconds of execution we start the new one
           
           do {
               if (time()-$start_time > $exec_time) return false;
               
               $res=$this->db->query("select id,value,tstamp,mark from pre_session where id='$1' and hash='$2' and tstamp>'$3'",$id,$this->hash,time()-$this->life_time);
               $row=$this->db->fetch_array($res);
               if (!empty($row))
               {
                   if ($row['mark']=='locked')
                   {
                       usleep(100000);
                       continue;
                   }
                   
                   $d=new CData();
                   $this->value=$d->do_unserialize($row['value']);
                   $this->id=$row['id'];
                   $this->tstamp=$row['tstamp'];
                   return true;
               }
               
               $locked=false;
           } while ($locked);
       }
       
       return false;
    }
    
    function set($var,$val)
    {
       $this->value[$var]=$val;
    }
    
    function get($var)
    {
        if (isset($this->value[$var])) return $this->value[$var];
        else return false;
    }
    
    function clear($var)
    {
       if (isset($this->value[$var]))
       {
           unset($this->value[$var]);
       }
    }
    
    function update($new=false,$state='')
    {
        if (!is_object($this->db)) return false;
        
       $d=new CData();
       
       $tstamp=$this->tstamp;
       $this->tstamp=time();
       
       if ($new)
       {
           if (!$this->db->query("insert into pre_session (id,hash,value,tstamp,mark) values ('$1','$2','$3','$4','$5')",$this->id,$this->hash,$d->do_serialize($this->value),$this->tstamp,$state))
               return false;
           return true;
       } else
       {
            if ($state == 'locked')
            {
                $this->db->query("update pre_session set value='$1',tstamp='$2',mark='$3' where id='$4' and tstamp='$5' and mark<>'locked'",$d->do_serialize($this->value),$this->tstamp,$state,$this->id,$tstamp);
                return $this->db->affected_rows();  
            } else 
                return $this->db->query("update pre_session set value='$1',tstamp='$2',mark='$3' where id='$4' and tstamp='$5'",$d->do_serialize($this->value),$this->tstamp,$state,$this->id,$tstamp);
       }
    }
    
    function empty_trash()
    {
       $this->db->query("delete from pre_session where tstamp < '$1'",time()-$this->life_time);
    }
    
    function destroy()
    {
        foreach($this->value as $key=>$value)
        {
            unset($this->value[$key]);
        }
    }
    
    function close()
    {
       $this->update();
    }
    
    function cron()
    {
        $this->empty_trash();
    }
    
    function schema()
    {
        $table=array(
            'name' => 'pre_session',
            'fields' => array(
                'id' => array(
                    'type' => 'string',
                    'length' => 32,
                    'not null' => true
                ),
                'hash' => array(
                    'type' => 'string',
                    'length' => 32,
                    'not null' => true
                ),
                'value' => array(
                    'type' => 'longtext',
                    'not null' => true
                ),
                'tstamp' => array(
                    'type' => 'integer',
                    'length' => 10,
                    'not null' => true
                ),
                'mark' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => false
                )
            ),
            'unique' => 'id',
            'index' => 'tstamp'
        );
        
        return $table;
    }
    
    function install(&$db,$config=array())
    {
        if (!$db->create_table($this->schema())) return false;
       
        return true;
    }
    
    function uninstall(&$db)
    {
        if (!$db->query("DROP TABLE IF EXISTS pre_session"));
        
        return true;
    }
}
