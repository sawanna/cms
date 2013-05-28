<?php
defined('SAWANNA') or die();

class CComments
{
    var $sawanna;
    var $module_id;
    var $max_length;
    var $word_max_length;
    var $start;
    var $limit;
    var $count;
    var $disabled_pathes;
    var $menu;
    var $is_parent;
    
    function CComments(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->max_length=$this->sawanna->get_option('comments_text_max_length');
        
        $this->word_max_length=$this->sawanna->get_option('comments_word_max_chars');
        
        $limit=$this->sawanna->get_option('comments_count_per_page');
        $this->limit=$limit ? $limit : 10;
        
        $this->start=is_numeric($this->sawanna->arg) && $this->sawanna->arg>0 ? floor($this->sawanna->arg) : 0;
        $this->count=0;
        
        $this->disabled_pathes=array();
        
        $this->module_id=$this->sawanna->module->get_module_id('comments');
        
        if ($this->sawanna->query != $this->sawanna->config->admin_area) 
        {
            $this->sawanna->tpl->add_script(quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/js/prettify');
            $this->sawanna->tpl->add_header('<script language="JavaScript">$(document).ready(function(){prettyPrint()});</script>');
        }    
        $this->is_parent=false;
        
        // deprecated
        //$this->menu=&$GLOBALS['menu'];
    }
    
    function ready()
    {
        if ($this->sawanna->has_access('view comments') && isset($GLOBALS['user']) && is_object($GLOBALS['user']) && isset($GLOBALS['user']->panel) && is_array($GLOBALS['user']->panel) && $GLOBALS['user']->logged() && !$this->sawanna->access->check_su()) $GLOBALS['user']->panel[]=$this->sawanna->constructor->link('user/comments','[str]My comments[/str]');
        
        if ($this->sawanna->query=='user/comments') $this->sawanna->tpl->default_title='[str]My comments[/str]';
        
        $this->is_parent();
    }
    
    function is_parent()
    {
		$this->sawanna->db->query("select * from pre_node_pathes where path like '$1/%'",$this->sawanna->query);
        $row=$this->sawanna->db->fetch_object();
        if (!empty($row))
        {
            $this->is_parent=true;
        }
	}
    
    function get_comment($offset=0,$limit=10)
    {
        $language=$this->sawanna->locale->current;
        $path=$this->sawanna->query;
        
        $res=$this->sawanna->db->query("select c.id, u.id as uid, c.uid as cuid, u.login, c.created, c.enabled, c.path, c.content from pre_comments c left join pre_users u on c.uid=u.id where c.language='$1' and c.path='$2' and c.enabled<>'0' order by c.created asc limit $3 offset $4",$language,$path,$limit,$offset);
        
        return $res;
    }
    
    function get_comments_count()
    {
        $language=$this->sawanna->locale->current;
        $path=$this->sawanna->query;
        
        $this->sawanna->db->query("select count(*) as co from pre_comments where language='$1' and path='$2' and enabled<>'0'",$language,$path);
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return $row->co;
        else return 0;
    }
    
    // deprecated
    function _check_node_is_parent()
    {
        $this->sawanna->db->query("select id from pre_menu_elements where path='$1'",$this->sawanna->query);
        $row=$this->sawanna->db->fetch_object();
        if (empty($row)) return false;
        
        $this->sawanna->db->query("select id from pre_menu_elements where parent='$1'",$row->id);
        $row=$this->sawanna->db->fetch_object();
        if (empty($row)) return false;
        
        return true;
    }
    
    function check_node_is_parent()
    {
        
        return $this->is_parent;
    }
    
    function comments($path='used by caching')
    {
         if (!$this->sawanna->has_access('view comments'))  return ;
         if ($this->sawanna->query=='node') return;
         
         if (in_array($this->sawanna->query,$this->disabled_pathes)) return;
         
         //if ($this->check_node_is_parent()) return;
         
         $this->count=$this->get_comments_count();
         
         if (empty($this->count)) return;
         
         $output_items=array(
            'widget_prefix'=>'<div class="comments-caption"><a name="comments">[str]Comments[/str]:</a></div>',
            'widget_items'=>array(),
            'widget_suffix'=>$this->sawanna->constructor->pager($this->start,$this->limit,$this->count,$this->sawanna->query,'#comments')
            );

         $res=$this->get_comment($this->start*$this->limit,$this->limit);
         while ($row=$this->sawanna->db->fetch_object($res))
         {
             $username=$row->login;
             $user_avatar='';
             $user_gender='';
             
             if (isset($GLOBALS['user']))
             {
                 if ($row->uid)
                 {
                     $user=$GLOBALS['user']->get_user($row->uid);
                     $usermeta=$GLOBALS['user']->get_user_meta($user);
                 } elseif ($row->cuid==-1)
                 {
                     $usermeta=$GLOBALS['user']->get_super_user();
                 } else
                 {
                     $usermeta=$GLOBALS['user']->get_anonymous_user();
                 }
                 
                 $username=$usermeta['name'];
                 $user_avatar=$usermeta['avatar'];
                 $user_gender=$usermeta['gender'];
             }
             
             $row->content=nl2br(strip_tags($row->content));
             $this->parse_bbcodes($row->content);
             $this->parse_emoticons($row->content);
             $this->parse_stop_words($row->content);
             
             $output_items['widget_items'][]=array(
                'username' => $username,
                'userlink' => !empty($row->uid) && $this->sawanna->has_access('view user profile') ? $this->sawanna->constructor->link('user/'.$row->uid,$username,'',array('target'=>'_blank')) : $username,
                'user_avatar'=>!empty($user_avatar) ? '<img src="'.$user_avatar.'" width="100" />' : '',
                'user_gender'=>!empty($user_gender) ? '<label>[str]Gender[/str]:</label> '.$user_gender : '',
                'content' => $row->content,
                'posted' => date($this->sawanna->config->date_format,$row->created)
             );
         }
         
         return $output_items;
    }
    
    function get_user_comment($uid,$offset=0,$limit=10)
    {
        $language=$this->sawanna->locale->current;
        $path=$this->sawanna->query;
        
        $res=$this->sawanna->db->query("select c.id, u.id as uid, c.uid as cuid, u.login, c.created, c.enabled, c.path, c.content from pre_comments c left join pre_users u on c.uid=u.id where c.uid='$1' and c.enabled<>'0' order by c.created desc limit $2 offset $3",$uid,$limit,$offset);
        
        return $res;
    }
    
    function get_user_comments_count($uid)
    {
        $this->sawanna->db->query("select count(*) as co from pre_comments where uid='$1' and enabled<>'0'",$uid);
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return $row->co;
        else return 0;
    }
    
    function user_comments()
    {
         if (!$this->sawanna->has_access('view comments'))  return ;
         
         if (empty($GLOBALS['user']->current->id)) return;
         
         $this->count=$this->get_user_comments_count($GLOBALS['user']->current->id);
         
         $output_items=array(
            'widget_prefix'=>'<div class="comments-caption">[str]My comments[/str]:</div>',
            'widget_items'=>array(),
            'widget_tpls' => array(),
            'widget_suffix'=>$this->sawanna->constructor->pager($this->start,$this->limit,$this->count,'user/comments')
            );
            
         $res=$this->get_user_comment($GLOBALS['user']->current->id,$this->start*$this->limit,$this->limit);
         while ($row=$this->sawanna->db->fetch_object($res))
         {
             $username=$row->login;
             $user_avatar='';
             $user_gender='';
             
             if (isset($GLOBALS['user']))
             {
                 $user=$GLOBALS['user']->get_user($row->uid);
                 $usermeta=$GLOBALS['user']->get_user_meta($user);

                 $username=$usermeta['name'];
                 $user_avatar=$usermeta['avatar'];
                 $user_gender=$usermeta['gender'];
             }
             
             $row->content=nl2br(strip_tags($row->content));
             $this->parse_bbcodes($row->content);
             $this->parse_emoticons($row->content);
             $this->parse_stop_words($row->content);
             
             $output_items['widget_items'][]=array(
                'username' => $username,
                'userlink' => !empty($row->uid) && $this->sawanna->has_access('view user profile') ? $this->sawanna->constructor->link('user/'.$row->uid,$username,'',array('target'=>'_blank')) : $username,
                'user_avatar'=>!empty($user_avatar) ? '<img src="'.$user_avatar.'" width="100" />' : '',
                'user_gender'=>!empty($user_gender) ? '<label>[str]Gender[/str]:</label> '.$user_gender : '',
                'content' => $row->content.'<div class="my-comments-url">'.$this->sawanna->constructor->link($row->path,SITE.'/'.$row->path).'</div>',
                'posted' => date($this->sawanna->config->date_format,$row->created)
             );
             
             $output_items['widget_tpls'][]='comments';
         }
         
         return $output_items;
    }
    
    function parse_bbcodes(&$content)
    {
        // common bbcodes
        $content=preg_replace('/\[b\](.*?)\[\/b\]/si','<b>$1</b>',$content);
        $content=preg_replace('/\[i\](.*?)\[\/i\]/si','<em>$1</em>',$content);
        $content=preg_replace('/\[u\](.*?)\[\/u\]/si','<u>$1</u>',$content);
        $content=preg_replace('/\[s\](.*?)\[\/s\]/si','<s>$1</s>',$content);
        $content=preg_replace('/\[color=([0-9a-zA-Z#]+)\](.*?)\[\/color\]/si','<span style="color:$1">$2</span>',$content);
        $content=preg_replace('/\[size=([\d]{1,2})[\d]*\](.*?)\[\/size\]/si','<span style="font-size:$1px">$2</span>',$content);
        
        if (preg_match_all('/\[list(=[\d]+)?\](.*)\[\/list\]/s',$content,$matches))
        {
           foreach ($matches[2] as $match)
           {
               if (strpos($match,'[*]')!==false) $match=substr($match,strpos($match,'[*]')+3);
               $match='<li>'.implode('</li><li>',explode('[*]',$match)).'</li>';
           }
           
           $content=str_replace($matches[2],$match,$content);
           $content=str_replace('<br />','',$content);
           
           $content=preg_replace('/\[list=([\d]+)\](.*?)\[\/list\]/si','<ol start="$1">$2</ol>',$content);
           $content=preg_replace('/\[list\](.*?)\[\/list\]/si','<ul>$1</ul>',$content);
        }
        
        $content=preg_replace('/\[quote\](.*?)\[\/quote\]/si','<blockquote>$1</blockquote>',$content);
        $content=preg_replace('/\[code\](.*?)\[\/code\]/si','<div class="kode"><code class="prettyprint">$1</code></div>',$content);
        
        // extra bbcodes
        $content=preg_replace('/\[url\](http:\/\/.*?)\[\/url\]/si','<noindex><a href="$1" rel="nofollow" target="_blank">$1</a></noindex>',$content);
        $content=preg_replace('/\[url=(http:\/\/.+)\](.*?)\[\/url\]/si','<noindex><a href="$1" rel="nofollow" target="_blank">$2</a></noindex>',$content);
        $content=preg_replace('/\[img\](http:\/\/.*?)\[\/img\]/si','<p class="img"><img src="$1" alt="$1" title="$1" /></p>',$content);
        
        // removing spaces
        if (preg_match_all('/<a href="([^"]+)" [^>]*>/s',$content,$matches))
        {
           foreach ($matches[1] as $match)
           {
               $match=preg_replace('/[\x20]+/s','',$match);
           }
           
           $content=str_replace($matches[1],$match,$content);
        }
        
        if (preg_match_all('/<img src="([^"]+)" [^>]*>/s',$content,$matches))
        {
           foreach ($matches[1] as $match)
           {
               $match=preg_replace('/[\x20]+/s','',$match);
           }
           
           $content=str_replace($matches[1],$match,$content);
        }
    }
    
    function fix_bbcodes(&$content)
    {
        // common bbcodes
         if (!$this->sawanna->has_access('use common bbcodes in comments'))
        {
            $content=preg_replace('/\[b\](.*?)\[\/b\]/si','{b}$1{/b}',$content);
            $content=preg_replace('/\[i\](.*?)\[\/i\]/si','{i}$1{/i}',$content);
            $content=preg_replace('/\[u\](.*?)\[\/u\]/si','{u}$1{/u}',$content);
            $content=preg_replace('/\[s\](.*?)\[\/s\]/si','{s}$1{/s}',$content);
            $content=preg_replace('/\[color=([0-9a-zA-Z#]+)\](.*?)\[\/color\]/si','{color=$1}$2{/color}',$content);
            $content=preg_replace('/\[size=([0-9]+)\](.*?)\[\/size\]/si','{size=$1}$2{/size}',$content);
            
            $content=preg_replace('/\[quote\](.*?)\[\/quote\]/si','{quote}$1{/quote}',$content);
            $content=preg_replace('/\[code\](.*?)\[\/code\]/si','{code}$1{/code}',$content);
            
            $content=preg_replace('/\[list=([\d]+)\](.*?)\[\/list\]/si','{list=$1}$2{\/list}',$content);
            $content=preg_replace('/\[list\](.*?)\[\/list\]/si','{list}$1{\/list}',$content);
        }
        
        // extra bbcodes
        if (!$this->sawanna->has_access('use extra bbcodes in comments'))
        {
            $content=preg_replace('/\[url\](http:\/\/.*?)\[\/url\]/si','{url}$1{/url}',$content);
            $content=preg_replace('/\[url=(http:\/\/.+)\](.*?)\[\/url\]/si','{url=$1}$2{/url}',$content);
            $content=preg_replace('/\[img\](http:\/\/.*?)\[\/img\]/si','{img}$1{/img}',$content);
        }
    }
    
    function strip_bbcodes(&$content)
    {
        // common bbcodes
        $content=preg_replace('/\[b\](.*?)\[\/b\]/si','$1',$content);
        $content=preg_replace('/\[i\](.*?)\[\/i\]/si','$1',$content);
        $content=preg_replace('/\[u\](.*?)\[\/u\]/si','$1',$content);
        $content=preg_replace('/\[s\](.*?)\[\/s\]/si','$1',$content);
        $content=preg_replace('/\[color=([0-9a-zA-Z#]+)\](.*?)\[\/color\]/si','$2',$content);
        $content=preg_replace('/\[size=([0-9]+)\](.*?)\[\/size\]/si','$2',$content);
        
        $content=preg_replace('/\[quote\](.*?)\[\/quote\]/si','$1',$content);
        $content=preg_replace('/\[code\](.*?)\[\/code\]/si','$1',$content);
        
        $content=preg_replace('/\[list=([\d]+)\](.*?)\[\/list\]/si','$2',$content);
        $content=preg_replace('/\[list\](.*?)\[\/list\]/si','$1',$content);
        $content=str_replace('[*]',' ',$content);
        
        // extra bbcodes
        $content=preg_replace('/\[url\](http:\/\/.*?)\[\/url\]/si','$1',$content);
        $content=preg_replace('/\[url=(http:\/\/.+)\](.*?)\[\/url\]/si','$2',$content);
        $content=preg_replace('/\[img\](http:\/\/.*?)\[\/img\]/si','$1',$content);
    }
    
    function get_emoticons()
    {
        $emoticons=array(
            ':)' => 'smile',
            ':D' => 'big_grin',
            ';)' => 'wink',
            ':(' => 'sad',
            ':o' => 'surprised',
            ':x' => 'mad',
            ':]' => 'glasses',
            ':angry:' => 'angry',
            ':cry:' => 'cry',
            ':happy:' => 'happy',
            ':sleep:' => 'sleep',
            ':stressed:' => 'stressed',
            ':tongue:' => 'tongue'
        );
        
        return $emoticons;
    }
    
    function parse_emoticons(&$content)
    {
        $emoticons=$this->get_emoticons();
        
        foreach ($emoticons as $code=>$class)
        {
            $content=preg_replace('/^'.preg_quote($code).'([\x20]|<br \/>)/si','<span class="emoticon '.preg_quote($class).'">&nbsp;</span>$1',$content);
            $content=preg_replace('/([\x20]|<br \/>)'.preg_quote($code).'([\x20]|<br \/>)/si','$1<span class="emoticon '.preg_quote($class).'">&nbsp;</span>$2',$content);
            $content=preg_replace('/([\x20]|<br \/>)'.preg_quote($code).'$/si','$1<span class="emoticon '.preg_quote($class).'">&nbsp;</span>',$content);
        }
    }
    
    function parse_stop_words(&$content)
    {
        $stop=$this->sawanna->get_option('comments_stop_words');
        if (!$stop) return ;
        
        $stops=explode("\n",$stop);
        if (empty($stops) || !is_array($stops)) return ;
        
        foreach ($stops as $word)
        {
            $content=preg_replace('/'.preg_quote(trim($word)).'/si','[[str]censored[/str]]',$content);
        }
    }
    
    function comments_form()
    {
        if (!$this->sawanna->has_access('create comments'))  return ;
        if ($this->sawanna->query=='node') return;
        
        if (in_array($this->sawanna->query,$this->disabled_pathes)) return;
        
        if ($this->check_node_is_parent()) return;
        
        $fields=array();
        
        $editor='';
        if ($this->sawanna->has_access('use common bbcodes in comments'))
        {
            $editor.='<div class="btn bold"></div>
                    <div class="btn italic"></div>
                    <div class="btn underline"></div>
                    <div class="btn quote"></div>
                    <div class="btn code"></div>
                    <div class="btn usize"></div>
                    <div class="btn dsize"></div>
                    <div class="btn nlist"></div>
                    <div class="btn blist"></div>
                    <div class="btn litem"></div>
                    <div class="btn back"></div>
                    <div class="btn forward"></div>';
        }
        if ($this->sawanna->has_access('use extra bbcodes in comments'))
        {
            $editor.='<div class="btn link"></div>
                           <div class="btn image"></div>';
        }
        if ($this->use_bbcode())
        {
            $editor.=$this->bbcode_editor();
        }
        
        $fields['comment-editor']=array(
            'type'=>'html',
            'value'=>$editor
        );
        
        $this->sawanna->tpl->add_script('js/paste');
        
        $smiles='';
        $emoticons=$this->get_emoticons();
        foreach ($emoticons as $code=>$class)
        {
            $smiles.='<span class="emoticon '.quotemeta($class).'" onclick="try { $(\'#comment-content\').insertAtCaret(\' '.htmlspecialchars($code).' \'); } catch(e) { $(\'#comment-content\').get(0).value+=\' '.htmlspecialchars($code).' \'; }" style="cursor:pointer">&nbsp;</span>';
        }
        
        $fields['comment-emoticons']=array(
            'type'=>'html',
            'value'=>$smiles
        );

        $fields['comment-content']=array(
            'type' => 'textarea',
            'title' => '[str]Your comment[/str]',
            'description' => '[str]Enter your comment text here[/str]',
            'attributes' => array('style'=>'width: 100%','rows'=>10,'cols'=>40),
            'default' => '',
            'required' => true
        );
        
        if (!$this->sawanna->has_access('create comments without captcha'))
        {
            $fields['comment-captcha']=array(
                'type' => 'captcha',
                'name' => 'captcha',
                'title' => '[str]CAPTCHA[/str]',
                'required' => true
            );
        }
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        return $this->sawanna->constructor->form('comments-form',$fields,'comments_form_process',$this->sawanna->query,$this->sawanna->query,'commentsForm','CComments');
    }
    
    function use_bbcode()
    {
        if ($this->sawanna->has_access('use common bbcodes in comments') || $this->sawanna->has_access('use extra bbcodes in comments')) return true;
        
        return false;
    }
    
    function bbcode_editor()
    {
        $script='<script language="JavaScript" src="'.SITE.'/'.quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/js/jquery-bbcodeeditor.js"></script>';
        $script.='<script type="text/javascript">';
        $script.="$(function(){";
        $script.="$('textarea[name=comment-content]').bbcodeeditor(";
        $script.="{";
        if ($this->sawanna->has_access('use common bbcodes in comments'))
        {
            $script.="bold:$('.bold'),italic:$('.italic'),underline:$('.underline'),quote:$('.quote'),code:$('.code'),";
            $script.="usize:$('.usize'),dsize:$('.dsize'),nlist:$('.nlist'),blist:$('.blist'),litem:$('.litem'),";
            $script.="back:$('.back'),forward:$('.forward'),back_disable:'btn back_disable',forward_disable:'btn forward_disable',";
        }
        if ($this->sawanna->has_access('use extra bbcodes in comments'))
        {
            $script.="link:$('.link'),image:$('.image'),";
        }
        $script.="exit_warning:false";
        $script.="});";
        $script.="});";
        $script.="</script>";
        
        return $script;
    }
    
    function comments_form_process()
    {
        if (!$this->sawanna->has_access('create comments'))  return array('Permission denied',array('comment-content'));
        
        if ($this->check_node_is_parent()) return;
        
        if ($this->max_length && sawanna_strlen($_POST['comment-content']) > $this->max_length) return array('[str]Comment text is too long[/str]',array('comment-content'));
        
        $this->fix_bbcodes($_POST['comment-content']);
        $this->fix_comment($_POST['comment-content']);
        
        $enabled=false;
        if ($this->sawanna->has_access('create comments without approval')) $enabled=true;
        
        $uid=$this->sawanna->access->check_su() ? -1 : 0;
        
        if (!$this->set($this->sawanna->locale->current,(!empty($GLOBALS['user']->current->id) ? $GLOBALS['user']->current->id : $uid),$this->sawanna->query,$_POST['comment-content'],$enabled))
        {
            return array('[str]Could not save your comment. Try later[/str]',array('comment-content'));
        }
        
        $this->send_notification();
        
        if (!$enabled)
            return array('[str]Thank you. Your comment was put on moderation[/str]');
        else 
        {
            $this->sawanna->cache->clear_all_by_type('comments');
            return array('[str]Thank you. Your comment saved[/str]');
        }
    }
    
    function set($language,$uid,$path,&$content,$enabled=false)
    {
        if (empty($content)) return false;
        
        if (empty($language)) $language=$this->sawanna->locale->current;
        if (empty($path)) $path=$this->sawanna->query;
        if (empty($uid)) $uid=0;
        $enabled=$enabled ? 1 : 0;

        if (!$this->sawanna->db->query("insert into pre_comments (uid,path,created,enabled,language,content) values ('$1','$2','$3','$4','$5','$6')",$uid,$path,time(),$enabled,$language,$content))
            return false;
            
        return true;
    }
    
    function fix_comment(&$content)
    {
        $content=htmlspecialchars($content);
        
        if ($this->max_length && sawanna_strlen($content) > $this->max_length) $content=sawanna_substr($content,0,$this->max_length).' ...';
        
        if ($this->word_max_length)
        {
            while (preg_match('/([^\x20\xA\xD]{'.preg_quote($this->word_max_length+1).',})/us',$content,$matches))
            {
                $content=str_replace($matches[1],sawanna_substr($matches[1],0,$this->word_max_length).' '.sawanna_substr($matches[1],$this->word_max_length),$content);
            }
        }
    }
    
    function send_notification()
    {
        $emails=$this->sawanna->get_option('comments_notification_emails');

        if (!$emails) return ;
        
        $message=$this->sawanna->locale->get_string('Hello').' '.$this->sawanna->config->su['user'].' !'."\n";
        $message.=$this->sawanna->locale->get_string('New comment have been posted on').' '.$this->sawanna->constructor->url($this->sawanna->query,false,false,true)."\n";
        $message.=$this->sawanna->locale->get_string('Please review').': '.$this->sawanna->constructor->url($this->sawanna->config->admin_area.'/comments',false,false,true)."\n\n";
    
        $this->sawanna->constructor->sendMail($emails,$this->sawanna->config->send_mail_from,$this->sawanna->config->send_mail_from_name,$this->sawanna->locale->get_string('New comment posted'),$message);
    }
    
    function latest_comments()
    {
        if (!$this->sawanna->has_access('view comments'))  return ;
         
        $html='';
        
        $limit=5;
        $chars=25;
        
        $language=$this->sawanna->locale->current;
        
        $res=$this->sawanna->db->query("select c.id, u.id as uid, c.uid as cuid, u.login, c.created, c.enabled, c.path, c.content from pre_comments c left join pre_users u on c.uid=u.id where c.language='$1' and c.enabled<>'0' order by c.created desc limit $2",$language,$limit);
        
        while ($row=$this->sawanna->db->fetch_object($res))
         {
             $username=$row->login;
             $user_avatar='';
             $user_gender='';
             
             if (isset($GLOBALS['user']))
             {
                 if ($row->uid)
                 {
                     $user=$GLOBALS['user']->get_user($row->uid);
                     $usermeta=$GLOBALS['user']->get_user_meta($user);
                 } elseif ($row->cuid==-1)
                 {
                     $usermeta=$GLOBALS['user']->get_super_user();
                 } else 
                 {
                     $usermeta=$GLOBALS['user']->get_anonymous_user();
                 }
                 
                 $username=$usermeta['name'];
                 $user_avatar=$usermeta['avatar'];
                 $user_gender=$usermeta['gender'];
             }
             
             $row->content=strip_tags($row->content);
             if (sawanna_strlen($row->content)>$chars) $row->content=sawanna_substr($row->content,0,$chars).' ...';

             $this->strip_bbcodes($row->content);
             $this->parse_emoticons($row->content);
             $this->parse_stop_words($row->content);
             
             $html.='<div class="latest-comment-item">';
             $html.='<div class="latest-comment-date">'.date($this->sawanna->config->date_format,$row->created).'</div>';
             $html.='<div class="latest-comment-text">'.$this->sawanna->constructor->link($row->path.'#comments',$row->content,'','',false,true).'</div>';
             $html.='<div class="latest-comment-user">[str]posted by[/str]: '.(!empty($row->uid) && $this->sawanna->has_access('view user profile') ? $this->sawanna->constructor->link('user/'.$row->uid,$username,'',array('target'=>'_blank')) : $username).'</div>';
             $html.="</div>";
             $html.='<div class="latest-comments-separator"></div>';
         }
         
         if (empty($html)) return ;
         
         return array('latest_comments'=>$html);
    }
    
    function ajaxer(&$scripts,&$outputs)
    {
		$this->is_parent();
		$this->start=is_numeric($this->sawanna->arg) && $this->sawanna->arg>0 ? floor($this->sawanna->arg) : 0;
		
		$scripts.='prettyPrint();'."\r\n";
	}
}
