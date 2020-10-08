<?php
namespace mod\home\installer;

use boa\boa;
use boa\msg;

class installer{
	private $info = [
		'name' => 'home',
		'title' => 'home module',
		'version' => '1.0',
		'build' => '1',
		'author' => 'poetbi',
		'contact' => 'poetbi@163.com',
		'website' => 'http://boasoft.top',
		'upgrade' => '',
		'copyright' => '(c) 2020 BoaSoft.Top'
	];

	public function install(){
		$public = BS_MOD . $this->info['name'] .'/public';
		$res_mod = BS_WWW .'res/'. $this->info['name'];
		boa::file()->copy_dir($public, $res_mod);

		//do something else

		return $this->info;
	}

	public function upgrade(){
		if($this->info['upgrade']){
			//do something else
		}

		return $this->info;
	}

	public function uninstall(){
		$res_mod = BS_WWW .'res/'. $this->info['name'];
		boa::file()->clear_dir($res_mod, true);

		//do something else

		return $this->info;
	}
}
?>