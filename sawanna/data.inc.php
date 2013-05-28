<?php 
defined('SAWANNA') or die();

class CData
{
    var $value;
    var $max_length;
    
    function CData()
    {
        $this->value='';
        $this->max_length=1000000000;
    }
    
    function encode_str($str)
    {
        $this->value.='<str>'.$str.'</str>';
        
        return $this->value;
    }
    
    function decode_str($str)
    {
        if (is_string($str)) 
        {
            $value=$str;
        } else
        {
            $value='';
        }
        
        return $value;
    }
    
    function encode_num($num)
    {
        $this->value.='<num>'.$num.'</num>';
        
        return $this->value;
    }
    
    function decode_num($num)
    {
        if (is_numeric($num))
        {
            $value=$num;
        } else
        {
            $value=0;
        }
        
        return $value;
    }
    
    function encode_bool($bool)
    {
        $_bool=$bool ? 1 : 0;
        
        $this->value.='<bool>'.$_bool.'</bool>';
        
        return $this->value;
    }
    
    function decode_bool($bool)
    {
        if ($bool==0)
        {
            $value=false;
        } else
        {
            $value=true;
        }
        
        return $value;
    }
    
    function encode_arr($arr)
    {
        $this->value.='<arr>';
        
        foreach($arr as $key=>$val)
        {
            $this->value.='<elem>'.'<key>'.$key.'</key>'.'<value>';
            
            if (is_string($val))
            {
                $this->encode_str($val);
            }
            elseif (is_numeric($val))
            {
                $this->encode_num($val);
            }
            elseif (is_bool($val))
            {
                $this->encode_bool($val);
            }
            elseif (is_array($val))
            {
               $this->encode_arr($val);
            }
            else
            {
                trigger_error('Cannot serialize array element. Expected string, number, boolean or array.',E_USER_WARNING);
                continue;
            }
            
            $this->value.='</value>'.'</elem>';
        }
        
        $this->value.='</arr>';
        
        return $this->value;
    }
    
    function decode_arr(&$arr)
    {
        $val=array();
        
        while ($this->first_match('<elem><key>','</key><value>',$arr,$karr) && $this->first_match('</key><value>','</value></elem>',$arr,$marr))
        {
            $value='';
            if ($this->last_match('<str>','</str>',$marr,$varr,true,true))
            {
                $value=$this->decode_str($varr);
            }
            elseif ($this->last_match('<num>','</num>',$marr,$varr,true,true))
            {
                $value=$this->decode_num($varr);
            }
            elseif ($this->last_match('<bool>','</bool>',$marr,$varr,true,true))
            {
                $value=$this->decode_bool($varr);
            }
            elseif ($this->first_match('<arr>','',$arr,$arrr))
            {
                $varr=$this->get_arr_content($arrr);
                
                if ($varr===false)
                {
                    trigger_error('Data parse error. Expected string, number, boolean or array.',E_USER_WARNING);
                    break;
                } else
                {
                    $marr='<arr>'.$varr.'</arr>';
                    $value=$this->decode_arr($varr);
                }
            }
            else
            {
                trigger_error('Data parse error. Expected string, number, boolean or array.',E_USER_WARNING);
                break;
            }
            
            if (strpos($arr,'<elem><key>'.$karr.'</key><value>'.$marr.'</value></elem>')!==false)
            {
                $start_str='<elem><key>'.$karr.'</key>';
                $end_str='<value>'.$marr.'</value></elem>';
                
                $start=strpos($arr,$start_str);
                $end=strpos($arr,$end_str)+strlen($end_str);
                
                $arr=substr_replace($arr,'',$start,$end);
            } else
            {
                trigger_error('Data parse error. Expected string, number, boolean or array.',E_USER_WARNING);
                break;
            }
           
            $val[$karr]=$value;
        }
        
        return $val;
    }
    
    function get_arr_content(&$data)
    {
        $code=$data;
        $closer=$this->find_arr_closer($code);
        
        if ($closer!==false)
            return substr($code,0,$closer);
        else
            return false;
    }
    
    function find_arr_closer($code)
    {
        $offset=0;
        while (($p=strpos($code,'</arr>',$offset))!==false)
        {
            if ($this->is_arr_closed(substr($code,0,$p)))
                return $p;
            else
                $offset=$p+6;
        }
        return false;
    }
    
    function is_arr_closed($code)
    {
        $b=substr_count($code,'<arr>');
        $e=substr_count($code,'</arr>');
        
        if ($b != $e) return false;
        return true;
    }
    
    function clear()
    {
        $this->value='';
    }
 
    function do_serialize($var)
    {
        $this->clear();

        if (is_string($var))
        {
            $_var=$this->encode_str($var);
        }
        elseif (is_numeric($var))
        {
            $_var=$this->encode_num($var);
        }
        elseif (is_bool($var))
        {
            $_var=$this->encode_bool($var);
        }
        elseif (is_array($var))
        {
            $_var=$this->encode_arr($var);
        }
        else
        {
            trigger_error('Cannot serialize element. Expected string, number, boolean or array. Unknown given',E_USER_WARNING);
            return false;
        }
        
        $this->value='<data>'.$_var.'</data>';
        
        if (strlen($this->value)>$this->max_length)
        {
            trigger_error('Cannot serialize data. Max. length exceeded ('.htmlspecialchars(strlen($this->value)).')',E_USER_WARNING);
            return;
        }
        
        return $this->value;
    }
    
    function do_unserialize(&$str)
    {
        if (empty($str)) return;
        
        if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) data_exec_time();
        
        $this->clear();

        if ($this->last_match('<data>','</data>',$str,$mstr))
        { 
            if ($this->last_match('<str>','</str>',$mstr,$varr,true,true))
            {
                $this->value=$this->decode_str($varr);
            }
            elseif ($this->last_match('<num>','</num>',$mstr,$varr,true,true))
            {
                $this->value=$this->decode_num($varr);
            }
            elseif ($this->last_match('<bool>','</bool>',$mstr,$varr,true,true))
            {
                $this->value=$this->decode_bool($varr);
            }
            elseif ($this->last_match('<arr>','</arr>',$mstr,$varr,true,true))
            {
                $this->value=$this->decode_arr($varr);
            }
            else
            {
                trigger_error('Cannot unserialize element. Expected string, number, boolean or array.',E_USER_WARNING);
            }
        } else 
        {
            trigger_error('Cannot unserialize string. Data corrupted : '.htmlspecialchars($str),E_USER_WARNING);
        }
        
        if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) data_exec_time(1);
        
        return $this->value;
    }
    
    function is_serialized(&$str)
    {
         if ($this->last_match('<data>','</data>',$str)) return true;
         
         return false;
    }
    
    function first_match($match_begin,$match_end,&$string,&$match=null,$start_with_begin=false,$finish_with_end=false)
    {
        $mb=strpos($string,$match_begin);
        if ((!$start_with_begin && $mb!==false) || ($start_with_begin && $mb===0))
        {
            $sb=$mb+strlen($match_begin);    
        
            if (!empty($match_end))
            {
                $me=strpos($string,$match_end,$sb);
                if ((!$finish_with_end && $me!==false) || ($finish_with_end && $me==strlen($string)-strlen($match_end)))
                { 
                    $match=substr($string,$sb,$me-$sb);
                } else return false;
            } else 
            {
                $match=substr($string,$sb);
            }
        } else return false;
        
        return true;
    }
    
    function last_match($match_begin,$match_end,$string,&$match=null,$start_with_begin=false,$finish_with_end=false)
    {
        $mb=strpos($string,$match_begin);
        if ((!$start_with_begin && $mb!==false) || ($start_with_begin && $mb===0))
        {
            $sb=$mb+strlen($match_begin);    
        
            if (!empty($match_end))
            {
                $me=strrpos($string,$match_end,$sb);
                if ((!$finish_with_end && $me!==false) || ($finish_with_end && $me==strlen($string)-strlen($match_end)))
                { 
                    $match=substr($string,$sb,$me-$sb);
                } else return false;
            } else 
            {
                $match=substr($string,$sb);
            }
        } else return false;
        
        return true;
    }
}

// this class is not using at the moment
class _CData
{
    var $value;
    var $max_length;
    
    function CData()
    {
        $this->value='';
        $this->max_length=1000000000;
    }
    
    function clear()
    {
        $this->value='';
    }
 
    function do_serialize($var)
    {
        $this->clear();
        
        $this->value='base64:'.base64_encode(serialize($var));
        
        if (strlen($this->value)>$this->max_length)
        {
            trigger_error('Cannot serialize data. Max. length exceeded ('.htmlspecialchars(strlen($this->value)).')',E_USER_WARNING);
            return;
        }
        
        return $this->value;
    }
    
    function do_unserialize(&$str)
    {
        if (empty($str)) return;
        
        if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) data_exec_time();
        
        $this->clear();

        if ($this->is_serialized($str))
        { 
            $this->value=unserialize(base64_decode(substr($str,7)));
        } else 
        {
            trigger_error('Cannot unserialize string. Data corrupted : '.htmlspecialchars($str),E_USER_WARNING);
        }
        
        if (DEBUG_MODE && defined('EXEC_TIME') && EXEC_TIME) data_exec_time(1);
        
        return $this->value;
    }
    
    function is_serialized($str)
    {
         if (substr($str,0,7)=='base64:') return true;
         
         return false;
    }
}

function data_exec_time($register=false,$show_total=false)
{
    static $time=0;
    static $total=0;
    
    $exec_time=0;
    
    $_time=microtime(1);
    if ($register) $exec_time=number_format($_time-$time,4);
    
    $time=$_time;
    $total+=$exec_time;
    
    return $show_total ? $total : $exec_time;
}
