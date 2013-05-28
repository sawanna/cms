<?php
defined('SAWANNA') or die();

class CArchiveAdmin
{
    var $sawanna;
    var $module_id;
    
    function CArchiveAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('archive');
    }

    function optbar()
    {
        return  array(
                        'archive/settings'=>'[str]Archive[/str]'
                        );
    }
    
    function admin_area()
    {
        
        $this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
        
        switch (true)
        {
            case $this->sawanna->arg=='archive/settings':
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
            'value' => '<h1 class="tab">[str]Archive settings[/str]</h1>'
        );
        
        $path=$this->sawanna->get_option('archive_nodes_path');
        
        $fields['settings-path']=array(
            'type' => 'text',
            'title' => '[str]Archive path[/str]',
            'description' => '[str]Enter archive nodes parent path.[/str] [str]Current node parent will be used, if empty[/str]',
            'default' => $path ? $path : ''
        );
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('settings-form',$fields,'settings_form_process',$this->sawanna->config->admin_area.'/archive/settings',$this->sawanna->config->admin_area.'/archive/settings','','CArchiveAdmin');
    }
    
    function settings_form_process()
    {
        if (!$this->sawanna->has_access('su')) 
        {
            return array('[str]Permission denied[/str]',array('archive-settings'));
        }
        
        if (!empty($_POST['settings-path']))
        {
            $_POST['settings-path']=trim($_POST['settings-path'],'/');
            if (strpos($_POST['settings-path'],'/')!==false)
            {
                $_POST['settings-path']=substr($_POST['settings-path'],0,strpos($_POST['settings-path'],'/'));
            }
            
            $this->sawanna->set_option('archive_nodes_path',$_POST['settings-path'],$this->module_id);
        } else 
        {
            $this->sawanna->set_option('archive_nodes_path','',$this->module_id);
        }
        
        return array('[str]Settings saved[/str]');
    }
}
