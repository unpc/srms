<?php

class EQ_Quota_Model extends Presentable_Model {

	const NO_APPROVAL = 0;
    const APPROVAL_QUOTA = 1;

    static $TYPES = [
        self::NO_APPROVAL => '不审核',
        self::APPROVAL_QUOTA => '额度审核',
    ];

}	
