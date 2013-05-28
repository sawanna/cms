<?php

/**
 * Returns an array with theme defined locations
 * 
 * @return array
 */
function theme_locations()
{
    $locations=array(
        'home' => '[str]Home page[/str]',
        'others' => '[str]Other pages[/str]'
    );
    
    return $locations;
}

/**
 * Returns an array with theme columns
 * 
 * @return array
 */
function theme_columns()
{
   $columns=array(
        'left' => '[str]Left column[/str]',
        'left_additional' => '[str]Left additional column[/str]',
        'right' => '[str]Right column[/str]',
        'right_additional' => '[str]Right additional column[/str]'
   ); 
   
   return $columns;
}

/**
 * Returns column state
 * 
 * @param string $location
 * @param string $column
 * 
 * @return int|bool
 */
function get_column_state($location,$column)
{
    global $sawanna;
    
    return $sawanna->get_option($sawanna->config->theme.'_theme_'.$location.'_'.$column.'_column');
}

/**
 * Saves column state
 * 
 * @param string $location
 * @param string $column
 * @param int $state
 */
function set_column_state($location,$column,$state)
{
    global $sawanna;
    
    return $sawanna->set_option($sawanna->config->theme.'_theme_'.$location.'_'.$column.'_column',$state,0);
}

/**
 * Returns an array with default theme colors
 * 
 * @param bool $is_mobile
 * 
 * @return array
 */
function theme_colors($is_mobile=false)
{
    $theme_colors=array(
        'background' => '#e3f1fd',
        'text_color' => '#000000',
        'link_color' => '#088dc8',
        'link_hover_color' => '#000000',
        'header_text_color' => '#088dc8',
        'header_top_color' => '#0188fb',
        'header_bottom_color' => '#ffffff'
    );

    return $theme_colors;
}

/**
 * Returns an array with theme configurable colors css elements
 * 
 * @param bool $is_mobile
 * 
 * @return array
 */
function theme_colors_css($is_mobile=false)
{
    global $sawanna;
    
    $theme_css=array(
        'background' => array(
                            'body { background-color: %s !important; }',
                            'ul.drop-down-menu-list li ul { background-color: %s !important; }'
                            ),
        'text_color' => 'body { color: %s !important; }',
        'link_color' => 'a:link, a:visited, ul.child-menu-list li ul li a span, ul.drop-down-menu-list li ul li a span { color: %s !important; }',
        'link_hover_color' => 'a:hover, a.active, ul.child-menu-list li ul li a:hover span, ul.child-menu-list li ul li a.active span, ul.drop-down-menu-list li ul li a:hover span, ul.drop-down-menu-list li ul li a.active span { color: %s !important; }',
        'header_text_color' => '.header, .header a { color: %s !important; }',
        'header_top_color' => '.header { background-color: %s !important; }',
        'header_bottom_color' => ''
    );

    return $theme_css;
}

/**
 * Returns an array with color titles
 * (will be displayed in admin-area)
 * 
 * @return array
 */
function theme_color_titles()
{
    $titles=array(
        'background' => '[str]Background color[/str]',
        'text_color' => '[str]Text color[/str]',
        'link_color' => '[str]Link text color[/str]',
        'link_hover_color' => '[str]Active link text color[/str]',
        'header_text_color' => '[str]Header text color[/str]',
        'header_top_color' => '[str]Header top color[/str]',
        'header_bottom_color' => '[str]Header bottom color[/str]'
    );
    
    return $titles;
}

/**
 * Returns element saved color
 * 
 * @param string $elem
 * 
 * @return string|bool
 */
function get_saved_color($elem)
{
    global $sawanna;

    return $sawanna->get_option($sawanna->config->theme.'_theme_'.$elem.'_color');
}

/**
 * Saves element color
 * 
 * @param string $elem
 * @param string $color
 * 
 */
function set_color($elem,$color)
{
    global $sawanna;
    
    if (preg_match('/\#[a-f0-9]{3,6}/i',$color))
        $sawanna->set_option($sawanna->config->theme.'_theme_'.$elem.'_color',$color,0);
}

/**
 * Return an array with theme defined sizes
 * 
 * @param bool $is_mobile
 * 
 * @return array
 */
function theme_sizes($is_mobile=false)
{
    $sizes=array(
        'header_height' => '150',
        'logo_margin_top' => '20',
        'logo_margin_left' => '40',
        'sitename_margin_top' => '60',
        'sitename_margin_left' => '240',
        'sitename_font_size' => '38',
        'text_font_size' => '12',
        'text_line_height' => '20',
        'widget_font_size' => '12',
        'widget_line_height' => '20',
        'sidebar_width' => '230',
        'parent_menu_width' => '500'
    );
    
    return $sizes;
}

/**
 * Returns an array with theme sizes titles
 * 
 * @return array
 */
function theme_size_titles()
{
    $titles=array(
        'header_height' => '[str]Header height[/str]',
        'logo_margin_top' => '[str]Logo top margin[/str]',
        'logo_margin_left' => '[str]Logo left margin[/str]',
        'sitename_margin_top' => '[str]Site name top margin[/str]',
        'sitename_margin_left' => '[str]Site name left margin[/str]',
        'sitename_font_size' => '[str]Site name font size[/str]',
        'text_font_size' => '[str]Text font size[/str]',
        'text_line_height' => '[str]Text line height[/str]',
        'widget_font_size' => '[str]Widget font size[/str]',
        'widget_line_height' => '[str]Widget line height[/str]',
        'sidebar_width' => '[str]Sidebar width[/str]',
        'parent_menu_width' => '[str]Parent menu width[/str]'
    );
    
    return $titles;
}

/**
 * Returns an array with size minimum and maximum values
 * 
 * @return array
 */
function theme_size_ranges()
{
    $ranges=array(
        'header_height' => array(100,500),
        'logo_margin_top' => array(10,500),
        'logo_margin_left' => array(10,1000),
        'sitename_margin_top' => array(10,500),
        'sitename_margin_left' => array(10,1000),
        'sitename_font_size' => array(6,100),
        'text_font_size' => array(6,30),
        'text_line_height' => array(10,50),
        'widget_font_size' => array(6,30),
        'widget_line_height' => array(10,50),
        'sidebar_width' => array(200,500),
        'parent_menu_width' => array(100,1280)
    );
    
    return $ranges;
}

/**
 * Returns an array with theme configurable sizes css elements
 * 
 * @param bool $is_mobile
 * 
 * @return array
 */
function theme_sizes_css($is_mobile=false)
{
    $theme_css=array(
        'header_height' => '.header { height: %spx !important; }',
        'logo_margin_top' => '.header .logo { margin-top: %spx !important }',
        'logo_margin_left' => '.header .logo { margin-left: %spx !important }',
        'sitename_margin_top' => '.header .sitename { top: %spx !important }',
        'sitename_margin_left' => '.header .sitename { left: %spx !important }',
        'sitename_font_size' => '.header .sitename a { font-size: %spx !important }',
        'widget_font_size' => '.block-body, .user_login_panel .node {font-size: %spx !important;}',
        'widget_line_height' => '.block-body {line-height: %spx !important}',
        'text_font_size' => array(
                            '.node, ul.message, ul.error, .search-content { font-size: %spx !important; }',
                            '+4'=>'h1 {font-size: %spx !important;}',
                            '+2'=>'h2, h2.node-title a, .search-title a {font-size: %spx !important;}'
                            ),
        'text_line_height' => array(
                            '-4'=>'.comments-list-block div#content-component, .user_login_panel .node {line-height: %spx !important}',
                            '.node, .search-content {line-height: %spx !important}'
                            ),
        'sidebar_width' => array(
                            '.leftbar, .rightbar { width: %spx !important; }',
                            '+20'=>'body.both .body { margin: 20px %spx 0px !important; }',
                            '+ 20'=>'body.right .body { margin-right: %spx !important; }',
                            '+20 '=>'body.left .body { margin-left: %spx !important; }',
                            '.leftbar ul.child-menu-list { width: %spx !important; }',
                            '-10'=>'.rightbar ul.child-menu-list { width: %spx !important; }',
                            '-60'=>'ul.child-menu-list li a span { width: %spx !important; }',
                            '-70'=>'* html ul.child-menu-list a span { width: %spx !important; }',
                            '- 10'=>'.widget { width: %spx !important; }',   
                            '- 55'=>'.widget .block-body { width: %spx !important; }',
                            '- 55 '=>'.latest-comment-text { width: %spx !important; }',
                            '-50'=>'* html .cforms-widget-block form { width: %spx !important; }',
                            '- 70'=>'.widget input { width: %spx !important; }',
                            '-90'=>'.widget textarea { width: %spx !important; }',
                            '- 60 '=>'#quick-search-form input#search-text { width: %spx !important; }',
                            '- 10 '=>'.user_login_panel { width: %spx !important; }',
                            '-55'=>'#user-panel input { width: %spx !important; }',
                            '- 70 '=>'div.gallery-widget-block .gallery-item, .leftbar .gallery-item, .rightbar .gallery-item { width: %spx !important; }',
                            '- 75'=>'div.gallery-widget-block .gallery-item img, .leftbar .gallery-item img, .rightbar .gallery-item img { width: %spx !important; height: auto !important; }'
                            ),
        'parent_menu_width' => '.parent-menu, .drop-down-menu { width: %spx !important; }'
    );
    
    if ($is_mobile)
    {
		$theme_css=array();
	}
    
    return $theme_css;
}

/**
 * Returns element saved size
 * 
 * @param string $elem
 * 
 * @return string|bool
 */
function get_saved_size($elem)
{
    global $sawanna;

    return $sawanna->get_option($sawanna->config->theme.'_theme_'.$elem.'_size');
}

/**
 * Saves element size
 * 
 * @param string $elem
 * @param string $size
 * 
 */
function set_size($elem,$size)
{
    global $sawanna;
    
    $min=10;
    $max=1000;
    $size=(int) $size;
    
    $ranges=theme_size_ranges();
    if (array_key_exists($elem,$ranges))
    {
        list($min,$max)=$ranges[$elem];
    }
    
    if ($size>=$min && $size<=$max)
        $sawanna->set_option($sawanna->config->theme.'_theme_'.$elem.'_size',$size,0);
}

/**
 * Returns an array with theme defined fonts
 * 
 * @param bool $is_mobile
 * 
 * @return array
 */
function theme_fonts($is_mobile=false)
{
   $fonts=array(
        'arial' => 'Arial',
        'comic sans ms' => 'Comic Sans MS',
        'courier' => 'Courier',
        'georgia' => 'Georgia',
        'sans serif' => 'Sans Serif',
        'serif' => 'Serif',
        'tahoma' => 'Tahoma',
        'times new roman' => 'Times New Roman',
        'trebuchet ms' => 'Trebuchet MS',
        'verdana' => 'Verdana'
   );
   
   return $fonts; 
}

/**
 * Returns an array with theme defined sources
 * 
 * @return array
 */
function theme_sources()
{
    $sources=array(
        'round' => '[str]Round[/str]',
        'straight' => '[str]Straight[/str]',
        'transparent' => '[str]Transparent[/str]',
        'plastic' => '[str]Plastic[/str]'
    );
    
    return $sources;
}

/**
 * Returns theme configurable styles
 * 
 * @param bool $is_mobile
 * 
 * @return string
 */
function theme_style($is_mobile=false)
{
    global $sawanna;
    
    $output='';
    
    // font family
    $fonts=theme_fonts($is_mobile);
    $f_family=$sawanna->get_option($sawanna->config->theme.'_theme_font_family');
    
    if (array_key_exists($f_family,$fonts))
    {
        $output.='body, .node, .block-body { font-family: "'.$f_family.'" !important; }'."\n";
    }
    
    // colors
    $theme_colors=theme_colors($is_mobile);
    
    foreach ($theme_colors as $elem=>$color)
    {
        $saved_color=get_saved_color($elem);
        
        if ($saved_color) $theme_colors[$elem]=$saved_color;
    }

    $theme_colors_css=theme_colors_css($is_mobile);
    
    foreach($theme_colors_css as $elem=>$css)
    {
        if (!array_key_exists($elem,$theme_colors)) continue;
        
        if (is_array($css))
        {
            foreach($css as $css_val)
            {
                $output.=sprintf($css_val,$theme_colors[$elem])."\n";
            }
        } else
        {
            $output.=sprintf($css,$theme_colors[$elem])."\n";
        }
    }
    
    $source=$sawanna->get_option($sawanna->config->theme.'_theme_source');
    if ($source == 'plastic') {
		$output.='.block-caption { color: #000 !important; }'."\n";
		$output.='ul.parent-menu-list, ul.drop-down-menu-list { margin-left:2px !important; margin-right:2px !important; }'."\n";
	}

	// header background image
	if (get_saved_color('header_top_color') && get_saved_color('header_bottom_color') && $css_version=$sawanna->get_option('css_version'))
	{
		$output.='.header { background-image: url(\''.SITE.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/header_bg_'.$css_version.'.png\') !important; }'."\n";
	} else // default value
	{
		$output.='.header { background-image: url(\''.SITE.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/images/header_bg.png\') !important; }'."\n";
	}
	
	if (get_saved_color('background') && $css_version=$sawanna->get_option('css_version'))
	{
		 if (!$is_mobile)
		{
			if (file_exists(ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/parent-menu-bg-x_'.$css_version.'.png')) {
				$output.='.parent-menu-block { background: url(\''.SITE.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/parent-menu-bg-x_'.$css_version.'.png\') repeat-x 0 0 !important; }'."\n";
			}
			
			// parent menu background image
			$output.='ul.parent-menu-list a, ul.parent-menu-list li a:visited, ul.drop-down-menu-list li, ul.parent-menu-list li a span, ul.drop-down-menu-list li a, ul.drop-down-menu-list li a:visited { background-image: url(\''.SITE.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/parent-menu-bg_'.$css_version.'.png\') !important; }'."\n";
		
			// child menu background image
			$output.='ul.child-menu-list a, ul.child-menu-list li a:visited, ul.child-menu-list li a span { background-image: url(\''.SITE.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/child-menu-bg_'.$css_version.'.png\') !important; }'."\n";
		} else {
			$output.='ul.parent-menu-list,ul.child-menu-list { background-image: url(\''.SITE.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-bg_'.$css_version.'.png\') !important; }'."\n";
		}
		
		// node template
		$output.='.node-lt { background-image: url(\''.SITE.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-lt_'.$css_version.'.png\') !important; }'."\n";
		$output.='.node-rt { background-image: url(\''.SITE.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-rt_'.$css_version.'.png\') !important; }'."\n";
		$output.='.node-t { background-image: url(\''.SITE.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-t_'.$css_version.'.png\') !important; }'."\n";
		$output.='.node-l { background-image: url(\''.SITE.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-l_'.$css_version.'.png\') !important; }'."\n";
		$output.='.node-r { background-image: url(\''.SITE.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-r_'.$css_version.'.png\') !important; }'."\n";
		$output.='.node-lb { background-image: url(\''.SITE.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-lb_'.$css_version.'.png\') !important; }'."\n";
		$output.='.node-rb { background-image: url(\''.SITE.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-rb_'.$css_version.'.png\') !important; }'."\n";
		$output.='.node-b { background-image: url(\''.SITE.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-b_'.$css_version.'.png\') !important; }'."\n";
		$output.='.node { background-image: url(\''.SITE.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-bg_'.$css_version.'.png\') !important; }'."\n";
		
		// widget template
		$output.='.block-lt, .block-rt { background-image: url(\''.SITE.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/block-t_'.$css_version.'.png\') !important; }'."\n";
		$output.='.block-lb, .block-rb { background-image: url(\''.SITE.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/block-b_'.$css_version.'.png\') !important; }'."\n";
		$output.='.block-l, .block-r { background-image: url(\''.SITE.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/block-bg_'.$css_version.'.png\') !important; }'."\n";
		
		// shadow
		$output.='.shadow { background-image: url(\''.SITE.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/shadow_'.$css_version.'.png\') !important; }'."\n";
	} else // default values
	{
		 if (!$is_mobile)
		{
			// parent menu background image
			$output.='ul.parent-menu-list a, ul.parent-menu-list li a:visited, ul.drop-down-menu-list li, ul.parent-menu-list li a span, ul.drop-down-menu-list li a, ul.drop-down-menu-list li a:visited { background-image: url(\''.SITE.'/'.quotemeta($sawanna->module->root_dir).'/menu/'.quotemeta($sawanna->module->themes_root).'/colorit/images/parent-menu-bg.png\') !important; }'."\n";
			
			// child menu background image
			$output.='ul.child-menu-list a, ul.child-menu-list li a:visited, ul.child-menu-list li a span { background-image: url(\''.SITE.'/'.quotemeta($sawanna->module->root_dir).'/menu/'.quotemeta($sawanna->module->themes_root).'/colorit/images/child-menu-bg.png\') !important; }'."\n";
		} else {
			$output.='ul.parent-menu-list,ul.child-menu-list { background-image: url(\''.SITE.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/images/node-bg.png\') !important; }'."\n";
		}
		
		// node template
		$output.='.node-lt { background-image: url(\''.SITE.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/images/node-lt.png\') !important; }'."\n";
		$output.='.node-rt { background-image: url(\''.SITE.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/images/node-rt.png\') !important; }'."\n";
		$output.='.node-t { background-image: url(\''.SITE.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/images/node-t.png\') !important; }'."\n";
		$output.='.node-l { background-image: url(\''.SITE.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/images/node-l.png\') !important; }'."\n";
		$output.='.node-r { background-image: url(\''.SITE.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/images/node-r.png\') !important; }'."\n";
		$output.='.node-lb { background-image: url(\''.SITE.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/images/node-lb.png\') !important; }'."\n";
		$output.='.node-rb { background-image: url(\''.SITE.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/images/node-rb.png\') !important; }'."\n";
		$output.='.node-b { background-image: url(\''.SITE.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/images/node-b.png\') !important; }'."\n";
		$output.='.node { background-image: url(\''.SITE.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/images/node-bg.png\') !important; }'."\n";
		
		// widget template
		$output.='.block-lt, .block-rt { background-image: url(\''.SITE.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/images/block-t.png\') !important; }'."\n";
		$output.='.block-lb, .block-rb { background-image: url(\''.SITE.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/images/block-b.png\') !important; }'."\n";
		$output.='.block-l, .block-r { background-image: url(\''.SITE.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/images/block-bg.png\') !important; }'."\n";
		
		// shadow
		$output.='.shadow { background-image: url(\''.SITE.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/images/shadow.png\') !important; }'."\n";
	}
	
    // sizes
    $theme_sizes=theme_sizes($is_mobile);
    
    foreach ($theme_sizes as $elem=>$size)
    {
        $saved_size=get_saved_size($elem);
        
        if ($saved_size) $theme_sizes[$elem]=$saved_size;
    }
    
    $theme_sizes_css=theme_sizes_css($is_mobile);
    
    foreach($theme_sizes_css as $elem=>$css)
    {
        if (!array_key_exists($elem,$theme_sizes)) continue;
        
        if (is_array($css))
        {
            foreach($css as $css_op=>$css_val)
            {
                $value=$theme_sizes[$elem];
                
                if (substr($css_op,0,1)=='+' || substr($css_op,0,1)=='-') eval('$value='.$theme_sizes[$elem].$css_op.';');
                
                $output.=sprintf($css_val,$value)."\n";
            }
        } else
        {
            $output.=sprintf($css,$theme_sizes[$elem])."\n";
        }
    }

    // parent menu alignment
    $m_align=$sawanna->get_option($sawanna->config->theme.'_theme_menu_align');
    
    switch ($m_align)
    {
        case 'left':
            $output.='.parent-menu, .drop-down-menu { margin: 0px auto 0px 0px !important; }'."\n";
            break;
        case 'right':
            $output.='.parent-menu, .drop-down-menu { margin: 0px 0px 0px auto !important; }'."\n";
            break;
        default:
            $output.='.parent-menu, .drop-down-menu { margin: 0px auto !important; }'."\n";
            break;
    }
    
    return $output;
}

/**
 * Returns theme settings form
 * 
 * @return string
 */
function theme_settings()
{
    global $sawanna;
    
    if (!$sawanna->has_access('su')) return;

    $locations=theme_locations();
    $columns=theme_columns();
    
    $fields=array();
    
    // columns
    foreach($locations as $location=>$location_title)
    {
        $fields[$location.'_tpl_block']=array(
            'type' => 'html',
            'value' => '<div class="form-caption">'.$location_title.':</div>'
        );
    
        foreach($columns as $column=>$column_title)
        {
            $state=get_column_state($location,$column);
            
            $fields[$location.'_'.$column.'_column']=array(
                'type' => 'checkbox',
                'title' => $column_title,
                'description' => '',
                'attributes' => $state ? array('checked'=>'checked') : array()
            );
        }
    }
    
    // colors
    $theme_colors=theme_colors();
    
    foreach($theme_colors as $elem=>$color)
    {
        $saved_color=get_saved_color($elem);
        
        ${$elem.'_color'}=$saved_color ? $saved_color : $color;
    }
    
    $color_titles=theme_color_titles();
    
    $fields['colors_tpl_block']=array(
        'type' => 'html',
        'value' => '<div class="form-caption">[str]Colors[/str]:</div>'
    );
        
    foreach($theme_colors as $elem=>$color)
    {
        $fields[$elem.'_color']=array(
            'type' => 'color',
            'title' => array_key_exists($elem,$color_titles) ? $color_titles[$elem] : '',
            'description' => '',
            'value' => isset(${$elem.'_color'}) ? ${$elem.'_color'} : '',
            'attributes' => array('class'=>'colorpicker')
        );
    }
    
    // sizes
    $theme_sizes=theme_sizes();
    
    foreach($theme_sizes as $elem=>$size)
    {
        $saved_size=get_saved_size($elem);
        
        ${$elem.'_size'}=$saved_size ? $saved_size : $size;
    }
    
    $size_titles=theme_size_titles();
    
    $fields['sizes_tpl_block']=array(
        'type' => 'html',
        'value' => '<div class="form-caption">[str]Sizes[/str]:</div>'
    );

    $ranges=theme_size_ranges();
    
    foreach($theme_sizes as $elem=>$size)
    {
        $fields[$elem.'_size']=array(
            'type' => 'text',
            'title' => array_key_exists($elem,$size_titles) ? $size_titles[$elem] : '',
            'description' => array_key_exists($elem,$ranges) ? '('.implode(' - ',$ranges[$elem]).')' : '',
            'value' => isset(${$elem.'_size'}) ? ${$elem.'_size'} : '',
            'attributes' => array('class'=>'size')
        );
    }
    
    // font family
    $fonts=theme_fonts();
    $f_family=$sawanna->get_option($sawanna->config->theme.'_theme_font_family');
    
    $fields['font_family']=array(
        'type' => 'select',
        'options' => $fonts,
        'title' => '[str]Text font family[/str]',
        'description' => '',
        'value' => $f_family ? $f_family : 'Arial',
        'attributes' => array('class'=>'size')
    );
    
    // parent menu alignment
    $m_align=$sawanna->get_option($sawanna->config->theme.'_theme_menu_align');
    
    $fields['menu_alignment']=array(
        'type' => 'select',
        'options' => array('left'=>'[str]Left[/str]','center'=>'[str]Center[/str]','right'=>'[str]Right[/str]'),
        'title' => '[str]Parent menu alignment[/str]',
        'description' => '',
        'value' => $m_align ? $m_align : 'center',
        'attributes' => array('class'=>'size')
    );
    
    // source
    $sources=theme_sources();
    $source=$sawanna->get_option($sawanna->config->theme.'_theme_source');
    
    $fields['source']=array(
        'type' => 'select',
        'options' => $sources,
        'title' => '[str]Style[/str]',
        'description' => '',
        'value' => $source ? $source : 'round',
        'attributes' => array('class'=>'size')
    );

    $fields['submit']=array(
        'type'=>'submit',
        'value'=>'[str]Submit[/str]'
    );
        
    return $sawanna->constructor->form('theme_settings',$fields,'process_admin_area_requests',$sawanna->config->admin_area.'/themes',$sawanna->config->admin_area.'/themes/settings','','CSawanna');
}

/**
 * Processes theme settings form submission
 * 
 * @return array
 */
function theme_settings_process()
{
    global $sawanna;
    
    if (!$sawanna->has_access('su')) return;
    
    // increasing css version
    $css_version=time();
    $sawanna->set_option('css_version',$css_version,0);
    
    $locations=theme_locations();
    $columns=theme_columns();
    
    // columns
    foreach($locations as $location=>$location_title)
    {
        foreach($columns as $column=>$column_title)
        {
            if (isset($_POST[$location.'_'.$column.'_column']) && $_POST[$location.'_'.$column.'_column']=='on')
            {
                set_column_state($location,$column,1);
            } else
            {
                set_column_state($location,$column,0);
            }
        }
    }
    
    // colors
    $theme_colors=theme_colors();

    foreach($theme_colors as $elem=>$color)
    {
        if (isset($_POST[$elem.'_color']))
        {
            set_color($elem,$_POST[$elem.'_color']);
        }
    }
    
    // sizes
    $theme_sizes=theme_sizes();

    foreach($theme_sizes as $elem=>$size)
    {
        if (isset($_POST[$elem.'_size']))
        {
            set_size($elem,$_POST[$elem.'_size']);
        }
    }
    
    // font family
    $fonts=theme_fonts();
    if (isset($_POST['font_family']) && array_key_exists($_POST['font_family'],$fonts))
    {
        $sawanna->set_option($sawanna->config->theme.'_theme_font_family',$_POST['font_family'],0);
    }
    
    // parent menu alignment
    if (isset($_POST['menu_alignment']) && in_array($_POST['menu_alignment'],array('left','center','right')))
    {
        $sawanna->set_option($sawanna->config->theme.'_theme_menu_align',$_POST['menu_alignment'],0);
    }
    
    // gradients
    $dir_ready=true;
    
    if (!is_writable(ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir)))
    {
        $dir_ready=false;
    } elseif (!file_exists(ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes'))
    {
        if (!mkdir(ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes',0777))
            $dir_ready=false;
    }
    
    if($dir_ready && !is_writable(ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes'))
    {
        $dir_ready=false;
    }
    
    if ($dir_ready && !file_exists(ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit'))
    {
        if (!mkdir(ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit',0777))
            $dir_ready=false;
    }
    
    if($dir_ready && !is_writable(ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit'))
    {
        $dir_ready=false;
    }
    
    // creating header background
    $header_top_color=get_saved_color('header_top_color');
    $header_bottom_color=get_saved_color('header_bottom_color');
    $header_height=get_saved_size('header_height');
    
    if ($dir_ready && $header_top_color && $header_bottom_color && $header_height)
    {
        $sawanna->constructor->gradient_image(ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/header_bg_'.$css_version.'.png',1, $header_height,$header_top_color, $header_bottom_color, true);
    }
    
    
    $background=get_saved_color('background');
    
    if ($dir_ready && $background)
    {
        $sources=theme_sources();
        $source=!empty($_POST['source']) && array_key_exists($_POST['source'],$sources) ? $_POST['source'] : 'round';
        
        if (!file_exists(ROOT_DIR.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/sources/'.quotemeta($source)))
        {
            return array('[str]Source directory not found[/str]','source');
        }
        
        $sawanna->set_option($sawanna->config->theme.'_theme_source',$source,0);
        
        // creating parent menu container bg
        if (file_exists(ROOT_DIR.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/sources/'.quotemeta($source).'/parent-menu-bg-x.png')) {
			 $sawanna->constructor->create_image(ROOT_DIR.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/sources/'.quotemeta($source).'/parent-menu-bg-x.png',ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/parent-menu-bg-x_'.$css_version.'.png',1,59,$background);
		}
        
        // creating parent menu background
        $sawanna->constructor->create_image(ROOT_DIR.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/sources/'.quotemeta($source).'/parent-menu-bg.png',ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/parent-menu-bg_'.$css_version.'.png',330,120,$background);
    
        // creating child menu background
        $sawanna->constructor->create_image(ROOT_DIR.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/sources/'.quotemeta($source).'/child-menu-bg.png',ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/child-menu-bg_'.$css_version.'.png',600,60,$background);
        
        // node template
        $sawanna->constructor->create_image(ROOT_DIR.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/sources/'.quotemeta($source).'/node-lt.png',ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-lt_'.$css_version.'.png',20,20,$background);
        $sawanna->constructor->create_image(ROOT_DIR.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/sources/'.quotemeta($source).'/node-rt.png',ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-rt_'.$css_version.'.png',20,20,$background);
        $sawanna->constructor->create_image(ROOT_DIR.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/sources/'.quotemeta($source).'/node-t.png',ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-t_'.$css_version.'.png',20,20,$background);
        $sawanna->constructor->create_image(ROOT_DIR.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/sources/'.quotemeta($source).'/node-l.png',ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-l_'.$css_version.'.png',20,20,$background);
        $sawanna->constructor->create_image(ROOT_DIR.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/sources/'.quotemeta($source).'/node-r.png',ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-r_'.$css_version.'.png',20,20,$background);
        $sawanna->constructor->create_image(ROOT_DIR.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/sources/'.quotemeta($source).'/node-lb.png',ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-lb_'.$css_version.'.png',20,20,$background);
        $sawanna->constructor->create_image(ROOT_DIR.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/sources/'.quotemeta($source).'/node-rb.png',ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-rb_'.$css_version.'.png',20,20,$background);
        $sawanna->constructor->create_image(ROOT_DIR.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/sources/'.quotemeta($source).'/node-b.png',ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-b_'.$css_version.'.png',20,20,$background);
        $sawanna->constructor->create_image(ROOT_DIR.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/sources/'.quotemeta($source).'/node-bg.png',ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/node-bg_'.$css_version.'.png',20,20,$background);
        
        // widget template
        $sawanna->constructor->create_image(ROOT_DIR.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/sources/'.quotemeta($source).'/block-t.png',ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/block-t_'.$css_version.'.png',600,40,$background);
        $sawanna->constructor->create_image(ROOT_DIR.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/sources/'.quotemeta($source).'/block-b.png',ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/block-b_'.$css_version.'.png',600,20,$background);
        $sawanna->constructor->create_image(ROOT_DIR.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/sources/'.quotemeta($source).'/block-bg.png',ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/block-bg_'.$css_version.'.png',600,1,$background);
    
        // shadow
        $sawanna->constructor->create_image(ROOT_DIR.'/'.quotemeta($sawanna->tpl->root_dir).'/colorit/sources/'.quotemeta($source).'/shadow.png',ROOT_DIR.'/'.quotemeta($sawanna->file->root_dir).'/themes/colorit/shadow_'.$css_version.'.png',5,3,$background);
    }

    return array('[str]Settings saved[/str]');
}
