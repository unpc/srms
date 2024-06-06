<?php

class Nrii_Device_Model extends Presentable_Model {

	// const TYPE_SPECIAL = 1;
	// const TYPE_COMMON = 2;

	// static $type_status = [
	// 	self::TYPE_SPECIAL => '专用',
	// 	self::TYPE_COMMON => '通用',
	// ];

	const STATUS_NORMAL = 1;
	const STATUS_AWAIT = 2;
	const STATUS_REMOTE_SERVICE = 3;
	const STATUS_OCCA_BREAK = 4;
	const STATUS_ALWAYS_BREAK = 5;
	const STATUS_WAIT_FIX = 6;
	const STATUS_WAIT_OFF = 7;

	static $status = [
		self::STATUS_NORMAL => '正常',
		self::STATUS_AWAIT => '待机',
		self::STATUS_REMOTE_SERVICE => '远程服务中',
		self::STATUS_OCCA_BREAK => '偶有故障',
		self::STATUS_ALWAYS_BREAK => '故障频繁',
		self::STATUS_WAIT_FIX => '待修',
		self::STATUS_WAIT_OFF => '待报废'
	];

	const SHARE_INNER = 1;
	const SHARE_OUTER = 2;
	const SHARE_NOTHING = 3;

	static $share_status = [
		self::SHARE_INNER => '内部共享',
		self::SHARE_OUTER => '外部共享',
		self::SHARE_NOTHING => '不共享'
	];

	const TYPE_SPECIAL = 1;
	const TYPE_COMMON = 2;
	const TYPE_PUBLIC = 3;

	static $device_category = [
		self::TYPE_SPECIAL => '专用研究设施',
		self::TYPE_COMMON => '公共实验设施',
		self::TYPE_PUBLIC => '公益服务设施',
	];

	const TYPE_BUILDING = 1;
	const TYPE_COMPLETE = 2;

	static $construction = [
		self::TYPE_BUILDING => '在建',
		self::TYPE_COMPLETE => '建成',
	];

	function & links($mode = NULL) {
		$links = new ArrayIterator;
		$me = L('ME');
	
		$links['edit'] = [
			'url' => URI::url('!nrii/device/edit.'.$this->id),
			'text'  => I18N::T('nrii', '编辑'),
			'extra'=>'class="blue"',
		];
		$links['delete'] = [
			'url'=> URI::url('!nrii/device/delete.'.$this->id),
			'tip'=>I18N::T('nrii','删除'),
			'extra'=>'class="blue" confirm="'.I18N::T('nrii','你确定要删除吗? 删除后不可恢复!').'"',
		];
	
		return (array) $links;
	}
}
