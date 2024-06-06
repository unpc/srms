<?php

class Sensor_Exception extends Exception {}

class Device_Sensor_TSZZ extends Device {
	
	const FLAG_NEW=0;
	const FLAG_COMMAND = 1;
	const FLAG_ADDR = 2;
	const FLAG_PARAMS = 3;
	const FLAG_CRC = 4;
	
	/* 清空数据的时间 */
	private $_clean_up_time;

	/* 清空数据的时间间隔 */
	private $_clean_up_interval 	 = 300;
	private $_sensor_update_interval = 300;

	private $_nodata_max_retries = 3;
	
	//private $_sample_interval = 5;

	private $_insert_time = [];
	private $_warning_time = [];
	
	/* 每个sensor没有数据报警的时间 */
	private $_nodata_time = [];
	
	/* 用于存放每次可执行刷新的sensor的地址 */
	private $_sensor_update_time;
	
	//env_actual_datapoint 缓冲时间带 5分钟 
	private $_cache_time = 300;

	//上次进行发送接收的时间
	private $_min_active_interval = 2;

	private $_last_active_time = 0;


	function __construct($in, $out = NULL) {
		/* 上次进行发送接受的时间间隔值 */
		$this->_min_active_interval = Config::get('envmon.min_active_interval') ?: $this->_min_active_interval;

		/* env_actual_datapoint 记录的缓冲时间 */
		$this->_cache_time = Config::get('envmon.cache_time') ?: $this->_cache_time;

		/* 821无数据再次查询的次数 */
		$this->_nodata_max_retries = Config::get('envmon.nodata_max_retries') ?: $this->_nodata_max_retries;

		/* 清空env_actual_datapoint数据的时间间隔 */
		$this->_clean_up_interval = Config::get('envmon.clean_up_interval') ?: $this->_clean_up_interval;

		/* 更新查询列表中之前无数据被清除掉的sensor间隔 */
		$this->_sensor_update_interval = Config::get('envmon.sensor_update_interval') ?: $this->_sensor_update_interval;

		/* 同一sensor需要两次查询的间隔时间值 （现在的机制可能没必要，先注释了）*/  
		//$this->_sample_interval = Config::get('envmon.sample_interval') ?: $this->_sample_interval;

		/* tszz超时等待的时间间隔 */
		$this->timeout = Config::get('envmon.timeout') ?: $this->timeout;

		parent::__construct($in, $out);
	}

	function close() {
		foreach ($this->agents() as $agent) {
			$node = $agent->object;
			if ($node->id) {
				$this->log("%s[%d]断开连接", $node->name, $node->id);
			}
		}
		parent::close();
	}

	protected $timeout = 4;
	
	private $_buffer = '';
	private $_buffer_status = 0;
	private $_command;
	
	function recv_command() {

		$this->_last_active_time = Date::time();

	 	$ch = $this->read(1);
	 	switch ($this->_buffer_status) {
	 	case self::FLAG_COMMAND:
	 		$this->_command['command'] = ord($ch);
			$this->_buffer_status = self::FLAG_ADDR;
			break;
		case self::FLAG_ADDR:
			$this->_command['addr'] = ord($ch);
			switch ($this->_command['command']) {
			case 0x31:
				$this->_buffer_status = self::FLAG_PARAMS;
				break;
			default:
				$this->_buffer_status = self::FLAG_CRC;
			}
			break;
		case self::FLAG_PARAMS:
			$this->_command['params'] .= $ch;
			switch ($this->_command['command']) {
			case 0x31:
				$len = 4;
				break;
			default: 
				$len = 1;
			}
			if (strlen($this->_command['params']) == $len) {
				$this->_buffer_status = self::FLAG_CRC;
			}
			break;
		case self::FLAG_CRC:
			$this->_buffer_status = self::FLAG_NEW;
			$this->debug_command($this->_buffer . $ch, TRUE, TRUE);
			if (self::crc($this->_buffer) == $ch) {
				$this->_buffer = '';
				return (object) $this->_command;
			}
			else {
				$this->_buffer = '';
				return NULL;
			}
			break;
	 	default:	//NEW
	 		if (strlen($this->_buffer) == 2) {
	 			$this->_command = NULL;
	 			if ($this->_buffer.$ch != 'com') {
	 				$this->_buffer = substr($this->_buffer, 1);
	 				$this->debug_command($ch, TRUE, TRUE);
	 			}
	 			else {
	 				$this->_buffer_status = self::FLAG_COMMAND;
	 			}
	 		}
	 	}
	 	
	 	$this->_buffer .= $ch;
	}

	function post_command($addr, $command, $params=NULL) {
		if ( $this->_last_active_time + $this->_min_active_interval > Date::time()) sleep($this->_min_active_interval);

		$data = 'com'.chr($command).chr($addr);
		$crc = self::crc($data);
		$this->debug_command($data.$crc, FALSE, TRUE);
		$this->write($data.$crc);

		$this->_last_active_time = Date::time();
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

		$this->clean_up();
		$this->send_dequeue();
	}
	
	private $send_queue = [];	
	
	function send_dequeue() {

		$now = Date::time();
		
		//取第一个
		foreach ($this->send_queue as $key => &$s) {
			break;
		}	

		$this->post_command($s[0], $s[1]);
		
		//有数据返回时再访问一遍
		//s[0] device地址
		//s[1] 发送的命令
		//s[2] 控制重复次数的
		if ($s[2] >= $this->_nodata_max_retries - 1) {
			unset($this->send_queue[$key]);	
			$s[2] = 0;  //无数据请求次数
			$this->send_queue[$key] = $s;		
		}
		else {
			$s[2]++;
		}
	}
	
	function process_command($command) {
		$full_addr = 'tszz://'.$this->channel.'/'.$command->addr;

	    $this->clean_up(); 
		$sensor = O('env_sensor', ['address'=>$full_addr]);
		if ($sensor->id) {
			$params = $command->params;		

			$current_value = ord($params[1])*10 + ord($params[2]) + ord($params[3]) * 0.1;
			if (ord($params[0]) != 0x0f) {
				$current_value = - $current_value;
			}
				
	        if ($sensor->status == Env_Sensor_Model::IN_SERVICE) {
			    $this->update_sensor_value($sensor, $current_value);
	        }

			$now = Date::time();

			//有数据返回，把当前传感器放到队列末尾
			unset($this->send_queue[$sensor->id]);	
			$this->send_queue[$sensor->id] = [$command->addr, 0x31, 0];
		}

		$this->send_dequeue();
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
				//添加新增的传感器
				if (!isset($this->send_queue[$sensor->id])) {
					$this->send_queue[$sensor->id] = [$addr, 0x31, 0];
				}
				
				$exist_sensor[] = $sensor->id;
			}
			
			//删除被移除的传感器
			$queue_keys = array_keys($this->send_queue);
			$delete_array = array_diff($queue_keys, $exist_sensor);
			foreach ($delete_array as $key=>$id) {
				unset($this->send_queue[$id]);
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
		ORM_Pool::release($point);
		
		if ($now - $sensor->interval >= $this->_insert_time[$sensor->id]) {
			$this->_insert_time[$sensor->id] = $now;

			$point = O('env_datapoint');
			$point->sensor = $sensor;
			$point->ctime = $now;
			$point->value = $current_value;
			$point->save();
			ORM_Pool::release($point);
		}

		ORM_Pool::release($sensor);
	}

}
