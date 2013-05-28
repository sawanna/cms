<?php
defined('SAWANNA') or die();

#################################################################################
# Post-uninstall callback
#################################################################################

function user_uninstall($module_id)
{
    global $sawanna;

    $sawanna->form->remove_form_extra_fields('node_create_form_post_fields',$module_id);
    $sawanna->form->remove_form_extra_fields('menu_create_form_extra_fields',$module_id);
    $sawanna->form->remove_form_extra_fields('widget_create_form_extra_fields',$module_id);
    
    return;
}