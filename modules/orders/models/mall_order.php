<?php

class Mall_Order_Model extends ORM_Model {

    //待确认
    // const STATUS_DRAFT = 0;
    const STATUS_NEED_VENDOR_APPROVE = 0;
    //待审核
    const STATUS_PENDING_APPROVAL = 1;

    //待付款
    const STATUS_APPROVED = 2;

    //退货中
    const STATUS_PENDING = 3;

    //付款中
    const STATUS_PENDING_TRANSFER = 4;

    //已付款
    const STATUS_TRANSFERRED = 5;

    //待结算
    const STATUS_PENDING_PAYMENT = 6;

    //已结算
    const STATUS_PAID = 7;

    //已取消
    const STATUS_CANCELED = 8;

    //待批准
    // const STATUS_PENDING_CUSTOMER_APPROVAL = 9;
    const STATUS_REQUESTING = 9;
    //退货申请
    const STATUS_RETURNING_APPROVAL = 10;
    //待买方确认
    const STATUS_NEED_CUSTOMER_APPROVE = 11;

    //收货状态
    const DELIVER_STATUS_NOT_DELIVERED = 0;
    const DELIVER_STATUS_DELIVERED = 1;
    const DELIVER_STATUS_RECEIVED = 2;

    //通过审核
    /*
    public function pass_examine() {

        return ! in_array($this->status, array(
            self::STATUS_DRAFT,
            self::STATUS_PENDING_APPROVAL,
            self::STATUS_PENDING_CUSTOMER_APPROVAL
        ));
    }
    */

}
