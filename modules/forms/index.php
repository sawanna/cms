<?php
defined('SAWANNA') or die();

class CForms
{
    var $sawanna;
    var $module_id;
    
    function CForms(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('forms');
        
    }
    
    function fix_widget($form)
    {
        $wid=$this->sawanna->module->get_widget_id($form->name);
        if(!$wid) return false;
        
        $tpl='';
        if (!empty($form->meta['tpl']))
        {
            if ($form->meta['tpl']=='widget') $tpl='widget';
            elseif ($form->meta['tpl']=='node') $tpl='node';
        }
        
        if (!empty($tpl)) $this->sawanna->module->widgets[$wid]->tpl=$tpl;
        return true;
    }

    function form($name)
    {
        if (empty($name)) return ;

        $this->sawanna->db->query("select * from pre_cforms where name='$1'",$name);
        $form=$this->sawanna->db->fetch_object();
        
        if (!$form) return ;
        
        if (!empty($form->meta))
        {
            $d=new CData();
            $form->meta=$d->do_unserialize($form->meta);
        }
        
        $tpl='';
        if (!empty($form->meta['tpl']))
        {
            if ($form->meta['tpl']=='widget') $tpl='widget';
            elseif ($form->meta['tpl']=='node') $tpl='node';
        }
        
        $html='';
        $fields=array();
        
        $html=$this->get_form_fields($form);
        
        if (!empty($html))
        {
            $title='';
            if (isset($form->meta['name'][$this->sawanna->locale->current])) $title=htmlspecialchars($form->meta['name'][$this->sawanna->locale->current]);
            
            if (!empty($tpl))
            {
                $this->fix_widget($form);
                return array('content'=>'<div class="cform">'.$html.'</div>','title'=>$title);
            } else return '<div class="cform">'.$html.'</div>';
        }
    }
    
    function get_form_fields($form)
    {
        $fields=array();
        
        $d=new CData();
        $types=$this->get_field_types();
        
        $this->sawanna->db->query("select * from pre_cform_elements where fid='$1' order by weight",$form->id);
        while ($row=$this->sawanna->db->fetch_object())
        {
            if (!in_array($row->type,$types)) continue;
            
            if (!empty($row->meta))
            {
                $row->meta=$d->do_unserialize($row->meta);
            }
            
            $title='';
            if (isset($row->meta['name'][$this->sawanna->locale->current])) $title=htmlspecialchars($row->meta['name'][$this->sawanna->locale->current]);
            
            $description='';
            $attributes=array();
            
            switch ($row->type)
            {
                case 'text':
                    $description='[strf]Maximum number of characters: %255%[/strf]';
                    break;
                case 'textarea':
                    $description='[strf]Maximum number of characters: %5000%[/strf]';
                    $attributes=array('style'=>'width:100%','cols'=>40,'rows'=>10);
                    break;
                case 'email':
                    $description='[str]Please enter valid E-Mail address[/str]';
                    break;
                case 'date':
                    $description='[strf]Format: %dd.mm.yyyy%[/strf]';
                    break;
                case 'checkbox':
                    $attributes=array('style'=>'width:auto','class'=>'checkbox');
                    break;
                case 'captcha':
                    $description='[str]Enter security code shown above[/str]';
                    break;
            }
            
            if (isset($row->meta['default'])) $this->fix_tokens($row->meta['default']);
            
            if ($row->type != 'radio')
            {
                $fields['cform-field-'.htmlspecialchars($row->name)]=array(
                    'type' => $row->type,
                    'title' => $title,
                    'description' => $description,
                    'attributes' => $attributes,
                    'default' => isset($row->meta['default']) ? $row->meta['default'] : '',
                    'required' => isset($row->meta['required']) && $row->meta['required'] ? true : false
                );
            } else 
            {
                $fields['cform-field-radio-title'.htmlspecialchars($row->name)]=array(
                    'type' => 'html',
                    'value' => '<div class="radio_title">'.$title.'</div>'
                );
                    
                $this->set_radio_options($fields,$row);
            }
            
            if ($row->type=='select')
            {
                $this->set_select_options($fields,$row);
            }
        }
        
        if (!empty($fields))
        {
            $fields['cform-fid']=array(
                'type' => 'hidden',
                'value' => $form->id
            );
            
            $fields['submit']=array(
                'type'=>'submit',
                'value'=>'[str]Submit[/str]'
            );
            
            return '<div class="cform-widget">'.$this->sawanna->constructor->form('cform-'.$form->name,$fields,'form_process',!empty($this->sawanna->query) ? $this->sawanna->query : '[home]',$this->sawanna->query,'','CForms',true).'</div>';
        }
    }
    
    function set_radio_options(&$fields,$field)
    {
        $options=array();
        $optkey='cform-field-'.trim($field->name);
        
        $current_language=0; 
        $co=0;
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
            if ($lcode==$this->sawanna->locale->current)
            {
                $current_language=$co;
                break;
            }
            $co++;
        }
                        
        if (!empty($field->meta['values']))
        {
            $values=explode("\n",$field->meta['values']);
            foreach ($values as $value)
            {
                $value=trim($value);
                if (strpos($value,'|'))
                {
                    $key=sawanna_substr($value,0,strpos($value,'|'));
                    $optval=trim($key);
                    
                    $value=sawanna_substr($value,strpos($value,'|')+1);
                    
                    if (strpos($value,','))
                    {
                        $vals=explode(',',$value);
                        $default=trim($vals[0]);
                        
                        if (!empty($vals[$current_language])) $value=trim($vals[$current_language]);
                        else $value=trim($default);
                    } else $value=trim($value);
                } else $optval=trim($value);
                
                $options[$optval]=$value;
            }
            
            if (!empty($options))
            {
                foreach ($options as $optval=>$title)
                {
                    $fields['cform-field-'.htmlspecialchars($field->name).'-'.$optval.'-element-begin']=array(
                        'type' => 'html',
                        'value' => '<div class="cform-inline">'
                    );
                    
                    $fields['cform-field-'.htmlspecialchars($field->name).'-'.htmlspecialchars($optval)]=array(
                        'type' => 'radio',
                        'name' => htmlspecialchars($optkey),
                        'value' => htmlspecialchars($optval),
                        'title' => '<label for="cform-field-'.htmlspecialchars($field->name).'-'.htmlspecialchars($optval).'">'.htmlspecialchars($title).'</label>',
                        'default' => isset($field->meta['default']) ? $field->meta['default'] : '',
                        'attributes' => array('class'=>'radio'),
                        'required' => isset($field->meta['required']) && $field->meta['required'] ? true : false
                    );
                    
                    $fields['cform-field-'.htmlspecialchars($field->name).'-'.$optval.'-element-end']=array(
                        'type' => 'html',
                        'value' => '</div>'
                    );
                }
                
                $fields['cform-field-'.htmlspecialchars($field->name).'-'.$optval.'-elements-clear']=array(
                    'type' => 'html',
                    'value' => '<div class="clear"></div>'
                );
            }
        }
    }
    
    function set_select_options(&$fields,$field)
    {
        $options=array();
        $current_language=0; 
        $co=0;
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
            if ($lcode==$this->sawanna->locale->current)
            {
                $current_language=$co;
                break;
            }
            $co++;
        }
                        
        if (!empty($field->meta['values']))
        {
            $values=explode("\n",$field->meta['values']);
            foreach ($values as $value)
            {
                $value=trim($value);
                if (strpos($value,'|'))
                {
                    $key=sawanna_substr($value,0,strpos($value,'|'));
                    $key=trim($key);
                    
                    $value=sawanna_substr($value,strpos($value,'|')+1);
                    
                    if (strpos($value,','))
                    {
                        $vals=explode(',',$value);
                        $default=trim($vals[0]);
                        
                        if (!empty($vals[$current_language])) $value=trim($vals[$current_language]);
                        else $value=trim($default);
                    } else $value=trim($value);
                } else $key=$value;
                
                $options[$key]=$value;
            }
        }
        
        $fields['cform-field-'.htmlspecialchars($field->name)]['options']=$options;
    }
    
    function form_process()
    {
        if (empty($_POST['cform-fid'])) return array();
        
        $this->sawanna->db->query("select * from pre_cforms where id='$1'",$_POST['cform-fid']);
        $form=$this->sawanna->db->fetch_object();
        
        if (!$form) return array();
        
        $d=new CData();
         
        if (!empty($form->meta))
        {
            $form->meta=$d->do_unserialize($form->meta);
        }
        
        $form_title=isset($form->meta['name'][$this->sawanna->locale->current]) ? $form->meta['name'][$this->sawanna->locale->current] : $form->name;
        
        $elems=array();
        
        $this->sawanna->db->query("select max(submission) as rev from pre_cform_results");
        $sub=$this->sawanna->db->fetch_object();
        $rev=!empty($sub->rev) ? ++$sub->rev : 1;
    
        $res=$this->sawanna->db->query("select * from pre_cform_elements where fid='$1' order by weight",$form->id);
        while ($row=$this->sawanna->db->fetch_object($res))
        {
            if ($row->type=='captcha') continue;
            
            $fname='cform-field-'.$row->name;

            if (!empty($row->meta))
            {
                $row->meta=$d->do_unserialize($row->meta);
            }
            
            $field_title=isset($row->meta['name'][$this->sawanna->locale->current]) ? $row->meta['name'][$this->sawanna->locale->current] : $row->name;
            
            if (empty($_POST[$fname])) 
            {
                $elems[$field_title]='-';
                continue;
            }
            
            $value=htmlspecialchars($_POST[$fname]);
            
            if (!empty($row->meta['values']) && !$this->check_field_values($value,$row->meta['values']))
            {
                return array('[strf]Invalid field value for `%'.htmlspecialchars($field_title).'%`[/strf]',array($fname));
            }
            
            switch ($row->type)
            {
                case 'text':
                    if (sawanna_strlen($value) > 255) $value=sawanna_substr($value,0,255);
                    break;
                case 'textarea':
                    if (sawanna_strlen($value) > 5000) $value=sawanna_substr($value,0,5000);
                    break;
                case 'checkbox':
                    $value=$value=='on' ? $this->sawanna->locale->get_string('yes') : $this->sawanna->locale->get_string('no');
                    break;
            }
            
            $elems[$field_title]=$value;

            $this->set_result($row->id,$value,$rev);
        }
        
        if (!empty($form->meta['email']) && !empty($elems))
        {
            $message=$this->sawanna->locale->get_string('Form').': '.htmlspecialchars($form_title)."\n\n";
            
            foreach ($elems as $f_title=>$f_value)
            {
                $message.=$f_title.': '.$f_value."\n";
            }
            
            $message.="\n\n/ ".$this->sawanna->config->site_name.' /'."\n".SITE;
            
            $this->sawanna->constructor->sendMail($form->meta['email'],$this->sawanna->config->send_mail_from,$this->sawanna->config->send_mail_from_name,$form_title,$message);
        }
        
        if (!empty($form->meta['message'][$this->sawanna->locale->current]))
        {
            return array($form->meta['message'][$this->sawanna->locale->current]);
        }
    }
    
    function check_field_values(&$check,$values)
    {  
        $allowed=array();
        
        if (!empty($values))
        {
            $values=explode("\n",$values);
            foreach ($values as $value)
            {
                $value=trim($value);
                if (strpos($value,'|'))
                {
                    $key=sawanna_substr($value,0,strpos($value,'|'));
                    $key=trim($key);
                } else $key=$value;
                
                $allowed[]=$key;
            }
        }
        
        if (!empty($allowed) && !in_array($check,$allowed)) return false;
        
        return true;
    }
    
    function set_result($fid,&$value,$rev)
    {
        $meta=array();
        
        $user=array(
            'id' => 0,
            'name' => '[str]Anonymous[/str]',
            'email' => ''
        );
        
        if ($this->sawanna->module_exists('user'))
        {
            if (!empty($GLOBALS['user']->current->id)) $user['id']=$GLOBALS['user']->current->id;
            if (!empty($GLOBALS['user']->current->login)) $user['name']=$GLOBALS['user']->current->login;
            if (!empty($GLOBALS['user']->current->email)) $user['email']=$GLOBALS['user']->current->email;
        }
        
        $meta['user']=$user;
        
        $d=new CData();
        $meta=$d->do_serialize($meta);

        return $this->sawanna->db->query("insert into pre_cform_results (fid,data,tstamp,meta,submission) values ('$1','$2','$3','$4','$5')",$fid,$value,time(),$meta,$rev);
    }
    
    function get_field_types()
    {
        return array(
            'text',
            'textarea',
            'email',
            'date',
            'checkbox',
            'radio',
            'select',
            'captcha'
        );
    }
    
    function fix_tokens(&$default)
    {
        switch ($default)
        {
            case '[username]':
                if ($this->sawanna->module_exists('user') && isset($GLOBALS['user']) && is_object($GLOBALS['user']) && method_exists($GLOBALS['user'],'get_user_name'))
                    $default=$GLOBALS['user']->get_user_name();
                else 
                    $default='';
                break;
            case '[usermail]':
                if ($this->sawanna->module_exists('user') && isset($GLOBALS['user']) && is_object($GLOBALS['user']) && isset($GLOBALS['user']->current->email))
                    $default=$GLOBALS['user']->current->email;
                else 
                    $default='';
                break;
            case '[query]':
                $default=$this->sawanna->query;
                break;
        }
    }
}
