<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.json.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa;

class json extends base{
	protected $cfg = [
		'en_option' => 0,
		'en_depth' => 0,
		'de_option' => 0,
		'de_depth' => 512,
		'de_assoc' => true
	];

	public function encode($val){
		if($this->cfg['en_depth'] > 0){
			$res = json_encode($val, $this->cfg['en_option'], $this->cfg['en_depth']);
		}else{
			$res = json_encode($val, $this->cfg['en_option']);
		}

		$errno = json_last_error();
		if($res === false || $errno){
			$error = $this->error($errno);
			msg::set('boa.error.71', $error);
		}

		return $res;
	}

	public function decode($val){
		$res = json_decode($val, $this->cfg['de_assoc'], $this->cfg['de_depth'], $this->cfg['de_option']);

		$errno = json_last_error();
		if($errno){
			$error = $this->error($errno);
			msg::set('boa.error.72', $error);
		}

		return $res;
	}

	private function error($errno){
		switch($errno){
			case JSON_ERROR_DEPTH:
				$str = 'JSON_ERROR_DEPTH';
				break;

			case JSON_ERROR_STATE_MISMATCH:
				$str = 'JSON_ERROR_STATE_MISMATCH';
				break;

			case JSON_ERROR_CTRL_CHAR:
				$str = 'JSON_ERROR_CTRL_CHAR';
				break;

			case JSON_ERROR_SYNTAX:
				$str = 'JSON_ERROR_SYNTAX';
				break;

			case JSON_ERROR_UTF8:
				$str = 'JSON_ERROR_UTF8';
				break;

			case JSON_ERROR_RECURSION:
				$str = 'JSON_ERROR_RECURSION';
				break;

			case JSON_ERROR_INF_OR_NAN:
				$str = 'JSON_ERROR_INF_OR_NAN';
				break;

			case JSON_ERROR_UNSUPPORTED_TYPE:
				$str = 'JSON_ERROR_UNSUPPORTED_TYPE';
				break;

			case JSON_ERROR_INVALID_PROPERTY_NAME:
				$str = 'JSON_ERROR_INVALID_PROPERTY_NAME';
				break;

			case JSON_ERROR_UTF16:
				$str = 'JSON_ERROR_UTF16';
				break;

			default:
				$str = 'UNKNOWN';
		}

		if(function_exists('json_last_error_msg')){
			$str = "[$str]". json_last_error_msg();
		}

		return $str;
	}
}
?>