<?php 
/*
NO.TASK#236（guoping.zhang@2010.11.16)
处理指定对象指定操作的权限判断的方法
*/
class Schedule_Access {
	/*
	判断用户是否有添加schedule的权限，目的时在保存对象前对用户权限和对象的合法性进行验证
		$object为cal_component对象
	*/
	static function add_is_allowed($e, $me, $perm_name, $object, $options) {
		
		if ($object->id) return;

		//如果日程不能被重叠
		if($object->check_overlap()) return;
		
		$parent = $object->calendar->parent;
		switch ($parent->name()) {
		case 'user':
			if ($object->calendar->type !== 'schedule') return;
			if ($parent->id === $me->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($me->access('管理所有成员的日程安排')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case 'lab':
			if ($me->access('管理本实验室的日程安排') && Q("$me $parent")->total_count()) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($me->access('管理负责实验室的日程安排') && Q("$me<pi $parent")->total_count()) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		}	
	}
	/*
	判断用户是否有修改schedule的权限，目的时在保存对象前对用户权限和对象的合法性进行验证
		$object为cal_component对象
	*/
	static function edit_is_allowed($e, $me, $perm_name, $object, $options) {
		if (!$object->id) return;

		//如果日程不能被重叠
		if ($object->check_overlap()) return;
		
		$parent = $object->calendar->parent;
		switch ($parent->name()) {
		case 'user':
			if ($object->calendar->type !== 'schedule') return;
			if ($me->id === $parent->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($me->access('管理所有成员的日程安排')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case 'lab':
			if ($me->access('管理本实验室的日程安排') && Q("$me $parent")->total_count()) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($me->access('管理负责实验室的日程安排') && Q("$me<pi $parent")->total_count()) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		}
		
		//已经结束的日程不能被修改
		//进行中的日程可以被修改
		$ori_dtend = $object->get('dtend', TRUE);
		if ($object->id && $ori_dtend<$now) {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('schedule', '该日程安排已经过期！'));
			return;
		}
	}
	/*
	判断用户是否有删除schedule的权限，目的时在保存删除前对用户权限进行验证
		$object为cal_component对象
	*/
	static function delete_is_allowed($e, $me, $perm_name, $object, $options) {
		if (!$object->id) return;
		
		$parent = $object->calendar->parent;
		switch ($parent->name()) {
		case 'user':
			if ($object->calendar->type !== 'schedule') return;
			if ($me->id === $parent->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($me->access('管理所有成员的日程安排')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case 'lab':
			if ($me->id == $parent->owner->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($me->access('管理本实验室的日程安排') && Q("$me $parent")->total_count()) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($me->access('管理负责实验室的日程安排') && Q("$me<pi $parent")->total_count()) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		}
	}
	/*
	判断用户是否有查看schedule的权限
		$object为cal_component对象
	*/
	static function view_is_allowed($e, $me, $perm_name, $object, $options) {
		if (!$object->id) return;
		
		$parent = $object->calendar->parent;
		switch ($parent->name()) {
		case 'user':
			if ($object->calendar->type !== 'schedule') return;
			if ($me->id === $parent->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($me->access('查看所有成员的日程安排')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case 'lab':
			if ($me->access('查看本实验室的日程安排') && Q("$me $parent")->total_count()) {
				$e->return_value = TRUE;
				return FALSE;
            }
            if ($me->access('查看负责实验室的日程安排') && Q("$me<pi $parent")->total_count()) {
				$e->return_value = TRUE;
				return FALSE;
            }
            if (self::check_attendee($me, $object) || self::check_speaker($me, $object) || self::check_organizer($me, $object)) {
            	$e->return_value = TRUE;
            	return FALSE;
            }
			break;
		}
	}
	
	/*
	判断用户是否有为某calendar对象修改schedule事件的权限，目的为判断是否可以向用户发送编辑的表单视图
	$object为calendar对象
	*/
	static function edit_event_is_allowed($e, $me, $perm_name, $object, $options) {
		if (!$object->id) return;
		
		$parent = $object->parent;
		switch ($parent->name()) {
		case 'user':
			if ($object->type !== 'schedule') return;
			if ($me->id === $parent->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($me->access('管理所有成员的日程安排')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case 'lab':
			$e->return_value = TRUE;
			return FALSE;
		}
	}
	/*
	判断用户是否有为某calendar对象添加schedule事件的权限，目的为判断是否可以向用户发送编辑的表单视图
	$object为calendar对象
	*/
	static function add_event_is_allowed($e, $me, $perm_name, $object, $options) {
		if (!$object->id) return;
		
		$parent = $object->parent;
		switch ($parent->name()) {
		case 'user':
			if ($object->type !== 'schedule') return;
			if ($parent->id === $me->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($me->access('管理所有成员的日程安排')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case 'lab':
			if ($me->access('管理本实验室的日程安排') && Q("$me $parent")->total_count()) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($me->access('管理负责实验室的日程安排') && Q("$me<pi $parent")->total_count()) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		}	
	}
	/*
	判断用户是否有为某calendar对象列表schedule事件的权限，目的为判断是否可以向用户发送列表的视图
	$object为calendar对象
	*/
	static function list_event_is_allowed($e, $me, $perm_name, $object, $options) {
		if (!$object->id) return;
		
		$parent = $object->parent;
		switch ($parent->name()) {
		case 'user':
			if ($object->type !== 'schedule') return;
			if ($parent->id === $me->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
			if ($me->access('查看所有成员的日程安排')) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case 'lab':
			if ($me->access('查看本实验室的日程安排') && Q("$me $parent")->total_count()) {
				$e->return_value = TRUE;
				return FALSE;
            }
            if ($me->access('查看负责实验室的日程安排') && Q("$me<pi $parent")->total_count()) {
				$e->return_value = TRUE;
				return FALSE;
            }
			break;
		}
	}
	
	static function attachment_is_allowed($e, $user, $perm_name, $component, $options) {
		if (!$component->id) {
			$e->return_value = TRUE;
			return FALSE;
		}
		$now = Date::time();
		$parent = $component->calendar->parent;
		switch ($parent->name()) {
		case 'lab':
			switch ($perm_name) {
				case '查看文件':
				case '列表文件':
				case '下载文件':
					if ($component->allow_download_attachments || $user->is_allowed_to('上传文件', $component) || ($component->allow_attendee_download_attachments && self::check_attendee($user, $component))) {
						$e->return_value = TRUE;
						return FALSE;
					}
					if ($user->access('查看本实验室的日程附件') && Q("$user $parent")->total_count()) {
						$e->return_value = TRUE;
						return FALSE;
					}
					if ($user->access('查看负责实验室的日程附件') && Q("$user<pi $parent")->total_count()) {
						$e->return_value = TRUE;
						return FALSE;
					}
					break;
				case '添加文件':	
				case '上传文件':
					if (self::check_organizer($user, $component) || self::check_speaker($user, $component)) {
						$e->return_value = TRUE;  
						return FALSE;
					}
					if ($user->access('管理本实验室的日程附件') && Q("$user $parent")->total_count()) {
						$e->return_value = TRUE;  
						return FALSE;
					}
					if ($user->access('管理负责实验室的日程附件') && Q("$user<pi $parent")->total_count()) {
						$e->return_value = TRUE;  
						return FALSE;
					}
					break;
				case '修改文件':
				case '删除文件':
					if (self::check_organizer($user, $component) && $component->dtend > $now) {
						$e->return_value = TRUE;  
						return FALSE;
					}
					if ($user->access('管理本实验室的日程附件') && Q("$user $parent")->total_count()) {
						$e->return_value = TRUE;  
						return FALSE;
					}
					if ($user->access('管理负责实验室的日程附件') && Q("$user<pi $parent")->total_count()) {
						$e->return_value = TRUE;  
						return FALSE;
					}
					break;
				default:
					break;
			}
			break;
		case 'user':
			if ($component->calendar->type !== 'schedule') return;
			if ($parent->id == $user->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
			switch ($perm_name) {
				case '查看文件':
				case '列表文件':
				case '下载文件':
					if ($user->access('查看所有成员的日程附件')) {
						$e->return_value = TRUE;
						return FALSE;
					}
					break;
				case '修改文件':	
				case '上传文件':
				case '添加文件':
				case '删除文件':
					if ($user->access('管理所有成员的日程附件')) {
						$e->return_value = TRUE;
						return FALSE;
					}
					break;
			}
			break;
		 }
	}
	
	static function check_speaker_attendee_and_organizer($user, $component) {
        $attendees = Q("schedule_attendee[component={$component}] user")->to_assoc('id', 'name');
        if (array_key_exists($user->id, $attendees) || ($user->id == $component->organizer->id)) {
            return TRUE;
        }
        return FALSE;
	}

	static function check_speaker_and_organizer($user, $component) {
        $speakers = Q("schedule_speaker[component={$component}] user")->to_assoc('id', 'name');
        if (array_key_exists($user->id, $speakers) || ($user->id == $component->organizer->id)) {
            return TRUE;
        }
        return FALSE;
	}

	static function check_attendee($user, $component) {
		/* user */
		$attendees = Q("sch_att_user[component={$component}] user")->to_assoc('id', 'name');
		if (array_key_exists($user->id, $attendees)) {
			return TRUE;
		}
		/* role */ 
		$sch_att_role = Q("sch_att_role[component={$component}]")->to_assoc('role_id','role_id');
		$roles = $user->roles();
		foreach ($roles as $key => $value) {
			if (array_key_exists($key ,$sch_att_role)) {
				return TRUE;
			}
		}
		/* group */
		$sch_att_groups = Q("sch_att_group[component={$component}]")->to_assoc('group_id','group_id');
		$group_path = $user->group->path;
		foreach ($group_path as $key => $value) {
			$group_id = $value[0];
			if (array_key_exists($group_id, $sch_att_groups)) {
				return TRUE;
			}	
		}
		return FALSE;
	}

	static function check_speaker($user, $component) {
		$speakers = Q("schedule_speaker[component={$component}] user")->to_assoc('id', 'name');
		if(array_key_exists($user->id, $speakers)){
			return TRUE;
		}
		return FALSE;
	}

	static function check_organizer($user, $component) {
		if ($user->id == $component->organizer->id) {
			return TRUE;
		}
		return FALSE;
	}
}
