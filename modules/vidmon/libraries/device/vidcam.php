<?php

class Vidcam_Exception extends Exception {}

class Device_Vidcam extends Device {

	const CIPHER_METHOD = 'des';

	public $is_ready = FALSE;
	private $signature;
	private $message;

	function close() {
		foreach ($this->agents() as $agent) {
			$vidcam = $agent->object;
			if ($vidcam->id) {
				$this->log('%s[%d]断开连接', $vidcam->name, $vidcam->id);
			}
		}
		parent::close();
	}

	function post_command($command, $params=NULL)
	{
		$data = (array) $params;
		$data['command'] = $command;
		$raw_data = @json_encode($data);

		$this->debug_command($raw_data, FALSE);

		if ($this->is_ready && $this->server_code) {
			//加密数据
			$code = @openssl_encrypt($raw_data, self::CIPHER_METHOD, $this->server_code, FALSE);
			$raw_data = @json_encode(['command'=>'decode', 'code'=>$code]);
		}

		$this->write_line($raw_data);
	}

	function send_command($command, $params=NULL)
	{
		$this->post_command($command, $params);
		return $this->recv_command();
	}

	function recv_command() {
		// 格式
		$raw_data = $this->read_line();
		$data = @json_decode($raw_data, TRUE);
		if (is_array($data)) {

			if ($data['command'] && $this->client_code) {
				$command = $data['command'];
				if ($command != 'decode') return NULL;

				$raw_data = @openssl_decrypt($data['code'], self::CIPHER_METHOD, $this->client_code, FALSE);
				$data = @json_decode($raw_data, TRUE);
			}

			$this->debug_command($raw_data, TRUE, FALSE);
			return (object)$data;
		}

		return NULL;
	}

	protected $timeout = 2;

	private function keep_alive() {
		if ($this->is_ready) {
			$agent = $this->agent(0);
            $vidcam = ORM_Model::refetch($agent->object);
            if ($vidcam->id) {
                // 刷新is_monitoring_mtime
                $now = Date::time();
                $db = ORM_Model::db('vidcam');
                $db->query('UPDATE `vidcam` SET `is_monitoring`=1, `is_monitoring_mtime`=%d WHERE `id`=%d', $now, $vidcam->id);
                $capture_duration = Config::get('vidmon.capture_duration');
                $upload_duration = Config::get('vidmon.upload_duration');

                if (!$vidcam->capture_key || $vidcam->capture_key_mtime + 30 < $now) {
                    $vidcam->capture_key = Misc::random_password(12);
                    $vidcam->capture_key_mtime = $now;
                    $vidcam->save();
                }

                $upload_url = strtr(Config::get('vidmon.capture_upload_url'), [
                    '%vidcam_id'=>$vidcam->id
                ]);

                if (!$upload_url) {
                    $this->log('无法找到系统capture上传地址,请配置vidmon.php capture_upload_url');
                }

                $alarmed_capture_timeout = Config::get('vidmon.alarmed_capture_timeout');                 
                if ($alarmed_capture_timeout > ($now - $vidcam->last_alarm_time)) {
                    $capture_duration = Config::get('vidmon.alarmed_capture_duration');
                }
                else {
                    $capture_duration = Config::get('vidmon.capture_duration');
                }
                //如果当前时间距离上次capture时间间隔超过系统配置的capture间隔时间，则进行capture, 同时更新last_capture_time，lasst_caputre_time为虚属性
                if ($now - $vidcam->last_capture_time >= $capture_duration) {
                    //发送截图命令
                    $this->post_command('capture', [
                        'file'=>$upload_url,
                        'key'=>$vidcam->capture_key,
                    ]);

                    //设定last_capture_time
                    $vidcam->last_capture_time = $now;
                    $vidcam->save();
                }

                //如果距离上次upload差时间超过原设定capture_duration，则补发capture
                if ($now - $vidcam->last_upload_time >= $upload_duration) {
                    //发送截图命令
                    $this->post_command('capture', [
                        'file'=>$upload_url,
                        'key'=>$vidcam->capture_key,
                    ]);
                }
            }
		}
		Event::trigger('device_vidcam.keep_alive', $this);
	}

	function idle() {
		parent::idle();
		$this->keep_alive();
	}

	function before_local_command() {
		$this->keep_alive();
	}

	function process_command($struct) {
		
		$this->keep_alive();

		$command = $struct->command;
		if ($command) {
			$method = 'on_command_'.$command;
			if (method_exists($this, $method)) {
				return $this->$method($struct);
			}
			else {
				Event::trigger('device_vidcam.remote_command.'.$command, $this, $struct);
			}
		}
	}

	function command_unknown($command, $data) {
		return Event::trigger('device_vidcam.agent_command.'.$command, $this, $data);
	}

	function on_command_connect($struct) {
		if ($this->is_ready) return;
		
		/*
		name: '客户端电脑名',
		message: '随机消息', //用于RSA签名验证
		 */

		$address = trim($struct->address);

		$this->log('视频监控%s尝试连接', $address);

		$vidcam = O('vidcam', ['control_address'=>$address]);
		if (!$vidcam->id) {
			$this->log('无法找到%s关联仪器', $address);
			throw new Device_Exception;
		}
		
		$this->is_ready = TRUE;
		
		$this->add_agent(new Device_Agent($vidcam, TRUE));
		$this->log('%s[%d]已连接', $vidcam->name, $vidcam->id);

		$this->post_command('set_alarm', 0);
		Event::trigger('device_vidcam.on_ready', $this);
	}

	function on_command_capture($struct) {
		if (!$this->is_ready) return;
		$file = $struct->file;
		//TODO: 复制文件到相应的地方
		Event::trigger('device_vidcam.on_capture', $this);
	}

	function on_command_alarm($struct) {
		if (!$this->is_ready) return;
	
		$agent = $this->agent(0);
		$vidcam = ORM_Model::refetch($agent->object);

        $now = Date::time();
        //仅保存报警记录
        $vidcam_alarm = O('vidcam_alarm');
        $vidcam_alarm->vidcam = $vidcam;
        $vidcam_alarm->ctime = $now;
        $vidcam_alarm->save();

        $vidcam->last_alarm_time = $now;
        $vidcam->save();
        
        $capture_duration_dtstart = $now - Config::get('vidmon.alarm_capture_time');

        //设定alarm时间之前的在保存的caputre时间范围内的vidcam_capture_data设定is_alarm 为TRUE
        $db = Database::factory();
        $query = strtr("UPDATE `vidcam_capture_data` SET `is_alarm`='1' WHERE `vidcam_id`='%vidcam_id' AND `ctime`>='%capture_duration_dtstart' AND `ctime`<='%now'", ['%vidcam_id'=>$vidcam->id, '%now'=>$now, '%capture_duration_dtstart'=>$capture_duration_dtstart]);
        $db->query($query);

		$this->log('%s[%d]有报警输入', $vidcam->name, $vidcam->id);
	}

	function command_capture($data) {
		if (!$this->is_ready) return;
		$agent = $this->agent(0);
		$vidcam = ORM_Model::refetch($agent->object);

		// $this->log('%s[%d]进行截图', $vidcam->name, $vidcam->id);
		$this->post_command('capture', [
			'file' => $data['url'],
			'key' => $vidcam->capture_key,
		]);
	}

}
