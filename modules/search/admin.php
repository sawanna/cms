<?php
defined('SAWANNA') or die();

class CSearchAdmin
{
    var $sawanna;
    var $module_id;
    
    function CSearchAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('search');
    }

    function optbar()
    {
        return  array(
                        'search/settings'=>'[str]Search[/str]'
                        );
    }
    
    function admin_area()
    {
        
        $this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
        
        switch (true)
        {
            case $this->sawanna->arg=='search/settings':
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
            'value' => '<h1 class="tab">[str]Search settings[/str]</h1>'
        );
        
        $parse=$this->sawanna->get_option('search_parse_content');
        
        $fields['settings-parse']=array(
            'type' => 'checkbox',
            'title' => '[str]Parse node content[/str]',
            'description' => '[str]Node keywords will be replaced by tag links, if enabled[/str]',
            'attributes' => $parse ? array('checked'=>'checked') : array()
        );
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('settings-form',$fields,'settings_form_process',$this->sawanna->config->admin_area.'/search/settings',$this->sawanna->config->admin_area.'/search/settings','','CSearchAdmin');
    }
    
    function settings_form_process()
    {
        if (!$this->sawanna->has_access('su')) 
        {
            return array('[str]Permission denied[/str]',array('search-settings'));
        }
        
        if (isset($_POST['settings-parse']) && $_POST['settings-parse']=='on')
        {
            $this->sawanna->set_option('search_parse_content',1,$this->module_id);
        } else 
        {
            $this->sawanna->set_option('search_parse_content',0,$this->module_id);
        }
        
        return array('[str]Settings saved[/str]');
    }
}
