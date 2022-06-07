<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/api/boa.http.driver.curl.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\http\driver;

use boa\boa;
use boa\msg;
use boa\http\driver;

class curl extends driver{
	protected $cfg = [
		'ssl' => 0, //0, 1, 2
		'proxy' => '',
		'posttype' => 'form', //form, json, xml
		'mimetype' => 'application/x-www-form-urlencoded',
		'timeout_connect' => 15,
		'timeout_execute' => 0,
		'header' => [],
		'option' => []
	];
	private $option = [];

	public function __construct($cfg){
		if(!function_exists('curl_exec')){
			msg::set('boa.error.6', 'CURL');
		}
		parent::__construct($cfg);
	}

	public function set_cookie($cookie){
		$this->cfg['option'][CURLOPT_COOKIE] = $cookie;
	}
	
	public function get($url){
		$this->option_init();
		$this->option_ssl();
		
		$this->option[CURLOPT_URL] = $url;

		$this->option_more();
		$this->send();
	}
	
	public function post($url, $data){
		$this->option_init();
		$this->option_ssl();
		
		$this->option[CURLOPT_URL] = $url;
		$this->option[CURLOPT_POST] = true;
		$this->option[CURLOPT_POSTFIELDS] = $data;
		
		$this->option_more();
		$this->send();
	}

	public function upload($url, $file, $form){
		$this->option_init();
		$this->option_ssl();
		
		$file = $this->file_data($file);
		$data = array_merge($form, $file);
		
		$this->option[CURLOPT_URL] = $url;
		$this->option[CURLOPT_POST] = true;
		$this->option[CURLOPT_POSTFIELDS] = $data;
		
		$this->option_more();
		$this->send();
	}
	
	private function send(){
		$this->result = [];
		$ch = curl_init();
		curl_setopt_array($ch, $this->option);

		if($this->cfg['timeout_execute'] > 0){
			set_time_limit($this->cfg['timeout_execute']);
		}

		$response = curl_exec($ch);
		
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		curl_close($ch);

		if($errno){
			$this->result['code'] = 63;
			$this->result['msg'] = boa::lang('boa.error.63', "[$errno]$error");
			return false;
		}

		$response = preg_replace('/^HTTP\/([\.\w ]+?)[\t\r\n]+HTTP/', 'HTTP', $response);
		$arr = explode("\r\n\r\n", $response, 2);
		$this->result['head'] = trim($arr[0]);
		$this->result['body'] = $arr[1];

		preg_match('/^HTTP\/[\d\.]+ (\d{3})/', $this->result['head'], $res);
		$this->result['code'] = $res[1];
		if($res[1] != 200){
			$this->result['msg'] = $res[2];
		}
	}
	
	private function option_init(){
		$this->option = $this->cfg['option'];
		
		if($this->cfg['posttype'] != 'form'){
			$this->cfg['header']['Content-type'] = $this->cfg['mimetype'] .'; charset='. CHARSET;
		}

		$arr = [];
		foreach($this->cfg['header'] as $k => $v){
			if($v){
				$arr[] = "$k: $v";
			}
		}
		if($arr){
			$this->option[CURLOPT_HTTPHEADER] = $arr;
		}

		if($this->cfg['proxy']){
			$this->option[CURLOPT_PROXY] = $this->cfg['proxy'];
		}

		if($this->cfg['timeout_connect'] > 0){
			$this->option[CURLOPT_CONNECTTIMEOUT] = $this->cfg['timeout_connect'];
		}

		if($this->cfg['timeout_execute'] > 0){
			$this->option[CURLOPT_TIMEOUT] = $this->cfg['timeout_execute'];
		}

		$this->option[CURLOPT_RETURNTRANSFER] = true;
		$this->option[CURLOPT_HEADER] = true;
	}

	private function option_ssl(){
		if($this->cfg['ssl'] > 0){
			$this->option[CURLOPT_SSL_VERIFYPEER] = 1;
			$this->option[CURLOPT_SSL_VERIFYHOST] = 2;

			$curl = BS_VAR .'http/';
			if($this->cfg['ssl'] == 2){
				if(!$this->option[CURLOPT_SSLCERT]){
					$this->option[CURLOPT_SSLCERT] = $curl .'bilateral/cacert.pem';
				}
				if(file_exists($this->option[CURLOPT_SSLCERT])){
					$this->set_option(CURLOPT_SSLCERTTYPE, 'PEM');
					$this->set_option(CURLOPT_SSLCERT, $this->option[CURLOPT_SSLCERT]);
					if(isset($this->option[CURLOPT_SSLCERTPASSWD])){
						$this->set_option(CURLOPT_SSLCERTPASSWD, $this->option[CURLOPT_SSLCERTPASSWD]);
					}
				}else{
					msg::set('boa.error.2', $this->option[CURLOPT_SSLCERT]);
				}

				if(!$this->option[CURLOPT_SSLKEY]){
					$this->option[CURLOPT_SSLKEY] = $curl .'bilateral/cacert.key';
				}
				if(file_exists($this->option[CURLOPT_SSLKEY])){
					$this->set_option(CURLOPT_SSLKEYTYPE, 'PEM');
					$this->set_option(CURLOPT_SSLKEY, $this->option[CURLOPT_SSLKEY]);
					if(isset($this->option[CURLOPT_SSLKEYPASSWD])){
						$this->set_option(CURLOPT_SSLKEYPASSWD, $this->option[CURLOPT_SSLKEYPASSWD]);
					}
				}else{
					msg::set('boa.error.2', $this->option[CURLOPT_SSLKEY]);
				}
			}else{
				if(!$this->option[CURLOPT_CAINFO]){
					$this->option[CURLOPT_CAINFO] = $curl .'unilateral/cacert.pem';
				}
				if(file_exists($this->option[CURLOPT_CAINFO])){
					$this->set_option(CURLOPT_CAINFO, $this->option[CURLOPT_CAINFO]);
				}else{
					msg::set('boa.error.2', $this->option[CURLOPT_CAINFO]);
				}
			}
		}else{
			$this->option[CURLOPT_SSL_VERIFYPEER] = 0;
			$this->option[CURLOPT_SSL_VERIFYHOST] = 0;
		}
	}

	private function option_more(){
		if(defined('DEBUG') && DEBUG){
			$this->option[CURLOPT_VERBOSE] = true;
			$this->option[CURLOPT_CERTINFO] = true;
			$this->option[CURLOPT_STDERR] = fopen(BS_VAR .'debug/http.curl.txt', 'a');
		}
	}

	private function set_option($k, $v){
		if(!array_key_exists($k, $this->cfg['option'])){
			$this->option[$k] = $v;
		}
	}
	
	private function file_data($file){
		if(function_exists('curl_file_create')){
			$safe = true;
			foreach($file as $k => $v){
				$file_name = substr(strrchr($v[0], '/'), 1);
				$file[$k] = new \CURLFile($v[0], $v[1], $file_name);
			}
			
		}else{
			$safe = false;
			foreach($file as $k => $v){
				$file[$k] = "@{$v[0]};type={$v[1]}";
			}
		}

		if(defined('CURLOPT_SAFE_UPLOAD')){//php5.5+
			$this->option[CURLOPT_SAFE_UPLOAD] = $safe;
		}

		return $file;
	}
}
?>