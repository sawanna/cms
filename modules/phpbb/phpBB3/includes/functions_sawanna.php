<?php

function sawanna_init() {
	global $phpbb_root_path;
	
	if (defined('DISABLE_SAWANNA')) return;
	
	define('SAWANNA',1);
	define('ROOT_DIR',(defined('SAWANNA_PATH') && file_exists(SAWANNA_PATH) ? SAWANNA_PATH : $phpbb_root_path.'../../..'));
	
	include_once(ROOT_DIR .'/settings.php');
	include_once(ROOT_DIR .'/sawanna/functions.php');

	if (!check_install()) return;
			
	include_once (ROOT_DIR .'/sawanna/lite.php');
	sawanna();
}

function sawanna_login($login,$password) {
	if (empty($login) || empty($password)) return;
	if (!isset($GLOBALS['sawanna']) || !is_object($GLOBALS['sawanna'])) return;
	
	$user=$GLOBALS['sawanna']->load_module('user');
	
	return $user->external_login($login,$password);
}

function sawanna_logout() {
	if (!isset($GLOBALS['sawanna']) || !is_object($GLOBALS['sawanna'])) return;
	
	$user=$GLOBALS['sawanna']->load_module('user');
	
	return $user->external_logout();
}

function sawanna_register($login,$password,$email) {
	if (empty($login) || empty($password) || empty($email)) return;
	if (!isset($GLOBALS['sawanna']) || !is_object($GLOBALS['sawanna'])) return;
	
	$user=$GLOBALS['sawanna']->load_module('user');
	
	return $user->external_register($login,$password,$email);
}

function sawanna_menu() {
	if (!isset($GLOBALS['sawanna']) || !is_object($GLOBALS['sawanna'])) return;
	
	$menu=$GLOBALS['sawanna']->load_module('menu');
	
	$phpbb_url=$GLOBALS['sawanna']->get_option('phpbb_url');
	$GLOBALS['sawanna']->query=$phpbb_url ? $phpbb_url : SITE.'/forum';
	$GLOBALS['sawanna']->locale->init($GLOBALS['user']->lang_name);
	$result=$menu->parent_menu('forum');
	
	return $result['parent-menu'];
}