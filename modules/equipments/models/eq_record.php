<?php

class EQ_Record_Model extends Presentable_Model {

    // const USE_TYPE_USING = 1;
    // const USE_TYPE_TRAINING = 2;
    // const USE_TYPE_TEACHING = 3;
    // const USE_TYPE_MAINTENANCE = 4;

    // static $use_type = [
    //     self::USE_TYPE_USING => '使用',
    //     self::USE_TYPE_TRAINING => '培训',
    //     self::USE_TYPE_TEACHING => '教学',
    //     self::USE_TYPE_MAINTENANCE => '维护保养',
	// ];
	
	// 为了测试临时调整为西交大类型
	const USE_TYPE_USING = 1;
    const USE_TYPE_TRAINING = 2;
    const USE_TYPE_TEACHING = 3;
    const USE_TYPE_MAINTENANCE = 4;
    const USE_TYPE_TESTING = 5;
    const USE_TYPE_ANALYZING = 6;

    static $use_type = [
        self::USE_TYPE_USING => '使用',
        self::USE_TYPE_TRAINING => '培训',
        self::USE_TYPE_TEACHING => '教学',
        self::USE_TYPE_MAINTENANCE => '保养维修',
        self::USE_TYPE_TESTING => '委托测试',
        self::USE_TYPE_ANALYZING => '数据分析',
    ];

	protected $object_page = [
		'view'=>'!equipments/records/index.%id[.%arguments]',
		//'edit'=>'!equipments/equipment/edit.%id[.%arguments]',
		//'delete'=>'!equipments/equipment/delete.%id[.%arguments]',
	];
	
	//用户使用仪器的反馈状态

	const FEEDBACK_NOTHING = 0;
	const FEEDBACK_PROBLEM = -1;
	const FEEDBACK_NORMAL = 1;

    static $status_type = [
        self::FEEDBACK_NOTHING => '未反馈',
        self::FEEDBACK_PROBLEM => '故障',
        self::FEEDBACK_NORMAL => '正常',
    ];

	function & links($mode= 'index', $ajax_id = '') {
		$links = new ArrayIterator;		
		
		switch($mode){
		case 'index':
		default:
			$me = L('ME');
			if ($me->is_allowed_to('反馈', $this) && !$this->is_locked) {
				$equipment = $this->equipment;
				$links['feedback'] = [
					'url' => '#',
                    'text' => I18N::T('equipments', '反馈'),
					'tip' => I18N::T('equipments', '反馈'),
					'extra'=>'class="blue" q-event="click" q-object="feedback_edit" q-static="record_id='.$this->id.'" q-src="'.$equipment->url('records').'"',
				];
                //浙江医学院实时推送之后判断是否能修改
                $canEdit = Event::trigger('eq_charge_can_edit',$this);
                if(true === $canEdit){
                    unset($links['feedback']);
                }
			}
			
			if ($me->is_allowed_to('修改', $this) && !$this->is_locked) {
				$links['edit'] = [
					'url' => '#',
					'tip' => I18N::T('equipments', '编辑'),
					'text'  => I18N::T('equipments', '编辑'),
				 	'extra'=>' class="blue" q-event="click" q-object="record_edit" q-static="record_id='.$this->id.'" q-src="'.$this->url().'"',
				];
				$reserv = $this->reserv;
				$record = Q("eq_record[reserv={$reserv}][dtend>0]:limit(1):sort(id D)")->current();
				if ($this->dtend && ($this->flag == EQ_Reserv_Model::LEAVE_EARLY || $this->flag == EQ_Reserv_Model::LATE_LEAVE_EARLY)) {
					// $links['clear_leave_early'] = [
					// 	'url' => '#',
					// 	'text'  => I18N::T('equipments', '取消早退'),
					// 	'extra'=>' class="blue" q-event="click" q-object="clear_leave_early" q-static="record_id='.$this->id.'" q-src="'.$this->url().'"',
					// ];
				}
                //浙江医学院实时推送之后判断是否能修改
                $canEdit = Event::trigger('eq_charge_can_edit',$this);
                if(true === $canEdit){
                    unset($links['edit']);
                }
			}
			
			if ($me->is_allowed_to('下载文件', $this, ['type' => 'attachments'])) {
				$n_attachments = NFS::count_attachments($this);
				if ($n_attachments > 0) {
					$links['attachments'] = [
						'url' => '#',
						'tip' => I18N::T('equipments', '结果(%num)', ['%num' => $n_attachments]),
						'text'  => I18N::T('equipments', '(%num)', ['%num' => $n_attachments]),
						//'extra' => 'class="blue" q-object="download_attachments" q-event="click" q-static="' .  H(['id'=>$this->id]) . '" q-src="' . URI::url('!equipments/records') . '"',
						'extra' => 'class="blue" q-object="download_attachments" q-event="click" q-static="' .  H(['id'=>$this->id]) . '" q-src="' . URI::url('!equipments/records') . '"',
						];
				}
			}	

			if ($me->is_allowed_to('锁定', $this)) {
				$extra =  'q-event="click" 
							q-static="'. H(['ajax_id'=>$ajax_id, ]). '" 
                            q-src="'.H($this->url()).'"';
				if (!$this->is_locked) {
					$links['lock'] = [
                        'url' => '#',
						'text'  => I18N::T('equipments', '锁定'),
						'tip' => I18N::T('equipments', '锁定'),
						'extra' => 'class="blue" q-object="lock" '. $extra.'',
					];
				} else {
					$links['unlock'] = [
						'url' => '#',
						'text'  => I18N::T('equipments', '解锁'),
						'tip' => I18N::T('equipments', '解锁'),
						'extra' => 'class="blue" q-object="unlock" ' . $extra.'',
					];
				}
			}

			Event::trigger('record.links_edit', $this, $links, $mode, $ajax_id);
			break;
		}

		return (array) $links;
	}
	
	function delete() {
		$equipment = $this->equipment;
		$user = $this->user;
		$me = L('ME');

		Log::add(strtr('[equipments] %admin_name[%admin_id] 删除 %equipment_name[%equipment_id] 的使用记录[%record_id]: %user_name[%user_id] %dtstart - %dtend', ['%admin_name'=> $me->name, '%admin_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%record_id'=> $this->id, '%user_name'=> $user->name, '%user_id'=> $user->id, '%dtstart'=> Date::format($this->dtstart), '%dtend'=> Date::format($this->dtend)]), 'record');

		return parent::delete();
	}

	function save($overwrite=FALSE) {
		/*
		  系统显示时间时，从不显示秒数，
		  为避免一些用户难以理解的超时、计费有零有整的bug，
		  在保存时，调整开始时间到分钟开始，结束时间也到分钟开始
		  (xiaopei.li@2011.07.22)

		  系统调整 按照秒为单位 避免分钟对齐产生的差异 因此不用再对齐
		  (jia.huang@2011.08.30)
		*/

		$create_flag = $this->id ? FALSE : TRUE;
		$this->mtime = time();
		$ret = parent::save($overwrite);
		if ($ret) {
			$equipment = $this->equipment;
			$user = $this->user;
			$me = L('ME');
            if (!$me->id) {
                $me = new stdClass();
                $me->id = 0;
                $me->name = '系统自动校正';
            }
            if ($create_flag) {
                Log::add(strtr('[equipments] %admin_name[%admin_id] 创建了 %equipment_name[%equipment_id] 的使用记录[%record_id]: %user_name[%user_id] %dtstart - %dtend', ['%admin_name'=> $me->name, '%admin_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%record_id'=> $this->id, '%user_name'=> $user->name, '%user_id'=> $user->id, '%dtstart'=> Date::format($this->dtstart), '%dtend'=> $this->dtend == 0 ? '未知' : Date::format($this->dtend)]), 'record');
            }
            else {
                Log::add(strtr('[equipments] %admin_name[%admin_id] 修改了 %equipment_name[%equipment_id] 的使用记录[%record_id]: %user_name[%user_id] %dtstart - %dtend', ['%admin_name'=> $me->name, '%admin_id'=> $me->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%record_id'=> $this->id, '%user_name'=> $user->name, '%user_id'=> $user->id, '%dtstart'=> Date::format($this->dtstart), '%dtend'=> $this->dtend == 0 ? '未知' : Date::format($this->dtend)]), 'record');
            }
		}
		return $ret;
	}

	function is_using() {
		if( !$this->id || $this->dtend || L('ME')->access('管理所有内容')){
			return FALSE;
		}
		else{
			return TRUE;
		}
	}

    function __get($property) {
        //先获取真实存储值
		$data = parent::__get($property);

        if (
            in_array($property, [
                'dtstart',
                'dtend',
                'samples',
            ])
           ) {
            // trigger进行校正
			$edata = Event::trigger("eq_record_model.get.{$property}", $this, $data);
            $data = is_null($edata) ?$data : $edata;
        }

        //return
        return $data;
    }

    //进行samples的锁定操作
    function lock_samples($samples = 0) {

        if ($samples) $this->set('samples',  $samples)->save();

        return P($this)->set('samples_lock', TRUE)->save();
    }

    //进行samples的解锁操作
    function unlock_samples() {
        return P($this)->set('samples_lock', FALSE)->save();
    }
}
