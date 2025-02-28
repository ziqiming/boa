<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/api/boa.database.builder.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\database;

use boa\boa;
use boa\msg;

class builder{
	private $select = 'SELECT %distinct%%field% FROM %table%%force%%join%%where%%group%%having%%order%%limit%%union%%lock%';
	private $insert = 'INSERT INTO %table%%fields% VALUES %values%';
	private $update = 'UPDATE %table% SET %fields%%where%';
	private $delete = 'DELETE FROM %table%%where%';
	private $data = [];
	private $getsql = false;
	private $error = null;

	public function __construct($table){
		$this->data['table'] = $table;
	}

	public function distinct($field){
		$arr = preg_split('/(\s+AS\s+|\s+)/i', $field, 2);
		$distinct = 'distinct('. $arr[0] .')';
		if($arr[1]){
			$distinct .= ' AS '. $arr[1];
		}
		$this->data['distinct'] = $distinct;
	}

	public function field($field){
		$this->data['field'] = $field;
	}

	public function force($index){
		$this->data['force'] = " FORCE INDEX($index)";
	}

	public function join($table, $on, $type = 'LEFT'){
		$this->data['join'] .= " $type JOIN $table ON $on";
	}

	public function where($where){
		$this->data['where'] = " WHERE $where";
	}

	public function group($field){
		$this->data['group'] = " GROUP BY $field";
	}

	public function having($where){
		$this->data['having'] = " HAVING $where";
	}

	public function order($order){
		$this->data['order'] = " ORDER BY $order";
	}

	public function limit($limit){
		$this->data['limit'] = " LIMIT $limit";
	}

	public function union($sql){
		$this->data['union'] .= " UNION $sql";
	}

	public function union_all($sql){
		$this->data['union'] .= " UNION ALL $sql";
	}

	public function lock($lock){
		switch($lock){
			case 'share':
				$lock = 'LOCK IN SHARE MODE';
				break;

			case 'update':
				$lock = 'FOR UPDATE';
				break;
		}
		$this->data['lock'] = ' '. $lock;
	}

	public function getsql($null = null){
		$this->getsql = true;
	}

	public function select($type = 0, $db){
		if($this->data['distinct']){
			unset($this->data['field']);
		}else{
			if(!$this->data['field']){
				$this->data['field'] = '*';
			}
		}

		$sql = $this->select;
		preg_match_all('/%(\w+)%/', $sql, $arr);
		foreach($arr[1] as $k => $v){
			$sql = str_replace($arr[0][$k], $this->data[$v], $sql);
		}

		if($this->getsql){
			return $sql;
		}else{
			$method = $type == 1 ? 'one' : 'query';
			$res = $db->$method($sql);
			boa::log()->set('info', "[builder]$sql");
			if($res === false){
				$this->error = $db->error();
			}
			return $res;
		}
	}

	public function insert($data, $db){
		$this->data['fields'] = ' ('. implode(', ', array_keys($data)) .')';
		$values = array_values($data);
		foreach($values as $k => $v){
			$values[$k] = "'". addslashes($v) ."'";
		}
		$this->data['values'] = '('. implode(', ', $values) .')';

		$res = $this->exec_sql($db, $this->insert);
		if(!$this->getsql && $res !== false){
			$cls = new \ReflectionClass($db);
			if(strpos($cls->name, 'pdo') > 0){
				$res = $db->lastid(); //for PDO
			}
		}
		return $res;
	}

	public function update($data, $db){
		$fields = '';
		foreach($data as $k => $v){
			$fields .= ", $k = '". addslashes($v) ."'";
		}
		$this->data['fields'] = substr($fields, 1);

		return $this->exec_sql($db, $this->update);
	}

	public function delete($db){
		return $this->exec_sql($db, $this->delete);
	}

	public function error(){
		return $this->error;
	}

	private function exec_sql($db, $sql){
		preg_match_all('/%(\w+)%/', $sql, $arr);
		foreach($arr[1] as $k => $v){
			$sql = str_replace($arr[0][$k], $this->data[$v], $sql);
		}

		if($this->getsql){
			return $sql;
		}else{
			$res = $db->execute($sql);
			boa::log()->set('info', "[builder]$sql");
			if($res === false){
				$this->error = $db->error();
			}
			return $res;
		}
	}
}
?>