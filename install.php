<?php 
define('SAWANNA',1);
define('INSTALL',1);

define('ROOT_DIR','.');
define('HOST','');
define('PROTOCOL','');
define('SITE','.');

define('DB_TYPE','');
define('DB_HOST','');
define('DB_PORT','');
define('DB_NAME','');
define('DB_USER','');
define('DB_PASS','');
define('DB_PREFIX','');

require_once(ROOT_DIR.'/sawanna/functions.php');
require_once(ROOT_DIR.'/sawanna/install.inc.php');
require_once(ROOT_DIR.'/sawanna/common.inc.php');

define('PRIVATE_KEY',random_chars(32));

$server_tz=ini_get('date.timezone');
if (empty($server_tz)) date_default_timezone_set('UTC');

function install_form_fields()
{
    $site_dir=isset($_SERVER['PHP_SELF']) ? dirname($_SERVER['PHP_SELF']) : '';
    $site_dir=str_replace('\\','/',$site_dir);
    
    $site_protocol=$_SERVER['SERVER_PORT']==443 ? 'https' : 'http';
    $private_key=random_chars(12);
    
    $default_db='';
    if (function_exists('mysql_connect')) $default_db='MySQL';
    elseif (function_exists('pg_connect')) $default_db='PostgreSQL';
    elseif (function_exists('sqlite_open')) $default_db='SQLite';
    
    $supported_db=array();
    if (function_exists('mysql_connect')) $supported_db[]='MySQL';
    if (function_exists('pg_connect')) $supported_db[]='PostgreSQL';
    if (function_exists('sqlite_open')) $supported_db[]='SQLite';
       
    $defaults=array(
        'database' => isset($_POST['database']) && !empty($_POST['database']) ? $_POST['database'] : $default_db,
        'root' => isset($_POST['root']) ? $_POST['root'] : '',
        'directory' => isset($_POST['directory']) ? $_POST['directory'] : $site_dir,
        'protocol' => isset($_POST['protocol']) ? $_POST['protocol'] : $site_protocol,
        'timezone' => isset($_POST['timezone']) ? $_POST['timezone'] : default_time_zone(),
        'private_key' => isset($_POST['private_key']) ? $_POST['private_key'] : $private_key,
        'db_host' => isset($_POST['db_host']) ? $_POST['db_host'] : 'localhost',
        'db_port' => isset($_POST['db_port']) ? $_POST['db_port'] : '3306',
        'db_name' => isset($_POST['db_name']) ? $_POST['db_name'] : '',
        'db_user' => isset($_POST['db_user']) ? $_POST['db_user'] : '',
        'db_pass' => isset($_POST['db_pass']) ? $_POST['db_pass'] : '',
        'db_prefix' => isset($_POST['db_prefix']) ? $_POST['db_prefix'] : 'pre',
        'site_name' => isset($_POST['site_name']) ? $_POST['site_name'] : '',
        'site_slogan' => isset($_POST['site_slogan']) ? $_POST['site_slogan'] : '',
        'su_login' => isset($_POST['su_login']) ? $_POST['su_login'] : '',
        'su_password' => isset($_POST['su_password']) ? $_POST['su_password'] : '',
        'su_password_re' => isset($_POST['su_password_re']) ? $_POST['su_password_re'] : '',
        'su_email' => isset($_POST['su_email']) ? $_POST['su_email'] : '',
    );
    
    $langs=$GLOBALS['sawanna']->locale->get_installed_languages();
    $languages=array();
    foreach ($langs as $lname=>$linfo)
    {
        $languages[$lname]=$linfo['name'];
    }
    
     $fields=array();
    
     ################## Language ####################
     $fields['language_caption']=array(
            'type'=>'html',
            'value' =>'<h3>[str]Language[/str]</h3><p>[str]Please choose the language for your new installation[/str]</p>',
       );
       
       foreach ($languages as $lang_code=>$lang_name)
       {
           $language_attr=$lang_code==$GLOBALS['sawanna']->locale->current ? array('checked'=>'checked') : array();
          // $language_attr['onclick']="window.location.href='install.php?language='+this.value";
           $language_attr['onclick']="$('#install_form').get(0).submit();";
           
           $fields['language_'.$lang_code]=array(
                'type'=>'radio',
                'name' => 'language',
                'value' => $lang_code,
                'title'=>$lang_name,
                'attributes' =>$language_attr
            );
       }
        
       $fields['language_separator']=array(
            'type'=>'html',
            'value' =>'<hr />' 
       );
       
       ################# Install Info ###################
       $fields['install_info_caption']=array(
            'type'=>'html',
            'value' =>'<h3>[str]Required Information[/str]</h3>' 
       );

       $fields['root']=array(
            'type' => 'text',
            'title' => '[str]Host root[/str]',
            'description' => '[str]Host root directory[/str] ([str]Optional[/str]). [str]Leave empty, if not sure[/str]',
            'default' => $defaults['root'],
            'required' => false
       );
       
       $fields['directory']=array(
            'type' => 'text',
            'title' => '[str]Website directory[/str]',
            'description' => '[strf]Root directory assumed when `%/%` specified[/strf]',
            'default' => $defaults['directory'],
            'required' => true
       );
       
       $fields['protocol']=array(
            'type' => 'text',
            'title' => '[str]Protocol[/str]',
            'description' => '[str]If detected not correctly, fix it manually.[/str]',
            'default' => $defaults['protocol'],
            'required' => true
       );
       
       $fields['timezone']=array(
            'type' => 'select',
            'optgroups' => get_timezones(),
            'title' => '[str]Timezone[/str]',
            'description' => '[str]Choose your timezone[/str]',
            'default' => $defaults['timezone'],
            'required' => false
       );
       
        $fields['private_key']=array(
            'type' => 'text',
            'title' => '[str]Private key[/str]',
            'description' => '[strf]Minimum required characters: %12%.[/strf] [str]This key used by system in some encrypted data[/str]',
            'default' => $defaults['private_key'],
            'required' => true
       );
       
       $fields['install_info_separator']=array(
            'type'=>'html',
            'value' =>'<hr />' 
       );
       
       ################# Database ####################
       $db_script="if (this.value=='MySQL'){\$('#db_host').get(0).value='localhost';\$('#db_port').get(0).value='3306';\$('#db_user').get(0).value='';\$('#db_pass').get(0).value='';\$('#db_name').get(0).value='';\$('#db_host').removeAttr('readonly');\$('#db_port').removeAttr('readonly');\$('#db_user').removeAttr('readonly');\$('#db_pass').removeAttr('readonly');};";
       $db_script.="if (this.value=='PostgreSQL'){\$('#db_host').get(0).value='localhost';\$('#db_port').get(0).value='5432';\$('#db_user').get(0).value='';\$('#db_pass').get(0).value='';\$('#db_name').get(0).value='';\$('#db_host').removeAttr('readonly');\$('#db_port').removeAttr('readonly');\$('#db_user').removeAttr('readonly');\$('#db_pass').removeAttr('readonly');};";
       $db_script.="if (this.value=='SQLite'){\$('#db_host').get(0).value='-';\$('#db_port').get(0).value='-';\$('#db_user').get(0).value='-';\$('#db_pass').get(0).value='-';\$('#db_name').get(0).value='".dirname(__FILE__).'/../sawanna.db'."';\$('#db_host').attr('readonly','readonly');\$('#db_port').attr('readonly','readonly');\$('#db_user').attr('readonly','readonly');\$('#db_pass').attr('readonly','readonly');};";
       
       $mysql_attr=$defaults['database']=='MySQL' ? array('checked'=>'checked','onclick'=>$db_script) : array('onclick'=>$db_script);
       if (!in_array('MySQL',$supported_db)) $mysql_attr=array('disabled'=>'disabled');
       
       $pg_attr=$defaults['database']=='PostgreSQL' ? array('checked'=>'checked','onclick'=>$db_script) : array('onclick'=>$db_script);
       if (!in_array('PostgreSQL',$supported_db)) $pg_attr=array('disabled'=>'disabled');
       
       $sqlite_attr=$defaults['database']=='SQLite' ? array('checked'=>'checked','onclick'=>$db_script) : array('onclick'=>$db_script);
       if (!in_array('SQLite',$supported_db)) $sqlite_attr=array('disabled'=>'disabled');
       
       $fields['database_caption']=array(
            'type'=>'html',
            'value' =>'<h3>[str]Database Information[/str]</h3>' 
       );
       
       $fields['db_mysql']=array(
                'type'=>'radio',
                'name' => 'database',
                'value' => 'MySQL',
                'title'=>'MySQL',
                'attributes' =>$mysql_attr
            );
            
        $fields['db_pgsql']=array(
                'type'=>'radio',
                'name' => 'database',
                'value' => 'PostgreSQL',
                'title'=>'PostgreSQL',
                'attributes' =>$pg_attr
            );
            
        $fields['db_sqlite']=array(
                'type'=>'radio',
                'name' => 'database',
                'value' => 'SQLite',
                'title'=>'SQLite',
                'description' => '[str]Please choose the database type for your installation[/str]',
                'attributes' =>$sqlite_attr
            );
            
        $fields['db_host']=array(
                'type'=>'text',
                'required' => true,
                'title'=>'[str]Database server[/str]',
                'description' => '[str]Required for MySQL and PostgreSQL installation types.[/str] [strf]Mostly used %localhost%.[/strf]',
                'attributes' =>array('style'=>'width:200px'),
                'default' => $defaults['db_host']
                );
                
        $fields['db_port']=array(
                'type'=>'text',
                'required' => true,
                'title'=>'[str]Database port[/str]',
                'description' => '[str]Required for MySQL and PostgreSQL installation types.[/str]',
                'attributes' =>array('style'=>'width:200px'),
                'default' => $defaults['db_port']
                );
                
        $fields['db_name']=array(
                'type'=>'text',
                'required' => true,
                'title'=>'[str]Database name[/str]',
                'description' => '[str]If you`re installing with SQLite specify the path to the database file here[/str]',
                'attributes' =>array('style'=>'width:300px'),
                'default' => $defaults['db_name']
                );
                
        $fields['db_user']=array(
                'type'=>'text',
                'required' => true,
                'title'=>'[str]Database user[/str]',
                'description' => '[str]Required for MySQL and PostgreSQL installation types.[/str]',
                'attributes' =>array('style'=>'width:200px'),
                'default' => $defaults['db_user']
                );
                
        $fields['db_pass']=array(
                'type'=>'text',
                'required' => false,
                'title'=>'[str]Database password[/str]',
                'description' => '[str]Required for MySQL and PostgreSQL installation types.[/str]',
                'attributes' =>array('style'=>'width:200px'),
                'default' => $defaults['db_pass']
                );
                
         $fields['db_prefix']=array(
                'type'=>'text',
                'required' => true,
                'title'=>'[str]Tables prefix[/str]',
                'description' => '[strf]Minimum required characters: %3%.[/strf]',
                'attributes' =>array('style'=>'width:200px'),
                'default' => $defaults['db_prefix']
                );
       
        $fields['database_separator']=array(
            'type'=>'html',
            'value' =>'<hr />' 
       );
       
       ################# Site Info #####################
       $fields['site_info_caption']=array(
            'type'=>'html',
            'value' =>'<h3>[str]Website Information[/str]</h3>' 
       );
       
       $fields['site_name']=array(
                'type'=>'text',
                'title'=>'[str]Website name[/str]',
                'description' => '[str]Specify your new website name here[/str]',
                'attributes' =>array('style'=>'width:300px'),
                'default' => $defaults['site_name']
                );
                
       $fields['site_slogan']=array(
                'type'=>'text',
                'title'=>'[str]Website slogan[/str]',
                'description' => '[str]Specify your new website slogan here[/str]',
                'attributes' =>array('style'=>'width:300px'),
                'default' => $defaults['site_slogan']
                );
       
       $fields['site_info_separator']=array(
            'type'=>'html',
            'value' =>'<hr />' 
       );
       
       ################# Super User ###################
       $fields['su_caption']=array(
            'type'=>'html',
            'value' =>'<h3>[str]Super-User[/str]</h3>' 
       );
       
       $fields['su_login']=array(
                'type'=>'text',
                'required' => true,
                'title'=>'[str]Super-User login[/str]',
                'description' => '[str]Enter login for System Super-User[/str]',
                'attributes' =>array('style'=>'width:200px'),
                'default' => $defaults['su_login']
                );
                
       $fields['su_password']=array(
                'type'=>'password',
                'required'=>true,
                'title'=>'[str]Password[/str]',
                'description' =>'[strf]Minimum required characters: %6%.[/strf]',
                'attributes' =>array('style'=>'width:200px'),
                'default' => $defaults['su_password']
                );
                
        $fields['su_password_re']=array(
                'type'=>'password',
                'required'=>true,
                'title'=>'[str]Retype password[/str]',
                'description' =>'[str]Repeat Super-User`s password[/str]',
                'attributes' =>array('style'=>'width:200px'),
                'default' => $defaults['su_password_re']
                );
                
        $fields['su_email']=array(
                'type'=>'email',
                'required' => true,
                'title'=>'[str]E-Mail[/str]',
                 'description' => '[str]Enter Super-User`s E-Mail address[/str]',
                 'attributes' =>array('style'=>'width:200px'),
                 'default' => $defaults['su_email']
                );
                
        $fields['su_separator']=array(
            'type'=>'html',
            'value' =>'<hr />' 
            );
            
        ###################### Modules ####################
        $default_modules=array();
        
        $fields['modules_caption']=array(
            'type'=>'html',
            'value' =>'<h3>[str]Modules[/str]</h3><div id="check_all"><a href="javascript:check_all()">[str]Install all[/str]</a></div><script language="JavaScript">function check_all() { $(\'input[type=checkbox]\').attr(\'checked\',\'checked\'); }</script>' 
       );
       
        $co=0;
        list($packages,$modules)=$GLOBALS['sawanna']->module->get_existing_packs(false);
        
        asort($packages);
        $packs=array();
        foreach ($packages as $module_dir=>$package)
        {
            $module_checked_default=array();
            if (in_array($module_dir,$default_modules)) $module_checked_default=array('checked'=>'checked');
            $module_checked=isset($_POST['module-install-'.$module_dir]) && $_POST['module-install-'.$module_dir]=='on' ? array('checked'=>'checked') : $module_checked_default;
            
            if (!in_array($package,$packs))
            {
                $packs[]=$package;
                $fields['package-'.$package]=array(
                    'type'=>'html',
                    'value' => '<div class="package">[str]Package[/str]: [str]'.$package.'[/str]</div>'
                );
            }
            
            $module=$modules[$module_dir];

            $GLOBALS['sawanna']->module->preload_module_language($module['root']);
            
            $dependent=$module['depend'];
            $dependent_str=!empty($dependent) ? '<div class="rel"><strong>[str]Depends from[/str]:</strong> '.$dependent.'</div>' : '';
            
            $co++;
            if ($co%2===0)
            {
                $fields['line-begin-'.$module_dir]=array(
                    'type'=>'html',
                    'value' => '<div class="line mark">'
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
                'value' => '<div class="title"><div class="caption"><strong>'.$module['title'].'</strong></div><div class="name"><strong>[str]Directory[/str]:</strong> '.$module['root'].'</div><p>[str]Description[/str]: '.$module['description'].'</p>'.$dependent_str.'</div>'
            );
                
            $fields['module-installed-'.$module_dir]=array(
                'title' => '<label for="module-installed-'.$module_dir.'">[str]Install[/str]</label>',
                'type' => 'checkbox',
                'attributes' => $module_checked
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
        
         $fields['modules_separator']=array(
            'type'=>'html',
            'value' =>'<hr />' 
            );

        return $fields;
}

function split_step_fields(&$fields,$step)
{
    if (!is_array($fields) || empty($fields) || !is_numeric($step) || $step<=0 || $step>5) return;

        $skip=array();
        
        switch ($step)
        {
            case 1:
                $skip=array(
                    'install_info_caption',
                    'root',
                    'directory',
                    'protocol',
                    'timezone',
                    'private_key',
                    'install_info_separator',
                    
                    'database_caption',
                    'db_mysql',
                    'db_pgsql',
                    'db_sqlite',
                    'db_host',
                    'db_port',
                    'db_name',
                    'db_user',
                    'db_pass',
                    'db_prefix',
                    'database_separator',
                    
                    'site_info_caption',
                    'site_name',
                    'site_slogan',
                    'site_info_separator',
                    
                    'su_caption',
                    'su_login',
                    'su_password',
                    'su_password_re',
                    'su_email',
                    'su_separator',
                    
                    'modules_caption',
                    'package-',
                    'line-begin-',
                    'module-name-',
                    'module-installed-',
                    'separator-',
                    'line-end-',
                    'modules_separator'
                );
                break;
            case 2:
                $skip=array(
                    'language_caption',
                    'language_',
                    'language_separator',
                    
                    'database_caption',
                    'db_mysql',
                    'db_pgsql',
                    'db_sqlite',
                    'db_host',
                    'db_port',
                    'db_name',
                    'db_user',
                    'db_pass',
                    'db_prefix',
                    'database_separator',
                    
                    'site_info_caption',
                    'site_name',
                    'site_slogan',
                    'site_info_separator',
                    
                    'su_caption',
                    'su_login',
                    'su_password',
                    'su_password_re',
                    'su_email',
                    'su_separator',
                    
                    'modules_caption',
                    'package-',
                    'line-begin-',
                    'module-name-',
                    'module-installed-',
                    'separator-',
                    'line-end-',
                    'modules_separator'
                );
                break;    
            case 3:
                $skip=array(
                    'language_caption',
                    'language_',
                    'language_separator',
                    
                    'install_info_caption',
                    'root',
                    'directory',
                    'protocol',
                    'timezone',
                    'private_key',
                    'install_info_separator',
                    
                    'site_info_caption',
                    'site_name',
                    'site_slogan',
                    'site_info_separator',
                    
                    'su_caption',
                    'su_login',
                    'su_password',
                    'su_password_re',
                    'su_email',
                    'su_separator',
                    
                    'modules_caption',
                    'package-',
                    'line-begin-',
                    'module-name-',
                    'module-installed-',
                    'separator-',
                    'line-end-',
                    'modules_separator'
                );
                break;    
            case 4:
                $skip=array(
                    'language_caption',
                    'language_',
                    'language_separator',
                    
                    'install_info_caption',
                    'root',
                    'directory',
                    'protocol',
                    'timezone',
                    'private_key',
                    'install_info_separator',
                    
                    'database_caption',
                    'db_mysql',
                    'db_pgsql',
                    'db_sqlite',
                    'db_host',
                    'db_port',
                    'db_name',
                    'db_user',
                    'db_pass',
                    'db_prefix',
                    'database_separator',

                    'modules_caption',
                    'package-',
                    'line-begin-',
                    'module-name-',
                    'module-installed-',
                    'separator-',
                    'line-end-',
                    'modules_separator'
                );
                break;
            case 5:
                $skip=array(
                    'language_caption',
                    'language_',
                    'language_separator',
                    
                    'install_info_caption',
                    'root',
                    'directory',
                    'protocol',
                    'timezone',
                    'private_key',
                    'install_info_separator',
                    
                    'database_caption',
                    'db_mysql',
                    'db_pgsql',
                    'db_sqlite',
                    'db_host',
                    'db_port',
                    'db_name',
                    'db_user',
                    'db_pass',
                    'db_prefix',
                    'database_separator',
                    
                    'site_info_caption',
                    'site_name',
                    'site_slogan',
                    'site_info_separator',
                    
                    'su_caption',
                    'su_login',
                    'su_password',
                    'su_password_re',
                    'su_email',
                    'su_separator',
                );
                break;
        }
        
        if (empty($skip)) return;
        
        if ($step!=1)
        {
            $fields['language']=array(
                'type' => 'hidden',
                'value' => isset($_POST['language']) ? $_POST['language'] : ''
            );
        }
        
        if ($step!=3)
        {
            $fields['database']=array(
                'type' => 'hidden',
                'value' => isset($_POST['database']) ? $_POST['database'] : ''
            );
        }
        
        foreach ($fields as $fid=>$field)
        {
            $no_match=false;
            if (!in_array($fid,$skip))
            {
                $no_match=true;
                $skipped=false;
                foreach ($skip as $sfid)
                {
                    $skipped=true;
                    if (strpos($fid,$sfid)===0)
                    {
                        $skipped=false;
                        break;
                    }
                }
                
                if ($skipped) continue;
            }
            
            if ($no_match || substr($fid,-8) == '_caption' || substr($fid,-10) == '_separator' || $fid=='db_mysql' || $fid=='db_pgsql' || $fid=='db_sqlite') 
            {
                unset($fields[$fid]);
                continue;
            }
            
            $fields[$fid]['type']='hidden';
            unset($fields[$fid]['title']);
            unset($fields[$fid]['description']);
            unset($fields[$fid]['required']);
        }
}

function install_form($step)
{
    if (!is_numeric($step) || $step<=0 || $step>6) return;
    
    $url=isset($_GET['language']) ? '?language='.$_GET['language'] : '?';
    $url='?';
    $prev_url=$url;
    $url=$GLOBALS['sawanna']->constructor->add_url_args($url,array('step'=>$step));
    if ($step>2) $prev_url=$GLOBALS['sawanna']->constructor->add_url_args($prev_url,array('step'=>$step-2));
    
    $fields=install_form_fields();
    split_step_fields($fields,$step);
    
     ######################## Form Footer ##########################    
    $fields['fname']=array(
            'type'=>'hidden',
            'value'=>'install_form'
            );
            
    if ($step>1)
    {
        $fields['prev']=array(
                'type'=>'button',
                'value'=>'[str]< Previous[/str]',
                 'attributes' =>array('class'=>'form_button','onclick'=>"$('#install_form').attr('action','".$prev_url."');$('#install_form').get(0).submit();"),
                );
    }      
    $fields['next']=array(
            'type'=>'submit',
            'value'=>'[str]Next >[/str]',
             'attributes' =>array('class'=>'form_button'),
            );
    
    $fields['end']=array(
            'type'=>'html',
            'value'=>'<div class="clear"></div>'
            );
    
    $form=array(
        'id'=>'install_form',
        'url' => $url,
        'fields'=>$fields,
        'callback' => 'install_process',
        'fill' => 0
    );
       
    return $form;
}

function install_process(&$form,$step)
{
    if (isset($_GET['step'])) $_GET['step']--;
    
    if (empty($form) || !is_array($form)) return;
    if (!isset($form['fields']) || empty($form['fields']) || !is_array($form['fields'])) return;
    
    if ($step<=0 || $step>5) return ;
    
    $failed=false;
    foreach ($form['fields'] as $id=>$field)
    {
        if (isset($field['required']) && $field['required'] && empty($_POST[$id]))
        {
            sawanna_error('[str]Please fill out all required fields[/str]');
            $failed=true;
            break;
        }
    }
    if ($failed) return ;
    
    switch ($step)
    {
        case 1:
            // language
            if (empty($_REQUEST['language']))
            {
                sawanna_error('[str]Please choose language[/str]');
                return;
            }
            break;
        case 2:
            // host info
            if (!empty($_POST['root']) && !file_exists($_POST['root']))
            {
                sawanna_error('[str]Root directory not found. Are you sure, that it is correct ?[/str]');
            }
            
            $directory=substr($_POST['directory'],0,1)=='/' ? substr($_POST['directory'],1) : $_POST['directory'];
            if (!empty($directory) && !file_exists($_SERVER['DOCUMENT_ROOT'].'/'.$directory))
            {
                sawanna_error('[str]It seems that specified website directory not in the server document root. Are you sure, that it is correct ?[/str]');
            }
            
            if ($_POST['protocol']!='http' && $_POST['protocol']!='https')
            {
                sawanna_error('[str]Unknown protocol[/str]');
                return;
            }
            
            if (!empty($_POST['timezone']) && !valid_timezone($_POST['timezone']))
            {
                sawanna_error('[str]Invalid timezone[/str]');
                return;
            }
            
            if (sawanna_strlen($_POST['private_key']) < 12)
            {
                sawanna_error('[str]Private key is too short[/str]');
                return;
            }
            break;
        case 3:
            // database
            if (sawanna_strlen($_POST['db_prefix']) < 3)
            {
                sawanna_error('[str]Tables prefix is too short[/str]');
                return;
            }
            if (!$GLOBALS['sawanna']->db_test($_POST['database'],$_POST['db_host'],$_POST['db_port'],$_POST['db_user'],$_POST['db_pass'],$_POST['db_name'],$_POST['db_prefix']))
            {
                sawanna_error('[str]Database connection failed[/str]');
            }
            
            $GLOBALS['sawanna']->db=null;
            break;
        case 4:     
            // su, site info
            if ($_POST['su_password'] != $_POST['su_password_re'])
            {
                sawanna_error('[str]Passwords do not match[/str]');
                return;
            }
            
            if (sawanna_strlen($_POST['su_password']) < 6)
            {
                sawanna_error('[str]Password is too short[/str]');
                return;
            }
            
            if (!valid_email($_POST['su_email']))
            {
                sawanna_error('[str]Invalid E-Mail address[/str]');
                return;
            }
            break;
        case 5:
            // modules, final test
            if (
                   empty($_REQUEST['language'])
                || empty($_POST['directory'])
                || empty($_POST['protocol'])
                || empty($_POST['private_key'])
                || empty($_POST['database'])
                || empty($_POST['db_host'])
                || empty($_POST['db_port'])
                || empty($_POST['db_user'])
                || empty($_POST['db_name'])
                || empty($_POST['db_prefix'])
                || empty($_POST['su_login'])
                || empty($_POST['su_password'])
                || empty($_POST['su_email'])
            ) {
                sawanna_error('[str]Some required information missing. Please go back and fill out all required fields[/str]');
                return;
            }
            
            if (!file_exists(ROOT_DIR.'/settings.php'))
            {
                sawanna_error('[strf]File %settings.php% not found[/strf]');
                return;
            }
            
            if (!is_writable(ROOT_DIR.'/settings.php'))
            {
                sawanna_error('[strf]File %settings.php% is not writable[/strf]');
                return;
            }
            
            if (!sawanna_do_install())
            {
                sawanna_error('[strf]Installation failed[/strf]');
                return;
            }
            
            sawanna_message('[str]Sawanna installed successfully.[/str] [strf]You can now set %settings.php% file`s permissions to 0644[/strf]');
            break;
    }
    
    
    if (isset($_GET['step'])) $_GET['step']++;
}

function sawanna_do_install()
{
    $settings_file='settings.php';
    
     $language=$GLOBALS['sawanna']->locale->current;
     
     if (!empty($_POST['root']))
     {
         $root=strip_tags($_POST['root']);
         if (substr($root,-1)=='/') $root=substr($root,0,sawanna_strlen($root)-1);
         $root_dir="'".$root."'";
     } else 
     {
         $root_dir="'.'";
     }
     
     $_POST['directory']=str_replace('\\','/',$_POST['directory']);
     $directory=substr($_POST['directory'],0,1)=='/' ? substr($_POST['directory'],1) : $_POST['directory'];
     if (substr($directory,-1)=='/') $directory=substr($directory,0,sawanna_strlen($directory)-1);
     $site_host=!empty($directory) ? '$_SERVER[\'HTTP_HOST\'].\'/'.$directory.'\'' : '$_SERVER[\'HTTP_HOST\']';
     
     $protocol=$_POST['protocol'];
     $private_key=$_POST['private_key'];
     $database=$_POST['database'];
     $db_host=$_POST['db_host'];
     $db_port=$_POST['db_port'];
     $db_user=$_POST['db_user'];
     $db_pass=isset($_POST['db_pass']) ? $_POST['db_pass'] : '';
     $db_name=$_POST['db_name'];
     $db_prefix=str_replace('-','_',$_POST['db_prefix']);
     $site_name=isset($_POST['site_name']) ? strip_tags($_POST['site_name']) : '';
     $site_slogan=isset($_POST['site_slogan']) ? strip_tags($_POST['site_slogan']) : '';
     $su_login=$_POST['su_login'];
     $su_password=$_POST['su_password'];
     $su_email=$_POST['su_email'];
     $timezone=isset($_POST['timezone']) ? $_POST['timezone'] : '';
     
     $base_path=!empty($directory) ? '/'.$directory : '';
    
     ############# writing settings #############
     if (!empty($timezone) && function_exists('date_default_timezone_set')) date_default_timezone_set($timezone);
     
    $file=fopen(ROOT_DIR.'/'.$settings_file,'w');
    if (!$file) return false;
    
    $settings='<?php'."\n";
    $settings.='// Created during installation on '.date('d.m.Y')."\n";
    $settings.="\n";
    $settings.='defined(\'SAWANNA\') or die();'."\n";
    $settings.="\n";
    $settings.='define(\'ROOT_DIR\','.$root_dir.');'."\n";
    $settings.='define(\'HOST\','.$site_host.');'."\n";
    $settings.='define(\'PROTOCOL\',\''.$protocol.'\');'."\n";
    $settings.='define(\'BASE_PATH\',\''.$base_path.'\');'."\n";
    $settings.="\n";
    $settings.='define(\'DB_TYPE\',\''.$database.'\');'."\n";
    $settings.='define(\'DB_HOST\',\''.$db_host.'\');'."\n";
    $settings.='define(\'DB_PORT\',\''.$db_port.'\');'."\n";
    $settings.='define(\'DB_NAME\',\''.$db_name.'\');'."\n";
    $settings.='define(\'DB_USER\',\''.$db_user.'\');'."\n";
    $settings.='define(\'DB_PASS\',\''.$db_pass.'\');'."\n";
    $settings.='define(\'DB_PREFIX\',\''.$db_prefix.'\');'."\n";
    $settings.="\n";
    $settings.='define(\'PRIVATE_KEY\',\''.$private_key.'\');';
    $settings.="\n";
    $settings.='define(\'TIMEZONE\',\''.$timezone.'\');';

    fwrite($file,$settings);
    fclose($file);
    
    ############## installing database #############
    if (!$GLOBALS['sawanna']->db_test($database,$db_host,$db_port,$db_user,$db_pass,$db_name,$db_prefix)) return false;
    
    $config=$GLOBALS['sawanna']->config->defaults();
    $config['language']=$language;
    $config['admin_area_language']=$language;
    
    $langs=$GLOBALS['sawanna']->locale->get_installed_languages();
    $languages=array();
    foreach ($langs as $lname=>$linfo)
    {
        $languages[$lname]=$linfo['name'];
    }
    
    $config['languages']=array_key_exists($language,$languages) ? array($language=>$languages[$language]) : array($language=>$language);
    $GLOBALS['sawanna']->locale->languages=$config['languages'];
            
    $config['site_name']=$site_name;
    $config['site_slogan']=$site_slogan;
    
    $config['send_mail_from']=$su_email;
    if (!empty($site_name)) $config['send_mail_from_name']=$site_name;

    $config['su']=array('user'=>$su_login, 'password'=>$GLOBALS['sawanna']->access->pwd_hash($su_password,$private_key), 'email'=>$su_email);
    
    if (!$GLOBALS['sawanna']->install_core_tables($config)) return false;
    
    ############### installing modules ############
    $GLOBALS['sawanna']->access->db=&$GLOBALS['sawanna']->db;
    $GLOBALS['sawanna']->node->db=&$GLOBALS['sawanna']->db;
    $GLOBALS['sawanna']->navigator->db=&$GLOBALS['sawanna']->db;
    $GLOBALS['sawanna']->module->db=&$GLOBALS['sawanna']->db;
    $GLOBALS['sawanna']->config->su=$config['su'];
    
    foreach ($_POST as $key=>$value)
    {
        if (preg_match('/^module-installed-(.+)$/',$key,$matches))
        {
            if(!$GLOBALS['sawanna']->module->install_module($matches[1])) 
                sawanna_error('[strf]Failed to install module %'.$matches[1].'%[/strf]');
            else 
                $GLOBALS['sawanna']->module->refresh_options();
        }
    }
    
    $GLOBALS['sawanna']->db=null;
    
    return true;
}

function sawanna_welcome()
{
    $html='<h2>Sawanna CMS ver.'.VERSION.'</h2>';
    $html.='<p>[str]Sawana CMS requires write permissions to following directories[/str]:</p>';
    $html.='<ul><li>uploads</li><li>cache</li><li>log</li></ul>';
    $html.='<p align="right">'.$GLOBALS['sawanna']->constructor->link($GLOBALS['sawanna']->config->admin_area,'[str]Log in to Admin-Area[/str]').'</p>';
    return $html;
}

function sawanna_process()
{
    $GLOBALS['sawanna']->init();
    
    $GLOBALS['sawanna']->config->site_name='Sawanna CMS';
    $GLOBALS['sawanna']->config->site_logo='misc/logo.png';
    $GLOBALS['sawanna']->config->site_slogan='ver.'.VERSION;
    
    $GLOBALS['sawanna']->tpl->params['head'].='<style>';
    $GLOBALS['sawanna']->tpl->params['head'].='p {margin: 0px; padding: 0px; text-indent: 0px !important;}';
    $GLOBALS['sawanna']->tpl->params['head'].='.form_item { margin: 0px;}';
    $GLOBALS['sawanna']->tpl->params['head'].='.form_title {float: left; width: 150px; height: 20px; line-height: 20px; text-align: right; font-weight: bold}';
    $GLOBALS['sawanna']->tpl->params['head'].='.form_element {margin: 5px 20px 10px 160px;}';
    $GLOBALS['sawanna']->tpl->params['head'].='.form_description {margin: 0px 100px 20px 100px; font-style: italic}';
    $GLOBALS['sawanna']->tpl->params['head'].='.form_button {float: left; margin: 20px 10px}';
    $GLOBALS['sawanna']->tpl->params['head'].='hr {margin: 20px 100px;}';
    $GLOBALS['sawanna']->tpl->params['head'].='.mark {background-color: #fef4d6}';
    $GLOBALS['sawanna']->tpl->params['head'].='.package {border-bottom: 1px dotted #000000; background-color: #ede4d4; font-size: 13px; font-style:italic; font-weight: bold; color: #000000;padding: 4px; margin: 5px 0px}';
    $GLOBALS['sawanna']->tpl->params['head'].='.line {padding: 5px}';
    $GLOBALS['sawanna']->tpl->params['head'].='.caption {font-size: 14px;height: 30px; line-height: 30px}';
    $GLOBALS['sawanna']->tpl->params['head'].='#check_all {text-align: right}';
    $GLOBALS['sawanna']->tpl->params['head'].='</style>';
   
    if (!sawanna_installed())
    {
        $GLOBALS['sawanna']->choose_lang();
        $GLOBALS['sawanna']->process_forms();
        
        $step=isset($_GET['step']) && is_numeric($_GET['step']) && $_GET['step']>0 && $_GET['step']<6 ? $_GET['step'] : 0;
        
        if ($step!=5)
        {
            $install_form=install_form(++$step);
            $html=$GLOBALS['sawanna']->form->render($install_form);
        } else 
        {
            $html=sawanna_welcome();
        }
    } else 
    {
        sawanna_error('[strf]Installation aborted. File %settings.php% is not empty[/strf]');
    }
    
    if (DEBUG_MODE && isset($_GET['reinstall']))
    {
        if ($GLOBALS['sawanna']->process_reinstall_request($_GET['reinstall']))
            sawanna_message('Reinstall done');
        else 
            sawanna_error('Reinstall failed');
    }
    
    $output=$GLOBALS['sawanna']->process($html);
    $GLOBALS['sawanna']->output($output);

}

function sawanna()
{
    error_reporting(E_ALL);
    set_error_handler('sawanna_set_error');
    register_shutdown_function('sawanna_shutdown');
    ob_start();
    
    $GLOBALS['sawanna']=new CSawannaInstaller();
    $GLOBALS['sawanna']->fix_php_options();
    sawanna_process();
    
}

function sawanna_shutdown()
{ 
    if (is_object($GLOBALS['sawanna'])) $GLOBALS['sawanna']->clear();
    
    sawanna_show_errors();
    sawanna_show_messages();
    
    sawanna_show_usage();
}

function sawanna_installed()
{
    if (!file_exists(ROOT_DIR.'/settings.php')) return false;
    
    $settings=file_get_contents(ROOT_DIR.'/settings.php');
    if (strlen($settings)>1) return true;
    
    return false;
}

sawanna();
