<?php

class Neu_EQ_Charge_Model extends Presentable_Model {

    const CONFIRM_EMPTY = 0; // 历史数据为空
    const CONFIRM_PENDING = 1; // 待审核
    const CONFIRM_PRINT = 2; // 待打印
    const CONFIRM_CONFIRM = 3; // 待确认
    const CONFIRM_DONE = 4; // 已完成

    static $confirm = [
        self::CONFIRM_EMPTY => '--',
        self::CONFIRM_PENDING => 'pending',
        self::CONFIRM_PRINT => 'print',
        self::CONFIRM_CONFIRM => 'confirm',
        self::CONFIRM_DONE => 'done',
    ];
}
