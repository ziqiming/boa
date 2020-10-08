<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.view.page.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa\view;

use boa\boa;
use boa\base;

class page extends base{
	protected $cfg = [
		'first'   => '<li class="first"><a href="@">#</a></li>',
		'prev'    => '<li class="prev"><a href="@">#</a></li>',
		'page'    => '<li><a href="@">#</a></li>',
		'current' => '<li class="current"><i>#</i></li>',
		'next'    => '<li class="next"><a href="@">#</a></li>',
		'last'    => '<li class="last"><a href="@">#</a></li>',
		'pages'   => '<ul class="pages">#</ul>'
	];

	public function __construct($cfg = []){
		if(defined('PAGE')){
			$cfg = array_merge(unserialize(PAGE), $cfg);
		}
		parent::__construct($cfg);
	}

	public function get($page, $number = 5, $first = true, $last = true, $prev = false, $next = false){
		if($page['current'] < 1){
			$page['current'] = 1;
		}
		if($page['current'] > $page['pages']){
			$page['current'] = $page['pages'];
		}

		switch(true){
			case $page['current'] == 1:
				$first = false;
				$prev = false;
				$next = $next && true;
				$last = $last && true;
				break;

			case $page['current'] > 1 && $page['current'] < $page['pages']:
				$first = $first && true;
				$prev = $prev && true;
				$next = $next && true;
				$last = $last && true;
				break;

			case $page['current'] == $page['pages']:
				$first = $first && true;
				$prev = $prev && true;
				$next = false;
				$last = false;
				break;
		}

		$str = '';
		$act = boa::env('mod') .'.'. boa::env('con') .'.'. boa::env('act');
		$var = boa::env('var');
		$router = boa::router();

		if($first){
			$var['page'] = 1;
			$url = $router->url($act, $var);
			$str .= $this->tpl('first', boa::lang('boa.system.page_first'), $url);
		}

		if($prev){
			$var['page'] = $page['current'] - 1;
			$url = $router->url($act, $var);
			$str .= $this->tpl('first', boa::lang('boa.system.page_prev'), $url);
		}

		if($number > 0){
			$min = $page['current'] - $number;
			$max = $page['current'] + $number;
			$page_min = $min <= 0 ? 1 : $min;
			$page_max = $max <= $page['pages'] ? $max : $page['pages'];

			for($i = $page_min; $i <= $page_max; $i++){
				if($i == $page['current']){
					$str .= $this->tpl('current', $i);
				}else{
					$var['page'] = $i;
					$url = $router->url($act, $var);
					$str .= $this->tpl('page', $i, $url);
				}
			}
		}else{
			$str .= $this->tpl('current', $page['current']);
		}

		if($next){
			$var['page'] = $page['current'] + 1;
			$url = $router->url($act, $var);
			$str .= $this->tpl('next', boa::lang('boa.system.page_next'), $url);
		}

		if($last){
			$var['page'] = $page['pages'];
			$url = $router->url($act, $var);
			$str .= $this->tpl('last', boa::lang('boa.system.page_last'), $url);
		}

		$str = $this->tpl('pages', $str);
		return $str;
	}

	private function tpl($tpl, $str, $url = ''){
		$tpl = $this->cfg[$tpl];
		$tpl = str_replace('#', $str, $tpl);
		$tpl = str_replace('@', $url, $tpl);
		return $tpl;
	}
}
?>