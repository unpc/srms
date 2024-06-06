<?php
/*
NO.TASK#274(guoping.zhang@2010.11.26)
成果管理模块应用权限判断新规则
*/
class Achievements_Access {
	/*
	操作论文信息时：
		删除/修改/查看：$objejct为pulication对象；
		列表论文/查看论文：$object为lab对像或字符串
	操作专利信息时：
		删除/修改/查看：$objejct为patent对象；
		列表专利/查看专利：$object为lab对像或字符串
	操作论文信息时：
		删除/修改/查看：$objejct为award对象；
		列表获奖/查看获奖：$object为lab对像或字符串
	*/
	static function operate_achievements_is_allowed($e, $user, $perm, $object, $options) {
		$multi_lab = $GLOBALS['preload']['people.multi_lab'];
		switch ($perm) {
		case '列表成果':
			if (!$GLOBALS['preload']['people.multi_lab']) {
				$e->return_value = TRUE;
				return FALSE;
			}
			
			if ($user->access('查看所有实验室成果')
			|| $user->access('查看负责实验室成果')
			|| $user->access('查看所属实验室成果')
			|| $user->access('查看下属机构仪器的关联成果')
			) {
				$e->return_value = TRUE;
				return FALSE;
			}
			
			break;
		case '查看成果':
			if ($user->access('查看所有实验室成果')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->access('查看负责实验室成果') && (Q("$user<pi $object")->total_count() || !$object->id)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if (!$multi_lab) {
				if ($user->access('查看本实验室成果') && (Q("$user $object")->total_count() || !$object->id)) {
					$e->return_value = TRUE;
					return FALSE;
				}
			}
			else {
				if ($user->access('查看所属实验室成果') && (Q("$user $object")->total_count() || !$object->id)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if ($user->access('查看下属机构仪器的关联成果') && (Q("{$user->group} $object")->total_count() || !$object->id)) {
					$e->return_value = TRUE;
					return FALSE;
				}
			}
			break;
		case '添加成果':
			if ($user->access('添加/修改所有实验室成果')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if (!$multi_lab) {
				if ($user->access('添加/修改本实验室成果') && (Q("$user $object")->total_count() || !$object->id)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if ($user->access('添加/修改本实验室成果') && (Q("$user<pi $object")->total_count() || !$object->id)) {
					$e->return_value = TRUE;
					return FALSE;
				}
			}
			else {
				if ($user->access('添加/修改负责实验室成果') && (Q("$user<pi $object")->total_count() || !$object->id)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if ($user->access('添加/修改所属实验室成果') && (Q("$user->group $object")->total_count() || !$object->id)) {
					$e->return_value = TRUE;
					return FALSE;
				}
			}
            //$is_pi = Q("$user<pi lab")->total_count();
	   //		if (!$is_pi) {
                //$e->return_value = TRUE;
                //return FALSE;
            //}
			break;
        case '导入成果':
            if (($user->access('管理所有内容') || $user->access('添加/修改所有实验室成果')) && !Module::is_installed('uno')) { // uno下隐藏批量导入
                $e->return_value = TRUE;
                return FALSE;
            }
            break;
		case '上传文件':	
		case '修改文件':	
		case '删除文件':
		case '修改':
		case '删除':
			if ($user->access('添加/修改所有实验室成果')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if (!$multi_lab) {
				if ($user->access('添加/修改本实验室成果') && (Q("($user, $object) lab")->total_count() || !$object->id)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if ($user->access('添加/修改本实验室成果') && (Q("($user<pi, $object) lab")->total_count() || !$object->id)) {
					$e->return_value = TRUE;
					return FALSE;
				}
			}
			else {
				if ($user->access('添加/修改负责实验室成果') && (Q("($user<pi, $object) lab")->total_count() || !$object->id)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if ($user->access('添加/修改所属实验室成果') && (Q("($user, $object) lab")->total_count() || !$object->id)) {
					$e->return_value = TRUE;
					return FALSE;
				}
			}
			break;
		case '列表文件':
		case '下载文件':
		case '查看':
			if ($user->access('查看所有实验室成果')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if (!$multi_lab) {
				if ($user->access('查看本实验室成果') && (Q("($user, $object) lab")->total_count() || !$object->id)) {
					$e->return_value = TRUE;
					return FALSE;
				}
			}
			else {
				if ($user->access('查看所属实验室成果') && (Q("($user, $object) lab")->total_count() || !$object->id)) {
					$e->return_value = TRUE;
					return FALSE;
				}
			
				if ($user->access('查看负责实验室成果') && (Q("($user<pi, $object) lab")->total_count() || !$object->id)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if ($user->access('查看下属机构仪器的关联成果') && (Q("($user->group equipment) $object")->total_count() || !$object->id)) {
					$e->return_value = TRUE;
					return FALSE;
				}
			}
			break;
		case '查看成果实验室':
			if (!$multi_lab) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->access('查看所有实验室成果')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->access('查看下属机构仪器的关联成果')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		}
	}

	static function is_accessible($e, $name) {
        $me = L('ME');
		if ($GLOBALS['preload']['people.multi_lab']) {
			if ($me->is_allowed_to('列表成果', O('lab'))) {
				$e->return_value = TRUE;
				return FALSE;
			}
			else {
				$e->return_value = FALSE;
				return FALSE;
			}
		}
		else {
            if ($me->is_active())
			    $e->return_value = TRUE;
            else
                $e->return_value = FALSE;
			return TRUE;
		}
	}
}
