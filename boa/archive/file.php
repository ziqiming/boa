<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.archive.file.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\archive;

use boa\boa;
use boa\msg;

class file{
	private $obj;

	public function __construct($cfg = []){
		if(!$cfg['driver']){
			$cfg['driver'] = 'zip';
		}

		$driver = '\\boa\\archive\\driver\\'. $cfg['driver'];
		$this->obj = new $driver($cfg);
	}

	public function cfg($k = null, $v = null){
		$res = $this->obj->cfg($k, $v);
		if($v !== null){
			return $this;
		}else{
			return $res;
		}
	}

	public function compress($source, $dest){
		if(!file_exists($source)){
			msg::set('boa.error.2', $source);
		}

		$source = $this->format($source, true);
		$dest = $this->format($dest);
		$res = $this->obj->compress($source, $dest);
		$this->close();

		if($res === false){
			msg::set('boa.error.153', $dest);
		}else{
			$res = true;
		}
        return $res;
    }

	public function decompress($source, $dest){
		if(!file_exists($source)){
			msg::set('boa.error.2', $source);
		}

		$source = $this->format($source);
		$dest = $this->format($dest, true);
		$res = $this->obj->decompress($source, $dest);
		$this->close();

		if($res === false){
			msg::set('boa.error.154', $source);
		}else{
			$res = true;
		}
        return $res;
    }

	public function open($file, $flag = null){
		$this->obj->open($file, $flag);
		return $this;
    }

	public function entries(){
		$res = $this->obj->entries();
		if(!$res){
			$res = [];
		}
		return $res;
    }

	public function comment(){
		$res = $this->obj->comment();
		if(!$res){
			$res = '';
		}
		return $res;
    }

	public function close(){
		$this->obj->close();
    }

	private function format($path, $isdir = false){
		$path = str_replace('\\', '/', $path);
		if($isdir){
			$path = rtrim($path, '/');
		}
		return $path;
	}
}
?>