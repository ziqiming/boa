<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.mq.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa;

class mq{
	private $obj;

	public function __construct($cfg = []){
		if(!$cfg['driver']){
			$cfg['driver'] = 'stomp';
		}

		$driver = '\\boa\\mq\\driver\\'. $cfg['driver'];
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

	public function publish($msg){
		if(!is_scalar($msg)){
			$msg = chr(8) . serialize($msg);
		}

		return $this->obj->publish($msg);
	}

	public function subscribe($queue = null){
		return $this->obj->subscribe($queue);
	}

	public function read(){
		$res = $this->obj->read();

		if(substr($res, 0, 1) === chr(8)){
			$res = unserialize(substr($res, 1));
		}

		return $res;
	}

	public function ack(){
		return $this->obj->ack();
	}

	public function unsubscribe($queue = null){
		return $this->obj->unsubscribe($queue);
	}

	public function __call($method, $args = []){
		if(method_exists($this->obj, $method)){
			return call_user_func_array(array($this->obj, $method), $args);
		}else{
			msg::set('boa.error.4', $method);
		}
    }
}