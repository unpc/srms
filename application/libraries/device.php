<?php

/*

$computer = Device::factory('computer');
$computer->add_agent(new Device_Agent($equipment));
$computer->run();


*/

class Device_Exception extends Exception {}

abstract class Device {

	const ENDL = "\n";
	const MAX_BUF_SIZE = 1048576;

	protected $in;
	protected $out;
	
	private $name;
	private $agents = [];
	protected $channel;
	
	public static function factory($name = NULL, $in = NULL, $out = NULL, $channel = NULL) {
		
		$device_class = 'Device_'.$name;
		
		if ($in === NULL) {
			$in = STDIN;
			$out = STDOUT;
		}

		$device =  new $device_class($in, $out);
		$device->name = $name;
		$device->channel = $channel;
		
		return $device;
	}

	public function close() {
		@fclose($this->in);
		@fclose($this->out);
		foreach ($this->agents as $agent) {
			$agent->close();
		}
		$this->agents = [];
		$this->in = NULL;
		$this->out = NULL;
	}

	public function __construct($in, $out = NULL) {
		if ($out === NULL) $out = $in;
		$this->in = $in;
		$this->out = $out;
	}
	
	public function add_agent($agent) {
		$this->agents[$agent->id] = $agent;
	}

	public function remove_agent($agent) {
		unset($this->agents[$agent->id]);
	}
	
	public function agent($index = 0) {
		return current(array_slice($this->agents, $index, 1));
	}
	
	public function agents() {
		return $this->agents;
	}

	public function log() {
		$args = func_get_args();
		if ($args) {
			$format = array_shift($args);
			$str = vsprintf($format, $args);
			$pid = posix_getpid();
			Log::add(strtr('%name[%pid] %str', [
						'%name' => $this->name,
						'%pid' => $pid,
						'%str' => $str,
			]), 'devices');
		}
	}
	
	function read($len) {
		$raw_data = @fread($this->in, $len);
		if (FALSE === $raw_data) throw new Device_Exception('DEVICE连接中断');
		return $raw_data;
	}
	
	function read_line() {
		//$raw_data = @stream_get_line($this->in, self::MAX_BUF_SIZE, self::ENDL);
		$raw_data = @fgets($this->in, self::MAX_BUF_SIZE);
		if (FALSE === $raw_data) throw new Device_Exception('DEVICE连接中断');
		return $raw_data;
	}

	function write($data) {
		$ret = @fwrite($this->out, $data);
		if ($ret === FALSE) throw new Device_Exception('DEVICE连接中断');
		return $ret;
	}

	function write_line($data) {
		$ret = @fwrite($this->out, $data. self::ENDL);
		if ($ret === FALSE) throw new Device_Exception('DEVICE连接中断');
		return $ret;
	}

	private $working_socks = [];
	private $working_data = [];

	protected $timeout = 10;	//最长10s超时

	function process_data($timeout = NULL) {
	
		$socks = ['device'=>$this->in];
		foreach ($this->agents as $key => $agent) {
			if ($agent->sock) $socks[$key] = $agent->sock;
		}
		foreach ($this->working_socks as $key => $sock) {
			$socks[$key] = $sock;
		}
		
		$read_socks = $socks;
		
		if ($timeout > 0) {
			$tv_sec = floor($timeout);
			$tv_usec = ($timeout * 1000000) % 1000000;
		}
		else {
			$tv_sec = floor($this->timeout);
			$tv_usec = ($this->timeout * 1000000) % 1000000;
		}
		
		$retval = @stream_select($read_socks, $write_socks, $except_socks, $tv_sec, $tv_usec);

		if ($retval === FALSE || feof($this->in)) {
			throw new Device_Exception("DEVICE连接中断");
		}
		
		if ($retval == 0) {
			// 超时
			if(!$timeout) $this->idle();
			return NULL;
		}
		
		$data = NULL;
		
		foreach ($read_socks as $sock) {
			if ($sock == $this->in) {
				$data = $this->recv_command();
				if (!is_null($data)) {
					$this->process_command($data);
				}
				continue;
			}
			else {
				$key = array_search($sock, $socks);
			}

			if (isset($this->working_data[$key])) {

				$working_data = $this->working_data[$key];

				$buffer = @fread($sock, self::MAX_BUF_SIZE);
				if (strlen($buffer)==0) {
					@fwrite($sock, @serialize(FALSE).self::ENDL);
					@stream_socket_shutdown($sock, STREAM_SHUT_WR);

					unset($this->working_socks[$key]);
					unset($this->working_data[$key]);
				}
				else {
					$pos = strpos($buffer, self::ENDL);
					
					if ($pos === FALSE) {
						$working_data->buffer .= $buffer;
					}
					else {
						$data = $working_data->buffer . substr($buffer, 0, $pos);
						$working_data->buffer = substr($buffer, $pos + 1);
						$data = @unserialize($data);
						$retval = FALSE;
						
						$this->before_local_command();	
						$method = 'command_'.$data['command'];
						if (method_exists($this, $method)) {
							$key = $working_data->agent_key;
							$data['agent'] = $this->agents[$key];
							$retval = call_user_func([$this, $method], $data);
						}
						else {
							$retval = $this->command_unknown($data['command'], $data);
						}
						@fwrite($sock, @serialize($retval).self::ENDL);
						@stream_socket_shutdown($sock, STREAM_SHUT_WR);

						unset($this->working_socks[$key]);
						unset($this->working_data[$key]);
					}
					
				}

			}
			else {
				$new_sock = @stream_socket_accept($sock);
				$new_key = 'working.'.uniqid();
				$this->working_socks[$new_key] =  $new_sock;
				$this->working_data[$new_key] = (object) [
					'agent_key' => $key,
					'buffer' => '',
				];
			}

		}
		
		return $data;
		
	}

	function before_local_command() {
	}

	function run() {

		try {
		
			while (TRUE) {
				$this->process_data();
			}
		
		}
		catch (Device_Exception $e) {
			$this->log($e->getMessage());
			$this->close();
		}
		
	}
	
	function debug_command($data, $recv=FALSE, $hexify = FALSE) {
		if (defined('DEBUG')) {
			if ($recv) {
				$log_mode = "RECV <== ";
			}
			else {
				$log_mode = "SEND ==> ";
			}
			
			if ($hexify) {
				$hex = '';
				for($i=0; $i<strlen($data); $i++) {
					$hex .= sprintf("%02X ", ord($data[$i]));
				}
				$this->log('%s %s', $log_mode, $hex);
			}
			else {
				$this->log('%s %s', $log_mode, trim($data));
			}
		}
	}

	function idle() {
		$has_zombie = FALSE;
		foreach ($this->agents as $key => $agent) {
			if ($agent->is_zombie()) {
				$has_zombies = TRUE;
				$agent->close();
				unset($this->agents[$key]);

				continue;
			}
			if ($agent->sock) {
				$object = ORM_Model::refetch($agent->object);
				if ($object->id && !$object->is_monitoring) {
					$object->is_monitoring = TRUE;
					$object->is_monitoring_mtime = time();
					$object->save();
				}
			}
			else {
				unset($this->agents[$key]);
			}
		}

		if ($has_zombies && count($this->agents) == 0) {
			throw new Device_Exception('超时后没有可用Agents, 中断链接');
		}
	}

	//please override it
	protected function command_unknown($command, $data) {
	}

    function command_halt() {
        throw new Device_Exception('接收到halt命令，中断链接');
    }
}
