<?php
defined('SAWANNA') or die();

class CAjaxerAdmin
{
    var $sawanna;
    var $module_id;
    
    function CAjaxerAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('ajaxer');
    }
    
    function optbar()
    {
        return  array(
                        'ajaxer/settings'=>'[str]AJAXer[/str]'
                        );
    }
    
    function admin_area()
    {
        if (!$this->sawanna->has_access('su')) return ;

        switch (true)
        {
            case $this->sawanna->arg=='ajaxer/settings':
				$this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
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
            'value' => '<h1 class="tab">[str]AJAXer settings[/str]</h1><div class="caption">[str]AJAXer components[/str]:</div><p class="form_description">[str]This components will be updated by AJAXer[/str]</p>'
        );
        
        $components=$this->sawanna->get_option('ajaxer_components');
        
        $tpl_components=$this->sawanna->tpl->getComponents();
        
        foreach($tpl_components as $cname=>$ctitle)
        {
			$fields['settings-component-'.$cname]=array(
				'type' => 'checkbox',
				'title' => '[str]'.$ctitle.'[/str]',
				'description' => '',
				'attributes'=> in_array($cname,$components) ? array('checked'=>'checked') : array()
			);
		}
		
		$ajaxer_forms=$this->sawanna->get_option('ajaxer_process_forms');
		
		$fields['settings-sep']=array(
			'type' => 'html',
			'value' => '<div class="ajaxer-settings-sep"></div>'
		);
		
		$fields['settings-process-forms']=array(
			'type' => 'checkbox',
			'title' => '[str]Process forms[/str]',
			'description' => '[str]AJAXer will try to process forms submissions, if enabled[/str]',
			'attributes'=> $ajaxer_forms ? array('checked'=>'checked') : array()
		);
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('settings-form',$fields,'settings_form_process',$this->sawanna->config->admin_area.'/ajaxer/settings',$this->sawanna->config->admin_area.'/ajaxer/settings','','CAjaxerAdmin');
    }
    
    function settings_form_process()
    {
        if (!$this->sawanna->has_access('su')) 
        {
            return array('[str]Permission denied[/str]',array('ajaxer-settings'));
        }
        
        $components=array();
        
        $tpl_components=$this->sawanna->tpl->getComponents();
        
        foreach($tpl_components as $cname=>$ctitle)
        {
			if (isset($_POST['settings-component-'.$cname]) && $_POST['settings-component-'.$cname]=='on')
			{
				$components[]=$cname;
			}
		}
        
        $d=new CData();
        $this->sawanna->set_option('ajaxer_components',$d->do_serialize($components),$this->module_id);
        
        if (isset($_POST['settings-process-forms']) && $_POST['settings-process-forms']=='on')
		{
			$this->sawanna->set_option('ajaxer_process_forms','1',$this->module_id);
		} else
		{
			$this->sawanna->set_option('ajaxer_process_forms','0',$this->module_id);
		}
			
        return array('[str]Settings saved[/str]');
    }   
}
