<?php
defined('SAWANNA') or die();

class CFilesAdmin
{
    var $sawanna;
    var $module_id;
    
    function CFilesAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('files');
    }
    
    function adminbar()
    {
        if (!$this->sawanna->has_access('upload files')) return;
            
        return array(
                        array(
                        'files/upload'=>'[str]Upload[/str]',
                        'files'=>'[str]Files[/str]'
                        ),
                        'files'
                        );
    }
    
    function admin_area()
    {
        if (!$this->sawanna->has_access('upload files')) return ;
        
        $this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
        
        switch (true)
        {
            case $this->sawanna->arg=='files/upload':
                $this->file_form();
                break;
            case preg_match('/^files\/edit\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->file_form($matches[1]);
                break;
            case preg_match('/^files\/delete\/([a-zA-Z0-9_-]+)$/',$this->sawanna->arg,$matches):
                $this->file_delete($matches[1]);
                break;
            case preg_match('/^files\/bulkdel$/',$this->sawanna->arg,$matches):
                $this->bulk_delete();
                break;
            case $this->sawanna->arg=='files':
                $this->files_list();
                break;
            case preg_match('/^files\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->files_list('',$matches[1]);
                break;
            case preg_match('/^files\/([a-zA-Z0-9_-]+)$/',$this->sawanna->arg,$matches):
                $this->files_list($matches[1]);
                break;
            case preg_match('/^files\/([a-zA-Z0-9_-]+)\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->files_list($matches[1],$matches[2]);
                break;
        }
    }
    
    function valid_alias($name)
    {
        if (!preg_match('/^[a-z0-9\-_]{3,}$/',$name)) return false;
        
        return true;
    }
    
    function file_alias_check()
    {
        if (!isset($_POST['q'])) return;
        
        if (!$this->valid_alias($_POST['q'])) echo $this->sawanna->locale->get_string('Invalid name');
    }
    
    function valid_filename($name)
    {
        $bad_names=array('create','edit','delete');
        
        if (!preg_match('/^[a-z0-9_]{3,}$/',$name)) return false;
        if (in_array($name,$bad_names)) return false;
        
        return true;
    }
    
    function file_name_check()
    {
        if (!isset($_POST['q'])) return;
        
        if (!$this->valid_filename($_POST['q'])) echo $this->sawanna->locale->get_string('Invalid name');
    }
    
    function file_form($id=0)
    {
        if (!$this->sawanna->has_access('upload files')) 
        {
            trigger_error('Permission denied',E_USER_ERROR);
            return ;
        }
        
        $menu=new stdClass();
        
        if (!empty($id) && !is_numeric($id)) 
        {
            trigger_error('Invalid file ID: '.$id,E_USER_ERROR);
            return;
        }
        if (!empty($id))
        {
            $file=$this->sawanna->file->get($id);
            if (!$file) 
            {
                trigger_error('File not found',E_USER_ERROR);
                return;    
            }
        }
        
        $fields=array();
        
        $fields['file-tab']=array(
            'type' => 'html',
            'value' => '<h1 class="tab">'.(!isset($file->id) ? '[str]Upload file[/str]' : '[str]Edit file attributes[/str]').'</h1>'
        );
        
        $dirs=array(''=>$this->sawanna->file->root_dir);
        $dirs=$dirs+$this->sawanna->file->get_sub_directories($this->sawanna->file->root_dir);

        $fields['file-dir']=array(
            'type' => 'select',
            'options' => $dirs,
            'title' => '[str]Directory[/str]',
            'description' => '[str]Choose upload directory[/str]',
            'default' => isset($file->path) ? $file->path : '' 
        );
        
        
        $fields['file-nicename']=array(
            'type' => 'text',
            'title' => '[str]Alias[/str]',
            'description' => '[str]Specify filename alias[/str]',
            'autocheck' => array('url'=>'file-alias-check','caller'=>'filesAdminHandler','handler'=>'file_alias_check'),
            'default' => isset($file->nicename) ? $file->nicename : '',
            'required' => false
        );

        if (empty($id))
        {
            $fields['file-file']=array(
                'type' => 'file',
                'title' => '[str]File[/str]',
                'description' => '[str]Choose file to upload[/str]',
                'attributes' => array('style'=>'width: 100%'),
                'required' => true
            );
        }
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('files-form',$fields,'file_form_process',$this->sawanna->config->admin_area.'/files',empty($id) ? $this->sawanna->config->admin_area.'/files/upload' : $this->sawanna->config->admin_area.'/files/edit/'.$id,'','CFilesAdmin');
        
        if (!isset($file->id))
        {
            $fields=array();
            
            $fields['archive-tab']=array(
                'type' => 'html',
                'value' => '<h1 class="tab">[str]Upload archive[/str]</h1>'
            );

            $formats=array();
            
            if (class_exists('ZipArchive')) $formats[]='zip';
            if (empty($formats)) $formats[]='-';

            $fields['file-archive']=array(
                'type' => 'file',
                'title' => '[str]File[/str]',
                'description' => '[str]Supported formats[/str]: '.implode(', ',$formats),
                'attributes' => array('style'=>'width: 100%'),
                'required' => true
            );
            
            $fields['submit']=array(
                'type'=>'submit',
                'value'=>'[str]Submit[/str]'
            );

            echo $this->sawanna->constructor->form('archive-form',$fields,'archive_form_process',$this->sawanna->config->admin_area.'/files',$this->sawanna->config->admin_area.'/files/upload','','CFilesAdmin');
        }
    }
    
    function file_form_process($id=0)
    {
        if (!$this->sawanna->has_access('upload files')) 
        {
            return array('[str]Permission denied[/str]',array('upload-file'));
        }

        if (empty($id) && preg_match('/^files\/edit\/(.+)$/',$this->sawanna->arg,$matches)) $id=$matches[1];
        
        if (!empty($id) && !is_numeric($id)) return array('[strf]Invalid file ID: %'.$id.'%[/strf]',array('edit-file'));
        if (!empty($id))
        {
            $file=$this->sawanna->file->get($id);
            if (!$file) return array('[str]File not found[/str]',array('edit-file'));    
            
            $meta=$file->meta;
        }
        
        $dir='';
        if (!empty($_POST['file-dir']))
        {
            $dir=quotemeta($_POST['file-dir']);
            if (substr($dir,0,1)=='/') $dir=substr($dir,1);
            if (substr($dir,-1)=='/') $dir=substr($dir,0,sawanna_strlen($dir)-1);
            
            if (!file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($_POST['file-dir'])) || !is_dir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($_POST['file-dir'])))
            {
                return array('[str]Invalid directory[/str]',array('file-dir'));
            }
        }
        
        $alias='';
        if (!empty($_POST['file-nicename']))
        {
            $alias=quotemeta($_POST['file-nicename']);
            if (substr($alias,0,1)=='/') $alias=substr($alias,1);
            if (substr($alias,-1)=='/') $alias=substr($alias,0,sawanna_strlen($alias)-1);
            
            if (!$this->valid_alias($alias))
            {
                return array('[str]Invalid name[/str]',array('file-nicename'));
            }
            
            $this->sawanna->file->fix_alias($alias);
        }

        if (!empty($id) && !$this->sawanna->file->save($file->id,$alias,$dir,$file->meta)) 
        {
            return array('[str]File could not be edited[/str]',array('edit-file'));
        }
        
        if (empty($id))
        {
            if (empty($_FILES['file-file'])) return array('[str]File could not be saved[/str]',array('upload-file'));
            
            if (!$this->sawanna->file->save($_FILES['file-file'],$alias,$dir)) return array('[str]File could not be saved[/str]',array('upload-file'));
        }
        
        return array('[str]File saved[/str]');
    }
    
    function archive_form_process()
    {
        if (!$this->sawanna->has_access('upload files')) 
        {
            return array('[str]Permission denied[/str]',array('upload-file'));
        }

        if (!is_writable(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir)))
        {
            return array('[str]File could not be saved[/str]',array('upload-file'));
        }
        
        if (empty($_FILES['file-archive'])) return array('[str]File could not be saved[/str]',array('upload-file'));
        
        $tmp_dir='archive_import';
        
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$tmp_dir))
        {
            mkdir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$tmp_dir,0777);
        } elseif (!is_writable(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$tmp_dir))
        {
            return array('[str]File could not be saved[/str]',array('upload-file'));
        }
        
        if (copy($_FILES['file-archive']['tmp_name'],ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$_FILES['file-archive']['name']))
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
                        if ($zip->open(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$_FILES['file-archive']['name']) === true) 
                        {
                            $zip->extractTo(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$tmp_dir);
                            $zip->close();
                        }
                    } else
                    {
                        return array('[str]Archive type not supported[/str]',array('upload-file'));
                    }
                    break;
                default:
                    return array('[str]Archive type not supported[/str]',array('upload-file'));
                    break;
            }
            
            $this->import_folder('',$tmp_dir);
        }
        
        return array('[str]Archive extracted successfully[/str]');
    }
    
    function import_folder($folder_dst,$folder_src)
    {
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_src)) return false;
        
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_dst))
        {
            mkdir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_dst,0777);
        } elseif (!is_writable(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_dst))
        {
            return false;
        }
        
        $dir=opendir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_src);
        if (!$dir) return false;
        
        while(($file=readdir($dir))!==false)
        {
            if ($file=='.' || $file=='..') continue;
            
            if (is_dir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_src.'/'.$file))
            {
                $this->import_folder($folder_dst.'/'.$file,$folder_src.'/'.$file);
                continue;
            }
            
            $FILE=array(
                'tmp_name' => ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_src.'/'.$file,
                'name' => $file,
                'type' => filetype(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_src.'/'.$file),
                'size' => filesize(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_src.'/'.$file)
            );
            
            $this->sawanna->file->save($FILE,'',$folder_dst);
            
            @unlink(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_src.'/'.$file);
        }
        
        closedir($dir);
        
        @rmdir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$folder_src);
    }
    
    function file_delete($id)
    {
        if (!$this->sawanna->has_access('upload files')) 
        {
            trigger_error('Could not delete file. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id)) 
        {
            trigger_error('Could not delete file or directory. Invalid ID: '.$id,E_USER_WARNING);
            return ;
        }
        
        $suffix='';
        
        if (is_numeric($id))
        {
            $file=$this->sawanna->file->get($id);
            if (empty($file))
            {
                trigger_error('Could not delete file. File not found',E_USER_WARNING);
                return ;
            }
            
            $suffix='/'.str_replace('/','-',$file->path);
            
            if (!$this->sawanna->file->delete($id))
            {
                $message='[str]Could not delete file.[/str]';    
                $event['job']='sawanna_error';
            } else 
            {
                $message='[strf]File with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
                $event['job']='sawanna_message';
            }
        } else 
        {
            $suffix=preg_match('/^(.*?)-[^\-]+$/',$id,$pd) ? '/'.$pd['1'] : '';
            $dir=quotemeta($this->sawanna->file->root_dir).'/'.quotemeta(str_replace('-','/',$id));
        
            if (!file_exists(ROOT_DIR.'/'.$dir) || !is_dir(ROOT_DIR.'/'.$dir)) 
            {
                trigger_error('Could not delete directory. Directory not found',E_USER_WARNING);
                return;
            }
            
            if (!@rmdir(ROOT_DIR.'/'.$dir))
            {
                $message='[str]Could not delete directory. Are you sure, that it is empty ?[/str]';    
                $event['job']='sawanna_error';
            } else 
            {
                $message='[strf]Directory: %'.htmlspecialchars(str_replace('-','/',$id)).'% deleted[/strf]';
                $event['job']='sawanna_message';
            }
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);
        
        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/files'.$suffix);
    }
    
    function bulk_delete()
    {
        if (!$this->sawanna->has_access('upload files')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
                $this->sawanna->file->delete(trim($id));
            }
            
            echo $this->sawanna->locale->get_string('Files were deleted');
        } else
        {
            echo $this->sawanna->locale->get_string('Files were not deleted');
        }
        
        die();
    }
    
    function files_list($dir='',$start=0,$limit=10)
    {
        if (!$this->sawanna->has_access('upload files')) 
        {
            trigger_error('Permission denied',E_USER_WARNING);
            return ;
        }
        
        $html='<h1 class="tab">[str]Files[/str]</h1>';
        $html.='<div id="files-list">';

        $cur_dir_link='';
        $prev_dir_link='';
        
        if (!empty($dir)) 
        {
            $cur_dir_link=$dir;
            $prev_dir_link=preg_match('/^(.*?)-[^\-]+$/',$dir,$pd) ? '/'.$pd['1'] : '';
            $dir=str_replace('-','/',$dir);
        }
        
        $current_dir=!empty($dir) ? quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($dir) : quotemeta($this->sawanna->file->root_dir);
        if (!file_exists(ROOT_DIR.'/'.$current_dir) || !is_dir(ROOT_DIR.'/'.$current_dir)) 
        {
            trigger_error('Directory not found',E_USER_WARNING);
            return;
        }
        
        $html.='<div class="files-list-item head">';
        $html.='<div class="id"></div>';
        $html.='<div class="file">'.htmlspecialchars($current_dir).'</div>';
        $html.='</div>';
        
        if (!empty($dir))
        {
            $html.='<div class="files-list-item mark highlighted">';
            $html.='<div class="id"><img src="'.SITE.'/misc/folder.png" width="20" /></div>';
            $html.='<div class="file">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/files'.$prev_dir_link,'..').'</div>';
            $html.='</div>';
        }
        
        $directory=opendir(ROOT_DIR.'/'.$current_dir);
        if ($directory)
        {
            while (($d=readdir($directory))!==false)
            {
				$d=mb_convert_encoding($d,'UTF-8');
                $dir_link=!empty($dir) ? $dir.'-'.$d : $d;
                
                if (!is_dir(ROOT_DIR.'/'.$current_dir.'/'.$d) || $d=='.' || $d=='..') continue;
                $html.='<div class="files-list-item mark highlighted">';
                $html.='<div class="id"><img src="'.SITE.'/misc/folder.png" width="20" /></div>';
                $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/files/delete/'.$dir_link,'[str]Delete[/str]','button',array('onclick'=>'if (!confirm(\'[str]Are you sure ?[/str]\')){return false;}')).'</div>';
                $html.='<div class="file">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/files/'.$dir_link,$d).'</div>';
                $html.='</div>';
            }
            closedir($directory);
        }
        
        $this->sawanna->db->query("select count(*) as count from pre_files where path='$1'",$dir);
        $row=$this->sawanna->db->fetch_object();
        
        $count=$row->count;
        
        $d=new CData();
        
        $co=0;
        $this->sawanna->db->query("select * from pre_files where path='$1' order by created desc limit $2 offset $3",$dir,$limit,$start*$limit);
        while($file=$this->sawanna->db->fetch_object())
        {
            $not_found=false;

            if (empty($dir) && !file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($file->name))) $not_found=true;
            if (!empty($dir) && !file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($dir).'/'.quotemeta($file->name))) $not_found=true;

            if ($co%2===0)
            {
                $html.='<div class="files-list-item">';
            } else 
            {
                $html.='<div class="files-list-item mark highlighted">';
            }

            $html.='<div class="id"><input type="checkbox" name="bulk_id[]" value="'.$file->id.'" class="bulk_id_checkbox" /></div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/files/delete/'.$file->id,'[str]Delete[/str]','button',array('onclick'=>'if (!confirm(\'[str]Are you sure ?[/str]\')){return false;}')).'</div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/files/edit/'.$file->id,'[str]Edit[/str]','button').'</div>';

            $file->meta=$d->do_unserialize($file->meta);

            $file_desc=!$not_found ? '' : '<string>Not found</string>';
            $file_desc.='<div class="file-desc"><label>[str]Upload date[/str]:</label> '.date($this->sawanna->config->date_format,$file->created).'</div>';
            $file_desc.='<div class="file-desc"><label>[str]Original name[/str]:</label> '.htmlspecialchars($file->original).'</div>';
            $file_desc.='<div class="file-desc"><label>[str]File type[/str]:</label> '.htmlspecialchars($file->type).'</div>';
            $file_desc.='<div class="file-desc"><label>[str]File size[/str]:</label> '.number_format($file->size/1024,2,',','.').' kB</div>';
            $file_desc.='<div class="file-desc"><label>[str]Saved name[/str]:</label> '.htmlspecialchars($file->name).'</div>';
            $file_desc.='<div class="file-desc"><label>[str]Alias[/str]:</label> '.(!empty($file->nicename) ? htmlspecialchars($file->nicename) : '-').'</div>';
            $file_desc.='<div class="file-desc"><label>[str]Downloads[/str]:</label> '.(isset($file->meta['downloads']) ? htmlspecialchars($file->meta['downloads']) : '0').'</div>';

            $html.='<div class="file">'.$file_desc.'</div>';
            $html.='</div>';

            $co++;
        }
        
        $html.='</div>';
        
        if ($count)
        {
            $html.='<div class="bulk_block">';
            $html.='<a href="#" id="bulk_id_selector" class="bulk_select_all">[str]Select all[/str]</a>';
            $html.='<select id="bulk_action_selector" name="bulk_action_selector" class="bulk_select" onchange="exec_bulk_action()"><option value="">[str]Choose action[/str]</option><option value="del">[str]Delete[/str]</option></select>';
            $html.='</div>';
            
            $html.="<script language=\"javascript\">$(document).ready(function(){ $('a#bulk_id_selector').toggle(function() { $('input[type=checkbox].bulk_id_checkbox').attr('checked','checked'); }, function() { $('input[type=checkbox].bulk_id_checkbox').removeAttr('checked'); }); });</script>";
            $html.="<script language=\"javascript\">function exec_bulk_action(){ var act=$('#bulk_action_selector').val(); var ids=''; if (act.length>0) { $('input[type=checkbox].bulk_id_checkbox').each(function(){ if ($(this).get(0).checked==true) { if (ids.length>0){ids=ids+',';}; ids=ids+$(this).val(); } }); if (ids.length>0 && confirm('[str]Are you sure ?[/str]')) { $.post('".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/files/bulkdel')."',{bulk_ids:ids},function(data){ alert(data); window.location.href='".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/'.$this->sawanna->arg)."'; }); $('#bulk_action_selector').get(0).disabled=true; } } $('#bulk_action_selector').get(0).selectedIndex=0; refresh_select($('#bulk_action_selector')); }</script>";
        }
        
        $html.=$this->sawanna->constructor->pager($start,$limit,$count,$this->sawanna->config->admin_area.'/files'.(!empty($dir) ? '/'.$dir : ''));
        
        $html.=$this->dir_form($cur_dir_link);

        echo $html;
    }
    
    function dir_form($current_dir)
    {
        $html='<h1 class="tab">[str]New directory[/str]</h1>';
        
        $fields['dirname']=array(
            'type' => 'text',
            'title' => '[str]Name[/str]',
            'description' => '[str]Specify the directory name[/str]',
            'autocheck' => array('url'=>'file-name-check','caller'=>'filesAdminHandler','handler'=>'file_name_check'),
            'required' => true
        );
        
         $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );
        
        $html.=$this->sawanna->constructor->form('dir-form',$fields,'dir_form_process',!empty($current_dir) ? $this->sawanna->config->admin_area.'/files/'.quotemeta($current_dir) : $this->sawanna->config->admin_area.'/files',!empty($current_dir) ? $this->sawanna->config->admin_area.'/files/'.quotemeta($current_dir) : $this->sawanna->config->admin_area.'/files','','CFilesAdmin');
        
        return $html;
    }
    
    function dir_form_process()
    {
        if (preg_match('/^files\/([a-zA-Z0-9_-]+)$/',$this->sawanna->arg,$matches)) 
            $directory=quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($matches[1]);
        else 
            $directory=quotemeta($this->sawanna->file->root_dir);
        
        if (!file_exists(ROOT_DIR.'/'.$directory) || !is_dir(ROOT_DIR.'/'.$directory))
        {
            return array('[str]Invalid directory[/str]',array('dirname'));
        }
            
       $dir=quotemeta($_POST['dirname']);
       if (substr($dir,0,1)=='/') $dir=substr($dir,1);
       if (substr($dir,-1)=='/') $dir=substr($dir,0,sawanna_strlen($dir)-1);
       if (!$this->valid_filename($dir)) return array('[str]Invalid name[/str]',array('dirname'));
       
       if (!mkdir(ROOT_DIR.'/'.$directory.'/'.$dir)) return array('[str]Failed to create new directory[/str]',array('dirname'));
       
       chmod(ROOT_DIR.'/'.$directory.'/'.$dir,0777);
       
       return array('[strf]Directory %'.htmlspecialchars($dir).'% created[/strf]');
    }
}
