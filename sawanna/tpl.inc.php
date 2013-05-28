<?php
defined('SAWANNA') or die();

class CTemplate
{
    var $html;
    var $params;
    var $config;
    var $cache;
    var $tpl;
    var $css;
    var $components;
    var $duty;
    var $root_dir;
    var $default_theme;
    var $query_var;
    var $default_title;
    var $is_mobile;
    
    function CTemplate(&$config,&$cache,$query_var,$is_mobile)
    {
        $this->config=$config;
        $this->cache=$cache;
        $this->components=array();
        
        $this->html='';
        $this->params=array();
        $this->params['title']='';
        $this->params['keywords']='';
        $this->params['description']='';
        $this->params['head']='';
        $this->params['duty']='';
        $this->params['scripts']=array('js/jquery');
        $this->params['header']=array('<script language="JavaScript">site="'.SITE.'";base="'.(defined('BASE_PATH') ? BASE_PATH : '').'";</script>');
        $this->params['css']=array();
        $this->params['link']=array();
        $this->params['sitename']='';
        $this->params['logo']='';
        $this->params['slogan']='';
        
        $this->params['location']='';
        $this->params['language']=$config->language;
        $this->params['argument']='';
        
        $this->params['language_switcher']='';
        
        $this->params['prepend_body']='<!--prepend-body-->';
        $this->params['append_body']='<!--append-body-->';
        
        $this->root_dir='themes';
        $this->default_theme='default';
        
        $this->query_var=$query_var;
        $this->default_title=!empty($config->default_meta_title) ? $this->config->default_meta_title : $config->site_name;
        
        $this->is_mobile=$is_mobile;
    }
    
    function init($location='',$language='',$argument='')
    { 
        $this->params['location']=$location;
        $this->params['language']=$language;
        $this->params['argument']=$argument;
        $this->params['is_mobile']=$this->is_mobile;
        
        if (preg_match('/^'.preg_quote($this->config->admin_area).'(\/.+)?$/',$location))  return true;
        
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($this->config->theme).'/tpl.php'))
        {
            trigger_error('Invalid theme "'.htmlspecialchars($this->config->theme).'". File tpl.php not found',E_USER_ERROR);
            return false;
        }

        include_once(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($this->config->theme).'/tpl.php');

        if (file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($this->config->theme).'/settings.php'))
        {
            include_once(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($this->config->theme).'/settings.php');
        }
        
        if (!function_exists('tpl'))
        {
            trigger_error('Invalid theme "'.htmlspecialchars($this->config->theme).'". Template is not assigned',E_USER_ERROR);
            return false;
        }
        
        if (!function_exists('tpl_components'))
        {
            trigger_error('Invalid theme "'.htmlspecialchars($this->config->theme).'". Components are not defined',E_USER_ERROR);
            return false;
        }
        
        if (!function_exists('duty'))
        {
            trigger_error('Invalid theme "'.htmlspecialchars($this->config->theme).'". Duty component is not assigned',E_USER_ERROR);
            return false;
        }
        
        if (!function_exists('css'))
        {
            trigger_error('Invalid theme "'.htmlspecialchars($this->config->theme).'". CSS file is not assigned',E_USER_ERROR);
            return false;
        }
        
        $this->tpl=tpl($location,$language,$this->is_mobile);
        
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($this->config->theme).'/'.quotemeta($this->tpl).'.tpl'))
        {
            trigger_error('Template file not found',E_USER_ERROR);
            return false;
        }

        $components=tpl_components($this->tpl);
        if (empty($components) || !is_array($components))
        {
             trigger_error('Template has no components',E_USER_ERROR);
        } else 
        {
            $this->components=$components;
        }
        
        if (empty($this->components) || !is_array($this->components))
        {
            trigger_error('Template has no components',E_USER_ERROR);
        }

        $this->duty=duty($location,$language,$this->is_mobile);
        
        if (empty($this->duty) || !in_array($this->duty,$this->components))
        {
            trigger_error('Template has no duty component',E_USER_ERROR);
        }
        
        $this->css=css($location,$language,$this->is_mobile);

        return true;
    }
    
    function getComponents()
    {
        $components=array();
        
        if (file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($this->config->theme).'/tpl.php'))
        {
             include_once(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($this->config->theme).'/tpl.php');
             if (function_exists('components')) $_components=components();
             if (!empty($_components) && is_array($_components)) $components=$_components;
        }
        
        return $components;
    }
    
    function getElements()
    {
        $elements=array();
        
        $html=file_get_contents(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($this->config->theme).'/'.quotemeta($this->tpl).'.tpl');
        
        if (preg_match_all('/\[element\](.*?)\[\/element\]/',$html,$matches))
        {
            $elements=$matches[1];
        }
        
        return $elements;
    }

    function get_installed_themes()
    {
        $themes=array();
        
        $dir=ROOT_DIR.'/'.quotemeta($this->root_dir);
        $tdir=opendir($dir);
        while(($file=readdir($tdir)) !== false)
        {  
            if (!is_dir($dir.'/'.quotemeta($file)) || $file=='.' || $file=='..') continue;
      
            if (!file_exists($dir.'/'.quotemeta($file).'/tpl.php')) continue;
         
            $info=array();
            $tpl=file_get_contents($dir.'/'.quotemeta($file).'/tpl.php');
           if (preg_match('/INFO(.+?[\x23])[\x23]/s',$tpl,$matches))
           {     
               if (preg_match('/name[\x20]*:(.+?)[\x23]/s',$matches[1],$name)) $info['name']=$name[1];
               if (preg_match('/description[\x20]*:(.+?)[\x23]/s',$matches[1],$desc)) $info['description']=$desc[1];
           } 
           
           $themes[$file]=array();
           $themes[$file]['name']=isset($info['name']) ? $info['name'] : $file;
           $themes[$file]['description']=isset($info['description']) ? $info['description'] : '';
        }
        
        ksort($themes);
        
        return $themes;
    }
    
    function add_title($string)
    {
        if (!empty($this->params['title'])) $this->params['title'].=' | ';
        
        $this->params['title'].=strip_tags($string);
    }
    
    function add_keywords($string)
    {
        if (!empty($this->params['keywords'])) $this->params['keywords'].=', ';
        $this->params['keywords'].=htmlspecialchars($string);
    }
    
    function add_description($string)
    {
        if (!empty($this->params['description'])) $this->params['description'].=' ';
        $this->params['description'].=htmlspecialchars($string);
    }
    
    function add_css($path)
    {
        if (!in_array($path,$this->params['css']))
        {
            $this->params['css'][]=$path;
        }
    }
    
    function add_link($link)
    {
        if (!in_array($link,$this->params['link']))
        {
            $this->params['link'][]=$link;
        }
    }
    
    function add_script($path)
    {
        if (!in_array($path,$this->params['scripts']))
        {
            $this->params['scripts'][]=$path;
        }
    }
    
    function add_header($code)
    {
        $this->params['header'][]=$code;
    }
    
    function add_jquery_ui()
    {
        $this->add_script('js/jquery-ui');
        $this->add_css('misc/jquery-ui');
    }
    
    function prepend_body($html) {
		$this->params['prepend_body'].=$html;
	}
    
    function append_body($html) {
		$this->params['append_body'].=$html;
	}
    
    function head()
    {
        $this->params['head'].="\n";
        $this->params['head'].='<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n";
        
        // title
        $widget_name='title';
        $widget_class='CSawanna';
        $widget_handler='title';
        $separator='-c-a-c-h-e-';
        
        if ($this->params['location']!=$this->config->admin_area && $title=$this->cache->get($this->cache->cache_name($this->params['language'],$this->config->theme,$widget_name,$widget_class,$widget_handler,str_replace('/',$separator,$this->params['location']).$this->params['argument']),'meta'))
        {
            $this->params['head'].='<title>'.$title.'</title>'."\n";
        } else 
        {
            $this->params['head'].='<title>'.(!empty($this->params['title']) ? $this->params['title'] : $this->default_title).'</title>'."\n";
            if ($this->params['location']!=$this->config->admin_area) $this->cache->set($this->cache->cache_name($this->params['language'],$this->config->theme,$widget_name,$widget_class,$widget_handler,str_replace('/',$separator,$this->params['location']).$this->params['argument']),$this->params['title'],'meta');
        }
        
        // keywords
        $widget_name='keywords';
        $widget_class='CSawanna';
        $widget_handler='keywords';
        $separator='-c-a-c-h-e-';
        
        if ($this->params['location']!=$this->config->admin_area && $keywords=$this->cache->get($this->cache->cache_name($this->params['language'],$this->config->theme,$widget_name,$widget_class,$widget_handler,str_replace('/',$separator,$this->params['location']).$this->params['argument']),'meta'))
        {
            $this->params['head'].='<meta name="keywords" content="'.$keywords.'" />'."\n";
        } else 
        {
            if (empty($this->params['keywords']) && !empty($this->config->default_meta_keywords)) $this->params['keywords']=$this->config->default_meta_keywords;
            $this->params['head'].='<meta name="keywords" content="'.$this->params['keywords'].'" />'."\n";
            if ($this->params['location']!=$this->config->admin_area) $this->cache->set($this->cache->cache_name($this->params['language'],$this->config->theme,$widget_name,$widget_class,$widget_handler,str_replace('/',$separator,$this->params['location']).$this->params['argument']),$this->params['keywords'],'meta');
        }
        
         // description
        $widget_name='description';
        $widget_class='CSawanna';
        $widget_handler='description';
        $separator='-c-a-c-h-e-';
        
        if ($this->params['location']!=$this->config->admin_area && $description=$this->cache->get($this->cache->cache_name($this->params['language'],$this->config->theme,$widget_name,$widget_class,$widget_handler,str_replace('/',$separator,$this->params['location']).$this->params['argument']),'meta'))
        {
            $this->params['head'].='<meta name="description" content="'.$description.'" />'."\n";
        } else 
        {
            if (empty($this->params['description']) && !empty($this->config->default_meta_description)) $this->params['description']=$this->config->default_meta_description;
            $this->params['head'].='<meta name="description" content="'.$this->params['description'].'" />'."\n";
            if ($this->params['location']!=$this->config->admin_area) $this->cache->set($this->cache->cache_name($this->params['language'],$this->config->theme,$widget_name,$widget_class,$widget_handler,str_replace('/',$separator,$this->params['location']).$this->params['argument']),$this->params['description'],'meta');
        }
        
        $this->params['head'].='<meta name="generator" content="Sawanna CMS" />'."\n";
        
        if (!defined('BASE_PATH'))
			$this->params['head'].='<link rel="shortcut icon" href="'.SITE.'/favicon.ico" />'."\n";
		else
			$this->params['head'].='<link rel="shortcut icon" href="'.BASE_PATH.'/favicon.ico" />'."\n";
        
        // css
        if (is_array($this->params['css']) && !empty($this->params['css']))
        {
            foreach ($this->params['css'] as $css)
            {
				if (!defined('BASE_PATH'))
					$this->params['head'].='<link rel="stylesheet" type="text/css" href="'.SITE.'/'.quotemeta($css).'.css" />'."\n";
                else
					$this->params['head'].='<link rel="stylesheet" type="text/css" href="'.BASE_PATH.'/'.quotemeta($css).'.css" />'."\n";
            }
        }    
        
        if (is_array($this->params['link']) && !empty($this->params['link']))
        {
            foreach ($this->params['link'] as $link)
            {
                $this->params['head'].=$link."\n";
            }
        }         

        if (!empty($this->css))
        {
			if (!defined('BASE_PATH'))
				$this->params['head'].='<link rel="stylesheet" type="text/css" href="'.SITE.'/'.quotemeta($this->root_dir).'/'.quotemeta($this->config->theme).'/'.quotemeta($this->css).'.css" />'."\n";
			else
				$this->params['head'].='<link rel="stylesheet" type="text/css" href="'.BASE_PATH.'/'.quotemeta($this->root_dir).'/'.quotemeta($this->config->theme).'/'.quotemeta($this->css).'.css" />'."\n";
        }
        
        // scripts
        if (is_array($this->params['scripts']) && !empty($this->params['scripts']))
        {
            foreach ($this->params['scripts'] as $path)
            {
				if (!defined('BASE_PATH'))
					$this->params['head'].='<script language="JavaScript" type="text/javascript" src="'.SITE.'/'.quotemeta($path).'.js"></script>'."\n";
				else
					$this->params['head'].='<script language="JavaScript" type="text/javascript" src="'.BASE_PATH.'/'.quotemeta($path).'.js"></script>'."\n";
            }
        }            
        
        if (is_array($this->params['header']) && !empty($this->params['header']))
        {
            foreach ($this->params['header'] as $code)
            {
                $this->params['head'].=$code."\n";
            }
        }
        
        if (!defined('BASE_PATH'))
			$this->params['head'].='<!--[if IE 6]><script language="JavaScript" src="'.SITE.'/js/png24.js"></script><![endif]-->'."\n";
		else
			$this->params['head'].='<!--[if IE 6]><script language="JavaScript" src="'.BASE_PATH.'/js/png24.js"></script><![endif]-->'."\n";
    }
    
    function fix_css_urls(&$output,$css_path)
    {
        $dir=dirname($css_path);
        preg_match_all('/url\(([\']|["])?(.*?)([\']|["])?\)/s',$output,$matches);
        
        if (!empty($matches[2]))
        {
            $replaced=array();
            foreach ($matches[2] as $url)
            {
                if (in_array($url,$replaced)) continue;
                if (empty($url) || preg_match('/^(http:\/\/|\/)/',$url)) continue;
                
                $output=str_replace($url,SITE.quotemeta($dir).'/'.$url,$output);
                $replaced[]=$url;
            }
        }
    }
    
    function sitename()
    {
        $this->params['sitename']=$this->config->site_name;
    }
    
    function logo()
    {
        if (!empty($this->config->site_logo))
            $this->params['logo']='<img src="'.SITE.'/'.$this->config->site_logo.'" alt="'.$this->config->site_name.'" />';
        else 
            $this->params['logo']='';
    }
    
    function slogan()
    {
        $this->params['slogan']=$this->config->site_slogan;
    }
    
    function language_switcher()
    {
        if (count($this->config->languages)<2) return;
        
        $html='<ul class="language-switcher">';
        foreach ($this->config->languages as $lcode=>$lname)
        {
            $html.='<li><a href="'.SITE.'/'.$lcode.'">'.htmlspecialchars($lname).'</a></li>';
        }
        $html.='</ul>';
        
        $this->params['language_switcher']=$html;
    }
    
    function duty()
    {
        $this->params['duty'].="\n<!--information elements-->\n";
        if(DEBUG_MODE) $this->params['duty'].='<div id="DEBUG_MODE" style="border:1px dotted #470606;background-color:#f68d8d;color:#470606;font-family:Verdana;font-size:11px;padding:5px;text-align:center;">&lt;DEBUG MODE&gt;</div>'."\n";
        
        $this->params['duty'].=sawanna_show_errors(false)."\n";
        $this->params['duty'].=sawanna_show_messages(false)."\n";
        
        $this->params['duty'].="<!--/information elements-->\n";
    }
    
    function parseComponents(&$components,&$html)
    {
        while (preg_match('/\[component\][\x20]*([a-z0-9_-]+?)[\x20]*\[\/component\]/i',$html,$matches)) 
        {
            if (array_key_exists($matches[1],$components))
                $html=str_replace($matches[0],'<div id="'.$matches[1].'-component" class="tpl_component">'.preg_replace('/\[(\/)?component\]/i','{$1component}',$components[$matches[1]]).'</div>',$html);
            else
                $html=str_replace($matches[0],'<!--['.$matches[1].']-->',$html);
        }
    }
    
    function parseElements(&$elements,&$html)
    {
        while (preg_match('/\[element\][\x20]*([a-z0-9_-]+?)[\x20]*\[\/element\]/i',$html,$matches)) 
        {
            if (array_key_exists($matches[1],$elements))
                $html=str_replace($matches[0],$elements[$matches[1]],$html);
            else
                $html=str_replace($matches[0],'<!--['.$matches[1].']-->',$html);
        }
    }

    function render(&$components,&$elements)
    {
		// deprecated
		//$this->html.=file_get_contents(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($this->config->theme).'/'.quotemeta($this->tpl).'.tpl');
		
		ob_start();
		include(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($this->config->theme).'/'.quotemeta($this->tpl).'.tpl');
		if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) {
			$this->html.=ob_get_clean();
		} else {
			$this->html=ob_get_clean();
		}
		
        
        $this->parseElements($elements,$this->html);
        $this->parseComponents($components,$this->html);
    }
    
    function process(&$components)
    {
        $elements=array();
        $tpl_elements=$this->getElements();

        $params=array(
            'title' => $this->params['title'],
            'keywords' => $this->params['keywords'],
            'description' => $this->params['description'],
            'css' => $this->params['css'],
            'scripts' => $this->params['scripts'],
            'site_name' => $this->config->site_name,
            'site_slogan' => $this->config->site_slogan,
            'site_logo' => $this->config->site_logo,
            'location' => $this->params['location'],
            'language' => $this->params['language'],
            'language_switcher' => $this->params['language_switcher']
        );
        
        foreach ($tpl_elements as $element)
        {
            $element_func='tpl_'.$element;
            if (function_exists($element_func))
                $elements[$element]=$element_func($params);
        }
        
        if (!array_key_exists('head',$elements)) 
        {
            $this->head();
            $elements['head']=$this->params['head'];
        }

        if (!array_key_exists('sitename',$elements))
        {
            $this->sitename();
            $elements['sitename']=$this->params['sitename'];
        }
                
        if (!array_key_exists('logo',$elements))
        {
            $this->logo();
            $elements['logo']=$this->params['logo'];
        }

        if (!array_key_exists('slogan',$elements))
        {
            $this->slogan();
            $elements['slogan']=$this->params['slogan'];
        }
        
        if (!array_key_exists('language_switcher',$elements))
        {
            $this->language_switcher();
            $elements['language_switcher']=$this->params['language_switcher'];
        }
        
        $duty=isset($components[$this->duty]) ? $components[$this->duty] : '';
        $this->duty();
        $components[$this->duty]=$this->params['duty'].$duty;
        
        $this->render($components,$elements);
        
        /*
        $this->html=preg_replace('/^(.+<body.*?>)(.+)$/is','$1'.$this->params['prepend_body'].'$2',$this->html);
        $this->html=preg_replace('/^(.+)(<\/body>.+)$/is','$1'.$this->params['append_body'].'$2',$this->html);
        */
    }
    
    function site_offline_message()
    {
        $html=file_get_contents(ROOT_DIR.'/offline.php');
        
        $offline_message=$this->config->site_offline_message;
        
        if (($b=strpos($html,'[message]'))!==false)
                $html=str_replace('[message]',$offline_message,$html);
        elseif (($b=strpos($html,'</body>'))!==false)
                $html=substr($html,0,$b).$offline_message.substr($html,$b);
        else 
                $html.=$offline_message;
                
        return $html;
    }
    
    static function failure_message()
    {
        $html=file_get_contents(ROOT_DIR.'/500.php');
        
        return $html;
    }
    
    static function not_found_message()
    {
        $html=file_get_contents(ROOT_DIR.'/404.php');
        
        return $html;
    }
    
    static function forbidden_message()
    {
        $html=file_get_contents(ROOT_DIR.'/403.php');
        
        return $html;
    }
    
    function cookie_check_script()
    {
        $this->params['duty'].="\n<!--checking cookies-->\n";
        $this->params['duty'].='<script language="JavaScript">';
        $this->params['duty'].='$(document).ready(function(){';
        $this->params['duty'].='if (!navigator.cookieEnabled) {alert("[str]Please enable Cookies in your browser[/str]");}';
        $this->params['duty'].='});';
        $this->params['duty'].='</script>';
        $this->params['duty'].="\n<!--/checking cookies-->\n";
    }

    function clear()
    {
        unset($this->html);
        unset($this->params);
    }
}
