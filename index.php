<?php 
define('SAWANNA',1);
ob_start();

@include_once('./settings.php');
require_once('./sawanna/functions.php');

if (!check_install()) force_install();
        
require_once ('./sawanna/main.php');
sawanna();
