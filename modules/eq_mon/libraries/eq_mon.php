<?php

class EQ_Mon {

	static function links($e, $equipment, $links, $mode) {
		if ($equipment->id && $mode == 'view') {
			$me = L('ME');
			$client = new Equipment_Client($equipment);

			if ($equipment->connect
				&& $equipment->is_using
				&& $client->monitor_able()
				&& $me->is_allowed_to('监控', $equipment)) {
				$links['cam_capture'] = [
					'tip' => I18N::HT('equipments', '实时监控'),
					'text' => I18N::HT('equipments', '实时监控'),
					'url' => '#',
					'extra' => 'class="button button_eye middle" q-object="cam_capture" q-event="click" q-src="'.H(URI::url('!eq_mon/index.'.$equipment->id)).'"',
				];
			}
		}
	}
	
	static function support_capture($equipment) {
        return !$equipment->support_device_plugin('local')
            || $equipment->support_device_plugin('monitor')
            || ($equipment->device['os'] =='Win32_CPP' && version_compare($equipment->device['version'], '2.4') >= 0) //win下版本高于2.4
            || ($equipment->device['os'] == 'mac' && version_compare($equipment->device['version'], '2.4.10') >= 0) //mac下版本高于2.4.10
            ;
	}

	static function support_capture_stream($equipment) {
		return $equipment->support_device_plugin('monitor');
	}

	//传入对象$object为equipment
	static function control_is_allowed($e, $me, $perm_name, $equipment, $options) {
		if (!$equipment->id
			|| $equipment->status == EQ_Status_Model::NO_LONGER_IN_SERVICE) return;

		if ($equipment->id && Equipments::user_is_eq_incharge($me, $equipment) && $me->access('实时监控负责的仪器')) {
			$e->return_value = TRUE;
			return FALSE;
		}

		if ($me->access('实时监控所有仪器')) {
			$e->return_value = TRUE;
			return FALSE;
		}

		if($me->access('实时监控下属机构的仪器') && $me->group->is_itself_or_ancestor_of($equipment->group)) {
			$e->return_value = TRUE;
			return FALSE;
		}
	}

	static function on_command_chat($e, $device, $struct) {
		// TODO: receive chat message
		$text = (string) $struct->text;
		$speaker = $struct->speaker;
		// $speaker->name, $speaker->token
		
		$agent = $device->agent(0);
		$equipment = ORM_Model::refetch($agent->object);
		
		if (!$equipment->id) {
			throw new Device_Exception('无法识别的仪器');
        }
		
		$token = Auth::normalize($speaker['token']);
		$user = O('user', ['token'=>$token]);

        $chat = O('eq_chat');
        $chat->equipment = $equipment;
        $chat->user = $user;
        $chat->name = $speaker['name'];
        $chat->content = $text;
        $chat->save();
	}

	private $observers = [];
	private $primary_oid = 0;

	static function clean_timeout_observers($device) {
		$now = Date::time();
		$modified = FALSE;
		foreach ((array)$device->observers as $uid => $v) {
			if ($now - $v[0] > 5) {
				unset($device->observers[$uid]);
				$modified = TRUE;
			}
		}
		
		$device->primary_oid = key((array)$device->observers);
		return $modified;
	}

	static function update_observers($device) {
		$observers = [];
		foreach (array_keys((array)$device->observers) as $uid) {
			$user = O('user', $uid);
			if (!$user->id) continue;
			$observers[$user->id] = $user->name;
		}
		$device->post_command('observers', ['observers'=>$observers]);
	}

	static function on_command_observers($e, $device, $struct) {
		self::clean_timeout_observers($device);
		self::update_observers($device);
	}

	static function on_command_cam_capture($e, $device, $struct) {
		if (!$device->is_ready) return;
		$device->is_streaming = !!$struct->streaming;
	}

	static function command_cam_capture($e, $device, $data) {

		$width = (int) $data['width'];
		$channel = $data['channel'];
		$chat_stream = $data['chat_stream'];
		if (is_numeric($channel)) $channel = (int) $channel;
		$user = $data['user'];

		$agent = $device->agent(0);
		$equipment = ORM_Model::refetch($agent->object);

		if ($equipment->id) {
			$now = Date::time();
			$key = $equipment->capture_key;
			if (is_string($key) && $key) {
				$equipment->capture_key_mtime = $now;
				$equipment->save();

				$modified = self::clean_timeout_observers($device);

				if (!isset($device->observers[$user->id])) {
					$device->log('%s[%d] 查看仪器 %s[%d] 频道[%d] KEY:%s',
						$user->name, $user->id,
						$equipment->name, $equipment->id,
						$channel, $key);
					$modified = TRUE;
				}

				$device->observers[$user->id] = [$now, $channel];

				if ($now - $device->last_cam_capture_mtime > 2) {
					$device->last_cam_capture_mtime = $now;

					$odata = $device->observers[$device->primary_oid] ?: reset($device->observers);
					$params = [
						'width'=>$width,
						'channel'=>$odata[1],
						'key'=>$key,
						'chat_stream' => $chat_stream,
					];

					$ips = (array) Config::get('equipment.capture_stream_to');
					$default_name = Config::get('equipment.default_capture_stream_name');
					$stream_to = count($ips) ? ($equipment->capture_stream_to ?: $ips[$default_name]['address']) : NULL;

					//1 不存在capture_stream_to 直接用post_to
					//2 存在capture_stream_to，但是不support_capture_stream，则用post_to
					if (!$stream_to || !EQ_Mon::support_capture_stream($equipment)) {
                        //如果没有配置stream_to
                        //或者这个仪器不支持stream(没安装gmonitor)
                        //需要使用upload_to

                        //获取默认upload_to
                        $upload_url = $equipment->capture_upload_to;
                        //如果未配置upload_url
                        //使用默认的upload_url
                        if (!$upload_url) {
                            $default_capture_upload_to = Config::get('equipment.default_capture_upload_to');
                            $all_capture_upload_to = Config::get('equipment.capture_upload_to');
                            $upload_url = $all_capture_upload_to[$default_capture_upload_to]['address'];
                            $upload_url = strtr($upload_url, ['%id'=> $equipment->id]);
                        }
                        //如果默认upload没配置，则走URI::url

                        $params['post_to'] = $upload_url ? : $data['url'];
					}
					else {
						$params['stream_to'] = $stream_to.'/'.$key;
						$params['stream_from'] = $stream_to.'/'.$key.'_chat';
					}

					$device->post_command('cam_capture', $params);
				}
				
				if ($modified) {
					self::update_observers($device);
				}

				$e->return_value = $device->is_streaming;
			}
			else {
				if ($now - $device->last_cam_capture_mtime > 2) {
					$device->last_cam_capture_mtime = $now;
					$device->post_command('server_time', ['time'=>Date::time()]);
				}
			}
		}

		return FALSE;
	}

	static function command_chat($e, $device, $data) {
		if (!$device->support_plugin('monitor')) return;
		
		$user = $data['user'];
		$text = (string) $data['text'];

		$params = [
			'speaker' => ['token'=>$user->token, 'name' => $user->name],
			'time'=>(int) $data['time'],
			'text' => $text,
		];
		if (!$GLOBALS['preload']['people.multi_lab']) {
			$params['speaker']['name'] .= ' ('.Q("$user lab")->current()->name.')';
		}
		$device->post_command('chat', $params);
	}

	static function command_cam_channels($e, $device, $data) {
	
	
		$device->post_command('cam_channels');
		$struct = $device->recv_command();

		$channels = (array) $struct->channels;
		$e->return_value = $channels;
	}
}
