<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.upload.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa;

class upload extends base{
	protected $cfg = [
		'strict' => false,
		'size' => 2, //MB, 0=unlimited
		'exts' => 'jpg,png,gif',
		'path' => '', //BS_WWW .'file/'
		'name' => null
	];
	private $files = [];
	private $current = 0;

	public function __construct($cfg = []){
		parent::__construct($cfg);
		
		if(!$this->cfg['path']){
			$this->cfg['path'] = BS_WWW .'file/';
		}

		$this->format_exts();
	}

	public function cfg($k = null, $v = null){
		if($k == 'exts' && $v !== null){
			$this->format_exts();
		}
		return parent::cfg($k, $v);
	}

	public function get_file($i = 0){
		return $this->files[$i];
	}

	public function get_files(){
		return $this->files;
	}

	public function base64($field, $save = ''){
		$file = $_POST[$field];
		if($save){
			$this->cfg('name', $save);
		}
		$this->files = [];

		if($file){
			$mime = str_replace('data:', '', strstr($file, ';base64,', true));
			$body = base64_decode(substr(strstr($file, ';base64,'), 8));

			$size = strlen($body);
			if($this->cfg['size'] > 0 && $size > $this->cfg['size'] * 1048576){
				$this->files[$this->current]['error'] = boa::lang('boa.error.113', $this->cfg['size']);
				return false;
			}

			$ext = $this->check_mime($mime);
			if(!$ext){
				$this->files[$this->current]['error'] = boa::lang('boa.error.114', $mime);
				return false;
			}

			$path = $this->path($ext);
			$res = boa::file()->write($path, $body);
			if($res){
				$arr = [
					'type' => $mime,
					'size' => $size,
					'file' => $path
				];
				$this->files[$this->current] = $arr;
				return true;
			}else{
				$this->files[$this->current]['error'] = boa::lang('boa.error.111', $field);
				return false;
			}
		}else{
			return false;
		}
	}

	public function single($field, $save = ''){
		$file = $_FILES[$field];
		if($save){
			$this->cfg('name', $save);
		}
		$this->files = [];

		if($file['tmp_name']){
			$this->files[$this->current] = $file;
			$res = $this->do_upload($file);
			return $res;
		}else{
			return false;
		}
	}

	public function multiple($field, $save = []){
		$files = $_FILES[$field];
		if($save){
			$this->cfg('name', $save);
		}
		$this->files = [];

		if($files['tmp_name']){
			$res = true;
			foreach($files['tmp_name'] as $k => $v){
				$file = [
					'tmp_name' => $v,
					'name' => $files['name'][$k],
					'type' => $files['type'][$k],
					'size' => $files['size'][$k],
					'error' => $files['error'][$k]
				];
				$this->current = $k;
				$this->files[$this->current] = $file;
				$res = $this->do_upload($file) && $res;
			}
			return $res;
		}else{
			return false;
		}
	}

	private function do_upload($file){
		if($file['error']){
			return false;
		}

		if(!is_uploaded_file($file['tmp_name'])){
			$this->files[$this->current]['error'] = boa::lang('boa.error.112', $file['name']);
			return false;
		}

		if($this->cfg['size'] > 0 && $file['size'] > $this->cfg['size'] * 1048576){
			$this->files[$this->current]['error'] = boa::lang('boa.error.113', $this->cfg['size']);
			return false;
		}

		$ext = strtolower(substr(strrchr($file['name'], '.'), 1));
		if(!preg_match("/(^|,)$ext(,|$)/", $this->cfg['exts'])){
			$this->files[$this->current]['error'] = boa::lang('boa.error.114', $ext);
			return false;
		}else if($this->cfg['strict'] && $file['type']){
			$mime = util::mimetype($ext);
			if($mime != $file['type']){
				$this->files[$this->current]['error'] = boa::lang('boa.error.114', $file['type']);
				return false;
			}
		}

		$path = $this->path($ext);
		if(move_uploaded_file($file['tmp_name'], $path)){
			unset($this->files[$this->current]['tmp_name']);
			$this->files[$this->current]['file'] = $path;
			return true;
		}else{
			$this->files[$this->current]['error'] = boa::lang('boa.error.111', $file['name']);
			return false;
		}
	}

	private function path($ext){
		if(is_array($this->cfg['name'])){
			$name = $this->cfg['name'][$this->current];
		}else{
			$name = $this->cfg['name'];
		}

		if(!$name){
			$micro = substr(strrchr(microtime(true), '.'), 1);
			$name = date('Y/m/d/His', time()) ."$micro.$ext";
		}else{
			$reg = preg_quote($this->cfg['path'], '/');
			$name = preg_replace("/^$reg/", '', $name);
			$name = ltrim($name, ' /');
		}

		$path = $this->cfg['path'] . $name;
		$dir = dirname($path);
		if(!file_exists($dir)){
			mkdir($dir, 0755, true);
		}

		return $path;
	}

	private function check_mime($mime){
		$exts = explode(',', $this->cfg['exts']);
		foreach($exts as $ext){
			$type = util::mimetype($ext);
			if($mime == $type){
				return $ext;
			}
		}
		return false;
	}

	private function format_exts(){
		$this->cfg['exts'] = str_replace(' ', '', strtolower($this->cfg['exts']));
	}
}
?>