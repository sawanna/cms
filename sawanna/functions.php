<?php
defined('SAWANNA') or die();

function sawanna_error($str='')
{
    static $errors=array();
    
    if (!empty($str) && !is_array($str)) $errors[]=$str;
    if (!empty($str) && is_array($str)) $errors=array_merge($errors,$str);
    
    if (empty($str))
    {
        $return=$errors;
        $errors=array();
        return $return;
    }
}

function sawanna_message($str='')
{
    static $messages=array();
    
    if (!empty($str) && !is_array($str)) $messages[]=$str;
    if (!empty($str) && is_array($str)) $messages=array_merge($messages,$str);
    
    if (empty($str))
    {
        $return=$messages;
        $messages=array();
        return $return;
    }
}

function sawanna_set_error($errno, $errstr, $errfile, $errline)
{
    switch ($errno) {
        case E_USER_ERROR:
            $error='ERROR: '.$errstr.' in '.$errfile.' on line '.$errline;
            break;
        case E_USER_WARNING:
            $error='WARNING: '.$errstr.' in '.$errfile.' on line '.$errline;
            break;
        case E_USER_NOTICE:
            $error='NOTICE: '.$errstr.' in '.$errfile.' on line '.$errline;
            break;
        default:
            $error='An error occured: '.$errstr.' in '.$errfile.' on line '.$errline;
            break;
    }
    
    if (is_writable(ROOT_DIR.'/log'))
    {
        if (file_exists(ROOT_DIR.'/log/'.date('d.m.Y').'-error.log'))
        {
            @chmod(ROOT_DIR.'/log/'.date('d.m.Y').'-error.log',0600);
            
            if (!is_writable(ROOT_DIR.'/log/'.date('d.m.Y').'-error.log'))
            {
                sawanna_error($error);
                return;
            }
        }
        
        $file=fopen(ROOT_DIR.'/log/'.date('d.m.Y').'-error.log','ab');
        if ($file)
        {
            fwrite($file,$error."\n");
            fclose($file);
        }
        
        if (file_exists(ROOT_DIR.'/log/'.date('d.m.Y').'-error.log'))
        {
            @chmod(ROOT_DIR.'/log/'.date('d.m.Y').'-error.log',0000);
        }
    }
    
    if (!DEBUG_MODE) return;
    
    sawanna_error($error);
}

function sawanna_show_errors($print=true)
{ 
    $errors=sawanna_error();
    $error='';
    if (!empty($errors))
    {
        $error.='<ul class="error">';
        foreach ($errors as $error_text)
        {
            $error.='<li>'.$error_text.'</li>';
        }
        $error.='</ul>';
    }

    if (!empty($error) && $print) echo $error;
    
    return $error;
}

function sawanna_show_messages($print=true)
{
    $messages=sawanna_message();
    $message='';
    if (!empty($messages))
    {
        $message.='<ul class="message">';
        foreach ($messages as $message_text)
        {
            $message.='<li>'.$message_text.'</li>';
        }
        $message.='</ul>';
    }
    
    if (!empty($message) && $print) echo $message;
    
    return $message;
}

function empty_errors_log($all=false)
{
    $dir=opendir(REAL_PATH.'/log');
    if (!$dir) return false;
    
    $log_files=array();
    
    for ($i=0;$i<30;$i++)
    {
        $log_files[]=date('d.m.Y',mktime(0,0,0,date('n'),date('j')-$i)).'-error.log';
    }
    
    $failed=false;
    
    while (($file=readdir($dir))!==false) {
        if (is_dir($file) || !preg_match('/^([\d]{2}\.[\d]{2}\.[\d]{4})-error\.log$/',$file,$matches)) continue;
        
        if ($all || !in_array($file,$log_files)) 
        {
            $failed=@unlink(ROOT_DIR.'/log/'.$file) ? false : true;
        }
    }
    
    return !$failed;
}

function sawanna_strlen($str)
{
    if (function_exists('mb_strlen')) 
    {
        mb_internal_encoding('utf-8');
        return mb_strlen($str);
    }
    else return strlen($str);
}

function sawanna_substr($str,$start=0,$length=null)
{
    if (function_exists('mb_substr')) 
    {
        mb_internal_encoding('utf-8');
        return !empty($length) ? mb_substr($str,$start,$length) : mb_substr($str,$start);
    }
    else return !empty($length) ? substr($str,$start,$length) : substr($str,$start);
}

function array2object($array)
{
    $object=new stdClass();
    
    foreach ($array as $key=>$value)
    {
        $object->$key=$value;
    }
    
    return $object;
}

function object2array($object)
{
    return get_object_vars($object);
}

function valid_login($login)
{
    if (!preg_match('/^\w{4,255}$/',$login)) return false;
    
    return true;
}

function valid_password($password)
{
    if (!preg_match('/^.{6,255}$/',$password)) return false;
    
    return true;
}

function valid_email($email) 
{
     if (!preg_match("/^\w+([\.\w-]+)*\w@\w(([\.\w-])*\w+)*\.\w{2,4}$/",$email)) return false;
     if (strlen($email)>255) return false;
     
     return true;
}

function valid_path($path)
{
    if ($path=='[home]') return true;
    
    if (!preg_match('/^[a-zа-яё0-9\/_-]{3,255}$/iu',$path)) return false;
    
    $bad_words=array("'",'\bselect\b','\bunion\b','\bchar\b','\bhex\b','\bload_file\b','\boutfile\b','\\x00');
    if (preg_match('/'.implode('|',$bad_words).'/i',$path)) return false;
    
    if (preg_match('/^[a-zа-я0-9_-]{1,2}\//iu',$path)) return false;
    if (preg_match('/\/[a-zа-я0-9_-]{1,2}\//iu',$path)) return false;
    if (preg_match('/\/[a-zа-я0-9_-]{1,2}$/iu',$path)) return false;
    if (preg_match('/^[\d]+$/',$path)) return false;

    if (file_exists(ROOT_DIR.'/'.$path)) return false;
    
    return true;
}

function random_chars($length)
{
    if ($length<=0) return false;
    
    $chars='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $random='';
    
    do
    {
        $key=mt_rand(0,strlen($chars)-1);
        $random.=$chars[$key];
    } while(strlen($random)<$length);
    
    return $random;
}

function sawanna_show_usage()
{
    if (!DEBUG_MODE) return;
    if (!isset($GLOBALS['sawanna']->tpl) || empty($GLOBALS['sawanna']->tpl->components)) return;
    if (in_array($GLOBALS['sawanna']->query,$GLOBALS['sawanna']->module->duty_paths)) return;
    
    if (function_exists('memory_get_usage')) $memory=intval(memory_get_usage()/1024);
    if (isset($memory)) echo "\n<!--Memory usage ".$memory." kB-->";
    if (function_exists('memory_get_peak_usage')) $peak=intval(memory_get_peak_usage()/1024);
    if (isset($peak)) echo "\n<!--The peak of memory usage ".$peak." kB-->";
}

function check_install()
{
    if (
        !defined('ROOT_DIR')
        || !defined('HOST')
        || !defined('PROTOCOL')
        || !defined('DB_TYPE')
        || !defined('DB_HOST')
        || !defined('DB_PORT')
        || !defined('DB_NAME')
        || !defined('DB_USER')
        || !defined('DB_PASS')
        || !defined('DB_PREFIX')
        ) {
            return false;
        }
        
    return true;
}

function force_install()
{
    header('Location: ./install.php');
    die();
}

function version($print=false)
{
    $version='Sawanna CMS ver.'.VERSION;
    
    if ($print) echo $version;
    
    return $version;
}

function get_timezones()
{
    $timezones=array(
        'Africa' => array(
                'Africa/Abidjan'=>'Africa/Abidjan',
                'Africa/Accra'=>'Africa/Accra',
                'Africa/Addis_Ababa'=>'Africa/Addis_Ababa',
                'Africa/Algiers'=>'Africa/Algiers',
                'Africa/Asmara'=>'Africa/Asmara',
                'Africa/Asmera'=>'Africa/Asmera',
                'Africa/Bamako'=>'Africa/Bamako',
                'Africa/Bangui'=>'Africa/Bangui',
                'Africa/Banjul'=>'Africa/Banjul',
                'Africa/Bissau'=>'Africa/Bissau',
                'Africa/Blantyre'=>'Africa/Blantyre',
                'Africa/Brazzaville'=>'Africa/Brazzaville',
                'Africa/Bujumbura'=>'Africa/Bujumbura',
                'Africa/Cairo'=>'Africa/Cairo',
                'Africa/Casablanca'=>'Africa/Casablanca',
                'Africa/Ceuta'=>'Africa/Ceuta',
                'Africa/Conakry'=>'Africa/Conakry',
                'Africa/Dakar'=>'Africa/Dakar',
                'Africa/Dar_es_Salaam'=>'Africa/Dar_es_Salaam',
                'Africa/Djibouti'=>'Africa/Djibouti',
                'Africa/Douala'=>'Africa/Douala',
                'Africa/El_Aaiun'=>'Africa/El_Aaiun',
                'Africa/Freetown'=>'Africa/Freetown',
                'Africa/Gaborone'=>'Africa/Gaborone',
                'Africa/Harare'=>'Africa/Harare',
                'Africa/Johannesburg'=>'Africa/Johannesburg',
                'Africa/Kampala'=>'Africa/Kampala',
                'Africa/Khartoum'=>'Africa/Khartoum',
                'Africa/Kigali'=>'Africa/Kigali',
                'Africa/Kinshasa'=>'Africa/Kinshasa',
                'Africa/Lagos'=>'Africa/Lagos',
                'Africa/Libreville'=>'Africa/Libreville',
                'Africa/Lome'=>'Africa/Lome',
                'Africa/Luanda'=>'Africa/Luanda',
                'Africa/Lubumbashi'=>'Africa/Lubumbashi',
                'Africa/Lusaka'=>'Africa/Lusaka',
                'Africa/Malabo'=>'Africa/Malabo',
                'Africa/Maputo'=>'Africa/Maputo',
                'Africa/Maseru'=>'Africa/Maseru',
                'Africa/Mbabane'=>'Africa/Mbabane',
                'Africa/Mogadishu'=>'Africa/Mogadishu',
                'Africa/Monrovia'=>'Africa/Monrovia',
                'Africa/Nairobi'=>'Africa/Nairobi',
                'Africa/Ndjamena'=>'Africa/Ndjamena',
                'Africa/Niamey'=>'Africa/Niamey',
                'Africa/Nouakchott'=>'Africa/Nouakchott',
                'Africa/Ouagadougou'=>'Africa/Ouagadougou',
                'Africa/Porto-Novo'=>'Africa/Porto-Novo',
                'Africa/Sao_Tome'=>'Africa/Sao_Tome',
                'Africa/Timbuktu'=>'Africa/Timbuktu',
                'Africa/Tripoli'=>'Africa/Tripoli',
                'Africa/Tunis'=>'Africa/Tunis',
                'Africa/Windhoek'=>'Africa/Windhoek'
            ),
        'America' => array(
                'America/Adak'=>'America/Adak',
                'America/Anchorage'=>'America/Anchorage',
                'America/Anguilla'=>'America/Anguilla',
                'America/Antigua'=>'America/Antigua',
                'America/Araguaina'=>'America/Araguaina',
                'America/Argentina/Buenos_Aires'=>'America/Argentina/Buenos_Aires',
                'America/Argentina/Catamarca'=>'America/Argentina/Catamarca',
                'America/Argentina/ComodRivadavia'=>'America/Argentina/ComodRivadavia',
                'America/Argentina/Cordoba'=>'America/Argentina/Cordoba',
                'America/Argentina/Jujuy'=>'America/Argentina/Jujuy',
                'America/Argentina/La_Rioja'=>'America/Argentina/La_Rioja',
                'America/Argentina/Mendoza'=>'America/Argentina/Mendoza',
                'America/Argentina/Rio_Gallegos'=>'America/Argentina/Rio_Gallegos',
                'America/Argentina/Salta'=>'America/Argentina/Salta',
                'America/Argentina/San_Juan'=>'America/Argentina/San_Juan',
                'America/Argentina/San_Luis'=>'America/Argentina/San_Luis',
                'America/Argentina/Tucuman'=>'America/Argentina/Tucuman',
                'America/Argentina/Ushuaia'=>'America/Argentina/Ushuaia',
                'America/Aruba'=>'America/Aruba',
                'America/Asuncion'=>'America/Asuncion',
                'America/Atikokan'=>'America/Atikokan',
                'America/Atka'=>'America/Atka',
                'America/Bahia'=>'America/Bahia',
                'America/Barbados'=>'America/Barbados',
                'America/Belem'=>'America/Belem',
                'America/Belize'=>'America/Belize',
                'America/Blanc-Sablon'=>'America/Blanc-Sablon',
                'America/Boa_Vista'=>'America/Boa_Vista',
                'America/Bogota'=>'America/Bogota',
                'America/Boise'=>'America/Boise',
                'America/Buenos_Aires'=>'America/Buenos_Aires',
                'America/Cambridge_Bay'=>'America/Cambridge_Bay',
                'America/Campo_Grande'=>'America/Campo_Grande',
                'America/Cancun'=>'America/Cancun',
                'America/Caracas'=>'America/Caracas',
                'America/Catamarca'=>'America/Catamarca',
                'America/Cayenne'=>'America/Cayenne',
                'America/Cayman'=>'America/Cayman',
                'America/Chicago'=>'America/Chicago',
                'America/Chihuahua'=>'America/Chihuahua',
                'America/Coral_Harbour'=>'America/Coral_Harbour',
                'America/Cordoba'=>'America/Cordoba',
                'America/Costa_Rica'=>'America/Costa_Rica',
                'America/Cuiaba'=>'America/Cuiaba',
                'America/Curacao'=>'America/Curacao',
                'America/Danmarkshavn'=>'America/Danmarkshavn',
                'America/Dawson'=>'America/Dawson',
                'America/Dawson_Creek'=>'America/Dawson_Creek',
                'America/Denver'=>'America/Denver',
                'America/Detroit'=>'America/Detroit',
                'America/Dominica'=>'America/Dominica',
                'America/Edmonton'=>'America/Edmonton',
                'America/Eirunepe'=>'America/Eirunepe',
                'America/El_Salvador'=>'America/El_Salvador',
                'America/Ensenada'=>'America/Ensenada',
                'America/Fort_Wayne'=>'America/Fort_Wayne',
                'America/Fortaleza'=>'America/Fortaleza',
                'America/Glace_Bay'=>'America/Glace_Bay',
                'America/Godthab'=>'America/Godthab',
                'America/Goose_Bay'=>'America/Goose_Bay',
                'America/Grand_Turk'=>'America/Grand_Turk',
                'America/Grenada'=>'America/Grenada',
                'America/Guadeloupe'=>'America/Guadeloupe',
                'America/Guatemala'=>'America/Guatemala',
                'America/Guayaquil'=>'America/Guayaquil',
                'America/Guyana'=>'America/Guyana',
                'America/Halifax'=>'America/Halifax',
                'America/Havana'=>'America/Havana',
                'America/Hermosillo'=>'America/Hermosillo',
                'America/Indiana/Indianapolis'=>'America/Indiana/Indianapolis',
                'America/Indiana/Knox'=>'America/Indiana/Knox',
                'America/Indiana/Marengo'=>'America/Indiana/Marengo',
                'America/Indiana/Petersburg'=>'America/Indiana/Petersburg',
                'America/Indiana/Tell_City'=>'America/Indiana/Tell_City',
                'America/Indiana/Vevay'=>'America/Indiana/Vevay',
                'America/Indiana/Vincennes'=>'America/Indiana/Vincennes',
                'America/Indiana/Winamac'=>'America/Indiana/Winamac',
                'America/Indianapolis'=>'America/Indianapolis',
                'America/Inuvik'=>'America/Inuvik',
                'America/Iqaluit'=>'America/Iqaluit',
                'America/Jamaica'=>'America/Jamaica',
                'America/Jujuy'=>'America/Jujuy',
                'America/Juneau'=>'America/Juneau',
                'America/Kentucky/Louisville'=>'America/Kentucky/Louisville',
                'America/Kentucky/Monticello'=>'America/Kentucky/Monticello',
                'America/Knox_IN'=>'America/Knox_IN',
                'America/La_Paz'=>'America/La_Paz',
                'America/Lima'=>'America/Lima',
                'America/Los_Angeles'=>'America/Los_Angeles',
                'America/Louisville'=>'America/Louisville',
                'America/Maceio'=>'America/Maceio',
                'America/Managua'=>'America/Managua',
                'America/Manaus'=>'America/Manaus',
                'America/Marigot'=>'America/Marigot',
                'America/Martinique'=>'America/Martinique',
                'America/Matamoros'=>'America/Matamoros',
                'America/Mazatlan'=>'America/Mazatlan',
                'America/Mendoza'=>'America/Mendoza',
                'America/Menominee'=>'America/Menominee',
                'America/Merida'=>'America/Merida',
                'America/Mexico_City'=>'America/Mexico_City',
                'America/Miquelon'=>'America/Miquelon',
                'America/Moncton'=>'America/Moncton',
                'America/Monterrey'=>'America/Monterrey',
                'America/Montevideo'=>'America/Montevideo',
                'America/Montreal'=>'America/Montreal',
                'America/Montserrat'=>'America/Montserrat',
                'America/Nassau'=>'America/Nassau',
                'America/New_York'=>'America/New_York',
                'America/Nipigon'=>'America/Nipigon',
                'America/Nome'=>'America/Nome',
                'America/Noronha'=>'America/Noronha',
                'America/North_Dakota/Center'=>'America/North_Dakota/Center',
                'America/North_Dakota/New_Salem'=>'America/North_Dakota/New_Salem',
                'America/Ojinaga'=>'America/Ojinaga',
                'America/Panama'=>'America/Panama',
                'America/Pangnirtung'=>'America/Pangnirtung',
                'America/Paramaribo'=>'America/Paramaribo',
                'America/Phoenix'=>'America/Phoenix',
                'America/Port-au-Prince'=>'America/Port-au-Prince',
                'America/Port_of_Spain'=>'America/Port_of_Spain',
                'America/Porto_Acre'=>'America/Porto_Acre',
                'America/Porto_Velho'=>'America/Porto_Velho',
                'America/Puerto_Rico'=>'America/Puerto_Rico',
                'America/Rainy_River'=>'America/Rainy_River',
                'America/Rankin_Inlet'=>'America/Rankin_Inlet',
                'America/Recife'=>'America/Recife',
                'America/Regina'=>'America/Regina',
                'America/Resolute'=>'America/Resolute',
                'America/Rio_Branco'=>'America/Rio_Branco',
                'America/Rosario'=>'America/Rosario',
                'America/Santa_Isabel'=>'America/Santa_Isabel',
                'America/Santarem'=>'America/Santarem',
                'America/Santiago'=>'America/Santiago',
                'America/Santo_Domingo'=>'America/Santo_Domingo',
                'America/Sao_Paulo'=>'America/Sao_Paulo',
                'America/Scoresbysund'=>'America/Scoresbysund',
                'America/Shiprock'=>'America/Shiprock',
                'America/St_Barthelemy'=>'America/St_Barthelemy',
                'America/St_Johns'=>'America/St_Johns',
                'America/St_Kitts'=>'America/St_Kitts',
                'America/St_Lucia'=>'America/St_Lucia',
                'America/St_Thomas'=>'America/St_Thomas',
                'America/St_Vincent'=>'America/St_Vincent',
                'America/Swift_Current'=>'America/Swift_Current',
                'America/Tegucigalpa'=>'America/Tegucigalpa',
                'America/Thule'=>'America/Thule',
                'America/Thunder_Bay'=>'America/Thunder_Bay',
                'America/Tijuana'=>'America/Tijuana',
                'America/Toronto'=>'America/Toronto',
                'America/Tortola'=>'America/Tortola',
                'America/Vancouver'=>'America/Vancouver',
                'America/Virgin'=>'America/Virgin',
                'America/Whitehorse'=>'America/Whitehorse',
                'America/Winnipeg'=>'America/Winnipeg',
                'America/Yakutat'=>'America/Yakutat',
                'America/Yellowknife'=>'America/Yellowknife'
             ),
        'Antarctica' =>array(
                'Antarctica/Casey'=>'Antarctica/Casey',
                'Antarctica/Davis'=>'Antarctica/Davis',
                'Antarctica/DumontDUrville'=>'Antarctica/DumontDUrville',
                'Antarctica/Macquarie'=>'Antarctica/Macquarie',
                'Antarctica/Mawson'=>'Antarctica/Mawson',
                'Antarctica/McMurdo'=>'Antarctica/McMurdo',
                'Antarctica/Palmer'=>'Antarctica/Palmer',
                'Antarctica/Rothera'=>'Antarctica/Rothera',
                'Antarctica/South_Pole'=>'Antarctica/South_Pole',
                'Antarctica/Syowa'=>'Antarctica/Syowa',
                'Antarctica/Vostok'=>'Antarctica/Vostok'
            ),
        'Arctic' => array(
                'Arctic/Longyearbyen'=>'Arctic/Longyearbyen'
            ),
        'Asia' => array(
                'Asia/Aden'=>'Asia/Aden',
                'Asia/Almaty'=>'Asia/Almaty',
                'Asia/Amman'=>'Asia/Amman',
                'Asia/Anadyr'=>'Asia/Anadyr',
                'Asia/Aqtau'=>'Asia/Aqtau',
                'Asia/Aqtobe'=>'Asia/Aqtobe',
                'Asia/Ashgabat'=>'Asia/Ashgabat',
                'Asia/Ashkhabad'=>'Asia/Ashkhabad',
                'Asia/Baghdad'=>'Asia/Baghdad',
                'Asia/Bahrain'=>'Asia/Bahrain',
                'Asia/Baku'=>'Asia/Baku',
                'Asia/Bangkok'=>'Asia/Bangkok',
                'Asia/Beirut'=>'Asia/Beirut',
                'Asia/Bishkek'=>'Asia/Bishkek',
                'Asia/Brunei'=>'Asia/Brunei',
                'Asia/Calcutta'=>'Asia/Calcutta',
                'Asia/Choibalsan'=>'Asia/Choibalsan',
                'Asia/Chongqing'=>'Asia/Chongqing',
                'Asia/Chungking'=>'Asia/Chungking',
                'Asia/Colombo'=>'Asia/Colombo',
                'Asia/Dacca'=>'Asia/Dacca',
                'Asia/Damascus'=>'Asia/Damascus',
                'Asia/Dhaka'=>'Asia/Dhaka',
                'Asia/Dili'=>'Asia/Dili',
                'Asia/Dubai'=>'Asia/Dubai',
                'Asia/Dushanbe'=>'Asia/Dushanbe',
                'Asia/Gaza'=>'Asia/Gaza',
                'Asia/Harbin'=>'Asia/Harbin',
                'Asia/Ho_Chi_Minh'=>'Asia/Ho_Chi_Minh',
                'Asia/Hong_Kong'=>'Asia/Hong_Kong',
                'Asia/Hovd'=>'Asia/Hovd',
                'Asia/Irkutsk'=>'Asia/Irkutsk',
                'Asia/Istanbul'=>'Asia/Istanbul',
                'Asia/Jakarta'=>'Asia/Jakarta',
                'Asia/Jayapura'=>'Asia/Jayapura',
                'Asia/Jerusalem'=>'Asia/Jerusalem',
                'Asia/Kabul'=>'Asia/Kabul',
                'Asia/Kamchatka'=>'Asia/Kamchatka',
                'Asia/Karachi'=>'Asia/Karachi',
                'Asia/Kashgar'=>'Asia/Kashgar',
                'Asia/Kathmandu'=>'Asia/Kathmandu',
                'Asia/Katmandu'=>'Asia/Katmandu',
                'Asia/Kolkata'=>'Asia/Kolkata',
                'Asia/Krasnoyarsk'=>'Asia/Krasnoyarsk',
                'Asia/Kuala_Lumpur'=>'Asia/Kuala_Lumpur',
                'Asia/Kuching'=>'Asia/Kuching',
                'Asia/Kuwait'=>'Asia/Kuwait',
                'Asia/Macao'=>'Asia/Macao',
                'Asia/Macau'=>'Asia/Macau',
                'Asia/Magadan'=>'Asia/Magadan',
                'Asia/Makassar'=>'Asia/Makassar',
                'Asia/Manila'=>'Asia/Manila',
                'Asia/Muscat'=>'Asia/Muscat',
                'Asia/Nicosia'=>'Asia/Nicosia',
                'Asia/Novokuznetsk'=>'Asia/Novokuznetsk',
                'Asia/Novosibirsk'=>'Asia/Novosibirsk',
                'Asia/Omsk'=>'Asia/Omsk',
                'Asia/Oral'=>'Asia/Oral',
                'Asia/Phnom_Penh'=>'Asia/Phnom_Penh',
                'Asia/Pontianak'=>'Asia/Pontianak',
                'Asia/Pyongyang'=>'Asia/Pyongyang',
                'Asia/Qatar'=>'Asia/Qatar',
                'Asia/Qyzylorda'=>'Asia/Qyzylorda',
                'Asia/Rangoon'=>'Asia/Rangoon',
                'Asia/Riyadh'=>'Asia/Riyadh',
                'Asia/Saigon'=>'Asia/Saigon',
                'Asia/Sakhalin'=>'Asia/Sakhalin',
                'Asia/Samarkand'=>'Asia/Samarkand',
                'Asia/Seoul'=>'Asia/Seoul',
                'Asia/Shanghai'=>'Asia/Shanghai',
                'Asia/Singapore'=>'Asia/Singapore',
                'Asia/Taipei'=>'Asia/Taipei',
                'Asia/Tashkent'=>'Asia/Tashkent',
                'Asia/Tbilisi'=>'Asia/Tbilisi',
                'Asia/Tehran'=>'Asia/Tehran',
                'Asia/Tel_Aviv'=>'Asia/Tel_Aviv',
                'Asia/Thimbu'=>'Asia/Thimbu',
                'Asia/Thimphu'=>'Asia/Thimphu',
                'Asia/Tokyo'=>'Asia/Tokyo',
                'Asia/Ujung_Pandang'=>'Asia/Ujung_Pandang',
                'Asia/Ulaanbaatar'=>'Asia/Ulaanbaatar',
                'Asia/Ulan_Bator'=>'Asia/Ulan_Bator',
                'Asia/Urumqi'=>'Asia/Urumqi',
                'Asia/Vientiane'=>'Asia/Vientiane',
                'Asia/Vladivostok'=>'Asia/Vladivostok',
                'Asia/Yakutsk'=>'Asia/Yakutsk',
                'Asia/Yekaterinburg'=>'Asia/Yekaterinburg',
                'Asia/Yerevan'=>'Asia/Yerevan'
            ),
        'Atlantic' => array(
                'Atlantic/Azores'=>'Atlantic/Azores',
                'Atlantic/Bermuda'=>'Atlantic/Bermuda',
                'Atlantic/Canary'=>'Atlantic/Canary',
                'Atlantic/Cape_Verde'=>'Atlantic/Cape_Verde',
                'Atlantic/Faeroe'=>'Atlantic/Faeroe',
                'Atlantic/Faroe'=>'Atlantic/Faroe',
                'Atlantic/Jan_Mayen'=>'Atlantic/Jan_Mayen',
                'Atlantic/Madeira'=>'Atlantic/Madeira',
                'Atlantic/Reykjavik'=>'Atlantic/Reykjavik',
                'Atlantic/South_Georgia'=>'Atlantic/South_Georgia',
                'Atlantic/St_Helena'=>'Atlantic/St_Helena',
                'Atlantic/Stanley'=>'Atlantic/Stanley'
            ),
        'Australia' => array(
                'Australia/ACT'=>'Australia/ACT',
                'Australia/Adelaide'=>'Australia/Adelaide',
                'Australia/Brisbane'=>'Australia/Brisbane',
                'Australia/Broken_Hill'=>'Australia/Broken_Hill',
                'Australia/Canberra'=>'Australia/Canberra',
                'Australia/Currie'=>'Australia/Currie',
                'Australia/Darwin'=>'Australia/Darwin',
                'Australia/Eucla'=>'Australia/Eucla',
                'Australia/Hobart'=>'Australia/Hobart',
                'Australia/LHI'=>'Australia/LHI',
                'Australia/Lindeman'=>'Australia/Lindeman',
                'Australia/Lord_Howe'=>'Australia/Lord_Howe',
                'Australia/Melbourne'=>'Australia/Melbourne',
                'Australia/North'=>'Australia/North',
                'Australia/NSW'=>'Australia/NSW',
                'Australia/Perth'=>'Australia/Perth',
                'Australia/Queensland'=>'Australia/Queensland',
                'Australia/South'=>'Australia/South',
                'Australia/Sydney'=>'Australia/Sydney',
                'Australia/Tasmania'=>'Australia/Tasmania',
                'Australia/Victoria'=>'Australia/Victoria',
                'Australia/West'=>'Australia/West',
                'Australia/Yancowinna'=>'Australia/Yancowinna'
            ),
        'Europe' =>array(
                'Europe/Amsterdam'=>'Europe/Amsterdam',
                'Europe/Andorra'=>'Europe/Andorra',
                'Europe/Athens'=>'Europe/Athens',
                'Europe/Belfast'=>'Europe/Belfast',
                'Europe/Belgrade'=>'Europe/Belgrade',
                'Europe/Berlin'=>'Europe/Berlin',
                'Europe/Bratislava'=>'Europe/Bratislava',
                'Europe/Brussels'=>'Europe/Brussels',
                'Europe/Bucharest'=>'Europe/Bucharest',
                'Europe/Budapest'=>'Europe/Budapest',
                'Europe/Chisinau'=>'Europe/Chisinau',
                'Europe/Copenhagen'=>'Europe/Copenhagen',
                'Europe/Dublin'=>'Europe/Dublin',
                'Europe/Gibraltar'=>'Europe/Gibraltar',
                'Europe/Guernsey'=>'Europe/Guernsey',
                'Europe/Helsinki'=>'Europe/Helsinki',
                'Europe/Isle_of_Man'=>'Europe/Isle_of_Man',
                'Europe/Istanbul'=>'Europe/Istanbul',
                'Europe/Jersey'=>'Europe/Jersey',
                'Europe/Kaliningrad'=>'Europe/Kaliningrad',
                'Europe/Kiev'=>'Europe/Kiev',
                'Europe/Lisbon'=>'Europe/Lisbon',
                'Europe/Ljubljana'=>'Europe/Ljubljana',
                'Europe/London'=>'Europe/London',
                'Europe/Luxembourg'=>'Europe/Luxembourg',
                'Europe/Madrid'=>'Europe/Madrid',
                'Europe/Malta'=>'Europe/Malta',
                'Europe/Mariehamn'=>'Europe/Mariehamn',
                'Europe/Minsk'=>'Europe/Minsk',
                'Europe/Monaco'=>'Europe/Monaco',
                'Europe/Moscow'=>'Europe/Moscow',
                'Europe/Nicosia'=>'Europe/Nicosia',
                'Europe/Oslo'=>'Europe/Oslo',
                'Europe/Paris'=>'Europe/Paris',
                'Europe/Podgorica'=>'Europe/Podgorica',
                'Europe/Prague'=>'Europe/Prague',
                'Europe/Riga'=>'Europe/Riga',
                'Europe/Rome'=>'Europe/Rome',
                'Europe/Samara'=>'Europe/Samara',
                'Europe/San_Marino'=>'Europe/San_Marino',
                'Europe/Sarajevo'=>'Europe/Sarajevo',
                'Europe/Simferopol'=>'Europe/Simferopol',
                'Europe/Skopje'=>'Europe/Skopje',
                'Europe/Sofia'=>'Europe/Sofia',
                'Europe/Stockholm'=>'Europe/Stockholm',
                'Europe/Tallinn'=>'Europe/Tallinn',
                'Europe/Tirane'=>'Europe/Tirane',
                'Europe/Tiraspol'=>'Europe/Tiraspol',
                'Europe/Uzhgorod'=>'Europe/Uzhgorod',
                'Europe/Vaduz'=>'Europe/Vaduz',
                'Europe/Vatican'=>'Europe/Vatican',
                'Europe/Vienna'=>'Europe/Vienna',
                'Europe/Vilnius'=>'Europe/Vilnius',
                'Europe/Volgograd'=>'Europe/Volgograd',
                'Europe/Warsaw'=>'Europe/Warsaw',
                'Europe/Zagreb'=>'Europe/Zagreb',
                'Europe/Zaporozhye'=>'Europe/Zaporozhye',
                'Europe/Zurich'=>'Europe/Zurich'
            ),
        'Indian' => array(
                'Indian/Antananarivo'=>'Indian/Antananarivo',
                'Indian/Chagos'=>'Indian/Chagos',
                'Indian/Christmas'=>'Indian/Christmas',
                'Indian/Cocos'=>'Indian/Cocos',
                'Indian/Comoro'=>'Indian/Comoro',
                'Indian/Kerguelen'=>'Indian/Kerguelen',
                'Indian/Mahe'=>'Indian/Mahe',
                'Indian/Maldives'=>'Indian/Maldives',
                'Indian/Mauritius'=>'Indian/Mauritius',
                'Indian/Mayotte'=>'Indian/Mayotte',
                'Indian/Reunion'=>'Indian/Reunion'
            ),
        'Pacific' => array(
                'Pacific/Apia'=>'Pacific/Apia',
                'Pacific/Auckland'=>'Pacific/Auckland',
                'Pacific/Chatham'=>'Pacific/Chatham',
                'Pacific/Easter'=>'Pacific/Easter',
                'Pacific/Efate'=>'Pacific/Efate',
                'Pacific/Enderbury'=>'Pacific/Enderbury',
                'Pacific/Fakaofo'=>'Pacific/Fakaofo',
                'Pacific/Fiji'=>'Pacific/Fiji',
                'Pacific/Funafuti'=>'Pacific/Funafuti',
                'Pacific/Galapagos'=>'Pacific/Galapagos',
                'Pacific/Gambier'=>'Pacific/Gambier',
                'Pacific/Guadalcanal'=>'Pacific/Guadalcanal',
                'Pacific/Guam'=>'Pacific/Guam',
                'Pacific/Honolulu'=>'Pacific/Honolulu',
                'Pacific/Johnston'=>'Pacific/Johnston',
                'Pacific/Kiritimati'=>'Pacific/Kiritimati',
                'Pacific/Kosrae'=>'Pacific/Kosrae',
                'Pacific/Kwajalein'=>'Pacific/Kwajalein',
                'Pacific/Majuro'=>'Pacific/Majuro',
                'Pacific/Marquesas'=>'Pacific/Marquesas',
                'Pacific/Midway'=>'Pacific/Midway',
                'Pacific/Nauru'=>'Pacific/Nauru',
                'Pacific/Niue'=>'Pacific/Niue',
                'Pacific/Norfolk'=>'Pacific/Norfolk',
                'Pacific/Noumea'=>'Pacific/Noumea',
                'Pacific/Pago_Pago'=>'Pacific/Pago_Pago',
                'Pacific/Palau'=>'Pacific/Palau',
                'Pacific/Pitcairn'=>'Pacific/Pitcairn',
                'Pacific/Ponape'=>'Pacific/Ponape',
                'Pacific/Port_Moresby'=>'Pacific/Port_Moresby',
                'Pacific/Rarotonga'=>'Pacific/Rarotonga',
                'Pacific/Saipan'=>'Pacific/Saipan',
                'Pacific/Samoa'=>'Pacific/Samoa',
                'Pacific/Tahiti'=>'Pacific/Tahiti',
                'Pacific/Tarawa'=>'Pacific/Tarawa',
                'Pacific/Tongatapu'=>'Pacific/Tongatapu',
                'Pacific/Truk'=>'Pacific/Truk',
                'Pacific/Wake'=>'Pacific/Wake',
                'Pacific/Wallis'=>'Pacific/Wallis',
                'Pacific/Yap'=>'Pacific/Yap'
            ),
        'Others' => array(
                'Brazil/Acre'=>'Brazil/Acre',
                'Brazil/DeNoronha'=>'Brazil/DeNoronha',
                'Brazil/East'=>'Brazil/East',
                'Brazil/West'=>'Brazil/West',
                'Canada/Atlantic'=>'Canada/Atlantic',
                'Canada/Central'=>'Canada/Central',
                'Canada/East-Saskatchewan'=>'Canada/East-Saskatchewan',
                'Canada/Eastern'=>'Canada/Eastern',
                'Canada/Mountain'=>'Canada/Mountain',
                'Canada/Newfoundland'=>'Canada/Newfoundland',
                'Canada/Pacific'=>'Canada/Pacific',
                'Canada/Saskatchewan'=>'Canada/Saskatchewan',
                'Canada/Yukon'=>'Canada/Yukon',
                'CET'=>'CET',
                'Chile/Continental'=>'Chile/Continental',
                'Chile/EasterIsland'=>'Chile/EasterIsland',
                'CST6CDT'=>'CST6CDT',
                'Cuba'=>'Cuba',
                'EET'=>'EET',
                'Egypt'=>'Egypt',
                'Eire'=>'Eire',
                'EST'=>'EST',
                'EST5EDT'=>'EST5EDT',
                'Etc/GMT'=>'Etc/GMT',
                'Etc/GMT+0'=>'Etc/GMT+0',
                'Etc/GMT+1'=>'Etc/GMT+1',
                'Etc/GMT+10'=>'Etc/GMT+10',
                'Etc/GMT+11'=>'Etc/GMT+11',
                'Etc/GMT+12'=>'Etc/GMT+12',
                'Etc/GMT+2'=>'Etc/GMT+2',
                'Etc/GMT+3'=>'Etc/GMT+3',
                'Etc/GMT+4'=>'Etc/GMT+4',
                'Etc/GMT+5'=>'Etc/GMT+5',
                'Etc/GMT+6'=>'Etc/GMT+6',
                'Etc/GMT+7'=>'Etc/GMT+7',
                'Etc/GMT+8'=>'Etc/GMT+8',
                'Etc/GMT+9'=>'Etc/GMT+9',
                'Etc/GMT-0'=>'Etc/GMT-0',
                'Etc/GMT-1'=>'Etc/GMT-1',
                'Etc/GMT-10'=>'Etc/GMT-10',
                'Etc/GMT-11'=>'Etc/GMT-11',
                'Etc/GMT-12'=>'Etc/GMT-12',
                'Etc/GMT-13'=>'Etc/GMT-13',
                'Etc/GMT-14'=>'Etc/GMT-14',
                'Etc/GMT-2'=>'Etc/GMT-2',
                'Etc/GMT-3'=>'Etc/GMT-3',
                'Etc/GMT-4'=>'Etc/GMT-4',
                'Etc/GMT-5'=>'Etc/GMT-5',
                'Etc/GMT-6'=>'Etc/GMT-6',
                'Etc/GMT-7'=>'Etc/GMT-7',
                'Etc/GMT-8'=>'Etc/GMT-8',
                'Etc/GMT-9'=>'Etc/GMT-9',
                'Etc/GMT0'=>'Etc/GMT0',
                'Etc/Greenwich'=>'Etc/Greenwich',
                'Etc/UCT'=>'Etc/UCT',
                'Etc/Universal'=>'Etc/Universal',
                'Etc/UTC'=>'Etc/UTC',
                'Etc/Zulu'=>'Etc/Zulu',
                'Factory'=>'Factory',
                'GB'=>'GB',
                'GB-Eire'=>'GB-Eire',
                'GMT'=>'GMT',
                'GMT+0'=>'GMT+0',
                'GMT-0'=>'GMT-0',
                'GMT0'=>'GMT0',
                'Greenwich'=>'Greenwich',
                'Hongkong'=>'Hongkong',
                'HST'=>'HST',
                'Iceland'=>'Iceland',
                'Iran'=>'Iran',
                'Israel'=>'Israel',
                'Jamaica'=>'Jamaica',
                'Japan'=>'Japan',
                'Kwajalein'=>'Kwajalein',
                'Libya'=>'Libya',
                'MET'=>'MET',
                'Mexico/BajaNorte'=>'Mexico/BajaNorte',
                'Mexico/BajaSur'=>'Mexico/BajaSur',
                'Mexico/General'=>'Mexico/General',
                'MST'=>'MST',
                'MST7MDT'=>'MST7MDT',
                'Navajo'=>'Navajo',
                'NZ'=>'NZ',
                'NZ-CHAT'=>'NZ-CHAT',
                'Poland'=>'Poland',
                'Portugal'=>'Portugal',
                'PRC'=>'PRC',
                'PST8PDT'=>'PST8PDT',
                'ROC'=>'ROC',
                'ROK'=>'ROK',
                'Singapore'=>'Singapore',
                'Turkey'=>'Turkey',
                'UCT'=>'UCT',
                'Universal'=>'Universal',
                'US/Alaska'=>'US/Alaska',
                'US/Aleutian'=>'US/Aleutian',
                'US/Arizona'=>'US/Arizona',
                'US/Central'=>'US/Central',
                'US/East-Indiana'=>'US/East-Indiana',
                'US/Eastern'=>'US/Eastern',
                'US/Hawaii'=>'US/Hawaii',
                'US/Indiana-Starke'=>'US/Indiana-Starke',
                'US/Michigan'=>'US/Michigan',
                'US/Mountain'=>'US/Mountain',
                'US/Pacific'=>'US/Pacific',
                'US/Pacific-New'=>'US/Pacific-New',
                'US/Samoa'=>'US/Samoa',
                'UTC'=>'UTC',
                'W-SU'=>'W-SU',
                'WET'=>'WET',
                'Zulu'=>'Zulu'
            )
    );
    
    return $timezones;
}

function valid_timezone($timezone)
{
    $timezones=get_timezones();
    
    foreach ($timezones as $zone=>$zones)
    {
        if (array_key_exists($timezone,$zones)) return true;
    }
    
    return false;
}

function default_time_zone()
{
    if (!DEBUG_MODE)
        return function_exists('date_default_timezone_get') ? date_default_timezone_get() : '';
    else 
        return 'GMT';
}

function exec_time($show_total=false)
{
    static $time=0;
    static $total=0;
    
    $exec_time=0;
    
    $_time=microtime(1);
    if (!empty($time)) $exec_time=number_format($_time-$time,4);
    
    $time=$_time;
    $total+=$exec_time;
    
    return $show_total ? $total : $exec_time;
}

function hex2RGB($hexStr) {
    $hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr);
    
    if (strlen($hexStr) == 6) {
        $colorVal = hexdec($hexStr);
        
        $r = 0xFF & ($colorVal >> 0x10);
        $g = 0xFF & ($colorVal >> 0x8);
        $b = 0xFF & $colorVal;
    } elseif (strlen($hexStr) == 3) {
        $r = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
        $g = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
        $b = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
    } else {
        return false;
    }
    
    return array($r, $g, $b);
}

function encode_url($url)
{
    $url=urlencode($url);
    $url=str_replace('%3A',':',$url);
    $url=str_replace('%2F','/',$url);
    $url=str_replace('%3F','?',$url);
    $url=str_replace('%3D','=',$url);
    $url=str_replace('%26','&',$url);
    $url=str_replace('%23','#',$url);
    
    return $url;
}

function gzip_PrintFourChars($Val) 
{
    for ($i = 0; $i < 4; $i ++) 
    {
        echo chr($Val % 256);
        $Val = floor($Val / 256);
    }
}

function sawanna_json_encode($a=false)
{
	if (function_exists('json_encode')) return json_encode($a);
	
	if (is_null($a)) return 'null';
	if ($a === false) return 'false';
	if ($a === true) return 'true';
	if (is_scalar($a))
	{
		if (is_float($a))
		{
			return floatval(str_replace(",", ".", strval($a)));
		}

		if (is_string($a))
		{
			static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
			return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
		}
		else
			return $a;
	}
	$isList = true;
	for ($i = 0; reset($a); $i++) {
		if (key($a) !== $i)
		{
			$isList = false;
			break;
		}
	}
	$result = array();
	if ($isList)
	{
		foreach ($a as $v) $result[] = sawanna_json_encode($v);
		return '[' . join(',', $result) . ']';
	}
	else
	{
		foreach ($a as $k => $v) $result[] = sawanna_json_encode($k).':'.sawanna_json_encode($v);
		return '{' . join(',', $result) . '}';
	}
}
