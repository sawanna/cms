<?php
defined('SAWANNA') or die();

class CCommentsAdmin
{
    var $sawanna;
    var $module_id;
    
    function CCommentsAdmin(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('comments');
    }
    
    function adminbar()
    {
        if (!$this->sawanna->has_access('administer comments')) return;
            
        return array(
                        array(
                        'comments/search'=>'[str]Search[/str]',
                        'comments'=>'[str]Comments[/str]'
                        ),
                        'comments'
                        );
    }
    
    function optbar()
    {
        return  array(
                        'comments/settings'=>'[str]Comments[/str]',
                        );
    }
    
    function admin_area()
    {
        if (!$this->sawanna->has_access('administer comments')) return ;
        
        $this->sawanna->tpl->add_css($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/'.$this->sawanna->module->themes_root.'/'.$this->sawanna->tpl->default_theme.'/admin');
        
        switch (true)
        {
            case $this->sawanna->arg=='comments/settings':
                $this->settings_form();
                break;
            case preg_match('/^comments\/enable\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->comment_enable($matches[1]);
                break;
            case preg_match('/^comments\/disable\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->comment_disable($matches[1]);
                break;
            case preg_match('/^comments\/delete\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->comment_delete($matches[1]);
                break;
            case preg_match('/^comments\/bulk$/',$this->sawanna->arg,$matches):
                $this->bulk_action();
                break;
            case $this->sawanna->arg=='comments':
                $this->comments_list('all');
                break;
            case preg_match('/^comments\/([\d]+)$/',$this->sawanna->arg,$matches):
                $this->comments_list('all',$matches[1]);
                break;
            case preg_match('/^comments\/([a-zA-Z0-9_-]+)\/([\d]+)$/',$this->sawanna->arg,$matches) && (array_key_exists($matches[1],$this->sawanna->config->languages) || $matches[1]=='all'):
                $this->comments_list($matches[1],$matches[2]);
                break;
            case preg_match('/^comments\/([a-zA-Z0-9_-]+)$/',$this->sawanna->arg,$matches) && (array_key_exists($matches[1],$this->sawanna->config->languages) || $matches[1]=='all'):
                $this->comments_list($matches[1]);
                break;
            case $this->sawanna->arg=='comments/search':
                $this->comments_search();
                break;
        }
    }
    
    function comment_enable($id)
    {
        if (!$this->sawanna->has_access('administer comments')) 
        {
            trigger_error('Could not enable comment. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not enable comment. Invalid comment ID: '.$id,E_USER_WARNING);
            return ;
        }
        
        $event=array();
        
        if (!$this->enable($id))
        {
            $message='[str]Could not activate comment. Database error[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
            $message='[strf]Comment with ID: %'.htmlspecialchars($id).'% activated[/strf]';
            $event['job']='sawanna_message';
            
            // clearing cache
            $this->sawanna->cache->clear_all_by_type('comments');
            
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);
        
        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/comments');
    }
    
     function comment_disable($id)
    {
        if (!$this->sawanna->has_access('administer comments')) 
        {
            trigger_error('Could not disable comment. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not disable comment. Invalid comment ID: '.$id,E_USER_WARNING);
            return ;
        }
        
        $event=array();
        
        if (!$this->disable($id))
        {
            $message='[str]Could not deactivate comment. Database error[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
            $message='[strf]Comment with ID: %'.htmlspecialchars($id).'% deactivated[/strf]';
            $event['job']='sawanna_message';
            
            // clearing cache
            $this->sawanna->cache->clear_all_by_type('comments');
            
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);
        
        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/comments');
    }
    
    function comment_delete($id)
    {
        if (!$this->sawanna->has_access('administer comments')) 
        {
            trigger_error('Could not delete comment. Permission denied',E_USER_WARNING);
            return;
        }
        
        if (empty($id) || !is_numeric($id)) 
        {
            trigger_error('Could not delete comment. Invalid comment ID: '.$id,E_USER_WARNING);
            return ;
        }
        
        $event=array();
        
        if (!$this->delete($id))
        {
            $message='[str]Could not delete comment. Database error[/str]';    
            $event['job']='sawanna_error';
        } else 
        {
            $message='[strf]Comment with ID: %'.htmlspecialchars($id).'% deleted[/strf]';
            $event['job']='sawanna_message';
            
            // clearing cache
            $this->sawanna->cache->clear_all_by_type('comments');
            
        }
        
        $event['argument']=$message;
        $this->sawanna->event->register($event);
        
        $this->sawanna->constructor->redirect($this->sawanna->config->admin_area.'/comments');
    }
    
    function enable($id)
    {
        if (empty($id)) return false;

        return $this->sawanna->db->query("update pre_comments set enabled='1' where id='$1'",$id);
    }
    
    function disable($id)
    {
        if (empty($id)) return false;

        return $this->sawanna->db->query("update pre_comments set enabled='0' where id='$1'",$id);
    }
    
    function delete($id)
    {
        if (empty($id)) return false;

        return $this->sawanna->db->query("delete from pre_comments where id='$1'",$id);
    }
    
    function bulk_action()
    {
        if (empty($_POST['action']) || !$this->sawanna->has_access('administer comments'))
        {
            echo 'Permission denied';
            die();
        }
        
        switch ($_POST['action'])
        {
            case 'del':
                $this->bulk_delete();
                break;
            case 'activate':
                $this->bulk_enable();
                break;
            case 'deactivate':
                $this->bulk_disable();
                break;
        }
    }
    
    function bulk_delete()
    {
        if (!$this->sawanna->has_access('administer comments')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
                $this->delete(trim($id));
            }
            
            // clearing cache
            $this->sawanna->cache->clear_all_by_type('comments');
            
            echo $this->sawanna->locale->get_string('Comments were deleted');
        } else
        {
            echo $this->sawanna->locale->get_string('Comments were not deleted');
        }
        
        die();
    }
    
    function bulk_enable()
    {
        if (!$this->sawanna->has_access('administer comments')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
                $this->enable(trim($id));
            }
            
            // clearing cache
            $this->sawanna->cache->clear_all_by_type('comments');
            
            echo $this->sawanna->locale->get_string('Comments were activated');
        } else
        {
            echo $this->sawanna->locale->get_string('Comments were not activated');
        }
        
        die();
    }
    
    function bulk_disable()
    {
        if (!$this->sawanna->has_access('administer comments')) 
        {
            echo 'Permission denied';
            die();
        }
        
        if (!empty($_POST['bulk_ids']))
        {
            $ids=explode(',',$_POST['bulk_ids']);
            
            foreach($ids as $id)
            {
                $this->disable(trim($id));
            }
            
            // clearing cache
            $this->sawanna->cache->clear_all_by_type('comments');
            
            echo $this->sawanna->locale->get_string('Comments were deactivated');
        } else
        {
            echo $this->sawanna->locale->get_string('Comments were not deactivated');
        }
        
        die();
    }
    
    function get_comments_count($language='')
    {
        if (empty($language)) $language=$this->sawanna->locale->current;
        
        if ($language!='all')
            $this->sawanna->db->query("select count(*) as co from pre_comments where language='$1'",$language);
        else 
            $this->sawanna->db->query("select count(*) as co from pre_comments");
            
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return $row->co;
        return 0;
    }
    
    function get_comment($language,$limit,$offset)
    {
        if (empty($language)) $language=$this->sawanna->locale->current;
        
        if ($language!='all')
            $res=$this->sawanna->db->query("select c.id, u.id as uid, u.login, c.created, c.enabled, c.path, c.content from pre_comments c left join pre_users u on c.uid=u.id where c.language='$1' order by c.created desc limit $2 offset $3",$language,$limit,$offset);
        else 
            $res=$this->sawanna->db->query("select c.id, u.id as uid, u.login, c.created, c.enabled, c.path, c.content from pre_comments c left join pre_users u on c.uid=u.id order by c.created desc limit $1 offset $2",$limit,$offset);
            
        return $res;
    }
    
    function comments_list($language,$start=0,$limit=10)
    {
        if (!$this->sawanna->has_access('administer comments')) 
        {
            trigger_error('Permission denied',E_USER_WARNING);
            return ;
        }
        
        $html='<h1 class="tab">[str]Comments[/str]</h1>';
        $html.='<div id="comments-list">';
        
        if (count($this->sawanna->config->languages)>1)
        {
            $html.='<div id="comments-language">';
            $html.='<div class="form_title">[str]Language[/str]:</div>';
            $html.='<select name="language" id="comment-language" onchange="comments_select_lang()">';
            
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
            $html.='<script langauge="JavaScript">function comments_select_lang() { window.location.href="'.$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/comments').'/"+$("#comment-language").get(0).options[$("#comment-language").get(0).selectedIndex].value; }</script>';
            $html.='</div>';
        }
        
        $html.='<div class="comments-list-item head">';
        $html.='<div class="id"></div>';
        $html.='<div class="comment">[str]Comment[/str]</div>';
        $html.='</div>';
            
        $count=$this->get_comments_count($language);
        
        $co=0;
        $res=$this->get_comment($language,$limit,$start*$limit);
        while ($row=$this->sawanna->db->fetch_object($res)) {
           if ($row->enabled)
           {
               if ($co%2===0)
               {
                   $html.='<div class="comments-list-item">';
               } else 
               {
                   $html.='<div class="comments-list-item mark highlighted">';
               }
           } else {
                $html.='<div class="comments-list-item disabled">';
           }
           
           $row->content=nl2br(strip_tags($row->content));
           if (isset($GLOBALS['comments']) && is_object($GLOBALS['comments']))
           {
                $GLOBALS['comments']->parse_bbcodes($row->content);
                $GLOBALS['comments']->parse_stop_words($row->content);
           }
           
            $html.='<div class="id"><input type="checkbox" name="bulk_id[]" value="'.$row->id.'" class="bulk_id_checkbox" /></div>';
            $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/comments/delete/'.$row->id,'[str]Delete[/str]','button',array('onclick'=>'if (!confirm(\'[str]Are you sure ?[/str]\')){return false;}')).'</div>';
            if (!$row->enabled)
                 $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/comments/enable/'.$row->id,'[str]Activate[/str]','button act').'</div>';
           else 
                $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/comments/disable/'.$row->id,'[str]Deactivate[/str]','button act').'</div>';
            
            $html.='<div class="comment"><div class="path">[str]Address[/str]: '.$row->path.'</div>'.$row->content.'<div class="user">[str]posted by[/str]: '.(!empty($row->uid) ? $this->sawanna->constructor->link('user/'.$row->uid,$row->login,'',array('target'=>'_blank')) : '[str]Guest[/str]').'</div></div>';
            $html.='</div>';
            
            $co++;
        }
        
        $html.='</div>';
        
        if ($count)
        {
            $html.='<div class="bulk_block">';
            $html.='<a href="#" id="bulk_id_selector" class="bulk_select_all">[str]Select all[/str]</a>';
            $html.='<select id="bulk_action_selector" name="bulk_action_selector" class="bulk_select" onchange="exec_bulk_action()"><option value="">[str]Choose action[/str]</option><option value="activate">[str]Activate[/str]</option><option value="deactivate">[str]Deactivate[/str]</option><option value="del">[str]Delete[/str]</option></select>';
            $html.='</div>';
            
            $html.="<script language=\"javascript\">$(document).ready(function(){ $('a#bulk_id_selector').toggle(function() { $('input[type=checkbox].bulk_id_checkbox').attr('checked','checked'); }, function() { $('input[type=checkbox].bulk_id_checkbox').removeAttr('checked'); }); });</script>";
            $html.="<script language=\"javascript\">function exec_bulk_action(){ var act=$('#bulk_action_selector').val(); var ids=''; if (act.length>0) { $('input[type=checkbox].bulk_id_checkbox').each(function(){ if ($(this).get(0).checked==true) { if (ids.length>0){ids=ids+',';}; ids=ids+$(this).val(); } }); if (ids.length>0 && confirm('[str]Are you sure ?[/str]')) { $.post('".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/comments/bulk')."',{bulk_ids:ids,action:act},function(data){ alert(data); window.location.href='".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/'.$this->sawanna->arg)."'; }); $('#bulk_action_selector').get(0).disabled=true; } } $('#bulk_action_selector').get(0).selectedIndex=0; refresh_select($('#bulk_action_selector')); }</script>";
        }
        
        $html.=$this->sawanna->constructor->pager($start,$limit,$count,$this->sawanna->config->admin_area.'/comments/'.$language);
        
        echo $html;
    }
    
    function comments_search()
    {
        if (!$this->sawanna->has_access('administer comments')) 
        {
            trigger_error('Permission denied',E_USER_WARNING);
            return ;
        }
        
        $html='<h1 class="tab">[str]Comments search[/str]</h1>';
        $html.='<div id="comments-list">';
        
        $fields=array();
        
        $search=$this->sawanna->session->get('admin_comments_search_text');
        
        $fields['text']=array(
            'type' => 'text',
            'title' => '[str]Text[/str]',
            'description' => '[str]Enter text to search[/str]',
            'attributes' => array('style' => 'width: 200px'),
            'default' => $search ? $search: '',
            'required' => true
        );
        
        $fields['submit']=array(
            'type'=>'submit',
            'title' => ' ',
            'value'=>'[str]Submit[/str]'
        );

        $html.=$this->sawanna->constructor->form('comments-search-form',$fields,'comments_search_process',$this->sawanna->config->admin_area.'/comments/search',$this->sawanna->config->admin_area.'/comments/search','','CCommentsAdmin');

        $html.='<div class="comments-list-item head">';
        $html.='<div class="id"></div>';
        $html.='<div class="comment">[str]Comment[/str]</div>';
        $html.='</div>';
            
        if ($search)
        {
            $co=0;
            $res=$this->sawanna->db->query("select c.id, u.id as uid, u.login, c.created, c.enabled, c.path, c.content from pre_comments c left join pre_users u on c.uid=u.id where c.content like '%".$this->sawanna->db->escape_string($search)."%' order by c.created desc");
            while ($row=$this->sawanna->db->fetch_object($res)) {
               if ($row->enabled)
               {
                   if ($co%2===0)
                   {
                       $html.='<div class="comments-list-item">';
                   } else 
                   {
                       $html.='<div class="comments-list-item mark highlighted">';
                   }
               } else {
                    $html.='<div class="comments-list-item disabled">';
               }
               
               $row->content=nl2br(strip_tags($row->content));
               if (isset($GLOBALS['comments']) && is_object($GLOBALS['comments']))
               {
                    $GLOBALS['comments']->parse_bbcodes($row->content);
                    $GLOBALS['comments']->parse_stop_words($row->content);
               }
               
                $html.='<div class="id"><input type="checkbox" name="bulk_id[]" value="'.$row->id.'" class="bulk_id_checkbox" /></div>';
                $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/comments/delete/'.$row->id,'[str]Delete[/str]','button',array('onclick'=>'if (!confirm(\'[str]Are you sure ?[/str]\')){return false;}')).'</div>';
                if (!$row->enabled)
                     $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/comments/enable/'.$row->id,'[str]Activate[/str]','button act').'</div>';
               else 
                    $html.='<div class="btn">'.$this->sawanna->constructor->link($this->sawanna->config->admin_area.'/comments/disable/'.$row->id,'[str]Deactivate[/str]','button act').'</div>';
                
                $html.='<div class="comment"><div class="path">[str]Address[/str]: '.$row->path.'</div>'.$row->content.'<div class="user">[str]posted by[/str]: '.(!empty($row->uid) ? $this->sawanna->constructor->link('user/'.$row->uid,$row->login,'',array('target'=>'_blank')) : '[str]Anonymous[/str]').'</div></div>';
                $html.='</div>';
                
                $co++;
            }
        }
        
        $html.='</div>';
        
        if (isset($co) && $co>0)
        {
            $html.='<div class="bulk_block">';
            $html.='<a href="#" id="bulk_id_selector" class="bulk_select_all">[str]Select all[/str]</a>';
            $html.='<select id="bulk_action_selector" name="bulk_action_selector" class="bulk_select" onchange="exec_bulk_action()"><option value="">[str]Choose action[/str]</option><option value="activate">[str]Activate[/str]</option><option value="deactivate">[str]Deactivate[/str]</option><option value="del">[str]Delete[/str]</option></select>';
            $html.='</div>';
            
            $html.="<script language=\"javascript\">$(document).ready(function(){ $('a#bulk_id_selector').toggle(function() { $('input[type=checkbox].bulk_id_checkbox').attr('checked','checked'); }, function() { $('input[type=checkbox].bulk_id_checkbox').removeAttr('checked'); }); });</script>";
            $html.="<script language=\"javascript\">function exec_bulk_action(){ var act=$('#bulk_action_selector').val(); var ids=''; if (act.length>0) { $('input[type=checkbox].bulk_id_checkbox').each(function(){ if ($(this).get(0).checked==true) { if (ids.length>0){ids=ids+',';}; ids=ids+$(this).val(); } }); if (ids.length>0 && confirm('[str]Are you sure ?[/str]')) { $.post('".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/comments/bulk')."',{bulk_ids:ids,action:act},function(data){ alert(data); window.location.href='".$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/'.$this->sawanna->arg)."'; }); $('#bulk_action_selector').get(0).disabled=true; } } $('#bulk_action_selector').get(0).selectedIndex=0; refresh_select($('#bulk_action_selector')); }</script>";
        }
        
        echo $html;
    }
    
    function comments_search_process()
    {
        if (!$this->sawanna->has_access('administer comments')) 
        {
            return array('Permission denied',array('text'));
        }
        
        $this->sawanna->session->set('admin_comments_search_text',trim($_POST['text']));
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
            'value' => '<h1 class="tab">'.'[str]Comments settings[/str]'.'</h1>'
        );
        
        $count=$this->sawanna->get_option('comments_count_per_page');
        
        $fields['settings_count']=array(
            'type' => 'text',
            'title' => '[str]Comments per page[/str]',
            'description' => '[str]Specify the maximum number of comments that should be shown on every page[/str]',
            'default' => $count ? $count : 10,
            'required' => true
        );
        
        $length=$this->sawanna->get_option('comments_text_max_length');
        
         $fields['settings_length']=array(
            'type' => 'text',
            'title' => '[str]Comment text maximum length[/str]',
            'description' => '[str]Unlimited if left empty[/str]',
            'default' => $length ? $length : '',
            'required' => false
        );
        
        $chars=$this->sawanna->get_option('comments_word_max_chars');
        
         $fields['settings_chars']=array(
            'type' => 'text',
            'title' => '[str]Word maximum number of characters[/str]',
            'description' => '[str]Leave empty to not split long words[/str]',
            'default' => $chars ? $chars : '',
            'required' => false
        );
        
        $email=$this->sawanna->get_option('comments_notification_emails');
        
         $fields['settings_emails']=array(
            'type' => 'text',
            'title' => '[str]New comment notification E-Mail addresses[/str]',
            'description' => '[str]You can specify several E-Mail addresses separated by comma. Leave empty if you do not wish to recieve notificaions[/str]',
            'default' => $email ? $email : '',
            'attributes' => array('style'=>'width: 100%'),
            'required' => false
        );
        
        $stop=$this->sawanna->get_option('comments_stop_words');
        
        $fields['settings_stop']=array(
            'type' => 'textarea',
            'title' => '[str]Stop-words[/str]',
            'description' => '[str]Specify every stop-word in a single line[/str]',
            'attributes' => array('rows'=>10,'cols'=>40,'style'=>'width:400px'),
            'default' => $stop ? $stop : ''
        );
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );
        
        echo $this->sawanna->constructor->form('comments-settings-form',$fields,'settings_form_process',$this->sawanna->config->admin_area.'/comments/settings',$this->sawanna->config->admin_area.'/comments/settings','','CCommentsAdmin');
    }
    
    function settings_form_process()
    {
        if (!$this->sawanna->has_access('su')) 
        {
            return array('[str]Permission denied[/str]',array('comments-settings'));
        }
        
        if (empty($_POST['settings_count']) || !is_numeric($_POST['settings_count']) || $_POST['settings_count']<=0)
        {
            return array('[str]Invalid number of comments[/str]',array('settings_count'));
        }
        
        $length='';
        
        if (!empty($_POST['settings_length']) && (!is_numeric($_POST['settings_length']) || $_POST['settings_length']<0))
        {
            return array('[str]Invalid text length[/str]',array('settings_length'));
        }
        
        if (!empty($_POST['settings_length']) && $_POST['settings_length']<50)
        {
            return array('[str]Text length is too short[/str]',array('settings_length'));
        }
        
        if (!empty($_POST['settings_length'])) $length=ceil($_POST['settings_length']);
        
        $chars='';
        
        if (!empty($_POST['settings_chars']) && (!is_numeric($_POST['settings_chars']) || $_POST['settings_chars']<0))
        {
            return array('[str]Invalid number of characters[/str]',array('settings_chars'));
        }
        
        if (!empty($_POST['settings_chars']) && $_POST['settings_chars']<10)
        {
            return array('[str]Number of characters is too small[/str]',array('settings_chars'));
        }
        
        if (!empty($_POST['settings_chars'])) $chars=ceil($_POST['settings_chars']);
        
        $mails=array();
        
        if (!empty($_POST['settings_emails']))
        {
            $emails=explode(',',$_POST['settings_emails']);
            foreach ($emails as $email)
            {
                if (!valid_email(trim($email)))
                {
                    return array('[str]Invalid E-Mail address[/str]',array('settings_emails'));
                }
                
                $mails[]=trim($email);
            }
        }
        
        $mail='';
        if (!empty($mails)) $mail=implode(', ',$mails);
        
        $this->sawanna->set_option('comments_count_per_page',ceil($_POST['settings_count']),$this->module_id);
        $this->sawanna->set_option('comments_text_max_length',$length,$this->module_id);
        $this->sawanna->set_option('comments_word_max_chars',$chars,$this->module_id);
        $this->sawanna->set_option('comments_notification_emails',$mail,$this->module_id);
        $this->sawanna->set_option('comments_stop_words',strip_tags($_POST['settings_stop']),$this->module_id);
        
        return array('[str]Settings saved[/str]');
    }
}
