<?php
defined('SAWANNA') or die();

class CGallery
{
    var $sawanna;
    var $module_id;
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
    var $interval;
    var $html_widget;
    var $html_layer;
    
    function CGallery(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('gallery');
        
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
        
        $this->img_width=$image_width ? $image_width : 800;
        $this->img_height=$image_height ? $image_height : 600;
        $this->img_crop=$image_crop ? $image_crop : false;
        
        $interval=$this->sawanna->get_option('gallery_widget_interval');
        $this->interval=$interval ? $interval*1000 : 5000;
        
        $this->html_widget=0;
        if ($this->sawanna->module->module_exists('code')) {
			$html_widget=$this->sawanna->get_option('gallery_html_widget');
			if ($html_widget) $this->html_widget = (int) $html_widget;
		}
		
		$this->html_layer='';
    }
    
    function ready()
    {
		if ($this->sawanna->module->module_exists('code') && $this->html_widget>0) {
			$this->sawanna->db->query("select * from pre_cwidgets where id='$1'",$this->html_widget);
			$code=$this->sawanna->db->fetch_object();
			if ($code && !empty($code->content)) {
				$this->html_layer=$code->content;
			}
		}
		
		$this->sawanna->tpl->append_body('<div id="gallery-loader"></div><div id="gallery-preloader"></div><div id="gallery-zoomer"><div id="gallery-zoom-links"><div id="gallery-zoom-dlink"></div><div id="gallery-zoom-plink"></div></div><div id="gallery-zoom-title"></div><div id="gallery-zoom-prev"></div><div id="gallery-zoom-next"></div><div id="gallery-html-layer">'.$this->html_layer.'</div><img id="gallery-zoom-image" /><div id="gallery-zoom-description"></div></div>');
			
        if ($this->sawanna->query != $this->sawanna->config->admin_area) $this->load_script();

        if (isset($GLOBALS['content']) && is_object($GLOBALS['content']) && isset($GLOBALS['content']->content_alters) && is_array($GLOBALS['content']->content_alters))
        {
            $GLOBALS['content']->content_alters[]=array('class'=>'CGallery','handler'=>'alter_content');
        }
    }
    
    function load_script()
    {
        $this->sawanna->tpl->add_script(quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/js/gallery');
            
        $event=array(
            'url' => 'gallery-ajax-load',
            'caller' => 'CGallery',
            'job' => 'ajax_load',
            'constant' => 1
        );
        
        $this->sawanna->event->register($event);
        
        // deprecated
        //$this->sawanna->tpl->add_header('<script language="JavaScript">site="'.$this->sawanna->constructor->url('[home]').'";</script>');
        
        $this->sawanna->tpl->add_header('<script language="JavaScript">gallery_dlink_str=\'[str]Download[/str]\';gallery_plink_str=\'[str]Print[/str]\';</script>');
        
        $this->script_loaded=true;
    }
    
    function gallery_name_exists($id)
    {
        $this->sawanna->db->query("select * from pre_galleries where name='$1'",$id);
        
        $row=$this->sawanna->db->fetch_object();
        
        if (!empty($row)) return true;
        return false;
    }
    
    function fix_widget($gal)
    {
        $wid=$this->sawanna->module->get_widget_id($gal->name);
        if(!$wid) return false;
        
        $tpl='';
        if (!empty($gal->meta['tpl']))
        {
            if ($gal->meta['tpl']=='widget') $tpl='widget';
            elseif ($gal->meta['tpl']=='node') $tpl='node';
        }
        
        if (!empty($tpl)) $this->sawanna->module->widgets[$wid]->tpl=$tpl;
        return true;
    }

    function gallery($name,$start=0)
    {
        if (empty($name)) return ;
 
        $html='';
        $d=new CData();
        $gtitle='';
        
        $this->sawanna->db->query("select * from pre_galleries where name='$1'",$name);
        $gal=$this->sawanna->db->fetch_object();
        if (empty($gal)) return;
        
        if (!empty($gal->meta)) $gal->meta=$d->do_unserialize($gal->meta);
        if (!empty($gal->meta['name'][$this->sawanna->locale->current])) $gtitle=strip_tags($gal->meta['name'][$this->sawanna->locale->current]);
            
        $tpl='';
        if (!empty($gal->meta['tpl']))
        {
            if ($gal->meta['tpl']=='widget') $tpl='widget';
            elseif ($gal->meta['tpl']=='node') $tpl='node';
        }
        
        $count=0;
        $this->sawanna->db->query("select count(*) as co from pre_gallery_images where gid='$1'",$gal->id);
        $row=$this->sawanna->db->fetch_object();
        if (!empty($row)) $count=$row->co;
        
        $limit=0;
        if (!empty($gal->meta['limit']) && is_numeric($gal->meta['limit']) && $gal->meta['limit']>0) $limit=ceil($gal->meta['limit']);
        
        //$this->sawanna->db->query("select * from pre_gallery_images where gid='$1' order by weight asc, id desc limit $2 offset $3",$gal->id,$limit,$start*$limit);
        $this->sawanna->db->query("select g.id,g.original,g.thumb,g.image,g.meta,f.id as file_id from pre_gallery_images g left join pre_files f on g.original=f.name where g.gid='$1' order by g.weight asc, g.id desc limit $2 offset $3",$gal->id,$limit,$start*$limit);
        
        while ($img=$this->sawanna->db->fetch_object())
        {
            if (!empty($img->meta)) $img->meta=$d->do_unserialize($img->meta);

            $ititle='';
            $idesc='';
            
            if (!empty($img->meta['name'][$this->sawanna->locale->current])) $ititle=strip_tags($img->meta['name'][$this->sawanna->locale->current]);
            if (!empty($img->meta['description'][$this->sawanna->locale->current])) $idesc=strip_tags($img->meta['description'][$this->sawanna->locale->current]);
            
            $thumb=SITE.'/misc/document.png';
            if (file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.quotemeta($this->thumbs_dir).'/'.quotemeta($img->thumb)))
                $thumb=SITE.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.quotemeta($this->thumbs_dir).'/'.quotemeta($img->thumb);
                
            $rel='';
            if (file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.quotemeta($this->img_dir).'/'.quotemeta($img->image)))
                $rel=SITE.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.quotemeta($this->img_dir).'/'.quotemeta($img->image);
            
			$href='';
            if (!empty($img->file_id) && file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.quotemeta($this->original_dir).'/'.quotemeta($img->original)))
                //$href=SITE.'/'.quotemeta($this->sawanna->file->root_dir).'/'.quotemeta($this->root_dir).'/'.quotemeta($this->original_dir).'/'.quotemeta($img->original);
				$href=$this->sawanna->constructor->url('file/'.$img->file_id,false,true);
			
            $html.='<div class="gallery-item">';
            $html.='<a href="'.$href.'" class="gallery-thumb-link noajaxer"><img id="gallery-thumb-'.$img->id.'" class="gallery-thumb" rel="'.$rel.'" src="'.$thumb.'" width="'.$this->thumb_width.'" height="'.$this->thumb_height.'" alt="'.$ititle.'" title="'.$idesc.'" /></a>';
            $html.='</div>';
        }
        
        $script='';
        
        if ($tpl!='widget') $html.=$this->sawanna->constructor->ajax_pager($start,$limit,$count,$gal->name,'gallery_load');
        elseif ($count>$limit && !isset($_POST['gallery']))
        {
            $script='<script language="JavaScript">var widget_'.$gal->name.'_offset=0; var widget_'.$gal->name.'_count='.ceil($count/$limit).'; function gallery_update_'.$gal->name.'_offset() { if (widget_'.$gal->name.'_offset<widget_'.$gal->name.'_count-1) { widget_'.$gal->name.'_offset++; } else { widget_'.$gal->name.'_offset=0; } }; window.setInterval("gallery_update_'.$gal->name.'_offset();gallery_load(\''.$gal->name.'\',widget_'.$gal->name.'_offset)",'.$this->interval.');</script>';    
        }
        
        if (!empty($html))
        {
            if (!empty($tpl))
            {
                $this->fix_widget($gal);
                return array('content'=>'<div class="gallery" id="gallery-'.$gal->name.'">'.$html.'</div>'.$script,'title'=>$gtitle);
            } else return '<div class="gallery" id="gallery-'.$gal->name.'">'.$html.'</div>'.$script;
        }
    }
    
    function ajax_load()
    {
        $start=0;
       
        if (empty($_POST['gallery']) || !$this->gallery_name_exists($_POST['gallery'])) return ;
        
        if (isset($_POST['offset']) && is_numeric($_POST['offset']) && $_POST['offset']>0) $start=ceil($_POST['offset']);
        
        $result=$this->gallery($_POST['gallery'],$start);
        
        if (is_array($result)) echo $result['content'];
        else echo $result;
    }
    
    function alter_content(&$content)
    {
        if (empty($content)) return ;
        
        while (preg_match('/\[gallery\](.*?)\[\/gallery\]/s',$content,$matches))
        {
            $gallery=$this->gallery($matches[1]);
            if (is_array($gallery)) $gallery=$gallery['content'];
            
            $content=str_replace($matches[0],'<!--Gallery-->'.$gallery.'<div class="clear"></div><!--/Gallery-->',$content);
        }
        
        $parse=$this->sawanna->get_option('gallery_parse_node');
        if ($this->sawanna->config->img_process && $parse)
        {
            $content=preg_replace('/<img([^>]+)src[\x20]*[=][\x20]*[\x22]('.addcslashes(SITE.'/','/').'|\/)?image\/([^\x22\/]+)(\/|\/small|\/normal)?([\x22][^>]*)>/si',
                                '<span class="node-image-zoomer" onclick="gallery_zoom_next(this)" rel="'.SITE.'/image/$3/big"></span><a href="'.SITE.'/image/$3/big" class="gallery-thumb-link noajaxer"><img$1src="'.SITE.'/image/$3$4" rel="'.SITE.'/image/$3/big" class="gallery-thumb node-image$5></a>',
                                $content);
            $content=preg_replace('/<img([^>]+)src[\x20]*[=][\x20]*[\x22]('.addcslashes(SITE.'/','/').'|\/)?index\.php\?'.quotemeta($this->sawanna->query_var).'=image\/([^\x22\/]+)(\/|\/small|\/normal)?([\x22][^>]*)>/si',
                                '<span class="node-image-zoomer" onclick="gallery_zoom_next(this)" rel="'.SITE.'/index.php?'.$this->sawanna->query_var.'=image/$3/big"></span><a href="'.SITE.'/image/$3/big" class="gallery-thumb-link noajaxer"><img$1src="'.SITE.'/index.php?'.$this->sawanna->query_var.'=image/$3$4" rel="'.SITE.'/index.php?'.$this->sawanna->query_var.'=image/$3/big" class="gallery-thumb node-image$5></a>',
                                $content);
        }
    }
    
    function ajaxer(&$scripts,&$outputs)
    {
		$scripts.='window.setTimeout("gallery_init();",100);'."\r\n";
	}
}
