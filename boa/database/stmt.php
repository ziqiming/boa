<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.database.stmt.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\database;

use boa\boa;
use boa\msg;

class stmt{
	private $db;
	private $stmt;
	private $sql;
	private $para = [];
	private $return = false;

	public function __construct($sql, $db, $return = false){
		$this->db = $db;
		$this->sql = $sql;
		$this->return = $return;
		$this->stmt = $this->db->prepare($sql);

		$err = $this->db->error(); //NOT STMT ERROR
		if($err){
			msg::set('boa.error.104', $sql, $err);
		}
	}

	public function execute($para = [], $type = ''){
		if(!is_array($para)){
			$para = array($para);
		}
		if($para){
			$this->para = $para;
			$this->db->stmt_bind($this->stmt, $para, $type);
		}
		$res = $this->stmt->execute();

		$sql = $this->sql();
		boa::log()->set('info', "[stmt]$sql");
		if($res === false){
			if($this->return){
				return false;
			}else{
				msg::set('boa.error.102', $this->error());
			}
		}
		return $res;
	}

	public function one(){
		return $this->db->stmt_one($this->stmt);
	}

	public function all(){
		return $this->db->stmt_all($this->stmt);
	}

	public function lastid(){
		return $this->db->stmt_lastid($this->stmt);
	}

	public function affected(){
		return $this->db->stmt_affected($this->stmt);
	}

	public function error(){
		return $this->db->stmt_error($this->stmt);
	}

	private function sql(){
		$sql = $this->sql;
		foreach($this->para as $v){
			$v = "'". addslashes($v) ."'";
			$sql = preg_replace('/\?/', $v, $sql, 1);
		}
		return $sql;
	}
}
?>