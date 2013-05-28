<?php 
defined('SAWANNA') or die();

require_once(ROOT_DIR.'/config.php');
require_once(ROOT_DIR.'/sawanna/lite.inc.php');
require_once(ROOT_DIR.'/sawanna/common.inc.php');

function sawanna()
{
    register_shutdown_function('sawanna_shutdown');
    
    $GLOBALS['sawanna']=new CSawannaLite();
    
    sawanna_process();
}

function sawanna_process()
{
    if (!$GLOBALS['sawanna']->init()) return;
	
	// nothing goes here
}

function sawanna_shutdown()
{ 
    if (is_object($GLOBALS['sawanna'])) $GLOBALS['sawanna']->clear();
}
