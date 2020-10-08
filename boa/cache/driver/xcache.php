<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.cache.driver.xcache.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\cache\driver;

use boa\msg;
use boa\cache\driver;

class xcache extends driver{
    public function __construct($cfg){
        if(!function_exists('xcache_get')){
            msg::set('boa.error.41', 'Xcache');
        }
        parent::__construct($cfg);
    }

	public function get($name){
		$res = xcache_get($this->cfg['prefix'] . $name);
		if(substr($res, 0, 1) === chr(8)){
			$res = unserialize(substr($res, 1));
		}
		if($res === null){
			$res = false;
		}
		return $res;
	}

	public function set($name, $val){
		if(!is_scalar($val)){
			$val = chr(8) . serialize($val);
		}
		return xcache_set($this->cfg['prefix'] . $name, $val, $this->cfg['expire']);
	}

	public function del($name){
		return xcache_unset($this->cfg['prefix'] . $name);
	}

	public function clear(){
		xcache_unset_by_prefix($this->cfg['prefix']);
	}
}
?>