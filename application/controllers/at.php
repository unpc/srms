<?php
class AT_AJAX_Controller extends AJAX_Controller {
	function index_at_users_get() {
		$form = input::form();
		$ar_str = $form['at_str'];
		if ($ar_str) {
			$repeat_users = Q("user[name*={$ar_str}|name_abbr*={$ar_str}]");
			$users = Q("user[name*={$ar_str}|name_abbr*={$ar_str}][!hidden][atime>0]:limit(10)");

			$user_names = $repeat_users->to_assoc('id', 'name');	

			// 获取去掉重复数据的数组  
		    $unique_names = array_unique ($user_names);  
		    // 获取重复数据的数组  
		    $repeat_names = array_diff_assoc ($user_names, $unique_names);  
			
			//剩余用户
			$rest = $users->total_count() - $users->length();
		}
		else {
			$users = Q("user[!hidden][atime>0]:limit(10)");

			if($users->length()){
				foreach ($users as $user) {
					//检测显示出来的用户，是否跟系统中的用户有重名
					$repeat_users = Q("user[name={$user->name}]");

					//如果数据库中姓名相同的用户在2个以上
					if($repeat_users->length() > 1) $repeat_names[] = $user->name;
				}
			}
		}

		if ($users->total_count()) {
			

			$content = (string) V('at/users', ['users'=>$users, 'repeat_names'=> $repeat_names, 'rest'=>$rest]);

		}
		else {
			$content = (string) V('at/empty');
		}
		Output::$AJAX['at_users'] = $content;

	}
}
