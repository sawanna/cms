<?php
defined('SAWANNA') or die();

class CArchive
{
    var $sawanna;
    var $module_id;
    var $start;
    var $limit;
    var $count;
    var $date;
    var $path;
    var $query;
    var $tstamp;
    var $id;
    var $exclude_perms;
    
    function CArchive(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('archive');
        
        $this->start=0;
        $this->count=0;
        $this->limit=10;
        
        $this->date='';
        $this->path='';
        $this->query='';
        
        $this->tstamp=0;
        $this->id=0;
        
        if ($this->sawanna->query != $this->sawanna->config->admin_area) $this->sawanna->tpl->add_script($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/js/datepicker');
    }
    
    function ready()
    {
        if ($this->sawanna->module_exists('comments') && isset($GLOBALS['comments']) && is_object($GLOBALS['comments']) && isset($GLOBALS['comments']->disabled_pathes) && is_array($GLOBALS['comments']->disabled_pathes)) $GLOBALS['comments']->disabled_pathes[]='archive';
        
        if ($this->sawanna->query=='archive') $this->sawanna->tpl->default_title='[str]Archive[/str]';
        
        $event=array();
        $event['caller']='CArchive';
        $event['url']='archive-check-date';
        $event['job']='check_date';
        $event['argument']=1;
        $event['constant']=1;
        $this->sawanna->event->register($event);
        
        $path=$this->sawanna->get_option('archive_nodes_path');
        $query=$path ? $path : $this->sawanna->query;
        
        if (strpos($query,'/')!==false)
        {
            $query=substr($query,0,strpos($query,'/'));
        }
            
        $url=$this->sawanna->constructor->url('archive',false,true);
        
        if (empty($query))
        {
            $url.='/';
        } else
        {
            $url.='/'.$query.'-';
        }
        
        $script='<script language="JavaScript">';
        $script.='function datepicker_callback(formated){';
        $script.='$.post(\''.$this->sawanna->constructor->url('archive-check-date').'\',{date:formated,path:\''.$query.'\'},function(response){ if (parseInt(response)!=response) { alert(response); } else { if (confirm(\'[str]Nodes found[/str]: \'+response+\'. [str]Do you want to see them ?[/str]\')) { window.location.href=\''.$url.'\'+formated; } }});';
        $script.='}';
        $script.='</script>';
        
        $this->sawanna->tpl->add_header($script);
        
        $this->exclude_perms=array();
        
        if (isset($GLOBALS['user']) && is_object($GLOBALS['user']))
        {
            $groups=$GLOBALS['user']->get_user_groups();
            
            foreach($groups as $group_id=>$group_name)
            {
                $perm='user group '.$group_id.' access';
                
                if (!$this->sawanna->has_access($perm))
                {
                    $this->exclude_perms[]=$perm;
                }
            }
        }
    }
    
   function archive($arg)
   {
       if (empty($arg))
       {
           sawanna_error('[str]Date not specified[/str]');
           return;
       }
       
       if (preg_match('/(.+)\/([\d]+)/',$arg,$matches))
       {
           $this->query=$matches[1];
           $this->start=floor($matches[2]);
       } else
       {
           $this->query=$arg;
       }
       
       if (preg_match('/(.+)-([\d]{4}-[\d]{1,2}-[\d]{1,2})/',$this->query,$matches))
       {
           $this->path=trim($matches[1]);
           $this->date=$matches[2];
       } else
       {
           $this->date=$this->query;
       }
       
       $this->tstamp=strtotime($this->date);
       
       if ($this->tstamp>time() || !preg_match('/^[\d]{4}-[\d]{1,2}-[\d]{1,2}$/',$this->date))
       {
           sawanna_error('[str]Invalid date[/str]');
           return;
       }
       
       $html='';
       
       $this->search_nodes($html);
       
       $this->pager($html);
       
       $this->sawanna->tpl->default_title.=' | '.date($this->sawanna->config->date_format,$this->tstamp);
       
       return $html;
   }
   
   function search_nodes(&$html)
   {
        if (empty($this->date)) return ;

        $pub_tstamp_from=$this->tstamp;
        $pub_tstamp_to=$pub_tstamp_from+86399;
        
        $perm_not_in='';
        
        if (!empty($this->exclude_perms))
        {
            foreach($this->exclude_perms as $perm)
            {
                $perm_not_in.="and n.permission <> '".$this->sawanna->db->escape_string($perm)."' ";
            }
        }

        $d=new CData();

        if (empty($this->path) || $this->path==$this->sawanna->config->front_page_path)
        {
           $this->sawanna->db->query("select count(*) as co from pre_nodes n left join pre_node_pathes m on n.id=m.nid where n.language='$1' and n.front<>'0' and n.enabled<>'0' ".$perm_not_in." and n.pub_tstamp between '$2' and '$3'",$this->sawanna->locale->current,$pub_tstamp_from,$pub_tstamp_to);
        } else
        {
            $this->sawanna->db->query("select count(*) as co from pre_nodes n left join pre_node_pathes m on n.id=m.nid where n.language='$1' and n.enabled<>'0' ".$perm_not_in." and m.path like '$2/%' and n.pub_tstamp between '$3' and '$4'",$this->sawanna->locale->current,$this->path,$pub_tstamp_from,$pub_tstamp_to);
        }
        
        $row=$this->sawanna->db->fetch_object();
        if (empty($row)) return;

        $this->count=$row->co;

        if (empty($this->path) || $this->path==$this->sawanna->config->front_page_path)
        {
            $this->sawanna->db->query("select n.id as nid,m.path,n.content,n.meta from pre_nodes n left join pre_node_pathes m on n.id=m.nid where n.language='$1' and n.front<>'0' and n.enabled<>'0' ".$perm_not_in." and n.pub_tstamp between '$2' and '$3' limit $4 offset $5",$this->sawanna->locale->current,$pub_tstamp_from,$pub_tstamp_to,$this->limit,$this->start*$this->limit);
        } else
        {
            $this->sawanna->db->query("select n.id as nid,m.path,n.content,n.meta from pre_nodes n left join pre_node_pathes m on n.id=m.nid where n.language='$1' and n.enabled<>'0' ".$perm_not_in." and m.path like '$2/%' and n.pub_tstamp between '$3' and '$4' limit $5 offset $6",$this->sawanna->locale->current,$this->path,$pub_tstamp_from,$pub_tstamp_to,$this->limit,$this->start*$this->limit);
        }
    
        while ($row=$this->sawanna->db->fetch_object()) 
        {
            if (!empty($row->meta) && $d->is_serialized($row->meta)) $row->meta=$d->do_unserialize($row->meta);

            $link=!empty($row->path) ? $row->path : 'node/'.$row->nid;
            $title=!empty($row->meta['title']) ? htmlspecialchars($row->meta['title']) : $row->nid;
            $teaser=!empty($row->meta['teaser']) ? strip_tags($row->meta['teaser']) : sawanna_substr(strip_tags($row->content),0,200);

            $html.='<div class="archive-item">';
            $html.='<div class="archive-title">'.$this->sawanna->constructor->link($link,$title).'</div>';
            $html.='<div class="archive-content">'.nl2br($teaser).'</div>';
            $html.='</div>';
        }
        
        if (!$this->sawanna->db->num_rows()) 
        {
            sawanna_error('[str]Nodes not found[/str]');
        }
   }
   
    function pager(&$html)
    {
        if (empty($this->count)) return;
        
        $html.= $this->sawanna->constructor->pager($this->start,$this->limit,$this->count,'archive/'.$this->query);
    }

    function check_date()
    {
		header('Content-Type: text/html; charset=utf-8'); 
		
        if (!empty($_POST['date']) && preg_match('/^[\d]{4}-[\d]{1,2}-[\d]{1,2}$/',$_POST['date']))
        {
            $path='';
            if (!empty($_POST['path']))
            {
                $path=trim($_POST['path'],'/');
            }
            
            $pub_tstamp_from=strtotime($_POST['date']);
            
            if ($pub_tstamp_from>time())
            {
                echo $this->sawanna->locale->get_string('Nodes not found');
                die();
            }
            
            $pub_tstamp_to=$pub_tstamp_from+86399;
            
            $perm_not_in='';
            
            if (!empty($this->exclude_perms))
            {
                foreach($this->exclude_perms as $perm)
                {
                    $perm_not_in.="and n.permission <> '".$this->sawanna->db->escape_string($perm)."' ";
                }
            }
        
            if (empty($path) || $path==$this->sawanna->config->front_page_path)
            {
               $this->sawanna->db->query("select count(*) as co from pre_nodes n left join pre_node_pathes m on n.id=m.nid where n.language='$1' and n.front<>'0' and n.enabled<>'0' ".$perm_not_in." and n.pub_tstamp between '$2' and '$3'",$this->sawanna->locale->current,$pub_tstamp_from,$pub_tstamp_to);
            } else
            {
                $this->sawanna->db->query("select count(*) as co from pre_nodes n left join pre_node_pathes m on n.id=m.nid where n.language='$1' and n.enabled<>'0' ".$perm_not_in." and m.path like '$2/%' and n.pub_tstamp between '$3' and '$4'",$this->sawanna->locale->current,$path,$pub_tstamp_from,$pub_tstamp_to);
            }
            
            $row=$this->sawanna->db->fetch_object();
            if (empty($row) || empty($row->co))
            {
                echo $this->sawanna->locale->get_string('Nodes not found');
                die();
            }
            
            echo $row->co;
        }
        
        die();
    }
   
   function calendar()
   {
        $this->id++;
       
        return array(
            'content' => '<div id="archive-calendar-'.$this->id.'"></div>'.$this->get_calendar_init_script()
        );
   }
   
   function get_calendar_init_script()
   {
        $script='<script language="JavaScript">';
        $script.='$(document).ready(function(){';
        $script.='archive_init();';
        $script.='});';
        
        $script.='function archive_init() {';
        $script.='$(\'#archive-calendar-'.$this->id.'\').DatePickerHide();';
        $script.='$(\'#archive-calendar-'.$this->id.'\').DatePicker({';
        $script.='flat: true,';
        $script.='date: \''.date('Y-m-d').'\',';
        $script.='current: \''.date('Y-m-d').'\',';
        $script.='format: \'Y-m-d\',';
        $script.='calendars: 1,';
        $script.='mode: \'single\',';
        if ($this->sawanna->locale->current == 'ru')
            $script.='starts: 1,';
        else
            $script.='starts: 0,';
        $script.='locale: {';
        $script.='days: ["[str]Sunday[/str]", "[str]Monday[/str]", "[str]Tuesday[/str]", "[str]Wednesday[/str]", "[str]Thursday[/str]", "[str]Friday[/str]", "[str]Saturday[/str]", "[str]Sunday[/str]"],';
        $script.='daysShort: ["[str]Sunday[/str]", "[str]Monday[/str]", "[str]Tuesday[/str]", "[str]Wednesday[/str]", "[str]Thursday[/str]", "[str]Friday[/str]", "[str]Saturday[/str]", "[str]Sunday[/str]"],';
        $script.='daysMin: ["[str]Sunday[/str]", "[str]Monday[/str]", "[str]Tuesday[/str]", "[str]Wednesday[/str]", "[str]Thursday[/str]", "[str]Friday[/str]", "[str]Saturday[/str]", "[str]Sunday[/str]"],';
        $script.='months: ["[str]January[/str]", "[str]February[/str]", "[str]March[/str]", "[str]April[/str]", "[str]May[/str]", "[str]June[/str]", "[str]July[/str]", "[str]August[/str]", "[str]September[/str]", "[str]October[/str]", "[str]November[/str]", "[str]December[/str]"],';
        $script.='monthsShort: ["[str]Jan[/str]", "[str]Feb[/str]", "[str]Mar[/str]", "[str]Apr[/str]", "[str]May[/str]", "[str]Jun[/str]", "[str]Jul[/str]", "[str]Aug[/str]", "[str]Sep[/str]", "[str]Oct[/str]", "[str]Nov[/str]", "[str]Dec[/str]"],';
        $script.='weekMin: "[str]wk[/str]"';
        $script.='}';
        $script.='});';
        $script.='}'."\r\n";
        
        $script.='</script>';

        return $script;
   }
   
   function ajaxer(&$scripts,&$outputs)
   {
		$scripts.='archive_init();'."\r\n";
   }
}
