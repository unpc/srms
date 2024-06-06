<?php

class EQ_Current {

	const SAMPLE_INTERVAL = 60;
	const DEFAULT_THRESHOLD = 50;

	static function on_command_current($e, $device, $struct) {
		
		$agent = $device->agent(0);
		$equipment = $agent->object;

		$threshold = $equipment->current_threshold;
		if (!isset($threshold)) $threshold = self::DEFAULT_THRESHOLD;

		if (!isset($device->current_value)) {
			$device->current_value = (int)$struct->value;
		}
		else {

			$off = $device->current_value < $threshold;
	        $device->current_value = ($device->current_value + (int)$struct->value) / 2;
	        $now = Date::time();

	        if (!$off) {
	            //没关闭
	            $must_update = TRUE;
	            $last_ptime = Q("eq_power_time[equipment=$equipment]:limit(1):sort(dtstart D)")->current();

	            if ($last_ptime->id && $last_ptime->dtend) {
	                //更新已有记录
	                $last_ptime->dtend = $now;
	                $last_ptime->save();
	            }
	            else {
	                //没有记录，则产生新记录
	                $ptime = O('eq_power_time');    
	                $ptime->equipment = $equipment;
	                $ptime->dtend = $now;
	                $ptime->dtstart = $now;
	                $ptime->save();
	            }
	        }
	        else {
	            //关闭状态产生间隔记录
	            $ptime = O('eq_power_time');
	            $ptime->equipment = $equipment;
	            $ptime->dtstart = $now;
	            $ptime->dtend = 0;
	            $ptime->save();
	        }
		}

		if ($must_update || $now - $device->last_current_value_time >= self::SAMPLE_INTERVAL) {
			$device->last_current_value_time = $now;
			$current = O('eq_current_dp');
			$current->equipment = $equipment;
			$current->value = (int) $device->current_value;
			$current->ctime = $now;
			$current->save();

			$device->current_value = NULL;
		}
	}

	static function on_command_current_threshold($e, $device, $struct) {
		//DO NOTHING
	}

	static function command_current_threshold($e, $device, $data) {
		if (!$device->support_plugin('current')) return;
		$device->post_command('current_threshold', [
			'value' => (int) $data['value']
		]);
	}

	static function command_current($e, $device, $data) {
		if (!$device->support_plugin('current')) return;
		$device->post_command('current');
	}

	static function keep_alive($e, $device) {
		if (!$device->support_plugin('current')) return;
		$now = Date::time();
		if ($now - $device->last_current_sample_time >= self::SAMPLE_INTERVAL) {
			$device->last_current_sample_time = $now;
			$device->post_command('current');
		}
	}

	static function on_plugin_update($e, $device) {
		if (!$device->support_plugin('current')) return;
	
		$device->post_command('current_threshold', [
			'value' => isset($equipment->current_threshold) 
						? (int) $equipment->current_threshold : self::DEFAULT_THRESHOLD
		]);
	}

	static function setup_view($e, $controller) {
		Event::bind('equipment.view.dashboard.sections', 'EQ_Current::current_dashboard');
	}

    static function current_dashboard($e, $equipment, $sections) {
        if ($equipment->control_mode != 'nocontrol' && $equipment->support_device_plugin('current')) {
            $sections[] = V('eq_current:current_dashboard', ['equipment'=>$equipment]);
        }
    }

	static function get_power($equipment, $dtstart, $dtend) {
		if ($dtend > $dtstart) {
			$db = ORM_Model::db('eq_current_dp');
			$seconds = $db->value('SELECT SUM(duration) FROM eq_power_time WHERE dtstart >= %d AND dtstart < %d AND dtend > 0', $dtstart, $dtend);
			$p = P($equipment);
			$rated_output = $p->rated_output ?: Config::get('equipment.rated_output');
			return  ($seconds / 3600 ) * $rated_output;	
		}
		return 0;
	}

	static function stat_power_consum($e, $equipment, $dtstart, $dtend) {
		$e->return_value = self::get_power($equipment, $dtstart, $dtend);
		return FALSE;
	}

	static function setup_edit() {
		Event::bind('equipment.edit.tab', 'EQ_Current::edit_current_tab');
	}

	static function edit_current_tab($e, $tabs) {
		$equipment = $tabs->equipment;
		$me = L('ME');
		if ($me->is_allowed_to('修改能耗设置', $equipment)) {
			$tabs
				->add_tab('current', [
					'url'=> $equipment->url('current', NULL, NULL, 'edit'),
					'title' => I18N::T('eq_current', '能耗设置'),
					'weight' => 80,
				]);
			Event::bind('equipment.edit.content', 'EQ_Current::edit_current_content', 0, 'current');
		}
	}

	static function edit_current_content($e, $tabs) {
		$equipment = $tabs->equipment;
		$form = Form::filter(Input::form());
		$p = P($equipment);
		if ($form['submit']) {
			$p->rated_output = (int) $form['rated_output'];
			$p->voltage = (int)$form['voltage'];
			$p->current_threshold = (int)$form['current_threshold'];
			$p->save();
			Lab::message(Lab::MESSAGE_NORMAL, I18N::HT('eq_current', '能耗设置更新成功!'));
		}
		else {
			$form['rated_output'] = $p->rated_output;
			$form['voltage'] = $p->voltage;
			$form['current_threshold'] = $p->current_threshold;
		}

		$tabs->content = V('eq_current:current_setting', ['form'=>$form]);
	}

	static function get_power_degrees($equipment, $dtstart, $dtend) {
        $real_power = EQ_Current::get_power($equipment, $dtstart, $dtend);
        if (!$real_power) return 0;
		$power = $real_power / 1000;
		$power = (string)round($power, 1);
		preg_match_all('/\d*\.(\d)/', $power, $match);
		if (count($match[1])) {
			if ($match[1][0] > 0) {
				return '~ ' . $power;
			}
			else {
				return '~ ' . preg_replace('/\.(\d)/', '.1', $power);
			} 
		}
		else {
			return '~ ' . $power . '.1';
		}
	}

}
