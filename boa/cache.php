<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.cache.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa;

class cache{
	private $obj;
	private $key;

	public function __construct($cfg = []){
		if(!$cfg['driver']){
			$cfg['driver'] = 'file';
		}

		$driver = '\\boa\\cache\\driver\\'. $cfg['driver'];
		$this->obj = new $driver($cfg);

		if($cfg['driver'] != 'file'){
			boa::cache_on();
		}
	}

	public function cfg($k = null, $v = null){
		$res = $this->obj->cfg($k, $v);
		if($v !== null){
			return $this;
		}else{
			return $res;
		}
	}

	public function key(){
		return $this->key;
	}

	public function real_get($name){
		return $this->obj->get($name);
	}

	public function real_set($name, $val){
		if($this->check($val)){
			$val = 0;
		}else if($val === true){
			$val = 1;
		}
		$res = $this->obj->set($name, $val);
		if($this->check($res)){
			return false;
		}else{
			$this->key = $name;
			return true;
		}
	}

	public function get($name, $args = []){
		$cname = $this->cname($name, $args);
		if(defined('DEBUG') && DEBUG){
			$res = $this->set($name, $cname, $args);
		}else{
			$res = $this->obj->get($cname);
			if($this->check($res)){
				$res = $this->set($name, $cname, $args);
			}
		}
		return $res;
	}

	private function set($name, $cname, $args){
		$val = $this->create($name, $args)->get();
		if($this->check($val)){
			$val = 0;
		}else if($val === true){
			$val = 1;
		}
		$res = $this->obj->set($cname, $val);
		if($this->check($res)){
			return false;
		}else{
			$this->key = $cname;
			return $val;
		}
	}

	public function del($name){
		return $this->obj->del($name);
	}

	public function clear(){
		$this->obj->clear();
	}

	private function check($v){
		if($v === false || $v === null){
			return true;
		}else{
			return false;
		}
	}

	private function cname($name, $args){
		if($args){
			ksort($args);
			$key = json_encode($args);
			$key = abs(crc32($key));
			$cname = "$name-$key";
		}else{
			$cname = $name;
		}
		return $cname;
	}

	private function create($name, $args){
		$arr = explode('.', $name, 2);
		if(count($arr) > 1){
			$mod = $arr[0];
			$name = $arr[1];
			$cls = "\\mod\\$mod\\cacher\\$name";
		}else{
			$cls = "\\boa\\cache\\cacher\\$name";
		}
		return new $cls($args);
	}
}