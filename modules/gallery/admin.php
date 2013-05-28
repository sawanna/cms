<?php
defined('SAWANNA') or die();

class CGalleryAdmin
{
    var $sawanna;
    var $module_id;
    var $limit;
    var $count;
    var $start;
    var $root_dir;
    var $thumbs_dir;
    var $img_dir;
    var $original_dir;
    var $thumb_width;
    var $thumb_height;
    var $thumb_crop;
    var $img_width;
    var $img_height;
    var $img_crop;
    var $img_ratio;
    var $image_size;
    
    function CGalleryAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('gallery');
        $this->count=0;
        $this->start=0;
        $this->limit=10;
        $this->root_dir='gallery';
        $this->thumbs_dir='thumbs';
        $this->img_dir='images';
        $this->original_dir='original';
        
        $thumb_width=$this->sawanna->get_option('gallery_thumbnail_width');
        $thumb_height=$this->sawanna->get_option('gallery_thumbnail_height');
        $thumb_crop=$this->sawanna->get_option('gallery_thumbnail_crop');
        
        $this->thumb_width=$thumb_width ? $thumb_width : 120;
        $this->thumb_height=$thumb_height ? $thumb_height : 90;
        $this->thumb_crop=$thumb_crop ? $thumb_crop : false;
        
        $image_width=$this->sawanna->get_option('gallery_image_width');
        $image_height=$this->sawanna->get_option('gallery_image_height');
        $image_crop=$this->sawanna->get_option('gallery_image_crop');
        $image_ratio=$this->sawanna->get_option('gallery_image_ratio');
        $image_size=$this->sawanna->get_option('gallery_image_size');
        
        $this->img_width=$image_width ? $image_width : 800;
        $this->img_height=$image_height ? $image_height : 600;
        $this->img_crop=$image_crop ? $image_crop : false;
        $this->img_ratio=$image_ratio ? $image_ratio : false;
        $this->img_size=$image_size ? $image_size : false;
    }
    
    function adminbar()
    {
        if (!$this->sawanna->has_access('create gallery')) return;
            
        return array(
                        array(
                        'gallery/create'=>'[str]New widget[/str]',
                        'gallery'=>'[str]Widgets[/str]',
                        'gallery/image-create'=>'[str]New image[/str]',
                        'gallery/images'=>'[str]Images[/str]',
						'gallery/update-images' => '[str]Actions[/str]'
                        ),
                        'gallery'
                        );
    }
    
     function optbar()
    {
        return  array(
                        'gallery/settings'=>'[str]Gallery[/str]'
                        );
    }
    
    function admin_area()
    {
        if (!$this->sawanna->has_access('create gallery')) return ;
        
        $this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
        
        switch (true)
        {
            case $this->sawanna->arg=='gallery/create':
                $this->gallery_form();
                break;
            case preg_match('/^gallery\/edit\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->gallery_form($matches[1]);
                break;
            case preg_match('/^gallery\/delete\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->gallery_delete($matches[1]);
                break;
            case $this->sawanna->arg=='gallery/image-create':
                $this->image_form();
                break;
            case preg_match('/^gallery\/image-edit\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->image_form($matches[1]);
                break;
            case $this->sawanna->arg=='gallery':
                $this->gallery_list();
                break;
            case preg_match('/^gallery\/([\d]+)$/',$this->sawanna->arg,$matches):
                if ($matches[1]>0) $this->start=floor($matches[1]);
                $this->gallery_list();
                break;
            case $this->sawanna->arg=='gallery/images':
                $this->image_list('all');
                break;
            case preg_match('/^gallery\/images\/([\d]+)$/',$this->sawanna->arg,$matches):
                if ($matches[1]>0) $this->start=floor($matches[1]);
                $this->image_list('all');
                break;
            case preg_match('/^gallery\/images\/([a-zA-Z0-9_-]+)$/',$this->sawanna->arg,$matches) && ($this->gallery_name_exists($matches[1]) || $matches[1]=='all'):
                $this->image_list($matches[1]);
                break;
            case preg_match('/^gallery\/images\/([a-zA-Z0-9_-]+)\/([\d]+)$/',$this->sawanna->arg,$matches) && ($this->gallery_name_exists($matches[1]) || $matches[1]=='all'):
                if ($matches[2]>0) $this->start=floor($matches[2]);
                $this->image_list($matches[1]);
                break;
            case preg_match('/^gallery\/image-delete\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->image_delete($matches[1]);
                break;
            case preg_match('/^gallery\/image-bulkdel$/',$this->sawanna->arg,$matches):
                $this->bulk_image_delete();
                break;
            case $this->sawanna->arg=='gallery/settings':
                $this->settings_form();
                break;
			 case $this->sawanna->arg=='gallery/update-images':
                $this->update_images_form();
                break;
        }
    }
    
    function gallery_exists($id)
    {
        $this->sawanna->db->query("select * from pre_galleries where id='$1'",$id);
        
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return true;
        return false;
    }
    
    function gallery_name_exists($id)
    {
        $this->sawanna->db->query("select * from pre_galleries where name='$1'",$id);
        
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return true;
        return false;
    }
    
    function get_existing_gallery($id)
    {
        $this->sawanna->db->query("select * from pre_galleries where id='$1'",$id);
        $row=$this->sawanna->db->fetch_object();
        
        $d=new CData();
        if (!empty($row->meta) && $d->is_serialized($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            
        if (!empty($row)) return $row;
    }
    
    function image_exists($id)
    {
        $this->sawanna->db->query("select * from pre_gallery_images where id='$1'",$id);
       
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return true;
        return false;
    }

    function get_existing_image($id)
    {
        $this->sawanna->db->query("select * from pre_gallery_images where id='$1'",$id);
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
    
    function gallery_form($id=0)
    {
        if (!$this->sawanna->has_access('create gallery')) 
        {
            trigger_error('Permission denied',E_USER_ERROR);
            return ;
        }
        
        $gallery=new stdClass();
        
        if (!empty($id) && !is_numeric($id)) 
        {
            trigger_error('Invalid widget ID: '.$id,E_USER_ERROR);
            return;
        }
        if (!empty($id))
        {
            if (!$this->gallery_exists($id)) 
            {
                trigger_error('Widget not found',E_USER_ERROR);
                return;    
            }
            
            $gallery=$this->get_existing_gallery($id);
        }
        
        $fields=array();
        
         $fields['gallery-tab']=array(
            'type' => 'html',
            'value' => '<h1 class="tab">'.(!isset($gallery->id) ? '[str]New widget[/str]' : '[str]Edit widget[/str]').'</h1>'
        );
        
        if (empty($id))
        {
            $fields['gallery-name']=array(
                'type' => 'text',
                'title' => '[str]Name[/str]',
                'description' => '[str]Specify the widget name here[/str]',
                'autocheck' => array('url'=>'gallery-widget-name-check','caller'=>'galleryAdminHandler','handler'=>'widget_name_check'),
                'default' => isset($gallery->name) ? $gallery->name : '',
                'required' => true
            );
        }
        
        $widget_titles=array();
        if (!empty($gallery->meta) && isset($gallery->meta['name']))
        {
            $widget_titles=$gallery->meta['name'];
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
                   
                $fields['gallery-title-'.$lcode]=array(
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
        
        $fields['gallery-type']=array(
            'type' => 'select',
            'title' => '[str]Template[/str]',
            'description' => '[str]Choose widget template[/str]',
            'options' => array(''=>'[str]Without template[/str]','widget'=>'Widget','node'=>'Node'),
            'default' => isset($gallery->meta['tpl']) ? $gallery->meta['tpl'] : '',
            'required' => false
        );
        
        $fields['gallery-limit']=array(
            'type' => 'text',
            'title' => '[str]Limit[/str]',
            'description' => '[str]Specify the maximum number of images to display[/str]',
            'default' => isset($gallery->meta['limit']) ? $gallery->meta['limit'] : '10',
            'required' => true
        );
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('gallery-form',$fields,'gallery_form_process',$this->sawanna->config->admin_area.'/gallery',empty($id) ? $this->sawanna->config->admin_area.'/gallery/create' : $this->sawanna->config->admin_area.'/gallery/edit/'.$id,'','CGalleryAdmin');
    }
    
    function gallery_form_process($id=0)
    {
        if (!$this->sawanna->has_access('create gallery')) 
        {
            return array('[str]Permission denied[/str]',array('create-gallery'));
        }

        if (empty($id) && preg_match('/^gallery\/edit\/(.+)$/',$this->sawanna->arg,$matches)) $id=$matches[1];
        
        if (!empty($id) && !is_numeric($id)) return array('[strf]Invalid widget ID: %'.$id.'%[/strf]',array('edit-gallery'));
        if (!empty($id) && !$this->gallery_exists($id)) return array('[str]Widget not found[/str]',array('edit-gallery'));    
        
        if (empty($id) && !$this->valid_widget_name($_POST['gallery-name']))
        {
              return array('[str]Invalid widget name[/str]',array('gallery-name'));
        }
        
        if (empty($id) && $this->widget_exists($_POST['gallery-name']))
        {
              return array('[str]Widget with such name already exists[/str]',array('gallery-name'));
        }
        
        $gallery=array();
        
        if (!empty($id)) 
        {
            $gallery['id']=$id;
            $_gallery=$this->get_existing_gallery($id);
            $gallery['name']=$_gallery->name;
        }
        
        if (empty($id)) $gallery['name']=$_POST['gallery-name'];
        
        $gallery['meta']=array('name'=>array());
        
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
            if (!empty($_POST['gallery-title-'.$lcode])) $gallery['meta']['name'][$lcode]=htmlspecialchars($_POST['gallery-title-'.$lcode]);
        }
        
        $gallery['meta']['tpl']='';
        if (!empty($_POST['gallery-type']))
        {
            $gallery['meta']['tpl']=$_POST['gallery-type']=='node' ? 'node' : 'widget';
        }
        
        $gallery['meta']['limit']=10;
        if (!empty($_POST['gallery-limit']) && is_numeric($_POST['gallery-limit']))
        {
            $gallery['meta']['limit']=ceil($_POST['gallery-limit']);
        }
       
        if (!$this->set($gallery))
        {
            return array('[str]Gallery widget could not be saved[/str]',array('new-gallery'));
        }
        
        // clearing cache
        $this->sawanna->cache->clear_all_by_type('gallery');
        
        return array('[str]Gallery widget saved[/str]');
    }
    
    function set($gallery)
    {
        if (empty($gallery) || !is_array($gallery)) return false;
        
        if (empty($gallery['name']))
        {
            trigger_error('Cannot set gallery widget. Invalid data given',E_USER_WARNING);
            return false;
        }
        
        if (!isset($gallery['meta'])) $gallery['meta']='';
        
        $gallery['tpl']=(!empty($gallery['meta']['tpl'])) ? $gallery['meta']['tpl'] : '';
        
        $d=new CData();
        $gallery['meta']=$d->do_serialize($gallery['meta']);
         
        if (isset($gallery['id']))
        {
            $res=$this->sawanna->db->query("update pre_galleries set name='$1', meta='$2' where id='$3'",$gallery['name'],$gallery['meta'],$gallery['id']);
        } else 
        {
            $res=$this->sawanna->db->query("insert into pre_galleries (name,meta) values ('$1','$2')",$gallery['name'],$gallery['meta']);
        }
        
        if (!$res) return false;
        
        if (empty($gallery['id']))
        {
            return $this->set_widget($gallery);
        } else 
            return true;
    }
    
    function set_widget($gallery)
    {
        $tpl='';
        if (isset($gallery['tpl']) && $gallery['tpl']=='widget') $tpl='widget';
     
        $widget=array(
        'name' => $gallery['name'],
        'title' => '[str]Gallery[/str]: '.$gallery['name'],
        'description' => '[str]Gallery widget[/str]',
        'class' => 'CGallery',
        'handler' => 'gallery',
        'argument' => $gallery['name'],
        'path' => '*',
        'component' => $tpl!='widget' ? '' : 'left',
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

        $gallery=$this->get_existing_gallery($id);
        
        if ($this->sawanna->db->query("delete from pre_galleries where id='$1'",$id))
        {
            $this->sawanna->module->delete($gallery->name,'widget');
            return true;
        }
        
        return false;
    }
    
    function gallery_delete($id)
    {
        if (!$this->sawanna->has_access('create gallery')) 
        {
            trigger_error('Could not delete gallery widget. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not delete gallery widget. Invalid widget ID: '.$id,E_USER_WARNING);
            return ;
        }
        if (!$this->gallery_exists($id)) 
        {
            trigger_error('Could not delete gallery widget. Widget not found',E_USER_WARNING);    
            return;
        }
        
        $event=array();
        
        if (!$this->delete($id))
        {
            $message='[str]Could not delete gallery widget. Database error[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
            $res=$this->sawanna->db->query("select * from pre_gallery_images where gid='$1'",$id);
            while ($row=$this->sawanna->db->fetch_object($res)) 
            {
                $this->sawanna->file->delete($this->sawanna->file->get_id_by_name($row->original));
                $this->sawanna->file->delete($this->sawanna->file->get_id_by_name($row->thumb));
                $this->sawanna->file->delete($this->sawanna->file->get_id_by_name($row->image));
            }
            
            $this->sawanna->db->query("delete from pre_gallery_images where gid='$1'",$id);
            
             // clearing cache
            $this->sawanna->cache->clear_all_by_type('gallery');
            
            $message='[strf]Gallery widget with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
            $event['job']='sawanna_message';
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);
        
        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/gallery');
    }
    
    function gallery_list()
    {
        if (!$this->sawanna->has_access('create gallery')) 
        {
            trigger_error('Permission denied',E_USER_WARNING);
            return ;
        }
        
        $html='<h1 class="tab">[str]Widgets[/str]</h1>';
        $html.='<div id="galleries-list">';
        
        $html.='<div class="gallery-list-item head">';
        $html.='<div class="id">[str]ID[/str]</div>';
        $html.='<div class="title">[str]Widget[/str]</div>';
        $html.='</div>';
            
        $this->sawanna->db->query("select count(*) as co from pre_galleries");
        $co=$this->sawanna->db->fetch_object();
        if (!empty($co)) $this->count=$co->co;
        
        $co=0;
        $this->sawanna->db->query("select * from pre_galleries order by id limit $1 offset $2",$this->limit,$this->start*$this->limit);
        while($row=$this->sawanna->db->fetch_object())
        {
           if ($co%2===0)
           {
               $html.='<div class="gallery-list-item">';
           } else 
           {
               $html.='<div class="gallery-list-item mark highlighted">';
           }
           
            $html.='<div class="id">'.$row->id.'</div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/gallery/delete/'.$row->id,'[str]Delete[/str]','button',array('onclick'=>'if (!confirm(\'[str]Are you sure ?[/str]\')){return false;}')).'</div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/gallery/edit/'.$row->id,'[str]Edit[/str]','button').'</div>';
            $html.='<div class="title">'.htmlspecialchars($row->name).'</div>';
            $html.='</div>';
            
            $co++;
        }
        
        $html.=$this->sawanna->constructor->pager($this->start,$this->limit,$this->count,$this->sawanna->config->admin_area.'/gallery');
        
        $html.='</div>';
        
        echo $html;
    }
    
    function image_form($id=0)
    {
        if (!$this->sawanna->has_access('create gallery')) 
        {
            trigger_error('Permission denied',E_USER_ERROR);
            return ;
        }
        
        $image=new stdClass();
        
        if (!empty($id) && !is_numeric($id)) 
        {
            trigger_error('Invalid image ID: '.$id,E_USER_ERROR);
            return;
        }
        if (!empty($id))
        {
            if (!$this->image_exists($id)) 
            {
                trigger_error('Image not found',E_USER_ERROR);
                return;    
            }
            
            $image=$this->get_existing_image($id);
        }
        
        $fields=array();
        
        $fields['image-tab']=array(
            'type' => 'html',
            'value' => '<h1 class="tab">'.(!isset($image->id) ? '[str]New image[/str]' : '[str]Edit image[/str]').'</h1>'
        );
        
        $galls=array(0=>'[str]Please choose[/str]') + $this->get_galleries();
        
        if (empty($id))
        {
            $fields['image-gallery']=array(
                'type' => 'select',
                'title' => '[str]Gallery[/str]',
                'options' => $galls,
                'description' => '[str]Choose gallery widget[/str]',
                'default' => isset($image->gid) ? $image->gid : '',
                'required' => true
            );
            
            $fields['image-file']=array(
                'type' => 'file',
                'title' => '[str]File[/str]',
                'description' => '[str]Please choose image file[/str]. ([strf]Supported formats: %jpg, png, gif%[/strf])',
                'required' => true
            );
        }
        
        $widget_titles=array();
        if (!empty($image->meta) && isset($image->meta['name']))
        {
            $widget_titles=$image->meta['name'];
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
                   
                $fields['image-title-'.$lcode]=array(
                    'type' => 'text',
                    'title' => '[str]Title[/str]',
                    'description' => '[strf]Specify image title in following language: %'.htmlspecialchars($lname).'%[/strf]',
                    'default' => isset($widget_titles[$lcode]) ? $widget_titles[$lcode] : '',
                    'required' => false
                );
                
                 $fields['title-block-end-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '</div>'
                   );
                
                $co++;
        }
        
        $widget_descs=array();
        if (!empty($image->meta) && isset($image->meta['name']))
        {
            $widget_descs=$image->meta['description'];
        }
        
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
                if ($co%2===0)
               {
                   $fields['desc-block-begin-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '<div class="mark highlighted">'
                   );
               } else 
               {
                    $fields['desc-block-begin-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '<div>'
                   );
               }
                   
                $fields['image-description-'.$lcode]=array(
                    'type' => 'textarea',
                    'title' => '[str]Description[/str]',
                    'description' => '[strf]Specify image description in following language: %'.htmlspecialchars($lname).'%[/strf]. ([strf]Maximum number of characters: %1000%[/strf])',
                    'default' => isset($widget_descs[$lcode]) ? $widget_descs[$lcode] : '',
                    'attributes' => array('style'=>'width: 100%','rows'=>'2','cols'=>'40'),
                    'required' => false
                );
                
                 $fields['desc-block-end-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '</div>'
                   );
                
                $co++;
        }
        
        /*
        $fields['image-weight']=array(
            'type' => 'text',
            'title' => '[str]Weight[/str]',
            'description' => '[str]Lighter images appears first[/str]',
            'default' => isset($image->weight) ? $image->weight: '0',
            'required' => false
        );
        */
        
        $middle_weight=isset($image->weight) ? $image->weight : 0;
        
        $fields['image-weight']=array(
            'type' => 'select',
            'options' => array_combine(range($middle_weight-30,$middle_weight+30,1),range($middle_weight-30,$middle_weight+30,1)),
            'title' => '[str]Weight[/str]',
            'description' => '[str]Lighter images appears first[/str]',
            'default' => isset($image->weight) ? $image->weight: '0',
            'attributes' => array('class'=>'slider'),
            'required' => false
        );
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('image-form',$fields,'image_form_process',$this->sawanna->config->admin_area.'/gallery/images',empty($id) ? $this->sawanna->config->admin_area.'/gallery/image-create' : $this->sawanna->config->admin_area.'/gallery/image-edit/'.$id,'','CGalleryAdmin');
        
        if (!isset($image->id))
        {
            $fields=array();
            
            $fields['archive-tab']=array(
                'type' => 'html',
                'value' => '<h1 class="tab">[str]Upload archive[/str]</h1>'
            );
            
            $fields['image-gallery']=array(
                'type' => 'select',
                'title' => '[str]Gallery[/str]',
                'options' => $galls,
                'description' => '[str]Choose gallery widget[/str]',
                'default' => isset($image->gid) ? $image->gid : '',
                'required' => true
            );
            
            $formats=array();
            
            if (class_exists('ZipArchive')) $formats[]='zip';
            if (empty($formats)) $formats[]='-';
            
            $fields['file-archive']=array(
                'type' => 'file',
                'title' => '[str]File[/str]',
                'description' => '[str]Supported formats[/str]: '.implode(', ',$formats),
                'required' => true
            );
            
            $fields['submit']=array(
                'type'=>'submit',
                'value'=>'[str]Submit[/str]'
            );
            
            echo $this->sawanna->constructor->form('image-archive-form',$fields,'archive_form_process',$this->sawanna->config->admin_area.'/gallery/images',$this->sawanna->config->admin_area.'/gallery/image-create','','CGalleryAdmin');
        }
    }
    
    function image_form_process($id=0)
    {
        if (!$this->sawanna->has_access('create gallery')) 
        {
            return array('[str]Permission denied[/str]',array('create-image'));
        }

        if (empty($id) && preg_match('/^gallery\/image-edit\/(.+)$/',$this->sawanna->arg,$matches)) $id=$matches[1];
        
        if (!empty($id) && !is_numeric($id)) return array('[strf]Invalid image ID: %'.$id.'%[/strf]',array('edit-image'));
        if (!empty($id) && !$this->image_exists($id)) return array('[str]Image not found[/str]',array('edit-image'));    
        
        if (empty($id) && !$this->get_existing_gallery($_POST['image-gallery']))
        {
              return array('[str]Widget not found[/str]',array('image-gallery'));
        }
        
        if (!empty($_POST['image-weight']) && !is_numeric($_POST['image-weight']))
        {
            return array('[str]Invalid image weight[/str]',array('image-weight'));
        }
        
        $image=array();
        
        if (!empty($id)) 
        {
            $image['id']=$id;
            $_image=$this->get_existing_image($id);
            $image['original']=$_image->original;
            $image['thumb']=$_image->thumb;
            $image['image']=$_image->image;
            $image['gid']=$_image->gid;
        }
        
        if (empty($id)) $image['gid']=$_POST['image-gallery'];
        
        if (empty($id)) 
        {
            $save=$this->save_image($_FILES['image-file'],$_POST['image-gallery']);
            if (!$save)
            {
                return array('[str]Could not save uploaded file[/str]',array('image-file'));
            }
            
            list($image['original'],$image['thumb'],$image['image'])=$save;
        }
        
        $image['weight']=!empty($_POST['image-weight']) ? floor($_POST['image-weight']) : 0;
        
        $image['meta']=array('name'=>array(),'description'=>array());
        
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
            if (!empty($_POST['image-title-'.$lcode]))
            {
                if (sawanna_strlen($_POST['image-title-'.$lcode])>255) $_POST['image-title-'.$lcode]=sawanna_substr($_POST['image-title-'.$lcode],0,255);
                $image['meta']['name'][$lcode]=htmlspecialchars($_POST['image-title-'.$lcode]);
            }
            if (!empty($_POST['image-description-'.$lcode])) 
            {
                if (sawanna_strlen($_POST['image-description-'.$lcode])>1000) $_POST['image-description-'.$lcode]=sawanna_substr($_POST['image-description-'.$lcode],0,1000);
                $image['meta']['description'][$lcode]=htmlspecialchars($_POST['image-description-'.$lcode]);
            }
        }
        
        if (!$this->set_image($image))
        {
            return array('[str]Gallery image could not be saved[/str]',array('new-image'));
        }
        
         // clearing cache
         $this->sawanna->cache->clear_all_by_type('gallery');
            
        return array('[str]Gallery image saved[/str]');
    }
    
    function archive_form_process()
    {
        if (!$this->sawanna->has_access('create gallery')) 
        {
            return array('[str]Permission denied[/str]',array('create-image'));
        }

        if (!is_writable(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir)))
        {
            return array('[str]File could not be saved[/str]',array('create-image'));
        }
        
        if (empty($_FILES['file-archive'])) return array('[str]File could not be saved[/str]',array('upload-file'));
        
        if (!$this->get_existing_gallery($_POST['image-gallery']))
        {
              return array('[str]Widget not found[/str]',array('image-gallery'));
        }
        
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir)))
        {
           mkdir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir));
        }
        
        $tmp_dir=quotemeta($this->root_dir).'/archive_import';
        
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$tmp_dir))
        {
            mkdir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$tmp_dir,0777);
        } elseif (!is_writable(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$tmp_dir))
        {
            return array('[str]File could not be saved[/str]',array('create-image'));
        }
        
        if (copy($_FILES['file-archive']['tmp_name'],ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.$_FILES['file-archive']['name']))
        {
            @ini_set('max_execution_time',0);
            
            $ext=substr($_FILES['file-archive']['name'],strrpos($_FILES['file-archive']['name'],'.')+1);
            
            switch ($ext)
            {
                case 'zip':
                    if (class_exists('ZipArchive'))
                    {
                    
                        //chmod(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$_FILES['file-archive']['name'],0777);
                        
                        $zip = new ZipArchive();
                        if ($zip->open(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.$_FILES['file-archive']['name']) === true) 
                        {
                            $zip->extractTo(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$tmp_dir);
                            $zip->close();
                        }
                    } else
                    {
                        return array('[str]Archive type not supported[/str]',array('create-image'));
                    }
                    break;
                default:
                    return array('[str]Archive type not supported[/str]',array('create-image'));
                    break;
            }
            
            $this->import_folder($tmp_dir,$_POST['image-gallery']);
        }
        
        return array('[str]Archive imported successfully[/str]');
    }
    
    function import_folder($folder_src,$gallery_id)
    {
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_src)) return false;
        
        $dir=opendir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_src);
        if (!$dir) return false;
        
        while(($file=readdir($dir))!==false)
        {
            if ($file=='.' || $file=='..') continue;
            
            if (is_dir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_src.'/'.$file))
            {
                $this->import_folder($folder_src.'/'.$file,$gallery_id);
                continue;
            }
            
            $FILE=array(
                'tmp_name' => ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_src.'/'.$file,
                'name' => $file,
                'type' => filetype(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_src.'/'.$file),
                'size' => filesize(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_src.'/'.$file)
            );
            
            $save=$this->save_image($FILE,$gallery_id);
            if ($save)
            {
                list($image['original'],$image['thumb'],$image['image'])=$save;
                
                $image['gid']=$gallery_id;
                
                $image['weight']=0;
        
                $image['meta']=array('name'=>array(),'description'=>array());

                $this->set_image($image);
            }
            
            @unlink(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_src.'/'.$file);
        }
        
        closedir($dir);
        
        @rmdir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_src);
    }
    
    function save_image($file,$gid)
    {
        if (!is_writeable(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir))) return false;
        
        if (empty($file) || !is_array($file) || empty($file['tmp_name']) || empty($gid) || !is_numeric($gid)) return false;
        
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir)))
        {
           mkdir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir));
        }
        
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.quotemeta($this->thumbs_dir)))
        {
           mkdir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.quotemeta($this->thumbs_dir));
        }
        
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.quotemeta($this->img_dir)))
        {
           mkdir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.quotemeta($this->img_dir));
        }
        
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.quotemeta($this->original_dir)))
        {
           mkdir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.quotemeta($this->original_dir));
        }
        
        $gal=$this->get_existing_gallery($gid);
        if (empty($gal) || empty($gal->name)) return false;
        
        $sz=getimagesize($file['tmp_name']);
		if (is_array($sz)) {
			$d=$sz[1]/$sz[0];
			
			//$this->thumb_height=round($this->thumb_width*$d);
			
			if ($sz[0]<$this->img_width) {
				$this->img_width=$sz[0];
				$this->img_height=round($this->img_width*$d);
			}
			
			if ($this->img_ratio) {
				$this->thumb_height=round($this->thumb_width*$d);
				$this->img_height=round($this->img_width*$d);
			}
			if ($this->img_size) {
				$this->img_width=$sz[0];
				$this->img_height=$sz[1];
			}
		}
        
        if (!$this->sawanna->file->save($file,'gallery-'.$gal->name,$this->root_dir.'/'.$this->original_dir)) return false;
        $original=$this->sawanna->file->last_name;
        
        if (!$this->sawanna->file->save($file,'gallery-'.$gal->name,$this->root_dir.'/'.$this->thumbs_dir)) return false;
        if (!$this->sawanna->constructor->resize_image(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.quotemeta($this->thumbs_dir).'/'.quotemeta($this->sawanna->file->last_name),ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.quotemeta($this->thumbs_dir).'/'.quotemeta($this->sawanna->file->last_name),$this->thumb_width,$this->thumb_height,$this->thumb_crop))
        {
            $new_id=$this->sawanna->file->get_id_by_name($this->sawanna->file->last_name);
            $this->sawanna->file->delete($new_id);
            $this->sawanna->file->delete($this->sawanna->file->get_id_by_name($original));
            return false;
        }
        $thumb=$this->sawanna->file->last_name;
        
        if (!$this->sawanna->file->save($file,'gallery-'.$gal->name,$this->root_dir.'/'.$this->img_dir)) return false;
        if (!$this->sawanna->constructor->resize_image(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.quotemeta($this->img_dir).'/'.quotemeta($this->sawanna->file->last_name),ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.quotemeta($this->img_dir).'/'.quotemeta($this->sawanna->file->last_name),$this->img_width,$this->img_height,$this->img_crop))
        {
            $new_id=$this->sawanna->file->get_id_by_name($this->sawanna->file->last_name);
            $this->sawanna->file->delete($new_id);
            return false;
        }
        $img=$this->sawanna->file->last_name;
        
        return array($original,$thumb,$img);
    }
    
    function set_image($image)
    {
        if (empty($image) || !is_array($image)) return false;
        
        if (empty($image['gid']) || empty($image['original']) || empty($image['thumb']) || empty($image['image']))
        {
            trigger_error('Cannot set gallery image. Invalid data given',E_USER_WARNING);
            return false;
        }
        
        if (!isset($image['weight'])) $image['weight']=0;
        if (!isset($image['meta'])) $image['meta']='';
        
        $d=new CData();
        $image['meta']=$d->do_serialize($image['meta']);
        
        if (isset($image['id']))
        {
            return $this->sawanna->db->query("update pre_gallery_images set gid='$1', original='$2', thumb='$3', image='$4', meta='$5', weight='$6' where id='$7'",$image['gid'],$image['original'],$image['thumb'],$image['image'],$image['meta'],$image['weight'],$image['id']);
        } else 
        {
            return $this->sawanna->db->query("insert into pre_gallery_images (gid,original,thumb,image,meta,weight) values ('$1','$2','$3','$4','$5','$6')",$image['gid'],$image['original'],$image['thumb'],$image['image'],$image['meta'],$image['weight']);
        }
    }
    
    function delete_image($id)
    {
        if (empty($id)) return false;

        if ($this->sawanna->db->query("delete from pre_gallery_images where id='$1'",$id))
        {
            return true;
        }
        
        return false;
    }
    
    function image_delete($id)
    {
        if (!$this->sawanna->has_access('create gallery')) 
        {
            trigger_error('Could not delete gallery image. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not delete gallery image. Invalid widget ID: '.$id,E_USER_WARNING);
            return ;
        }
        if (!$this->image_exists($id)) 
        {
            trigger_error('Could not delete gallery image. Image not found',E_USER_WARNING);    
            return;
        }
        
        $image=$this->get_existing_image($id);
        
        $event=array();
        
        if (!$this->delete_image($id))
        {
            $message='[str]Could not delete gallery image. Database error[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
            $this->sawanna->file->delete($this->sawanna->file->get_id_by_name($image->original));
            $this->sawanna->file->delete($this->sawanna->file->get_id_by_name($image->thumb));
            $this->sawanna->file->delete($this->sawanna->file->get_id_by_name($image->image));
            
             // clearing cache
            $this->sawanna->cache->clear_all_by_type('gallery');
            
            $message='[strf]Gallery image with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
            $event['job']='sawanna_message';
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);

        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/gallery/images');
    }
    
    function bulk_image_delete()
    {
        if (!$this->sawanna->has_access('create gallery')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
                if (!$this->image_exists(trim($id))) continue;
                
                $image=$this->get_existing_image(trim($id));
                
                $this->delete_image(trim($id));
                
                $this->sawanna->file->delete($this->sawanna->file->get_id_by_name($image->original));
                $this->sawanna->file->delete($this->sawanna->file->get_id_by_name($image->thumb));
                $this->sawanna->file->delete($this->sawanna->file->get_id_by_name($image->image));
            }

             // clearing cache
            $this->sawanna->cache->clear_all_by_type('gallery');
            
            echo $this->sawanna->locale->get_string('Images were deleted');
        } else
        {
            echo $this->sawanna->locale->get_string('Images were not deleted');
        }
        
        die();
    }
    
    function update_images_form()
    {
        if (!$this->sawanna->has_access('create gallery')) 
        {
            trigger_error('Permission denied',E_USER_ERROR);
            return ;
        }
        
        $fields=array();
        
        $fields['image-tab']=array(
            'type' => 'html',
            'value' => '<h1 class="tab">[str]Update images[/str]</h1>'
        );
        
        $galls=array(0=>'[str]Please choose[/str]') + $this->get_galleries();
        
		$fields['image-gallery']=array(
			'type' => 'select',
			'title' => '[str]Gallery[/str]',
			'options' => $galls,
			'description' => '[str]Choose gallery widget[/str]',
			'default' => '',
			'required' => true
		);
        
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
                   
                $fields['image-title-'.$lcode]=array(
                    'type' => 'text',
                    'title' => '[str]Title[/str]',
                    'description' => '[strf]Specify image title in following language: %'.htmlspecialchars($lname).'%[/strf]',
                    'default' => '',
                    'required' => false
                );
                
                 $fields['title-block-end-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '</div>'
                   );
                
                $co++;
        }
        
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
                if ($co%2===0)
               {
                   $fields['desc-block-begin-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '<div class="mark highlighted">'
                   );
               } else 
               {
                    $fields['desc-block-begin-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '<div>'
                   );
               }
                   
                $fields['image-description-'.$lcode]=array(
                    'type' => 'textarea',
                    'title' => '[str]Description[/str]',
                    'description' => '[strf]Specify image description in following language: %'.htmlspecialchars($lname).'%[/strf]. ([strf]Maximum number of characters: %1000%[/strf])',
                    'default' => '',
                    'attributes' => array('style'=>'width: 100%','rows'=>'2','cols'=>'40'),
                    'required' => false
                );
                
                 $fields['desc-block-end-'.$lcode]=array(
                        'type' => 'html',
                        'value' => '</div>'
                   );
                
                $co++;
        }
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('update-images-form',$fields,'update_images_form_process',$this->sawanna->config->admin_area.'/gallery/images',$this->sawanna->config->admin_area.'/gallery/update-images','','CGalleryAdmin');
    }
    
    function update_images_form_process()
    {
        if (!$this->sawanna->has_access('create gallery')) 
        {
            return array('Permission denied','update-image');
        }
        
        if (!$this->get_existing_gallery($_POST['image-gallery']))
        {
              return array('[str]Widget not found[/str]',array('image-gallery'));
        }
        
        $meta=array('name'=>array(),'description'=>array());
        
        foreach ($this->sawanna->config->languages as $lcode=>$lname)
        {
            if (!empty($_POST['image-title-'.$lcode]))
            {
                if (sawanna_strlen($_POST['image-title-'.$lcode])>255) $_POST['image-title-'.$lcode]=sawanna_substr($_POST['image-title-'.$lcode],0,255);
                $meta['name'][$lcode]=htmlspecialchars($_POST['image-title-'.$lcode]);
            }
            if (!empty($_POST['image-description-'.$lcode])) 
            {
                if (sawanna_strlen($_POST['image-description-'.$lcode])>1000) $_POST['image-description-'.$lcode]=sawanna_substr($_POST['image-description-'.$lcode],0,1000);
                $meta['description'][$lcode]=htmlspecialchars($_POST['image-description-'.$lcode]);
            }
        }
        
        $d=new CData();
        $meta=$d->do_serialize($meta);
        
        $result=$this->sawanna->db->query("update pre_gallery_images set meta='$1' where gid='$2'",$meta,$_POST['image-gallery']);

		// clearing cache
		$this->sawanna->cache->clear_all_by_type('gallery');
		
        if ($result) {
            return array('[str]Images were update[/str]');
        } else
        {
            return array('[str]Images were not updated[/str]','update-image');
        }
    }
    
    function get_galleries()
    {
        $galleries=array();
        $d=new CData();
        
        $this->sawanna->db->query("select * from pre_galleries order by id");
        while ($row=$this->sawanna->db->fetch_object())
        {
            if (!empty($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            $galleries[$row->id]=isset($row->meta['name'][$this->sawanna->locale->current]) ? $row->meta['name'][$this->sawanna->locale->current] : $row->name;
        }
        
        return $galleries;
    }
    
     function image_list($arg=0)
    {
        if (!$this->sawanna->has_access('create gallery')) 
        {
            trigger_error('Permission denied',E_USER_WARNING);
            return ;
        }
        
        $d=new CData();
        
        $html='<h1 class="tab">[str]Gallery images[/str]</h1>';
        $html.='<div id="images-list">';

        $galleries=array();
        $this->sawanna->db->query("select * from pre_galleries order by id");
        while ($row=$this->sawanna->db->fetch_object()) 
        {
            if (!empty($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            $gal_title=!empty($row->meta['name'][$this->sawanna->locale->current]) ? $row->meta['name'][$this->sawanna->locale->current] : $row->name;
            $galleries[$row->name]=$gal_title;
        }
        
        if (count($galleries)>1)
        {
            $html.='<div id="galleries-form">';
            $html.='<div class="form_title">[str]Gallery[/str]:</div>';
            $html.='<select name="gallery" id="gallery-form" onchange="select_gallery()">';
            
            if ($arg=='all')
                $html.='<option value="all" selected>[str]All galleries[/str]</option>';
            else 
                $html.='<option value="all">[str]All galleries[/str]</option>';
                
            foreach ($galleries as $gname=>$gtitle)
            {
                if ($gname==$arg)
                    $html.='<option value="'.$gname.'" selected>'.$gtitle.'</option>';
                else 
                    $html.='<option value="'.$gname.'">'.$gtitle.'</option>';
            }
            $html.= '</select>';
            $html.='<script language="JavaScript">function select_gallery() { window.location.href="'.$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/gallery/images').'/"+$("#gallery-form").get(0).options[$("#gallery-form").get(0).selectedIndex].value; }</script>';
            $html.='</div>';
        }
        
        $html.='<div class="images-list-item head">';
        $html.='<div class="id"></div>';
        $html.='<div class="image">[str]Image[/str]</div>';
        $html.='</div>';
        
        if ($arg!='all')
        {
            $this->sawanna->db->query("select count(*) as co from pre_gallery_images i left join pre_galleries g on g.id=i.gid where g.name='$1'",$arg);
            $co=$this->sawanna->db->fetch_object();
            if (!empty($co)) $this->count=$co->co;
            
            $res=$this->sawanna->db->query("select i.id,i.original, i.thumb, i.image, i.meta, g.name, g.meta as gmeta from pre_gallery_images i left join pre_galleries g on i.gid=g.id where g.name='$1' order by i.weight limit $2 offset $3",$arg,$this->limit,$this->start*$this->limit);
        } else 
        {
            $this->sawanna->db->query("select count(*) as co from pre_gallery_images");
            $co=$this->sawanna->db->fetch_object();
            if (!empty($co)) $this->count=$co->co;
            
            $res=$this->sawanna->db->query("select i.id,i.original, i.thumb, i.image, i.meta, g.name, g.meta as gmeta from pre_gallery_images i left join pre_galleries g on i.gid=g.id order by i.weight limit $1 offset $2",$this->limit,$this->start*$this->limit);
        }
        
        $co=0;
        while($img=$this->sawanna->db->fetch_object($res))
        {
            if ($co%2===0)
            {
                $html.='<div class="images-list-item">';
            } else 
            {
                $html.='<div class="images-list-item mark highlighted">';
            }
            
            $gname=!empty($img->name) ? $img->name : '-';
            $iname='-';
            $idesc='-';
            $ipath=SITE.'/misc/document.png';
            
            if (!empty($img->meta)) $img->meta=$d->do_unserialize($img->meta);
            if (!empty($img->gmeta)) $img->gmeta=$d->do_unserialize($img->gmeta);
            
            if (!empty($img->gmeta['name'][$this->sawanna->locale->current])) $gname=$img->gmeta['name'][$this->sawanna->locale->current];
            if (!empty($img->meta['name'][$this->sawanna->locale->current])) $iname=$img->meta['name'][$this->sawanna->locale->current];
            if (!empty($img->meta['description'][$this->sawanna->locale->current])) $idesc=$img->meta['description'][$this->sawanna->locale->current];
                
            if (file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.quotemeta($this->thumbs_dir).'/'.quotemeta($img->thumb))) $ipath=SITE.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.quotemeta($this->thumbs_dir).'/'.quotemeta($img->thumb);
            
            $gal='<div class="gallery"><label>[str]Gallery[/str]: </label>'.$gname.'</div>';
            $title='<div class="title"><label>[str]Title[/str]: </label>'.$iname.'</div>';
            $description='<div class="description"><label>[str]Description[/str]: </label>'.$idesc.'</div>';
            $preview='<div class="preview"><img src="'.$ipath.'" width="'.$this->thumb_width.'" height="'.$this->thumb_height.'" /></div>';
            
            $html.='<div class="id"><input type="checkbox" name="bulk_id[]" value="'.$img->id.'" class="bulk_id_checkbox" /></div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/gallery/image-delete/'.$img->id,'[str]Delete[/str]','button',array('onclick'=>'if (!confirm(\'[str]Are you sure ?[/str]\')){return false;}')).'</div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/gallery/image-edit/'.$img->id,'[str]Edit[/str]','button').'</div>';
            $html.='<div class="image">'.$gal.$preview.$title.$description.'</div>';
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
            $html.="<script language=\"javascript\">function exec_bulk_action(){ var act=$('#bulk_action_selector').val(); var ids=''; if (act.length>0) { $('input[type=checkbox].bulk_id_checkbox').each(function(){ if ($(this).get(0).checked==true) { if (ids.length>0){ids=ids+',';}; ids=ids+$(this).val(); } }); if (ids.length>0 && confirm('[str]Are you sure ?[/str]')) { $.post('".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/gallery/image-bulkdel')."',{bulk_ids:ids},function(data){ alert(data); window.location.href='".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/'.$this->sawanna->arg)."'; }); $('#bulk_action_selector').get(0).disabled=true; } } $('#bulk_action_selector').get(0).selectedIndex=0; refresh_select($('#bulk_action_selector')); }</script>";
        }
        
        $html.=$this->sawanna->constructor->pager($this->start,$this->limit,$this->count,$arg=='all' ? $this->sawanna->config->admin_area.'/gallery/images' : $this->sawanna->config->admin_area.'/gallery/images/'.htmlspecialchars($arg));
        
        echo $html;
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
            'value' => '<h1 class="tab">[str]Gallery settings[/str]</h1>'
        );
        
        $parse=$this->sawanna->get_option('gallery_parse_node');
        
        $fields['settings-parse']=array(
            'type' => 'checkbox',
            'title' => '[str]Parse node images[/str]',
            'description' => '[strf]Images loaded via %`image`% path will be replaced with gallery thumbnail[/strf]',
            'attributes'=> $parse ? array('checked'=>'checked') : array()
        );
        
        $thumb_width=$this->sawanna->get_option('gallery_thumbnail_width');
        
        $fields['settings-thumb_width']=array(
            'type' => 'text',
            'title' => '[str]Thumbnail width[/str]',
            'description' => '[str]Specify the image thumbnails width[/str]',
            'default' => $thumb_width ? $thumb_width : '120'
        );
        
       $thumb_height=$this->sawanna->get_option('gallery_thumbnail_height');
        
        $fields['settings-thumb_height']=array(
            'type' => 'text',
            'title' => '[str]Thumbnail height[/str]',
            'description' => '[str]Specify the image thumbnails height[/str]',
            'default' => $thumb_height ? $thumb_height : '90'
        );
        
        $thumb_crop=$this->sawanna->get_option('gallery_thumbnail_crop');
        
        $fields['settings-thumb_crop']=array(
            'type' => 'checkbox',
            'title' => '[str]Crop thumbnail[/str]',
            'description' => '[str]Specify whether to crop the image to keep its proportion[/str]',
            'attributes'=> $thumb_crop ? array('checked'=>'checked') : array()
        );

        $image_width=$this->sawanna->get_option('gallery_image_width');
        
        $fields['settings-image_width']=array(
            'type' => 'text',
            'title' => '[str]Image width[/str]',
            'description' => '[str]Specify the image width[/str]',
            'default' => $image_width ? $image_width : '800'
        );
        
       $image_height=$this->sawanna->get_option('gallery_image_height');
        
        $fields['settings-image_height']=array(
            'type' => 'text',
            'title' => '[str]Image height[/str]',
            'description' => '[str]Specify the image height[/str]',
            'default' => $image_height ? $image_height : '600'
        );
        
        $image_crop=$this->sawanna->get_option('gallery_image_crop');
        
        $fields['settings-image_crop']=array(
            'type' => 'checkbox',
            'title' => '[str]Crop image[/str]',
            'description' => '[str]Specify whether to crop the image to keep its proportion[/str]',
            'attributes'=> $image_crop ? array('checked'=>'checked') : array()
        );
        
        $image_ratio=$this->sawanna->get_option('gallery_image_ratio');
        
        $fields['settings-image_ratio']=array(
            'type' => 'checkbox',
            'title' => '[str]Keep image ratio[/str]',
            'description' => '[str]Specify whether to keep image ratio[/str]',
            'attributes'=> $image_ratio ? array('checked'=>'checked') : array()
        );
        
        $image_size=$this->sawanna->get_option('gallery_image_size');
        
        $fields['settings-image_size']=array(
            'type' => 'checkbox',
            'title' => '[str]Keep image original size[/str]',
            'description' => '[str]Specify whether to keep image original size[/str]',
            'attributes'=> $image_size ? array('checked'=>'checked') : array()
        );
        
        $image_interval=$this->sawanna->get_option('gallery_widget_interval');
        
        $fields['settings-interval']=array(
            'type' => 'text',
            'title' => '[str]Widget update interval[/str]',
            'description' => '[str]Specify the interval in seconds for loading widget images[/str]',
            'default' => $image_interval ? $image_interval : '5'
        );
        
        if ($this->sawanna->module->module_exists("code")) {
			$gallery_html_widget=$this->sawanna->get_option('gallery_html_widget');
			
			$codes=array(0=>'[str]Please choose widget[/str]');
			$this->sawanna->db->query("select * from pre_cwidgets order by id");
			while($row=$this->sawanna->db->fetch_object())
			{
				$codes[$row->id]=$row->name;
			}
			
			$fields['settings-gallery-html']=array(
                'type' => 'select',
                'title' => '[str]Show HTML widget layer[/str]',
                'options' => $codes,
                'description' => '[str]Choose html widget[/str]',
                'default' => $gallery_html_widget ? $gallery_html_widget : 0,
                'required' => false
            );
		}
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('settings-form',$fields,'settings_form_process',$this->sawanna->config->admin_area.'/gallery/settings',$this->sawanna->config->admin_area.'/gallery/settings','','CGalleryAdmin');
    }
    
    function settings_form_process()
    {
        if (!$this->sawanna->has_access('su')) 
        {
            return array('[str]Permission denied[/str]',array('gallery-settings'));
        }
        
         if (isset($_POST['settings-parse']) && $_POST['settings-parse']=='on')
        {
            $this->sawanna->set_option('gallery_parse_node',1,$this->module_id);
        } else 
        {
            $this->sawanna->set_option('gallery_parse_node',0,$this->module_id);
        }
        
        if (isset($_POST['settings-thumb_width']) && is_numeric($_POST['settings-thumb_width']) && $_POST['settings-thumb_width']>0 && $_POST['settings-thumb_width']<=800)
        {
            $this->sawanna->set_option('gallery_thumbnail_width',ceil($_POST['settings-thumb_width']),$this->module_id);
        } else 
        {
            $this->sawanna->set_option('gallery_thumbnail_width',120,$this->module_id);
        }
        
        if (isset($_POST['settings-thumb_height']) && is_numeric($_POST['settings-thumb_height']) && $_POST['settings-thumb_height']>0 && $_POST['settings-thumb_height']<=600)
        {
            $this->sawanna->set_option('gallery_thumbnail_height',ceil($_POST['settings-thumb_height']),$this->module_id);
        } else 
        {
            $this->sawanna->set_option('gallery_thumbnail_height',90,$this->module_id);
        }
        
        if (isset($_POST['settings-thumb_crop']) && $_POST['settings-thumb_crop']=='on')
        {
            $this->sawanna->set_option('gallery_thumbnail_crop',1,$this->module_id);
        } else 
        {
            $this->sawanna->set_option('gallery_thumbnail_crop',0,$this->module_id);
        }
        
        if (isset($_POST['settings-image_width']) && is_numeric($_POST['settings-image_width']) && $_POST['settings-image_width']>0 && $_POST['settings-image_width']<=1280)
        {
            $this->sawanna->set_option('gallery_image_width',ceil($_POST['settings-image_width']),$this->module_id);
        } else 
        {
            $this->sawanna->set_option('gallery_image_width',400,$this->module_id);
        }
        
        if (isset($_POST['settings-image_height']) && is_numeric($_POST['settings-image_height']) && $_POST['settings-image_height']>0 && $_POST['settings-image_height']<=1024)
        {
            $this->sawanna->set_option('gallery_image_height',ceil($_POST['settings-image_height']),$this->module_id);
        } else 
        {
            $this->sawanna->set_option('gallery_image_height',300,$this->module_id);
        }
        
        if (isset($_POST['settings-image_crop']) && $_POST['settings-image_crop']=='on')
        {
            $this->sawanna->set_option('gallery_image_crop',1,$this->module_id);
        } else 
        {
            $this->sawanna->set_option('gallery_image_crop',0,$this->module_id);
        }
        
        if (isset($_POST['settings-image_ratio']) && $_POST['settings-image_ratio']=='on')
        {
            $this->sawanna->set_option('gallery_image_ratio',1,$this->module_id);
        } else 
        {
            $this->sawanna->set_option('gallery_image_ratio',0,$this->module_id);
        }
        
        if (isset($_POST['settings-image_size']) && $_POST['settings-image_size']=='on')
        {
            $this->sawanna->set_option('gallery_image_size',1,$this->module_id);
        } else 
        {
            $this->sawanna->set_option('gallery_image_size',0,$this->module_id);
        }
        
        if (isset($_POST['settings-interval']) && is_numeric($_POST['settings-interval']) && $_POST['settings-interval']>0 && $_POST['settings-interval']<=3600)
        {
            $this->sawanna->set_option('gallery_widget_interval',ceil($_POST['settings-interval']),$this->module_id);
        } else 
        {
            $this->sawanna->set_option('gallery_widget_interval',5,$this->module_id);
        }
        
        if ($this->sawanna->module->module_exists('code')) {
			if (isset($_POST['settings-gallery-html']) && is_numeric($_POST['settings-gallery-html']) && $_POST['settings-gallery-html']>0)
			{
				$this->sawanna->set_option('gallery_html_widget',ceil($_POST['settings-gallery-html']),$this->module_id);
			} else 
			{
				$this->sawanna->set_option('gallery_html_widget',0,$this->module_id);
			}
		}
        
        // clearing cache
        $this->sawanna->cache->clear_all_by_type('gallery');
            
        return array('[str]Settings saved[/str]');
    }   
}
