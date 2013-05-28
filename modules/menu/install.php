<?php
defined('SAWANNA') or dir();

#################################################################################
# Post-install callback
#################################################################################

function menu_install($module_id)
{
    global $sawanna;
    
    $handler=array(
        'object' => 'menuAdminHandler',
        'function' => 'node_form_menu_title_extra_field'
    );
    
    $validate=array(
        'object' => 'menuAdminHandler',
        'function' => 'node_form_menu_title_extra_field_validate'
    );
    
    $process=array(
        'object' => 'menuAdminHandler',
        'function' => 'node_form_menu_title_extra_field_process'
    );

    $sawanna->form->add_form_extra_fields('node_create_form_pre_fields',$handler,$validate,$process,$module_id);
    
    $sawanna->db->query("insert into pre_menu_elements (path,language,title,parent,weight,enabled,nid) values ('$1','$2','$3','$4','$5','$6','$7')",'[home]',$sawanna->locale->current,$sawanna->locale->get_string('Home'),0,0,1,0);
}