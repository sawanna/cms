<?php 
defined('SAWANNA') or die();

class CLocale
{
    var $languages;
    var $current;
    var $phrases;
    var $config;
    var $query;
    var $root_dir;
    
    function CLocale(&$config)
    {
        $this->config=$config;
        $this->languages=$config->languages;
        $this->current=$config->language;
        
        if ($config->accept_language) {
            $this->accept_language();
        }

        $this->phrases=array();
        $this->root_dir='languages';
    }
    
    function init(&$get)
    {
        if (count($this->languages)<2) return;
        if (empty($get)) return;
        
        $s=strpos($get,'/');
        if ($s!==false)
        {
            $l=substr($get,0,$s);    
        } else
        {
            $l=$get;
        }
        
        if (!array_key_exists($l,$this->languages)) return;
        
        $this->current=$l;
        $get=substr($get,strlen($this->current));
        if (substr($get,0,1)=='/') $get=substr($get,1); 
    }
    
    function fix($query)
    {
        $this->query=$query;
        // if (strpos($query,$this->config->admin_area)===0) $this->current = $this->config->admin_area_language;
        if (preg_match('/^'.preg_quote($this->config->admin_area).'(\/.+)?$/',$query)) $this->current = $this->config->admin_area_language;
    }
    
    function get_installed_languages()
    {
        $langs=array();
        
        $dir=ROOT_DIR.'/'.quotemeta($this->root_dir);
        $ldir=opendir($dir);
        while(($file=readdir($ldir)) !== false)
        {  
            if (!is_dir($dir.'/'.quotemeta($file)) || $file=='.' || $file=='..') continue;
      
            if (!file_exists($dir.'/'.quotemeta($file).'/lng.php')) continue;
         
            $info=array();
            $lng=file_get_contents($dir.'/'.quotemeta($file).'/lng.php');
           if (preg_match('/INFO(.+?[\x23])[\x23]/s',$lng,$matches))
           {     
               if (preg_match('/name[\x20]*:(.+?)[\x23]/s',$matches[1],$name)) $info['name']=$name[1];
           } 
           
           $langs[$file]=array();
           $langs[$file]['name']=isset($info['name']) ? $info['name'] : $file;
        }
        
        ksort($langs);
        
        return $langs;
    }
    
    function accept_language()
    {
         if (count($this->languages)<2) return false;
        
          $accept_lang='';
          if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
          {
              $l=explode(';',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
              if (isset($l[0]) && !empty($l[0]))
              {
                  if (array_key_exists($l[0],$this->languages)) $accept_lang=$l[0];
                  else 
                  {
                      $ll=explode(',',$l[0]);
                      if (isset($ll[0]) && !empty($ll[0]) && array_key_exists($ll[0],$this->languages)) $accept_lang=$ll[0];
                      elseif (isset($ll[1]) && !empty($ll[1]) && array_key_exists($ll[1],$this->languages)) $accept_lang=$ll[1];
                  }
              }
          }
          
          if (!empty($accept_lang)) 
          {
              $this->current=$accept_lang;
              return true;
          } else return false;
    }
    
    function load($file='')
    {
        if (!empty($file))
        {
            if (!file_exists($file)) return;
            
            include_once($file);
            if (isset($phrases) && is_array($phrases) && !empty($phrases))
            {
                $this->phrases=array_merge($this->phrases,$phrases);
            }
            
            return ;
        }
        
        if (file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($this->current).'/lng.php'))
        {
            include_once(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($this->current).'/lng.php');
            if (isset($phrases) && is_array($phrases) && !empty($phrases))
            {
                $this->phrases=array_merge($this->phrases,$phrases);
            }
        }
        
        if (preg_match('/^'.preg_quote($this->config->admin_area).'(\/.+)?$/',$this->query) && file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($this->current).'/admin.lng.php'))
        {
            unset($phrases);
            include_once(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($this->current).'/admin.lng.php');
            if (isset($phrases) && is_array($phrases) && !empty($phrases))
            {
                $this->phrases=array_merge($this->phrases,$phrases);
            }
        }
        
        $this->loaded(true);
    }
    
    function loaded($load=false)
    {
        static $loaded=false;
        
        if ($load) $loaded=true;
        
        return $loaded;
    }
    
    function get_string($str)
    {
        if (!$this->loaded()) $this->load();
        
        if (isset($this->phrases[$str]))
        {
            return $this->phrases[$str];
        }
        
        return $str;
    }
    
    function translate(&$html)
    {
        if (!$this->loaded()) $this->load();

        while (preg_match('/\[strf\](.*?)\[\/strf\]/s',$html,$matches))
        {
            if (preg_match('/(.*)[%](.*?)[%](.*)/s',$matches[1],$str_vars))
            {
                 $m=$str_vars[1].'%'.$str_vars[3];
                 if (array_key_exists($m,$this->phrases))
                 {
                     $html=str_replace('[strf]'.$matches[1].'[/strf]',str_replace('%',$str_vars[2],$this->phrases[$m]),$html);
                 } else
                 {
                     $html=str_replace('[strf]'.$matches[1].'[/strf]',str_replace('%','',$matches[1]),$html);
                 }
            } else 
            {
                 if (array_key_exists($matches[1],$this->phrases))
                 {
                    $html=str_replace('[strf]'.$matches[1].'[/strf]',$this->phrases[$matches[1]],$html);
                 } else
                 {
                     $html=str_replace('[strf]'.$matches[1].'[/strf]',$matches[1],$html);
                 }
            }
        }
            
        while (preg_match('/\[str\](.*?)\[\/str\]/s',$html,$matches))
        {
             if (array_key_exists($matches[1],$this->phrases))
             {
                $html=str_replace('[str]'.$matches[1].'[/str]',$this->phrases[$matches[1]],$html);
             } else
             {
                 $html=str_replace('[str]'.$matches[1].'[/str]',$matches[1],$html);
             }
        }

    }
    
    function clear()
    {
        unset($this->phrases);
    }
}