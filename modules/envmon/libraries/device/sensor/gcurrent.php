<?php

class Sensor_Exception extends Exception {}

class Device_Sensor_GCurrent extends Device {
	
	const FLAG_NEW=0;
	const FLAG_COMMAND = 1;
	const FLAG_ADDR = 2;
	const FLAG_PARAMS = 3;
	const FLAG_CRC = 4;
	
	function close() {
		foreach ($this->agents() as $agent) {
			$node = $agent->object;
			if ($node->id) {
				$this->log("%s[%d]断开连接", $node->name, $node->id);
			}
		}
		parent::close();
	}

	protected $timeout = 2;
	
	private $_buffer = '';
	private $_buffer_status = 0;
	private $_command;
	
	function recv_command() {
		$ch = $this->read(1);

		try {
			if (strlen($this->_buffer) < 13) throw new Exception;

			if (0 != strncmp($this->_buffer, '1G', 2)) {
				$s = substr($this->_buffer, '1G');
				if ($s === FALSE) {
					$this->_buffer = '';
					throw new Exception;
				}
				$this->_buffer = $s;
			}

			$len = substr($this->_buffer, 2, 2);
			if (!is_numeric($len) || $this->_buffer[12] != ' ') {
				$this->_buffer = '';
				throw new Exception;
			}

			if ($len == strlen($this->_buffer)) {
				//finish a frame, try to parse it
				$data = trim(substr($this->_buffer, 13), "\t\r\n ");
				if ($this->_server_key) {
					$data = base64_decode($data);
					// $data = XXTEA::decrypt($data);
				}
			}

		}
		catch (Exception $e) {
		}
	 	$this->_buffer .= $ch;
	}

	function post_command($addr, $command, $params=NULL) {
		$data = 'com'.chr($command).chr($addr);
		$crc = self::crc($data);
		$this->debug_command($data.$crc, FALSE, TRUE);
		$this->write($data.$crc);
	}

	static function crc($data) {
		$len = strlen($data);
		$check = 0;
		for ($i=0;$i<$len;$i++) {
			$check ^= ord($data[$i]);
		}
		return chr($check);
	}
	
	function idle() {
		parent::idle();
		//$this->_system_start_time = $this->_system_start_time ?: time();
		$this->clean_up();
		//$this->check_sensor_data();
		$this->send_dequeue();
	}
	
	private $send_queue = [];	
	function send_dequeue() {
		$now = Date::time();
		$found = FALSE;
		foreach($this->send_queue as $key => &$s) {
			if ($s[2] < $now) {
				$found = TRUE;
				break;
			}
		}				
		
		if ($found) {
			$this->post_command($s[0], $s[1]);
			if ($s[3] >= 3) {
				unset($this->send_queue[$key]);
			}
			else {
				$s[2] = $now + $this->_sample_interval;
				$s[3]++;
			}
		}
	}
	
	function process_command($command) {
	
		$full_addr = 'tszz://'.$this->channel.'/'.$command->addr;
		
		$sensor = O('env_sensor', ['address'=>$full_addr]);
		if (!$sensor->id) return;

		$params = $command->params;		

		$current_value = ord($params[1])*10 + ord($params[2]) + ord($params[3]) * 0.1;
		if (ord($params[0]) != 0x0f) {
			$current_value = - $current_value;
		}
			
        if ($sensor->status == Env_Sensor_Model::IN_SERVICE) {
		    $this->update_sensor_value($sensor, $current_value);
        }

        $this->clean_up(); 
		
		$now = Date::time();
		if (!isset($this->send_queue[$sensor->id])) {
			$this->send_queue[$sensor->id] = [$command->addr, 0x31, $now + $this->_sample_interval, 0];
		}
	}
		
	private function clean_up() {
		$now = Date::time();
		if (!$this->_clean_up_time || $now - $this->_clean_up_time > $this->_clean_up_interval) {		
			$this->_clean_up_time = $now;
			$db = ORM_Model::db('env_actual_datapoint');
			$query = sprintf("DELETE FROM env_actual_datapoint WHERE exp_time < %d", $now);
			$db->query($query);
		}
		
		if (!$this->_sensor_update_time || $now - $this->_sensor_update_time > $this->_sensor_update_interval) {
			$this->_sensor_update_time = $now;
			$prefix = 'tszz://'.$this->channel.'/';
			$db = ORM_Model::db('env_sensor');
			$query = $db->query("SELECT * FROM env_sensor WHERE address LIKE '%s%%'", $prefix);		
			if ($query) while ($sensor = $query->row()) {
				$addr = trim(strtr($sensor->address, [$prefix=>'']));
				if (!isset($this->send_queue[$sensor->id])) {
					$this->send_queue[$sensor->id] = [$addr, 0x31, $now + $this->_sample_interval, 0];
				}
			}	
		}
	}
	
	private function check_sensor_data() {
		$now = Date::time();
			
		$prefix = 'tszz://'.$this->channel.'/';
		$db = ORM_Model::db('env_sensor');
		$query = $db->query("SELECT * FROM env_sensor WHERE address LIKE '%s%%'", $prefix);
		
		if ($query) while ($sensor = $query->row()) {
			if (!isset($this->send_queue[$sensor->id])) {
				$cache_time = $sensor->alert_time ?: $this->_cache_time;
				
				if ($now - $this->_system_start_time < $cache_time) continue;
				
				$nodata_time = $this->_nodata_time[$sensor->id];
				if (!$nodata_time || $now - $nodata_time > $cache_time) {
				
					$nodata_time = $nodata_time ?: ($now - $cache_time);
					$actual_db = ORM_Model::db('env_actual_datapoint');
					$count = $actual_db->value("SELECT COUNT(*) FROM env_actual_datapoint WHERE ctime >= %d AND ctime <= %d AND sensor_id = %d", $nodata_time, $now, $sensor->id);
					if (!$count) {
						$sensor = O('env_sensor', $sensor->id);
						$this->_nodata_time[$sensor->id] = $now;
						
                        $node = $sensor->node;
                        foreach(Q("{$node} user.incharge") as $user) {
                            Notification::send('envmon.sensor.nodata', $user, [
                                '%user' => Markup::encode_Q($user),
                                '%sensor' => $sensor->name,
                                '%dtstart' => Date::format($nodata_time),
                                '%dtend' => Date::relative($now, $nodata_time)
                            ]);
                        }
						
						$tokens = Config::get('envmon.admin');
						$tokens = is_array($tokens) ? $tokens : [$tokens];
						foreach ($tokens as $token) {
							$user = O('user', ['token' => Auth::normalize($token)]);
							if ($user->id) {
								Notification::send('envmon.sensor.nodata', $user, [
									'%user' => Markup::encode_Q($user),
									'%sensor' => $sensor->name,
									'%dtstart' => Date::format($nodata_time),
									'%dtend' => Date::relative($now, $nodata_time)
								]);
							}
						}
					}
				}
			}
		}	
	}
	
	private function update_sensor_value($sensor, $current_value) {
		$now = Date::time();

		$sensor->value = $current_value;
		$sensor->save();

		$point = O('env_actual_datapoint');
		$point->sensor = $sensor;
		$point->ctime = $now;
		$point->exp_time = $now + $sensor->interval;
		$point->value = $current_value;
		$point->save();
		
		$interval = $sensor->interval;
		if ($now - $sensor->interval >= $this->_insert_time[$sensor->id]) {
			$this->_insert_time[$sensor->id] = $now;

			$point = O('env_datapoint');
			$point->sensor = $sensor;
			$point->ctime = $now;
			$point->value = $current_value;
			$point->save();
		}
		
		$cache_time = $sensor->alert_time ?: $this->_cache_time;
		$warning_time = $this->_warning_time[$sensor->id];
		if (!$warning_time || $now - $warning_time > $cache_time) {
			$warning_time = $warning_time ?: ($now - $cache_time);
			
			$db = ORM_Model::db('env_actual_datapoint');
			$average = $db->value("SELECT AVG(value) FROM env_actual_datapoint WHERE ctime >= %d AND ctime <= %d AND sensor_id = %d", $warning_time, $now, $sensor->id);
			$average = round($average, 1);
			if ($average > $sensor->vto || $average < $sensor->vfrom) {
				$this->_warning_time[$sensor->id] = $now;
				
                $node = $sensor->node;
                foreach (Q("{$node} user.incharge") as $user) {
                    Notification::send('envmon.sensor.warning', $user, [
                        '%user' => Markup::encode_Q($user),
                        '%node' => Markup::encode_Q($node),
                        '%sensor' => $sensor->name,
                        '%dtstart' => Date::format($warning_time),
                        '%dtend' => Date::relative($now, $warning_time),
                        '%data' => $average.$sensor->unit(),
                        '%alert_data'=> $current_value.$sensor->unit(),
                        '%standard_start'=> $sensor->vfrom.$sensor->unit(),
                        '%standard_end'=> $sensor->vto.$sensor->unit(),
                    ]);
                }
				
				$tokens = Config::get('envmon.admin');
				$tokens = is_array($tokens) ? $tokens : [$tokens];
				foreach ($tokens as $token) {
					$user = O('user', ['token' => Auth::normalize($token)]);
					if ($user->id) {
						Notification::send('envmon.sensor.warning', $user, [
							'%user' => Markup::encode_Q($user),
							'%node' => Markup::encode_Q($node),
							'%sensor' => $sensor->name,
							'%dtstart' => Date::format($warning_time),
							'%dtend' => Date::relative($now, $warning_time),
							'%data' => $average.$sensor->unit(),
                            '%alert_data'=> $current_value.$sensor->unit(),
                            '%standard_start'=> $sensor->vfrom.$sensor->unit(),
                            '%standard_end'=> $sensor->vto.$sensor->unit(),
						]);
					}
				}
			}
		}
	}
}
