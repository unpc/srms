<?php

class DC_Record_Model extends Presentable_Model {

	const IN_DOOR = 1;
	const OUT_DOOR = 0;

	static $direction = [
		self::OUT_DOOR => '出门',
		self::IN_DOOR => '进门',
	];

	const STATUS_SUCCESS = 1;
	const STATUS_FAIL = 0;
	static $status = [
		self::STATUS_SUCCESS => '成功',
		self::STATUS_FAIL => '失败',
	];

    //所有记录
    const FILTER_ALL = 0;
    //每日最早记录
    const FILTER_EARLIEST = 1;
    //每日最晚记录
    const FILTER_LATEST = 2;

	function & links($mode = 'index') {
		$uniqid = 'dc_record_'.uniqid();
		$links = new ArrayIterator;
		switch ($mode) {
		case 'index':
		default:
			if (L('ME')->is_allowed_to('删除', $this)) {
                $links[] = [
                    'url' => '#',
					'tip' => I18N::T('entrance','删除'),
					'text'  => I18N::T('entrance','删除'),
					'extra'=> 'style="color:#F5222D" class="blue" q-object="delete_record" q-event="click" q-static="'.
                        H(['id'=>$this->id,'uniqid'=>$uniqid]).'" q-src="'.H(URI::url('!entrance/dc_record/index')).'"',
				];
			}
        }

        Event::trigger('dc_record.links_edit', $this, $links, $mode);
		return (array) $links;
	}
}
