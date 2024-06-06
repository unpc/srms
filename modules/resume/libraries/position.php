<?php

class Position {

	static function position_ACL($e, $user, $perm, $object, $option) {
		switch($perm){
		case '查看':
			if( $user->access('查看职位') ){
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '添加':
		case '修改':
			if( $user->access('添加/修改职位') ){
				$e->return_value = TRUE;
				return FALSE;
			}
		}
	}
}
