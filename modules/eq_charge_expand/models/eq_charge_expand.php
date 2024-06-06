<?php

class EQ_Charge_Expand_Model extends ORM_Model {

	public function calculate_minimum() {
		$charge = $this->charge;
		$charge_lua = new EQ_Charge_LUA($charge);
        $result = $charge_lua->run(['option', 'fee', 'description', 'charge_duration', 'duration']);
		$source = $charge->source;
        switch ($source->name()) {
            case 'eq_record':
				$this->minimum_fee = $result['option']['minimum_fee'] ? : 0;
                break;
            case 'eq_sample':
            	$this->minimum_fee = $result['option']['minimum_fee'] ? : 0;
                break;
            default:
		        $time = $result['duration'] ? : $result['charge_duration'];
				$this->minimum_fee = ($result['fee'] - ($result['option']['unit_price'] * $time)) ? : 0;
            	break;
        }
	}

	// 计算机时补贴费
	public function calculate_subsidy() {
		// 免费使用的仪器
		if ($this->charge->amount == 0) {
			$this->subsidy_fee = 0;
			return $this;
		}

		$user      = $this->charge->user;
		$equipment = $this->charge->equipment;
		$settings  = EQ_Charge_Expand::get_charge_subsidy_setting($equipment);

		$this->subsidy_fee = $this->calculate($user, $equipment, $settings, 'subsidy');
	}

	// 计算耗材费
	public function calculate_expend() {
		// 免费使用的仪器
		if ($this->charge->amount == 0) {
			$this->expend_fee = 0;
			return $this;
		}

		$user      = $this->charge->user;
		$equipment = $this->charge->equipment;
		$settings  = EQ_Charge_Expand::get_charge_expend_setting($equipment);

		$this->expend_fee = $this->calculate($user, $equipment, $settings, 'expend');
	}

	public function calculate($user, $equipment, $settings, $type) {
		$setting = $settings['*']?:['hour' => 0, 'sample' => 0];

		// 获取用户的特殊配置
		try {
			$roots = [$equipment->get_root(), Tag_Model::root('equipment_user_tags')];

			unset($settings['*']);

			foreach ($settings as $tag_name => $s) {
				foreach ($roots as $root) {
					if (Q("{$user} tag_equipment_user_tags[name={$tag_name}][root={$root}]")->total_count()) {
						throw new Error_Exception;
					}

					$labs = Q("$user lab");
					if (!$labs->total_count()) {return NULL;
					}

					if (Q("{$labs} tag_equipment_user_tags[name={$tag_name}][root={$root}]")->total_count()) {
						throw new Error_Exception;
					}

					$user_group = $user->group;
					$group_root = Tag_Model::root('group');
					$groups     = Q("tag_equipment_user_tags[name={$tag_name}][root={$root}] tag_group[root={$group_root}]");
					foreach ($groups as $group) {
						if ($group->is_itself_or_ancestor_of($user_group)) {
							throw new Error_Exception;
						}
						foreach ($labs as $lab) {
							$lab_group  = $lab->group;
							if ($group->is_itself_or_ancestor_of($lab_group)) {
								throw new Error_Exception;
							}
						}
					}
				}
			}
		} catch (Error_Exception $e) {
			$setting = $settings[$tag_name];
		}

		//进行实际收费计算
		$source_name = $this->charge->source->name();
		$charge_template = $equipment->charge_template;

		if ($charge_template['record'] == 'record_samples') {
			$setting['hour'] = 0;
		}

		if (!$charge_template['sample'] && $charge_template['record'] != 'record_samples') {
			$setting['sample'] = 0;
		}

		if ($charge_template['reserv'] == 'custom_reserv' || $charge_template['record'] == 'custom_record') {
			//高级自定义
			switch ($source_name) {
				case 'eq_record':
					//按照使用时间收费
					$charge_type = 'record_time';
					break;
				case 'eq_reserv':
					$charge_type = 'time_reserv_record';
					break;
			}
		}
		else {
			//获取charge_type //收费类型
			switch ($source_name) {
				case 'eq_record':
					//综合计费时候, record按照record_time计算
					$charge_type = $charge_template['record'] ? : 'record_time';
					break;
				case 'eq_reserv':
					$charge_type = $charge_template['reserv'];
					break;
				case 'eq_sample':
					$charge_type = $charge_template['sample'];
					break;
			}
		}

		return call_user_func([$this, "_{$source_name}_{$charge_type}"], $setting, $type);
	}

	//预约计费
	private function _eq_reserv_time_reserv_record($setting, $key) {

		//需要综合计算收费信息
		$reserv = $this->charge->source;

		$equipment = $this->charge->equipment;
		//获取该时段内的使用记录

		$dtstart = $reserv->dtstart;
		$dtend   = $reserv->dtend;

		$reserv_records = Q("eq_record[equipment={$equipment}][dtend>0][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]");

		//构造record的合计
		$records = [];

		foreach ($reserv_records as $record) {
			$records[] = $record;
		}

		//计费时段计算
		$charge_start_time = $reserv->dtstart;
		$charge_end_time   = $reserv->dtend;

		//通过获取时段内的使用记录, 计算最长跨度时段
		if (count($records)) {
			foreach ($records as $record) {
				if ($record->dtstart < $charge_start_time) {
					$charge_start_time = $record->dtstart;
				}

				if ($record->dtend > $charge_end_time) {
					$charge_end_time = $record->dtend;
				}
			}
		}

		//对别人超时使用占用进行处理
		if (count($records)) {
			//获取第一个
			$first_record = reset($records);

			//如果第一个使用记录为非预约人使用记录, 则重新设置起始时间
			if ($first_record->reserv->id != $reserv->id && $first_record->dtstart <= $reserv->start_time) {
				$charge_start_time = $first_record->dtend+1;
			}
		}

		//计算收费时间
		$duration = $charge_end_time - $charge_start_time + 1;

		foreach ($records as $record) {
			//他人使用
			if ($record->user->id != $reserv->user->id) {
				$duration = $duration-($record->dtend-$record->dtstart+1);
			}
		}

		switch ($key) {
			case 'subsidy':
				return $setting['hour']*round($duration/3600, 4);
				break;
			default:
				return $setting['hour']*round($duration/3600, 4);
				break;
		}
	}

	//预约计费下的 按预约时间计费
	private function _eq_reserv_only_reserv_time($setting, $key) {

		$reserv = $this->charge->source;
		//每小时收费 * 时长
		switch ($key) {
			case 'subsidy':
				return $setting['hour']*round(($reserv->dtend-$reserv->dtstart)/3600, 4);
				break;
			default:
				return $setting['hour']*round(($reserv->dtend-$reserv->dtstart)/3600, 4);
				break;
		}
	}

	//按使用情况计费 -> 按使用时间
	private function _eq_record_record_time($setting, $key) {
		$record = $this->charge->source;
		//每小时收费 * 时长
		switch ($key) {
			case 'subsidy':
				return $setting['hour']*round(($record->dtend-$record->dtstart)/3600, 4);
				break;
			default:
				return $setting['hour']*round(($record->dtend-$record->dtstart)/3600, 4);
				break;
		}

	}

	private function _eq_record_record_times($setting, $key) {
		$record = $this->charge->source;
		//每小时收费 * 时长
		switch ($key) {
			case 'subsidy':
				return $setting['hour']*round(($record->dtend-$record->dtstart)/3600, 4);
				break;
			default:
				return $setting['hour']*round(($record->dtend-$record->dtstart)/3600, 4);
				break;
		}
	}

	//按样品数计费
	private function _eq_record_record_samples($setting, $key) {
		//按样品数收费
		switch ($key) {
			case 'subsidy':
				return $setting['sample']*$this->charge->source->samples;
				break;
			default:
				return $setting['sample']*$this->charge->source->samples;
				break;
		}
	}

	//按样品数计费
	private function _eq_sample_sample_count($setting, $key) {
		switch ($key) {
			case 'subsidy':
				return $setting['sample']*$this->charge->source->count;
				break;
			default:
				return $setting['sample']*$this->charge->source->count;
				break;
		}
	}

	//使用记录自定义
	//按时长计费
	//如果为自定义收费, 则按照小时收费
	private function _eq_record_custom_record($setting, $key) {
		$record = $this->charge->source;
		//每小时收费 * 时长
		switch ($key) {
			case 'subsidy':
				return $setting['hour']*round(($record->dtend-$record->dtstart)/3600, 4);
				break;
			default:
				return $setting['hour']*round(($record->dtend-$record->dtstart)/3600, 2);
				break;
		}
	}

	//送样计费自定义
	//按照送样数计算
	//如果为自定义收费, 则按照小时收费
	private function _eq_sample_custom_sample($setting, $key) {
		switch ($key) {
			case 'subsidy':
				return $setting['sample']*$this->charge->source->count;
				break;
			default:
				return $setting['sample']*$this->charge->source->count;
				break;
		}
	}

	//预约收费自定义
	//按照预约时长计算
	//如果为自定义收费, 则按照小时收费
	private function _eq_reserv_custom_reserv($setting, $key) {
		$reserv = $this->charge->source;
		//每小时收费 * 时长
		switch ($key) {
			case 'subsidy':
				return $setting['hour']*round(($reserv->dtend-$reserv->dtstart)/3600, 4);
				break;
			default:
				return $setting['hour']*round(($reserv->dtend-$reserv->dtstart)/3600, 4);
				break;
		}
	}
}
