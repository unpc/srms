<?php

class Computer_Controller extends Controller {
	
	function logoff() {
		$computer = Input::form('computer');
		$token = Input::form('user');
		$feedback = Input::form('feedback');
		$status = Input::form('status');
		if ($computer && $token) {
			try {
				
				$equipment = O('equipment', ['control_mode'=>'computer', 'control_address'=>$computer]);
				$token = Auth::normalize($token);
				$user = O('user', ['token'=>$token]);

				if (!$equipment->id || !$user->id) throw new Error_Exception;
			
				$now = time();
				$record = Q("eq_record[equipment=$equipment][user=$user][dtstart<=$now][dtend=0]");
				if (!$record->id) throw new Error_Exception;
				
				$equipment->is_using = FALSE;
				$equipment->save();

				if($status){
					$record->status = $status;
					$record->feedback = $feedback;
					$record->project = O('lab_project', Input::form('project'));
				}
				
				$record->dtend = $now;
				$record->save();

				Log::add(strtr('[equipments] %user_name[%user_id] 登出 %equipment_name[%equipment_id] (%equipment_control_address)', ['%user_name'=> $user->name, '%user_id'=> $user->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%equipment_control_address'=> $equipment->control_address]), 'computer');
			}
			catch (Error_Exception $e) {
                Log::add(strtr('[equipments] 无法识别: %user_token登出%computer', ['%user_token'=> $token, '%computer'=> $computer]), 'computer');
			}
		}
	}
	
	function logon() {
		try {
			$computer = Input::form('computer');
			$token = Input::form('user');
			$equipment = O('equipment', ['control_mode'=>'computer', 'control_address'=>$computer]);
			$token = Auth::normalize($token);
			$user = O('user', ['token'=>$token]);

			if (!$equipment->id || !$user->id) throw new Error_Exception;

			$now = time();
			//如果之前的用户未能正常关闭仪器应该结束上一次该仪器的使用记录
			foreach(Q("eq_record[equipment=$equipment][dtstart<=$now][dtend=0]") as $record){
				$record->dtend = $now;
				$record->save();

				Log::add(strtr('[equipments] %user_name[%user_id] 登出 %equipment_name[%equipment_id] (%equipment_control_address)', ['%user_name'=> $record->user->name, '%user_id'=> $record->user->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%equipment_control_address'=> $equipment->control_address]), 'computer');

			}
			
			$equipment->is_using = TRUE;
			$equipment->save();
			
			$record = O('eq_record');
			$record->equipment = $equipment;
			$record->user = $user;
			$record->dtstart = $now;
			$record->dtend = 0;
			$record->save();

			Log::add(strtr('[equipments] %user_name[%user_id] 登入 %equipment_name[%equipment_id] (%equipment_control_address)', ['%user_name'=> $user->name, '%user_id'=> $user->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%equipment_control_address'=> $equipment->control_address]), 'computer');
		}
		catch (Error_Exception $e) {
            Log::add(strtr('[equipments] 无法识别: %token 登入 %computer', ['%token'=> $token, '%computer'=> $computer]), 'computer');

			header('HTTP/1.0 404 Not Found');
		}
	}
	
	function logout() {
		$computer = Input::form('computer');
		$feedback = Input::form('feedback');
		$status = Input::form('status');
		if ($computer) {
			try {
			
				$user = L('ME');
				
				$equipment = O('equipment', ['control_mode'=>'computer', 'control_address'=>$computer]);

				if (!$equipment->id || !$user->id) throw new Error_Exception;
			
				$now = time();
				$record = Q("eq_record[equipment=$equipment][user=$user][dtstart<=$now][dtend=0]");
				if (!$record->id) throw new Error_Exception;
				
				$equipment->is_using = FALSE;
				$equipment->save();

				if (isset($status)) {
					$record->status = $status;
					$record->feedback = $feedback;
					$record->project = O('lab_project', Input::form('project'));
				}
				else {
					if (!$user->is_allowed_to('管理使用', $equipment)) {
						$record->status = EQ_Record_Model::FEEDBACK_NORMAL;
					}
				}
				
				$record->dtend = $now;
				$record->save();

                Log::add(strtr('[equipments] %user_name[%user_id] 登出 %equipment_name[%equipment_id] (%equipment_control_address)', ['%user_name'=> $user->name, '%user_id'=> $user->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%equipment_control_address'=> $equipment->control_address]), 'computer');
				Auth::logout();
			}
			catch (Error_Exception $e) {

                Log::add(strtr('[equipments] 无法识别: %token 登出 %computer', ['%token'=> $token, '%computer'=> $computer]), 'computer');

				header('HTTP/1.0 401 Unauthorized');
			}
		}
	}
	
	function login() {
		try {
			$computer = Input::form('computer');
			$token = Input::form('user');
			$password = Input::form('password');
			$token = Auth::normalize($token);
			$user = O('user', ['token'=>$token]);
			
			$equipment = O('equipment', ['control_mode'=>'computer', 'control_address'=>$computer]);

			if (!$equipment->id || !$user->id) {
                Log::add(strtr('[equipments] 无法识别: %token 登入 %computer', ['%token'=> $token, '%computer'=> $computer]), 'computer');
				throw new Error_Exception;
			}

			Cache::L('ME', $user);
			
			if (!$user->is_allowed_to('管理使用', $equipment) && $equipment->cannot_access($user, time())) {

				Log::add(strtr('[equipments] 用户%user_name[%user_id]无权登入%equipment_name[%equipment_id]', ['%user_name'=> $user->name, '%user_id'=> $user->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id]), 'computer');

                throw new Error_Exception;
			}			

			$auth = new Auth($token);
			if (!$auth->verify($password)) {

				Log::add(strtr('[equipments] 用户%user_name[%user_id]密码尝试失败', ['%user_name'=> $user->name, '%user_id'=> $user->id]), 'computer');

				throw new Error_Exception;
			}
			
			$message = Input::form('message');
			$ph = openssl_get_privatekey(Config::get('equipment.private_key'));
			if ($ph === FALSE ||
				!openssl_sign($message, $digest, $ph, OPENSSL_ALGO_SHA1)) {
				Log::add('[equipments] OPENSSL生成DIGEST失败', 'computer');
				throw new Error_Exception;
			}

			$digest = base64_encode($digest);
			
			$now = Date::time();
			//如果之前的用户未能正常关闭仪器应该结束上一次该仪器的使用记录
			foreach(Q("eq_record[equipment={$equipment}][dtstart<$now][dtend=0]") as $record){
				$record->dtend = $now - 1;
				$record->save();

				Log::add(strtr('[equipments] %user_name[%user_id] 登出 %equipment_name[%equipment_id] (%equipment_control_address)', ['%user_name'=> $record->user->name, '%user_id'=> $record->user->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%equipment_control_address'=> $equipment->control_address]), 'computer');
			}
			
			$equipment->is_using = TRUE;
			$equipment->save();
			
			$record = O('eq_record');
			$record->equipment = $equipment;
			$record->user = $user;
			$record->dtstart = $now;
			$record->dtend = 0;
			$record->save();

				Log::add(strtr('[equipments] %user_name[%user_id] 登入 %equipment_name[%equipment_id] (%equipment_control_address)', ['%user_name'=> $record->user->name, '%user_id'=> $record->user->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%equipment_control_address'=> $equipment->control_address]), 'computer');
			Auth::login($token);

			echo $digest;
		}
		catch (Error_Exception $e) {
			header('HTTP/1.0 401 Unauthorized');
		}
	}
	
	function keepalive() {
		// do nothing, just keep the session alive;
		$computer = Input::form('computer');
		
		try {
			if (!$computer) throw new Error_Exception;
			
			$user = L('ME');
			$equipment = O('equipment', ['control_mode'=>'computer', 'control_address'=>$computer]);
			if (!$equipment->id || !$user->id) throw new Error_Exception;
			Log::add(strtr('[equipments] %user_name[%user_id] 正在使用 %equipment_name[%equipment_id] (%equipment_control_address)', ['%user_name'=> $user->name, '%user_id'=> $user->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%equipment_control_address'=> $equipment->control_address]), 'computer');
		}
		catch (Error_Exception $e) {
			header('HTTP/1.0 401 Unauthorized');
		}

	}
	
	function offline_password() {
		// do nothing, just keep the session alive;
		$computer = Input::form('computer');
		
		try {
			if (!$computer) throw new Error_Exception;
			
			$user = L('ME');
			$equipment = O('equipment', ['control_mode'=>'computer', 'control_address'=>$computer]);
			if (!$equipment->id || !$user->id) throw new Error_Exception;
			
			$equipment->offline_password = Misc::random_password(6, 1);
			$equipment->save();
			echo base64_encode(md5($equipment->offline_password, TRUE));
			
			Log::add(strtr('[equipments] %user_name[%user_id] 刷新 %equipment_name[%equipment_id] (%equipment_control_address) 的离线管理密码', ['%user_name'=> $user->name, '%user_id'=> $user->id, '%equipment_name'=> $equipment->name, '%equipment_id'=> $equipment->id, '%equipment_control_address'=> $equipment->control_address]), 'computer');
		}
		catch (Error_Exception $e) {
			header('HTTP/1.0 401 Unauthorized');
		}

	}
	
}
