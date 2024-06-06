<?php

class EQDevice_Exception extends Exception {}

class Device_ICCO extends Device {
	
	const PACKET_FLAG = "\xAA\xAA";
	
	const COMMAND_GET_DEVICES = 0x0003;
	
	const COMMAND_GET_DEVICE_ID = 0x0205;
	const COMMAND_ALTER_MODE = 0x022E;

	const COMMAND_SET_TIME = 0x0202; // 设置机具当前时钟
	const COMMAND_GET_TIME = 0x0203; // 读取机具当前时钟

	const COMMAND_RELAY_SWITCH = 0x0600;
	const COMMAND_RELAY_CHECK = 0x0601;
	const COMMAND_CARD_OP_RESPONSE = 0x0602;
	const COMMAND_CARD_OP = 0x0603;
	const COMMAND_CARD_RECORD = 0x0605;

	const ACCEPTABLE_TIME_DIFF = 5; // 可接受的刷卡误差时间

	const ALL_DEVICES = 0xffff;
	const MAX_OUTLETS = 4;
	
	const RETURN_SUCCESS = "\x00";

	const RECORD_FORMAT_BCD = 0;
	const RECORD_FORMAT_HEX = 1;

	private $outlet_agents = [];
	private $pending_switch = [];
	private $no_0607 = FALSE;

	function close() {
		foreach ($this->agents() as $agent) {
			$equipment = $agent->object;
			if ($equipment->id) {
				$this->log("%s[%d]断开连接", $equipment->name, $equipment->id);
			}
		}
		parent::close();
	}

	function recv_command() {
		$header = $this->read(6);
		if (substr($header, 0, 2) == self::PACKET_FLAG) {
			$dev_id_be = substr($header, 2, 2);
			$len_be = substr($header, 4, 2);

			$dev_id = self::be2s($dev_id_be);
			$len = self::be2s($len_be) + 1;

			$body = $this->read($len);
			$this->debug_command($header.$body, TRUE, TRUE);

			if (strlen($body) == $len) {
				$lrc = substr($body, -1);
				$body = substr($body, 0, -1);
				if ($lrc == self::lrc(substr($header, 2).$body)) {
					return (object) [
						'dev_id'=>$dev_id, 
						'command'=>self::be2s(substr($body, 0, 2)), 
						'params'=>substr($body, 2)
					];
				}
			}
			
		}
		else {
			$this->debug_command($header, TRUE, TRUE);
		}
		return NULL;
	}
	
	function post_command($dev_id, $command, $params=NULL) 
	{		
		$data = self::pack_command($dev_id, $command, $params);
		$this->debug_command($data, FALSE, TRUE);
		$this->write($data);
	}

	function send_command($dev_id, $command, $params=NULL) {
		$this->post_command($dev_id, $command, $params);
		return $this->wait_for($dev_id, $command);
	}

	static function pack_command($dev_id, $command, $params)
	{
		$body = self::s2be($dev_id) 
			. self::s2be(2 + strlen($params))
			. self::s2be($command) . $params;
			
		return self::PACKET_FLAG
			. $body
			. self::lrc($body);
	}
	
	function run() {
		$this->post_command(self::ALL_DEVICES, self::COMMAND_GET_DEVICES);
		parent::run();
	}

	private function keep_alive() {
		$now = Date::time();

		foreach ($this->outlet_agents as $id => $outlet_agent) {
			$this->send_command($id, self::COMMAND_SET_TIME, self::bcd(date('Ymd0wHis')));
			$this->send_command($id, self::COMMAND_GET_TIME);

			for ($outlet=1; $outlet<=self::MAX_OUTLETS; $outlet++) {

				if (isset($outlet_agent[$outlet])) {
					$agent = $outlet_agent[$outlet];
					$db = ORM_Model::db('equipment');
					$db->query('UPDATE `equipment` SET `is_monitoring`=1, `is_monitoring_mtime`=%d WHERE `id`=%d',
							   $now, $agent->object->id);
				}
			}
		}
	}

	function idle() {
		parent::idle();
		$this->keep_alive();
	}

	function process_command($struct) {
		
		$command = $struct->command;
		$dev_id = $struct->dev_id;
		
		switch($command) {
		case self::COMMAND_GET_DEVICES:
			//查询该DEVICE的序列号
			$this->post_command($dev_id, self::COMMAND_GET_DEVICE_ID);
			break;
		case self::COMMAND_GET_DEVICE_ID:

			$serial_no = $struct->params;
			
			//ugly hack
			if (FALSE === strpos($serial_no, 'T')) {
				// 如果没有T 被认为是老版本门禁控制器
				$this->no_0607 = TRUE;
			}

			//清空之前的管理卡号
			$this->send_command($dev_id, 0x0320);

			//同步时间
			// $client->log("同步时间 %s", date('Y/m/d H:i:s'));

			// icco 有奇怪的 bug, 校时前需先读时间, 才能成功设置时间 (xiaopei.li@2012-09-26)
			$this->send_command($dev_id, self::COMMAND_GET_TIME);
			$this->send_command($dev_id, self::COMMAND_SET_TIME, self::bcd(date('Ymd0wHis')));
			// $this->send_command($dev_id, self::COMMAND_SET_TIME, self::bcd(date('Ymd0wHis', strtotime('2015/06/04 06:00'))));
			$this->send_command($dev_id, self::COMMAND_GET_TIME);

			// $client->log("设置开锁时长0.2s");
			$this->send_command($dev_id, 0x0224, "\x00\x02\x00\x00\x06\x04");

			$this->log("控制器%s已连接", $serial_no);

			$cards = [];
			for ($outlet=1;$outlet<=self::MAX_OUTLETS;$outlet++) {
				$control_address = "icco://{$serial_no}/{$outlet}";
				$equipment = O('equipment', ['control_mode'=>'power', 'control_address'=>$control_address]);
				if ($equipment->id) {
					if ($equipment->status != EQ_Status_Model::IN_SERVICE) {
						$this->log("%s[%d] (%s)处于故障状态", $equipment->name, $equipment->id, $equipment->location );
					}
					else {
						//加入列表
						
						$agent = new Device_Agent($equipment, TRUE);
						$agent->dev_id = $dev_id;
						$agent->outlet = $outlet;
						$this->outlet_agents[$dev_id][$outlet] = $agent;
						
						$this->add_agent($agent);
						
						$flag = (1 << ($outlet - 1));
						$free_access_cards = $equipment->get_free_access_cards();
						foreach($free_access_cards as $card_no => $user) {

							if (isset($_SERVER['CARD_BYTE_SWAP'])) {
								$card_no = Misc::uint32_to_string(Misc::byte_swap32($card_no));
							}							

							$this->log('添加管理人员%s[%d] 卡号 %012s', $user->name, $user->id, $card_no);
							$cards[$card_no] |= $flag;
							$card_no_s = (string)(($card_no + 0) & 0xffffff);
							$cards[$card_no_s] |= $flag;
						}

						$this->log("%s[%d] (%s)已连接", $equipment->name, $equipment->id, $equipment->location );

					}
				}
			}
			
			//写入新的管理卡号
			foreach ($cards as $card_no => $val) {
				$this->send_command($dev_id, 0x030c,
					"\xC0"
					.self::bcd(str_pad($card_no, 12, '0', STR_PAD_LEFT))
					."\x20\x50\x01\x01"
					."\x00\x00\xFF\xFF"
					."\x00"
					.chr($val)
				);
			}

			//尝试读取之前的刷卡记录
			$this->send_command($dev_id, self::COMMAND_ALTER_MODE, "\x00");
			$this->post_command($dev_id, self::COMMAND_CARD_RECORD, "\x00");
			break;

		case self::COMMAND_CARD_RECORD:
			$status = ord($struct->params[0]);
			if ($status == 0xFD || $status == 0xFC) break;
			if ($status == 0x00 && strlen($struct->params) == 1) {
				//记录读取完成, 切换成检测刷卡状态
				$this->send_command($dev_id, self::COMMAND_ALTER_MODE, "\x30");
				$this->send_command($dev_id, self::COMMAND_CARD_OP, "\x00");

				//一切就绪响应一声
				$this->send_command($dev_id, 0x0706, "\x10\x03\x10\x10");

				break;
			}

			// 读取下一条
			$this->post_command($dev_id, self::COMMAND_CARD_RECORD, "\x01");
			break;

		case self::COMMAND_CARD_OP:
			$status = ord($struct->params[0]);
			if ($status == 0x00 && strlen($struct->params) == 1) {
				break;
			}
			
			try {

				$operation = self::parse_card_operation($struct->params);	
				if (!$operation) {
					throw new EQDevice_Exception("无法识别的刷卡操作");
				}
				
				$card_no = (string) $operation->card_no;
				if (isset($_SERVER['CARD_BYTE_SWAP'])) {
					$card_no = Misc::uint32_to_string(Misc::byte_swap32($card_no));
				}							

				$card_no_s = (string)(($card_no+0) & 0xffffff);
				$user = Q("user[card_no=$card_no|card_no_s=$card_no_s]:limit(1)")->current();
				if (!$user->id) {
					throw new EQDevice_Exception(sprintf("卡号%012s找不到相应的用户", $operation->card_no));
				}

				$outlet = $operation->reader_id;

				$this->log("机具%02d 插座%02d 卡号:%012s => 用户:%s", $dev_id, $outlet, $operation->card_no, $user->name);		


				/*
				  增加刷卡的时间有效性判断(xiaopei.li@2012-09-26)

				  应仅在符合以下条件时, 才继续处理:
				  1. 不穿越, 刷卡时间晚于此次连接时间;
				  2. 不超时, 刷卡时间与当前时间误差不超过 误差容忍值;

				  绝大多数情况下, 超时判断涵盖穿越判断
				*/
				$now = Date::time();
				if (abs($now - $operation->time) > self::ACCEPTABLE_TIME_DIFF) {
					// 超时刷卡, 拒绝
					throw new EQDevice_Exception("超时的刷卡请求!");
				}

				Cache::L('ME', $user);	//当前用户切换为该用户

				$agent = $this->outlet_agents[$dev_id][$outlet];
				$equipment = ORM_Model::refetch($agent->object);
				if (!$equipment->id) {
					throw new EQDevice_Exception(sprintf("找不到读卡器绑定的仪器!", $operation->reader_id));
				}

				if ($operation->time < $equipment->is_monitoring_mtime) {
					// 穿越刷卡, 拒绝
					throw new EQDevice_Exception("穿越的刷卡请求!");
				}

				if (!$equipment->is_monitoring) {
					$equipment->is_monitoring = TRUE;
					$equipment->is_monitoring_mtime = time();
					$equipment->save();
				}

				// $this->send_command($dev_id, 0x0601);
				$power_on = !$equipment->is_using;
				
				if (!$power_on) {
					//要求关闭仪器
					if (!$user->is_allowed_to('管理使用', $equipment)) {
						$record = Q("eq_record[dtstart<=$now][dtend=0][equipment=$equipment][user=$user]:sort(dtstart D):limit(1)")->current();
						if (!$record->id) {
							// 没使用记录...  检查是否因为没有任何使用记录
							if (Q("eq_record[dtstart<=$now][dtend=0][equipment=$equipment]")->total_count() > 0) {
								throw new EQDevice_Exception(sprintf("用户%s[%d]无权关闭%s[%d]", $user->name, $user->id, $equipment->name, $equipment->id));
							}
						}
					}
					
				}
				else {
					//要求打开仪器
					//检测用户是否可以操作仪器
					if (!$user->is_allowed_to('管理使用', $equipment) && $equipment->cannot_access($user, $now)) {
						throw new EQDevice_Exception(sprintf("用户%s[%d]无权打开%s[%d]", $user->name, $user->id, $equipment->name, $equipment->id));
					}			
		
				}


				//回复通过信息
				$this->log(sprintf("机具%02d 插座%02d 通过", $dev_id, $outlet));
				$this->send_command($dev_id, self::COMMAND_CARD_OP_RESPONSE, "\x01\x02".self::bcd(date('YmdHis')));
				
				$this->command_switch_to(['agent'=>$agent, 'power_on'=>$power_on, 'user'=>$user]);
					
			}
			catch(EQDevice_Exception $e) {
				$this->log(sprintf("机具%02d 插座%02d %s", $dev_id, $outlet, $e->getMessage()));
				$this->send_command($dev_id, self::COMMAND_CARD_OP_RESPONSE, "\x01\x0C".self::bcd(date('YmdHis')));
			}

			break;

		case 0x0601:

			$params = $struct->params;
			$reader_num = ord($params[0]);
			$relay_num = ord($params[1]);
			
			$skip = 2 + ceil($relay_num / 8) + ceil($reader_num / 8);
			$power_status = substr($params, $skip, ceil($relay_num / 8));

			$now = time();

			foreach($this->outlet_agents[$dev_id] as $outlet => $agent) {
				$i = $outlet - 1;
				$byte = ord($power_status[floor($i/4)]);
				$shift = ($i % 4) * 2;
				$p = ($byte & (0x1 << $shift)) ? TRUE : FALSE;
				
				$equipment = ORM_Model::refetch($agent->object);
				if (!$equipment->id) continue;

				if ($equipment->is_using != $p) {
					$equipment->is_using = $p;
					$equipment->is_monitoring = TRUE;
					$equipment->is_monitoring_mtime = time();
					$equipment->save();
				}

				$ps = $this->pending_switch[$dev_id][$outlet];
				if (isset($ps) && $ps['power_on'] == $p) {
					$this->close_pending_switch($ps);
					unset($this->pending_switch[$dev_id][$outlet]);
				}

			}
			
			break;
			
		case 0x0607:
			//门磁返回
			$power_on = self::be2s($struct->params);
			foreach($this->outlet_agents[$dev_id] as $outlet => $agent) {
				$p = ($power_on >> ($outlet-1)) & 0x1;

				$equipment = ORM_Model::refetch($agent->object);
				if (!$equipment->id) continue;

				if ($equipment->is_using != $p) {
					$equipment->is_using = $p;
					$equipment->is_monitoring = TRUE;
					$equipment->is_monitoring_mtime = time();
					$equipment->save();
				}

				$ps = $this->pending_switch[$dev_id][$outlet];
				if (isset($ps) && $ps['power_on'] == $p) {
					$this->close_pending_switch($ps);
					unset($this->pending_switch[$dev_id][$outlet]);
				}

			}

			break;

		}
	}

	private function close_pending_switch(&$ps) {
		$now = time();
		$user = $ps['user'];
		$equipment = $ps['equipment'];
		$p = $ps['power_on'];
		if ($p) {
			// 打开仪器
			foreach (Q("eq_record[dtend=0][dtstart<=$now][equipment=$equipment]") as $record) {
				if ($record->dtstart==$now) {
					$record->delete();
					continue;
				}
				$record->dtend = $now - 1;
				$record->status = EQ_Record_Model::FEEDBACK_NORMAL;
				$record->save();
			}

			$record = O('eq_record');
			$record->dtstart = $now;
			$record->dtend = 0;
			$record->user = $user;
			$record->equipment = $equipment;
			$record->save();
		}
		else {
			$record =  Q("eq_record[dtstart<{$now}][dtend=0][equipment={$equipment}]:sort(dtstart D):limit(1)")->current();
			if ($record->id) {
				$record->dtend = $now;
				//负责人关闭设备时,当这条记录是负责人自己的,那么状态默认是正常，如果是代开,status不设置
				if ($record->user->is_allowed_to('管理使用', $equipment)) {
					$record->status = EQ_Record_Model::FEEDBACK_NORMAL;
				}
				$record->save();
			}

		}
	}

	private function wait_for($dev_id, $command, $timeout = 2.0) {

		$start_time = microtime(TRUE);
		
		while (TRUE) {

			$data = $this->process_data($timeout);
			if (!$data) break;
			
			if ($data->dev_id == $dev_id) {
				if ($data->command == $command) {
					return $data;
				}
			}

			$timeout -= (microtime(TRUE) - $start_time);
			if ($timeout <= 0) break;
		}

		return NULL;
	}
	
	function command_switch_to($data) {

		$user = $data['user'];
		$power_on = $data['power_on'];
		$agent = $data['agent'];
	
		$dev_id = $agent->dev_id;
		$outlet = $agent->outlet;
		
		$outlet0 = $outlet - 1;
		$relays = self::s2be(1<<$outlet0);
		
		$equipment = ORM_Model::refetch($agent->object);
		
		$this->log("%s[%d]尝试切换%s[%d] (%s) 的状态 => %s", $user->name, $user->id, $equipment->name, $equipment->id, $equipment->location , $power_on ? '打开':'关闭');
		if ($equipment->is_using == $power_on) return TRUE;

		$this->send_command($dev_id, self::COMMAND_RELAY_SWITCH, "\x02".$relays);

		$this->pending_switch[$dev_id][$outlet] = ['user'=>$user, 'equipment'=>$equipment, 'power_on'=>$power_on];

		if ($this->no_0607) {
			$this->send_command($dev_id, 0x0601);
		}
		
		return TRUE;	//无论如何先返回成功
	}
	
	static function parse_card_operation($params) {
	
		$operation->card_no = substr(self::parse_bcd(substr($params, 0, 6)), -10) + 0;
		$operation->reader_id = 1 + (ord($params[6]) & 0x7f);
		$operation->status = ord($params[7]);
		$operation->time = mktime(
			self::parse_bcd($params[12]), self::parse_bcd($params[13]), self::parse_bcd($params[14]), 
			self::parse_bcd($params[10]), self::parse_bcd($params[11]), self::parse_bcd($params[8].$params[9]));
		
		return $operation;
		
	}

	static function parse_bcd($s) {
		$len = strlen($s);
		$ret = '';
		for($i = 0; $i < $len; $i++) {
			$ret .= sprintf('%02X', ord($s[$i]));
		}
		return $ret;
	}

	static function bcd($s) {
		if (strlen($s) % 2) $s = '0'.$s;
		$s_arr = str_split($s, 2);
		$s = '';
		foreach($s_arr as $c) {
			$s .= chr(hexdec($c));
		}
		return $s;
	}

	static function be2s($be)
	{
		list(,$s) = unpack('n', $be);
		return $s;
	}
	
	static function s2be($n)
	{
		return pack('n', $n);
	}
	
	static function l2be($n)
	{
		return pack('N', $n); 
	}

	static function be2l($be) {
		list(,$l) = unpack('N', $be);
		return $l;
	}
	
	static function lrc($buffer)
	{
		$len = 0;
		if (isset($buffer) && !empty($buffer)){
			$len = strlen($buffer);
		}
		$check = $buffer[0];
		for ($i = 1; $i < $len ; $i++) {
			$check ^= $buffer[$i];
		}
		return $check;
	}
	
}

