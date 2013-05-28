<?php 
defined('SAWANNA') or die();

require_once(ROOT_DIR.'/sawanna/data.inc.php');

class CConstructor
{
    var $locale;
    var $session;
    var $config;
    var $form;
    var $query_var;
    var $mobile_query_var;
    var $query;
    
    function CConstructor(&$locale,&$session,&$config,&$form,$query_var,$mobile_query_var,$query)
    {
        $this->locale=$locale;
        $this->session=$session;
        $this->config=$config;
        $this->form=$form;
        $this->query_var=$query_var;
        $this->mobile_query_var=$mobile_query_var;
        $this->query=$query;
    }
    
    function url($url,$forceGET=false,$noEXT=false,$external=false)
    {
        if (strpos($url,'http://')===0 || strpos($url,'https://')===0 || $url=='#') return $url;
        
        $lang_prefix='';
        if (count($this->locale->languages)>1 && ($this->config->accept_language || $this->locale->current != $this->config->language))
        {
            $lang_prefix=$this->locale->current.'/';
        }

		$dest=$lang_prefix.$url;
		
		$anc='';
		if (($p=strpos($dest,'#'))!==false)
		{
			$anc=substr($dest,$p);
			$dest=substr($dest,0,$p);
		}
		
		if (!defined('BASE_PATH') || $external) 
		{
			if (empty($dest) || $dest=='[home]') 
			{
				$url = SITE.$this->get_trans_sid();
			} else
			{
				$dest=encode_url($dest);
				
				if (!$noEXT && !$forceGET && $this->config->clean_url && $this->config->path_extension && $this->query!=$this->config->admin_area) $dest.='.html';
				
				if ($this->config->clean_url && !$forceGET)
				{
					$url = empty($url) || $url=='[home]' ? SITE.'/'.$this->locale->current.$this->get_trans_sid(false) : SITE.'/'.$dest.$this->get_trans_sid(false);
				} else
				{
					$url = empty($url) || $url=='[home]' ? SITE.'/index.php?'.$this->query_var.'='.$this->locale->current.$this->get_trans_sid(false) : SITE.'/index.php?'.$this->query_var.'='.$dest.$this->get_trans_sid(false);
				}
			}
		} else
		{
			if (empty($dest) || $dest=='[home]') 
			{
				$suffix=$this->get_trans_sid();
				$url = BASE_PATH.(!empty($suffix) ? $suffix : '/');
			} else
			{
				$dest=encode_url($dest);
				
				if (!$noEXT && !$forceGET && $this->config->clean_url && $this->config->path_extension && $this->query!=$this->config->admin_area) $dest.='.html';
				
				if ($this->config->clean_url && !$forceGET)
				{
					$url = empty($url) || $url=='[home]' ? BASE_PATH.'/'.$this->locale->current.$this->get_trans_sid(false) : BASE_PATH.'/'.$dest.$this->get_trans_sid(false);
				} else
				{
					$url = empty($url) || $url=='[home]' ? BASE_PATH.'/index.php?'.$this->query_var.'='.$this->locale->current.$this->get_trans_sid(false) : BASE_PATH.'/index.php?'.$this->query_var.'='.$dest.$this->get_trans_sid(false);
				}
			}
		}
		
		if (isset($_GET[$this->mobile_query_var]))
		{
			$url=$this->add_url_args($url,array(
											$this->mobile_query_var => ($_GET[$this->mobile_query_var] ? '1' : '0')
											));
		}
		
		return $url.$anc;
    }
    
    function get_trans_sid($first_arg=true)
    {
        if (!$this->config->trans_sid) return false;
        
        if (!isset($_COOKIE[$this->session->query_var])){
            $trans_sid=$this->session->query_var.'='.$this->session->id;
            return $first_arg ? '/?'.$trans_sid : '&'.$trans_sid;
        }
    }
    
    function add_url_args($url,$args)
    {
        if (empty($url) || empty($args) || !is_array($args)) return $url;
        
        $vars=array();
        foreach ($args as $var=>$val)
        {
            $vars[]=$var.'='.$val;
        }
        
        if ($url==SITE.'/index.php') $url=SITE;
        if ($url==SITE.'/index.php?') $url=SITE;
        if ($url==SITE.'/') $url=SITE;
        if ($url==SITE.'/?') $url=SITE;
        
        if ($url==SITE && $this->config->clean_url) $url.='/';
        
        return (strpos($url,'?')===false && !$this->config->clean_url) ? rtrim($url,'/').'/?'.implode('&',$vars) : $url.'&'.implode('&',$vars);
    }
    
    function remove_url_args($url,$args)
    {
		if (empty($url) || empty($args) || !is_array($args)) return $url;
		
		foreach ($args as $arg)
		{
			$url=preg_replace('/(?:[?]|[&])?'.addcslashes(preg_quote($arg),'/').'[^&]*/ui','',$url);
		}
		
		return $url;
	}
    
    function link($url,$text,$class='',$attributes=array(),$forceGET=false,$noEXT=false)
    {
        $attr_str='';
        if (!empty($attributes) && is_array($attributes))
        {
            foreach ($attributes as $attr_name=>$attr_value)
            {
                $attr_str.=' '.strip_tags($attr_name).'="'.strip_tags($attr_value).'"';
            }
        }
        
        $class=!empty($class) ? ' class="'.$class.'"' : '';
        return '<a href="'.$this->url($url,$forceGET,$noEXT).'"'.$class.$attr_str.'>'.$text.'</a>';
    }
    
    function redirect($location)
    {
        $dest=$this->url($location);
        header("Location: ".$dest);
        exit();
    }
    
    function form($id,$fields,$callback='',$redirect='',$url='',$tpl='',$caller='',$fill=true,$class='',$elements_only=false)
    {
        if (empty($id) || empty($fields) || !is_array($fields))
        {
            trigger_error('Could not render form. Not enough parameters',E_USER_WARNING);
            return;
        }
        
        $dest=!empty($url) ? $this->url($url) : $this->url($this->query);
        
        $form=array(
            'id'=>$id,
            'url' => $dest,
            'fields'=>$fields,
            'fill' => $fill
        );
        
        if (!empty($class)) $form['class']=$class;
        
        if (!empty($callback)) $form['callback']=$callback;
        if (!empty($redirect)) $form['redirect']=$redirect;
        if (!empty($tpl)) $form['tpl']=$tpl;
        if (!empty($caller)) $form['caller']=$caller;
        
        return $this->form->render($form,$elements_only);
    }
    
    function pager($start,$limit,$count,$query,$suffix='')
    {
        if ($count <= $limit) return ;
        
        if (!empty($query)) $query=$query.'/';
        
        $html='<div class="pager"><ul>';
        
        $begin=floor(($start)/10)*10+1;
        $end=$begin+10<=ceil($count/$limit) ? $begin+10 : ceil($count/$limit)+1;
    
        if ($start>0) 
        {
            $query_last=trim($query,'/').$suffix;
            $html.='<li title="[str]<< First[/str]">'.$this->link($query_last,'&laquo;','','',false,true).'</li>';
            
            $query_prev=($start-1)==0 ? trim($query,'/').$suffix : $query.($start-1).$suffix;
            $html.='<li title="[str]< Previous[/str]">'.$this->link($query_prev,'&lsaquo;','','',false,true).'</li>';
        }
            
        for ($i=$begin;$i<$end;$i++)
        {
            if ($i-1==$start)
                $html.='<li class="active current"><span>'.$i.'</span></li>';
            else 
            {
                $query_i=($i-1)==0 ? trim($query,'/').$suffix : $query.($i-1).$suffix;
                $html.='<li>'.$this->link($query_i,$i,'','',false,true).'</li>';
            }
        }
        
         if ($start+1<ceil($count/$limit)) 
         {
            $html.='<li title="[str]Next >[/str]">'.$this->link($query.($start+1).$suffix,'&rsaquo;','','',false,true).'</li>';
            $html.='<li title="[str]Last >>[/str]">'.$this->link($query.(ceil($count/$limit)-1).$suffix,'&raquo;','','',false,true).'</li>';
         }
        
        $html.='</ul></div>';
        
        return $html;
    }
    
    function ajax_pager($start,$limit,$count,$query,$callback)
    {
        if ($count <= $limit) return ;
        
        $html='<div class="pager ajax-pager"><ul>';
        
        $begin=floor(($start)/10)*10+1;
        $end=$begin+10<=ceil($count/$limit) ? $begin+10 : ceil($count/$limit)+1;
    
        if ($start>0) 
        {
            $html.='<li title="[str]<< First[/str]"><a href="javascript:'.htmlspecialchars($callback).'(\''.$query.'\',0)">&laquo;</a></li>';
            $html.='<li title="[str]< Previous[/str]"><a href="javascript:'.htmlspecialchars($callback).'(\''.$query.'\','.($start-1).')">&lsaquo;</a></li>';
        }
            
        for ($i=$begin;$i<$end;$i++)
        {
            if ($i-1==$start)
                $html.='<li class="active current"><span>'.$i.'</span></li>';
            else 
                $html.='<li><a href="javascript:'.htmlspecialchars($callback).'(\''.$query.'\','.($i-1).')">'.$i.'</a></li>';
        }
        
         if ($start+1<ceil($count/$limit)) 
         {
            $html.='<li title="[str]Next >[/str]"><a href="javascript:'.htmlspecialchars($callback).'(\''.$query.'\','.($start+1).')">&rsaquo;</a></li>';
            $html.='<li title="[str]Last >>[/str]"><a href="javascript:'.htmlspecialchars($callback).'(\''.$query.'\','.(ceil($count/$limit)-1).')">&raquo;</a></li>';
         }
        
        $html.='</ul></div>';
        
        return $html;
    }
    
    function resize_image($img_src_path,$img_dst_path,$dst_width,$dst_height,$crop=false,$force_dst_type=false)
    {
            if (!function_exists('gd_info')) return false;
        
            $src=false;
            $size=@getimagesize($img_src_path);
            if (!$size) return false;
            
            switch ($size[2])
            {
                case IMAGETYPE_JPEG :
                    $src=@imagecreatefromjpeg($img_src_path);
                    $dst_type='jpeg';
                    break;
                case IMAGETYPE_PNG :
                    $src=@imagecreatefrompng($img_src_path);
                    $dst_type='png';
                    break;
                case IMAGETYPE_GIF :
                    $src=@imagecreatefromgif($img_src_path);
                    $dst_type='gif';
                    break;
            }
            
            if ($src)
            {
                $src_width=$size[0];
                $src_height=$size[1];
                $src_x=0;
                $src_y=0;
                            
                $dst_x=0;
                $dst_y=0;
                
                $dst=imagecreatetruecolor($dst_width,$dst_height);
                $bg_color=@imagecolorallocate($dst, 255, 255, 255);
                @imagefill($dst, 0, 0, $bg_color);
            
                if ($src_width<$dst_width && $src_height<$dst_height)
                {
                    $dst_x=floor(($dst_width-$src_width)/2);
                    $dst_y=floor(($dst_height-$src_height)/2);
                    $dst_width=$src_width;
                    $dst_height=$src_height;
                }
                elseif ($crop)
                {
                    switch (true)
                    {
                        case ($dst_width/$dst_height) > ($src_width/$src_height) :
                            $src_height=$src_width*($dst_height/$dst_width);
                            $src_y=($size[1]-$src_height)/2;
                            break;
                        case ($dst_width/$dst_height) < ($src_width/$src_height) :
                            $src_width=$src_height*($dst_width/$dst_height);
                            $src_x=($size[0]-$src_width)/2;
                            break;
                    }
                } else 
                {
                    switch (true)
                    {
                        case ($dst_width/$dst_height) < ($src_width/$src_height) :
                            $_dst_height=$dst_width*($src_height/$src_width);
                            $dst_y=($dst_height-$_dst_height)/2;
                            $dst_height=$_dst_height;
                            unset($_dst_height);
                            break;
                        case ($dst_width/$dst_height) > ($src_width/$src_height) :
                            $_dst_width=$dst_height*($src_width/$src_height);
                            $dst_x=($dst_width-$_dst_width)/2;
                            $dst_width=$_dst_width;
                            unset($_dst_width);
                            break;
                    }
                }
                
                if (!@imagecopyresampled($dst,$src,$dst_x,$dst_y,$src_x,$src_y,$dst_width,$dst_height,$src_width,$src_height)) return false;
                
                if (!empty($force_dst_type)) $dst_type=$force_dst_type;
                
                switch ($dst_type)
                {
                    case 'jpeg' :
                        if (!@imagejpeg($dst,$img_dst_path)) return false;
                        break;
                    case 'png' :
                        if (!@imagepng($dst,$img_dst_path)) return false;
                        break;
                    case 'gif' :
                        if (!function_exists('imagegif') || !@imagegif($dst,$img_dst_path)) return false;
                         break;
                     default:
                         if (!@imagejpeg($dst,$img_dst_path)) return false;
                         break;
                }
                        
                imagedestroy($src);
                imagedestroy($dst);
                
                return true;
            } else return false;
    }
    
    function gradient_image($image_dst_path,$image_width, $image_height, $color1, $color2, $vertical=false)
    {
        if (!function_exists('gd_info')) return false;
        
        $color1=hex2RGB($color1);
        $color2=hex2RGB($color2);
        
        if (!$color1 || !$color2) return false;
        
        list($c1_r, $c1_g, $c1_b) = $color1;
        list($c2_r, $c2_g, $c2_b) = $color2;
        
        $image  = imagecreatetruecolor($image_width, $image_height);

        if ($vertical)
        {
            for($i=0; $i<$image_height; $i++)
            {
                $color_r = floor($i * ($c2_r-$c1_r) / $image_height)+$c1_r;
                $color_g = floor($i * ($c2_g-$c1_g) / $image_height)+$c1_g;
                $color_b = floor($i * ($c2_b-$c1_b) / $image_height)+$c1_b;

                $color = imagecolorallocate($image, $color_r, $color_g, $color_b);
                imageline($image, 0, $i, $image_width, $i, $color);
            }
        } else
        {
            for($i=0; $i<$image_width; $i++)
            {
                $color_r = floor($i * ($c2_r-$c1_r) / $image_width)+$c1_r;
                $color_g = floor($i * ($c2_g-$c1_g) / $image_width)+$c1_g;
                $color_b = floor($i * ($c2_b-$c1_b) / $image_width)+$c1_b;

                $color = imagecolorallocate($image, $color_r, $color_g, $color_b);
                imageline($image, $i, 0, $i, $image_height, $color);
            }
        }
        
        imagepng($image,$image_dst_path);
        imagedestroy($image);
        
        return true;
    }
    
    function create_image($img_src_path,$img_dst_path,$dst_width,$dst_height,$bg_color,$dst_type='png')
    {
        if (!function_exists('gd_info')) return false;
        
        $src=false;
        
        if ($img_src_path)
        {
            $size=@getimagesize($img_src_path);
            if (!$size) return false;
            
            switch ($size[2])
            {
                case IMAGETYPE_JPEG :
                    $src=@imagecreatefromjpeg($img_src_path);
                    break;
                case IMAGETYPE_PNG :
                    $src=@imagecreatefrompng($img_src_path);
                    break;
                case IMAGETYPE_GIF :
                    $src=@imagecreatefromgif($img_src_path);
                    break;
            }
        }
        
        $bg_color=hex2RGB($bg_color);
        
        if (!$bg_color) return false;
        
        list($cr, $cg, $cb) = $bg_color;
        
        $dst=imagecreatetruecolor($dst_width,$dst_height);
        $bg_color=@imagecolorallocate($dst, $cr, $cg, $cb);
        @imagefill($dst, 0, 0, $bg_color);
            
        if ($src)
        {
            $src_width=$size[0];
            $src_height=$size[1];
            
            if (!@imagecopyresampled($dst,$src,0,0,0,0,$dst_width,$dst_height,$src_width,$src_height)) return false;
            
            imagedestroy($src);
        }
            
        switch ($dst_type)
        {
            case 'jpeg' :
                if (!@imagejpeg($dst,$img_dst_path)) return false;
                break;
            case 'png' :
                if (!@imagepng($dst,$img_dst_path)) return false;
                break;
            case 'gif' :
                if (!function_exists('imagegif') || !@imagegif($dst,$img_dst_path)) return false;
                 break;
             default:
                 if (!@imagejpeg($dst,$img_dst_path)) return false;
                 break;
        }
        
        imagedestroy($dst);
        
        return true;
    }
    
    function sendMail($to,$from_mail,$from_name,$subject,$message,$type='plain',$file_name="") 
    {
          $bound="SAWANNA";
          
          $header="From: \"$from_name\" <$from_mail>\r\n";
          $header.="Mime-Version: 1.0\r\n";
          
          $body='';
          
          if (!empty ($file_name)) 
          {
              $header.="Content-Type: multipart/mixed; boundary=$bound";
              $body.="\n\n--$bound\n";
              $body.="Content-type: text/html; charset=utf-8\n\n";
          }
          
          switch ($type)
          {
              case 'plain':
                  $header.="Content-type: text/plain; charset=utf-8\r\n";
                  break;
              case 'html':
                  if (empty($file_name))
                    $header.="Content-type: text/html; charset=utf-8\n\n";
                  break;
              default:
                  $header.="Content-type: text/plain; charset=utf-8\r\n";
                  break;
          }

          $body.="$message\n";
          
          if (!empty ($file_name)) 
          {   
              $file=fopen($file_name,"rb");
              $body.="--$bound\n";
              $body.="Content-Type: application/octet-stream;";
              $body.="name=".basename($file_name)."\n";
              $body.="Content-Transfer-Encoding:base64\n";
              $body.="Content-Disposition:attachment\n\n";
              $body.=base64_encode(fread($file,filesize($file_name)))."\n";
          }
          
          if(mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $body, $header)) 
            return true;
          else 
            return false;
    }
}
