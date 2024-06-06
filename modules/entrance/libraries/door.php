<?php

class Door {

	//分析每一条规则
	static function match_rule($user, $direction, $time, $rule) {
		// 1. 判断方向是否符合
		$ret = FALSE;
		$directions = (array) $rule['directions'];
		
		if (in_array($direction, $directions)) {
			$ret = TRUE;
		}
	
		if (!$ret) return FALSE;
		// 2. 判断用户是否符合
		$ret = FALSE;

		foreach (['user', 'lab', 'group'] as $k) {
			if (!$rule['select_user_mode_'.$k] || !$rule['select_user_mode_'.$k] == 'on') continue;
			switch ($k) {
				case 'user':
					$users = (array) $rule['users'];
					if (self::match_user($user, $users)) {
						$ret = TRUE;
					}
					break;
				case 'lab':
					$labs = (array) $rule['labs'];
					if (self::match_lab_user($user, $labs)) {
						$ret = TRUE;
					}
					break;
				case 'group':
					$groups = (array) $rule['groups'];
					if (self::match_group_user($user, $groups)) {
						$ret = TRUE;
					}
					break;
				default:
			}
		}
		
		if (!$ret) return FALSE;
		//by Cheng.liu @ 2010.10.27
		//修改判断时间规则，之前的Door::match_time_rule移植到Rule_Date类中
		$ret = TM_RRule::match_time_rule($time, $rule);
		
		return $ret;
	}
	
	//按照user分析人员
	static function match_user($user, $users) {
		foreach ($users as $id=>$name) {
			if ($user->id == $id) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
	//按照lab分析人员
	static function match_lab_user($user, $labs) {
		foreach ($labs as $id=>$value) {
			if (Q("$user lab[id=$id]")->total_count()) {
				return TRUE;
			}
		}
		return FALSE;
	}
	
    //按照group分析人员
    static function match_group_user($user, $groups) {
        //获取Group Tag Root
        $root = Tag_Model::root('group');
        foreach ($groups as $id=>$value) {
            $group = O('tag_group', ['id'=> $id, 'root'=> $root]);

            //存在Group
            foreach (Q("$user lab") as $lab) {
				if ($group->id && $group->is_itself_or_ancestor_of($lab->group)) {
					return TRUE;
				}
            }
        }
        return FALSE;
    }
	/*
	NO.TASK#274(guoping.zhang@2010.11.27
	操作门禁权限设置
		$object为door对象
		远程控制/刷卡控制：设置$params['direction']
	*/
	static function operate_door_is_allowed($e, $user, $perm, $object, $params) {
		
		$incharge_ids = Q("$object<incharge user")->to_assoc('id', 'id');
		switch ($perm) {
			case '添加':
				if ($user->access('管理所有门禁')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			case '修改':
			case '删除':
				if ($object->id && $user->access('管理所有门禁')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if ($object->id && $user->access('管理负责的门禁') && in_array($user->id, $incharge_ids)) {	
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
            case '查看':
			case '列表':
                if ($user->access('查看门禁模块')) {
                    $e->return_value = TRUE;
                }
                return FALSE;
			case '列表记录':
				if ($user->access('管理所有门禁') || $user->access('查看所有门禁的进出记录')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if ('door' == $object->name() && $object->id && in_array($user->id, $incharge_ids)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			case '导出记录':
				if ($user->access('管理所有门禁') || $user->access('查看所有门禁的进出记录')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if ($object->id && in_array($user->id, $incharge_ids)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			case '远程控制':
				if ($object->id
					&& $object->type != Door_Model::type('genee') 
					&& !Door_Model::iot_door_driver('driver_unlock')
				){
					$e->return_value = FALSE;
					return FALSE;
				}
				if ($user->access('管理所有门禁')) {
					$e->return_value = TRUE;
					return FALSE;
				}

                if ($object->id && $user->access('远程控制负责的门禁') && in_array($user->id, $incharge_ids)) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
				break;
			case '刷卡控制':
                //未激活成员不能刷卡进门
                if (!$user->is_active()) {
                    $e->return_value = FALSE;
                    return FALSE;
                }
				if($user->access('管理所有门禁')) {
					$e->return_value = TRUE;
					return FALSE;
				}

				$free_access_cards = (array)$object->get_free_access_cards();
				foreach ($free_access_cards as $card_no => $incharger) {
					if ($incharger->id == $user->id) {
						$e->return_value = TRUE;
						return FALSE;
					}
				}

                if ($object->id && in_array($user->id, $incharge_ids)) {
                    $e->return_value = TRUE;
                    return FALSE;
                }

                $direction = $params['direction'];
                if (!is_numeric($direction)) {
                    $direction = $direction == 'in' ? 1 : 0;
                }
				$ret = Event::trigger('operate_door_is_allowed', $user, $direction, $object);
				if ($ret) {
					$e->return_value = TRUE;
					return FALSE;
				}

				$rules = (array) @json_decode($object->rules, TRUE);
				$default_rule = (array) $rules['default'];
				unset($rules['default']);
				
				$time = Date::time();
				foreach ($rules as $rule) {
					
					if (self::match_rule($user, $direction, $time, $rule)) {
						//如果这条规则都 return TRUE 的话, 那么判断其access, 如果access不通过, 则不通过, 如果通过则return TRUE
						$e->return_value = $rule['access'] ? TRUE : FALSE;
						return FALSE;
					}			
				}
				
				//判断默认规则
				if (in_array($direction, (array)$default_rule['directions']) && $default_rule['access']) {
					$e->return_value = TRUE;
					return FALSE;
				}

				break;
		}
	}

	static function cannot_access_door($e, $door, $params) {
		$rules = (array) @json_decode($door->rules, TRUE);
		$default_rule = (array) $rules['default'];
		unset($rules['default']);
		
        $user = $params[0];

        //如果用户未激活
        //无法开门
        if (!$user->is_active()) {
            $e->return_value = TRUE;
            return FALSE;
        }

        $time = (int)$params[1];

		foreach ($rules as $rule) {
			$direction = $params[2] == 'in' ? 1 : 0;
			if (Door::match_rule($params[0], $direction, $time, $rule)) {
				//如果这条规则都 return TRUE 的话, 那么判断其access, 如果access不通过, 则不通过, 如果通过则return TRUE
				if ($rule['access']) {
					$e->return_value = FALSE;
					return FALSE;
				}
				else {
					$e->return_value = TRUE;
					return FALSE;
				}
			}			
		}
		
		//判断默认规则
		$direction = $params[2] == 'in' ? 1 : 0; 
		if (in_array($direction, $default_rule['directions'] ? : []) && $default_rule['access']) {
			$e->return_value = FALSE;
			return FALSE;
		}

		$e->return_value = TRUE;
	}

	/*
	NO.TASK#274(guoping.zhang@2010.11.27
	操作门禁记录权限设置
		$object为dc_record对象
	*/
	static function operate_record_is_allowed($e, $user, $perm, $object, $params) {	
		switch ($perm) {
			case '删除':
				if ($object->id && $user->access('管理所有门禁')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
		}
	}

	static function operate_object_record_is_allowed($e, $user, $perm, $object, $params) {	
		switch ($perm) {
			case '列表门禁记录':
				if ($user->access('管理所有门禁') || $user->access('查看所有门禁的进出记录')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if ('lab' == $object->name() && $object->id && $object->owner->id == $user->id && $user->access('查看负责课题组的进出记录')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if ('user' == $object->name() && $object->id && $object->id == $user->id) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if ('equipment' == $object->name() && $object->id &&  Q("$object $user.incharge")->total_count() && $user->access('查看负责仪器关联的进出记录')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
		}
	}

	static function entrance_newsletter_content($e, $user) {

		$templates = Config::get('newsletter.template');
		$dtstart = strtotime(date('Y-m-d')) - 86400;
		$dtend = strtotime(date('Y-m-d'));
		$db = Database::factory();
		$template = $templates['security']['enter_count'];
		$sql = 'SELECT COUNT(*) FROM (SELECT DISTINCT user_id FROM `dc_record` WHERE time>%d AND time<%d) as total';
		$count = $db->value($sql, $dtstart, $dtend);
		if ($count > 0) {
			$str .= V('entrance:newsletter/enter_count', [
				'count' => $count,
				'template' => $template,
			]);
		}
		
		if (strlen($str) > 0) {
			$view = V('entrance:newsletter/view', [
					'str' => $str,
			]);
			$e->return_value .= $view;
		}
	}

	static function entrance_setting_sync($e, $user) {
		$free_access_tokens = array_unique(array_merge((array)Config::get('lab.admin'), (array)Config::get('entrance.free_access_users')));
		if (in_array($user->token, $free_access_tokens)) {
			// 由于 icco-server 式的门禁 is_monitoring 可能管理得不及时,
			// 而 Device_Agent 中是会对是否 connect 做判断的, 比较严谨.
			// 所以在此去除 is_monitoring 的判断
			$doors = Q('door');
			foreach ($doors as $door) {
				$agent = new Device_Agent($door, FALSE, 'in');
        		$agent->call('sync');
			}
		}
		else {
			$doors = Q("{$user} door.incharge");
			foreach ($doors as $door) {
				$agent = new Device_Agent($door, FALSE, 'in');
        		$agent->call('sync');				
			}
		}
	}

    static function on_door_saved($e, $door, $old_data, $new_data) {
        //进行修改时候,发生数据变动, call halt
        if ($door->id && ($old_data['in_addr'] != $new_data['in_addr']
            || $old_data['out_addr'] != $new_data['out_addr']
            || $old_data['lock_id'] != $new_data['lock_id']
            || $old_data['detector_id'] != $new_data['detector_id'])) {
            $agent = new Device_Agent($door, FALSE, 'in');
            $agent->call('halt');
        }
    }
}
