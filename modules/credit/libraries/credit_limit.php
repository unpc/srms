<?php

class Credit_Limit
{
    //检测是否能预约仪器
    public static function can_not_reserv($e, $equipment, $params)
    {
        $user = $params[0];
        if ($user->access('管理所有内容') || $user->access('管理组织机构')) {
            $e->return_value = false;
            return true;
        }
        $credit = O('credit', ['user' => $user]);
        if (!$credit->id) {
            $e->return_value = false;
            return true;
        }
        $measures = O('credit_measures', ['ref_no' => 'can_not_reserv']);
        $res      = Credit_Limit::check_condition($user, $measures);
        if ($res['status']) {
            $e->return_value = true;
            return false;
        }
    }

    //加入系统黑名单
    public static function ban($e, $user, $limit)
    {
        $filter            = ['user' => $user, 'object_id' => 0];
        $eq_banned         = O('eq_banned', $filter);
        $eq_banned->user   = $user;
        $eq_banned->reason = '信用分过低,系统自动加入黑名单';
        $eq_banned->atime  = $limit->ban_day ? strtotime("+{$limit->ban_day} day") : 0;
        $eq_banned->save();
    }

    //更改状态为未激活
    public static function unactive_user($e, $user, $limit)
    {
        $user->atime = 0;
        $user->save();
        return;
    }

    // 检测是否符合惩罚/阈值条件
    public static function check_condition($user, $measures)
    {
        $credit        = O('credit', ['user' => $user]);
        $system_limit  = self::_get_system_limit($measures);
        $special_limit = self::_get_special_limit($user, $measures);

        // 个别设置优先，多个个别设置同时命中，第一个匹配
        if ($special_limit->id) {
            $apply_limit = $special_limit;
        } elseif ($system_limit->id) {
            $apply_limit = $system_limit;
        } else {
            return [
                'status'   => false,
            ];
        }

        return [
            'status'   => $credit->total < $apply_limit->score,
            'limit'    => $apply_limit->id ? $apply_limit : null,
        ];
    }

    //取个别设置
    private static function _get_special_limit($user, $measures)
    {
        //用户
        $user_limits = Q("{$user} credit_limit[measures={$measures}]")->to_assoc('id', 'id');
        //课题组
        $lab_limits = Q("{$user} lab credit_limit[measures={$measures}]")->to_assoc('id', 'id');
        //组织机构
        $tag_ids = [];
        $groups  = Q("credit_limit[measures={$measures}] tag_group");
        foreach ($groups as $group) {
            if ($group->is_itself_or_ancestor_of($user->group)) {
                $tag_ids[] = $group->id;
            }
        }
        $group_limits = Q("tag_group[id=" . implode(',', $tag_ids) . "] credit_limit[measures={$measures}]")->to_assoc('id', 'id');

        $limit_ids = array_unique(array_merge($user_limits, $lab_limits, $group_limits));

        //惩罚条件取最高的分数
        $special_limit = Q('credit_limit[id=' . implode(',', $limit_ids) . ']:sort(score D)')->current();

        return $special_limit;
    }

    //取全局设置
    private static function _get_system_limit($measures)
    {
        $system_limit = Q("credit_limit[enable][!is_custom][measures={$measures}]")->current();

        return $system_limit;
    }

    /**
     * @param user 用户
     * @param measures 措施
     * @return 用户触发某项惩罚措施的条目
     */
    public static function get_punishment_limit($user, $measures, $order_by = 'score', $order = 'D')
    {
        if (!in_array($order_by, ['id', 'score']) || !in_array($order, ['A', 'D'])) {
            return O('credit_limit');
        }

        if (Q("perm[name=管理所有内容|name=管理组织机构] role {$user}")->total_count()) {
            return O('credit_limit');
        }

        // 用户
        $user_limits = Q("{$user} credit_limit[enable=1][measures={$measures}]")->to_assoc('id', 'id');

        // 课题组
        $lab_limits = Q("{$user} lab credit_limit[enable=1][measures={$measures}]")->to_assoc('id', 'id');

        // 组织机构
        $tag_ids = [];
        $groups  = Q("credit_limit[measures={$measures}] tag_group");
        foreach ($groups as $group) {
            if ($group->is_itself_or_ancestor_of($user->group)) {
                $tag_ids[] = $group->id;
            }
        }
        $group_limits = Q("tag_group[id=" . implode(',', $tag_ids) . "] credit_limit[enable=1][measures={$measures}]")->to_assoc('id', 'id');

        // 系统设置
        $system_limits = Q("credit_limit[enable][!is_custom][measures={$measures}]")->to_assoc('id', 'id');

        $special_limits = array_unique(array_merge($user_limits, $lab_limits, $group_limits));
        if (count((array) $special_limits)) {
            return Q('credit_limit[id=' . implode(',', $special_limits) . ']:sort(' . $order_by . ' ' . $order . ')')->current();
        } elseif (count((array) $system_limits)) {
            return Q('credit_limit[id=' . implode(',', $system_limits) . ']')->current();
        } else {
            return O('credit_limit');
        }
    }
}
