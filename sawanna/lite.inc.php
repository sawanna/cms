<?php
defined('SAWANNA') or die();

require_once(ROOT_DIR.'/sawanna/sawanna.inc.php');

class CSawannaLite extends CSawanna
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
	
    function init()
    {
        $this->query=isset($_GET[$this->query_var]) ? urldecode(trim($_GET[$this->query_var],'/')) : '';
        
        //if (DEBUG_MODE && $this->query!='css' && $this->query!='image' && $this->query!='file' && $this->query!='ping') define('EXEC_TIME',1); // will brake some output like ajax, css, image etc.
        
        if (!$this->valid_inputs())
            return false;

        if (!$this->db_init() || !$this->load_conf() || !$this->locale_init())
            return false;

        $this->config_process();     
        $this->cache_init();
        $this->tpl_init();
        
        $this->sess_init();
        
        $this->check_cookies();
        $this->access_init();
        
        $this->navigator_init();
        
        $this->module_init();
        
        $this->files_init();
        
        if (!$this->navigator_process()) return false;
        
        $this->events_init();
        $this->forms_init();
        $this->constructor_init(); 
            
        $this->form_process();
        $this->nodes_init();
        
        return true;
    }
    
    function create_module_object($module)
    {
        if (empty($module) || !is_object($module)) return false;
        
		if (!isset($module->root)) continue;
		if (!$module->enabled) continue;
		
		if (empty($module->name)) continue;
		
		$module_class_name='C'.ucwords($module->name);
		
		if (class_exists($module_class_name))
			return new $module_class_name($this);
    }
    
    function load_module($name) {
		$mid=$this->module->get_module_id($name);
		
		if (!$mid) return false;
		
		$module=$this->module->modules[$mid];
		$modules=array($mid=>$module);
		
		$this->module->include_modules($modules);
        return $this->create_module_object($module);
	}

}
