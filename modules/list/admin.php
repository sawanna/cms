<?php
defined('SAWANNA') or die();

class CNodesListAdmin
{
    var $sawanna;
    var $module_id;
    
    function CNodesListAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('nodesList');
    }
    
    function optbar()
    {
        return  array(
                        'nodeslist/settings'=>'[str]Nodes list[/str]'
                        );
    }
    
    function admin_area()
    {
        if (!$this->sawanna->has_access('su')) return ;
        
        $this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
        
        switch (true)
        {
            case $this->sawanna->arg=='nodeslist/settings':
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
            'value' => '<h1 class="tab">[str]Nodes list settings[/str]</h1>'
        );
        
        $limit=$this->sawanna->get_option('nodes_list_limit');
        
        $fields['settings-limit']=array(
            'type' => 'text',
            'title' => '[str]Limit[/str]',
            'description' => '[str]Specify the number of nodes to display per page[/str]',
            'default' => $limit ? $limit : '10'
        );
        
        $titles=$this->sawanna->get_option('nodes_list_add_titles');
        
        $fields['settings-titles']=array(
            'type' => 'checkbox',
            'title' => '[str]Show nodes titles in browser window[/str]',
            'description' => '[strf]Nodes titles will be added to the "%title%" tag if enabled[/strf]',
            'attributes' => $titles ? array('checked'=>'checked') : array()
        );
        
        $keywords=$this->sawanna->get_option('nodes_list_add_keywords');
        
        $fields['settings-keywords']=array(
            'type' => 'checkbox',
            'title' => '[str]Show nodes keywords[/str]',
            'description' => '[strf]Nodes keywords will be added to the "%keywords%" meta-tag if enabled[/strf]',
            'attributes' => $keywords ? array('checked'=>'checked') : array()
        );
        
        $descs=$this->sawanna->get_option('nodes_list_add_descriptions');
        
        $fields['settings-descs']=array(
            'type' => 'checkbox',
            'title' => '[str]Show nodes meta-descriptions[/str]',
            'description' => '[strf]Nodes descriptions will be added to the "%description%" meta-tag if enabled[/strf]',
            'attributes' => $descs ? array('checked'=>'checked'): array()
        );
        
        $arg=$this->sawanna->get_option('nodes_list_check_arg');
        
        $fields['settings-arg']=array(
            'type' => 'checkbox',
            'title' => '[str]Check page number[/str]',
            'description' => '[str]Page number will be checked before adding titles, keywords and descriptions, if enabled. First page will be skipped[/str]',
            'attributes' => $arg ? array('checked'=>'checked'): array()
        );
        
        if ($this->sawanna->module->module_exists("code")) {
			$list_html_widget=$this->sawanna->get_option('list_html_widget');
			
			$codes=array(0=>'[str]Please choose widget[/str]');
			$this->sawanna->db->query("select * from pre_cwidgets order by id");
			while($row=$this->sawanna->db->fetch_object())
			{
				$codes[$row->id]=$row->name;
			}
			
			$fields['settings-list-html']=array(
                'type' => 'select',
                'title' => '[str]Show HTML widget layer[/str]',
                'options' => $codes,
                'description' => '[str]Choose html widget[/str]',
                'default' => $list_html_widget ? $list_html_widget : 0,
                'required' => false
            );
            
            $devider=$this->sawanna->get_option('list_html_widget_devider');
        
			$fields['settings-html-devider']=array(
				'type' => 'text',
				'title' => '[str]Widget devider[/str]',
				'description' => '[str]Specify the number of nodes which should be skipped before the widget displayed[/str]',
				'default' => $devider ? $devider : '1'
			);
		}
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('settings-form',$fields,'settings_form_process',$this->sawanna->config->admin_area.'/nodeslist/settings',$this->sawanna->config->admin_area.'/nodeslist/settings','','CNodesListAdmin');
    }
    
    function settings_form_process()
    {
        if (!$this->sawanna->has_access('su')) 
        {
            return array('[str]Permission denied[/str]',array('nodesList-settings'));
        }
        
        if (isset($_POST['settings-limit']) && is_numeric($_POST['settings-limit']) && $_POST['settings-limit']>0)
        {
            $this->sawanna->set_option('nodes_list_limit',ceil($_POST['settings-limit']),$this->module_id);
        } else 
        {
            $this->sawanna->set_option('nodes_list_limit',10,$this->module_id);
        }
        
        if (isset($_POST['settings-titles']) && $_POST['settings-titles']=='on')
        {
            $this->sawanna->set_option('nodes_list_add_titles',1,$this->module_id);
        } else 
        {
            $this->sawanna->set_option('nodes_list_add_titles',0,$this->module_id);
        }
        
        if (isset($_POST['settings-keywords']) && $_POST['settings-keywords']=='on')
        {
            $this->sawanna->set_option('nodes_list_add_keywords',1,$this->module_id);
        } else 
        {
            $this->sawanna->set_option('nodes_list_add_keywords',0,$this->module_id);
        }
        
        if (isset($_POST['settings-descs']) && $_POST['settings-descs']=='on')
        {
            $this->sawanna->set_option('nodes_list_add_descriptions',1,$this->module_id);
        } else 
        {
            $this->sawanna->set_option('nodes_list_add_descriptions',0,$this->module_id);
        }
        
        if (isset($_POST['settings-arg']) && $_POST['settings-arg']=='on')
        {
            $this->sawanna->set_option('nodes_list_check_arg',1,$this->module_id);
        } else 
        {
            $this->sawanna->set_option('nodes_list_check_arg',0,$this->module_id);
        }
        
        if ($this->sawanna->module->module_exists('code')) {
			if (isset($_POST['settings-list-html']) && is_numeric($_POST['settings-list-html']) && $_POST['settings-list-html']>0)
			{
				$this->sawanna->set_option('list_html_widget',ceil($_POST['settings-list-html']),$this->module_id);
			} else 
			{
				$this->sawanna->set_option('list_html_widget',0,$this->module_id);
			}
			
			if (isset($_POST['settings-html-devider']) && is_numeric($_POST['settings-html-devider']) && $_POST['settings-html-devider']>1)
			{
				$this->sawanna->set_option('list_html_widget_devider',ceil($_POST['settings-html-devider']),$this->module_id);
			} else 
			{
				$this->sawanna->set_option('list_html_widget_devider',1,$this->module_id);
			}
		}

        
        return array('[str]Settings saved[/str]');
    }    
}
