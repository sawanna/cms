<?php
defined('SAWANNA') or die();

class CPhpbb
{
    var $sawanna;
	var $user;
	var $phpbb_path;
	var $globals;
	var $phpbb_url;
	var $limit;
	
    function CPhpbb(&$sawanna)
    {
        $this->sawanna=$sawanna;
		if (isset($GLOBALS['user']) && is_object($GLOBALS['user'])) $this->user=&$GLOBALS['user'];
		
		$this->globals=array();
		
		$phpbb_path=$this->sawanna->get_option('phpbb_path');
		$phpbb_url=$this->sawanna->get_option('phpbb_url');
		$limit=$this->sawanna->get_option('phpbb_limit');
		
		$this->phpbb_path=$phpbb_path ? $phpbb_path : ROOT_DIR.'/'.quotemeta($this->sawanna->module->root_dir).'/phpbb/phpBB3';
		$this->phpbb_url=$phpbb_url ? $phpbb_url : SITE.'/forum';
		$this->limit=$limit ? $limit : 5;
		
		// don't continue if phpbb disabled manually
		if (defined('DISABLE_PHPBB')) return;
		
		// registering login hook
		$this->sawanna->register_hook('user_login','login',null,'Cphpbb');
		// registering logout hook
		$this->sawanna->register_hook('user_logout','logout',null,'Cphpbb');
		// registering register hook
		$this->sawanna->register_hook('user_register','register',null,'Cphpbb');
    }
    
    function get_used_globals() {
		return array(
			'phpbb_root_path',
			'phpEx',
			'user',
			'auth',
			'template',
			'cache',
			'db',
			'config'
		);
	}
    
    function dump_globals() {
		$globals=$this->get_used_globals();
		
		if (empty($globals) || !is_array($globals)) return;
		
		foreach($globals as $global) {
		    if (isset($GLOBALS[$global])) $this->globals[$global]=$GLOBALS[$global];
		}
	}
	
	function restore_globals() {
		$globals=$this->get_used_globals();
		
		if (empty($globals) || !is_array($globals)) return;
		
		foreach($globals as $global) {
		    if (isset($this->globals[$global])) $GLOBALS[$global]=$this->globals[$global];
		}
	}
    
    function phpBB3_init() {
		define('IN_PHPBB', true);
		define('STRIP',ini_get('magic_quotes_gpc'));
		
		$GLOBALS['phpbb_root_path']=$phpbb_root_path=$this->phpbb_path.'/';
		$GLOBALS['phpEx']=$phpEx='php';
		
		if (!file_exists($this->phpbb_path)) return false;
		
		if (!file_exists($phpbb_root_path . 'config.' . $phpEx)) return false;
		
		include($phpbb_root_path . 'config.' . $phpEx);
		
		if (!defined('PHPBB_INSTALLED')) return false;
		
		include($phpbb_root_path . 'includes/acm/acm_' . $acm_type . '.' . $phpEx);
		include($phpbb_root_path . 'includes/cache.' . $phpEx);
		include($phpbb_root_path . 'includes/template.' . $phpEx);
		include($phpbb_root_path . 'includes/session.' . $phpEx);
		include($phpbb_root_path . 'includes/auth.' . $phpEx);
		include($phpbb_root_path . 'includes/functions.' . $phpEx);
		include($phpbb_root_path . 'includes/functions_content.' . $phpEx);
		include($phpbb_root_path . 'includes/constants.' . $phpEx);
		include($phpbb_root_path . 'includes/db/' . $dbms . '.' . $phpEx);
		include($phpbb_root_path . 'includes/utf/utf_tools.' . $phpEx);

		$GLOBALS['user']=$user=new user();
		$GLOBALS['auth']=$auth=new auth();
		$GLOBALS['template']=$template=new template();
		$GLOBALS['cache']=$cache=new cache();
		$GLOBALS['db']=$db=new $sql_db();

		$db->sql_connect($dbhost, $dbuser, $dbpasswd, $dbname, $dbport, false, defined('PHPBB_DB_NEW_LINK') ? PHPBB_DB_NEW_LINK : false);

		$GLOBALS['config']=$config = $cache->obtain_config();
		
		$GLOBALS['user']->session_begin();
		
		return true;
	}
    
    function login() {
		if (empty($_POST['login']) || empty($_POST['password'])) return;
		
		$this->dump_globals();
		if (!$this->phpBB3_init()) return $this->restore_globals();
		
		if (isset($GLOBALS['auth'])) {
			$result=$GLOBALS['auth']->login($_POST['login'],$_POST['password']);
		}
		$this->restore_globals();
	}
	
	function logout() {
		$this->dump_globals();
		if (!$this->phpBB3_init()) return $this->restore_globals();
		
		if (isset($GLOBALS['user'])) {
			$GLOBALS['user']->session_kill();
			$GLOBALS['user']->session_begin();
		}
		$this->restore_globals();
	}
	
	function register() {
		if (empty($_POST['login']) || empty($_POST['password']) || empty($_POST['email'])) return;
		
		$data=array(
			'username' => $_POST['login'],
			'new_password' => $_POST['password'],
			'email' => $_POST['email']
		);
		
		$this->dump_globals();
		if (!$this->phpBB3_init()) return $this->restore_globals();
		
		include($GLOBALS['phpbb_root_path'] . 'includes/functions_user.' . $GLOBALS['phpEx']);
		
		// these functions return false on success and error message on failure
		if (validate_username($data['username']) || validate_password($data['new_password']) || validate_email($data['email'])) return;
		
		$cp_data=array();
		
		$user_row = array(
					'username'				=> $data['username'],
					'user_password'			=> phpbb_hash($data['new_password']),
					'user_email'			=> $data['email'],
					'group_id'				=> 2,
					'user_timezone'			=> (float) $GLOBALS['config']['board_timezone'],
					'user_dst'				=> $GLOBALS['config']['board_dst'],
					'user_lang'				=> $GLOBALS['user']->lang_name,
					'user_type'				=> USER_NORMAL,
					'user_actkey'			=> '',
					'user_ip'				=> $GLOBALS['user']->ip,
					'user_regdate'			=> time(),
					'user_inactive_reason'	=> 0,
					'user_inactive_time'	=> 0,
				);
				
		$user_id = user_add($user_row, $cp_data);	
		
		$this->restore_globals();
	}
	
    function last_posts() {
		$return='';
		
		$this->dump_globals();
		if (!$this->phpBB3_init()) return $this->restore_globals();
		
		$sql="SELECT p.post_id,p.topic_id,p.forum_id,p.poster_id,p.post_subject,p.post_text,u.username from ".POSTS_TABLE." p inner join ".USERS_TABLE." u on u.user_id=p.poster_id where p.post_approved='1' order by post_id desc limit ".$this->limit;
		
		$_html='';
		$result = $GLOBALS['db']->sql_query($sql);
		while ($row = $GLOBALS['db']->sql_fetchrow($result)) {
			$row['post_subject']=strip_tags($row['post_subject']);
			$row['post_text']=str_replace("\n",' ',strip_tags($row['post_text']));
			$title=sawanna_strlen($row['post_subject']) > 20 ? sawanna_substr($row['post_subject'],0,20).'...' : $row['post_subject'];
			$text=sawanna_strlen($row['post_text']) > 50 ? sawanna_substr($row['post_text'],0,50).'...' : $row['post_text'];
			
			$_html.='<li><a href="'.$this->phpbb_url.'/viewtopic.php?f='.$row['forum_id'].'&t='.$row['topic_id'].'#p'.$row['post_id'].'">'.$title.'</a><span>'.$text.'</span><span class="phpbb_poster">[strf]by: %<a href="'.$this->phpbb_url.'/memberlist.php?mode=viewprofile&u='.$row['poster_id'].'" class="phpbb_user">'.$row['username'].'</a>%[/strf]</span></li>';
		}
		
		$GLOBALS['db']->sql_freeresult($result);
		$this->restore_globals();
		
		if (!empty($_html)) {
			$return=array('content'=>'<ul class="phpbb_last_posts">'.$_html.'</ul>','title'=>'[str]Recently posted[/str]');
		}
		
		return $return;
	}
}
