<?php

class Lab_Project_Model extends Presentable_Model {

	const TYPE_EDUCATION = 0;
	const TYPE_RESEARCH = 1;
	const TYPE_SERVICE = 2;
	const TYPE_STATE = 3;
	const TYPE_PROVINCE = 4;
	const TYPE_COLLEGE = 5;

	static $types = [
		self::TYPE_RESEARCH=>'科研类项目',
		self::TYPE_EDUCATION=>'教学类项目',
		self::TYPE_STATE=>'国家级基金',
		self::TYPE_PROVINCE=>'省部级基金',
		self::TYPE_COLLEGE=>'校级基金',
		self::TYPE_SERVICE=>'社会服务类项目',
	];
	
	//仪器统计列表: 在Labs::get_statistic_options中调用
	static $stat_types = [
		self::TYPE_EDUCATION => 'project_education',
		self::TYPE_RESEARCH => 'project_research',
		self::TYPE_STATE=> 'project_state',
		self::TYPE_PROVINCE=>'project_province',
		self::TYPE_COLLEGE=>'project_college',
		self::TYPE_SERVICE => 'project_service',
	];
	
	const AWARDS_NATION = 0;
	const AWARDS_PROVINCE = 1;
	const PATENTS_TEACHER = 0;
	const PATENTS_STUDENT = 1;
	const PUBLICATIONS_TOP3INDEX = 0;
	const PUBLICATIONS_COREJOURNAL = 1;

	const STATUS_ACTIVED = 0;
	const STATUS_EXPIRED = 1;

	static $status = [
		self::STATUS_ACTIVED => '已激活',
		self::STATUS_EXPIRED => '已过期'
	];
}
