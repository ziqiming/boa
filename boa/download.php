<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.download.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa;

class download extends base{
	protected $cfg = [
		'header' => [],
		'expire' => 0,
		'size' => 2, //MB, 0=unlimited
		'exts' => 'jpg,png,gif',
		'path' => '', //BS_WWW .'file/'
		'name' => null
	];
	private $files = [];
	private $current = 0;
	private $obj;

	public function __construct($cfg = []){
		parent::__construct($cfg);

		if(!$this->cfg['path']){
			$this->cfg['path'] = BS_WWW .'file/';
		}

        $this->format_exts();

		$arr = [];
		if($this->cfg['expire'] > 0){
			$arr['timeout_execute'] = $this->cfg['expire'];
		}
		if($this->cfg['header']){
			$arr['header'] = $this->cfg['header'];
		}
		$this->obj = boa::http($arr);
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

	public function single($file, $save = ''){
		if($save){
			$this->cfg('name', $save);
		}
		$this->files = [];
		
		$res = $this->do_download($file);
		return $res;
	}

	public function multiple($arr, $save = []){
		if($save){
			$this->cfg('name', $save);
		}
		$this->files = [];

		$res = true;
		foreach($arr as $k => $v){
			$this->current = $k;
			$res = $this->do_download($v) && $res;
		}
		return $res;
	}

	public function extract($str, $type = 0, $self = false){
		$this->files = [];
		$exts = str_replace(',', '|', $this->cfg['exts']);
		$file = "['\"]?(http[s]?:)?\/\/(.+?)\.($exts)(['\"]| |>)";
		if($type == 0 || $type == -1){
			$res = preg_match_all("/<img [^>]*src=$file/i", $str, $arr);
			$this->do_extract($arr, $self);
		}
		if($type == 1 || $type == -1){
			$res = preg_match_all("/<a [^>]*href=$file/i", $str, $arr);
			$this->do_extract($arr, $self);
		}
		return $this->files;
	}
	
	private function do_extract($arr, $self){
		if(is_array($arr)){
			foreach($arr[2] as $k => $v){
				if($self){
					$file = "http://$v.". $arr[3][$k];
				}else{
					if(!preg_match('/^'. preg_quote($_SERVER['SERVER_NAME']) .'/i', $v)){
						$file = "http://$v.". $arr[3][$k];
					}
				}

				if($file){
					$key = crc32($file);
					$this->files[$key] = $file;
				}
			}
		}
	}
	
	private function do_download($file){
		$this->files[$this->current]['name'] = $file;

		$ext = strtolower(substr(strrchr($file, '.'), 1));
		if(!preg_match("/(^|,)$ext(,|$)/", $this->cfg['exts'])){
			$this->files[$this->current]['error'] = boa::lang('boa.error.123', $ext);
			return false;
		}

		$header = $this->obj->head($file);
		$size = $header['Content-Length'];
		if($this->cfg['size'] > 0 && $size > $this->cfg['size'] * 1048576){
			$this->files[$this->current]['error'] = boa::lang('boa.error.122', $this->cfg['size']);
			return false;
		}

		$path = $this->path($ext);
		$this->obj->get($file);
		if($this->obj->get_status() == 200){
			$body = $this->obj->get_body();
			$res = boa::file()->write($path, $body);
			if($res){
				$this->files[$this->current]['type'] = $header['Content-Type'];
				$this->files[$this->current]['size'] = $size;
				$this->files[$this->current]['file'] = $path;
				return true;
			}else{
				$this->files[$this->current]['error'] = boa::lang('boa.error.121', $file);
				return false;
			}
		}else{
			$this->files[$this->current]['error'] = $this->obj->get_error();
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
		return $path;
	}

	private function format_exts(){
		$this->cfg['exts'] = str_replace(' ', '', strtolower($this->cfg['exts']));
	}
}
?>