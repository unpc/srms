<?php
/*
NO.TASK#265（guoping.zhang@2010.11.22)
EQ_Reserv模块新权限，绑定
*/
class EQ_Reserv_Access {
	/*
	为仪器添加预约或非预约时段
		$component为cal_component对象
	*/
	static function add_is_allowed($e, $user, $perm_name, $component, $options) {

        $calendar = $component->calendar;

        try {
            //没有设定calendar->id，不予判断
            if (!$calendar->id) throw new Error_Exception;

            //calendar类型不为eq_reserv下可判断的类型
            if (!($calendar->type == 'eq_reserv' || ($calendar->type == 'eq_incharge' && $calendar->parent->name() == 'user'))) throw new Error_Exception;

            //如果预约被重叠
            if($component->check_overlap($options)) throw new Error_Exception;
        }
        catch(Error_Exception $e) {
            //进行判断，有问题跳出
            return;
        }

		$parent = $component->calendar->parent;

		switch ($parent->name()) {
		case 'user':
			if ($component->calendar->type !== 'eq_incharge') return;
			if ($user->id === $parent->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
			/*
			  NO.BUG#212(xiaopei.li@2010.12.03)
			  删除为别人负责的仪器添加非预约时段的权限
			*/
			break;
		case 'equipment':
            if (Module::is_installed('labs')) {
                if (!Q("$user lab[atime>0]")->total_count()) {
                    $e->return_value = FALSE;
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '您所在实验室未激活!'));
                    return FALSE;
                }
            }
			$equipment = $parent;
            $reserv = O('eq_reserv');
			$reserv->component = $component;

            //如果在锁定时间之前则不能添加和修改
            if($reserv->is_locked()){
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '您添加的时段已被锁定!'));
                $e->return_value = FALSE;
                return FALSE;
            }

            if ($user->access('为所有仪器添加预约')) {
				$e->return_value = TRUE;
                return FALSE;
			}

			if ($user->group->id && $user->access('为下属机构仪器添加预约') && $user->group->is_itself_or_ancestor_of($equipment->group)) {
				$e->return_value = TRUE;
                return FALSE;
			}
			
			if ($user->access('为负责仪器添加预约') && Equipments::user_is_eq_incharge($user, $equipment)) {
				$e->return_value = TRUE;
                return FALSE;
			}

			//预约次数&时长限制
            if (Module::is_installed('eq_time_counts')) {
	            $check_time_counts = Event::trigger('eq_time_counts.check_time_counts', $equipment, $component);
	            if ($check_time_counts['allow'] === false) {
	                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_time_counts', $check_time_counts['msg']));
	                $e->return_value = FALSE;
	                return FALSE;
	            }
	        }

            //繁忙阶段不能进行预约
            if (EQ_Reserv::_check_busy_handler($equipment, $component)) {
                return;
            }

            //不在创建预约的有效时间内
            if (!EQ_Reserv::check_create_time($equipment, $component)) {
                return FALSE;
			}
			
			if (Config::get('eq_reserv.single_equipemnt_reserv') && !EQ_Reserv::check_single_time($user, $equipment, $component)) {
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '预约失败：该时段不支持同时预约两台仪器!'));
				$e->return_value = FALSE;
				return FALSE;
			}

            if (Config::get('eq_reserv.single_equipemnt_reserv') && !EQ_Reserv::check_single_time($user, $equipment, $component)) {
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '预约失败：该时段不支持同时预约两台仪器!'));
                $e->return_value = FALSE;
                return FALSE;
            }

            if (!$user->is_allowed_to('修改公告', $equipment) && Event::trigger('enable.announcemente', $equipment, $user) ) {
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '您需要阅读公告方可预约仪器!'));
				$e->return_value = FALSE;
				return FALSE;
            }

            if (!$equipment->cannot_be_reserved($user, $component->dtstart, $component->dtend)) {
                $e->return_value = TRUE;
                return FALSE;
            }
			break;
		}
	}
	/*
	删除仪器预约或非预约时段
		$component为cal_component对象
	*/
	static function delete_is_allowed($e, $user, $perm_name, $component, $options) {

        $calendar = $component->calendar;

        try {
            //没有设定calendar->id，不予判断
            if (!$calendar->id) throw new Error_Exception;

            //没有设定component->id， 不予判断
            if (!$component->id) throw new Error_Exception;

            //calendar类型不为eq_reserv下可判断的类型
            if (!($calendar->type == 'eq_reserv' || ($calendar->type == 'eq_incharge' && $calendar->parent->name() == 'user'))) throw new Error_Exception;
        }
        catch(Error_Exception $e) {
            //进行判断，有问题跳出
            return;
        }

		$parent = $component->calendar->parent;
		switch ($parent->name()) {
		case 'user':
			if ($component->calendar->type !== 'eq_incharge') return;
			if ($user->id == $parent->id && $user->access('删除负责仪器的预约')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->group->id && $user->access('删除下属机构仪器的预约') && $user->group->is_itself_or_ancestor_of($parent->group)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->access('删除所有仪器的预约')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case 'equipment':
			$equipment = $parent;

            $reserv = O('eq_reserv', ['component'=>$component]);
            //如果在锁定时间之前则不能添加和修改
            if($reserv->is_locked()){
                $e->return_value = FALSE;
                return FALSE;
            }
			if ($component->id && Q("eq_reserv[component={$component}]<reserv eq_record[dtend>0]")->total_count()) {
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '该预约似乎关联了已经被使用的记录，因此无法删除！'));
				return;
			}
			if ($user->access('删除所有仪器的预约')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			
			if ($user->group->id && $user->access('删除下属机构仪器的预约') && $user->group->is_itself_or_ancestor_of($parent->group)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			
			if ($user->access('删除负责仪器的预约') && Equipments::user_is_eq_incharge($user, $parent)) {
				$e->return_value = TRUE;
				return FALSE;
			}

			//不在修改预约的有效时间内
            if (!EQ_Reserv::check_delete_time($equipment, $component)) {
                return FALSE;
            }

			/*
			NO.BUG#267(guoping.zhang@2010.12.21)
			普通用户可以删除将来的预约
			*/
			if ($user->id == $component->organizer->id) {
				$e->return_value = TRUE;
				return FALSE;
			}

			break;
		}
	}

	/*
	修改仪器预约或非预约时段
		$component为cal_component对象
	*/
	static function edit_is_allowed($e, $user, $perm_name, $component, $options) {

        $calendar = $component->calendar;

        try {
            //没有设定calendar->id，不予判断
            if (!$calendar->id) throw new Error_Exception;

            //没有设定component->id， 不予判断
            if (!$component->id) throw new Error_Exception;

            //calendar类型不为eq_reserv下可判断的类型
            if (!($calendar->type == 'eq_reserv' || ($calendar->type == 'eq_incharge' && $calendar->parent->name() == 'user'))) throw new Error_Exception;

            //如果预约重叠
            if($component->check_overlap($options)) throw new Error_Exception;
        }
        catch(Error_Exception $e) {
            //进行判断，有问题跳出
            return;
        }

		$parent = $component->calendar->parent;
		switch ($parent->name()) {
		case 'user':
			if ($component->calendar->type !== 'eq_incharge') return;
			if ($user->id == $parent->id && $user->access('修改负责仪器的预约')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->group->id && $user->access('修改下属机构仪器的预约') && $user->group->is_itself_or_ancestor_of($parent->group)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->access('修改所有仪器的预约')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case 'equipment':

            $reserv = O('eq_reserv', ['component'=>$component]);
            //如果在锁定时间之前则不能添加和修改
            if($reserv->is_locked()){
                $e->return_value = FALSE;
                return FALSE;
            }

            $now = Date::time();
            $equipment = $parent;

            //关联了已经被使用的记录,无法更新 已增加测试用例
            //关联了已经被使用的记录，1，不是自己的预约，不能修改。2，超过预约时间，不能修改
			if ($reserv->id && ($user->id != $component->organizer->id || $now >= $component->get('dtend', TRUE))) {
				if (Q("eq_record[reserv={$reserv}][dtend>0]")->total_count() > 0) {
					Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '该预约似乎关联了已经被使用的记录，因此无法更新！'));
					$e->return_value = FALSE;
					return FALSE;
				}
			}

			if ($user->access('修改所有仪器的预约')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			
			if ($user->group->id && $user->access('修改下属机构仪器的预约') && $user->group->is_itself_or_ancestor_of($parent->group)) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ($user->access('修改负责仪器的预约') && Equipments::user_is_eq_incharge($user, $equipment)) {
				$e->return_value = TRUE;
				return FALSE;
			}

			//预约次数&时长限制
            if (Module::is_installed('eq_time_counts')) {
	            $check_time_counts = Event::trigger('eq_time_counts.check_time_counts', $equipment, $component);
	            if ($check_time_counts['allow'] === false) {
	                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_time_counts', $check_time_counts['msg']));
	                $e->return_value = FALSE;
	                return FALSE;
	            }
	        }

			//只能修改自己的预约
			$ori_organizer = $component->get('organizer', TRUE);
			if ($user->id != $ori_organizer->id) {
				Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '您没有权限修改他人预约！'));
				$e->return_value = FALSE;
				return;
			}

			/*
				TASK#1235 任务codereview时发现该方法被添加到了_check_motify_time_limit，
				这样操作可能会影响其他地方判断， 因为该种修正仅仅是在编辑时才会发生的。故移出放在编辑中，挑换了位置，让其能够在检查时间段之前进行。
			*/
			// 已有的生效的预约能够在使用过程中延长
			$ori_dtstart = $component->get('dtstart',TRUE);
			if ($component->dtstart == $ori_dtstart) { /* 开始时间未改变 */
				$ori_dtend = $component->get('dtend', TRUE);
				if ($ori_dtend > $now && $now > $ori_dtstart && $ori_dtend < $component->dtend) { /* 仅延长 */
					$e->return_value = TRUE;
					return FALSE;
				}
			}

            //进行修改的时候
            //需要判断时间问题
            if (
                $component->get('dtstart', TRUE) != $component->dtstart
                ||
                $component->get('dtend', TRUE) != $component->dtend
                ) {

                //不在创建预约的有效时间内
                if (!EQ_Reserv::check_create_time($equipment, $component)) {
                    return FALSE;
                }
            }

			//在繁忙阶段不能进行预约
			if (EQ_Reserv::_check_busy_handler($equipment, $component)) {
				return;
			}

			//不在修改预约的有效时间内
            if (!EQ_Reserv::check_edit_time($equipment, $component)) {
                return;
            }
            

			if (!$equipment->cannot_be_reserved($user, $component->dtstart, $component->dtend, $reserv)) {
				$e->return_value = TRUE;
				return FALSE;
			}

			break;
		}
	}

	/*
	查看仪器预约或非预约时段
		$component为cal_component对象
	*/
	static function view_is_allowed($e, $user, $perm_name, $component, $options) {

        $calendar = $component->calendar;

        try {
            //没有设定calendar->id，不予判断
            if (!$calendar->id) throw new Error_Exception;

            //没有设定component->id， 不予判断
            if (!$component->id) throw new Error_Exception;

            //calendar类型不为eq_reserv下可判断的类型
            if (!($calendar->type == 'eq_reserv' || ($calendar->type == 'eq_incharge' && $calendar->parent->name() == 'user'))) throw new Error_Exception;
        }
        catch(Error_Exception $e) {
            //进行判断，有问题跳出
            return;
        }

		$parent = $component->calendar->parent;
		switch ($parent->name()) {
		case 'user':
			if ($component->calendar->type !== 'eq_incharge') return;
			if ($user->id === $parent->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case 'equipment':
			// 所有人能能够查看预约
			$e->return_value = TRUE;
			return FALSE;
		}
	}

	static function add_rrule_is_allowed($e, $user, $perm_name, $calendar, $options) {
        try {
            //没有设定calendar->id，不予判断
            if (!$calendar->id) throw new Error_Exception;

            //calendar类型不为eq_reserv下可判断的类型
            if (!($calendar->type == 'eq_reserv' || ($calendar->type == 'eq_incharge' && $calendar->parent->name() == 'user') || $calendar->type == 'me_reserv')) throw new Error_Exception;
        }
        catch(Error_Exception $e) {
            //进行判断，有问题跳出
            return;
        }

		$parent = $calendar->parent;
		if ($parent->name() == 'equipment') {
			if ($user->access('为所有仪器添加重复预约事件')) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ($user->group->id && $user->access('为下属机构仪器添加重复预约事件') && $user->group->is_itself_or_ancestor_of($parent->group)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			
			if ($user->access('为负责仪器添加重复预约事件') &&
				Equipments::user_is_eq_incharge($user, $parent)) {
				$e->return_value = TRUE;
				return FALSE;
			}
		}
		elseif($parent->name() == 'meeting') {
			if ($user->access('管理所有会议室的重复预约')) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ($user->access('管理负责会议室的重复预约') && ME_Reserv::user_is_meeting_incharge($user, $parent)) {
				$e->return_value = TRUE;
				return FALSE;
			}
		}
		elseif ($parent->name() == 'user' &&
				$calendar->type == 'eq_incharge' &&
				$user->access('为负责仪器添加重复预约事件')) {
			$e->return_value = TRUE;
			return FALSE;
		}
	}
	/*
	判断用户是否有为某calendar对象添加eq_reserv事件的权限，目的为判断是否可以向用户发送编辑的表单视图
	$calendar为calendar对象
	*/
	static function add_event_is_allowed($e, $user, $perm_name, $calendar, $options) {

        try {

            //没有设定calendar->id，不予判断
            if (!$calendar->id) throw new Error_Exception;

            //calendar类型不为eq_reserv下可判断的类型
            if (!($calendar->type == 'eq_reserv' || ($calendar->type == 'eq_incharge' && $calendar->parent->name() == 'user'))) throw new Error_Exception;
        }
        catch(Error_Exception $e) {
            //进行判断，有问题跳出
            return;
        }

		$parent = $calendar->parent;
		switch ($parent->name()) {
		case 'user':
			if ($calendar->type !== 'eq_incharge') return;
			
			/*if ($user->id === $parent->id) {
				$e->return_value = TRUE;
				return FALSE;
			}*/
			/*
			  NO.BUG#212(xiaopei.li@2010.12.03)
			  删除为别人负责的仪器添加非预约时段的权限
			*/
			break;
		case 'equipment':
			if ($user->access('为所有仪器添加预约')) {
				$e->return_value = TRUE;
                return FALSE;
			}
			
			if ($user->group->id && $user->access('为下属机构仪器添加预约') && $user->group->is_itself_or_ancestor_of($parent->group)) {
				$e->return_value = TRUE;
                return FALSE;
			}

			if ($user->access('为负责仪器添加预约') && Equipments::user_is_eq_incharge($user, $parent)) {
				$e->return_value = TRUE;
                return FALSE;
			}

			if (!$parent->cannot_be_reserved($user)) {
				$e->return_value = TRUE;
				return FALSE;
			} else { 
            	Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '您可先进行预约资格自检, 了解是否具备全部预约资格'));
			}
				
			break;
		}
	}
	/*
	判断用户是否有为某calendar对象修改eq_reserv事件的权限，目的为判断是否可以向用户发送编辑的表单视图
	$calendar为calendar对象
	*/
	static function edit_event_is_allowed($e, $user, $perm_name, $calendar, $options) {
        try {
            //没有设定calendar->id，不予判断
            if (!$calendar->id) throw new Error_Exception;

            //calendar类型不为eq_reserv下可判断的类型
            if (!($calendar->type == 'eq_reserv' || ($calendar->type == 'eq_incharge' && $calendar->parent->name() == 'user'))) throw new Error_Exception;
        }
        catch(Error_Exception $e) {
            //进行判断，有问题跳出
            return;
        }

		$parent = $calendar->parent;
		switch ($parent->name()) {
		case 'user':
			if ($calendar->type !== 'eq_incharge') return;
			if ($user->access('修改所有仪器的预约')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->id === $parent->id || $user->access('修改负责仪器的预约')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->group->id && $user->access('修改下属机构仪器的预约') && $user->group->is_itself_or_ancestor_of($parent->group)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case 'equipment':
			$equipment = $parent;
			if ($user->access('修改所有仪器的预约')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->group->id && $user->access('修改下属机构仪器的预约') && $user->group->is_itself_or_ancestor_of($equipment->group)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->access('修改负责仪器的预约') && Equipments::user_is_eq_incharge($user, $equipment)) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if (!$equipment->cannot_be_reserved($user)) {
				$e->return_value = TRUE;
				return FALSE;
			}

			break;
		}
	}
	/*
	判断用户是否有为某calendar对象列表eq_reserv事件的权限，目的为判断是否可以向用户发送编辑的表单视图
	$calendar为calendar对象
	*/
	static function list_event_is_allowed($e, $user, $perm_name, $calendar, $options) {
        try {
            //没有设定calendar->id，不予判断
            if (!$calendar->id) throw new Error_Exception;

            //calendar类型不为eq_reserv下可判断的类型
            if (!($calendar->type == 'eq_reserv' || ($calendar->type == 'eq_incharge' && $calendar->parent->name() == 'user'))) throw new Error_Exception;
        }
        catch(Error_Exception $e) {
            //进行判断，有问题跳出
            return;
        }

		$parent = $calendar->parent;
		switch ($parent->name()) {
		case 'user':
			if ($calendar->type !== 'eq_incharge') return;
			if ($user->id === $parent->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case 'equipment':
			//允许所有用户查看预约
			$e->return_value = TRUE;
			return FALSE;
		}
	}

	/*
	NO.BUG#199（guoping.zhang@2010.11.29）
	$equipment为equipment对象
	*/
	static function equipment_ACL($e, $user, $perm_name, $equipment, $options) {
		if (!$equipment->id) return;

		if ($perm_name == '修改预约') {
			if ($user->access('为所有仪器添加预约') || $user->access('修改所有仪器的预约')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->group->id && ($user->access('修改下属机构仪器的预约') || $user->access('为下属机构仪器添加预约')) && $user->group->is_itself_or_ancestor_of($equipment->group)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if (($user->access('修改负责仪器的预约') || $user->access('为负责仪器添加预约')) && Equipments::user_is_eq_incharge($user, $equipment)) {
				$e->return_value = TRUE;
				return FALSE;
			}

		}
		//elseif ($user->is_allowed_to('修改使用设置', $equipment)) {
		else if ($perm_name == '锁定预约') {
			if ($user->access('添加/修改所有机构的仪器')) {
				$e->return_value = TRUE;
				return FALSE;
			}
		}
		else {
			if ($user->access('修改所有仪器的预约设置')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->group->id && $user->access('修改下属机构仪器的预约设置') && $user->group->is_itself_or_ancestor_of($equipment->group)) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($user->access('修改负责仪器的预约设置') && Equipments::user_is_eq_incharge($user, $equipment)) {
				$e->return_value = TRUE;
				if ($perm_name == '修改预约设置' && $equipment->reserv_lock) {
					$e->return_value = FALSE;
				}
				return FALSE;
			}
		}
		//}
	}

    static function user_ACL($e, $user, $perm, $object, $options) {
        switch($perm) {
            case '修改预约违规次数' :
                if ($user->access('修改用户的预约违规次数')) {
                    $e->return_value = TRUE;
                }
                if ($user->access('修改下属机构用户的预约违规次数')
                    && $user->group->id && $user->group->is_itself_or_ancestor_of($object->group)
                ) {
                    $e->return_value = TRUE;
                }
                return FALSE;
                break;
            default :
                break;
        }
    }

    static function lab_equipments_reserv_ACL($e, $me, $perm_name, $object, $options) {
		$lab = $object;
		if ($perm_name=='列表仪器预约') {
			if ($lab->id && Q("$me $lab")->total_count() 
				&& ($me->access('查看本实验室成员的仪器使用情况') 
					|| $me->access('查看负责实验室成员的预约情况')|| $me->access('查看本实验室成员的预约情况')) 
				|| $me->access('管理所有内容')
                || $me->access('查看下属机构实验室成员的预约情况') && $me->group->is_itself_or_ancestor_of($object->group)
            ) {
				$e->return_value = TRUE;
				return FALSE;
			}
		}
	}

	/**
	 * 预约token
	 */
	static function cache_access_token($token , $value = []){
		$cache = Cache::factory('redis');
		$set_value['init_access_token'] = (array) $value;
		$cache->set($token,json_encode($set_value),3600);
	}

	static function cache_used_token($token){
		$cache = Cache::factory('redis');
		$cache->set('used_'.$token,1,3600);
	}

	static function clear_access_token($token){
		$cache = Cache::factory('redis');
		$cache->remove($token);
	}

	//TODO：：是否细化错误提示。
	static function valid_access_token($token = ''){
		$cache = Cache::factory('redis');
		$value = $cache->get($token);
		if(!$value) return ['code' => 500,'error'=>'非法的请求来源'];
		$value = $cache->get('used_'.$token);
		if($value) return ['code' => 500,'error'=>'无效的token'];
		return ['code'=>200];
	}

}
