<?php
/*
NO.TASK282(guoping.zhang@2010.12.02）
仪器送样预约权限设置
*/
class EQ_Sample_Access {

	/*
	修改仪器送样设置/添加送样请求/列表送样请求
	$object为equipment对象
	*/
	static function equipment_ACL($e, $user, $perm, $equipment, $params) {
		if (!$equipment->id) return;
		if ($equipment->status == EQ_Status_Model::NO_LONGER_IN_SERVICE) return;
		
		switch ($perm) {
        case '导出送样记录':
			if ($user->access('修改所有仪器的送样设置')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->group->id && $user->access('修改下属机构仪器的送样设置') && $user->group->is_itself_or_ancestor_of($equipment->group)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->access('修改负责仪器的送样设置') && Equipments::user_is_eq_incharge($user, $equipment)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '查看送样设置':
			if ($user->access('修改所有仪器的送样设置')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->group->id && $user->access('修改下属机构仪器的送样设置') && $user->group->is_itself_or_ancestor_of($equipment->group)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->access('修改负责仪器的送样设置') && Equipments::user_is_eq_incharge($user, $equipment)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '修改送样设置':
			if ($user->access('修改所有仪器的送样设置')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->group->id && $user->access('修改下属机构仪器的送样设置') && $user->group->is_itself_or_ancestor_of($equipment->group)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if (!$equipment->sample_lock && $user->access('修改负责仪器的送样设置') && Equipments::user_is_eq_incharge($user, $equipment)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '添加送样请求':
			if ($equipment->accept_sample == TRUE) {
				/*
					在仪器打开了接受送样预约之后，被添加到黑名单的普通用户是无法进行预约的。
				*/
				$admin = $user->is_allowed_to('修改送样设置', $equipment);

				if ($admin) {
					$e->return_value = TRUE;
					return FALSE;
				}

				if (!$user->is_allowed_to('修改公告', $equipment) && Event::trigger('enable.announcemente', $equipment, $user) ) {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_sample', '您需要阅读该仪器公告才可以申请送样!'));
					$e->return_value = FALSE;
					return FALSE;
				}

				if (Module::is_installed('eq_ban')) {
					//如果用户在黑名单中，则不允许进行相关操作
					if (EQ_Ban::is_user_banned($user, $equipment)) {
						Lab::message('sample',I18N::T('eq_sample', '您已被加入黑名单, 目前无法对该仪器申请送样!'));
						$e->return_value = FALSE;
						return FALSE;
					}
				}

				if ($equipment->status != EQ_Status_Model::IN_SERVICE) {
					Lab::message('sample',I18N::T('eq_sample', '仪器暂时故障，不可送样预约!'));
					$e->return_value = FALSE;
					return FALSE;
				}
				if (Module::is_installed('billing') && $equipment->charge_template['sample']) {
					// 获取送样申请人本实验室在仪器收费中心的账户
					$department = $equipment->billing_dept;
					$department = Billing_Department::get($department->id);
					$lab = $sample->lab;

					//该仪器无财务部门
					if (!$department->id) {
						Lab::message('sample',I18N::T('eq_sample', '该仪器暂无财务部门管理, 您目前无法申请送样!'));
						$e->return_value = FALSE;
						return FALSE;
					}

					//送样申请者无所属实验室
					if (!$lab->id && !Q("$user lab")->total_count()) {
						Lab::message('sample',I18N::T('eq_sample', '您没有在任何实验室, 目前无法申请送样!'));
						$e->return_value = FALSE;
						return FALSE;
					}

					if (!Q("$user lab billing_account[department={$department}]")->total_count()) {
						Lab::message('sample',I18N::T('eq_sample', '您的实验室在该仪器所属的财务部门还没有账户, 目前无法申请送样!'));
						$e->return_value = FALSE;
						return FALSE;
					}
				}

				$lab = $sample->lab;
				//送样申请者无所属实验室
				if (!$lab->id && !Q("$user lab")->total_count()) {
					Lab::message('sample',I18N::T('eq_sample', '您没有在任何实验室, 目前无法申请送样!'));
					$e->return_value = FALSE;
					return FALSE;
				}

				if (!$equipment->cannot_be_sampled($user, $sample)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				
				$e->return_value = FALSE;
                return FALSE;
			}
			break;
		case '列表送样请求':
			if ($equipment->accept_sample == FALSE) return;
			$e->return_value = TRUE;
			return FALSE;			
		case '锁定送样':
			if ($user->access('添加/修改所有机构的仪器')) {
				$e->return_value = TRUE;
				return FALSE;
			}	
			break;
        case '添加送样记录':
            if ($user->access('修改所有仪器的送样设置')) {
                $e->return_value = TRUE;
                return FALSE;
            }
            if ($user->group->id && $user->access('修改下属机构仪器的送样设置') && $user->group->is_itself_or_ancestor_of($equipment->group)) {
                $e->return_value = TRUE;
                return FALSE;
            }
            if ($user->access('修改负责仪器的送样设置') && Equipments::user_is_eq_incharge($user, $equipment)) {
                $e->return_value = TRUE;
                return FALSE;
            }
            break;
         case '查看所有送样记录':
         	if ($user->access('修改所有仪器的送样')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->group->id && $user->access('修改下属机构仪器的送样') && $user->group->is_itself_or_ancestor_of($equipment->group)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->access('修改负责仪器的送样') && Equipments::user_is_eq_incharge($user, $equipment)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			//【定制】RQ183708-中科院长春稀土国重实验室-LIMS定制化需求
            try{
			    if(Event::trigger('registration.has_registration_role',$user)){
                    $e->return_value = TRUE;
                    return FALSE;
                }
            }catch (Exception $e){}
            break;
        }	
	}
	/*
	查看/修改/发送消息
		$object为eq_sample对象
	*/
	static function eq_sample_ACL($e, $user, $perm, $object, $params) {
		$equipment = $object->equipment;
		if ($equipment->accept_sample == FALSE) return;
		
		switch ($perm) {
		case '查看':
			$e->return_value = TRUE;
			return FALSE;
        case '发送报告':
        	if ($user->is_allowed_to('管理', $object) && $object->status == EQ_Sample_Model::STATUS_TESTED) {
                $e->return_value = TRUE;
                return FALSE;
            }
            break;
        case '发送消息':
        case '管理':
            if ($user->access('修改所有仪器的送样')) {
                $e->return_value = TRUE;
                return FALSE;
            }
            if ($user->group->id && $user->access('修改下属机构仪器的送样') && $user->group->is_itself_or_ancestor_of($equipment->group)) {
                $e->return_value = TRUE;
                return FALSE;
            }
            if ($user->access('修改负责仪器的送样') && Equipments::user_is_eq_incharge($user, $equipment)) {
                $e->return_value = TRUE;
                return FALSE;
            }
            break;
		case '修改':
		case '删除':
			if ($object->is_locked()) {
				$e->return_value = FALSE;
				return FALSE;
			}

            if ($user->is_allowed_to('管理', $object)) {
                $e->return_value = TRUE;
                return FALSE;
            }

            //如果当前时间超过最晚
            if(!EQ_Sample::check_modify_time($object,$equipment)){
                $e->return_value = false;
                return false;
            }

            if ($user->id == $object->sender->id && $object->status == EQ_Sample_Model::STATUS_APPLIED) {
                $e->return_value = TRUE;
                return FALSE;
            }
            break;
    	}
	}

	static function eq_sample_attachments_ACL($e, $user, $perm, $object, $options) {
		/* if (!$object->id) {		/\* 仅已存在的送样可以有附件 *\/
		 * 	return;
		 * } */

		if ($options['type'] != 'attachments') return;

		switch ($perm) {
		case "列表文件":
		case "下载文件":
			if( $user->id == $object->sender->id /* 送样人能够在查看送样记录的时候下载附件 */
			||  $user->access('下载实验室成员仪器使用送样记录附件') && Q("({$object->sender}, $user) lab")->total_count()) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case "上传文件":
		case "修改文件":
		case "删除文件":
			break;
		default:
			return;				/* 到这儿说明是$perm有误 */
		}

		if ($object->id && $object->status == EQ_Sample_Model::STATUS_APPLIED && $user->id == $object->sender->id) {
			$e->return_value = TRUE;
			return FALSE;
		}

		if (!$object->id) {
			$e->return_value = TRUE;
			return FALSE;
		}
		
		if (Equipments::user_is_eq_incharge($user, $object->equipment) ||
			$user->access('管理所有内容') || $user->access('修改所有仪器的送样')) {
			/* 机主或有全选的用户能够在添加和编辑送样记录的时候上传附件 */
			$e->return_value = TRUE;
			return FALSE;
		}
    }

    static function user_ACL($e, $user, $perm, $object, $params) {
         /*$user为$me,$object是查看的user对象*/
        switch($perm) {
            case '列表个人页面送样预约':
            case '导出个人页面送样预约':
                if ($user->id == $object->id) {
                    $e->return_value = TRUE;
                    return FALSE;
                }

                if (Q("($object, $user) lab")->total_count() && $user->access('查看本实验室成员送样记录')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }

                if (Q("($object, $user<pi) lab")->total_count() && $user->access('查看负责实验室成员送样记录')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }

                if ($user->access('查看所有实验室的成员送样记录')) {
                	$e->return_value = TRUE;
                    return FALSE;
                }

                if ($user->access('查看下属实验室的成员送样记录') && $user->group->is_itself_or_ancestor_of($object->group)){
                    $e->return_value = TRUE;
                    return FALSE;
                }

                $selector = "{$user}<incharge equipment eq_sample[sender={$object}]";
                if (Q($selector)->total_count()) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            default:
                return FALSE;
        }
    }

    static function lab_ACL($e, $me, $perm, $object, $options) {
        switch($perm) {
        case '列表仪器送样' :
            if ((Q("$me $object")->total_count() && $me->access('查看本实验室成员送样记录'))
                || (Q("$me<pi $object")->total_count() && $me->access('查看负责实验室成员送样记录'))
                || $me->access('管理所有内容')
                || $me->access('查看所有实验室的成员送样记录')
                || $me->access('查看下属实验室的成员送样记录') && $me->group->is_itself_or_ancestor_of($object->group)
            ) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
            break;
        }
    }

    static function calendar_ACL($e, $user, $perm, $calendar, $options) {

        if ($calendar->type != 'eq_sample' || !$calendar->id) return;

        switch($perm) {
            case '列表事件':
                $e->return_value = TRUE;
                break;
            case '添加事件':
            case '修改事件':
            case '添加重复规则':
                $e->return_value = FALSE;
                break;
            default:
        }
    }

    static function component_ACL($e, $user, $perm, $component, $options) {

        if ($component->calendar->type != 'eq_sample' || !$component->calendar->id) return;

        switch($perm) {
            case '查看':
            case '添加':
            case '删除':
            case '修改':
            default:
                $e->return_value = FALSE;
                return FALSE;
        }
    }
}
