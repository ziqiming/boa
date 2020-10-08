<?php
/*
Author  : poetbi (poetbi@163.com)
Document: http://boasoft.top/doc/#api/boa.permission.html
Licenses: Apache-2.0 (http://apache.org/licenses/LICENSE-2.0)
*/
namespace boa;

class permission{
	public function validate($group = ''){
		$res = $this->check($group);
		if(!$res){
			$mod = boa::env('mod');
			$con = boa::env('con');
			$act = boa::env('act');
			msg::set('boa.error.21', "$mod.$con.$act");
		}
    }

	public function check($group = ''){
		$perms = boa::cache()->get('permission', ['group' => $group]);

		$mod = boa::env('mod');
		$con = boa::env('con');
		$act = boa::env('act');
		$perm = "$mod.$con.$act";

		$res = false;
		if($perms['allow']){
			foreach($perms['allow'] as $v){
				$reg = preg_quote($v);
				$reg = str_replace('\*', '(\w+)', $reg);
				if(preg_match("/^$reg$/", $perm)){
					$res = true;
					break;
				}
			}
		}

		if($res && $perms['deny']){
			foreach($perms['deny'] as $v){
				$reg = preg_quote($v);
				$reg = str_replace('\*', '(\w+)', $reg);
				if(preg_match("/^$reg$/", $perm)){
					$res = false;
					break;
				}
			}
		}

        return $res;
    }
}
?>