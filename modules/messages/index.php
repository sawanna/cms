<?php
defined('SAWANNA') or die();

class CMessages
{
    var $sawanna;
    var $module_id;
    var $max_length;
    var $word_max_length;
    var $start;
    var $limit;
    var $count;
    var $user;
    
    function CMessages(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->max_length=5000;
        
        $this->word_max_length=50;
        
        $this->limit=10;
        
        $this->start=is_numeric($this->sawanna->arg) && $this->sawanna->arg>0 ? floor($this->sawanna->arg) : 0;
        $this->count=0;
        
        $this->module_id=$this->sawanna->module->get_module_id('messages');

    }
    
    function ready()
    {
        if (isset($GLOBALS['user']) && is_object($GLOBALS['user']) && ($GLOBALS['user'] instanceof CUser))
        {
            $this->user=&$GLOBALS['user'];
            
            if ($this->sawanna->query=='user' && !empty($this->sawanna->arg) && is_numeric($this->sawanna->arg) && $this->sawanna->has_access('send user messages')) $this->user->profile_links[]=$this->sawanna->constructor->link('user/send/'.floor($this->sawanna->arg),'[str]Send private message[/str]');
        }
        
        $inbox_co=0;
        $outbox_co=0;
        if (is_object($this->user) && $this->user->logged()) 
        {
            $inbox_co=$this->get_inbox_messages_count($this->user->current->id,true);
            $outbox_co=$this->get_outbox_messages_count($this->user->current->id,true);
        }
        
        $inbox_str='';
        if (!empty($inbox_co)) $inbox_str=' ('.$inbox_co.')';
        $outbox_str='';
        if (!empty($outbox_co)) $outbox_str=' ('.$outbox_co.')';
        
        if (is_object($this->user) && isset($this->user->panel) && is_array($this->user->panel) && $this->user->logged() && !$this->sawanna->access->check_su())
        { 
            $this->user->panel[]=$this->sawanna->constructor->link('user/send','[str]New private message[/str]');
            $this->user->panel[]=$this->sawanna->constructor->link('user/inbox','[str]Incoming messages[/str]'.$inbox_str);
            $this->user->panel[]=$this->sawanna->constructor->link('user/outbox','[str]Outcoming messages[/str]'.$outbox_str);
        }
        
        if ($this->sawanna->query=='user/inbox') $this->sawanna->tpl->default_title='[str]My incoming messages[/str]';
        if ($this->sawanna->query=='user/outbox') $this->sawanna->tpl->default_title='[str]My outcoming messages[/str]';
        if ($this->sawanna->query=='user/send') $this->sawanna->tpl->default_title='[str]New private message[/str]';
    }
    
    function get_inbox_messages_count($uid,$new=false)
    {
        if (empty($uid) || !is_numeric($uid)) return;
        
        $mark='';
        if ($new) $mark=" and mark='new'";
        
        $this->sawanna->db->query("select count(*) as co from pre_messages where to_uid='$1'".$mark,$uid);
        $row=$this->sawanna->db->fetch_object();
        
        if (empty($row)) return;
        
        return $row->co;
    }
    
    function get_outbox_messages_count($uid,$new=false)
    {
        if (empty($uid) || !is_numeric($uid)) return;
        
        $mark='';
        if ($new) $mark=" and mark='new'";
        
        $this->sawanna->db->query("select count(*) as co from pre_messages where from_uid='$1'".$mark,$uid);
        $row=$this->sawanna->db->fetch_object();
        
        if (empty($row)) return;
        
        return $row->co;
    }
    
    function inbox()
    {
         if (!is_object($this->user) || !$this->user->logged())  return ;
         
         $this->count=$this->get_inbox_messages_count($this->user->current->id);
         
         if ($this->start*$this->limit >= $this->count) $this->start=0;
         
         if (empty($this->count)) 
         {
             sawanna_error('[str]No messages[/str]');
             return;
         }
         
         $output_items=array(
            'widget_prefix'=>'<div class="messages-caption"><a name="messages">[str]Incoming messages[/str]:</a></div>',
            'widget_items'=>array(),
            'widget_suffix'=>$this->sawanna->constructor->pager($this->start,$this->limit,$this->count,'user/inbox','#messages')
            );

         $new=false;
         
         $res=$this->sawanna->db->query("select * from pre_messages where to_uid='$1' order by created desc limit $2 offset $3",$this->user->current->id,$this->limit,$this->start*$this->limit);
         while ($row=$this->sawanna->db->fetch_object($res))
         {
             $username='';
             $user_avatar='';
             $user_gender='';
             
             $usermeta=array();
             if ($row->from_uid>0)
             {
                 $user=$this->user->get_user($row->from_uid);
                 $usermeta=$this->user->get_user_meta($user);
             } elseif ($row->from_uid==-1)
             {
                 $usermeta=$this->user->get_super_user();
             } else
             {
                 $usermeta=$this->user->get_anonymous_user();
             }
             
             if (!empty($usermeta))
             {
                 $username=$usermeta['name'];
                 $user_avatar=$usermeta['avatar'];
                 $user_gender=$usermeta['gender'];
             }
            
             $row->content=nl2br(strip_tags($row->content));
             
             $status = $row->mark=='new' ? '<div class="message-status">[str]New[/str]</div>' : '';
             
             $output_items['widget_items'][]=array(
                'username' => $username,
                'userlink' => $row->from_uid>0 && $this->sawanna->has_access('view user profile') ? $this->sawanna->constructor->link('user/'.$row->from_uid,$username,'',array('target'=>'_blank')) : $username,
                'user_avatar'=>!empty($user_avatar) ? '<img src="'.$user_avatar.'" width="100" />' : '',
                'user_gender'=>!empty($user_gender) ? '<label>[str]Gender[/str]:</label> '.$user_gender : '',
                'content' => $row->content,
                'subject' => $status.strip_tags($row->subject),
                'created' => date($this->sawanna->config->date_format,$row->created),
                'delete' => $this->sawanna->has_access('send user messages') ? '<a href="#messages" id="mess-del-link-'.$row->id.'" onclick="if (confirm(\'[str]Are you sure ?[/str]\')) { $.post(\''.$this->sawanna->constructor->url('user/mess-del').'\',{mid:\''.$row->id.'\'},function(data){if (data != \'[str]Message was deleted[/str]\') { alert(data); } else { $(\'#mess-del-link-'.$row->id.'\').closest(\'.messages-list-block\').remove(); if ($(\'.messages-list-block\').length==0) { window.location.href=\''.$this->sawanna->constructor->url('user/inbox').'\'; } }}); }">[str]Delete[/str]</a>' : '',
                'reply' => $this->sawanna->has_access('send user messages') && $row->from_uid>0 ? $this->sawanna->constructor->link('user/reply/'.$row->id,'[str]Reply[/str]') : ''
             );
             
             if ($row->mark=='new') $new=true;
         }
         
         if ($this->sawanna->has_access('send user messages'))
         {
             $event=array(
                'url' => 'user/mess-del',
                'caller' => 'messages',
                'job' => 'delete',
                'constant' => 1
            );
            
            $this->sawanna->event->register($event);
         }
         
         if ($new)
         {
             $this->sawanna->db->query("update pre_messages set mark='' where to_uid='$1'",$this->user->current->id);
         }
         
         return $output_items;
    }
    
    function delete()
    {
        if (!$this->sawanna->has_access('send user messages')) die();
        if (!is_object($this->user) || !$this->user->logged())  die();
        if (empty($_POST['mid']) || !is_numeric($_POST['mid'])) die();
        
        $this->sawanna->db->query("select * from pre_messages where id='$1'",$_POST['mid']);
        $row=$this->sawanna->db->fetch_object();
        
        if (empty($row))
        {
            echo $this->sawanna->locale->get_string('Message not found');
            die();
        }
        
        if ($row->to_uid != $this->user->current->id)
        {
            echo $this->sawanna->locale->get_string('Permission denied');
            die();
        }
        
        if (!$this->sawanna->db->query("delete from pre_messages where id='$1' and to_uid='$2'",$_POST['mid'],$this->user->current->id))
        {
            echo $this->sawanna->locale->get_string('Could not delete message. Try later');
            die();
        }
        
        echo $this->sawanna->locale->get_string('Message was deleted');
        die();
    }
    
    function outbox()
    {
         if (!is_object($this->user) || !$this->user->logged())  return ;
         
         $this->count=$this->get_outbox_messages_count($this->user->current->id);
         
         if (empty($this->count)) 
         {
             sawanna_error('[str]No messages[/str]');
             return;
         }
         
         $remark='<div class="messages-remark">* [str]Displaying only that messages which are not deleted by recipient[/str]</div>';
         
         $output_items=array(
            'widget_prefix'=>'<div class="messages-caption"><a name="messages">[str]Outcoming messages[/str]:</a></div>',
            'widget_items'=>array(),
            'widget_suffix'=>$this->sawanna->constructor->pager($this->start,$this->limit,$this->count,'user/outbox','#messages').$remark
            );

         $res=$this->sawanna->db->query("select * from pre_messages where from_uid='$1' order by created desc limit $2 offset $3",$this->user->current->id,$this->limit,$this->start*$this->limit);
         while ($row=$this->sawanna->db->fetch_object($res))
         {
             $username='';
             $user_avatar='';
             $user_gender='';
             
             if ($row->to_uid>0)
             {
                 $user=$this->user->get_user($row->to_uid);
                 $usermeta=$this->user->get_user_meta($user);
                 
                 $username=$usermeta['name'];
                 $user_avatar=$usermeta['avatar'];
                 $user_gender=$usermeta['gender'];
             }

             $row->content=nl2br(strip_tags($row->content));
             
             $status = $row->mark=='new' ? '<div class="message-status">[str]Not read yet[/str]</div>' : '';
             
             $output_items['widget_items'][]=array(
                'username' => $username,
                'userlink' => $row->to_uid>0 && $this->sawanna->has_access('view user profile') ? $this->sawanna->constructor->link('user/'.$row->to_uid,$username,'',array('target'=>'_blank')) : $username,
                'user_avatar'=>!empty($user_avatar) ? '<img src="'.$user_avatar.'" width="100" />' : '',
                'user_gender'=>!empty($user_gender) ? '<label>[str]Gender[/str]:</label> '.$user_gender : '',
                'content' => $row->content,
                'subject' => $status.strip_tags($row->subject),
                'created' => date($this->sawanna->config->date_format,$row->created)
             );
         }

         return $output_items;
    }
    
    function login_autocomplete()
    {
        if (!$this->sawanna->has_access('view user profile')) die();
        
        if (!empty($_POST['q']) && valid_login($_POST['q']))
        {
            $this->sawanna->db->query("select login from pre_users where login like '$1%' limit 10",$_POST['q']);
            while ($row=$this->sawanna->db->fetch_object()) {
                   echo $row->login."\n";
            }
        }
        
        die();
    }
    
    function messages_form($arg=0)
    {
        if (!$this->sawanna->has_access('send user messages'))  return ;
        
        if ($this->sawanna->query=='user/reply' && (!is_object($this->user) || !$this->user->logged() || $this->sawanna->access->check_su()))
        {
            $this->sawanna->constructor->redirect('user/send');
        }
        
        $fields=array();
        
        $user=false;
        $reply=false;
    
        if (!empty($arg) && is_numeric($arg))
        {
            if ($this->sawanna->query=='user/send') $user=$this->user->get_user($arg);
            
            if ($this->sawanna->query=='user/reply')
            {
                $this->sawanna->db->query("select * from pre_messages where id='$1'",$arg);
                $row=$this->sawanna->db->fetch_object();
                
                if (empty($row) || $row->to_uid != $this->user->current->id)
                {
                    sawanna_error('[str]Message not found[/str]');
                } else
                {
                    $user=$this->user->get_user($row->from_uid);
                    $reply=true;
                }
            }
        }
        
        $fields['message-recipient']=array(
            'type' => 'text',
            'title' => '[str]Recipient[/str]',
            'description' => '[str]Enter user login of recipient[/str]',
            'default' => $user ? $user->login : '',
            'attributes' => $user ? array('readonly' => 'readonly') : array(),
            'required' => true
        );
        
        if (!$user && $this->sawanna->has_access('view user profile'))
        {
            $fields['message-recipient']['autocomplete'] = array('url'=>'user-messages-login-autocomplete','caller'=>'messages','handler'=>'login_autocomplete');
        }
        
        $fields['message-subject']=array(
            'type' => 'text',
            'title' => '[str]Subject[/str]',
            'description' => '[str]Enter message subject here[/str]',
            'default' => $reply ? 'Re: '.$row->subject : '',
            'required' => true
        );
       
        $fields['message-content']=array(
            'type' => 'textarea',
            'title' => '[str]Your message[/str]',
            'description' => '[str]Enter your message text here[/str]',
            'attributes' => array('style'=>'width: 100%','rows'=>10,'cols'=>40),
            'default' => $reply ? "\r\n\r\n\r\n\r\n----------\r\n".'-- '.str_replace("\r\n","\r\n-- ",$row->content) : '',
            'required' => true
        );
        
        $fields['message-captcha']=array(
            'type' => 'captcha',
            'name' => 'captcha',
            'title' => '[str]CAPTCHA[/str]',
            'required' => true
        );
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        return $this->sawanna->constructor->form('messages-form',$fields,'messages_form_process',(is_object($this->user) && $this->user->logged() && !$this->sawanna->access->check_su()) ? 'user/outbox' : 'user/send','user/send','messageForm','CMessages');
    }
    
    function messages_form_process()
    {
        if (!$this->sawanna->has_access('send user messages'))  return array('Permission denied',array('message-content'));

        $user=false;
        if ($_POST['message-recipient']=='*' && $this->sawanna->has_access('su')) $user=true;

        if (!$user && $_POST['message-recipient']!='*') $user=$this->user->get_user_by_login($_POST['message-recipient']);
        
        if (!$user) return array('[str]Invalid user[/str]',array('message-recipient'));
        
        if ($this->max_length && sawanna_strlen($_POST['message-content']) > $this->max_length) return array('[str]Message text is too long[/str]',array('message-content'));
        
        $_POST['message-subject']=htmlspecialchars($_POST['message-subject']);
        if (sawanna_strlen($_POST['message-subject'])>50) $_POST['message-subject']=sawanna_substr($_POST['message-subject'],0,50).'...';
        
        $this->fix_message($_POST['message-content']);
        
        $uid=$this->sawanna->access->check_su() ? -1 : 0;
        
        if ($_POST['message-recipient']!='*')
        {
            if (!$this->set((!empty($GLOBALS['user']->current->id) ? $GLOBALS['user']->current->id : $uid),$user->id,$_POST['message-subject'],$_POST['message-content']))
            {
                return array('[str]Could not save your message. Try later[/str]',array('message-content'));
            }
            
            $this->send_notification($user);
        } else 
        {
            ini_set('max_execution_time',0);
            
            $res=$this->sawanna->db->query("select u.* from pre_users u left join pre_user_groups g on g.id=u.group_id where u.enabled<>'0' and u.active<>'0' and g.enabled<>'0' order by u.created");
            while($user=$this->sawanna->db->fetch_object($res))
            {
                if (!$this->set((!empty($GLOBALS['user']->current->id) ? $GLOBALS['user']->current->id : $uid),$user->id,$_POST['message-subject'],$_POST['message-content']))
                {
                    return array('[str]Could not save your message. Try later[/str]',array('message-content'));
                }
                
                $this->send_notification($user);
            }
        }
        
        return array('[str]Your message sent[/str]');
    }
    
    function set($from_uid,$to_uid,$subject,&$content)
    {
        if (empty($subject) || empty($content) || empty($to_uid)) return false;
        
        if (empty($from_uid)) $uid=0;

        return $this->sawanna->db->query("insert into pre_messages (from_uid,to_uid,subject,content,created,mark) values ('$1','$2','$3','$4','$5','new')",$from_uid,$to_uid,$subject,$content,time());
    }
    
    function fix_message(&$content)
    {
        $content=htmlspecialchars($content);
        
        if ($this->max_length && sawanna_strlen($content) > $this->max_length) $content=sawanna_substr($content,0,$this->max_length).' ...';
        
        if ($this->word_max_length)
        {
            while (preg_match('/([^\x20\xA\xD]{'.preg_quote($this->word_max_length+1).',})/us',$content,$matches))
            {
                $content=str_replace($matches[1],sawanna_substr($matches[1],0,$this->word_max_length).' '.sawanna_substr($matches[1],$this->word_max_length),$content);
            }
        }
    }
    
    function send_notification($user)
    {
        $usermeta=$this->user->get_user_meta($user);

        $message=$this->sawanna->locale->get_string('Hello').' '.$usermeta['name'].' !'."\n";
        $message.=$this->sawanna->locale->get_string('You have new private message')."\n";
        $message.=$this->sawanna->locale->get_string('You can read it here').': '.$this->sawanna->constructor->url('user/inbox')."\n\n";
    
        $this->sawanna->constructor->sendMail($user->email,$this->sawanna->config->send_mail_from,$this->sawanna->config->send_mail_from_name,$this->sawanna->locale->get_string('New private message'),$message);
    }
}
