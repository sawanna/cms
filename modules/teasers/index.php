<?php
defined('SAWANNA') or die();

class CTeasers
{
    var $sawanna;
    var $module_id;
    var $tree;
    var $pathes;
    var $menu_element_id;
    var $thumbs_dir;
    var $thumb_width;
    var $thumb_height;
    var $limit;
    var $start;
    var $count;
    var $front;
    var $query;
    var $menu;
    var $exclude_perms;
    var $thumbs;
    
    function CTeasers(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('teasers');
        $this->thumbs_dir='thumbs';
        
        $this->thumbs=array();
        
        
        $thumb_width=$this->sawanna->get_option('content_thumbnail_width');
        $thumb_height=$this->sawanna->get_option('content_thumbnail_height');
        
        $this->thumb_width=$thumb_width ? $thumb_width : 120;
        $this->thumb_height=$thumb_height ? $thumb_height : 90;
        
        $this->limit=5;
        $this->start=0;
        $this->count=0;

        $this->query='';
        
        $this->sawanna->cache->set_clear_on_change('content','teasers');
        $this->sawanna->cache->set_clear_on_change('menu','teasers');
        
        if ($this->sawanna->query != $this->sawanna->config->admin_area) 
        {
            $this->sawanna->tpl->add_script($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/js/teaser');
            
            $script='<script language="JavaScript">';
            $script.='teaser_thumbnail_zoomer_width='.$this->thumb_width.';';
            $script.='teaser_thumbnail_zoomer_height='.$this->thumb_height.';';
            $script.='</script>';
            $this->sawanna->tpl->add_header($script);
        }
    }
    
    function init()
    {
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
    
    function get_teaser($name)
    {
        $this->sawanna->db->query("select * from pre_teasers where name='$1'",$name);
        $row=$this->sawanna->db->fetch_object();
        
        if (empty($row)) return false;
        
        if (!empty($row->meta))
        {
            $d=new CData();
            $row->meta=$d->do_unserialize($row->meta);
        }
        
        return $row;
    }
    
    function fix_widget($teaser)
    {
        $wid=$this->sawanna->module->get_widget_id($teaser->name);
        if(!$wid) return false;
        
        $tpl='widget';
        if (isset($teaser->meta['tpl']) && $teaser->meta['tpl']=='node') $tpl='node';
        
        $this->sawanna->module->widgets[$wid]->tpl=$tpl;
        return true;
    }

    function teasers($name)
    {
        if (empty($name)) return ;
        
        $teaser=$this->get_teaser($name);
        if (!$teaser) return ;
        
        $this->query=$teaser->path;
        if (isset($teaser->meta['count']) && is_numeric($teaser->meta['count'])) $this->limit=$teaser->meta['count'];
        
        if ($this->query=='node') return;
        
        $this->init();
        
        $html='';
        
        $tpl='widget';
        if (isset($teaser->meta['tpl']) && $teaser->meta['tpl']=='node') $tpl='node';
        
        $snids=$this->get_child_nodes(true);
        $onids=$this->get_child_nodes(false,$this->start*$this->limit,$this->limit);
        
        $this->sawanna->file->preload($this->thumbs,true);
        
        if (!empty($snids) && is_array($snids))
        {
            foreach ($snids as $nid)
            {
                $output=array();
                
                $this->get_node_teaser($nid,$output);
                
                if (empty($output)) continue;
                
                $html.='<div class="teaser-item teaser-sticky">';
                $html.='<div class="teaser-date">'.$output['date'].'</div>';
                $html.='<div class="teaser-title">'.$output['title'].'</div>';
                $html.='<div class="teaser-body">';
                $html.='<div class="teaser-thumbnail">'.$output['thumbnail'].'</div>';
                $html.='<div class="teaser-content">'.$output['content'].'</div>';
                $html.='</div>';
                
                if ($tpl=='node')
                    $html.='<div class="teaser-link">'.$output['btn-link'].'</div>';
                else
                    $html.='<div class="teaser-link">'.$output['link'].'</div>';
                
                $html.='<div class="clear"></div>';    
                $html.='</div>';
                $html.='<div class="teaser-separator"></div>';
            }
        }
        
        if (!empty($onids) && is_array($onids))
        {
            foreach ($onids as $nid)
            {
                $output=array();
                
                $this->get_node_teaser($nid,$output);
                
                if (empty($output)) continue;
                
                $html.='<div class="teaser-item">';
                $html.='<div class="teaser-date">'.$output['date'].'</div>';
                $html.='<div class="teaser-title">'.$output['title'].'</div>';
                $html.='<div class="teaser-body">';
                $html.='<div class="teaser-thumbnail">'.$output['thumbnail'].'</div>';
                $html.='<div class="teaser-content">'.$output['content'].'</div>';
                $html.='</div>';
                
                 if ($tpl=='node')
                    $html.='<div class="teaser-link">'.$output['btn-link'].'</div>';
                else
                    $html.='<div class="teaser-link">'.$output['link'].'</div>';
                    
                $html.='<div class="clear"></div>';   
                $html.='</div>';
                $html.='<div class="teaser-separator"></div>';
            }
        }
       
        if (!empty($html))
        {
            if (!$this->fix_widget($teaser)) return;
            
            $title='';
            if (isset($teaser->meta['name'][$this->sawanna->locale->current])) $title=htmlspecialchars($teaser->meta['name'][$this->sawanna->locale->current]);
            
            return array('content'=>$html,'title'=>$title);
        }
    }
    
    function get_node_teaser($nid,&$output)
    {
        $node=$this->sawanna->node->get($nid);
        if (!empty($node) && is_object($node))
        {
            if (empty($node->meta['teaser'])) return;
            
            if (!empty($node->meta['author']))
            {
                if (strpos($node->meta['author']['url'],$this->sawanna->config->admin_area)===0)
                    $node_author=$node->meta['author']['name'];
                else 
                    $node_author=$this->sawanna->constructor->link($node->meta['author']['url'],$node->meta['author']['name'],'profile');
            } else 
            {
                $node_author='[str]Unknown[/str]';
            }
            
            $thumbnail='';
            $thumb=$this->sawanna->file->get('node-'.$nid);
            if ($thumb && $thumb->path==$this->thumbs_dir && file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->thumbs_dir).'/'.quotemeta($thumb->name)))
            {
                $size=getimagesize(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->thumbs_dir).'/'.quotemeta($thumb->name));
                if ($size && $size[0]==$this->thumb_width && $size[1]==$this->thumb_height)
                {
                    $thumbnail='<img src="'.SITE.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->thumbs_dir).'/'.quotemeta($thumb->name).'" width="'.$this->thumb_width.'" height="'.$this->thumb_height.'" />';
                }
            }
            
            $output['date']=date($this->sawanna->config->date_format,$node->pub_tstamp);
            
            $title=!empty($node->meta['title']) ? strip_tags($node->meta['title']) : '';
            $output['title']=isset($this->pathes[$nid]) ? $this->sawanna->constructor->link($this->pathes[$nid],$title) : $this->sawanna->constructor->link('node/'.$nid,$title) ;
            
            $output['content']=nl2br(strip_tags($node->meta['teaser']));
            $output['author']=$node_author;
            $output['keywords']=!empty($node->meta['keywords']) ? $node->meta['keywords'] : '';
            $output['thumbnail']=$thumbnail;
            $output['link']=isset($this->pathes[$nid]) ? $this->sawanna->constructor->link($this->pathes[$nid],'[str]Read more[/str]') : $this->sawanna->constructor->link('node/'.$nid,'[str]Read more[/str]') ;
            $output['btn-link']=isset($this->pathes[$nid]) ? $this->sawanna->constructor->link($this->pathes[$nid],'[str]Read more[/str]','btn') : $this->sawanna->constructor->link('node/'.$nid,'[str]Read more[/str]','btn') ;
        }
    }
    
    function get_child_nodes($sticky=false,$offset=0,$limit=10)
    {
        
        $nids=array();
        
        $perm_not_in='';
        
        if (!empty($this->exclude_perms))
        {
            foreach($this->exclude_perms as $perm)
            {
                $perm_not_in.="and n.permission <> '".$this->sawanna->db->escape_string($perm)."' ";
            }
        }

        if (!$sticky)
            $this->sawanna->db->query("select n.id, p.path from pre_nodes n left join pre_node_pathes p on n.id=p.nid where p.path like '$1/%' and n.sticky='0' and n.language='$2' and n.pub_tstamp < '$3' and n.enabled<>'0' ".$perm_not_in." order by n.weight asc, n.pub_tstamp desc, n.created desc limit $4 offset $5",$this->query,$this->sawanna->locale->current,time(),$limit,$offset);
        else 
            $this->sawanna->db->query("select n.id, p.path from pre_nodes n left join pre_node_pathes p on n.id=p.nid where p.path like '$1/%' and n.sticky<>'0' and n.language='$2' and n.pub_tstamp < '$3' and n.enabled<>'0' ".$perm_not_in." order by n.weight asc, n.pub_tstamp desc, n.created desc",$this->query,$this->sawanna->locale->current,time());
         
        while ($row=$this->sawanna->db->fetch_object())
        {
            $nids[]=$row->id;
            $this->thumbs[]='node-'.$row->id;
            
            $this->pathes[$row->id]=$row->path;
        }
        
        return $nids;
    }
    
    function get_child_nodes_count($sticky=false,$front=false)
    {
        $perm_not_in='';
        
        if (!empty($this->exclude_perms))
        {
            foreach($this->exclude_perms as $perm)
            {
                $perm_not_in.="and n.permission <> '".$this->sawanna->db->escape_string($perm)."' ";
            }
        }
        
        if (!$sticky)
            $this->sawanna->db->query("select count(*) as co from pre_nodes n left join pre_node_pathes p on n.id=p.nid where p.path like '$1/%' and n.sticky='0' and n.language='$2' and n.pub_tstamp < '$3' and n.enabled<>'0' ".$perm_not_in,$this->query,$this->sawanna->locale->current,time());
        else 
            $this->sawanna->db->query("select count(*) as co from pre_nodes n left join pre_node_pathes p on n.id=p.nid where p.path like '$1/%' and n.sticky<>'0' and n.language='$2' and n.pub_tstamp < '$3' and n.enabled<>'0' ".$perm_not_in,$this->query,$this->sawanna->locale->current,time());
    
        $row=$this->sawanna->db->fetch_object();
        if (!empty($row))
        {
            return $row->co;
        }
        
        return 0;
    }
}
