<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.archive.driver.phar.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\archive\driver;

use boa\boa;
use boa\msg;
use boa\base;

class phar extends base{
	protected $cfg = [
		'open_flag' => null,
		'en_type' => 'gzip', //zip, phar, gzip, bzip2
		'en_emptydir' => false,
		'en_filter' => null,
		'de_filter' => null,
		'de_overwrite' => false
    ];
	private $info = [];
	private $obj = null;
	private $root;

	public function compress($source, $dest){
		$file = $this->file($dest);
		$this->open($file);
		try{
			$this->obj->buildFromDirectory($source, $this->cfg['en_filter']);
			if($this->cfg['en_emptydir']){
				$this->root = preg_quote($source .'/', '/');
				$this->add_empty_dir($source);
			}
		}catch(\Exception $e){
			msg::set('boa.error.155', $file, $e->getMessage());
		}

		if($this->info['format'] == \Phar::TAR){
			try{
				$this->obj->compress($this->info['compress']);
			}catch(\Exception $e){
				msg::set('boa.error.153', $dest, $e->getMessage());
			}
			unlink($file);
		}

		return true;
	}

	public function decompress($source, $dest){
		$this->open($source);
		if($this->info['format'] == \Phar::TAR){
			try{
				$this->obj->decompress();
				$source = $this->file($source);
			}catch(\Exception $e){
				msg::set('boa.error.154', $source, $e->getMessage());
			}
		}

		try{
			$this->obj->extractTo($dest, $this->cfg['de_filter'], $this->cfg['de_overwrite']);
			if($this->info['format'] == \Phar::TAR){
				unlink($source);
			}
		}catch(\Exception $e){
			msg::set('boa.error.156', $source, $e->getMessage());
		}

		return true;
	}

	public function open($file, $flag = null){
		$this->info();
		if(!$flag){
			$flag = $this->cfg['open_flag'];
		}
		try{
			if($this->cfg['en_type'] == 'phar'){
				$this->obj = new \Phar($file, $flag);
			}else{
				$this->obj = new \PharData($file, $flag, null, $this->info['format']);
			}
		}catch(\Exception $e){
			msg::set('boa.error.157', $file, $e->getMessage());
		}
	}

	public function entries(){
		return [];
	}

	public function comment(){
		return '';
	}

	public function close(){
		if($this->obj){
			$this->obj = null;
		}
	}

	private function add_empty_dir($path){
		$num = 0;

		if($fp = opendir($path)){
			while(false !== ($v = readdir($fp))){
			   if($v != '.' && $v != '..'){
					$num++;
					$thispath = "$path/$v";
					if(is_dir($thispath)){
						$this->add_empty_dir($thispath);
					}
				}
			}
			closedir($fp);
		}

		if($num == 0){
			$path = preg_replace('/^'. $this->root .'/', '', $path);
			$this->obj->addEmptyDir($path);
		}
	}

	private function file($file){
		return preg_replace('/\.(gz|bz2)$/i', '', $file);
	}

	private function info(){
		switch($this->cfg['en_type']){
			case 'zip':
				$this->info = [
					'format' => \Phar::ZIP,
					'compress' => \Phar::NONE
				];
				break;

			case 'phar':
				$this->info = [
					'format' => \Phar::PHAR,
					'compress' => \Phar::NONE
				];
				break;

			case 'gzip':
				$this->info = [
					'format' => \Phar::TAR,
					'compress' => \Phar::GZ
				];
				break;

			case 'bzip2':
				$this->info = [
					'format' => \Phar::TAR,
					'compress' => \Phar::BZ2
				];
				break;
		}
	}
}
?>