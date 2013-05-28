<?php
defined('SAWANNA') or die();

class CForm
{
    var $db;
    var $config;
    var $session;
    var $event;
    var $tpl;
    var $locale;
    var $module;
    var $constructor;
    var $message;
    
    function CForm(&$db,&$config,&$session,&$event,&$tpl,&$locale,&$module)
    {
        $this->db=$db;
        $this->config=$config;
        $this->session=$session;
        $this->event=$event;
        $this->tpl=$tpl;
        $this->locale=$locale;
        $this->module=$module;
        $this->message=array();
    }
    
    function init(&$constructor)
    {
         $this->constructor=$constructor;
    }
    
    function tpl($elements,$titles,$descriptions,$types,$tpl='',$module_dir='')
    {
        if (empty($elements) || !is_array($elements) || !is_array($titles) || !is_array($descriptions)) return;
        
        $html='';

        if (empty($tpl))
        {
            $co=0;
            foreach($elements as $name=>$code)
            {
                if (!empty($types[$name]) && $types[$name]!='html')
                {
                    if ($types[$name]!='submit') $co++;
                    $marked=($co%2===0 && $types[$name]!='submit') ? ' marked' : '';
                    $html.='<div id="'.htmlspecialchars($name).'_item" class="form_item'.$marked.'">';
                    
                    if ($types[$name]=='checkbox' || $types[$name]=='radio')
                        $html.=!empty($titles[$name]) ? '<div id="'.htmlspecialchars($name).'_title" class="form_title"><label for="'.htmlspecialchars($name).'">'.$titles[$name].'</label></div>' : '';
                    else
                        $html.=!empty($titles[$name]) ? '<div id="'.htmlspecialchars($name).'_title" class="form_title">'.$titles[$name].'</div>' : '';
                    
                    $html.='<div id="'.htmlspecialchars($name).'_element" class="form_element">'.$code.'</div>';
                    $html.=!empty($descriptions[$name]) ? '<div id="'.htmlspecialchars($name).'_description" class="form_description">'.$descriptions[$name].'</div>' : '';
                    $html.='</div>';
                } else 
                {
                    $html.=$code;
                }
            }
        } elseif(file_exists(ROOT_DIR.'/'.quotemeta($this->tpl->root_dir).'/'.quotemeta($this->config->theme).'/'.quotemeta($tpl).'.tpl'))
        {
            $html.=file_get_contents(ROOT_DIR.'/'.quotemeta($this->tpl->root_dir).'/'.quotemeta($this->config->theme).'/'.quotemeta($tpl).'.tpl');
            
            $components=array();
            foreach ($elements as $ename=>$ecode)
            {
                $components[$ename]=$ecode;
                if (array_key_exists($ename,$titles)) $components[$ename.'_title']=$titles[$ename];
                if (array_key_exists($ename,$descriptions)) $components[$ename.'_description']=$descriptions[$ename];
            }
            
            $this->tpl->parseComponents($components,$html);
        } elseif(!empty($module_dir) && file_exists(ROOT_DIR.'/'.quotemeta($this->module->root_dir).'/'.quotemeta($module_dir).'/'.quotemeta($this->module->themes_root).'/'.quotemeta($this->config->theme).'/'.quotemeta($tpl).'.tpl'))
        {
            $html.=file_get_contents(ROOT_DIR.'/'.quotemeta($this->module->root_dir).'/'.quotemeta($module_dir).'/'.quotemeta($this->module->themes_root).'/'.quotemeta($this->config->theme).'/'.quotemeta($tpl).'.tpl');
            
            $components=array();
            foreach ($elements as $ename=>$ecode)
            {
                $components[$ename]=$ecode;
                if (array_key_exists($ename,$titles)) $components[$ename.'_title']=$titles[$ename];
                if (array_key_exists($ename,$descriptions)) $components[$ename.'_description']=$descriptions[$ename];
            }
            
            $this->tpl->parseComponents($components,$html);
        } elseif(!empty($module_dir) && $this->config->theme!=$this->tpl->default_theme && file_exists(ROOT_DIR.'/'.quotemeta($this->module->root_dir).'/'.quotemeta($module_dir).'/'.quotemeta($this->module->themes_root).'/'.quotemeta($this->tpl->default_theme).'/'.quotemeta($tpl).'.tpl'))
        {
            if (DEBUG_MODE) trigger_error('Could not render form template `'.htmlspecialchars($tpl).'` using current theme. File '.htmlspecialchars($tpl).'.tpl not found. Using default theme',E_USER_NOTICE);
            
            $html.=file_get_contents(ROOT_DIR.'/'.quotemeta($this->module->root_dir).'/'.quotemeta($module_dir).'/'.quotemeta($this->module->themes_root).'/'.quotemeta($this->tpl->default_theme).'/'.quotemeta($tpl).'.tpl');
            
            $components=array();
            foreach ($elements as $ename=>$ecode)
            {
                $components[$ename]=$ecode;
                if (array_key_exists($ename,$titles)) $components[$ename.'_title']=$titles[$ename];
                if (array_key_exists($ename,$descriptions)) $components[$ename.'_description']=$descriptions[$ename];
            }
            
            $this->tpl->parseComponents($components,$html);
        } elseif(file_exists(ROOT_DIR.'/'.quotemeta($tpl).'.tpl'))
        {
            $html.=file_get_contents(ROOT_DIR.'/'.quotemeta($tpl).'.tpl');
            
            $components=array();
            foreach ($elements as $ename=>$ecode)
            {
                $components[$ename]=$ecode;
                if (array_key_exists($ename,$titles)) $components[$ename.'_title']=$titles[$ename];
                if (array_key_exists($ename,$descriptions)) $components[$ename.'_description']=$descriptions[$ename];
            }
            
            $this->tpl->parseComponents($components,$html);
        } else 
        {
            trigger_error('Could not render form template `'.htmlspecialchars($tpl).'`. File not found',E_USER_WARNING);
        }
        
        $this->tpl->add_css('misc/form');
        
        return $html;
    }
    
    function render(&$form,$elements_only=false)
    {
        if (!is_array($form) || empty($form['id']) || empty($form['url']) || empty($form['fields']) || !is_array($form['fields']))
        {
            trigger_error('Invalid form',E_USER_WARNING);
            return false;
        }
        
        $error_fields=array();
        $field_values=array();
       
        if (!isset($form['fill']) || $form['fill']!=false)
        {
            $res=$this->db->query("select * from pre_forms where form_id='$1' and sid='$2'",$this->generate_id($form['id']),$this->session->id);
            $row=$this->db->fetch_array($res);
            if (!empty($row))  
            { 
                $d=new CData();
                
                $row['form']=$d->do_unserialize($row['form']);
                if (is_array($row['form']) && !empty($row['form']['id']) && !empty($row['form']['url']) && !empty($row['form']['fields']) && is_array($row['form']['fields']) && $row['form']['id']==$form['id'])
                { 
                    $row['failed']=$d->do_unserialize($row['failed']);
                    if (!empty($row['failed']) && is_array($row['failed'])) $error_fields=&$row['failed'];
                    
                    $row['field_values']=$d->do_unserialize($row['field_values']);
                    if (!empty($row['field_values']) && is_array($row['field_values'])) $field_values=&$row['field_values'];
                }
            }
        }

        $multipart=false;
        $elements=array();
        $titles=array();
        $descriptions=array();
        $types=array();
        $script='';
        
        foreach ($form['fields'] as $name=>$field)
        {
            if (!is_array($field) || empty($field['type'])) continue;
            
            $attributes='';
            if (!empty($field['attributes']) && is_array($field['attributes']))
            {
                foreach ($field['attributes'] as $attr=>$attrv)
                {
                    if ($attr=='class' && in_array($name,$error_fields))
                        $attributes.=' '.htmlspecialchars($attr).'="'.htmlspecialchars($attrv).' failed"';
                    elseif ($attr=='checked' && (array_key_exists($name,$field_values) || (isset($field['name']) && array_key_exists($field['name'],$field_values))))
                    { 
                        continue;
                    }else    
                        $attributes.=' '.htmlspecialchars($attr).'="'.strip_tags($attrv).'"';
                }
            }
            
            if (in_array($name,$error_fields) && (empty($field['attributes']) || (!empty($field['attributes']) && is_array($field['attributes']) && !array_key_exists('class',$field['attributes']))))
            {
                $attributes.=' class="failed"';
            }
            
            $_name=!empty($field['name']) ? $field['name'] : $name;
            $id=!empty($field['id']) ? $field['id'] : $name;
            $_value=isset($field['value']) ? $field['value'] : '';
            if (isset($field['default']) && $field['type']!='radio') $_value=$field['default'];
            if (isset($field['default']) && $field['type']=='radio' && empty($field_values)) $field_values[$_name]=$field['default'];
            if (($field['type']=='text' || $field['type']=='textarea' || $field['type']=='select' || $field['type']=='checkbox' || $field['type']=='email' || $field['type']=='date' || $field['type']=='autocomplete' || $field['type']=='autocheck') && array_key_exists($name,$field_values)) $_value=$field_values[$name];
            
            switch ($field['type'])
            {
                case 'text':
                     $value=!empty($_value) || is_numeric($_value) ? ' value="'.htmlspecialchars($_value).'"' : '';
                     $elements[$name]='<input type="text" name="'.htmlspecialchars($_name).'" id="'.htmlspecialchars($id).'"'.$value.$attributes.' />';
                     break;
                case 'password':
                     $elements[$name]='<input type="password" name="'.htmlspecialchars($_name).'" id="'.htmlspecialchars($id).'"'.$attributes.' />';
                     break;
                case 'button':
                     $value=!empty($_value) || is_numeric($_value) ? ' value="'.htmlspecialchars($_value).'"' : '';
                     $elements[$name]='<input type="button" name="'.htmlspecialchars($_name).'" id="'.htmlspecialchars($id).'"'.$value.$attributes.' />';
                     break;
                case 'radio':
                     $value=!empty($_value) || is_numeric($_value) ? ' value="'.htmlspecialchars($_value).'"' : '';
                     if (array_key_exists($_name,$field_values) && $field_values[$_name]==$_value) $attributes.=' checked="checked"';
                     $elements[$name]='<input type="radio" name="'.htmlspecialchars($_name).'" id="'.htmlspecialchars($id).'"'.$value.$attributes.' />';
                     break;
                case 'checkbox':
                    if (array_key_exists($_name,$field_values) && $field_values[$_name]=='on') $attributes.=' checked="checked"';
                     $elements[$name]='<input type="checkbox" name="'.htmlspecialchars($_name).'" id="'.htmlspecialchars($id).'"'.$attributes.' />';
                     break;
                case 'textarea':
                     $value=!empty($_value) || is_numeric($_value) ? htmlspecialchars($_value) : '';
                     $elements[$name]='<textarea name="'.htmlspecialchars($_name).'" id="'.htmlspecialchars($id).'"'.$attributes.'>'.$value.'</textarea>';
                     break;
                case 'select':
                     $values=(!empty($_value) && is_array($_value)) ? $_value : array();
                     $value=((!empty($_value) || is_numeric($_value)) && !is_array($_value)) ? $_value : '';
                     
                     if (isset($field['attributes']['multiple']) && $field['attributes']['multiple']=='multiple')
                        $elements[$name]='<select name="'.htmlspecialchars($_name).'[]" id="'.htmlspecialchars($id).'"'.$attributes.'>';
                     else 
                        $elements[$name]='<select name="'.htmlspecialchars($_name).'" id="'.htmlspecialchars($id).'"'.$attributes.'>';
                     if (!empty($field['optgroups']) && is_array($field['optgroups']))
                     {
                         foreach($field['optgroups'] as $label=>$options)
                         {
                             $elements[$name].='<optgroup label="'.htmlspecialchars($label).'">';
                             if (is_array($options))
                             {
                                 foreach($options as $optval=>$optname)
                                 {
                                     $selected=(((!empty($value) || is_numeric($_value)) && $value==$optval) || (!empty($values) && in_array($optval,$values))) ? ' selected' : '';
                                     $elements[$name].='<option value="'.htmlspecialchars($optval).'"'.$selected.'>'.htmlspecialchars($optname).'</option>';
                                 }
                             }
                             $elements[$name].='</optgroup>';
                         }
                     } elseif (!empty($field['options']) && is_array($field['options']))
                     {
                         foreach($field['options'] as $optval=>$optname)
                         {
                             $selected=(((!empty($value) || is_numeric($_value)) && $value==$optval) || (!empty($values) && in_array($optval,$values))) ? ' selected' : '';
                             $elements[$name].='<option value="'.htmlspecialchars($optval).'"'.$selected.'>'.htmlspecialchars($optname).'</option>';
                         }
                     }
                     $elements[$name].='</select>';
                     break;
                case 'file':
                     $elements[$name]='<input type="file" name="'.htmlspecialchars($_name).'" id="'.htmlspecialchars($id).'"'.$attributes.' />';
                     $multipart=true;
                     break;
                case 'hidden':
                     $value=!empty($_value) || is_numeric($_value) ? ' value="'.htmlspecialchars($_value).'"' : '';
                     $elements[$name]='<input type="hidden" name="'.htmlspecialchars($_name).'" id="'.htmlspecialchars($id).'"'.$value.$attributes.' />';
                     break;
                case 'submit':
                     $value=!empty($_value) || is_numeric($_value) ? ' value="'.htmlspecialchars($_value).'"' : '';
                     $elements[$name]='<input type="submit" name="'.htmlspecialchars($_name).'" id="'.htmlspecialchars($id).'"'.$value.$attributes.' />';
                     break;
                case 'html':
                    $elements[$name]=$_value;
                    break;
                case 'captcha':
					if(!$this->config->captcha) break;
					
                    if (isset($field['skip']) && is_numeric($field['skip']) && !empty($field['skip']))
                    {
                        $captcha=$this->session->get('captcha_count');
                        if (!empty($captcha) && is_array($captcha) && isset($captcha[$form['id']]))
                        {
                            if ($captcha[$form['id']]<$field['skip']) 
                            {
                                //$captcha[$form['id']]++;
                                //$this->session->set('captcha_count',$captcha);
                                unset($form['fields'][$name]);
                                break;
                            }
                        } else 
                        {
                            $captcha[$form['id']]=0;
                            $this->session->set('captcha_count',$captcha);
                            unset($form['fields'][$name]);
                            break;
                        }
                    }
                    $captcha_url=$this->constructor->url('captcha',true);
                    $captcha_url=$this->constructor->add_url_args($captcha_url,array('t'=>time()));
                    if (!$this->constructor->get_trans_sid()) $captcha_url=$this->constructor->add_url_args($captcha_url,array($this->session->query_var=>$this->session->id));
                    $elements[$name]='<div class="captcha"><img id="captcha_image" width="150" height="40" src="'.$captcha_url.'" onclick="refresh_captcha()" style="cursor:pointer" title="[str]Refresh[/str]" /><img src="'.SITE.'/misc/refresh.png" width="16" height="16" style="cursor:pointer;vertical-align:top" onclick="refresh_captcha()" title="[str]Refresh[/str]" /></div><input type="text" name="'.htmlspecialchars($_name).'" id="'.htmlspecialchars($id).'"'.$attributes.' />';
                    $script.='<script language="JavaScript">refresh_captcha=function(){ if(typeof(refresh_captcha.co)=="undefined") { refresh_captcha.co=1; }; $("#captcha_image").get(0).src=$("#captcha_image").get(0).src+"&"+refresh_captcha.co; refresh_captcha.co++;}</script>';
                    $this->register_captcha();
                    break;
                case 'email':
                    $value=!empty($_value) || is_numeric($_value) ? ' value="'.htmlspecialchars($_value).'"' : '';
                    $elements[$name]='<input type="text" name="'.htmlspecialchars($_name).'" id="'.htmlspecialchars($id).'"'.$value.$attributes.' />';
                    break;  
                case 'date':
                    $value=!empty($_value) || is_numeric($_value) ? ' value="'.htmlspecialchars($_value).'"' : '';
                    $this->tpl->add_jquery_ui();
                    $script.="<script language=\"JavaScript\">
                                $(document).ready(function(){
                                $('#".htmlspecialchars($id)."').datepicker({
                                clearText: '[str]Clear[/str]', closeText: '[str]Close[/str]', 
                                prevText: '[str]Previous[/str]', nextText: '[str]Next[/str]', currentText: '[str]Today[/str]',
                                dayNamesMin: ['[str]Sunday[/str]','[str]Monday[/str]','[str]Tuesday[/str]','[str]Wednesday[/str]','[str]Thursday[/str]','[str]Friday[/str]','[str]Saturday[/str]'],
                                monthNames: ['[str]January[/str]','[str]February[/str]','[str]March[/str]','[str]April[/str]','[str]May[/str]','[str]June[/str]','[str]July[/str]','[str]August[/str]','[str]September[/str]','[str]October[/str]','[str]November[/str]','[str]December[/str]'],
                                dateFormat: 'dd.mm.yy',
                                showAnim:'fadeIn',constrainInput:false,showOn: 'both',buttonImage: '".SITE."/misc/calendar.gif',buttonImageOnly: true,showButtonPanel: true";
                    if ($this->locale->current=='ru') $script.=",firstDay:1";
                    $script.="});});</script>";
                    $elements[$name]='<input type="text" name="'.htmlspecialchars($_name).'" id="'.htmlspecialchars($id).'"'.$value.$attributes.' />';
                    break;  
                case 'color':
                    static $colorpicker_created=false;
                    
                    $value=!empty($_value) || is_numeric($_value) ? ' value="'.htmlspecialchars($_value).'"' : '';
                    
                    $this->tpl->add_script('js/raphael');
                    $this->tpl->add_script('js/colorwheel');
                    
                    if (!$colorpicker_created)
                    {
                        $script.='<div id="colorpicker"><div style="position:absolute;top:1px;right:4px;color:red;font-size:10px;cursor:pointer" onclick="try { window.clearTimeout(colorpicker_timer); } catch(e) { }; $(\'#colorpicker\').css(\'display\',\'none\');">x</div><div id="colorwheel"></div></div>';
                        $script.="<script language=\"JavaScript\">";
                        $script.="$(document).ready(function(){ window.setTimeout(\"";
                        $script.= "cw = Raphael.colorwheel($('#colorwheel').get(0), 150);";
                        $script.= "$('#colorpicker').eq(0).css({'display':'none','position':'absolute'});";
                        $script.= "\",300);";
                        $script.= "});";
                        $script.= "</script>";
                        
                        $colorpicker_created=true;
                    }
                    
                    $script.="<script language=\"JavaScript\">";
                    $script.="$(document).ready(function(){ ";
                    $script.="window.setTimeout(\"cw.input($('#".htmlspecialchars($id)."').get(0));\",350);";
                    $script.= "$('#colorpicker').eq(0).click(function() { try { window.clearTimeout(colorpicker_timer); } catch(e) { }; colorpicker_timer=window.setTimeout(\"$('#colorpicker').eq(0).css('display','none');\",5000); });";
                    $script.= "$('#".htmlspecialchars($id)."').eq(0).click(function(){ try { window.clearTimeout(colorpicker_timer); } catch(e) { }; cw.input($('#".htmlspecialchars($id)."').get(0)); $('#colorpicker').css({'top':$(this).offset().top+$(this).height(),'left':$(this).offset().left}); colorpicker_timer=window.setTimeout(\"$('#colorpicker').eq(0).css('display','none');\",5000); $('#colorpicker').css('display','block');  });";
                    $script.= "});";
                    $script.= "</script>";
                    
                    $elements[$name]='<input type="text" name="'.htmlspecialchars($_name).'" id="'.htmlspecialchars($id).'"'.$value.$attributes.' /><div id="'.htmlspecialchars($id).'-colorwheel" class="colorwheel"></div>';
                    break;
            }
            
            #################  autofields ###################
            
             if (isset($field['autocomplete']) && !empty($field['autocomplete']))
            {
                if (is_array($field['autocomplete']) && isset($field['autocomplete']['url']) && isset($field['autocomplete']['caller']) && isset($field['autocomplete']['handler']))
                {
                     $this->tpl->add_jquery_ui();
                     $this->tpl->add_script('js/autocomplete');
                     $this->tpl->add_css('misc/autocomplete');
                     if (empty($field['autocomplete']['url'])) trigger_error('Empty URL for autocomplete requests not allowed',E_USER_WARNING);
                     $script.='<script language="JavaScript">';
                     if (!empty($field['autocomplete']['caller']) && !empty($field['autocomplete']['url']) && !empty($field['autocomplete']['handler']))
                     {
                         $autocomplete_url=$this->constructor->url($field['autocomplete']['url'],true);
                         $autocomplete_url=$this->constructor->add_url_args($autocomplete_url,array('t'=>time()));
                         if (!$this->constructor->get_trans_sid()) $autocomplete_url=$this->constructor->add_url_args($autocomplete_url,array($this->session->query_var=>$this->session->id));
                         $script.="$(document).ready(function(){\$('#".htmlspecialchars($id)."').autocomplete('".$autocomplete_url."');});";
                         $this->register_autofield($field['autocomplete']['url'],$field['autocomplete']['caller'],$field['autocomplete']['handler']);
                     } elseif (isset($field['autocomplete']['data'])) $script.="data='".$field['autocomplete']['data']."'.split(' '); $(document).ready(function(){\$('#".htmlspecialchars($id)."').autocomplete(data);});";
                     $script.='</script>';
                } else trigger_error('Invalid data given for autocomplete field',E_USER_NOTICE);
            }
            
            if (isset($field['autocheck']) && !empty($field['autocheck']))
            {
                if (is_array($field['autocheck']) && isset($field['autocheck']['url']) && isset($field['autocheck']['caller']) && isset($field['autocheck']['handler']))
                {
                     $this->tpl->add_script('js/autocheck');
                     if (empty($field['autocheck']['url'])) trigger_error('Empty URL for autocheck requests not allowed',E_USER_WARNING);
                     if (!empty($field['autocheck']['caller']) && !empty($field['autocheck']['url']) && !empty($field['autocheck']['handler']))
                     {
                         $autocheck_url=$this->constructor->url($field['autocheck']['url'],true);
                         $autocheck_url=$this->constructor->add_url_args($autocheck_url,array('t'=>time()));
                         if (!$this->constructor->get_trans_sid()) $autocheck_url=$this->constructor->add_url_args($autocheck_url,array($this->session->query_var=>$this->session->id));
                         $script.='<script language="JavaScript">';
                         $script.="$(document).ready(function(){ autocheck('".htmlspecialchars($id)."','".$autocheck_url."');});";  
                         $this->register_autofield($field['autocheck']['url'],$field['autocheck']['caller'],$field['autocheck']['handler']);
                         $script.='</script>'; 
                         $script.='<div id="'.htmlspecialchars($id).'_autocheck_loader" class="autocheck_loader"></div>';
                     }
                } else trigger_error('Invalid data given for autocheck field',E_USER_NOTICE);
            }
            
            #################  /autofields ###################
            
            $title=!empty($field['title']) ? $field['title'] : '';
            if (isset($field['required']) && $field['required']==true) $title.='<sup class="required">*</sup>';
            $titles[$name]=$title;
            
            $descriptions[$name]=!empty($field['description']) ? $field['description'] : '';
            $types[$name]=$field['type'];
        }
        
        $action=' action="'.strip_tags($form['url']).'"';
        $method=isset($form['method']) ? ' method="'.htmlspecialchars($form['method']).'"' : ' method="POST"';
        $multipart_form=$multipart ? ' enctype="multipart/form-data"' : '';
        $form_id=' id="'.htmlspecialchars($form['id']).'"';
        $form_name=isset($form['name']) ? ' name="'.htmlspecialchars($form['name']).'"' : ' name="'.htmlspecialchars($form['id']).'"';
        
        $form_class=isset($form['class']) ? ' class="'.htmlspecialchars($form['class']).'"' : '';
        
        $html='';
        if (!$elements_only) {
			$html.='<div id="'.htmlspecialchars($form['id']).'_form">';
			$html.='<form'.$form_id.$form_name.$action.$method.$multipart_form.$form_class.'>';
		}
        if (!isset($form['tpl']))
        {
            $html.=$this->tpl($elements,$titles,$descriptions,$types);
        } else
        {
            if (!isset($form['caller']))
                $html.=$this->tpl($elements,$titles,$descriptions,$types,$form['tpl']);
            else 
            {
                if (($module=$this->module->get_module_by_class($form['caller']))!=false)
                    $html.=$this->tpl($elements,$titles,$descriptions,$types,$form['tpl'],$module->root);
                else 
                    $html.=$this->tpl($elements,$titles,$descriptions,$types,$form['tpl']);
            }
        }
        
        if (!$elements_only) {
			$fid=$this->generate_id($form['id']);
			$html.='<input type="hidden" name="fid" value="'.$fid.'" />';
			
			$html.='</form>';
			$html.='</div>';
			
			$this->register($fid,$form);
		}
        $html.=$script;
           
        return $html;
    }
    
    function register_captcha()
    {
        $event=array();
        $event['caller']='CSawanna';
        $event['url']='captcha';
        $event['job']='process_captcha';
        $event['argument']=1;
        $event['constant']=1;
        $this->event->register($event);
    }
    
     function unregister_captcha()
    {
        $event=array();
        $event['caller']='CSawanna';
        $event['url']='captcha';
        $event['job']='process_captcha';
        $event['argument']=1;
        $event['constant']=1;
        $this->event->remove($event);
    }
    
    function register_autofield($url,$caller,$handler)
    {
         $event=array();
         $event['url']=$url;
         $event['caller']=$caller;
         $event['job']=$handler;
         $event['constant']=1;
         $this->event->register($event);
    }
    
    function unregister_autofield($url,$caller,$handler)
    {
         $event=array();
         $event['url']=$url;
         $event['caller']=$caller;
         $event['job']=$handler;
         $event['constant']=1;
         $this->event->remove($event);
    }
    
    function generate_id($id)
    {
        return md5($id.PRIVATE_KEY);
    }
    
    function register($id,$form)
    {
        if (!is_object($this->db)) return false;
        
        foreach ($form['fields'] as $ind=>$field)
        {
            if (!empty($field['default'])) $form['fields'][$ind]['default']='';
        }
        
        $d=new CData();
        
        $res=$this->db->query("select * from pre_forms where form_id='$1' and sid='$2'",$id,$this->session->id);
        $row=$this->db->fetch_array($res);
        if (!empty($row))
        {
            if (!$this->db->query("update pre_forms set form='$1', field_values='', failed='' where form_id='$2' and sid='$3'",$d->do_serialize($form),$id,$this->session->id))
            {
                trigger_error('Could not register form `'.htmlspecialchars($form['id']).'`');
                return false;
            }
            return true;
        }

        if (!$this->db->query("insert into pre_forms (form_id,sid,form) values ('$1','$2','$3')",$id,$this->session->id,$d->do_serialize($form)))
        {
            trigger_error('Could not register form `'.htmlspecialchars($form['id']).'`');
            return false;
        }
        return true;
    }
    
    function unregister($id)
    {
        if (!$this->db->query("delete from pre_forms where form_id='$1' and sid='$2'",$id,$this->session->id))
            return false;
        return true;
    }
    
    function validate($id)
    { 
        // getting form
        $res=$this->db->query("select * from pre_forms where form_id='$1' and sid='$2'",$id,$this->session->id);
        $row=$this->db->fetch_array($res);
        if (empty($row))  
        { 
            $this->unregister($id);
            return false;
        }
        
        if ($row['sid'] != $this->session->id)
        { 
            $this->unregister($id);
            return false;
        }
        
        $d=new CData();
        $form=$d->do_unserialize($row['form']);
        if (empty($form) || !is_array($form))
        { 
            $this->unregister($id);
            return false;
        }
        $method=isset($form['method']) ? strtoupper($form['method']) : 'POST';
        
        if ($method!='GET' && $method!='POST') 
        { 
            $this->unregister($id);
            return false;
        }
        if (empty($form['fields']) || !is_array($form['fields'])) 
        { 
            $this->unregister($id);
            return false;
        }
        
        // increasing captcha counter
        $captcha_co=$this->session->get('captcha_count');
        if (!empty($captcha_co) && is_array($captcha_co) && isset($captcha_co[$form['id']]) && $this->config->captcha)
        {
            $captcha_co[$form['id']]++;
            $this->session->set('captcha_count',$captcha_co);
        }

        $failed=false;
        $failed_fields=array();
        $recieved=array();
        
        foreach($form['fields'] as $name=>$field)
        {
            if (!is_array($field)) continue;
            
            $title=!empty($field['title']) ? $field['title'] : '';
            $_name=!empty($field['name']) ? $field['name'] : $name;
            
            if ($field['type'] == 'captcha' && !$this->config->captcha) $field['required']=false;
            
            if ($field['type'] != 'file')
            {
                $recieved_val='';
                if ($method=='POST' && isset($_POST[$_name])) $recieved_val=$_POST[$_name];
                if ($method=='GET' && isset($_GET[$_name])) $recieved_val=$_GET[$_name];
                $recieved[$_name]=$recieved_val;
                
                if ($field['type']=='checkbox' && empty($recieved_val)) $recieved[$_name]='off';
                   
                if (isset($field['required']) && $field['required']==true && (($method=='POST' && (!isset($_POST[$_name]) || empty($_POST[$_name]))) || ($method=='GET' && (!isset($_GET[$_name]) || empty($_GET[$_name])))))
                {
                    $this->message[]='[strf]Field "%'.$title.'%" is required[/strf]';
                    $failed_fields[]=$name;
                    $failed=true;
                }
            } else
            {
                if (isset($field['required']) && $field['required']==true && (!isset($_FILES[$_name]) || empty($_FILES[$_name]['tmp_name'])) && (!isset($_POST[$_name]) || empty($_POST[$_name]) || $_POST[$_name]!=md5($_name.PRIVATE_KEY)))
                {
                    $this->message[]='[strf]Field "%'.$title.'%" is required[/strf]';
                    $failed_fields[]=$name;
                    $failed=true;
                }
            }
            
             if (($field['type']=='text' || $field['type']=='email' || $field['type']=='date' || $field['type']=='autocomplete' || $field['type']=='autocheck')) 
             {
                    if ($method=='POST' && isset($_POST[$_name]) && sawanna_strlen($_POST[$_name])>255) 
                    {
                        $this->message[]='[strf]The length of "%'.$title.'%" field value greater than 255 characters[/strf]';
                        $failed_fields[]=$name;
                        $failed=true;
                    }
                    if ($method=='GET' && isset($_GET[$_name]) && sawanna_strlen($_GET[$_name])>255) 
                    {
                        $this->message[]='[strf]The length of "%'.$title.'%" field value greater than 255 characters[/strf]';
                        $failed_fields[]=$name;
                        $failed=true;
                    }
             }
            
            if ($field['type'] == 'captcha' && !empty($_POST[$_name]) && $this->config->captcha)
            {
                $captcha=$this->session->get('captcha');
                
                if ($method=='POST' && (empty($_POST[$_name]) || $_POST[$_name]!=$captcha))
                {
                    $this->message[]='[str]Wrong CAPTCHA[/str]';
                    if (DEBUG_MODE) $this->message[]='Captcha was '.$captcha.', but recieved '.(!empty($_POST[$_name]) ? $_POST[$_name] : 'none');
                    $failed_fields[]=$name;
                    $failed=true;
                }
                
                $this->unregister_captcha();
                $this->session->clear('captcha');
            }
            
            if ($field['type'] == 'email' && !empty($_POST[$_name]))
            {
                if (!preg_match("/^\w+([\.\w-]+)*\w@\w(([\.\w-])*\w+)*\.\w{2,4}$/",$_POST[$_name]))
                {
                     $this->message[]='[str]Wrong E-Mail[/str]';
                     $failed_fields[]=$name;
                     $failed=true;
                }
            }
            
            if ($field['type'] == 'date' && !empty($_POST[$_name]))
            {
                if (!preg_match("/^[\d]{2}\.[\d]{2}\.[\d]{4}$/",$_POST[$_name]))
                {
                     $this->message[]='[str]Wrong date[/str]';
                     $failed_fields[]=$name;
                     $failed=true;
                }
            }
            
            if ($field['type'] == 'autocomplete' || $field['type'] == 'autocheck')
            {
                $this->unregister_autofield($field['url'],$form['caller'],$field['handler']);
            }
        }

        $recieved=$d->do_serialize($recieved);
        $failed_fields=!empty($failed_fields) ? $d->do_serialize($failed_fields) : ''; 
        if (!$this->db->query("update pre_forms set field_values='$1', failed='$2' where form_id='$3' and sid='$4'",$recieved,$failed_fields,$id,$this->session->id))
            trigger_error($this->db->last_error(),E_USER_WARNING);

        if ($failed) return false;
        return true;
    }
    
    function process_prepare($id)
    {
        $res=$this->db->query("select * from pre_forms where form_id='$1' and sid='$2'",$id,$this->session->id);
        $row=$this->db->fetch_array($res);
        if (empty($row))  
        { 
            trigger_error('Invalid form',E_USER_WARNING);
            $this->unregister($id);
            return;
        }

        $d=new CData();
        $form=$d->do_unserialize($row['form']);
        
        if (!is_array($form) || empty($form['id']) || empty($form['url']) || empty($form['fields']) || !is_array($form['fields']))
        {
            trigger_error('Invalid form',E_USER_WARNING);
            $this->unregister($id);
            return false;
        }

        return $form;
    }
    
    function process_callback($result,&$form)
    {
        if (!empty($result) && is_array($result))
        {
            if (count($result)>1)
                list($message,$failed_fields)=$result;
            else 
                $message=$result[0];
        
            $event=array();
            $event['argument']=$message;
           
            if (empty($failed_fields))
            {
                $event['job']='sawanna_message';
                $this->event->register($event);
                return true;
            } else 
            {
                $this->update_failed_fields($form['id'],$failed_fields);
                $event['job']='sawanna_error';
                $this->event->register($event);
                
                if ((!isset($form['fill']) || $form['fill']) && isset($form['redirect'])) $form['redirect']='';
                return false;
            }
        } 
    }
    
    function process_clear($id)
    {
        $this->unregister($id);
    }
    
    function update_failed_fields($form_id,$failed_fields)
    {
        if (empty($failed_fields) || !is_array($failed_fields)) return;
        
        $d=new CData();
         
        $fields=$d->do_serialize($failed_fields);
        $this->db->query("update pre_forms set failed='$1' where form_id='$2' and sid='$3'",$fields,$this->generate_id($form_id),$this->session->id);
                    
    }
    
    function add_form_extra_fields($extra,$handler,$validate,$process,$module_id)
    {
        if (!is_array($handler) || !isset($handler['function']) || !is_array($validate) || !isset($validate['function']) || !is_array($process) || !isset($process['function']))
        {
            trigger_error('Could not set '.htmlspecialchars($extra).' form extra fields',E_USER_WARNING);
            return false;
        }
        
        $option=array($module_id=>$handler);
        
        $ex=$this->module->get_option($extra);
        if (!empty($ex) && is_array($ex))
        {
            $option=$ex+$option;
        }
        
        $option_validate=array($module_id=>$validate);
        
        $ex=$this->module->get_option($extra.'_validate');
        if (!empty($ex) && is_array($ex))
        {
            $option_validate=$ex+$option_validate;
        }
        
        $option_process=array($module_id=>$process);
        
        $ex=$this->module->get_option($extra.'_process');
        if (!empty($ex) && is_array($ex))
        {
            $option_process=$ex+$option_process;
        }
        
        $this->module->set_option($extra,$option,0);
        $this->module->set_option($extra.'_validate',$option_validate,0);
        $this->module->set_option($extra.'_process',$option_process,0);
    }
    
    function get_form_extra_fields($extra,$arg='')
    {
        $fields=array();
        
        $form_fields=$this->module->get_option($extra); 
        if (is_array($form_fields) && !empty($form_fields))
        {
            foreach ($form_fields as $id=>$handler)
            {
                if (!is_array($handler) || !isset($handler['function'])) continue;
                
                $field=array();
                
                if (!isset($handler['object']) && function_exists($handler['function']))
                    $field=$handler['function']($arg);
                elseif (isset($handler['object']) && isset($GLOBALS[$handler['object']]) && is_object($GLOBALS[$handler['object']]) && method_exists($GLOBALS[$handler['object']],$handler['function']))
                    $field=$GLOBALS[$handler['object']]->{$handler['function']}($arg);
                    
                if (!empty($field) && is_array($field)) $fields=array_merge($fields,$field);
            }
        }
        
        return $fields;
    }
    
    function validate_form_extra_fields($extra,$arg='')
    {
        $form_fields_handlers=$this->module->get_option($extra.'_validate');
        if (is_array($form_fields_handlers) && !empty($form_fields_handlers))
        {
            foreach ($form_fields_handlers as $id=>$handler)
            {
                if (!is_array($handler) || !isset($handler['function'])) continue;
                
                $return=false;
                
                if (!isset($handler['object']) && function_exists($handler['function']))
                    $return= $handler['function']($arg);
                elseif (isset($handler['object']) && isset($GLOBALS[$handler['object']]) && is_object($GLOBALS[$handler['object']]) && method_exists($GLOBALS[$handler['object']],$handler['function']))
                    $return= $GLOBALS[$handler['object']]->{$handler['function']}($arg);
                    
                if (!empty($return)) return $return;
            }
        }
    }
    
    function process_form_extra_fields($extra,$arg='')
    {
        $form_fields_handlers=$this->module->get_option($extra.'_process');
        if (is_array($form_fields_handlers) && !empty($form_fields_handlers))
        {
            foreach ($form_fields_handlers as $id=>$handler)
            {
                if (!is_array($handler) || !isset($handler['function'])) continue;
                
                $return=false;
                
                if (!isset($handler['object']) && function_exists($handler['function']))
                    $return= $handler['function']($arg);
                elseif (isset($handler['object']) && isset($GLOBALS[$handler['object']]) && is_object($GLOBALS[$handler['object']]) && method_exists($GLOBALS[$handler['object']],$handler['function']))
                    $return= $GLOBALS[$handler['object']]->{$handler['function']}($arg);
                    
                if (!empty($return)) return $return;
            }
        }
    }

    function remove_form_extra_fields($extra,$module_id)
    {
        $ex=$this->module->get_option($extra);
        if (!empty($ex))
        {
            if (isset($ex[$module_id])) unset($ex[$module_id]);
            if (!empty($ex)) $this->module->set_option($extra,$ex,0);
            else $this->module->remove_option($extra);
        }
        
        $ex=$this->module->get_option($extra.'_validate');
        if (!empty($ex))
        {
            if (isset($ex[$module_id])) unset($ex[$module_id]);
            if (!empty($ex)) $this->module->set_option($extra.'_validate',$ex,0);
            else $this->module->remove_option($extra.'_validate');
        }
        
        $ex=$this->module->get_option($extra.'_process');
        if (!empty($ex))
        {
            if (isset($ex[$module_id])) unset($ex[$module_id]);
            if (!empty($ex)) $this->module->set_option($extra.'_process',$ex,0);
            else $this->module->remove_option($extra.'_process');
        }
    }
    
    function get_message()
    {
        $message=$this->message;
        $this->message=array();
        return $message;
    }
    
    function empty_trash()
    {
        $fsid=array();
        $this->db->query('select distinct sid from pre_forms');
        while($row=$this->db->fetch_object())
        {
            $fsid[]=$row->sid;
        }
        
        $sid=array();
        $this->db->query('select id from pre_session');
        while($row=$this->db->fetch_object())
        {
            $sid[]=$row->id;
        }
        
        $insid=array_diff($fsid,$sid);
        
        $this->db->query("delete from pre_forms where sid in('".implode("','",$insid)."')");
    }
    
    function cron()
    {
        $this->empty_trash();
    }

    function schema()
    {
        $table=array(
            'name' => 'pre_forms',
            'fields' => array(
                'form_id' => array(
                    'type' => 'string',
                    'length' => 32,
                    'not null' => true
                ),
                'sid' => array(
                    'type' => 'string',
                    'length' => 32,
                    'not null' => true
                ),
                'form' => array(
                    'type' => 'longtext',
                    'not null' => true
                ),
                'field_values' => array(
                    'type' => 'longtext',
                    'not null' => false
                ),
                'failed' => array(
                    'type' => 'text',
                    'not null' => false
                ),
                'mark' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => false
                )
            ),
            'index' => array('form_id','sid')
        );
        
        return $table;
    }
    
    function install(&$db)
    {
        if (!$db->create_table($this->schema())) return false;
        
        return true;
    }
    
    function uninstall(&$db)
    {
        if (!$db->query("DROP TABLE IF EXISTS pre_forms")) return false;
        
        return true;
    }
}
