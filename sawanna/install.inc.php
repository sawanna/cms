<?php
defined('SAWANNA') or die();

require_once(ROOT_DIR.'/sawanna/sawanna.inc.php');

class CSawannaInstaller extends CSawanna 
{
    function init()
    {
        $this->query='';
        
        $this->db_init();
        $this->load_conf();
        $this->locale_init();     
        $this->config_process();
        $this->cache_init();
        $this->tpl_init();
        $this->sess_init(false);
        $this->log();
        $this->check_cookies();
        $this->access_init();
        $this->navigator_init();
        $this->module_init();
        $this->files_init();
        $this->navigator_process();
        $this->module_process();
        $this->events_init();
        $this->forms_init();
        $this->constructor_init();
        $this->form_process();
        $this->nodes_init();
            
        return true;
    }
    
    function db_init()
    {
        return false;
    }
    
    function db_test($db_type,$db_host,$db_port,$db_user,$db_pass,$db_name,$db_prefix)
    {
        require_once(ROOT_DIR.'/sawanna/db.inc.php');
        
        switch ($db_type)
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
         
        $this->db=new CDB($db_host,$db_port,$db_name,$db_user,$db_pass,$db_prefix);
        
        if (!$this->db->open()) return false;
        
        return true;
    }
    
    function choose_lang()
    {
        if (isset($_REQUEST['language']))
        {
            $langs=$GLOBALS['sawanna']->locale->get_installed_languages();
            $languages=array();
            foreach ($langs as $lname=>$linfo)
            {
                $languages[$lname]=$linfo['name'];
            }
            
            if (array_key_exists($_REQUEST['language'],$languages))
            {
                $this->locale->current=$_REQUEST['language'];
            } else 
            {
                unset($_REQUEST['language']);
            }
        }
        
        if (file_exists(ROOT_DIR.'/'.quotemeta($this->locale->root_dir).'/'.quotemeta($this->locale->current).'/install.lng.php'))
        {
            $this->locale->load(ROOT_DIR.'/'.quotemeta($this->locale->root_dir).'/'.quotemeta($this->locale->current).'/install.lng.php');
        }
    }
    
    function get_installed_settings()
    {
        if (!file_exists(ROOT_DIR.'/settings.php')) return false;
        $settings=file_get_contents(ROOT_DIR.'/settings.php');
        if (strlen($settings)<=1) return false;

        $find=array('DB_TYPE','DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS','DB_PREFIX');
        $consts=array();
        
        foreach ($find as $const)
        {
            if (preg_match('/define\([\x27]'.preg_quote($const).'[\x27],[\x27](.*?)[\x27]\);/s',$settings,$matches))
                $consts[$const]=$matches[1];
            else 
                $consts[$const]='';
        }
        
        return array($consts['DB_TYPE'],$consts['DB_HOST'],$consts['DB_PORT'],$consts['DB_USER'],$consts['DB_PASS'],$consts['DB_NAME'],$consts['DB_PREFIX']);
    }
    
    function process_forms($return=false)
    {
        $fname='';
        if (isset($_GET['fname'])) $fname=$_GET['fname'];
        if (isset($_POST['fname'])) $fname=$_POST['fname'];
        
        $step=isset($_GET['step']) && is_numeric($_GET['step']) && $_GET['step']>0 && $_GET['step']<6 ? $_GET['step'] : 1;
        
        if (!empty($fname) && function_exists($fname))
        {
                $redirect='';
                $form=$fname($step);

                if (isset($form['callback']))
                {
                    $result='';
                    if (!empty($form['caller']))
                    {
                        $obj=$this->module->get_widget_object_name($form['caller']);
                        if (!isset($GLOBALS[$obj]) && class_exists($form['caller'])) $GLOBALS[$obj]=new $form['caller'](); 
                        if (isset($GLOBALS[$obj]) && is_object($GLOBALS[$obj]) && method_exists($GLOBALS[$obj],$form['callback']))
                            $result=$GLOBALS[$obj]->{$form['callback']}($form,$step);
                    } elseif (empty($form['caller']) && function_exists($form['callback']))
                        $result=$form['callback']($form,$step);
                }
                
                if (!empty($form['redirect'])) $this->constructor->redirect($form['redirect']);
        }
    }
    
    function process()
    {
		$args=func_get_args();
		$html=$args[0];
		
        $this->tpl->init($this->query,$this->locale->current);

        $components=array();
        $components[$this->tpl->duty]=$html;
        
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
    
    function install_core_tables($config=array())
    {
           if (!is_object($this->db)) return false;
        
           $this->config->install($this->db,$config);
           $this->session->install($this->db);
           
           include_once(ROOT_DIR.'/sawanna/log.inc.php');
           $log=new CLog($this->db,$this->config->log_period); 
           $log->install($this->db);
           
           $this->event->install($this->db);
           $this->form->install($this->db); 
           $this->access->install($this->db); 
           $this->module->install($this->db); 
           $this->navigator->install($this->db);
           $this->node->install($this->db);
           $this->file->install($this->db);
           
           return true;
    }
    
    function reinstall_core_tables($table='all',$config=array())
    {
        if (!DEBUG_MODE) return false;
        
        if (!is_object($this->db)) return false;
        
        switch ($table)
        {
            case 'configuration':
                $this->config->uninstall($this->db);
                $this->config->install($this->db,$config=array());
                break;
            case 'session':
                $this->session->uninstall($this->db);
                $this->session->install($this->db);
                break;
            case 'log':
                include_once(ROOT_DIR.'/sawanna/log.inc.php'); 
                $log=new CLog($this->db,$this->config->log_period);
                $log->uninstall($this->db);
                $log->install($this->db);
                break;        
            case 'events':
                $this->event->uninstall($this->db);
                $this->event->install($this->db);
                break;
            case 'forms':
                $this->form->uninstall($this->db);
                $this->form->install($this->db);
                break;
            case 'permissions':
                $this->access->uninstall($this->db); 
                $this->access->install($this->db); 
                break;
            case 'modules':
                $this->module->uninstall($this->db);
                $this->module->install($this->db);
                break;
            case 'navigation':
                $this->navigator->uninstall($this->db);
                $this->navigator->install($this->db);
                break;
            case 'nodes':
                $this->node->uninstall($this->db);
                $this->node->install($this->db);
                break;
            case 'files':
                $this->file->uninstall($this->db);
                $this->file->install($this->db);
                break;
            case 'all':
                $this->config->uninstall($this->db);
                $this->config->install($this->db,$config=array());
               
                $this->session->uninstall($this->db);
                $this->session->install($this->db);
                
                include_once(ROOT_DIR.'/sawanna/log.inc.php');
                $log=new CLog($this->db,$this->config->log_period);
                $log->uninstall($this->db);
                $log->install($this->db);
                
                $this->event->uninstall($this->db);
                $this->event->install($this->db);
                
                $this->form->uninstall($this->db);
                $this->form->install($this->db); 
                
                $this->access->uninstall($this->db); 
                $this->access->install($this->db); 
                
                $this->module->uninstall($this->db);
                $this->module->install($this->db); 
                
                $this->navigator->uninstall($this->db);
                $this->navigator->install($this->db);
                
                $this->node->uninstall($this->db);
                $this->node->install($this->db);
                
                $this->file->uninstall($this->db);
                $this->file->install($this->db);
                break;
        }
        
        return true;
    }
    
    function process_reinstall_request($table)
    {
        $return=false;
        
        if (!empty($table))
        {
            list($db_type,$db_host,$db_port,$db_user,$db_pass,$db_name,$db_prefix)=$this->get_installed_settings();
            $this->db_test($db_type,$db_host,$db_port,$db_user,$db_pass,$db_name,$db_prefix);
            
            if ($this->reinstall_core_tables($table))
                $return=true;
                
            $this->db=null;
        }
        
        return $return;
    }
}
