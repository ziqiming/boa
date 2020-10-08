<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.archive.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa;

class archive{
	private $cfg = [
		'str' => [],
		'file' => []
	];
	private $str, $file;

	public function __construct($cfg = []){
		if($cfg){
			foreach($cfg as $k => $v){
				$this->cfg[$k] = array_merge($this->cfg[$k], $v);
			}
		}
    }

	public function str(){
		if(!$this->str){
			$this->str = new \boa\archive\str($this->cfg['str']);
		}
		return $this->str;
	}

	public function file(){
		if(!$this->file){
			$this->file = new \boa\archive\file($this->cfg['file']);
		}
		return $this->file;
	}
}
?>