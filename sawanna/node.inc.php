<?php
defined('SAWANNA') or die();

class CNode
{
    var $db;
    var $access;
    var $navigator;
    var $locale;
    var $path;
    
    function CNode(&$db,&$access,&$navigator,&$locale,$path)
    {
        $this->db=$db;
        $this->access=$access;
        $this->navigator=$navigator;
        $this->locale=$locale;
        $this->path=$path;
    }
    
    function set($node)
    {
        if (empty($node) || !is_array($node)) return false;
       
        if (!isset($node['content'])) $node['content']='';
        if (!isset($node['permission'])) $node['permission']='';
        if (!isset($node['type'])) $node['type']='';
        if (!isset($node['module'])) $node['module']=0;
        if (!isset($node['front'])) $node['front']=0;
        if (!isset($node['sticky'])) $node['sticky']=0;
        if (!isset($node['weight'])) $node['weight']=0;
        if (!isset($node['enabled'])) $node['enabled']=0;
        if (!isset($node['meta'])) $node['meta']='';
        if (!isset($node['pub_tstamp'])) $node['pub_tstamp']=time();
        if (!isset($node['language'])) $node['language']=$this->locale->current;
        
        $d=new CData();
        
        if (isset($node['id']))
        {
            if (!$this->db->query("update pre_nodes set content='$1', permission='$2', type='$3', module='$4', front='$5', sticky='$6', weight='$7', enabled='$8', meta='$9', language='$10', pub_tstamp='$11' where id='$12'",$node['content'],$node['permission'],$node['type'],$node['module'],$node['front'],$node['sticky'],$node['weight'],$node['enabled'],$d->do_serialize($node['meta']),$node['language'],$node['pub_tstamp'],$node['id']))
                return false;
        } else 
        {
             if (!$this->db->query("insert into pre_nodes (content,permission,type,module,front,sticky,weight,enabled,meta,created,language,pub_tstamp) values ('$1','$2','$3','$4','$5','$6','$7','$8','$9','$10','$11','$12')",$node['content'],$node['permission'],$node['type'],$node['module'],$node['front'],$node['sticky'],$node['weight'],$node['enabled'],$d->do_serialize($node['meta']),time(),$node['language'],$node['pub_tstamp']))
                return false;
        }
        
        return true;
    }
    
    function delete($id='',$module='')
    {
        if (empty($id) && empty($module)) return false;
        
        if (!empty($id))
        {
            if (!$this->db->query("delete from pre_nodes where id='$1'",$id))
                return false;
        } elseif (!empty($module))
        {
            if (!$this->db->query("delete from pre_nodes where module='$1'",$module))
                return false;
        }
        
        return true;
    }
    
    function enable($id='')
    {
        if (empty($id)) return false;
        
        if (!$this->db->query("update pre_nodes set enabled='1' where id='$1'",$id))
        return false;
        
        return true;
    }
    
    function disable($id='')
    {
        if (empty($id)) return false;
        
        if (!$this->db->query("update pre_nodes set enabled='0' where id='$1'",$id))
            return false;
                
        return true;
    }
    
    function stick($id='')
    {
        if (empty($id)) return false;
        
        if (!$this->db->query("update pre_nodes set sticky='1' where id='$1'",$id))
        return false;
        
        return true;
    }
    
    function unstick($id='')
    {
        if (empty($id)) return false;
        
        if (!$this->db->query("update pre_nodes set sticky='0' where id='$1'",$id))
            return false;
                
        return true;
    }
    
    function front($id='')
    {
        if (empty($id)) return false;
        
        if (!$this->db->query("update pre_nodes set front='1' where id='$1'",$id))
        return false;
        
        return true;
    }
    
    function unfront($id='')
    {
        if (empty($id)) return false;
        
        if (!$this->db->query("update pre_nodes set front='0' where id='$1'",$id))
            return false;
                
        return true;
    }
    
    function get($id='')
    {
        if (empty($language)) $language=$this->locale->current;

        $this->db->query("select * from pre_nodes where id='$1' and pub_tstamp < '$2' and enabled<>'0'",$id,time());
        $row=$this->db->fetch_object();
        
        if (!empty($row))
        {
            $d=new CData();
            if (!empty($row->meta) && $d->is_serialized($row->meta)) $row->meta=$d->do_unserialize($row->meta);
            else $row->meta=array();
            
            if ($this->access->has_access($row->permission)) return $row;
        }
    }
    
    function get_existing_node($id='')
    {
        $this->db->query("select * from pre_nodes where id='$1'",$id);
        $row=$this->db->fetch_object();
        
        if (!empty($row))
        {
            return $row;
        } else return false;
    }
    
    function node_exists($id)
    {
        if (empty($id)) return false;
        
        $this->db->query("select * from pre_nodes where id='$1'",$id);
        $row=$this->db->fetch_object();
        
        if (!empty($row)) return true;
        
        return false;
    }
    
    function get_all_nodes_count($language='')
    {
        if (empty($language)) $language=$this->locale->current;
        
        if ($language!='all')
            $this->db->query("select count(*) as co from pre_nodes where language='$1'",$language);
        else 
            $this->db->query("select count(*) as co from pre_nodes");
            
        $row=$this->db->fetch_object();
        
        if (!empty($row)) return $row->co;
        return 0;
    }
    
    function get_all_nodes($language='',$limit=10,$offset=0)
    {
        if (empty($language)) $language=$this->locale->current;
        
        if ($language!='all')
            $this->db->query("select * from pre_nodes where language='$1' order by created desc limit $2 offset $3",$language,$limit,$offset);
        else 
            $this->db->query("select * from pre_nodes order by created desc limit $1 offset $2",$limit,$offset);
    }
    
    function schema()
    {
        $table=array(
            'name' => 'pre_nodes',
            'fields' => array(
                'id' => array(
                    'type' => 'id',
                    'length' => 10,
                    'not null' => true
                ),
                'content' => array(
                    'type' => 'longtext',
                    'not null' => true
                ),
                'permission' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => true
                ),
                'type' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => true
                ),
                'module' => array(
                    'type' => 'integer',
                    'length' => 4,
                    'not null' => false
                ),
                'front' => array(
                    'type' => 'integer',
                    'length' => 4,
                    'not null' => true
                ),
                'sticky' => array(
                    'type' => 'integer',
                    'length' => 4,
                    'not null' => true
                ),
                'weight' => array(
                    'type' => 'integer',
                    'length' => 4,
                    'not null' => true
                ),
                'enabled' => array(
                    'type' => 'integer',
                    'length' => 4,
                    'not null' => true
                ),
                'meta' => array(
                    'type' => 'longtext',
                    'not null' => true
                ),
                'created' => array(
                    'type' => 'integer',
                    'length' => 10,
                    'not null' => true
                ),
               'pub_tstamp' => array(
                    'type' => 'integer',
                    'length' => 10,
                    'not null' => true
                ),
                'language' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => true
                ),
                'mark' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => false
                )
            ),
            'primary key' => 'id',
            'index' => array('front','sticky','enabled')
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
        if(!$db->query("DROP TABLE IF EXISTS pre_nodes")) return false;
        
        return true;
    }
}
