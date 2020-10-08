<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.archive.str.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\archive;

use boa\boa;
use boa\msg;

class str{
	private $cfg = [
		'type' => 'zlib', //raw, zlib, deflate, lzf, bzip2
		'level' => -1 //0-9
	];

	public function __construct($cfg = []){
        if($cfg){
			$this->cfg = array_merge($this->cfg, $cfg);
		}
	}

	public function cfg($k = null, $v = null){
		switch(true){
			case $k === null && $v === null:
				return $this->cfg;
				break;

			case $v === null:
				return $this->cfg[$k];
				break;

			default:
				$this->cfg[$k] = $v;
				return $this;
		}
	}

	public function encode($data, $type = null){
		if($type){
			$this->cfg['type'] = $type;
		}

		$coding = $this->coding();
		switch($coding){
			case 'lzf':
				if(function_exists('lzf_compress')){
					$res = lzf_compress($data);
				}else{
					msg::set('boa.error.6', 'lzf_compress');
				}
				break;

			case 'bzip2':
				if(function_exists('bzcompress')){
					$level = $this->bzip_level();
					$res = bzcompress($data, $level);
					if(is_int($res)){
						$res = false;
					}
				}else{
					msg::set('boa.error.6', 'bzcompress');
				}
				break;

			default:
				$res = zlib_encode($data, $coding, $this->cfg['level']);
		}

		if($res === false){
			msg::set('boa.error.151', $this->cfg['type']);
		}
        return $res;
    }

	public function decode($data, $type = null){
		if($type){
			$this->cfg['type'] = $type;
		}

		$coding = $this->coding();
		switch($coding){
			case 'lzf':
				$res = lzf_decompress($data);
				break;

			case 'bzip2':
				$res = bzdecompress($data);
				if(is_int($res)){
					$res = false;
				}
				break;

			default:
				$res = zlib_decode($data);
		}

		if($res === false){
			msg::set('boa.error.152', $this->cfg['type']);
		}
        return $res;
    }

	private function coding(){
		switch($this->cfg['type']){
			case 'raw':
				$res = ZLIB_ENCODING_RAW;
				break;

			case 'zlib':
				$res = ZLIB_ENCODING_GZIP;
				break;

			case 'deflate':
				$res = ZLIB_ENCODING_DEFLATE;
				break;

			default:
				$res = $this->cfg['type'];
		}
		return $res;
	}

	private function bzip_level(){
		if($this->cfg['level'] <= 0){
			$res = 4;
		}else{
			$res = $this->cfg['level'];
		}
		return $res;
	}
}
?>