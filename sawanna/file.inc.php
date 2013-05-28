<?php
defined('SAWANNA') or die();

class CFile
{
    var $db;
    var $root_dir;
    var $last_name;
    var $md5file;
    var $files; // caching db results
    
    function CFile(&$db)
    {
        $this->db=$db;
        $this->root_dir='uploads';
        $this->last_name='';
        $this->md5file=ROOT_DIR.'/'.quotemeta($this->root_dir).'/md5sum.txt';
        $this->files=array();
    }
    
    function generate_name($original)
    {
        if (empty($original)) return false;
        
        return md5($original.random_chars(12).PRIVATE_KEY);
    }
    
    function save($file=0,$alias='',$path='',$meta='')
    {
        if (empty($path) && (!file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir)) || !is_dir(ROOT_DIR.'/'.quotemeta($this->root_dir)) || !is_writable(ROOT_DIR.'/'.quotemeta($this->root_dir)))) return false;
        if (!empty($path) && (!file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($path)) || !is_dir(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($path)) || !is_writable(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($path)))) return false;
        
        if (!empty($meta) && is_array($meta))
        {
            $d=new CData();
            $meta=$d->do_serialize($meta);
        }
        
        if (is_array($file))
        {
            if (!isset($file['tmp_name']) || !file_exists($file['tmp_name']) || !isset($file['name']) || !isset($file['type']) || !isset($file['size'])) return false;

            do 
                $name=$this->generate_name($file['name']);
            while (!$this->db->query("insert into pre_files (name,original,path,type,size,created,meta,nicename) values ('$1','$2','$3','$4','$5','$6','$7','$8')",$name,$file['name'],trim($path,'/'),$file['type'],$file['size'],time(),$meta,$alias));
            
            $this->last_name=$name;
            
            if (!empty($path))
                return copy($file['tmp_name'],ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($path).'/'.$name);
           else 
                return copy($file['tmp_name'],ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.$name);
        } elseif (!empty($file) && is_numeric($file)) 
        {
            $ex=$this->get($file);
            if (!$ex) return false;
            
            if ($ex->path!=$path)
            {
                 if (!empty($ex->path))
                 {
                     if (!empty($path) && !copy(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($ex->path).'/'.$ex->name,ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($path).'/'.$ex->name))
                         return false;
                     elseif (empty($path) && !copy(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($ex->path).'/'.$ex->name,ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.$ex->name)) 
                         return false;
                         
                     unlink(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($ex->path).'/'.$ex->name);
                 } else 
                 {
                     if (!empty($path) && !copy(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.$ex->name,ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($path).'/'.$ex->name))
                         return false;
                     elseif (empty($path) && !copy(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.$ex->name,ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.$ex->name)) 
                         return false;
                         
                     unlink(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.$ex->name);
                 }
            }
            
            return $this->db->query("update pre_files set path='$1',meta='$2',nicename='$3' where id='$4'",trim($path,'/'),$meta,$alias,$file);
        } else return false;
        
        return true;
    }
    
    function fix_alias(&$alias)
    {
        static $suffix='';
        static $co=1;
        
        if (is_numeric($alias)) $alias='image-'.$alias;
        
        if ($this->get($alias.$suffix))
        {
            $suffix=++$co;
            $this->fix_alias($alias);
            return;
        }
        
        $alias.=$suffix;
    }
    
    function delete($id)
    {
        if (empty($id)) return false;
        $file=$this->get($id);
        if (!$file) return false;
        
        $this->db->query("delete from pre_files where id='$1'",$id);
        
        if (empty($file->path) && !unlink(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.$file->name))
         return false;
        elseif (!empty($file->path) && !unlink(ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($file->path).'/'.$file->name)) 
         return false;
             
         return true;
    }
    
    function update_meta($meta,$id)
    {
        if (empty($id) || !is_numeric($id)) return false;
        if (!is_array($meta) || empty($meta)) return false;
        
        $d=new CData();
        $meta=$d->do_serialize($meta);

        return $this->db->query("update pre_files set meta='$1' where id='$2'",$meta,$id);
    }
    
    function get($id)
    {
        if (empty($id)) return false;
        
        if (array_key_exists($id,$this->files)) return $this->files[$id];
        
        if (is_numeric($id))
            $this->db->query("select * from pre_files where id='$1'",$id);
        else 
            $this->db->query("select * from pre_files where nicename='$1' order by id desc",$id);
            
        $row=$this->db->fetch_object();
        
        $this->files[$id]=!empty($row) ? $row : false;
        
        if (empty($row)) return false;
        
        return $row;
    }
    
    function preload($files,$nicenames=false)
    {
        if (empty($files)) return false;
        
        $in='';
        
        if (is_array($files))
        {
            foreach($files as $file)
            {
                if (!empty($in)) $in.=', ';
                $in.="'".$this->db->escape_string($file)."'";
                
                $this->files[$file]=false;
            }
        } elseif (is_string($files))
        {
            $in.="'".$this->db->escape_string($files)."'";
            
            $this->files[$files]=false;
        } else
        {
            return false;
        }
        
        if (!$nicenames)
            $this->db->query("select * from pre_files where id in ( ".$in." )");
        else
            $this->db->query("select * from pre_files where nicename in ( ".$in." )  order by id desc");
            
        while($row=$this->db->fetch_object())
        {
            if (!$nicenames)
                $this->files[$row->id]=!empty($row) ? $row : false;
            else
                $this->files[$row->nicename]=!empty($row) ? $row : false;
        }
        
        return true;
    }
    
    function get_id_by_name($name)
    {
        $this->db->query("select * from pre_files where name='$1'",$name);
            
        $row=$this->db->fetch_object();
        
        if (empty($row)) return false;
        
        return $row->id;
    }
    
    function get_meta($id)
    {
        if (empty($id)) return false;
        
        if (is_numeric($id))
            $this->db->query("select meta from pre_files where id='$1'",$id);
        else 
            $this->db->query("select meta from pre_files where nicename='$1' order by id desc",$id);
            
        $row=$this->db->fetch_object();
        
        if (empty($row)) return false;
        
        $d=new CData();
        $meta=$d->do_unserialize($row->meta);
        
        return $meta;
    }
    
    function fetch_file($id)
    {
        if (empty($id)) return false;
        
        $file=$this->get($id);
        if (!$file) return false;
        
        $file_path=!empty($file->path) ? ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.quotemeta($file->path).'/'.$file->name : ROOT_DIR.'/'.quotemeta($this->root_dir).'/'.$file->name;
        if (!file_exists($file_path)) return false;
        
        $f=fopen($file_path,"rb");
        if (!$f) return false;
        
        ob_end_clean();
        
        if (empty($file->type)) $file->type='application/force-download';
        
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: public");
        
        header("Content-Description: File Transfer");
        header("Content-Type: $file->type");
        header("Content-Disposition: attachment; filename=\"".$file->original."\"");
        header("Content-Transfer-Encoding: binary");
        // header("Content-Length: " . $file->size); // there is some download problems in mozilla firefox when content-length is sent

        while(!feof($f)) {
            echo fread($f, 10000);
            flush();
            
            if (connection_status()!=0) 
            {
              fclose($f);
              die();
            }
        }
        fclose($f);
        
        return true;
    }
    
    function count($id)
    {
        $file=$this->get($id);
        if (!$file) return false;
        
        $d=new CData();

        $meta=array();
        if (!empty($file->meta))
            $meta=$d->do_unserialize($file->meta);
            
        if (isset($meta['downloads'])) $meta['downloads']++;
        else $meta['downloads']=1;
        
        $this->update_meta($meta,$file->id);
    }
    
    function get_sub_directories($root)
    {
        static $directories=array();
        
        if (!file_exists(ROOT_DIR.'/'.quotemeta($root))) return $directories;
        
        $dir=opendir(ROOT_DIR.'/'.quotemeta($root));
        if (!$dir) return $directories;
        
        while (($d=readdir($dir))!==false) {
			$d=mb_convert_encoding($d,'UTF-8');
            if (is_dir(ROOT_DIR.'/'.quotemeta($root).'/'.quotemeta($d)) && $d!='.' && $d!='..')
            {
                $d_key=preg_replace('/^'.preg_quote($this->root_dir).'\//','',$root.'/'.$d);
                $directories[$d_key]=$root.'/'.$d;
                $this->get_sub_directories($root.'/'.$d);
            }
        }
        
        closedir($dir);
        
        return $directories;
    }
    
    function get_md5sum(&$out,$path)
    {
        $dir=opendir($path);
        if (!$dir) return false;
        
        while (($file=readdir($dir))!==false) 
        {
            if ($file=='.' || $file=='..') continue;
            if (!is_readable($path.'/'.$file)) continue;
            
            if (is_dir($path.'/'.$file)) 
            {
                $this->get_md5sum($out,$path.'/'.$file);
            } else 
            {
                $out[$path.'/'.$file]=md5_file($path.'/'.$file);
            }
        }
        
        closedir($dir);
    }
    
    function md5sum($path)
    {
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir)) && is_dir(ROOT_DIR.'/'.quotemeta($this->root_dir)) && is_writable(ROOT_DIR.'/'.quotemeta($this->root_dir))) return false;
        if (!file_exists($path)) return false;
        
        if (file_exists($this->md5file)) chmod($this->md5file,0600);
        if (file_exists($this->md5file) && !is_writable($this->md5file)) return false;
        
        if (file_exists($this->md5file)) unlink($this->md5file);
        
        $out=fopen($this->md5file,'w');
        if (!$out) return false;

        $hashes=array();
        $this->get_md5sum($hashes,$path);
        
        if (empty($hashes)) return false;
        
        foreach ($hashes as $filename=>$hash)
        {
            fwrite($out,$hash.' '.$filename."\n");
        }
        
        fclose($out);
        
        chmod($this->md5file,0000);
        
        return true;
    }
    
    function check_md5sum($path,&$errors)
    {
        if (!file_exists(ROOT_DIR.'/'.quotemeta($this->root_dir)) && is_dir(ROOT_DIR.'/'.quotemeta($this->root_dir)) && is_writable(ROOT_DIR.'/'.quotemeta($this->root_dir))) return false;
        if (!file_exists($path)) return false;
        
        if (file_exists($this->md5file)) chmod($this->md5file,0400);
        if (!file_exists($this->md5file) || !is_readable($this->md5file)) return false;
        
        $fhashes=array();
        $out=fopen($this->md5file,'r');
        if (!$out) return false;
        
        while (!feof($out)) 
        {
            $fhash_str=fgets($out);
            if (strpos($fhash_str,' ')===false) continue;
            
            list($fhash,$fname)=explode(' ',trim($fhash_str));
            $fhashes[$fname]=$fhash;
        }
        
        fclose($out);
        
        chmod($this->md5file,0000);
        
        if (empty($fhashes)) return false;

        $hashes=array();
        $this->get_md5sum($hashes,$path);
        
        foreach ($hashes as $name=>$check)
        {
            if (!array_key_exists($name,$fhashes)) 
            {
                $errors[$name]=$check;
                continue;
            }
            
            if ($fhashes[$name]!=$check && $name!=$this->md5file)
            {
                $errors[$name]=$check;
            }
        }
        
        if (!empty($errors)) 
        {
            return false;
        }
        else return true;
    }
    
     function schema()
    {
        $table=array(
            'name' => 'pre_files',
            'fields' => array(
                'id' => array(
                    'type' => 'id',
                    'length' => 10,
                    'not null' => true
                ),
                'name' => array(
                    'type' => 'string',
                    'length' => 32,
                    'not null' => true
                ),
                'original' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => true
                ),
                'path' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => true
                ),
                'nicename' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => false
                ),
                'type' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => true
                ),
               'size' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => true
                ),
                'meta' => array(
                    'type' => 'longtext',
                    'not null' => false
                ),
                'created' => array(
                    'type' => 'integer',
                    'length' => 10,
                    'not null' => true
                ),
                'mark' => array(
                    'type' => 'string',
                    'length' => 255,
                    'not null' => false
                )
            ),
            'primary key' => 'id',
            'unique' => 'name'
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
        if (!$db->query("DROP TABLE IF EXISTS pre_files")) return false;
        
        return true;
    }
}
