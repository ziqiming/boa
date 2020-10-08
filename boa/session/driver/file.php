<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.session.driver.file.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\session\driver;

use boa\session\driver;

class file extends driver{
	protected $cfg = [
        'path' => '' //BS_VAR .'session/'
    ];

	public function __construct($cfg){
 		parent::__construct($cfg);
		
		if(!$this->cfg['path']){
			$this->cfg['path'] = BS_VAR .'session/';
		}

		if(!file_exists($this->cfg['path'])){
			mkdir($this->cfg['path'], 0755, true);
		}
		session_save_path($this->cfg['path']);
		
		session_start();
    }
}
?>