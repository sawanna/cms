<?php
defined('SAWANNA') or die();

#################################################################################
# Module definition
#################################################################################

$comments_module=array(
'name' => 'comments',
'title' => '[str]Comments[/str]',
'description' => '[str]Adds the ability to comment nodes[/str]',
'root' => 'comments',
'enabled' => 1,
'package' => 'Content',
'depend' => 'content, user',
'widgets' => array('comments_output_widget','latest_comments_widget','comments_form_widget','comments_user_comments_output_widget'),
'paths' => array('comments_user_comments_path','comments_user_comments_offset_path'),
'options' => array('comments_count_per_page'=>10,'comments_text_max_length'=>3000,'comments_word_max_chars'=>50,'comments_notification_emails'=>$GLOBALS['sawanna']->config->su['email'],'comments_stop_words'=>''),
'permissions' => array('comments_create_permission','comments_create_without_captcha_permission','comments_create_without_approval_permission','comments_use_common_bbcode_permission','comments_use_extra_bbcode_permission','comments_administer_permission','comments_view_permission'),
'schemas' => array('comments_schema'),
'admin-area-class' => 'CCommentsAdmin',
'version' => '3.3-1'
);

#################################################################################
# Widgets definition
#################################################################################

$comments_output_widget=array(
'name' => 'comments',
'title' => '[str]Comments[/str]',
'description' => '[str]Displays the list of posted comments according to URL[/str]',
'class' => 'CComments',
'handler' => 'comments',
'argument' => '%path',
'path' => "![home]\n!user\n!user/*\n!users\n!users/*\n*",
'component' => '',
'permission' => 'view comments',
'weight' => 5,
'cacheable' => 1,
'enabled' => 1,
'fix' => 0
);

$comments_form_widget=array(
'name' => 'commentsForm',
'title' => '[str]Comments form[/str]',
'description' => '[str]Displays the comments posting form[/str]',
'class' => 'CComments',
'handler' => 'comments_form',
'argument' => 1,
'path' => "![home]\n!user\n!user/*\n!users\n!users/*\n*",
'component' => '',
'permission' => 'create comments',
'weight' => 6,
'cacheable' => 0,
'enabled' => 1,
'fix' => 0
);

$latest_comments_widget=array(
'name' => 'latestComments',
'title' => '[str]Latest comments[/str]',
'description' => '[str]Displays the recently posted comments[/str]',
'class' => 'CComments',
'handler' => 'latest_comments',
'argument' => 1,
'path' => "*",
'component' => 'left;left-single',
'permission' => 'view comments',
'weight' => 0,
'cacheable' => 1,
'enabled' => 1,
'fix' => 0
);

$comments_user_comments_output_widget=array(
'name' => 'userComments',
'title' => '[str]User Comments[/str]',
'description' => '[str]Displays the list of user posted comments[/str]',
'class' => 'CComments',
'handler' => 'user_comments',
'argument' => 1,
'path' => "user/comments",
'component' => '',
'permission' => 'view comments',
'weight' => 5,
'cacheable' => 0,
'enabled' => 1,
'fix' => 1
);

#################################################################################
# URL paths definition
#################################################################################

$comments_user_comments_path=array(
'path' => 'user/comments',
'permission' => 'view comments',
'enabled' => 1,
'render' => 1 
);

$comments_user_comments_offset_path=array(
'path' => 'user/comments/d',
'permission' => 'view comments',
'enabled' => 1,
'render' => 1 
);

#################################################################################
# Permissions definition
#################################################################################

$comments_create_permission=array(
'name' => 'create comments',
'title' => '[str]Post comments[/str]',
'description' => '[str]Privileged users will be able to post comments[/str]',
'access' => 1
);

$comments_create_without_captcha_permission=array(
'name' => 'create comments without captcha',
'title' => '[str]Post comments without CAPTCHA[/str]',
'description' => '[str]Privileged users will be able to post comments without anti-bot verification[/str]',
'access' => 0
);

$comments_create_without_approval_permission=array(
'name' => 'create comments without approval',
'title' => '[str]Post comments without approval[/str]',
'description' => '[str]Privileged users will be able to post comments without approval[/str]',
'access' => 0
);

$comments_use_common_bbcode_permission=array(
'name' => 'use common bbcodes in comments',
'title' => '[str]Use common BBCodes in comments[/str]',
'description' => '[strf]Privileged users will be able to use common BBCodes (such as %[b],[i],[u],[quote],[code]%) in their comments text[/strf]',
'access' => 1
);

$comments_use_extra_bbcode_permission=array(
'name' => 'use extra bbcodes in comments',
'title' => '[str]Use extra BBCodes in comments[/str]',
'description' => '[strf]Privileged users will be able to use extra BBCodes (such as %[url],[img]%) in their comments text[/strf]',
'access' => 0
);

$comments_administer_permission=array(
'name' => 'administer comments',
'title' => '[str]Administer comments[/str]',
'description' => '[str]Privileged users will be able to moderate posted comments[/str]',
'access' => 0
);

$comments_view_permission=array(
'name' => 'view comments',
'title' => '[str]View comments[/str]',
'description' => '[str]Privileged users will be able to view posted comments[/str]',
'access' => 1
);

#################################################################################
# Database tables definition
#################################################################################

$comments_schema=array(
'name' => 'pre_comments',
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
    'path' => array(
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
    'language' => array(
        'type' => 'string',
        'length' => 255,
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
'primary key' => 'id'
);
