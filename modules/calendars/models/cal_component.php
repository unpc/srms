<?php

class Cal_Component_Model extends Presentable_Model {
	
	//事件类型
	const TYPE_VEVENT = 0;
	const TYPE_VTODO = 1;
	const TYPE_VJOURNAL = 2;
	const TYPE_VFREEBUSY = 3;
	
	static $form = [];
	
	function color($calendar) {
		if ($this->type==self::TYPE_VFREEBUSY) {
			$default = 7;
		}
		else {
			$default = (int)(($this->calendar->id + $this->organizer->id + $this->calendar->parent->id) % 6);
		}

		$value = Event::trigger("calendar.component.get_color", $this, $calendar);
		
		if (is_null($value)) return $default;
		if ($value != 7) return (int) ($value % 6);
		return $value;
	}
	
	static function prerender_component($e, $view) {
		$view->component_form = [
			'name' => [
				'label'=>I18N::T('calendars', '主题'),
				'weight'=>10,
			],
			'organizer' => [
				'label'=>I18N::T('calendars', '组织者'),
				'weight'=>20,
			],
			'location' => [
				'label'=>I18N::T('calendars', '地址'),
				'weight'=>30,
			],
			'dtstart' => [
				'label'=>I18N::T('calendars', '起始时间'),
				'weight'=>40,
			],
			'dtend' => [
				'label'=>I18N::T('calendars', '结束时间'),
				'weight'=>50,
			],
			'attendees' => [
				'label'=>I18N::T('calendars', '相关人员'),
				'weight'=>60,
			],
			'attachments' => [
				'label'=>I18N::T('calendars', '附件'),
				'weight'=>70,
			],
			'url' => [
				'label'=>I18N::T('calendars', '网址'),
				'weight'=>80,
			],
			'description' => [
				'label'=>I18N::T('calendars', '备注'),
				'weight'=>90,
			],
		];
	}

	static function prerender_permission_check($e, $view) {
		$view->check_list = [
			[
				'title' => I18N::T('calendars', '用户状态'),
				'result' => true,
				'description' => ''
			]
		];
	}
	
	static function cmp($a, $b) {
		$a = $a['weight'];
		$b = $b['weight'];
		if ($a==$b) return 0;
		return $a < $b ? -1 : 1;
	}
	

	//使用预约table的link
	function & links($mode='list') {

		$me = L('ME');

		$links = new ArrayIterator;

		if ($me->is_allowed_to('修改', $this)) {
			$links['edit'] = [
				'text' => I18N::HT('calendars', '修改'),
				'tip' => I18N::HT('calendars', '修改'),
				'url' => '#',
				'extra' => 'class="blue" q-src="'
				. H($this->calendar->url($mode)) . '"'
				. 'q-object="edit_component" q-event="click"'
			    . 'q-static="' . H(['id'=>$this->id,'dtstart'=>$this->dtstart,'dtend'=>$this->dtend]) . '"'	
				,
			];
		}

		Event::trigger('cal_component.links', $this, $links, $mode);

		return (array) $links;
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
            $data = Event::trigger("cal_component_model.get.{$property}", $this, $data) ? : $data ;
        }

        //return
        return $data;
    }
}
