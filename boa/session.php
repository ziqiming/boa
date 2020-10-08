<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.session.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa;

class session{
	private $cookie = [
		'lifetime' => 0,
		'path' => '/',
		'domain' => '',
		'secure' => false,
		'httponly' => true
	];
	private $obj;

	public function __construct($cfg = []){
		if(!$cfg['driver']){
			$cfg['driver'] = 'file';
		}

		if(defined('COOKIE')){
			$this->cookie = array_merge($this->cookie, unserialize(COOKIE));
		}

		if($cfg['expire'] > 0){
			$this->cookie['lifetime'] = $cfg['expire'];
		}

		session_set_cookie_params(
			$this->cookie['lifetime'],
			$this->cookie['path'],
			$this->cookie['domain'],
			$this->cookie['secure'],
			$this->cookie['httponly']
		);

		$driver = '\\boa\\session\\driver\\'. $cfg['driver'];
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

	public function __get($k){
		return $this->cfg($k);
	}

	public function __set($k, $v){
		$this->cfg($k, $v);
	}

	public function sid(){
		return session_id();
	}

	public function get($key){
		$arr = explode('.', $key);
		$val = $_SESSION[$arr[0]];
		$num = count($arr);
		for($i = 1; $i < $num; $i++){
			$val = $val[$arr[$i]];
		}
		return $val;
	}

	public function set($key, $val){
		$arr = explode('.', $key);
		$obj = &$_SESSION[$arr[0]];
		$num = count($arr);
		for($i = 1; $i < $num; $i++){
			$obj = &$obj[$arr[$i]];
		}
		$obj = $val;
	}

	public function del($key){
		$arr = explode('.', $key);
		$obj = &$_SESSION;
		$num = count($arr);
		for($i = 0; $i < $num - 1; $i++){
			$obj = &$obj[$arr[$i]];
		}
		unset($obj[$arr[$num -1]]);
	}

	public function gc(){
		if(function_exists('session_gc')){
			return session_gc();
		}else{
			return false;
		}
	}

	public function save(){
		return session_write_close();
	}

	public function clear(){
		unset($_SESSION);
	    return session_destroy();
	}
}