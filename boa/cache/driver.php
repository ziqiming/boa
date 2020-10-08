<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.cache.driver.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\cache;

class driver{
 	protected $cfg = [
        'expire' => 0,
        'prefix' => ''
    ];
	protected $obj = null;

	public function __construct($cfg = []){
        if($cfg){
			$this->cfg = array_merge($this->cfg, $cfg);
		}
	}

	public function cfg($k = null, $v = null){
		switch(true){
			case $k === null && $v === null:
				return $this->cfg;
				break;

			case $v === null:
				return $this->cfg[$k];
				break;

			default:
				$this->cfg[$k] = $v;
				return $this;
		}
	}
}
?>