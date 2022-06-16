<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/api/boa.view.page.html
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

	public function get($page, $number = 10, $first = true, $last = true, $prev = false, $next = false){
		if($page['current'] < 1){
			$page['current'] = 1;
		}
		if($page['current'] > $page['pages']){
			$page['current'] = $page['pages'];
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
			$var['page'] = $page['current'] > 1 ? $page['current'] - 1 : 1;
			$url = $router->url($act, $var);
			$str .= $this->tpl('first', boa::lang('boa.system.page_prev'), $url);
		}

		if($number > 1){
			$half = floor($number / 2);
			$min = max(1, $page['current'] - $half);
			$max = min($page['pages'], $min + $number - 1);
			if($max - $min < $number){
				$min = max(1, $max - $number + 1);
			}

			for($i = $min; $i <= $max; $i++){
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
			$var['page'] = $page['current'] < $page['pages'] ? $page['current'] + 1 : $page['pages'];
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