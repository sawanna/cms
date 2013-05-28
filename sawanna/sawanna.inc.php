<?php
defined('SAWANNA') or die();

class CSawanna
{
    public $config;
    public $db;
    public $tpl;
    public $locale;
    public $session;
    public $constructor;
    public $access;
    public $navigator;
    public $event;
    public $form;
    public $module;
    public $node;
    public $file;
    public $cache;
    public $query;
    public $arg;
    public $query_var;
    public $status;
    public $is_mobile;
    public $mobile_query_var;
	
	private $hooks;

    function CSawanna()
    {
        $this->arg='';
        $this->query_var='location';
        $this->mobile_query_var='mobile';
        $this->messages=array();
        $this->errors=array();
        $this->config=array();
        
        if (!defined('VERSION')) define('VERSION','3.4.5');
        if (!defined('SITE')) define('SITE',PROTOCOL.'://'.HOST);
        if (!defined('DEBUG_MODE')) define('DEBUG_MODE',0);
        
        define('REAL_PATH',realpath(ROOT_DIR));
        
        if (defined('TIMEZONE') && function_exists('date_default_timezone_set')) date_default_timezone_set(TIMEZONE);
        
        $this->status=500;
		$this->hooks=array();
    }
    
    function init()
    {
        $this->query=isset($_GET[$this->query_var]) ? urldecode(trim($_GET[$this->query_var],'/')) : '';
        
        //if (DEBUG_MODE && $this->query!='css' && $this->query!='image' && $this->query!='file' && $this->query!='ping') define('EXEC_TIME',1); // will brake some output like ajax, css, image etc.
        
        if (!$this->valid_inputs())
            return false;

        if (!$this->db_init() || !$this->load_conf() || !$this->locale_init())
            return false;

		$this->detect_mobile();
        $this->config_process();     
        $this->cache_init();
        $this->tpl_init();
        
        if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) 
            $this->tpl->html.='<!--Initialization start time: '.exec_time().'-->'."\n";
            
        $this->sess_init();
        if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) 
            $this->tpl->html.='<!--Session initialization time: '.exec_time().'-->'."\n";
            
        $this->log();
        if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) 
            $this->tpl->html.='<!--Log initialization time: '.exec_time().'-->'."\n";
            
        $this->check_cookies();
        $this->access_init();
        if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) 
            $this->tpl->html.='<!--Access initialization time: '.exec_time().'-->'."\n";
            
        $this->navigator_init();
        if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) 
            $this->tpl->html.='<!--Navigation initialization time: '.exec_time().'-->'."\n";
            
        $this->module_init();
        if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) 
            $this->tpl->html.='<!--Modules initialization time: '.exec_time().'-->'."\n";
            
        $this->files_init();
        if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) 
            $this->tpl->html.='<!--Files initialization time: '.exec_time().'-->'."\n";
        
        if (!$this->navigator_process()) return false;
        if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) 
            $this->tpl->html.='<!--Navigation processing time: '.exec_time().'-->'."\n";

        $this->module_process();
        if (!$this->navigator_access() && $this->query!=$this->config->admin_area) return false;
        
        if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) 
            $this->tpl->html.='<!--Modules processing time: '.exec_time().'-->'."\n";
        
        $this->events_init();
        $this->forms_init();
        $this->constructor_init();
        
        if ($this->status==403 &&  $this->query==$this->config->admin_area)
        {
            $this->constructor->redirect($this->config->login_area);
        }
            
        $this->form_process();
        $this->nodes_init();

        $this->ready();

        if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) 
            $this->tpl->html.='<!--Initialization end time: '.exec_time().'-->'."\n";

        if (!$this->online()) return false;
        return true;
    }

    function fix_php_options()
    {
        // disabling register globals
        if (ini_get('register_globals') && ini_set('register_globals',0)===false) 
            trigger_error('Could not disable register_globals option',E_USER_NOTICE);
        
        // disabling magic_quotes_gpc
        if (get_magic_quotes_gpc())
        {
            $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
            while (list($key, $val) = each($process)) {
                foreach ($val as $k => $v) {
                    unset($process[$key][$k]);
                    if (is_array($v)) {
                        $process[$key][stripslashes($k)] = $v;
                        $process[] = &$process[$key][stripslashes($k)];
                    } else {
                        $process[$key][stripslashes($k)] = stripslashes($v);
                    }
                }
            }
            unset($process);
        }
        
        /*
        $pcre_limit=(int) ini_get('pcre.backtrack_limit');
        if ($pcre_limit < 200000) {
            $pcre_set=ini_set('pcre.backtrack_limit',200000);
            if ($pcre_set===false) {
                trigger_error('Could not increase pcre.backtrack_limit option',E_USER_NOTICE);
            }
        }
        */
    }
    
    function db_init()
    {
        require_once(ROOT_DIR.'/sawanna/db.inc.php');
        
        switch (DB_TYPE)
        {
            case 'MySQL':
                include_once(ROOT_DIR.'/sawanna/mysql.inc.php');
                break;
            case 'SQLite':
                include_once(ROOT_DIR.'/sawanna/sqlite.inc.php');
                break;
            case 'PostgreSQL':
                include_once(ROOT_DIR.'/sawanna/pgsql.inc.php');
                break;
            default:
                trigger_error('Invalid database type.',E_USER_ERROR);
                return false;
        }
         
        $this->db=new CDB(DB_HOST,DB_PORT,DB_NAME,DB_USER,DB_PASS,DB_PREFIX);
        
        if (!$this->db->open()) return false;
        
        return true;
    }
    
    function load_conf()
    {
        require_once(ROOT_DIR.'/sawanna/config.inc.php');
     
        $this->config=new CConf();
        
        if (!$this->config->init($this->db)) return false;
        
        foreach($this->config as $var=>$val)
        {
            if (defined(strtoupper($var)))
            {
                $this->config->$var=constant(strtoupper($var));
            }
        }
      
        return true;
    }
    
    function tpl_init()
    {
        require_once(ROOT_DIR.'/sawanna/tpl.inc.php');
     
        $this->tpl=new CTemplate($this->config,$this->cache,$this->query_var,$this->is_mobile);

        return true;
    }
    
    function locale_init()
    {
        require_once(ROOT_DIR.'/sawanna/locale.inc.php');
     
        $this->locale=new CLocale($this->config);
        
        $this->locale->init($this->query);
        $this->locale->fix($this->query);
        
        return true;
    }
    
    function config_process()
    {
        if (preg_match('/^'.preg_quote($this->config->admin_area).'(\/.+)?$/',$this->query)) return true;
        // if (strpos($this->query,$this->config->admin_area)===0) return true;
        
        $this->config->fix($this->locale,$this->query,$this->is_mobile);
        
        return true;
    }
    
    function online()
    {
        if (preg_match('/^'.preg_quote($this->config->admin_area).'(\/.+)?$/',$this->query)  || $this->query==$this->config->login_area || $this->query==$this->config->logout_area || $this->query=='captcha' || $this->query=='css') return true;
        
        if ($this->has_access('access admin area')) return true;
        
        if ($this->config->site_offline)
        {
            trigger_error('Site is currently offline',E_USER_NOTICE);
            trigger_error($this->config->site_offline_message,E_USER_NOTICE);
            $this->status=0;
            return false;
        }
        
        return true;
    }
    
    function sess_init($open=true)
    {
        require_once(ROOT_DIR.'/sawanna/session.inc.php');
        
        $this->session=new CSession($this->db,$this->config);

        if ($open) $this->session->open();
    }
    
    function log()
    {
        include_once(ROOT_DIR.'/sawanna/log.inc.php');
        
        $log=new CLog($this->db,$this->config->log_period);
        $log->init($this->session);
        $log->add_record();
    }
    
    function check_cookies()
    {
        if (!isset($_COOKIE[$this->session->query_var]) && !$this->config->trans_sid)
        {
            $this->tpl->cookie_check_script();
        }
    }
    
    function get_location_by_url($full_url)
    {
        if (count($this->config->languages)<2)
        {
            $location=preg_replace('/^('.addcslashes(preg_quote(SITE),'/').')(\/(index.php)?(\?(.*&)?'.$this->query_var.'=([^&]*).*)?|([^&]*)(\/&.*)?)?(\/)?$/','$6$7',$full_url);
        } else
        {
            $lang_rgxp_1_arr=array();
            $lang_rgxp_2_arr=array();
            
            foreach ($this->config->languages as $lang=>$lname)
            {
                $lang_rgxp_1_arr[]=preg_quote($lang).'\/?';
                $lang_rgxp_2_arr[]='\/'.preg_quote($lang).'\/?';
            }
            $lang_rgxp_1=implode('|',$lang_rgxp_1_arr);
            $lang_rgxp_2=implode('|',$lang_rgxp_2_arr);
            
            $location=preg_replace('/^('.addcslashes(preg_quote(SITE),'/').')(\/(index.php)?(\?(.*&)?'.$this->query_var.'=('.$lang_rgxp_1.')?([^&]*))?.*|('.$lang_rgxp_2.')?([^&]*)(\/&.*)?)?(\/)?$/','$7$9',$full_url);
        }
        
        if (substr($location,0,1)=='/') $location=substr($location,1);
        if (substr($location,strlen($location)-1,1)=='/') $location=substr($location,0,strlen($location)-1);
        
        return $location;
    }
    
    function valid_inputs()
    {
        $search=array("'",'\bselect\b','\bunion\b','\bchar\b','\bhex\b','\bload_file\b','\boutfile\b','\\x00');
        
        $pattern='/'.implode('|',$search).'/i';
        $gpc=implode(' ',$_GET).' '.implode(' ',$_COOKIE);
        
        if (preg_match($pattern,$gpc,$matches))
        {
             trigger_error('Bad word or simbol detected: '.$matches[0],E_USER_ERROR);
             return false;
        }
        
        return true;
    }
    
    function access_init()
    {
        require_once(ROOT_DIR.'/sawanna/access.inc.php');
        
        $this->access=new CAccess($this->db,$this->session,$this->config);
        $this->access->init();
        
        return true;
    }
    
    function has_access($permission,$show_error=false)
    {
        if (!$this->access->has_access($permission))
        {
            if ($show_error) sawanna_error('[str]Permission denied[/str]');
            return false;
        }
        
        return true;
    }

    function navigator_init()
    {
        require_once(ROOT_DIR.'/sawanna/navigator.inc.php');
        
        $this->navigator=new CNavigator($this->db,$this->config,$this->access);
    }
    
    function navigator_process()
    {
        $this->status=$this->navigator->init($this->query,$this->arg);
        
        if ($this->status==200) 
        {
            $this->locale->fix($this->query);
            $this->module->fix($this->query);
            return true;
        }
     
         return false;
    }
    
    function navigator_access()
    {
        $this->status=$this->navigator->check_access();
        
        if ($this->status==200) 
        { 
            return true;
        }
        
        return false;
    }
    
    function module_init()
    {
        require_once(ROOT_DIR.'/sawanna/module.inc.php');
        
        $this->module=new CModule($this->db,$this->config,$this->locale,$this->tpl,$this->access,$this->navigator,$this->query,$this->is_mobile);
        $this->module->init();
        
        return true;
    }
    
    function module_process()
    {
        $this->module->argument_tokens['%path']=empty($this->arg) ? $this->query : $this->query.'/'.$this->arg;
        $this->module->argument_tokens['%query']=$this->query;
        $this->module->argument_tokens['%arg']=$this->arg;
        $this->module->argument_tokens['%language']=$this->locale->current;
        
        $this->module->include_modules($this->module->modules);
        $this->module->create_modules_objects($this->module->modules);
        $this->module->load_widgets();
        $this->module->create_widgets_objects($this->module->widgets);
        
        if ($this->has_access('access admin area')) $this->module->include_admin_handlers($this->module->modules);
        if ($this->has_access('access admin area')) $this->module->create_admin_handlers($this->module->modules);
        
        return true;
    }
    
    function module_exists($name)
    {
        foreach ($this->module->modules as $mid=>$module)
        {
            if ($module->name == $name)
            {
                return $module->enabled;
            }
        }
        
        return false;
    }
    
    function get_option($name)
    {
        if (empty($name)) return;
        
        return $this->module->get_option($name);
    }
    
    function set_option($name,$value,$module_id)
    {
        if (empty($name)) return;
        
        return $this->module->set_option($name,$value,$module_id);
    }
    
    function events_init()
    {
        require_once(ROOT_DIR.'/sawanna/event.inc.php');
        
        $this->event=new CEvent($this->db,$this->session);
        
        return true;
    }
    
     function forms_init()
    {
        require_once(ROOT_DIR.'/sawanna/form.inc.php');
        
        $this->form=new CForm($this->db,$this->config,$this->session,$this->event,$this->tpl,$this->locale,$this->module);

        return true;
    }
    
    function constructor_init()
    {
        $this->constructor=new CConstructor($this->locale,$this->session,$this->config,$this->form,$this->query_var,$this->mobile_query_var,$this->query);
        
        return true;
    }
    
    function form_process()
    {
        $this->form->init($this->constructor);
        
        return true;
    }
    
    function nodes_init()
    {
        require_once(ROOT_DIR.'/sawanna/node.inc.php');
        
        $this->node=new CNode($this->db,$this->access,$this->navigator,$this->locale,$this->query);
        
        return true;
    }
    
    function files_init()
    {
        require_once(ROOT_DIR.'/sawanna/file.inc.php');
     
        $this->file=new CFile($this->db);

        return true;
    }
    
    function cache_init()
    {
        require_once(ROOT_DIR.'/sawanna/cache.inc.php');
        
        $this->cache=new CCache($this->config);
    }
    
    function ready()
    {
        $this->fix_admin_area();
        
        $this->module->modules_ready($this->module->modules);
        
        return true;
    }
    
    function detect_mobile()
    {
		$this->is_mobile=false;

		if (isset($_GET[$this->mobile_query_var]))
		{
			$this->is_mobile=(bool) $_GET[$this->mobile_query_var];
			
			setcookie($this->mobile_query_var,($_GET[$this->mobile_query_var] ? '1' : '0'));
		} elseif(isset($_COOKIE[$this->mobile_query_var]))
		{
			$this->is_mobile=(bool) $_COOKIE[$this->mobile_query_var];
		} elseif($this->config->detect_mobile)
		{
			include_once(ROOT_DIR.'/sawanna/mobile.inc.php');
			
			$m=new Mobile_Detect();
			$this->is_mobile=$m->isMobile();
		}
		
		if (isset($_GET[$this->mobile_query_var]))
		{
			unset($_GET[$this->mobile_query_var]);
		}
		
		return $this->is_mobile;
	}
	
	function register_hook($name,$method,$args=null,$class='') {
		
		if (!isset($this->hooks[$name])) $this->hooks[$name]=array();
		
		// we don't let to override existing hooks
		if (array_key_exists($method.'_'.$class.'_hook',$this->hooks[$name])) {
			trigger_error('Hook '.$method.'_'.$class.'_hook already registered',E_USER_WARNING);
			return false;
		}
		
		$this->hooks[$name][$method.'_'.$class.'_hook']=array(
			'method' => $method,
			'class' => $class,
			'args' => $args
		);
		
		return true;
	}
	
	function process_hook($name) {
	
		if (!isset($this->hooks[$name]) || !is_array($this->hooks[$name])) return;
		
		foreach ($this->hooks[$name] as $hook) {
			
			if (empty($hook['method'])) continue;
			if (empty($hook['class']) && !function_exists($hook['method'])) continue;
			
			if (!isset($hook['args'])) $hook['args']=null;
			
			if (empty($hook['class'])) {
				$f=$hook['method'];
				$f($hook['args']);
				continue;
			}
			
			$name=$this->module->get_widget_object_name($hook['class']);
			
			if (!isset($GLOBALS[$name])) {
				$GLOBALS[$name]=new $hook['class']($this);
			}
			
			if (isset($GLOBALS[$name])) {
				if (!is_object($GLOBALS[$name]) || !method_exists($GLOBALS[$name],$hook['method'])) continue;
				
				$GLOBALS[$name]->{$hook['method']}($hook['args']);
				continue;
			}
		}
	}
    
    function process_events($exclude=array())
    {
        $callers=$this->event->get_callers($this->query);
        
        foreach($callers as $class=>$event)
        {
			if (!empty($exclude) && is_array($exclude))
			{
				$continue=false;
				
				foreach($exclude as $ek=>$ev)
				{
					if (isset($event->{$ek}) && $event->{$ek}==$ev)
					{
						$continue=true;
						break;
					}
				}
				
				if ($continue) continue;
			}
			
            if (!$event->constant) $this->event->remove(get_object_vars($event));
            
            if (empty($event->job)) continue;

            if (!empty($class))
            { 
                $obj=$this->module->get_widget_object_name($class);
                if (!isset($GLOBALS[$obj]) && class_exists($class)) $GLOBALS[$obj]=new $class($this); 
                if (isset($GLOBALS[$obj]) && is_object($GLOBALS[$obj]) && method_exists($GLOBALS[$obj],$event->job))
                    $GLOBALS[$obj]->{$event->job}($event->argument);
            } elseif (empty($class) && function_exists($event->job))
            {
                $f=$event->job;
                $f($event->argument);
            }
        }
    }
    
    function process_forms($return=false)
    {
        $fid=0;
        if (isset($_GET['fid'])) $fid=$_GET['fid'];
        if (isset($_POST['fid'])) $fid=$_POST['fid'];
        
        if (!empty($fid))
        {
            if (!$this->form->validate($fid))
            {
                $error=$this->form->get_message();
                if (!empty($error)) sawanna_error($error);
            } else
            {
                $redirect='';
                $form=$this->form->process_prepare($fid);
                if (!$form) return ;
                
                if (isset($form['callback']))
                {
                    $result='';
                    if (!empty($form['caller']))
                    {
                        $obj=$this->module->get_widget_object_name($form['caller']);
                        if (!isset($GLOBALS[$obj]) && class_exists($form['caller'])) $GLOBALS[$obj]=new $form['caller']($this); 
                        if (isset($GLOBALS[$obj]) && is_object($GLOBALS[$obj]) && method_exists($GLOBALS[$obj],$form['callback']))
                            $result=$GLOBALS[$obj]->{$form['callback']}();
                    } elseif (empty($form['caller']) && function_exists($form['callback']))
                        $result=$form['callback']();
                       
                    if (!empty($result) && is_array($result))
                    {
                        if ($this->form->process_callback($result,$form))
                            $this->form->process_clear($fid);
                    } else 
                    {
                        $this->form->process_clear($fid);
                    }
                } else 
                {
                    $this->form->process_clear($fid);
                }
               
                if (!empty($form['redirect']))
                {
					if (!$return)
						$this->constructor->redirect($form['redirect']);
					else
						return $form['redirect'];
				}
            }
        }
    }
    
    function process_captcha($noice=true)
    {
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: ".date('r',time()-3600)); 
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Content-Type: image/png");

        $range=array_merge(range('a','z'),range(0,9));
        //$code=(String)mt_rand(10000,99999);
        $code='';
        do {
            $code.=$range[mt_rand(0,count($range)-1)];
        } while (strlen($code)<5);
        
        $captcha=imagecreatetruecolor(150,40);
        $bg=imagecolorallocate($captcha,mt_rand(200, 250), mt_rand(200,250), mt_rand(200,250));
        imagefill($captcha, 0, 0, $bg);
        
        
 
        for ($i=0;$i<strlen($code);$i++)
        {
           if (function_exists('imagettftext'))
           {
               putenv('GDFONTPATH='.ROOT_DIR.'/misc');
               $size=mt_rand(22,36);
               $angle=mt_rand(-35,35);
               $res=imagettftext($captcha,$size,$angle,($i+1)*22+1,31,imagecolorallocate($captcha, mt_rand(0,100), mt_rand(0,100), mt_rand(0,100)),'FreeSerifBold',$code[$i]);
               if ($res) imagettftext($captcha,$size,$angle,($i+1)*22,30,$bg,'FreeSerifBold',$code[$i]);
               else imagestring($captcha,5,($i+1)*22,12,$code[$i],imagecolorallocate($captcha, mt_rand(0,100), mt_rand(0,100), mt_rand(0,100)));
           } else
               imagestring($captcha,5,($i+1)*22,12,$code[$i],imagecolorallocate($captcha, mt_rand(0,100), mt_rand(0,100), mt_rand(0,100)));
        }
        
        if ($noice)
        {
			$st=mt_rand(2,4);
            for ($i=0;$i<$st;$i++)
            {
                imageline($captcha,0,mt_rand(0,40),150,mt_rand(0,40),imagecolorallocate($captcha, mt_rand(200,250), mt_rand(200,250), mt_rand(200,250)));
            }
            for ($i=0;$i<$st;$i++)
            {
                imageline($captcha,mt_rand(0,150),0,mt_rand(0,150),40,imagecolorallocate($captcha, mt_rand(200,250), mt_rand(200,250), mt_rand(200,250)));
            }
            
            //imagefilter($captcha,IMG_FILTER_GAUSSIAN_BLUR);
        }

        $this->session->set('captcha',$code);
        
        if (function_exists('imagefilter')) imagefilter($captcha,IMG_FILTER_GAUSSIAN_BLUR);
        
        imagepng($captcha);
        imagedestroy($captcha);
        exit();
    }

    function process()
    {
        if (!$this->navigator->current->render) return false;
        
        if (!$this->tpl->init($this->query,$this->locale->current,$this->arg)) 
        {
            if ($this->config->theme==$this->tpl->default_theme) return false;
            else
            {
                $current_theme=$this->config->theme;
                $this->config->theme=$this->tpl->default_theme;
                if ($this->tpl->init($this->query,$this->locale->current))
                {
                    trigger_error('Failed to initialize '.htmlspecialchars($current_theme).' theme. Using default theme',E_USER_WARNING);
                } else return false;
            }
        }
        
        $widget_lang='all';
        $widget_name='css';
        $widget_class='CSawanna';
        $widget_handler='css';
        $widget_param='modules';
        
        if ($this->query!=$this->config->admin_area && ($this->cache->get($this->cache->cache_name($widget_lang,$this->config->theme,$widget_name,$widget_class,$widget_handler,$widget_param),'css') || function_exists('theme_style')))
        {
            $css_update=$this->get_option('css_update');
            $css_version=$this->get_option('css_version');
            
            if (!defined('BASE_PATH'))
				$this->tpl->add_link('<link rel="stylesheet" type="text/css" href="'.SITE.'/index.php?'.$this->query_var.'=css'.($css_update ? '&t='.$css_update : '').($css_version ? '&v='.$css_version : '').($this->is_mobile ? '&'.$this->mobile_query_var.'=1' : '').'" />');
			else
				$this->tpl->add_link('<link rel="stylesheet" type="text/css" href="'.BASE_PATH.'/index.php?'.$this->query_var.'=css'.($css_update ? '&t='.$css_update : '').($css_version ? '&v='.$css_version : '').($this->is_mobile ? '&'.$this->mobile_query_var.'=1' : '').'" />');
            //$this->tpl->add_link('<link rel="stylesheet" type="text/css" href="'.$this->constructor->url('css').'" />');
        }
        
        if ($this->query==$this->config->admin_area || !$this->cache->get($this->cache->cache_name($widget_lang,$this->config->theme,$widget_name,$widget_class,$widget_handler,$widget_param),'css'))
        {
            $css_output=$this->module->add_modules_css($this->module->modules);
        
            if (!empty($css_output)) $this->cache->set($this->cache->cache_name($widget_lang,$this->config->theme,$widget_name,$widget_class,$widget_handler,$widget_param),$css_output,'css');  
        }
        
        $components=array();
        
        if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) 
            $this->tpl->html.='<!--Widget execution preparation time: '.exec_time().'-->'."\n";
         
        if (!empty($this->tpl->components) && is_array($this->tpl->components))
        {
            foreach($this->tpl->components as $component)
            {
                $this->module->argument_tokens['%component']=$component;

                $components[$component]='';
                $this->process_components($component,$components);  
            }
        }
        
        $output=ob_get_contents();
        if (empty($output))
        {
            $this->tpl->process($components);
            $this->locale->translate($this->tpl->html);
            $this->locale->clear();
            $output=$this->tpl->html;
            $this->tpl->clear();

            return $output;
        }
    }
    
    function process_components($component,&$components)
    {
        $widgets=$this->module->get_component_widgets($component);
        if (!empty($widgets) && is_array($widgets))
        {
            foreach ($widgets as $widget)
            {
                $argument=(array_key_exists($widget->argument,$this->module->argument_tokens)) ? $this->module->argument_tokens[$widget->argument] : $widget->argument;
                
                if ($widget->cacheable && ($cache=$this->cache->get($this->cache->cache_name($this->locale->current,$this->config->theme,$widget->name,$widget->class,$widget->handler,$argument),$widget->module ? $this->module->modules[$widget->module]->name : 'system'))!=false)
                {
                    if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) 
                        $cache.='<!--'.$widget->name.' cache loading time: '.exec_time().'-->'."\n";
                
                    $components[$component].=$cache;
                } else 
                {
                    $widget_output=$this->module->exec_widget($widget);
                    if ($widget->cacheable && !empty($widget_output) && !$this->db->last_error())
                    {
                        $this->cache->set($this->cache->cache_name($this->locale->current,$this->config->theme,$widget->name,$widget->class,$widget->handler,$argument),$widget_output,$widget->module ? $this->module->modules[$widget->module]->name : 'system');
                    }
                    
                    if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) 
                        $widget_output.='<!--'.$widget->name.' execution time: '.exec_time().'-->'."\n";
                    
                    $components[$component].=$widget_output;
                    unset($widget_output);
                }
            }
        }
    }
    
    function output(&$html)
    {
        if (empty($html) || $this->query==$this->config->admin_area) return;

        header('Content-Type: text/html; charset=utf-8'); 
        
        if (in_array($this->query,array($this->config->login_area,$this->config->logout_area)))
        {
            header("Cache-Control: no-cache, must-revalidate");
            header("Expires: ".date('r',time()-3600)); 
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        }
        
        echo $html;
        
        if ($this->config->gzip_out && function_exists('gzencode') && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)
        {
            
            header("Content-Encoding: gzip");  
            $output=ob_get_contents();
            ob_end_clean();

            ob_start('ob_gzhandler');
            echo $output;
        }
    }
    
    function css($argument)
    {
        $css_output='';
        
        $widget_lang='all';
        $widget_name='css';
        $widget_class='CSawanna';
        $widget_handler='css';
        $widget_param='modules';
        
        $css_output=$this->cache->get($this->cache->cache_name($widget_lang,$this->config->theme,$widget_name,$widget_class,$widget_handler,$widget_param),'css');
        if (!$css_output)
        { 
            // if we're here, it means that something is wrong
            $css_output.='/* WARNING: css cache not found */'."\n";
        }
        
        if (function_exists('theme_style'))
        {
            $css_output.="\n\n/* Theme configurable styles */\n".theme_style($this->is_mobile);
        }
        
        header("Cache-Control: public");
        header("Expires: ".date('r',time()+3600*24)); 
        header('Content-Type: text/css; charset=utf-8');
        echo $css_output;
        
        if ($this->config->gzip_out && function_exists('gzencode') && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)
        {
               header("Content-Encoding: gzip");  
               $css_output=ob_get_contents();
               ob_end_clean();
               
               ob_start('ob_gzhandler');
               echo $css_output;
        }
        
        die();
    }
    
    function file()
    {
        if (!empty($this->arg))
        {
            if ($this->file->fetch_file($this->arg)) 
            {
                $this->file->count($this->arg);
                die();
            }
            else sawanna_error('[str]File not found[/str]');
        }
    }
    
    function image()
    {
        if (!empty($this->arg))
        {
            $argument=$this->arg;
            $dst_width=$this->config->img_process_width ? $this->config->img_process_width : 200;
            $dst_height=$this->config->img_process_height ? $this->config->img_process_height : 150;
            
            if (preg_match('/(.*?)\/(small|normal|big|sys)$/',$this->arg,$matches))
            {
                $this->arg=$matches[1];

                switch ($matches[2])
                {
                    case 'small':
                        $dst_width=$this->config->img_process_small_width ? $this->config->img_process_small_width : 120;
                        $dst_height=$this->config->img_process_small_height ? $this->config->img_process_small_height : 90;
                        break;
                    case 'normal':
                        $dst_width=$this->config->img_process_width ? $this->config->img_process_width : 200;
                        $dst_height=$this->config->img_process_height ? $this->config->img_process_height : 150;
                        break;
                    case 'big':
                        $dst_width=$this->config->img_process_big_width ? $this->config->img_process_big_width : 400;
                        $dst_height=$this->config->img_process_big_height ? $this->config->img_process_big_height : 300;
                        break;
                    case 'sys':
                        $dst_width=120;
                        $dst_height=90;
                        break;
                    default:
                        $dst_width=$this->config->img_process_width ? $this->config->img_process_width : 200;
                        $dst_height=$this->config->img_process_height ? $this->config->img_process_height : 150;
                        break;
                }
                
                if ($matches[2]!='sys' && !$this->config->img_process) return false; 
                if ($matches[2]=='sys' && !$this->has_access('access admin area')) return false;
            } elseif (!$this->config->img_process) return false;
            
            if (is_numeric($this->arg) && !$this->has_access('access admin area')) 
            {
                sawanna_error('[str]Permission denied[/str]');
                return false;
            }
    
            $file=$this->file->get($this->arg);
            if (!$file) 
            {
                sawanna_error('[str]File not found[/str]');
                return false;
            }
            
            header("Cache-Control: public");
            header("Expires: ".date('r',time()+3600*24*30));
                
            $image_cache=$this->cache->get($this->cache->cache_name('none','default','image','CSawanna','imagecache',$argument),'image');
            
            if (!$image_cache)
            {
                $file_path=!empty($file->path) ? ROOT_DIR.'/'.quotemeta($this->file->root_dir).'/'.quotemeta($file->path).'/'.$file->name : ROOT_DIR.'/'.quotemeta($this->file->root_dir).'/'.$file->name;
                if (!file_exists($file_path)) 
                {
                    sawanna_error('[str]File not found[/str]');
                    return false;
                }
    
                if (!$this->constructor->resize_image($file_path,null,$dst_width,$dst_height,$this->config->img_process_crop,false))
                {
                    $this->constructor->resize_image(ROOT_DIR.'/misc/document.png',null,$dst_width,$dst_height,false,false);
                    header('Content-type: image/png');
                    die();
                }
                
                $img_output=ob_get_contents();
                $this->cache->set($this->cache->cache_name('none','default','image','CSawanna','imagecache',$argument),$img_output,'image');
            } else 
            {
                echo $image_cache;
            }
            
            header('Content-type: '.$file->type);
            $this->file->count($this->arg);
            die();
        }
    }
    
    function fix_admin_area()
    {
        if ($this->has_access('access admin area'))
        {
            if (preg_match('/^'.preg_quote($this->config->admin_area).'(\/.+)?$/',$this->query))
                $this->register_admin_area();
        } else 
        {
                $this->unregister_admin_area();
        }
    }
    
    function register_admin_area()
    {
        $event=array(
                'url' => $this->config->admin_area,
                'caller' => 'CSawanna',
                'job' => 'process_admin_area',
                'constant' => 1,
                'weight' => 9999
            );
            
        if (!$this->event->event_exists($event)) $this->event->register($event);
    }
    
    function unregister_admin_area()
    {
        $event=array(
                'url' => $this->config->admin_area,
                'caller' => 'CSawanna',
                'job' => 'process_admin_area',
                'constant' => 1,
                'weight' => 9999
            );
            
        if ($this->event->event_exists($event)) $this->event->remove($event);
    }
    
    function su_login_form()
    {
        $this->tpl->default_title='[str]Super-User authorization[/str]';
        
        if (!$this->access->check_su())
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
                  'recover' => array(
                    'type' => 'html',
                    'value' => $this->constructor->link($this->config->pwd_recover_area,'[str]Forgot password ?[/str]')
                  ),
                  'submit' => array(
                    'type' => 'submit',
                    'attributes' => array('value'=>'[str]Sign In[/str]')
                   ),
            );
        } else 
        {
            $access_form=array(
                'login' => array(
                    'type' => 'html',
                    'title' => '[str]Logged in as[/str]:',
                    'value' => '<span class="login">'.$this->session->get('su_user').'</span>'
                  ),
                 'password' => array(
                    'type' => 'html',
                    'value' => $this->constructor->link($this->config->logout_area,'[str]Logout[/str]','logout')
                  )
            );
        }
        
        return $this->constructor->form('signin',$access_form,'su_login_process',$this->config->admin_area,'','login','CSawanna');
    }
    
    function su_login_process()
    {
        if ($this->access->check_su()) return array('[strf]Welcome %'.$this->session->get('su_user').'% ![/strf]');
        
        if ($_POST['login']==$this->config->su['user'] && $this->access->pwd_hash($_POST['password'])===$this->config->su['password'])
        {
            $this->session->set('su_user',$_POST['login']);
            $this->session->set('su_password',$this->access->pwd_hash($_POST['password']));
            
            $event=array(
                'url'=>$this->config->logout_area,
                'caller'=>'CSawanna',
                'job'=>'su_logout_process'
            );
            $this->event->register($event);

            $this->locale->current = $this->config->admin_area_language;
            
            return array('[strf]Hello %'.$_POST['login'].'% ![/strf]');
        }
        
        return array('[str]Login failed[/str]',array('login','password'));
    }
    
    function su_logout_process()
    {
        if ($this->access->check_su())
        {
            $this->session->clear('su_user');
            $this->session->clear('su_password');
            
            $this->unregister_admin_area();
            
            $this->constructor->redirect($this->config->logout_dest);
        }
    }
    
    function su_pwd_recover_form()
    {
        $this->tpl->default_title='[str]Super-User password recovery[/str]';
        
        if (!$this->access->check_su())
        {
            $recover_form=array(
                'login' => array(
                    'type' => 'text',
                    'name' => 'login',
                    'title' => '[str]Login[/str]',
                    'required' => true
                  ),
                 'email' => array(
                    'type' => 'email',
                    'name' => 'email',
                    'title' => '[str]E-Mail[/str]',
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
        } else 
        {
            $recover_form=array(
                'login' => array(
                    'type' => 'html',
                    'title' => '[str]Logged in as[/str]:',
                    'value' => '<span class="login">'.$this->session->get('su_user').'</span>'
                  ),
                 'email' => array(
                    'type' => 'html',
                    'value' => $this->constructor->link($this->config->logout_area,'[str]Logout[/str]','logout')
                  )
            );
        }
        
        return $this->constructor->form('recover',$recover_form,'su_pwd_recover_process',$this->config->login_area,'','recover','CSawanna');
    }
    
    function su_pwd_recover_process()
    {
        if ($this->access->check_su()) return array('[strf]Welcome %'.$this->session->get('su_user').'% ![/strf]');
        
        if ($_POST['login']==$this->config->su['user'] && $_POST['email']==$this->config->su['email'])
        {
            $recovery_key=md5(random_chars(12).PRIVATE_KEY);
            
            $new_pwd_link=$this->constructor->url($this->config->pwd_recover_area,true);
            $new_pwd_link=$this->constructor->add_url_args($new_pwd_link,array('key'=>$recovery_key));
            
            $message=$this->locale->get_string('Hello').' '.$this->config->su['user'].' !'."\n";
            $message.=$this->locale->get_string('This message sent to you, because password recovery was requested')."\n";
            $message.=$this->locale->get_string('Please follow the link below to reset your password').': '.$new_pwd_link."\n\n";
            
            if ($this->constructor->sendMail($this->config->su['email'],$this->config->send_mail_from,$this->config->send_mail_from_name,$this->locale->get_string('New password request confirmation'),$message))
            {
                $this->session->set('pwd_recovery_key',$recovery_key);
                $event=array(
                    'url' => $this->config->pwd_recover_area,
                    'caller' => 'CSawanna',
                    'job' => 'su_do_pwd_recover_process',
                    'constant' => 1
                );
                $this->event->register($event);
                
                return array('[str]Password recovery instructions sent. Please check your E-Mail for details[/str]');
            } else 
            {
                return array('[str]Could not send your password recovery instructions[/str]',array('recover'));
            }
        }
        
        return array('[str]Incorrect login or E-Mail address[/str]',array('login','email'));
    }
    
    function su_do_pwd_recover_process()
    {
        if ($this->access->check_su()) return;
        $recovery_key=$this->session->get('pwd_recovery_key');
        if (!$recovery_key) return ;
        
        if (isset($_GET['key']) && $_GET['key']==$recovery_key)
        {
            $new_pwd=random_chars(12);
            
            $su=$this->config->su;    
            $su['password']=$this->access->pwd_hash($new_pwd);
            
            $message=$this->locale->get_string('Hello').' '.$this->config->su['user'].' !'."\n";
            $message.=$this->locale->get_string('This message sent to you, because password recovery was requested')."\n";
            $message.=$this->locale->get_string('Your new password').': '.$new_pwd."\n\n";
            
            if ($this->constructor->sendMail($this->config->su['email'],$this->config->send_mail_from,$this->config->send_mail_from_name,$this->locale->get_string('Your new password'),$message))
            {
                $this->config->set(array('su'=>$su),$this->db);
                $event=array(
                    'url' => $this->config->pwd_recover_area,
                    'caller' => 'CSawanna',
                    'job' => 'su_do_pwd_recover_process',
                    'constant' => 1
                );
                $this->event->remove($event);
                
                $event=array(
                    'url' => $this->config->login_area,
                    'job' => 'sawanna_message',
                    'argument' => '[str]Your new password sent to your E-Mail address[/str]'
                );
                $this->event->register($event);
            } else 
            {
                $event=array(
                    'url' => $this->config->login_area,
                    'job' => 'sawanna_message',
                    'argument' => '[str]Could not send your new password[/str]'
                );
                $this->event->register($event);
            }
            
            $this->constructor->redirect($this->config->login_area);
        } elseif (isset($_GET['key']) && $_GET['key']!=$recovery_key)
        {
            sawanna_error('[str]Key invalid or expired[/str]');
        }
    }
    
    function admin_area_init(&$object)
    {
        include_once(ROOT_DIR.'/sawanna/admin.inc.php');
        
        $object=new CAdminArea($this->db,$this->config,$this->session,$this->locale,$this->access,$this->form,$this->module,$this->event,$this->navigator,$this->tpl,$this->constructor,$this->cache,$this->file,$this->arg);
        
        return true;
    }
    
    function process_admin_area()
    {
        $admin=null;
        $this->admin_area_init($admin);
        $admin->process();
    }
    
    function process_admin_area_requests()
    {
        $admin=null;
        $this->admin_area_init($admin);
        
        return $admin->process_requests();
    }

    function cron($print=false)
    {
        $render=!empty($_GET['render']) ? 1 : 0;
        
        $out='Cron jobs started...';
        $out.="\n";
        
        $this->session->cron();
        $out.='Sessions garbage cleared.';
        $out.="\n";
        
        include_once(REAL_PATH.'/sawanna/log.inc.php');
        $log=new CLog($this->db,$this->config->log_period);
        $log->cron();
        $out.='Logs garbage cleared.';
        $out.="\n";
        
        $this->event->cron();
        $out.='Events garbage cleared.';
        $out.="\n";
        
        $this->form->cron();
        $out.='Forms garbage cleared.';
        $out.="\n";
        
        if (empty_errors_log())
        {
            $out.='Errors log garbage cleared.';
            $out.="\n";
        } else 
        {
            trigger_error('Could not delete errors log garbage',E_USER_NOTICE);
            $out.='Error. Could not delete errors log garbage.';
            $out.="\n";
        }
        
        // disabled
        if (false && $this->config->md5_notify) {
			if (!file_exists(ROOT_DIR.'/'.$this->file->md5file))
			{
				if ($this->file->md5sum(ROOT_DIR))
				{
					$out.='MD5 sum calculated.';
					$out.="\n";
				} else 
				{
					trigger_error('Could not calculate MD5 sum',E_USER_NOTICE);
					$out.='Error. Could not calculate MD5 sum';
					$out.="\n";
				}
			} else 
			{
				$out.='Checking MD5 sum ...';
				$out.="\n";
				
				$errors=array();
				if ($this->file->check_md5sum(ROOT_DIR,$errors))
				{
					$out.='MD5 sum checked successfully.';
					$out.="\n";
				} else 
				{
					trigger_error('Modified files found',E_USER_WARNING);
					$out.='Warning: Modified files found';
					$out.="\n";
					
					if ($this->config->md5_notify)
					{
						$message=$this->locale->get_string('Hello').' '.$this->config->su['user'].' !'."\n\n";
						$message.=$this->locale->get_string('Following modified files found during last MD5 sum check:')."\n\n";
						foreach ($errors as $filename=>$hash)
						{
							$message.='      '.$filename."\n";
						}
						$message.="\n\n".SITE;
						
						if ($this->constructor->sendMail($this->config->su['email'],$this->config->send_mail_from,$this->config->send_mail_from_name,$this->locale->get_string('Warning: Modified files found'),$message))
							$out.='Notification sent to the site administrator'."\n";
					}
				}
			}
		}
		
        $out.='Finished.';
        
        if ($render) return '<pre>'."\n".$out."\n".'</pre>'."\n";
        elseif ($print) echo "\n".$out."\n";
    }
        
    function clear()
    {
        // Do not unset sawanna properties
        
        // checking weather the core cron jobs are executed
        if (is_object($this->db))
        {
            $this->db->query('select tstamp from pre_session order by tstamp limit 1');
            $row=$this->db->fetch_object();
            if (!empty($row) && $row->tstamp < time()-3600*24*7)
            {
                trigger_error('Cron jobs not executed for a long time. Core cron jobs execution forced. Modules` cron tasks still not executed!',E_USER_WARNING);
                $this->cron();
            }
            
            if (is_object($this->session)) $this->session->close();
            $this->db->close();
        }
    }
}
