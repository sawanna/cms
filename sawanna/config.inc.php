<?php
defined('SAWANNA') or die();

class CConf
{
    var $theme;
    var $language;
    var $languages;
    var $admin_area_language;
    var $clean_url;
    var $gzip_out;
    var $session_ip_bind;
    var $session_ua_bind;
    var $session_life_time;
    var $site_name;
    var $site_slogan;
    var $default_meta_title;
    var $default_meta_keywords;
    var $default_meta_description;
    var $site_logo;
    var $site_offline;
    var $site_offline_message;
    var $log_period;
    var $front_page_path;
    var $admin_area;
    var $login_area;
    var $logout_area;
    var $pwd_recover_area;
    var $login_auth_dest;
    var $logout_dest;
    var $logged_user_profile_url;
    var $logged_user_name;
    var $accept_language;
    var $trans_sid;
    var $cache;
    var $cache_css;
    var $su;
    var $send_mail_from;
    var $send_mail_from_name;
    var $date_format;
    var $path_extension;
    var $img_process;
    var $img_process_cache;
    var $img_process_width;
    var $img_process_height;
    var $img_process_small_width;
    var $img_process_small_height;
    var $img_process_big_width;
    var $img_process_big_height;
    var $img_process_crop;
    var $md5_notify;
    var $detect_mobile;
    var $captcha;
    var $admin_load_news;
    
    function init(&$db)
    { 
        if (!$this->load($db))
        {
            trigger_error('Could not load configuration',E_USER_ERROR);
            return false;
        }
        
        return true;
    }
    
    function load(&$db)
    {
        $vars=get_object_vars($this);
     
        if (is_object($db))
        {
            $res=$db->query("select * from pre_configuration");
            while ($row=$db->fetch_object($res))
            {
                if (array_key_exists($row->name,$vars))
                {
                    $field=$row->name;
                    $d=new CData();
                    $this->$field=$d->do_unserialize($row->value);
                } else trigger_error('Configuration variable '.htmlspecialchars($row->name).' not defined. Default value will be used',E_USER_NOTICE);
            }
        }
        
        $defaults=$this->defaults();
        $txt_vars=array('site_name','site_slogan','site_logo','site_offline_message','default_meta_title','default_meta_keywords','default_meta_description');
        
        foreach ($vars as $var=>$val)
        {
            if (empty($this->$var) && !is_numeric($this->$var) && isset($defaults[$var]))
            {
                if (in_array($var,$txt_vars)) continue;
                
                $this->$var=$defaults[$var];
            } elseif (empty($this->$var) && !is_numeric($this->$var)) return false;
        }
        
        return true;
    }
    
    function fix(&$locale,&$query,$is_mobile)
    {
        if (is_array($this->site_name))
        {
            if (isset($this->site_name[$locale->current])) $this->site_name=$this->site_name[$locale->current];
            else $this->site_name='';
        }
        
        if (is_array($this->site_slogan))
        {
            if (isset($this->site_slogan[$locale->current])) $this->site_slogan=$this->site_slogan[$locale->current];
            else $this->site_slogan='';
        }
        
        if (is_array($this->site_offline_message))
        {
            if (isset($this->site_offline_message[$locale->current])) $this->site_offline_message=$this->site_offline_message[$locale->current];
            else $this->site_offline_message='';
        }
        
        if (is_array($this->default_meta_title))
        {
            if (isset($this->default_meta_title[$locale->current])) $this->default_meta_title=$this->default_meta_title[$locale->current];
            else $this->default_meta_title='';
        }
        
        if (is_array($this->default_meta_keywords))
        {
            if (isset($this->default_meta_keywords[$locale->current])) $this->default_meta_keywords=$this->default_meta_keywords[$locale->current];
            else $this->default_meta_keywords='';
        }
        
        if (is_array($this->default_meta_description))
        {
            if (isset($this->default_meta_description[$locale->current])) $this->default_meta_description=$this->default_meta_description[$locale->current];
            else $this->default_meta_description='';
        }
        
        if (empty($query))
        {
            $query=trim($this->front_page_path);
        }
        
        if ($is_mobile)
        {
			$this->path_extension=false;
			$this->cache_css=false;
		}
    }
    
    function set($config,&$db)
    {
        if (!is_array($config) || empty($config)) return false;
        
        $vars=get_object_vars($this);
        $d=new CData();
        
        foreach ($config as $option=>$value)
        {
            if (array_key_exists($option,$vars))
            {
                $db->query("select id from pre_configuration where name='$1'",$option);
                $ex=$db->fetch_object();
                
                if (!empty($ex))
                {
                    if (!$db->query("update pre_configuration set value='$1' where name='$2'",$d->do_serialize($value),$option))
                        trigger_error('Cannot set configuration option '.htmlspecialchars($option).'.',E_USER_NOTICE);
                } else
                {
                    if (!$db->query("insert into pre_configuration (value,name) values('$1','$2')",$d->do_serialize($value),$option))
                        trigger_error('Cannot set configuration option '.htmlspecialchars($option).'.',E_USER_NOTICE);
                }
            } else trigger_error('Configuration variable '.htmlspecialchars($option).' not defined.',E_USER_NOTICE);
        }
        
        return true;
    }
    
    function schema()
    {
        $table=array(
            'name' => 'pre_configuration',
            'fields' => array(
                'id' => array(
                    'type' => 'id',
                    'length' => 10,
                    'not null' => true
                ),
                'name' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => true
                ),
                'value' => array(
                    'type' => 'longtext',
                    'not null' => true
                ),
                'mark' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => false
                )
            ),
            'primary key' => 'id',
            'unique' => 'name'
        );
        
        return $table;
    }
    
    function defaults()
    {
        $values=array(
            'theme' =>  'default',
            'language' => 'ru',
            'admin_area_language' => 'ru',
            'languages' => array('ru'=>'Русский','en'=>'English'),
            'clean_url' => false,
            'gzip_out' => false,
            'session_ip_bind' => false,
            'session_ua_bind' => false,
            'session_life_time' => 10,
            'site_name' => $_SERVER['HTTP_HOST'],
            'site_slogan' => 'Powered by Sawanna CMS',
            'default_meta_title' => '',
            'default_meta_keywords' => '',
            'default_meta_description' => '',
            'site_logo' => 'misc/logo.png',
            'site_offline' => false,
            'site_offline_message'=>'Sorry, this website currently offline',
            'log_period' => '1',
            'front_page_path' => '',
            'admin_area' => 'admin',
            'login_area' => 'login',
            'logout_area' => 'logout',
            'pwd_recover_area' => 'recover',
            'login_auth_dest' => 'admin',
            'logout_dest'=>'login',
            'accept_language' => 0,
            'trans_sid' => 0,
            'cache' => 0,
            'cache_css' => 0,
            'su' => array('user'=>'admin', 'password'=>'e4f8bb7d8986c5c5ffc0a6b6910b3f22', 'email'=>'info@'.$_SERVER['HTTP_HOST']), // su pwd hash generated with 123456 salt
            'logged_user_profile_url' => 'admin/user',
            'logged_user_name' => 'Administrator',
            'send_mail_from' => 'support@'.$_SERVER['HTTP_HOST'],
            'send_mail_from_name' => $_SERVER['HTTP_HOST'],
            'date_format' => 'd.m.Y',
            'path_extension' => 0,
            'img_process' => 0,
            'img_process_cache' => 0,
            'img_process_width' => 200,
            'img_process_height' => 150,
            'img_process_small_width' => 120,
            'img_process_small_height' => 90,
            'img_process_big_width' => 400,
            'img_process_big_height' => 300,
            'img_process_crop' => 0,
            'md5_notify' => 0,
            'detect_mobile' => 1,
            'captcha' => 1,
            'admin_load_news' => 1
        );
        
        return $values;
    }
    
    function install(&$db,$config=array())
    {
        
        if (!$db->create_table($this->schema())) return false;
        
        if (is_array($config) && !empty($config))
        {
            $defaults=$config;
        } else
        {
            $defaults=$this->defaults();
        }
        
        $d=new CData();
        $inserts=array();
        foreach($defaults as $name=>$value)
        {
            $inserts['name']=$name;
            $inserts['value']=$d->do_serialize($value);
            
            if (!$db->insert('pre_configuration',$inserts)) return false;
        }
        
        return true;
    }
    
    function uninstall(&$db)
    {
        if (!$db->query("DROP TABLE IF EXISTS pre_configuration")) return false;
        
        return true;
    }
}
