<?php

class API_Exhibition {

    public static $errors = [
        401 => 'Access Denied',
        404 => 'Not Found',
        500 => 'Internal Error',
    ];

    private function _ready() {
        $whitelist = Config::get('api.white_list_exhibition');

        if(!in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
            throw new API_Exception(self::$errors[401], 401);
        }

		return;
    }

    public function statistics() {
        // $this->_ready();
        $status = EQ_Status_Model::OUT_OF_SERVICE;

        $statistics = [
            'total' => Q('equipment')->total_count(),
            'share' => Q('equipment[accept_reserv=1|accept_sample=1]')->total_count(),
            'use' => Q('equipment[is_using=1]')->total_count(),
            'maintain' => Q("equipment[status={$status}]")->total_count(),
        ];

        return $statistics;
    }

    public function similarity() {
        // $this->_ready();

        $similarity = json_decode(Lab::get('exhibition.similarity.equipments', ''), true);

        $equipments = [];

        foreach ($similarity as $eq) {
            $equipments[] = O('equipment', $eq);
        }

        return $this->get_equipments($equipments);
    }

    public function forecast() {
        // $this->_ready();

        $forecast = json_decode(Lab::get('exhibition.forecast.equipments', ''), true);
        
        $equipments = [];

        foreach ($forecast as $eq) {
            $equipments[] = O('equipment', $eq);
        }

        return $this->get_equipments($equipments);
    }

    private function get_equipments($equipments) {
        $data = [];
        foreach ($equipments as $equipment) {
            $tag = $equipment->group;
            $group = $tag->id ? [$tag->id => $tag->name] : [] ;
            while($tag->parent->id && $tag->parent->root->id){
                $group += [$tag->parent->id => $tag->parent->name];
                $tag = $tag->parent;
            }
            $root = Tag_Model::root('equipment');
            $users = Q("{$equipment} user.contact")->to_assoc('id', 'name');
            $incharges = Q("{$equipment} user.incharge")->to_assoc('id', 'name');
            $tags = Q("{$equipment} tag_equipment[root=$root]")->to_assoc('id', 'name');

            $info = [
                'id' => $equipment->id,
                'icon_url' => $equipment->icon_url('32'),
                'icon16_url' => $equipment->icon_url('16'),
                'icon32_url' => $equipment->icon_url('32'),
                'icon48_url' => $equipment->icon_url('48'),
                'icon64_url' => $equipment->icon_url('64'),
                'icon128_url' => $equipment->icon_url('128'),
                'iconreal_url' => $equipment->icon_file('real') ? Config::get('system.base_url') . Cache::cache_file($equipment->icon_file('real')) . '?_=' . $equipment->mtime : $equipment->icon_url('128'),
                'url' => $equipment->url(),
                'name' => $equipment->name,
                'name_abbr' =>$equipment->name_abbr,
                'phone' => $equipment->phone,
                'contact' => join(', ', $users),
                'email' => $equipment->email,
                'location' => $equipment->location,
                'location2' => $equipment->location2, 
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
                'accept_limit_time' =>  $equipment->accept_limit_time,
                'organization' =>$equipment->organization,
                'specification' =>$equipment->specification,
                'tech_specs' => $equipment->tech_specs,
                'features' => $equipment->features,
                'configs' => $equipment->configs,
                'charge_rule' => $equipment->ReferChargeRule,
                'manu_at' => $equipment->manu_at,
                'manufacturer' => $equipment->manufacturer,
                'manu_date' => $equipment->manu_date,
                'purchased_date' => $equipment->purchased_date,
                'control_address' => $equipment->control_address,
                'require_training' => $equipment->require_training,
                'ctime' => $equipment->ctime,
                'atime' => $equipment->atime,
                'mtime' => $equipment->mtime,
                'access_code' => $equipment->access_code,
                'group' => $group,
                'group_id' => $equipment->group->id,
                'group_name' => $equipment->group->name,
                'tag_root_id' => $equipment->tag_root_id,
                'billing_dept_id' =>  $equipment->billing_dept_id,
                'incharges' =>join(', ', $incharges),
                'tags' => join(', ', $tags)
            ];
            
            $data[] = $info;
        }

        return $data;
    }

    public function used_info($equipment_id) {
        // $this->_ready();
        $equipment = O('equipment', $equipment_id);
		if (!$equipment->id) throw new API_Exception(self::$errors[404], 404);
		$now = Date::time();
		return [
			'total_used_time' => Q("eq_record[equipment={$equipment}][dtstart<={$now}][dtend>0][dtstart<@dtend]")->SUM('dtend')-Q("eq_record[equipment={$equipment}][dtstart<={$now}][dtend>0][dtstart<@dtend]")->SUM('dtstart'),
            'total_time' => Q("eq_record[equipment={$equipment}][dtstart<=$now]")->total_count(),
            'time' => $now
		];
    }
    
    public function used_status($equipment_id) {
        // $this->_ready();
        $equipment = O('equipment', $equipment_id);
		if (!$equipment->id) throw new API_Exception(self::$errors[404], 404);
		$now = Date::time();

		$status = [];

		$record = Q("eq_record[equipment={$equipment}][dtstart<{$now}][dtend=0]:sort(dtstart D):limit(1)")->current();
		if ($record->id) {
			$user = $record->user;
			$reserv = Q("eq_reserv[equipment={$equipment}][dtstart<={$now}][dtend>={$now}]")->current();
			$data = [
				'uid' => $user->id,
				'uname' => H($user->name),
				'icon_url' => $user->icon_url('64'),
				'is_admin' => (int)$user->is_allowed_to('管理使用', $equipment)
			];
			if ($reserv->id) {
				$data['reserv'] = [
					'dtstart' => $reserv->dtstart,
					'dtend' => $reserv->dtend
				];
				$ruser = $reserv->user;
				$times = Q("eq_record[reserv={$reserv}][dtend>0]")->SUM('dtend - dtstart');
				if ($ruser->id == $user->id) {
					$times += ($now - $record->dtstart);
				}
				else {
					$data['reserv']['uname'] = H($reserv->user->name);
				}
				$data['reserv']['used_time'] = $times;
				$data['reserv']['surplus_time'] = $reserv->dtend - $now;
			}
			if (!$GLOBALS['preload']['people.multi_lab']) {
				$lab = Q("$user lab")->current();
				$data['lab'] = H($lab->name);
			}
			else {
				$data['lab'] = H($reserv->project->lab->name);
			}
			$status['current'] = $data;
		}
		

		$reserv = Q("eq_reserv[equipment={$equipment}][dtstart>={$now}]:sort(dtstart)")->current();
		if ($reserv->id) {
			$status['next'] = [
				'uname' => H($reserv->user->name),
				'dtstart' => $reserv->dtstart,
				'dtend' => $reserv->dtend
			];
			if (!$GLOBALS['preload']['people.multi_lab']) {
				$lab = Q("{$reserv->user} lab")->current();
				$status['next']['lab'] = H($lab->name);
			}
			else {
				$status['next']['lab'] = H($reserv->project->lab->name);
			}
		}
		
		$record = Q("eq_record[equipment={$equipment}][dtend>0][dtstart<{$now}]:sort(dtend D):limit(1)")->current();
		if ($record->id) {
			$status['before'] = [
				'uname' => H($record->user->name),
				'phone' => $record->user->phone,
			];
			if (!$GLOBALS['preload']['people.multi_lab']) {
				$lab = Q("{$record->user} lab")->current();
				$status['before']['lab'] = H($lab->name);
			}
			else {
				$status['before']['lab'] = H($record->project->lab->name);
			}
		}
		
		$status['is_using'] = H($equipment->is_using);
		$status['status'] = H($equipment->status);

		return $status;

	}
}