<?php
defined('SAWANNA') or die();

class CUserAdmin
{
    var $sawanna;
    var $module_id;
    var $user_default_group;
    var $files_dir;
    
    function CUserAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('user');
        $user_default_group=$this->sawanna->get_option('user_default_group');
        $this->files_dir='users';
        $this->user_default_group=is_numeric($user_default_group) && $this->group_exists($user_default_group) ? $user_default_group : 0;
    }
    
    function adminbar()
    {
        if (!$this->sawanna->has_access('administer users')) return;
            
        $adminbar=array();
        
        if ($this->sawanna->has_access('create user')) $adminbar['user/create']='[str]New user[/str]';
        if ($this->sawanna->has_access('create user groups')) $adminbar['user/groups']='[str]Groups[/str]';
        if ($this->sawanna->has_access('create user extra fields')) $adminbar['user/profile']='[str]Profile[/str]';

        $adminbar['user/messages']='[str]Messages[/str]';
        $adminbar['user/search']='[str]Search[/str]';
        $adminbar['user']='[str]Users[/str]';
        
        return array(
                        $adminbar,
                        'user'
                        );
    }
    
    function admin_area()
    {
        if (!$this->sawanna->has_access('administer users')) return ;
        
        $this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
        
        switch (true)
        {
            case $this->sawanna->arg=='user/create':
                $this->user_form();
                break;
            case preg_match('/^user\/edit\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->user_form($matches[1]);
                break;
            case preg_match('/^user\/delete\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->user_delete($matches[1]);
                break;
            case preg_match('/^user\/bulkdel$/',$this->sawanna->arg,$matches):
                $this->bulk_delete();
                break;
            case $this->sawanna->arg=='user/groups':
                $this->user_groups();
                break;
            case $this->sawanna->arg=='user/group-add':
                $this->group_form();
                break;
            case preg_match('/^user\/group-edit\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->group_form($matches[1]);
                break;
            case preg_match('/^user\/group-delete\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->group_delete($matches[1]);
                break;
            case $this->sawanna->arg=='permissions':
                $this->groups_permissions();
                break;
            case $this->sawanna->arg=='user':
                $this->users_list();
                break;
            case $this->sawanna->arg=='user/profile':
                $this->profile_fields_list();
                break;
             case $this->sawanna->arg=='user/profile-field-add':
                $this->profile_field_form();
                break;
            case preg_match('/^user\/profile-field-edit\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->profile_field_form($matches[1]);
                break;
            case preg_match('/^user\/profile-field-delete\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->profile_field_delete($matches[1]);
                break;
            case $this->sawanna->arg=='user/messages':
                $this->messages_form();
                break;
            case $this->sawanna->arg=='user/search':
                $this->user_search();
                break;
            case preg_match('/^user\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->users_list($matches[1]);
                break;
        }
    }

    function user_exists($id)
    {
        if (empty($id)) return false;
        
        if (is_numeric($id))
            $this->sawanna->db->query("select * from pre_users where id='$1'",$id);
        else 
            $this->sawanna->db->query("select * from pre_users where login='$1'",$id);
            
        return $this->sawanna->db->num_rows();
    }
    
    function email_exists($mail)
    {
        if (empty($mail)) return false;
        
       $this->sawanna->db->query("select * from pre_users where email='$1'",$mail);
        
        return $this->sawanna->db->num_rows();
    }
    
    function group_exists($id)
    {
        if (empty($id)) return false;
        
        if (is_numeric($id))
            $this->sawanna->db->query("select * from pre_user_groups where id='$1'",$id);
        else 
            $this->sawanna->db->query("select * from pre_user_groups where name='$1'",$id);
        
        return $this->sawanna->db->num_rows();
    }
    
    function get_user($id,$array=false)
    {
        if (empty($id)) return false;
        
        $this->sawanna->db->query("select * from pre_users where id='$1'",$id);
        if ($array)
            $row=$this->sawanna->db->fetch_array();
        else 
            $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return $row;
        return false;
    }
    
    function get_group($id,$array=false)
    {
        if (empty($id)) return false;
        
        $this->sawanna->db->query("select * from pre_user_groups where id='$1'",$id);
        if ($array)
            $row=$this->sawanna->db->fetch_array();
        else 
            $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return $row;
        return false;
    }
    
    function user_login_check()
    {
        if (empty($_POST['q'])) return;
        
        if (!valid_login($_POST['q']))
       {
            echo $this->sawanna->locale->get_string('Invalid login');
            die();
        }
        
        if ($this->user_exists($_POST['q']))
        {
            echo $this->sawanna->locale->get_string('Login already in use');
            die();
        }
        
        die();
    }
    
    function user_password_check()
    {
         if (empty($_POST['q'])) return;
         
         if (!valid_password($_POST['q'])) 
        {
            echo $this->sawanna->locale->get_string('Invalid password');
            die();
        }
        
        die();
    }
    
    function user_email_check()
    {
        if (empty($_POST['q'])) return;
        
        if (!valid_email($_POST['q'])) 
        {
            echo $this->sawanna->locale->get_string('Invalid E-Mail');
            die();
        }
        
        if ($this->email_exists($_POST['q']))
        {
            echo $this->sawanna->locale->get_string('E-Mail already in use');
            die();
        }
        
        die();
    }
    
    function get_user_groups()
    {
        $groups=array();
        
        $d=new CData();
        
        $this->sawanna->db->query("select * from pre_user_groups where enabled <> '0' order by id");
        while ($row=$this->sawanna->db->fetch_object())
        {
            if (!empty($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            
            $group_name=!empty($row->meta) && isset($row->meta['name']) && isset($row->meta['name'][$this->sawanna->locale->current]) ? $row->meta['name'][$this->sawanna->locale->current] : $row->name;
            
            $groups[$row->id]=$group_name;
        }
        
        return $groups;
    }
    
    function get_users_count()
    {
        $this->sawanna->db->query("select count(*) as count from pre_users");
        $row=$this->sawanna->db->fetch_object();
        if (!empty($row)) return $row->count;
        else return 0;
    }
    
    function valid_field_name($name)
    {
        if (empty($name)) return false;
        
        if (!preg_match('/^[a-zA-Z0-9_-]{3,255}$/',$name)) return false;
        
        return true;
    }
    
    function field_name_exists($name)
    {
        if (empty($name)) return false;
        
        $this->sawanna->db->query("select * from pre_user_extra_fields where name='$1'",$name);
        
        return $this->sawanna->db->num_rows();
    }
    
    function field_name_check()
    {
        if (!isset($_POST['q'])) return;
        
        if (!$this->valid_field_name($_POST['q']))
        {
            echo $this->sawanna->locale->get_string('Invalid field name');
        } elseif ($this->field_name_exists($_POST['q']))
        {
            echo $this->sawanna->locale->get_string('Field name already in use');
        }
    }
    
    function field_exists($id)
    {
        if (empty($id)) return false;
        
        $this->sawanna->db->query("select * from pre_user_extra_fields where id='$1'",$id);
        
        return $this->sawanna->db->num_rows();
    }
    
    function get_field($id,$array=false)
    {
        if (empty($id)) return false;
        
        $this->sawanna->db->query("select * from pre_user_extra_fields where id='$1'",$id);
        if ($array)
            $row=$this->sawanna->db->fetch_array();
        else 
            $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return $row;
        return false;
    }
    
    function user_form($id=0)
    {
        if (!$this->sawanna->has_access('administer users') || !$this->sawanna->has_access('create user')) 
        {
            trigger_error('Permission denied',E_USER_ERROR);
            return ;
        }
        
        $user=new stdClass();
        
        if (!empty($id) && !is_numeric($id)) 
        {
            trigger_error('Invalid user ID: '.$id,E_USER_ERROR);
            return;
        }
        if (!empty($id))
        {
            if (!$this->user_exists($id)) 
            {
                trigger_error('User not found',E_USER_ERROR);
                return;    
            }
            
            $user=$this->get_user($id);
            
            $d=new CData();
            if (!empty($user->meta)) $user->meta=$d->do_unserialize($user->meta);
        }
        
        $fields=array();
        
         $fields['user-tab']=array(
            'type' => 'html',
            'value' => '<h1 class="tab">'.(!isset($user->id) ? '[str]New user[/str]' : '[str]Edit user[/str]').'</h1>'
        );
        
        $fields['login']=array(
            'type' => 'text',
            'title' => '[str]Login[/str]',
            'description' => '[str]Specify user login[/str]',
            'autocheck' => array('url'=>'user-login-check','caller'=>'userAdminHandler','handler'=>'user_login_check'),
            'default' => isset($user->login) ? $user->login : '',
            'required' => true
        );
        
        $fields['password']=array(
            'type' => 'password',
            'title' => '[str]Password[/str]',
            'autocheck' => array('url'=>'user-password-check','caller'=>'userAdminHandler','handler'=>'user_password_check'),
            'description' => '[str]Specify user password here[/str]',
            'required' => empty($id) ? true : false
        );
        
       $fields['password_re']=array(
            'type' => 'password',
            'title' => '[str]Retype password[/str]',
            'description' => '[str]Please enter password again[/str]',
            'required' => empty($id) ? true : false
        );
        
       $fields['email']=array(
            'type' => 'email',
            'title' => '[str]E-Mail[/str]',
            'description' => '[str]Specify user E-Mail address here[/str]',
            'autocheck' => array('url'=>'user-email-check','caller'=>'userAdminHandler','handler'=>'user_email_check'),
            'default' => isset($user->email) ? $user->email : '',
            'required' => true
        );
        
        if ($this->sawanna->has_access('change user group'))
        {
            $groups=array(''=>'[str]Choose[/str]')+$this->get_user_groups();
            $default_group=array_key_exists($this->user_default_group,$groups) ? $this->user_default_group : '';
            
            $fields['group']=array(
                'type' => 'select',
                'title' => '[str]Group[/str]',
                'options' => $groups,
                'description' => '[str]Choose user group[/str]',
                'default' => isset($user->group_id) ? $user->group_id : $default_group,
                'required' => true
            );
        }

        if ($this->sawanna->has_access('create user without confirmation'))
        {
            $fields['active']=array(
                'type' => 'checkbox',
                'title' => '[str]Verified[/str]',
                'description' => '[str]If not checked, E-Mail confirmation will be required for this account[/str]',
                'attributes' => !isset($user->active) || $user->active ? array('checked'=>'checked','class'=>'checkbox') : array('class'=>'checkbox'),
                'required' => false
            );
        }
        
       $fields['enabled']=array(
            'type' => 'checkbox',
            'title' => '[str]Enabled[/str]',
            'description' => '[str]User will not be able to sign in until account enabled[/str]',
            'attributes' => !isset($user->enabled) || $user->enabled ? array('checked'=>'checked','class'=>'checkbox') : array('class'=>'checkbox'),
            'required' => false
        );
        
        $fields['sep']=array(
            'type' => 'html',
            'value' => '<div class="extra-fields-separator"></div>'
        );
        
        $default_profile_fields=$this->get_default_field_types();
        foreach($default_profile_fields as $dftype=>$dfname)
        {
            $this->get_default_field($fields,$dftype,isset($user->meta) ? $user->meta : array());
        }
        
        $res=$this->sawanna->db->query("select * from pre_user_extra_fields order by weight");
        while($row=$this->sawanna->db->fetch_object($res))
        {
            $this->get_extra_field($fields,$row,$id);
        }

        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('user-form',$fields,'user_form_process',$this->sawanna->config->admin_area.'/user',empty($id) ? $this->sawanna->config->admin_area.'/user/create' : $this->sawanna->config->admin_area.'/user/edit/'.$id,'','CUserAdmin');
    }
    
    function user_form_process($id=0)
    {
        if (!$this->sawanna->has_access('administer users') || !$this->sawanna->has_access('create user')) 
        {
            return array('[str]Permission denied[/str]',array('create-user'));
        }

        if (empty($id) && preg_match('/^user\/edit\/(.+)$/',$this->sawanna->arg,$matches)) $id=$matches[1];
        
        if (!empty($id) && !is_numeric($id)) return array('[strf]Invalid user ID: %'.$id.'%[/strf]',array('edit-user'));
        if (!empty($id) && !$this->user_exists($id)) return array('[str]User not found[/str]',array('edit-user'));
        
        $user=array();
        if (!empty($id)) 
        {
            $user=$this->get_user($id,true);
            
            $d=new CData();
            if (!empty($user['meta'])) $user['meta']=$d->do_unserialize($user['meta']);
        }
        
        if (empty($id) && empty($_POST['password']))
        {
            return array('[str]Password required[/str]',array('password'));
        }
        
        if (!valid_login($_POST['login']))
        {
            return array('[str]Invalid login[/str]',array('login'));
        }
        
        if ((empty($id) || $user['login']!=$_POST['login']) && $this->user_exists($_POST['login']))
        {
            return array('[str]Login already in use[/str]',array('login'));
        }
        
        if (!empty($_POST['password']))
        {
            if (empty($_POST['password_re']))
            {
                return array('[str]Please retype your password[/str]',array('password_re'));
            }
            
            if (!valid_password($_POST['password']))
            {
                return array('[str]Invalid password[/str]',array('password'));
            }
            
            if ($_POST['password_re'] != $_POST['password'])
            {
                return array('[str]Passwords do not match[/str]',array('password'));
            }
        }
     
        if (!valid_email($_POST['email']))
        {
            return array('[str]Invalid E-Mail[/str]',array('email'));
        }
        
        if ((empty($id) || $user['email']!=$_POST['email']) && $this->email_exists($_POST['email']))
        {
            return array('[str]E-Mail already in use[/str]',array('email'));
        }
        
        if (!empty($_POST['group']) && !$this->group_exists($_POST['group']))
        {
            return array('[str]Invalid group[/str]',array('group'));
        }

        $user['login']=$_POST['login'];
        
        if (!empty($_POST['password']))
            $user['password']=$this->sawanna->access->pwd_hash($_POST['password']);
            
        $user['email']=$_POST['email'];
        
        if ($this->sawanna->has_access('change user group'))
        {
            $user['group']=$_POST['group'];
        }
        
        $user['active']=0;
        if ($this->sawanna->has_access('create user without confirmation'))
        {
            if (isset($_POST['active']) && $_POST['active']=='on') $user['active']=1;
        }
        
        $user['enabled']=0;
        if (isset($_POST['enabled']) && $_POST['enabled']=='on') $user['enabled']=1;
        
        $meta=array();
        if (isset($user['meta'])) $meta=$user['meta'];
        
        $default_profile_fields=$this->get_default_field_types();
        foreach($default_profile_fields as $dftype=>$dfname)
        {
            $this->process_default_field($dftype,$meta);
        }

        $user['meta']=$meta;
         
        if (!empty($id)) 
        {
            $user['id']=$id;
        }
        
        if (!$this->set($user))
        {
            return array('[str]User could not be saved[/str]',array('new-user'));
        }
        
        if (empty($id))
        {
            $this->sawanna->db->last_insert_id('users','id');
            $row=$this->sawanna->db->fetch_object();
            $id=$row->id;
        }

        $res=$this->sawanna->db->query("select * from pre_user_extra_fields order by weight");
        while($row=$this->sawanna->db->fetch_object($res))
        {
            $this->process_extra_field($row,$id);
        }
        
        if (!isset($user['id']) && $user['enabled'] && !$user['active']) $this->send_confirm_message($id);

        return array('[str]User saved[/str]');
    }
    
    function set($user)
    {
        if (empty($user) || !is_array($user)) return false;
        
        if (empty($user['login']) || empty($user['password']) || empty($user['email']))
        {
            trigger_error('Cannot set user',E_USER_WARNING);
            return false;
        }
        
        if (!isset($user['group'])) $user['group']=$this->user_default_group;
        if (!isset($user['active'])) $user['active']=0;
        if (!isset($user['enabled'])) $user['enabled']=0;
        if (!isset($user['meta'])) $user['meta']='';
        
        if (!empty($user['meta']))
        {
            $d=new CData();
            $user['meta']=$d->do_serialize($user['meta']);
        }
        
        if (isset($user['id']))
        {
            return $this->sawanna->db->query("update pre_users set login='$1', password='$2', email='$3', group_id='$4', active='$5', enabled='$6', meta='$7' where id='$8'",$user['login'],$user['password'],$user['email'],$user['group'],$user['active'],$user['enabled'],$user['meta'],$user['id']);
        } else 
        {
            return $this->sawanna->db->query("insert into pre_users (login,password,email,group_id,active,enabled,created,meta) values ('$1','$2','$3','$4','$5','$6','$7','$8')",$user['login'],$user['password'],$user['email'],$user['group'],$user['active'],$user['enabled'],time(),$user['meta']);
        }
    }
    
    function send_confirm_message($uid)
    {
        if (empty($uid) || !is_numeric($uid)) return false;
        
        $this->sawanna->db->query("select * from pre_users where id='$1'",$uid);
        $row=$this->sawanna->db->fetch_object();
        
        $key=md5('user-'.$uid.'-'.$row->created.PRIVATE_KEY);
            
        $confirm_link=$this->sawanna->constructor->url('user/confirm',true);
        $confirm_link=$this->sawanna->constructor->add_url_args($confirm_link,array('uid'=>$row->id));
        $confirm_link=$this->sawanna->constructor->add_url_args($confirm_link,array('key'=>$key));
        
        $message=$this->get_message('confirm',$this->sawanna->locale->current);
        if (!empty($message))
        {
            $vars=$this->get_message_variables('confirm',true);
            
            $vals=array(
                '[login]' => $row->login,
                '[email]' => $row->email,
                '[link]' => $confirm_link,
                '[site]' => SITE
            );
            
            foreach ($vars as $var)
            {
                $val=isset($vals[$var]) ? $vals[$var] : ' ';
                $message->content=str_replace($var,$val,$message->content);
            }
            
            $message=$message->content;
        } else 
        {
            $message=$this->sawanna->locale->get_string('Hello').' '.$row->login.' !'."\n";
            $message.=$this->sawanna->locale->get_string('Your account is not active yet')."\n";
            $message.=$this->sawanna->locale->get_string('Please follow the link below to activate it').': '.$confirm_link."\n\n";
        }
        
        if ($this->sawanna->constructor->sendMail($row->email,$this->sawanna->config->send_mail_from,$this->sawanna->config->send_mail_from_name,$this->sawanna->locale->get_string('Account activation'),$message))
        {
            return true;
        } else 
        {
            return false;
        }
    }
    
    function delete($id)
    {
        if (empty($id)) return false;

        if ($this->sawanna->db->query("delete from pre_users where id='$1'",$id))
        {
            return true;
        }
        
        return false;
    }
    
    function user_delete($id)
    {
        if (!$this->sawanna->has_access('administer users')) 
        {
            trigger_error('Could not delete user. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not delete user. Invalid user ID: '.$id,E_USER_WARNING);
            return ;
        }
        if (!$this->user_exists($id)) 
        {
            trigger_error('Could not delete user. User not found',E_USER_WARNING);    
            return;
        }

        $event=array();
        
        if ((isset($GLOBALS['user']) && is_object($GLOBALS['user']) && isset($GLOBALS['user']->current->id) && $GLOBALS['user']->current->id==$id) || !$this->delete($id))
        {
            $message='[str]Could not delete user.[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
            $message='[strf]User with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
            $event['job']='sawanna_message';
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);
        
        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/user');
    }
    
    function bulk_delete()
    {
        if (!$this->sawanna->has_access('administer users')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
                if (isset($GLOBALS['user']) && is_object($GLOBALS['user']) && isset($GLOBALS['user']->current->id) && $GLOBALS['user']->current->id==$id) continue;
        
                $this->delete(trim($id));
            }

            echo $this->sawanna->locale->get_string('Users were deleted');
        } else
        {
            echo $this->sawanna->locale->get_string('Users were not deleted');
        }
        
        die();
    }
    
    function users_list($start=0,$limit=10)
    {
        if (!$this->sawanna->has_access('administer users')) 
        {
            trigger_error('Permission denied',E_USER_WARNING);
            return ;
        }
        
        $html='<h1 class="tab">[str]Users[/str]</h1>';
        $html.='<div id="users-list">';
        
        $html.='<div class="users-list-item head">';
        $html.='<div class="id"></div>';
        $html.='<div class="user">[str]User[/str]</div>';
        $html.='</div>';
            
        $groups=$this->get_user_groups();
        
        $count=$this->get_users_count();
        
        $d=new CData();
        
        $co=0;
        $this->sawanna->db->query("select u.id,u.login,u.email,u.group_id,u.created,u.active,u.enabled,u.meta,a.ip,a.tstamp from pre_users u left join pre_user_activity a on u.id=a.uid order by u.created desc limit $2 offset $1",$start*$limit,$limit);
        while($user=$this->sawanna->db->fetch_object())
        {
            if ($user->enabled)
            {
               if ($co%2===0)
               {
                   $html.='<div class="users-list-item">';
               } else 
               {
                   $html.='<div class="users-list-item mark highlighted">';
               }
            } else {
                $html.='<div class="users-list-item disabled">';
            }

            if (!empty($user->meta)) $user->meta=$d->do_unserialize($user->meta);

            $user_info='<div class="user-info">'.htmlspecialchars($user->login).'</div>';
            $user_info.='<div class="user-info">'.htmlspecialchars($user->email).'</div>';
            $user_info.='<div class="user-info"><label>[str]Full name[/str]: </label>'.(isset($user->meta) && is_array($user->meta) && isset($user->meta['name']) && !empty($user->meta['name']) ? $user->meta['name'] : '-').'</div>';
            $user_info.='<div class="user-info"><label>[str]Group[/str]: </label>'.(isset($groups[$user->group_id]) ? htmlspecialchars($groups[$user->group_id]) : '-').'</div>';
            $user_info.='<div class="user-info"><label>[str]Verified[/str]: </label>'.($user->active ? '<span class="yes">[str]yes[/str]</span>' : '<span class="no">[str]no[/str]</span>').'</div>';
            $user_info.='<div class="user-info"><label>[str]IP-address[/str]: </label>'.(!empty($user->ip) ? htmlspecialchars($user->ip) : '-').'</div>';
            $user_info.='<div class="user-info"><label>[str]Last activity[/str]: </label>'.(!empty($user->tstamp) ? date($this->sawanna->config->date_format,$user->tstamp) : '-').'</div>';

            $html.='<div class="id"><input type="checkbox" name="bulk_id[]" value="'.$user->id.'" class="bulk_id_checkbox" /></div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/user/delete/'.$user->id,'[str]Delete[/str]','button',array('onclick'=>'if (!confirm(\'[str]Are you sure ?[/str]\')){return false;}')).'</div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/user/edit/'.$user->id,'[str]Edit[/str]','button').'</div>';
            $html.='<div class="user">'.$user_info.'</div>';
            $html.='</div>';
            
            $co++;
        }
        
        $html.='</div>';
        
        if ($count)
        {
            $html.='<div class="bulk_block">';
            $html.='<a href="#" id="bulk_id_selector" class="bulk_select_all">[str]Select all[/str]</a>';
            $html.='<select id="bulk_action_selector" name="bulk_action_selector" class="bulk_select" onchange="exec_bulk_action()"><option value="">[str]Choose action[/str]</option><option value="del">[str]Delete[/str]</option></select>';
            $html.='</div>';
            
            $html.="<script language=\"javascript\">$(document).ready(function(){ $('a#bulk_id_selector').toggle(function() { $('input[type=checkbox].bulk_id_checkbox').attr('checked','checked'); }, function() { $('input[type=checkbox].bulk_id_checkbox').removeAttr('checked'); }); });</script>";
            $html.="<script language=\"javascript\">function exec_bulk_action(){ var act=$('#bulk_action_selector').val(); var ids=''; if (act.length>0) { $('input[type=checkbox].bulk_id_checkbox').each(function(){ if ($(this).get(0).checked==true) { if (ids.length>0){ids=ids+',';}; ids=ids+$(this).val(); } }); if (ids.length>0 && confirm('[str]Are you sure ?[/str]')) { $.post('".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/user/bulkdel')."',{bulk_ids:ids},function(data){ alert(data); window.location.href='".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/'.$this->sawanna->arg)."'; }); $('#bulk_action_selector').get(0).disabled=true; } } $('#bulk_action_selector').get(0).selectedIndex=0; refresh_select($('#bulk_action_selector')); }</script>";
        }
        
        $html.=$this->sawanna->constructor->pager($start,$limit,$count,$this->sawanna->config->admin_area.'/user');
        
        echo $html;
    }
    
    function user_search()
    {
        if (!$this->sawanna->has_access('administer users')) 
        {
            trigger_error('Permission denied',E_USER_WARNING);
            return ;
        }
        
        $html='<h1 class="tab">[str]User search[/str]</h1>';
        $html.='<div id="users-list">';
        
        $fields=array();
        
        $search=$this->sawanna->session->get('admin_user_search_name');
        
        $fields['login']=array(
            'type' => 'text',
            'title' => '[str]Login[/str]',
            'description' => '[str]Specify user login or name[/str]',
            'attributes' => array('style' => 'width: 250px'),
            'default' => $search ? $search: '',
            'required' => true
        );
        
        $fields['submit']=array(
            'type'=>'submit',
            'title' => ' ',
            'value'=>'[str]Submit[/str]'
        );

        $html.=$this->sawanna->constructor->form('user-search-form',$fields,'user_search_process',$this->sawanna->config->admin_area.'/user/search',$this->sawanna->config->admin_area.'/user/search','','CUserAdmin');

        $html.='<div class="users-list-item head">';
        $html.='<div class="id"></div>';
        $html.='<div class="user">[str]User[/str]</div>';
        $html.='</div>';
            
        $d=new CData();
        
        if ($search)
        {
            $co=0;
            $this->sawanna->db->query("select u.id,u.login,u.email,u.group_id,u.created,u.active,u.enabled,u.meta,a.ip,a.tstamp from pre_users u left join pre_user_activity a on u.id=a.uid order by u.created desc");
            while($user=$this->sawanna->db->fetch_object())
            {
               if (!empty($user->meta)) $user->meta=$d->do_unserialize($user->meta);
               $username=isset($user->meta) && is_array($user->meta) && isset($user->meta['name']) && !empty($user->meta['name']) ? $user->meta['name'] : '-';
               
               if (!preg_match('/'.preg_quote($search).'/i',$user->login) && ($username!='-' && !preg_match('/'.preg_quote($search).'/i',$username) || $username=='-')) continue; 
                
               if ($user->enabled)
               {
                   if ($co%2===0)
                   {
                       $html.='<div class="users-list-item">';
                   } else 
                   {
                       $html.='<div class="users-list-item mark highlighted">';
                   }
                } else {
                    $html.='<div class="users-list-item disabled">';
                }
               
                $user_info='<div class="user-info">'.htmlspecialchars($user->login).'</div>';
                $user_info.='<div class="user-info">'.htmlspecialchars($user->email).'</div>';
                $user_info.='<div class="user-info"><label>[str]Full name[/str]: </label>'.$username.'</div>';
                $user_info.='<div class="user-info"><label>[str]Group[/str]: </label>'.(isset($groups[$user->group_id]) ? htmlspecialchars($groups[$user->group_id]) : '-').'</div>';
                $user_info.='<div class="user-info"><label>[str]Verified[/str]: </label>'.($user->active ? '<span class="yes">[str]yes[/str]</span>' : '<span class="no">[str]no[/str]</span>').'</div>';
                $user_info.='<div class="user-info"><label>[str]IP-address[/str]: </label>'.(!empty($user->ip) ? htmlspecialchars($user->ip) : '-').'</div>';
                $user_info.='<div class="user-info"><label>[str]Last activity[/str]: </label>'.(!empty($user->tstamp) ? date($this->sawanna->config->date_format,$user->tstamp) : '-').'</div>';
                
                $html.='<div class="id"><input type="checkbox" name="bulk_id[]" value="'.$user->id.'" class="bulk_id_checkbox" /></div>';
                $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/user/delete/'.$user->id,'[str]Delete[/str]','button',array('onclick'=>'if (!confirm(\'[str]Are you sure ?[/str]\')){return false;}')).'</div>';
                $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/user/edit/'.$user->id,'[str]Edit[/str]','button').'</div>';
                $html.='<div class="user">'.$user_info.'</div>';
                $html.='</div>';
                
                $co++;
            }
        }
        
        $html.='</div>';
        
        if (isset($co) && $co>0)
        {
            $html.='<div class="bulk_block">';
            $html.='<a href="#" id="bulk_id_selector" class="bulk_select_all">[str]Select all[/str]</a>';
            $html.='<select id="bulk_action_selector" name="bulk_action_selector" class="bulk_select" onchange="exec_bulk_action()"><option value="">[str]Choose action[/str]</option><option value="del">[str]Delete[/str]</option></select>';
            $html.='</div>';
            
            $html.="<script language=\"javascript\">$(document).ready(function(){ $('a#bulk_id_selector').toggle(function() { $('input[type=checkbox].bulk_id_checkbox').attr('checked','checked'); }, function() { $('input[type=checkbox].bulk_id_checkbox').removeAttr('checked'); }); });</script>";
            $html.="<script language=\"javascript\">function exec_bulk_action(){ var act=$('#bulk_action_selector').val(); var ids=''; if (act.length>0) { $('input[type=checkbox].bulk_id_checkbox').each(function(){ if ($(this).get(0).checked==true) { if (ids.length>0){ids=ids+',';}; ids=ids+$(this).val(); } }); if (ids.length>0 && confirm('[str]Are you sure ?[/str]')) { $.post('".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/user/bulkdel')."',{bulk_ids:ids},function(data){ alert(data); window.location.href='".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/'.$this->sawanna->arg)."'; }); $('#bulk_action_selector').get(0).disabled=true; } } $('#bulk_action_selector').get(0).selectedIndex=0; refresh_select($('#bulk_action_selector')); }</script>";
        }
        
        echo $html;
    }
    
    function user_search_process()
    {
        if (!$this->sawanna->has_access('administer users')) 
        {
            return array('Permission denied',array('login'));
        }
        
        $this->sawanna->session->set('admin_user_search_name',trim($_POST['login']));
    }
    
    function user_groups()
    {
         if (!$this->sawanna->has_access('administer users') || !$this->sawanna->has_access('create user groups')) 
        {
            trigger_error('Permission denied',E_USER_WARNING);
            return ;
        }
        
        $d=new CData();
        
        $html='<h1 class="tab">[str]User groups[/str]</h1>';
        $html.='<div id="groups-list">';
        
        $html.='<div class="groups-list-item head">';
        $html.='<div class="id">[str]ID[/str]</div>';
        $html.='<div class="group">[str]Group[/str]</div>';
        $html.='</div>';
            
        $co=0;
        $this->sawanna->db->query("select * from pre_user_groups order by id");
        while ($row=$this->sawanna->db->fetch_object())
        {
             if ($row->enabled)
               {
                   if ($co%2===0)
                   {
                       $html.='<div class="groups-list-item">';
                   } else 
                   {
                       $html.='<div class="groups-list-item mark highlighted">';
                   }
                } else {
                    $html.='<div class="groups-list-item disabled">';
                }
            
            if (!empty($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            
            $group_name=!empty($row->meta) && isset($row->meta['name']) && isset($row->meta['name'][$this->sawanna->locale->current]) ? $row->meta['name'][$this->sawanna->locale->current] : $row->name;
            
            $html.='<div class="id">'.$row->id.'</div>';
             $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/user/group-delete/'.$row->id,'[str]Delete[/str]','button',array('onclick'=>'if (!confirm(\'[str]Are you sure ?[/str]\')){return false;}')).'</div>';
             $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/user/group-edit/'.$row->id,'[str]Edit[/str]','button').'</div>';
             $html.='<div class="group">'.$group_name.'</div>';
             $html.='</div>';
             
             $co++;
        }
        
        $html.='</div>';
        
        echo $html;
        
        $this->group_form();
    }
    
    function group_form($id=0)
    {
        if (!$this->sawanna->has_access('administer users') || !$this->sawanna->has_access('create user groups')) 
        {
            trigger_error('Permission denied',E_USER_ERROR);
            return ;
        }
        
        $group=new stdClass();
        
        if (!empty($id) && !is_numeric($id)) 
        {
            trigger_error('Invalid group ID: '.$id,E_USER_ERROR);
            return;
        }
        if (!empty($id))
        {
            if (!$this->group_exists($id)) 
            {
                trigger_error('Group not found',E_USER_ERROR);
                return;    
            }
            
            $d=new CData();
            
            $group=$this->get_group($id);
            if (!empty($group->meta)) $group->meta=$d->do_unserialize($group->meta);
        }
        
        $fields=array();
        
        $fields['name']=array(
            'type' => 'text',
            'title' => '[str]Name[/str]',
            'description' => '[str]Specify group system name[/str]',
            'default' => isset($group->name) ? $group->name : '',
            'required' => true
        );
        
        $group_titles=array();
        if (!empty($group->meta) && isset($group->meta['name']))
        {
            $group_titles=$group->meta['name'];
        }
        
        $co=0;
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
                if ($co%2===0)
               {
                   $fields['title-block-begin-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '<div class="mark highlighted">'
                   );
               } else 
               {
                    $fields['title-block-begin-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '<div>'
                   );
               }
                   
                $fields['title-'.$lcode]=array(
                    'type' => 'text',
                    'title' => '[str]Title[/str]',
                    'description' => '[strf]Specify group title in following language: %'.htmlspecialchars($lname).'%[/strf]',
                    'default' => isset($group_titles[$lcode]) ? $group_titles[$lcode] : '',
                    'required' => false
                );
                
                 $fields['title-block-end-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '</div>'
                   );
                
                $co++;
        }
        
       $fields['enabled']=array(
            'type' => 'checkbox',
            'title' => '[str]Enabled[/str]',
            'description' => '[str]All user accounts of disabled group are inaccessible[/str]',
            'attributes' => !isset($group->enabled) || $group->enabled ? array('checked'=>'checked','class'=>'checkbox') : array('class'=>'checkbox'),
            'required' => false
        );

        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );
        
        echo '<h1 class="tab">'.(!isset($menu->id) ? '[str]New group[/str]' : '[str]Edit group[/str]').'</h1>';
        echo $this->sawanna->constructor->form('group-form',$fields,'group_form_process',$this->sawanna->config->admin_area.'/user/groups',empty($id) ? $this->sawanna->config->admin_area.'/user/group-add' : $this->sawanna->config->admin_area.'/user/group-edit/'.$id,'','CUserAdmin');
        
    }
    
    function group_form_process($id=0)
    {
        if (!$this->sawanna->has_access('administer users') || !$this->sawanna->has_access('create user groups')) 
        {
            return array('[str]Permission denied[/str]',array('create-group'));
        }

        if (empty($id) && preg_match('/^user\/group-edit\/(.+)$/',$this->sawanna->arg,$matches)) $id=$matches[1];
        
        if (!empty($id) && !is_numeric($id)) return array('[strf]Invalid group ID: %'.$id.'%[/strf]',array('edit-group'));
        if (!empty($id) && !$this->group_exists($id)) return array('[str]Group not found[/str]',array('edit-group'));
        
        $group=array();
        if (!empty($id)) 
        {
            $d=new CData();
            
            $group=$this->get_group($id,true);
            if (!empty($group['meta'])) $group['meta']=$d->do_unserialize($group->meta);
        }
        
        $group['name']=htmlspecialchars($_POST['name']);
        
        if (empty($id) && $this->group_exists($group['name'])) return array('[str]Group with such name already exists[/str]',array('name'));
      
        $group['enabled']=0;
        if (isset($_POST['enabled']) && $_POST['enabled']=='on') $group['enabled']=1;
        
        $meta=array('name'=>array());
        
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
            if (!empty($_POST['title-'.$lcode])) $meta['name'][$lcode]=htmlspecialchars($_POST['title-'.$lcode]);
        }
        
        if (!empty($group['meta']) && !empty($meta['name'])) $group['meta']=array_merge($group['meta'],$meta);
        else $group['meta']=$meta;
         
        if (!empty($id)) 
        {
            $group['id']=$id;
        }
        
        if (!$this->set_group($group))
        {
            return array('[str]Group could not be saved[/str]',array('new-group'));
        }

        return array('[str]Group saved[/str]');
    }
    
    function set_group($group)
    {
        if (empty($group) || !is_array($group)) return false;
        
        if (empty($group['name']))
        {
            trigger_error('Cannot set group',E_USER_WARNING);
            return false;
        }
        
        if (!isset($group['meta'])) $group['meta']='';
        if (!isset($group['enabled'])) $group['enabled']=0;
        
        if (!empty($group['meta']))
        {
            $d=new CData();
            $group['meta']=$d->do_serialize($group['meta']);
        }
        
        if (isset($group['id']))
        {
            return $this->sawanna->db->query("update pre_user_groups set name='$1', meta='$2', enabled='$3' where id='$4'",$group['name'],$group['meta'],$group['enabled'],$group['id']);
        } else 
        {
            if (!$this->sawanna->db->query("insert into pre_user_groups (name,meta,enabled,created) values ('$1','$2','$3','$4')",$group['name'],$group['meta'],$group['enabled'],time()))
                return false;
                
            $this->sawanna->db->last_insert_id('user_groups','id');
            $row=$this->sawanna->db->fetch_object();
            if (empty($row)) return true;
            
            $group_id=$row->id;
            $group_perm_name='user group '.$group_id.' access';
            
            $group_perm=array(
                'name' => $group_perm_name,
                'title' => '[strf]%'.$group['name'].'% group common access[/strf]',
                'description' => '[str]By default this permission granted only to its group[/str]',
                'access' => 0,
                'module' => $this->module_id
            );
            
            $this->sawanna->access->set($group_perm);
            
            $this->sawanna->db->query("insert into pre_user_group_permissions (permission,access,group_id) values ('$1','$2','$3')",$group_perm_name,1,$group_id);
            
            return true;
        }
    }
    
    function delete_group($id)
    {
        if (empty($id)) return false;

        if ($this->sawanna->db->query("delete from pre_user_groups where id='$1'",$id))
        {
            $group_perm_name='user group '.$id.' access';
            $this->sawanna->access->delete($group_perm_name);
            
            $this->sawanna->db->query("delete from pre_user_group_permissions where group_id='$1'",$id);
            return true;
        }
        
        return false;
    }
    
    function group_delete($id)
    {
        if (!$this->sawanna->has_access('administer users') || !$this->sawanna->has_access('create user groups')) 
        {
            trigger_error('Could not delete group. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not delete group. Invalid group ID: '.$id,E_USER_WARNING);
            return ;
        }
        if (!$this->group_exists($id)) 
        {
            trigger_error('Could not delete group. Group not found',E_USER_WARNING);    
            return;
        }
        
        $event=array();
        
        if ($id == $this->user_default_group)
        {
            $message='[str]Could not delete users default group.[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
            if (!$this->delete_group($id))
            {
                $message='[str]Could not delete group. Database error[/str]';    
                $event['job']='sawanna_error';
            } else 
            {
                $message='[strf]Group with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
                $event['job']='sawanna_message';
            }
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);
        
        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/user/groups');
    }
    
    function get_group_access_table($group_id)
    {
        if (empty($group_id)) return false;
        
        $table=array();
        
        $this->sawanna->db->query("select * from pre_user_group_permissions where group_id='$1' order by id",$group_id);
        while($row=$this->sawanna->db->fetch_object())
        {
            $row->name=$row->permission;
            $table[$row->name]=$row;
        }
        
        return $table;
    }
    
    function get_group_current_access($group_id,$id)
    {
        $perms=$this->get_group_access_table($group_id);
        foreach ($perms as $perm)
        {
            if (is_numeric($id) && $perm->id==$id) return $perm->access;
            elseif ($perm->name==$id) return $perm->access;
        }
        
        return false;
    }
    
    function groups_permissions()
    {
        if (!$this->sawanna->has_access('su')) return;
        
        $fields=array();
        
        $groups=$this->get_user_groups();
        
        foreach ($groups as $gid=>$gname)
        {
            echo '<h1 class="tab">'.htmlspecialchars($gname).'</h1>';
            echo '<div class="group-permissions">';

            $_perms=$this->sawanna->access->get_access_table();
            $group_perms=$this->get_group_access_table($gid);
            
            $modules=array(0);
            foreach ($this->sawanna->module->modules as $id=>$module)
            {
                $modules[$id]=$module;
            }
        
            foreach ($modules as $mid=>$module)
            {
                $perms=$this->sawanna->access->get_module_permissions($_perms,$mid);
    
                 if (!empty($perms))
                {
                    $fields['module-'.$mid]=array(
                        'type'=>'html',
                        'value' => '<div class="module section-highlight">'.($mid ? $module->title : '[str]System[/str]').'</div>'
                    );
                }
            
                $co=0;
                foreach($perms as $pkey=>$perm)
                {
                    if ($co%2===0)
                    {
                        $fields['component-line-begin-'.$pkey]=array(
                            'type'=>'html',
                            'value'=>'<div class="form_component_line">'
                        );
                    } else 
                    {
                        $fields['component-line-begin-'.$pkey]=array(
                            'type'=>'html',
                            'value'=>'<div class="form_component_line mark highlighted">'
                        );
                    }
                
                    if (isset($group_perms[$perm->name]))
                    {
                        $perm_attr=$group_perms[$perm->name]->access ? array('checked'=>'checked','class'=>'perm-'.$gid) : array('class'=>'perm-'.$gid);
                    } else
                    {
                        $perm_attr=$perm->access ? array('checked'=>'checked','class'=>'perm-'.$gid) : array('class'=>'perm-'.$gid);
                    }
                    
                    $perm_attr['onclick']='check_su_'.$gid.'($(this).attr(\'rel\'))';
                    $perm_attr['rel']=$perm->name.'-'.$gid;
                    
                    if ($perm->name!='su' && $this->get_group_current_access($gid,'su')) $perm_attr['disabled']='disabled';
                    
                    $fields['permission-'.$perm->id]=array(
                        'type' => 'checkbox',
                        'name' => 'permission-'.$perm->id,
                        'title' => $perm->title,
                        'description' => $perm->description,
                        'attributes' => $perm_attr
                    ); 
                    
                    $fields['perm-script']=array(
                        'type' => 'html',
                        'value' => '<script language="JavaScript">function check_su_'.$gid.'(perm) {if (perm=="su-'.$gid.'"){$(".perm-'.$gid.'").each( function(){ if ($(this).attr("rel")!="su-'.$gid.'") { if ($("[rel=\'su-'.$gid.'\']").get(0).checked) {$(this).attr("disabled","disabled");} else {$(this).removeAttr("disabled");} } });}}</script>'
                    );
                    
                    $fields['component-line-end-'.$pkey]=array(
                        'type'=>'html',
                        'value'=>'</div>'
                    );
                    
                    $fields['component-separator-'.$pkey]=array(
                        'type'=>'html',
                        'value'=>'<div class="separator"></div>'
                    );
                    
                    $co++;
                }
            }
            
            $fields['permission-group-'.$gid]=array(
                'type' => 'hidden',
                'name' => 'group_id',
                'value' => $gid
            ); 
                    
            $fields['submit']=array(
                     'type' => 'submit',
                     'value' => '[str]Save[/str]'
                );
          
            echo $this->sawanna->constructor->form('groups_permissions_'.$gid,$fields,'groups_permissions_process',$this->sawanna->config->admin_area.'/permissions',$this->sawanna->config->admin_area.'/user/permissions-save','','CUserAdmin');   
            echo '</div>';   
        }
    }
    
    function groups_permissions_process()
    {
        if (empty($_POST['group_id']))
        {
            return array('[str]Cannot set group permissions. Invalid group ID[/str]','group-permissions');
        }
        
        $groups=$this->get_user_groups();
        if (!array_key_exists($_POST['group_id'],$groups))
        {
            return array('[str]Cannot set group permissions. Invalid group ID[/str]','group-permissions');
        }
        
        $errors=array();

        $perms=$this->sawanna->access->get_access_table();
        $_perms=array();
        
        foreach($_POST as $pkey=>$pval)
        {
            if (!preg_match('/^permission-(.+)$/',$pkey,$matches)) continue;
            if (!is_numeric($matches[1]) || !$this->sawanna->access->perm_exists($matches[1])) 
            {
                $errors[]='[strf]Invalid permission ID: %'.htmlspecialchars($matches[1]).'%[/strf]';
                continue;
            }
            
            if ($pval=='on') 
            {
                $_perms[]=$matches[1];
            }
        }
        
        foreach ($perms as $pkey=>$perm)
        {
            $access=0;
            if (in_array($perm->id,$_perms)) $access=1;
            
            $this->sawanna->db->query("select id from pre_user_group_permissions where permission='$1' and group_id='$2'",$perm->name,$_POST['group_id']);
            if ($this->sawanna->db->num_rows())
            {
                if (!$this->sawanna->db->query("update pre_user_group_permissions set access='$1' where permission='$2' and group_id='$3'",$access,$perm->name,$_POST['group_id']))
                    $errors[]='[strf]Permission %'.htmlspecialchars($perm->title).'% could not be saved[/strf]';
            } else 
            {
                if (!$this->sawanna->db->query("insert into pre_user_group_permissions (permission,access,group_id) values ('$1','$2','$3')",$perm->name,$access,$_POST['group_id']))
                    $errors[]='[strf]Permission %'.htmlspecialchars($perm->title).'% could not be saved[/strf]';
            }
        }
        
        $_errors=sawanna_error();
        if (!empty($_errors)) $errors=array_merge($errors,$_errors);
        
        if (!empty($errors))
        {
            return array('[str]Error[/str]:<ul><li>'.implode('</li><li>',$errors).'</li></ul>','permissions');
        }
        else 
        {
            return array('[strf]Permissions for group `%'.htmlspecialchars($groups[$_POST['group_id']]).'%` saved[/strf]');
        }
    }
    
    function get_default_field_types()
    {
        $types=array(
            'name' => '[str]Full name[/str]',
            'avatar' => '[str]Avatar[/str]',
            'gender' => '[str]Gender[/str]'
        );
        
        return $types;
    }
    
    function get_default_field(&$fields,$type,$meta=array())
    {
        if (empty($type)) return false;
        
        switch ($type)
        {
            case 'avatar':
                if (isset($meta['avatar']) && file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir).'/'.quotemeta($meta['avatar'])))
                {
                    $fields['avatar_val']=array(
                        'type' => 'html',
                        'value' => '<div class="photo_preview"><img src="'.SITE.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir).'/'.quotemeta($meta['avatar']).'" width="100" /></div>'
                    );
                    
                    $fields['avatar_current']=array(
                        'type' => 'hidden',
                        'name' => 'avatar',
                        'value' => md5('avatar'.PRIVATE_KEY)
                    );
                }
                
                $fields['avatar']=array(
                    'type' => 'file',
                    'title' => '[str]Avatar[/str]',
                    'description' => '[str]Please select image file to upload.[/str] [strf]Supported formats: %jpg,png,gif%[/strf]',
                    'required' => false
                );
                break;
            case 'name':
                $fields['name']=array(
                    'type' => 'text',
                    'title' => '[str]Full name[/str]',
                    'description' => '[str]Please specify user full name here[/str]',
                    'default' => isset($meta['name']) ? htmlspecialchars($meta['name']) : '',
                    'required' => false
                );
                break;
            case 'gender':
                $fields['gender-cap']=array(
                    'type' => 'html',
                    'value' => '<div class="gender-cap form_title">[str]Gender[/str]: </div><div class="gender-block">'
                );
                
                $fields['gender-m-item']=array(
                    'type' => 'html',
                    'value' => '<div class="gender-block-item">'
                );
                
                $fields['gender-m']=array(
                    'type' => 'radio',
                    'name' => 'gender',
                    'value' => 'M',
                    'title' => '<label for="gender-m">[str]Male[/str]</label>',
                    'attributes' => isset($meta['gender']) && $meta['gender']=='M' ? array('checked'=>'checked','class'=>'checkbox') : array('class'=>'checkbox'),
                    'required' => false
                );
                
                $fields['gender-f-item']=array(
                    'type' => 'html',
                    'value' => '</div><div class="gender-block-item">'
                );
                
                $fields['gender-f']=array(
                    'type' => 'radio',
                    'name' => 'gender',
                    'value' => 'F',
                    'title' => '<label for="gender-f">[str]Female[/str]</label>',
                    'attributes' => isset($meta['gender']) && $meta['gender']=='F' ? array('checked'=>'checked','class'=>'checkbox') : array('class'=>'checkbox'),
                    'required' => false
                );
                
                $fields['gender-end']=array(
                    'type' => 'html',
                    'value' => '</div></div><div class="gender-separator"></div>'
                );
                break;
        }
    }
    
    function process_default_field($type,&$meta)
    {
        if (empty($type)) return false;
        
        switch ($type)
        {
            case 'name':
                $meta['name']='';
                
                if (empty($_POST['name'])) break;
                
                $meta['name']=htmlspecialchars($_POST['name']);
                break;
            case 'gender':
                $meta['gender']='';
                
                if (empty($_POST['gender']) || ($_POST['gender']!='M' && $_POST['gender']!='F')) break;
                
                $meta['gender']=htmlspecialchars($_POST['gender']);
                break;
            case 'avatar':
                if (empty($_FILES['avatar']) || empty($_FILES['avatar']['tmp_name'])) break;
                
                if (!$this->process_avatar($_FILES['avatar'],$meta))
                {
                    $FILE=array(
                        'tmp_name' => ROOT_DIR.'/misc/logo.png',
                        'name' => 'Sawanna',
                        'type' => 'image/png',
                        'size' => filesize(ROOT_DIR.'/misc/logo.png')
                   );
                   
                   if ($this->process_avatar($FILE,$meta)) $meta['avatar']=$this->sawanna->file->last_name;
                } else 
                {
                    $meta['avatar']=$this->sawanna->file->last_name;
                }
                break;
        }
        return true;
    }
    
    function process_avatar($FILE,$meta)
    {
        if (!is_writeable(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir))) return false;
        
        if (empty($FILE) || !is_array($FILE) || empty($FILE['tmp_name']) || empty($FILE['name']) || empty($FILE['type']) || empty($FILE['size'])) return false;
                
        if (!getimagesize($FILE['tmp_name']))  return false;
        
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir)))
        {
           mkdir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir));
        }
        
        if (!$this->sawanna->file->save($FILE,'avatar',$this->files_dir)) return false;
        
        if (!empty($meta) && isset($meta['avatar']) && !empty($meta['avatar']))
        {
            $file_id=$this->sawanna->file->get_id_by_name($meta['avatar']);
            $this->sawanna->file->delete($file_id);
        }
        
        $name=$this->sawanna->file->last_name;
        
        if (!$this->sawanna->constructor->resize_image(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir).'/'.quotemeta($name),ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir).'/'.quotemeta($name),100,100,false))
        {
            $file_id=$this->sawanna->file->get_id_by_name($name);
            $this->sawanna->file->delete($file_id);
            
            return false;
        }
        
        return true;
    }

    function get_field_types()
    {
        $types=array(
            'photo_100' => '[strf]Photo (%100x100%)[/strf]',
            'photo_200' => '[strf]Photo (%200x200%)[/strf]',
            'email' => '[str]E-Mail[/str]',
            'date' => '[str]Date[/str]',
            'text' => '[str]Text (single line)[/str]',
            'textarea' => '[str]Text (multiple lines)[/str]'
        );
        
        return $types;
    }
    
    function get_extra_field(&$fields,$field,$uid=0)
    {
        if (empty($field) || !is_object($field)) return false;
        
        $d=new CData();
        if (!empty($field->meta)) $field->meta=$d->do_unserialize($field->meta);
            
        $field_name=!empty($field->meta) && isset($field->meta['name']) && isset($field->meta['name'][$this->sawanna->locale->current]) ? $field->meta['name'][$this->sawanna->locale->current] : $field->name;
            
        
        if (!empty($uid))
        {
            $this->sawanna->db->query("select * from pre_user_extra_field_values where field_id='$1' and user_id='$2'",$field->id,$uid);
            $val=$this->sawanna->db->fetch_object();
        }
        
        switch($field->type)
        {
            case 'photo_100':
                if (isset($val->val) && !empty($val->val) && file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir).'/'.quotemeta($val->val)))
                {
                    $fields['field-'.$field->name.'_val']=array(
                        'type' => 'html',
                        'value' => '<div class="photo_preview"><img src="'.SITE.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir).'/'.quotemeta($val->val).'" width="100" /></div>'
                    );
                    
                    $fields['field-'.$field->name.'_current']=array(
                        'type' => 'hidden',
                        'name' => 'field-'.$field->name,
                        'value' => md5('field-'.$field->name.PRIVATE_KEY)
                    );
                }
                
                $fields['field-'.$field->name]=array(
                    'type' => 'file',
                    'title' => $field_name,
                    'description' => '[str]Please select image file to upload.[/str] [strf]Supported formats: %jpg,png,gif%[/strf]',
                    'required' => $field->required ? true : false
                );
                break;
            case 'photo_200':
                if (isset($val->val) && !empty($val->val) && file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir).'/'.quotemeta($val->val)))
                {
                    $fields['field-'.$field->name.'_val']=array(
                        'type' => 'html',
                        'value' => '<div class="photo_preview"><img src="'.SITE.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir).'/'.quotemeta($val->val).'" width="200" /></div>'
                    );
                    
                     $fields['field-'.$field->name.'_current']=array(
                        'type' => 'hidden',
                        'name' => 'field-'.$field->name,
                        'value' => md5('field-'.$field->name.PRIVATE_KEY)
                    );
                }
                
                $fields['field-'.$field->name]=array(
                    'type' => 'file',
                    'title' => $field_name,
                    'description' => '[str]Please select image file to upload.[/str] [strf]Supported formats: %jpg,png,gif%[/strf]',
                    'required' => $field->required ? true : false
                );
                break;
            case 'email':
                $fields['field-'.$field->name]=array(
                    'type' => 'email',
                    'title' => $field_name,
                    'description' => '[str]Please enter E-Mail address[/str]',
                    'default' => isset($val->val) && !empty($val->val) ? $val->val : '',
                    'required' => $field->required ? true : false
                );
                break;
            case 'date':
                $fields['field-'.$field->name]=array(
                    'type' => 'date',
                    'title' => $field_name,
                    'description' => '[strf]Please enter date in following format: %dd.mm.YY%[/strf]',
                    'default' => isset($val->val) && !empty($val->val) ? date('d.m.Y',$val->val) : '',
                    'required' => $field->required ? true : false
                );
                break;
            case 'text':
                $fields['field-'.$field->name]=array(
                    'type' => 'text',
                    'title' => $field_name,
                    'description' => '[strf]Maximum number of characters: %255%[/strf]',
                    'default' => isset($val->val) && !empty($val->val) ? $val->val : '',
                    'required' => $field->required ? true : false
                );
                break;
            case 'textarea':
                $fields['field-'.$field->name]=array(
                    'type' => 'textarea',
                    'title' => $field_name,
                    'description' => '[strf]Maximum number of characters: %10000%[/strf]',
                    'default' => isset($val->val) && !empty($val->val) ? $val->val : '',
                    'attributes' => array('cols'=>40,'rows'=>10,'style'=>'width:100%'),
                    'required' => $field->required ? true : false
                );
                break;
        }
    }
    
    function process_photo_field($field,$uid,$FILE,$width,$height)
    {
        if (!is_writeable(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir))) return false;
        
        if (empty($FILE) || !is_array($FILE) || empty($FILE['tmp_name']) || empty($FILE['name']) || empty($FILE['type']) || empty($FILE['size'])) return false;
        
        if (!getimagesize($FILE['tmp_name']))  return false;
        
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir)))
        {
           mkdir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir));
        }
  
        if (!$this->sawanna->file->save($FILE,'user-'.$uid.'-field-'.$field->id,$this->files_dir)) return false;
        
        $this->sawanna->db->query("select * from pre_user_extra_field_values where field_id='$1' and user_id='$2'",$field->id,$uid);
        $row=$this->sawanna->db->fetch_object();
        if (!empty($row))
        {
            $file_id=$this->sawanna->file->get_id_by_name($row->val);
            $this->sawanna->file->delete($file_id);
        }
        
        $name=$this->sawanna->file->last_name;
        
        if (!$this->sawanna->constructor->resize_image(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir).'/'.quotemeta($name),ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir).'/'.quotemeta($name),$width,$height,false)) 
        {
            $file_id=$this->sawanna->file->get_id_by_name($name);
            $this->sawanna->file->delete($file_id);
            
            return false;
        }
        
        $this->sawanna->db->query("select * from pre_user_extra_field_values where field_id='$1' and user_id='$2'",$field->id,$uid);
        if ($this->sawanna->db->num_rows())
        {
           $this->sawanna->db->query("update pre_user_extra_field_values set val='$1' where field_id='$2' and user_id='$3'",$name,$field->id,$uid);
        } else 
        {
           $this->sawanna->db->query("insert into pre_user_extra_field_values (field_id,user_id,val) values ('$1','$2','$3')",$field->id,$uid,$name);
        }
        
        return true;
    }
    
    function save_extra_field_value($field,$uid,$value)
    {
        if (empty($field) || !is_object($field) || empty($uid) || !is_numeric($uid)) return false;
        
        $this->sawanna->db->query("select * from pre_user_extra_field_values where field_id='$1' and user_id='$2'",$field->id,$uid);
        if ($this->sawanna->db->num_rows())
        {
           $this->sawanna->db->query("update pre_user_extra_field_values set val='$1' where field_id='$2' and user_id='$3'",$value,$field->id,$uid);
        } else 
        {
           $this->sawanna->db->query("insert into pre_user_extra_field_values (field_id,user_id,val) values ('$1','$2','$3')",$field->id,$uid,$value);
        }
    }
    
    function process_extra_field($field,$uid)
    {
        if (empty($field) || !is_object($field) || empty($uid) || !is_numeric($uid)) return false;
        
        switch($field->type)
        {
            case 'photo_100':
               if (!isset($_FILES['field-'.$field->name]['tmp_name']) || empty($_FILES['field-'.$field->name]['tmp_name'])) break;
               
               if (!$this->process_photo_field($field,$uid,$_FILES['field-'.$field->name],100,100))
               {
                   $FILE=array(
                        'tmp_name' => ROOT_DIR.'/misc/logo.png',
                        'name' => 'Sawanna',
                        'type' => 'image/png',
                        'size' => filesize(ROOT_DIR.'/misc/logo.png')
                   );
                   
                   $this->process_photo_field($field,$uid,$FILE,100,100);
               } 
               break;
            case 'photo_200':
               if (!isset($_FILES['field-'.$field->name]['tmp_name']) || empty($_FILES['field-'.$field->name]['tmp_name'])) break;
               
               if (!$this->process_photo_field($field,$uid,$_FILES['field-'.$field->name],200,200))
               {
                   $FILE=array(
                        'tmp_name' => ROOT_DIR.'/misc/logo.png',
                        'name' => 'Sawanna',
                        'type' => 'image/png',
                        'size' => filesize(ROOT_DIR.'/misc/logo.png')
                   );
                   
                   $this->process_photo_field($field,$uid,$FILE,200,200);
               } 
                break;
            case 'email':
               if (empty($_POST['field-'.$field->name])) break;
                
               $this->save_extra_field_value($field,$uid,$_POST['field-'.$field->name]);
               break;
            case 'date':
               if (empty($_POST['field-'.$field->name])) break;
               
               if (!preg_match('/^([\d]{2})\.([\d]{2})\.([\d]{4})$/',$_POST['field-'.$field->name],$matches)) 
                    $date=time();
                else                
                    $date=mktime(0,0,0,$matches[2],$matches[1],$matches[3]);
               
               $this->save_extra_field_value($field,$uid,$date);
               break;
            case 'text':
               if (empty($_POST['field-'.$field->name])) break;
                
               $_POST['field-'.$field->name]=htmlspecialchars($_POST['field-'.$field->name]);
               if (sawanna_strlen($_POST['field-'.$field->name]) > 255) $_POST['field-'.$field->name]=sawanna_substr($_POST['field-'.$field->name],0,255);
                
               $this->save_extra_field_value($field,$uid,$_POST['field-'.$field->name]);
               break;
            case 'textarea':
               if (empty($_POST['field-'.$field->name])) break;
                
               $_POST['field-'.$field->name]=htmlspecialchars($_POST['field-'.$field->name]);
               if (sawanna_strlen($_POST['field-'.$field->name]) > 10000) $_POST['field-'.$field->name]=sawanna_substr($_POST['field-'.$field->name],0,10000);
                
               $this->save_extra_field_value($field,$uid,$_POST['field-'.$field->name]);
               break;
        }
    }
    
    function profile_fields_list()
    {
         if (!$this->sawanna->has_access('administer users') || !$this->sawanna->has_access('create user extra fields')) 
        {
            trigger_error('Permission denied',E_USER_WARNING);
            return ;
        }
        
        $d=new CData();
        
        $html='<h1 class="tab">[str]User profile fields[/str]</h1>';
        $html.='<div id="fields-list">';
        
        $html.='<div class="fields-list-item head">';
        $html.='<div class="id">[str]ID[/str]</div>';
        $html.='<div class="field">[str]Field[/str]</div>';
        $html.='</div>';
            
        $co=0;
        
        $default_fields=$this->get_default_field_types();
        foreach($default_fields as $dftype=>$dfname)
        {
            if ($co%2===0)
           {
               $html.='<div class="fields-list-item">';
           } else 
           {
               $html.='<div class="fields-list-item mark highlighted">';
           }
           
           $html.='<div class="id">#</div>';
           $html.='<div class="field">'.$dfname.'</div>';
           $html.='</div>';
           
           $co++;
        }
        
        $this->sawanna->db->query("select * from pre_user_extra_fields order by weight");
        while ($row=$this->sawanna->db->fetch_object())
        {
           if ($co%2===0)
           {
               $html.='<div class="fields-list-item">';
           } else 
           {
               $html.='<div class="fields-list-item mark highlighted">';
           }
                
            if (!empty($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            
            $field_name=!empty($row->meta) && isset($row->meta['name']) && isset($row->meta['name'][$this->sawanna->locale->current]) ? $row->meta['name'][$this->sawanna->locale->current] : $row->name;
            
            $html.='<div class="id">'.$row->id.'</div>';
             $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/user/profile-field-delete/'.$row->id,'[str]Delete[/str]','button',array('onclick'=>'if (!confirm(\'[str]Are you sure ?[/str]\')){return false;}')).'</div>';
             $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/user/profile-field-edit/'.$row->id,'[str]Edit[/str]','button').'</div>';
             $html.='<div class="field">'.$field_name.'</div>';
             $html.='</div>';
             
             $co++;
        }
        
        $html.='</div>';
        
        echo $html;
        
        $this->profile_field_form();
    }
    
    function profile_field_form($id=0)
    {
        if (!$this->sawanna->has_access('administer users') || !$this->sawanna->has_access('create user extra fields')) 
        {
            trigger_error('Permission denied',E_USER_ERROR);
            return ;
        }
        
        $field=new stdClass();
        
        if (!empty($id) && !is_numeric($id)) 
        {
            trigger_error('Invalid field ID: '.$id,E_USER_ERROR);
            return;
        }
        if (!empty($id))
        {
            if (!$this->field_exists($id)) 
            {
                trigger_error('Field not found',E_USER_ERROR);
                return;    
            }
            
            $d=new CData();
            
            $field=$this->get_field($id);
            if (!empty($field->meta)) $field->meta=$d->do_unserialize($field->meta);
        }
        
        $fields=array();
        
        
        $_types=$this->get_field_types();
        $types=array(''=>'[str]Choose[/str]') + $_types;
        
        $fields['type']=array(
            'type' => 'select',
            'options' => $types,
            'title' => '[str]Field type[/str]',
            'description' => '[str]Choose field type[/str]',
            'default' => isset($field->type) ? $field->type : '',
            'required' => true
        );
        
        $fields['name']=array(
            'type' => 'text',
            'title' => '[str]Name[/str]',
            'description' => '[str]Specify field system name[/str]',
            'autocheck' => array('url'=>'user-extra-field-check','caller'=>'userAdminHandler','handler'=>'field_name_check'),
            'default' => isset($field->name) ? $field->name : '',
            'required' => true
        );
        
        $field_titles=array();
        if (!empty($field->meta) && isset($field->meta['name']))
        {
            $field_titles=$field->meta['name'];
        }
        
        $co=0;
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
                if ($co%2===0)
               {
                   $fields['title-block-begin-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '<div class="mark highlighted">'
                   );
               } else 
               {
                    $fields['title-block-begin-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '<div>'
                   );
               }
                   
                $fields['title-'.$lcode]=array(
                    'type' => 'text',
                    'title' => '[str]Title[/str]',
                    'description' => '[strf]Specify field title in following language: %'.htmlspecialchars($lname).'%[/strf]',
                    'default' => isset($field_titles[$lcode]) ? $field_titles[$lcode] : '',
                    'required' => false
                );
                
                 $fields['title-block-end-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '</div>'
                   );
                
                $co++;
        }
        
        $required_checked=!empty($field->required) ? array('checked'=>'checked','class'=>'checkbox') : array('class'=>'checkbox');
        
        /*
        $fields['weight']=array(
            'type' => 'text',
            'title' => '[str]Weight[/str]',
            'description' => '[str]Lighter fields appear first[/str]',
            'default' => isset($field->weight) ? $field->weight : '0',
            'required' => false
        );
        */
        
        $middle_weight=isset($field->weight) ? $field->weight : 0;
        
        $fields['weight']=array(
            'type' => 'select',
            'options' => array_combine(range($middle_weight-30,$middle_weight+30,1),range($middle_weight-30,$middle_weight+30,1)),
            'title' => '[str]Weight[/str]',
            'description' => '[str]Lighter fields appear first[/str]',
            'default' => isset($field->weight) ? $field->weight : '0',
            'attributes' => array('class'=>'slider'),
            'required' => false
        );

        $fields['required']=array(
            'type' => 'checkbox',
            'title' => '[str]Required[/str]',
            'description' => '[str]Registration process cannot be completed until all required fields are filled[/str]',
            'attributes' => $required_checked,
            'required' => false
        );

        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );
        
        echo '<h1 class="tab">'.(!isset($menu->id) ? '[str]New field[/str]' : '[str]Edit field[/str]').'</h1>';
        echo $this->sawanna->constructor->form('field-form',$fields,'profile_field_form_process',$this->sawanna->config->admin_area.'/user/profile',empty($id) ? $this->sawanna->config->admin_area.'/user/profile-field-add' : $this->sawanna->config->admin_area.'/user/profile-field-edit/'.$id,'','CUserAdmin');
        
    }
    
    function profile_field_form_process($id=0)
    {
        if (!$this->sawanna->has_access('administer users') || !$this->sawanna->has_access('create user extra fields')) 
        {
            return array('[str]Permission denied[/str]',array('create-field'));
        }

        if (empty($id) && preg_match('/^user\/profile-field-edit\/(.+)$/',$this->sawanna->arg,$matches)) $id=$matches[1];
        
        if (!empty($id) && !is_numeric($id)) return array('[strf]Invalid field ID: %'.$id.'%[/strf]',array('edit-field'));
        if (!empty($id) && !$this->field_exists($id)) return array('[str]Field not found[/str]',array('edit-field'));
        
        if (!$this->valid_field_name($_POST['name'])) return array('[str]Invalid field name[/str]',array('edit-field'));
        if (empty($id) && $this->field_name_exists($_POST['name'])) return array('[str]Field name already in use[/str]',array('edit-field'));
        
        $types=$this->get_field_types();
        if (!array_key_exists($_POST['type'],$types)) return array('[str]Invalid field type[/str]',array('edit-field'));
        
        $field=array();
        
        if (!empty($id)) 
        {
            $d=new CData();
            
            $field=$this->get_field($id,true);
            if (!empty($field['meta'])) $field['meta']=$d->do_unserialize($field['meta']);
            
            if ($_POST['name'] != $field['name'] && $this->field_name_exists($_POST['name'])) return array('[str]Field name already in use[/str]',array('edit-field'));
        }
        
        $meta=array('name'=>array());
        
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
            if (!empty($_POST['title-'.$lcode])) $meta['name'][$lcode]=htmlspecialchars($_POST['title-'.$lcode]);
        }
        
        if (!empty($field['meta']) && !empty($meta['name'])) $field['meta']=array_merge($field['meta'],$meta);
        else $field['meta']=$meta;
        
        $field['name']=htmlspecialchars($_POST['name']);
        $field['type']=$_POST['type'];
        
        $field['required']=0;
        if (isset($_POST['required']) && $_POST['required']=='on') $field['required']=1;
        
        if (!empty($_POST['weight']) && is_numeric($_POST['weight'])) $field['weight']=floor($_POST['weight']);
         
        if (!empty($id)) 
        {
            $field['id']=$id;
        }
        
        if (!$this->set_field($field))
        {
            return array('[str]Profile field could not be saved[/str]',array('new-field'));
        }

        return array('[str]Profile field saved[/str]');
    }
    
    function set_field($field)
    {
        if (empty($field) || !is_array($field)) return false;
        
        if (empty($field['type']) || empty($field['name']))
        {
            trigger_error('Cannot set profile field',E_USER_WARNING);
            return false;
        }
        
        if (!isset($field['meta'])) $field['meta']='';
        if (!isset($field['required'])) $field['required']=0;
        if (!isset($field['weight'])) $field['weight']=0;
        
        if (!empty($field['meta']))
        {
            $d=new CData();
            $field['meta']=$d->do_serialize($field['meta']);
        }
        
        if (isset($field['id']))
        {
            return $this->sawanna->db->query("update pre_user_extra_fields set name='$1', meta='$2', type='$3', required='$4', weight='$5' where id='$6'",$field['name'],$field['meta'],$field['type'],$field['required'],$field['weight'],$field['id']);
        } else 
        {
            return $this->sawanna->db->query("insert into pre_user_extra_fields (name,meta,type,required,weight) values ('$1','$2','$3','$4','$5')",$field['name'],$field['meta'],$field['type'],$field['required'],$field['weight']);
        }
    }
    
    function delete_field($id)
    {
        if (empty($id)) return false;

        if ($this->sawanna->db->query("delete from pre_user_extra_fields where id='$1'",$id))
        {
            $this->sawanna->db->query("delete from pre_user_extra_field_values where field_id='$1'",$id);
            return true;
        }
        
        return false;
    }
    
    function profile_field_delete($id)
    {
        if (!$this->sawanna->has_access('administer users') || !$this->sawanna->has_access('create user extra fields')) 
        {
            trigger_error('Could not delete profile field. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not delete profile field. Invalid group ID: '.$id,E_USER_WARNING);
            return ;
        }
        if (!$this->field_exists($id)) 
        {
            trigger_error('Could not delete profile field. Field not found',E_USER_WARNING);    
            return;
        }
        
        $event=array();
        
        if (!$this->delete_field($id))
        {
            $message='[str]Could not delete profile field. Database error[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
            $message='[strf]Profile field with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
            $event['job']='sawanna_message';
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);
        
        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/user/profile');
    }
    
    function get_message_types()
    {
        $types=array(
            'welcome'=>'[str]Welcome message[/str]',
            'register' => '[str]Registration complete message[/str]',
            'confirm' => '[str]Registration confirmation message[/str]',
            'terms'=> '[str]Terms and Conditions[/str]'
        );
        
        return $types;
    }
    
    function get_message_variables($type,$arr=false)
    {
        $vars=array();
        
        switch ($type)
        {
            case 'welcome':
                $vars=array(
                    '[login]'
                );
                break;
            case 'register':
                $vars=array(
                    '[login]',
                    '[email]',
                    '[site]'
                );
                break;
            case 'confirm':
                $vars=array(
                    '[login]',
                    '[email]',
                    '[link]',
                    '[site]'
                );
                break;
            case 'terms':
                $vars=array(
                    '[site]'
                );
                break;
        }
        
        if (!$arr)
            return implode(', ',$vars);
        else 
            return $vars;
    }
    
    function get_message($type,$language)
    {
        if (empty($type) || empty($language)) return false;
        
        $this->sawanna->db->query("select * from pre_user_messages where name='$1' and language='$2'",$type,$language);
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return $row;
        return false;
    }
    
    function messages_form()
    {
         if (!$this->sawanna->has_access('administer users') || !$this->sawanna->has_access('change user messages')) 
        {
            trigger_error('Permission denied',E_USER_ERROR);
            return ;
        }
        
        $fields=array();
        
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
            $fields['mess-tab-'.$lcode]=array(
                'type' => 'html',
                'value' => '<h1 class="tab">'.$lname.'</h1>'
            );
    
            $types=$this->get_message_types();
            
            $co=0;
            foreach ($types as $type=>$type_title)
            {
                $message=$this->get_message($type,$lcode);
    
                if ($co%2===0)
                {
                   $fields['mess-block-begin-'.$type.'-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '<div>'
                   );
                } else 
                {
                    $fields['mess-block-begin-'.$type.'-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '<div class="mark highlighted">'
                   );
                }
                   
                $fields['message-'.$type.'-'.$lcode]=array(
                    'type' => 'textarea',
                    'title' => $type_title,
                    'description' => '[strf]You can use following variables: %'.$this->get_message_variables($type).'%[/strf]',
                    'attributes' => array('cols'=> 40,'rows'=> 5, 'style'=> 'width:100%'),
                    'default' => isset($message->content) ? $message->content : '',
                    'required' => false
                );
                
                 $fields['mess-block-end-'.$type.'-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '</div>'
                   );

                $co++;
            }
                
            $fields['submit-'.$lcode]=array(
                'type'=>'submit',
                'value'=>'[str]Submit[/str]'
            );
        }
        
        echo $this->sawanna->constructor->form('messages-form',$fields,'messages_form_process',$this->sawanna->config->admin_area.'/user/messages',$this->sawanna->config->admin_area.'/user/messages','','CUserAdmin');
    }
    
    function messages_form_process()
    {
        if (!$this->sawanna->has_access('administer users') || !$this->sawanna->has_access('change user messages')) 
        {
            return array('[str]Permission denied[/str]',array('create-message'));
        }

        $errors=array();
        $types=$this->get_message_types();
        
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
            foreach ($types as $type=>$type_title)
            {
                if (empty($_POST['message-'.$type.'-'.$lcode])) 
                {
                    $this->delete_message($type,$lcode);
                    continue;
                }
                
                $message=array();
                $message['type']=$type;
                $message['language']=$lcode;
                $message['content']=htmlspecialchars($_POST['message-'.$type.'-'.$lcode]);
                
                if (!$this->set_message($message))
                {
                    $errors[]='[strf]%'.htmlspecialchars($type_title).'% could not be saved[/strf]';
                }
            }
        }
        
        $_errors=sawanna_error();
        if (!empty($_errors)) $errors=array_merge($errors,$_errors);
        
        if (!empty($errors))
        {
            return array('[str]Error[/str]:<ul><li>'.implode('</li><li>',$errors).'</li></ul>','messages');
        }
        else 
        {
            return array('[str]Messages saved[/str]');
        }
    }
    
    function set_message($message)
    {
        if (empty($message) || !is_array($message) || !isset($message['type']) || !isset($message['language']) || !isset($message['content'])) return false;
        
        $ex=$this->get_message($message['type'],$message['language']);
        if (!empty($ex))
        {
            return $this->sawanna->db->query("update pre_user_messages set content='$1' where name='$2' and language='$3'",$message['content'],$message['type'],$message['language']);
        } else 
        {
            return $this->sawanna->db->query("insert into pre_user_messages (name,language,content) values ('$1','$2','$3')",$message['type'],$message['language'],$message['content']);
        }
    }
    
    function delete_message($type,$language)
    {
        if (empty($type) || empty($language)) return false;
        
        return $this->sawanna->db->query("delete from pre_user_messages where name='$1' and language='$2'",$type,$language);
    }
    
    function node_group_access_field($nid)
    {
        $d=new CData();
        $node_perm='';
        $default='all';
        
        if (!empty($nid))
        {
            $this->sawanna->db->query("select permission from pre_nodes where id='$1'",$nid);
            $row=$this->sawanna->db->fetch_object();
            if (!empty($row->permission))
            {
                $node_perm=$row->permission;
            }
        }
        
        if ($node_perm=='user group 0 access')
        {
            $default='#';
            $node_perm='';
        }
        
        $groups=array('all' => '[str]All user groups[/str]','#'=>'[str]Guests[/str]');
        
        $res=$this->sawanna->db->query("select * from pre_user_groups order by id");
        while ($row=$this->sawanna->db->fetch_object($res))
        {
            $this->sawanna->db->query("select access from pre_user_group_permissions where permission='su' and group_id='$1'",$row->id);
            $_row=$this->sawanna->db->fetch_object();
            
            if (!empty($_row->access)) continue;
            
            if (!empty($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            
            $group_name=!empty($row->meta) && isset($row->meta['name']) && isset($row->meta['name'][$this->sawanna->locale->current]) ? $row->meta['name'][$this->sawanna->locale->current] : $row->name;
            
            $groups[$row->id]=$group_name;
            
            if (!empty($node_perm))
            {
                $group_perm_name='user group %s access';
                if (sprintf($group_perm_name,$row->id)==$node_perm) $default=$row->id;
            }
        }
        
        $field['node_user_group_access']=array(
            'type' => 'select',
            'options' => $groups,
            'title' => '[str]Access[/str]',
            'description' => '[str]Please select user group[/str]',
            'default' => $default
        );
        
        return $field;
    }
    
    function menu_group_access_field($mid)
    {
        $d=new CData();
        $menu_perm='';
        $default='all';
        
        if (!empty($mid))
        {
            $this->sawanna->db->query("select n.permission from pre_navigation n  left join pre_menu_elements m on m.path=n.path where m.id='$1'",$mid);
            $row=$this->sawanna->db->fetch_object();
            if (!empty($row->permission))
            {
                $menu_perm=$row->permission;
            }
        }
        
        if ($menu_perm=='user group 0 access')
        {
            $default='#';
            $menu_perm='';
        }
        
        $groups=array('all' => '[str]All user groups[/str]','#'=>'[str]Guests[/str]');
        
        $res=$this->sawanna->db->query("select * from pre_user_groups order by id");
        while ($row=$this->sawanna->db->fetch_object($res))
        {
            $this->sawanna->db->query("select access from pre_user_group_permissions where permission='su' and group_id='$1'",$row->id);
            $_row=$this->sawanna->db->fetch_object();
            
            if (!empty($_row->access)) continue;
            
            if (!empty($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            
            $group_name=!empty($row->meta) && isset($row->meta['name']) && isset($row->meta['name'][$this->sawanna->locale->current]) ? $row->meta['name'][$this->sawanna->locale->current] : $row->name;
            
            $groups[$row->id]=$group_name;
            
            if (!empty($menu_perm))
            {
                $group_perm_name='user group %s access';
                if (sprintf($group_perm_name,$row->id)==$menu_perm) $default=$row->id;
            }
        }
        
        $field['menu_user_group_access']=array(
            'type' => 'select',
            'options' => $groups,
            'title' => '[str]Access[/str]',
            'description' => '[str]Please select user group[/str] ([str]for internal pathes only[/str])',
            'default' => $default
        );
        
        return $field;
    }
    
    function widget_group_access_field($wid)
    {
        $d=new CData();
        $widget_perm='';
        $default='all';
        
        if (!empty($wid))
        {
            $this->sawanna->db->query("select permission from pre_widgets where id='$1'",$wid);
            $row=$this->sawanna->db->fetch_object();
            if (!empty($row->permission))
            {
                $widget_perm=$row->permission;
            }
        }
        
        if ($widget_perm=='user group 0 access')
        {
            $default='#';
            $widget_perm='';
        }
        
        $groups=array('all' => '[str]All user groups[/str]','#'=>'[str]Guests[/str]');
        
        $res=$this->sawanna->db->query("select * from pre_user_groups order by id");
        while ($row=$this->sawanna->db->fetch_object($res))
        {
            $this->sawanna->db->query("select access from pre_user_group_permissions where permission='su' and group_id='$1'",$row->id);
            $_row=$this->sawanna->db->fetch_object();
            
            if (!empty($_row->access)) continue;
            
            if (!empty($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            
            $group_name=!empty($row->meta) && isset($row->meta['name']) && isset($row->meta['name'][$this->sawanna->locale->current]) ? $row->meta['name'][$this->sawanna->locale->current] : $row->name;
            
            $groups[$row->id]=$group_name;
            
            if (!empty($widget_perm))
            {
                $group_perm_name='user group %s access';
                if (sprintf($group_perm_name,$row->id)==$widget_perm) $default=$row->id;
            }
        }
        
        $field['widget_user_group_access']=array(
            'type' => 'select',
            'options' => $groups,
            'title' => '[str]Access[/str]',
            'description' => '[str]Please select user group[/str]',
            'default' => $default
        );
        
        return $field;
    }
    
    function group_access_field_validate($nid)
    {
        if (!empty($_POST['node_user_group_access']) && is_numeric($_POST['node_user_group_access']))
        {
            $this->sawanna->db->query("select * from pre_user_groups where id='$1'",$_POST['node_user_group_access']);
            $row=$this->sawanna->db->fetch_object();
            
            if (empty($row))
                return array('[str]Invalid group[/str]',array('node_user_group_access'));
        }
    }
    
    function node_group_access_field_process($nid)
    {
        if (!empty($_POST['node_user_group_access']) && is_numeric($_POST['node_user_group_access']))
        {
             $group_perm_name='user group '.$_POST['node_user_group_access'].' access';
             
             $this->sawanna->db->query("update pre_nodes set permission='$1' where id='$2'",$group_perm_name,$nid);
        } elseif ($_POST['node_user_group_access']=='#')
        {
            $group_perm_name='user group 0 access';
            
            $this->sawanna->db->query("update pre_nodes set permission='$1' where id='$2'",$group_perm_name,$nid);
        } else 
        {
            $this->sawanna->db->query("update pre_nodes set permission='' where id='$1'",$nid);
        }
    }
    
    function menu_group_access_field_process($mid)
    {
        if (!isset($_POST['menu_user_group_access'])) return ;
        
        $path='';
        $this->sawanna->db->query("select path from pre_menu_elements where id='$1'",$mid);
        $row=$this->sawanna->db->fetch_object();
        $path=$row->path;
        
        $menu_module_id=$this->sawanna->module->get_module_id('menu');
        $content_module_id=$this->sawanna->module->get_module_id('content');
        
        if (empty($row->path) || $row->path=='[home]' || preg_match('/^http:\/\/.+$/',$row->path)) return ;
        
        if (!empty($_POST['menu_user_group_access']) && is_numeric($_POST['menu_user_group_access']))
        {
             $group_perm_name='user group '.$_POST['menu_user_group_access'].' access';
             
             $this->sawanna->db->query("update pre_navigation set permission='$1' where path='$2' and ( module='$3' or module='$4' )",$group_perm_name,$path,$menu_module_id,$content_module_id);
             $this->sawanna->db->query("update pre_navigation set permission='$1' where path='$2' and ( module='$3' or module='$4' )",$group_perm_name,$path.'/d',$menu_module_id,$content_module_id);
        } elseif ($_POST['menu_user_group_access']=='#')
        {
            $group_perm_name='user group 0 access';
            
            $this->sawanna->db->query("update pre_navigation set permission='$1' where path='$2' and ( module='$3' or module='$4' )",$group_perm_name,$path,$menu_module_id,$content_module_id);
            $this->sawanna->db->query("update pre_navigation set permission='$1' where path='$2' and ( module='$3' or module='$4' )",$group_perm_name,$path.'/d',$menu_module_id,$content_module_id);
        } else 
        {
            $this->sawanna->db->query("update pre_navigation set permission='' where path='$1' and ( module='$2' or module='$3' )",$path,$menu_module_id,$content_module_id);
            $this->sawanna->db->query("update pre_navigation set permission='' where path='$1' and ( module='$2' or module='$3' )",$path.'/d',$menu_module_id,$content_module_id);
        }
    }
    
    function widget_group_access_field_process($wid)
    {
        if (!empty($_POST['widget_user_group_access']) && is_numeric($_POST['widget_user_group_access']))
        {
             $group_perm_name='user group '.$_POST['widget_user_group_access'].' access';
             
             $this->sawanna->db->query("update pre_widgets set permission='$1' where id='$2'",$group_perm_name,$wid);
        } elseif ($_POST['widget_user_group_access']=='#')
        {
            $group_perm_name='user group 0 access';
            
            $this->sawanna->db->query("update pre_widgets set permission='$1' where id='$2'",$group_perm_name,$wid);
        } else 
        {
            $this->sawanna->db->query("update pre_widgets set permission='' where id='$1'",$wid);
        }
    }
}
