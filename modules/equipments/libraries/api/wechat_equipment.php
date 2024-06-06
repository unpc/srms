<?php

class API_Wechat_Equipment extends API_Wechat{

	protected static $errors = [
        401 => 'Access Denied',
        404 => '未找到使用仪器',
        500 => 'Internal Error',
        501 => '不能使用仪器'
    ];

	function powerOn($token) {
		if(!$this->checkToken($token)) {
            throw new API_Exception(self::$errors[500], 500);
		}	
		$criteria = $_SESSION['api_criteria'];

		$user = O('user', $criteria['LimsUserID']);	
		$equipment = O('equipment', $criteria['equipmentID']);
        $now = Date::time();
		if (!$user->is_allowed_to('管理使用', $equipment) && $equipment->cannot_access($user, $now)) {
			throw new API_Exception(implode('|',Lab::$messages[Lab::MESSAGE_ERROR]), 501);
		}

		$agent = new Device_Agent($equipment);
		$success = $agent->call('switch_to', array('power_on'=>TRUE));

		if ($success) {
			$equipment->is_using = TRUE;
			Log::add(strtr('[equipments] %user_name[%user_id]通过Wechat打开%equipment_name[%equipment_id]仪器', array('%user_name'=> $user->name, '%user_id'=> $user->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id)), 'journal');
		}
		else{
			if (strpos($equipment->control_address, 'gmeter://') == 0) {
				// 开关 gmeter 是异步的, 无法在 switch_to 调用时获得结果,
				// 只能通过之后 gstation 汇报的 power (会被 epc-server 转为
				// offline record) 得知.
				// 此处 sleep 再 refresh, 一般可等到 gstation 回复
				// (Xiaopei Li@2013-12-07)
				sleep(1);
				// TODO sleep 的秒数可以设为超时秒数, sleep 后, 从 db refetch
				// equipment, 并根据此时状态告知用户开关成功与否
			}
			else {
				Log::add(strtr('[equipments] %user_name[%user_id]无法通过Wechat打开%equipment_name[%equipment_id]仪器', array(
					'%user_name'=> $user->name,
					'%user_id'=> $user->id,
					'%equipment_name'=> $equipment->name,
					'%equipment_id'=> $equipment->id,
				)));
				throw new API_Exception(self::$errors[501], 501);
			}
		}

		sleep(2);
		$record =  Q("eq_record[dtstart={$now}][dtend=0][equipment={$equipment}]:sort(dtstart D):limit(1)")->current();
		if($record->id) {
			$record->user = $user;
			$record->save();
			$return_array = array();
			$return_array['record_id'] = $record->id;
			$return_array['dtstart'] = $record->dtstart;
			$return_array['username'] = $user->name;
			unset($_SESSION['api_token']);
			return $return_array;
		}
		else {
			Log::add(strtr('[equipments] %user_name[%user_id]无法通过Wechat打开%equipment_name[%equipment_id]仪器', array(
				'%user_name'=> $user->name,
				'%user_id'=> $user->id,
				'%equipment_name'=> $equipment->name,
				'%equipment_id'=> $equipment->id,
			)));
			throw new API_Exception(self::$errors[501], 501);
		}
	}

	private function powerOff($criteria) {
		$user = O('user', $criteria['LimsUserID']);	
		$equipment = O('equipment', $criteria['equipmentID']);
        $now = Date::time();
		if (!$user->is_allowed_to('管理使用', $equipment) && $equipment->cannot_access($user, $now)) {
			throw new API_Exception(implode('|',Lab::$messages[Lab::MESSAGE_ERROR]), 501);
		}

		if (Q("eq_record[dtstart<{$now}][dtend=0][equipment={$equipment}][user={$user}]")->total_count()==0) {
			throw new API_Exception(self::$errors[501], 501);
		}

		$record =  Q("eq_record[dtstart<{$now}][dtend=0][equipment={$equipment}]:sort(dtstart D):limit(1)")->current();
		$agent = new Device_Agent($equipment);
		$success = $agent->call('switch_to', array('power_on'=>FALSE));

		if ($success) {
			return $record->id;
		}
		else {
			if (strpos($equipment->control_address, 'gmeter://') == 0) {
				// 开关 gmeter 是异步的, 无法在 switch_to 调用时获得结果,
				// 只能通过之后 gstation 汇报的 power (会被 epc-server 转为
				// offline record) 得知.
				// 此处 sleep 再 refresh, 一般可等到 gstation 回复
				// (Xiaopei Li@2013-12-07)
				sleep(1);
				// TODO sleep 的秒数可以设为超时秒数, sleep 后, 从 db refetch
				// equipment, 并根据此时状态告知用户开关成功与否
//					return I18N::T('equipments', '无法关闭电源控制设备，请确认仪器已经关闭!');
				throw new API_Exception(self::$errors[500], 500);
			}
			else {
				//return I18N::T('equipments', '无法关闭电源控制设备，请确认仪器已经关闭!');
				throw new API_Exception(self::$errors[500], 500);
			}
		}
	}

	function setFeedback($token) {
		if(!$this->checkToken($token)) {
            throw new API_Exception(self::$errors[500], 500);
		}	
		$criteria = $_SESSION['api_criteria'];

		$record_id = $this->powerOff($criteria);
		sleep(2);
		$record =  O('eq_record', $record_id);

		if($record->id) {
			$feedback_status = self::$feedback_status_map;
			$record_status = 0;
			if (isset($feedback_status[$criteria['recordStatus']])) {
				$record_status = (int)$feedback_status[$criteria['recordStatus']];
			}
			$record->status = $record_status;
			$record->feedback = $criteria['feedback'];
			if (isset($criteria['samples'])) {
				$record->samples = max(Config::get('eq_record.record_default_samples'), (int)$criteria['samples']);
			}
			$record->save();	

			$return_array = array();
			$return_array['record_id'] = $record->id;
			$return_array['dtstart'] = $record->dtstart;
			$return_array['dtend'] = $record->dtend;
			$return_array['equipment_name'] = $record->equipment->name;
			$return_array['username'] = $record->user->name;

			$charge = O("eq_charge", array('source' => $record));
			$reserv_charge = O('eq_charge', array('source' => $record->reserv));

			$description = $charge->description;
			if ($reserv_charge->id) {
				$description = $reserv_charge->description . $description;
			}

			$return_array['description'] = $description;
			unset($_SESSION['api_token']);
			return $return_array;
		}
		throw new API_Exception(self::$errors[500], 500);
	}

	function getEquipments($token) {
		if(!$this->checkToken($token)) {
            throw new API_Exception(self::$errors[500], 500);
		}	
		$criteria = $_SESSION['api_criteria'];

        $selector ="equipment";
        $pre_selectors =array();

        if ($criteria['cat']) {
			$cat = O('tag_group', $criteria['cat']);
			if($cat->root->id) {
				$pre_selectors['cat'] = "{$cat}";
			}
        }

        if($criteria['group']) {
            $group = O('tag_group', $criteria['group']);
            if ($group->root->id) {
                $pre_selectors['group'] = "{$group}";
            }
        }
        
        if($criteria['ids']!=''){
            $ids = join(',', $criteria['ids']);
            $selector .= "[id={$ids}]";
        }

        if($criteria['searchtext']!=''){
            $name = $criteria['searchtext'];
            $selector .="[name*=$name]";
        }

        if (count($pre_selectors) > 0) {
            $selector = '('.implode(', ', $pre_selectors).') ' . $selector;
        }
        $selector .= ':sort(accept_reserv D, accept_sample D)';

		if(!$start) $start = 0;
        $equipments = Q("$selector")->limit($start,$num);
		unset($_SESSION['api_token']);
        return $this->get_equipments_model($equipments);
	}

	public function getEquipment($token) {
		if(!$this->checkToken($token)) {
            throw new API_Exception(self::$errors[500], 500);
		}	
		$criteria = $_SESSION['api_criteria'];
		$equipment = O('equipment', $criteria['id']);
        $equipments = $this->get_equipments_model(array($equipment));
		unset($_SESSION['api_token']);
		if(is_array($equipments) && sizeof($equipments) > 0) {
			return $equipments[0];	
		}
	}

	public function getFollowEquipment($token) {
		if(!$this->checkToken($token)) {
            throw new API_Exception(self::$errors[500], 500);
		}	
		$criteria = $_SESSION['api_criteria'];
		$user = O('user', $criteria['LimsUserID']);
		$follows = $user->followings('equipment');
		$equipments = array();
		foreach($follows as $follow) {
			$equipments[] = $follow->object;
		}
        $equipments = $this->get_equipments_model($equipments);
		unset($_SESSION['api_token']);
		return $equipments;	
	}

    private function get_equipments_model($equipments) {
        $equipments_data = array();
        foreach ($equipments as $equipment) {
            $root = Tag_Model::root('equipment');
            $users = Q("{$equipment} user.contact")->to_assoc('id', 'name');
            $incharges = Q("{$equipment} user.incharge")->to_assoc('id', 'name');
            $tags = Q("{$equipment} tag_equipment[root=$root]")->to_assoc('id', 'name');
            $data = array(
                    'id'    => $equipment->id,
                    'icon_url' => $equipment->icon_url('32'),//默认图标，向后兼容
                    'icon16_url' => $equipment->icon_url('16'),
                    'icon32_url' => $equipment->icon_url('32'),
                    'icon48_url' => $equipment->icon_url('48'),
                    'icon64_url' => $equipment->icon_url('64'),
                    'icon128_url' => $equipment->icon_url('128'),
                    'url' => $equipment->url(),
                    'name' => $equipment->name,
                    'name_abbr' =>$equipment->name_abbr,
                    'phone' => $equipment->phone,
                    'contact' => join(', ', $users),
                    'email' => $equipment->email,
                    'location' => $equipment->location, 
                    'accept_sample' => $equipment->accept_sample,
                    'accept_reserv' => $equipment->accept_reserv,
                    'reserv_url' => $equipment->url('reserv'),
                    'sample_url' => $equipment->url('sample'),
                    'price' => $equipment->price,
                    'status' => $equipment->status,

                    'ref_no' => $equipment->ref_no,
                    'cat_no' =>$equipment->cat_no,
                    'model_no' => $equipment->model_no,
                    'control_mode' => $equipment->control_mode,
                    'is_using' => $equipment->is_using,
                    'is_monitoring' => $equipment->is_monitoring,
                    'is_monitoring_mtime' => $equipment->is_monitoring_mtime,
                    'current_user' => $equipment->current_user()->name,
                    'current_user_id' => $equipment->current_user()->id,
                    'accept_limit_time' =>  $equipment->accept_limit_time,
                    'organization' =>$equipment->organization,
                    'specification' =>$equipment->specification,
                    'tech_specs' => $equipment->tech_specs,
                    'features' => $equipment->features,
                    'configs' => $equipment->configs,

                    'manu_at' => $equipment->manu_at,
                    'manufacturer' => $equipment->manufacturer,
                    'manu_date' => $equipment->manu_date,
                    'purchased_date' => $equipment->purchased_date,
                    'control_address' => $equipment->control_address,
                    'require_training' => $equipment->require_training,
                    
                    'ctime' => $equipment->ctime,
                    'mtime' => $equipment->mtime,
                    'access_code' => $equipment->access_code,
                    'group_id' => $equipment->group_id,
                    'tag_root_id' => $equipment->tag_root_id,
                    'billing_dept_id' =>  $equipment->billing_dept_id,
                    'incharges' =>join(', ', $incharges),
                    'tags' => join(', ', $tags)
            );
            
            $equipments_data[] = $data;
        }
        return $equipments_data;
    }

}
