<?php
defined('SAWANNA') or die();

class CPhpbbAdmin
{
    var $sawanna;
    var $module_id;
    
    function CPhpbbAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('phpbb');
    }
    
    function optbar()
    {
        return  array(
                        'phpbb/settings'=>'[str]Forum[/str]'
                        );
    }
    
    function admin_area()
    {
        if (!$this->sawanna->has_access('su')) return ;
        
        $this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
        
        switch (true)
        {
            case $this->sawanna->arg=='phpbb/settings':
                $this->settings_form();
                break;
        }
    }
    
     function settings_form()
    {
        if (!$this->sawanna->has_access('su')) 
        {
            trigger_error('Permission denied',E_USER_ERROR);
            return ;
        }
        
        $fields=array();
        
         $fields['settings-tab']=array(
            'type' => 'html',
            'value' => '<h1 class="tab">[str]Forum settings[/str]</h1>'
        );
        
        $limit=$this->sawanna->get_option('phpbb_limit');
        
        $fields['settings-limit']=array(
            'type' => 'text',
            'title' => '[str]Limit[/str]',
            'description' => '[str]Specify the number of last posts to display[/str]',
            'default' => $limit ? $limit : '5'
        );
        
        $path=$this->sawanna->get_option('phpbb_url');
        
        $fields['settings-url']=array(
            'type' => 'text',
            'title' => '[str]Forum URL[/str]',
            'description' => '[str]Specify phpbb3 URL path[/str]',
            'default' => $path? $path : SITE.'/forum'
        );
        
        $dir=$this->sawanna->get_option('phpbb_path');
        
        $fields['settings-path']=array(
            'type' => 'text',
            'title' => '[str]Forum path[/str]',
            'description' => '[str]Specify phpbb3 directory[/str]',
            'default' => $dir? $dir : REAL_PATH.'/'.quotemeta($this->sawanna->module->root_dir).'/phpbb/phpBB3'
        );
        
        $sw=REAL_PATH;
        if (file_exists($fields['settings-path']['default'].'/config.php')) {
			include($fields['settings-path']['default'].'/config.php');
			if (defined('SAWANNA_PATH')) $sw=SAWANNA_PATH;
		}
        
        $fields['settings-sw']=array(
            'type' => 'text',
            'title' => '[str]Sawanna path[/str]',
            'description' => '[str]Specify Sawanna directory[/str]',
            'default' => $sw
        );
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('settings-form',$fields,'settings_form_process',$this->sawanna->config->admin_area.'/phpbb/settings',$this->sawanna->config->admin_area.'/phpbb/settings','','CPhpbbAdmin');
    }
    
    function settings_form_process()
    {
        if (!$this->sawanna->has_access('su')) 
        {
            return array('[str]Permission denied[/str]',array('phpbb-settings'));
        }
        
        if (isset($_POST['settings-limit']) && is_numeric($_POST['settings-limit']) && $_POST['settings-limit']>0)
        {
            $this->sawanna->set_option('phpbb_limit',ceil($_POST['settings-limit']),$this->module_id);
        } else 
        {
            $this->sawanna->set_option('phpbb_limit',5,$this->module_id);
        }
        
        if (!empty($_POST['settings-url']))
        {
			$_POST['settings-url']=trim($_POST['settings-url'],'/');
			
            $this->sawanna->set_option('phpbb_url',$_POST['settings-url'],$this->module_id);
        } else 
        {
            $this->sawanna->set_option('phpbb_url',SITE.'/forum',$this->module_id);
        }
        
        if (!empty($_POST['settings-path']))
        {
			$_POST['settings-path']=rtrim($_POST['settings-path'],'/');
			
            $this->sawanna->set_option('phpbb_path',$_POST['settings-path'],$this->module_id);
        } else 
        {
            $this->sawanna->set_option('phpbb_path',REAL_PATH.'/'.quotemeta($this->sawanna->module->root_dir).'/phpbb/phpBB3',$this->module_id);
        }
        
        $this->sawanna->module->refresh_options();
        
        if (!empty($_POST['settings-sw']))
        {	
			$_POST['settings-sw']=rtrim($_POST['settings-sw'],'/');
			
			$phpbb_path=$this->sawanna->get_option('phpbb_path');
			if (file_exists($phpbb_path.'/config.php') && is_writable($phpbb_path.'/config.php')) {
				$phpbb_config=file_get_contents($phpbb_path.'/config.php');
				if (preg_match("/^(<[?]php[\r\n\x20])(?:[\n]*define[(]'SAWANNA_PATH','[^']+'[)];[\n]{0,2})?(.+)$/si",$phpbb_config,$m)) {
					$phpbb_config=$m[1]."\ndefine('SAWANNA_PATH','".$_POST['settings-sw']."');\n\n".$m[2];
					$c=fopen($phpbb_path.'/config.php','wb');
					fwrite($c,$phpbb_config);
					fclose($c);
				} else {
					return array('No match in phpBB3 config.php <pre>'.htmlspecialchars($phpbb_config).'</pre>',array('settings-sw'));
				}
			} else {
				return array('[str]Forum configuration file is not writable[/str]',array('settings-sw'));
			}
		}
		
        return array('[str]Settings saved[/str]');
    }    
}
