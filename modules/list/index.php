<?php
defined('SAWANNA') or die();

class CNodesList
{
    var $sawanna;
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
    var $show_titles;
    var $show_keywords;
    var $show_descriptions;
    var $menu;
    var $query;
    var $exclude_perms;
    var $thumbs;
    var $check_arg;
    var $html_widget;
    var $html_widget_devider;
    
    function CNodesList(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->query=$this->sawanna->query;
        $this->thumbs_dir='thumbs';
        
        $this->thumbs=array();
        
        $thumb_width=$this->sawanna->get_option('content_thumbnail_width');
        $thumb_height=$this->sawanna->get_option('content_thumbnail_height');
        
        $this->thumb_width=$thumb_width ? $thumb_width : 120;
        $this->thumb_height=$thumb_height ? $thumb_height : 90;
        
        $limit=$this->sawanna->get_option('nodes_list_limit');
        $this->limit=$limit && is_numeric($limit) && $limit>0 ? ceil($limit) : 10;
        
        $this->html_widget=0;
        $this->html_widget_devider=1;
        if ($this->sawanna->module->module_exists('code')) {
			$html_widget=$this->sawanna->get_option('list_html_widget');
			if ($html_widget) $this->html_widget = (int) $html_widget;
			
			$html_widget_devider=$this->sawanna->get_option('list_html_widget_devider');
			if ($html_widget_devider && $html_widget_devider>1) $this->html_widget_devider = (int) $html_widget_devider;
		}
        
        $this->start=0;
        $this->count=0;
        $this->front=false;
        
        $titles=$this->sawanna->get_option('nodes_list_add_titles');
        $this->show_titles=$titles ? true : false;
        
        $keywords=$this->sawanna->get_option('nodes_list_add_keywords');
        $this->show_keywords=$keywords ? true : false;
        
        $descs=$this->sawanna->get_option('nodes_list_add_descriptions');
        $this->show_descriptions=$descs ? true : false;
        
        $check_arg=$this->sawanna->get_option('nodes_list_check_arg');
        $this->check_arg=$check_arg ? true : false;
        
        $this->sawanna->cache->set_clear_on_change('content','nodesList');
    }
    
    function init()
    {
        $this->front=($this->sawanna->query == $this->sawanna->config->front_page_path) ? true : false;
        
        $this->start=is_numeric($this->sawanna->arg) && $this->sawanna->arg>0 ? floor($this->sawanna->arg) : 0;
        
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
        
        $this->count=$this->get_child_nodes_count(false,$this->front);
    }

    function nodes_list($path='used by caching')
    {
        if ($this->sawanna->query=='node') return;
        
        if (empty($this->count)) $this->init();
        
        $output_items=array(
            'widget_items'=>array(),
            'widget_tpls'=>array(),
            'widget_prefix'=>'<a name="list"></a>',
            'widget_suffix'=>$this->pager()
        );

        $snids=$this->get_child_nodes(true,$this->front);
        $onids=$this->get_child_nodes(false,$this->front,$this->start*$this->limit,$this->limit);
        
        $this->sawanna->file->preload($this->thumbs,true);
        
        $html_widget='';
        $html_widget_title='';
        if ($this->sawanna->module->module_exists('code') && $this->html_widget>0) {
			$this->sawanna->db->query("select * from pre_cwidgets where id='$1'",$this->html_widget);
			$code=$this->sawanna->db->fetch_object();
			if ($code && !empty($code->content)) {
				$html_widget=$code->content;
				
				
				if (!empty($code->meta))
				{
					$d=new CData();
					$code->meta=$d->do_unserialize($code->meta);
					if (isset($code->meta['name'][$this->sawanna->locale->current])) $html_widget_title=htmlspecialchars($code->meta['name'][$this->sawanna->locale->current]);
				}
			}
		}
        
        $co=0;
        if (!empty($snids) && is_array($snids))
        {
            foreach ($snids as $nid)
            {
                $output=array();
                
                $this->get_node_teaser($nid,$output);
                
                if (empty($output)) continue;
                
                if ($co>0 && $co%$this->html_widget_devider===0 && !empty($html_widget)) {
					$output_items['widget_items'][]=array(
												'title'=>$html_widget_title,
												'content'=>$html_widget
												);
					$output_items['widget_tpls'][]='htmlWidget';
				}
				
				$co++;
				
                $output_items['widget_items'][]=$output;
                $output_items['widget_tpls'][]='nodesListSticky';
            }
        }
        
        if (!empty($onids) && is_array($onids))
        {
            foreach ($onids as $nid)
            {
                $output=array();
                
                $this->get_node_teaser($nid,$output);
                
                if (empty($output)) continue;
                
                if ($co>0 && $co%$this->html_widget_devider===0 && !empty($html_widget)) {
					$output_items['widget_items'][]=array(
												'title'=>$html_widget_title,
												'content'=>$html_widget
												);
					$output_items['widget_tpls'][]='htmlWidget';
				}
				
				$co++;
                
                $output_items['widget_items'][]=$output;
                $output_items['widget_tpls'][]='nodesList';
            }
        }
       
        return $output_items;
    }
    
    function get_node_teaser($nid,&$output)
    {
        $node=$this->sawanna->node->get($nid);
        if (!empty($node) && is_object($node))
        {
            if (empty($node->meta['teaser'])) return;
            
            if (!$this->check_arg || !empty($this->sawanna->arg))
            {
				if ($this->show_titles && !empty($node->meta['title'])) $this->sawanna->tpl->add_title($node->meta['title']);
				if ($this->show_keywords && !empty($node->meta['keywords'])) $this->sawanna->tpl->add_keywords($node->meta['keywords']);
				if ($this->show_descriptions && !empty($node->meta['description'])) $this->sawanna->tpl->add_description($node->meta['description']);
			}
				
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
            
            $node_url=!empty($this->pathes[$nid]) ? $this->pathes[$nid] : 'node/'.$nid;
           
            $thumbnail='';
            $thumb=$this->sawanna->file->get('node-'.$nid);
            if ($thumb && $thumb->path==$this->thumbs_dir && file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->thumbs_dir).'/'.quotemeta($thumb->name)))
            {
                $size=getimagesize(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->thumbs_dir).'/'.quotemeta($thumb->name));
                if ($size && $size[0]==$this->thumb_width && $size[1]==$this->thumb_height)
                {
                    $thumbnail='<a href="'.$this->sawanna->constructor->url($node_url).'"><img src="'.SITE.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->thumbs_dir).'/'.quotemeta($thumb->name).'" width="'.$this->thumb_width.'" height="'.$this->thumb_height.'" border="0" /></a>';
                }
            }
            
            if (!isset($this->pathes[$nid]))
            {
                $this->sawanna->db->query("select path from pre_node_pathes where nid='$1'",$nid);
                $row=$this->sawanna->db->fetch_object();
                if (!empty($row)) $this->pathes[$nid]=$row->path;
            }
            
            $output['date']=date($this->sawanna->config->date_format,$node->pub_tstamp);
            $output['title']=!empty($node->meta['title']) ? '<a href="'.$this->sawanna->constructor->url($node_url).'">'.strip_tags($node->meta['title']).'</a>' : '';
            $output['content']=nl2br(strip_tags($node->meta['teaser'],'<strong><b><a>'));
            $output['author']=$node_author;
            $output['keywords']=!empty($node->meta['keywords']) ? $node->meta['keywords'] : '';
            $output['thumbnail']=$thumbnail;
            $output['link']=$this->sawanna->constructor->link($node_url,'[str]Read more[/str]','btn');
            $output['url']=$this->sawanna->constructor->url($node_url);
            
            $rate='';
            
            if ($this->sawanna->module->module_exists('top')) {
				$rate.='<div class="node-list-rate-container">';
				$stars=round($GLOBALS['top']->get_node_rate($nid));
				for ($i=1;$i<=5;$i++) {
					if ($i<=$stars) {
						$rate.='<div class="node-list-rate-yellow"></div>';
					} else {
						$rate.='<div class="node-list-rate-grey"></div>';
					}
				}
				$rate.='</div>';
			}
            
            $output['rate']=$rate;
        }
    }
    
    function get_child_nodes($sticky=false,$front=false,$offset=0,$limit=10)
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

        if (!$front)
        {
            if (!$sticky)
                $this->sawanna->db->query("select n.id, p.path from pre_nodes n left join pre_node_pathes p on n.id=p.nid where p.path like '$1/%' and n.sticky='0' and n.language='$2' and n.pub_tstamp < '$3' and n.enabled<>'0' ".$perm_not_in." order by n.weight asc, n.pub_tstamp desc, n.created desc limit $4 offset $5",$this->query,$this->sawanna->locale->current,time(),$limit,$offset);
            else 
                $this->sawanna->db->query("select n.id, p.path from pre_nodes n left join pre_node_pathes p on n.id=p.nid where p.path like '$1/%' and n.sticky<>'0' and n.language='$2' and n.pub_tstamp < '$3' and n.enabled<>'0' ".$perm_not_in." order by n.weight asc, n.pub_tstamp desc, n.created desc",$this->query,$this->sawanna->locale->current,time());
        } else
         {      
              if (!$sticky)
                $this->sawanna->db->query("select n.id, p.path from pre_nodes n left join pre_node_pathes p on n.id=p.nid where n.front<>'0' and n.sticky='0' and n.language='$1' and n.pub_tstamp < '$2' and n.enabled<>'0' ".$perm_not_in." order by n.weight asc, n.pub_tstamp desc, n.created desc limit $3 offset $4",$this->sawanna->locale->current,time(),$limit,$offset);
            else 
                $this->sawanna->db->query("select n.id, p.path from pre_nodes n left join pre_node_pathes p on n.id=p.nid where n.front<>'0' and n.sticky<>'0' and n.language='$1' and n.pub_tstamp < '$2' and n.enabled<>'0' ".$perm_not_in." order by n.weight asc, n.pub_tstamp desc, n.created desc",$this->sawanna->locale->current,time());
         }
             
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
        
        if (!$front)
        {
            if (!$sticky)
                $this->sawanna->db->query("select count(*) as co from pre_nodes n left join pre_node_pathes p on n.id=p.nid where p.path like '$1/%' and n.sticky='0' and n.language='$2' and n.pub_tstamp < '$3' and n.enabled<>'0' ".$perm_not_in,$this->query,$this->sawanna->locale->current,time());
            else 
                $this->sawanna->db->query("select count(*) as co from pre_nodes n left join pre_node_pathes p on n.id=p.nid where p.path like '$1/%' and n.sticky<>'0' and n.language='$2' and n.pub_tstamp < '$3' and n.enabled<>'0' ".$perm_not_in,$this->query,$this->sawanna->locale->current,time());
        } else
         {      
              if (!$sticky)
                $this->sawanna->db->query("select count(*) as co from pre_nodes n left join pre_node_pathes p on n.id=p.nid where n.front<>'0' and n.sticky='0' and n.language='$1' and n.pub_tstamp < '$2' and n.enabled<>'0' ".$perm_not_in,$this->sawanna->locale->current,time());
            else 
                $this->sawanna->db->query("select count(*) as co from pre_nodes n left join pre_node_pathes p on n.id=p.nid where n.front<>'0' and n.sticky<>'0' and n.language='$1' and n.pub_tstamp < '$2' and n.enabled<>'0' ".$perm_not_in,$this->sawanna->locale->current,time());
         }
             
        $row=$this->sawanna->db->fetch_object();
        if (!empty($row))
        {
            return $row->co;
        }
        
        return 0;
    }
    
    function pager()
    {
        if (empty($this->count)) $this->init();
        
        return $this->sawanna->constructor->pager($this->start,$this->limit,$this->count,$this->sawanna->query,'#list');
    }
    
    function ajaxer()
    {
		$this->query=$this->sawanna->query;
	}
}
