<?php 
defined('SAWANNA') or die();

#################################################################
# INFO
# name : Default
# description : Тема по умолчанию с двумя шаблонами: 3-х колоночный на главной и 2-х колоночный на остальных страницах
#################################################################

/**
 * Returns a template name, which should be used  (required)
 * 
 * @param string $location
 * @param string $language
 * @param bool $is_mobile
 * 
 * @return string
 */
function tpl($location='',$language='',$is_mobile=false)
{
    global $sawanna;
    
    switch(true)
    {
		case $is_mobile:          // mobile devices
			$tpl= 'mobile';
			break;
        case $location == $sawanna->config->front_page_path:   //  home page
            $tpl= 'index';
            break;
        default:
            $tpl= 'main';
            break;
    }
    
    return $tpl;
}

/**
 * Returns a css filename, which should be used (required)
 * 
 * @param string $location
 * @param string $language
 * @param bool $is_mobile
 * 
 * @return string
 */
function css($location='',$language='',$is_mobile=false)
{
    switch(true)
    {
		case $is_mobile:
			$css='mobile';
			break;
        default:
            $css='main';
            break;
    }
    
    return $css;
}

/**
 * Returns an array with titles of all theme components (required)
 * 
 * @param string $tpl
 * 
 * @return array
 */
function components($tpl='')
{
    $components=array(
        'header' => 'Header',
        'parent-menu' => 'Parent menu',
        'child-menu' => 'Child menu',
        'left' => 'Left Sidebar',
        'left-single' => 'Left Sidebar single',
        'body' => 'Content',
        'right' => 'Right Sidebar',
        'footer' => 'Footer'
    );

    return $components;
}

/**
 * Returns an array with theme components (required)
 * All components used in theme templates should be defined here
 * 
 * @param string $tpl
 * 
 * @return array
 */
function tpl_components($tpl='')
{
    switch ($tpl)
    {
        case 'main':
                $components=array(
                    'header',
                    'parent-menu',
                    'child-menu',
                    'left-single',
                    'body',
                    'footer'
                );
                break;
        case 'mobile':
                $components=array(
                    'header',
                    'parent-menu',
                    'child-menu',
                    'body',
                    'footer'
                );
                break;
        default:
                $components=array(
                    'header',
                    'parent-menu',
                    'child-menu',
                    'left',
                    'body',
                    'right',
                    'footer'
                );
                break;
    }
    
    return $components;
}

/**
 * Returns a duty component name, according to location and current language (required)
 * This component will be used to display system information, such as errors and messages
 * 
 * @param string $location
 * @param string $location
 * @param bool $is_mobile
 * 
 * @return string
 */
function duty($location='',$language='',$is_mobile=false)
{
    switch(true)
    {
         default:
            $duty='body';
            break;
    }
    
    return $duty;
}

/**
 * Template function
 * Returns site logo
 * Default output will be used if removed
 * 
 * @param array $params
 * 
 * @return string
 */
function tpl_logo($params)
{
    global $sawanna;
    
    $logo='';
    
    if (!empty($params['site_logo']))
    {
        if (!defined('BASE_PATH'))
			$logo='<a href="'.$sawanna->constructor->url('[home]').'" title="'.$params['site_name'].'"><img src="'.SITE.'/'.$params['site_logo'].'" alt="'.$params['site_name'].'" border="0" /></a>';
        else
			$logo='<a href="'.$sawanna->constructor->url('[home]').'" title="'.$params['site_name'].'"><img src="'.BASE_PATH.'/'.$params['site_logo'].'" alt="'.$params['site_name'].'" border="0" /></a>';
	}
    return $logo;
}

/**
 * Template function
 * Returns site name
 * Default output will be used if removed
 * 
 * @param array $params
 * 
 * @return string
 */
function tpl_sitename($params)
{
    global $sawanna;
    
    $sitename='';
    
    if (!empty($params['site_name']))
        $sitename='<a href="'.$sawanna->constructor->url('[home]').'" title="'.$params['site_name'].'">'.$params['site_name'].'</a>';
        // shadow
        $sitename.='<span class="shadow-txt">'.$params['site_name'].'</span>';
        
    return $sitename;
}

/**
 * Template function
 * Returns site slogan
 * Default output will be used if removed
 * 
 * @param array $params
 * 
 * @return string
 */
function tpl_slogan($params)
{
    $slogan='';
    
    if (!empty($params['site_slogan']))
        $slogan='<p>'.$params['site_slogan'].'<p>';
        
    return $slogan;
}

/**
 * Template function
 * Returns theme language switcher
 * Default output will be used if removed
 * 
 * @param array $params
 * 
 * @return string
 */
function tpl_language_switcher($params)
{
    global $sawanna;
   
    if (defined('INSTALL')) return;
    if (count($sawanna->config->languages)<2) return;
    
    $html='<ul class="language-switcher">';
    foreach ($sawanna->config->languages as $lcode=>$lname)
    {
        $url='';
        
        $lang_prefix='';
    //  if ($sawanna->config->accept_language || $lcode != $sawanna->config->language)
        {
            $lang_prefix=$lcode;
        }
    
        $dest=$lang_prefix.(!empty($params['location']) && !empty($lang_prefix) ? '/'.$params['location'] : $params['location']);
        if (empty($dest)) 
        {
            $url=SITE;
        } elseif ($sawanna->config->clean_url)
        {
            $url=SITE.'/'.$dest;
        } else
        {
            $url=SITE.'/index.php?'.$sawanna->query_var.'='.$dest;
        }
    
        if ($lcode!=$params['language'])
            $html.='<li><a href="'.$url.'">'.htmlspecialchars($lname).'</a></li>';
        else 
            $html.='<li><span>'.htmlspecialchars($lname).'</span></li>';
    }
    $html.='</ul>';
    
    return $html;
}

/**
 * Template function
 * Mobile version switcher
 * 
 * @param array $params
 * 
 * @return string
 */
function tpl_mobile_version_switcher($params)
{
	global $sawanna;
	
	if (defined('INSTALL') && INSTALL) return;
		
	$url=$sawanna->constructor->url($sawanna->query,false,true);
	$url=$sawanna->constructor->remove_url_args($url,array($sawanna->mobile_query_var));
	$url=$sawanna->constructor->add_url_args($url,array($sawanna->mobile_query_var=>'1'));
	
	return '<noindex><a href="'.$url.'" class="mobile_switcher noajaxer" rel="nofollow">[str]Mobile version[/str]</a></noindex>';
}

/**
 * Template function
 * Full version switcher
 * 
 * @param array $params
 * 
 * @return string
 */
function tpl_full_version_switcher($params)
{
	global $sawanna;
		
	$url=$sawanna->constructor->url($sawanna->query);
	$url=$sawanna->constructor->remove_url_args($url,array($sawanna->mobile_query_var));
	$url=$sawanna->constructor->add_url_args($url,array($sawanna->mobile_query_var=>'0'));
	
	return '<a href="'.$url.'" class="mobile_switcher">[str]Full version[/str]</a>';
}

/**
 * Template function
 * Appends content to body
 * 
 * @param array $params
 * 
 * @return string
 */
function tpl_append_body($params)
{
	global $sawanna;
		
	return $sawanna->tpl->params['append_body'];
}

/**
 * Template function
 * Prepends content to body
 * 
 * @param array $params
 * 
 * @return string
 */
function tpl_prepend_body($params)
{
	global $sawanna;
		
	return $sawanna->tpl->params['prepend_body'];
}
