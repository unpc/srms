<?php 

class Basic_Info_Access {
	static function is_accessible($e, $name) {
        $me = L('ME');
        if (
                (
                    ! $me->is_allowed_to('查看实验室信息统计', $name)
                    &&
                    ! Q("{$me}<incharge equipment")->total_count()
                )
           ) {
            $e->return_value = false;
		
            return FALSE;
        }
    }

    static function basic_info_is_allowed($e, $user, $perm, $object, $options) {
        switch ($perm) {
            // 基表权限
            case '查看实验室信息统计':
                if ($user->access('管理实验室信息统计') 
                    || $user->access('查看下属机构的仪器信息')
                    || $user->access('管理下属机构的实验室统计信息')
                    || $user->access('管理下属机构实验室的教学项目信息')
                    || $user->access('管理下属机构实验室科研项目信息')) 
                {
                    $e->return_value = TRUE;
                    return FALSE;
                }
            default:
                return FALSE;
        }
    }
}
