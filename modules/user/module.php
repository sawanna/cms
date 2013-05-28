<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$user_module=array(
'name' => 'user',
'title' => '[str]Users[/str]',
'description' => '[str]Adds user support to your website[/str]',
'root' => 'user',
'enabled' => 1,
'package' => 'Users',
'widgets' => array('user_register_form_widget','user_recover_form_widget','user_confirm_form_widget','user_panel_widget','user_profile_widget','user_edit_profile_widget','user_list_widget','user_random_widget','user_login_form_widget','user_online_widget'),
'paths' => array('user_path','user_profile_path','user_list_path','user_list_offset_path','user_register_path','user_recover_path','user_confirm_path','user_edit_path','user_login_path','user_logout_path','user_login_check_path','user_password_check_path','user_email_check_path','user_extra_field_name_check_path'),
'permissions' => array('user_create_perm','user_create_without_confirm_perm','user_assign_group_perm','user_create_group_perm','user_create_extra_fields_perm','user_assign_messages_perm','user_view_profile_perm','user_view_list','user_administer_perm'),
'options' => array('user_default_group'=>2),
'schemas' => array('user_schema','user_group_schema','user_perms_schema','user_extra_fields_schema','user_extra_field_values_schema','user_messages_schema','user_activity_schema'),
'admin-area-class' => 'CUserAdmin',
'version' => '3.4-1'
);

#################################################################################
# Widgets definition
#################################################################################

$user_register_form_widget=array(
'name' => 'userRegForm',
'title' => 'User register form',
'description' => 'Required, do not remove !',
'class' => 'CUser',
'handler' => 'register_form',
'path' => "user/register",
'component' => '',
'permission' => 'create user',
'weight' => -99,
'cacheable' => 0,
'enabled' => 1,
'fix' => 1
);

$user_recover_form_widget=array(
'name' => 'userRecoverForm',
'title' => 'User password recover form',
'description' => 'Required, do not remove !',
'class' => 'CUser',
'handler' => 'recover_form',
'path' => "user/recover",
'component' => '',
'permission' => '',
'weight' => -99,
'cacheable' => 0,
'enabled' => 1,
'fix' => 1
);

$user_confirm_form_widget=array(
'name' => 'userConfirmForm',
'title' => 'User E-Mail confirmation form',
'description' => 'Required, do not remove !',
'class' => 'CUser',
'handler' => 'confirm_form',
'path' => "user/confirm",
'component' => '',
'permission' => '',
'weight' => -99,
'cacheable' => 0,
'enabled' => 1,
'fix' => 1
);

$user_panel_widget=array(
'name' => 'userPanel',
'title' => '[str]Quick login[/str]',
'description' => '[str]Displays panel with authorization form[/str]',
'class' => 'CUser',
'handler' => 'panel',
'path' => "!user/login\n!user/register\n!user/confirm\n!user/recover\n*",
'component' => 'right;left-single',
'permission' => '',
'weight' => -1,
'cacheable' => 0,
'enabled' => 1
);

$user_profile_widget=array(
'name' => 'userProfile',
'title' => 'User profile',
'description' => 'Required, do not remove !',
'class' => 'CUser',
'handler' => 'profile',
'argument' => '%arg',
'path' => "user\nuser/*",
'component' => '',
'permission' => '',
'weight' => 0,
'cacheable' => 0,
'enabled' => 1,
'fix' => 1
);

$user_edit_profile_widget=array(
'name' => 'userProfileEdit',
'title' => 'User profile edit form',
'description' => 'Required, do not remove !',
'class' => 'CUser',
'handler' => 'profile_edit_form',
'argument' => '%arg',
'path' => "user/edit",
'component' => '',
'permission' => '',
'weight' => 0,
'cacheable' => 0,
'enabled' => 1,
'fix' => 1
);

$user_list_widget=array(
'name' => 'userList',
'title' => 'User list',
'description' => 'Required, do not remove !',
'class' => 'CUser',
'handler' => 'user_list',
'argument' => '%arg',
'path' => "users\nusers/*",
'component' => '',
'permission' => 'view users list',
'weight' => 0,
'cacheable' => 0,
'enabled' => 1,
'fix' => 1
);

$user_random_widget=array(
'name' => 'randomUser',
'title' => '[str]Random user[/str]',
'description' => '[str]Displays random user information[/str]',
'class' => 'CUser',
'handler' => 'random_user',
'path' => "*",
'component' => 'right;left-single',
'permission' => 'view user profile',
'weight' => 0,
'cacheable' => 0,
'enabled' => 1
);

$user_login_form_widget=array(
'name' => 'userLoginForm',
'title' => 'User login form',
'description' => 'Required, do not remove !',
'class' => 'CUser',
'handler' => 'login_form',
'path' => "user/login",
'component' => '',
'permission' => '',
'weight' => -99,
'cacheable' => 0,
'enabled' => 1,
'fix' => 1
);

$user_online_widget=array(
'name' => 'usersOnline',
'title' => '[str]Who is online[/str]',
'description' => '[str]Displays the list of logged in users[/str]',
'class' => 'CUser',
'handler' => 'online',
'path' => "*",
'component' => 'right;left-single',
'permission' => '',
'weight' => 0,
'cacheable' => 0,
'enabled' => 1,
'fix' => 0
);

#################################################################################
# URL paths definition
#################################################################################

$user_path=array(
'path' => 'user',
'permission' => '',
'enabled' => 1,
'render' => 1 
);

$user_profile_path=array(
'path' => 'user/d',
'permission' => 'view user profile',
'enabled' => 1,
'render' => 1 
);

$user_list_path=array(
'path' => 'users',
'permission' => 'view users list',
'enabled' => 1,
'render' => 1 
);

$user_list_offset_path=array(
'path' => 'users/d',
'permission' => 'view users list',
'enabled' => 1,
'render' => 1 
);

$user_register_path=array(
'path' => 'user/register',
'permission' => 'create user',
'enabled' => 1,
'render' => 1
);

$user_recover_path=array(
'path' => 'user/recover',
'permission' => '',
'enabled' => 1,
'render' => 1
);

$user_confirm_path=array(
'path' => 'user/confirm',
'permission' => '',
'enabled' => 1,
'render' => 1
);

$user_edit_path=array(
'path' => 'user/edit',
'permission' => '',
'enabled' => 1,
'render' => 1
);

$user_login_path=array(
'path' => 'user/login',
'permission' => '',
'enabled' => 1,
'render' => 1
);

$user_logout_path=array(
'path' => 'user/logout',
'permission' => '',
'enabled' => 1,
'render' => 0
);

$user_login_check_path=array(
'path' => 'user-login-check',
'permission' => 'create user',
'enabled' => 1,
'render' => 0
);

$user_password_check_path=array(
'path' => 'user-password-check',
'permission' => 'create user',
'enabled' => 1,
'render' => 0
);

$user_email_check_path=array(
'path' => 'user-extra-field-check',
'permission' => 'create user extra fields',
'enabled' => 1,
'render' => 0
);

$user_extra_field_name_check_path=array(
'path' => 'user-email-check',
'permission' => 'create user',
'enabled' => 1,
'render' => 0
);

#################################################################################
# Permissions definition
#################################################################################

$user_create_perm=array(
'name' => 'create user',
'title' => '[str]Register user[/str]',
'description' => '[str]Users will not be able to register if disabled[/str]',
'access' => 1
);

$user_create_without_confirm_perm=array(
'name' => 'create user without confirmation',
'title' => '[str]Register user without confirmation[/str]',
'description' => '[str]Privileged users will be able to register user without E-Mail confirmation[/str]',
'access' => 0
);

$user_create_group_perm=array(
'name' => 'create user groups',
'title' => '[str]Create and edit user groups[/str]',
'description' => '[str]Privileged users will be able to manage user groups[/str]',
'access' => 0
);

$user_assign_group_perm=array(
'name' => 'change user group',
'title' => '[str]Change user group[/str]',
'description' => '[str]Privileged users will be able to change user group[/str]',
'access' => 0
);

$user_create_extra_fields_perm=array(
'name' => 'create user extra fields',
'title' => '[str]Create and edit user extra fields[/str]',
'description' => '[str]Privileged users will be able to manage user profile extra fields[/str]',
'access' => 0
);

$user_assign_messages_perm=array(
'name' => 'change user messages',
'title' => '[str]Change user messages[/str]',
'description' => '[str]Privileged users will be able to change text of messages shown in various user actions[/str]',
'access' => 0
);

$user_view_profile_perm=array(
'name' => 'view user profile',
'title' => '[str]View user profile[/str]',
'description' => '[str]Privileged users will be able to access users profiles[/str]',
'access' => 1
);

$user_view_list=array(
'name' => 'view users list',
'title' => '[str]View users list[/str]',
'description' => '[str]Privileged users will be able to view registered users list[/str]',
'access' => 1
);

$user_administer_perm=array(
'name' => 'administer users',
'title' => '[str]Administer users[/str]',
'description' => '[str]Privileged users will be able to administer registered users[/str]',
'access' => 0
);

#################################################################################
# Database tables definition
#################################################################################

$user_schema=array(
'name' => 'pre_users',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
   'login' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
   'password' => array(
        'type' => 'string',
        'length' => 32,
        'not null' => true
    ),
    'email' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'group_id' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
     'created' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'active' => array(
        'type' => 'integer',
        'length' => 4,
        'not null' => true
    ),
    'enabled' => array(
        'type' => 'integer',
        'length' => 4,
        'not null' => true
    ),
    'meta' => array(
        'type' => 'longtext',
        'not null' => false
    ),
    'mark' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => false
    )
),
'primary key' => 'id',
'unique' => array('login','email'),
'index' => array('active','enabled')
);

$user_group_schema=array(
'name' => 'pre_user_groups',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
   'name' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'created' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'enabled' => array(
        'type' => 'integer',
        'length' => 4,
        'not null' => true
    ),
    'meta' => array(
        'type' => 'longtext',
        'not null' => false
    ),
    'mark' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => false
    )
),
'primary key' => 'id',
'unique' => 'name'
);

$user_perms_schema=array(
'name' => 'pre_user_group_permissions',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
   'group_id' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'permission' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'access' => array(
        'type' => 'integer',
        'length' => 4,
        'not null' => true
    ),
    'mark' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => false
    )
),
'primary key' => 'id',
'index' => 'group_id'
);

$user_extra_fields_schema=array(
'name' => 'pre_user_extra_fields',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
   'name' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'type' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'required' => array(
        'type' => 'integer',
        'length' => 4,
        'not null' => true
    ),
    'weight' => array(
        'type' => 'integer',
        'length' => 4,
        'not null' => true
    ),
    'meta' => array(
        'type' => 'longtext',
        'not null' => false
    ),
    'mark' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => false
    )
),
'primary key' => 'id'
);

$user_extra_field_values_schema=array(
'name' => 'pre_user_extra_field_values',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
   'field_id' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'user_id' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'val' => array(
        'type' => 'longtext',
        'not null' => true
    ),
    'mark' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => false
    )
),
'primary key' => 'id',
'index' => array('field_id','user_id')
);

$user_messages_schema=array(
'name' => 'pre_user_messages',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
   'name' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'content' => array(
        'type' => 'text',
        'not null' => true
    ),
    'language' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'mark' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => false
    )
),
'primary key' => 'id'
);

$user_activity_schema=array(
'name' => 'pre_user_activity',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
   'uid' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'ip' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'ua' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'tstamp' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'mark' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => false
    )
),
'primary key' => 'id',
'index' => 'uid'
);
