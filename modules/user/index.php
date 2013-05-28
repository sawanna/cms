<?php
defined('SAWANNA') or die();

class CUser
{
    var $sawanna;
    var $module_id;
    var $user_default_group;
    var $files_dir;
    var $current;
    var $panel;
    var $profile_links;
    var $user_groups;
    var $exists; // caching db results
    
    function CUser(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('user');
        $user_default_group=$this->sawanna->get_option('user_default_group');
        
        $this->get_user_groups();
        
        $this->user_default_group=is_numeric($user_default_group) && $this->group_exists($user_default_group) ? $user_default_group : 0;
        $this->files_dir='users';
        $this->panel=array();
        $this->profile_links=array();
        $this->user_groups=array();
        $this->exists=array();
       
        if ($this->sawanna->query != $this->sawanna->config->login_area) $this->init();
        
        if ($this->sawanna->query != $this->sawanna->config->admin_area) $this->sawanna->tpl->add_script($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/js/panel');
    }
    
    function init()
    {
        $this->sawanna->config->login_area='user/login';

        $this->current=new stdClass();
        $this->current->id=0;
        $this->current->meta=$this->get_anonymous_user();
        $this->current->created=null;
        
        $user_login=$this->sawanna->session->get('user_login');
        $user_password=$this->sawanna->session->get('user_password');
        
        if (!$this->sawanna->access->check_su())
        {
            $this->sawanna->config->pwd_recover_area='user/recover';
            $this->sawanna->config->login_auth_dest='user';
            $this->sawanna->config->logout_dest='user/login';
            $this->sawanna->config->logout_area='user/logout';
            
            if (!empty($user_login) && !empty($user_password) && ($user=$this->check_user($user_login,$user_password))!=false)
            {
                $d=new CData();
                
                if (!empty($user->meta)) 
                {
                    $user->meta=$d->do_unserialize($user->meta);
                }
                
                if (!empty($user->meta['avatar']) && file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir).'/'.quotemeta($user->meta['avatar'])))
                    $user->meta['avatar']=SITE.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir).'/'.quotemeta($user->meta['avatar']);
                else 
                    $user->meta['avatar']=SITE.'/'.quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/images/user.jpg';
                
                if (!empty($user->meta['gender']) && $user->meta['gender']=='M')
                    $user->meta['gender']='[str]Male[/str]';
                elseif(!empty($user->meta['gender']) && $user->meta['gender']=='F')
                    $user->meta['gender']='[str]Female[/str]';
                else
                    $user->meta['gender']='';
                
                $this->current=$user;
           
                if (!empty($this->current->group_id)) 
                {
                    $table=$this->get_group_access_table($this->current->group_id);
                    foreach ($table as $name=>$permission)
                    {
                        $this->sawanna->access->table[$name]=$permission->access;
                    }
                }

                $this->sawanna->config->logged_user_profile_url='user/'.$this->current->id;
                $this->sawanna->config->logged_user_name=$this->get_user_name();
                
                $this->sawanna->access->logged=true;
            } else 
            {
                $this->sawanna->session->clear('user_login');
                $this->sawanna->session->clear('user_password');
            }
        } else 
        {
            $this->current->meta['name']=$this->sawanna->config->logged_user_name;
        }
    }
    
    function ready()
    {
        if ($this->sawanna->query != $this->sawanna->config->login_area && !$this->sawanna->config->site_offline) $this->register_activity($this->current->id);
        
        if ($this->sawanna->query=='user/login') $this->sawanna->tpl->default_title='[str]Sign in[/str]';
        if ($this->sawanna->query=='user/register') $this->sawanna->tpl->default_title='[str]Registration[/str]';
        if ($this->sawanna->query=='user/recover') $this->sawanna->tpl->default_title='[str]Password recovery[/str]';
        if ($this->sawanna->query=='user' && empty($this->sawanna->arg)) $this->sawanna->tpl->default_title='[str]My Profile[/str]';
        if ($this->sawanna->query=='user' && !empty($this->sawanna->arg)) $this->sawanna->tpl->default_title='[str]User Profile[/str]';
        if ($this->sawanna->query=='user/edit') $this->sawanna->tpl->default_title='[str]Edit Profile[/str]';
        
        //disabling all widgets on login page
        if ($this->sawanna->query=='user/login')
        {
            foreach ($this->sawanna->module->widgets as $wid=>$widget)
            {
                if ($widget->name != 'userLoginForm')
                    $this->sawanna->module->widgets[$wid]->enabled=0;
            }
        }
        
        // disabling caching for authenticated users
        if ($this->logged())
        {
            $this->sawanna->cache->enabled=false;
        }
    }
    
    function login_reserved($login)
    {
        $reserved=array(
            'admin',
            'administrator'
        );
        
        $login=strtolower($login);
        if (in_array($login,$reserved)) return true;
        
        return false;
    }
    
    function logged()
    {
        if ($this->sawanna->access->check_su()) return true;
        
        if (!isset($this->current->id) || $this->current->id<=0 || !$this->user_exists($this->current->id)) return false;
        
        return true;
    }
    
    function check_user($login,$password_hash)
    {
        if (empty($login) || empty($password_hash)) return false;
        
        $user=$this->get_user_by_login($login);
        if (!$user) return false;
        
        if ($user->password != $password_hash) return false;
        
        return $user;
    }
    
    function user_exists($id)
    {
        if (array_key_exists($id,$this->exists)) return $this->exists[$id]; 
        
        if (empty($id)) return false;
        
        if (is_numeric($id))
            $this->sawanna->db->query("select * from pre_users where id='$1'",$id);
        else 
            $this->sawanna->db->query("select * from pre_users where login='$1'",$id);
            
        $exists=$this->sawanna->db->num_rows();
        
        $this->exists[$id]=$exists;
        
        return $exists;
    }
    
    function email_exists($mail)
    {
        if (empty($mail)) return false;
        
       $this->sawanna->db->query("select * from pre_users where email='$1'",$mail);
        
        return $this->sawanna->db->num_rows();
    }
    
    // deprecated
    function _group_exists($id)
    {
        if (empty($id)) return false;
        
        $this->sawanna->db->query("select * from pre_user_groups where id='$1'",$id);
        
        return $this->sawanna->db->num_rows();
    }
    
    function group_exists($id)
    {
        if (empty($id)) return false;
        
        return array_key_exists($id,$this->user_groups);
    }
    
    function get_user($id,$arr=false)
    {
        if (empty($id)) return false;
        
        $this->sawanna->db->query("select u.* from pre_users u left join pre_user_groups g on g.id=u.group_id where u.id='$1' and u.enabled<>'0' and u.active<>'0' and g.enabled<>'0'",$id);
        if (!$arr)
            $row=$this->sawanna->db->fetch_object();
        else 
            $row=$this->sawanna->db->fetch_array();
            
        if (!empty($row)) return $row;
        return false;
    }
    
    function get_user_by_login($login)
    {
        if (empty($login)) return false;
        
        $this->sawanna->db->query("select u.* from pre_users u left join pre_user_groups g on g.id=u.group_id where u.login='$1' and u.enabled<>'0' and u.active<>'0' and g.enabled<>'0'",$login);
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return $row;
        return false;
    }
    
    function get_user_name()
    {
        if (isset($this->current->meta) && is_array($this->current->meta) && isset($this->current->meta['name']) && !empty($this->current->meta['name'])) return $this->current->meta['name'];
        if (isset($this->current->login) && !empty($this->current->login)) return $this->current->login;
        
        return '[str]Unknown[/str]';
    }
    
    function get_user_meta($user)
    {
        if (empty($user) || !is_object($user)) return ;
        
        $meta=array('name'=>'','avatar'=>'','gender'=>'');
        
        $d=new CData();
        if (!empty($user->meta)) $user->meta=$d->do_unserialize($user->meta);
        
        $meta['name']='[str]Unknown[/str]';
        if (isset($user->login) && !empty($user->login)) $meta['name']=htmlspecialchars($user->login);
        if (isset($user->meta) && is_array($user->meta) && isset($user->meta['name']) && !empty($user->meta['name'])) $meta['name']=htmlspecialchars($user->meta['name']);
    
        if (!empty($user->meta['avatar']) && file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir).'/'.quotemeta($user->meta['avatar'])))
            $meta['avatar']=SITE.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir).'/'.quotemeta($user->meta['avatar']);
        else 
            $meta['avatar']=SITE.'/'.quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/images/user.jpg';
        
        if (!empty($user->meta['gender']) && $user->meta['gender']=='M')
            $meta['gender']='[str]Male[/str]';
        elseif(!empty($user->meta['gender']) && $user->meta['gender']=='F')
            $meta['gender']='[str]Female[/str]';
        else
            $meta['gender']='';
            
        return $meta;
    }
    
    function get_anonymous_user()
    {
        return  array(
                        'name'=>'[str]Guest[/str]',
                        'avatar'=>SITE.'/'.quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/images/user.jpg',
                        'gender'=>''
                        );
    }
    
    function get_super_user()
    {
        return  array(
                        'name'=>'[str]Administrator[/str]',
                        'avatar'=>SITE.'/'.quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/images/user.jpg',
                        'gender'=>''
                        );
    }
   
    function get_user_groups()
    {
        if (!empty($this->user_groups)) return $this->user_groups;
        
        $groups=array();
        
        $d=new CData();
        
        $this->sawanna->db->query("select * from pre_user_groups where enabled <> '0'");
        while ($row=$this->sawanna->db->fetch_object())
        {
            if (!empty($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            
            $group_name=!empty($row->meta) && isset($row->meta['name']) && isset($row->meta['name'][$this->sawanna->locale->current]) ? $row->meta['name'][$this->sawanna->locale->current] : $row->name;
            
            $groups[$row->id]=$group_name;
        }
        
        $this->user_groups=$groups;
        
        return $groups;
    }
    
    function get_group_access_table($group_id)
    {
        if (empty($group_id)) return false;
        
        $default=$this->sawanna->access->get_access_table();
        
        $table=array();
        foreach ($default as $perm)
        {
            $table[$perm->name]=$perm;
        }
        
        $this->sawanna->db->query("select * from pre_user_group_permissions where group_id='$1' order by id",$group_id);
        while($row=$this->sawanna->db->fetch_object())
        {
            $table[$row->permission]=$row;
        }
        
        return $table;
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
        
        if ($this->login_reserved($_POST['q']))
        {
            echo $this->sawanna->locale->get_string('Invalid login');
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
    
    function register_form()
    {
        if ($this->logged()) 
        {
            $recover_form=array(
                'login' => array(
                    'type' => 'html',
                    'title' => '[str]Logged in as[/str]:',
                    'value' => '<span class="login">'.$this->get_user_name().'</span>'
                  ),
                 'password' => array(
                    'type' => 'html',
                    'value' => $this->sawanna->constructor->link('user/logout','[str]Logout[/str]','logout')
                  )
            );
            
            return $this->sawanna->constructor->form('user-form',$recover_form,'Cuser','user/login','user/register','userLoginForm','CUser');
        } else 
        {
            return array('form'=>$this->user_form());
        }
    }
    
    function user_form($id=0)
    {
        if (empty($id) && !$this->sawanna->has_access('create user')) 
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
        
        if (!empty($id) && $id!=$this->current->id)
        {
            trigger_error('Permission denied',E_USER_ERROR);
            return ;
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
        
        $terms=$this->get_message('terms',$this->sawanna->locale->current);
        
        if (empty($id) && !empty($terms))
        {
            $fields['terms']=array(
                'type' => 'html',
                'value' => '<h2 class="user-terms-cap">[str]Terms and Conditions[/str]</h2><div class="user-terms">'.nl2br($terms->content).'</div>'
            );    
        }
        
        if (empty($id))
        {
            $fields['login']=array(
                'type' => 'text',
                'title' => '[str]Login[/str]',
                'description' => '[str]Enter your login[/str]',
                'autocheck' => array('url'=>'user-login-check','caller'=>'user','handler'=>'user_login_check'),
                'default' => isset($user->login) ? $user->login : '',
                'required' => true
            );
        }
        
        $fields['password']=array(
            'type' => 'password',
            'title' => '[str]Password[/str]',
            'autocheck' => array('url'=>'user-password-check','caller'=>'user','handler'=>'user_password_check'),
            'description' => '[str]Enter your password here[/str]',
            'required' => empty($id) ? true : false
        );
        
       $fields['password_re']=array(
            'type' => 'password',
            'title' => '[str]Retype password[/str]',
            'description' => '[str]Please enter password again[/str]',
            'required' => empty($id) ? true : false
        );
        
        if (empty($id))
        {
           $fields['email']=array(
                'type' => 'email',
                'title' => '[str]E-Mail[/str]',
                'description' => '[str]Enter your E-Mail address here[/str]',
                'autocheck' => array('url'=>'user-email-check','caller'=>'user','handler'=>'user_email_check'),
                'default' => isset($user->email) ? $user->email : '',
                'required' => true
            );
        }
        
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
        
        if (empty($id) && !empty($terms))
        {
            $fields['terms_accept']=array(
                'type' => 'checkbox',
                'title' => '[str]Agreement[/str]',
                'description' => '[str]I accept terms and conditions[/str]',
                'attributes' => array('class'=>'checkbox'),
                'required' => true
            );
        }
        
        $fields['captcha']=array(
            'type'=>'captcha',
            'title' => '[str]Security code[/str]',
            'description' => '[str]Please enter security code shown above[/str]',
            'required' => true
        );

        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        return $this->sawanna->constructor->form('user-form',$fields,'user_form_process',empty($id) ? 'user/login' : 'user',empty($id) ? 'user/register' : 'user/edit','','CUser');
    }
    
    function user_form_process($id=0)
    {
        if (empty($id) && $this->sawanna->query=='user/edit' && $this->logged()) $id=$this->current->id;

        if (empty($id) && !$this->sawanna->has_access('create user')) 
        {
            return array('[str]Permission denied[/str]',array('create-user'));
        }
        
        if (!empty($id) && !is_numeric($id)) return array('[strf]Invalid user ID: %'.$id.'%[/strf]',array('edit-user'));
        if (!empty($id) && !$this->user_exists($id)) return array('[str]User not found[/str]',array('edit-user'));
        
        if (!empty($id) && $id!=$this->current->id) return array('[str]Permission denied[/str]',array('edit-user'));
        
        $user=array();
        if (!empty($id)) 
        {
            $user=$this->get_user($id,true);
            
            if (!$user) return array('[str]User not found[/str]',array('edit-user'));
            
            $d=new CData();
            if (!empty($user['meta'])) $user['meta']=$d->do_unserialize($user['meta']);
        }
        
        if (empty($id) && empty($_POST['password']))
        {
            return array('[str]Password required[/str]',array('password'));
        }
        
        if (empty($id) && !valid_login($_POST['login']))
        {
            return array('[str]Invalid login[/str]',array('login'));
        }
        
        if (empty($id) && $this->user_exists($_POST['login']))
        {
            return array('[str]Login already in use[/str]',array('login'));
        }
        
        if (empty($id) && $this->login_reserved($_POST['login']))
        {
            return array('[str]Invalid login[/str]',array('login'));
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
     
        if (empty($id) && !valid_email($_POST['email']))
        {
            return array('[str]Invalid E-Mail[/str]',array('email'));
        }
        
        if (empty($id) && $this->email_exists($_POST['email']))
        {
            return array('[str]E-Mail already in use[/str]',array('email'));
        }
        
        if (empty($id))
            $user['login']=$_POST['login'];
        
        if (!empty($_POST['password']))
            $user['password']=$this->sawanna->access->pwd_hash($_POST['password']);
        
        if (empty($id))
            $user['email']=$_POST['email'];
        
        if (empty($id))
        {
            $user['active']=0;
            if ($this->sawanna->has_access('create user without confirmation'))
            {
                $user['active']=1;
            }
            
            $user['enabled']=1;
        }
        
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
        if (!isset($user['id']) && $user['enabled'] && $user['active']) $this->send_register_message($id);

        // calling register hook
        if (!isset($user['id'])) {
			$this->sawanna->process_hook('user_register');
		}
        
        return !isset($user['id']) ? array('[str]Account created[/str]') : array('[str]Account saved[/str]');
    }
    
    function external_register($login,$password,$email)
    {
        $user=array();
        
        if (empty($login) || empty($password) || empty($email)) return false;
        if (!valid_login($login) || $this->user_exists($login) || $this->login_reserved($login)) return false;
        if (!valid_password($password)) return false;
        if (!valid_email($email) || $this->email_exists($email)) return false;
        
		$user['login']=$login;
		$user['password']=$this->sawanna->access->pwd_hash($password);
		$user['email']=$email;
	
		$user['active']=0;
		if ($this->sawanna->has_access('create user without confirmation'))
		{
			$user['active']=1;
		}
		
		$user['enabled']=1;
        $user['meta']='';
        
        return $this->set($user);
    }
    
    function set($user)
    {
        if (empty($user) || !is_array($user)) return false;
        
        if (empty($user['login']) || empty($user['password']) || empty($user['email']))
        {
            trigger_error('Cannot set user',E_USER_WARNING);
            return false;
        }
        
        if (!isset($user['group_id'])) $user['group_id']=$this->user_default_group;
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
            return $this->sawanna->db->query("update pre_users set login='$1', password='$2', email='$3', group_id='$4', active='$5', enabled='$6', meta='$7' where id='$8'",$user['login'],$user['password'],$user['email'],$user['group_id'],$user['active'],$user['enabled'],$user['meta'],$user['id']);
        } else 
        {
            return $this->sawanna->db->query("insert into pre_users (login,password,email,group_id,active,enabled,created,meta) values ('$1','$2','$3','$4','$5','$6','$7','$8')",$user['login'],$user['password'],$user['email'],$user['group_id'],$user['active'],$user['enabled'],time(),$user['meta']);
        }
    }
    
    function send_confirm_message($uid)
    {
        if (empty($uid) || !is_numeric($uid)) return false;
        
        $this->sawanna->db->query("select * from pre_users where id='$1'",$uid);
        $row=$this->sawanna->db->fetch_object();
        
        $key=md5('user-'.$uid.'-'.$row->created.PRIVATE_KEY);
            
        $confirm_link=$this->sawanna->constructor->url('user/confirm',true,false,true);
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
        return $this->sawanna->constructor->sendMail($row->email,$this->sawanna->config->send_mail_from,$this->sawanna->config->send_mail_from_name,$this->sawanna->locale->get_string('Account activation'),$message);
        if ($this->sawanna->constructor->sendMail($row->email,$this->sawanna->config->send_mail_from,$this->sawanna->config->send_mail_from_name,$this->sawanna->locale->get_string('Account activation'),$message))
        {
            return true;
        } else 
        {
            return false;
        }
    }
    
    function send_register_message($uid)
    {
        if (empty($uid) || !is_numeric($uid)) return false;
        
        $message=$this->get_message('register',$this->sawanna->locale->current);
        if (empty($message)) return false;
        
        $this->sawanna->db->query("select * from pre_users where id='$1'",$uid);
        $row=$this->sawanna->db->fetch_object();

        $vars=$this->get_message_variables('register',true);
        
        $vals=array(
            '[login]' => $row->login,
            '[email]' => $row->email,
            '[site]' => SITE
        );
        
        foreach ($vars as $var)
        {
            $val=isset($vals[$var]) ? $vals[$var] : ' ';
            $message->content=str_replace($var,$val,$message->content);
        }
        
        $message=$message->content;
        
        if ($this->sawanna->constructor->sendMail($row->email,$this->sawanna->config->send_mail_from,$this->sawanna->config->send_mail_from_name,$this->sawanna->locale->get_string('Registration complete'),$message))
        {
            return true;
        } else 
        {
            return false;
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
                    'description' => '[str]Please specify your full name here[/str]',
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
    
    function confirm_form()
    {
        if (isset($_GET['key']) && isset($_GET['uid']))
        {
            if ($this->do_confirm($_GET['uid'],$_GET['key']))
            {
                $event=array();
                $event['job']='sawanna_message';
                $event['argument']='[str]Your account activated[/str]';
                $this->sawanna->event->register($event);

                $this->send_register_message($_GET['uid']);
                
                $this->sawanna->constructor->redirect('user/login');
            }
        }
        
        if ($this->logged()) 
        {
            $fields=array(
                'login' => array(
                    'type' => 'html',
                    'title' => '[str]Logged in as[/str]:',
                    'value' => '<span class="login">'.$this->get_user_name().'</span>'
                  ),
                 'password' => array(
                    'type' => 'html',
                    'value' => $this->sawanna->constructor->link('user/logout','[str]Logout[/str]','logout')
                  )
            );
            
            return $this->sawanna->constructor->form('user-form',$fields,'CUser','user/login','user/confirm','userLoginForm','CUser');
        } else 
        {
            $fields['login']=array(
                'type' => 'text',
                'title' => '[str]Login[/str]',
                'description' => '[str]Enter your login[/str]',
                'default' => isset($user->login) ? $user->login : '',
                'required' => true
            );
            
           $fields['email']=array(
                'type' => 'email',
                'title' => '[str]E-Mail[/str]',
                'description' => '[str]Enter your E-Mail address here[/str]',
                'default' => isset($user->email) ? $user->email : '',
                'required' => true
            );
            
            $fields['captcha']=array(
                'type'=>'captcha',
                'title' => '[str]Security code[/str]',
                'description' => '[str]Please enter security code shown above[/str]',
                'required' => true
            );
    
            $fields['submit']=array(
                'type'=>'submit',
                'value'=>'[str]Submit[/str]'
            );
        }

        return $this->sawanna->constructor->form('user-form',$fields,'confirm_form_process','user/login','user/confirm','userConfirm','CUser');
    }
    
    function confirm_form_process()
    {
        if ($this->logged()) return;
        
        $this->sawanna->db->query("select u.* from pre_users u left join pre_user_groups g on g.id=u.group_id where u.login='$1' and u.email='$2' and u.enabled<>'0' and u.active='0' and g.enabled<>'0'",$_POST['login'],$_POST['email']);
        $row=$this->sawanna->db->fetch_object();
        
        if (empty($row)) return array('[str]User not found[/str]',array('confirm-user'));
        
        $this->send_confirm_message($row->id);
        return array('[str]Confirmation message sent to your E-Mail address[/str]');
    }
    
    function do_confirm($uid,$key)
    {
        if ($this->logged()) return false;
        if (empty($uid) || !is_numeric($uid)) return false;
        
        $this->sawanna->db->query("select u.* from pre_users u left join pre_user_groups g on g.id=u.group_id where u.id='$1' and u.enabled<>'0' and u.active='0' and g.enabled<>'0'",$uid);
        $row=$this->sawanna->db->fetch_object();
        
        if (empty($row)) 
        {
            sawanna_error('[str]User not found[/str]');
            return false;
        }
        
        $check=md5('user-'.$row->id.'-'.$row->created.PRIVATE_KEY);
        
        if ($key!=$check)
        {
            sawanna_error('[str]Invalid key.[/str] [str]Haven`t recieve confirmation link ?[/str]');
            return false;
        }
        
        $this->sawanna->db->query("update pre_users set active='1' where id='$1'",$row->id);

        return true;
    }
    
    function recover_form()
    {
        if ($this->logged()) 
        {
            $recover_form=array(
                'login' => array(
                    'type' => 'html',
                    'title' => '[str]Logged in as[/str]:',
                    'value' => '<span class="login">'.$this->get_user_name().'</span>'
                  ),
                 'password' => array(
                    'type' => 'html',
                    'value' => $this->sawanna->constructor->link('user/logout','[str]Logout[/str]','logout')
                  )
            );
            
            return $this->sawanna->constructor->form('user-form',$recover_form,'CUser','user/login','user/recover','userLoginForm','CUser');
        } else 
        {
            $recover_form=array(
                'login' => array(
                    'type' => 'text',
                    'name' => 'login',
                    'title' => '[str]Login[/str]',
                    'description' => '[str]Enter your login[/str]',
                    'required' => true
                  ),
                 'email' => array(
                    'type' => 'email',
                    'name' => 'email',
                    'title' => '[str]E-Mail[/str]',
                    'description' => '[str]Enter your E-Mail address here[/str]',
                    'required' => true
                  ),
                  'captcha' => array(
                    'type' => 'captcha',
                    'name' => 'captcha',
                    'title' => '[str]CAPTCHA[/str]',
                    'required' => true
                  ),
                  'submit' => array(
                    'type' => 'submit',
                    'attributes' => array('value'=>'[str]Recover[/str]')
                   ),
                );
        }
        
        return $this->sawanna->constructor->form('user-form',$recover_form,'recover_process','user/login','user/recover','userRecover','CUser');
    }
    
    function recover_process()
    {
        if ($this->logged()) return false;
        
        $this->sawanna->db->query("select u.* from pre_users u left join pre_user_groups g on g.id=u.group_id where u.login='$1' and u.email='$2' and u.enabled<>'0' and u.active<>'0' and g.enabled<>'0'",$_POST['login'],$_POST['email']);
        $row=$this->sawanna->db->fetch_object();
        
        if (empty($row)) 
        {
            return array('[str]User not found[/str]',array('recover'));
        }
        
        $recovery_key=md5(random_chars(12).PRIVATE_KEY);
        
        $new_pwd_link=$this->sawanna->constructor->url('user/recover',true);
        $new_pwd_link=$this->sawanna->constructor->add_url_args($new_pwd_link,array('key'=>$recovery_key));
        
        $message=$this->sawanna->locale->get_string('Hello').' '.$row->login.' !'."\n";
        $message.=$this->sawanna->locale->get_string('This message sent to you, because password recovery was requested')."\n";
        $message.=$this->sawanna->locale->get_string('Please follow the link below to reset your password').': '.$new_pwd_link."\n\n";
        
        if ($this->sawanna->constructor->sendMail($row->email,$this->sawanna->config->send_mail_from,$this->sawanna->config->send_mail_from_name,$this->sawanna->locale->get_string('New password request confirmation'),$message))
        {
            $this->sawanna->session->set('user_pwd_recovery_key',$recovery_key);
            $this->sawanna->session->set('user_pwd_recovery_uid',$row->id);
            
            $event=array(
                'url' => 'user/recover',
                'caller' => 'CUser',
                'job' => 'do_recover_process',
                'constant' => 1
            );
            $this->sawanna->event->register($event);
            
            return array('[str]Password recovery instructions sent. Please check your E-Mail for details[/str]');
        } else 
        {
            return array('[str]Could not send your password recovery instructions[/str]',array('recover'));
        }

        
        return array('[str]Incorrect login or E-Mail address[/str]',array('login','email'));
    }
    
    function do_recover_process()
    {
        if ($this->logged()) return;
        
        $recovery_key=$this->sawanna->session->get('user_pwd_recovery_key');
        $uid=$this->sawanna->session->get('user_pwd_recovery_uid');
        
        if (!$recovery_key || !$uid) return ;
        
        $this->sawanna->db->query("select u.* from pre_users u left join pre_user_groups g on g.id=u.group_id where u.id='$1' and u.enabled<>'0' and u.active<>'0' and g.enabled<>'0'",$uid);
        $row=$this->sawanna->db->fetch_object();
        
        if (empty($row)) return;
        
        if (isset($_GET['key']) && $_GET['key']==$recovery_key)
        {
            $new_pwd=random_chars(12);
             
            $password=$this->sawanna->access->pwd_hash($new_pwd);
            
            $message=$this->sawanna->locale->get_string('Hello').' '.$row->login.' !'."\n";
            $message.=$this->sawanna->locale->get_string('This message sent to you, because password recovery was requested')."\n";
            $message.=$this->sawanna->locale->get_string('Your new password').': '.$new_pwd."\n\n";
            
            if ($this->sawanna->constructor->sendMail($row->email,$this->sawanna->config->send_mail_from,$this->sawanna->config->send_mail_from_name,$this->sawanna->locale->get_string('Your new password'),$message))
            {
                $this->sawanna->db->query("update pre_users set password='$1' where id='$2'",$password,$uid);
                
                $event=array(
                    'url' => 'user/recover',
                    'caller' => 'CUser',
                    'job' => 'do_recover_process',
                    'constant' => 1
                );
                $this->sawanna->event->remove($event);
                
                $event=array(
                    'url' => 'user/login',
                    'job' => 'sawanna_message',
                    'argument' => '[str]Your new password sent to your E-Mail address[/str]'
                );
                $this->sawanna->event->register($event);
            } else 
            {
                $event=array(
                    'url' => 'user/login',
                    'job' => 'sawanna_message',
                    'argument' => '[str]Could not send your new password[/str]'
                );
                $this->sawanna->event->register($event);
            }
            
            $this->sawanna->constructor->redirect('user/login');
        } elseif (isset($_GET['key']) && $_GET['key']!=$recovery_key)
        {
            sawanna_error('[str]Key invalid or expired[/str]');
        }
    }
    
    function login_form()
    {
        if ($this->logged())
        {
            $access_form=array(
                'login' => array(
                    'type' => 'html',
                    'title' => '[str]Logged in as[/str]:',
                    'value' => '<span class="login">'.$this->get_user_name().'</span>'
                  ),
                 'password' => array(
                    'type' => 'html',
                    'value' => $this->sawanna->constructor->link('user/logout','[str]Logout[/str]','logout')
                  )
            );
        } else
        {
            $access_form=array(
                'login' => array(
                    'type' => 'text',
                    'name' => 'login',
                    'title' => '[str]Login[/str]',
                    'required' => true
                  ),
                 'password' => array(
                    'type' => 'password',
                    'name' => 'password',
                    'title' => '[str]Password[/str]',
                    'required' => true
                  ),
                  'captcha' => array(
                    'type' => 'captcha',
                    'name' => 'captcha',
                    'title' => '[str]CAPTCHA[/str]',
                    'skip' => 3,
                    'required' => true
                  ),
                 'register' => array(
                    'type' => 'html',
                    'value' => $this->sawanna->constructor->link('user/register','[str]Create account[/str] &rsaquo;&rsaquo;')
                  ),
                  'recover' => array(
                    'type' => 'html',
                    'value' => $this->sawanna->constructor->link('user/recover','[str]Forgot password ?[/str]')
                  ),
                  'submit' => array(
                    'type' => 'submit',
                    'attributes' => array('value'=>'[str]Sign In[/str]')
                   ),
            );
        }
        
        if (!$this->sawanna->has_access('create user')) $access_form['register']=array('type'=>'html','value'=>'');
        
        return $this->sawanna->constructor->form('user-form',$access_form,'login_process','user','user/login','userLoginForm','CUser');
    }
    
    function login_process()
    {
        if ($this->logged()) return;
        
        if ($this->check_user($_POST['login'],$this->sawanna->access->pwd_hash($_POST['password'])))
        {
            $this->sawanna->session->set('user_login',$_POST['login']);
            $this->sawanna->session->set('user_password',$this->sawanna->access->pwd_hash($_POST['password']));
            
            $this->sawanna->db->query("delete from pre_user_activity where uid='0' and ip='$1' and ua='$2'",$this->sawanna->session->ip,$this->sawanna->session->ua);
            
            $event=array(
                'url'=>'user/logout',
                'caller'=>'CUser',
                'job'=>'logout_process'
            );
            $this->sawanna->event->register($event);
            
            // calling login hook
			$this->sawanna->process_hook('user_login');
                

            $message=$this->get_message('welcome',$this->sawanna->locale->current);
            if (!empty($message))
            {
                $vars=$this->get_message_variables('welcome',true);
            
                $vals=array(
                    '[login]' => $_POST['login'],
                );
                
                foreach ($vars as $var)
                {
                    $val=isset($vals[$var]) ? $vals[$var] : ' ';
                    $message->content=str_replace($var,$val,$message->content);
                }
                
                $message=$message->content;
				
                return array($message);
            } else 
            {
                return array('[str]Hello[/str] '.$_POST['login'].' !');
            }
        }
        
        $this->sawanna->db->query("select * from pre_users where login='$1'",$_POST['login']);
        $row=$this->sawanna->db->fetch_object();
        if (!empty($row) && $row->enabled && !$row->active)
        {
            return array('[str]Login failed[/str]. '.$this->sawanna->constructor->link('user/confirm','[str]Haven`t recieve confirmation link ?[/str]'),array('login','password'));
        }
        
        return array('[str]Login failed[/str]',array('login','password'));
    }
    
    function external_login($login,$password)
    {
        if ($this->logged()) return;
        
        if ($this->check_user($login,$this->sawanna->access->pwd_hash($password)))
        {
            $this->sawanna->session->set('user_login',$login);
            $this->sawanna->session->set('user_password',$this->sawanna->access->pwd_hash($password));
            
            $this->sawanna->db->query("delete from pre_user_activity where uid='0' and ip='$1' and ua='$2'",$this->sawanna->session->ip,$this->sawanna->session->ua);
            
            $event=array(
                'url'=>'user/logout',
                'caller'=>'CUser',
                'job'=>'logout_process'
            );
            $this->sawanna->event->register($event);
            
            return true;
        }
        
        return false;
    }
    
    function logout_process()
    {
        if ($this->logged())
        {
            $this->sawanna->session->clear('user_login');
            $this->sawanna->session->clear('user_password');
            
            $delta=60*5;
            $this->sawanna->db->query("update pre_user_activity set tstamp='$1' where uid='$2'",time()-$delta,$this->current->id);
            
            // calling logout hook
			$this->sawanna->process_hook('user_logout');
				
            $this->sawanna->constructor->redirect('user/login');
        }
    }
    
    function external_logout()
    {
        if ($this->logged())
        {
            $this->sawanna->session->clear('user_login');
            $this->sawanna->session->clear('user_password');
            
            $delta=60*5;
            $this->sawanna->db->query("update pre_user_activity set tstamp='$1' where uid='$2'",time()-$delta,$this->current->id);
            
        }
    }
    
    function register_activity($uid)
    {
        if (empty($uid)) $uid=0;
        
        $ip=$this->sawanna->session->ip;
        $ua=$this->sawanna->session->ua;
        $tstamp=time();
        
        if ($uid)
            $this->sawanna->db->query("delete from pre_user_activity where uid='$1'",$uid);
        else 
            $this->sawanna->db->query("delete from pre_user_activity where uid='$1' and ip='$2' and ua='$3'",$uid,$ip,$ua);
            
        $this->sawanna->db->query("insert into pre_user_activity (uid,ip,ua,tstamp) values ('$1','$2','$3','$4')",$uid,$ip,$ua,$tstamp);
    }
    
    function get_user_last_activity($uid)
    {
        if (empty($uid) || !is_numeric($uid)) return;
        
        $this->sawanna->db->query("select * from pre_user_activity where uid='$1' order by tstamp desc",$uid);
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return $row;
        else return false;
    }
    
    function panel()
    {
        if (!$this->logged()) 
        {
            
            $access_form=array(
                'switcher'=>array(
                    'type'=>'html',
                    'value' => '<a href="#">&rsaquo;&rsaquo;</a>'
                ),
                'login' => array(
                    'type' => 'text',
                    'name' => 'login',
                    'title' => '[str]Login[/str]',
                    'required' => true
                  ),
                 'password' => array(
                    'type' => 'password',
                    'name' => 'password',
                    'title' => '[str]Password[/str]',
                    'required' => true
                  ),
                  'captcha' => array(
                    'type' => 'captcha',
                    'name' => 'captcha',
                    'title' => '[str]CAPTCHA[/str]',
                    'skip' => 3,
                    'required' => true
                  ),
                 'register' => array(
                    'type' => 'html',
                    'value' => $this->sawanna->constructor->link('user/register','[str]Create account[/str] &rsaquo;&rsaquo;')
                  ),
                  'recover' => array(
                    'type' => 'html',
                    'value' => $this->sawanna->constructor->link('user/recover','[str]Forgot password ?[/str]')
                  ),
                  'submit' => array(
                    'type' => 'submit',
                    'attributes' => array('value'=>'[str]Sign In[/str]')
                   ),
            );
            
            if (!$this->sawanna->has_access('create user')) $access_form['register']=array('type'=>'html','value'=>'');
        
            return $this->sawanna->constructor->form('user-panel',$access_form,'login_process','user','user/login','userLoginPanel','CUser');
        }
        
        $panel=array();

        $panel['user']=$this->get_user_name();
        $panel['profile']=$this->sawanna->constructor->link('user','[str]My Profile[/str]');
        
        if (!$this->sawanna->access->check_su())
        {
            $panel['edit']=$this->sawanna->constructor->link('user/edit','[str]Edit Profile[/str]');
        }
        
        $panel['extra']='';
        if (!empty($this->panel) && is_array($this->panel))
        {
            foreach ($this->panel as $link)
            {
                $panel['extra'].='<div class="user-panel-extra">'.$link.'</div>';
            }
        }
        
        if ($this->sawanna->has_access('access admin area'))
        { 
            $panel['admin_area']=$this->sawanna->constructor->link($this->sawanna->config->admin_area,'[str]Administer[/str]');
        }
        
        $panel['logout']=$this->sawanna->constructor->link($this->sawanna->config->logout_area,'[str]Logout[/str]');
    
        return $panel;
    }
    
    function profile($id)
    {
        if ($this->sawanna->query!='user') return;
        if (!empty($id) && !is_numeric($id)) return;
        
        if (!empty($id) && ($user=$this->get_user($id))==false)
        {
            sawanna_error('[str]User not found[/str]');
            return ;
        }
        
        if (!isset($user) && !$this->logged()) 
        {
            $this->sawanna->constructor->redirect('user/login');
        }
        
        $profile=array();
        
        $profile['links']='';
        if (!empty($this->profile_links) && is_array($this->profile_links))
        {
            foreach ($this->profile_links as $link)
            {
                $profile['links'].='<div class="user-profile-link">'.$link.'</div>';
            }
        }
        
        if (!isset($user))
        {
            $profile['name']=$this->get_user_name();
            $profile['avatar']='<img src="'.$this->current->meta['avatar'].'" width="100" />';
            $profile['gender']=!empty($this->current->meta['gender']) ? '<label>[str]Gender[/str]:</label> '.$this->current->meta['gender'] : '';
            if (!$this->sawanna->access->check_su()) $profile['joined']='<label>[str]Registration date[/str]:</label> '.date($this->sawanna->config->date_format,$this->current->created); else $profile['joined']='';
            $profile['activity']='';
            $profile['extra']='';
            
            if ($this->sawanna->has_access('view users list'))
            {
                $profile['users']=$this->sawanna->constructor->link('users','','right-arrow',array('title'=>'[str]View other users profiles[/str]'));
            } else 
            {
                $profile['users']='';
            }
        } else 
        {
            $user_meta=$this->get_user_meta($user);
            $last=$this->get_user_last_activity($user->id);
                    
            $profile['name']=$user_meta['name'];
            $profile['avatar']='<img src="'.$user_meta['avatar'].'" width="100" />';
            $profile['gender']=!empty($user_meta['gender']) ? '<label>[str]Gender[/str]:</label> '.$user_meta['gender'] : '';
            $profile['joined']='<label>[str]Joined[/str]:</label> '.date($this->sawanna->config->date_format,$user->created);
            $profile['activity']=$last ? '<label>[str]Last activity[/str]:</label> '.date($this->sawanna->config->date_format,$last->tstamp) : '';
            $profile['extra']='';
            
            if ($this->sawanna->has_access('view users list'))
            {
                $profile['users']=$this->sawanna->constructor->link('users','','right-arrow',array('title'=>'[str]View other users profiles[/str]'));
            } else 
            {
                $profile['users']='';
            }
        }
        
        $co=0;
        $res=$this->sawanna->db->query("select f.name,f.type,f.meta,v.val from pre_user_extra_fields f left join pre_user_extra_field_values v on f.id=v.field_id where v.user_id='$1' order by f.weight",!isset($user) ? $this->current->id : $user->id);
        while($row=$this->sawanna->db->fetch_object($res))
        {
            $d=new CData();
            if (!empty($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            
            $field_name=!empty($row->meta) && isset($row->meta['name']) && isset($row->meta['name'][$this->sawanna->locale->current]) ? $row->meta['name'][$this->sawanna->locale->current] : $row->name;
            
            $field_val='';
            
            switch ($row->type)
            {
                case 'photo_100':
                    $field_val='<img src="'.SITE.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir).'/'.quotemeta($row->val).'" width="100" height="100" />';
                    break;
                case 'photo_200':
                    $field_val='<img src="'.SITE.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->files_dir).'/'.quotemeta($row->val).'" width="200" height="200" />';
                    break;
                case 'email':
                    $field_val=htmlspecialchars($row->val);
                    break;
                case 'date':
                    $field_val=date($this->sawanna->config->date_format,$row->val);
                    break; 
                case 'text':
                    $field_val=htmlspecialchars($row->val);
                    break;
                case 'textarea':
                    $field_val='<p>'.nl2br(htmlspecialchars($row->val)).'</p>';
                    break;
            }
            
            $profile['extra_'.$row->name.'_title']=htmlspecialchars($field_name);
            $profile['extra_'.$row->name]=$field_val;
            
            $mark=$co%2!==0 ? ' mark' : '';
            $profile['extra'].='<div class="user-extra-item user-extra-'.htmlspecialchars($row->name).$mark.'"><label>'.htmlspecialchars($field_name).': </label><div class="user-extra-val">'.($field_val).'</div></div>';
            $co++;
        }
        
        if (empty($profile['extra'])) $profile['extra']='[str]No more information available[/str]';
        
        return $profile;
    }
    
    function profile_edit_form()
    {
        if (!$this->logged()) 
        {
            $this->sawanna->constructor->redirect('user/login');
        } else 
        {
            return array('form'=>$this->user_form($this->current->id));
        }
    }
    
    function get_users_count()
    {
        $this->sawanna->db->query("select count(*) as count from pre_users u left join pre_user_groups g on g.id=u.group_id where u.enabled<>'0' and u.active<>'0' and g.enabled<>'0'");
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return $row->count;
        else return 0;
    }
    
    function user_list($start=0,$limit=5)
    {
        if (empty($start) || !is_numeric($start) || $start<0) $start=0;
        
        $html='';
        $co=0;
        
        $count=$this->get_users_count();
        
        $this->sawanna->db->query("select u.* from pre_users u left join pre_user_groups g on g.id=u.group_id where u.enabled<>'0' and u.active<>'0' and g.enabled<>'0' order by u.created desc limit $1 offset $2",$limit,floor($start*$limit));
        while($user=$this->sawanna->db->fetch_object())
        {
           if ($co%2===0)
           {
               $html.='<div class="users-list-item">';
           } else 
           {
               $html.='<div class="users-list-item users-list-mark">';
           }
           
           $meta=$this->get_user_meta($user);
           
           $gender=!empty($meta['gender']) ? '<label>[str]Gender[/str]: </label>'.$meta['gender'] : '';
           
           $html.='<div class="user-info-avatar"><img src="'.$meta['avatar'].'" width="100" alt="'.$meta['name'].'" /></div>';
           $html.='<div class="user-info-name">'.$meta['name'].'</div>';
           $html.='<div class="user-info-gender">'.$gender.'</div>';
          
           if ($this->sawanna->has_access('view user profile'))
           {
               $html.='<div class="user-profile-btn">'.$this->sawanna->constructor->link('user/'.$user->id,'[str]View profile[/str]','btn').'</div>';
           }
           
            $html.='</div><div class="users-list-sep"></div>';
            
            $co++;
        }
        
        $html.=$this->sawanna->constructor->pager($start,$limit,$count,'users');
        
        return $html;
    }
    
    function random_user($limit=1)
    {
        $count=$this->get_users_count();
        if ($count<=0) return ;
        
        $rand=mt_rand(0,$count-1);
        
        $this->sawanna->db->query("select u.* from pre_users u left join pre_user_groups g on g.id=u.group_id where u.enabled<>'0' and u.active<>'0' and g.enabled<>'0' order by u.created desc limit $1 offset $2",$limit,$rand);
        $user=$this->sawanna->db->fetch_object();
        
        if (!empty($user))
        {
            $random=array();
            $meta=$this->get_user_meta($user);
            
            $random['name']=$meta['name'];
            $random['avatar']='<img src="'.$meta['avatar'].'" width="100" alt="'.$meta['name'].'" />';
            $random['gender']=!empty($meta['gender']) ? '<label>[str]Gender[/str]: </label>'.$meta['gender'] : '';
            $random['profile']=$this->sawanna->constructor->link('user/'.$user->id,'[str]View profile[/str]');
            
            return $random;
        }
    }
    
    function online()
    {
        $delta=60*5;
        
        $online=array('users'=>'','guests'=>0);
        
        $this->sawanna->db->query("select u.id,u.login,a.ip,a.ua from pre_user_activity a left join pre_users u on u.id=a.uid where a.tstamp>'$1'",time()-$delta);
        while ($row=$this->sawanna->db->fetch_object())
        {
            if ($row->id) 
                $online['users'].='<div class="user-online">'.($this->sawanna->has_access('view user profile') ? $this->sawanna->constructor->link('user/'.$row->id,$row->login,'online',array('title'=>'IP: '.$row->ip.' UA: '.$row->ua)) : '<span title="IP: '.$row->ip.' UA: '.$row->ua.'">'.$row->login.'</span>').'</div>';
            else 
                $online['guests']++;
        }
        
        if (empty($online['users'])) $online['users']='[str]nobody[/str]';
        $online['guests']='<label>[str]Guests[/str]: </label>'.$online['guests'];
        
        return $online;
    }
    
    function ajaxer(&$scripts,&$outputs)
    {
		$scripts.='user_panel_init();'."\r\n";
	}
}
