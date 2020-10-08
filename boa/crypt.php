<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.crypt.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa;

class crypt extends base{
	protected $cfg = [
		'cipher' => 'aes-128-cbc',
		'key' => '',
		'options' => 0,
		'iv' => null,
		'tag' => '',
		'aad' => '',
		'public_key' => '',  //BS_VAR .'crypt/rsa_public.pem'
		'private_key' => '', //BS_VAR .'crypt/rsa_private.pem'
		'private_pass' => '',
		'sign_alg' => 'sha1'
	];

	public function __construct($cfg = []){
        parent::__construct($cfg);

		$crypt = BS_VAR .'crypt/';
		if(!$this->cfg['public_key']){
			$this->cfg['public_key'] = $crypt .'rsa_public.pem';
		}
		if(!$this->cfg['private_key']){
			$this->cfg['private_key'] = $crypt .'rsa_private.pem';
		}
	}

	public function enc($data, $cipher = null){
		if($cipher === null){
			$cipher = $this->cfg['cipher'];
		}

		if(in_array($cipher, openssl_get_cipher_methods())){
			$iv = $this->get_iv($cipher);
			if($this->cfg['tag'] || $this->cfg['aad']){
				$data = openssl_encrypt($data, $cipher, $this->cfg['key'], $this->cfg['options'], $iv, $this->cfg['tag'], $this->cfg['aad']);
			}else{
				$data = openssl_encrypt($data, $cipher, $this->cfg['key'], $this->cfg['options'], $iv);
			}
		}else{
			msg::set('boa.error.141', $cipher);
		}
		return $data;
	}

	public function dec($data, $cipher = null){
		if($cipher === null){
			$cipher = $this->cfg['cipher'];
		}

		if(in_array($cipher, openssl_get_cipher_methods())){
			$iv = $this->get_iv($cipher);
			if($this->cfg['tag'] || $this->cfg['aad']){
				$data = openssl_decrypt($data, $cipher, $this->cfg['key'], $this->cfg['options'], $iv, $this->cfg['tag'], $this->cfg['aad']);
			}else{
				$data = openssl_decrypt($data, $cipher, $this->cfg['key'], $this->cfg['options'], $iv);
			}
		}else{
			msg::set('boa.error.141', $cipher);
		}
		return $data;
	}

	public function public_enc($data, $padding = OPENSSL_PKCS1_PADDING){
		$key = $this->get_public_key();
		$res = openssl_public_encrypt($data, $result, $key, $padding);

		if($res === true){
			return $result;
		}else{
			msg::set('boa.error.143');
		}
	}

	public function public_dec($data, $padding = OPENSSL_PKCS1_PADDING){
		$key = $this->get_public_key();
		$res = openssl_public_decrypt($data, $result, $key, $padding);

		if($res === true){
			return $result;
		}else{
			msg::set('boa.error.144');
		}
	}

	public function private_enc($data, $padding = OPENSSL_PKCS1_PADDING){
		$key = $this->get_private_key();
		$res = openssl_private_encrypt($data, $result, $key, $padding);

		if($res === true){
			return $result;
		}else{
			msg::set('boa.error.145');
		}
	}

	public function private_dec($data, $padding = OPENSSL_PKCS1_PADDING){
		$key = $this->get_private_key();
		$res = openssl_private_decrypt($data, $result, $key, $padding);

		if($res === true){
			return $result;
		}else{
			msg::set('boa.error.146');
		}
	}

	public function sign($data, $sign_alg = null){
		if($sign_alg === null){
			$sign_alg = $this->cfg['sign_alg'];
		}

		if(in_array($sign_alg, openssl_get_md_methods())){
			$key = $this->get_private_key();
			$res = openssl_sign($data, $result, $key, $sign_alg);
		}else{
			msg::set('boa.error.142', $sign_alg);
		}

		if($res === true){
			return $result;
		}else{
			msg::set('boa.error.147');
		}
	}

	public function verify($data, $sign, $sign_alg = null){
		if($sign_alg === null){
			$sign_alg = $this->cfg['sign_alg'];
		}

		if(in_array($sign_alg, openssl_get_md_methods())){
			$key = $this->get_public_key();
			$res = openssl_verify($data, $sign, $key, $sign_alg);
		}else{
			msg::set('boa.error.142', $sign_alg);
		}

		if($res == -1){
			msg::set('boa.error.148');
		}else{
			return $res;
		}
	}

	private function get_iv($cipher){
		if($this->cfg['iv'] === null){
			$ivlen = openssl_cipher_iv_length($cipher);
			$this->cfg['iv'] = openssl_random_pseudo_bytes($ivlen);
		}
		return $this->cfg['iv'];
	}

	private function get_public_key(){
		if(file_exists($this->cfg['public_key'])){
			return openssl_pkey_get_public($this->cfg['public_key']);
		}else{
			msg::set('boa.error.2', $this->cfg['public_key']);
		}
	}

	private function get_private_key(){
		if(file_exists($this->cfg['private_key'])){
			return openssl_pkey_get_private($this->cfg['private_key'], $this->cfg['private_pass']);
		}else{
			msg::set('boa.error.2', $this->cfg['private_key']);
		}
	}
}
?>