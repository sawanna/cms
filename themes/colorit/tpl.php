<?php 
defined('SAWANNA') or die();

#################################################################
# INFO
# name : Colorit
# description : Настраиваемая тема
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
    
    $columns=theme_columns();
    
    $left_state=0;
    $left_additional_state=0;
    $right_state=0;
    $right_additional_state=0;
    
    if ($location == $sawanna->config->front_page_path)
    {
        foreach($columns as $column=>$column_title)
        {
            ${$column.'_state'}=get_column_state('home',$column);
        }
    } else
    {
        foreach($columns as $column=>$column_title)
        {
            ${$column.'_state'}=get_column_state('others',$column);
        }
    }
    
    components_state(array($left_state,$left_additional_state,$right_state,$right_additional_state));
    
    switch(true)
    {
		case $is_mobile:        // mobile devices
			$tpl= 'mobile';
			break;
        case ($left_state || $left_additional_state) && ($right_state || $right_additional_state):
            $tpl= 'both';
            break;
        case ($left_state || $left_additional_state) && (!$right_state && !$right_additional_state):
            $tpl= 'left';
            break;
        case (!$left_state && !$left_additional_state) && ($right_state || $right_additional_state):
            $tpl= 'right';
            break;
         default:
            $tpl= 'none';
            break;
    }
    
    return $tpl;
}

/**
 * Stores theme components state  (internal)
 * 
 * @param array $state
 * 
 * @return array
 */
function components_state($state=array())
{
    static $left,$left_add,$right,$right_add;
    
    if (!empty($state))
    {
        list($left,$left_add,$right,$right_add)=$state;
    }
    
    return array($left,$left_add,$right,$right_add);
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
        'left-additional' => 'Left additional sidebar',
        'body' => 'Content',
        'right' => 'Right Sidebar',
        'right-additional' => 'Right additional sidebar',
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
    list($left,$left_add,$right,$right_add)=components_state();
    
    $header_components=array(
        'header',
        'parent-menu',
        'child-menu',
        );
        
    $body_components=array(
        'body'
        );
        
    $footer_components=array(
        'footer'
    );
                    
    switch ($tpl)
    {
		case 'mobile':
			$components=$header_components;

			$components=array_merge($components,$body_components);

			$components=array_merge($components,$footer_components);
			break;
        case 'both':
                $components=$header_components;
                
                if ($left) $components[]='left';
                if ($left_add) $components[]='left-additional';
                
                $components=array_merge($components,$body_components);
                
                if ($right) $components[]='right';
                if ($right_add) $components[]='right-additional';
                
                $components=array_merge($components,$footer_components);
                break;
        case 'left':
                $components=$header_components;
                
                if ($left) $components[]='left';
                if ($left_add) $components[]='left-additional';
                
                $components=array_merge($components,$body_components);
                
                $components=array_merge($components,$footer_components);
                break;
        case 'right':
                $components=$header_components;
                
                $components=array_merge($components,$body_components);
                
                if ($right) $components[]='right';
                if ($right_add) $components[]='right-additional';
                
                $components=array_merge($components,$footer_components);
                break;
        default:
                $components=$header_components;

                $components=array_merge($components,$body_components);

                $components=array_merge($components,$footer_components);
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
