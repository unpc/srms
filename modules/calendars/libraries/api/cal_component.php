<?php

class API_Cal_Component extends API_Calendar {

	public static $errors = [
        1001 => '请求非法!',
        1002 => '您没有权限进行该操作!', 
        1003 => '用户注册失败',
        1010 => '用户信息不合法!',
        1011 => '用户保存失败',
    ];

	public function get($id) {
        $this->_checkAuth();

		$data = [];

		$component = O('cal_component', $id);

		if ($component->id) {
			$data['id'] = $id;
			$data['calendar'] = $component->calendar->parent->id;
			$data['organizer'] = $component->organizer->id;
			$data['name'] = $component->name;
			$data['description'] = $component->description;
			$data['type'] = $component->type;
			$data['dtstart'] = $component->dtstart;
			$data['dtend'] = $component->dtend;
		}
		
		return $data;
	}

	public function fetch($id, $dtstart, $dtend) {
        $this->_checkAuth();

		$data = [];

		$calendar = O('calendar', $id);
		
		$components = Q("cal_component[calendar=$calendar][dtstart=$dtstart~$dtend]");
		if ($components) foreach ($components as $component) {
			$item = [];
			$item['id'] = $id;
			$item['calendar'] = $component->calendar->parent->id;
			$item['organizer'] = $component->organizer->id;
			$item['name'] = $component->name;
			$item['description'] = $component->description;
			$item['type'] = $component->type;
			$item['dtstart'] = $component->dtstart;
			$item['dtend'] = $component->dtend;
			$data[] = $item;
		}

		return $data;
	}

	public function create($id, $data) {
        $this->_checkAuth();

		$me = O('user', $data['userId']);

		Cache::L('ME', $me);

		$calendar = O('calendar', $id);

		$component = O('cal_component');
 		$component->calendar = !$component->calendar->parent->id ? $calendar : $component->calendar;
		$component->organizer = $data['organizer'] ? O('user', $data['organizer']) : $component->organizer;
		$component->name = $data['name'];
		$component->description = $data['description'];
		$component->type = isset($data['type']) ? $data['type'] : '';
		
		//调整位置 
		Event::trigger('calendar.component_form.submit', Form::filter($data), $component, ['calendar'=>$calendar]);

	    $dtstart = $data['dtstart'];
        $dtend = $data['dtend'];
        if ($dtstart > $dtend) {
            list($dtstart, $dtend) = [$dtend, $dtstart];
        }
        $component->dtstart = $dtstart;
        $component->dtend = $dtend;

        //Log处理机制	
        $msg = Event::trigger('calendar.component_form.attempt_submit.log', $data, $component, $calendar) ? :
		sprintf('[calendars] %s[%d] 于 %s 尝试创建新的预约!', $me->name, $me->id, Date::format(Date::time()));		
		Log::add($msg, 'journal');

		if ($me->is_allowed_to('添加', $component)) {
			define('CLI_MODE', 1);
			Config::load(LAB_PATH, 'system');
			$base_url = Config::get('system.base_url');
			define('CLI_MODE', 0);
			Config::load(LAB_PATH, 'system');

			if ($base_url) {
				Config::set('system.base_url', $base_url);
				Config::set('system.script_url', $base_url);
			}
			
			$result = $component->save();
			$merge = L('MERGE_COMPONENT_ID');

			if ($result) {
                Cache::L('YiQiKongReservAction', true); // bug: 21835 用户预约后发了两条提醒消息
                if (isset($form['count'])) $form['extra_fields']['count'] = $form['count'];
				Event::trigger('calendar.component_form.post_submit', $component, $form);
				
				Log::add(strtr('[calendars] %user_name[%user_id] 于 %date 成功创建新的预约[%component_id]!', array(
					'%user_name' => $me->name,
					'%user_id' => $me->id,
					'%date' => Date::format(Date::time()),
					'%component_id' => $component->id,
					)), 'journal');
						
				return [ 
					'success' => 1,
					'id' => $component->id
				];
			}

			if ($merge) {
				$component = O('cal_component', $merge_yet);
				Log::add(strtr('[calendars] %user_name[%user_id] 于 %date 合并预约[%component_id], 时间为[%dtstart ~ %dtend]', array(
						'%user_name' => $me->name,
						'%user_id' => $me->id,
						'%date' => Date::format(Date::time()),
						'%component_id' => $merge_yet,
						'%dtstart' => Date::format($component->dtstart),
						'%dtend' => Date::format($component->dtend)
						)), 'journal');

				$ids = join(',', (array)L('REMOVE_COMPONENT_IDS'));

				Cache::L('MERGE_COMPONENT_ID', NULL);
				Cache::L('REMOVE_COMPONENT_IDS', NULL);

				return [
					'success' => 1,
					'id' => $merge,
					'merges' => $ids
				];
			}

			return [
				'success' => 0, 
				'msg' => I18N::T('calendar', '添加预约失败!')
			];
        }
        else {
        	$messages = Lab::messages(Lab::MESSAGE_ERROR);
			if (count($messages) > 0) {
				$errorMsg = implode(', ', $messages);
			}
        }

        return [
			'success' => 0, 
			'msg' => $errorMsg
		];
	}

	public function update($id, $data) {
        $this->_checkAuth();
		
		$me = O('user', $data['userId']);

		Cache::L('ME', $me);

		$calendar = O('calendar', $id);

		$component = O('cal_component', $form['id']);
 		$component->calendar = !$component->calendar->parent->id ? $calendar : $component->calendar;
		$component->organizer = $data['organizer'] ? O('user', $data['organizer']) : $component->organizer;
		$component->name = $data['name'];
		$component->description = $data['description'];
		$component->type = isset($data['type']) ? $data['type'] : '';

		$oldId = $component->id;
		
		//调整位置 
		Event::trigger('calendar.component_form.submit', Form::filter($data), $component, ['calendar' => $calendar]);

	    $dtstart = $data['dtstart'];
        $dtend = $data['dtend'];
        if ($dtstart > $dtend) {
            list($dtstart, $dtend) = [$dtend, $dtstart];
        }
        $component->dtstart = $dtstart;
        $component->dtend = $dtend;

        //Log处理机制	
        $msg = Event::trigger('calendar.component_form.attempt_submit.log', $form, $component, $calendar) ? :
		sprintf('[calendars] %s[%d] 于 %s 尝试修改预约[%d]!', $me->name, $me->id, Date::format(Date::time()), $component->id);
		Log::add($msg, 'journal');

		if ($me->is_allowed_to('修改', $component)) {
			define('CLI_MODE', 1);
			Config::load(LAB_PATH, 'system');
			$base_url = Config::get('system.base_url');
			define('CLI_MODE', 0);
			Config::load(LAB_PATH, 'system');

			if ($base_url) {
				Config::set('system.base_url', $base_url);
				Config::set('system.script_url', $base_url);
			}
			
			$result = $component->save();
			$merge = L('MERGE_COMPONENT_ID');
			if ($result) {
                Cache::L('YiQiKongReservAction', true); // bug: 21835 用户预约后发了两条提醒消息
                if (isset($form['count'])) $form['extra_fields']['count'] = $form['count'];
				Event::trigger('calendar.component_form.post_submit', $component, $form);

				Log::add(strtr('[calendars] %user_name[%user_id] 于 %date 成功修改预约[%component_id]!', array(
					'%user_name' => $me->name,
					'%user_id' => $me->id,
					'%date' => Date::format(Date::time()),
					'%component_id' => $component->id,
					)), 'journal');
						
				return [ 
					'success' => 1,
					'id' => $component->id
				];
			}

			if ($merge) {
				$component = O('cal_component', $merge_yet);
				Log::add(strtr('[calendars] %user_name[%user_id] 于 %date 合并预约[%component_id], 时间为[%dtstart ~ %dtend]', array(
						'%user_name' => $me->name,
						'%user_id' => $me->id,
						'%date' => Date::format(Date::time()),
						'%component_id' => $merge_yet,
						'%dtstart' => Date::format($component->dtstart),
						'%dtend' => Date::format($component->dtend)
						)), 'journal');

				$ids = join(',', (array)L('REMOVE_COMPONENT_IDS'));

				Cache::L('MERGE_COMPONENT_ID', NULL);
				Cache::L('REMOVE_COMPONENT_IDS', NULL);

				return [
					'success' => 1,
					'id' => $merge,
					'merges' => $ids
				];
			}

			return [
				'success' => 0, 
				'msg' => I18N::T('calendar', '修改预约失败!')
			];
        }
        else {
        	$messages = Lab::messages(Lab::MESSAGE_ERROR);
			if (count($messages) > 0) {
				$errorMsg = implode(', ', $messages);
			}
        }

        return [
			'success' => 0, 
			'msg' => $errorMsg
		];
	}

	public function delete($id, $data) {
        $this->_checkAuth();

		$user = O('user', $data['userId']);
		$component = O('cal_component', $id);

		$me = L('ME', $user);
		
		if($user->is_allowed_to('删除', $component)) {
            if (Event::trigger('calendar.component_form.before_delete', $data, $component)) {
                return FALSE;
            }
            else {
				$overrided = Event::trigger('calendar.component_form.delete', $data, $component);
				if ($overrided || $component->delete()) {
					Event::trigger('calendar.component_form.after_delete', $data, $component);				
					return [ 
						'success' => 1,
						'msg' => '删除成功'
					];
				}
				else {
					return [ 
						'success' => 0,
						'msg' => '删除失败'
					];
				}
            }
		}
		else{
			$messages = Lab::messages(Lab::MESSAGE_ERROR);
			if (count($messages) > 0) {
				$errorMsg = implode("\n", $messages);
			}

			return [ 
				'success' => 0,
				'msg' => $errorMsg
			];
		}
	}

}
