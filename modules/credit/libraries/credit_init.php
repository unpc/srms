<?php

// 用户信用分
class Credit_Init
{
    public static function create_orm_tables()
    {
        self::load_default_credit_rules(); // 加载系统默认规则
        self::create_credit_default_level(); // 新建用户等级
        self::create_credit_default_measures(); // 创建默认奖惩措施
        self::clear_eq_ban_settings();
    }

    public static function load_default_credit_rules()
    {
        $default_credits = Config::get('credit.default');
        foreach ($default_credits as $key => $credit) {
            $credit_rule = O('credit_rule', ['ref_no' => $key]);
            if ($credit_rule->id) {
                continue;
            }

            $credit_rule->ref_no      = $key;
            $credit_rule->name        = $credit['title'];
            $credit_rule->score       = $credit['score'] ?: 0;
            $credit_rule->hidden      = isset($credit['hidden']) ? $credit['hidden'] : Credit_Rule_Model::NOT_HIDE_ITEMS;
            $credit_rule->type        = $credit['type']; // 加分项
            $credit_rule->is_custom   = isset($credit['is_custom']) ? $credit['is_custom'] : Credit_Rule_Model::STATUS_SYSTEM; // 非自定义项
            $credit_rule->is_disabled = isset($credit['is_disabled']) ? $credit['is_disabled'] : Credit_Rule_Model::DISABLED; // 该条目启用
            $credit_rule->description = $credit['description'];
            $credit_rule->save();
        }
    }

    // 新建用户等级
    public static function create_credit_default_level($force_refresh = false)
    {
        $default_credit_levels = Config::get('credit.default_levels');
        foreach ($default_credit_levels as $level => $name) {
            $credit_level = O('credit_level', ['level' => $level]);
            if ($credit_level->id && !$force_refresh) {
                continue;
            }

            $credit_level->level      = $level;
            $credit_level->name       = $name;
            $credit_level->rank_start = (5 - $level) / 5 * 100;
            $credit_level->rank_end   = (5 - ($level - 1)) / 5 * 100;
            $credit_level->save();
        }
    }

    public static function create_credit_default_measures()
    {
        $default_measures = Config::get('credit.default_measures');
        foreach ($default_measures as $ref_no => $name) {
            $credit_measures = O('credit_measures', ['ref_no' => $ref_no]);
            if ($credit_measures->id) {
                continue;
            }

            $credit_measures->ref_no = $ref_no;
            $credit_measures->name   = $name;
            $credit_measures->ctime  = Date::time();
            $credit_measures->save();
            //新建默认对应的限制条件
            $credit_limit           = O('credit_limit');
            $credit_limit->measures = $credit_measures;
            $credit_limit->save();
        }
    }

    /**
     * 旧站点升级, 清除之前的加入黑名单触发条件
     */
    public static function clear_eq_ban_settings()
    {
        Lab::set('equipment.max_allowed_miss_times', 0);
        Lab::set('equipment.max_allowed_leave_early_times', 0);
        Lab::set('equipment.max_allowed_overtime_times', 0);
        Lab::set('equipment.max_allowed_late_times', 0);
    }
}
