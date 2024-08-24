<?php

class ME_Reserv_Access {

	static function add_is_allowed($e, $user, $perm_name, $component, $options) {

		if ($component->id) return;

		if ($component->check_overlap()) return;

		$parent = $component->calendar->parent;
		if ($parent->name() == 'meeting') {
			$meeting = $parent;
		}
		elseif ($parent->name() == 'lab' && $component->me_room->id) {
			$meeting = $component->me_room;
		}
		else {
			return;
		}
		
		if ($user->is_allowed_to('管理预约', $meeting)) {
			$e->return_value = TRUE;
			return FALSE;
		}

		//时间是否合法
		$now = Date::time();

		if ($component->dtstart < $now || $component->dtend < $now) {
			Lab::message(Lab::MESSAGE_ERROR, I18N::T('meeting', '请选择有效的预约时间'));
			$e->return_value = FALSE;	
			return FALSE;
        }
        
        if (!ME_Reserv::check_create_time($meeting, $component)) {
            return FALSE;
        }

		//是否通过授权
		if (self::check_authorized($user, $meeting)) {
			$e->return_value = TRUE;
			return FALSE;
		}

	}

	static function delete_is_allowed($e, $user, $perm_name, $component, $options) {
		if (L('skip_meeting_reserv_delete_check')) return;
		if (!$component->id) return;
		$parent = $component->calendar->parent;
		if ($parent->name() == 'meeting') {
			$meeting = $parent;
		}
		elseif ($parent->name() == 'lab' && $component->me_room->id) {
			$meeting = $component->me_room;
		}
		else {
			return;
		}

		if ($user->access('管理所有会议室的预约')) {
			$e->return_value = TRUE;
			return FALSE;
		}

		if ($user->access('管理负责会议室的预约') && ME_Reserv::user_is_meeting_incharge($user, $meeting)) {
			$e->return_value = TRUE;
			return FALSE;
        }
        
        if (!ME_Reserv::check_delete_time($meeting, $component)) {
            return FALSE;
        }

		//普通用户可以删除将来的预约
		if ($user->id == $component->organizer->id && $component->dtstart > time()) {
			$e->return_value = TRUE;
			return FALSE;
		}

	}

	static function edit_is_allowed($e, $user, $perm_name, $component, $options) {
		
		if (L('skip_meeting_reserv_edit_check')) return;
		if (!$component->id) return;
		$parent = $component->calendar->parent;
		if ($parent->name() == 'meeting') {
			$meeting = $parent;
		}
		elseif ($parent->name() == 'lab' && $component->me_room->id) {
			$meeting = $component->me_room;
		}
		else {
			return;
		}
		
		//如果预约重叠
		if ($component->check_overlap()) return;
		
        if ($parent->name() == 'meeting') {
			if ($user->access('管理所有会议室的预约')) {
				$e->return_value = TRUE;
				return FALSE;
			}

			if ($user->access('管理负责会议室的预约') && ME_Reserv::user_is_meeting_incharge($user, $meeting)) {
				$e->return_value = TRUE;
				return FALSE;
			}

			$ori_organizer = $component->get('organizer', TRUE);
            $now = Date::time();
            
            if (!ME_Reserv::check_create_time($meeting, $component)) {
                return FALSE;
            }

            if (!ME_Reserv::check_edit_time($meeting, $component)) {
                return false;
            }
			
			if ($user->id == $ori_organizer->id && $component->dtstart > $now 
			&& self::check_authorized($user, $meeting, false)) {
				$e->return_value = TRUE;
				return FALSE;
			}

        }

	}


	static function view_is_allowed($e, $user, $perm_name, $component, $options) {
		
		if (!$component->id) return;
		$parent = $component->calendar->parent;
		$pname = $parent->name();
		if ($pname == 'meeting') {
			$e->return_value = TRUE;
			return FALSE;
		}
		else if ($pname == 'lab' && $component->me_room->id) {
			$e->return_value = TRUE;
			return FALSE;			
		}
	}


	static function add_event_is_allowed($e, $user, $perm_name, $calendar, $options) {	
		
		if (!$calendar->id) return;

		if ($calendar->parent->name() == 'meeting') {
			$e->return_value = TRUE;
			return FALSE;
		}
	}

	static function edit_event_is_allowed($e, $user, $perm_name, $calendar, $options) {
	
		if (!$calendar->id) return;
		$parent = $calendar->parent;
		if ($parent->name() == 'meeting') {
			$e->return_value = TRUE;
			return FALSE;
		}
		
	}

	static function list_event_is_allowed($e, $user, $perm_name, $calendar, $options) {
		
		if (!$calendar->id) return;


		$parent = $calendar->parent;
		switch ($parent->name()) {
		case 'user':
			if ($calendar->type !== 'me_incharge') return;
			if ($user->id === $parent->id) {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case 'meeting':
			if ($calendar->type !== 'me_reserv') return;
			$e->return_value = TRUE;
			return FALSE;
			break;
		case 'calendar':
			if ($user->access('管理所有会议室的预约') && $calendar->type == 'all_meetings') {
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		}
	}
	
	static function check_authorized ($user, $meeting, $message = true) {
		if (!$meeting->require_auth) return TRUE;
		
		if (Event::trigger('meeting.door.check.authorized', $user, $meeting)) return TRUE;

		$auths = Q("um_auth[!user][meeting={$meeting}]");
		$root = $meeting->get_root();
		foreach ($auths as $auth) {
			$name = $auth->tag->name;
			if (EQ_Lua::user_has_tag($name, $user, [$root, Tag_Model::root('meeting_user_tags')])) return TRUE;
		}

		$auth = O('um_auth', ['user' => $user, 'meeting' => $meeting]);
		
		if ($auth->id && $auth->status == UM_Auth_Model::STATUS_APPLIED ) {
			if ($message) Lab::message(Lab::MESSAGE_ERROR, 
			I18N::T('meeting', '您申请了此会议室的授权，但尚未通过，请联系会议室管理员。'));
			return FALSE;
		} 
		elseif (!$auth->id || $auth->status!=UM_Auth_Model::STATUS_APPROVED) {
			if ($message) Lab::message(Lab::MESSAGE_ERROR, 
			I18N::T('meeting', '您还未通过此会议室的授权，请向会议室管理员申请授权。'));
			return FALSE;
		}

		return TRUE;
	}
}
