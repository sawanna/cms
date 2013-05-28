<?php
defined('SAWANNA') or die();

class CPollAdmin
{
    var $sawanna;
    var $module_id;
    
    function CPollAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('poll');
    }
    
    function adminbar()
    {
        if (!$this->sawanna->has_access('create poll')) return;
            
        return array(
                        array(
                        'poll/create'=>'[str]New widget[/str]',
                        'poll/field-create'=>'[str]New field[/str]',
                        'poll'=>'[str]Widgets[/str]',
                        'poll/results'=>'[str]Results[/str]'
                        ),
                        'poll'
                        );
    }
    
    function admin_area()
    {
        if (!$this->sawanna->has_access('create poll')) return ;
        
        $this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
        
        switch (true)
        {
            case $this->sawanna->arg=='poll/create':
                $this->poll_form();
                break;
            case preg_match('/^poll\/edit\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->poll_form($matches[1]);
                break;
            case preg_match('/^poll\/delete\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->poll_delete($matches[1]);
                break;
            case $this->sawanna->arg=='poll/field-create':
                $this->field_form();
                break;
            case preg_match('/^poll\/field-edit\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->field_form($matches[1]);
                break;
            case preg_match('/^poll\/field-delete\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->field_delete($matches[1]);
                break;
            case $this->sawanna->arg=='poll':
                $this->poll_list();
                break;
            case $this->sawanna->arg=='poll/results':
                $this->results_list();
                break;
            case preg_match('/^poll\/result-delete\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->results_delete($matches[1]);
                break;
            case $this->sawanna->arg=='poll/settings':
                $this->settings_form();
                break;
        }
    }
    
    function optbar()
    {
        return  array(
                        'poll/settings'=>'[str]Poll[/str]'
                        );
    }
    
    function poll_exists($id)
    {
        $this->sawanna->db->query("select * from pre_polls where id='$1'",$id);
        
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return true;
        return false;
    }
    
    function poll_name_exists($id)
    {
        $this->sawanna->db->query("select * from pre_polls where name='$1'",$id);
        
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return true;
        return false;
    }
    
    function get_existing_poll($id)
    {
        $this->sawanna->db->query("select * from pre_polls where id='$1'",$id);
        $row=$this->sawanna->db->fetch_object();
        
        $d=new CData();
        if (!empty($row->meta) && $d->is_serialized($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            
        if (!empty($row)) return $row;
    }
    
    function field_exists($id)
    {
        $this->sawanna->db->query("select * from pre_poll_elements where id='$1'",$id);
       
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return true;
        return false;
    }
    
    function field_name_exists($id)
    {
        $this->sawanna->db->query("select * from pre_poll_elements where name='$1'",$id);
        
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return true;
        return false;
    }
    
    function get_existing_field($id)
    {
        $this->sawanna->db->query("select * from pre_poll_elements where id='$1'",$id);
        $row=$this->sawanna->db->fetch_object();
        
        $d=new CData();
        if (!empty($row->meta) && $d->is_serialized($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            
        if (!empty($row)) return $row;
    }
    
    function valid_field_name($name)
    {
        if (!preg_match('/^[a-zA-Z0-9_-]{3,255}$/',$name)) return false;
        
        return true;
    }
    
    function field_name_check()
    {
        if (!isset($_POST['q'])) return;

        if (!$this->valid_field_name($_POST['q']))
        {
           echo $this->sawanna->locale->get_string('Invalid field name');
           die();
        }
        
        if ($this->field_name_exists($_POST['q']))
        {
           echo $this->sawanna->locale->get_string('Field with such name already exists');
           die();
        }
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
    
    function poll_form($id=0)
    {
        if (!$this->sawanna->has_access('create poll')) 
        {
            trigger_error('Permission denied',E_USER_ERROR);
            return ;
        }
        
        $form=new stdClass();
        
        if (!empty($id) && !is_numeric($id)) 
        {
            trigger_error('Invalid widget ID: '.$id,E_USER_ERROR);
            return;
        }
        if (!empty($id))
        {
            if (!$this->poll_exists($id)) 
            {
                trigger_error('Widget not found',E_USER_ERROR);
                return;    
            }
            
            $form=$this->get_existing_poll($id);
        }
        
        $fields=array();
        
         $fields['poll-tab']=array(
            'type' => 'html',
            'value' => '<h1 class="tab">'.(!isset($form->id) ? '[str]New widget[/str]' : '[str]Edit widget[/str]').'</h1>'
        );
        
        if (empty($id))
        {
            $fields['poll-name']=array(
                'type' => 'text',
                'title' => '[str]Name[/str]',
                'description' => '[str]Specify the widget name here[/str]',
                'autocheck' => array('url'=>'poll-widget-name-check','caller'=>'pollAdminHandler','handler'=>'widget_name_check'),
                'default' => isset($form->name) ? $form->name : '',
                'required' => true
            );
        }

        $widget_titles=array();
        if (!empty($form->meta) && isset($form->meta['name']))
        {
            $widget_titles=$form->meta['name'];
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
                   
                $fields['poll-title-'.$lcode]=array(
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
        
        $widget_questions=array();
        if (!empty($form->meta) && isset($form->meta['question']))
        {
            $widget_questions=$form->meta['question'];
        }
        
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
                if ($co%2===0)
               {
                   $fields['question-block-begin-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '<div class="mark highlighted">'
                   );
               } else 
               {
                    $fields['question-block-begin-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '<div>'
                   );
               }
                   
                $fields['poll-question-'.$lcode]=array(
                    'type' => 'textarea',
                    'title' => '[str]Subject[/str]',
                    'description' => '[strf]Specify poll subject in following language: %'.htmlspecialchars($lname).'%[/strf]',
                    'default' => isset($widget_questions[$lcode]) ? $widget_questions[$lcode] : '',
                    'attributes' => array('rows'=>2,'cols'=>40),
                    'required' => true
                );
                
                 $fields['question-block-end-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '</div>'
                   );
                
                $co++;
        }
        
        $fields['poll-multiple']=array(
            'type' => 'checkbox',
            'title' => '[str]Allow multiple choices[/str]',
            'description' => '[str]Tick to allow multiple choices[/str]',
            'attributes' => !empty($form->meta['multiple']) ? array('checked'=>'checked','class'=>'checkbox') : array('class'=>'checkbox'),
            'required' => false
        );
        
        $fields['poll-type']=array(
            'type' => 'select',
            'title' => '[str]Template[/str]',
            'description' => '[str]Choose widget template[/str]',
            'options' => array(''=>'[str]Without template[/str]','widget'=>'Widget','node'=>'Node'),
            'default' => isset($form->meta['tpl']) ? $form->meta['tpl'] : '',
            'required' => false
        );
        
        if (!empty($id))
        {
            $fields['poll-fields-begin-'.$lcode]=array(
                'type' => 'html',
                'value' => '<h3>[str]Fields[/str]:</h3>'
            );
            
            $co=0;
            $this->sawanna->db->query("select * from pre_poll_elements where pid='$1' order by weight",$id);
            while ($row=$this->sawanna->db->fetch_object()) {
                 if ($co%2===0)
                       {
                           $fields['field-block-begin-'.$row->id]=array(
                                'type' => 'html',
                                'value' => '<div class="mark highlighted fields-list">'
                           );
                       } else 
                       {
                            $fields['field-block-begin-'.$row->id]=array(
                                'type' => 'html',
                                'value' => '<div class="fields-list">'
                           );
                       }
               
                        $html='<div class="id">'.$row->id.'</div>';
                        $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/poll/field-delete/'.$row->id,'[str]Delete[/str]','button',array('onclick'=>'if (!confirm(\'[str]Are you sure ?[/str]\')){return false;}')).'</div>';
                        $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/poll/field-edit/'.$row->id,'[str]Edit[/str]','button').'</div>';
                        $html.='<div class="title">'.htmlspecialchars($row->name).'</div>';

                       $fields['poll-field-'.$row->id]=array(
                            'type' => 'html',
                            'value' =>$html
                        );
                       
                        $fields['field-block-end-'.$co]=array(
                                'type' => 'html',
                                'value' => '</div>'
                           );
                        
                        $co++;
            }
        }
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('poll-form',$fields,'poll_form_process',$this->sawanna->config->admin_area.'/poll',empty($id) ? $this->sawanna->config->admin_area.'/poll/create' : $this->sawanna->config->admin_area.'/poll/edit/'.$id,'','CPollAdmin');
    }
    
    function poll_form_process($id=0)
    {
        if (!$this->sawanna->has_access('create poll')) 
        {
            return array('[str]Permission denied[/str]',array('create-form'));
        }

        if (empty($id) && preg_match('/^poll\/edit\/(.+)$/',$this->sawanna->arg,$matches)) $id=$matches[1];
        
        if (!empty($id) && !is_numeric($id)) return array('[strf]Invalid widget ID: %'.$id.'%[/strf]',array('edit-form'));
        if (!empty($id) && !$this->poll_exists($id)) return array('[str]Widget not found[/str]',array('edit-form'));    
        
        if (empty($id) && !$this->valid_widget_name($_POST['poll-name']))
        {
              return array('[str]Invalid widget name[/str]',array('poll-name'));
        }
        
        if (empty($id) && $this->widget_exists($_POST['poll-name']))
        {
              return array('[str]Widget with such name already exists[/str]',array('poll-name'));
        }
        
        $form=array();
        
        if (!empty($id)) 
        {
            $form['id']=$id;
            $_form=$this->get_existing_poll($id);
            $form['name']=$_form->name;
        }
        
        if (empty($id)) $form['name']=$_POST['poll-name'];
        
        $form['meta']=array('name'=>array(),'question'=>array());
        
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
            if (!empty($_POST['poll-title-'.$lcode])) $form['meta']['name'][$lcode]=htmlspecialchars($_POST['poll-title-'.$lcode]);
            if (!empty($_POST['poll-question-'.$lcode])) $form['meta']['question'][$lcode]=htmlspecialchars($_POST['poll-question-'.$lcode]);
        }
        
        $form['meta']['multiple']=0;
        if (!empty($_POST['poll-multiple']) && $_POST['poll-multiple']=='on')
        {
            $form['meta']['multiple']=1;
        }
        
        $form['meta']['tpl']='';
        if (!empty($_POST['poll-type']))
        {
            $form['meta']['tpl']=$_POST['poll-type']=='node' ? 'node' : 'widget';
        }
        
        if (!$this->set($form))
        {
            return array('[str]Poll widget could not be saved[/str]',array('new-form'));
        }
        
        return array('[str]Poll widget saved[/str]');
    }
    
    function set($form)
    {
        if (empty($form) || !is_array($form)) return false;
        
        if (empty($form['name']))
        {
            trigger_error('Cannot set poll widget. Invalid data given',E_USER_WARNING);
            return false;
        }
        
        if (!isset($form['meta'])) $form['meta']='';
        
        $form['tpl']=(!empty($form['meta']['tpl'])) ? $form['meta']['tpl'] : '';
        
        $d=new CData();
        $form['meta']=$d->do_serialize($form['meta']);
        
        if (isset($form['id']))
        {
            $res=$this->sawanna->db->query("update pre_polls set name='$1', meta='$2' where id='$3'",$form['name'],$form['meta'],$form['id']);
        } else 
        {
            $res=$this->sawanna->db->query("insert into pre_polls (name,meta) values ('$1','$2')",$form['name'],$form['meta']);
        }
        
        if (!$res) return false;
        
        if (empty($form['id']))
        {
            return $this->set_widget($form);
        } else 
            return true;
    }
    
    function set_widget($form)
    {
        $tpl='';
        if (isset($form['tpl']) && $form['tpl']=='widget') $tpl='widget';
     
        $widget=array(
        'name' => $form['name'],
        'title' => '[str]Poll[/str]: '.$form['name'],
        'description' => '[str]Poll widget[/str]',
        'class' => 'CPoll',
        'handler' => 'poll',
        'argument' => $form['name'],
        'path' => '*',
        'component' => $tpl!='widget' ? '' : 'left',
        'permission' => '',
        'weight' => 1,
        'cacheable' => 0,
        'enabled' => $this->sawanna->has_access('manage widgets') ? 1 : 0,
        'fix' => 0
        );
        
        
        $widget['module']=$this->module_id;
        
        return $this->sawanna->module->set($widget,'widget');
    }
    
    function delete($id)
    {
        if (empty($id)) return false;

        $form=$this->get_existing_poll($id);
        
        if ($this->sawanna->db->query("delete from pre_polls where id='$1'",$id))
        {
            $this->sawanna->module->delete($form->name,'widget');
            return true;
        }
        
        return false;
    }
    
    function poll_delete($id)
    {
        if (!$this->sawanna->has_access('create poll')) 
        {
            trigger_error('Could not delete poll widget. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not delete poll widget. Invalid widget ID: '.$id,E_USER_WARNING);
            return ;
        }
        if (!$this->poll_exists($id)) 
        {
            trigger_error('Could not delete poll widget. Widget not found',E_USER_WARNING);    
            return;
        }
        
        $event=array();
        
        if (!$this->delete($id))
        {
            $message='[str]Could not delete poll widget. Database error[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
            $this->sawanna->db->query("delete from pre_poll_elements where pid='$1'",$id);
            
            $message='[strf]Poll widget with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
            $event['job']='sawanna_message';
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);
        
        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/poll');
    }
    
    function poll_list()
    {
        if (!$this->sawanna->has_access('create poll')) 
        {
            trigger_error('Permission denied',E_USER_WARNING);
            return ;
        }
        
        $html='<h1 class="tab">[str]Widgets[/str]</h1>';
        $html.='<div id="poll-list">';
        
        $html.='<div class="poll-list-item head">';
        $html.='<div class="id">[str]ID[/str]</div>';
        $html.='<div class="title">[str]Widget[/str]</div>';
        $html.='</div>';
            
        $co=0;
        $this->sawanna->db->query("select * from pre_polls order by id");
        while($row=$this->sawanna->db->fetch_object())
        {
           if ($co%2===0)
           {
               $html.='<div class="poll-list-item">';
           } else 
           {
               $html.='<div class="poll-list-item mark highlighted">';
           }
           
            $html.='<div class="id">'.$row->id.'</div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/poll/delete/'.$row->id,'[str]Delete[/str]','button',array('onclick'=>'if (!confirm(\'[str]Are you sure ?[/str]\')){return false;}')).'</div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/poll/edit/'.$row->id,'[str]Edit[/str]','button').'</div>';
            $html.='<div class="title">'.htmlspecialchars($row->name).'</div>';
            $html.='</div>';
            
            $co++;
        }
        
        $html.='</div>';
        
        echo $html;
    }
    
    function field_form($id=0)
    {
        if (!$this->sawanna->has_access('create poll')) 
        {
            trigger_error('Permission denied',E_USER_ERROR);
            return ;
        }
        
        $field=new stdClass();
        
        if (!empty($id) && !is_numeric($id)) 
        {
            trigger_error('Invalid field ID: '.$id,E_USER_ERROR);
            return;
        }
        if (!empty($id))
        {
            if (!$this->field_exists($id)) 
            {
                trigger_error('Field not found',E_USER_ERROR);
                return;    
            }
            
            $field=$this->get_existing_field($id);
        }
        
        $fields=array();
        
         $fields['field-tab']=array(
            'type' => 'html',
            'value' => '<h1 class="tab">'.(!isset($field->id) ? '[str]New choice field[/str]' : '[str]Edit choice field[/str]').'</h1>'
        );
        
        $forms=array(0=>'[str]Please choose[/str]') + $this->get_polls();
        
        if (empty($id))
        {
            $fields['field-poll']=array(
                'type' => 'select',
                'title' => '[str]Poll[/str]',
                'options' => $forms,
                'description' => '[str]Choose poll widget[/str]',
                'default' => isset($field->pid) ? $field->pid : '',
                'required' => true
            );
            
            $fields['field-name']=array(
                'type' => 'text',
                'title' => '[str]Name[/str]',
                'description' => '[str]Specify the field name here[/str]',
                'default' => isset($field->name) ? $field->name : '',
                'autocheck' => array('url'=>'poll-field-name-check','caller'=>'pollAdminHandler','handler'=>'field_name_check'),
                'required' => true
            );
        }
        
        $widget_titles=array();
        if (!empty($field->meta) && isset($field->meta['name']))
        {
            $widget_titles=$field->meta['name'];
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
                   
                $fields['field-title-'.$lcode]=array(
                    'type' => 'text',
                    'title' => '[str]Field title[/str]',
                    'description' => '[strf]Specify choice field title in following language: %'.htmlspecialchars($lname).'%[/strf]',
                    'default' => isset($widget_titles[$lcode]) ? $widget_titles[$lcode] : '',
                    'required' => true
                );
                
                 $fields['title-block-end-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '</div>'
                   );
                
                $co++;
        }
        
        $middle_weight=isset($field->weight) ? $field->weight : 0;
        
        $fields['field-weight']=array(
            'type' => 'select',
            'options' => array_combine(range($middle_weight-30,$middle_weight+30,1),range($middle_weight-30,$middle_weight+30,1)),
            'title' => '[str]Weight[/str]',
            'description' => '[str]Lighter fields appears first[/str]',
            'default' => isset($field->weight) ? $field->weight: '0',
            'attributes' => array('class'=>'slider'),
            'required' => false
        );
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('field-form',$fields,'field_form_process',empty($field->pid) ? $this->sawanna->config->admin_area.'/poll' : $this->sawanna->config->admin_area.'/poll/edit/'.$field->pid,empty($id) ? $this->sawanna->config->admin_area.'/poll/field-create' : $this->sawanna->config->admin_area.'/poll/field-edit/'.$id,'','CPollAdmin');
    }
    
    function field_form_process($id=0)
    {
        if (!$this->sawanna->has_access('create poll')) 
        {
            return array('[str]Permission denied[/str]',array('create-field'));
        }

        if (empty($id) && preg_match('/^poll\/field-edit\/(.+)$/',$this->sawanna->arg,$matches)) $id=$matches[1];
        
        if (!empty($id) && !is_numeric($id)) return array('[strf]Invalid field ID: %'.$id.'%[/strf]',array('edit-field'));
        if (!empty($id) && !$this->field_exists($id)) return array('[str]Field not found[/str]',array('edit-field'));    
      
        if (empty($id) && !$this->get_existing_poll($_POST['field-poll']))
        {
              return array('[str]Widget not found[/str]',array('field-poll'));
        }
        
        if (empty($id) && !$this->valid_field_name($_POST['field-name']))
        {
              return array('[str]Invalid field name[/str]',array('field-name'));
        }
        
        if (empty($id) && $this->field_name_exists($_POST['field-name']))
        {
              return array('[str]Field with such name already exists[/str]',array('field-name'));
        }
        
        if (!empty($_POST['field-weight']) && !is_numeric($_POST['field-weight']))
        {
            return array('[str]Invalid field weight[/str]',array('field-weight'));
        }
         
        $field=array();
        
        if (!empty($id)) 
        {
            $field['id']=$id;
            $_field=$this->get_existing_field($id);
            $field['name']=$_field->name;
            $field['pid']=$_field->pid;
        }
        
        if (empty($id)) $field['pid']=$_POST['field-poll'];
        if (empty($id)) $field['name']=$_POST['field-name'];
        
        $field['weight']=!empty($_POST['field-weight']) ? floor($_POST['field-weight']) : 0;
        
        $field['meta']=array('name'=>array());
        
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
            if (!empty($_POST['field-title-'.$lcode])) $field['meta']['name'][$lcode]=htmlspecialchars($_POST['field-title-'.$lcode]);
        }
        
        if (!$this->set_field($field))
        {
            return array('[str]Choice field could not be saved[/str]',array('new-field'));
        }
        
        return array('[str]Choice field saved[/str]');
    }
    
    function set_field($field)
    {
        if (empty($field) || !is_array($field)) return false;
        
        if (empty($field['pid']) || empty($field['name']))
        {
            trigger_error('Cannot set poll field. Invalid data given',E_USER_WARNING);
            return false;
        }
        
        if (!isset($field['weight'])) $field['weight']=0;
        if (!isset($field['meta'])) $field['meta']='';
       
        $d=new CData();
        $field['meta']=$d->do_serialize($field['meta']);
        
        if (isset($field['id']))
        {
            return $this->sawanna->db->query("update pre_poll_elements set pid='$1', name='$2', meta='$3', weight='$4' where id='$5'",$field['pid'],$field['name'],$field['meta'],$field['weight'],$field['id']);
        } else 
        {
            return $this->sawanna->db->query("insert into pre_poll_elements (pid,name,meta,weight) values ('$1','$2','$3','$4')",$field['pid'],$field['name'],$field['meta'],$field['weight']);
        }
    }
    
    function delete_field($id)
    {
        if (empty($id)) return false;

        if ($this->sawanna->db->query("delete from pre_poll_elements where id='$1'",$id))
        {
            return true;
        }
        
        return false;
    }
    
    function field_delete($id)
    {
        if (!$this->sawanna->has_access('create poll')) 
        {
            trigger_error('Could not delete poll field. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not delete poll field. Invalid widget ID: '.$id,E_USER_WARNING);
            return ;
        }
        if (!$this->field_exists($id)) 
        {
            trigger_error('Could not delete poll field. Field not found',E_USER_WARNING);    
            return;
        }
        
        $field=$this->get_existing_field($id);
        
        $event=array();
        
        if (!$this->delete_field($id))
        {
            $message='[str]Could not delete choice field. Database error[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
            $message='[strf]Choice field with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
            $event['job']='sawanna_message';
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);

        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/poll/edit/'.$field->pid);
    }
    
    function get_polls()
    {
        $forms=array();
        $d=new CData();
        
        $this->sawanna->db->query("select * from pre_polls order by id");
        while ($row=$this->sawanna->db->fetch_object())
        {
            if (!empty($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            $forms[$row->id]=isset($row->meta['name'][$this->sawanna->locale->current]) ? $row->meta['name'][$this->sawanna->locale->current] : $row->name;
        }
        
        return $forms;
    }
    
     function results_list($arg=0)
    {
        if (!$this->sawanna->has_access('create poll')) 
        {
            trigger_error('Permission denied',E_USER_WARNING);
            return ;
        }
        
        $d=new CData();
        
        $html='<h1 class="tab">[str]Polls results[/str]</h1>';
        $html.='<div id="results-list">';
        
        $html.='<div class="results-list-item head">';
        $html.='<div class="id">ID</div>';
        $html.='<div class="title">[str]Result[/str]</div>';
        $html.='</div>';
            
        $co=0;
        $res=$this->sawanna->db->query("select * from pre_poll_results order by pid");
        while ($row=$this->sawanna->db->fetch_object($res))
        {
            $result='';
            
            if ($co%2===0)
            {
                $html.='<div class="results-list-item">';
            } else
            {
                $html.='<div class="results-list-item mark highlighted">';
            }
            
            if (!empty($row->data)) $row->data=$d->do_unserialize($row->data);
            if (!empty($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            
            $poll='-';
            if (!empty($row->meta) && is_array($row->meta) && isset($row->meta['poll']))
            {
                $poll=$row->meta['poll'];
            }
            
            $fields=array();
            
            $pres=$this->sawanna->db->query("select p.meta as poll,f.meta as field, f.name from pre_poll_elements f left join pre_polls p on f.pid=p.id where p.id='$1'",$row->pid);
            while ($prow=$this->sawanna->db->fetch_object($pres))
            {
                if (!empty($prow->poll)) $prow->poll=$d->do_unserialize($prow->poll);
                if (!empty($prow->field)) $prow->field=$d->do_unserialize($prow->field);
                
                if (isset($prow->poll['question'][$this->sawanna->locale->current])) $poll=$prow->poll['question'][$this->sawanna->locale->current];
                if (isset($prow->field['name'][$this->sawanna->locale->current])) $fields[$prow->name]=$prow->field['name'][$this->sawanna->locale->current];
            }
            
            $result.='<div class="poll">[str]Poll[/str]: '.htmlspecialchars($poll).'</div>';

            if (!empty($row->data) && is_array($row->data))
            {
                foreach($row->data as $field=>$value)
                {
                    $elem=isset($fields[$field]) ? $fields[$field] : $field;
                    $result.='<div class="field"><label>'.htmlspecialchars($elem).': </label>'.htmlspecialchars($value).'</div>';
                }
            } else
            {
                $result.='<div class="field">[str]No results[/str]</div>';
            }
            
            $html.='<div class="id">'.$row->pid.'</div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/poll/result-delete/'.$row->id,'[str]Delete[/str]','button',array('onclick'=>'if (!confirm(\'[str]Are you sure ?[/str]\')){return false;}')).'</div>';
            $html.='<div class="title">'.$result.'</div>';
            $html.='</div>';
            
            $co++;
        }

        $html.='</div>';
    
        echo $html;
    }
    
    function delete_result($id)
    {
        if (empty($id)) return false;

        if ($this->sawanna->db->query("delete from pre_poll_results where id='$1'",$id))
        {
            return true;
        }
        
        return false;
    }
    
    function results_delete($id)
    {
        if (!$this->sawanna->has_access('create poll')) 
        {
            trigger_error('Could not delete poll result. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not delete poll result. Invalid result ID: '.$id,E_USER_WARNING);
            return ;
        }
       
        $event=array();
        
        if (!$this->delete_result($id))
        {
            $message='[str]Could not delete poll result. Database error[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
            $message='[strf]Poll result with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
            $event['job']='sawanna_message';
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);
        
        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/poll/results');
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
            'value' => '<h1 class="tab">[str]Poll settings[/str]</h1>'
        );
        
        $ajax=$this->sawanna->get_option('poll_ajax');
        
        $fields['settings-ajax']=array(
            'type' => 'checkbox',
            'title' => '[str]Use AJAX[/str]',
            'description' => '[str]Polling will be processed without page reloading, if enabled[/str]',
            'attributes' => $ajax ? array('checked'=>'checked') : array()
        );
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('settings-form',$fields,'settings_form_process',$this->sawanna->config->admin_area.'/poll/settings',$this->sawanna->config->admin_area.'/poll/settings','','CPollAdmin');
    }
    
    function settings_form_process()
    {
        if (!$this->sawanna->has_access('su')) 
        {
            return array('[str]Permission denied[/str]',array('poll-settings'));
        }
        
         if (isset($_POST['settings-ajax']) && $_POST['settings-ajax']=='on')
        {
            $this->sawanna->set_option('poll_ajax',1,$this->module_id);
        } else 
        {
            $this->sawanna->set_option('poll_ajax',0,$this->module_id);
        }
        
        return array('[str]Settings saved[/str]');
    }   
}
