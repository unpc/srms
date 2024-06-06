<?php

// 信用分规则项
class Credit_Rule_Model extends ORM_Model
{
    const STATUS_ADD = 0; // 加分
    const STATUS_CUT = 1; // 扣分

    public static $status = [
        self::STATUS_ADD => '加分项',
        self::STATUS_CUT => '扣分项',
    ];

    const STATUS_SYSTEM = 0; // 系统规则
    const STATUS_CUSTOM = 1; // 用户自定义规则

    const ENABLED  = 0; // 启用
    const DISABLED = 1; // 禁用

    const NOT_HIDE_ITEMS = 0; // 非隐藏规则
    const HIDE_ITEMS     = 1; // 隐藏规则

    const CUSTOM_ADD = 'custom_add'; //自定义加分项规则ref_no
    const CUSTOM_CUT = 'custom_cut'; //自定义减分项规则ref_no

    public function save($overwrite = false)
    {
        if (!$this->ctime) {
            $this->ctime = Date::time();
        }
        return parent::save($overwrite);
    }
}
