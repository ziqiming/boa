<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.archive.driver.zip.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\archive\driver;

use boa\boa;
use boa\msg;
use boa\base;

class zip extends base{
	protected $cfg = [
 		'open_flag' => null,
        'password' => null,
		'en_emptydir' => false,
		'en_filter' => '',
		'en_comment' => '',
		'en_options' => [],
		'de_filter' => null
    ];
	private $obj = null;

	public function compress($source, $dest){
		$this->open($dest, \ZipArchive::OVERWRITE | \ZipArchive::CREATE);
		if($this->cfg['en_comment']){
			$this->obj->setArchiveComment($this->cfg['en_comment']);
		}

		if(!$this->cfg['en_options']){
			$this->cfg['en_options']['remove_path'] = $source;
		}
		$res = $this->do_compress($source);
		return $res;
	}

	public function decompress($source, $dest){
		$this->open($source);
		return $this->obj->extractTo($dest, $this->cfg['de_filter']);
	}

	public function open($file, $flag = null){
		if(!$this->obj){
			$this->obj = new \ZipArchive();
		}
		if(!$flag){
			$flag = $this->cfg['open_flag'];
		}
		$res = $this->obj->open($file, $flag);
		if($res === true){
			if($this->cfg['password'] && method_exists($this->obj, 'setPassword')){ //php5.6+
				$this->obj->setPassword($this->cfg['password']);
			}
		}else{
			$err = $this->error($res);
			msg::set('boa.error.157', $file, $err);
		}
	}

	public function entries(){
		$num = $this->obj->numFiles;
		for($i = 0; $i < $num; $i++){
			$item = $this->obj->getNameIndex($i);
			$res[] = str_replace('\\', '/', $item);
		}
		return $res;
	}

	public function comment(){
		return $this->obj->getArchiveComment();
	}

	public function close(){
		if($this->obj){
			$this->obj->close();
			$this->obj = null;
		}
	}

	private function do_compress($path){
		if($fp = opendir($path)){
			$remove_all = $this->cfg['en_options']['remove_all_path'];
			$remove = $this->cfg['en_options']['remove_path'];
			$add = $this->cfg['en_options']['add_path'];
			while(false !== ($v = readdir($fp))){
			   if($v != '.' && $v != '..'){
					$thispath = "$path/$v";
					if(is_dir($thispath)){
						$res = $this->do_compress($thispath);
						if(!$remove_all && $this->cfg['en_emptydir'] && !$res){
							$dir = $thispath;
							if($remove){
								$remove = rtrim($remove, '/') .'/';
								$dir = preg_replace('/^'. preg_quote($remove, '/') .'/', '', $dir);
							}
							if($add){
								$dir = rtrim($add, '/') .'/'. ltrim($dir, '/');
							}
							$this->obj->addEmptyDir($dir);
						}
					}
				}
			}
			closedir($fp);
		}

		$res = $this->obj->addPattern('/'. $this->cfg['en_filter'] .'/', $path, $this->cfg['en_options']); //php5.4.45+zip1.11.0 'remove_path' is unavailable
		return $res;
	}

	private function error($code){
		switch($code){
			case \ZipArchive::ER_EXISTS:
				$res = 'ER_EXISTS';
				break;

			case \ZipArchive::ER_INCONS:
				$res = 'ER_INCONS';
				break;

			case \ZipArchive::ER_INVAL:
				$res = 'ER_INVAL';
				break;

			case \ZipArchive::ER_MEMORY:
				$res = 'ER_MEMORY';
				break;

			case \ZipArchive::ER_NOZIP:
				$res = 'ER_NOZIP';
				break;

			case \ZipArchive::ER_OPEN:
				$res = 'ER_OPEN';
				break;

			case \ZipArchive::ER_READ:
				$res = 'ER_READ';
				break;

			case \ZipArchive::ER_SEEK:
				$res = 'ER_SEEK';
				break;

			default:
				$res = 'ER_UNKNOWN';
				break;
		}
		return $res;
	}
}
?>