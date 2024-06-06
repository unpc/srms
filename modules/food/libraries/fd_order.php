<?php

class Fd_Order {

	static function operate_fd_order_is_allowed($e, $user, $perm, $fd_order, $params ) {
		switch ($perm) {
		case '查看' :
			if ($user->access('查看订单记录')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '删除' :
			if ($user->id == $fd_order->user->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
		case '添加' :
		case '修改' :
		default:
			if ($user->access('添加/修改订餐记录')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;	
		}
	}

}
