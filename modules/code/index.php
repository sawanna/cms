<?php
defined('SAWANNA') or die();

class CCode
{
    var $sawanna;
    
    function CCode(&$sawanna)
    {
        $this->sawanna=$sawanna;
    }
    
    function fix_widget($code)
    {
        $wid=$this->sawanna->module->get_widget_id($code->name);
        if(!$wid) return false;
        
        $tpl='';
        if (!empty($code->meta['tpl']))
        {
            if ($code->meta['tpl']=='widget') $tpl='widget';
            elseif ($code->meta['tpl']=='node') $tpl='node';
        }
        
        if (!empty($tpl)) $this->sawanna->module->widgets[$wid]->tpl=$tpl;
        return true;
    }

    function code($name)
    {
        if (empty($name)) return ;

        $this->sawanna->db->query("select * from pre_cwidgets where name='$1'",$name);
        $code=$this->sawanna->db->fetch_object();
        
        if (!$code) return ;
        
        if (!empty($code->meta))
        {
            $d=new CData();
            $code->meta=$d->do_unserialize($code->meta);
        }
        
        $tpl='';
        if (!empty($code->meta['tpl']))
        {
            if ($code->meta['tpl']=='widget') $tpl='widget';
            elseif ($code->meta['tpl']=='node') $tpl='node';
        }
        
        
        if (!empty($code->content))
        {
            $title='';
            if (isset($code->meta['name'][$this->sawanna->locale->current])) $title=htmlspecialchars($code->meta['name'][$this->sawanna->locale->current]);
            
            if (!empty($tpl))
            {
                $this->fix_widget($code);
                return array('content'=>$code->content,'title'=>$title);
            } else return $code->content;
        }
    }
}
