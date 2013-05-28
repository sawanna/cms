<?php 
defined('SAWANNA') or die();

class CCache
{
    var $root_dir;
    var $enabled;
    var $css;
    var $image;
    var $clear_on_change;
    var $path_extension;
    
    function CCache(&$config)
    {
        $this->root_dir='cache';
        $this->enabled=(file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir)) && is_dir(ROOT_DIR.'/'.quotemeta($this->root_dir)) && is_writable(ROOT_DIR.'/'.quotemeta($this->root_dir))) ? $config->cache : false;
        $this->css=(file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir)) && is_dir(ROOT_DIR.'/'.quotemeta($this->root_dir)) && is_writable(ROOT_DIR.'/'.quotemeta($this->root_dir))) ? $config->cache_css : false;
        $this->image=(file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir)) && is_dir(ROOT_DIR.'/'.quotemeta($this->root_dir)) && is_writable(ROOT_DIR.'/'.quotemeta($this->root_dir))) ? $config->img_process_cache : false;
        $this->clear_on_change=array();
        $this->path_extension=preg_match('/\.html$/',$_SERVER['REQUEST_URI']) ? 'html' : 'void';
    }
    
    function cache_name($language,$theme,$widget_name,$widget_class,$widget_handler,$argument)
    {
        $separator='-#cache#-';
        
        if (!empty($argument))
            $name=quotemeta(SITE).$separator.quotemeta($language).$separator.quotemeta($theme).$separator.quotemeta($widget_name).$separator.quotemeta($widget_class).$separator.quotemeta($widget_handler).$separator.quotemeta($argument);
        else 
            $name=quotemeta(SITE).$separator.quotemeta($language).$separator.quotemeta($theme).$separator.quotemeta($widget_name).$separator.quotemeta($widget_class).$separator.quotemeta($widget_handler);
       
        return md5($name.PRIVATE_KEY.$this->path_extension);
    }
    
    function set($name,&$html,$type='common')
    {
         if ($type=='css' && !$this->css) return false;
         if ($type=='image' && !$this->image) return false;
         if ($type!='css' && $type!='image' && !$this->enabled) return false;
        
        if (file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($name).'.'.quotemeta($type).'.cache'))
        {
            @chmod(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($name).'.'.quotemeta($type).'.cache',0600);
        }
        
        $file=fopen(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($name).'.'.quotemeta($type).'.cache','wb');
        if (!$file)
        {
            trigger_error('Failed to set cached data',E_USER_WARNING);
            return false;
        }
        
        if (fwrite($file,$html) === false)
        {
             trigger_error('Failed to set cached data',E_USER_WARNING);
             fclose($file);
             return false;
        }
        
        fclose($file);
        @chmod(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($name).'.'.quotemeta($type).'.cache',0000);
        return true;
    }
    
    function get($name,$type='common')
    {
         if ($type=='css' && !$this->css) return false;
         if ($type=='image' && !$this->image) return false;
         if ($type!='css' && $type!='image' && !$this->enabled) return false;
         if (empty($name)) return false;
         if (!file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($name).'.'.quotemeta($type).'.cache')) return false;
         
         @chmod(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($name).'.'.quotemeta($type).'.cache',0600);
         
         if (!is_readable(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($name).'.'.quotemeta($type).'.cache')) return false;

         $html=file_get_contents(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($name).'.'.quotemeta($type).'.cache');
         
         @chmod(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($name).'.'.quotemeta($type).'.cache',0000);
         
         if (empty($html)) return false;
         
         return $html;
    }
    
    function set_clear_on_change($to,$type)
    {
        if (empty($to) || empty($type)) return ;
        if (isset($this->clear_on_change[$to]) && is_array($this->clear_on_change[$to]) && in_array($type,$this->clear_on_change[$to])) return ;
        
        if (isset($this->clear_on_change[$to]) && is_array($this->clear_on_change[$to]))
            array_push($this->clear_on_change[$to],$type);
        else 
            $this->clear_on_change[$to]=array($type);
    }
    
    function clear_on_change($type)
    {
        if (array_key_exists($type,$this->clear_on_change) && is_array($this->clear_on_change[$type]) && !empty($this->clear_on_change[$type])) return true;
        
        return false;
    }
    
    function clear($name,$type='common')
    {
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($name).'.'.quotemeta($type).'.cache')) return false;
        
        return @unlink(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($name).'.'.quotemeta($type).'.cache');
    }
    
    function clear_all_by_type($type='common')
    {
        $dir=opendir(ROOT_DIR.'/'.$this->root_dir);
        if (!$dir) return false;
        
        while (($file=readdir($dir))!==false) {
        	   if (!is_file(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.$file)) continue;
        	   if (!preg_match('/\.'.preg_quote($type).'\.cache$/',$file)) continue;
        	   
        	   @unlink(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.$file);
        }
        
        closedir($dir);
        return true;
    }
    
    function clear_all()
    {
        $dir=opendir(ROOT_DIR.'/'.$this->root_dir);
        if (!$dir) return false;
        
        while (($file=readdir($dir))!==false) {
        	   if (!is_file(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.$file)) continue;
        	   if (substr($file,-6)!='.cache') continue;
        	   
        	   @unlink(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.$file);
        }
        
        closedir($dir);
        return true;
    }
}
