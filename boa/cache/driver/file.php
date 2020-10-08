<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.cache.driver.file.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\cache\driver;

use boa\boa;
use boa\msg;
use boa\cache\driver;

class file extends driver{
	protected $cfg = [
        'path' => '', //BS_VAR .'cache/'
        'expire' => 0,
        'prefix' => ''
    ];

	public function __construct($cfg){
        parent::__construct($cfg);

        if(!$this->cfg['path']){
			$this->cfg['path'] = BS_VAR .'cache/';
		}
	}

	public function get($name){
		$file = $this->cfg['path']. $this->cfg['prefix'] ."$name.dat";
		if(file_exists($file)){
			if($this->cfg['expire'] > 0){
				$mtime = filemtime($file);
				if($mtime + $this->cfg['expire'] <= time()){
					$res = false;
				}else{
					$res = file_get_contents($file);
				}
			}else{
				$res = file_get_contents($file);
			}
		}else{
			$res = false;
		}

		if($res !== false){
			if(substr($res, 0, 1) === chr(8)){
				$res = unserialize(substr($res, 1));
			}
		}
		return $res;
	}

	public function set($name, $val){
		$file = $this->cfg['path']. $this->cfg['prefix'] ."$name.dat";
		if(!is_scalar($val)){
			$val = chr(8) . serialize($val);
		}
		return boa::file()->write($file, $val);
	}

	public function del($name){
		$fp = opendir($this->cfg['path']);
		if($fp){
			while(false !== ($v = readdir($fp))){
				if($v == '.' || $v == '..'){
					continue;
				}

				if(preg_match('/^'. $this->cfg['prefix'] . $name .'(\-|\.)/', $v)){
					unlink($this->cfg['path'] . $v);
				}
			}
			closedir($fp);
		}
		return true;
	}

	public function clear(){
		$fp = opendir($this->cfg['path']);
		if($fp){
			while(false !== ($v = readdir($fp))){
				if($v != '.' && $v != '..'){
					unlink($this->cfg['path'] . $v);
				}
			}
			closedir($fp);
		}
	}
}
?>