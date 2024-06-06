<?php

class Device_DC_ICCO_Exception extends Exception {}

class Device_DC_ICCO extends Device {

	const PACKET_FLAG = "\xAA\xAA";

	const COMMAND_GET_DEVICES = 0x0003;

	const COMMAND_GET_DEVICE_ID = 0x0205;
	const COMMAND_ALTER_MODE = 0x022E;

	const COMMAND_RELAY_SWITCH = 0x0600;
	const COMMAND_RELAY_CHECK = 0x0601;
	const COMMAND_CARD_OP_RESPONSE = 0x0602;
	const COMMAND_CARD_OP = 0x0603;
	const COMMAND_OFFLINE_RECORD = 0x0605;

	const ALL_DEVICES = 0xffff;
	const MAX_OUTLETS = 4;

	const RETURN_SUCCESS = "\x00";

	const RECORD_FORMAT_BCD = 0;
	const RECORD_FORMAT_HEX = 1;

	const COMMAND_OFFLINE_RECORD_NEW = 0x0507;
	const COMMAND_OFFLINE_RECORD_CLEAN = 0x0500;
	const OFFLINE_CHECK_TIMEOUT = 60;
	// 1 min, 若超出 timeout 后, offline_record 相关命令未回复, 则超时.
	// 此项影响 idle 时是否发送 COMMAND_OFFLINE_RECORD_NEW 命令

	const PARSE_OFFLINE_CARD_OPERATION_EMPTY = 'parse_offline_card_operation_empty';
	const PARSE_OFFLINE_CARD_OPERATION_ERROR = 'parse_offline_card_operation_error';

	private $outlet_agents = [];

	private $offline_record_offset = 0;
	private $offline_record_last_process_date = 0;

	// 设置超时时间为 2min. 时间过长 (如 5min) 会造成门禁连接中断.
	protected $timeout = 120;

	function close() {
		foreach ($this->agents() as $agent) {
            if(!is_object($agent)) continue;
			$door = $agent->object;
			if ($door->id) {
				$this->log("门%s[%d](%s)断开连接", $door->name, $door->id, $door->location1 . $door->location2);
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

	function post_command($dev_id, $command, $params=NULL) {
		$data = self::pack_command($dev_id, $command, $params);
		$this->debug_command($data, FALSE, TRUE);
		$this->write($data);
	}

	function send_command($dev_id, $command, $params=NULL) {
		$this->post_command($dev_id, $command, $params);
		return $this->wait_for($dev_id, $command);
	}

	static function pack_command($dev_id, $command, $params) {
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

	function idle() {

		$outlet_agents = $this->outlet_agents;

		if (count($outlet_agents)) {
			$ids = array_keys($outlet_agents);
			foreach ($ids as $id) {

				// 校时
				$this->send_command($id, 0x0203);
				$this->send_command($id, 0x0202, self::bcd(date('Ymd0wHis')));
				$this->send_command($id, 0x0203);

				// 如果未在某次同步离线记录的过程中, 或是上次同步离线记录已超时,
				// 则开始同步离线记录
				if (!$this->offline_record_last_process_date ||
					time() > $this->offline_record_last_process_date + self::OFFLINE_CHECK_TIMEOUT) {
					// pack('n', $some_integer) => "\x01\x23"
					$this->post_command($id, self::COMMAND_OFFLINE_RECORD_NEW,
							"\x00" . pack('n', $this->offline_record_offset) . "\x00\x01");
				}
			}
		}
	}

	function process_command($struct) {

		$command = $struct->command;
		$dev_id = $struct->dev_id;


		switch($command) {
		case self::COMMAND_OFFLINE_RECORD_NEW:

			$operation = self::parse_offline_card_operation($struct->params);

			// $this->log("%s", print_r($operation, TRUE));

			if ($operation === self::PARSE_OFFLINE_CARD_OPERATION_EMPTY) {

				if ($this->offline_record_offset > 0) {
					// 离线记录已全部记录, 清空
					$this->send_command($dev_id, self::COMMAND_OFFLINE_RECORD_CLEAN);
				}
				else {
					// 没有离线记录
					// 更新检查时间
					$this->offline_record_last_process_date = 0;
				}

			}
			else if ($operation === self::PARSE_OFFLINE_CARD_OPERATION_ERROR) {
				$this->log(sprintf("非离线记录, 继续读取: %s",
								   self::hexify($struct->params)
							   ));

				// 更新检查时间和位置
				$this->offline_record_offset++;
				$this->offline_record_last_process_date = time();

				// 读取下一条
				$this->post_command($dev_id, self::COMMAND_OFFLINE_RECORD_NEW,
									"\x00" . pack('n', $this->offline_record_offset) . "\x00\x01");
			}
			else {

				try {

					$card_no = (string) $operation->card_no;
					if (isset($_SERVER['CARD_BYTE_SWAP'])) {
						$card_no = Misc::uint32_to_string(Misc::byte_swap32($card_no));
					}

					$user = Q("user[card_no=$card_no]:limit(1)")->current();

		            if (!$user->id) {
		                $card_no_s = (string)(($card_no+0) & 0xffffff);
		                $user = Q("user[card_no_s=$card_no_s]:limit(1)")->current();
		            }
		            
					if (!$user->id) {
						throw new Device_DC_ICCO_Exception(sprintf("卡号%s找不到相应的用户", $operation->card_no));
					}

					$outlet = $operation->reader_id;

					$this->log("机具%02d 读卡器%02d 卡号:%s => 用户:%s", $dev_id, $outlet, $operation->card_no, $user->name);

					Cache::L('ME', $user);	//当前用户切换为该用户

					$agent = $this->outlet_agents[$dev_id][$outlet];
					if (!$agent) {
						$this->post_command($dev_id, self::COMMAND_OFFLINE_RECORD, "\x01");
						$this->post_command($dev_id, self::COMMAND_GET_DEVICE_ID);
						break;
					}

					$door = ORM_Model::refetch($agent->object);
					if (!$door->id) {
						throw new Device_DC_ICCO_Exception("找不到读卡器绑定的门!");
					}

                    if ($operation->time <= Date::time()) {
                        $record = O('dc_record');
                        $record->time = $operation->time;
                        $record->user = $user;
                        $record->door = $door;
                        $record->direction = $agent->direction;
                        $record->save();

                        //回复通过信息
                        $this->log(sprintf("离线记录 %s %s[%d]通过%s[%d](%s)", date('Y/m/d H:i:s', $record->time), $user->name, $user->id, $door->name, $door->id, $door->location1 . $door->location2));
                    }
				}
				catch (Device_DC_ICCO_Exception $e) {
					// DO NOTHING
					// $this->log(sprintf("机具%02d 读卡器%02d %s", $dev_id, $outlet, $e->getMessage()));
				}

				// 更新检查时间和位置
				$this->offline_record_offset++;
				$this->offline_record_last_process_date = time();

				// 读取下一条
				$this->post_command($dev_id, self::COMMAND_OFFLINE_RECORD_NEW,
									"\x00" . pack('n', $this->offline_record_offset) . "\x00\x01");
			}

			break;
		case self::COMMAND_OFFLINE_RECORD_CLEAN:
			// 清空离线记录的检查标志, 以让 cron 检查重新运行
			$this->log('已清空离线记录');
			$this->offline_record_offset = 0;
			$this->offline_record_last_process_date = 0;
			break;
		case self::COMMAND_GET_DEVICES:
			//查询该DEVICE的序列号
			$this->post_command($dev_id, self::COMMAND_GET_DEVICE_ID);
			break;
		case self::COMMAND_GET_DEVICE_ID:

			$serial_no = $struct->params;

			//清空之前的管理卡号
			$this->send_command($dev_id, 0x0320);

			//同步时间
			// $client->log("同步时间 %s", date('Y/m/d H:i:s'));
			// icco 有奇怪的 bug, 校时前需先读时间, 才能成功设置时间
			$this->send_command($dev_id, 0x0203);
			$this->send_command($dev_id, 0x0202, self::bcd(date('Ymd0wHis')));
			$this->send_command($dev_id, 0x0203);

			// $client->log("设置开锁时长3s");
			$this->send_command($dev_id, 0x0224, "\x00\x1E\x00\x00\x06\x04");

			//设置读卡类型 M1 IC卡
			$this->send_command($dev_id, 0x0212, str_repeat("\x02", self::MAX_OUTLETS));

			$cards = [];

			$this->log("控制器%s已连接", $serial_no);

			$reader_to_door = '';
			$n_door = 0;
			for ($outlet=1; $outlet<=self::MAX_OUTLETS; $outlet++) {
				$addr ="icco://{$serial_no}/{$outlet}";
				$quoted_addr = Q::quote($addr);
				$door = Q("door[in_addr=$quoted_addr|out_addr=$quoted_addr]:limit(1)")->current();
				if ($door->id) {
					if ($door->status != EQ_Status_Model::IN_SERVICE) {
						$this->log("%s[%d](%s)处于故障状态", $door->name, $door->id, $door->location1 . $door->location2);
					}
					else {
						//加入列表
						//方向 进门:1, 出门:0
						$direction = ($door->in_addr == $addr);

						$agent = new Device_Agent($door, TRUE, $direction ? 'in':'out');
						$agent->dev_id = $dev_id;
						$agent->outlet = $outlet;
						$agent->direction = $direction;

						$this->outlet_agents[$dev_id][$outlet] = $agent;

						$this->add_agent($agent);

						$this->log("%s[%d](%s) %s读卡器 已连接", $door->name, $door->id, $door->location1 . $door->location2, $direction ? '进门':'出门');

						$flag = (1 << ($outlet - 1));
						$free_access_cards = (array)$door->get_free_access_cards();
						foreach($free_access_cards as $card_no => $user) {

							if (isset($_SERVER['CARD_BYTE_SWAP'])) {
								$card_no = Misc::uint32_to_string(Misc::byte_swap32($card_no));
							}

							$card_no_s = (string)(($card_no + 0) & 0xffffff);
							$this->log('%s[%d] 添加管理人员 %s[%d] 卡号 %012s(%s)', $door->name, $door->id, $user->name, $user->id, $card_no, $card_no_s);
							$cards[$card_no_s] |= $flag;
						}


						$n_door++;

					}
					//设置读卡器对应的受控门
					$reader_to_door .=  chr((max((int)$door->lock_id, 1) - 1) & 0xff);
                    $connected_door = $door;
				}
				else {
					$reader_to_door .= "\x00";
				}
			}

			// 没有任何门关联
			if ($n_door == 0) {
				$this->log('控制器%s无任何关联的读头', $serial_no);
				throw new Device_Exception;
			}

			// 设置读卡器对应的受控门
			$this->send_command($dev_id, 0x0228, $reader_to_door);

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

			// 初始化为检测刷卡状态
			// 1. 切换模式
			$this->send_command($dev_id, self::COMMAND_ALTER_MODE, "\x30");
			// 2. 触发一下
			$this->send_command($dev_id, self::COMMAND_CARD_OP, "\x00");

			// 一切就绪响应一声
			$this->send_command($dev_id, 0x0706, "\x10\x03\x10\x10");

			// 读取离线卡号的操作已移至 idle 进行
			break;
		case self::COMMAND_CARD_OP:


			$status = ord($struct->params[0]);
			if ($status == 0x00 && strlen($struct->params) == 1) {
				break;
			}

			try {

				$operation = self::parse_card_operation($struct->params);
				if (!$operation) {
					throw new Device_DC_ICCO_Exception("无法识别的刷卡操作");
				}

				$card_no = sprintf('%u', $operation->card_no);
				if (isset($_SERVER['CARD_BYTE_SWAP'])) {
					$card_no = Misc::uint32_to_string(Misc::byte_swap32($card_no));
				}
				$card_no_s = sprintf('%u', $operation->card_no & 0xffffff);


				$user = Q("user[card_no=$card_no|card_no_s=$card_no_s]:limit(1)")->current();
				if (!$user->id) {
					throw new Device_DC_ICCO_Exception(sprintf("卡号%s找不到相应的用户", $operation->card_no));
				}

				$outlet = $operation->reader_id;

				$this->log("机具%02d 读卡器%02d 卡号:%s => 用户:%s[%d]", $dev_id, $outlet, $operation->card_no, $user->name, $user->id);

				$now = time();
				if ($now - $operation->time > 10) {
					//超过10s 拒绝这次命令
					throw new Device_DC_ICCO_Exception("超时的刷卡请求!");
				}

				Cache::L('ME', $user);	//当前用户切换为该用户


				$agent = $this->outlet_agents[$dev_id][$outlet];
				if (!$agent) {
					// 未知门禁

					// 不开
    				$this->send_command($dev_id, self::COMMAND_CARD_OP_RESPONSE, "\x01\x0C".self::bcd(date('YmdHis')));

					// 要求门禁信息
        			$this->post_command($dev_id, self::COMMAND_GET_DEVICE_ID);

					break;
				}


				$door = ORM_Model::refetch($agent->object);
				if (!$door->id) {
					throw new Device_DC_ICCO_Exception("找不到读卡器绑定的门!");
				}


				//检测用户是否可以打开门
				/*
				NO.TASK#274(guoping.zhang@2010.11.27)
				应用权限设置新规则
				*/
				$is_allowed = $user->is_allowed_to('刷卡控制', $door, ['direction'=>$agent->direction]);
				if (!$is_allowed && $door->cannot_access($user, $now, $agent->direction)) {
					throw new Device_DC_ICCO_Exception(sprintf("用户%s[%d]无权打开%s[%d](%s)", $user->name, $user->id, $door->name, $door->id, $door->location1.$door->location2));
				}

				$record = O('dc_record');
				$record->time = $now;
				$record->user = $user;
				$record->door = $door;
				$record->direction = $agent->direction;
				$record->save();

				//回复通过信息
				$this->log(sprintf("机具%02d 读卡器%02d 通过", $dev_id, $outlet));
				$this->send_command($dev_id, self::COMMAND_CARD_OP_RESPONSE, "\x01\x02".self::bcd(date('YmdHis')));

				// 常开 常闭 正常开门
				$this->command_open(['agent'=>$agent, 'user'=>$user]);

			}
			catch(Device_DC_ICCO_Exception $e) {
				$this->log(sprintf("机具%02d 读卡器%02d %s", $dev_id, $outlet, $e->getMessage()));
				$this->send_command($dev_id, self::COMMAND_CARD_OP_RESPONSE, "\x01\x0C".self::bcd(date('YmdHis')));
			}

			break;
		/*
		case 0x0601:

			$params = $struct->params;
			$reader_num = ord($params[0]);
			$relay_num = ord($params[1]);

			$skip = 2 + ceil($relay_num / 8) + ceil($reader_num / 8);
			$status = substr($params, $skip, ceil($relay_num / 8));

			foreach($this->outlet_agents[$dev_id] as $outlet => $agent) {
				$i = $outlet - 1;
				$byte = ord($status[floor($i/4)]);
				$shift = ($i % 4) * 2;
				$is_open = ($byte & (0x1 << $shift)) ? TRUE : FALSE;

				$door = ORM_Model::refetch($agent->object);
				if (!$door->id) continue;

				if ($door->is_open != $is_open) {
					$door->is_open = $is_open;
					$door->save();
				}
			}

			break;
		*/
		case 0x0607:
			// 门磁返回
			$status = self::be2s($struct->params);
			foreach((array) $this->outlet_agents[$dev_id] as $outlet => $agent) {

				$door = ORM_Model::refetch($agent->object);
				if (!$door->id) continue;

				$detector_id = max((int)$door->detector_id, 1);

				$is_open = !(($status >> ($detector_id-1)) & 0x1);

				if ($door->is_open != $is_open) {
					$door->is_open = $is_open;
					$door->save();
				}

			}

			break;

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

	function command_sync($data) {
		//同步数据 可重新启动
		$this->log('重启来同步门禁设置');
		exit;
	}

	function command_open($data) {
		$user = $data['user'];
		$agent = (object)$data['agent'];

		$dev_id = $agent->dev_id;
		$direction = $agent->direction;

		$door = ORM_Model::refetch($agent->object);

		$lock_id = max((int)$door->lock_id, 1);

		$relays = self::s2be(1<<($lock_id - 1));

		$door = ORM_Model::refetch($agent->object);

		$this->log("%s[%d]尝试开启%s[%d] (%s) 方向 => %s", $user->name, $user->id, $door->name, $door->id, $door->location1 . $door->location2, $direction ? '进门':'出门');

		$this->send_command($dev_id, self::COMMAND_RELAY_SWITCH, "\x02".$relays);

		return TRUE;

	}

	static function parse_offline_card_operation($params) {

		// 如果没有参数 (whbio 的 icco1 当没有记录时会如此返回),
		// 或全 F, 则已无记录;
		if (!$params || $params[1] == "\xFF") {
			return self::PARSE_OFFLINE_CARD_OPERATION_EMPTY;
		}

		// 否则可能是正常记录, 可能有错误记录, 总之都要继续读取.
		if (!in_array($params[2] , ["\x02"])) { // \x02，正常通行；
			// 其他可能是错误数据
			return self::PARSE_OFFLINE_CARD_OPERATION_ERROR;
		}

		$operation = new StdClass;
		$operation->card_no = substr(self::parse_bcd(substr($params, 9, 6)), -10) + 0;
		$operation->reader_id = 1 + (ord($params[1]) & 0x7f);
		//$operation->status = ord($params[7]);
		$operation->time = mktime( // mktime 要求: 时分秒月日年
			self::parse_bcd($params[6]), self::parse_bcd($params[7]), self::parse_bcd($params[8]),
			self::parse_bcd($params[4]), self::parse_bcd($params[5]), self::parse_bcd("\x20".$params[3]));
		// 数据格式: 年(不带20)月日时分秒

		return $operation;

	}

	static function parse_card_operation($params) {

		$operation = new StdClass;
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

	static function be2s($be) {
		return (ord($be[0])<<8) + ord($be[1]);
	}

	static function s2be($n) {
		return
			chr($n>>8 & 0xff)
			. chr($n & 0xff);
	}

	static function l2be($n) {
		return chr($n>>24 & 0xff)
			. chr($n>>16 & 0xff)
			. chr($n>>8 & 0xff)
			. chr($n & 0xff);
	}

	static function lrc($buffer) {
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

	static function hexify($data) {
		$hex = '';
		for ($i=0; $i<strlen($data); $i++) {
			$hex .= sprintf("%02X ", ord($data[$i]));
		}
		return $hex;
	}

}

