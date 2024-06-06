<?php

class Announce_Approval_Model extends Presentable_Model
{
    const STATUS_APPROVAL = 0;
    const STATUS_PASS = 1;
    const STATUS_REBUT = 2;

    static $share_status = [
        self::STATUS_APPROVAL => '待审核',
        self::STATUS_PASS => '通过',
        self::STATUS_REBUT => '驳回',
    ];
}
