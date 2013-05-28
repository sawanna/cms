<?php
defined('SAWANNA') or dir();

#################################################################################
# Post-install callback
#################################################################################

function tinyMCE_install($module_id)
{
    global $sawanna;
    
    $handler=array(
        'object' => 'tinyMCE',
        'function' => 'wysiwyg'
    );
    
    $btn_handler=array(
        'object' => 'tinyMCE',
        'function' => 'button'
    );
    
    $validate=array(
        'object' => 'tinyMCE',
        'function' => 'validate'
    );
    
    $process=array(
        'object' => 'tinyMCE',
        'function' => 'process'
    );

    $sawanna->form->add_form_extra_fields('node_create_form_pre_fields',$handler,$validate,$process,$module_id);
    
    $sawanna->form->add_form_extra_fields('code_create_form_pre_fields',$handler,$validate,$process,$module_id);
    $sawanna->form->add_form_extra_fields('code_create_form_post_fields',$btn_handler,$validate,$process,$module_id);
}
