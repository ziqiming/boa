<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.installer.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa;

class installer{
	public function initlize($www = 'www', $mod = 'home'){
		define('CHARSET', 'UTF-8');
		$file = boa::file();
		$path = BS_BOA .'installer';
		$local = PHP_SAPI != 'cli' && $_SERVER['SERVER_ADDR'] != '127.0.0.1' ? false : true;

		if($local && $www){
			$dir = BS_ROOT . "var/$www";
			$file->copy_dir("$path/var", $dir);
			$file->chmod($dir, 0777);

			$dir = BS_ROOT . $www;
			$file->copy_dir("$path/www", $dir);
			$file->chmod($dir, 0777);
		}

		if($local && $mod){
			if($mod == 'boa'){
				msg::set('boa.error.7', $mod);
			}

			$dir = BS_MOD . $mod;
			if(!file_exists($dir)){
				$file->copy_dir("$path/mod", $dir);
				if($www){
					$file->copy_dir("$dir/view", BS_ROOT . "$www/tpl/$mod");
					$file->copy_dir("$dir/public", BS_ROOT . "$www/res/$mod");
				}else{
					$file->copy_dir("$dir/view", BS_WWW . "tpl/$mod");
					$file->copy_dir("$dir/public", BS_WWW . "res/$mod");
				}

				$file->chmod($dir, 0777);

				$file->clear_dir("$dir/view", true);
				$file->clear_dir("$dir/public", true);

				if($mod != 'home'){
					$file->replace_dir($dir, 'home', $mod, 'php|html');
				}
			}
		}
	}

	public function install($mod){
		boa::file()->chmod(BS_BOA, 0555);

		$program = $this->program($mod);
		return $program->install();
	}

	public function upgrade($mod){
		$program = $this->program($mod);
		return $program->upgrade();
	}

	public function uninstall($mod, $clear = false){
		$program = $this->program($mod);
		$res = $program->uninstall();

		if($clear){
			boa::file()->clear_dir(BS_MOD . $mod, true);
		}

		return $res;
	}

	private function program($mod){
		$cls = "\\mod\\$mod\\installer\\installer";
		return new $cls();
	}
}
