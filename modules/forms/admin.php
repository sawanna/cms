<?php
defined('SAWANNA') or die();

class CFormsAdmin
{
    var $sawanna;
    var $module_id;
    var $limit;
    var $count;
    var $start;
    
    function CFormsAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('forms');
        $this->count=0;
        $this->start=0;
        $this->limit=10;
    }
    
    function adminbar()
    {
        if (!$this->sawanna->has_access('create forms')) return;
            
        return array(
                        array(
                        'forms/create'=>'[str]New widget[/str]',
                        'forms/field-create'=>'[str]New field[/str]',
                        'forms'=>'[str]Widgets[/str]',
                        'forms/results'=>'[str]Results[/str]'
                        ),
                        'forms'
                        );
    }
    
    function admin_area()
    {
        if (!$this->sawanna->has_access('create forms')) return ;
        
        $this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
        
        switch (true)
        {
            case $this->sawanna->arg=='forms/create':
                $this->forms_form();
                break;
            case preg_match('/^forms\/edit\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->forms_form($matches[1]);
                break;
            case preg_match('/^forms\/delete\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->forms_delete($matches[1]);
                break;
            case $this->sawanna->arg=='forms/field-create':
                $this->field_form();
                break;
            case preg_match('/^forms\/field-edit\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->field_form($matches[1]);
                break;
            case preg_match('/^forms\/field-delete\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->field_delete($matches[1]);
                break;
            case $this->sawanna->arg=='forms':
                $this->forms_list();
                break;
            case $this->sawanna->arg=='forms/results':
                $this->results_list('all');
                break;
            case preg_match('/^forms\/results\/([\d]+)$/',$this->sawanna->arg,$matches):
                if ($matches[1]>0) $this->start=floor($matches[1]);
                $this->results_list('all');
                break;
            case preg_match('/^forms\/results\/([a-zA-Z0-9_-]+)$/',$this->sawanna->arg,$matches) && ($this->form_name_exists($matches[1]) || $matches[1]=='all'):
                $this->results_list($matches[1]);
                break;
            case preg_match('/^forms\/results\/([a-zA-Z0-9_-]+)\/([\d]+)$/',$this->sawanna->arg,$matches) && ($this->form_name_exists($matches[1]) || $matches[1]=='all'):
                if ($matches[2]>0) $this->start=floor($matches[2]);
                $this->results_list($matches[1]);
                break;
            case preg_match('/^forms\/result-delete\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->results_delete($matches[1]);
                break;
            case preg_match('/^forms\/results-bulkdel$/',$this->sawanna->arg,$matches):
                $this->bulk_results_delete();
                break;
        }
    }
    
    function form_exists($id)
    {
        $this->sawanna->db->query("select * from pre_cforms where id='$1'",$id);
        
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return true;
        return false;
    }
    
    function form_name_exists($id)
    {
        $this->sawanna->db->query("select * from pre_cforms where name='$1'",$id);
        
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return true;
        return false;
    }
    
    function get_existing_form($id)
    {
        $this->sawanna->db->query("select * from pre_cforms where id='$1'",$id);
        $row=$this->sawanna->db->fetch_object();
        
        $d=new CData();
        if (!empty($row->meta) && $d->is_serialized($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            
        if (!empty($row)) return $row;
    }
    
    function field_exists($id)
    {
        $this->sawanna->db->query("select * from pre_cform_elements where id='$1'",$id);
       
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return true;
        return false;
    }
    
    function field_name_exists($id)
    {
        $this->sawanna->db->query("select * from pre_cform_elements where name='$1'",$id);
        
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return true;
        return false;
    }
    
    function get_existing_field($id)
    {
        $this->sawanna->db->query("select * from pre_cform_elements where id='$1'",$id);
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
    
    function forms_form($id=0)
    {
        if (!$this->sawanna->has_access('create forms')) 
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
            if (!$this->form_exists($id)) 
            {
                trigger_error('Widget not found',E_USER_ERROR);
                return;    
            }
            
            $form=$this->get_existing_form($id);
        }
        
        $fields=array();
        
         $fields['forms-tab']=array(
            'type' => 'html',
            'value' => '<h1 class="tab">'.(!isset($form->id) ? '[str]New widget[/str]' : '[str]Edit widget[/str]').'</h1>'
        );
        
        if (empty($id))
        {
            $fields['form-name']=array(
                'type' => 'text',
                'title' => '[str]Name[/str]',
                'description' => '[str]Specify the widget name here[/str]',
                'autocheck' => array('url'=>'forms-widget-name-check','caller'=>'formsAdminHandler','handler'=>'widget_name_check'),
                'default' => isset($form->name) ? $form->name : '',
                'required' => true
            );
        }
        
        $fields['form-email']=array(
            'type' => 'email',
            'title' => '[str]E-Mail[/str]',
            'description' => '[str]If you wish to recieve form submissions to your E-Mail, specify its address here[/str]',
            'default' => isset($form->meta['email']) ? $form->meta['email'] : '',
            'required' => false
        );

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
                   
                $fields['form-title-'.$lcode]=array(
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
        
        $widget_messages=array();
        if (!empty($form->meta) && isset($form->meta['message']))
        {
            $widget_messages=$form->meta['message'];
        }
        
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
                if ($co%2===0)
               {
                   $fields['mess-block-begin-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '<div class="mark highlighted">'
                   );
               } else 
               {
                    $fields['mess-block-begin-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '<div>'
                   );
               }
                   
                $fields['form-message-'.$lcode]=array(
                    'type' => 'text',
                    'title' => '[str]Message[/str]',
                    'description' => '[strf]Specify the message that should be shown after form submission in following language: %'.htmlspecialchars($lname).'%[/strf]',
                    'default' => isset($widget_messages[$lcode]) ? $widget_messages[$lcode] : '',
                    'attributes' => array('style' => 'width: 100%'),
                    'required' => false
                );
                
                 $fields['mess-block-end-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '</div>'
                   );
                
                $co++;
        }
        
        $fields['form-type']=array(
            'type' => 'select',
            'title' => '[str]Template[/str]',
            'description' => '[str]Choose widget template[/str]',
            'options' => array(''=>'[str]Without template[/str]','widget'=>'Widget','node'=>'Node'),
            'default' => isset($form->meta['tpl']) ? $form->meta['tpl'] : '',
            'required' => false
        );
        
        if (!empty($id))
        {
            $fields['form-fields-begin-'.$lcode]=array(
                'type' => 'html',
                'value' => '<h3>[str]Fields[/str]:</h3>'
            );
            
            $co=0;
            $this->sawanna->db->query("select * from pre_cform_elements where fid='$1' order by weight",$id);
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
                $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/forms/field-delete/'.$row->id,'[str]Delete[/str]','button',array('onclick'=>'if (!confirm(\'[str]Are you sure ?[/str]\')){return false;}')).'</div>';
                $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/forms/field-edit/'.$row->id,'[str]Edit[/str]','button').'</div>';
                $html.='<div class="title">'.htmlspecialchars($row->name).'</div>';
            
                       $fields['form-field-'.$row->id]=array(
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

        echo $this->sawanna->constructor->form('forms-form',$fields,'forms_form_process',$this->sawanna->config->admin_area.'/forms',empty($id) ? $this->sawanna->config->admin_area.'/forms/create' : $this->sawanna->config->admin_area.'/forms/edit/'.$id,'','CFormsAdmin');
    }
    
    function forms_form_process($id=0)
    {
        if (!$this->sawanna->has_access('create forms')) 
        {
            return array('[str]Permission denied[/str]',array('create-form'));
        }

        if (empty($id) && preg_match('/^forms\/edit\/(.+)$/',$this->sawanna->arg,$matches)) $id=$matches[1];
        
        if (!empty($id) && !is_numeric($id)) return array('[strf]Invalid widget ID: %'.$id.'%[/strf]',array('edit-form'));
        if (!empty($id) && !$this->form_exists($id)) return array('[str]Widget not found[/str]',array('edit-form'));    
        
        if (empty($id) && !$this->valid_widget_name($_POST['form-name']))
        {
              return array('[str]Invalid widget name[/str]',array('form-name'));
        }
        
        if (empty($id) && $this->widget_exists($_POST['form-name']))
        {
              return array('[str]Widget with such name already exists[/str]',array('form-name'));
        }
        
        $form=array();
        
        if (!empty($id)) 
        {
            $form['id']=$id;
            $_form=$this->get_existing_form($id);
            $form['name']=$_form->name;
        }
        
        if (empty($id)) $form['name']=$_POST['form-name'];
        
        $form['meta']=array('name'=>array(),'message'=>array());
        
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
            if (!empty($_POST['form-title-'.$lcode])) $form['meta']['name'][$lcode]=htmlspecialchars($_POST['form-title-'.$lcode]);
            if (!empty($_POST['form-message-'.$lcode])) $form['meta']['message'][$lcode]=htmlspecialchars($_POST['form-message-'.$lcode]);
        }
        
        $form['meta']['tpl']='';
        if (!empty($_POST['form-type']))
        {
            $form['meta']['tpl']=$_POST['form-type']=='node' ? 'node' : 'widget';
        }
        
        $form['meta']['email']='';
        if (!empty($_POST['form-email']))
        {
            $form['meta']['email']=$_POST['form-email'];
        }
        
        if (!$this->set($form))
        {
            return array('[str]Form widget could not be saved[/str]',array('new-form'));
        }
        
        return array('[str]Form widget saved[/str]');
    }
    
    function set($form)
    {
        if (empty($form) || !is_array($form)) return false;
        
        if (empty($form['name']))
        {
            trigger_error('Cannot set form widget. Invalid data given',E_USER_WARNING);
            return false;
        }
        
        if (!isset($form['meta'])) $form['meta']='';
        
        $form['tpl']=(!empty($form['meta']['tpl'])) ? $form['meta']['tpl'] : '';
        
        $d=new CData();
        $form['meta']=$d->do_serialize($form['meta']);
        
        if (isset($form['id']))
        {
            $res=$this->sawanna->db->query("update pre_cforms set name='$1', meta='$2' where id='$3'",$form['name'],$form['meta'],$form['id']);
        } else 
        {
            $res=$this->sawanna->db->query("insert into pre_cforms (name,meta) values ('$1','$2')",$form['name'],$form['meta']);
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
        'title' => '[str]Form[/str]: '.$form['name'],
        'description' => '[str]Form widget[/str]',
        'class' => 'CForms',
        'handler' => 'form',
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

        $form=$this->get_existing_form($id);
        
        if ($this->sawanna->db->query("delete from pre_cforms where id='$1'",$id))
        {
            $this->sawanna->module->delete($form->name,'widget');
            return true;
        }
        
        return false;
    }
    
    function forms_delete($id)
    {
        if (!$this->sawanna->has_access('create forms')) 
        {
            trigger_error('Could not delete form widget. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not delete form widget. Invalid widget ID: '.$id,E_USER_WARNING);
            return ;
        }
        if (!$this->form_exists($id)) 
        {
            trigger_error('Could not delete form widget. Widget not found',E_USER_WARNING);    
            return;
        }
        
        $event=array();
        
        if (!$this->delete($id))
        {
            $message='[str]Could not delete form widget. Database error[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
            $this->sawanna->db->query("delete from pre_cform_elements where fid='$1'",$id);
            
            $message='[strf]Form widget with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
            $event['job']='sawanna_message';
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);
        
        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/forms');
    }
    
    function forms_list()
    {
        if (!$this->sawanna->has_access('create forms')) 
        {
            trigger_error('Permission denied',E_USER_WARNING);
            return ;
        }
        
        $html='<h1 class="tab">[str]Widgets[/str]</h1>';
        $html.='<div id="forms-list">';
        
        $html.='<div class="forms-list-item head">';
        $html.='<div class="id">[str]ID[/str]</div>';
        $html.='<div class="title">[str]Widget[/str]</div>';
        $html.='</div>';
            
        $co=0;
        $this->sawanna->db->query("select * from pre_cforms order by id");
        while($row=$this->sawanna->db->fetch_object())
        {
           if ($co%2===0)
           {
               $html.='<div class="forms-list-item">';
           } else 
           {
               $html.='<div class="forms-list-item mark highlighted">';
           }
           
            $html.='<div class="id">'.$row->id.'</div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/forms/delete/'.$row->id,'[str]Delete[/str]','button',array('onclick'=>'if (!confirm(\'[str]Are you sure ?[/str]\')){return false;}')).'</div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/forms/edit/'.$row->id,'[str]Edit[/str]','button').'</div>';
            $html.='<div class="title">'.htmlspecialchars($row->name).'</div>';
            $html.='</div>';
            
            $co++;
        }
        
        $html.='</div>';
        
        echo $html;
    }
    
    function field_form($id=0)
    {
        if (!$this->sawanna->has_access('create forms')) 
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
            'value' => '<h1 class="tab">'.(!isset($field->id) ? '[str]New field[/str]' : '[str]Edit field[/str]').'</h1>'
        );
        
        $forms=array(0=>'[str]Please choose[/str]') + $this->get_forms();
        
        if (empty($id))
        {
            $fields['field-form']=array(
                'type' => 'select',
                'title' => '[str]Form[/str]',
                'options' => $forms,
                'description' => '[str]Choose form widget[/str]',
                'default' => isset($field->fid) ? $field->fid : '',
                'required' => true
            );
            
            $fields['field-name']=array(
                'type' => 'text',
                'title' => '[str]Name[/str]',
                'description' => '[str]Specify the field name here[/str]',
                'default' => isset($field->name) ? $field->name : '',
                'autocheck' => array('url'=>'forms-field-name-check','caller'=>'formsAdminHandler','handler'=>'field_name_check'),
                'required' => true
            );
        }
        
        $types=array(0=>'[str]Please choose[/str]') + $this->get_field_types();
        
        $fields['field-type']=array(
            'type' => 'select',
            'title' => '[str]Field type[/str]',
            'options' => $types,
            'description' => '[str]Choose field type[/str]',
            'default' => isset($field->type) ? $field->type : '',
            'required' => true
        );
        
        $examples='<div class="example-title">[strf]Examples for %radio, select% elements[/strf]:</div>';
        $examples.='<ul class="examples">';
        $examples.='<li><label>option-1</label></li>';
        $examples.='<li><label>option-2</label></li>';
        $examples.='<li><label>option-3</label> | [strf]%Option #3% title[/strf]</li>';
        $examples.='<li><label>option-4</label> | [strf]%Option #4% title in first language[/strf], [strf]%Option #4% title in second language[/strf]</li>';
        $examples.='</ul>';
        
        $fields['field-values']=array(
            'type' => 'textarea',
            'title' => '[str]Field values[/str]',
            'description' => '[str]Specify the values that field can take[/str]'.$examples,
            'attributes' => array('style'=>'width: 100%','cols'=>40,'rows'=> 10),
            'default' => isset($field->meta['values']) ? $field->meta['values'] : '',
            'required' => false
        );
        
        $fields['field-default']=array(
            'type' => 'text',
            'title' => '[str]Field default value[/str]',
            'description' => '[str]Specify the field default value[/str]'.'. [strf]You can use following variables: %[username], [usermail], [query]%[/strf]',
            'default' => isset($field->meta['default']) ? $field->meta['default'] : '',
            'required' => false
        );
        
        $required=isset($field->meta['required']) && $field->meta['required'] ? true : false;
        
        $fields['field-required']=array(
            'type' => 'checkbox',
            'title' => '[str]Required[/str]',
            'description' => '[str]Field will be required to fill, if enabled[/str]',
            'attributes' => $required ? array('checked'=>'checked') : array(),
            'required' => false
        );
        
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
                    'title' => '[str]Title[/str]',
                    'description' => '[strf]Specify field title in following language: %'.htmlspecialchars($lname).'%[/strf]',
                    'default' => isset($widget_titles[$lcode]) ? $widget_titles[$lcode] : '',
                    'required' => false
                );
                
                 $fields['title-block-end-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '</div>'
                   );
                
                $co++;
        }
        
        /*
        $fields['field-weight']=array(
            'type' => 'text',
            'title' => '[str]Weight[/str]',
            'description' => '[str]Lighter fields appears first[/str]',
            'default' => isset($field->weight) ? $field->weight: '0',
            'required' => false
        );
        */
        
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

        echo $this->sawanna->constructor->form('field-form',$fields,'field_form_process',empty($field->fid) ? $this->sawanna->config->admin_area.'/forms' : $this->sawanna->config->admin_area.'/forms/edit/'.$field->fid,empty($id) ? $this->sawanna->config->admin_area.'/forms/field-create' : $this->sawanna->config->admin_area.'/forms/field-edit/'.$id,'','CFormsAdmin');
    }
    
    function field_form_process($id=0)
    {
        if (!$this->sawanna->has_access('create forms')) 
        {
            return array('[str]Permission denied[/str]',array('create-field'));
        }

        if (empty($id) && preg_match('/^forms\/field-edit\/(.+)$/',$this->sawanna->arg,$matches)) $id=$matches[1];
        
        if (!empty($id) && !is_numeric($id)) return array('[strf]Invalid field ID: %'.$id.'%[/strf]',array('edit-field'));
        if (!empty($id) && !$this->field_exists($id)) return array('[str]Field not found[/str]',array('edit-field'));    
        
        if (empty($id) && !$this->get_existing_form($_POST['field-form']))
        {
              return array('[str]Widget not found[/str]',array('field-form'));
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
        
        $types=$this->get_field_types();
        if (!array_key_exists($_POST['field-type'],$types))
        {
            return array('[str]Invalid field type[/str]',array('field-type'));
        }
        
        $field=array();
        
        if (!empty($id)) 
        {
            $field['id']=$id;
            $_field=$this->get_existing_field($id);
            $field['name']=$_field->name;
            $field['fid']=$_field->fid;
        }
        
        if (empty($id)) $field['fid']=$_POST['field-form'];
        if (empty($id)) $field['name']=$_POST['field-name'];
        
        $field['type']=$_POST['field-type'];
        $field['weight']=!empty($_POST['field-weight']) ? floor($_POST['field-weight']) : 0;
        
        $field['meta']=array('name'=>array());
        
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
            if (!empty($_POST['field-title-'.$lcode])) $field['meta']['name'][$lcode]=htmlspecialchars($_POST['field-title-'.$lcode]);
        }
        
        $field['meta']['values']='';
        if (!empty($_POST['field-values']))
        {
            $field['meta']['values']=strip_tags($_POST['field-values']);
        }
        
        $field['meta']['default']='';
        if (!empty($_POST['field-default']))
        {
            $field['meta']['default']=strip_tags($_POST['field-default']);
        }
        
        $field['meta']['required']=0;
        if (isset($_POST['field-required']) && $_POST['field-required']=='on')
        {
            $field['meta']['required']=1;
        }
        
        if (!$this->set_field($field))
        {
            return array('[str]Form field could not be saved[/str]',array('new-field'));
        }
        
        return array('[str]Form field saved[/str]');
    }
    
    function set_field($field)
    {
        if (empty($field) || !is_array($field)) return false;
        
        if (empty($field['fid']) || empty($field['name']) || empty($field['type']))
        {
            trigger_error('Cannot set form field. Invalid data given',E_USER_WARNING);
            return false;
        }
        
        if (!isset($field['weight'])) $field['weight']=0;
        if (!isset($field['meta'])) $field['meta']='';
        
        $d=new CData();
        $field['meta']=$d->do_serialize($field['meta']);
        
        if (isset($field['id']))
        {
            return $this->sawanna->db->query("update pre_cform_elements set fid='$1', name='$2', type='$3', meta='$4', weight='$5' where id='$6'",$field['fid'],$field['name'],$field['type'],$field['meta'],$field['weight'],$field['id']);
        } else 
        {
            return $this->sawanna->db->query("insert into pre_cform_elements (fid,name,type,meta,weight) values ('$1','$2','$3','$4','$5')",$field['fid'],$field['name'],$field['type'],$field['meta'],$field['weight']);
        }
    }
    
    function delete_field($id)
    {
        if (empty($id)) return false;

        if ($this->sawanna->db->query("delete from pre_cform_elements where id='$1'",$id))
        {
            return true;
        }
        
        return false;
    }
    
    function field_delete($id)
    {
        if (!$this->sawanna->has_access('create forms')) 
        {
            trigger_error('Could not delete form field. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not delete form field. Invalid widget ID: '.$id,E_USER_WARNING);
            return ;
        }
        if (!$this->field_exists($id)) 
        {
            trigger_error('Could not delete form field. Field not found',E_USER_WARNING);    
            return;
        }
        
        $field=$this->get_existing_field($id);
        
        $event=array();
        
        if (!$this->delete_field($id))
        {
            $message='[str]Could not delete form field. Database error[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
            $message='[strf]Form field with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
            $event['job']='sawanna_message';
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);

        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/forms/edit/'.$field->fid);
    }
    
    function get_forms()
    {
        $forms=array();
        $d=new CData();
        
        $this->sawanna->db->query("select * from pre_cforms order by id");
        while ($row=$this->sawanna->db->fetch_object())
        {
            if (!empty($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            $forms[$row->id]=isset($row->meta['name'][$this->sawanna->locale->current]) ? $row->meta['name'][$this->sawanna->locale->current] : $row->name;
        }
        
        return $forms;
    }
    
    function get_field_types()
    {
        return array(
            'text' => '[str]Text (single line)[/str]',
            'textarea' => '[str]Text (multiple rows)[/str]',
            'email' => '[str]E-Mail[/str]',
            'date' => '[str]Date[/str]',
            'checkbox' => '[str]Checkbox[/str]',
            'radio' => '[str]Choice (radio)[/str]',
            'select' => '[str]Choice (select)[/str]',
            'captcha' => '[str]CAPTCHA[/str]'
        );
    }
    
     function results_list($arg=0)
    {
        if (!$this->sawanna->has_access('create forms')) 
        {
            trigger_error('Permission denied',E_USER_WARNING);
            return ;
        }
        
        $d=new CData();
        
        $html='<h1 class="tab">[str]Forms submission results[/str]</h1>';
        $html.='<div id="results-list">';

        $forms=array();
        $this->sawanna->db->query("select * from pre_cforms order by id");
        while ($row=$this->sawanna->db->fetch_object()) 
        {
            if (!empty($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            $form_title=!empty($row->meta['name'][$this->sawanna->locale->current]) ? $row->meta['name'][$this->sawanna->locale->current] : $row->name;
            $forms[$row->name]=$form_title;
        }
        
        if (count($forms)>1)
        {
            $html.='<div id="results-form">';
            $html.='<div class="form_title">[str]Form[/str]:</div>';
            $html.='<select name="form" id="result-form" onchange="results_select_form()">';
            
            if ($arg=='all')
                $html.='<option value="all" selected>[str]All forms[/str]</option>';
            else 
                $html.='<option value="all">[str]All forms[/str]</option>';
                
            foreach ($forms as $fname=>$ftitle)
            {
                if ($fname==$arg)
                    $html.='<option value="'.$fname.'" selected>'.$ftitle.'</option>';
                else 
                    $html.='<option value="'.$fname.'">'.$ftitle.'</option>';
            }
            $html.= '</select>';
            $html.='<script language="JavaScript">function results_select_form() { window.location.href="'.$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/forms/results').'/"+$("#result-form").get(0).options[$("#result-form").get(0).selectedIndex].value; }</script>';
            $html.='</div>';
        }
        
        $html.='<div class="results-list-item head">';
        $html.='<div class="id"></div>';
        $html.='<div class="title">[str]Result[/str]</div>';
        $html.='</div>';
        
        if ($arg!='all')
        {
            $this->sawanna->db->query("select count(s.submission) as co from (select distinct r.submission from pre_cform_results r left join pre_cform_elements e on r.fid=e.id left join pre_cforms f on e.fid=f.id where f.name='$1') s",$arg);
            $co=$this->sawanna->db->fetch_object();
            if (!empty($co)) $this->count=$co->co;
                
            if (DB_TYPE != 'SQLite') $res=$this->sawanna->db->query("select r.submission as id from pre_cform_results r left join pre_cform_elements e on r.fid=e.id left join pre_cforms f on e.fid=f.id where f.name='$1' group by r.submission order by max(r.tstamp) desc limit $2 offset $3",$arg,$this->limit,$this->start*$this->limit);
            else $res=$this->sawanna->db->query("select r.submission as id from pre_cform_results r left join pre_cform_elements e on r.fid=e.id left join pre_cforms f on e.fid=f.id where f.name='$1' group by r.submission order by r.tstamp desc limit $2 offset $3",$arg,$this->limit,$this->start*$this->limit);
        } else 
        {
            $this->sawanna->db->query("select count(s.submission) as co from (select distinct submission from pre_cform_results) s");
            $co=$this->sawanna->db->fetch_object();
            if (!empty($co)) $this->count=$co->co;
                
            if (DB_TYPE != 'SQLite') $res=$this->sawanna->db->query("select submission as id from pre_cform_results group by submission order by max(tstamp) desc limit $1 offset $2",$this->limit,$this->start*$this->limit);
            else $res=$this->sawanna->db->query("select submission as id from pre_cform_results group by submission order by tstamp desc limit $1 offset $2",$this->limit,$this->start*$this->limit);
        }
        
        $co=0;
        while($submission=$this->sawanna->db->fetch_object($res))
        {
            if ($co%2===0)
            {
                $html.='<div class="results-list-item">';
            } else 
            {
                $html.='<div class="results-list-item mark highlighted">';
            }
                
            $form='';
            $result='';
            $user='';
            $email='';
            $date='';
            
            $this->sawanna->db->query("select  r.data,r.tstamp,r.meta,e.name,e.type,e.meta as emeta,f.name as fname,f.meta as fmeta from pre_cform_results r left join pre_cform_elements e on e.id=r.fid left join pre_cforms f on e.fid=f.id where r.submission='$1' order by e.weight",$submission->id);
            while ($row=$this->sawanna->db->fetch_object())
            {
                if (!empty($row->meta)) $row->meta=$d->do_unserialize($row->meta);
                if (!empty($row->emeta)) $row->emeta=$d->do_unserialize($row->emeta);
                if (!empty($row->fmeta)) $row->fmeta=$d->do_unserialize($row->fmeta);

                if (empty($form))
                {
                    $form= isset($row->fmeta['name'][$this->sawanna->locale->current]) ? $row->fmeta['name'][$this->sawanna->locale->current] : $row->fname;
                    if (empty($form)) $form='-';
                }
                
                if (empty($user) && isset($row->meta['user']['name']))
                {
                    if (!empty($row->meta['user']['id'])) $user=$this->sawanna->constructor->link('user/'.$row->meta['user']['id'],$row->meta['user']['name'],'',array('target'=>'_blank'));
                    else $user=$row->meta['user']['name'];
                }
                
                if (empty($email) && !empty($row->meta['user']['email'])) $email=$row->meta['user']['email'];
                if (empty($date)) $date=date($this->sawanna->config->date_format,$row->tstamp);
                
                $field_title=isset($row->emeta['name'][$this->sawanna->locale->current]) ? $row->emeta['name'][$this->sawanna->locale->current] : $row->name;
                
                $result.='<div><label>'.$field_title.': </label>'.nl2br($row->data).'</div>';
            }
            
            $form='<div class="form"><label>[str]Form[/str]: </label>'.$form.'</div>';
            $data='<div class="date"><label>[str]Date[/str]: </label>'.$date.'</div>';
            $email=!empty($email) ? ' ('.$email.')' : '';
            $sender='<div class="from"><label>[str]From[/str]: </label>'.$user.$email.'</div>';
            
            $html.='<div class="id"><input type="checkbox" name="bulk_id[]" value="'.$submission->id.'" class="bulk_id_checkbox" /></div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/forms/result-delete/'.$submission->id,'[str]Delete[/str]','button',array('onclick'=>'if (!confirm(\'[str]Are you sure ?[/str]\')){return false;}')).'</div>';
            $html.='<div class="title">'.$form.$data.$result.$sender.'</div>';
            $html.='</div>';
            
            $co++;
        }
        
        $html.='</div>';
        
        if ($this->count)
        {
            $html.='<div class="bulk_block">';
            $html.='<a href="#" id="bulk_id_selector" class="bulk_select_all">[str]Select all[/str]</a>';
            $html.='<select id="bulk_action_selector" name="bulk_action_selector" class="bulk_select" onchange="exec_bulk_action()"><option value="">[str]Choose action[/str]</option><option value="del">[str]Delete[/str]</option></select>';
            $html.='</div>';
            
            $html.="<script language=\"javascript\">$(document).ready(function(){ $('a#bulk_id_selector').toggle(function() { $('input[type=checkbox].bulk_id_checkbox').attr('checked','checked'); }, function() { $('input[type=checkbox].bulk_id_checkbox').removeAttr('checked'); }); });</script>";
            $html.="<script language=\"javascript\">function exec_bulk_action(){ var act=$('#bulk_action_selector').val(); var ids=''; if (act.length>0) { $('input[type=checkbox].bulk_id_checkbox').each(function(){ if ($(this).get(0).checked==true) { if (ids.length>0){ids=ids+',';}; ids=ids+$(this).val(); } }); if (ids.length>0 && confirm('[str]Are you sure ?[/str]')) { $.post('".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/forms/results-bulkdel')."',{bulk_ids:ids},function(data){ alert(data); window.location.href='".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/'.$this->sawanna->arg)."'; }); $('#bulk_action_selector').get(0).disabled=true; } } $('#bulk_action_selector').get(0).selectedIndex=0; refresh_select($('#bulk_action_selector')); }</script>";
        }
        
        $html.=$this->sawanna->constructor->pager($this->start,$this->limit,$this->count,$arg=='all' ? $this->sawanna->config->admin_area.'/forms/results' : $this->sawanna->config->admin_area.'/forms/results/'.htmlspecialchars($arg));
        
        echo $html;
    }
    
    function delete_result($id)
    {
        if (empty($id)) return false;

        if ($this->sawanna->db->query("delete from pre_cform_results where submission='$1'",$id))
        {
            return true;
        }
        
        return false;
    }
    
    function results_delete($id)
    {
        if (!$this->sawanna->has_access('create forms')) 
        {
            trigger_error('Could not delete form result. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not delete form result. Invalid result ID: '.$id,E_USER_WARNING);
            return ;
        }
       
        $event=array();
        
        if (!$this->delete_result($id))
        {
            $message='[str]Could not delete form result. Database error[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
            $message='[strf]Form result with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
            $event['job']='sawanna_message';
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);
        
        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/forms/results');
    }
    
    function bulk_results_delete()
    {
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
                $this->delete_result(trim($id));
            }

            echo $this->sawanna->locale->get_string('Form results were deleted');
        } else
        {
            echo $this->sawanna->locale->get_string('Form results were not deleted');
        }
        
        die();
    }
}
