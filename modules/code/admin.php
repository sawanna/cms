<?php
defined('SAWANNA') or die();

class CCodeAdmin
{
    var $sawanna;
    var $module_id;
    
    function CCodeAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('code');
    }
    
    function adminbar()
    {
        if (!$this->sawanna->has_access('create code')) return;
            
        return array(
                        array(
                        'code/create'=>'[str]New widget[/str]',
                        'code'=>'[str]Widgets[/str]'
                        ),
                        'code'
                        );
    }
    
    function admin_area()
    {
        if (!$this->sawanna->has_access('create code')) return ;
        
        $this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
        
        switch (true)
        {
            case $this->sawanna->arg=='code/create':
                $this->code_form();
                break;
            case preg_match('/^code\/edit\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->code_form($matches[1]);
                break;
            case preg_match('/^code\/delete\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->code_delete($matches[1]);
                break;
            case $this->sawanna->arg=='code':
                $this->code_list();
                break;
        }
    }
    
    function code_exists($id)
    {
        $this->sawanna->db->query("select * from pre_cwidgets where id='$1'",$id);
        
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return true;
        return false;
    }
    
    function get_existing_code($id)
    {
        $this->sawanna->db->query("select * from pre_cwidgets where id='$1'",$id);
        $row=$this->sawanna->db->fetch_object();
        
        $d=new CData();
        if (!empty($row->meta) && $d->is_serialized($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            
        if (!empty($row)) return $row;
    }
    
    function valid_widget_name($name)
    {
        if (!preg_match('/^[a-zA-Z0-9]{3,255}$/',$name)) return false;
        
        return true;
    }
    
    function widget_exists($name)
    {
        $this->sawanna->db->query("select * from pre_widgets where name='$1'",$name);
        
        return $this->sawanna->db->num_rows();
    }
    
    function widget_name_check()
    {
        if (!isset($_POST['q'])) return;

        if (!$this->valid_widget_name($_POST['q']))
        {
           echo $this->sawanna->locale->get_string('Invalid widget name');
           die();
        }
        
        if ($this->widget_exists($_POST['q']))
        {
           echo $this->sawanna->locale->get_string('Widget with such name already exists');
           die();
        }
    }
    
    function code_form($id=0)
    {
        if (!$this->sawanna->has_access('create code')) 
        {
            trigger_error('Permission denied',E_USER_ERROR);
            return ;
        }
        
        $code=new stdClass();
        
        if (!empty($id) && !is_numeric($id)) 
        {
            trigger_error('Invalid widget ID: '.$id,E_USER_ERROR);
            return;
        }
        if (!empty($id))
        {
            if (!$this->code_exists($id)) 
            {
                trigger_error('Widget not found',E_USER_ERROR);
                return;    
            }
            
            $code=$this->get_existing_code($id);
        }
        
        $fields=array();
        
         $fields['code-tab']=array(
            'type' => 'html',
            'value' => '<h1 class="tab">'.(!isset($code->id) ? '[str]New widget[/str]' : '[str]Edit widget[/str]').'</h1>'
        );
        
        if (empty($id))
        {
            $fields['code-name']=array(
                'type' => 'text',
                'title' => '[str]Name[/str]',
                'description' => '[str]Specify the widget name here[/str]',
                'autocheck' => array('url'=>'code-widget-name-check','caller'=>'codeAdminHandler','handler'=>'widget_name_check'),
                'default' => isset($code->name) ? $code->name : '',
                'required' => true
            );
        }

        $widget_titles=array();
        if (!empty($code->meta) && isset($code->meta['name']))
        {
            $widget_titles=$code->meta['name'];
        }
        
        $co=0;
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
                if ($co%2===0)
               {
                   $fields['title-block-begin-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '<div class="mark highlighted">'
                   );
               } else 
               {
                    $fields['title-block-begin-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '<div>'
                   );
               }
                   
                $fields['code-title-'.$lcode]=array(
                    'type' => 'text',
                    'title' => '[str]Title[/str]',
                    'description' => '[strf]Specify widget title in following language: %'.htmlspecialchars($lname).'%[/strf]',
                    'default' => isset($widget_titles[$lcode]) ? $widget_titles[$lcode] : '',
                    'required' => false
                );
                
                 $fields['title-block-end-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '</div>'
                   );
                
                $co++;
        }
        
        $extra=$this->sawanna->form->get_form_extra_fields('code_create_form_pre_fields',isset($code->id) ? $code->id : 0);
        
        if (!empty($extra))
        {
            $fields=array_merge($fields,$extra);
        }
        
        $fields['code-content']=array(
            'type' => 'textarea',
            'title' => '[str]Content[/str]',
            'description' => '[str]Enter HTML code here[/str]',
            'default' => isset($code->content) ? $code->content : '',
            'attributes' => array('style'=>'width: 100%','cols'=>40,'rows'=>20),
            'required' => false
        );
        
        $extra=$this->sawanna->form->get_form_extra_fields('code_create_form_post_fields',isset($code->id) ? $code->id : 0);
        
        if (!empty($extra))
        {
            $fields=array_merge($fields,$extra);
        }

        $fields['code-type']=array(
            'type' => 'select',
            'title' => '[str]Template[/str]',
            'description' => '[str]Choose widget template[/str]',
            'options' => array(''=>'[str]Without template[/str]','widget'=>'Widget','node'=>'Node'),
            'default' => isset($code->meta['tpl']) ? $code->meta['tpl'] : '',
            'required' => false
        );

        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('cwidgets-form',$fields,'code_form_process',$this->sawanna->config->admin_area.'/code',empty($id) ? $this->sawanna->config->admin_area.'/code/create' : $this->sawanna->config->admin_area.'/code/edit/'.$id,'','CCodeAdmin');
    }
    
    function code_form_process($id=0)
    {
        if (!$this->sawanna->has_access('create code')) 
        {
            return array('[str]Permission denied[/str]',array('create-code'));
        }

        if (empty($id) && preg_match('/^code\/edit\/(.+)$/',$this->sawanna->arg,$matches)) $id=$matches[1];
        
        if (!empty($id) && !is_numeric($id)) return array('[strf]Invalid widget ID: %'.$id.'%[/strf]',array('edit-code'));
        if (!empty($id) && !$this->code_exists($id)) return array('[str]Widget not found[/str]',array('edit-code'));    
        
        if (empty($id) && !$this->valid_widget_name($_POST['code-name']))
        {
              return array('[str]Invalid widget name[/str]',array('code-name'));
        }
        
        if (empty($id) && $this->widget_exists($_POST['code-name']))
        {
              return array('[str]Widget with such name already exists[/str]',array('code-name'));
        }
        
        $code=array();
        
        if (!empty($id)) 
        {
            $code['id']=$id;
            $_code=$this->get_existing_code($id);
            $code['name']=$_code->name;
        }
        
        if (empty($id)) $code['name']=$_POST['code-name'];
        
        $code['meta']=array('name'=>array());
        
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
            if (!empty($_POST['code-title-'.$lcode])) $code['meta']['name'][$lcode]=htmlspecialchars($_POST['code-title-'.$lcode]);
        }
        
        if (!empty($_POST['code-content'])) $code['content']=$_POST['code-content'];
        
        $code['meta']['tpl']='';
        if (!empty($_POST['code-type']))
        {
            $code['meta']['tpl']=$_POST['code-type']=='node' ? 'node' : 'widget';
        }
        
        if (!$this->set($code))
        {
            return array('[str]HTML widget could not be saved[/str]',array('new-code'));
        }
        
        // clearing cache
        $this->sawanna->cache->clear_all_by_type('code');
        
        return array('[str]HTML widget saved[/str]');
    }
    
    function set($code)
    {
        if (empty($code) || !is_array($code)) return false;
        
        if (empty($code['name']))
        {
            trigger_error('Cannot set HTML widget. Invalid data given',E_USER_WARNING);
            return false;
        }
        
        if (!isset($code['content'])) $code['content']='';
        if (!isset($code['meta'])) $code['meta']='';
        
        $code['tpl']=(!empty($code['meta']['tpl'])) ? $code['meta']['tpl'] : '';
        
        $d=new CData();
        $code['meta']=$d->do_serialize($code['meta']);
        
        if (isset($code['id']))
        {
            $res=$this->sawanna->db->query("update pre_cwidgets set name='$1', content='$2', meta='$3' where id='$4'",$code['name'],$code['content'],$code['meta'],$code['id']);
        } else 
        {
            $res=$this->sawanna->db->query("insert into pre_cwidgets (name,content,meta) values ('$1','$2','$3')",$code['name'],$code['content'],$code['meta']);
        }
        
        if (!$res) return false;
        
        if (empty($code['id']))
            return $this->set_widget($code);
        else 
            return true;
    }
    
    function set_widget($code)
    {
        $tpl='';
        if (isset($code['tpl']) && $code['tpl']=='node') $tpl='node';
     
        $widget=array(
        'name' => $code['name'],
        'title' => '[str]HTML[/str]: '.$code['name'],
        'description' => '[str]HTML widget[/str]',
        'class' => 'CCode',
        'handler' => 'code',
        'argument' => $code['name'],
        'path' => '*',
        'component' => $tpl=='node' ? '' : 'left',
        'permission' => '',
        'weight' => 1,
        'cacheable' => 1,
        'enabled' => $this->sawanna->has_access('manage widgets') ? 1 : 0,
        'fix' => 0
        );
        
        
        $widget['module']=$this->module_id;
        
        return $this->sawanna->module->set($widget,'widget');
    }
    
    function delete($id)
    {
        if (empty($id)) return false;

        $code=$this->get_existing_code($id);
        
        if ($this->sawanna->db->query("delete from pre_cwidgets where id='$1'",$id))
        {
            $this->sawanna->module->delete($code->name,'widget');
            return true;
        }
        
        return false;
    }
    
    function code_delete($id)
    {
        if (!$this->sawanna->has_access('create code')) 
        {
            trigger_error('Could not delete HTML widget. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not delete HTML widget. Invalid widget ID: '.$id,E_USER_WARNING);
            return ;
        }
        if (!$this->code_exists($id)) 
        {
            trigger_error('Could not delete HTML widget. Widget not found',E_USER_WARNING);    
            return;
        }
        
        $event=array();
        
        if (!$this->delete($id))
        {
            $message='[str]Could not delete HTML widget. Database error[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
            $message='[strf]HTML widget with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
            $event['job']='sawanna_message';
            
            // clearing cache
            $this->sawanna->cache->clear_all_by_type('code');
            
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);
        
        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/code');
    }
    
    function code_list()
    {
        if (!$this->sawanna->has_access('create code')) 
        {
            trigger_error('Permission denied',E_USER_WARNING);
            return ;
        }
        
        $html='<h1 class="tab">[str]Widgets[/str]</h1>';
        $html.='<div id="cwidgets-list">';
        
        $html.='<div class="cwidgets-list-item head">';
        $html.='<div class="id">[str]ID[/str]</div>';
        $html.='<div class="title">[str]Widget[/str]</div>';
        $html.='</div>';
            
        $co=0;
        $this->sawanna->db->query("select * from pre_cwidgets order by id");
        while($row=$this->sawanna->db->fetch_object())
        {
           if ($co%2===0)
           {
               $html.='<div class="cwidgets-list-item">';
           } else 
           {
               $html.='<div class="cwidgets-list-item mark highlighted">';
           }
           
            $html.='<div class="id">'.$row->id.'</div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/code/delete/'.$row->id,'[str]Delete[/str]','button',array('onclick'=>'if (!confirm(\'[str]Are you sure ?[/str]\')){return false;}')).'</div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/code/edit/'.$row->id,'[str]Edit[/str]','button').'</div>';
            $html.='<div class="title">'.htmlspecialchars($row->name).'</div>';
            $html.='</div>';
            
            $co++;
        }
        
        $html.='</div>';
        
        echo $html;
    }
}
