<?php
defined('SAWANNA') or die();

class CTeasersAdmin
{
    var $sawanna;
    var $module_id;
    
    function CTeasersAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('teasers');
    }
    
    function adminbar()
    {
        if (!$this->sawanna->has_access('create teasers')) return;
            
        return array(
                        array(
                        'teasers/create'=>'[str]New widget[/str]',
                        'teasers'=>'[str]Widgets[/str]'
                        ),
                        'teasers'
                        );
    }
    
    function admin_area()
    {
        if (!$this->sawanna->has_access('create teasers')) return ;
        
        $this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
        
        switch (true)
        {
            case $this->sawanna->arg=='teasers/create':
                $this->teaser_form();
                break;
            case preg_match('/^teasers\/edit\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->teaser_form($matches[1]);
                break;
            case preg_match('/^teasers\/delete\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->teaser_delete($matches[1]);
                break;
            case $this->sawanna->arg=='teasers':
                $this->teasers_list();
                break;
        }
    }
    
    function teaser_exists($id)
    {
        $this->sawanna->db->query("select * from pre_teasers where id='$1'",$id);
        
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return true;
        return false;
    }
    
    function get_existing_teaser($id)
    {
        $this->sawanna->db->query("select * from pre_teasers where id='$1'",$id);
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
    
    function teaser_form($id=0)
    {
        if (!$this->sawanna->has_access('create teasers')) 
        {
            trigger_error('Permission denied',E_USER_ERROR);
            return ;
        }
        
        $teaser=new stdClass();
        
        if (!empty($id) && !is_numeric($id)) 
        {
            trigger_error('Invalid widget ID: '.$id,E_USER_ERROR);
            return;
        }
        if (!empty($id))
        {
            if (!$this->teaser_exists($id)) 
            {
                trigger_error('Widget not found',E_USER_ERROR);
                return;    
            }
            
            $teaser=$this->get_existing_teaser($id);
        }
        
        $fields=array();
        
         $fields['teaser-tab']=array(
            'type' => 'html',
            'value' => '<h1 class="tab">'.(!isset($teaser->id) ? '[str]New widget[/str]' : '[str]Edit widget[/str]').'</h1>'
        );
        
        if (empty($id))
        {
            $fields['teaser-name']=array(
                'type' => 'text',
                'title' => '[str]Name[/str]',
                'description' => '[str]Specify the widget name here[/str]',
                'autocheck' => array('url'=>'teasers-widget-name-check','caller'=>'teasersAdminHandler','handler'=>'widget_name_check'),
                'default' => isset($teaser->name) ? $teaser->name : '',
                'required' => true
            );
        }
        
        $fields['teaser-path']=array(
            'type' => 'text',
            'title' => '[str]URL path[/str]',
            'description' => '[str]Specify parent URL path[/str]',
            'autocomplete' => array('url'=>'nodes/path-complete','caller'=>'contentAdminHandler','handler'=>'node_path_complete'),
            'default' => isset($teaser->path) ? $teaser->path : '',
            'attributes' => array('style'=>'width: 100%'),
            'required' => true
        );
        
        $fields['teaser-count']=array(
            'type' => 'text',
            'title' => '[str]Teasers number[/str]',
            'description' => '[str]Specify the number of teasers to display[/str]',
            'default' => isset($teaser->meta['count']) ? $teaser->meta['count'] : '5',
            'required' => true
        );
        
        $fields['teaser-type']=array(
            'type' => 'select',
            'title' => '[str]Template[/str]',
            'description' => '[str]Choose widget template[/str]',
            'options' => array(''=>'[str]Please choose[/str]','widget'=>'Widget','node'=>'Node'),
            'default' => isset($teaser->meta['tpl']) ? $teaser->meta['tpl'] : '',
            'required' => true
        );
        
        $widget_titles=array();
        if (!empty($teaser->meta) && isset($teaser->meta['name']))
        {
            $widget_titles=$teaser->meta['name'];
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
                   
                $fields['teaser-title-'.$lcode]=array(
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
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('teasers-form',$fields,'teaser_form_process',$this->sawanna->config->admin_area.'/teasers',empty($id) ? $this->sawanna->config->admin_area.'/teasers/create' : $this->sawanna->config->admin_area.'/teasers/edit/'.$id,'','CTeasersAdmin');
    }
    
    function teaser_form_process($id=0)
    {
        if (!$this->sawanna->has_access('create teasers')) 
        {
            return array('[str]Permission denied[/str]',array('create-teaser'));
        }

        if (empty($id) && preg_match('/^teasers\/edit\/(.+)$/',$this->sawanna->arg,$matches)) $id=$matches[1];
        
        if (!empty($id) && !is_numeric($id)) return array('[strf]Invalid widget ID: %'.$id.'%[/strf]',array('edit-teaser'));
        if (!empty($id) && !$this->teaser_exists($id)) return array('[str]Widget not found[/str]',array('edit-teaser'));    
        
        if (empty($id) && !$this->valid_widget_name($_POST['teaser-name']))
        {
              return array('[str]Invalid widget name[/str]',array('teaser-name'));
        }
        
        if (empty($id) && $this->widget_exists($_POST['teaser-name']))
        {
              return array('[str]Widget with such name already exists[/str]',array('teaser-name'));
        }
        
        $teaser=array();
        
        if (!empty($id)) 
        {
            $teaser['id']=$id;
            $_teaser=$this->get_existing_teaser($id);
            $teaser['name']=$_teaser->name;
        }
        
        if (!valid_path($_POST['teaser-path']))
        {
            return array('[strf]URL path %'.htmlspecialchars($_POST['teaser-path']).'% is invalid[/strf]',array('teaser-path'));
        }
        
        if (!is_numeric($_POST['teaser-count']))
        {
            return array('[str]Invalid teasers number[/str]',array('teaser-count'));
        }
        
        if (empty($id)) $teaser['name']=$_POST['teaser-name'];
        $teaser['path']=$_POST['teaser-path'];
        
        $teaser['meta']=array('name'=>array());
        
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
            if (!empty($_POST['teaser-title-'.$lcode])) $teaser['meta']['name'][$lcode]=htmlspecialchars($_POST['teaser-title-'.$lcode]);
        }
        
        $teaser['meta']['count']=ceil($_POST['teaser-count']);
        $teaser['meta']['tpl']='widget';
        if ($_POST['teaser-type']=='node') $teaser['meta']['tpl']='node';
        
        
        if (!$this->set($teaser))
        {
            return array('[str]Teasers widget could not be saved[/str]',array('new-teaser'));
        }
        
        // clearing cache
        $this->sawanna->cache->clear_all_by_type('teasers');
        
        return array('[str]Teasers widget saved[/str]');
    }
    
    function set($teaser)
    {
        if (empty($teaser) || !is_array($teaser)) return false;
        
        if (empty($teaser['name']) || empty($teaser['path']))
        {
            trigger_error('Cannot set teasers widget. Invalid data given',E_USER_WARNING);
            return false;
        }
        
        if (empty($teaser['meta'])) $teaser['meta']='';
        else 
        {
            if (empty($teaser['meta']['count'])) $teaser['meta']['count']=5;
            if (empty($teaser['meta']['tpl'])) $teaser['meta']['tpl']='widget';
        }
        
        $teaser['tpl']=(!empty($teaser['meta']['tpl'])) ? $teaser['meta']['tpl'] : 'widget';
        
        $d=new CData();
        $teaser['meta']=$d->do_serialize($teaser['meta']);
        
        if (isset($teaser['id']))
        {
            $res=$this->sawanna->db->query("update pre_teasers set name='$1', path='$2', meta='$3' where id='$4'",$teaser['name'],$teaser['path'],$teaser['meta'],$teaser['id']);
        } else 
        {
            $res=$this->sawanna->db->query("insert into pre_teasers (name,path,meta) values ('$1','$2','$3')",$teaser['name'],$teaser['path'],$teaser['meta']);
        }
        
        if (!$res) return false;
        
        if (empty($teaser['id']))
            return $this->set_widget($teaser);
        else 
            return true;
    }
    
    function set_widget($teaser)
    {
        $tpl='widget';
        if (isset($teaser['tpl']) && $teaser['tpl']=='node') $tpl='node';
     
        $widget=array(
        'name' => $teaser['name'],
        'title' => '[str]Teasers[/str]: '.$teaser['name'],
        'description' => '[str]Teasers widget[/str]',
        'class' => 'CTeasers',
        'handler' => 'teasers',
        'argument' => $teaser['name'],
        'path' => '*',
        'component' => $tpl=='widget' ? 'left' : '',
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

        $teaser=$this->get_existing_teaser($id);
        
        if ($this->sawanna->db->query("delete from pre_teasers where id='$1'",$id))
        {
            $this->sawanna->module->delete($teaser->name,'widget');
            return true;
        }
        
        return false;
    }
    
    function teaser_delete($id)
    {
        if (!$this->sawanna->has_access('create teasers')) 
        {
            trigger_error('Could not delete teasers widget. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not delete teasers widget. Invalid widget ID: '.$id,E_USER_WARNING);
            return ;
        }
        if (!$this->teaser_exists($id)) 
        {
            trigger_error('Could not delete teasers widget. Widget not found',E_USER_WARNING);    
            return;
        }
        
        $event=array();
        
        if (!$this->delete($id))
        {
            $message='[str]Could not delete teasers widget. Database error[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
            $message='[strf]Teasers widget with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
            $event['job']='sawanna_message';
            
            // clearing cache
            $this->sawanna->cache->clear_all_by_type('teasers');
            
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);
        
        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/teasers');
    }
    
    function teasers_list()
    {
        if (!$this->sawanna->has_access('create teasers')) 
        {
            trigger_error('Permission denied',E_USER_WARNING);
            return ;
        }
        
        $html='<h1 class="tab">[str]Widgets[/str]</h1>';
        $html.='<div id="teasers-list">';
        
        $html.='<div class="teasers-list-item head">';
        $html.='<div class="id">[str]ID[/str]</div>';
        $html.='<div class="title">[str]Widget[/str]</div>';
        $html.='</div>';
            
        $co=0;
        $this->sawanna->db->query("select * from pre_teasers order by id");
        while($row=$this->sawanna->db->fetch_object())
        {
           if ($co%2===0)
           {
               $html.='<div class="teasers-list-item">';
           } else 
           {
               $html.='<div class="teasers-list-item mark highlighted">';
           }
           
            $html.='<div class="id">'.$row->id.'</div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/teasers/delete/'.$row->id,'[str]Delete[/str]','button',array('onclick'=>'if (!confirm(\'[str]Are you sure ?[/str]\')){return false;}')).'</div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/teasers/edit/'.$row->id,'[str]Edit[/str]','button').'</div>';
            $html.='<div class="title">'.htmlspecialchars($row->name).'</div>';
            $html.='</div>';
            
            $co++;
        }
        
        $html.='</div>';
        
        echo $html;
    }
}
