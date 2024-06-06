<?php

// 用户信用分
class Credit
{
    /**
     * 用户首次激活时触发自动计分规则
     */
    public static function on_user_saved($e, $user, $old_data, $new_data)
    {
        $credit = O('credit', ['user' => $user]);
        if ($credit->id) {
            return true; // 信用分初始化已完成
        }

        Event::trigger('trigger_scoring_rule', $user, 'init_credit_score'); // 触发用户首次激活自动计分规则
    }

    static function on_user_deleted($e, $user) {
        $credit = O('credit', ['user' => $user]);
        if ($credit->id) {
            $credit->delete();
        }
        Q("credit_record[user=$user]")->delete_all();
	}
    /**
     * 总分不能自己生成, 必须挂勾每一条明细, 可以实现类似财务重算计费的功能
     */
    public static function on_credit_record_saved($e, $credit_record, $old_data, $new_data)
    {
        if (!$credit_record->id || !$credit_record->user->id) {
            Log::add('[credit] 触发信用总分计算时, 由于明细不存在导致总分计算失败', 'credit');
            return true;
        }

        $credit       = O('credit', ['user' => $credit_record->user]);
        $credit->user = $credit_record->user;
        $credit->total = $credit->total + $credit_record->score;

        // 用户等级, 首次标记为大众会员, 之后由于计算量较大, 走cli脚本更新该值
        if (!$credit->credit_level->id) {
            $credit->credit_level = O('credit_level', ['level' => 1]); // 不管实际名称叫什么, 所占百分比为多少, 这是默认的等级
        }
        $credit->save();

        //进行奖惩错误
        $eq_ban_measures = O('credit_measures', ['ref_no' => 'eq_ban']);
        $eq_ban_limit    = O('credit_limit', ['measures' => $eq_ban_measures, 'is_custom' => 0]);
        if($eq_ban_limit->score && $eq_ban_limit->enable && $credit_record->equipment->id){
            $rule_type = Credit_Rule_Model::STATUS_CUT;
            $total_count = Q("credit_rule[type={$rule_type}] credit_record[equipment={$credit_record->equipment}][!status][user={$credit_record->user}]")->total_count();
            if($total_count >= $eq_ban_limit->score){
                $eq_banned = O('eq_banned',['object'=>$credit_record->equipment,'user'=>$credit_record->user]);
                $eq_banned->user = $credit_record->user;
                if (!$GLOBALS['preload']['people.multi_lab']) {
                    $lab = Q("$credit_record->user lab")->current();
                }
                $eq_banned->object = $credit_record->equipment;
                $eq_banned->is_from_credit_limit = 1;
                $eq_banned->lab = $lab;
                $eq_banned->atime  = 0;
                $eq_banned->reason = I18N::T('credit',"单台仪器扣分项{$eq_ban_limit->score}次时，自动加入该仪器黑名单");
                $eq_banned->save();
                //发消息
                $key = 'credit.eq_ban';
                Notification::send($key, $eq_banned->user, [
                    '%user' => Markup::encode_Q($eq_banned->user),
                    '%time' => Date::format(time(), 'Y/m/d H:i:s'),
                    '%reason' => I18N::T('credit','多次触发单台仪器扣分项'),
                    '%scope' => "仪器黑名单",
                ]);
            }
        }

        //信用分变更通知
        Event::trigger('notification.after.credit_record_saved', $credit_record);
    }

    /**
     * 用户总分重新计算后, 根据新的总分触发奖惩规则
     */
    public static function on_credit_saved($e, $credit, $old_data, $new_data)
    {
        //初始化就不要触发了
        if (!$old_data['id'] && $new_data['id']) {
            return true;
        }
        $user = $credit->user;
        // list($token, $backend) = explode('|', $user->token);

        /**
         * @todo 目前奖惩都不算, 之后是否要改成只算奖不算惩的逻辑?
         */

        if (Q("perm[name=管理所有内容|name=管理组织机构] role {$user}")->total_count()) {
            return true;
        }

        /**
         * @todo 各达各种分后发邮件
         */

        /**
         * @todo 这里应该在加schema加两张分值奖惩表, 根据用户当前的总分进行奖惩
         * 一张表是分值对应奖惩明细
         * 一张表是奖惩明细
         */

        foreach (Q('credit_measures') as $measure) {

            $donot = false;

            $res = Credit_Limit::check_condition($user, $measure);

            $setting = O('notification_read_setting', ['source' => $measure, 'user' => $user]);
            if ($setting->id) {
                //获取用户特殊设置
                $limit = $res['limit'];
                if ($credit->total < $limit->score) {
                    $setting->delete();
                }
                $donot = true;
            }

            if ($res['status']) {
                Event::trigger('trigger_measures_' . $measure->ref_no, $user, $res['limit']);

                // 到达阈值发消息, 这里触发奖励措施了也应该发消息
                if(!$donot) {
                    Event::trigger('notification.' . $measure->ref_no, $credit, $res['limit'], $measure->ref_no);
                }
            }
        }
    }

    public static function reserv_permission_check($e, $view) {
        if ($view->calendar->type != 'eq_reserv') {
            return;
        }
        $check_list = $view->check_list;
        $me = L('ME');
        $equipment = $view->calendar->parent;
        if (($me->access('为所有仪器添加预约'))
            || ($me->group->id && $me->access('为下属机构仪器添加预约') && $me->group->is_itself_or_ancestor_of($equipment->group))
            || ($me->access('为负责仪器添加预约') && Equipments::user_is_eq_incharge($me, $equipment))
        ) {
            $check_list[] = [
                'title' => I18N::T('credit', '信用分资格限制'),
                'result' => true,
                'description' => ''
            ];
        } else {
            if (!$equipment->accept_reserv) {
                $check_list[] = [
                    'title' => I18N::T('credit', '信用分资格限制'),
                    'result' => true,
                    'description' => ''
                ];
            } else {
                $e = new StdClass();
                Credit_Limit::can_not_reserv($e, $equipment, [$me]);
                if ($e->return_value) {
                    $check_list[] = [
                        'title' => I18N::T('credit', '信用分资格限制'),
                        'result' => false,
                        'description' => I18N::T('credit', '信用分不足')
                    ];
                }else{
                    $check_list[] = [
                        'title' => I18N::T('credit', '信用分资格限制'),
                        'result' => true,
                        'description' => ''
                    ];
                }

            }
        }
        $view->check_list = $check_list;
    }

    public static function credit_record_before_delete($e, $credit_record)
    {
        //明细记录删除后，原来加的分减掉，原来减的分加上
        $credit = O('credit', ['user' => $credit_record->user]);
        $credit_rule = $credit_record->credit_rule;
        if ($credit_rule->type) {
            $total = $credit->id ? ($credit->total + $credit_rule->score) : -$credit_rule->score;
        } else {
            $total = $credit->id ? ($credit->total - $credit_rule->score) : -$credit_rule->score;
        }
        $credit->total = $total;
        $credit->save();
    }

}
