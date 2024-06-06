<?php
class EQ_Charge_Confirm_Model extends Presentable_Model {
    const CONFIRM_PENDDING = 0; // 等待确认
	const CONFIRM_INCHARGE = 1; // 确认完成

	private static $confirm = [
		self::CONFIRM_PENDDING => '等待确认',
        self::CONFIRM_INCHARGE => '确认完成'
	];

	static function confirm($key) {
        return self::$confirm[$key];
	}
	
	static function conforms() {
        return self::$confirm;
    }
}
