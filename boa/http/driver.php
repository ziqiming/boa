<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.http.driver.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\http;

class driver{
	protected $cfg = [];
	protected $result = [];

	public function __construct($cfg){
        if($cfg){
			$this->cfg = array_merge($this->cfg, $cfg);
		}
	}

	public function cfg($k, $v){
		switch(true){
			case $k === null && $v === null:
				return $this->cfg;
				break;

			case $v === null:
				return $this->cfg[$k];
				break;

			default:
				$this->cfg[$k] = $v;
				return $this;
		}
	}

	public function get_posttype(){
		return $this->cfg['posttype'];
	}
	
	public function get_header(){
		return $this->result['head'];
	}
	
	public function get_body(){
		return $this->result['body'];
	}
	
	public function get_status(){
		return $this->result['code'];
	}
	
	public function get_error(){
		return $this->result['msg'];
	}
}
?>