<?php

/*

$agent = new Device_Agent($object);
$agent->call('switch_to', array('power_on'=>TRUE));

$agent = new Device_Agent($object, TRUE);

*/

class Device_Agent {

	const ENDL = "\n";
	const MAX_BUF_SIZE = 1048576;

	public $sock;
	public $object;
	public $ctime;
	public $id;

	private $sock_path = NULL;
	private $real_sock_path = NULL;

	private $server = FALSE;
	
	public static function get_sock_name($object, $suffix=NULL) {
		$sock_name = Config::get('system.tmp_dir').sprintf('devices/%s.%d', $object->name(), $object->id);
		if ($suffix) {
			$sock_name .= '.'.$suffix;
		}
		return $sock_name;
	}

	public function is_zombie() {
		return basename($this->real_sock_path) != @readlink($this->sock_path);
	}

	public function __construct($object, $server = FALSE, $suffix=NULL){		
		$this->object = $object;
		$this->ctime = time();
			
		$this->sock_path = $path = self::get_sock_name($object, $suffix);

		if ($server) {
			$this->server = TRUE;
			$this->real_sock_path = $sock_path = $path . '.' . uniqid();
			$this->id = uniqid();
			
			if (file_exists($sock_path)) {
				@unlink($sock_path);
			}
			else {
				File::check_path($sock_path);
			}
			
			$sock = @stream_socket_server("unix://$sock_path");

			if (!$sock) {
				throw new Device_Exception("Device_Agent无法绑定$sock_path");
			}
			
			if(is_link($path)) @unlink($path);
			
			@symlink(basename($sock_path), $path);
			
			@stream_set_blocking($sock, 1);

			$object->is_monitoring = TRUE;
			$object->is_monitoring_mtime = time();
			$object->save();
			
		}
		else {
            if ($object->device2) {
                $sock = new ZMQSocket(new ZMQContext(), ZMQ::SOCKET_DEALER);

                $sock->setSockOpt(ZMQ::SOCKOPT_RCVTIMEO, 2000);
                // 设置 recv timeout 为 2 秒, 以防 epc-server 不在线时, lims2 一直等待回复, 卡死

                $object->device2['ipc'] && $sock->connect($object->device2['ipc']);
            }
            else {
    			if (!file_exists($path)) return;
    			$sock = @stream_socket_client("unix://$path");
            }
		}

		$this->sock = $sock;

	}

	public function call($command, $data=NULL) {
		
		if (!$this->sock) return FALSE;

        if ($this->sock instanceof ZMQSocket) {
            $d = [];
            foreach ((array)$data as $k =>$v) {
                if (is_object($v)) {
                    $d[$k] = ['name'=>$v->name(), 'id'=>$v->id];
                }
                else {
                    $d[$k] = $v;
                }
            }
            
            $d['user'] = ['username' => L('ME')->token, 'name' => L('ME')->name];
            $d['uuid'] = $this->object->device2['uuid'];
            
            $json = [
    			'jsonrpc' => '2.0',
    			'method' => $command,
    			'params' => $d,
    			'id' => uniqid(),
            ];
            
            $msg = msgpack_pack($json);
            $msg = $this->sock->send($msg)->recv();
            $json = msgpack_unpack($msg);
            return isset($json['error']) ? FALSE : $json['result'];
        }
        
		if (FALSE === $this->post($command, $data)) return FALSE;

        return @unserialize(@fgets($this->sock, self::MAX_BUF_SIZE));
	}

	public function post($command, $data=NULL) {
		if (!$this->sock) return FALSE;

		$data = (array) $data;
		$data['command'] = $command;
		$data['user'] = L('ME');

		if (FALSE === @fwrite($this->sock, serialize($data).self::ENDL)) return FALSE;

		return TRUE;
	}

	public function close() {
		@stream_socket_shutdown($this->sock, STREAM_SHUT_RDWR);
		file_exists($this->real_sock_path) and @unlink($this->real_sock_path);
		if (basename($this->real_sock_path) == @readlink($this->sock_path)) {
			@unlink($this->sock_path);
		}
		
		if ($this->server && $this->object->id) {
			$object = ORM_Model::refetch($this->object);
			$object->is_monitoring = FALSE;
			$object->save();
		}
	}
		
}
