<?php

class Node {

	static function node_ACL($e, $user, $perm, $node, $params) {	
		
		if( $user->access('管理所有环境监控对象') ) {
			$e->return_value = TRUE;
			return FALSE;
		}

        switch($perm) {
            case '查看' :
                //进行单一查看
                if ($node->id) {
                    if ($user->access('管理所有环境监控对象') || self::user_is_node_incharge($user, $node)) {
                        $e->return_value = TRUE;
                        return FALSE;
                    }
                }
                else {
                //列表查看
                    if ($user->access('查看环境监控模块') || Q("{$user}<incharge env_node")->total_count()) {
                        $e->return_value = TRUE;
                        return FALSE;
                    }
                }
                break;
            case '添加' :
            case '删除' :
                if($user->access('管理所有环境监控对象')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            case '修改' :
                if($user->access('管理所有环境监控对象')) {
                    $e->return_value = TRUE;
                    return FALSE;
                }

                if (self::user_is_node_incharge($user, $node)) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
                break;
            case '添加传感器' :

                if (self::user_is_node_incharge($user, $node)) {
                    $e->return_value = TRUE;
                    return FALSE;
                }
        }
	}

	static function is_accessible($e, $name) {
		if( !L('ME')->is_allowed_to('查看', 'env_node')) {
			$e->return_value = FALSE;
			return FALSE;
		}
	}

    static function user_is_node_incharge($user, $node) {
        if ($user->id && $node->id && Q("{$node} user[id=$user->id].incharge")->total_count()) {
            return TRUE;
        }
        else {
            return FALSE;
        }
    }

    static function notif_classification_enable_callback($user) {
        //如果用户为envmon.admin配置的账号
        //也应该考虑进行配置设置
        $is_admin = FALSE;
        $admins = Config::get('envmon.admin');
        foreach($admins as $token) {
            if ($user->token == Auth::normalize($token)) {
                $is_admin = TRUE;
                break;
            }
        }


        return $is_admin || Q("env_node $user.incharge")->total_count();
    }
}
