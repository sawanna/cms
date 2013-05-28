<?php
defined('SAWANNA') or die();

class CRssAdmin
{
    var $sawanna;
    var $module_id;
    
    function CRssAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('rss');
    }
    
    function optbar()
    {
        return  array(
                        'rss/settings'=>'[str]RSS[/str]'
                        );
    }
    
    function admin_area()
    {
        if (!$this->sawanna->has_access('su')) return ;
        
        $this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
        
        switch (true)
        {
            case $this->sawanna->arg=='rss/settings':
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
            'value' => '<h1 class="tab">[str]RSS settings[/str]</h1>'
        );
        
        $limit=$this->sawanna->get_option('rss_limit');
        
        $fields['settings-limit']=array(
            'type' => 'text',
            'title' => '[str]Limit[/str]',
            'description' => '[str]Specify the number of nodes to display[/str]',
            'default' => $limit ? $limit : '10'
        );
        
        $path=$this->sawanna->get_option('rss_path');
        
        $fields['settings-path']=array(
            'type' => 'text',
            'title' => '[str]RSS path[/str]',
            'description' => '[str]If RSS path specified, RSS feed will be generated from nodes which parent has specified path, otherwise it will be generated according to the URL path[/str]',
            'default' => $path? $path : ''
        );
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('settings-form',$fields,'settings_form_process',$this->sawanna->config->admin_area.'/rss/settings',$this->sawanna->config->admin_area.'/rss/settings','','CRssAdmin');
    }
    
    function settings_form_process()
    {
        if (!$this->sawanna->has_access('su')) 
        {
            return array('[str]Permission denied[/str]',array('rss-settings'));
        }
        
        if (isset($_POST['settings-limit']) && is_numeric($_POST['settings-limit']) && $_POST['settings-limit']>0)
        {
            $this->sawanna->set_option('rss_limit',ceil($_POST['settings-limit']),$this->module_id);
        } else 
        {
            $this->sawanna->set_option('rss_limit',10,$this->module_id);
        }
        
        if (isset($_POST['settings-path']) && valid_path($_POST['settings-path']))
        {
            $this->sawanna->set_option('rss_path',$_POST['settings-path'],$this->module_id);
        } else 
        {
            $this->sawanna->set_option('rss_path','',$this->module_id);
        }
        
        return array('[str]Settings saved[/str]');
    }    
}
