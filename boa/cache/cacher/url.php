<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.cache.cacher.url.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\cache\cacher;

use boa\boa;
use boa\msg;
use boa\cache\cacher;

class url implements cacher{
	private $router = [];
	private $param_var = '/\{(\w+?)\}/';
	private $param_val = '([^\/]+?)';
	private $default = 'index';
	
	public function __construct($args){
		if(defined('ROUTER')){
			$cfg = unserialize(ROUTER);
			if($cfg && $cfg['param_val']){
				$this->param_val = $cfg['param_val'];
				$this->default = $cfg['default'];
			}
		}

		$file = BS_VAR .'config/router.php';
		if(file_exists($file)){
			$this->router = include($file);
		}
	}

	public function get(){
		$res = [];

		foreach($this->router as $group => $rules){
			if(is_string($rules['url']) && is_string($rules['act'])){
				$act = $this->act($rules['act']);
				$arr = $this->url($group, $rules);
				$res[$act][] = $arr;
			}else{
				foreach($rules as $rule){
					if(is_array($rule)){
						$act = $this->act($rule['act']);
						$arr = $this->url($group, $rule);
						$res[$act][] = $arr;
					}
				}
			}
		}

		ksort($res);
		foreach($res as $act => $v){
			$res[$act] = $this->sort($v);
		}

		return $res;
	}

	private function url($k, $v){
		$res = [];
		$url = parse_url($k);
		if($url['host']){
			$v['url'] = ltrim($v['url'], '/');
			$scheme = $url['scheme'] ? $url['scheme'] .'://' : '//';
			$res['url'] = $scheme . $url['host'] .'/'. $v['url'];
		}else{
			$res['url'] = $v['url'];
		}

		preg_match_all($this->param_var, $v['url'], $arr);
		foreach($arr[1] as $arg){
			if($v['param'][$arg]){
				$res['param'][$arg] = $v['param'][$arg];
			}else{
				$res['param'][$arg] = $this->param_val;
			}
		}

		if($v['scheme']){
			$res['scheme'] = strtolower($v['scheme']);
		}

		if($v['method']){
			$res['method'] = strtoupper($v['method']);
		}

		return $res;
	}

	private function sort($arr){
		$num = [];
		$res = [];
		foreach($arr as $v){
			if(is_array($v['param'])){
				$count = count($v['param']);
			}else{
				$count = 1;
			}
			$key = $count * 100;
			if(array_key_exists($key, $res)){
				if($num[$count]){
					$key = $num[$count] - 1;
				}else{
					$key--;
				}
				$num[$count] = $key;
			}
			$res[$key] = $v;
		}
		krsort($res);
		return $res;
	}

	private function act($act){
		$num = 2 - substr_count($act, '.');
		if($num > 0){
			$act .= str_repeat('.'. $this->default, $num);
		}
		return $act;
	}
}
?>