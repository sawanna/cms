<?php
defined('SAWANNA') or die();

class CJcloudAdmin
{
    var $sawanna;
    var $module_id;
    
    function CJcloudAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('jcloud');
    }
    
    function admin_area()
    {
        if (!$this->sawanna->has_access('su')) return ;
        
        $this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
        
        switch (true)
        {
            case $this->sawanna->arg=='jcloud/settings':
                $this->settings_form();
                break;
        }
    }
    
    function optbar()
    {
        return  array(
                        'jcloud/settings'=>'[str]Tags cloud[/str]'
                        );
    }
    
    function get_cloud_views()
    {
        return array(
            'circle' => '[str]Circle[/str]',
            'sphere' => '[str]Sphere[/str]',
            'spiral' => '[str]Spiral[/str]',
            'chaos' => '[str]Chaos[/str]',
            'rings' => '[str]Rings[/str]',
            'cumulus' => '[str]Cumulus[/str]'
        );
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
            'value' => '<h1 class="tab">[str]Tags cloud settings[/str]</h1>'
        );
        
        $radius=$this->sawanna->get_option('jcloud_radius');
        
        $fields['settings-radius']=array(
            'type' => 'text',
            'title' => '[str]Radius[/str]',
            'description' => '[str]Specify cloud radius[/str]',
            'default' => $radius ? $radius : '90'
        );
        
        $size=$this->sawanna->get_option('jcloud_size');
        
        $fields['settings-size']=array(
            'type' => 'text',
            'title' => '[str]Font size[/str]',
            'description' => '[str]Specify tag font size[/str]',
            'default' => $size ? $size : '20'
        );
        
        $color=$this->sawanna->get_option('jcloud_color');
        
        $fields['settings-color']=array(
            'type' => 'color',
            'title' => '[str]Color[/str]',
            'description' => '[str]Choose tag color[/str]',
            'default' => $color ? $color : '#000000'
        );
        
        
        $view=$this->sawanna->get_option('jcloud_view');
        
        $cloud_views=$this->get_cloud_views();
        
        $fields['settings-view']=array(
            'type' => 'select',
            'options' => $cloud_views,
            'title' => '[str]Cloud view[/str]',
            'description' => '[str]Choose tags cloud view[/str]',
            'default' => $view ? $view : 'circle'
        );
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('settings-form',$fields,'settings_form_process',$this->sawanna->config->admin_area.'/jcloud/settings',$this->sawanna->config->admin_area.'/jcloud/settings','','CJcloudAdmin');
    }
    
    function settings_form_process()
    {
        if (!$this->sawanna->has_access('su')) 
        {
            return array('[str]Permission denied[/str]',array('jcloud-settings'));
        }
        
         if (isset($_POST['settings-radius']) && is_numeric($_POST['settings-radius']) && $_POST['settings-radius']>=50 && $_POST['settings-radius']<=300)
        {
            $this->sawanna->set_option('jcloud_radius',floor($_POST['settings-radius']),$this->module_id);
        } else 
        {
            $this->sawanna->set_option('jcloud_radius',90,$this->module_id);
        }
        
        if (isset($_POST['settings-size']) && is_numeric($_POST['settings-size']) && $_POST['settings-size']>4 && $_POST['settings-size']<=50)
        {
            $this->sawanna->set_option('jcloud_size',ceil($_POST['settings-size']),$this->module_id);
        } else 
        {
            $this->sawanna->set_option('jcloud_size',20,$this->module_id);
        }
        
        if (isset($_POST['settings-color']) && preg_match('/\#[a-f0-9]{3,6}/i',$_POST['settings-color']))
        {
            $this->sawanna->set_option('jcloud_color',$_POST['settings-color'],$this->module_id);
        } else 
        {
            $this->sawanna->set_option('jcloud_color','#000000',$this->module_id);
        }
        
        $views=$this->get_cloud_views();
        if (isset($_POST['settings-view']) && array_key_exists($_POST['settings-view'],$views))
        {
            $this->sawanna->set_option('jcloud_view',$_POST['settings-view'],$this->module_id);
        } else 
        {
            $this->sawanna->set_option('jcloud_view','circle',$this->module_id);
        }
    
        // clearing cache
        $this->sawanna->cache->clear_all_by_type('jcloud');
            
        return array('[str]Settings saved[/str]');
    }   
}
