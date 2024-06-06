<?php

// 7.3.2010 CACS协议升级至6.2 添加设置上传间隔功能

class Device_DC_CACS_Exception extends Exception {}

class Device_DC_CACS extends Device {

	const PACKET_FLAG = "\x28\xEA\x85\x9A";
		
	private $is_ready = FALSE;
	protected $timeout = 300;

	function close() {
		foreach ($this->agents() as $agent) {
			$door = $agent->object;
			if ($door->id) {
				$this->log("仪器%s[%d](%s)断开连接", $door->name, $door->id, $door->location1.$door->location2);
			}
		}
		parent::close();
	}

	function recv_command() {
		
		$header = $this->read(8);
		if (substr($header, 0, 4) != self::PACKET_FLAG) {
			$this->debug_command($header, TRUE, TRUE);
			throw new Device_Exception('[帧解析] 无法找到帧头标志');
		}

		$len_le = substr($header, 4, 4);
		$len = self::le2l($len_le) + 2;
		
		$body = $this->read($len);
		$this->debug_command($header.$body, TRUE, TRUE);

		if (strlen($body) != $len) {
			throw new Device_Exception('[帧解析] 正文长度异常');
		}

		$crc = substr($body, -2);
		$body = substr($body, 0, -2);
		if ($crc != self::crc($header.$body)) {
			throw new Device_Exception('[帧解析] 帧校验失败');
		}

		return (object) [
			'command'=>self::le2l(substr($body, 0, 4)), 
			'params'=> substr($body, 4)
		];

	}
	
	function post_command($command, $params=NULL) 
	{		
		$data = self::pack_command($command, $params);
		$this->debug_command($data, FALSE, TRUE);
		$this->write($data);
	}

	function process_command($struct) {
		
		$command = $struct->command;

		if (!$this->is_ready) {
			switch ($command) {
			case 0xFE01:
				if (!$struct->params) {
					throw new Device_Exception;
				}
					
				$dev_id = trim(substr($struct->params, 22));
				$this->log("控制器%s已连接", $dev_id);
				
				$addr = "cacs://$dev_id";
				$door = Q("door[in_addr=$addr|out_addr=$addr]:limit(1)")->current();

				if (!$door->id) {
					$this->log("无法找到与%s关联的门", $addr);
					throw new Device_Exception;
				}
				
				if ($door->status != EQ_Status_Model::IN_SERVICE) {
					$this->log("%s[%d](%s)处于故障状态", $door->name, $door->id, $door->location1 . $door->location2);
					throw new Device_Exception;
				}

				$direction = ($door->in_addr == $addr);
				
				$agent = new Device_Agent($door, TRUE, $direction ? 'in':'out');
				$agent->direction = $direction;

				$this->add_agent($agent);
			
				$this->log("%s[%d](%s)已连接", $door->name, $door->id, $door->location1 . $door->location2);

				$door->last_event_no = 0;
				$door->save();
				
				// 注册控制器
				$this->post_command(0xFE02, "\x00\x00\x00\x00\x00\x00\x00\x00");	

				// 删除所有星期时间表
				$this->post_command(0xFE15, "\x02\xFF\xFF");
				
				// 删除所有成员员信息
				$this->post_command(0xFE0F, "\x02\x00\x00\x00\x00");
				
				//校时
				$this->log("同步时间 %s", date('Y/m/d H:i:s'));
				$this->post_command(0xFE22, self::date2le());
	
				//设置门参数
				$this->post_command(0xFE07,
					"\x01\x00\x00\x00"
					. "\x00\x00\x00"
					. "\x00\x00\x00"
					. "\x01"				//门磁输入类型 开路有效
					. "\x00"				//电锁输出类型 有输出为常开
					. "\x00"				//出门按钮 闭路有效
					. "\x00"				//开门模式 刷卡开门
					. "\x01\x00\xFF\xFF\xFF\xFF"
					. "\x03\x00\x00\x00"	//门开保持时间 3s
					. "\xFF\xFF\xFF\xFF"	//门开超时时间 0
					. "\x00\x00\x00\x00"	//胁迫告警输出时间 0
					. "\x00\x00\x00\x00"	//胁迫密码
					. "\x00\x00\x00\x00"	//门密码
					. "\x00\x00\x00\x00"	//第1互锁门编号
					. "\x00\x00\x00\x00"	//第2互锁门编号
					. "\x00\x00\x00\x00"	//第3互锁门编号
					. "\x00\x00\x00\x00"	//多卡开门间隔时间
					. "\x00\x00\x00\x00"	//卡加密码间隔时间
					. "\x00"				// 所类型，0表示可以常开；1表示只开1秒
					);

				// 7.3.2010 CACS协议升级至6.2 添加设置上传间隔功能 
				// 设置上传间隔50ms
				$this->post_command(0xFE25, self::l2le(50));
				
				//写入新的管理卡号			
				$cards = [];

				$free_access_cards = (array)$door->get_free_access_cards();
				foreach($free_access_cards as $card_no => $user) {
					if (isset($_SERVER['CARD_BYTE_SWAP'])) {
						$card_no = Misc::uint32_to_string(Misc::byte_swap32($card_no));
					}							

					$cards[$card_no] = $user;

					$card_no_s = (string)(($card_no+0) & 0xffffff);
					$cards[$card_no_s] = $user;
				}

				foreach ($cards as $card_no => $user) {
					$this->log('写入管理员卡号 %s[%d] => %012s', $user->name, $user->id, $card_no); 
					$this->post_command(0xFE0B,
						"\x01"	// 一张卡
						.self::l2le($card_no)
						.self::l2le($user->id)
						."\x00\x00\x00\x00"
						.self::date2le()
						."\x00"
						.self::date2le(Date::time() + 315360000)
						."\x00" 	//卡类型
						."\xC0"		//门1 不受准进时段限制
						."\x01\x00"	//刷卡授权
						."\xC0"		//门2 不受准进时段限制
						."\x01\x00"	//刷卡授权
						."\xC0"		//门3 不受准进时段限制
						."\x01\x00"	//刷卡授权
						."\xC0"		//门4 不受准进时段限制
						."\x01\x00"	//刷卡授权
						."\xFF\xFF"	// 当前区域号
					);
				}
				
				$this->is_ready = TRUE;
				break;
			}
		}
		else {
			
			$this->sync_time();

			$agent = $this->agent(0);

			switch($command) {
			case 0xFE1D:
				//门状态
				$door = ORM_Model::refetch($agent->object);
				if (!$door->id) {
					throw new Device_Exception('无法识别的门');
				}
	
				$params = $struct->params;
				$is_open = !(ord($struct->params[10]) & 0x01);

				if ($door->is_open != $is_open) {
					$door->is_open = $is_open;
					$door->save();
				}
				
				break;
			
			case 0xFE1E:
	
				$door = ORM_Model::refetch($agent->object);
				if (!$door->id) {
					throw new Device_Exception('无法识别的门');
				}
	
				//事件告警 返回值
				$event_no = self::le2l(substr($struct->params, 2, 4));	
				$count = self::le2s(substr($struct->params, 0, 2));

				$this->post_command(0xFE1F, "\x00\x00\x00\x00". self::s2le($count). self::l2le($event_no));
				
				if ($door->last_event_no == $event_no) {
					//重复事件
					break;
				}
				
				$door->last_event_no = $event_no;
				$door->save();
				
				$start = 6 + 21 * ($count - 1) ;
				$i = $count;

				$now = time();

				while ($i--) {

					$params = substr($struct->params, $start, 21);
					$type = ord($params[0]);
					if ($type == 0)  {	//刷卡操作

						$time = mktime( ord($params[5]), ord($params[6]), ord($params[7]),
									ord($params[3]), ord($params[4]), self::le2s(substr($params, 1, 2)) );

						$card_no = self::le2l(substr($params, 12, 4));
						$status = self::le2l(substr($params, 16, 4)); 

						if (isset($_SERVER['CARD_BYTE_SWAP'])) {
							$card_no = Misc::uint32_to_string(Misc::byte_swap32($card_no));
						}							

						try {

							$user = Q("user[card_no=$card_no]:limit(1)")->current();

				            if (!$user->id) {
				                $card_no_s = (string)(($card_no+0) & 0xffffff);
				                $user = Q("user[card_no_s=$card_no_s]:limit(1)")->current();
				            }

							if (!$user->id) {
								throw new Device_DC_CACS_Exception(sprintf("卡号%s找不到相应的用户", $card_no));
							}
						
							$this->log("卡号:%s => 用户:%s STATUS:%d", $card_no, $user->name, $status);		
							
							Cache::L('ME', $user);	//当前用户切换为该用户

							if ($status && $time > $agent->ctime && $now - $time < 5) {
								// 连接后的 5s 内事件
								// 进行远程验证并开锁
							
								//检测用户是否可以打开门
								/*
								NO.TASK#274(guoping.zhang@2010.11.27)
								应用权限设置新规则
								 */
								$is_allowed = $user->is_allowed_to('刷卡控制', $door, ['direction'=>$direction]);
								if (!$is_allowed && $door->cannot_access($user, $now, $direction)) {
									throw new Device_DC_CACS_Exception(sprintf("用户%s[%d]无权打开%s[%d](%s)", $user->name, $user->id, $door->name, $door->id, $door->location1.$door->location2));
								}			

								$this->command_open(['user'=>$user, 'agent'=>$agent]);
							
							}
							else {
								$this->log('STATUS: %d, TIME: %s, NOW: %s', $status, Date::format($time), Date::format($now));
							}
				
                            if ($time <= Date::time()) {
                                $record = O('dc_record');
                                $record->time = $time;
                                $record->user = $user;
                                $record->door = $door;
                                $record->direction = $agent->direction;
                                $record->save();

                                if ($time < $agent->ctime) {
                                    $this->log("离线记录 %s %s[%d]通过%s[%d](%s)", date('Y/m/d H:i:s', $record->time), $user->name, $user->id, $door->name, $door->id, $door->location1 . $door->location2);
                                }
                            }
												
						}
						catch (Device_DC_CACS_Exception $e) {
							$this->log($e->getMessage());
						}
						
						break;
						
					}
	
					$start -= 21;
	
				}
				
				break;
	
			case 0xFE24:
				//控制器状态
				break;
			
			default:
				//$this->log("插座%02d 未知消息", $struct->dev_id);
			}
		}

	}

	function command_sync($data) {
		//同步数据 可重新启动
		$this->log('重启来同步门禁设置');
		exit;
	}
	
	function command_open ($data) {
	
		$user = $data['user'];
		$agent = $data['agent'];
		
		$direction = $agent->direction;
		
		$door = ORM_Model::refetch($agent->object);

		$this->log("%s[%d]尝试开启%s[%d] (%s) 方向 => %s", $user->name, $user->id, $door->name, $door->id, $door->location1 . $door->location2, $direction ? '进门':'出门');
		
		$this->post_command(0xFE20, "\x00\x01\x00\x00\x00");	//1号门开门一次
		
		return TRUE;
	}
	
	private function wait_for($command, $expect_value=NULL, $timeout = 0.500) {

		$start_time = microtime(TRUE);
		
		$this->is_waiting_for = $command;
		
		$retval = NULL;
		
		while (TRUE) {
			
			$data = $this->process_data($timeout);
			if ($data->command == $command && ($expect_value===NULL || $data->return_value == $expect_value)) {
				$retval = $data;
				break;
			}

			$now = microtime(TRUE);			
			$timeout -= ($now - $start_time);
			if ($timeout <= 0) break;
		}
		
		$this->is_waiting_for = 0;
		
		return $retval;
	}
	
	static function pack_command($command, $params)
	{
		$body = self::PACKET_FLAG 
			. self::l2le(4 + strlen($params))
			. self::l2le($command)
			. $params;
		return 
			$body
			. self::crc($body);
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

	static function le2s($le)
	{
		list(,$s) = unpack('v', $le);
		return $s;
	}
	
	static function s2le($n)
	{
		return pack('v', $n);
	}
	
	static function l2le($n)
	{
		return pack('V', $n);

	}
	
	static function le2l($le)
	{
		list(,$l) = unpack('V', $le);
		return $l;
	}
	
	static function date2le($time = NULL) {
		if (!$time) $time = time();
		$now = getdate($time);
		return self::s2le($now['year']).chr($now['mon']).chr($now['mday']).chr($now['hours']).chr($now['minutes']).chr($now['seconds']);
	}
	
	static function crc($buffer)
	{
		/* CRC 高位字节值表 */ 				
		static $auchCRCHi = [
			0x00,0xC1,0x81,0x40,0x01,0xC0,0x80,0x41,0x01,0xC0,
			0x80,0x41,0x00,0xC1,0x81,0x40,0x01,0xC0,0x80,0x41,
			0x00,0xC1,0x81,0x40,0x00,0xC1,0x81,0x40,0x01,0xC0,
			0x80,0x41,0x01,0xC0,0x80,0x41,0x00,0xC1,0x81,0x40,
			0x00,0xC1,0x81,0x40,0x01,0xC0,0x80,0x41,0x00,0xC1,
			0x81,0x40,0x01,0xC0,0x80,0x41,0x01,0xC0,0x80,0x41,
			0x00,0xC1,0x81,0x40,0x01,0xC0,0x80,0x41,0x00,0xC1,
			0x81,0x40,0x00,0xC1,0x81,0x40,0x01,0xC0,0x80,0x41,
			0x00,0xC1,0x81,0x40,0x01,0xC0,0x80,0x41,0x01,0xC0,
			0x80,0x41,0x00,0xC1,0x81,0x40,0x00,0xC1,0x81,0x40,
			0x01,0xC0,0x80,0x41,0x01,0xC0,0x80,0x41,0x00,0xC1,
			0x81,0x40,0x01,0xC0,0x80,0x41,0x00,0xC1,0x81,0x40,
			0x00,0xC1,0x81,0x40,0x01,0xC0,0x80,0x41,0x01,0xC0,
			0x80,0x41,0x00,0xC1,0x81,0x40,0x00,0xC1,0x81,0x40,
			0x01,0xC0,0x80,0x41,0x00,0xC1,0x81,0x40,0x01,0xC0,
			0x80,0x41,0x01,0xC0,0x80,0x41,0x00,0xC1,0x81,0x40,
			0x00,0xC1,0x81,0x40,0x01,0xC0,0x80,0x41,0x01,0xC0,
			0x80,0x41,0x00,0xC1,0x81,0x40,0x01,0xC0,0x80,0x41,
			0x00,0xC1,0x81,0x40,0x00,0xC1,0x81,0x40,0x01,0xC0,
			0x80,0x41,0x00,0xC1,0x81,0x40,0x01,0xC0,0x80,0x41,
			0x01,0xC0,0x80,0x41,0x00,0xC1,0x81,0x40,0x01,0xC0,
			0x80,0x41,0x00,0xC1,0x81,0x40,0x00,0xC1,0x81,0x40,
			0x01,0xC0,0x80,0x41,0x01,0xC0,0x80,0x41,0x00,0xC1,
			0x81,0x40,0x00,0xC1,0x81,0x40,0x01,0xC0,0x80,0x41,
			0x00,0xC1,0x81,0x40,0x01,0xC0,0x80,0x41,0x01,0xC0,
			0x80,0x41,0x00,0xC1,0x81,0x40 
		];
		
		/* CRC 低位字节值表 */ 
		static $auchCRCLo = [
			0x00,0xC0,0xC1,0x01,0xC3,0x03,0x02,0xC2,0xC6,0x06,
			0x07,0xC7,0x05,0xC5,0xC4,0x04,0xCC,0x0C,0x0D,0xCD,
			0x0F,0xCF,0xCE,0x0E,0x0A,0xCA,0xCB,0x0B,0xC9,0x09,
			0x08,0xC8,0xD8,0x18,0x19,0xD9,0x1B,0xDB,0xDA,0x1A,
			0x1E,0xDE,0xDF,0x1F,0xDD,0x1D,0x1C,0xDC,0x14,0xD4,
			0xD5,0x15,0xD7,0x17,0x16,0xD6,0xD2,0x12,0x13,0xD3,
			0x11,0xD1,0xD0,0x10,0xF0,0x30,0x31,0xF1,0x33,0xF3,
			0xF2,0x32,0x36,0xF6,0xF7,0x37,0xF5,0x35,0x34,0xF4,
			0x3C,0xFC,0xFD,0x3D,0xFF,0x3F,0x3E,0xFE,0xFA,0x3A,
			0x3B,0xFB,0x39,0xF9,0xF8,0x38,0x28,0xE8,0xE9,0x29,
			0xEB,0x2B,0x2A,0xEA,0xEE,0x2E,0x2F,0xEF,0x2D,0xED,
			0xEC,0x2C,0xE4,0x24,0x25,0xE5,0x27,0xE7,0xE6,0x26,
			0x22,0xE2,0xE3,0x23,0xE1,0x21,0x20,0xE0,0xA0,0x60,
			0x61,0xA1,0x63,0xA3,0xA2,0x62,0x66,0xA6,0xA7,0x67,
			0xA5,0x65,0x64,0xA4,0x6C,0xAC,0xAD,0x6D,0xAF,0x6F,
			0x6E,0xAE,0xAA,0x6A,0x6B,0xAB,0x69,0xA9,0xA8,0x68,
			0x78,0xB8,0xB9,0x79,0xBB,0x7B,0x7A,0xBA,0xBE,0x7E,
			0x7F,0xBF,0x7D,0xBD,0xBC,0x7C,0xB4,0x74,0x75,0xB5,
			0x77,0xB7,0xB6,0x76,0x72,0xB2,0xB3,0x73,0xB1,0x71,
			0x70,0xB0,0x50,0x90,0x91,0x51,0x93,0x53,0x52,0x92,
			0x96,0x56,0x57,0x97,0x55,0x95,0x94,0x54,0x9C,0x5C,
			0x5D,0x9D,0x5F,0x9F,0x9E,0x5E,0x5A,0x9A,0x9B,0x5B,
			0x99,0x59,0x58,0x98,0x88,0x48,0x49,0x89,0x4B,0x8B,
			0x8A,0x4A,0x4E,0x8E,0x8F,0x4F,0x8D,0x4D,0x4C,0x8C,
			0x44,0x84,0x85,0x45,0x87,0x47,0x46,0x86,0x82,0x42,
			0x43,0x83,0x41,0x81,0x80,0x40 
		];
		
		$uchCRCHi = 0xFF ; /* 高CRC字节初始化 */ 	
		$uchCRCLo = 0xFF ; /* 低CRC 字节初始化 */ 
	
		$len = strlen($buffer);	
	
		for ($i = 0; $i < $len; $i++) { 
			
			$uIndex = $uchCRCHi ^ ord($buffer[$i]); /* 计算CRC */ 
			$uchCRCHi = $uchCRCLo ^ $auchCRCHi[$uIndex] ; 			
			$uchCRCLo = $auchCRCLo[$uIndex];
			
		} 
		
		return self::s2le($uchCRCLo << 8 | $uchCRCHi); 
		
	}	

	function idle() {
		if ($this->is_ready) {
			$this->sync_time();
		}
	}
	
	private $_sync_timeout = 0;
	const SYNC_TIME_INTERVAL = 300;

	function sync_time() {

		$now = time();
		if ($now >= $this->_sync_timeout) {

			//校时
			$this->log("同步时间 %s", date('Y/m/d H:i:s'));
			$this->post_command(0xFE22, self::date2le());

			$this->_sync_timeout = $now + self::SYNC_TIME_INTERVAL;
		}
	}
}
