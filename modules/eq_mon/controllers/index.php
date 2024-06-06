<?php

class Index_AJAX_Controller extends AJAX_Controller {

	function index_cam_capture_click($id=0){
		
        $me = L('ME');
		$equipment = O('equipment', $id);
		try{
		
			if (!$equipment->connect) {
				throw new Exception(I18N::T('equipments', '该仪器已关机或断网，无法进行监控!'));
			}

			$me = L('ME');
			if (!$me->is_allowed_to('监控', $equipment)) {
				throw new Exception(I18N::T('equipments', '您无权对该仪器进行监控!'));
			}
			
			if (!EQ_Mon::support_capture($equipment)) {
				throw new Exception(I18N::T('equipments', '该仪器没有打开视频交互功能!'));
			}

			$now = Date::time();

			$mtime = (int) $equipment->capture_key_mtime;
			if (!$equipment->capture_key || $mtime == 0 || $now - $mtime > 5) {
				//renew capture key
				$equipment->capture_key = md5(mt_rand());
				$equipment->capture_key_mtime = $now;
				$equipment->save();
			}

			Lab::set("equipment_{$equipment->id}_{$me->id}_chat_time", time());

            $client = new \GuzzleHttp\Client([
                'base_uri' => $equipment->server, 
                'http_errors' => FALSE, 
                'timeout' => Config::get('device.computer.timeout', 5)
			]);
			
			$channels = json_decode($client->post('cam_channels', [
				'form_params' => [
					'uuid' => $equipment->control_address
				]
			])->getBody()->getContents(), true) ?? [0 => 0];
			
			$form = Input::form();
			$current_id = $form['channel'];

			$width = $form['width'];
			$width = is_numeric($width) ? $width : Config::get('equipment.default_capture_size', 640);

			JS::dialog(V('eq_mon:cam_capture', [
				'equipment'=>$equipment, 
				'width'=>$width,
				'channels' => $channels,
				'current_id'=>$current_id,
			]), ['drag'=>TRUE]);

			/* 记录日志 */
			Log::add(strtr('[eq_mon] %user_name[%user_id]开启了仪器%equipment_name[%equipment_id]的远程监控 通道[%current_id]', [
						'%user_name' => $me->name,
						'%user_id' => $me->id,
						'%equipment_name' => $equipment->name,
						'%equipment_id' => $equipment->id,
						'%current_id' => $current_id,
			]), 'journal');
		}
		catch(Exception $e){
			//JS::close_dialog();
			JS::alert($e->getMessage());
		}
	}

	function index_cam_keepalive($id = 0) {
		try {
			$equipment = O('equipment', $id);
			$me = L('ME');

			if (!$equipment->id || !$equipment->connect || !$me->id
				|| !$me->is_allowed_to('监控', $equipment) ) {
				throw new Exception;
			}

			$form = Input::form();
			$client = new Equipment_Client($equipment);
			$client->monitor_notice($form);

			Output::$AJAX['streaming'] = true;
		}
		catch(Exception $e){
		}
	}

	function index_talk_search_change() {
		$form = Input::form();
		$equipment = O('equipment', $form['eid']);
		if (!$equipment->id) return;
		
		$class = $form['container'];
		Output::$AJAX[".{$class} .talk_content"] = [
			'data' => (string)V('eq_mon:client/chat', ['equipment'=>$equipment, 'class'=>$class])
		];
	}
	
	function index_chat_send_click() {
		$form = Input::form();
		
		$equipment = O('equipment', $form['eid']);
		if (!$equipment->id) return;
		
		$content = H($form['content']);
		
		$user = L('ME');
	    
        $chat = O('eq_chat');
        $chat->equipment = $equipment;
        $chat->user = $user;
        $chat->name = $user->name;
        $chat->content = $content;
        $chat->ctime = time();
        $chat->save();    

		$agent = new Device_Agent($equipment);
		/**
		   目前 agent 可能为 device_agent 也可能为 glogon_server,
		   chat 的 speaker 要按 用户姓名 (实验室名) 显示, 但
		   glogon_server 由于已在 node.js 端, 无法获得 $user的lab, 所
		   以此处 call('chat') 时直接把拼接好的 user->name 也传过去
		   (Xiaopei Li@2014-04-24)
		*/
		$params = [
			'user' => $user,
			'user_name' => $user->name,
			'time' => $chat->ctime,
			'text' => $content
		];
		if (!$GLOBALS['preload']['people.multi_lab']) {
			$params['user_name'] .= ' ('.Q("$user lab")->current()->name.')';
		}

		$agent->call('chat', $params);
    }

    function index_chat_get() {
        $me = L('ME');
        $form = Input::form();
        
        $equipment  = O('equipment', $form['eid']);
        if (!$equipment->id)  return FALSE;
        $class = $form['container'];
		
		$refresh_time = Lab::get("equipment_{$equipment->id}_{$me->id}_chat_time");
		
		$last_time = Q("eq_chat[equipment={$equipment}][ctime>=$refresh_time]:sort(ctime D)")->limit(1)->current()->ctime;
		
		if (!$last_time) {
			return false;
		}
		
		Lab::set("equipment_{$equipment->id}_{$me->id}_chat_time", $last_time + 1);
		
		$chats = Q("eq_chat[equipment={$equipment}][ctime>=$refresh_time]:sort(ctime A)");
		
		foreach ($chats as $chat) {
			if ($chat->user->id) {
				$name = $chat->user->name;
				if (!$GLOBALS['preload']['people.multi_lab']) {
					$name .= ' ('.Q("{$chat->user} lab")->current()->name.')';
				}
			}
			elseif ($chat->name) {
				$name = $chat->name;
			}
			else {
				$name = I18N::T('eq_mon', '未知用户');
			}
			$name = H($name);
		    $content = H($chat->content);
			$show_user = '<p class="speaker">'.$name.' '.date('Y/m/d H:i:s', $chat->ctime).'</p>';
			$show_text = '<p class="text">'.$content.'</p>';
			$talks .= $show_user . $show_text;
		}
		
        Output::$AJAX["chats"] = $talks; 
    }
}
