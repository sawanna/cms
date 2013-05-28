<?php
defined('SAWANNA') or die();

class CTopAdmin
{
    var $sawanna;
    var $module_id;
    
    function CTopAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('top');
    }
    
    function optbar()
    {
        return  array(
                        'top/settings'=>'[str]Top-rating[/str]'
                        );
    }
    
     function admin_area()
    {
        if (!$this->sawanna->has_access('create nodes')) return ;
        
        $this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
        
        switch (true)
        {
            case $this->sawanna->arg=='top/settings':
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
            'value' => '<h1 class="tab">[str]Top-rating settings[/str]</h1>'
        );

        $most_visited_limit=$this->sawanna->get_option('top_most_visited_limit');
        
        $fields['settings-most_visited_limit']=array(
            'type' => 'text',
            'title' => '[str]Most visited pages limit[/str]',
            'description' => '[str]Specify how many pages to show[/str]',
            'default' => $most_visited_limit ? $most_visited_limit : '5'
        );
        
        $top_rated_limit=$this->sawanna->get_option('top_top_rated_limit');
        
        $fields['settings-top_rated_limit']=array(
            'type' => 'text',
            'title' => '[str]Top rated nodes limit[/str]',
            'description' => '[str]Specify how many nodes to show[/str]',
            'default' => $top_rated_limit ? $top_rated_limit : '5'
        );
        
        $top_visited_exclude_paths=$this->sawanna->get_option('top_visited_exclude_paths');
        
        $fields['settings-top_visited_exclude_paths']=array(
            'type' => 'textarea',
            'title' => '[str]Exclude pathes[/str]',
            'description' => '[str]Specify which pages should be excluded from top visited list[/str]',
            'attributes' => array(
								'rows' => 5,
								'cols' => 40
							),
            'default' => $top_visited_exclude_paths ? $top_visited_exclude_paths : ''
        );
        
        $stats=$this->sawanna->get_option('top_stats');
        
        $fields['settings-top_stats']=array(
            'type' => 'checkbox',
            'title' => '[str]Count page visits[/str]',
            'description' => '[str]Page visits counting will be enabled, if checked[/str]',
            'attributes'=> $stats ? array('checked'=>'checked') : array()
        );
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('settings-form',$fields,'settings_form_process',$this->sawanna->config->admin_area.'/top/settings',$this->sawanna->config->admin_area.'/top/settings','','CTopAdmin');
    }
    
    function settings_form_process()
    {
        if (!$this->sawanna->has_access('su')) 
        {
            return array('[str]Permission denied[/str]',array('top-settings'));
        }
        
        
        if (isset($_POST['settings-most_visited_limit']) && is_numeric($_POST['settings-most_visited_limit']) && $_POST['settings-most_visited_limit']>0 && $_POST['settings-most_visited_limit']<=100)
        {
            $this->sawanna->set_option('top_most_visited_limit',ceil($_POST['settings-most_visited_limit']),$this->module_id);
        } else 
        {
            $this->sawanna->set_option('top_most_visited_limit',5,$this->module_id);
        }
        
        if (isset($_POST['settings-top_rated_limit']) && is_numeric($_POST['settings-top_rated_limit']) && $_POST['settings-top_rated_limit']>0 && $_POST['settings-top_rated_limit']<=100)
        {
            $this->sawanna->set_option('top_top_rated_limit',ceil($_POST['settings-top_rated_limit']),$this->module_id);
        } else 
        {
            $this->sawanna->set_option('top_top_rated_limit',5,$this->module_id);
        }
        
        if (!empty($_POST['settings-top_visited_exclude_paths']))
        {
            $this->sawanna->set_option('top_visited_exclude_paths',trim($_POST['settings-top_visited_exclude_paths']),$this->module_id);
        } else 
        {
            $this->sawanna->set_option('top_visited_exclude_paths','',$this->module_id);
        }
        
         if (isset($_POST['settings-top_stats']) && $_POST['settings-top_stats']=='on')
        {
            $this->sawanna->set_option('top_stats',1,$this->module_id);
        } else 
        {
            $this->sawanna->set_option('top_stats',0,$this->module_id);
        }
             
        return array('[str]Settings saved[/str]');
    }
}
