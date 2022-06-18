<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/api/boa.console.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa;

class console{
	private $cfg = [
		'DOCUMENT_ROOT' => '',
		'SERVER_PORT' => 80,
		'SERVER_NAME' => 'localhost',
		'HTTP_HOST' => 'localhost',
		'REMOTE_ADDR' => '127.0.0.1',
		'REMOTE_PORT' => 8888,
		'REQUEST_METHOD' => 'GET',
		'REQUEST_SCHEME' => 'http',
		'REQUEST_URI' => '',
		'QUERY_STRING' => '',
		'PATH_INFO' => '',
		'HTTP_COOKIE' => '', //PHPSESSID=xxx; test=1
		'HTTP_USER_AGENT' => 'Console boa/5.2'
	];

	private $cmd = [
		'h' => 'help',
		'v' => 'version',
		'b' => 'build',
		'w' => 'www',
		'm' => 'mod',
		'c' => 'clear',
		's' => 'serve',
		'r' => 'run'
	];

	private $out;

	public function __construct(){
		require(BS_ROOT .'boa/boa.php');
		boa::init();
		ob_end_flush();
		msg::set_type('cli');
		$this->init();
	}

	public function start(){
		switch($_SERVER['argv'][1]){
			case '-h' :
			case 'help' :
				$this->help();
				break;

			case '-v' :
			case 'version' :
				$this->version();
				break;

			case '-b' :
			case 'build' :
				$this->build();
				break;

			case '-w' :
			case 'www' :
				$this->www($_SERVER['argv'][2]);
				break;

			case '-m' :
			case 'mod' :
				$this->mod($_SERVER['argv'][2]);
				break;

			case '-c' :
			case 'clear' :
				$this->clear();
				break;

			case '-s' :
			case 'serve' :
				$this->serve();
				break;

			case '-r' :
			case 'run' :
				$this->run();
				break;

			default:
				$this->out()->printl('Boa v'. VERSION, 2);
				$this->out->printl('Sorry, what?');
		}
	}

	private function init(){
		set_time_limit(0);
		if(defined('CONSOLE')){
			$cfg = unserialize(CONSOLE);
			$cfg = array_change_key_case($cfg, CASE_UPPER);
			$this->cfg = array_merge($this->cfg, $cfg);
		}

		if(!$this->cfg['DOCUMENT_ROOT']){
			$this->cfg['DOCUMENT_ROOT'] = BS_WWW;
		}

		if($this->cfg['HTTP_COOKIE']){
			preg_match_all('/(\w+)=(.+?)(;|$)/', $this->cfg['HTTP_COOKIE'], $arr);
			foreach($arr[1] as $k => $v){
				$_COOKIE[$v] = $arr[2][$k];
			}
		}

		if($_SERVER['argv'][1] == '-r' || $_SERVER['argv'][1] == 'run'){
			$act = $_SERVER['argv'][2];
			if($act){
				$args = array_slice($_SERVER['argv'], 3, null, true);
				$arr = boa::router()->make($act, $args);
				$this->cfg = array_merge($this->cfg, $arr);
			}
		}

		foreach($this->cfg as $k => $v){
			$_SERVER[$k] = $v;
		}

		parse_str($this->cfg['QUERY_STRING'], $_GET);
	}

	private function help(){
		$this->out()->printl('BOA v'. VERSION, 2);
		$this->out->printl('php boa [command] [arguments]', 2);

		foreach($this->cmd as $k => $v){
			$arr[] = ["-$k, ", "$v ", boa::lang("boa.console.$v")];
		}
		$this->out->table($arr);
	}

	private function version(){
		$this->out()->printl('BOA v'. VERSION, 2);
	}

	private function build(){
		$this->out()->printl('Boa welcome you!');
	}

	private function www($name){
		if(!$name){
			msg::set('boa.error.91', '');
		}
		boa::installer()->initlize($name);
		$this->result();
	}

	private function mod($name){
		if(!$name){
			msg::set('boa.error.91', '');
		}
		boa::installer()->initlize(null, $name);
		$this->result();
	}

	private function clear(){
		$file = boa::file();
		$path = BS_ROOT . 'var';
		$vars = $file->read_dir($path, file::TYPE_FOLDER);
		foreach($vars as $dir){
			$file->clear_dir("$dir/cache");
			$file->clear_dir("$dir/view");
		}
		$this->result();
	}

	private function serve(){
		if($_SERVER['argv'][2]){
			$port = $_SERVER['argv'][2];
		}else{
			$port = 8000;
		}
		if($_SERVER['argv'][3]){
			$root = $_SERVER['argv'][3];
		}else{
			$root = BS_WWW;
		}
		$cmd = "php -S 127.0.0.1:$port -t $root";
		exec($cmd);
	}

	private function run(){
		if(!$_SERVER['argv'][2]){
			msg::set('boa.error.91', ' mod.con.act');
		}

		if($this->cfg['HTTP_HOST'] == 'localhost'){
			$this->cfg['HTTP_HOST'] = $this->cfg['SERVER_NAME'];
		}

		boa::route();
		boa::call();
	}

	private function result(){
		$this->out()->printl('BOA v'. VERSION, 2);
		$this->out->printl(boa::lang('boa.info.done'));
	}

	private function out(){
		if(!$this->out){
			$this->out = new \boa\console\output();
		}
		return $this->out;
	}
}
?>