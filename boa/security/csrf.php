<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.security.csrf.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\security;

use boa\base;
use boa\boa;
use boa\msg;

class csrf extends base{
	protected $cfg = [
		'key' => 'CSRF-TOKEN',
		'type' => 1, //0=close 1=client 2=server 3=both
		'expire' => 0,
		'delete' => true
	];
	private $obj;

	public function create(){
		$token = null;
		
		if($this->cfg['type'] > 0){
			$token = $this->generate($_SERVER['REQUEST_TIME']);

			if($this->cfg['type'] == 1){
				$this->obj()->set($this->cfg['key'], $token, 0, true);
			}else if($this->cfg['type'] > 1){
				$this->obj()->set($this->cfg['key'], $token);
			}
		}

        return $token;
    }

    public function check(){
		if($this->cfg['type'] <= 0 || strpos(PHP_SAPI, 'cli') !== false){
			return true;
		}

		$res = false;
		$token = $this->obj()->get($this->cfg['key']);

		if($token){
			$time = substr($token, 0, 10);
			$diff = time() - $time;
			if($this->cfg['expire'] == 0 || $diff <= $this->cfg['expire']){
				$_token = $this->generate($time);
				if($_token == $token){
					$res = true;
				}

				if($this->cfg['type'] == 3){
					$_token = $_POST[$this->cfg['key']] ? $_POST[$this->cfg['key']] : $_SERVER['X-CSRF-TOKEN'];
					if($_token == $token){
						$res = true;
					}else{
						$res = false;
					}
				}
			}
		}

		if($this->cfg['delete']){
			$this->obj()->del($this->cfg['key']);
		}
		return $res;
    }

	public function validate(){
		$res = $this->check();
		if(!$res){
			msg::set('boa.error.23', 'csrf');
		}
    }

	public function delete(){
		$this->obj()->del($this->cfg['key']);
    }

	private function generate($time){
		$agent = $_SERVER['HTTP_USER_AGENT'];
		$token = md5($agent . $time . SALT);
		$token = $time . substr($token, 10);
		return $token;
	}

	private function obj(){
		if(!$this->obj){
			if($this->cfg['type'] == 1){
				$this->obj = boa::cookie();
			}else if($this->cfg['type'] > 1){
				$this->obj = boa::session();
			}
		}
		return $this->obj;
	}
}
?>