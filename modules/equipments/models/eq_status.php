<?php

class EQ_Status_Model extends Presentable_Model {

	const IN_SERVICE = 0;
	const OUT_OF_SERVICE = 1;
	const NO_LONGER_IN_SERVICE = 2;

	static $status = [
		self::IN_SERVICE=>'正常',
		self::OUT_OF_SERVICE=>'暂时故障',
		self::NO_LONGER_IN_SERVICE=>'报废',
	];
	
	static $normal_status = [
		self::IN_SERVICE=>'正常',
		self::OUT_OF_SERVICE=>'暂时故障',
	];
}
