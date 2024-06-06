<?php
/*
 NO.TASK#274(guoping.zhang@2010.11.25)
 订单管理权限判断新规则
 */
class Order_Access{

	static function order_ACL($e, $me, $perm, $object, $options) {

		if ($me->access('管理订单和供应商') || $me->token == Lab::get('lab.pi')) { // 修改/取消/删除
			$e->return_value = TRUE;
			return FALSE;
		}

		switch($perm) {
		case '列表':
		case '查看':
			$e->return_value = TRUE;
			return FALSE;
			break;
		case '添加申购':
			if ($me->access('添加申购')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '确认':
			if ($me->access('确认订单')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '订出':
			if ($me->access('订出订单')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '收货':
			if ($me->access('确认收货')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '管理订单':
			if ($me->access('管理订单和供应商')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '编辑订单标签':
			if ($me->access('编辑订单标签')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '导出':
			if ($me->access('导出订单')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '导入':
			if ($me->access('导入订单')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '列表关注':
		case '列表关注的订单':

			if (!$object->id) return;

			if (!$object->get_follows_count('*')) return;

			if ($me->id == $object->id) {

				$e->return_value = TRUE;
				return FALSE;
			}

			if ($me->access('查看其他用户关注的订单') && $object->get_follows_count('order') > 0) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
        case '取消':
            if (($me->access('确认订单') && $object->status == Order_Model::REQUESTING ) || 
            	($me->access('订出订单') && $object->status == Order_Model::READY_TO_ORDER) ||
            	$me->id == $object->requester->id) {
                $e->return_value = TRUE;
                return FALSE;
            }
            break;
        default:
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
		case '列表关注的订单':
			if ($user->access('查看其他用户关注的订单') && $object->get_follows_count('order') > 0) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		}
	}

	/*
	  操作营销商权限设置，$object为dealer对象
	*/
	static function operate_dealer_is_allowed($e, $user, $perm, $object, $options) {
		switch($perm) {
		case '查看':
			if ($object->id > 0 && $user->access('查看所有供应商信息')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '修改':
			if ($object->id > 0 && $user->access('修改所有供应商信息')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '添加':
			if ($user->access('添加所有供应商信息')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '列表':
			if ($user->access('列表所有供应商信息')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		}
	}

	static function order_comment_ACL($e, $user, $perm, $object, $options){
		if ($user->access('管理所有项目')) {
			$e->return_value = TRUE;
			return FALSE;
		}
		if ($user->is_allowed_to('查看', $object)) {
			$e->return_value = TRUE;
			return FALSE;
		}

	}
}
