<?php
defined('SAWANNA') or die();

class CSitemapAdmin
{
    var $sawanna;
    var $module_id;
    
    function CSitemapAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('sitemap');
    }
    
    function optbar()
    {
        return  array(
                        'sitemap/settings'=>'[str]Sitemap[/str]'
                        );
    }
    
    function admin_area()
    {
        if (!$this->sawanna->has_access('su')) return ;
        
        $this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
        
        switch (true)
        {
            case $this->sawanna->arg=='sitemap/settings':
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
            'value' => '<h1 class="tab">[str]Sitemap settings[/str]</h1>'
        );
        
        $limit=$this->sawanna->get_option('sitemap_limit');
        
        $fields['settings-limit']=array(
            'type' => 'text',
            'title' => '[str]Limit[/str]',
            'description' => '[str]Specify the number to of links to display per page[/str]',
            'default' => $limit ? $limit : '100'
        );
        
        $freqs=$this->get_freqs();
        
        $freq=$this->sawanna->get_option('sitemap_freq');
        
        $fields['settings-freq']=array(
            'type' => 'select',
            'options' => $freqs,
            'title' => '[str]Change frequency[/str]',
            'description' => '[str]Specify how often your website pages change[/str]',
            'default' => $freq ? $freq : 'daily'
        );
        
        // deprecated
        /*
        $prio=$this->sawanna->get_option('sitemap_decrease_priority');
        
        $fields['settings-prio']=array(
            'type' => 'checkbox',
            'title' => '[str]Descrease priority of child nodes[/str]',
            'description' => '[str]The priority of every child node will be decreased if enabled[/str]',
            'attributes'=> $prio ? array('checked'=>'checked') : array()
        );
        */
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('settings-form',$fields,'settings_form_process',$this->sawanna->config->admin_area.'/sitemap/settings',$this->sawanna->config->admin_area.'/sitemap/settings','','CSitemapAdmin');
    }
    
    function settings_form_process()
    {
        if (!$this->sawanna->has_access('su')) 
        {
            return array('[str]Permission denied[/str]',array('sitemap-settings'));
        }
        
        if (isset($_POST['settings-limit']) && is_numeric($_POST['settings-limit']) && $_POST['settings-limit']>0)
        {
            $this->sawanna->set_option('sitemap_limit',ceil($_POST['settings-limit']),$this->module_id);
        } else 
        {
            $this->sawanna->set_option('sitemap_limit',100,$this->module_id);
        }
        
        $freqs=$this->get_freqs();
        if (isset($_POST['settings-freq']) && array_key_exists($_POST['settings-freq'],$freqs))
        {
            $this->sawanna->set_option('sitemap_freq',$_POST['settings-freq'],$this->module_id);
        } else 
        {
            $this->sawanna->set_option('sitemap_freq','daily',$this->module_id);
        }
        
        if (isset($_POST['settings-prio']) && $_POST['settings-prio']=='on')
        {
            $this->sawanna->set_option('sitemap_decrease_priority',1,$this->module_id);
        } else 
        {
            $this->sawanna->set_option('sitemap_decrease_priority',0,$this->module_id);
        }
        
        return array('[str]Settings saved[/str]');
    }   
    
    function get_freqs()
    {
        return array(
            'always' => '[str]Always[/str]',
            'hourly' => '[str]Hourly[/str]',
            'daily' => '[str]Daily[/str]',
            'weekly' => '[str]Weekly[/str]',
            'monthly' => '[str]Monthly[/str]',
            'yearly'=> '[str]Yearly[/str]',
            'never' => '[str]Never[/str]'
        );
    }
}
