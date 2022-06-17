<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/api/boa.view.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa;

class view{
	private $charset = 'UTF-8';
	private $remain = ['__file', '__name', 'return'];
	private $var = [];

	public function __construct(){
		$charset = boa::constant('CHARSET');
		if($charset){
			$this->charset = $charset;
		}
	}

	public function assign($k, $v){
		if(in_array($k, $this->remain)){
			msg::set('boa.error.8', $k);
		}else{
			$act = boa::env('act');
			$this->var[$act][$k] = $v;
		}
	}

	public function cli($str, $clean = true, $exit = true){
		if($clean){
			ob_clean();
		}

		fwrite(STDOUT, $str);

		if($exit){
			exit();
		}
	}

	public function str($str, $exit = true){
		echo $str;

		if($exit){
			exit();
		}
	}

	public function json($data = [], $code = 0, $msg = 'OK', $return = false){
		ob_clean();
		header('Content-type: application/json;charset='. $this->charset);

		$num = is_array($data) ? count($data) : -1;
		$arr = [
			'code' => $code,
			'msg' => $msg,
			'data' => $data,
			'num' => $num
		];

		$str = boa::json()->encode($arr, JSON_UNESCAPED_UNICODE);
		if($return){
			return $str;
		}else{
			echo $str;
			exit();
		}
	}

	public function page($page, $number = 10, $first = true, $last = true, $prev = false, $next = false){
		$obj = new \boa\view\page();
		$str = $obj->get($page, $number, $first, $last, $prev, $next);
		return $str;
	}

	public function html($tpl = '', $return = false){
		if(!$tpl){
			$con = boa::env('con');
			$act = boa::env('act');
			$tpl = "$con/$act";
		}

		$__file = $this->cache_file($tpl);
		if($__file && file_exists($__file)){
			header('Content-type: text/html;charset='. $this->charset);

			$act = boa::env('act');
			if($this->var[$act]){
				extract($this->var[$act]);
			}

			msg::set_type('str');
			require($__file);
			if($return){
				$html = ob_get_contents();
				ob_end_clean();
				return $html;
			}
		}else{
			msg::set('boa.error.2', $__file);
		}
	}

	public function xml($data = [], $code = 0, $msg = 'OK', $return = false){
		ob_clean();
		header('Content-type: application/xml;charset='. $this->charset);

		$num = is_array($data) ? count($data) : -1;
		$root = defined('VIEW_XML_ROOT') ? VIEW_XML_ROOT : 'boa';
		$arr = [
			$root => [
				'code' => $code,
				'msg' => $msg,
				'data' => $data,
				'num' => $num
			]
		];

		$str = boa::xml()->write($arr);
		if($return){
			return $str;
		}else{
			echo $str;
			exit();
		}
	}

	public function jsonp($callback, $data = [], $code = 0, $msg = 'OK', $return = false){
		ob_clean();
		header('Content-type: text/javascript;charset='. $this->charset);

		$num = is_array($data) ? count($data) : -1;
		$arr = [
			'code' => $code,
			'msg' => $msg,
			'data' => $data,
			'num' => $num
		];

		$str = boa::json()->encode($arr, JSON_UNESCAPED_UNICODE);
		$str = "$callback($str);";
		if($return){
			return $str;
		}else{
			echo $str;
			exit();
		}
	}

	public function jump($url, $sec = 0, $tip = null){
		ob_clean();
		if($sec > 0){
			$this->assign('url', $url);
			$this->assign('sec', $sec);
			$this->assign('tip', $tip);
			$this->require_file('jump');
		}else{
			header("HTTP/1.1 302 Found");
			header('location:'. $url);
		}
		exit();
	}

	public function msg($msg, $type = 'error', $clean = true, $exit = true){
		if($clean){
			ob_clean();
		}
		$this->assign('msg', $msg);
		$this->require_file($type);
		if($exit){
			exit();
		}
	}

	public function lost($url = ''){
		ob_clean();
		if(!$url){
			$url = $_SERVER['REQUEST_URI'];
		}
		header("HTTP/1.1 404 Not Found");
		$this->assign('url', $url);
		$this->require_file('404');
		exit();
	}

	public function error(){
		ob_clean();
		header("HTTP/1.1 500 Internal Error");
		$this->require_file('500');
		exit();
	}

	public function file($file, $name = ''){
		ob_end_clean();
		if($file && file_exists($file)){
			if(!$name){
				$name = basename($file);
			}
			$name = rawurlencode($name);
			$mimetype = boa::fileinfo($file)->mime_type();

			header('Pragma: public');
			header('Last-Modified: '. gmdate('D, d M Y H:i:s') .' GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: pre-check=0, post-check=0, max-age=0');
			header('Content-Transfer-Encoding: binary');
			header('Content-Encoding: none');
			header('Content-type: '. $mimetype);
			header('Content-Disposition: attachment; filename="'. $name .'"');
			header('Content-length: '. filesize($file));
			readfile($file);
			exit();
		}else{
			msg::set('boa.error.2', $file);
		}
	}

	private function require_file($__name){
		header('Content-type: text/html;charset='. $this->charset);

		$act = boa::env('act');
		if($this->var[$act]){
			extract($this->var[$act]);
		}

		msg::set_type('str');
		$tpl = "msg/$__name";
		$__file = $this->cache_file($tpl, true);
		if($__file && file_exists($__file)){
			require($__file);
		}else{
			require(BS_BOA . "view/$tpl.php");
		}
	}

	private function cache_file($tpl, $silence = false){
		$mod = boa::env('mod');
		$file = BS_WWW ."tpl/$mod/$tpl.html";
		if(file_exists($file)){
			$mtime = filemtime($file);
		}else{
			$file = BS_MOD ."$mod/view/$tpl.html";
			if(file_exists($file)){
				$mtime = filemtime($file);
			}
		}

		if($mtime > 0){
			$__file = BS_VAR ."view/$mod/$tpl.php";
			if(
				(defined('DEBUG') && DEBUG)
				 || !file_exists($__file)
				 || $mtime > filemtime($__file)
			){
				$view = new \boa\view\compiler();
				$view->file($file, $__file);
			}
		}else{
			if(!$silence){
				msg::set('boa.error.2', BS_WWW ."tpl/$mod/$tpl.html");
			}
			$__file = null;
		}
		return $__file;
	}
}
?>