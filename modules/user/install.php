<?php
defined('SAWANNA') or dir();

#################################################################################
# Post-install callback
#################################################################################

function user_install($module_id)
{
    global $sawanna;
    
    $sawanna->db->query("insert into pre_user_groups (name,created,enabled) values ('$1','$2','$3')",'Administrators',time(),1);
    $sawanna->db->query("insert into pre_user_groups (name,created,enabled) values ('$1','$2','$3')",'Users',time(),1);
    $sawanna->db->query("insert into pre_user_group_permissions (group_id,permission,access) values ('$1','$2','$3')",1,'su',1);
    
    $guests_group_perm_name='user group 0 access';
    $group_perm=array(
        'name' => $guests_group_perm_name,
        'title' => '[strf]%Guests% group common access[/strf]',
        'description' => '[str]By default this permission granted only to its group[/str]',
        'access' => 1,
        'module' => $module_id
    );
    
    $sawanna->access->set($group_perm);
    
    $admin_group_perm_name='user group 1 access';
    $group_perm=array(
        'name' => $admin_group_perm_name,
        'title' => '[strf]%Administrators% group common access[/strf]',
        'description' => '[str]By default this permission granted only to its group[/str]',
        'access' => 0,
        'module' => $module_id
    );
    
    $sawanna->access->set($group_perm);
    
    $sawanna->db->query("insert into pre_user_group_permissions (permission,access,group_id) values ('$1','$2','$3')",$admin_group_perm_name,1,1);
    
    $admin_group_perm_name='user group 0 access';
    $sawanna->db->query("insert into pre_user_group_permissions (permission,access,group_id) values ('$1','$2','$3')",$admin_group_perm_name,0,1);
            
    $user_group_perm_name='user group 2 access';
    $group_perm=array(
        'name' => $user_group_perm_name,
        'title' => '[strf]%Users% group common access[/strf]',
        'description' => '[str]By default this permission granted only to its group[/str]',
        'access' => 0,
        'module' => $module_id
    );
    
    $sawanna->access->set($group_perm);
    
    $sawanna->db->query("insert into pre_user_group_permissions (permission,access,group_id) values ('$1','$2','$3')",$user_group_perm_name,1,2);
    
    $user_group_perm_name='user group 0 access';
    $sawanna->db->query("insert into pre_user_group_permissions (permission,access,group_id) values ('$1','$2','$3')",$user_group_perm_name,0,2);
            
    $sawanna->db->query("insert into pre_users (login,password,email,group_id,created,active,enabled) values ('$1','$2','$3','$4','$5','$6','$7')",$sawanna->config->su['user'],$sawanna->config->su['password'],$sawanna->config->su['email'],1,time(),1,1);
    
    $n_handler=array(
        'object' => 'userAdminHandler',
        'function' => 'node_group_access_field'
    );
    
    $m_handler=array(
        'object' => 'userAdminHandler',
        'function' => 'menu_group_access_field'
    );
    
    $w_handler=array(
        'object' => 'userAdminHandler',
        'function' => 'widget_group_access_field'
    );
    
    $validate=array(
        'object' => 'userAdminHandler',
        'function' => 'group_access_field_validate'
    );
    
    $n_process=array(
        'object' => 'userAdminHandler',
        'function' => 'node_group_access_field_process'
    );
    
    $m_process=array(
        'object' => 'userAdminHandler',
        'function' => 'menu_group_access_field_process'
    );
    
     $w_process=array(
        'object' => 'userAdminHandler',
        'function' => 'widget_group_access_field_process'
    );

    $sawanna->form->add_form_extra_fields('node_create_form_post_fields',$n_handler,$validate,$n_process,$module_id);
    $sawanna->form->add_form_extra_fields('menu_create_form_extra_fields',$m_handler,$validate,$m_process,$module_id);
    $sawanna->form->add_form_extra_fields('widget_create_form_extra_fields',$w_handler,$validate,$w_process,$module_id);
}