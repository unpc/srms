<?php
/*
NO.TASK#274(guoping.zhang@2010.11.25)
订单管理权限判断新规则
*/
class Stock_Access{

	static function stock_ACL($e, $me, $perm, $object, $options) {
		if ($me->access('管理存货')) {
			$e->return_value = TRUE;
			return FALSE;
		}

		switch($perm) {
		case '列表':
		case '查看':
			$e->return_value = TRUE;
			return FALSE;
			break;
		case '领用/归还':
			if ($me->access('领用/归还') ) {
				$e->return_value = TRUE;
				return FALSE;
			}
            break;
		case '代人领用/归还':
			if ($me->access('代人领用/归还') ) {
				$e->return_value = TRUE;
				return FALSE;
			}
            break;
         case '导出':
         	$e->return_value = TRUE;
         	return false;
         	break;
		}
	}

	/*
	  操作其他用户关注的订单的权限设置，$object为user对象
	*/
	static function operate_follow_is_allowed($e, $user, $perm, $object, $options) {
		if (!$object->id) return;

		if ($perm == '关注' || $perm == '取消关注') {
			$e->return_value = TRUE;
			return FALSE;
		}
		
		if (!$object->get_follows_count('*')) return;

		if ($user->id == $object->id) {
			$e->return_value = TRUE;
			return FALSE;
		}
		switch ($perm) {
		case '列表关注':
		case '列表关注的存货':
			if ($user->access('查看其他用户关注的存货') && $object->get_follows_count('stock') > 0) {
				$e->return_value = TRUE;
				return FALSE;
			}
		}
	}
}
