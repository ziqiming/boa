<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/api/boa.security.xss.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\security;

use boa\base;

class xss extends base{
	protected $cfg = [
		'tags' => ['script', 'base'],
		'events' => ['on*']
	];

	function filter($str){
		$tag = implode('|', $this->cfg['tags']);
		$str = preg_replace("/<($tag)[^>]*>([\s\S]*?)<\/($tag)>/i", '', $str);
		$str = preg_replace("/<($tag)[^>]*>/i", '', $str);

		$event = implode('|', $this->cfg['events']);
		$event = str_replace('*', '[a-z]+', $event);
		$str = preg_replace("/<([^>]+?)\s+($event)\s*=\s*(\"|')([\s\S]+?)(\"|')(\s+|>)/i", '<$1 $6', $str);

		return $str;
	}
}
?>