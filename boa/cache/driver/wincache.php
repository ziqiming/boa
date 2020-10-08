<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.cache.driver.wincache.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\cache\driver;

use boa\msg;
use boa\cache\driver;

class wincache extends driver{
    public function __construct($cfg){
        if(!function_exists('wincache_ucache_get')){
            msg::set('boa.error.41', 'WinCache');
        }
        parent::__construct($cfg);
    }

	public function get($name){
		$res = wincache_ucache_get($this->cfg['prefix'] . $name);
		if(substr($res, 0, 1) === chr(8)){
			$res = unserialize(substr($res, 1));
		}
		return $res;
	}

	public function set($name, $val){
		if(!is_scalar($val)){
			$val = chr(8) . serialize($val);
		}
		return wincache_ucache_set($this->cfg['prefix'] . $name, $val, $this->cfg['expire']);
	}

	public function del($name){
		return wincache_ucache_delete($this->cfg['prefix'] . $name);
	}

	public function clear(){
		wincache_ucache_clear();
	}
}
?>