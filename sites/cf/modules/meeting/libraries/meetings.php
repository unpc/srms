<?php

	class Meetings {
		
		static function meeting_ACL($e, $me, $perm, $meeting, $options) {
			switch($perm) {
			case '列表':
		        $e->return_value = TRUE;
		        return FALSE;
	        	break;
			case '添加':
				if ($me->access('添加/修改所有会议室') ) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			case '删除':
				if ($me->access('添加/修改所有会议室')) {
					$e->return_value = TRUE;
					return FALSE;
				}
                if ($me->access('修改负责会议室信息') && ME_Reserv::user_is_meeting_incharge($me, $meeting)) {
					$e->return_value = TRUE;
					return FALSE;
				}
			
				break;
			case '修改':
			case '添加公告':
			case '修改公告':
			case '删除公告':
				if ($me->access('添加/修改所有会议室')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if ($me->access('修改负责会议室信息') && (is_string($meeting) || ME_Reserv::user_is_meeting_incharge($me, $meeting))) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			case '管理授权':
				if ($me->access('管理所有会议室的授权')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if ($me->access('管理负责会议室的授权') && ME_Reserv::user_is_meeting_incharge($me, $meeting)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;			
			case '管理预约':
				if ($me->access('管理所有会议室的预约')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				if ($me->access('管理负责会议室的预约') && ME_Reserv::user_is_meeting_incharge($me, $meeting)) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			case '查看所有会议室预约':
				if ($me->access('管理所有会议室的预约')) {
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			}
			
		}    

        static function notif_classification_enable_callback($user) {
            return Q("meeting $user.incharge")->total_count();
        }

        static function newsletter_view($e) {
        	$e->return_value[] = V('meeting:hooks/admin_newsletter');
        }

}

