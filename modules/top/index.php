<?php
defined('SAWANNA') or die();

class CTop
{
    var $sawanna;
    var $module_id;
    var $menu;
    var $user;
    
    var $most_visited_limit;
    var $top_rated_limit;
    var $top_visited_exclude_paths;
    var $counting;
    
    function CTop(&$sawanna)
    {
        $this->sawanna=$sawanna;
        $this->module_id=$this->sawanna->module->get_module_id('top');
        
        $most_visited_limit=$this->sawanna->get_option('top_most_visited_limit');
        $this->most_visited_limit=$most_visited_limit ? ((int) $most_visited_limit) : 5;
        
        $top_rated_limit=$this->sawanna->get_option('top_top_rated_limit');
        $this->top_rated_limit=$top_rated_limit ? ((int) $top_rated_limit) : 5;
        
        $stats=$this->sawanna->get_option('top_stats');
        $this->counting=$stats ? true : false;
        
        $this->top_visited_exclude_paths=array('captcha','search','sitemap','sitemap-xml','users','login','logout','recover','file','image','css','ping','cron');
        $top_visited_exclude_paths=trim($this->sawanna->get_option('top_visited_exclude_paths'));
        if (!empty($top_visited_exclude_paths)) {
			$top_visited_exclude_paths=explode("\r\n",$top_visited_exclude_paths);
			foreach($top_visited_exclude_paths as $excluded_path) {
				$this->top_visited_exclude_paths[]=trim($excluded_path);
			}
		}
        
        
        if ($this->sawanna->query != $this->sawanna->config->admin_area) 
        {
            $this->sawanna->tpl->add_script($this->sawanna->module->root_dir.'/'.$this->sawanna->module->modules[$this->module_id]->root.'/js/rate');
        }
        
        $this->menu=&$GLOBALS['menu'];
        $this->user=&$GLOBALS['user'];
    }
    
    function ready() {
		$event=array(
            'url' => 'vote',
            'caller' => 'CTop',
            'job' => 'vote',
            'constant' => 1
        );
        
        $this->sawanna->event->register($event);
        
		$query=$this->sawanna->query;
		
		if ($this->sawanna->module->module_exists('ajaxer') && !empty($_POST['ajaxer_path'])) {
			$path=$GLOBALS['ajaxer']->extract_path($_POST['ajaxer_path']);
			$query=urldecode($path);
		}
		
		if ($this->counting && !empty($query) && $query != $this->sawanna->config->admin_area && strpos($query,'user')!==0) {
			$reg_path=$this->sawanna->navigator->get($query);
			if ($reg_path && $reg_path->render) {
				$this->count_visited_path($query);
			}
		}
		
		if (isset($GLOBALS['content']) && is_object($GLOBALS['content']) && isset($GLOBALS['content']->content_alters) && is_array($GLOBALS['content']->content_alters))
        {
            $GLOBALS['content']->content_alters[]=array('class'=>'CTop','handler'=>'build_rate_container');
        }
        
        $this->sawanna->tpl->add_header('<script language="JavaScript">top_rating_str=\'[str]Rating[/str]\';top_voted_str=\'[str]Voted[/str]\';</script>');
        
	}
	
	function count_visited_path($path) {
		$this->sawanna->db->query("select * from pre_top_visits where path='$1' and language='$2'",$path,$this->sawanna->locale->current);
		$row=$this->sawanna->db->fetch_object();
		
		if (!empty($row)) {
			$this->sawanna->db->query("update pre_top_visits set co=co+1 where id=$1",$row->id);
		} else {
			$this->sawanna->db->query("insert into pre_top_visits(path,language,co) values ('$1','$2','1')",$path,$this->sawanna->locale->current);
		}
	}
    
    function most_visited() {
		if (!$this->counting) return;
		
		$d=new CData();
		
		foreach ($this->top_visited_exclude_paths as $key=>$val) {
			$this->top_visited_exclude_paths[$key]=$this->sawanna->db->escape_string($val);
		}
		
		$paths=array();
		$cos=array();
		$this->sawanna->db->query("select * from pre_top_visits where language='$1' and path not in ('".implode("','",$this->top_visited_exclude_paths)."') and path not like 'user/%' order by co desc limit $2",$this->sawanna->locale->current,$this->most_visited_limit);
		while($row=$this->sawanna->db->fetch_object()) {
			$paths[]=$this->sawanna->db->escape_string($row->path);
			$cos[$row->path]=(int)$row->co;
		}
		
		$nodes=array();
		$this->sawanna->db->query("select p.nid,p.path,n.meta from pre_node_pathes p inner join pre_nodes n on n.id=p.nid where p.path in ('".implode("','",$paths)."') and n.language='$1' and n.id is not null order by n.sticky desc, n.weight asc, n.created desc",$this->sawanna->locale->current);
		while($row=$this->sawanna->db->fetch_object()) {
			$row->meta=$d->do_unserialize($row->meta);
			if (!empty($row->meta['title']))
				$nodes[$row->path]=$row->meta['title'];
		}
		
		$_html='';
		foreach ($paths as $path) {
			$title=urldecode($this->sawanna->constructor->url($path,false,false,true));
		
			$menu=$this->menu->get_element_row_by_path($path);
			if (!empty($menu)) {
				$title=$menu->title;
			}
		
			if (array_key_exists($path,$nodes)) $title=$nodes[$path];
			
			$_html.='<li><span class="top_item_title">'.$this->sawanna->constructor->link($path,$title).'</span><span class="top_item_desc">('.$cos[$path].')</span></li>';
		}
		
		if (empty($_html)) return;
		
		$html='<ul class="top_most_visited">'.$_html.'</ul>';
		
		return array(
			'title'=>'[str]Most visited[/str]',
			'content'=>$html
		);
	}
	
	function top_rated() {
		$d=new CData();
		
		$nids=array();
		$cos=array();
		$this->sawanna->db->query("select nid,avg(rate) as co from pre_top_rates group by nid order by co desc limit $1",$this->top_rated_limit);
		while($row=$this->sawanna->db->fetch_object()) {
			$nids[]=$this->sawanna->db->escape_string($row->nid);
			$cos[$row->nid]=number_format($row->co,1);
		}
		
		$nodes=array();
		$node_pathes=array();
		$this->sawanna->db->query("select p.nid,p.path,n.meta from pre_nodes n left join pre_node_pathes p on n.id=p.nid where n.id in ('".implode("','",$nids)."')");
		while($row=$this->sawanna->db->fetch_object()) {
			$row->meta=$d->do_unserialize($row->meta);
			if (!empty($row->meta['title']))
				$nodes[$row->nid]=$row->meta['title'];
				
			$node_pathes[$row->nid]=$row->path;
		}
		
		$_html='';
		foreach ($nids as $nid) {
			$path=(array_key_exists($nid,$node_pathes) && !empty($node_pathes[$nid])) ? $node_pathes[$nid] : 'node/'.$nid;
			
			$title=urldecode($this->sawanna->constructor->url($path,false,false,true));
		
			if (array_key_exists($nid,$nodes)) $title=$nodes[$nid];
			
			$_html.='<li><span class="top_item_title">'.$this->sawanna->constructor->link($path,$title).'</span><span class="top_item_desc">('.$cos[$nid].')</span></li>';
		}
		
		if (empty($_html)) return;
		
		$html='<ul class="top_most_rated">'.$_html.'</ul>';
		
		return array(
			'title'=>'[str]Top rated[/str]',
			'content'=>$html
		);
	}
	
	function build_rate_container(&$content,$nid) {
		//if (!$this->sawanna->has_access('rate')) return;
		
		$content.='<div id="rate-nid-'.$nid.'" class="rate-container"></div>';
	}
	
	function get_node_rate($nid) {
		$this->sawanna->db->query("select avg(rate) as rate from pre_top_rates where nid='$1'",$nid);
		$row=$this->sawanna->db->fetch_object();
		
		return $row ? $row->rate : 0;
	}
	
	function get_node_rates_count($nid) {
		$this->sawanna->db->query("select count(*) as co from pre_top_rates where nid='$1'",$nid);
		$row=$this->sawanna->db->fetch_object();
		
		return $row ? $row->co : 0;
	}
	
	function user_is_rated($uid,$nid) {
		if ($uid<=0) return false;
		
		$this->sawanna->db->query("select * from pre_top_rates where uid='$1' and nid='$2'",$uid,$nid);
		$row=$this->sawanna->db->fetch_object();
		
		return $row ? true : false;
	}
	
	function set_user_rate($uid,$nid,$rate) {
		if (!$this->sawanna->has_access('rate')) return;
		
		$rate=floor($rate);
		if ($rate<1) $rate=1;
		if ($rate>5) $rate=5;
		
		if ($this->user_is_rated($uid,$nid)) {
			$this->sawanna->db->query("update pre_top_rates set rate='$1' where uid='$2' and nid='$3'",$rate,$uid,$nid);
		} else {
			$this->sawanna->db->query("insert into pre_top_rates (uid,nid,rate) values ('$1','$2','$3')",$uid,$nid,$rate);
		}
	}
	
	function vote() {

		if (empty($_POST['nid']) || !is_numeric($_POST['nid'])) die();
		
		if (!empty($_POST['rate']) && is_numeric($_POST['rate']) && $this->sawanna->has_access('rate')) {
			$uid=$this->user->current->id;
			if ($uid>0 || empty($_COOKIE['top_rate_'.((int)$_POST['nid']).'_voted'])) {
				$this->set_user_rate($uid,$_POST['nid'],floor($_POST['rate']));
			}
			if ($uid<=0) {
				setcookie('top_rate_'.((int)$_POST['nid']).'_voted','1',time()+3600*24*365,'/');
			}
		}
		
		$rate=number_format($this->get_node_rate($_POST['nid']),1);
		$total=$this->get_node_rates_count($_POST['nid']);
		$allow=$this->sawanna->has_access('rate');
		
		echo sawanna_json_encode(array('nid'=>$_POST['nid'],'rate'=>$rate,'total'=>$total,'allow'=>$allow));
		
		die();
	}
	
	function ajaxer(&$scripts,&$outputs)
    {
		$scripts.='window.setTimeout("rate_init();",150);'."\r\n";
	}
}
