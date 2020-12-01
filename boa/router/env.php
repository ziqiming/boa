<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.router.env.html
License : Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\router;

use boa\boa;
use boa\msg;

class env{
	private $cfg = [];
	private $env = [];
	private $router = [];
	private $request = [];
	private $param_var = '/^(\w+?)$/';

	public function __construct($cfg){
        $this->cfg = $cfg;

		if($this->cfg['enable']){
			$router = boa::cache()->get('router');
			$this->router = $router;
		}

		$arr = [
			'method' => $_SERVER['REQUEST_METHOD'],
			'scheme' => $_SERVER['REQUEST_SCHEME'],
			'host'   => $_SERVER['HTTP_HOST']
		];
		switch($this->cfg['type']){
			case 0:
				$arr['path'] = substr($_SERVER['REQUEST_URI'], 1);
				break;

			case 1:
				$arr['path'] = substr($_SERVER['PATH_INFO'], 1);
				break;

			case 2:
				$arr['path'] = substr($_SERVER['REQUEST_URI'], 1);
				break;
		}
		$arr['path'] = preg_replace('/\?(.+?)$/', '', $arr['path']);
		$this->request = $arr;
	}

	public function get(){
		if(!$_SERVER['QUERY_STRING']){
			if($this->request['path'] == '' || $this->request['path'] == 'index.php'){
				return [];
			}
		}

		if($this->cfg['enable'] && $this->request['path']){
			foreach($this->router as $group => $rules){
				$arr = parse_url($group);
				$host_vars = [];
				$path_vars = [];

				if($arr['scheme'] && $arr['scheme'] != $this->request['scheme']){
					continue;
				}

				if($arr['host']){
					$host_vars = $this->match($arr['host'], $this->request['host']);
					if($host_vars === false){
						continue;
					}
				}

				if($arr['path']){
					$path_vars = $this->match($arr['path'], $this->request['path']);
					if($path_vars === false){
						continue;
					}
				}

				$vars = array_merge($host_vars, $path_vars);

				foreach($rules as $k => $rule){
					if($rule['method'] && $rule['method'] != $this->request['method']){
						continue;
					}

					if($rule['scheme'] && $rule['scheme'] != $this->request['scheme']){
						continue;
					}
					
					if($vars){
						$rule['url'] = $this->restore($vars, $rule['url']);
					}

					$res = preg_match('/^'. $rule['url'] .'$/', $this->request['path'], $paras);
					if($res){
						if($vars){
							$rule['act'] = $this->restore($vars, $rule['act']);
						}
						$acts = explode('.', $rule['act']);
						$this->env['act'] = $acts[2];
						$this->env['con'] = $acts[1];
						$this->env['mod'] = $acts[0];

						array_shift($paras);
						foreach($paras as $i => $para){
							$key = $rule['var'][$i];
							$this->env['var'][$key] = $para;
						}

						boa::log()->set('info', "Router matched [$k] => [". $rule['act'] .']');
						break;
					}
				}

				if($this->env){
					break;
				}
			}

			if($this->cfg['force'] && !$this->env){
				if(defined('DEBUG') && DEBUG){
					msg::set('boa.error.31');
				}else{
					boa::view()->lost();
				}
			}
		}

		if(!$this->env){
			$arr = [];
			if($this->cfg['type'] > 0 && $this->request['path']){
				$top_arr = explode('/', $this->request['path']);
				$max = count($top_arr);

				$arr[$this->cfg['mod']] = $top_arr[0];
				if($max > 1){
					$arr[$this->cfg['con']] = $top_arr[1];
					if($max > 2){
						$arr[$this->cfg['act']] = $top_arr[2];
						if($max > 3){
							$step = $this->cfg['delimiter'] == '/' ? 2 : 1;
							for($i = 3; $i < $max; $i = $i + $step){
								if($step == 2){
									$k = $top_arr[$i];
									$v = $top_arr[$i + 1];
									if(preg_match($this->param_var, $k)){
										$arr[$k] = urldecode($v);
									}
								}else{
									if($top_arr[$i]){
										$sub_arr = explode($this->cfg['delimiter'], $top_arr[$i], 2);
										$k = $sub_arr[0];
										$v = $sub_arr[1];
										if(preg_match($this->param_var, $k)){
											$arr[$k] = urldecode($v);
										}
									}
								}
							}
						}
					}
				}
			}

			if($_SERVER['QUERY_STRING']){
				parse_str($_SERVER['QUERY_STRING'], $arr_param);
				foreach($arr_param as $k => $v){
					$arr[$k] = urldecode($v);
				}
			}
			$this->parse_env($arr);

			if(!$this->env['mod'] && !$this->env['con'] && !$this->env['act']){
				if(defined('DEBUG') && DEBUG){
					msg::set('boa.error.32');
				}else{
					boa::view()->lost();
				}
			}
		}

		return $this->env;
	}

	private function match($rule_str, $str){
		$res = [];
		$rule_str = preg_quote($rule_str, '/');
		if(strpos($rule_str, '%') !== false){
			preg_match_all('/%(\d)/', $rule_str, $keys);
			$rule_str = preg_replace('/%\d/', $this->cfg['url_word'], $rule_str);
			$matched = preg_match("/^$rule_str/", $str, $res);
			if($matched){
				array_shift($res);
				$res = array_combine($keys[1], $res);
			}
		}else{
			$matched = preg_match("/^$rule_str/", $str);
			if(!$matched){
				$res = false;
			}
		}
		return $res;
	}

	private function restore($vars, $str){
		foreach($vars as $k => $var){
			$str = str_replace("%$k", $var, $str);
		}
		return $str;
	}

	private function parse_env($arr){
		foreach($arr as $k => $v){
			switch($k){
				case $this->cfg['mod']:
					if($v){
						$this->env['mod'] = $v;
					}
					break;

				case $this->cfg['con']:
					if($v){
						$this->env['con'] = $v;
					}
					break;

				case $this->cfg['act']:
					if($v){
						$this->env['act'] = $v;
					}
					break;

				default:
					$this->env['var'][$k] = $v;
			}
		}
	}
}
?>
