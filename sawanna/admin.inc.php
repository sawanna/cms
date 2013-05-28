<?php
defined('SAWANNA') or die();

class CAdminArea
{
	const WIDGETS_LIMIT=5;
	
    var $constructor;
    var $db;
    var $config;
    var $session;
    var $locale;
    var $access;
    var $form;
    var $module;
    var $event;
    var $navigator;
    var $tpl;
    var $cache;
    var $file;
    var $arg;
    
    function CAdminArea(&$db,&$config,&$session,&$locale,&$access,&$form,&$module,&$event,&$navigator,&$tpl,&$constructor,&$cache,&$file,$arg)
    {
        $this->db=$db;
        $this->config=$config;
        $this->session=$session;
        $this->locale=$locale;
        $this->access=$access;
        $this->form=$form;
        $this->module=$module;
        $this->event=$event;
        $this->navigator=$navigator;
        $this->tpl=$tpl;
        $this->constructor=$constructor;
        $this->cache=$cache;
        $this->file=$file;
        $this->arg=$arg;
    }
    
    function add_scripts()
    {
        $this->tpl->add_jquery_ui();
        
        if (!defined('ADMIN_LOW_THEME') || !ADMIN_LOW_THEME)
        {
            $this->tpl->add_header('<script language="JavaScript">admin_low_theme=false;</script>');
        } else
        {
            $this->tpl->add_header('<script language="JavaScript">admin_low_theme=true;</script>');
        }
        
        $this->tpl->add_script('js/admin');
        $this->tpl->add_script('js/selectmenu');
        $this->tpl->add_script('js/select2uislider');
    }
    
    function output(&$content)
    {
        $this->tpl->init($this->config->admin_area,$this->locale->current);
        $this->tpl->add_title('Sawanna CMS ver.'.VERSION);
        $this->tpl->add_css('misc/admin');
        
        $this->tpl->head();
        
        $html='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html><head>';
        $html.=$this->tpl->params['head'];
        $content=$html.'</head><body>'.$content.'</body></html>';
    }
    
    function build_admin_header()
    {
        $html='<div id="admin-header">';
        $html.='<a class="index" href="'.$this->constructor->url('[home]').'" title="'.SITE.'"></a>';
        $html.='<p>[str]'.date('F').'[/str], '.date('j Y').'</p>';
        $html.='<p><span class="sawanna">Sawanna CMS</span> ver.'.VERSION.'</p>';
        if ($this->access->logged)
        {
            $html.='<p>[str]Logged in as[/str]: '.$this->constructor->link($this->config->logged_user_profile_url,'[str]'.$this->config->logged_user_name.'[/str]','user_profile_link').'</p>';
            $html.='<p>'.$this->constructor->link($this->config->logout_area,'[str]Logout[/str]','logout').'</p>';
        }
        $html.='</div>';
        
        return $html;
    }
    
    function build_admin_footer()
    {
        $html='<div id="admin-footer">';
        $html.='<p>Powered by <a href="http://sawanna.org" target="_blank">Sawanna CMS</a></p>';
        $html.='</div>';

        return $html;
    }
    
    function build_adminbar()
    {
        static $selected='system';
        static $opt_paths=array('image/settings');

        $html='<div class="sidebar"><div id="adminbar">';
        
        $_html='';
        
        if ($this->access->has_access('su'))
        {
            $_html.='<li class="adminbar_item">'.$this->constructor->link($this->config->admin_area,'[str]Home[/str]','adminbar_link').'</li>';
            $_html.='<li class="adminbar_item">'.$this->constructor->link($this->config->admin_area.'/info','[str]Information[/str]','adminbar_link').'</li>';
            $_html.='<li class="adminbar_item">'.$this->constructor->link($this->config->admin_area.'/config','[str]Configuration[/str]','adminbar_link').'</li>';
            $_html.='<li class="adminbar_item">'.$this->constructor->link($this->config->admin_area.'/md5','[str]System check[/str]','adminbar_link').'</li>';
            $_html.='<li class="adminbar_item">'.$this->constructor->link($this->config->admin_area.'/themes','[str]Theme[/str]','adminbar_link').'</li>';
            $_html.='<li class="adminbar_item">'.$this->constructor->link($this->config->admin_area.'/languages','[str]Languages[/str]','adminbar_link').'</li>';
            $_html.='<li class="adminbar_item">'.$this->constructor->link($this->config->admin_area.'/permissions','[str]Permissions[/str]','adminbar_link').'</li>';
            $_html.='<li class="adminbar_item">'.$this->constructor->link($this->config->admin_area.'/modules','[str]Modules[/str]','adminbar_link').'</li>';
        }      
         
        if ($this->access->has_access('manage widgets'))
        {   
            $_html.='<li class="adminbar_item">'.$this->constructor->link($this->config->admin_area.'/widgets','[str]Widgets[/str]','adminbar_link').'</li>';
        }
            
        if (!empty($_html))
        {
            $html.='<div><h3 id="system"><a href="#">[str]System[/str]</a></h3><ul>';
            $html.=$_html;
            $html.='</ul></div>';
        }
        
        if ($this->access->has_access('su'))
        {
            $opts='<li class="adminbar_item">'.$this->constructor->link($this->config->admin_area.'/image/settings','[str]Images[/str]','adminbar_link').'</li>';
            $opts.=$this->build_module_optionsbar($opt_paths);
            if (!empty($opts))
                $html.='<div><h3 id="options"><a href="#">[str]Options[/str]</a></h3><div>'.$opts.'</div></div>';
            
        }

        $html.=$this->build_module_adminbar($selected);
        
        if (in_array($this->arg,$opt_paths)) $selected='options';
            
        $html.='</div></div>';

        $html.='<script language="JavaScript">';
        $html.='$(document).ready(function(){$("#adminbar").accordion({ header: "h3", active: "#'.strip_tags($selected).'" });});';
        $html.='</script>';
        
        return $html;
    }
    
    function build_module_optionsbar(&$opt_paths)
    {
        $html='';
        
        foreach ($this->module->modules as $module)
        {
            if (!isset($GLOBALS[$module->name.'AdminHandler']) || !is_object($GLOBALS[$module->name.'AdminHandler'])) continue;
            if (!method_exists($GLOBALS[$module->name.'AdminHandler'],'optbar')) continue;
            
            $items=array();
            $optbar=$GLOBALS[$module->name.'AdminHandler']->optbar();
            if (!is_array($optbar) || empty($optbar)) continue;
            
            foreach ($optbar as $arg=>$title)
            {
                $opt_paths[]=$arg;
                $html.='<div class="adminbar_item">'.$this->constructor->link($this->config->admin_area.'/'.$arg,$title,'adminbar_link options').'</div>';
            }
        }
        
        return $html;
    }
    
    function build_module_adminbar(&$selected)
    {
        $html='';
        
        foreach ($this->module->modules as $module)
        {
            if (!isset($GLOBALS[$module->name.'AdminHandler']) || !is_object($GLOBALS[$module->name.'AdminHandler'])) continue;
            if (!method_exists($GLOBALS[$module->name.'AdminHandler'],'adminbar')) continue;
            
            $items=array();
            $adminbar=$GLOBALS[$module->name.'AdminHandler']->adminbar();
            if (is_array($adminbar) && !empty($adminbar))
                list($items,$select_path_prefix)=$adminbar;
            
            if (!is_array($items)) 
            {
                trigger_error('Cannot set adminbar item for '.htmlspecialchars($module->name).' module. Invalid data given',E_USER_WARNING);
                continue;
            }
            
            if (empty($items)) continue;
            
            $html.='<div><h3 id="'.htmlspecialchars($module->name).'"><a href="#">'.htmlspecialchars($module->title).'</a></h3><div>';
            foreach ($items as $arg=>$title)
            {
                $html.='<div class="adminbar_item">'.$this->constructor->link($this->config->admin_area.'/'.$arg,$title,'adminbar_link '.htmlspecialchars($module->name)).'</div>';
            }
            $html.='</div></div>';
            
            if (!empty($select_path_prefix) && strpos($this->arg,$select_path_prefix)===0) $selected=$module->name;
        }
        
        return $html;
    }
    
    function build_admin_area()
    {
        $content=ob_get_contents();
        ob_end_clean();
        
        $split=$this->split_content($content);
        unset($content);
        
        $pre_content='';
        $heads=array();
        $contents=array();
        
        foreach ($split as $num=>$elem)
        {
            if ($num==0) 
            {
                $pre_content=$elem;
                continue;
            }
            if ($num%2) $heads[]=$elem;
            else $contents[]=$elem;
        }
        unset($split);
        
        $html='<script language="JavaScript">';
        $html.='$(document).ready(function(){window.setTimeout("$(\'#admin-area\').tabs()",100);});';
        $html.='</script>';
        
        $html.='<div id="admin-area">';
        $html.='<ul>';
        foreach ($heads as $num=>$elem)
        {
            $html.='<li><a href="#tabs-'.$num.'">'.$elem.'</a></li>';
        }
        $html.='</ul>';
        foreach ($contents as $num=>$elem)
        {
            $html.='<div id="tabs-'.$num.'">'.$elem.'</div>';
        }
        $html.='</div>';

        return '<div class="workspace">'.$pre_content.$html.'</div>';
    }
    
    function build_module_admin_area()
    {
        foreach ($this->module->modules as $module)
        {
            if (!isset($GLOBALS[$module->name.'AdminHandler']) || !is_object($GLOBALS[$module->name.'AdminHandler'])) continue;
            if (!method_exists($GLOBALS[$module->name.'AdminHandler'],'admin_area')) continue;
            
            $GLOBALS[$module->name.'AdminHandler']->admin_area();
        }
    }
    
    function split_content(&$content)
    {
        $split=preg_split('/<h1.*?class="tab".*?>(.+?)<\/h1>/si',$content,0,PREG_SPLIT_DELIM_CAPTURE);
        return $split;
    }
    
    function welcome()
    {
        $html='<fieldset class="welcome_block"><legend>'.($this->config->admin_load_news ? '[str]News[/str]' : 'Sawanna CMS ver.'.VERSION).'</legend>';
        $html.='<div id="sawanna-news"></div><div id="promo">[strf]Read manual on %<a href="http://sawanna.org" target="_blank">http://sawanna.org</a>%[/strf]</div>';
        $html.='</fieldset>';
        
        if ($this->config->admin_load_news) {
			$html.='<script language="JavaScript">';
			$html.='$(document).ready(function(){';
			$html.='$("#sawanna-news").html("<span class=\"loader\">[str]Loading news...[/str]</span>");';
			$html.='$.post("'.$this->constructor->url($this->config->admin_area.'/news').'",{mode:"sawanna"},function(response){$("#sawanna-news").html(response);});';
			$html.='});';
			$html.='</script>';
        }
        
        if ($this->access->has_access('su'))
        {
            $fields=array(
                'submit' => array(
                    'type' => 'submit',
                    'value' => '[str]Clear cached data[/str]',
                    'attributes' => array('class'=>'button')
                )
            );
    
            $clear_form=$this->constructor->form('clear-cache',$fields,'process_admin_area_requests',$this->config->admin_area,$this->config->admin_area.'/clear-cache','','CSawanna',0);
            
            $log_form=$this->constructor->link($this->config->admin_area.'/log','[str]View access log[/str]','welcome_block_link button');
            $errorlog_form=$this->constructor->link($this->config->admin_area.'/errors','[str]View errors log[/str]','welcome_block_link button');
            $cron_form=$this->constructor->link('cron','[str]Run cron[/str]','welcome_block_link button',array('target'=>'_blank','onclick'=>"$(this).attr('href',$(this).attr('href')+'&render=1')"),true);
            
            $html.='<fieldset class="welcome_block"><legend>[str]Actions[/str]</legend><div class="inline-left-item">'.$log_form.'</div><div class="inline-left-item">'.$errorlog_form.'</div><div class="inline-right-item">'.$cron_form.'</div><div class="inline-right-item">'.$clear_form.'</div></fieldset>';
            
            include_once(ROOT_DIR.'/sawanna/log.inc.php');
            $log=new CLog($this->db,$this->config->log_period);
            
            $today_all_co=$log->get_today_records_count();
            $today_ip_co=$log->get_today_records_count('ip');
            $today_ua_co=$log->get_today_records_count('ua');
            $today_referer_co=$log->get_today_records_count('referer');
            $today_hosts_co=$log->get_today_hosts_count();
            
            if (function_exists('gd_info'))
            {
                $gd=gd_info(); 
                $gd_version=isset($gd['GD Version']) ? $gd['GD Version'] : 'unknown';
                $gd_freetype=isset($gd['FreeType Support']) && $gd['FreeType Support'] ? '<span class="ok">[str]yes[/str]</span>' : '<span class="warning">[str]no[/str]</span>'; 
                $gd_gif_read=isset($gd['GIF Read Support']) && $gd['GIF Read Support'] ? '<span class="ok">[str]yes[/str]</span>' : '<span class="warning">[str]no[/str]</span>'; 
                $gd_gif_create=isset($gd['GIF Create Support']) && $gd['GIF Create Support'] ? '<span class="ok">[str]yes[/str]</span>' : '<span class="warning">[str]no[/str]</span>'; 
                $gd_jpg=(isset($gd['JPG Support']) && $gd['JPG Support']) || (isset($gd['JPEG Support']) && $gd['JPEG Support']) ? '<span class="ok">[str]yes[/str]</span>' : '<span class="warning">[str]no[/str]</span>'; 
                $gd_png=isset($gd['PNG Support']) && $gd['PNG Support'] ? '<span class="ok">[str]yes[/str]</span>' : '<span class="warning">[str]no[/str]</span>'; 
                
                $gd_html='<ul class="info">';
                $gd_html.='<li><label>[str]GD Version[/str]:</label> '.$gd_version.'</li>';
                $gd_html.='<li><label>[str]FreeType Support[/str]:</label> '.$gd_freetype.'</li>';
                $gd_html.='<li><label>[str]GIF Read Support[/str]:</label> '.$gd_gif_read.'</li>';
                $gd_html.='<li><label>[str]GIF Create Support[/str]:</label> '.$gd_gif_create.'</li>';
                $gd_html.='<li><label>[str]JPG Support[/str]:</label> '.$gd_jpg.'</li>';
                $gd_html.='<li><label>[str]PNG Support[/str]:</label> '.$gd_png.'</li>';
                $gd_html.='</ul>';
            } else 
            {
                $gd_html='<span class="warning">[str]GD not supported[/str]</span>';
            }
            
            $mb_string=function_exists('mb_substr') ? '<span class="ok">[str]yes[/str]</span>' : '<span class="warning">[str]no[/str]</span>'; 
            
            $fs_free=disk_free_space(ROOT_DIR);
            $fs_free=$fs_free ? number_format($fs_free/1048576,2,'.',',').' Mb' : '[str]unknown[/str]';
            $fs_total=disk_total_space(ROOT_DIR);
            $fs_total=$fs_total ? number_format($fs_total/1048576,2,'.',',').' Mb' : '[str]unknown[/str]';
            
            
            $files_html='<ul class="info">';
       //     $files_html.='<li><label>[strf]Total disk space[/strf]:</label> '.$fs_total.'</li>';
       //     $files_html.='<li><label>[strf]Free disk space[/strf]:</label> '.$fs_free.'</li>';
            $files_html.='<li><label>[strf]%uploads% directory is writable[/strf]:</label> '.(is_writable(ROOT_DIR.'/uploads') ? '<span class="ok">[str]yes[/str]</span>' : '<span class="warning">[str]no[/str]</span>').'</li>';
            $files_html.='<li><label>[strf]%cache% directory is writable[/strf]:</label> '.(is_writable(ROOT_DIR.'/cache') ? '<span class="ok">[str]yes[/str]</span>' : '<span class="warning">[str]no[/str]</span>').'</li>';
            $files_html.='<li><label>[strf]%log% directory is writable[/strf]:</label> '.(is_writable(ROOT_DIR.'/log') ? '<span class="ok">[str]yes[/str]</span>' : '<span class="warning">[str]no[/str]</span>').'</li>';
            $files_html.='<li><label>[strf]%settings.php% is protected from writing[/strf]:</label> '.(!is_writable(ROOT_DIR.'/settings.php') ? '<span class="ok">[str]yes[/str]</span>' : '<span class="warning">[str]no[/str]</span>').'</li>';
            $files_html.='<li><label>[strf]%install.php% is deleted[/strf]:</label> '.(!file_exists(ROOT_DIR.'/install.php') ? '<span class="ok">[str]yes[/str]</span>' : '<span class="warning">[str]no[/str]</span>').'</li>';
            $files_html.='</ul>';
    
            $html.='<h1 class="tab">[str]Information[/str]</h1>';
            $html.='<fieldset class="welcome_block">';
            
            $html.='<ul class="info">';
            $html.='<li><label>[str]Your IP-address[/str]:</label> '.$this->session->ip.'</li>';
            $html.='<li><label>[str]PHP version[/str]:</label> '.phpversion().'</li>';
            $html.='<li><label>[str]Database version[/str]:</label> '.$this->db->version().'</li>';
            if (isset($_SERVER['SERVER_SOFTWARE']))
                $html.='<li><label>[str]Server[/str]:</label> '.$_SERVER['SERVER_SOFTWARE'].'</li>';
            $html.='<li><label>[str]mbstring support[/str]:</label> '.$mb_string.'</li>';
            $html.='<li><label>[strf]%register_globals% option disabled[/strf]:</label> '.(!ini_get('register_globals') ? '<span class="ok">[str]yes[/str]</span>' : '<span class="warning">[str]no[/str]</span>').'</li>';
            $html.='<li><label>[strf]%magic_quotes_gpc% option disabled[/strf]:</label> '.(!ini_get('magic_quotes_gpc') ? '<span class="ok">[str]yes[/str]</span>' : '<span class="warning">[str]no[/str]</span>').'</li>';
            $html.='<li><label>[str]GD library[/str]:</label> '.$gd_html.'</li>';
            $html.='<li><label>[str]File system[/str]:</label> '.$files_html.'</li>';
            $html.='<li>'.$this->constructor->link($this->config->admin_area.'/phpinfo','phpinfo()').'</li>';
            $html.='</ul>';
            
            $html.='</fieldset>';
            
            $html.='<h1 class="tab">[str]Today[/str]</h1>';
            $html.='<fieldset class="welcome_block">';
            
            $html.='<ul class="today_log">';
            $html.='<li><label>[str]Total queries[/str]:</label> '.$today_all_co.'</li>';
            $html.='<li><label>[str]Unique IP-addresses[/str]:</label> '.$today_ip_co.'</li>';
            $html.='<li><label>[str]Unique User-Agents[/str]:</label> '.$today_ua_co.'</li>';
            $html.='<li><label>[str]Unique Referers[/str]:</label> '.$today_referer_co.'</li>';
            $html.='<li><label>[str]Unique Visitors[/str]:</label> '.$today_hosts_co.'</li>';
            $html.='</ul>';
            /*
            $ip_limit=100;
            $ips=$log->get_today_records_info('ip');
            if ($ips)
            {
                $html.='<h3>[str]IP-addresses[/str]:</h3>';
                $html.='<ul class="today_log">';
                $co=0;
                while($row=$this->db->fetch_object($ips))
                {
                    if ($co>$ip_limit) break;
                    $html.='<li>'.$row->ip.'</li>';
                    $co++;
                }
                $html.='</ul>';
            }
            
            $ua_limit=100;
            $uas=$log->get_today_records_info('ua');
            if ($uas)
            {
                $html.='<h3>[str]User-Agents[/str]:</h3>';
                $html.='<ul class="today_log">';
                $co=0;
                while($row=$this->db->fetch_object($uas))
                {
                    if ($co>$ua_limit) break;
                    $html.='<li>'.$row->ua.'</li>';
                    $co++;
                }
                $html.='</ul>';
            }
            */
            
            $_html='';
            $ref_limit=100;
            $refs=$log->get_today_records_info('referer');
            if ($refs)
            {
                $_html.='<ul class="today_log">';
                $co=0;
                while($row=$this->db->fetch_object($refs))
                {
                    if ($co>$ref_limit) break;
                    $_html.='<li>'.urldecode($row->referer).'</li>';
                    $co++;
                }
                $_html.='</ul>';
                
                if ($co>0) {
					$html.='<h3>[str]Referers[/str]:</h3>'.$_html;
				}
            }

            $html.='</fieldset>';
        }
        
        return $html;
    }
    
    function news() {
		if (!@ini_get('allow_url_fopen')) {
			die($this->locale->get_string('Failed to load news from remote server. Check your PHP configuration: ').'allow_url_fopen='.@ini_get('allow_url_fopen'));
		}
		
		$url='http://sawanna.org/news.php?host='.HOST.'&mode=sawanna&lang='.$this->locale->current;
		
		$news=@file_get_contents($url);
		if (!$news) {
			die($this->locale->get_string('Server not responding. Try later'));
		}
		
		// not secure
		//$news=strip_tags($news,'<h1><h2><h3><h4><h5><h6><ul><li><a><p><div><span><img><br>');
		$news=strip_tags($news);
		
		die($news);
	}
    
    function config_area()
    {
        if (!$this->access->has_access('su')) return;
        
        $html='<h1 class="tab">[str]Configuration[/str]</h1>';
        
        $clean_url_checked=$this->config->clean_url ? array('checked'=>'checked','disabled' => 'disabled') : array('disabled' => 'disabled');
        $clean_url_checker='<script language="JavaScript">$(document).ready(function(){$.ajax({url:\''.SITE.'/ping&sid='.$this->session->id.'\',success:function(data){if (data.length>0 && data=="'.version().'") {$("#clean_url").get(0).disabled=false; $("#clean_url_support").addClass("message"); $("#clean_url_support").get(0).innerHTML="[str]Clean URLs are supported by your server[/str]";} else {$("#clean_url").get(0).disabled=true; $("#clean_url_support").addClass("error"); $("#clean_url_support").get(0).innerHTML="[str]Clean URLs are not supported by your server[/str]";};},error:function(xhr, ajaxOptions, thrownError){$("#clean_url").get(0).disabled=true; $("#clean_url_support").addClass("error"); $("#clean_url_support").get(0).innerHTML="[str]Clean URLs are not supported by your server[/str]";}});});</script>';
        $gzip_out_checked=$this->config->gzip_out ? array('checked'=>'checked') : array();
        $gzip_out_supported=function_exists('gzencode') ? true : false;
        $session_ip_bind_checked=$this->config->session_ip_bind ? array('checked'=>'checked','onchange'=>"alert('[str]Your current session will be lost[/str]')") : array('onchange'=>"alert('[str]Your current session will be lost[/str]')");
        $session_ua_bind_checked=$this->config->session_ua_bind ? array('checked'=>'checked','onchange'=>"alert('[str]Your current session will be lost[/str]')") : array('onchange'=>"alert('[str]Your current session will be lost[/str]')");
        $accept_language_checked=$this->config->accept_language ? array('checked'=>'checked') : array();
        $trans_sid_checked=$this->config->trans_sid ? array('checked'=>'checked') : array();
        $cache_checked=$this->config->cache ? array('checked'=>'checked') : array();
        $cache_css_checked=$this->config->cache_css ? array('checked'=>'checked') : array();
        $cache_supported=(file_exists(ROOT_DIR.'/'.quotemeta($this->cache->root_dir)) && is_dir(ROOT_DIR.'/'.quotemeta($this->cache->root_dir)) && is_writable(ROOT_DIR.'/'.quotemeta($this->cache->root_dir))) ? true : false;
        $ext_checked=$this->config->path_extension ? array('checked'=>'checked') : array();
        $md5_checked=$this->config->md5_notify ? array('checked'=>'checked') : array();
        $detect_mobile_checked=$this->config->detect_mobile ? array('checked'=>'checked') : array();
        $captcha_checked=$this->config->captcha ? array('checked'=>'checked') : array();
        $news_checked=$this->config->admin_load_news ? array('checked'=>'checked') : array();
        
        $event=array(
            'url' => 'ping',
            'job' => 'version',
            'argument' => 1
        );
        $this->event->register($event);

        $fields=array(
            'clean_url' => array(
                'type' => 'checkbox',
                'name' => 'clean_url',
                'id' => 'clean_url',
                'title' => '[str]Clean URL[/str]',
                'description' => '<div id="clean_url_support">[str]checking[/str]...</div>',
                'attributes' => $clean_url_checked
                ),
             'clean_url_checker' => array(
                'type' => 'html',
                'value' => $clean_url_checker
             ),
             'gzip_out' => array(
                'type' => 'checkbox',
                'name' => 'gzip_out',
                'id' => 'gzip_out',
                'title' => '[str]GZIP-compress[/str]',
                'description' => $gzip_out_supported ? '<div class="message">[str]GZIP supported by your server[/str]</div>' : '<div class="error">[str]GZIP not supported by your server[/str]</div>',
                'attributes' => $gzip_out_supported ? $gzip_out_checked : array('disabled' => 'disabled')
             ),
             'session_ip_bind' => array(
                'type' => 'checkbox',
                'name' => 'session_ip_bind',
                'id' => 'session_ip_bind',
                'title' => '[str]Bind IP-address to session ID[/str]',
                'description' => '[str]User IP-address will be checked if enabled[/str]',
                'attributes' => $session_ip_bind_checked
             ),
             'session_ua_bind' => array(
                'type' => 'checkbox',
                'name' => 'session_ua_bind',
                'id' => 'session_ua_bind',
                'title' => '[str]Bind User-Agent to session ID[/str]',
                'description' => '[str]User client will be checked if enabled[/str]',
                'attributes' => $session_ua_bind_checked
             ),
              'session_life_time' => array(
                'type' => 'text',
                'name' => 'session_life_time',
                'id' => 'session_life_time',
                'title' => '[str]Session life time[/str]',
                'description' => '([str]in minutes[/str])',
                'value' => $this->config->session_life_time,
                'attributes' => array('style' => 'width:50px')
                ),
               'log_period' => array(
                'type' => 'text',
                'name' => 'log_period',
                'id' => 'log_period',
                'title' => '[str]Log period[/str]',
                'description' => '([str]in days[/str])',
                'value' => $this->config->log_period,
                'attributes' => array('style' => 'width:50px')
                ),
               'accept_language' => array(
                'type' => 'checkbox',
                'name' => 'accept_language',
                'id' => 'accept_language',
                'title' => '[str]Accept browser language[/str]',
                'description' => '[str]Default language will be used, if language not supported[/str]',
                'attributes' => $accept_language_checked
                ),
               'detect_mobile' => array(
                'type' => 'checkbox',
                'name' => 'detect_mobile',
                'id' => 'detect_mobile',
                'title' => '[str]Detect mobile devices[/str]',
                'description' => '[str]Mobile version will be shown for mobile devices[/str]',
                'attributes' => $detect_mobile_checked
                ),
               'date_format' => array(
                'type' => 'text',
                'name' => 'date_format',
                'id' => 'date_format',
                'title' => '[str]Date format[/str]',
                'description' => '[str]PHP date format[/str]',
                'value' => $this->config->date_format,
                'attributes' => array('style' => 'width:50px')
                ),
               'trans_sid' => array(
                'type' => 'checkbox',
                'name' => 'trans_sid',
                'id' => 'trans_sid',
                'title' => '[str]Enable `trans_sid`[/str]',
                'description' => '[str]Session ID will be added to GET variable, if Cookies are not enabled[/str]',
                'attributes' => $trans_sid_checked
                ),
               'path_extension' => array(
                'type' => 'checkbox',
                'name' => 'path_extension',
                'id' => 'path_extension',
                'title' => '[str]Add file extension to URL pathes[/str]',
                'description' => '[str]Clean URL enabled required[/str]',
                'attributes' => $ext_checked
                ),
                'cache' => array(
                'type' => 'checkbox',
                'name' => 'cache',
                'id' => 'cache',
                'title' => '[str]Enable caching[/str]',
                'description' => $cache_supported ? '<div class="message">[str]Cache directory is writable[/str]</div>' : '<div class="error">[str]Cache directory is not writable[/str]</div>',
                'attributes' => $cache_supported ? $cache_checked : array('disabled' => 'disabled')
                ),
                'cache_css' => array(
                'type' => 'checkbox',
                'name' => 'cache_css',
                'id' => 'cache_css',
                'title' => '[str]Enable CSS caching[/str]',
                'description' => $cache_supported ? '<div>[str]Modules` CSS files will be cached into one file, if enabled[/str]</div>' : '<div class="error">[str]Cache directory is not writable[/str]</div>',
                'attributes' => $cache_supported ? $cache_css_checked : array('disabled' => 'disabled')
                ),
                'captcha' => array(
                'type' => 'checkbox',
                'name' => 'captcha',
                'id' => 'captcha',
                'title' => '[str]Enable CAPTCHA[/str]',
                'description' => '[str]GD2 need to be installed[/str]',
                'attributes' => $captcha_checked
                ),
                'md5_notify' => array(
                'type' => 'checkbox',
                'name' => 'md5_notify',
                'id' => 'md5_notify',
                'title' => '[str]Notify on modified files[/str]',
                'description' => '[str]Need cron jobs to be set[/str]',
                'attributes' => $md5_checked
                ),
                'admin_load_news' => array(
                'type' => 'checkbox',
                'name' => 'admin_load_news',
                'id' => 'admin_load_news',
                'title' => '[str]Load news[/str]',
                'description' => '[str]Load latest news from sawanna.org[/str]',
                'attributes' => $news_checked
                ),
               'separator' => array(
                'type' => 'html',
                'value' => '<div class="separator"></div>' 
                ),
               'submit' => array(
                 'type' => 'submit',
                 'value' => '[str]Save[/str]'
               )
        );
       
        $html.=$this->constructor->form('configuration',$fields,'process_admin_area_requests',$this->config->admin_area.'/config',$this->config->admin_area.'/config','','CSawanna');
       
        return $html;
    }
    
    function config_save()
    {
        $errors=array();
        $failed=array();
        $config=array();
        
        if (isset($_POST['clean_url']) && $_POST['clean_url']=='on') $config['clean_url']=1; else $config['clean_url']=0;
        if (isset($_POST['gzip_out']) && $_POST['gzip_out']=='on') $config['gzip_out']=1; else $config['gzip_out']=0;
        if (isset($_POST['session_ip_bind']) && $_POST['session_ip_bind']=='on') $config['session_ip_bind']=1; else $config['session_ip_bind']=0;
        if (isset($_POST['session_ua_bind']) && $_POST['session_ua_bind']=='on') $config['session_ua_bind']=1; else $config['session_ua_bind']=0;
        if (isset($_POST['session_life_time']))
        {
            if (is_numeric($_POST['session_life_time']))
            {
                if ($_POST['session_life_time']<1)
                {
                    $errors[]='[str]Session life time is too short[/str]';
                    $failed[]='session_life_time';
                } else $config['session_life_time']=floor($_POST['session_life_time']);
            } else 
            {
                $errors[]='[str]Invalid session life time[/str]';
                $failed[]='session_life_time';
            }
        }
        if (isset($_POST['log_period']))
        {
            if (is_numeric($_POST['log_period']))
            {
                if ($_POST['log_period']<1)
                {
                    $errors[]='[str]Log period is too short[/str]';
                    $failed[]='log_period';
                } else $config['log_period']=floor($_POST['log_period']);
            } else 
            {
                $errors[]='[str]Invalid log period[/str]';
                $failed[]='log_period';
            }
        }
        if (isset($_POST['accept_language']) && $_POST['accept_language']=='on') $config['accept_language']=1; else $config['accept_language']=0;
        if (isset($_POST['detect_mobile']) && $_POST['detect_mobile']=='on') $config['detect_mobile']=1; else $config['detect_mobile']=0;
        if (isset($_POST['trans_sid']) && $_POST['trans_sid']=='on') $config['trans_sid']=1; else $config['trans_sid']=0;
        if (isset($_POST['path_extension']) && $_POST['path_extension']=='on') $config['path_extension']=1; else $config['path_extension']=0;
        if (isset($_POST['cache']) && $_POST['cache']=='on') $config['cache']=1; else $config['cache']=0;
        if (isset($_POST['cache_css']) && $_POST['cache_css']=='on') $config['cache_css']=1; else $config['cache_css']=0;
        if (isset($_POST['md5_notify']) && $_POST['md5_notify']=='on') $config['md5_notify']=1; else $config['md5_notify']=0;
        if (isset($_POST['captcha']) && $_POST['captcha']=='on') $config['captcha']=1; else $config['captcha']=0;
        if (!empty($_POST['date_format'])) $config['date_format']=strip_tags($_POST['date_format']); else $config['date_format']='d.m.Y';
        if (isset($_POST['admin_load_news']) && $_POST['admin_load_news']=='on') $config['admin_load_news']=1; else $config['admin_load_news']=0;
        
        $this->module->set_option('css_update',time(),0);
        
        if (!$this->config->set($config,$this->db)) 
        {
            $errors[]=('[str]Configuration could not be saved[/str]');
            $failed[]='configuration';
        }
        
        $_errors=sawanna_error();
        if (!empty($_errors)) $errors=array_merge($errors,$_errors);
        
        $this->cache->clear_all();
        
        if (!empty($errors)) return array('[str]Error[/str]:<ul><li>'.implode('</li><li>',$errors).'</li></ul>',$failed);
        else return array('[str]Configuration saved[/str]');
    }
    
    function info_area()
    {
        if (!$this->access->has_access('su')) return;
        
        $html='';
        
        $offline_checked=$this->config->site_offline ? array('checked'=>'checked') : array();
        
        $site_names=array();
        $site_slogans=array();
        $site_offlines=array();
        $site_titles=array();
        $site_keywords=array();
        $site_description=array();
        
        foreach ($this->config->languages as $code=>$name)
        {
            if (is_array($this->config->site_name))
            {
                if (isset($this->config->site_name[$code])) $site_names[$code]=$this->config->site_name[$code];
                else $site_names[$code]='';
            } else 
            {
                $site_names[$code]=$this->config->site_name;
            }
            
            if (is_array($this->config->site_slogan))
            {
                if (isset($this->config->site_slogan[$code])) $site_slogans[$code]=$this->config->site_slogan[$code];
                else $site_slogans[$code]='';
            } else 
            {
                $site_slogans[$code]=$this->config->site_slogan;
            }
            
            if (is_array($this->config->site_offline_message))
            {
                if (isset($this->config->site_offline_message[$code])) $site_offlines[$code]=$this->config->site_offline_message[$code];
                else $site_offlines[$code]='';
            } else 
            {
                $site_offlines[$code]=$this->config->site_offline_message;
            }
            
            if (is_array($this->config->default_meta_title))
            {
                if (isset($this->config->default_meta_title[$code])) $site_titles[$code]=$this->config->default_meta_title[$code];
                else $site_titles[$code]='';
            } else 
            {
                $site_titles[$code]=$this->config->default_meta_title;
            }
            
            if (is_array($this->config->default_meta_keywords))
            {
                if (isset($this->config->default_meta_keywords[$code])) $site_keywords[$code]=$this->config->default_meta_keywords[$code];
                else $site_keywords[$code]='';
            } else 
            {
                $site_keywords[$code]=$this->config->default_meta_keywords;
            }
            
            if (is_array($this->config->default_meta_description))
            {
                if (isset($this->config->default_meta_description[$code])) $site_description[$code]=$this->config->default_meta_description[$code];
                else $site_description[$code]='';
            } else 
            {
                $site_description[$code]=$this->config->default_meta_description;
            }
        }
        
        $co=0;
        foreach ($this->config->languages as $code=>$name)
        {
            $fields['infotab_'.$code]=array(
                'type'=>'html',
                'value'=>'<h1 class="tab">[str]Information[/str] ('.$name.')</h1>'
                );

            $fields['site_name-'.$code] = array(
                    'type' => 'text',
                    'name' => 'site_name_'.$code,
                    'id' => 'site_name_'.$code,
                    'title' => '[str]Site name[/str]',
                    'description' => '&nbsp;',
                    'value' => $site_names[$code],
                    'attributes' => array('style'=>'width:400px')
             );
            $fields['site_slogan-'.$code] = array(
                    'type' => 'textarea',
                    'name' => 'site_slogan_'.$code,
                    'id' => 'site_slogan_'.$code,
                    'title' => '[str]Site slogan[/str]',
                    'description' => '&nbsp;',
                    'value' => $site_slogans[$code],
                    'attributes' => array('style'=>'width:400px','cols'=>'40','rows'=>'2')
             );
            $fields['site_offline_message-'.$code] = array(
                    'type' => 'textarea',
                    'name' => 'site_offline_message_'.$code,
                    'id' => 'site_offline_message_'.$code,
                    'title' => '[str]Offline message[/str]',
                    'description' => '&nbsp;',
                    'value' => $site_offlines[$code],
                    'attributes' => array('style'=>'width:400px','cols'=>'40','rows'=>'4')
             );
             $fields['site_title-'.$code] = array(
                    'type' => 'text',
                    'name' => 'site_title_'.$code,
                    'id' => 'site_title_'.$code,
                    'title' => '[str]Page default title[/str]',
                    'description' => '[str]Site name will be used if not specified[/str]',
                    'value' => $site_titles[$code],
                    'attributes' => array('style'=>'width:400px')
             );
             $fields['site_keywords-'.$code] = array(
                    'type' => 'text',
                    'name' => 'site_keywords_'.$code,
                    'id' => 'site_keywords_'.$code,
                    'title' => '[str]Page default keywords[/str]',
                    'description' => '&nbsp;',
                    'value' => $site_keywords[$code],
                    'attributes' => array('style'=>'width:400px')
             );
             $fields['site_description-'.$code] = array(
                    'type' => 'textarea',
                    'name' => 'site_description_'.$code,
                    'id' => 'site_description_'.$code,
                    'title' => '[str]Page default meta description[/str]',
                    'description' => '&nbsp;',
                    'value' => $site_description[$code],
                    'attributes' => array('style'=>'width:400px','cols'=>'40','rows'=>'4')
             );
             
            $fields['submit_'.$code]=array(
            'type' => 'submit',
            'value' => '[str]Save[/str]',
            'attributes' => array('class'=>'submit')
        );
             
        }
        
        $fields['infotab_settings']=array(
                'type'=>'html',
                'value'=>'<h1 class="tab">[str]Settings[/str]</h1>'
                );
                  
        $fields['site_logo'] = array(
                'type' => 'text',
                'name' => 'site_logo',
                'id' => 'site_logo',
                'title' => '[str]Site logo[/str]',
                'description' => '[strf]relative path (example: %uploads/images/logo.gif%)[/strf]',
                'value' => $this->config->site_logo,
                'attributes' => array('style'=>'width:400px','cols'=>'40','rows'=>'4')
         );
         
        $fields['front_page_path'] = array(
                'type' => 'text',
                'name' => 'front_page_path',
                'id' => 'front_page_path',
                'title' => '[str]Front page path[/str]',
                'description' => '[strf]Enter page internal path (example: %page/sub-page%)[/strf]',
                'value' => $this->config->front_page_path,
                'attributes' => array('style'=>'width:400px')
         );
         
        $fields['send_mail_from'] = array(
                'type' => 'email',
                'name' => 'send_mail_from',
                'id' => 'send_mail_from',
                'title' => '[str]Send E-Mail messages from this address[/str]',
                'description' => '[strf]Enter valid E-Mail address from which all E-Mail messages should be sent[/strf]',
                'value' => $this->config->send_mail_from,
                'required' => 1,
                'attributes' => array('style'=>'width:400px')
         );
         
        $fields['send_mail_from_name'] = array(
                'type' => 'text',
                'name' => 'send_mail_from_name',
                'id' => 'send_mail_from_name',
                'title' => '[str]Send E-Mail messages from this name[/str]',
                'description' => '[strf]Enter name that should be shown in `From` header of all outgoing E-Mail messages[/strf]',
                'value' => $this->config->send_mail_from_name,
                'required' => 1,
                'attributes' => array('style'=>'width:400px')
         );
        
        $fields['site_offline'] = array(
                'type' => 'checkbox',
                'name' => 'site_offline',
                'id' => 'site_offline',
                'title' => '[str]Site offline[/str]',
                'description' => '[str]offline message will be shown[/str]',
                'attributes' => $offline_checked
         );
         
        $fields['submit_settings']=array(
            'type' => 'submit',
            'value' => '[str]Save[/str]',
            'attributes' => array('class'=>'submit')
        );
        
        $html.=$this->constructor->form('information',$fields,'process_admin_area_requests',$this->config->admin_area.'/info',$this->config->admin_area.'/info','','CSawanna');
       
        return $html;
    }
    
    function config_info_save()
    {
        $config=array();
        $errors=array();
        $failed=array();
        
        if (count($this->config->languages)<2)
        {
            if (isset($_POST['site_name_'.$this->config->language])) $config['site_name']=htmlspecialchars($_POST['site_name_'.$this->config->language]);
            if (isset($_POST['site_slogan_'.$this->config->language])) $config['site_slogan']=htmlspecialchars($_POST['site_slogan_'.$this->config->language]);
            if (isset($_POST['site_offline_message_'.$this->config->language])) $config['site_offline_message']=htmlspecialchars($_POST['site_offline_message_'.$this->config->language]);
            if (isset($_POST['site_title_'.$this->config->language])) $config['default_meta_title']=htmlspecialchars($_POST['site_title_'.$this->config->language]);
            if (isset($_POST['site_keywords_'.$this->config->language])) $config['default_meta_keywords']=htmlspecialchars($_POST['site_keywords_'.$this->config->language]);
            if (isset($_POST['site_description_'.$this->config->language])) $config['default_meta_description']=htmlspecialchars($_POST['site_description_'.$this->config->language]);
        } else 
        {
            $site_names=array();
            $site_slogans=array();
            $site_offlines=array();
            $site_titles=array();
            $site_keywords=array();
            $site_descriptions=array();
            
            foreach ($this->config->languages as $code=>$name)
            {
                if (isset($_POST['site_name_'.$code])) $site_names[$code]=htmlspecialchars($_POST['site_name_'.$code]);
                if (isset($_POST['site_slogan_'.$code])) $site_slogans[$code]=htmlspecialchars($_POST['site_slogan_'.$code]);
                if (isset($_POST['site_offline_message_'.$code])) $site_offlines[$code]=htmlspecialchars($_POST['site_offline_message_'.$code]);
                if (isset($_POST['site_title_'.$code])) $site_titles[$code]=htmlspecialchars($_POST['site_title_'.$code]);
                if (isset($_POST['site_keywords_'.$code])) $site_keywords[$code]=htmlspecialchars($_POST['site_keywords_'.$code]);
                if (isset($_POST['site_description_'.$code])) $site_descriptions[$code]=htmlspecialchars($_POST['site_description_'.$code]);
            }
            
            $config['site_name']=$site_names;
            $config['site_slogan']=$site_slogans;
            $config['site_offline_message']=$site_offlines;
            $config['default_meta_title']=$site_titles;
            $config['default_meta_keywords']=$site_keywords;
            $config['default_meta_description']=$site_descriptions;
        }
        
        if (isset($_POST['site_logo'])) 
        {
            if (!empty($_POST['site_logo']))
            {
                if (strpos($_POST['site_logo'],'http://')===0) $_POST['site_logo']=substr($_POST['site_logo'],7);
                if (strpos($_POST['site_logo'],'https://')===0) $_POST['site_logo']=substr($_POST['site_logo'],8);
                if (strpos($_POST['site_logo'],'/')===0) $_POST['site_logo']=substr($_POST['site_logo'],1);
                
                if (strpos($_POST['site_logo'],'..')===false && file_exists(ROOT_DIR.'/'.$_POST['site_logo'])) $config['site_logo']=$_POST['site_logo'];
                else 
                {
                    $errors[]='[str]Invalid site logo specified[/str]';
                    $failed[]='site_logo';
                }
            } else $config['site_logo']='';
        }
        
        if (isset($_POST['site_offline']) && $_POST['site_offline']=='on') $config['site_offline']=1; else $config['site_offline']=0;
        
        if (isset($_POST['front_page_path']))
        {
            if (substr($_POST['front_page_path'],0,1)=='/') $_POST['front_page_path']=substr($_POST['front_page_path'],1);
            if (substr($_POST['front_page_path'],-1)=='/') $_POST['front_page_path']=substr($_POST['front_page_path'],0,sawanna_strlen($_POST['front_page_path'])-1);
            if (!empty($_POST['front_page_path']))
            {
                $path=$this->navigator->get($_POST['front_page_path'],null,false,false);
                if (!empty($path)) $config['front_page_path']=$_POST['front_page_path'];
                else 
                {
                    $errors[]='[str]Invalid front page path specified[/str]';
                    $failed[]='front_page_path';
                }
            } else 
            {
                $config['front_page_path']='';
            }
        }
        
        $config['send_mail_from']=$_POST['send_mail_from'];
        $config['send_mail_from_name']=$_POST['send_mail_from_name'];
        
        if (!$this->config->set($config,$this->db)) 
        {
            $errors[]='[str]Information could not be saved[/str]';
            $failed[]='information';
        }
        
        $_errors=sawanna_error();
        if (!empty($_errors)) $errors=array_merge($errors,$_errors);
        
        if (!empty($errors)) return array('[str]Error[/str]:<ul><li>'.implode('</li><li>',$errors).'</li></ul>',$failed);
        else return array('[str]Information saved[/str]');
    }
    
    function themes_area()
    {
        if (!$this->access->has_access('su')) return;
        
        $html='<h1 class="tab">[str]Theme[/str]</h1>';
        
        $fields=array();
        
        $themes=$this->tpl->get_installed_themes();
        
        foreach ($themes as $tname=>$tinfo)
        {
            
            $checked=$this->config->theme==$tname ? array('checked'=>'checked') : array();
            $screenshot=file_exists(ROOT_DIR.'/'.quotemeta($this->tpl->root_dir).'/'.quotemeta($tname).'/screenshot.jpg') ? '<img src="'.SITE.'/'.quotemeta($this->tpl->root_dir).'/'.quotemeta($tname).'/screenshot.jpg" width="300" height="225" />' : '';
            
            $fields['theme-'.$tname]=array(
                'type' => 'radio',
                'name' => 'theme',
                'title' => $tinfo['name'].'<div class="screenshot"><label for="theme-'.$tname.'">'.$screenshot.'</label></div>',
                'description' => $tinfo['description'],
                'value' => $tname,
                'attributes' => $checked
                );
        }
        
        $fields['submit']=array(
            'type' => 'submit',
            'value' => '[str]Save[/str]'
        );
        
        $html.=$this->constructor->form('themes',$fields,'process_admin_area_requests',$this->config->admin_area.'/themes',$this->config->admin_area.'/themes','','CSawanna');
       
        $html.='<h1 class="tab">[str]Settings[/str]</h1>';
        
        if (function_exists('theme_settings'))
        {
            $html.=theme_settings();
        } else
        {
            $html.='<em>[str]Your current theme is not configurable[/str]</em>';
        }
       
        return $html;
    }
    
    function config_save_theme()
    {
        if (!isset($_POST['theme'])) return array('[str]Invalid theme[/str]',array('theme'));
        
        $themes=$this->tpl->get_installed_themes();
        if (!array_key_exists($_POST['theme'],$themes)) 
            return array('[str]Invalid theme[/str]',array('theme'));

        $_errors=sawanna_error();
        if (!empty($_errors)) $errors=array_merge($errors,$_errors);
        
        if ($this->config->set(array('theme'=>$_POST['theme']),$this->db))
        {
            $this->module->set_option('css_update',time(),0);
            return array('[str]Theme saved[/str]');
        }
        else return array('[str]Theme could not be saved[/str]',array('theme'));
    }
    
    function languages_area()
    {
        if (!$this->access->has_access('su')) return;
        
        $html='<h1 class="tab">[str]Languages[/str]</h1>';
        $html.='<div id="languages-header">';
        $html.='<div>&nbsp;</div>';
        $html.='<div>[str]Enabled[/str]</div>';
        $html.='<div>[str]Default[/str]</div>';
        $html.='<div>[str]Admin-Area[/str]</div>';
        $html.='<div>[str]Native name[/str]</div>';
        $html.='<div>[str]Order[/str]</div>';
        $html.='</div>';
        
        $orders=array();
        $order=0;
        foreach ($this->config->languages as $code=>$name)
        {
            $order++;
            $orders[$code]=$order;
        }
        
        $langs=$this->locale->get_installed_languages();
       
        $co=0;
        foreach ($langs as $lname=>$linfo)
        {
            $default=$this->config->language==$lname ? array('checked'=>'checked') : array();
            $admin_default=$this->config->admin_area_language==$lname ? array('checked'=>'checked') : array();
            $enabled=array_key_exists($lname,$this->config->languages) ? array('checked'=>'checked') : array();
            $native=array_key_exists($lname,$this->config->languages) ? $this->config->languages[$lname] : $linfo['name'];
            
            $co++;
            if ($co%2===0)
            {
                $fields['line-begin-'.$lname]=array(
                    'type'=>'html',
                    'value' => '<div class="line mark highlighted">'
                );
            } else 
            {
                $fields['line-begin-'.$lname]=array(
                    'type'=>'html',
                    'value' => '<div class="line">'
                );
            }
            
            $fields['name-'.$lname]=array(
                'type'=>'html',
                'value' => '<div class="title">'.$linfo['name'].'</div>'
            );
            
            $fields['lang-enabled-'.$lname]=array(
                'type' => 'checkbox',
                'name' => 'language-enabled-'.$lname,
                'attributes' => $enabled
                );
            
            $fields['lang-'.$lname]=array(
                'type' => 'radio',
                'name' => 'language',
                'value' => $lname,
                'attributes' => !empty($enabled) ? $default : array('disabled'=>'disabled')
                );
                
            $fields['lang-admin-'.$lname]=array(
                'type' => 'radio',
                'name' => 'admin-language',
                'value' => $lname,
                'attributes' => !empty($enabled) ? $admin_default : array('disabled'=>'disabled')
                );

              $fields['lang-native-'.$lname]=array(
                'type' => 'text',
                'name' => 'language-native-'.$lname,
                'default' => $native,
                'attributes' => !empty($enabled) ? array('style'=>'width:90px'): array('disabled'=>'disabled','style'=>'width:90px')
                );
              
              $fields['lang-order-'.$lname]=array(
                'type' => 'text',
                'name' => 'language-order-'.$lname,
                'default' => isset($orders[$lname]) ? $orders[$lname] : '-',
                'attributes' => !empty($enabled) ? array('style'=>'width:90px') : array('disabled'=>'disabled','style'=>'width:90px')
                );
                
               $fields['separator-'.$lname]=array(
                'type'=>'html',
                'value' => '<div class="separator"></div>'
                );
                
                $fields['line-end-'.$lname]=array(
                    'type'=>'html',
                    'value' => '</div>'
                );
        }
        
        $fields['submit']=array(
            'type' => 'submit',
            'value' => '[str]Save[/str]'
        );
        
        $html.=$this->constructor->form('languages',$fields,'process_admin_area_requests',$this->config->admin_area.'/languages',$this->config->admin_area.'/languages','','CSawanna',0);
       
        return $html;
    }
    
    function config_save_languages()
    {
        $langs=$this->locale->get_installed_languages();
        
        $languages=array();
        $default='';
        $admin_area='';
        $natives=array();
        $orders=array();
        
        foreach ($langs as $lname=>$linfo)
        {
            if (!isset($_POST['language-enabled-'.$lname]) || $_POST['language-enabled-'.$lname]!='on') continue;
            
            if ($_POST['language']==$lname) $default=$_POST['language'];
            if (isset($_POST['admin-language']) && $_POST['admin-language']==$lname) $admin_area=$_POST['admin-language'];
            if (isset($_POST['language-native-'.$lname]))
            {
                $natives[$lname]=htmlspecialchars($_POST['language-native-'.$lname]);
            } else 
            {
                $natives[$lname]=$linfo['name'];
            }
            if (isset($_POST['language-order-'.$lname]) && is_numeric($_POST['language-order-'.$lname]))
            {
                $orders[$lname]=$_POST['language-order-'.$lname];
            } else 
            {
                $orders[$lname]=0;
            }
        }
       
        if (!empty($default))
        {
            $this->config->set(array('language'=>$default),$this->db);
        } elseif(!array_key_exists($this->config->language,$natives))
        {
            return array('[str]Cannot disable default language[/str]',array('language'));
        }
        
        if (!empty($admin_area))
        {
            $this->config->set(array('admin_area_language'=>$admin_area),$this->db);
            $this->locale->current=$admin_area;
        } elseif(!array_key_exists($this->config->admin_area_language,$natives))
        {
            return array('[str]Cannot disable language assigned to admin-area[/str]',array('language'));
        }
        
        asort($orders);
        foreach ($orders as $lang=>$order)
        {
            $languages[$lang]=$natives[$lang];
        }

        if (!empty($languages) && is_array($languages))
        {
            $refresh=false;
            foreach ($languages as $lcode=>$lname)
            {
                if (!array_key_exists($lcode,$this->config->languages))
                {
                    $refresh=true;
                    break;
                }
            }
            
            $this->config->set(array('languages'=>$languages),$this->db);
            $this->locale->languages=$languages;
            
            if ($refresh) $this->module->install_all_modules_languages();
        }
        
        return array('[str]Language configuration saved[/str]');
    }
    
    function md5_area()
    {
        if (!$this->access->has_access('su')) return;
        
        $html='<h1 class="tab">[str]System check[/str]</h1>';
        
        if (!is_writable(ROOT_DIR.'/'.quotemeta($this->file->root_dir)))
        {
            $html.='<div class="error">[strf]Warning: %uploads% directory is not writable[/strf]</div><div class="clear" style="height:20px"></div>';
        }
        
        if (!file_exists($this->file->md5file))
        {
            $html.='<div class="error">[str]MD5 sum is not calculated yet[/str]</div><div class="clear" style="height:20px"></div>';
        }

        if (file_exists($this->file->md5file))
        {
            $errors=array();
            $this->file->check_md5sum(ROOT_DIR,$errors);
            
            if (!empty($errors))
            {
                $html.='<div class="warning">[str]Warning: following files are modified:[/str]</div>';
                $html.='<ul class="md5-error">';
                foreach ($errors as $filename=>$hash)
                {
                    $html.='<li>'.$filename.'</li>';
                }
                $html.='</ul>';
            } else 
            {
                $html.='<div class="message">[strf]System is okay %:)%[/strf]</div>';
            }
            
            chmod($this->file->md5file,0400);
            
            $fhashes=array();
            $f=fopen($this->file->md5file,'r');
            if (!$f) return false;
            
            while (!feof($f)) 
            {
                $fhash_str=fgets($f);
                if (strpos($fhash_str,' ')===false) continue;
                
                list($fhash,$fname)=explode(' ',trim($fhash_str));
                $fhashes[$fname]=$fhash;
            }
            
            fclose($f);
            
            chmod($this->file->md5file,0000);
        
            $html.='<div class="md5-list"><ul class="md5">';
            
            ksort($fhashes);
            
            $co=1;
            foreach ($fhashes as $filename=>$hash)
            {
                $class=($co%2==0) ? ' class="highlighted"' : '';
                
                if (array_key_exists($filename,$errors))
                    $html.='<li class="error"><span class="counter">'.$co.'</span><label>'.$hash.'</label>'.$filename.'</li>';
                else
                    $html.='<li'.$class.'><span class="counter">'.$co.'</span><label>'.$hash.'</label>'.$filename.'</li>';
                    
                $co++;
            }
            
            $html.='</ul></div>';
        }
        
        $fields=array(
            'submit' => array(
                'type' => 'submit',
                'value' => '[str]Calculate MD5 sum[/str]',
                'attributes' => array('class'=>'button')
            )
        );
            
        $html.=$this->constructor->form('md5-calc',$fields,'process_admin_area_requests',$this->config->admin_area.'/md5',$this->config->admin_area.'/md5-calc','','CSawanna',0);
           
        return $html;
    }
    
    function md5_calc()
    {
        if (!$this->file->md5sum(ROOT_DIR))
        {
            return array('[str]Failed to calculate MD5 sum[/str]','md5');
        } else 
        {
            return array('[str]MD5 sum calculated[/str]');
        }
    }
    
     function user_area()
    {
        if (!$this->access->check_su()) return;
        
        $html='<h1 class="tab">[str]Super User Profile[/str]</h1>';

        $fields=array(
            'su_user' => array(
                'type' => 'text',
                'title' => '[str]Login[/str]',
                'description' => '([strf]number of characters required: %4%[/strf])',
                'value' => $this->config->su['user'],
                'required' => true
            ),
           'su_password' => array(
                'type' => 'password',
                'title' => '[str]Password[/str]',
                'description' => '([strf]number of characters required: %6%[/strf])'
           ),
           'su_password_retype' => array(
                'type' => 'password',
                'title' => '[str]Retype password[/str]',
                'description' => '[str]please enter your new password again[/str]'
            ),
            'su_email' => array(
                'type' => 'text',
                'title' => '[str]E-Mail[/str]',
                'description' => '[str]please enter your e-mail address[/str]',
                'value' => $this->config->su['email'],
                'required' => true
            ),
            'su_password_current' => array(
                'type' => 'password',
                'title' => '[str]Current password[/str]',
                'description' => '([strf]please enter your current password[/strf])',
                'required' => true
            ),
            'submit' => array(
                 'type' => 'submit',
                 'value' => '[str]Save[/str]'
            )
        );
        
        $html.=$this->constructor->form('su_profile',$fields,'process_admin_area_requests',$this->config->admin_area.'/user',$this->config->admin_area.'/user','','CSawanna');
    
        return $html;
    }
    
    function config_save_user()
    {
         if (!$this->access->check_su())
        {
            return array('[str]Permission denied[/str]',array('su_user','su_password','su_email'));
        }
        
        $config=array();
        $errors=array();
        $failed=array();
        
        $su=$this->config->su;
        
        if ($this->access->pwd_hash($_POST['su_password_current']) == $this->config->su['password'])
        {
            if (!valid_login($_POST['su_user']))
            {
                $errors[]='[str]Invalid login specified[/str]';
                $failed[]='su_user';
            } else 
            {
                $su['user']=$_POST['su_user'];
                $this->session->set('su_user',$su['user']);
            }
            
            if (!valid_email($_POST['su_email']))
            {
                $errors[]='[str]Invalid email specified[/str]';
                $failed[]='su_email';
            } else 
            {
                $su['email']=$_POST['su_email'];
            }
            
            if (!empty($_POST['su_password']))
            {
                if (!isset($_POST['su_password_retype']) || $_POST['su_password_retype'] != $_POST['su_password'])
                {
                    $errors[]='[str]Passwords do not match[/str]';
                    $failed[]='su_password';
                    $failed[]='su_password_retype';
                } elseif (!valid_password($_POST['su_password']))
                {
                    $errors[]='[str]Invalid password specified[/str]';
                    $failed[]='su_password';
                } else 
                {
                    $su['password']=$this->access->pwd_hash($_POST['su_password']);
                    $this->session->set('su_password',$su['password']);
                }
            }
            
            $config['su']=$su;
            
            if (!$this->config->set($config,$this->db)) 
            {
                $errors[]='[str]Super User Profile could not be saved[/str]';
                $failed[]='su_user';
                $failed[]='su_password';
                $failed[]='su_email';
            }
            
            $_errors=sawanna_error();
            if (!empty($_errors)) $errors=array_merge($errors,$_errors);
            
            if (!empty($errors)) return array('[str]Error[/str]:<ul><li>'.implode('</li><li>',$errors).'</li></ul>',$failed);
            else return array('[str]Super User Profile saved[/str]');
        } else 
        {
            return array('[str]Incorrect current password specified[/str]',array('su_password_current'));
        }
    }
    
    function modules_area()
    {
        if (!$this->access->has_access('su')) return;
        
        $fields=array();
        $html='<h1 class="tab">[str]Modules[/str]</h1>';

        $enabled=array();
        $installed=array();
        foreach ($this->module->modules as $id=>$module_data)
        {
            $installed[$module_data->root]=$module_data;
            if ($module_data->enabled) $enabled[$module_data->root]=$module_data;
        }
        
        $modules=array();
        $packages=array();
        $co=0;
        
        list($packages,$modules)=$this->module->get_existing_packs(false);
        
        asort($packages);
        $packs=array();
        foreach ($packages as $module_dir=>$package)
        {
            if (!in_array($package,$packs))
            {
                $packs[]=$package;
                $fields['package-'.$package]=array(
                    'type'=>'html',
                    'value' => '<div class="package section-highlight">[str]'.$package.'[/str]</div>'
                );
            }
            
            $module=$modules[$module_dir];
			
			$changed='$(\'#state-informer\').html(\'*[str]Changes not saved[/str]\').css(\'display\',\'block\');if(typeof(informed)==\'undefined\'){$(\'#submit\').focus();};informed=1;';
            
            $module_enabled=array_key_exists($module_dir,$enabled) ? array('checked'=>'checked','onclick'=>$changed) : array('onclick'=>$changed);
            $module_installed=array_key_exists($module_dir,$installed) ? array('checked'=>'checked','onclick'=>$changed) : array('onclick'=>$changed);
            if (!empty($module_enabled['checked'])) $module_installed['disabled']='disabled';
            
            if (empty($module_enabled['checked'])) $this->module->preload_module_language($module['root']);
            
            $required=$this->module->get_dependent_modules($module['root']);
            $dependent=$this->module->get_required_modules($module['depend']);
            
            $required_str=!empty($required) ? '<div class="rel"><strong>[str]Required by[/str]:</strong> '.implode(', ',$required).'</div>' : '';
            $dependent_str=!empty($dependent) ? '<div class="rel"><strong>[str]Depends from[/str]:</strong> '.implode(', ',$dependent).'</div>' : '';
            
            $version=!empty($module['version']) ? '<div class="version">[str]version[/str]: '.$module['version'].'</div>' : '';
            
            if (!empty($module_enabled))
            {
                if (!empty($required)) $module_enabled['disabled']='disabled';
            } else 
            {
                foreach ($dependent as $droot=>$depend)
                {
                    if (!array_key_exists($droot,$enabled))
                    {
                        $module_enabled['disabled']='disabled';
                        break;
                    }
                }
            }
            
            $co++;
            if ($co%2===0)
            {
                $fields['line-begin-'.$module_dir]=array(
                    'type'=>'html',
                    'value' => '<div class="line mark highlighted">'
                );
            } else 
            {
                $fields['line-begin-'.$module_dir]=array(
                    'type'=>'html',
                    'value' => '<div class="line">'
                );
            }
            
            $fields['module-name-'.$module_dir]=array(
                'type'=>'html',
                'value' => '<div class="title"><div class="caption"><strong>'.$module['title'].'</strong></div><div class="name"><strong>[str]Directory[/str]:</strong> '.$module['root'].'</div>'.$version.'<p>[str]Description[/str]: '.$module['description'].'</p>'.$dependent_str.$required_str.'</div>'
            );
            
            $fields['module-enabled-'.$module_dir]=array(
                'type' => 'checkbox',
                'name' => 'module-enabled-'.$module_dir,
                'attributes' => !empty($module_installed) ? $module_enabled : array('disabled'=>'disabled')
                );
                
            $fields['module-installed-'.$module_dir]=array(
                'type' => 'checkbox',
                'name' => 'module-installed-'.$module_dir,
                'attributes' => $module_installed
                );
           
           $fields['separator-'.$module_dir]=array(
                'type'=>'html',
                'value' => '<div class="separator"></div>'
                );     
                
            $fields['line-end-'.$module_dir]=array(
                    'type'=>'html',
                    'value' => '</div>'
                );
        }
       
        if (!empty($fields))
        {
			$fields['informer']=array(
				'type'=>'html',
				'value'=>'<div id="state-informer"></div>'
			);
				
            $fields['submit']=array(
                     'type' => 'submit',
                     'value' => '[str]Save[/str]'
                );
                
                $html.='<div id="modules-header">';
                $html.='<div class="title">[str]Module[/str]</div>';
                $html.='<div>[str]Enabled[/str]</div>';
                $html.='<div>[str]Installed[/str]</div>';
                $html.='</div>';
        
            $html.=$this->constructor->form('modules',$fields,'process_admin_area_requests',$this->config->admin_area.'/modules',$this->config->admin_area.'/modules','','CSawanna',0);
        } else 
        {
            $html.='<div class="information">[str]No modules found[/str]</div>';
        }
        
        return $html;
    }
    
    function modules_save()
    {
        $errors=array();
        $messages=array();
        
        $enabled=array();
        $installed=array();
        foreach ($this->module->modules as $id=>$module_data)
        {
            $installed[$module_data->root]=$module_data;
            if ($module_data->enabled) $enabled[$module_data->root]=$module_data;
        }
        
        $dir=opendir(ROOT_DIR.'/'.$this->module->root_dir);
        while(($module_dir=readdir($dir)) !== false)
        {
            if (!is_dir(ROOT_DIR.'/'.$this->module->root_dir.'/'.$module_dir) || $module_dir=='.' || $module_dir=='..') continue;
            
            $data=$this->module->get_module_data($module_dir);
            if (!$data || !is_array($data)) continue;
            
            list($module,$widgets,$schemas,$options,$permissions,$paths,$admin_area_handler)=$data;
            
            $install_disabled=array_key_exists($module_dir,$enabled) ? true : false;
            $enable_disabled=array_key_exists($module_dir,$installed) ? false : true;
            
            $required=$this->module->get_dependent_modules($module['root']);
            $dependent=$this->module->get_required_modules($module['depend']);
            
            if (!empty($required) && !$enable_disabled) $enable_disabled=true;

            if (!$install_disabled && isset($_POST['module-installed-'.$module_dir]) && $_POST['module-installed-'.$module_dir]=='on' && !array_key_exists($module_dir,$installed))
            {
                if($this->module->install_module($module_dir)) 
                {
                    $this->module->refresh_options();
                    $messages[]='[strf]Module %`'.$module['title'].'`% installed[/strf]';
                } else 
                    $errors[]='[strf]Could not install %`'.$module['title'].'`% module[/strf]';
            } elseif (!$install_disabled && (!isset($_POST['module-installed-'.$module_dir]) || $_POST['module-installed-'.$module_dir]!='on') && array_key_exists($module_dir,$installed))
            {
                 if($this->module->uninstall_module($module_dir)) 
                 {
                    $this->module->refresh_options();
                    $messages[]='[strf]Module %`'.$module['title'].'`% uninstalled[/strf]';
                 } else 
                    $errors[]='[strf]Could not uninstall %`'.$module['title'].'`% module[/strf]';
            }
            
            if (!$enable_disabled && isset($_POST['module-enabled-'.$module_dir]) && $_POST['module-enabled-'.$module_dir]=='on' && !array_key_exists($module_dir,$enabled))
            {
                if($this->module->activate(array2object($module),'module')) 
                    $messages[]='[strf]Module %`'.$module['title'].'`% activated[/strf]';
                else 
                    $errors[]='[strf]Could not activate %`'.$module['title'].'`% module[/strf]';
            } elseif (!$enable_disabled && (!isset($_POST['module-enabled-'.$module_dir]) || $_POST['module-enabled-'.$module_dir]!='on') && array_key_exists($module_dir,$enabled))
            {
                 if($this->module->deactivate(array2object($module),'module')) 
                    $messages[]='[strf]Module %`'.$module['title'].'`% deactivated[/strf]';
                else 
                    $errors[]='[strf]Could not deactivate %`'.$module['title'].'`% module[/strf]';
            }
        }
        
        $this->cache->clear_all();
        
        $_errors=sawanna_error();
        if (!empty($_errors)) $errors=array_merge($errors,$_errors);

        if (!empty($errors)) return array('[str]Error[/str]:<ul><li>'.implode('</li><li>',$errors).'</li><li>[str]Check module dependencies[/str]</li></ul>','modules');
        elseif (!empty($messages)) return array('[str]Information[/str]:<ul><li>'.implode('</li><li>',$messages).'</li></ul>');
    }

    function widgets_area($module_id=0)
    {
        if (!$this->access->has_access('manage widgets')) return;
        
        $fields=array();
        
        $html='<h1 class="tab">[str]Widgets[/str]</h1>';

        //$_widgets=$this->module->get_widgets_table();
        $_widgets=$this->module->get_module_widgets_table_by_name();
        
        if ($module_id<=0) {
			$widgets_limit=self::WIDGETS_LIMIT;
			foreach ($this->module->modules as $mid=>$module)
			{
				$widgets=$this->module->get_widgets_by_module_id($_widgets,$mid,false);
				
				if (!empty($widgets))
				{
					$fields['module-'.$mid]=array(
						'type'=>'html',
						'value' => '<div class="module section-highlight">'.$this->constructor->link($this->config->admin_area.'/widgets/'.$mid,$module->title).'</div>'
					);
				}
				
				$fields['widgets-wrapper-start-for-'.$mid]=array(
					'type'=>'html',
					'value' => '<div id="widgets-for-'.$mid.'">'
				);
				
				$this->widgets_pager($mid,$widgets,0,$widgets_limit,$fields);
			   
				$fields['widgets-wrapper-end-for-'.$mid]=array(
					'type'=>'html',
					'value' => '</div>'
				);
				
				if (!empty($co))
				{
					$fields['mod-separator-'.$mid]=array(
						'type'=>'html',
						'value' => '<div class="widgets-module-separator"></div>'
					);
				}
		   }
	   } elseif (isset($this->module->modules[$module_id])) {
		   $widgets_limit=50;
		   
		   $module=$this->module->modules[$module_id];
		   $mid=$module_id;
		   $widgets=$this->module->get_widgets_by_module_id($_widgets,$mid,false);
				
			if (!empty($widgets))
			{
				$fields['module-'.$mid]=array(
					'type'=>'html',
					'value' => '<div class="module section-highlight">'.$module->title.'</div>'
				);
			}
			
			$fields['widgets-wrapper-start-for-'.$mid]=array(
				'type'=>'html',
				'value' => '<div id="widgets-for-'.$mid.'">'
			);
			
			$this->widgets_pager($mid,$widgets,0,$widgets_limit,$fields);
		   
			$fields['widgets-wrapper-end-for-'.$mid]=array(
				'type'=>'html',
				'value' => '</div>'
			);
			
			if (!empty($co))
			{
				$fields['mod-separator-'.$mid]=array(
					'type'=>'html',
					'value' => '<div class="widgets-module-separator"></div>'
				);
			}
	   }
    
        if (!empty($fields))
        {
			/*
            $fields['submit']=array(
                     'type' => 'submit',
                     'value' => '[str]Save[/str]'
                );
            */
            $html.='<div id="widgets-header">';
            $html.='<div class="title">[str]Widget[/str]</div>';
            $html.='<div>[str]Enabled[/str]</div>';
            $html.='</div>';
            
            $html.=$this->constructor->form('widgets',$fields,'process_admin_area_requests',$this->config->admin_area.'/widgets',$this->config->admin_area.'/widgets','','CSawanna',false);
        } else 
        {
            $html.='<div class="information">[str]No widgets installed[/str]</div>';
        }
        
        $html.='<script language="JavaScript">';
        $html.='function widgets_load(mid,start){$.post(\''.$this->constructor->url($this->config->admin_area.'/widgets-load').'\',{module:mid,offset:start,limit:'.$widgets_limit.'},function(data){$(\'#widgets-for-\'+mid).html(data);});}';
        $html.='</script>';
        
        return $html;
    }
    
    function widgets_pager($mid,&$widgets,$start,$limit,&$fields) {
		$total=0;
		
		foreach ($widgets as $id=>$widget)
		{
			if (!$widget->module) continue;
			if ($widgets[$id]->fix) continue;
			
			$total++;
		}
		
		if (!$total) return; 
		
		$co=0;
		foreach ($widgets as $id=>$widget)
		{
			if (!$widget->module) continue;
			if ($widgets[$id]->fix) continue;
			
			$co++;
			
			if ($co<=$start*$limit) continue;
			
			if ($co>$limit+$start*$limit) break;
			
			if ($co%2===0)
			{
				$fields['line-begin-'.$id]=array(
					'type'=>'html',
					'value' => '<div class="line mark highlighted">'
				);
			} else 
			{
				$fields['line-begin-'.$id]=array(
					'type'=>'html',
					'value' => '<div class="line">'
				);
			}
			
			$fields['widget-name-'.$id]=array(
				'type'=>'html',
				'value' => '<div class="title"><div class="caption"><strong>'.$widget->title.'</strong></div><div class="name"><strong>[str]Name[/str]:</strong> '.$widget->name.'</div><p>[str]Description[/str]: '.$widget->description.'</p></div>'
			);
			
			$onclick='if(this.checked){w_state=1;}else{w_state=0;};$.post(\''.$this->constructor->url($this->config->admin_area.'/widget-switch').'\',{wid:'.$id.',state:w_state});';
			
			$fields['widget-enabled-'.$id]=array(
				'type' => 'checkbox',
				'name' => 'widget-enabled-'.$id,
				'attributes' => ($widget->enabled) ? array('checked'=>'checked','onclick'=>$onclick) : array('onclick'=>$onclick)
				);
				
			$fields['widgets-configure-'.$id]=array(
				'type' => 'html',
				'value' => '<div class="configure">'.$this->constructor->link($this->config->admin_area.'/widget/'.$id,'[str]Configure[/str]','button').'</div>'
			);
				
			$fields['separator-'.$id]=array(
				'type'=>'html',
				'value' => '<div class="separator"></div>'
				);     
				
			$fields['line-end-'.$id]=array(
					'type'=>'html',
					'value' => '</div>'
				);
		}
		
		if ($total > $limit) {
			$fields['pager-'.$id]=array(
				'type'=>'html',
				'value'=>$this->constructor->ajax_pager($start,$limit,$total,$mid,'widgets_load')
			);
		}
	}
    
    function widgets_load() {
		if (!$this->access->has_access('manage widgets')) die();
        
		if (empty($_POST['module']) || !is_numeric($_POST['module'])) die();
		
		$mid=(int) $_POST['module'];
		$start=isset($_POST['offset']) ? ((int) $_POST['offset']) : 0;
		$limit=isset($_POST['limit']) ? ((int) $_POST['limit']) : self::WIDGETS_LIMIT;
		
		$fields=array();
		
		//$_widgets=$this->module->get_widgets_table();
		$_widgets=$this->module->get_module_widgets_table_by_name();
		$widgets=$this->module->get_widgets_by_module_id($_widgets,$mid,false);
            
        $this->widgets_pager($mid,$widgets,$start,$limit,$fields);
        
        $output=$this->constructor->form('widgets',$fields,'process_admin_area_requests',$this->config->admin_area.'/widgets',$this->config->admin_area.'/widgets','','CSawanna',false,'',true);
        $this->locale->translate($output);
        header('Content-Type: text/html; charset=utf-8'); 
        echo $output;
        die();
	}
	
	// deprecated
    function widgets_save()
    {
		return array('[str]Error[/str]:<ul><li>Deprecated</li></ul>','widgets');
        
        $errors=array();
        $messages=array();

        $widgets=$this->module->get_widgets_table();
        
        foreach($widgets as $id=>$widget)
        {
            if (!$widget->module) continue;
            if ($widgets[$id]->fix) continue;
            
            if (isset($_POST['widget-enabled-'.$id]) && $_POST['widget-enabled-'.$id]=='on' && !$widget->enabled)
            {
                if($this->module->activate($widget,'widget')) 
                    $messages[]='[strf]Widget %'.$widget->name.'% activated[/strf]';
                else 
                    $errors[]='[strf]Could not activate %'.$widget->name.'% widget[/strf]';
            } elseif ((!isset($_POST['widget-enabled-'.$id]) || $_POST['widget-enabled-'.$id]!='on') && $widget->enabled)
            {
                 if($this->module->deactivate($widget,'widget')) 
                    $messages[]='[strf]Widget %'.$widget->name.'% deactivated[/strf]';
                else 
                    $errors[]='[strf]Could not deactivate %'.$widget->name.'% widget[/strf]';
            }
        }
        
        $this->cache->clear_all();
        
        $_errors=sawanna_error();
        if (!empty($_errors)) $errors=array_merge($errors,$_errors);
        
        if (!empty($errors)) return array('[str]Error[/str]:<ul><li>'.implode('</li><li>',$errors).'</li></ul>','widgets');
        elseif (!empty($messages)) return array('[str]Information[/str]:<ul><li>'.implode('</li><li>',$messages).'</li></ul>');
    }
    
    public function widget_switch() {
		if (!$this->access->has_access('manage widgets')) die();
        
		$widgets=$this->module->get_widgets_table();
        
        if (!isset($_POST['wid']) || !is_numeric($_POST['wid'])) die();
        
        $id=(int) $_POST['wid'];
        $state=isset($_POST['state']) ? ((int) $_POST['state']) : 0;
		
		if ($state) {
			$this->module->activate($widgets[$id],'widget');
		} else {
			$this->module->deactivate($widgets[$id],'widget');
		}
		
		die();
	}
    
    function widget_area($id)
    {
        if (!$this->access->has_access('manage widgets')) return;
        
        $fields=array();
        
        $html='<h1 class="tab">[str]Widget[/str]</h1>';

        $widgets=$this->module->get_widgets_table();
        
        if (array_key_exists($id,$widgets) && $widgets[$id]->module && !$widgets[$id]->fix)
        {

            $fields['widget-name-'.$id]=array(
                'type'=>'html',
                'value' => '<div class="title"><div class="caption"><strong>'.$widgets[$id]->title.'</strong></div><div class="name"><strong>[str]Name[/str]:</strong> '.$widgets[$id]->name.'</div><p>[str]Description[/str]: '.$widgets[$id]->description.'</p></div>'
            );
            
            $fields['widget-component-cap-separator']=array(
                'type'=>'html',
                'value'=>'<div class="wrapper"></div>'
            );
                
            $fields['widget-component-cap']=array(
                'type'=>'html',
                'value'=>'<div class="form_title">[str]Component[/str]:</div>'
            );
                
            $components=$this->tpl->getComponents();
            $components['auto']='Choose automatically';

            $widget_components=array();
            if (strpos($widgets[$id]->component,';')!==false)
            {
              $widget_components=explode(';',$widgets[$id]->component);
              foreach ($widget_components as $wnum=>$wc)
              {
                  if (empty($wc)) $widget_components[$wnum]='auto';
                  if (!empty($wc) && !array_key_exists($wc,$components)) $components[$wc]=$this->locale->get_string('Other').': '.$wc;
              }
            } else 
            {
              $widget_components[]=!empty($widgets[$id]->component) ? $widgets[$id]->component : 'auto';
              if (!empty($widgets[$id]->component) && !array_key_exists($widgets[$id]->component,$components)) $components[$widgets[$id]->component]=$this->locale->get_string('Other').': '.$widgets[$id]->component;
            }

            $co=0;
            foreach($components as $cname=>$ctitle)
            {
                if ($co%2===0)
                {
                    $fields['component-line-begin-'.$cname]=array(
                        'type'=>'html',
                        'value'=>'<div class="form_component_line">'
                    );
                } else 
                {
                    $fields['component-line-begin-'.$cname]=array(
                        'type'=>'html',
                        'value'=>'<div class="form_component_line mark highlighted">'
                    );
                }
            
                if ($cname!='auto')
                {
                    if (!in_array('auto',$widget_components))
                    {
                        $component_cheched=in_array($cname,$widget_components) ? array('checked'=>'checked','class'=>'tpl-component') : array('class'=>'tpl-component');
                    } else 
                    {
                        $component_cheched=array('disabled'=>'disabled','class'=>'tpl-component');
                    }
                    
                    $fields['widget-component-'.$cname]=array(
                        'type' => 'checkbox',
                        'name' => 'widget-component-'.$cname,
                        'title' => '[str]'.$ctitle.'[/str]',
                        'value' => $cname,
                        'attributes' => $component_cheched
                    ); 
                } else 
                {
                    $comp_auto_script="if (this.checked) {\$('.tpl-component').each(function(){\$(this).get(0).disabled=true;\$(this).get(0).checked=false;});} else {\$('.tpl-component').each(function(){\$(this).get(0).disabled=false;});}";
                    
                    $component_cheched=in_array($cname,$widget_components) ? array('checked'=>'checked') : array();
                    $component_cheched['onclick']=$comp_auto_script;
                    
                    $fields['widget-component-'.$cname]=array(
                        'type' => 'checkbox',
                        'name' => 'widget-component-'.$cname,
                        'title' => '[str]'.$ctitle.'[/str]',
                        'value' => $cname,
                        'attributes' => $component_cheched
                    ); 
                }
                
                $fields['component-line-end-'.$cname]=array(
                    'type'=>'html',
                    'value'=>'</div>'
                );
                
                $fields['component-separator-'.$cname]=array(
                    'type'=>'html',
                    'value'=>'<div class="separator"></div>'
                );
                
                $co++;
            }
            
            $fields['component-desc']=array(
                'type'=>'html',
                'value'=>'<div class="form_description">[str]If `Choose automatically` checked, component will be selected by your theme template.[/str]</div>'
            );
            
            $fields['widget-url-separator']=array(
                'type'=>'html',
                'value'=>'<div class="wrapper"></div>'
            );
                
            $path_desc='[strf]Use %*% simbol to show the widget on all pages of your website[/strf]';
            $path_desc.='<div class="examples_caption">[str]Examples[/str]:</div>';
            $path_desc.='<ul class="examples">';
            $path_desc.='<li>parent/child <label>[strf]Widget appears on the page with following address %http://yoursite.com/parent/child%[/strf]</label></li>';
            $path_desc.='<li>!parent/child <label>[strf]Widget do not appear on the page with address %http://yoursite.com/parent/child%[/strf]</label></li>';
            $path_desc.='<li>!parent/child<br />* <label>[strf]Widget appears on all pages except the page with following address %http://yoursite.com/parent/child%[/strf]</label></li>';
            $path_desc.='<li>[home] <label>[str]Widget appears only on your website home page[/str]</label></li>';
            $path_desc.='<li>parent/* <label>[strf]Widget appears on every child page with address that begins with %http://yoursite.com/parent%[/strf]</label></li>';
            $path_desc.='</ul>';
            
            $fields['widget-path-'.$id]=array(
                'type' => 'textarea',
                'name' => 'widget-path-'.$id,
                'title' => '[str]Widget URL[/str]',
                'description' => $path_desc,
                'value' => $widgets[$id]->path,
                'attributes' => array('rows'=> 5,'cols' => 40),
                'required' => true
            ); 
            
            $fields['widget-path-separator']=array(
                'type'=>'html',
                'value'=>'<div class="wrapper"></div>'
            );
            
            if (count($this->config->languages)>1)
            {
                $widget_langs=array('all'=>'[str]All languages[/str]') + $this->config->languages;
                $widget_lang='all';
                
                if (!empty($widgets[$id]->meta['language']) && array_key_exists($widgets[$id]->meta['language'],$this->config->languages)) $widget_lang=$widgets[$id]->meta['language'];

                $fields['widget-language']=array(
                        'type' => 'select',
                        'name' => 'widget-language',
                        'options' => $widget_langs,
                        'title' => '[str]Language[/str]',
                        'description' => '[str]Choose widget language[/str]',
                        'default' => $widget_lang,
                        'required' => false
                    ); 
                
                $fields['widget-language-separator']=array(
                    'type'=>'html',
                    'value'=>'<div class="wrapper"></div>'
                );
            }

            /*
            $fields['widget-weight-'.$id]=array(
                'type' => 'text',
                'name' => 'widget-weight-'.$id,
                'title' => '[str]Weight[/str]',
                'description' => '[str]Use decimal number to specify the weight of the widget. Lighter widgets will be shown first[/str]',
                'value' => $widgets[$id]->weight
            ); 
            */
            
            $middle_weight=isset($widgets[$id]->weight) ? $widgets[$id]->weight : 0;
            
            $fields['widget-weight-'.$id]=array(
                'type' => 'select',
                'options' => array_combine(range($middle_weight-30,$middle_weight+30,1),range($middle_weight-30,$middle_weight+30,1)),
                'name' => 'widget-weight-'.$id,
                'title' => '[str]Weight[/str]',
                'description' => '[str]Lighter widgets will be shown first[/str]',
                'attributes' => array('class'=>'slider'),
                'value' => $widgets[$id]->weight
            ); 
            
            
            $extra=$this->form->get_form_extra_fields('widget_create_form_extra_fields',$id);
        
            if (!empty($extra))
            {
                $fields=array_merge($fields,$extra);
            }
        
            $fields['submit']=array(
                     'type' => 'submit',
                     'value' => '[str]Save[/str]'
                );
            
            $html.=$this->constructor->form('widget',$fields,'process_admin_area_requests',$this->config->admin_area.'/widget/'.$id,$this->config->admin_area.'/widget/'.$id,'','CSawanna');
        } else 
        {
            $html.='<div class="information">[str]Widget not found[/str]</div>';
        }
        
        return $html;
    }
    
    function widget_save($id)
    {
        $errors=array();
        $message='';
        $failed=array();

        $widgets=$this->module->get_widgets_table();
        
        if (array_key_exists($id,$widgets) && $widgets[$id]->module && !$widgets[$id]->fix)
        {
            $widget_component='';
            
            $components=array();
            foreach($_POST as $pkey=>$pval)
            {
                if (!preg_match('/^widget-component-(.+)$/',$pkey,$matches)) continue;
                if ($pval!='on') continue;
                
                if ($matches[1]=='auto') $components[]='';
                else $components[]=$matches[1];
            }
            
            if (count($components)==0) $components[]='';
            
            if (count($components)==1) 
                $widgets[$id]->component=$components[0];
            else 
                $widgets[$id]->component=implode(';',$components);
            
            if (isset($_POST['widget-path-'.$id])) 
            {
                $path=strip_tags($_POST['widget-path-'.$id]);
                if (strpos($path,"\n")!==false)
                {
                    $path=str_replace("\r",'',$path);
                    $pathes=explode("\n",$path);
                    $_pathes=array();
                    foreach ($pathes as $_path)
                    {
                        if (!empty($_path)) $_pathes[]=trim($_path);
                    }
                    if (!empty($_pathes))
                    {
                        $path=implode("\n",$_pathes);
                    } else 
                    {
                        $path='';
                    }
                }
                
                if (!empty($path))
                    $widgets[$id]->path=trim($path);
                else 
                {
                    $errors[]='[str]Invalid widget URL[/str]';
                    $failed[]='widget-path-'.$id;
                }
            }
            
            if (!isset($widgets[$id]->meta)) $widgets[$id]->meta=array();
            
            if (count($this->config->languages)>1)
            {
                $widgets[$id]->meta['language']='';
                if (!empty($_POST['widget-language']) && array_key_exists($_POST['widget-language'],$this->config->languages)) $widgets[$id]->meta['language']=$_POST['widget-language'];
            }
            
            if (isset($_POST['widget-weight-'.$id]) && is_numeric($_POST['widget-weight-'.$id])) $widgets[$id]->weight=floor($_POST['widget-weight-'.$id]);
            
            if ($this->module->set(object2array($widgets[$id]),'widget'))
            {
                 // processing extra fields
                $res=$this->form->process_form_extra_fields('widget_create_form_extra_fields',$id);
                if (is_array($res) && !empty($res)) return $res;
        
                $message='[str]Widget saved[/str]';
            } else 
                $errors[]='[str]Widget could not be saved[/str]';
        } else 
        {
            $errors[]='[str]Widget not found[/str]';
        }
        
        $this->cache->clear_all();
        
        $_errors=sawanna_error();
        if (!empty($_errors)) $errors=array_merge($errors,$_errors);
        
        if (!empty($errors))
        {
            if (empty($failed)) $failed='widget';
            return array('[str]Error[/str]:<ul><li>'.implode('</li><li>',$errors).'</li></ul>',$failed);
        }
        elseif (!empty($message)) return array($message);
    }
    
    function permissions_area()
    {
        if (!$this->access->has_access('su')) return;
        
        $fields=array();
        
        $html='<h1 class="tab">[str]Default[/str]</h1>';

        $_perms=$this->access->get_access_table();
        
        $modules=array(0);
        foreach ($this->module->modules as $id=>$module)
        {
            $modules[$id]=$module;
        }
        
        foreach ($modules as $mid=>$module)
        {
            $perms=$this->access->get_module_permissions($_perms,$mid);

            if (!empty($perms))
            {
                $fields['module-'.$mid]=array(
                    'type'=>'html',
                    'value' => '<div class="module section-highlight">'.($mid ? $module->title : '[str]System[/str]').'</div>'
                );
            }
            
            $co=0;
            foreach($perms as $pkey=>$perm)
            {
                if ($co%2===0)
                {
                    $fields['component-line-begin-'.$pkey]=array(
                        'type'=>'html',
                        'value'=>'<div class="form_component_line">'
                    );
                } else 
                {
                    $fields['component-line-begin-'.$pkey]=array(
                        'type'=>'html',
                        'value'=>'<div class="form_component_line mark highlighted">'
                    );
                }
            
                $perm_attr=$perm->access ? array('checked'=>'checked','class'=>'perm') : array('class'=>'perm');
                $perm_attr['onclick']='check_su($(this).attr(\'rel\'))';
                $perm_attr['rel']=$perm->name;
                
                if ($perm->name!='su' && $this->access->get_current_access('su')) $perm_attr['disabled']='disabled';
                
                $fields['permission-'.$perm->id]=array(
                    'type' => 'checkbox',
                    'name' => 'permission-'.$perm->id,
                    'title' => $perm->title,
                    'description' => $perm->description,
                    'attributes' => $perm_attr
                ); 
                
                $fields['perm-script']=array(
                    'type' => 'html',
                    'value' => '<script language="JavaScript">function check_su(perm) {if (perm=="su"){$(".perm").each( function(){ if ($(this).attr("rel")!="su") { if ($("[rel=\'su\']").get(0).checked) {$(this).attr("disabled","disabled");} else {$(this).removeAttr("disabled");} } });}}</script>'
                );
               
                $fields['component-line-end-'.$pkey]=array(
                    'type'=>'html',
                    'value'=>'</div>'
                );
                
                $fields['component-separator-'.$pkey]=array(
                    'type'=>'html',
                    'value'=>'<div class="separator"></div>'
                );
                
                $co++;
            }
       }
    
        $fields['submit']=array(
                 'type' => 'submit',
                 'value' => '[str]Save[/str]'
            );
        
        $html.=$this->constructor->form('permissions',$fields,'process_admin_area_requests',$this->config->admin_area.'/permissions',$this->config->admin_area.'/permissions','','CSawanna');
    
        return $html;
    }
    
    function permissions_save()
    {
        $errors=array();

        $perms=$this->access->get_access_table();
        $_perms=array();
        
        foreach($_POST as $pkey=>$pval)
        {
            if (!preg_match('/^permission-(.+)$/',$pkey,$matches)) continue;
            if (!is_numeric($matches[1]) || !$this->access->perm_exists($matches[1])) 
            {
                $errors[]='[strf]Invalid permission ID: %'.htmlspecialchars($matches[1]).'%[/strf]';
                continue;
            }
            
            if ($pval=='on') 
            {
                $_perms[]=$matches[1];
            }
        }
        
        foreach ($perms as $pkey=>$perm)
        {
            $access=0;
            if (in_array($perm->id,$_perms)) $access=1;
            
            if (!$this->db->query("update pre_permissions set access='$1' where id='$2'",$access,$perm->id))
                $errors[]='[strf]Permission %'.htmlspecialchars($perm->title).'% could not be saved[/strf]';
        }
        
        $_errors=sawanna_error();
        if (!empty($_errors)) $errors=array_merge($errors,$_errors);
        
        if (!empty($errors))
        {
            return array('[str]Error[/str]:<ul><li>'.implode('</li><li>',$errors).'</li></ul>','permissions');
        }
        else return array('[str]Permissions saved[/str]');
    }
    
    function images_form()
    {
        if (!$this->access->has_access('su')) 
        {
            trigger_error('Permission denied',E_USER_ERROR);
            return ;
        }
        
        $fields=array();
        
         $fields['settings-tab']=array(
            'type' => 'html',
            'value' => '<h1 class="tab">[str]Image settings[/str]</h1>'
        );
        
        $enabled=$this->config->img_process;
        
        $fields['settings-enabled']=array(
            'type' => 'checkbox',
            'title' => '[strf]Enable %`image`% path[/strf]',
            'description' => '[strf]This path can be used for %JPEG, GIF, PNG% images quick load[/strf]. <div>[strf]Example: %image/image-alias%[/strf] ([strf]where %image-alias% is image file alias[/strf]).</div>',
            'attributes'=> $enabled ? array('checked'=>'checked') : array()
        );
        
        $cache_enabled=$this->config->img_process_cache;
        
        $cache_supported=(file_exists(ROOT_DIR.'/'.quotemeta($this->cache->root_dir)) && is_dir(ROOT_DIR.'/'.quotemeta($this->cache->root_dir)) && is_writable(ROOT_DIR.'/'.quotemeta($this->cache->root_dir))) ? true : false;
        
        $cache_checked=$cache_enabled ? array('checked'=>'checked') : array();
        
        $fields['settings-cache-enabled']=array(
            'type' => 'checkbox',
            'title' => '[strf]Enable caching for %`image`% path[/strf]',
            'description' => $cache_supported ? '[str]Can greatly reduce the system load[/str]' :  '<div class="error">[str]Cache directory is not writable[/str]</div>',
            'attributes'=> $cache_supported ? $cache_checked : array('disabled' => 'disabled')
        );
        
        $width=$this->config->img_process_width;
        
        $fields['settings-width']=array(
            'type' => 'text',
            'title' => '[str]Image width[/str]',
            'description' => '[str]Specify the image width[/str]',
            'default' => $width ? $width : '200'
        );
        
       $height=$this->config->img_process_height;
        
        $fields['settings-height']=array(
            'type' => 'text',
            'title' => '[str]Image height[/str]',
            'description' => '[str]Specify the image height[/str]',
            'default' => $height ? $height : '150'
        );
        
        $small_width=$this->config->img_process_small_width;
        
        $fields['settings-small-width']=array(
            'type' => 'text',
            'title' => '[str]Small image width[/str]',
            'description' => '[strf]This width will be used if %image/image-alias/small% path is requested[/strf]',
            'default' => $small_width ? $small_width : '120'
        );
        
       $small_height=$this->config->img_process_small_height;
        
        $fields['settings-small-height']=array(
            'type' => 'text',
            'title' => '[str]Small image height[/str]',
            'description' => '[strf]This height will be used if %image/image-alias/small% path is requested[/strf]',
            'default' => $small_height ? $small_height : '90'
        );
        
        $big_width=$this->config->img_process_big_width;
        
        $fields['settings-big-width']=array(
            'type' => 'text',
            'title' => '[str]Big image width[/str]',
            'description' => '[strf]This width will be used if %image/image-alias/big% path is requested[/strf]',
            'default' => $big_width ? $big_width : '400'
        );
        
       $big_height=$this->config->img_process_big_height;
        
        $fields['settings-big-height']=array(
            'type' => 'text',
            'title' => '[str]Big image height[/str]',
            'description' => '[strf]This height will be used if %image/image-alias/big% path is requested[/strf]',
            'default' => $big_height ? $big_height : '300'
        );
        
        $crop=$this->config->img_process_crop;
        
        $fields['settings-crop']=array(
            'type' => 'checkbox',
            'title' => '[str]Crop image[/str]',
            'description' => '[str]Specify whether to crop the image to keep its proportion[/str]',
            'attributes'=> $crop ? array('checked'=>'checked') : array()
        );
        
        $fields['submit']=array(
            'type'=>'submit',
            'value'=>'[str]Submit[/str]'
        );

        return $this->constructor->form('settings-form',$fields,'process_admin_area_requests',$this->config->admin_area.'/image/settings',$this->config->admin_area.'/image/settings','','CSawanna');
    
    }
    
    function images_save()
    {
        if (!$this->access->has_access('su')) 
        {
            return array('[str]Permission denied[/str]',array('image-settings'));
        }
        
        $config=array();
        
         if (isset($_POST['settings-enabled']) && $_POST['settings-enabled']=='on')
        {
            $config['img_process']=1;
        } else 
        {
            $config['img_process']=0;
        }
        
        if (isset($_POST['settings-cache-enabled']) && $_POST['settings-cache-enabled']=='on')
        {
            $config['img_process_cache']=1;
        } else 
        {
            $config['img_process_cache']=0;
        }
        
        if (isset($_POST['settings-width']) && is_numeric($_POST['settings-width']) && $_POST['settings-width']>0 && $_POST['settings-width']<1280)
        {
            $config['img_process_width']=ceil($_POST['settings-width']);
        } else 
        {
            $config['img_process_width']=200;
        }
        
        if (isset($_POST['settings-height']) && is_numeric($_POST['settings-height']) && $_POST['settings-height']>0 && $_POST['settings-height']<1024)
        {
            $config['img_process_height']=ceil($_POST['settings-height']);
        } else 
        {
            $config['img_process_height']=150;
        }
        
         if (isset($_POST['settings-small-width']) && is_numeric($_POST['settings-small-width']) && $_POST['settings-small-width']>0 && $_POST['settings-small-width']<1280)
        {
            $config['img_process_small_width']=ceil($_POST['settings-small-width']);
        } else 
        {
            $config['img_process_small_width']=120;
        }
        
        if (isset($_POST['settings-small-height']) && is_numeric($_POST['settings-small-height']) && $_POST['settings-small-height']>0 && $_POST['settings-small-height']<1024)
        {
            $config['img_process_small_height']=ceil($_POST['settings-small-height']);
        } else 
        {
            $config['img_process_small_height']=90;
        }
        
        if (isset($_POST['settings-big-width']) && is_numeric($_POST['settings-big-width']) && $_POST['settings-big-width']>0 && $_POST['settings-big-width']<1280)
        {
            $config['img_process_big_width']=ceil($_POST['settings-big-width']);
        } else 
        {
            $config['img_process_big_width']=400;
        }
        
        if (isset($_POST['settings-big-height']) && is_numeric($_POST['settings-big-height']) && $_POST['settings-big-height']>0 && $_POST['settings-big-height']<1024)
        {
            $config['img_process_big_height']=ceil($_POST['settings-big-height']);
        } else 
        {
            $config['img_process_big_height']=300;
        }
        
        if (isset($_POST['settings-crop']) && $_POST['settings-crop']=='on')
        {
            $config['img_process_crop']=1;
        } else 
        {
            $config['img_process_crop']=0;
        }
        
        $this->config->set($config,$this->db);

         // clearing cache
        $this->cache->clear_all_by_type('image');
        
        return array('[str]Settings saved[/str]');
    }   
    
    function clear_cache()
    {
        if ($this->cache->clear_all())
            return array('[str]Cached data deleted[/str]');
        else 
            return array('[str]Could not delete cached data[/str]','cache');
    }
    
    function log_area($start=0,$limit=10)
    {
        if (!$this->access->has_access('su')) return;

        include_once(ROOT_DIR.'/sawanna/log.inc.php');
        $log=new CLog($this->db,$this->config->log_period);
        
        $html='<h1 class="tab">[str]Access log[/str]</h1>';
        $html.='<div class="log">';
        
        $count=$log->get_records_count();
        $log->get_records($start*$limit,$limit);
        
        $co=0;
        while ($row=$this->db->fetch_object()) {
            if ($co%2)
                $html.='<div class="log-item marked highlighted">';
            else 
                $html.='<div class="log-item">';
                
           $html.='<ul>';
           $html.='<li><label>[str]Time[/str]: </label>'.date ('d.m.Y H:i:s',$row->tstamp).'</li>';
            $html.='<li><label>[str]IP-address[/str]: </label>'.htmlspecialchars($row->ip).'</li>';
            $html.='<li><label>[str]Query[/str]: </label>'.htmlspecialchars($row->query).'</li>';
            $html.='<li><label>[str]Referer[/str]: </label>'.htmlspecialchars(urldecode($row->referer)).'</li>';
            $html.='<li><label>[str]User-Agent[/str]: </label>'.htmlspecialchars($row->ua).'</li>';
            $html.='</ul>';

            $html.='</div>';
            $co++;
        }

        $html.='</div>';
        $html.=$this->constructor->pager($start,$limit,$count,$this->config->admin_area.'/log');
        
        $fields=array(
                'submit' => array(
                    'type' => 'submit',
                    'value' => '[str]Clear access log[/str]',
                    'attributes' => array('class'=>'button')
                )
            );
    
        $clear_form=$this->constructor->form('clear-logs',$fields,'process_admin_area_requests',$this->config->admin_area,$this->config->admin_area.'/clear-logs','','CSawanna',0);
        
        $html.='<div class="footer_block"><div class="inline-right-item">'.$clear_form.'</div></div>';
        
        return $html;
    }
    
    function clear_logs()
    {
        include_once(ROOT_DIR.'/sawanna/log.inc.php');
        $log=new CLog($this->db,$this->config->log_period);
        
        if ($log->clear_all())
            return array('[str]Access logs deleted[/str]');
        else 
            return array('[str]Could not delete access logs[/str]','log');
    }
    
     function errors_area()
    {
        if (!$this->access->has_access('su')) return;

        $html='<h1 class="tab">[str]Errors log[/str]</h1>';
        $html.='<div class="log">';
        
        $co=0;
        $dir=opendir(ROOT_DIR.'/log');
        if (!$dir) return $html;
        
        $err_dates=array();
        $errors=array();
        
        while (($file=readdir($dir))!==false) {
            if (is_dir($file) || !preg_match('/^([\d]{2}\.[\d]{2}\.[\d]{4})-error\.log$/',$file,$matches)) continue;
            
            $mem_limit=(int) ini_get('memory_limit');
            
            $_html='';
            @chmod(ROOT_DIR.'/log/'.$file,0600);
            
            $log_size=filesize(ROOT_DIR.'/log/'.$file);
            
            if (!$log_size) continue;
            
            if ($log_size > 1024*1024*3) 
            {
                $err_dates[]=$matches[1];
                $errors[$matches[1]]='<div class="log-item">File '.$file.' is too large : '.number_format($log_size/1024/1024,2).'M.</div>';
                continue;
            }
            
            $log=fopen(ROOT_DIR.'/log/'.$file,'rb');
           
           $_html.='<div class="log-item">';   
           $_html.='<label class="date">[str]Date[/str]: '.$matches[1].'</label>';
           $_html.='<ul>';
           
            $_co=0;
           while (!feof($log))
           {
               $_co++;
               $error=fgets($log,1024);
               if (empty($error)) continue;
               
               if (function_exists('memory_get_usage'))
               {
                   $memory=ceil(intval(memory_get_usage())/1024/1024);
                   if ($memory >= $mem_limit-4)
                   {
                       trigger_error('Errors list iteration: '.$_co.' Could not display all errors. Memory limit of '.$limit.'M exhausted.',E_USER_WARNING);
                       break;
                   }
               }
               
               if ($co%2)
                   $_html.='<li class="marked highlighted">'.$error.'</li>';
                else 
                   $_html.='<li>'.$error.'</li>';
            
                $co++;
           }
           $_html.='</ul>';
            
           fclose($log);
            @chmod(ROOT_DIR.'/log/'.$file,0000);
            
            $_html.='</div>';
            
            $err_dates[]=$matches[1];
            $errors[$matches[1]]=$_html;
            unset($_html);
        }
        
        if (empty($errors) || empty($err_dates)) $html.='<em>[str]No errors logged yet[/str]</em>';
        else 
        {
            rsort($err_dates);    
            foreach ($err_dates as $date)
            {
                if (!array_key_exists($date,$errors)) continue;
                
                $html.=$errors[$date];
                unset($errors[$date]);
            }

            $fields=array(
                'submit' => array(
                    'type' => 'submit',
                    'value' => '[str]Clear errors log[/str]',
                    'attributes' => array('class'=>'button')
                )
            );
    
            $clear_form=$this->constructor->form('clear-errors',$fields,'process_admin_area_requests',$this->config->admin_area,$this->config->admin_area.'/clear-errors','','CSawanna',0);
            
            $html.='<div class="footer_block"><div class="inline-right-item">'.$clear_form.'</div></div>';
        }
        
        $html.='</div>';
        return $html; 
    }
    
    function clear_errors()
    {
        if (empty_errors_log(true))
            return array('[str]Error logs deleted[/str]');
        else 
            return array('[str]Could not delete error logs[/str]','errors');
    }
    
    function phpinfo()
    {
        if (!$this->access->has_access('su')) return;
        
        ob_start();
        phpinfo();
        $info=ob_get_contents();
        ob_end_clean();
        
        $info=preg_replace('/^.*?<body>/s','',$info);
        $info=preg_replace('/<\/body>.*?$/s','',$info);
        $info=str_replace('width="600"','width="100%"',$info);
        
        return '<h1 class="tab">phpinfo()</h1><div id="phpinfo">'.$info.'</div>';
    }

    function process()
    {
        
        $this->add_scripts();
        
        $content='<div id="notification">';
        $content.=sawanna_show_errors(false);
        $content.=sawanna_show_messages(false);
        $content.='</div>';
        $content.="\n";
        
        $this->build_module_admin_area();
        
        switch(true)
        {
            case $this->arg=='config':
                echo $this->config_area();
                break;
            case $this->arg=='info':
                echo $this->info_area();
                break;
            case $this->arg=='themes':
                if (file_exists(ROOT_DIR.'/'.quotemeta($this->tpl->root_dir).'/'.quotemeta($this->config->theme).'/settings.php'))
                {
                    include_once(ROOT_DIR.'/'.quotemeta($this->tpl->root_dir).'/'.quotemeta($this->config->theme).'/settings.php');
                }
                echo $this->themes_area();
                break;
            case $this->arg=='languages':
                echo $this->languages_area();
                break;
            case $this->arg=='user':
                echo $this->user_area();
                break;
            case $this->arg=='md5':
                echo $this->md5_area();
                break;
            case $this->arg=='modules':
                echo $this->modules_area();
                break;
            case $this->arg=='widgets':
                echo $this->widgets_area();
                break;
            case preg_match('/^widgets\/([\d]+)$/',$this->arg,$matches):
                echo $this->widgets_area($matches[1]);
                break;
			case $this->arg=='widget-switch':
                echo $this->widget_switch();
                break;
			case $this->arg=='widgets-load':
                echo $this->widgets_load();
                break;
            case preg_match('/^widget\/([\d]+)$/',$this->arg,$matches):
                echo $this->widget_area($matches[1]);
                break;
           case $this->arg=='permissions':
                echo $this->permissions_area();
                break;
            case $this->arg=='log':
                echo $this->log_area();
                break;
            case preg_match('/^log\/([\d]+)$/',$this->arg,$matches):
                echo $this->log_area($matches[1]);
                break;
            case $this->arg=='errors':
                echo $this->errors_area();
                break;
            case $this->arg=='phpinfo':
                echo $this->phpinfo();
                break;
            case $this->arg=='image/settings':
                echo $this->images_form();
                break;
			case $this->arg=='news':
                echo $this->news();
                break;
            case $this->arg=='' || !ob_get_contents():
                 echo '<h1 class="tab">[str]Welcome to Sawanna CMS[/str]</h1>'.$this->welcome();
                 break;
        }
        
        $content.=$this->build_admin_header();
        $content.="\n";
        $content.=$this->build_adminbar();
        $content.="\n";
        $content.=$this->build_admin_area();
        $content.="\n";
        $content.=$this->build_admin_footer();
        $content.="\n";
        
        $this->output($content); 
        $this->locale->translate($content);
        
        header('Content-Type: text/html; charset=utf-8');
        
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: ".date('r',time()-3600)); 
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        
        echo $content;
    }
    
    function process_requests()
    {
        if ($this->arg!='widgets' && !preg_match('/^widget\/([\d]+)$/',$this->arg) && !$this->access->has_access('su')) return;
        if (($this->arg=='widgets' || preg_match('/^widget\/([\d]+)$/',$this->arg)) && !$this->access->has_access('manage widgets')) return;
        
        switch (true)
        {
            case $this->arg=='config':
                return $this->config_save();
                break;
            case $this->arg=='info':
                return $this->config_info_save();
                break;
            case $this->arg=='themes':
                 return $this->config_save_theme();
                 break;
            case $this->arg=='themes/settings':
                 if (file_exists(ROOT_DIR.'/'.quotemeta($this->tpl->root_dir).'/'.quotemeta($this->config->theme).'/settings.php'))
                 {
                    include_once(ROOT_DIR.'/'.quotemeta($this->tpl->root_dir).'/'.quotemeta($this->config->theme).'/settings.php');
                    if (function_exists('theme_settings_process'))
                    {
                        return theme_settings_process();
                    }
                 }
                 break;
            case $this->arg=='languages':
                 return $this->config_save_languages();
                 break;
            case $this->arg=='user':
                 return $this->config_save_user();
                 break;
            case $this->arg=='modules':
                return $this->modules_save();
                break;
            case $this->arg=='widgets':
                return $this->widgets_save();
                break;
            case preg_match('/^widget\/([\d]+)$/',$this->arg,$matches):
                return $this->widget_save($matches[1]);
                break;
            case $this->arg=='permissions':
                return $this->permissions_save();
                break;
            case $this->arg=='clear-cache':
                return $this->clear_cache();
                break;
            case $this->arg=='clear-errors':
                return $this->clear_errors();
                break;
            case $this->arg=='clear-logs':
                return $this->clear_logs();
                break;
            case $this->arg=='md5-calc':
                return $this->md5_calc();
                break;
            case $this->arg=='image/settings':
                return $this->images_save();
                break;
        }
    }
}
