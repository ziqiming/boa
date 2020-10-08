<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.cache.driver.apcu.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\cache\driver;

use boa\msg;
use boa\cache\driver;

class apcu extends driver{
    public function __construct($cfg){
        if(!function_exists('apcu_fetch')){
            msg::set('boa.error.41', 'APCu');
        }
        parent::__construct($cfg);
    }

	public function get($name){
		return apcu_fetch($this->cfg['prefix'] . $name);
	}

	public function set($name, $val){
		return apcu_store($this->cfg['prefix'] . $name, $val, $this->cfg['expire']);
	}

	public function del($name){
		return apcu_delete($this->cfg['prefix'] . $name);
	}

	public function clear(){
		apcu_clear_cache();
	}
}
?>