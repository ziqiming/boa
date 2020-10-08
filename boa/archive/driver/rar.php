<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.archive.driver.rar.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\archive\driver;

use boa\boa;
use boa\msg;
use boa\base;

class rar extends base{
	protected $cfg = [
		'program' => 'rar',
        'password' => null,
		'en_emptydir' => false,
		'en_comment' => ''
    ];
	private $obj = null;

	public function compress($source, $dest){
		$cmd = $this->cfg['program'];
		$add = '';
		if(!$this->cfg['en_emptydir']){
			$add .= ' -ed';
		}
		if($this->cfg['password']){
			$add .= ' -p'. $this->cfg['password'];
		}
		$res = exec("$cmd a -r -ep1 $add $dest $source");

		if($this->cfg['en_comment'] && file_exists($dest)){
			exec("$cmd c ". $this->cfg['en_comment'] ." $dest");
		}
		return $res;
	}

	public function decompress($source, $dest){
		$this->open($source);
		$dest .= '/';
		$entries = $this->entries();
		foreach($entries as $entry){
			$file = $dest . $entry;
			$entry->extract($dest, $file);
		}
		return true;
	}

	public function open($file, $flag = null){
		if(!$this->obj){
			$this->obj = rar_open($file, $this->cfg['password']);
		}
		if($this->obj === false){
			msg::set('boa.error.157', $file, '');
		}
	}

	public function entries(){
		return rar_list($this->obj);
	}

	public function comment(){
		return rar_comment_get($this->obj);
	}

	public function close(){
		if($this->obj){
			rar_close($this->obj);
			$this->obj = null;
		}
	}
}
?>