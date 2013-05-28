<?php
defined('SAWANNA') or die();

class CLog
{
    var $db;
    var $period;
    var $ip;
    var $referer;
    var $ua;
    var $query;
    var $sid;
    
    function CLog(&$db,$log_period_in_days)
    {
        $this->db=$db;
        $this->period=3600*24*$log_period_in_days;
    }
    
    function init(&$session)
    {
        $this->ip=$session->ip;
        $this->ua=$session->ua;
        $this->sid=$session->id;
        $this->referer=!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-';
        $this->query=!empty($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '/';
    }
    
    function add_record()
    {
        if (is_object($this->db))
            $this->db->query("insert into pre_log (query,referer,ip,ua,sid,tstamp) values ('$1','$2','$3','$4','$5','$6')",$this->query,$this->referer,$this->ip,$this->ua,$this->sid,time());
    }
    
    function get_records($start=0,$limit=10)
    {
        return $this->db->query("select * from pre_log order by tstamp desc limit $2 offset $1",$start,$limit);
    }
    
    function get_today_records_count($type='all')
    {
        switch ($type)
        {
            case 'ip':
                //$return = $this->db->query("select count(distinct ip) as count from pre_log where tstamp>'$1'",mktime(0,0,0));
                // sqlite query
                $return = $this->db->query("select count(s.ip) as count from (select distinct ip from pre_log where tstamp>'$1') s",mktime(0,0,0));
            break;
            case 'ua':
                //$return = $this->db->query("select count(distinct ua) as count from pre_log where tstamp>'$1'",mktime(0,0,0));
                // sqlite query
                $return = $this->db->query("select count(s.ua) as count from (select distinct ua from pre_log where tstamp>'$1') s",mktime(0,0,0));
            break;
            case 'referer':
                //$return = $this->db->query("select count(distinct referer) as count from pre_log where tstamp>'$1' and referer<>'-' and referer not like '$2%'",mktime(0,0,0),SITE);
                // sqlite query
                $return = $this->db->query("select count(s.referer) as count from (select distinct referer from pre_log where tstamp>'$1' and referer<>'-' and referer not like '$2%') s",mktime(0,0,0),SITE);
            break;
            case 'all':
            default:
                $return = $this->db->query("select count(*) as count from pre_log where tstamp>'$1'",mktime(0,0,0));
            break;
        }
        
        $row=$this->db->fetch_object();
        if (!empty($row)) return $row->count;
        else return 0;
    }
    
    function get_today_hosts_count()
    {
        $co=0;
        //$this->db->query("select count(distinct ip) as count from pre_log where tstamp>'$1' group by ua",mktime(0,0,0));
        // sqlite query
        $this->db->query("select ua,ip from pre_log where tstamp>'$1'",mktime(0,0,0));
        $ips=array();
        $uas=array();
        while ($row=$this->db->fetch_object())
        {
            if (!in_array($row->ip,$ips) || !in_array($row->ua,$uas))
            {
                if (!in_array($row->ip,$ips)) $ips[]=$row->ip;
                if (!in_array($row->ua,$uas)) $uas[]=$row->ua;
                $co++;
            }
        }
        
        return $co;
    }
    
    function get_records_count()
    {
        $this->db->query("select count(*) as count from pre_log");
        $row=$this->db->fetch_object();
        if (!empty($row)) return $row->count;
        else return 0;
    }
    
    function get_today_records_info($type='all')
    {
        switch ($type)
        {
            case 'ip':
                // sqlite query
                $return = $this->db->query("select ip from (select distinct ip from pre_log where tstamp>'$1') s",mktime(0,0,0));
            break;
            case 'ua':
                // sqlite query
                $return = $this->db->query("select ua from (select distinct ua from pre_log where tstamp>'$1') s",mktime(0,0,0));
            break;
            case 'referer':
                // sqlite query
                $return = $this->db->query("select referer from (select distinct referer from pre_log where tstamp>'$1' and referer<>'-' and referer not like '$2%') s",mktime(0,0,0),SITE);
            break;
            case 'all':
            default:
                $return = false;
            break;
        }
        
        return $return;
    }
    
    function empty_trash()
    {
        return $this->db->query("delete from pre_log where tstamp < '$1'",time()-$this->period);
    }
    
    function clear_all()
    {
        return $this->db->query("delete from pre_log");
    }
    
    function cron()
    {
        $this->empty_trash();
    }
    
    function schema()
    {
        $table=array(
            'name' => 'pre_log',
            'fields' => array(
                'id' => array(
                    'type' => 'id',
                    'length' => 10,
                    'not null' => true
                ),
                'query' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => true
                ),
                'referer' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => true
                ),
                'ip' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => true
                ),
                'ua' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => true
                ),
                'sid' => array(
                    'type' => 'string',
                    'length' => 32,
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
        if (!$db->query("DROP TABLE IF EXISTS pre_log")) return false;
        
        return true;
    }
}