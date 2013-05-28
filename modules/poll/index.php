<?php
defined('SAWANNA') or die();

class CPoll
{
    var $sawanna;
    var $module_id;
    var $use_ajax;
    
    function CPoll(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('poll');
        $this->use_ajax=$this->sawanna->get_option('poll_ajax');
    }
    
    function ready()
    {
        if ($this->use_ajax && $this->sawanna->query != $this->sawanna->config->admin_area)
        {
            $event=array();
            $event['caller']='CPoll';
            $event['url']='poll-ajax';
            $event['job']='poll_process_ajax';
            $event['argument']=1;
            $event['constant']=1;
            $this->sawanna->event->register($event);
        
            $this->sawanna->tpl->add_header('<script language="JavaScript">poll_ajax_url="'.$this->sawanna->constructor->url('poll-ajax').'";</script>');
            $this->sawanna->tpl->add_script($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/js/poll');
        }
    }
    
    function fix_widget($poll)
    {
        $wid=$this->sawanna->module->get_widget_id($poll->name);
        if(!$wid) return false;
        
        $tpl='';
        if (!empty($poll->meta['tpl']))
        {
            if ($poll->meta['tpl']=='widget') $tpl='widget';
            elseif ($poll->meta['tpl']=='node') $tpl='node';
        }
        
        if (!empty($tpl)) $this->sawanna->module->widgets[$wid]->tpl=$tpl;
        return true;
    }

    function poll($name)
    {
        if (empty($name)) return ;

        $this->sawanna->db->query("select * from pre_polls where name='$1'",$name);
        $poll=$this->sawanna->db->fetch_object();
        
        if (!$poll) return ;
        
        if (!empty($poll->meta))
        {
            $d=new CData();
            $poll->meta=$d->do_unserialize($poll->meta);
        }
        
        $tpl='';
        if (!empty($poll->meta['tpl']))
        {
            if ($poll->meta['tpl']=='widget') $tpl='widget';
            elseif ($poll->meta['tpl']=='node') $tpl='node';
        }
        
        $html='';
        $fields=array();
        
        $d=new CData();
        
        $this->sawanna->db->query("select * from pre_poll_results where pid='$1' order by id desc",$poll->id);
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row->data)) $row->data=$d->do_unserialize($row->data);
        if (!empty($row->meta)) $row->meta=$d->do_unserialize($row->meta);
        
        if (!empty($row->data) && ((isset($row->meta['users']) && is_array($row->meta['users']) && isset($GLOBALS['user']) && isset($GLOBALS['user']->current->id) && $GLOBALS['user']->current->id>0 && in_array($GLOBALS['user']->current->id,$row->meta['users'])) || isset($_COOKIE['poll-'.$poll->name])))
        {
            $html=$this->get_poll_results($poll,$row->data);
        } else 
        {
            $html=$this->get_poll_fields($poll);
        }
        
        if (!empty($html))
        {
            $title='';
            if (isset($poll->meta['name'][$this->sawanna->locale->current])) $title=htmlspecialchars($poll->meta['name'][$this->sawanna->locale->current]);
            
            if (!empty($tpl))
            {
                $this->fix_widget($poll);
                return array('content'=>'<div class="poll poll-'.htmlspecialchars($poll->name).'">'.$html.'</div>','title'=>$title);
            } else return '<div class="poll poll-'.htmlspecialchars($poll->name).'">'.$html.'</div>';
        }
    }
    
    function get_poll_fields($poll)
    {
        $subject='';
        if (!empty($poll->meta['question'][$this->sawanna->locale->current]))
        {
            $subject=strip_tags($poll->meta['question'][$this->sawanna->locale->current]);
        }
        
        $_fields=array();
        
        $d=new CData();
        
        $this->sawanna->db->query("select * from pre_poll_elements where pid='$1' order by weight",$poll->id);
        while ($row=$this->sawanna->db->fetch_object())
        {
            if (!empty($row->meta))
            {
                $row->meta=$d->do_unserialize($row->meta);
            }
            
            $title=$row->name;
            if (isset($row->meta['name'][$this->sawanna->locale->current])) $title=htmlspecialchars($row->meta['name'][$this->sawanna->locale->current]);
            
            $attributes=array();
            
            $_fields['pform-field-'.htmlspecialchars($row->name).'-element-begin']=array(
                'type' => 'html',
                'value' => '<div class="pform-inline">'
            );
            
            if (isset($poll->meta['multiple']) && $poll->meta['multiple'])
            {
                $_fields['poll-field-'.htmlspecialchars($row->name)]=array(
                    'type' => 'checkbox',
                    'name' => 'poll-'.$row->name,
                    'title' => $title,
                    'description' => '',
                    'attributes' => $attributes,
                    'required' => false
                );
            } else 
            {
               $_fields['poll-field-'.htmlspecialchars($row->name)]=array(
                    'type' => 'radio',
                    'name' => 'poll-'.$poll->name,
                    'title' => $title,
                    'description' => '',
                    'attributes' => $attributes,
                    'value' => $row->name,
                    'required' => false
                );
            }
            
            $_fields['pform-field-'.htmlspecialchars($row->name).'-elements-end']=array(
                'type' => 'html',
                'value' => '</div><div class="clear"></div>'
            );
        }
        
        if (!empty($_fields))
        {
            $fields['poll-subject']=array(
                'type' => 'html',
                'value' => '<div class="poll-subject">'.$subject.'</div>'
            );
            
            $fields+=$_fields;
            
            $fields['poll-pid']=array(
                'type' => 'hidden',
                'value' => $poll->id
            );
            
            $fields['submit']=array(
                'type'=>'submit',
                'value'=>'[str]Vote[/str]'
            );
            
            return '<div class="pform">'.$this->sawanna->constructor->form('poll-'.$poll->name,$fields,'poll_process',!empty($this->sawanna->query) ? $this->sawanna->query : '[home]',$this->sawanna->query,'','CPoll',false,$this->use_ajax ? 'noajaxer' : '').'</div>';
        }
    }
    
    function poll_process()
    {
        if (empty($_POST['poll-pid'])) return array();
        
        $this->sawanna->db->query("select * from pre_polls where id='$1'",$_POST['poll-pid']);
        $poll=$this->sawanna->db->fetch_object();
        
        if (!$poll) return array();
        
        $d=new CData();
         
        if (!empty($poll->meta))
        {
            $poll->meta=$d->do_unserialize($poll->meta);
        }
        
        $title=$poll->name;
        
        $elems=array();
        
        $choice=false;
        
        $res=$this->sawanna->db->query("select * from pre_poll_elements where pid='$1' order by weight",$poll->id);
        while ($row=$this->sawanna->db->fetch_object($res))
        {
            if (!empty($poll->meta['multiple']) && $poll->meta['multiple'])
            {
                $pname='poll-'.$row->name;
                $multiple=true;
            } else {
                $pname='poll-'.$poll->name;
                $multiple=false;
            }
            
            if (!empty($row->meta))
            {
                $row->meta=$d->do_unserialize($row->meta);
            }
            
            $field_title=$row->name;
            
            if (empty($_POST[$pname])) 
            {
                $value=0;
            } elseif(!$multiple && $_POST[$pname]!=$row->name)
            {
                $value=0;
            } elseif($multiple && $_POST[$pname]!='on')
            {
                $value=0;
            } else {
                $value=1;
                $choice=true;
            }

            $elems[$field_title]=$value;
        }
        
        if (!$choice) return array('[str]Your vote not accepted[/str]','no-choice');
       
        $this->save_results($poll,$elems);
        
        return array('[str]Thank you. Your vote accepted[/str]');
    }
    
    function poll_process_ajax()
    {
        if (!empty($_POST['poll_pid']) && !empty($_POST['poll_results']))
        {
            $results=explode(',',$_POST['poll_results']);
            
            if (!empty($results))
            {
                foreach($results as $result)
                {
                    if (empty($result)) continue;
                    if (strpos($result,':')===false) continue;
                    
                    $res=explode(':',$result);
                    if (count($res)!=2) continue;
                    
                    list($var,$val)=$res;
                    if (empty($var) || empty($val)) continue;
                    
                    $_POST[$var]=$val;
                }
                
                $_POST['poll-pid']=$_POST['poll_pid'];
                
                $return=$this->poll_process();
                
                if ($return && is_array($return) && count($return)==1)
                {
                    $d=new CData();
                    
                    $this->sawanna->db->query("select * from pre_polls where id='$1'",$_POST['poll-pid']);
                    $poll=$this->sawanna->db->fetch_object();
                    
                    if (!empty($poll->meta)) $poll->meta=$d->do_unserialize($poll->meta);

                    $this->sawanna->db->query("select data from pre_poll_results where pid='$1' order by id desc",$poll->id);
                    $row=$this->sawanna->db->fetch_object();
                    
                    if (!empty($row->data)) $row->data=$d->do_unserialize($row->data);
                    
                    /*
                    if (function_exists('json_encode'))
                        echo json_encode(array('poll_name'=>$poll->name,'results'=>$this->get_poll_results($poll,$row->data)));
                    else
                        echo sawanna_json_encode(array('poll_name'=>$poll->name,'results'=>$this->get_poll_results($poll,$row->data)));
                    */
                      
                    echo sawanna_json_encode(array('poll_name'=>$poll->name,'results'=>$this->get_poll_results($poll,$row->data)));
                }
            }
        }
        
        die();
    }
    
    function save_results($poll,$elems)
    {
        setcookie('poll-'.$poll->name,time(),time()+31536000,'/');
        
        $d=new CData();
        
        $res=$this->sawanna->db->query("select * from pre_poll_results where pid='$1' order by id desc",$poll->id);
        $row=$this->sawanna->db->fetch_object($res);
        
        if (!empty($row))
        {
            if (!empty($row->data)) $row->data=$d->do_unserialize($row->data);
            if (!empty($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            
            if (!is_array($row->data)) $row->data=array();
            if (!is_array($row->meta)) $row->meta=array();
            
            foreach($elems as $field=>$value)
            {
                if (!isset($row->data[$field])) 
                {
                    $row->data[$field]=$value;
                    continue;
                }
                
                if ($value) $row->data[$field]++;
            }
            
            $row->meta['poll']=$poll->name;
            if (!is_array($row->meta['users'])) $row->meta['users']=array();
            
            if (isset($GLOBALS['user']) && isset($GLOBALS['user']->current->id) && $GLOBALS['user']->current->id>0 && !in_array($GLOBALS['user']->current->id,$row->meta['users']))
            {
                $row->meta['users'][]=$GLOBALS['user']->current->id;
            }
            
            return $this->sawanna->db->query("update pre_poll_results set data='$1', meta='$2' where pid='$3'",$d->do_serialize($row->data),$d->do_serialize($row->meta),$poll->id);
        } else {
            $meta['poll']=$poll->name;
            $meta['users']=array();
            
            if (isset($GLOBALS['user']) && isset($GLOBALS['user']->current->id) && $GLOBALS['user']->current->id>0)
            {
                $meta['users'][]=$GLOBALS['user']->current->id;
            }
            
            return $this->sawanna->db->query("insert into pre_poll_results (pid,data,meta) values ('$1','$2','$3')",$poll->id,$d->do_serialize($elems),$d->do_serialize($meta));
        }
    }
    
    function get_poll_results($poll,$results)
    {
        $html='';
        
        if (empty($results) || !is_array($results)) return;
        
        $subject='';
        if (!empty($poll->meta['question'][$this->sawanna->locale->current]))
        {
            $subject=strip_tags($poll->meta['question'][$this->sawanna->locale->current]);
        }

        $fields=array();
        $total=0;
        
        $d=new CData();
        
        $this->sawanna->db->query("select * from pre_poll_elements where pid='$1' order by weight",$poll->id);
        while ($row=$this->sawanna->db->fetch_object())
        {
            if (!empty($row->meta))
            {
                $row->meta=$d->do_unserialize($row->meta);
            }
            
            $title=$row->name;
            if (isset($row->meta['name'][$this->sawanna->locale->current])) $title=htmlspecialchars($row->meta['name'][$this->sawanna->locale->current]);
            
            if (!array_key_exists($row->name,$results) || !is_numeric($results[$row->name]))
            {
                $fields[$title]=0;
                continue;
            }
            
            $fields[$title]=$results[$row->name];
            $total+=$results[$row->name];
        }
        
        if (!empty($fields))
        {
            $html.='<div class="poll-subject">'.$subject.'</div>';
            
            foreach($fields as $field=>$value)
            {
                $percent=round($value/$total*100);
                $html.='<div class="poll-results-field"><label>'.$field.'</label><span class="separator">-</span><span class="value">'.$value.'</span><img src="'.SITE.'/misc/blank.gif" style="width:'.$percent.'%" /></div><div class="poll-space"></div>';
            }
            
            return '<div class="poll-result">'.$html.'</div>';
        }
    }

	function ajaxer(&$scripts,&$outputs)
	{
		if ($this->use_ajax && $this->sawanna->query != $this->sawanna->config->admin_area)
        {
			$scripts.='poll_init();'."\r\n";
		}
	}
}
