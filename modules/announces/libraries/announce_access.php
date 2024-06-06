<?php
class Announce_Access {
	static function announce_attachments_ACL($e, $user, $perm, $object, $options) {
		switch ($perm) {
		case '上传文件':
			if($user->access('管理公告')){
				$e->return_value = TRUE;
				return FALSE;
			}
			break;
		case '列表文件':
		case '下载文件':
		case '查看':
			$e->return_value = TRUE;
			return FALSE;
			break;
        case '删除文件':
        case '修改文件':
            $e->return_value = FALSE;
            break;
        }
	}

	static function announce_ACL($e, $user, $perm, $announce, $options){
		
		switch($perm){
			case '修改':
			case '查看所有':
			case '添加':
			case '管理':
				if($user->access('管理公告')){
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
			case '删除':
				if($user->access('管理公告')){
					$e->return_value = TRUE;
					return FALSE;
				}
		}
	}

}
