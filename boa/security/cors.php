<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.security.cors.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\security;

use boa\base;
use boa\msg;

class cors extends base{
	protected $cfg = [
		'origin' => '', //separated by comma(,)
		'credentials' => true,
		'headers' => '',
		'methods' => 'GET,POST'
	];

	public function check(){
		if($_SERVER['HTTP_ORIGIN'] && $this->cfg['origin']){
			$origin = $this->get_origin();
			if($origin){
				header('Access-Control-Allow-Origin: '. $origin);
				if($this->cfg['credentials']){
					header('Access-Control-Allow-Credentials: true');
				}
				if($this->cfg['headers']){
					header('Access-Control-Allow-Headers: '. $this->cfg['headers']);
				}
				if($this->cfg['methods']){
					header('Access-Control-Allow-Methods: '. $this->cfg['methods']);
				}
			}
		}
	}

	private function get_origin(){
		$origin = str_replace(' ', '', $this->cfg['origin']);
		if($origin == '*'){
			return '*';
		}else{
			$reg = preg_quote($_SERVER['HTTP_ORIGIN'], '/');
			$res = preg_match("/(^|,)$reg(,|$)/i", $origin);
			if($res){
				return $_SERVER['HTTP_ORIGIN'];
			}else{
				msg::set('boa.error.22', $_SERVER['HTTP_ORIGIN']);
			}
		}
	}
}
?>