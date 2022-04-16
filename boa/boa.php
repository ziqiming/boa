<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.boa.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa;

defined('BS_ROOT') or exit('BS_ROOT');
defined('BS_WWW') or exit('BS_WWW');
class boa{
	private static $env = [[
		'lng'  => 'zh-cn',
		'mod'  => 'home',
		'con'  => 'index',
		'act'  => 'index',
		'var' => [
			'page' => 1
		]
	]];
	private static $info = [];
	private static $obj = [];
	private static $mod = [];
	private static $con = [];
	private static $lang = [];
	private static $cache_is_up = 0;
	private static $save;

	public static function init(){
		self::$info['time_start'] = microtime(true);
		self::$info['mem_start'] = memory_get_usage();

		set_error_handler(['\\boa\\boa', 'error']);
		set_exception_handler(['\\boa\\boa', 'exception']);
		spl_autoload_register(['\\boa\\boa', 'load']);
		register_shutdown_function(['\\boa\\boa', 'finish']);

		ob_start();
		self::head();
		self::conf();

		if(!file_exists(BS_MOD)){
			self::installer()->initlize();
		}

		self::event()->trigger('init');
		ob_clean();
	}

	public static function start($env = []){
		if($env){
			foreach($env as $k => $v){
				self::$env[0][$k] = $v;
			}
		}
		try{
			self::init();
			self::type();
			if(!$env){
				self::route();
			}
			self::call();
		}catch(\Throwable $e){
			msg::setEx($e);
		}catch(\Exception $e){
			msg::setEx($e);
		}
	}

	public static function call($key = null, $var = []){
		if($key){
			$arr = explode('.', $key);
			$env = [
				'mod'  => $arr[0],
				'con'  => $arr[1],
				'act'  => $arr[2]
			];
			if($var){
				$env['var'] = $var;
			}
		}

		if($env){
			array_unshift(self::$env, $env);
		}

		self::mod(self::env('mod'));
		$act = self::env('act');
		$con = self::con();
		$res = $con->$act();
		self::event()->trigger('action');

		if($env){
			array_shift(self::$env);
		}

		if($res){
			return $res;
		}
	}

	public static function info($k = null, $v = null){
		switch(true){
			case $k === null && $v === null:
				return self::$info;
				break;

			case $v === null:
				return self::$info[$k];
				break;

			default:
				self::$info[$k] = $v;
		}
	}

	public static function env($k = null, $v = null){
		$env = array_merge(end(self::$env), self::$env[0]);
		switch(true){
			case $k === null && $v === null:
				return $env;
				break;

			case $v === null:
				list($top, $sub) = explode('.', $k, 2);
				$v = $env[$top];
				if($sub){
					$v = $v[$sub];
				}
				return $v;
				break;

			default:
				list($top, $sub) = explode('.', $k, 2);
				if($sub){
					self::$env[0][$top][$sub] = $v;
				}else{
					self::$env[0][$k] = $v;
				}
		}
	}

	public static function route(){
		$router = self::router();
		self::$env[0] = array_merge(self::$env[0], $router->env());
	}

	public static function in_env(){
		return self::$env[1];
	}

	public static function cache_on(){
		self::$cache_is_up = 1;
	}

	public static function lang(){
		$args = func_get_args();
		$key = array_shift($args);

		$lng = self::env('lng');
		$arr = explode('.', $key);
		$k = "$lng.{$arr[0]}.{$arr[1]}";

		if(self::$cache_is_up){
			$lang = self::cache()->get('language', [
				'mod' => $arr[0],
				'file' => $arr[1],
				'lng' => $lng
			]);
		}else{
			if(!array_key_exists($k, self::$lang)){
				if($arr[0] == 'boa'){
					$path = BS_BOA . 'language/';
				}else{
					$path = BS_MOD . $arr[0] .'/language/';
				}
				$file = "$path$lng/{$arr[1]}.php";
				if(file_exists($file)){
					self::$lang[$k] = include($file);
				}else{
					return '1:'. strtoupper($key);
				}
			}
			$lang = self::$lang[$k];
		}

		for($i = 2; $i < count($arr); $i++){
			if(!array_key_exists($arr[$i], $lang)){
				return "$i:". strtoupper($key);
			}else{
				$lang = $lang[$arr[$i]];
			}
		}

		foreach($args as $k => $v){
			$v = strip_tags($v, '<a><i>');
			$lang = preg_replace("/%$k/", $v, $lang);
		}
		$lang = preg_replace('/%\d/', '', $lang);

		return $lang;
	}

	public static function model($key){
		list($mod, $model) = explode('.', $key, 2);
		$cls = "\\mod\\$mod\\model\\$model";
		return new $cls();
	}

	public static function constant($key){
		$mod = self::env('mod');
		if(defined("$mod\\$key")){
			$key = "$mod\\$key";
		}
		return constant($key);
	}

	public static function db($new = []){
		$key = 'database'. self::arr2key($new);
		if(!self::$obj[$key]){
			$cfg = self::merge(DATABASE, $new);
			self::$obj[$key] = new database($cfg);
		}
		return self::$obj[$key];
	}

	private static function conf(){
		if(!defined('BS_VAR')){
			$www = rtrim(BS_WWW, '/');
			$www = substr(strrchr($www, '/'), 1);
			define('BS_VAR', BS_ROOT ."var/$www/");
		}

		$config = BS_VAR .'config/config.php';
		if(file_exists($config)){
			$arr = include($config);
			foreach($arr as $k => $v){
				$k = strtoupper($k);
				if(is_array($v)){
					$v = serialize($v);
				}
				define($k, $v);
			}
		}

		if(defined('LANGUAGE')){
			self::env('lng', strtolower(LANGUAGE));
		}

		if(defined('DEBUG') && DEBUG){
			ini_set('display_errors', 'On');
		}else{
			error_reporting(0);
		}

		if(!defined('BS_BOA')){
			define('BS_BOA', BS_ROOT .'boa/');
		}
		if(!defined('BS_MOD')){
			define('BS_MOD', BS_ROOT .'mod/');
		}

		if(!defined('WWW')){
			$root = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
			$dir = strrchr($root, '/') .'/';
			$www = substr(BS_WWW, strrpos(BS_WWW, $dir) + strlen($dir) - 1);
			define('WWW', $www);
		}
		if(!defined('WWW_RES')){
			define('WWW_RES', WWW .'res/');
		}
		if(!defined('WWW_FILE')){
			define('WWW_FILE', WWW .'file/');
		}

		ini_set('date.timezone', self::lang('boa.locale.timezone'));
	}

	private static function mod($mod){
		if(!in_array($mod, self::$mod)){
			self::$mod[] = $mod;
			$file = BS_MOD . "$mod/config.php";
			if(file_exists($file)){
				$arr = include($file);
				foreach($arr as $k => $v){
					$k = strtoupper($k);
					if(is_array($v)){
						$v = serialize($v);
					}
					define("$mod\\$k", $v);
				}
			}
		}

		self::event()->trigger('module');
	}

	private static function con(){
		$mod = self::env('mod');
		$con = self::env('con');
		$key = "$mod.$con";
		if(!array_key_exists($key, self::$con)){
			$file = BS_MOD ."$mod/controller/$con.php";
			$empty = BS_MOD ."$mod/controller/_empty.php";

			if(file_exists($file)){
				require($file);
				$cls = "\\mod\\$mod\\controller\\$con";
				if(class_exists($cls, false)){
					self::$con[$key] = new $cls();
				}else{
					msg::set('boa.error.3', $cls);
				}
			}else if(file_exists($empty)){
				$key = "$mod._empty";
				if(!array_key_exists($key, self::$con)){
					require($empty);
					$cls = "\\mod\\$mod\\controller\\_empty";
					if(class_exists($cls, false)){
						self::$con[$key] = new $cls();
					}else{
						msg::set('boa.error.3', $cls);
					}
				}
			}else{
				if(defined('DEBUG') && DEBUG){
					msg::set('boa.error.2', $file);
				}else{
					self::view()->lost();
				}
			}
		}

		self::event()->trigger('controller');
		return self::$con[$key];
	}

	private static function head(){
		error_reporting(E_ALL & ~E_NOTICE);
		header('X-Powered-By: BOA (http://boasoft.top)');

		if(isset($_SERVER['HTTP_ORIGIN'])){
			self::security()->cors()->check();
		}

		if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
			exit('BOA');
		}
	}

	public static function lib($key, $args = null){
		$arr = explode('.', $key, 2);
		if(count($arr) == 1){
			$mod = self::env('mod');
			$cls = $arr[0];
		}else{
			$mod = $arr[0];
			$cls = $arr[1];
		}
		$file = BS_MOD ."$mod/library/$cls.php";
		if(file_exists($file)){
			$cls = "\\mod\\$mod\\library\\$cls";
			return new $cls($args);
		}else{
			msg::set('boa.error.2', $file);
		}
	}

	public static function load($cls){
		$cls = str_replace('\\', '/', $cls);
		$file = BS_ROOT . "$cls.php";
		if(file_exists($file)){
			require($file);
		}else{
			msg::set('boa.error.2', $file);
		}
	}

	public static function error($no, $str, $file, $line){
		msg::setE($no, $str, $file, $line);
	}

	public static function exception($e){
		msg::setEx($e);
	}

	public static function save($path, $force = false){
		if($force){
			$path = chr(8) . $path;
		}
		self::$save = $path;
	}

	public static function finish(){
		$path = self::$save;
		if($path){
			$force = substr($path, 0, 1) == chr(8) ? true : false;
			if($force){
				$path = substr($path, 1);
			}
			if(!file_exists($path) || $force){
				self::file()->write($path, ob_get_contents());
			}
		}
		self::$info['time_end'] = microtime(true);
		self::$info['mem_end'] = memory_get_usage();
		self::log()->save();
	}

	public static function debug($v, $k = '-'){
		if(!is_scalar($v)){
			if(is_resource($v)){
				$v = serialize($v);
			}else{
				$json = self::json();
				$v = $json->encode($v, JSON_UNESCAPED_UNICODE);
			}
		}

		$time = date(boa::lang('boa.locale.longtime'));
		$str = "[$time] $k : $v\r\n\r\n";

		self::file()->write(BS_VAR .'debug/debug.txt', $str, FILE_APPEND);
	}

	public static function __callStatic($name, $cfg = []){
		if($cfg){
			$cfg = current($cfg);
		}
		$const = strtoupper($name);
		$mod_const = self::env('mod') .'\\'. $const;
		if(defined($mod_const)){
			$cfg = self::merge(constant($mod_const), $cfg);
		}
		$key = $name . self::arr2key($cfg);
		if(!self::$obj[$key]){
			if(defined($const)){
				$cfg = self::merge(constant($const), $cfg);
			}
			$name = '\\boa\\'. $name;
			self::$obj[$key] = new $name($cfg);
		}
		return self::$obj[$key];
    }

	private static function type(){
		$name = defined('MSG_TYPE_VAR') ? MSG_TYPE_VAR : '_msg';
		$type = $_REQUEST[$name];
		if(!$type){
			if($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
				$type = defined('MSG_TYPE_VAL') ? MSG_TYPE_VAL : 'json';
			}
		}
		if($type){
			msg::set_type($type);
		}
	 }

	private static function merge($old, $new = []){
		$cfg = unserialize($old);
		if($cfg === false){
			$cfg = [];
		}
		if($new){
			$cfg = array_merge($cfg, $new);
		}
		return $cfg;
	}

	private static function arr2key($arr = []){
		if($arr){
			if(is_array($arr)){
				ksort($arr);
			}
			$str = json_encode($arr);
			$key = crc32($str);
		}else{
			$key = '';
		}
		return $key;
	}
}
?>
