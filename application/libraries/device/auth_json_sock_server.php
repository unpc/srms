<?php
/*
  适用于 Auth_JSON_Sock_Server 的服务脚本(xiaopei.li@2012-02-10)
*/
class Device_Auth_JSON_Sock_Server extends Device {

	function recv_command() {
		// 通信使用 json
		$raw_data = $this->read_line();
		$data = @json_decode($raw_data, TRUE);
		if (is_array($data)) {
			$this->debug_command($raw_data, TRUE, FALSE);
			return (object)$data;
		}

		return NULL;
	}

	function post_command($params) {
		// 通信使用 json
		$raw_data = @json_encode($params);
		$this->write_line($raw_data);
	}

	function process_command($struct) {
		$command = $struct->command;
		if ($command) {
			$method = 'on_command_'.$command;
			if (method_exists($this, $method)) {
				return $this->$method($struct);
			}
		}
	}

	function on_command_verify($struct) {
		$auth = new Auth($struct->token);

		$result = 0;
		if (TRUE == $auth->verify($struct->password)) {
			$result = 1;
		}

		$this->post_command( ['result'=>$result] );
	}
}
