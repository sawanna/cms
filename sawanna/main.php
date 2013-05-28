<?php 
defined('SAWANNA') or die();

require_once(ROOT_DIR.'/config.php');
require_once(ROOT_DIR.'/sawanna/sawanna.inc.php');
require_once(ROOT_DIR.'/sawanna/common.inc.php');

function sawanna()
{
    error_reporting(E_ALL);
    set_error_handler('sawanna_set_error');
    register_shutdown_function('sawanna_shutdown');
    
    if (function_exists('mb_internal_encoding')) mb_internal_encoding('UTF-8');
    if (function_exists('mb_regex_encoding')) mb_regex_encoding("UTF-8");

    $GLOBALS['sawanna']=new CSawanna();
    if (DEBUG_MODE) exec_time();
    $GLOBALS['sawanna']->fix_php_options();
    sawanna_process();
}

function sawanna_process()
{
    if (!$GLOBALS['sawanna']->init())
    {
        if (!DEBUG_MODE)
        {
            include_once(ROOT_DIR.'/sawanna/tpl.inc.php');
            switch ($GLOBALS['sawanna']->status)
            {
                case 0:
                    header('HTTP/1.1 200 OK');
                    echo $GLOBALS['sawanna']->tpl->site_offline_message();
                    break;
                case 500:
                    header('HTTP/1.1 500 Internal Server Error');
                    echo CTemplate::failure_message();
                    break;
                case 404:
                    header('HTTP/1.1 404 Not Found');
                    echo CTemplate::not_found_message();
                    break;
                case 403:
                        header('HTTP/1.1 403 Forbidden');
                        echo CTemplate::forbidden_message();
                    break;
                default:
                    header('HTTP/1.1 500 Internal Server Error');
                    echo CTemplate::failure_message();
                    break;
            }
        }
        
        return;
    }
   
    if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) $GLOBALS['sawanna']->tpl->html.='<!--Initialization time: '.exec_time(1).'-->'."\n";
    $GLOBALS['sawanna']->process_forms();
    if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) $GLOBALS['sawanna']->tpl->html.='<!--Forms processing time: '.exec_time().'-->'."\n";
    $GLOBALS['sawanna']->process_events();
    if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) $GLOBALS['sawanna']->tpl->html.='<!--Events processing time: '.exec_time().'-->'."\n";

    $output=$GLOBALS['sawanna']->process();
    if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) $output.="\n".'<!--Total execution time: '.exec_time(1).'-->';
    if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) $output.="\n".'<!--Total data unserialization time: '.data_exec_time(1,1).'-->';
    $GLOBALS['sawanna']->output($output);
}

function sawanna_shutdown()
{ 
    if (is_object($GLOBALS['sawanna'])) $GLOBALS['sawanna']->clear();
    
    sawanna_show_errors();
    sawanna_show_messages();
    
    sawanna_show_usage();
}
