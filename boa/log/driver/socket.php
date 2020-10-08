<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.log.driver.socket.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\log\driver;

use boa\boa;
use boa\log\driver;

class socket extends driver{
	protected $cfg = [
		'timeline' => false,
		'host' => 'http://127.0.0.1:8888'
    ];
	private $obj;

	public function __construct($cfg){
        parent::__construct($cfg);

		$this->obj = boa::http($cfg);
	}

	public function save($info, $log){
		$str  = str_repeat('=', 20) .' Time:'. $info['use_time'] .'s  Memory:'. $info['use_mem'] .'kb '. str_repeat('=', 20) ."\r\n";
		$str .= '['. $info['time'] .']'. $info['from'] .' '. $info['type'] .' '. $info['uri'] ."\r\n";

		$timeline = '';
		foreach($log as $v){
			$type = $v['type'];
			$msg = $v['msg'];

			if(!is_scalar($msg)){
				$msg = rtrim(print_r($msg, true));
			}

			if($this->cfg['timeline']){
				$timeline = $this->timeline($v['time']);
			}

			$str .= $timeline ."[$type] $msg\r\n";
		}

		$this->obj->post($this->cfg['host'], $str);
	}
}
?>