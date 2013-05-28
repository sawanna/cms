<?php
defined('SAWANNA') or die();

class CAjaxer
{
    var $sawanna;
    var $components;
    var $exclude_pathes;
    var $ajax_forms;
    
    function CAjaxer(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('ajaxer');
        
        $this->components=array();
        
        $components=$this->sawanna->get_option('ajaxer_components');
        
        if (!empty($components) && is_array($components)) $this->components=$components;
        
        $this->exclude_pathes=array();
        
        $this->ajax_forms=$this->sawanna->get_option('ajaxer_process_forms');
    }
    
    function ready()
    {
		$this->sawanna->module->duty_paths[]='ajaxer';
		
		$this->exclude_pathes[]=$this->sawanna->constructor->url('rss',false,true);
		$this->exclude_pathes[]=$this->sawanna->constructor->url('logout',false,true);
		$this->exclude_pathes[]=$this->sawanna->constructor->url('user/logout',false,true);
		$this->exclude_pathes[]=$this->sawanna->constructor->url('file',false,true);
		
		if (count($this->sawanna->locale->languages)>1)
		{
			foreach($this->sawanna->locale->languages as $lcode=>$lname)
			{
				if ($lcode==$this->sawanna->locale->current) continue;
				
				if ($this->sawanna->config->clean_url)
				{
					$url=SITE.'/'.$lcode;
				} else
				{
					$url=SITE.'/index.php?'.$this->sawanna->query_var.'='.$lcode;
				}
			
				$this->exclude_pathes[]=$url;
			}
		}
		
		if ($this->sawanna->module_exists('phpbb') && isset($GLOBALS['phpbb']) && is_object($GLOBALS['phpbb'])) {
			$phpbb_url=$this->sawanna->get_option('phpbb_url');
			$this->exclude_pathes[]=$phpbb_url ? $phpbb_url : SITE.'/forum';
		}

		$this->exclude_pathes[]=$this->sawanna->constructor->url($this->sawanna->config->admin_area,false,true);
	}
	
    function load()
    {
		if ($this->sawanna->is_mobile) return;
		if (empty($this->components)) return;
		if (in_array($this->sawanna->constructor->url($this->sawanna->query),$this->exclude_pathes)) return;
		
		$this->sawanna->tpl->add_script(quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/js/ajaxer');
		if ($this->ajax_forms) $this->sawanna->tpl->add_script(quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/js/forms');
		$this->sawanna->tpl->add_header('<script language="JavaScript">ajaxer_path="'.$this->sawanna->constructor->url('ajaxer').'"; ajaxerComponents=new Array("'.implode('","',$this->components).'"); ajaxerExclude=new Array("'.implode('","',$this->exclude_pathes).'"); ajaxer_error_msg="[str]Error. AJAX mode will be disabled now[/str]"; ajaxer_host="'.PROTOCOL.'://'.$_SERVER['HTTP_HOST'].'";</script>');
		
		$event=array();
		$event['url']='ajaxer';
		$event['caller']='CAjaxer';
		$event['job']='ajaxer';
		$event['weight']=99999;
		$event['constant']=1;
		$this->sawanna->event->register($event);
		
		$this->save_loaded_scripts();
		$this->save_loaded_styles();

		return '<div id="ajaxerBG"></div><div id="ajaxerLoader">[str]Please wait[/str]...</div>';
	}
	
	function save_loaded_scripts()
	{
		if (!empty($this->sawanna->tpl->params['scripts']) && is_array($this->sawanna->tpl->params['scripts']))
		{
			$this->sawanna->session->set('ajaxer_loaded_scripts',$this->sawanna->tpl->params['scripts']);
		}
	}
	
	function save_loaded_styles()
	{
		if (!empty($this->sawanna->tpl->params['css']) && is_array($this->sawanna->tpl->params['css']))
		{
			$this->sawanna->session->set('ajaxer_loaded_styles',$this->sawanna->tpl->params['css']);
		}
	}
    
    function ajaxer($recall=false)
    {
		
		if (empty($_POST['ajaxer_components']) || empty($_POST['ajaxer_path'])) 
		{
			$this->reset();
			return;
		}
		
		$components=explode(',',trim($_POST['ajaxer_components'],','));
		$path=$this->extract_path($_POST['ajaxer_path']);
	
		$this->sawanna->query=urldecode($path);
		
		$this->sawanna->locale->init($this->sawanna->query);

		if (empty($this->sawanna->query)) 
			$this->sawanna->query=trim($this->sawanna->config->front_page_path);
		
		if (!$this->sawanna->navigator_process()) {
			$this->reset();
			return;
		}

		if ($this->sawanna->query == $this->sawanna->config->admin_area) {
			$this->reset();
			return;
		}

		$this->sawanna->module_process();
		
        if (!$this->sawanna->navigator_access()) {
			$this->reset();
			return;
		}
        
        $scripts='';
        $outputs=array();
        
        $this->fix_modules($scripts,$outputs);

        $form_result=$this->process_forms();
     
        if (!empty($form_result))
        {
			$this->reset();
			echo sawanna_json_encode(array('redirect'=>$this->sawanna->constructor->url($form_result,false,false,true)));
			return;
		}
        
        $this->sawanna->process_events(array('url'=>'ajaxer'));

        $this->sawanna->tpl->init($this->sawanna->query,$this->sawanna->locale->current,$this->sawanna->arg);
 
		foreach($components as $component)
		{
			if (!in_array($component,$this->components)) {
				$this->reset();
				return;
			}
			
			$output=$this->process($component);
			$scripts.=$this->extract_scripts($output);
			
			if (isset($outputs[$component])) 
				$outputs[$component].=$output;
			else
				$outputs[$component]=$output;
		}
		
		$duty=isset($outputs[$this->sawanna->tpl->duty]) ? $outputs[$this->sawanna->tpl->duty] : '';
        $this->sawanna->tpl->duty();
        $this->sawanna->locale->translate($this->sawanna->tpl->params['duty']);
        $outputs[$this->sawanna->tpl->duty]=$this->sawanna->tpl->params['duty'].$duty;
	
		$scripts=$this->load_scripts()."\r\n".$scripts;		
		$css=$this->load_styles();	
        $output=sawanna_json_encode(array_merge($outputs,array('script'=>$scripts,'css'=>$css)));
        
        $this->reset();
        
        echo $this->output($output);
	}
	
	function process_forms()
	{
		if (empty($_POST['ajaxer_form_elements'])) return;
		
		$elems=explode(';',trim($_POST['ajaxer_form_elements'],';'));
		
		$_POST['ajaxer_form_elements']='';
		
		foreach($elems as $elem)
		{
			if (!preg_match('/^[^=]+=[^=]*$/',$elem)) continue;
			
			list($key,$value)=explode('=',$elem);
			
			$_POST[urldecode($key)]=urldecode($value);
		}
		
		$return=$this->sawanna->process_forms(true);
		
		foreach($elems as $elem)
		{
			if (!preg_match('/^[^=]+=[^=]*$/',$elem)) continue;
			
			list($key,$value)=explode('=',$elem);
			
			$_POST[urldecode($key)]='';
		}
		
		return $return;
	}
	
	function reset()
	{
		$this->sawanna->query='ajaxer';
        $this->sawanna->arg='';
        $this->sawanna->navigator->current->render=false;
	}
	
	function extract_path($path)
	{
		if (defined('BASE_PATH') && (bool)BASE_PATH && strpos($path,BASE_PATH)===0) {
			$path=substr($path,strlen(BASE_PATH));
		}
		
		$path=trim($path,'/');
		
		if (preg_match('/(.+)\.html$/i',$path,$m))
		{
			$path=$m[1];
		}
		
		if (preg_match('/(.*)\#.*/i',$path,$m))
		{
			$path=$m[1];
		}
		
		if (strpos($path,SITE)!==0) {
			$path=SITE.'/'.$path;
		}
		
		if (preg_match('/^'.addcslashes(preg_quote(SITE),'/').'\/([^\.\?]+)$/i',$path,$m))
		{
			$path=SITE.'/index.php?'.$this->sawanna->query_var.'='.$m[1];
		}
		
		$path=$this->sawanna->get_location_by_url($path);
		
		return $path;
	}
	
	function extract_scripts(&$output)
	{
		$scripts='';
		
		while(preg_match('/<script[^>]*>([^<]+)<\/script>/i',$output,$m))
		{
			$scripts.=$m[1]."\r\n";
			$output=str_replace($m[0],' ',$output);
		}
		
		return $scripts;
	}
	
	function load_scripts()
	{
		$return='';
		
		if (!empty($this->sawanna->tpl->params['scripts']) && is_array($this->sawanna->tpl->params['scripts']))
		{
			$loaded_scripts=$this->sawanna->session->get('ajaxer_loaded_scripts');
			
			if (empty($loaded_scripts) || !is_array($loaded_scripts)) $loaded_scripts=array();
			
			foreach($this->sawanna->tpl->params['scripts'] as $script)
			{
				if (!in_array($script,$loaded_scripts) && file_exists(ROOT_DIR.'/'.quotemeta($script).'.js'))
				{
					$return.=file_get_contents(ROOT_DIR.'/'.quotemeta($script).'.js')."\r\n";
				}
			}
		}
		
		return $return;
	}
	
	function load_styles()
	{
		$return=" \r\n";
		
		if (!empty($this->sawanna->tpl->params['css']) && is_array($this->sawanna->tpl->params['css']))
		{
			$loaded_styles=$this->sawanna->session->get('ajaxer_loaded_styles');
			
			if (empty($loaded_styles) || !is_array($loaded_styles)) $loaded_styles=array();
			
			foreach($this->sawanna->tpl->params['css'] as $css)
			{
				if (!in_array($css,$loaded_styles) && file_exists(ROOT_DIR.'/'.quotemeta($css).'.css'))
				{
					$return.=file_get_contents(ROOT_DIR.'/'.quotemeta($css).'.css')."\r\n";
				}
			}
		}
		
		return $return;
	}
	
	function fix_modules(&$scripts,&$outputs)
	{
		foreach ($this->sawanna->module->modules as $module)
        {
           if (!isset($module->root)) continue;
           if (!$module->enabled) continue;
            
           if (empty($module->name)) continue;
            
           if ($module->name == 'ajaxer') continue;
           
           if (!isset($GLOBALS[$module->name]) || !is_object($GLOBALS[$module->name]) || !method_exists($GLOBALS[$module->name],'ajaxer')) continue;
            
           $GLOBALS[$module->name]->ajaxer($scripts,$outputs);
        }
	}
	
	function process($component)
    {
		$this->sawanna->module->argument_tokens['%component']=$component;

		$components=array();
		$components[$component]='';
		
		$this->sawanna->process_components($component,$components);  
        
        if (!empty($components[$component]))
        {
            $this->sawanna->locale->translate($components[$component]);

            return $components[$component];
        } else return ' ';
    }
    
    function output(&$html)
    {
        if (empty($html) || $this->sawanna->query==$this->sawanna->config->admin_area) return;

        header('Content-Type: text/html; charset=utf-8'); 
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: ".date('r',time()-3600)); 
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	
        echo $html;
        
        if ($this->sawanna->config->gzip_out && function_exists('gzencode') && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)
        {
            
            header("Content-Encoding: gzip");  
            $output=ob_get_contents();
            ob_end_clean();

            ob_start('ob_gzhandler');
            echo $output;
        }
    }
}
    
