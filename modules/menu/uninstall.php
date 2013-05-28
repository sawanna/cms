<?php
defined('SAWANNA') or die();

#################################################################################
# Post-uninstall callback
#################################################################################

function menu_uninstall($module_id)
{
    global $sawanna;

    $sawanna->form->remove_form_extra_fields('node_create_form_pre_fields',$module_id);
}