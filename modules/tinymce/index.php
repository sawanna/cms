<?php
defined('SAWANNA') or die();

class CTinyMCE
{
    var $sawanna;
    var $module_id;
    
    function CTinyMCE(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('tinyMCE');
    }
    
    function wysiwyg()
    {
        if ($this->sawanna->config->gzip_out)
        {
            $this->sawanna->tpl->add_script(quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/jscripts/tiny_mce/tiny_mce_gzip');
            $this->sawanna->tpl->add_script(quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/js/init_gzip');
            $this->sawanna->tpl->add_script(quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/js/init');
        } else 
        {
            $this->sawanna->tpl->add_script(quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/jscripts/tiny_mce/tiny_mce');
            $this->sawanna->tpl->add_script(quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/js/init');
        }
        
        $lang=file_exists(ROOT_DIR.'/'.quotemeta($this->sawanna->module->root_dir).'/'.quotemeta($this->sawanna->module->modules[$this->module_id]->root).'/jscripts/tiny_mce/langs/'.quotemeta($this->sawanna->locale->current).'.js') ? $this->sawanna->locale->current : 'en';
        $this->sawanna->tpl->add_header('<script language="JavaScript">tinymce_site="'.SITE.'"; tinymce_lang="'.$lang.'";</script>');

        return 0;
    }
    
    function button()
    {
        $fields['tinymce-toggle-btn']=array(
            'type' => 'html',
            'value' => '<div id="wysiwyg-btn-block"><input type="button" id="wysiwyg-btn" onclick="toggleEditor(\'code-content\');return false" value="TinyMCE" title="[str]Enable/Disable editor[/str]" /></div><script language="JavaScript">function toggleEditor(id) { if (!tinyMCE.get(id)) tinyMCE.execCommand(\'mceAddControl\', false, id); else tinyMCE.execCommand(\'mceRemoveControl\', false, id); }</script>'
        );
        
        return $fields;
    }
    
    function validate()
    {
        return true;
    }
    
    function process()
    {
        return;
    }
}
