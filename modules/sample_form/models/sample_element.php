<?php

class Sample_Element_Model extends Presentable_Model {

    const STATUS_WAIT = 0;
    const STATUS_TODO = 1;
    const STATUS_DOING = 2;
    const STATUS_DONE = 3;
    const STATUS_REVIEW = 4;
    const STATUS_COMPLETE = 5;
    const STATUS_REJECT = 6;

    public static $STATUS_LABEL = [
        self::STATUS_WAIT => '待提测',
        self::STATUS_TODO => '待检测',
        self::STATUS_DOING => '检测中',
        self::STATUS_DONE => '检测完成',
        self::STATUS_REVIEW => '待审核',
        self::STATUS_COMPLETE => '审核通过',
        self::STATUS_REJECT => '审核驳回',
    ];

    public static $CONNECT_STATUS = [
        self::STATUS_DOING,
        self::STATUS_DONE,
    ];

    public static $LOCK_STATUS = [
        self::STATUS_REVIEW,
        self::STATUS_COMPLETE,
    ];

    public function toString() {
        return I18N::T('sample_form', '%user(%element_name) %time', [
            '%user' => $this->user->name,
            '%element_name' => $this->eq_element,
            '%time' => Date::format($this->ctime, 'Y/m/d H:i')
        ]);
    }
}

