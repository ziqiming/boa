<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.security.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa;

class security{
	private $cfg = [
		'cors' => [],
		'csrf' => [],
		'xss' => []
	];
	private $cors, $csrf, $xss;

	public function __construct($cfg = []){
		if($cfg){
			foreach($cfg as $k => $v){
				$this->cfg[$k] = array_merge($this->cfg[$k], $v);
			}
		}
	}

	public function __call($name, $args = []){
		if(!$this->$name){
			$cls = "\\boa\\security\\$name";
			$this->$name = new $cls($this->cfg[$name]);
		}
		return $this->$name;
	}
}
?>