<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.cache.cacher.permission.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\cache\cacher;

use boa\msg;
use boa\cache\cacher;

class permission implements cacher{
	private $perms = [];

	public function __construct($args){
		$group = $args['group'] ? '-'. $args['group'] : '';
		$file = BS_VAR ."config/perm$group.php";
		if(file_exists($file)){
			$this->perms = include($file);
		}else{
			msg::set('boa.error.2', $file);
		}
	}

	public function get(){
		if($this->perms['allow']){
			$this->perm('allow');
		}

		if($this->perms['deny']){
			$this->perm('deny');
		}

		return $this->perms;
	}

	private function perm($type){
		foreach($this->perms[$type] as $k => $v){
			$this->perms[$type][$k] = $this->act($v);
		}
	}

	private function act($act){
		$num = 2 - substr_count($act, '.');
		if($num > 0){
			$act .= str_repeat('.*', $num);
		}
		return $act;
	}
}
?>