<?php
class Announce_Approval_Access {
	static function announce_ACL($e, $user, $perm, $announce, $options){
		
		switch($perm){
			case '添加':
				if( $user->access('管理公告') ||
                    $user->access('发布公告')){
					$e->return_value = TRUE;
					return FALSE;
				}
				break;
            case '列表审批':
                if( $user->access('管理公告') ||
                    $user->access('发布公告') ||
                    $user->access('审核公告')){
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            case '审批':
                if( ($user->access('管理公告') || $user->access('审核公告'))
                    &&
                    (!$announce->id ||
                        ($announce->id && $announce->need_approval && !$announce->flag)
                    )
                ){
                    $e->return_value = TRUE;
                }
                return FALSE;
                break;
		}
	}


    static function announce_attachments_ACL($e, $user, $perm, $object, $options) {
        switch ($perm) {
            case '上传文件':
                if($user->access('管理公告') || $user->access('发布公告')){
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

	static function need_approval($e, $user){
	    if (!$user->access('审核公告') && !$user->access('管理公告')) {
            $e->return_value = TRUE;
            return FALSE;
        }
    }

}
