<?php

class EQ_Reserv_Model extends Presentable_Model {

	const PENDING = 0;
	const NORMAL = 1;
	const MISSED = 2;
	//const INADVERTENTLY_MISSED = 3;
	const OVERTIME = 4;
	const LATE = 5;
	const LATE_OVERTIME = 6;
	const LEAVE_EARLY = 7;
	const LATE_LEAVE_EARLY = 8;
	
	//预约迟到后, 能否使用仪器
	const LATE_USE_ALLOW = 1;
	const LATE_USE_BANISH = 2;
	static $late_use = [ 
		self::LATE_USE_ALLOW => '允许', 
		self::LATE_USE_BANISH => '禁止'
	];
	
	static $reserv_status = [
		self::PENDING => '待定',
		self::NORMAL => '正常使用',
		self::MISSED => '爽约',
		self::LATE_OVERTIME => '迟到&超时',
		// self::INADVERTENTLY_MISSED => '非故意爽约',
		self::OVERTIME => '超时',
		self::LATE => '迟到',
		self::LATE_LEAVE_EARLY => '迟到&早退',
		self::LEAVE_EARLY => '早退',
	];

	static $ban_status_settings = [
		self::LATE => '迟到',
		self::OVERTIME => '超时',
		self::LEAVE_EARLY => '早退',
		self::MISSED => '爽约',
	];
	
	protected $object_page = [];
	
	function save($overwrite = FALSE) {
		return parent::save($overwrite);
	}

    function __get($property) {
        //先获取真实存储值
        $data = parent::__get($property);

        if (
            in_array($property, [
                'dtstart',
                'dtend',
            ])
           ) {
            //trigger进行校正
            $data = Event::trigger("eq_reserv_model.get.{$property}", $this, $data) ? : $data ;
        }

        //return
        return $data;
    }

	function & links($mode = 'index') {
		$reserv = O('eq_reserv', ['component'=>$this->component]);
		$me = L('ME');
			
        $static = [
            'id' => $this->component->id,
            'calendar_id' => $this->component->calendar->id,
            'dtstart' => $this->component->dtstart,
            'dtend' => $this->component->dtend,
            'mode' => 'list',
            'cal_week_rel' => true,
        ];

        if (($reserv->id && $this->user->id == $this->component->organizer->id)||$me->is_allowed_to('修改预约设置',$this->equipment)){
            $links['edit'] = [
                'url' => NULL,
                'text' => I18N::T('eq_sample', '修改'),
                'tip' => I18N::T('eq_sample','修改'),
                'extra' => 'class="blue" q-src="'.URI::url('!calendars/calendar').'" q-static="'.H($static).'" q-event="click" q-object="edit_component"',
            ];
        }

		return (array)$links;
	}	
}	
