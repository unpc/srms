<?php

class Szu_EQ_Reserv_Model extends Presentable_Model {

    const STATE_APPROVE = 'approve';
    const STATE_PASS = 'pass';
    const STATE_REJECT = 'reject';
    const STATE_CANCEL = 'cancel';
    const STATE_NO_NEED = 'no_need';

    const STATE_APPROVE_NEED = 'need';
    const STATE_APPROVE_DONE = 'done';
    const STATE_APPROVE_REJECT = 'reject';
    const STATE_APPROVE_CANCLE = 'cancel';

    static $state = [
        self::STATE_APPROVE => '申请中',
        self::STATE_PASS => '已批准',
        self::STATE_REJECT => '已驳回',
        self::STATE_CANCEL => '已删除',
    ];

    static $state_num = [
        self::STATE_APPROVE => 1,
        self::STATE_PASS => 2,
        self::STATE_REJECT => 3,
        self::STATE_CANCEL => 4,
        self::STATE_NO_NEED => 5
    ];

    static $approve_state = [
        self::STATE_APPROVE_NEED => '待审核',
        self::STATE_APPROVE_DONE => '已批准',
        self::STATE_APPROVE_REJECT => '已驳回',
        self::STATE_APPROVE_CANCLE => '已删除',
    ];
}
