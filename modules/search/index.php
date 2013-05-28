<?php
defined('SAWANNA') or die();

class CSearch
{
    var $sawanna;
    var $no_clear_pathes;
    var $start;
    var $limit;
    var $count;
    var $search_text_min;
    var $search_text_max;
    var $exclude_perms;
    
    function CSearch(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->no_clear_pathes=array(
                                    'search',
                                    'captcha',
                                    $this->sawanna->config->admin_area
                                    );
        $this->start=0;
        $this->count=0;
        $this->limit=10;
        
        $this->search_text_min=3;
        $this->search_text_max=255;
    }
    
    function ready()
    {
        if ($this->sawanna->module_exists('comments') && isset($GLOBALS['comments']) && is_object($GLOBALS['comments']) && isset($GLOBALS['comments']->disabled_pathes) && is_array($GLOBALS['comments']->disabled_pathes)) 
        {
            $GLOBALS['comments']->disabled_pathes[]='search';
            $GLOBALS['comments']->disabled_pathes[]='tag';
        }
        
        //if (!in_array($this->sawanna->query,$this->no_clear_pathes)) $this->sawanna->session->clear('search_text');
        
        if ($this->sawanna->query=='search') $this->sawanna->tpl->default_title='[str]Search[/str]';
        if ($this->sawanna->query=='tag' && !empty($this->sawanna->arg))
        {
            if (function_exists('mb_strlen'))
            {
                $this->sawanna->tpl->default_title=mb_strtoupper(mb_substr($this->sawanna->arg,0,1,'utf-8'),'utf-8').mb_substr($this->sawanna->arg,1,mb_strlen(strip_tags(urldecode($this->sawanna->arg))),'utf-8');
            } else 
            {
                $this->sawanna->tpl->default_title=ucwords(strip_tags(urldecode($this->sawanna->arg)));
            }
        }
        
        if (isset($GLOBALS['content']) && is_object($GLOBALS['content']) && isset($GLOBALS['content']->content_alters) && is_array($GLOBALS['content']->content_alters))
        {
            $GLOBALS['content']->content_alters[]=array('class'=>'CSearch','handler'=>'alter_content');
        }
        
        $this->exclude_perms=array();
        
        if (isset($GLOBALS['user']) && is_object($GLOBALS['user']))
        {
            $groups=$GLOBALS['user']->get_user_groups();
            
            foreach($groups as $group_id=>$group_name)
            {
                $perm='user group '.$group_id.' access';
                
                if (!$this->sawanna->has_access($perm))
                {
                    $this->exclude_perms[]=$perm;
                }
            }
        }
    }
    
    function keyword_complete()
    {
        if (!isset($_POST['q'])) return;
        
        $this->sawanna->db->query("select distinct keyword from pre_node_keywords where keyword like '$1%'",$_POST['q']);
        while ($row=$this->sawanna->db->fetch_object()) {
            echo $row->keyword."\n";
        }
    }
    
   function search($arg)
   {
       if (!empty($arg) && is_numeric($arg) && $arg>0) $this->start=floor($arg);
       
       $html=$this->search_form();
       
       $this->do_search($html);
       
       return $html;
   }
   
   function tag($arg)
   {
       if (empty($arg) || !is_string($arg)) return;
       
       $html='';
       $this->do_search_keywords($html,urldecode($arg));
       
       return $html;
   }
   
   function search_form()
   {
       $search=$this->sawanna->session->get('search_text');
       
        $fields=array();
        
        $fields['search-text']=array(
            'type' => 'text',
            'title' => '[str]Search text[/str]',
            'description' => '[str]Enter text to search[/str]',
            'attributes' => array('style'=>'width: 100%'),
            'default' => $search ? htmlspecialchars($search) : '',
            'autocomplete' => array('url'=>'search-text-complete','caller'=>'search','handler'=>'keyword_complete'),
            'required' => true
        );
        
        if ($this->sawanna->has_access('search content text'))
        {
            $text=$this->sawanna->session->get('search_in_nodes_text');
            
            $fields['search-type']=array(
                'type' => 'checkbox',
                'title' => '[str]Search in nodes text[/str]',
                'description' => '[str]By default only keywords search is performed[/str]',
                'attributes' => $text ? array('checked'=>'checked') : array(),
                'required' => false
            );
        }
        
        $fields['search-captcha']=array(
            'type' => 'captcha',
            'name' => 'captcha',
            'title' => '[str]CAPTCHA[/str]',
            'skip' => 3,
            'required' => true
        );
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Search[/str]'
        );
        
        return $this->sawanna->constructor->form('search-form',$fields,'search_form_process','search','search','searchForm','CSearch');
   }
   
   function search_form_process()
   {
       $_POST['search-text']=trim(strip_tags($_POST['search-text']));
       
       if (sawanna_strlen($_POST['search-text']) < $this->search_text_min || sawanna_strlen($_POST['search-text']) > $this->search_text_max)
       {
           return array('[str]Invalid search text[/str]',array('search-text'));
       }
       
       if ($this->sawanna->has_access('search content text') && isset($_POST['search-type']) && $_POST['search-type']=='on')
       {
            $this->sawanna->session->set('search_in_nodes_text',1);
       } else 
       {
           $this->sawanna->session->set('search_in_nodes_text',0);
       }
       
       $this->sawanna->session->set('search_text',$_POST['search-text']);
   }
   
   function do_search(&$html)
   {
       $search=$this->sawanna->session->get('search_text');
       if (!$search) return ;
       
       $text=$this->sawanna->session->get('search_in_nodes_text');
       
       if ($this->sawanna->has_access('search content text') && $text) $this->do_search_text($html);
       else $this->do_search_keywords($html);
       
       $this->pager($html);
   }
   
   function do_search_keywords(&$html,$keyword='')
   {
       $search=empty($keyword) ? $this->sawanna->session->get('search_text') : $keyword;
       if (!$search) return ;
       
       $s=array();
       if (strpos($search,' ')!==false) $s[]="'".$this->sawanna->db->escape_string(trim($search))."'";
       
       $txt=explode(' ',$search);
       foreach ($txt as $text)
       {
           $s[]="'".$this->sawanna->db->escape_string(trim($text))."'";
       }
       if (empty($s)) return ;
       
       $perm_not_in='';
        
        if (!empty($this->exclude_perms))
        {
            foreach($this->exclude_perms as $perm)
            {
                $perm_not_in.="and n.permission <> '".$this->sawanna->db->escape_string($perm)."' ";
            }
        }
       
       $d=new CData();
       
       $this->sawanna->db->query("select count(*) as co from pre_node_keywords k left join pre_node_pathes m on k.nid=m.nid left join pre_nodes n on k.nid=n.id where k.keyword in ( ".implode(', ',$s)." ) and n.language='$1' and n.enabled<>'0' ".$perm_not_in,$this->sawanna->locale->current);
       $row=$this->sawanna->db->fetch_object();
       if (empty($row)) return;
       
       $this->count=$row->co;
       
        $this->sawanna->db->query("select k.keyword,k.nid,m.path,n.content,n.meta from pre_node_keywords k left join pre_node_pathes m on k.nid=m.nid left join pre_nodes n on k.nid=n.id where k.keyword in ( ".implode(', ',$s)." ) and n.language='$1' and n.enabled<>'0' ".$perm_not_in." limit $2 offset $3",$this->sawanna->locale->current,$this->limit,$this->start*$this->limit);
        while ($row=$this->sawanna->db->fetch_object()) 
        {
           if (!empty($row->meta) && $d->is_serialized($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            
           $link=!empty($row->path) ? $row->path : 'node/'.$row->nid;
           $title=!empty($row->meta['title']) ? htmlspecialchars($row->meta['title']) : $row->nid;
           $teaser=!empty($row->meta['teaser']) ? strip_tags($row->meta['teaser']) : sawanna_substr(strip_tags($row->content),0,200);
            
            $html.='<div class="search-item">';
            $html.='<div class="search-title">'.$this->sawanna->constructor->link($link,$title).'</div>';
            $html.='<div class="search-content">'.nl2br($teaser).'</div>';
            $html.='<div class="search-keyword">[str]Keyword[/str]: '.htmlspecialchars(($row->keyword)).'</div>';
            $html.='</div>';
        }
        
        if (!$this->sawanna->db->num_rows()) {
            sawanna_error('[str]Your search did not match any node[/str]');
            $this->sawanna->session->clear('search_text');
        }
   }
   
   function do_search_text(&$html)
   {
       $search=$this->sawanna->session->get('search_text');
       if (!$search) return ;
       
       $s='';
       if (strpos($search,' ')!==false) $s.="n.content like '%".$this->sawanna->db->escape_string(trim($search))."%'";
       
       $txt=explode(' ',$search);
       foreach ($txt as $text)
       {
           if (!empty($s)) $s.=' or ';
           $s.="n.content like '%".$this->sawanna->db->escape_string(trim($text))."%'";
       }
       if (empty($s)) return ;
       
       $perm_not_in='';
        
        if (!empty($this->exclude_perms))
        {
            foreach($this->exclude_perms as $perm)
            {
                $perm_not_in.="and n.permission <> '".$this->sawanna->db->escape_string($perm)."' ";
            }
        }
       
       $d=new CData();
       
       $this->sawanna->db->query("select count(*) as co from pre_nodes n left join pre_node_pathes m on n.id=m.nid where ".$s." and n.language='$1' and n.enabled<>'0' ".$perm_not_in,$this->sawanna->locale->current);
       $row=$this->sawanna->db->fetch_object();
       if (empty($row)) return;

       $this->count=$row->co;
       
        $this->sawanna->db->query("select n.id as nid,m.path,n.content,n.meta from pre_nodes n left join pre_node_pathes m on n.id=m.nid where ".$s." and n.language='$1' and n.enabled<>'0' ".$perm_not_in." limit $2 offset $3",$this->sawanna->locale->current,$this->limit,$this->start*$this->limit);
        while ($row=$this->sawanna->db->fetch_object()) 
        {
           if (!empty($row->meta) && $d->is_serialized($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            
           $link=!empty($row->path) ? $row->path : 'node/'.$row->nid;
           $title=!empty($row->meta['title']) ? htmlspecialchars($row->meta['title']) : $row->nid;
           $teaser=!empty($row->meta['teaser']) ? strip_tags($row->meta['teaser']) : sawanna_substr(strip_tags($row->content),0,200);
            
            $html.='<div class="search-item">';
            $html.='<div class="search-title">'.$this->sawanna->constructor->link($link,$title).'</div>';
            $html.='<div class="search-content">'.nl2br($teaser).'</div>';
            $html.='</div>';
        }
        
        if (!$this->sawanna->db->num_rows()) 
        {
            sawanna_error('[str]Your search did not match any node[/str]');
            $this->sawanna->session->clear('search_text');
        }
   }
   
   function pager(&$html)
    {
        if (empty($this->count)) return;
        
        $html.= $this->sawanna->constructor->pager($this->start,$this->limit,$this->count,'search');
    }
   
   function panel()
   {
       $search=$this->sawanna->session->get('search_text');
       
        $fields=array();
        
        $fields['search-text']=array(
            'type' => 'text',
            'title' => '[str]Search text[/str]',
            'description' => '[str]Enter text to search[/str]',
            'attributes' => array('style'=>'width: 100%'),
            'default' => $search ? htmlspecialchars($search) : '',
        //    'autocomplete' => array('url'=>'search-text-complete','caller'=>'search','handler'=>'keyword_complete'), // no need to load jquery-ui css
            'required' => true
        );
        
        $fields['search-captcha']=array(
            'type' => 'captcha',
            'name' => 'captcha',
            'title' => '[str]CAPTCHA[/str]',
            'skip' => 3,
            'attributes' => array('style'=>'width: 150px'),
            'required' => true
        );
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Search[/str]'
        );
        
        return $this->sawanna->constructor->form('quick-search-form',$fields,'search_form_process','search','search','quickSearchForm','CSearch');
   }
   
   function alter_content(&$content)
   {
        if (empty($content)) return ;
       
        $parse=$this->sawanna->get_option('search_parse_content');
        if (!$parse) return ;
       
        $this->sawanna->db->query("select distinct k.keyword from pre_node_keywords k left join pre_nodes n on k.nid=n.id where n.language='$1'",$this->sawanna->locale->current);
        while ($row=$this->sawanna->db->fetch_object()) 
        {
            //if (strpos($row->keyword,'/')!==false) continue;
            //if (strpos($row->keyword,'.')!==false) continue;
            if (preg_match('/[^a-zа-я\x20\-_]/iu',$row->keyword)) continue;

            //$content=preg_replace('/( |>)('.preg_quote($row->keyword).')( |<)/siu','$1'.$this->sawanna->constructor->link('tag/'.$row->keyword,'$2').'$3',$content,1);
            $content=preg_replace('/(<(?!(?:h1|h2|h3|h4|h5|h6|a|b|strong))[\w\d]+>(?:[^<]+[\x20])?)('.preg_quote($row->keyword).')( |<)/siu','$1'.$this->sawanna->constructor->link('tag/'.$row->keyword,'$2','',array('title'=>ucwords($row->keyword))).'$3',$content,1);
        }
        
   }
}
