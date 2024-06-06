<?php
class CLI_Equipment {

    private static function log() {
		$args = func_get_args();
		if ($args) {
			$format = array_shift($args);
			$str = vsprintf($format, $args);
			Log::add(strtr('%name %str', [
						'%name' => '[GLogon CLI]',
						'%str' => $str,
			]), 'devices');
		}
	}

	static function update_eq_mon_mtime() {
		$cache = Cache::factory();
		$now = Date::time();
		$exp_time = $now - 60;
		if ($cache->get('equipment.monitoring_mtime') < $exp_time) {
			$SQL = "UPDATE equipment SET is_monitoring = 0
			WHERE is_monitoring = 1
			AND control_mode <> 'computer'
			AND is_monitoring_mtime < {$exp_time}";
			ORM_Model::db('equipment')->query($SQL);
			$cache->set('equipment.monitoring_mtime', $now);
		}
	}

	//删除仪器不存在的eq_record
	static function cleanup_records() {
		foreach (Q("eq_record") as $record) {
			if (!$record->equipment->id) {
				$record->delete();
			}
		}
	}

	//将所有仪器负责人加入到某一指定的角色
	static function add_role_to_incharge($role_id=null) {
		$role_to_add = O('role', $role_id);
		if (!$role_to_add->id) {
			echo 'invalid role' . "\n";
			die;
		}

		echo '对所有仪器负责人添加' . $role_to_add->name . '权限？(yes/no)';
		$fh = fopen('php://stdin', 'r') or die($php_errormesg);
		$ret = fgets($fh, 5);
		if (trim($ret) != 'yes') {
			die;
		}

		$all_incharges = Q("equipment user.incharge");
		echo '共有' . count($all_incharges) . "个仪器负责人\n";
		foreach ($all_incharges as $user) {
			echo $user->name . "...";
			if (!$user->connected_with($role_to_add)) {
				if ($user->connect($role_to_add)) {
					echo "链接成功 \n";
				}
				else {
					echo "链接失败 \n";
				}
			}
			else {
				echo "已链接 \n";
			}
		}
	}
	
	// 向glogon/epc-server获取一下仪器的链接状态
	static function control_sync_status() {
		$db = Database::factory();
		// 以防glogon-server分布式部署 这边取一下
		$result = $db->query("SELECT DISTINCT `server` FROM `equipment` WHERE `connect` = 1");
		if ($result) $rows = $result->rows('assoc');
		if ($rows) foreach ($rows as $row) {
			// 将所有电脑控制的仪器发送出去请求状态
			if (!$row['server']) continue;
			$equipments = Q("equipment[server={$row['server']}][connect]");
			$devices = $equipments->to_assoc('id', 'control_address');
		
			try {
				$client = new \GuzzleHttp\Client([
					'base_uri' => $row['server'],
					'http_errors' => FALSE,
					'timeout' => Config::get('device.computer.timeout', 5)
				]);
				$result = $client->post('status', [
					'form_params' => [
						'equipments' => $devices
					]
				])->getBody()->getContents();
				$statuses = json_decode($result, true) ? : [];

				$addresses = array_keys(array_filter($statuses, function ($v) {
					return !$v;
				})) ? : [];
				$disconnect = implode(',', array_map(function ($v) {
					return "'{$v}'";
				}, $addresses));
				$db->query("UPDATE `equipment` SET `connect` = 0
				WHERE control_address IN ({$disconnect})");
			}
			catch (\GuzzleHttp\Exception\ServerException $e) {
				self::log('调用Glogon/EPC-server失败', $e->getCode(), $e->getMessage());
			}
			catch (\Exception $e) {
				self::log('Glogon/EPC-server服务异常');
			}
		}
	}


    static function set_holiday_for_equipment(){
        $params = func_get_args();
        $admiSetting = $params[1];
        $me = O('user',$params[0]);
        if(!$admiSetting || !$me->id) return;

        $form = json_decode($admiSetting,true);
        $open = $form['has_setting'] == 'on' ? true :  false;

        //删除原来所有的设置结果
        $originHolidayReserv = Q('holiday_reserv');
        foreach ($originHolidayReserv as $hrv){
            $hrv->source->component->delete();
            $hrv->source->delete();
            $hrv->delete();
        }

        $settingTagIds = $hasSetting = [];
        foreach ($form['tagged'] as $tagData){
            $tag = O('tag_group',$tagData['tid']);
            $settingTagIds[] = $tagData['tid'];
            $eqIds = Q("{$tag} equipment")->to_assoc('id','id');
            $hasSetting = array_merge($hasSetting,$eqIds);
            $holidayReservEqIds = Q("holiday_reserv[equipment=".implode(',',$eqIds)."]")->to_assoc('id','equipment_id');
            $toSetting = array_diff($eqIds,$holidayReservEqIds);
            self::_set_holiday_by_eqids($params,$toSetting,$tagData);
        }

        if($open){
            //设置通用动
            $status= EQ_Status_Model::NO_LONGER_IN_SERVICE;
            $allEqids = Q("equipment[accept_reserv=1][status<{$status}]")->to_assoc('id','id');
            $unSettingEqIds = array_diff($allEqids,$hasSetting);
            self::_set_holiday_by_eqids($params,$unSettingEqIds,$form);
        }
        @unlink(sys_get_temp_dir().'/set_holiday_for_equipment');
    }

    //以下为原逻辑，未动
    private static function _set_holiday_by_eqids($params,$eqids,$form){
        if (empty($eqids)) return;
        $me = O('user',$params[0]);
        $reserv2user2equipment = $holiday2eq = [];//被删除预约的用户-仪器,假期最终时间
        $holidayDtstart = $form['dtstart'];//所有仪器的原始假期开始时间
        $holidayDtend = $form['dtend'];//所有仪器的原始假期截止时间

        //之前预约删除并更新每台仪器的实际假期
        $eqids = implode(',',$eqids);
        $reservs = Q("eq_reserv[equipment_id={$eqids}][dtstart={$form['dtstart']}~{$form['dtend']}|dtend={$form['dtstart']}~{$form['dtend']}]:sort(dtstart A)");
        foreach ($reservs as $reserv){
            isset($holiday2eq[$reserv->equipment->id]['dtstart']) ? '' : $holiday2eq[$reserv->equipment->id]['dtstart'] = $holidayDtstart;
            isset($holiday2eq[$reserv->equipment->id]['dtend']) ? '' : $holiday2eq[$reserv->equipment->id]['dtend'] = $holidayDtend;
            if ($reserv->dtstart < $form['dtstart'] && $reserv->dtend > $form['dtstart']){
                $holiday2eq[$reserv->equipment->id]['dtstart'] = max($reserv->dtend,$holiday2eq[$reserv->equipment->id]['start']);
                continue;
            }
            if ($reserv->dtstart < $form['dtend'] && $reserv->dtend > $form['dtend']){
                $holiday2eq[$reserv->equipment->id]['dtend'] = min($reserv->dtstart,$holiday2eq[$reserv->equipment->id]['dtend']);
                continue;
            }
            $reserv2user2equipment[$reserv->user->id][$reserv->equipment->id][] = $reserv;
        }

        if (!empty($reserv2user2equipment)){
            foreach ($reserv2user2equipment as $uid => $eq2reservs){
                foreach ($eq2reservs as $eid => $res){
                    foreach ($res as $re){
                        $re->component->delete();
                        $re->delete();
                    }
                    $equipment = O('equipment',$eid);
                    $user = O('user',$uid);
                    //发送给预约用户的消息提醒
                    Notification::send('eq_reserv.holiday_setted_deleted', $user, [
                        '%user' => Markup::encode_Q($user),
                        '%equipment' => Markup::encode_Q($equipment),
                        '%holiday_dtstart' => date('Y-m-d H:i:s',$holidayDtstart),
                        '%holiday_dtend' => date('Y-m-d H:i:s',$holidayDtend),
                        '%other_content' => '',
                    ]);
                }
            }
        }

        //设置每台仪器的非预约时段
        $status= EQ_Status_Model::NO_LONGER_IN_SERVICE;
        foreach (Q("equipment[id={$eqids}][accept_reserv=1][status<{$status}]") as $equipment){
            $per = $holiday2eq[$equipment->id] ?? ['dtstart'=>$holidayDtstart,'dtend'=>$holidayDtend];
            $calendar = O('calendar', ['parent'=> $equipment, 'type'=> 'eq_reserv']);
            if (!$calendar->id) {
                $calendar = O('calendar');
                $calendar->parent = $equipment;
                $calendar->type = 'eq_reserv';
                $calendar->name = I18N::T('eq_reserv', '%equipment的预约', ['%equipment' => $equipment->name]);
                $calendar->save();
            }
            $component = O('cal_component',['dtstart'=>$per['dtstart'] + 1,'dtend'=>$per['dtend'] - 1,'calendar'=>$calendar]);
            $component->organizer = $me;
            $component->calendar = $calendar;
            $component->name = I18N::T('equipments','仪器假期时段');
            $component->description = I18N::T('equipments','仪器假期时段,您无法预约');
            $component->dtstart = $per['dtstart'] + 1;
            $component->dtend = $per['dtend']-1;
            $component->type = Cal_Component_Model::TYPE_VFREEBUSY;

            if(!$me->is_allowed_to('添加', $component)){
                continue;
            }
            $component->save();
            $reserv = O('eq_reserv', ['component' => $component]);
            $reserv->dtstart = $component->dtstart;
            $reserv->dtend = $component->dtend;
            $reserv->equipment = $equipment;
            $reserv->user = $me;
            $reserv->component = $component;
            $reserv->use_type_reserv = 1;
            $reserv->save();
            $hr = O('holiday_reserv',['equipment'=>$equipment]);
            $hr->equipment = $equipment;
            $hr->source = $reserv;
            $hr->dtstart = $reserv->dtstart;
            $hr->dtend = $reserv->dtend;
            $hr->save();
        }
    }

}
