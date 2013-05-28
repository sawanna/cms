<?php
defined('SAWANNA') or die();

#################################################################################
# Post-uninstall callback
#################################################################################

function tinyMCE_uninstall($module_id)
{
    global $sawanna;

    $sawanna->form->remove_form_extra_fields('node_create_form_pre_fields',$module_id);
    
    $sawanna->form->remove_form_extra_fields('code_create_form_pre_fields',$module_id);
    $sawanna->form->remove_form_extra_fields('code_create_form_post_fields',$module_id);
}