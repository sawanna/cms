<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$messages_module=array(
'name' => 'messages',
'title' => '[str]Messages[/str]',
'description' => '[str]Adds the ability to send private messages[/str]',
'root' => 'messages',
'enabled' => 1,
'package' => 'Users',
'depend' => 'user',
'widgets' => array('messages_inbox_widget','messages_outbox_widget','messages_form_widget'),
'paths' => array('messages_inbox_path','messages_inbox_offset_path','messages_outbox_path','messages_outbox_offset_path','messages_send_path','messages_send_arg_path','messages_login_autoload_path','messages_reply_path','messages_delete_path'),
'permissions' => array('messages_send_permission'),
'schemas' => array('messages_schema'),
'version' => '3.2-1'
);

#################################################################################
# Widgets definition
#################################################################################

$messages_inbox_widget=array(
'name' => 'inbox',
'title' => 'Incoming messages',
'description' => 'User inbox widget. NOT CONFIGURABLE',
'class' => 'CMessages',
'handler' => 'inbox',
'path' => "user/inbox",
'component' => '',
'permission' => '',
'weight' => 5,
'cacheable' => 0,
'enabled' => 1,
'fix' => 1
);

$messages_outbox_widget=array(
'name' => 'outbox',
'title' => 'Outcoming messages',
'description' => 'User outbox widget. NOT CONFIGURABLE',
'class' => 'CMessages',
'handler' => 'outbox',
'path' => "user/outbox",
'component' => '',
'permission' => '',
'weight' => 5,
'cacheable' => 0,
'enabled' => 1,
'fix' => 1
);

$messages_form_widget=array(
'name' => 'messagesForm',
'title' => 'New message form',
'description' => 'Displays the new message form. NOT CONFIGURABLE',
'class' => 'CMessages',
'handler' => 'messages_form',
'argument' => "%arg",
'path' => "user/send\nuser/reply",
'component' => '',
'permission' => 'send user messages',
'weight' => 5,
'cacheable' => 0,
'enabled' => 1,
'fix' => 1
);

#################################################################################
# URL paths definition
#################################################################################

$messages_inbox_path=array(
'path' => 'user/inbox',
'permission' => '',
'enabled' => 1,
'render' => 1 
);

$messages_inbox_offset_path=array(
'path' => 'user/inbox/d',
'permission' => '',
'enabled' => 1,
'render' => 1 
);

$messages_outbox_path=array(
'path' => 'user/outbox',
'permission' => '',
'enabled' => 1,
'render' => 1 
);

$messages_outbox_offset_path=array(
'path' => 'user/outbox/d',
'permission' => '',
'enabled' => 1,
'render' => 1 
);

$messages_send_path=array(
'path' => 'user/send',
'permission' => 'send user messages',
'enabled' => 1,
'render' => 1 
);

$messages_send_arg_path=array(
'path' => 'user/send/d',
'permission' => 'send user messages',
'enabled' => 1,
'render' => 1 
);

$messages_login_autoload_path=array(
'path' => 'user-messages-login-autocomplete',
'permission' => 'view user profile',
'enabled' => 1,
'render' => 0 
);

$messages_reply_path=array(
'path' => 'user/reply/d',
'permission' => 'send user messages',
'enabled' => 1,
'render' => 1 
);

$messages_delete_path=array(
'path' => 'user/mess-del',
'permission' => 'send user messages',
'enabled' => 1,
'render' => 0 
);

#################################################################################
# Permissions definition
#################################################################################

$messages_send_permission=array(
'name' => 'send user messages',
'title' => '[str]Send user private messages[/str]',
'description' => '[str]Privileged users will be able to send user private messages[/str]',
'access' => 1
);

#################################################################################
# Database tables definition
#################################################################################

$messages_schema=array(
'name' => 'pre_messages',
'fields' => array(
    'id' => array(
        'type' => 'id',
        'not null' => true
    ),
    'from_uid' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'to_uid' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'subject' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => true
    ),
    'created' => array(
        'type' => 'integer',
        'length' => 10,
        'not null' => true
    ),
    'content' => array(
        'type' => 'text',
        'not null' => true
    ),
    'mark' => array(
        'type' => 'string',
        'length' => 255,
        'not null' => false
    )
),
'primary key' => 'id',
'index' => array('from_uid','to_uid')
);
