<?php 

class Vote {

	static function vote_ACL($e, $user, $perm_name, $vote, $opt) {
		
		switch($perm_name) {
			case '创建':
				if ($user->access('创建活动')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;	
		}
	}
}