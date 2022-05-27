<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.database.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa;

class database{
	private $cfg = [
		'driver' => 'pdo', //pdo, mysqli
		'prefix' => 'bs_',
		'charset' => 'utf8',
		'persist' => false,
		'option' => [],
		'type' => 'mysql',
		'host' => '127.0.0.1',
		'port' => 3306,
		'name' => '',
		'user' => null,
		'pass' => null,
		'hashtype' => 0, //0, 1
		'master' => [],
		'slave' => []
	];
	private $db, $dbw;
	private $builder;
	private $return = false;
	private $error = null;

	public function __construct($cfg = []){
        if($cfg){
			$this->cfg = array_merge($this->cfg, $cfg);
		}
	}

	public function set_return($val = true){
		$this->return = $val;
	}

	public function get_error(){
		$err = $this->error;
		$this->error = null;
		return $err;
	}

	public function execute(){
		$args = func_get_args();
		$sql = $this->sql($args);
		$res = $this->db(1)->execute($sql);
		boa::log()->set('info', $sql);
		if($res === false){
			return $this->error($this->db(1));
		}
		return intval($res);
	}

	public function query(){
		$args = func_get_args();
		$sql = $this->sql($args);
		$res = $this->db(0)->query($sql);
		boa::log()->set('info', $sql);
		if($res === false){
			return $this->error($this->db(0));
		}
		return $res;
	}

	public function one(){
		$args = func_get_args();
		$sql = $this->sql($args);
		$res = $this->db(0)->one($sql);
		boa::log()->set('info', $sql);
		if($res === false){
			return $this->error($this->db(0));
		}
		return $res;
	}

	public function lastid($name = null){
		return $this->db(1)->lastid($name);
	}

	public function page($pagesize = 10, $sql = null){
		$total = $this->db(0)->page($sql);
		$arr['total'] = intval($total);
		$arr['pagesize'] = $pagesize;
		$arr['pages'] = ceil($arr['total'] / $arr['pagesize']);
		$arr['current'] = intval(boa::env('var.page'));
		return $arr;
	}

	public function begin($return = true){
		$this->return = $return;
		return $this->db(1)->begin();
	}

	public function commit(){
		$this->return = false;
		return $this->db(1)->commit();
	}

	public function rollback(){
		$this->return = false;
		return $this->db(1)->rollback();
	}

	public function stmt_execute($sql){
		return $this->stmt(1, $sql);
	}

	public function stmt_query($sql){
		return $this->stmt(0, $sql);
	}

	public function table($table){
		$this->builder = new \boa\database\builder($this->cfg['prefix'] . $table);
		return $this;
	}

	public function __call($name, $args){
		if($this->builder){
			if(method_exists($this->builder, $name)){
				switch(true){
					case in_array($name, ['where', 'having', 'union', 'union_all']):
						$args = $this->sql($args);
						$this->builder->$name($args);
						break;

					case $name == 'join':
						$table = $this->cfg['prefix'] . $args[0];
						if($args[2]){
							$this->builder->$name($table, $args[1], $args[2]);
						}else{
							$this->builder->$name($table, $args[1]);
						}
						break;

					default:
						if(count($args) > 1){
							$args = implode(', ', $args);
						}else{
							$args = current($args);
						}
						$this->builder->$name($args);
				}
				return $this;
			}else{
				msg::set('boa.error.4', $name);
			}
		}else{
			msg::set('boa.error.103');
		}
	}

	public function select(){
		$res = $this->builder->select(0, $this->db(0));
		if($res === false){
			return $this->error($this->builder);
		}
		return $res;
	}

	public function find(){
		$this->builder->limit(1);
		$res = $this->builder->select(1, $this->db(0));
		if($res === false){
			return $this->error($this->builder);
		}
		return $res;
	}

	public function insert($data){
		$res = $this->builder->insert($data, $this->db(1));
		if($res === false){
			return $this->error($this->builder);
		}
		return $res;
	}

	public function update($data){
		$res = $this->builder->update($data, $this->db(1));
		if($res === false){
			return $this->error($this->builder);
		}
		return $res;
	}

	public function delete(){
		$res = $this->builder->delete($this->db(1));
		if($res === false){
			return $this->error($this->builder);
		}
		return $res;
	}

	private function stmt($type, $sql){
		$sql = $this->prefix($sql);
		return new \boa\database\stmt($sql, $this->db($type), $this->return);
	}

	private function sql($args){
		$sql = array_shift($args);
		$sql = $this->prefix($sql);
		if($args){
			foreach($args as $arg){
				if(is_array($arg)){
					foreach($arg as $v){
						$sql = preg_replace('/\?/', "'". addslashes($v) ."'", $sql, 1);
					}
				}else{
					$sql = preg_replace('/\?/', "'". addslashes($arg) ."'", $sql, 1);
				}
			}
		}
		return $sql;
	}

	private function prefix($sql){
		return str_replace('@bs_', $this->cfg['prefix'], $sql);
	}

	private function db($type){
		$driver = '\\boa\\database\\driver\\'. $this->cfg['driver'];
		if($this->cfg['master']){
			if($type){
				if(!$this->dbw){
					if($this->cfg['master']['host']){
						$arr = $this->config($this->cfg['master']);
					}else{
						$i = $this->hash('master');
						$arr = $this->config($this->cfg['master'][$i]);
					}
					$this->dbw = new $driver($arr);
				}
				return $this->dbw;
			}else{
				if(!$this->db){
					if($this->cfg['slave']['host']){
						$arr = $this->config($this->cfg['slave']);
					}else{
						$i = $this->hash('slave');
						$arr = $this->config($this->cfg['slave'][$i]);
					}
					$this->db = new $driver($arr);
				}
				return $this->db;
			}
		}else{
			if(!$this->db){
				$this->db = new $driver($this->cfg);
			}
			return $this->db;
		}
	}

	private function hash($type){
		if($this->hashtype == 0){
			$num = count($this->cfg[$type]);
			$i = $_SERVER['REMOTE_PORT'] % $num;
		}else{
			$num = count($this->cfg[$type]) - 1;
			$i = mt_rand(0, $num);
		}
		return $i;
	}

	private function config($arr){
		$cfg = ['type', 'charset', 'persist', 'option', 'host', 'port', 'name', 'user', 'pass'];
		foreach($cfg as $k => $v){
			if(!array_key_exists($k, $arr)){
				$arr[$k] = $this->cfg[$k];
			}
		}
		return $arr;
	}

	private function error($obj){
		$err = $obj->error();
		if($this->return){
			$this->error = $err;
		}else{
			msg::set('boa.error.102', $err);
		}
		return false;
	}
}
?>
