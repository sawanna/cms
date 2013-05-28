<?php
defined('SAWANNA') or die();

class CContentAdmin
{
    var $sawanna;
    var $node_type;
    var $teaser_length;
    var $module_id;
    var $node_widget_name;
    var $node_widget_class;
    var $node_widget_handler;
    var $thumbs_dir;
    var $disable_thumbs;
    
    function CContentAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->node_type='node';
        $this->teaser_length=500;
        $this->module_id=$this->sawanna->module->get_module_id('content');
        $this->node_widget_name='node';
        $this->node_widget_class='CContent';
        $this->node_widget_handler='node';
        $this->thumbs_dir='thumbs';
        
        $thumbs=$this->sawanna->get_option('content_thumbnail_off');
        $this->disable_thumbs=$thumbs ? true : false;
    }
    
    function adminbar()
    {
        if (!$this->sawanna->has_access('create nodes')) return;
            
        return array(
                        array(
                        'nodes/create'=>'[str]New node[/str]',
                        'nodes'=>'[str]Nodes[/str]'
                        ),
                        'nodes'
                        );
    }
    
    function optbar()
    {
        return  array(
                        'nodes/settings'=>'[str]Content[/str]'
                        );
    }
    
    function admin_area()
    {
        if (!$this->sawanna->has_access('create nodes')) return ;
        
        $this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
        
        switch (true)
        {
            case $this->sawanna->arg=='nodes/create':
                $this->node_form();
                break;
            case preg_match('/^nodes\/edit\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->node_form($matches[1]);
                break;
            case preg_match('/^nodes\/delete\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->node_delete($matches[1]);
                break;
			case preg_match('/^nodes\/remove\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->node_remove($matches[1]);
                break;
            case preg_match('/^nodes\/bulk$/',$this->sawanna->arg,$matches):
                $this->bulk_action();
                break;
            case $this->sawanna->arg=='nodes/file-upload':
                $this->node_file_upload();
                break;
            case $this->sawanna->arg=='nodes/file-mgr':
                $this->node_file_mgr();
                break;
            case $this->sawanna->arg=='nodes':
                $this->nodes_list('all');
                break;
            case preg_match('/^nodes\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->nodes_list('all',0,$matches[1]);
                break;
            case preg_match('/^nodes\/([a-zA-Z0-9_-]+)\/([\d]+)$/',$this->sawanna->arg,$matches) && (array_key_exists($matches[1],$this->sawanna->config->languages) || $matches[1]=='all'):
                $this->nodes_list($matches[1],0,$matches[2]);
                break;
            case preg_match('/^nodes\/([a-zA-Z0-9_-]+)$/',$this->sawanna->arg,$matches) && (array_key_exists($matches[1],$this->sawanna->config->languages) || $matches[1]=='all'):
                $this->nodes_list($matches[1]);
                break;
			case preg_match('/^nodes\/([a-zA-Z0-9_-]+)\/[-]([0-9]+)[-]\/([\d]+)$/',$this->sawanna->arg,$matches) && (array_key_exists($matches[1],$this->sawanna->config->languages) || $matches[1]=='all'):
                $this->nodes_list($matches[1],$matches[2],$matches[3]);
                break;
            case preg_match('/^nodes\/([a-zA-Z0-9_-]+)\/[-]([0-9]+)[-]$/',$this->sawanna->arg,$matches) && (array_key_exists($matches[1],$this->sawanna->config->languages) || $matches[1]=='all'):
                $this->nodes_list($matches[1],$matches[2]);
                break;
            case $this->sawanna->arg=='nodes/settings':
                $this->settings_form();
                break;
        }
    }
    
     function node_path_complete()
    {
        if (!isset($_POST['q'])) return;
        
        // $this->sawanna->db->query("select * from pre_node_pathes where keyword like '$1%'",$_POST['q']);
        $this->sawanna->db->query("select * from pre_navigation where path like '$1%'",$_POST['q']);
        while ($row=$this->sawanna->db->fetch_object()) {
            if (valid_path($row->path))
               echo $row->path."\n";
        }
    }
    
    function node_keyword_complete()
    {
        if (!isset($_POST['q'])) return;
        
        $this->sawanna->db->query("select distinct keyword from pre_node_keywords where keyword like '$1%'",$_POST['q']);
        while ($row=$this->sawanna->db->fetch_object()) {
            echo $row->keyword."\n";
        }
    }
    
    function get_node_keyword($keyword)
    {
        if (empty($keyword)) return false;
        
        $this->sawanna->db->query("select * from pre_node_keywords where keyword = '$1'",$keyword);
        $row=$this->sawanna->db->fetch_object();
        if (!empty($row)) return $row;   

        return false;
    }
    
    function set_node_keyword($keyword,$nid)
    {
        if (empty($nid) || empty($keyword)) return false;
        
        return $this->sawanna->db->query("insert into pre_node_keywords (nid,keyword) values ('$1','$2')",$nid,$keyword);
    }
    
    function delete_node_keywords($nid)
    {
        if (empty($nid)) return false;
        
        return $this->sawanna->db->query("delete from pre_node_keywords where nid='$1'",$nid);
    }
    
    function get_node_path($path,$nid=0)
    {
        if (empty($path)) return false;
        
        if (empty($nid))
        {
            $this->sawanna->db->query("select * from pre_node_pathes where path = '$1'",$path);
        } else 
        {
            $this->sawanna->db->query("select * from pre_node_pathes where path = '$1' and nid='$2'",$path,$nid);
        }
        
        $row=$this->sawanna->db->fetch_object();
        if (!empty($row)) return $row;
            
        return false;
    }
    
    function set_node_path($path,$nid)
    {
        if (empty($nid) || empty($path)) return false;
        
        return $this->sawanna->db->query("insert into pre_node_pathes (nid,path) values ('$1','$2')",$nid,$path);
    }
    
    function get_node_pathes($nid=0)
    {
        
        if (empty($nid)) return;

        return $this->sawanna->db->query("select * from pre_node_pathes where nid = '$1'",$nid);
    }
    
    function get_node_keywords($nid=0)
    {
        
        if (empty($nid)) return;

        return $this->sawanna->db->query("select * from pre_node_keywords where nid = '$1'",$nid);
    }
    
    function delete_node_pathes($nid)
    {
        if (empty($nid)) return false;
        
        return $this->sawanna->db->query("delete from pre_node_pathes where nid='$1'",$nid);
    }
    
    function node_form($id=0)
    {
        if (!$this->sawanna->has_access('create nodes')) 
        {
            trigger_error('Permission denied',E_USER_ERROR);
            return ;
        }
        
        $node=new stdClass();
        
        if (!empty($id) && !is_numeric($id)) 
        {
            trigger_error('Invalid node ID: '.$id,E_USER_ERROR);
            return;
        }
        if (!empty($id))
        {
            if (!$this->sawanna->node->node_exists($id)) 
            {
                trigger_error('Node not found',E_USER_ERROR);
                return;    
            }
            
            $node=$this->sawanna->node->get_existing_node($id);
            
            $d=new CData();
            $node->meta=$d->do_unserialize($node->meta);
        }
        
        $fields=array();
        
         $fields['node-tab']=array(
            'type' => 'html',
            'value' => '<h1 class="tab">'.(!isset($node->id) ? '[str]New node[/str]' : '[str]Edit node[/str]').'</h1>'
        );
        
        if (count($this->sawanna->config->languages)>1)
        {
            $fields['node-language']=array(
                'type' => 'select',
                'options' => $this->sawanna->config->languages,
                'title' => '[str]Language[/str]',
                'description' => '[str]Please choose node language[/str]',
                'default' => isset($node->language) ? $node->language : $this->sawanna->locale->current,
                'required' => false
            );
        }
        
        $fields['node-title']=array(
            'type' => 'text',
            'title' => '[str]Title[/str]',
            'description' => '[str]Specify node title here[/str]',
            'attributes' => array('style'=>'width: 100%'),
            'default' => isset($node->meta['title']) ? $node->meta['title'] : '' 
        );
        
        $extra=$this->sawanna->form->get_form_extra_fields('node_create_form_pre_fields',isset($node->id) ? $node->id : 0);
        
        if (!empty($extra))
        {
            $fields=array_merge($fields,$extra);
        }
        
        $node_pathes='';
        $pco=0;
        if (isset($node->id))
        {
            $res=$this->get_node_pathes($node->id);
            while ($row=$this->sawanna->db->fetch_object($res))
            {
                $node_pathes.='<div class="node-pathes-item"><input type="checkbox" name="node-path-'.$pco.'" value="'.$row->path.'" id="node-path-'.$pco.'" value="'.$row->path.'" checked="checked" /><label for="node-path-'.$pco.'" value="'.$row->path.'">'.$row->path.'</label></div>';
                $pco++;
            }
        }
        
        $fields['node-pathes']=array(
            'type' => 'html',
            'value' => '<strong>[str]URL pathes[/str]:</strong><div id="node-pathes-block" class="node-pathes">'.$node_pathes.'</div>'
        );
        
        $fields['node-path']=array(
            'type' => 'text',
            'title' => '[str]New URL path[/str]',
            'attributes' => array('style'=>'width: 100%'),
            'autocomplete' => array('url'=>'nodes/path-complete','caller'=>'contentAdminHandler','handler'=>'node_path_complete')
        );
        
         $fields['node-add-path-btn']=array(
            'type' => 'button',
            'title' => ' ',
            'value' => '[str]Add[/str]',
            'attributes' => array('onclick'=>'add_node_path()')
        );
        
        $fields['node-add-path-script']=array(
            'type' => 'html',
            'value' => '<script language="JavaScript">node_path_co='.$pco.'; function add_node_path() { if ($(\'#node-path\').get(0).value.length==0) {return;}; $(\'#node-pathes-block\').append(\'<div class="node-pathes-item"><input type="checkbox" class="checkbox" name="node-path-\'+node_path_co+\'" id="node-path-\'+node_path_co+\'" value="\'+$(\'#node-path\').get(0).value+\'" checked="checked" /><label for="node-path-\'+node_path_co+\'">\'+$(\'#node-path\').get(0).value+\'</label></div>\');$(\'#node-path\').get(0).value=\'\';node_path_co++;}</script>'
        );
        
        $fields['node-pub-tstamp']=array(
            'type' => 'date',
            'title' => '[str]Publish date[/str]',
            'description' => '[str]You can choose node publishing date[/str] ([str]Date format[/str]: d.m.Y)',
            'default' => isset($node->pub_tstamp) ? date('d.m.Y',$node->pub_tstamp) : date('d.m.Y'),
            'required' => false
        );
        
        $fields['node-content']=array(
            'type' => 'textarea',
            'title' => '[str]Content[/str]',
            'description' => '[str]Type the node content here[/str]',
            'attributes' => array('rows'=>20,'cols'=>60,'style'=>'width: 100%','class'=>'wysiwyg'),
            'required' => false,
            'default' => isset($node->content) ? $node->content : ''
        );
        
        $fields['node-files-block-begin']=array(
            'type' => 'html',
            'value' => '<div id="node-files-block">'
        );
        
        ################ quick file upload ##################
        
        if ($this->sawanna->has_access('upload files'))
        {
      //      $this->sawanna->tpl->add_script('js/ajaxupload');
            $this->sawanna->tpl->add_script('js/ajaxfileupload');
            
            $fields['node-file-upload-btn']=array(
                'type' => 'html',
                'value' => '<a class="btn button" href="javascript:node_show_file_uploader()">[str]Upload file[/str]</a>'
            );
            
            $fields['node-file-upload-dialog-begin']=array(
                'type' => 'html',
                'value' => '<div id="node-file-uploader" title="[str]Upload file[/str]">'
            );
            
            $dirs=array(''=>$this->sawanna->file->root_dir);
            $dirs=$dirs+$this->sawanna->file->get_sub_directories($this->sawanna->file->root_dir);
    
            $fields['node-file-dir']=array(
                'type' => 'select',
                'options' => $dirs,
                'title' => '[str]Directory[/str]',
                'description' => '[str]Choose upload directory[/str]',
                'default' => '' 
            );
            
            $fields['node-file-alias']=array(
                'type' => 'text',
                'title' => '[str]Alias[/str]',
                'description' => '[str]Enter file alias[/str]',
                'autocheck' => array('url'=>'nodes/file-alias-check','caller'=>'contentAdminHandler','handler'=>'file_alias_check'),
                'default' => '' 
            );
            
            $fields['node-file-upload-file']=array(
                'type' => 'file',
                'title' => '[str]Choose file[/str]',
                'attributes' => array('style'=>'width: 200px;')
            );
            
            $fields['node-file-upload-response']=array(
                'type' => 'html',
                'value' => '<div id="node-file-uploader-response"></div>'
            );
            
            $fields['node-file-btn']=array(
                'type' => 'button',
                'value' => '[str]Upload[/str]',
       //         'attributes' => array('onclick'=>"nodeUploader.setData({directory : $('#node-file-dir').val(), alias : $('#node-file-alias').get(0).value}); nodeUploader.submit();")
                'attributes' => array('onclick' => "$.ajaxFileUpload
                                                            (
                                                                {
                                                                    url:'".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/nodes/file-upload')."',
                                                                    data: {directory : $('#node-file-dir').val(), alias : $('#node-file-alias').val()},
                                                                    secureuri:false,
                                                                    fileElementId:'node-file-upload-file',
                                                                    dataType: 'html',
                                                                    success: function (response, status)
                                                                    {
                                                                        $('#node-file-uploader-response').html(response);
                                                                        $('#node-file-upload-file').val('');
                                                                    },
                                                                    error: function (data, status, e)
                                                                    {
                                                                        alert(e);
                                                                    }
                                                                }
                                                            ); 
                                                            $('#node-file-uploader-response').html('[str]Uploading[/str]...'); $('#node-file-alias').get(0).value='';"
                                                 )
            );

            $fields['node-file-upload-dialog-end']=array(
                'type' => 'html',
                'value' => '</div>'
            );
            
            $fields['node-file-upload-script']=array(
                'type' => 'html',
       //         'value' => '<script language="JavaScript">$(document).ready(function() { try { nodeUploader = new AjaxUpload(\'node-file-upload-file\', {action: \''.$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/nodes/file-upload').'\', name: \'node-file-upload-file\', autoSubmit: false, onSubmit: function(){$(\'#node-file-uploader-response\').html(\'[str]Uploading[/str]...\'); $(\'#node-file-alias\').get(0).value=\'\'; }, onComplete: function(file, response){$(\'#node-file-uploader-response\').html(response);} });   $("#node-file-uploader").dialog({ autoOpen: false, modal: false, resizable: false, beforeclose: function(event, ui) {$(\'#node-file-uploader-response\').empty()} }); } catch (e) { alert (\'Error. Ajax Uploader not initialized\');} });   function node_show_file_uploader() {$("#node-file-uploader").dialog("open")}</script>'
                'value' => '<script language="JavaScript">$(document).ready(function() { window.setTimeout(\'try { $("#node-file-uploader").dialog({ autoOpen: false, modal: false, resizable: false, beforeclose: function(event, ui) {$("#node-file-uploader-response").empty()} }); } catch (e) { alert ("Error. Ajax Uploader not initialized");} \',300); });   function node_show_file_uploader() {$("#node-file-uploader").dialog("open")}</script>'
            );
        }
        
        ############## file manager ################
        
        $fields['node-file-mgr-btn']=array(
            'type' => 'html',
            'value' => '<a class="btn button" href="javascript:node_show_file_mgr()">[str]File manager[/str]</a>'
        );
        
        $fields['node-file-mgr-dialog-begin']=array(
            'type' => 'html',
            'value' => '<div id="node-file-mgr" title="[str]File manager[/str]">'
        );
            
        $dirs=array(''=>$this->sawanna->file->root_dir);
        $dirs=$dirs+$this->sawanna->file->get_sub_directories($this->sawanna->file->root_dir);

        $fields['node-file-mgr-dir']=array(
            'type' => 'select',
            'options' => $dirs,
            'title' => '[str]Directory[/str]',
            'description' => '[str]Choose directory[/str]',
            'attributes' => array('onchange'=>"node_show_directory(0,0)"),
            'default' => '' 
        );
        
        $fields['node-file-mgr-path']=array(
            'type' => 'html',
            'value' => '<div id="node-file-mgr-path"></div>'
        );
        
        $fields['node-file-mgr-block']=array(
            'type' => 'html',
            'value' => '<div id="node-file-mgr-wnd"></div>'
        );
        
        $fields['node-file-mgr-paste-btn']=array(
            'type' => 'button',
            'value' => '[str]Paste[/str]',
            'attributes' => array('onclick'=>"node_paste_content(file_name,file_type,file_attr1,file_attr2)")
        );

        $fields['node-file-mgr-dialog-end']=array(
            'type' => 'html',
            'value' => '</div>'
        );
        
        $this->sawanna->tpl->add_script('js/paste');
        
        $fields['node-file-mgr-script']=array(
            'type' => 'html',
            'value' => '<script language="JavaScript">$(document).ready(function() {  file_path=""; file_name=""; file_type=""; file_attr1=""; file_attr2=""; window.setTimeout(\'$("#node-file-mgr").dialog({ autoOpen: false, modal: false, resizable: false, width: 600 });\',300);  });   function node_show_file_mgr() {$("#node-file-mgr").dialog("open"); $("#node-file-mgr-wnd").empty(); node_show_directory();}; function node_show_directory(dir_query,dir_page) { $.post("'.$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/nodes/file-mgr').'",{directory: $("#node-file-mgr-dir").val(),offset: dir_page},function(data){ $("#node-file-mgr-wnd").html(data); }); $("#node-file-mgr-wnd").html(\'[str]Downloading[/str]...\'); $("#node-file-mgr-path").empty()}; function get_node_paste_content(filename,type,attr1,attr2) { var content; if (type==\'image\') { content=\'<img src="\'+filename+\'" width="\'+attr1+\'" height="\'+attr2+\'" />\' } else { content=\'<a href="\'+filename+\'" class="\'+attr1+\'" target="\'+attr2+\'">\'+filename+\'</a>\'}; return content;}; function node_paste_content(filename,type,attr1,attr2) { if (!filename.length){alert(\'[str]File not selected[/str]\'); return false;}; var content=get_node_paste_content(filename,type,attr1,attr2); try {tinyMCE.execCommand(\'mceInsertContent\',false,content);} catch (e) { try { $(\'#node-content\').insertAtCaret(\' \'+content+\' \'); } catch (err) { $(\'#node-content\').get(0).value+=content}; }}</script>'
        );

        $fields['node-files-block-end']=array(
            'type' => 'html',
            'value' => '</div>'
        );
        
         $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );
        
        $fields['node_options-tab']=array(
            'type' => 'html',
            'value' => '<h1 class="tab">[str]Node settings[/str]</h1>'
        );
        
        $fields['node-teaser']=array(
            'type' => 'textarea',
            'title' => '[str]Short description[/str]',
            'description' => '[str]If not specified, it will be generated[/str]',
            'attributes' => array('rows'=>10,'cols'=>60,'style'=>'width: 100%'),
            'default' => isset($node->meta['teaser']) ? $node->meta['teaser'] : '',
            'required' => false
        );
        
        $fields['node_publish-cap']=array(
            'type' => 'html',
            'value' => '<h3>[str]Publishing options[/str]:</h3>'
        );
        
        if ($this->sawanna->has_access('publish nodes'))
        {
            $fields['node-published']=array(
                'type' => 'checkbox',
                'title' => '[str]Publish[/str]',
                'description' => '[str]Node not shown on the website until published[/str]',
                'attributes' => !isset($node->enabled) || $node->enabled ? array('checked'=>'checked','class'=>'checkbox') : array('class'=>'checkbox'),
                'required' => false
            );
            
            $fields['node-sticky']=array(
                'type' => 'checkbox',
                'title' => '[str]Sticky[/str]',
                'description' => '[str]If checked, node appears on top of the nodes list[/str]',
                'attributes' => !isset($node->sticky) || !$node->sticky ? array('class'=>'checkbox') : array('checked'=>'checked','class'=>'checkbox'),
                'required' => false
            );
        }
        
        if ($this->sawanna->has_access('publish nodes on front page'))
        {
            $fields['node-front']=array(
                'type' => 'checkbox',
                'title' => '[str]Show on front page[/str]',
                'description' => '[str]If checked, node appears on the website front page[/str]',
                'attributes' => !isset($node->front) || !$node->front ? array('class'=>'checkbox') : array('checked'=>'checked','class'=>'checkbox'),
                'required' => false
            );
        }
        
        $tpls=$this->get_node_templates();
        asort($tpls);
        
        $fields['node-tpl']=array(
            'type' => 'select',
            'title' => '[str]Template[/str]',
            'options' => $tpls,
            'description' => '[str]Choose node template[/str]',
            'default' => isset($node->meta['tpl']) ? $node->meta['tpl'] : 'node',
            'required' => false
        );
        
        /*
        $fields['node-weight']=array(
            'type' => 'text',
            'title' => '[str]Weight[/str]',
            'description' => '[str]Specify the node weight here.[/str] [str]Lighter nodes appear first[/str]',
            'default' => isset($node->weight) ? $node->weight :  '0',
            'required' => false
        );
        */
        
        $middle_weight=isset($node->weight) ? $node->weight : 0;
        
        $fields['node-weight']=array(
            'type' => 'select',
            'options' => array_combine(range($middle_weight-30,$middle_weight+30,1),range($middle_weight-30,$middle_weight+30,1)),
            'title' => '[str]Weight[/str]',
            'description' => '[str]Specify the node weight here.[/str] [str]Lighter nodes appear first[/str]',
            'default' => isset($node->weight) ? $node->weight :  0,
            'attributes' => array('class'=>'slider'),
            'required' => false
        );
        
        $fields['submit-opt']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );
        
        $fields['node_meta-cap']=array(
            'type' => 'html',
            'value' => '<h1 class="tab">[str]Meta[/str]</h1>'
        );

        $node_keywords='';
        $kco=0;
        if (isset($node->id))
        {
            $res=$this->get_node_keywords($node->id);
            while ($row=$this->sawanna->db->fetch_object($res))
            {
                $node_keywords.='<div class="node-keywords-item"><input type="checkbox" name="node-meta-keyword-'.$kco.'" id="node-meta-keyword-'.$kco.'" value="'.$row->keyword.'" checked="checked" /><label for="node-meta-keyword-'.$kco.'">'.$row->keyword.'</label></div>';
                $kco++;
            }
        }
        
        $fields['node-meta-keywords']=array(
            'type' => 'html',
            'value' => '<strong>[str]Keywords[/str]:</strong><div id="node-keywords-block" class="node-keywords">'.$node_keywords.'</div>'
        );
        
        $fields['node-meta-keyword']=array(
            'type' => 'text',
            'title' => '[str]New keyword[/str]',
            'description' => '[str]Recommended for SEO[/str]',
            'autocomplete' => array('url'=>'nodes/keyword-complete','caller'=>'contentAdminHandler','handler'=>'node_keyword_complete'),
            'required' => false
        );
        
         $fields['node-add-keyword-btn']=array(
            'type' => 'button',
            'value' => '[str]Add[/str]',
            'title' => ' ',
            'attributes' => array('onclick'=>'add_node_keyword()')
        );
        
        $fields['node-add-keyword-script']=array(
            'type' => 'html',
            'value' => '<script language="JavaScript">node_keyword_co='.$kco.'; function add_node_keyword() {  if ($(\'#node-meta-keyword\').get(0).value.length==0) {return;}; $(\'#node-keywords-block\').append(\'<div class="node-keywords-item"><input type="checkbox" class="checkbox" name="node-meta-keyword-\'+node_keyword_co+\'" id="node-meta-keyword-\'+node_keyword_co+\'" value="\'+$(\'#node-meta-keyword\').get(0).value+\'" checked="checked" /><label for="node-meta-keyword-\'+node_keyword_co+\'">\'+$(\'#node-meta-keyword\').get(0).value+\'</label></div>\');$(\'#node-meta-keyword\').get(0).value=\'\';node_keyword_co++;}</script>'
        );
        
        $fields['node-meta-description']=array(
            'type' => 'textarea',
            'title' => '[str]Meta description[/str]',
            'description' => '[str]Recommended for SEO[/str]',
            'attributes' => array('rows'=>5,'cols'=>60,'style'=>'width: 100%'),
            'default' => isset($node->meta['description']) ? $node->meta['description'] : '',
            'required' => false
        );

        $fields['submit-seo']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );
        
        $extra=$this->sawanna->form->get_form_extra_fields('node_create_form_post_fields',isset($node->id) ? $node->id : 0);
        
        if (!empty($extra))
        {
            $fields['node_additions-tab']=array(
                'type' => 'html',
                'value' => '<h1 class="tab">[str]Additionally[/str]</h1>'
            );
            
            $fields=array_merge($fields,$extra);
            
             $fields['submit-additions']=array(
                'type'=>'submit',
                'value'=>'[str]Submit[/str]'
            );
        }
        
        echo $this->sawanna->constructor->form('node-form',$fields,'node_form_process',$this->sawanna->config->admin_area.'/nodes',empty($id) ? $this->sawanna->config->admin_area.'/nodes/create' : $this->sawanna->config->admin_area.'/nodes/edit/'.$id,'','CContentAdmin');
    }
    
    function node_form_process()
    {
        if (!$this->sawanna->has_access('create nodes')) 
        {
            return array('[str]Permission denied[/str]',array('create-node'));
        }
        
        $id=0;
        if (preg_match('/^nodes\/edit\/(.+)$/',$this->sawanna->arg,$matches)) $id=$matches[1];
        
        if (!empty($id) && !is_numeric($id)) return array('[strf]Invalid node ID: %'.$id.'%[/strf]',array('edit-node'));
        if (!empty($id) && !$this->sawanna->node->node_exists($id)) return array('[str]Node not found[/str]',array('edit-node'));    
        
        $node_pathes=array();
        
        if (!empty($_POST['node-path'])) 
        {
            if (sawanna_substr($_POST['node-path'],0,1)=='/') $_POST['node-path']=sawanna_substr($_POST['node-path'],1);
            if (sawanna_substr($_POST['node-path'],-1)=='/') $_POST['node-path']=sawanna_substr($_POST['node-path'],0,sawanna_strlen($_POST['node-path'])-1);
            
            $_POST['node-path']=trim(str_replace(' ','-',$_POST['node-path']),'-');
            
            if (valid_path($_POST['node-path']))
            { 
                if (!in_array($_POST['node-path'],$node_pathes)) $node_pathes[]=$_POST['node-path'];
            } else 
                return array('[strf]URL path %'.htmlspecialchars($_POST['node-path']).'% is invalid[/strf]',array('node-path'));
        }
        
        foreach ($_POST as $key=>$value)
        {
            if (preg_match('/^node-path-([\d]+)$/',$key,$matches) && !in_array($matches[1],$node_pathes))
            {
                if (sawanna_substr($value,0,1)=='/') $value=sawanna_substr($value,1);
                if (sawanna_substr($value,-1)=='/') $value=sawanna_substr($value,0,sawanna_strlen($value)-1);
                
                if (valid_path($value))
                { 
                    if (!in_array($value,$node_pathes)) $node_pathes[]=$value;
                } else
                    return array('[strf]URL path %'.htmlspecialchars($value).'% is invalid[/strf]',array('node-path'));
            }
        }

        $res=$this->sawanna->form->validate_form_extra_fields('node_create_form_pre_fields',$id);
        if (is_array($res) && !empty($res)) return $res;

        $res=$this->sawanna->form->validate_form_extra_fields('node_create_form_post_fields',$id);
        if (is_array($res) && !empty($res)) return $res;

        $d=new CData();

        $node=array();
        $existing_node=new stdClass();
        if (!empty($id)) $existing_node=$this->sawanna->node->get_existing_node($id);
        
        if (!empty($id) && !empty($existing_node->id))
        {
            foreach($existing_node as $prop=>$value)
            {
                $node[$prop]=$value;
            }
            
            if (!empty($node['meta'])) $node['meta']=$d->do_unserialize($node['meta']);
        }
        
        if (!isset($node['meta'])) $node['meta']=array();
        
        if (isset($_POST['node-title'])) $node['meta']['title']=strip_tags($_POST['node-title']);
        
        if (!empty($_POST['node-language']) && array_key_exists($_POST['node-language'],$this->sawanna->config->languages))
            $node['language']=$_POST['node-language'];
            
        if (!empty($_POST['node-pub-tstamp']) && preg_match('/^([\d]{2})\.([\d]{2})\.([\d]{4})$/',$_POST['node-pub-tstamp'],$matches)) 
            $node['pub_tstamp']=mktime(0,0,0,$matches[2],$matches[1],$matches[3]);
            
        if (!empty($_POST['node-content'])) $node['content']=$_POST['node-content'];
        
        if (!empty($_POST['node-teaser']))
        {
            $node['meta']['teaser']=strip_tags($_POST['node-teaser'],'<strong><b><a>');
        } elseif (isset($node['content']) && sawanna_strlen(strip_tags($node['content']))>$this->teaser_length) 
        {
            $node['meta']['teaser']=sawanna_substr(strip_tags($node['content']),0,$this->teaser_length).' ...';
        } elseif (isset($node['content']))
        {
            $node['meta']['teaser']=strip_tags($node['content']);
        }
        
        $node['meta']['author']=array('name'=>$this->sawanna->config->logged_user_name,'url'=>$this->sawanna->config->logged_user_profile_url);
        
        $node_keywords=array();
        
        if (!empty($_POST['node-meta-keyword'])) $node_keywords[]=$_POST['node-meta-keyword'];
        
        foreach ($_POST as $key=>$value)
        {
            if (preg_match('/^node-meta-keyword-([\d]+)$/',$key,$matches) && !in_array($matches[1],$node_keywords))
            {
                $node_keywords[]=$value;
            }
        }
        
        if (!empty($node_keywords)) $node['meta']['keywords']=implode(', ',$node_keywords);
        
        if (!empty($_POST['node-meta-description'])) $node['meta']['description']=strip_tags($_POST['node-meta-description']);
        
        if ($this->sawanna->has_access('publish nodes'))
        {
            if (isset($_POST['node-published']) && $_POST['node-published']=='on') 
                $node['enabled']=1;
            else
                $node['enabled']=0;
                
            if (isset($_POST['node-sticky']) && $_POST['node-sticky']=='on') 
                $node['sticky']=1;
            else
                $node['sticky']=0;
        }
        
        if ($this->sawanna->has_access('publish nodes on front page'))
        {
            if (isset($_POST['node-front']) && $_POST['node-front']=='on') 
                $node['front']=1;
            else
                $node['front']=0;
        }
        
        if (isset($_POST['node-tpl']) && !empty($_POST['node-tpl'])) $node['meta']['tpl']=quotemeta($_POST['node-tpl']);
        if (isset($_POST['node-weight']) && is_numeric($_POST['node-weight'])) $node['weight']=floor($_POST['node-weight']);
        
        if (!empty($id)) $node['id']=$id;
        $node['type']='node';
        $node['module']=$this->module_id;
        
        if (!$this->sawanna->node->set($node))
        {
            return array('[str]Node could not be saved[/str]',array('new-node'));
        }
        
        if (empty($id))
        {
            $this->sawanna->db->last_insert_id('nodes','id');
            $row=$this->sawanna->db->fetch_object();
            $node['id']=$row->id;
        }
        
        //creating thumbnail
        if (!$this->disable_thumbs && !empty($node['content']) && (preg_match('/<img[^>]+src[\x20]*[=][\x20]*[\x22]('.addcslashes(SITE.'/','/').'|\/)?image\/([^\x22\/]+)(\/[^\x22]+)?[\x22][^>]*>/si',$node['content'],$matches) || preg_match('/<img[^>]+src[\x20]*[=][\x20]*[\x22]('.addcslashes(SITE.'/','/').'|\/)?index\.php\?'.quotemeta($this->sawanna->query_var).'=image\/([^\x22\/]+)(\/[^\x22]+)?[\x22][^>]*>/si',$node['content'],$matches)))
        {
            $img_url=substr($matches[2],0,1)=='/' ? substr($matches[2],1) : $matches[2];
            
            $img=$this->sawanna->file->get($img_url);
            if ($img)
            {
                $img_path=!empty($img->path) ? ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($img->path).'/'.quotemeta($img->name) : ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($img->name);
                if (file_exists($img_path))
                {
                    $this->create_thumb($img_path,$node['id']);
                }
            }
        } elseif (!$this->disable_thumbs && !empty($node['content']) && preg_match('/<img[^>]+src[\x20]*[=][\x20]*[\x22]('.addcslashes(SITE,'/').')?([^\x22]+)[\x22][^>]*>/si',$node['content'],$matches))
        {
            $img_url=substr($matches[2],0,1)=='/' ? substr($matches[2],1) : $matches[2];
            
            if (file_exists(ROOT_DIR.'/'.$img_url))
            {
                $this->create_thumb(ROOT_DIR.'/'.$img_url,$node['id']);
            }
        }
        
        // clearing cache
        if ($this->sawanna->cache->clear_on_change('content'))
        {
            foreach ($this->sawanna->cache->clear_on_change['content'] as $type)
            {
                $this->sawanna->cache->clear_all_by_type($type);
            }
        }
        
        $this->sawanna->cache->clear_all_by_type('content');
        
        $this->delete_node_pathes($node['id']);
        
        if (!empty($node_pathes))
        {
            foreach ($node_pathes as $path)
            {
                $path_ex=$this->sawanna->navigator->get($path,'',false,false);
                if (empty($path_ex)) 
                {
                    $this->sawanna->navigator->set(array('path'=>$path,'enabled'=>1,'render'=>1,'module'=>$this->module_id));
                    $this->sawanna->navigator->set(array('path'=>$path.'/d','enabled'=>1,'render'=>1,'module'=>$this->module_id));
                }
                
                $this->set_node_path($path,$node['id']);
                
                $this->sawanna->cache->clear($this->sawanna->cache->cache_name(isset($node['language']) ? $node['language'] : $this->sawanna->locale->current,$this->sawanna->config->theme,$this->node_widget_name,$this->node_widget_class,$this->node_widget_handler,$path));
            }
        }
        
        $this->delete_node_keywords($node['id']);
        
        if (!empty($node_keywords))
        {
            foreach ($node_keywords as $keyword)
            {
                $this->set_node_keyword($keyword,$node['id']);
            }
        }
        
        
        // processing extra fields
        $res=$this->sawanna->form->process_form_extra_fields('node_create_form_pre_fields',$node['id']);
        if (is_array($res) && !empty($res)) return $res;

        $res=$this->sawanna->form->process_form_extra_fields('node_create_form_post_fields',$node['id']);
        if (is_array($res) && !empty($res)) return $res;

        return array('[strf]Node saved with ID: %'.$node['id'].'%[/strf]');
    }
    
    function node_file_upload()
    {
        if (!$this->sawanna->has_access('upload files')) return;
        
        if (empty($_FILES['node-file-upload-file'])) return;
        
        header('Content-Type: text/html; charset=utf-8');
        
        $dir='';
        if (!empty($_POST['directory']))
        {
            $dir=quotemeta($_POST['directory']);
            if (substr($dir,0,1)=='/') $dir=substr($dir,1);
            if (substr($dir,-1)=='/') $dir=substr($dir,0,sawanna_strlen($dir)-1);
            
            if (!file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($_POST['directory'])) || !is_dir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($_POST['directory'])))
            {
                echo $this->sawanna->locale->get_string('Invalid directory');
                exit();
            }
        }
        
        $alias=!empty($_POST['alias']) && $this->valid_file_alias($_POST['alias']) ? $_POST['alias'] : '';
        if (!empty($alias)) $this->sawanna->file->fix_alias($alias);

        if (!$this->sawanna->file->save($_FILES['node-file-upload-file'],$alias,$dir)) 
        {
            echo $this->sawanna->locale->get_string('File could not be saved');
            exit();
        }

        echo $this->sawanna->locale->get_string('File saved with name').': '.htmlspecialchars($this->sawanna->file->last_name);
        exit();
    }
    
    function valid_file_alias($name)
    {
        if (!preg_match('/^[a-z0-9\-_]{3,}$/',$name)) return false;
        
        return true;
    }
    
    function file_alias_check()
    {
        if (!isset($_POST['q'])) return;
        
        if (!$this->valid_file_alias($_POST['q'])) echo $this->sawanna->locale->get_string('Invalid name');
    }
    
    function node_file_mgr()
    {
        if (!isset($_POST['directory'])) return false;

        $dir='';
        $limit=40;
        $start=0;
        $count=0;
        
        if (isset($_POST['offset']) && is_numeric($_POST['offset']) && $_POST['offset']>0) $start=ceil($_POST['offset']);
        
        if (!empty($_POST['directory']))
        {
            $dir=quotemeta($_POST['directory']);
            if (substr($dir,0,1)=='/') $dir=substr($dir,1);
            if (substr($dir,-1)=='/') $dir=substr($dir,0,sawanna_strlen($dir)-1);
            
            if (!file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($_POST['directory'])) || !is_dir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($_POST['directory'])))
            {
                echo $this->sawanna->locale->get_string('Invalid directory');
                exit();
            }
        }
        
        $this->sawanna->db->query("select count(*) as co from pre_files where path='$1'",$_POST['directory']);
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) $count=$row->co;
        
        $co=0;
        $this->sawanna->db->query("select * from pre_files where path='$1' order by created desc limit $2 offset $3",$_POST['directory'],$limit,$start*$limit);
        while($file=$this->sawanna->db->fetch_object())
        {
           $not_found=false;
            
           if (empty($dir) && !file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($file->name))) $not_found=true;
           if (!empty($dir) && !file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($dir).'/'.quotemeta($file->name))) $not_found=true;
            
           $file_name=!empty($file->path) ? SITE.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$file->path.'/'.$file->name : SITE.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$file->name;
           $file_path=!empty($file->path) ? ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$file->path.'/'.$file->name : ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.$file->name;
           
           if (!$not_found && $size=getimagesize($file_path))
           {
               $type='image';
               $attr1=$size[0];
               $attr2=$size[1];
           } else 
           {
               $type='link';
               $attr1='file_link';
               $attr2='_blank';
           }
           
           if (!$not_found)
           {
               $click='file_path=$(this).attr(\'title\'); file_name=\''.$file_name.'\'; file_type=\''.$type.'\'; file_attr1=\''.$attr1.'\'; file_attr2=\''.$attr2.'\'; $(\'.file\').removeClass(\'selected\'); $(\'#file-'.$co.'\').addClass(\'selected\'); $(\'#node-file-mgr-path\').html(file_path)';
               
               $dblclick='node_paste_content(\''.$file_name.'\',\''.$type.'\',\''.$attr1.'\',\''.$attr2.'\')';
               
               echo '<div class="file" id="file-'.$co.'"><img src="'.$this->sawanna->constructor->url('image/'.$file->id.'/sys').'" title="'.$file_name.'" alt="'.$file->original.'" width="100" onclick="'.$click.'" ondblclick="'.$dblclick.'" /><span class="cap">'.(!empty($file->nicename) ? $file->nicename : $file->id).'</span><span>'.$file->original.'</span></div>';
            } else
            {
                echo '<div class="file" id="file-'.$co.'"><span class="cap">Not found</span><span class="cap">'.(!empty($file->nicename) ? $file->nicename : $file->id).'</span><span>'.$file->original.'</span></div>';
            }
            
            $co++;
        }

        echo $this->sawanna->constructor->ajax_pager($start,$limit,$count,'','node_show_directory');
        
        exit();
    }
    
    function create_thumb($filepath,$nid)
    {
        if (!is_writeable(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir))) return false;
        
        if (empty($filepath) || empty($nid) || !is_numeric($nid)) return false;
        
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->thumbs_dir)))
        {
           mkdir(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->thumbs_dir));
        }
        
        $old=$this->sawanna->file->get('node-'.$nid);
        
        $FILE=array(
            'tmp_name' => $filepath,
            'name' => 'thumbnail',
            'type' => filetype($filepath),
            'size' => filesize($filepath)
        );
        
        if (!$this->sawanna->file->save($FILE,'node-'.$nid,$this->thumbs_dir)) return false;
         
        if ($old) $this->sawanna->file->delete($old->id);
        
        $thumb_width=$this->sawanna->get_option('content_thumbnail_width');
        $thumb_height=$this->sawanna->get_option('content_thumbnail_height');
        $thumb_crop=$this->sawanna->get_option('content_thumbnail_crop');
        
        if (!$this->sawanna->constructor->resize_image(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->thumbs_dir).'/'.quotemeta($this->sawanna->file->last_name),ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->thumbs_dir).'/'.quotemeta($this->sawanna->file->last_name),$thumb_width ? $thumb_width : 120,$thumb_height ? $thumb_height : 90,$thumb_crop ? true : false))
        {
            $new_id=$this->sawanna->file->get_id_by_name($this->sawanna->file->last_name);
            $this->sawanna->file->delete($new_id);
            return false;
        }
        
        return true;
    }
    
    function node_delete($id)
    {
        if (!$this->sawanna->has_access('create nodes')) 
        {
            trigger_error('Could not delete node. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not delete node. Invalid node ID: '.$id,E_USER_WARNING);
            return ;
        }
        if (!$this->sawanna->node->node_exists($id)) 
        {
            trigger_error('Could not delete node. Node not found',E_USER_WARNING);    
            return;
        }
        
        $event=array();
        
        if (!$this->sawanna->node->delete($id))
        {
            $message='[str]Could not delete node. Database error[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
            $message='[strf]Node with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
            $event['job']='sawanna_message';
            
            // clearing cache
            if ($this->sawanna->cache->clear_on_change('content'))
            {
                foreach ($this->sawanna->cache->clear_on_change['content'] as $type)
                {
                    $this->sawanna->cache->clear_all_by_type($type);
                }
            }
            
            $this->sawanna->cache->clear_all_by_type('content');
            
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);
        
        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/nodes');
    }
    
    function node_remove($id) {
		if (!$this->sawanna->has_access('create nodes')) 
        {
            trigger_error('Could not delete node. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not delete node. Invalid node ID: '.$id,E_USER_WARNING);
            return ;
        }
        if (!$this->sawanna->node->node_exists($id)) 
        {
            trigger_error('Could not delete node. Node not found',E_USER_WARNING);    
            return;
        }
        
        $event=array();
        
        if (!$this->sawanna->node->delete($id))
        {
            $message='[str]Could not delete node. Database error[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
			$this->sawanna->db->query("select path from pre_node_pathes where nid='$1'",$id);
			while($row=$this->sawanna->db->fetch_object()) {
				$this->sawanna->navigator->delete($row->path);
				$this->sawanna->navigator->delete($row->path.'/d');
			}
			
			$this->sawanna->db->query("delete from pre_node_pathes where nid='$1'",$id);
			
            $message='[strf]Node with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
            $event['job']='sawanna_message';
            
            // clearing cache
            if ($this->sawanna->cache->clear_on_change('content'))
            {
                foreach ($this->sawanna->cache->clear_on_change['content'] as $type)
                {
                    $this->sawanna->cache->clear_all_by_type($type);
                }
            }
            
            $this->sawanna->cache->clear_all_by_type('content');
            
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);
        
        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/nodes');
	}
    
    function bulk_action()
    {
        if (empty($_POST['action']) || !$this->sawanna->has_access('create nodes'))
        {
            echo 'Permission denied';
            die();
        }
        
        switch ($_POST['action'])
        {
            case 'del':
                $this->bulk_delete();
                break;
			case 'remove':
                $this->bulk_remove();
                break;
            case 'publish':
                $this->bulk_publish();
                break;
            case 'unpublish':
                $this->bulk_unpublish();
                break;
            case 'stick':
                $this->bulk_stick();
                break;
            case 'unstick':
                $this->bulk_unstick();
                break;
            case 'front':
                $this->bulk_front();
                break;
            case 'unfront':
                $this->bulk_unfront();
                break;
        }
    }
    
    function bulk_delete()
    {
        if (!$this->sawanna->has_access('create nodes')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
                $this->sawanna->node->delete(trim($id));
            }
            
            // clearing cache
            if ($this->sawanna->cache->clear_on_change('content'))
            {
                foreach ($this->sawanna->cache->clear_on_change['content'] as $type)
                {
                    $this->sawanna->cache->clear_all_by_type($type);
                }
            }
            
            $this->sawanna->cache->clear_all_by_type('content');
            
            echo $this->sawanna->locale->get_string('Nodes were deleted');
        } else
        {
            echo $this->sawanna->locale->get_string('Nodes were not deleted');
        }
        
        die();
    }
    
    function bulk_remove()
    {
        if (!$this->sawanna->has_access('create nodes')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
				$id=trim($id);
                $this->sawanna->node->delete($id);
                
                $res=$this->sawanna->db->query("select path from pre_node_pathes where nid='$1'",$id);
				while($row=$this->sawanna->db->fetch_object($res)) {
					$this->sawanna->navigator->delete($row->path);
					$this->sawanna->navigator->delete($row->path.'/d');
				}
				
				$this->sawanna->db->query("delete from pre_node_pathes where nid='$1'",$id);
				
            }
            
            // clearing cache
            if ($this->sawanna->cache->clear_on_change('content'))
            {
                foreach ($this->sawanna->cache->clear_on_change['content'] as $type)
                {
                    $this->sawanna->cache->clear_all_by_type($type);
                }
            }
            
            $this->sawanna->cache->clear_all_by_type('content');
            
            echo $this->sawanna->locale->get_string('Nodes were deleted');
        } else
        {
            echo $this->sawanna->locale->get_string('Nodes were not deleted');
        }
        
        die();
    }
    
    function bulk_publish()
    {
        if (!$this->sawanna->has_access('create nodes')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
                $this->sawanna->node->enable(trim($id));
            }
            
            // clearing cache
            if ($this->sawanna->cache->clear_on_change('content'))
            {
                foreach ($this->sawanna->cache->clear_on_change['content'] as $type)
                {
                    $this->sawanna->cache->clear_all_by_type($type);
                }
            }
            
            $this->sawanna->cache->clear_all_by_type('content');
            
            echo $this->sawanna->locale->get_string('Nodes were published');
        } else
        {
            echo $this->sawanna->locale->get_string('Nodes were not published');
        }
        
        die();
    }
    
    function bulk_unpublish()
    {
        if (!$this->sawanna->has_access('create nodes')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
                $this->sawanna->node->disable(trim($id));
            }
            
            // clearing cache
            if ($this->sawanna->cache->clear_on_change('content'))
            {
                foreach ($this->sawanna->cache->clear_on_change['content'] as $type)
                {
                    $this->sawanna->cache->clear_all_by_type($type);
                }
            }
            
            $this->sawanna->cache->clear_all_by_type('content');
            
            echo $this->sawanna->locale->get_string('Nodes were unpublished');
        } else
        {
            echo $this->sawanna->locale->get_string('Nodes were not unpublished');
        }
        
        die();
    }
    
    function bulk_stick()
    {
        if (!$this->sawanna->has_access('create nodes')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
                $this->sawanna->node->stick(trim($id));
            }
            
            // clearing cache
            if ($this->sawanna->cache->clear_on_change('content'))
            {
                foreach ($this->sawanna->cache->clear_on_change['content'] as $type)
                {
                    $this->sawanna->cache->clear_all_by_type($type);
                }
            }
            
            $this->sawanna->cache->clear_all_by_type('content');
            
            echo $this->sawanna->locale->get_string('Nodes were made sticky');
        } else
        {
            echo $this->sawanna->locale->get_string('Nodes were not made sticky');
        }
        
        die();
    }
    
    function bulk_unstick()
    {
        if (!$this->sawanna->has_access('create nodes')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
                $this->sawanna->node->unstick(trim($id));
            }
            
            // clearing cache
            if ($this->sawanna->cache->clear_on_change('content'))
            {
                foreach ($this->sawanna->cache->clear_on_change['content'] as $type)
                {
                    $this->sawanna->cache->clear_all_by_type($type);
                }
            }
            
            $this->sawanna->cache->clear_all_by_type('content');
            
            echo $this->sawanna->locale->get_string('Nodes were made not sticky');
        } else
        {
            echo $this->sawanna->locale->get_string('Nodes were not made not sticky');
        }
        
        die();
    }
    
    function bulk_front()
    {
        if (!$this->sawanna->has_access('create nodes')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
                $this->sawanna->node->front(trim($id));
            }
            
            // clearing cache
            if ($this->sawanna->cache->clear_on_change('content'))
            {
                foreach ($this->sawanna->cache->clear_on_change['content'] as $type)
                {
                    $this->sawanna->cache->clear_all_by_type($type);
                }
            }
            
            $this->sawanna->cache->clear_all_by_type('content');
            
            echo $this->sawanna->locale->get_string('Nodes were published on front page');
        } else
        {
            echo $this->sawanna->locale->get_string('Nodes were not published on front page');
        }
        
        die();
    }
    
    function bulk_unfront()
    {
        if (!$this->sawanna->has_access('create nodes')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
                $this->sawanna->node->unfront(trim($id));
            }
            
            // clearing cache
            if ($this->sawanna->cache->clear_on_change('content'))
            {
                foreach ($this->sawanna->cache->clear_on_change['content'] as $type)
                {
                    $this->sawanna->cache->clear_all_by_type($type);
                }
            }
            
            $this->sawanna->cache->clear_all_by_type('content');
            
            echo $this->sawanna->locale->get_string('Nodes were removed from front page');
        } else
        {
            echo $this->sawanna->locale->get_string('Nodes were not removed from front page');
        }
        
        die();
    }
    
    function nodes_list($language,$path=0,$start=0,$limit=10)
    {
        if (!$this->sawanna->has_access('create nodes')) 
        {
            trigger_error('Permission denied',E_USER_WARNING);
            return ;
        }
        
        $html='<h1 class="tab">[str]Nodes[/str]</h1>';
        $html.='<div id="node-list">';
        $html.='<div id="node-filters">';
        
        if (count($this->sawanna->config->languages)>1)
        {
            $html.='<div id="node-language" class="node-filter">';
            $html.='<div class="form_title">[str]Language[/str]:</div>';
            $html.='<select name="language" id="nodes-language" onchange="nodes_select_lang()">';
            
            if ($language=='all')
                $html.='<option value="all" selected>[str]All languages[/str]</option>';
            else 
                $html.='<option value="all">[str]All languages[/str]</option>';
                
            foreach ($this->sawanna->config->languages as $lcode=>$lname)
            {
                if ($lcode==$language)
                    $html.='<option value="'.$lcode.'" selected>'.$lname.'</option>';
                else 
                    $html.='<option value="'.$lcode.'">'.$lname.'</option>';
            }
            $html.= '</select>';
            $html.='<script language="JavaScript">function nodes_select_lang() { window.location.href="'.$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/nodes').'/"+$("#nodes-language").get(0).options[$("#nodes-language").get(0).selectedIndex].value; }</script>';
            $html.='</div>';
        }
        
        $_path='';
        if ($this->sawanna->module->module_exists('menu') && isset($GLOBALS['menuAdminHandler']) && is_object($GLOBALS['menuAdminHandler'])) {
			$menu=&$GLOBALS['menuAdminHandler'];
			$menu_lang=$language!='all' ? $language : $this->sawanna->locale->current;
			$menu_elements=$menu->get_elements_tree($menu_lang,'','','--',0,1);
			
			$menu_elements=array('0000'=>'&darr;&nbsp;[str]Child nodes[/str]')+$menu_elements;
			$menu_elements=array('000'=>'[str]Sticky nodes[/str]')+$menu_elements;
			$menu_elements=array('00'=>'[str]On front page[/str]')+$menu_elements;
			$menu_elements=array('0'=>'[str]All nodes[/str]')+$menu_elements;
			
			$html.='<div id="node-path" class="node-filter">';
            $html.='<div class="form_title">[str]Filter[/str]:</div>';
            $html.='<select name="path" id="nodes-pathes" onchange="nodes_select_path()">';
            
            foreach ($menu_elements as $eid=>$ename)
            {
                if ((string)$eid===(string)$path)
                    $html.='<option value="'.$eid.'" selected>'.$ename.'</option>';
                else 
                    $html.='<option value="'.$eid.'">'.$ename.'</option>';
            }
            $html.= '</select>';
            $html.='<script language="JavaScript">function nodes_select_path() { if($("#nodes-pathes").get(0).options[$("#nodes-pathes").get(0).selectedIndex].value=="0000"){return;};window.location.href="'.$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/nodes/'.$menu_lang).'/-"+$("#nodes-pathes").get(0).options[$("#nodes-pathes").get(0).selectedIndex].value+"-"; }</script>';
            $html.='</div>';
            
            if (!empty($path)) {
				$element=$menu->get_existing_element($path);
				if (!empty($element)) $_path=$element->path;
			}
		}
        
        $html.='</div>';
        $html.='<div class="clear"></div>';
        $html.='<div class="nodes-list-item head">';
        $html.='<div class="id"></div>';
        $html.='<div class="title">[str]Title[/str]</div>';
        $html.='</div>';
            
        $d=new CData();
        
		if ((string)$path==='00') {
			$count=$this->get_front_nodes_count($language);
			$res=$this->get_front_nodes($language,$limit,$start*$limit);
		} elseif ((string)$path==='000') {
			$count=$this->get_sticky_nodes_count($language);
			$res=$this->get_sticky_nodes($language,$limit,$start*$limit);
		} else {
			$count=$this->get_all_nodes_count($language,$_path);
			$res=$this->get_all_nodes($language,$_path,$limit,$start*$limit);
		}
		
        $co=0;
        while ($row=$this->sawanna->db->fetch_object($res)) {
           $node_meta=$d->do_unserialize($row->meta);
            
           if ($co%2===0)
           {
               $html.='<div class="nodes-list-item">';
           } else 
           {
               $html.='<div class="nodes-list-item mark highlighted">';
           }
            $html.='<div class="id"><input type="checkbox" name="bulk_id[]" value="'.$row->id.'" class="bulk_id_checkbox" /></div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/nodes/delete/'.$row->id,'[str]Delete[/str]','button',array('onclick'=>'prompt_delete('.$row->id.');return false')).'</div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/nodes/edit/'.$row->id,'[str]Edit[/str]','button').'</div>';
            $html.='<div class="title">'.(isset($node_meta['title']) ? $node_meta['title'] : '-').'</div>';
            $html.='<div class="state">'.($row->enabled ? '[str]Published[/str]' : '[str]Not published[/str]').' | '.($row->sticky ? '[str]Sticked[/str]' : '[str]Not sticked[/str]').' | '.($row->front ? '[str]On front page[/str]' : '[str]Not on front page[/str]').'</div>';
            $html.='</div>';
            
            $co++;
        }
        
        $html.='</div>';
        
        if ($count)
        {
            $html.='<div class="bulk_block">';
            $html.='<a href="#" id="bulk_id_selector" class="bulk_select_all">[str]Select all[/str]</a>';
            $html.='<select id="bulk_action_selector" name="bulk_action_selector" class="bulk_select" onchange="exec_bulk_action()"><option value="">[str]Choose action[/str]</option><option value="publish">[str]Publish[/str]</option><option value="unpublish">[str]Unpublish[/str]</option><option value="stick">[str]Make sticky[/str]</option><option value="unstick">[str]Make not sticky[/str]</option><option value="front">[str]Publish on front page[/str]</option><option value="unfront">[str]Do not publish on front page[/str]</option><option value="del">[str]Delete[/str]</option><option value="remove">[str]Delete with URL-addresses[/str]</option></select>';
            $html.='</div>';
            
            $html.="<script language=\"javascript\">$(document).ready(function(){ $('a#bulk_id_selector').toggle(function() { $('input[type=checkbox].bulk_id_checkbox').attr('checked','checked'); }, function() { $('input[type=checkbox].bulk_id_checkbox').removeAttr('checked'); }); });</script>";
            $html.="<script language=\"javascript\">function exec_bulk_action(){ var act=$('#bulk_action_selector').val(); var ids=''; if (act.length>0) { $('input[type=checkbox].bulk_id_checkbox').each(function(){ if ($(this).get(0).checked==true) { if (ids.length>0){ids=ids+',';}; ids=ids+$(this).val(); } }); if (ids.length>0 && confirm('[str]Are you sure ?[/str]')) { $.post('".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/nodes/bulk')."',{bulk_ids:ids,action:act},function(data){ alert(data); window.location.href='".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/'.$this->sawanna->arg)."'; }); $('#bulk_action_selector').get(0).disabled=true; } } $('#bulk_action_selector').get(0).selectedIndex=0; refresh_select($('#bulk_action_selector')); }</script>";
       
			$html.='<div id="delete-prompt-wnd"><div><input type="checkbox" name="url-inaccessible" id="url-inaccessible" checked="checked" /><label for="url-inaccessible">[str]Make URL-address inaccessible[/str]</label></div></div>';
			$html.='<script language="JavaScript">$(document).ready(function() { window.setTimeout(\'try { $("#delete-prompt-wnd").dialog({ autoOpen: false, modal: false, resizable: false, title: "[str]Are you sure ?[/str]", buttons: { "[str]Cancel[/str]": function() { $(this).dialog("close"); },"[str]Delete[/str]": do_delete_node } }); } catch (e) { alert ("Error. Prompt window not initialized");} \',300); });function prompt_delete(nid){prompted_node_id=nid;$("#delete-prompt-wnd").dialog("open");};function do_delete_node(){var nid=prompted_node_id;var checked=$("#url-inaccessible").get(0).checked;if(checked){window.location.href="'.$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/nodes/remove').'/"+nid;}else{window.location.href="'.$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/nodes/delete').'/"+nid;}};</script>';
		}
        
        $html.=$this->sawanna->constructor->pager($start,$limit,$count,$this->sawanna->config->admin_area.'/nodes/'.$language.'/-'.$path.'-');
        
        echo $html;
    }
    
    function get_all_nodes_count($language='',$path='')
    {
        if (empty($language)) $language=$this->sawanna->locale->current;
        
		if (empty($path)) return $this->sawanna->node->get_all_nodes_count($language);
		
		if ($path!='[home]') {
			if ($language!='all')
				$this->sawanna->db->query("select count(*) as co from pre_nodes n inner join pre_node_pathes p on n.id=p.nid where n.language='$1' and p.path like '$2%'",$language,$path);
			else 
				$this->sawanna->db->query("select count(*) as co from pre_nodes n inner join pre_node_pathes p on n.id=p.nid where p.path like '$1%'",$path);
		} else {
			if ($language!='all')
				$this->sawanna->db->query("select count(*) as co from pre_nodes n inner join pre_node_pathes p on n.id=p.nid where n.language='$1' and p.path not like '%/%'",$language);
			else 
				$this->sawanna->db->query("select count(*) as co from pre_nodes n inner join pre_node_pathes p on n.id=p.nid where p.path not like '%/%'");
		}
		
        $row=$this->sawanna->db->fetch_object();
        
        return $row->co;
    }
    
    function get_all_nodes($language='',$path='',$limit=10,$offset=0)
    {
        if (empty($language)) $language=$this->locale->current;
		
		if (empty($path)) return $this->sawanna->node->get_all_nodes($language,$limit,$offset);
        
        if ($path!='[home]') {
			if ($language!='all')
				$this->sawanna->db->query("select * from pre_nodes n inner join pre_node_pathes p on n.id=p.nid where n.language='$1' and p.path like '$2%' order by created desc limit $3 offset $4",$language,$path,$limit,$offset);
			else 
				$this->sawanna->db->query("select * from pre_nodes n inner join pre_node_pathes p on n.id=p.nid where p.path like '$1%' order by created desc limit $2 offset $3",$path,$limit,$offset);
		} else {
			if ($language!='all')
				$this->sawanna->db->query("select * from pre_nodes n inner join pre_node_pathes p on n.id=p.nid where n.language='$1' and p.path not like '%/%' order by created desc limit $2 offset $3",$language,$limit,$offset);
			else 
				$this->sawanna->db->query("select * from pre_nodes n inner join pre_node_pathes p on n.id=p.nid where p.path not like '%/%' order by created desc limit $1 offset $2",$limit,$offset);
		}
	}
    
    function get_front_nodes_count($language='')
    {
        if (empty($language)) $language=$this->sawanna->locale->current;
        
        if ($language!='all')
            $this->sawanna->db->query("select count(*) as co from pre_nodes where language='$1' and front='1'",$language);
        else 
            $this->sawanna->db->query("select count(*) as co from pre_nodes where front='1'");
            
        $row=$this->sawanna->db->fetch_object();
        
        return $row->co;
    }
    
    function get_front_nodes($language='',$limit=10,$offset=0)
    {
        if (empty($language)) $language=$this->locale->current;
		
        if ($language!='all')
            $this->sawanna->db->query("select * from pre_nodes where language='$1' and front='1' order by created desc limit $2 offset $3",$language,$limit,$offset);
        else 
            $this->sawanna->db->query("select * from pre_nodes where front='1' order by created desc limit $1 offset $2",$limit,$offset);
    }
    
    function get_sticky_nodes_count($language='')
    {
        if (empty($language)) $language=$this->sawanna->locale->current;
        
        if ($language!='all')
            $this->sawanna->db->query("select count(*) as co from pre_nodes where language='$1' and sticky='1'",$language);
        else 
            $this->sawanna->db->query("select count(*) as co from pre_nodes where sticky='1'");
            
        $row=$this->sawanna->db->fetch_object();
        
        return $row->co;
    }
    
    function get_sticky_nodes($language='',$limit=10,$offset=0)
    {
        if (empty($language)) $language=$this->locale->current;
		
        if ($language!='all')
            $this->sawanna->db->query("select * from pre_nodes where language='$1' and sticky='1' order by created desc limit $2 offset $3",$language,$limit,$offset);
        else 
            $this->sawanna->db->query("select * from pre_nodes where sticky='1' order by created desc limit $1 offset $2",$limit,$offset);
    }
    
    function get_node_templates()
    {
       $tpls=array();

        $dir=ROOT_DIR.'/'.$this->sawanna->tpl->root_dir.'/'.quotemeta($this->sawanna->config->theme);
        $theme_dir=opendir($dir);
        
        while (($file=readdir($theme_dir))!==false)
        {
            if (is_dir($file)) continue;
            
            if (preg_match('/^(([a-zA-Z0-9\._-]+-)?node)\.tpl$/',$file,$matches) && !in_array($file,$tpls)) $tpls[$matches[1]]=$matches[1];
        }
        
        closedir($theme_dir);
        
        $dir=ROOT_DIR.'/'.quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/'.quotemeta($this->sawanna->module->themes_root).'/'.quotemeta($this->sawanna->config->theme);
        if (file_exists($dir)) $theme_dir=opendir($dir); else $theme_dir=false;
        if ($theme_dir)
        {
            while (($file=readdir($theme_dir))!==false)
            {
                if (is_dir($file)) continue;
                
                if (preg_match('/^(([a-zA-Z0-9\._-]+-)?node)\.tpl$/',$file,$matches) && !in_array($file,$tpls)) $tpls[$matches[1]]=$matches[1];
            }
            
            closedir($theme_dir);
        }

        if ($this->sawanna->config->theme==$this->sawanna->tpl->default_theme)
            return $tpls;
            
        $dir=ROOT_DIR.'/'.quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/'.quotemeta($this->sawanna->module->themes_root).'/'.quotemeta($this->sawanna->tpl->default_theme);
        $theme_dir=opendir($dir);
        
        while (($file=readdir($theme_dir))!==false)
        {
            if (is_dir($file)) continue;
            
            if (preg_match('/^(([a-zA-Z0-9\._-]+-)?node)\.tpl$/',$file,$matches) && !in_array($file,$tpls)) $tpls[$matches[1]]=$matches[1];
        }
        
        closedir($theme_dir);
        
        return $tpls;
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
            'value' => '<h1 class="tab">[str]Content settings[/str]</h1>'
        );
        
        $thumbs=$this->sawanna->get_option('content_thumbnail_off');
        
        $fields['settings-thumbs']=array(
            'type' => 'checkbox',
            'title' => '[str]Do not create thumbnails[/str]',
            'description' => '[str]Tick this if you don`t want thumbnails to be created for first image used in node[/str]',
            'attributes'=> $thumbs ? array('checked'=>'checked') : array()
        );
        
        $width=$this->sawanna->get_option('content_thumbnail_width');
        
        $fields['settings-width']=array(
            'type' => 'text',
            'title' => '[str]Thumbnail width[/str]',
            'description' => '[str]Specify the image thumbnails width[/str]',
            'default' => $width ? $width : '120'
        );
        
       $height=$this->sawanna->get_option('content_thumbnail_height');
        
        $fields['settings-height']=array(
            'type' => 'text',
            'title' => '[str]Thumbnail height[/str]',
            'description' => '[str]Specify the image thumbnails height[/str]',
            'default' => $height ? $height : '90'
        );
        
        $crop=$this->sawanna->get_option('content_thumbnail_crop');
        
        $fields['settings-crop']=array(
            'type' => 'checkbox',
            'title' => '[str]Crop thumbnail[/str]',
            'description' => '[str]Specify whether to crop the image to keep its proportion[/str]',
            'attributes'=> $crop ? array('checked'=>'checked') : array()
        );
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        echo $this->sawanna->constructor->form('settings-form',$fields,'settings_form_process',$this->sawanna->config->admin_area.'/nodes/settings',$this->sawanna->config->admin_area.'/nodes/settings','','CContentAdmin');
    }
    
    function settings_form_process()
    {
        if (!$this->sawanna->has_access('su')) 
        {
            return array('[str]Permission denied[/str]',array('content-settings'));
        }
        
        if (isset($_POST['settings-width']) && is_numeric($_POST['settings-width']) && $_POST['settings-width']>0 && $_POST['settings-width']<1280)
        {
            $this->sawanna->set_option('content_thumbnail_width',ceil($_POST['settings-width']),$this->module_id);
        } else 
        {
            $this->sawanna->set_option('content_thumbnail_width',120,$this->module_id);
        }
        
        if (isset($_POST['settings-height']) && is_numeric($_POST['settings-height']) && $_POST['settings-height']>0 && $_POST['settings-height']<1024)
        {
            $this->sawanna->set_option('content_thumbnail_height',ceil($_POST['settings-height']),$this->module_id);
        } else 
        {
            $this->sawanna->set_option('content_thumbnail_height',90,$this->module_id);
        }
        
        if (isset($_POST['settings-crop']) && $_POST['settings-crop']=='on')
        {
            $this->sawanna->set_option('content_thumbnail_crop',1,$this->module_id);
        } else 
        {
            $this->sawanna->set_option('content_thumbnail_crop',0,$this->module_id);
        }
        
         if (isset($_POST['settings-thumbs']) && $_POST['settings-thumbs']=='on')
        {
            $this->sawanna->set_option('content_thumbnail_off',1,$this->module_id);
        } else 
        {
            $this->sawanna->set_option('content_thumbnail_off',0,$this->module_id);
        }
        
         // clearing cache
        if ($this->sawanna->cache->clear_on_change('content'))
        {
            foreach ($this->sawanna->cache->clear_on_change['content'] as $type)
            {
                $this->sawanna->cache->clear_all_by_type($type);
            }
        }
        
        $this->sawanna->cache->clear_all_by_type('content');
        
        return array('[str]Settings saved[/str]');
    }   
}
