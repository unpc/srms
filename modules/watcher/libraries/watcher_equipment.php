<?php

class Watcher_Equipment {

	public static function get_uuid ($equipment) {
		if ($equipment->id) {
			switch ($equipment->control_mode) {
				case 'computer':
					$uuid = $equipment->control_address;
					break;
				case 'power':
					$address = $equipment->control_address;
					list($mode, $uuid) = explode('//', $address);
					break;
				default:
					$uuid = $equipment->yiqikong_id ?: $equipment->id;
					break;
			}
		}

		return $uuid;
	}

	public static function save ($e, $equipment, $old_data, $new_data) {
        if (!$equipment->control_gstation) {
            return TRUE;
        }
		// 如果是更改状态的操作，则进行状态通知
		if ($new_data['status'] != $old_data['status']) {
			$now = Date::time();

			$status = [];

			$record = Q("eq_record[equipment={$equipment}][dtstart<{$now}][dtend=0]:sort(dtend D):limit(1)")->current();
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
			try {
				$rpc = new RPC($equipment->control_gstation . ':2047');
				$rpc->status($status);
			}
	        catch (RPC_Exception $e) {
			}
		}
		// 否则便判定为信息更改，则进行信息更改通知
		else {
			$incharges = Q("{$equipment} user.incharge")->to_assoc('id', 'name');

			$info = [
	        	'id' => $equipment->id,
	        	'yiqikong_id' => $equipment->yiqikong_id,
	        	'icon_url' => $icon_url,
	            'url' => $equipment->url(),
	        	'name' => H($equipment->name),
	        	'phone' => H($equipment->phone),
	        	'email' => H($equipment->email),
	        	'ref_no' => H($equipment->ref_no),
	            'cat_no' => H($equipment->cat_no),
	            'model_no' => H($equipment->model_no),
	            'price' => (float)$equipment->price,
	            'control_mode' => H($equipment->control_mode),
	            'current_user' => H($equipment->current_user()->name),
	            'accept_sample' => (int)$equipment->accept_sample,
	            'accept_reserv' => (int)$equipment->accept_reserv,
	            'accept_limit_time' =>  (int)$equipment->accept_limit_time,
	            'reserv_url' => $equipment->url('reserv'),
	            'sample_url' => $equipment->url('sample'),
	            'manufacturer' => H($equipment->manufacturer),
	            'organization' => H($equipment->organization),
	            'specification' => H($equipment->specification),
	            'tech_specs' => H($equipment->tech_specs),
	            'features' => H($equipment->features),
	            'configs' => H($equipment->configs),

	            'incharges' =>join(', ', $incharges),
	            'time' => $now
			];

			try {
				$rpc = new RPC($equipment->control_gstation . ':2047');
				$rpc->info($info);
			}
	        catch (RPC_Exception $e) {
			}
		}

	}

	public static function edit_use ($e, $equipment, $form) {
		$equipment->control_gstation = $form['control_power_gstation'];
		return FALSE;
	}

}
