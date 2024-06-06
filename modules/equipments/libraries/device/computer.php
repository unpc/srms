<?php


class EQDevice_Exception extends Exception {}

class Device_Computer extends Device {

	const CIPHER_METHOD = 'des';
    //假的lab_project_id
    const FAKE_PROJECT_ID = -1;

    private $project_id;

	public $is_ready = FALSE;
	private $signature;
	private $message;

	public $os;
	public $version;

    static $feedback_status_map = [
            1 => EQ_Record_Model::FEEDBACK_NORMAL,
            2 => EQ_Record_Model::FEEDBACK_PROBLEM,
            0 => EQ_Record_Model::FEEDBACK_NOTHING
    ];

	function close() {
		foreach ($this->agents() as $agent) {
			$equipment = ORM_Model::refetch($agent->object);
			if ($equipment->id) {
				$equipment->device = NULL;
				$equipment->save();
				$this->log('%s[%d]断开连接', $equipment->name, $equipment->id);
			}
		}
		parent::close();
	}

	private function _get_iv() {
		return "\0\0\0\0\0\0\0\0";
	}

	function post_command($command, $params=NULL)
	{
		$data = (array) $params;
		$data['command'] = $command;

        //需要将$data强制设定为object
        //删除JSON_FORCE_OBJECT
        //针对特殊数据结构应考虑传入之前自行处理类型
		$raw_data = @json_encode($data);

		$this->debug_command($raw_data, FALSE);

		if ($this->is_ready && $this->server_code) {
			//加密数据
			$code = @openssl_encrypt($raw_data, self::CIPHER_METHOD, $this->server_code, FALSE, self::_get_iv());
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

				$raw_data = @openssl_decrypt($data['code'], self::CIPHER_METHOD, $this->client_code, FALSE, self::_get_iv());
				$data = @json_decode($raw_data, TRUE);
			}

			$this->debug_command($raw_data, TRUE, FALSE);
			return (object)$data;
		}

		return NULL;
	}

	protected $timeout = 5;

	// 同步时间相关的设置:
	private $server_time_timeout = 5; // TODO 如果该时间 > 5, 则会造成连接校时两次后就断开, 即使调大 $timeout 也不行, 具体原因暂未查出(xiaopei.li@2012-10-09)
	private $server_time_expire = 0;

	// 刷新 is_monitoring_mtime 相关的设置:
	// 刷新仪器监控状态的脚本(cli/update_eq_mon_mtime.php)中, 认为更新时间在 1 分钟前为过期,
	// 故数据库更新 is_monitoring_mtime 的间隔只要小于 1 分钟就可, 不必太频繁;
	// (xiaopei.li@2012-10-09)
	private $monitoring_timeout = 30; // TODO 另测试出即使 $monitoring_timeout 设为很大的值(如 10000), 上次更新约 85 秒后, is_monitoring_mtime 肯定会更新, 具体原因暂未查出 (xiaopei.li@2012-10-09)
	private $monitoring_expire = 0;

	private function keep_alive() {
		if ($this->is_ready) {
			$agent = $this->agent(0);
			if ($agent->object->id) {

				$now = Date::time();

				if ($now >= $this->server_time_expire) {
					// 同步时间
					$this->post_command('server_time', ['time'=>$now]);

					/** @todo server_time_timeout 没有?! (Xiaopei Li@2014-04-10) */
					$this->server_time_expire = $now + $this->server_time_timeout;
				}

				if ($now >= $this->monitoring_expire) {
					// 刷新is_monitoring_mtime
					$db = ORM_Model::db('equipment');
					$db->query('UPDATE `equipment` SET `is_monitoring`=1, `is_monitoring_mtime`=%d WHERE `id`=%d', $now, $agent->object->id);
					$this->monitoring_expire = $now + $this->monitoring_timeout;
				}

			}
		}
		Event::trigger('device_computer.keep_alive', $this);
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
				Event::trigger('device_computer.remote_command.'.$command, $this, $struct);
			}
		}

	}

	function command_unknown($command, $data) {
		return Event::trigger('device_computer.agent_command.'.$command, $this, $data);
	}

	function on_command_server_time($struct) {
		if (!$this->is_ready) return;
		$this->post_command('server_time', ['time'=>Date::time()]);
	}

	function on_command_connect($struct) {
		if ($this->is_ready) return;

		/*
		name: '客户端电脑名',
		message: '随机消息', //用于RSA签名验证
		 */

		$device = $struct->name;
		$control_address = "computer://$device";
		$this->log('控制器%s尝试连接', $control_address);

		$cards = [];
		
		$equipment = O('equipment', ['control_mode'=>'computer', 'control_address'=>$device]);

		/*
		//如下原始代码
		if (!$equipment->id) {
			$this->log('无法找到%s关联仪器', $control_address);
			throw new Device_Exception;
		}
		//如下代码为更新代码。为了与LIMSLogon同步升级。影响的版本为2.0.0.61～
		//如果客户端同步传过来了oldname的值，怎进行以下逻辑判断
		//如果name对应的仪器没有找到
		//如果提交了oldname
		//如果能找到oldname对应的仪器
		//仪器的control_address更新为name
		 */

		/** @todo 需确认 oldname 机制在 GLogon 中是否还在使用 (Xiaopei Li@2014-04-09) */

		if (!$equipment->id) {
			$this->log('无法找到%s关联仪器', $control_address);
			if (!isset($struct->oldname)) throw new Device_Exception;
			$old_device = $struct->oldname;
			$control_address = "computer://$old_device";
			$this->log('控制器%s尝试连接', $control_address);
			$equipment = O('equipment', ['control_mode'=>'computer', 'control_address'=>$old_device]);
			if (!$equipment->id) {
				$this->log('无法找到%s关联仪器', $control_address);
				throw new Device_Exception;
			}
			$equipment->control_address = $device;
			$equipment->save();
		}

		/*
		if ($equipment->status != EQ_Status_Model::IN_SERVICE) {
			$this->log('%s[%d]处于故障状态', $equipment->name, $equipment->id);
			throw new Device_Exception;
		}
		 */

		if ($struct->code) {

			$priv = @openssl_get_privatekey(Config::get('equipment.private_key'));
			if ($priv === FALSE || !@openssl_private_decrypt(@base64_decode($struct->code), $client_code, $priv)) {
				$this->log('%s[%d]OPENSSL解密签名失败', $equipment->name, $equipment->id);
				throw new Device_Exception;
			}

			$pub = @openssl_get_publickey($equipment->public_key);
			if ($pub === FALSE ||
				!@openssl_verify($client_code, @base64_decode($struct->signature), $pub, OPENSSL_ALGO_SHA1)) {
					$this->log('%s[%d]OPENSSL验证签名失败', $equipment->name, $equipment->id);
					throw new Device_Exception;
				}

			$this->server_code = Misc::random_password(8, 3);

			if (!@openssl_public_encrypt($this->server_code, $server_code, $pub)) {
				$this->log('%s[%d]OPENSSL加密签名失败', $equipment->name, $equipment->id);
				$this->server_code = NULL;
				throw new Device_Exception;
			}

			if (!@openssl_sign($client_code, $server_signature, $priv, OPENSSL_ALGO_SHA1)) {
				$this->log('%s[%d]OPENSSL生成签名失败', $equipment->name, $equipment->id);
				$this->server_code = NULL;
				throw new Device_Exception;
			}

			$this->client_code = $client_code;

			$this->add_agent(new Device_Agent($equipment, TRUE));
			$this->log('%s[%d]已连接', $equipment->name, $equipment->id);

			$this->post_command('connect', [
				'signature' => @base64_encode($server_signature),
				'code' => @base64_encode($server_code),
			]);

		}
		else {

			/** @todo 确认 "老版本协议" 是多老的 GLogon/LIMSLogon 用? 是否还需实现 (Xiaopei Li@2014-04-09) */
				
			//老版本协议 明文使用
			$message = $struct->message;
			$ph = @openssl_get_privatekey(Config::get('equipment.private_key'));
			if ($ph === FALSE ||
				!@openssl_sign($message, $digest, $ph, OPENSSL_ALGO_SHA1)) {
					$this->log('%s[%d]OPENSSL生成签名失败', $equipment->name, $equipment->id);
					throw new Device_Exception;
				}

			$this->signature = @base64_encode($digest);
			$this->message = Misc::random_password(12, 3);

			$this->add_agent(new Device_Agent($equipment, TRUE));
			$this->log('%s[%d]已连接', $equipment->name, $equipment->id);

			$this->post_command('connect', [
				'signature' => $this->signature,
				'message' => $this->message,
			]);
		}

		if ($struct->lang) {
			$this->log('SET LOCALE=%s', $struct->lang);
			Config::set('system.locale', $struct->lang);
			I18N::shutdown();
			I18N::setup();
		}

		Event::trigger('device_computer.on_ready', $this);
	}


	function on_command_uninstall($struct) {
		if ($this->is_ready) return;

		$device = $struct->name;
		$control_address = "computer://$device";
		$equipment = O('equipment', ['control_mode'=>'computer', 'control_address'=>$device]);
		if ($equipment->id) {
			$now = time();
			$current_user = Q("eq_record[equipment=$equipment][dtstart<$now][dtend=0] user:limit(1)")->current();
			if ($current_user->id) {
				$user_name = $current_user->name.'['.$current_user->id.']';
			}
			else {
				$user_name = '未知用户';
			}
		}
		if (!isset($struct->status)) {
			$this->log('%s请求卸载%s[%d]', $user_name, $equipment->id ? $equipment->name : $control_address, $equipment->id);
			if (!$equipment->cannot_uninstall_logon) {
				$this->post_command('uninstall', [
					'is_allowed'=>1,
				]);
			}
		}
		else {
			$this->log('%s卸载%s[%d]成功', $user_name, $equipment->id ? $equipment->name : $control_address, $equipment->id);
			//仪器客户端卸载成功之后，仪器的使用设置应该进行还原操作
			//至少，计算机名应该设置为空
			$equipment->control_address = '';
			$equipment->save();
		}
	}

	function on_command_install($struct) {
		if ($this->is_ready) return;

		$sn = $struct->access_code;
		$computer_name = $struct->computer_name;
		$os = $struct->os;
		$now = time();

		try {

			$locales = (array) Config::get('system.locales');
			if ($struct->lang && isset($locales[$struct->lang])) {
				$this->log('SET LOCALE=%s', $struct->lang);
				Config::set('system.locale', $struct->lang);
				I18N::shutdown();
				I18N::setup();
			}

			$equipment = O('equipment', ['control_mode'=>'computer', 'access_code'=>$sn]);

			if (!$equipment->id) {
				throw new EQDevice_Exception(I18N::T('equipments', '序列号验证失败!'));
			}

			$lifetime = (int)Config::get('equipment.access_code_lifetime');

			if ($lifetime !== 0 && $equipment->access_code_ctime + $lifetime < $now) {
				throw new EQDevice_Exception(I18N::T('equipments', '序列号已过期!'));
			}
			
			$res = openssl_pkey_new([
				'private_key_bits' => 2048,
				'private_key_type' => OPENSSL_KEYTYPE_RSA,
			]);
			openssl_pkey_export($res, $eq_private_key);

			$this->log('重新生成RSA私钥');

			$key_details = openssl_pkey_get_details($res);
			$eq_public_key = $key_details['key'];

			$raw_super_key = Config::get('equipment.super_key');
			@openssl_public_encrypt($raw_super_key, $super_key, $eq_public_key);
			$super_key = base64_encode($super_key);

			$equipment->control_address = $computer_name;
			$equipment->public_key = $eq_public_key;
			$equipment->access_code_ctime = 0;	// 设置序列号过期

			if(!$equipment->save()){
				throw new EQDevice_Exception(I18N::T('equipments', '设备信息修改失败, 请联系技术支持!'));
			}

			$ph = openssl_get_privatekey(Config::get('equipment.private_key'));
			$details = openssl_pkey_get_details($ph);
			$public_key = $details['key'];


			$update = Updater::available_update('LIMSLogon', $os);
			$this->post_command('install', [
				'private_rsa'=>$eq_private_key,
				'public_rsa'=>$public_key,
				'super_key'=>$super_key,
				'update_uri'=>$update->update_uri,
				'uninstall_uri'=>$update->uninstall_uri,
				'public_key_token'=>$update->public_key_token,
				'autorun_uri'=>$update->autorun_uri,
			]);

			$this->log('发送安装请求');
			//throw new Device_Exception;
		}
		catch(EQDevice_Exception $e){
			$this->post_command('message', [
				'text' => $e->getMessage(),
			]);
		}
	}

	function on_command_confirm($struct) {
		if ($this->is_ready) return;

		/*
		ver: '客户端版本',
		os: '客户端操作系统',
		signature: '客户端签名',
		 */

		$now = time();
		$agent = $this->agent(0);
		$equipment = ORM_Model::refetch($agent->object);
		if (!$equipment->id) {
			throw new Device_Exception('无法识别的仪器');
		}

		/** @todo 当前协议是否会走到这儿? (Xiaopei Li@2014-04-10)*/
		if (!$this->client_code) {
			$digest = @base64_decode($struct->signature);
			$ph = @openssl_get_publickey($equipment->public_key);
			if ($ph === FALSE ||
				!@openssl_verify($this->message, $digest, $ph, OPENSSL_ALGO_SHA1)) {
					$this->log('%s[%d]OPENSSL验证签名失败', $equipment->name, $equipment->id);
					throw new Device_Exception;
				}
		}
		
		$this->is_ready = TRUE;


		//1.0.0.1
		$this->version = $struct->version;
		//Unix, Win32NT, Win32s, Win32Windows, WinCE
		$this->os = $struct->os;

        $this->pid = $struct->pid;

        $this->log('客户端版本: %s %s %s', $this->os, $this->version, $this->pid ? : '');

		/** @todo update 现在没有用 (Xiaopei Li@2014-04-10) */
		$update = Updater::available_update('LIMSLogon', $this->os, $this->version);
		if ($update) {
			$this->log('服务器最新版本:%s %s', $this->os, $update->version);
			/* .net framework 2.0的clickonce updatelocation为只读，无法修改
			$this->post_command('upgrade', array(
				'download_url' => $update->download_url,
				'execute_url' => $update->execute_url,
			));
			 */

			$this->post_command('upgrade', [
				'update_uri' => $update->update_uri,
			]);

			$this->post_command('update', [
				'update_uri' => $update->update_uri,
				'update_md5' => $update->update_md5,
			]);

		}

		/*
		if ($equipment->access_code) {
			$equipment->access_code = NULL;
			$equipment->access_code_ctime = NULL;
			$equipment->save();
		}*/

		/** 保存 glogon 版本信息 */
		//一旦客户端通过认证，先清除插件属性
		$equipment->device = [
			'os' => $this->os,
			'version' => $this->version,
			'plugins' => NULL,
		];
		$equipment->save();

		/** 同步 glogon 与服务器间状态 */
		//一旦客户端通过认证，同步客户端与服务器端的状态：以客户端为标准修改服务器端的状态。
		$token = Auth::normalize($struct->user);
		$user = O('user', ['token'=>$token]);

		if (!$user->id && $struct->user != 'administrator') {
            //当前无用户, 并且不为administrator
            //post_command status
            //仪器未使用
			if ($equipment->is_using) {
				$equipment->is_using = FALSE;
				$equipment->save();
			}

            //等待offline_record后再获取status
			//$this->post_command('status');
		}
		else {
            //仪器使用中
			if (!$equipment->is_using) {
				$equipment->is_using = TRUE;
				$equipment->save();
			}

            //如果仪器无使用中使用记录
            //但是当前仪器在使用
            //进行version比对, 如果超过2.2版本
            //会有离线记录进行更新, 不予自动创建
            //反之, 创建使用中使用记录
            if (version_compare($this->version, '2.2') < 0 && ! Q("eq_record[equipment={$equipment}][dtend=0]")->total_count()) {

                $record = O('eq_record');
                $record->dtstart = $now;
                $record->dtend = 0;
                $record->user = $user;
                $record->equipment = $equipment;
                $record->save();
            }
		}

		// 发送backends信息更新login窗口
		$backends = [];
		$auth_backends = (array) Config::get('auth.backends');
		$num = 1;
		foreach ($auth_backends as $k=>$o) {
			$backends[$k] = $num.'. '.I18N::T('people', $o['title']);
			$num++;
		}

		$this->post_command('backends', [
			'backends' => $backends,
			'default_backend' => Config::get('auth.default_backend'),
		]);

		// 发送plugins命令 确定目前客户端的插件类别
		$this->post_command('plugins');

		$cards = [];
		$free_access_cards = $equipment->get_free_access_cards();
		foreach($free_access_cards as $card_no => $user) {
			if (isset($_SERVER['CARD_BYTE_SWAP'])) {
				$card_no = (string) Misc::byte_swap32($card_no);
			}

			$cards[$card_no] = (string) $card_no;
		}

		$this->post_command('cards', [
			'cards' => array_values($cards),
		]);
	}

	function on_command_plugins($struct) {
		if (!$this->is_ready) return;

		$agent = $this->agent(0);
		$equipment = ORM_Model::refetch($agent->object);

		$device = (array) $equipment->device;
		$device['plugins']  = (array) $struct->plugins;
		$equipment->device = $device;
		$equipment->save();

		$this->log('客户端插件: %s', @json_encode($equipment->device['plugins']));

		Event::trigger('device_computer.on_plugin_update', $this);
	}

	function on_command_login($struct) {
		if (!$this->is_ready) return;

		$agent = $this->agent(0);
		$equipment = ORM_Model::refetch($agent->object);
		/*
		user:
		password:
		card_no:
		 */

		try {
			if ($struct->card_no || $struct->card) {
				$card_no = (string) (($struct->card_no ?: $struct->card) + 0);
				$card_no_s = (string)(($card_no + 0) & 0xffffff);
				$user = Q("user[card_no=$card_no|card_no_s=$card_no_s]:limit(1)")->current();
				if (!$user->id) {
					$this->log('卡号%s尝试失败', $card_no);
					throw new EQDevice_Exception(I18N::T('equipments', '该卡号找不到相应的用户'));
				}

				$this->log('卡号:%s => 用户:%s[%d]', $card_no, $user->name, $user->id);
			}
			else {
				$token = Auth::normalize($struct->user);
				$user = O('user', ['token'=>$token]);
				if (!$user->id) {
                    $this->log('%s尝试登录, 但系统不存在该用户', $token);
                    list($token, $backend) = Auth::parse_token($token);
                    $backends = Config::get('auth.backends');
                    $backend_title = $backends[$backend]['title'] ? I18N::T('people', $backends[$backend]['title']) : $backend;
                    throw new EQDevice_Exception(I18N::T('equipments', '登录名%token找不到相应的用户', ['%token'=>implode('@', [$token, $backend_title])]));
				}

				$digest = @base64_decode($struct->password);
				$ph = @openssl_get_privatekey(Config::get('equipment.private_key'));
				$ret = @openssl_private_decrypt($digest, $password, $ph);

				if (!$ret) {
					$this->log('用户%s[%d]密码无法进行解码', $user->name, $user->id);
					throw new EQDevice_Exception(I18N::T('equipments', '密码验证失败, 请重新输入'));
				}

				$auth = new Auth($token);
				if (!$auth->verify($password)) {
					$this->log('用户%s[%d]密码验证失败', $user->name, $user->id);
					throw new EQDevice_Exception(I18N::T('equipments', '密码验证失败, 请重新输入'));
				}

			}


			Cache::L('ME', $user);	//当前用户切换为该用户

            //进行Event
            Event::trigger('equipments.glogon.login', $struct, $user);

			//要求打开仪器
			//检测用户是否可以操作仪器
			if (!$user->is_allowed_to('管理使用', $equipment) && $equipment->cannot_access($user, Date::time())) {
				$this->log('用户%s[%d]无权打开%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);

                $this->log('无权打开原因:');
                foreach(Lab::messages(Lab::MESSAGE_ERROR) as $message) {
                    $this->log($message);
                }

                $messages = Lab::messages(Lab::MESSAGE_ERROR);
                if (count($messages)) {
                	//清空Lab::$messages,得到正确的错误提示
                	Lab::$messages[Lab::MESSAGE_ERROR] = [];
                    throw new EQDevice_Exception(join(' ', array_map(function($msg) {
                        return I18N::T('equipments', $msg);
                    }, $messages)));
                }
                else {
                    throw new EQDevice_Exception(I18N::T('equipments', '您无权使用%equipment', ['%equipment'=>$equipment->name]));
                }
			}

            $data = Event::trigger('equipments.glogon.login.return', $struct, $user);
            if ($data) {
                $this->command_switch_to($data);
            }
            else {
                $this->command_switch_to(['user'=>$user, 'power_on'=>TRUE, 'agent'=>$agent]);
            }

		}
		catch(EQDevice_Exception $e) {
			$this->post_command('message', [
				'text' => $e->getMessage(),
			]);
		}
	}

	function on_command_logout($struct) {
		if (!$this->is_ready) return;

		$agent = $this->agent(0);
		$equipment = ORM_Model::refetch($agent->object);

		/*
		user:
		 */

		try {

			$token = Auth::normalize($struct->user);
			$user = O('user', ['token'=>$token]);

			if (!$user->id) {	
                //直接可进行关闭
				$this->command_switch_to(['user'=>$user, 'power_on'=>FALSE, 'agent'=>$agent, 'feedback'=>$feedback]);
			}
            else {

                Cache::L('ME', $user);	//当前用户切换为该用户

                $now = time();

                //要求关闭仪器
                if (!$user->is_allowed_to('管理使用', $equipment)) {
                    $record = Q("eq_record[dtstart<=$now][dtend=0][equipment=$equipment][user=$user]:sort(dtstart D):limit(1)")->current();
                    if (!$record->id) {
                        // 没使用记录...  检查是否因为没有任何正在使用的记录
                        if (Q("eq_record[dtstart<=$now][dtend=0][equipment=$equipment]")->total_count() > 0) {
                            $this->log('用户%s[%d]无权关闭%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);
                            throw new EQDevice_Exception(I18N::T('equipments', '您无权关闭%equipment', ['%equipment'=>$equipment->name]));
                        }
                    }
                }

                $feedback = [
                    'status' => $struct->status,
                    'feedback' => $struct->feedback,
                ];

                if (isset($struct->samples)) {
                    $feedback['samples'] = $struct->samples;
                }

                if (class_exists('Lab_Project_Model')) {

                    $project_id = $struct->project;

                    if ($project_id == self::FAKE_PROJECT_ID && $this->project_id) $project_id = $this->project_id;

                    $project = O('lab_project', $project_id);
                    $count = Q("lab_project[lab={$user->lab}]")->total_count();
                    $must_connect_project = Config::get('eq_record.must_connect_lab_project');

                    if ($must_connect_project && $count && !$project->id) {
                        throw new EQDevice_Exception(I18N::T('equipments', '请选择项目后再进行提交!'));
                    }

                    $feedback['project'] = $project;
                    $this->project_id = NULL;

                    if ($must_connect_project && !$count) {
                        $feedback = NULL;
                    }

                }

                //如果samples为必填
                if (Config::get('eq_record.glogon_require_samples') && ! $struct->samples) {
                    throw new EQDevice_Exception(I18N::T('equipments', '样品数填写有误, 请填写大于0的整数!'));
                }

                $this->command_switch_to(['user'=>$user, 'power_on'=>FALSE, 'agent'=>$agent, 'feedback'=>$feedback]);
            }

		}
		catch(EQDevice_Exception $e) {
			$this->post_command('message', [
				'text' => $e->getMessage(),
			]);
			//$this->post_command('logout');
		}

	}

	function on_command_status($struct) {
		if (!$this->is_ready) return;
		$agent = $this->agent(0);
		$equipment = ORM_Model::refetch($agent->object);
        $now = Date::time();

		//控制器状态

		$is_using = FALSE;

		if ($struct->user) {

			$token = Auth::normalize($struct->user);
			$user = O('user', ['token' => $token]);

            //获取最后一个使用记录
            $last_record = Q("eq_record[equipment={$equipment}][dtstart<$now]:sort(dtstart DESC, id DESC):limit(1)")->current();

            //如果使用中，比对用户
            if (!$last_record->dtend) {
                //如果不统一，结束record，logout
                //如果为代开, 也算正常使用
                if ($user->id == $last_record->user->id || $user->id == $last_record->agent->id) {
                	$is_using = TRUE;
                }
               	else {
                    //最后的使用中的使用记录的闭合
                    $last_record->dtend = $now;
                    $last_record->save();

                    //glogon重新登录
                    $this->post_command('logout');
                }
            }
            else {
            	//非使用中
            	/* 解决南开负载高的问题，暂不处理 */
                //$this->post_command('status');
            }
		}
        else {
            //没使用 关闭最后一条使用记录
            $record =  Q("eq_record[dtend=0][equipment={$equipment}]:sort(dtstart D):limit(1)")->current();

            if ($record->id) {
                $record->dtend = $now - 1;
                $record->save();
            }
        }

		if ($equipment->is_using != $is_using) {
			$equipment->is_using = $is_using;
			$equipment->save();
			$struct->return_value = TRUE;
		}
	}

	private function hash_password($password) {
		return base64_encode(md5($password, TRUE));
	}

	function on_command_password($struct) {

		if (!$this->is_ready) return;
		$agent = $this->agent(0);
		$equipment = ORM_Model::refetch($agent->object);

		//如果没有离线密码，链接时自动生成
		$now = time();
		if(!$equipment->offline_password) {
			$equipment->offline_password = Misc::random_password(6, 1);
			$equipment->save();

			//发送初始化离线密码
			Equipments::send_offline_password_init($equipment);
		}

		$hash = $this->hash_password($equipment->offline_password);
		if ($hash != $struct->password_hash) {
			$this->log('刷新 %s[%d] (%s) 的离线管理密码', $equipment->name, $equipment->id, $equipment->control_address);
			$this->post_command('password', [
				'hash' => $hash,
			]);
		}

	}

	function on_command_backends($struct) {
		if (!$this->is_ready) return;

		$backends = [];
		$auth_backends = (array) Config::get('auth.backends');
		foreach ($auth_backends as $k=>$o) {
			$backends[$k] = I18N::T('people', $o['title']);
		}

		$this->post_command('backends', [
			'backends' => $backends,
			'default_backend' => Config::get('auth.default_backend'),
		]);

	}

	function on_command_projects($struct) {
		if (!$this->is_ready) return;

		//控制器状态
		$projects = [];

		try {
			if (!$struct->user) throw new EQDevice_Exception;

			$token = Auth::normalize($struct->user);
			$user = O('user', ['token' => $token]);

			if (!$user->id) throw new EQDevice_Exception;

			$lab = $user->lab;
			if (!$lab->id) throw new EQDevice_Exception;

			if (Module::is_installed('labs')) {
				$projects = Q("lab_project[lab={$lab}]:sort(id A)")->to_assoc('id', 'name');
				$must_connect_project = Config::get('eq_record.must_connect_lab_project');
				$total_count = count($projects);

				
                //只要有项目, 就可进行反馈
                if ($total_count) {
					/*
					 *	在必须关联项目的情况下，如果有项目，则需要增加必须让用户选择项目的选项
					 *	如果不存在项目，则需要发送消息提醒，实验室无项目，需要联系实验室负责人进行添加。
					 */
					$projects[0] = I18N::T('equipments', '请选择此次仪器服务的项目');
					ksort($projects);
				}


                if (Module::is_installed('eq_reserv')) {
                    //获取使用中的使用记录对应的预约记录所关联的project
                    $agent = $this->agent(0);
                    $equipment = ORM_Model::refetch($agent->object);

                    //使用中的使用记录
                    $record = Q("eq_record[equipment={$equipment}][dtend=0]:limit(1)")->current();

                    $dtstart = $record->dtstart;

                    //对应预约记录
                    $reserv = Q("eq_reserv[user={$user}][equipment={$equipment}][dtstart~dtend={$dtstart}]:limit(1)")->current();

                    $project = $reserv->project;
                    $this->project_id = $pid = $project->id;

                    //进行project的id获取
                    if ($pid) {

                        unset($projects[$pid]);
                        //相当于array_unshift增加key设置
                        //由于GLogon的机制, 导致需要传递self::FAKE_PROJECT_ID虚假id给Glogon
                        $projects = [self::FAKE_PROJECT_ID => $project->name] + $projects;
                    }
                }

				if ($struct->locale && !in_array($struct->locale, ['zh-CN', 'zh_CN'])) {
					$projects = array_map(function($v){
						return PinYin::code($v);
					}, $projects);
				}

                //从0开始
                $num = 0;
				if ($total_count) {
                    foreach ($projects as $k => $p) {
                        $projects[$k] = $num.' - '.$p;
                        $num++;
                    }
				}
				else {
					$message = I18N::T('equipments', '您实验室尚无项目, 请联系负责人添加项目!');
				}
			}
			else {
				$projects = [];
			}

            //强制转换为object, 防止出现key值丢失
            $projects = (object) $projects;
		}
		catch (EQDevice_Exception $e) {

		}
		$this->post_command('message', [
			'text' => $message
		]);
		$this->post_command('projects', [
			'projects' => $projects,
		]);

	}

    function on_command_samples($struct) {
        if (!$this->is_ready) return;

        $this->post_command('samples', [
                    'require'=> Config::get('eq_record.glogon_require_samples'),
                    ]);
    }

	function on_command_file($struct) {
		//上传文件
		if (!Module::is_installed('nfs_share')) {
			$this->post_command('message', ['text'  => I18N::T('equipments', '不支持上传文件')]);
		}

		$me = $struct->user;
	}

	function on_command_upload($struct) {
		if (!$this->is_ready) return;
		$agent = $this->agent(0);
		$equipment = ORM_Model::refetch($agent->object);

		$file = new Device_File($struct->hash);

		if ($file->is_empty) {
			$file->start_upload($struct->size, $struct->md5);
		}
		elseif (!$file->check_upload($struct->size, $struct->md5)) {
			//传输错误
			$this->post_command('upload_error', ['hash'=>$hash]);
			return;
		}

		$chunk = $struct->chunk;
		if ($chunk) {
			$offset = $chunk->offset;
			$md5 = $chunk->md5;
			$data = @base64_decode($chunk->data);
			if (!$file->write($offset, $data, $md5)) {
				//传输错误
				$this->post_command('upload_error', ['hash'=>$hash, 'offset'=>$offset]);
				return;
			}
		}

		if ($file->is_uploaded) {
			$file->finish();
		}

	}

	function on_command_offline_record($struct) {
		if (!$this->is_ready) return;
		$agent = $this->agent(0);

		$equipment = ORM_Model::refetch($agent->object);

		if ($struct->record) {
			$offline_record = (array)$struct->record;

			$card_no = (string) $offline_record['card'];
			if ($card_no) {
				$card_no_s = (string)(($card_no + 0) & 0xffffff);
				$user = Q("user[card_no=$card_no|card_no_s=$card_no_s]:limit(1)")->current();
				if (!$user->id) {
					$this->log('[更新离线记录] 卡号 %s 找不到相应的用户', $card_no);
					$user = O('user');
				}
				else {
					$this->log('[更新离线记录] 卡号:%s => 用户:%s[%d]', $card_no, $user->name, $user->id);
				}
			}
			else {
				$token = Auth::normalize($offline_record['token']);
				$user = O('user', ['token'=>$token]);
				if (!$user->id) {
					$this->log('[更新离线记录] 登录名%s找不到相应的用户', $token);
				}
			}

			$time = (int) $offline_record['time'];

			switch($offline_record['status']) {
			case 'login':
				// 关闭该仪器所有因意外未关闭的record
				foreach (Q("eq_record[dtend=0][dtstart<=$time][equipment=$equipment]") as $record) {
					if ($record->dtstart==$time) {
						$record->delete();
						continue;
					}

					$record->dtend = $time - 1;
					$record->status = EQ_Record_Model::FEEDBACK_NORMAL;
					$record->save();
				}

				//刷新对象
				if ($user->id) {
					$this->log('[更新离线记录] %s[%d] 在 %s 登入仪器 %s[%d](%s)', $user->name, $user->id, Date::format($time), $equipment->name, $equipment->id, $equipment->location );
				}
				else {
					$this->log('[更新离线记录] 未知卡号 %s 在 %s 登入仪器 %s[%d](%s)', $card_no ?: '--', Date::format($time), $equipment->name, $equipment->id, $equipment->location );
				}

				$record = O('eq_record');
				$record->dtstart = $time;
				$record->dtend = 0;
				$record->user = $user;
				$record->equipment = $equipment;
				$record->save();
				$equipment->is_using = TRUE;
				$equipment->save();
				break;
			case 'logout':
				if (!$user->id) {
					//open last open record
					$record = Q("eq_record[dtstart<=$time][dtend=0][equipment=$equipment]:sort(dtstart D):limit(1)")->current();
					if ($record->id) {
						$user = $record->user;
					}
				}
				if ($user->id) {
					$record =  Q("eq_record[dtstart<$time][dtend=0][equipment={$equipment}]:sort(dtstart D):limit(1)")->current();
					if ($record->id) {
						$this->log('[更新离线记录] %s[%d] 在 %s 登出仪器 %s[%d](%s)', $user->name, $user->id, Date::format($time), $equipment->name, $equipment->id, $equipment->location );
						$record->dtend = $time;
						$feedback = @json_decode($offline_record['feedback'], TRUE);
						if (is_array($feedback)) {
							$this->log(json_encode($feedback));
                            $feedback_status = self::$feedback_status_map;

                            if (isset($feedback_status[($feedback['status'])])) {
                                $feedback['status'] = $feedback_status[($feedback['status'])];
                            }
							$record->status = (int)$feedback['status'];
							$record->feedback = $feedback['feedback'];
							$record->samples = max(1, (int)$feedback['samples']);
							if ($feedback['project']) $record->project = $feedback['project'];
						}

						//负责人关闭设备时,当这条记录是负责人自己的,那么状态默认是正常，如果是代开,status不设置
						if ($record->status == EQ_Record_Model::FEEDBACK_NOTHING && $record->user->is_allowed_to('管理使用', $equipment)) {
							$record->status = EQ_Record_Model::FEEDBACK_NORMAL;
						}

						$record->save();
					}
					else {
						$this->log('[更新离线记录] %s[%d] 在 %s 登出仪器 %s[%d](%s) 但没找到相应记录', $user->name, $user->id, Date::format($time), $equipment->name, $equipment->id, $equipment->location );
					}
				}
				else {
					//离线使用后，恢复网络，关闭使用记录
					if($record->id){
						$record->dtend = $time;
						$record->save();
					}
					$this->log('[更新离线记录] 未知卡号 %s 在 %s 登出仪器', $card_no ?: '--', Date::format($time));
				}
				$equipment->is_using = FALSE;
				$equipment->save();
				break;
			case 'error':
				$this->log('[更新离线记录] %s[%d] %s 在 %s 尝试 %s 登录失败', $user->name, $user->id, $card_no ?: '--', Date::format($time), $card_no ? '刷卡' : '密码');
				break;
			}

			$this->post_command('confirm_record', ['record_id'=>$offline_record['id']]);
		}
		elseif (!$equipment->is_using) {
			// 关闭该仪器所有因意外未关闭的record
			$now = Date::time();
			foreach (Q("eq_record[dtend=0][dtstart<=$now][equipment=$equipment]") as $record) {

				if ($record->dtstart==$now) {
					$record->delete();
					continue;
				}

				$record->dtend = $now - 1;
				$record->status = EQ_Record_Model::FEEDBACK_NORMAL;
				$record->save();
			}

			$this->post_command('status');
		}
        else {
            $this->post_command('status');
        }

	}

	function command_upgrade() {
		$this->post_command('upgrade');
	}

	function command_switch_to ($data) {
		$user = $data['user'];
		$power_on = $data['power_on'];
		$agent = $data['agent'];
		$feedback = $data['feedback'];

		//刷新对象
		$equipment = ORM_Model::refetch($agent->object);


		$now = Date::time();

		$this->log('%s[%d] 尝试切换%s[%d] (%s) 的状态 => %s', $user->name, $user->id, $equipment->name, $equipment->id, $equipment->location , $power_on ? '打开':'关闭');

		$equipment->is_using = $power_on;
		$equipment->save();
		if ($power_on) {
			// 关闭该仪器所有因意外未关闭的record
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
            $record->is_computer_device = TRUE;
			$record->dtstart = $now;
			$record->dtend = 0;
			$record->user = $user;
			$record->equipment = $equipment;
            if ($record->save()) {
                Event::trigger('equipments.glogon.switch_to.record_saved', $record, $data);
            }

			$name = $user->name;
			if ($user->lab->id) {
				$name .= ' ('.$user->lab->name.')';
			}

            if ($data['locale'] && !in_array($data['locale'], ['zh-CN', 'zh_CN'])) {
                $name = PinYin::code($name);
            }

			$data = [
				'user' => $user->token,
				'name' => $name,
				'dtstart' => $now,
			];

			// tranfer file server config for window file server if it set
			if(isset($config['fsip']) && isset($config['fsfolder'])) {
				$data['fsip'] = $config['fsip'];
				$data['fsfolder'] = $config['fsfolder'];
			}

			// ugly hack to add eq_reserv info to device_computer
			if (Module::is_installed('eq_reserv')) {
				if ($record->reserv->id) {
					$data['reserv'] = [
						'dtstart' => $record->reserv->dtstart,
						'dtend' => $record->reserv->dtend
					];
				}
			}

			$this->post_command('login', $data);

		}
		else {
			$this->post_command('logout');

			$record =  Q("eq_record[dtstart<{$now}][dtend=0][equipment={$equipment}]:sort(dtstart D):limit(1)")->current();
			if ($record->id) {
				$record->dtend = $now;
				//负责人关闭设备时,当这条记录是负责人自己的,那么状态默认是正常，如果是代开,status不设置
				if ($feedback) {
                    $feedback_status = self::$feedback_status_map;

                    if (isset($feedback_status[($feedback['status'])])) {
                        $feedback['status'] = $feedback_status[($feedback['status'])];
                    }
					$record->status = (int)$feedback['status'];
					$record->feedback = $feedback['feedback'];
					if (isset($feedback['samples'])) {
						$record->samples = max(0, (int)$feedback['samples']);
					}
					if ($feedback['project']) $record->project = $feedback['project'];
				}
				elseif ($record->user->is_allowed_to('管理使用', $equipment)) {
					$record->status = EQ_Record_Model::FEEDBACK_NORMAL;
				}
				$record->save();
			}

		}

		return TRUE;
	}

	function support_plugin($plugin_name) {
		if (!$this->is_ready) return FALSE;

		$agent = $this->agent(0);
		$equipment = ORM_Model::refetch($agent->object);
		return $equipment->support_device_plugin($plugin_name);
	}

	function command_password() {
		$this->on_command_password((object)['password_hash'=>NULL]);
	}

}
