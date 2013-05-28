<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$files_module=array(
'name' => 'files',
'title' => '[str]Files[/str]',
'description' => '[str]Upload and manage files[/str]',
'root' => 'files',
'enabled' => 1,
'package' => 'Content',
'paths' => array('files_name_autocheck','files_alias_autocheck'),
'permissions' => array('files_upload_permission'),
'admin-area-class' => 'CFilesAdmin',
'version' => '3.2-1'
);

#################################################################################
# URL paths definition
#################################################################################

$files_name_autocheck=array(
'path' => 'file-name-check',
'permission' => 'upload files',
'enabled' => 1,
'render' => 0 
);

$files_alias_autocheck=array(
'path' => 'file-alias-check',
'permission' => 'upload files',
'enabled' => 1,
'render' => 0 
);

#################################################################################
# Permissions definition
#################################################################################

$files_upload_permission=array(
'name' => 'upload files',
'title' => '[str]Manage files[/str]',
'description' => '[str]Upload, edit or delete files[/str]',
'access' => 0
);
