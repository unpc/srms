<?php

class EQDevice_Exception extends Exception {}

class API_Glogon {
    /** install 或 connect 中会传 lang */

    static $feedback_status_map = [
        1 => EQ_Record_Model::FEEDBACK_NORMAL,
        2 => EQ_Record_Model::FEEDBACK_PROBLEM,
        0 => EQ_Record_Model::FEEDBACK_NOTHING
        ];

    public static $errors = [
        1001 => '请求来源非法!',
        1002 => '找不到对应的仪器!',
        1003 => 'OPENSSL解密签名失败',
        1004 => 'OPENSSL验证签名失败',
        1005 => 'OPENSSL加密签名失败',
        1006 => 'OPENSSL生成签名失败',
        1007 => '仪器未建立初始连接',
        /**
           找不到相应的用户!
           用户验证失败!
           用户无权打开仪器!
           ...
        **/
        ];

    //假的lab_project_id
    const FAKE_PROJECT_ID = -1;

    private function log() {
        $args = func_get_args();
        if ($args) {
            $format = array_shift($args);
            $str = vsprintf($format, $args);
            Log::add(strtr('%name %str', [
                        '%name' => '[GLogon API]',
                        '%str' => $str,
            ]), 'devices');
        }
    }

    private function hash_password($password) {
        return base64_encode(md5($password, TRUE));
    }

    private function _ready() {

        $whitelist = Config::get('api.white_list_glogon', []);
        $whitelist[] = $_SERVER['SERVER_ADDR'];

        if (in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
            return;
        }

        // whitelist 支持ip段配置 形如 192.168.*.*
        foreach ($whitelist as $white) {
            if (strpos($white, '*')) {
                $reg = str_replace('*', '(25[0-5]|2[0-4][0-9]|[0-1]?[0-9]?[0-9])', $white);
                if (preg_match('/^'.$reg.'$/', $_SERVER['REMOTE_ADDR'])) {
                    return;
                }
            }
        }
        throw new API_Exception(self::$errors[1001], 1001);
    }

    private function _get_equipment($device, $lang = NULL) {

        $equipment = O('equipment', [
                           'control_mode'    => 'computer',
                           'control_address' => $device
                           ]);

        if (!$equipment->id) {
            throw new API_Exception(self::$errors[1002], 1002);
        }

        /**
           如果调用 _get_equipment 时设置了 lang, 则用 lang, 否则
           若 equipment 中设置过 device_lang , 就用 device_lang

           (Xiaopei Li@2014-04-16)
        */
        if (!$lang && $equipment->device['lang']) {
            $lang = $equipment->device['lang'];
        }

        if ($lang) {
            Config::set('system.locale', $equipment->device['lang']);
            I18N::shutdown();
            I18N::setup();
        }

        return $equipment;
    }

    function password($device, $struct) {

        $this->_ready();

        $equipment = $this->_get_equipment($device);
        $struct = (object) $struct;

        // 如果没有离线密码，链接时自动生成
        if(!$equipment->offline_password) {
            // 中南林业大学固定密码
            $equipment->offline_password = Config::get('equipment.offline_password') ?: Misc::random_password(6, 1);
            $equipment->save();

            // 对管理员发送邮件/通知, 告知初始化离线密码
            Equipments::send_offline_password_init($equipment);
        }

        $hash = $this->hash_password($equipment->offline_password);
        if ($hash != $struct->password_hash) {
            $this->log('刷新 %s[%d] (%s) 的离线管理密码', $equipment->name, $equipment->id, $equipment->control_address);

            $ret['password'] = [
                'hash' => $hash,
                ];
        }

        return $ret;
    }

    function projects($device, $struct) {
        /*
          { command: 'projects',
          locale: 'zh_CN',
          user: 'xiaopei.li|database' }
        */


        $this->_ready();

        $equipment = $this->_get_equipment($device);

        $ret = [];

        /** @todo */
        $struct = (object) $struct;

        //控制器状态
        $projects = [];

        try {
            // if (!$struct->user) throw new EQDevice_Exception;
            if (!$struct->user) throw new EQDevice_Exception('no user token');

            $token = Auth::normalize($struct->user);
            $user = O('user', ['token' => $token]);

            // if (!$user->id) throw new EQDevice_Exception;
            if (!$user->id) throw new EQDevice_Exception('no user');

            // if (!$lab->id) throw new EQDevice_Exception;
            if (!Q("$user lab")->total_count()) throw new EQDevice_Exception('no lab');


            if (Module::is_installed('labs')) {
                $lab = Q("$user lab")->current();
                $status = Lab_Project_Model::STATUS_ACTIVED;
                $projects = Q("lab_project[status={$status}][lab=$lab]:sort(id A)")->to_assoc('id', 'name');

                Event::trigger('equipments.glogon.projects', $projects);

                $must_connect_project = Config::get('eq_record.must_connect_lab_project');
                $total_count = count($projects);

                if ($total_count) {
                    /*
                     *  在必须关联项目的情况下，如果有项目，则需要增加必须让用户选择项目的选项
                     *  如果不存在项目，则需要发送消息提醒，实验室无项目，需要联系实验室负责人进行添加。
                     */
                    $projects[0] = I18N::T('equipments', '请选择此次仪器服务的项目');
                    ksort($projects);
                }

                if (Module::is_installed('eq_reserv')) {
                    //获取使用中的使用记录对应的预约记录所关联的project

                    //使用中的使用记录
                    $record = Q("eq_record[equipment={$equipment}][dtend=0]:limit(1)")->current();

                    $dtstart = $record->dtstart;

                    //对应预约记录
                    $reserv = Q("eq_reserv[user={$user}][equipment={$equipment}][dtstart~dtend={$dtstart}]:limit(1)")->current();

                    $project = $reserv->project;
                    $pid = $project->id;

                    //进行project的id获取
                    if ($pid) {

                        unset($projects[$pid]);
                        //相当于array_unshift增加key设置
                        $projects = [self::FAKE_PROJECT_ID => $project->name] + $projects;
                    }
                }

                if ($struct->locale && !in_array($struct->locale, ['zh-CN', 'zh_CN'])) {
                    $projects = array_map(function($v){
                            return PinYin::code($v);
                        }, $projects);
                }

                $num = 0;
                if ($total_count) foreach ($projects as $k => $p) {
                        $projects[$k] = $num.' - '.$p;
                        $num++;
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
            //catch (Exception $e) {
        }


        if ($message) {
            $ret['message'] = ['text' => $message];
        }

        if ($projects) {
            $ret['projects'] = ['projects' => $projects];
        }

        return $ret;
    }

    function offline_record($device, $struct) {
        $this->_ready();

        $equipment = $this->_get_equipment($device);
        $struct = (object) $struct;
        
        $ret = [];
        $now = time();

        if ($struct->record) {
            $offline_record = (array)$struct->record;
            
            $card_no = (string) $offline_record['card'];
            if ($card_no) {
                $user = Event::trigger('get_user_from_sec_card', $card_no) ? : O('user', ['card_no' => $card_no]);

                if (!$user->id) {
                    $card_no_s = (string)(($card_no + 0) & 0xffffff);
                    $user = Event::trigger('get_user_from_sec_card_s', $card_no_s) ? : O('user', ['card_no_s' => $card_no_s]);
                }

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
                $record->is_computer_device = TRUE;
                $record->dtstart = $time;
                $record->dtend = 0;
                $record->user = $user;
                $record->equipment = $equipment;
                $record->samples = Config::get('eq_record.must_samples') ? 0 : Config::get('eq_record.record_default_samples');

                if ($record->save()) {
                    Event::trigger('equipments.glogon.offline.login.record_saved', $record, $struct);
                }

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
                        $now = Date::time();
                        $record->dtend = min($time, $now);
                        if ($offline_record['extra']) {
                            $feedback = @json_decode($offline_record['extra'], true);
                        }
                        else {
                            $feedback = @json_decode($offline_record['feedback'], TRUE);
                        }
                        if (is_array($feedback)) {
                            $this->log(json_encode($feedback));


                            $feedback_status = self::$feedback_status_map;

                            if (isset($feedback_status[($feedback['status'])])) {
                                $feedback['status'] = $feedback_status[($feedback['status'])];
                            }
                            $record->status = (int)$feedback['status'];
                            $record->feedback = $feedback['feedback'];
                            if ($record->dtend == $now) {
                                $record->feedback .= "\n客户端电脑时间有误, 自动矫正结束时间";
                            }
                            if (isset($feedback['samples'])) {
                                $record->samples = max(Config::get('eq_record.record_default_samples'), (int)$feedback['samples']);
                            }
                            
                            if ($feedback['project']) $record->project = O('lab_project', $feedback['project']);
                        }



                        //负责人关闭设备时,当这条记录是负责人自己的,那么状态默认是正常，如果是代开,status不设置
                        if ($record->status == EQ_Record_Model::FEEDBACK_NOTHING && $record->user->is_allowed_to('管理使用', $equipment)) {
                            $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
                        }

                        if ($record->save()) {
                            Event::trigger('equipments.glogon.offline.logout.record_saved', $record, $struct);
                        }

                    }
                    else {
                        $this->log('[更新离线记录] %s[%d] 在 %s 登出仪器 %s[%d](%s) 但没找到相应记录', $user->name, $user->id, Date::format($time), $equipment->name, $equipment->id, $equipment->location );
                    }


                }
                else {
                    //离线使用后，恢复网络，关闭使用记录
                    if($record->id){

                        $r = $struct->record;

                        if ($r['extra']) {
                            $extra = @json_decode($r['extra'], TRUE);
                            $status = $extra['status'];
                            $record->samples = (int) $extra['samples'];
                            $record->feedback = $extra['feedback'];
                        }
                        else {
                            $status = $r['status'];
                        }

                        $record->status = $status;
                        $record->dtend = min($time, $now);
                        if ($record->save()) {
                            Event::trigger('equipments.glogon.offline.logout.record_saved', $record, $struct);
                        }

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


            // $this->post_command('confirm_record', array('record_id'=>$offline_record['id']));
            $ret['confirm_record'] = ['record_id'=>$offline_record['id']];
        }

        return $ret;
    }

    function login($device, $struct) {
        $this->_ready();

        $equipment = $this->_get_equipment($device);
        $struct = (object) $struct;
        $ret = new ArrayIterator();

        try {
            if ($struct->card_no || $struct->card) {
                $card_no = (string) (($struct->card_no ?: $struct->card) + 0);
                if (strlen($card_no) < 6) {
                    $this->log('卡号%s过于简单', $card_no);
                    throw new EQDevice_Exception(I18N::T('equipments', '该卡号过于简单'));
                }

                $user = Event::trigger('get_user_from_sec_card', $card_no) ? : O('user', ['card_no' => $card_no]);

                if (!$user->id) {
                    $card_no_s = (string)(($card_no + 0) & 0xffffff);
                    $user = Event::trigger('get_user_from_sec_card_s', $card_no_s) ? : O('user', ['card_no_s' => $card_no_s]);
                }
                
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
                    $user = Event::trigger('get_user_by_token', $token);
                }

                if (!$user->id) {
                    $this->log('%s尝试登录, 但系统不存在该用户', $token);
                    list($token, $backend) = Auth::parse_token($token);
                    $backends = Config::get('auth.backends');
                    $backend_title = $backends[$backend]['title'] ? I18N::T('people', $backends[$backend]['title']) : $backend;
                    throw new EQDevice_Exception(I18N::T('equipments', '登录名%token找不到相应的用户', ['%token'=>implode('@', [$token, $backend_title])]));
                }

				if ($user->atime == 0) {
					$this->log('%s尝试登录，但此账号未激活', $token);
                    $backends = Config::get('auth.backends');
                    $backend_title = $backends[$backend]['title'] ? I18N::T('people', $backends[$backend]['title']) : $backend;
					throw new EQDevice_Exception(I18N::T('equipments', '登录名%token未激活', ['%token'=>implode('@', [$token, $backend_title])]));	
				}
				
				if ($user->dto != 0 && $user->dto < time()) {
					$this->log('%s尝试登录，但此账号已过期', $token);
                    $backends = Config::get('auth.backends');
                    $backend_title = $backends[$backend]['title'] ? I18N::T('people', $backends[$backend]['title']) : $backend;
					throw new EQDevice_Exception(I18N::T('equipments', '登录名%token已过期', ['%token'=>implode('@', [$token, $backend_title])]));	
				}

                $digest = @base64_decode($struct->password);
                $ph = @openssl_get_privatekey(Config::get("equipment.private_key.{$equipment->site}", config::get('equipment.private_key')));
                $foo = @openssl_private_decrypt($digest, $password, $ph);

                if (!$foo) {
                    $this->log('用户%s[%d]密码无法进行解码', $user->name, $user->id);
                    throw new EQDevice_Exception(I18N::T('equipments', '密码验证失败, 请重新输入'));
                }

                $access = false;
                if (Module::is_installed('eq_glogon')) {
                    $access = EQ_Glogon::verify($token, $password);
                }
                if (!$access) {
                    $auth = new Auth($token);
                    $access = $auth->verify($password);
                }
                if (!$access) {
                    $this->log('用户%s[%d]密码验证失败', $user->name, $user->id);
                    throw new EQDevice_Exception(I18N::T('equipments', '密码验证失败, 请重新输入'));
                }
            }

            Cache::L('ME', $user);  //当前用户切换为该用户

            //进行Event
            Event::trigger('equipments.glogon.login', $struct, $user, $equipment);

            //要求打开仪器
            //检测用户是否可以操作仪器
            if (!$user->is_allowed_to('管理使用', $equipment) 
            && $equipment->cannot_access($user, Date::time())) {
                $this->log('用户%s[%d]无权打开%s[%d]', $user->name, $user->id, $equipment->name, $equipment->id);
                $messages = Lab::messages(Lab::MESSAGE_ERROR);
                if (count($messages)) {
                    //清空Lab::$messages,得到正确的错误提示
                    Lab::$messages[Lab::MESSAGE_ERROR] = [];
                    throw new EQDevice_Exception(join(' ', array_map(function($msg) {
                        return I18N::T('equipments', $msg);
                    }, $messages)));
                }
                else {
                    throw new EQDevice_Exception(I18N::T('equipments', '您无权使用%equipment', [
                        '%equipment'=>$equipment->name
                    ]));
                }
            }

            //进行Event
            Event::trigger('equipments.glogon.ret', $ret, $user, $equipment);

            $ret['switch_to'] = [
                'user' => ['username' => $user->token],
                'power_on' => TRUE, 
                'extra' => @json_decode($struct->extra, TRUE)
            ];

        }
        catch(EQDevice_Exception $e) {
            $ret['message'] = [
                'text' => $e->getMessage(),
            ];
        }

        // 触发脚本向glogon推送预约信息
        // 可能失败 因为有可能login成功之后才做某些事 
        $root = ROOT_PATH;
        $site = SITE_ID;
        $lab = LAB_ID;
        putenv("Q_ROOT_PATH={$root}");
        $cmd = "SITE_ID={$site} LAB_ID={$lab} php {$root} cli/cli.php eq_reserv glogon_current_reserv {$reserv->id} >/dev/null 2>&1 &";
        exec($cmd, $output);

        return (array)$ret;
    }

    function logout($device, $struct) {

        $this->_ready();

        $equipment = $this->_get_equipment($device);
        $struct = (object) $struct;
        $ret = [];

        try {

            $token = Auth::normalize($struct->user);
            $user = O('user', ['token'=>$token]);
            ///新协议
            $new_protocol = (bool) $struct->extra;

            if ($new_protocol) {
    
                $extra = @json_decode($struct->extra, TRUE);
                $feedback = [
                    'status'=> $extra['status'],
                    'feedback'=> $extra['feedback'],
                    ];

                $feedback['samples'] = isset($extra['samples']) ? (int) $extra['samples'] : Config::get('eq_record.record_default_samples');
            }
            else {
                $feedback = [
                    'status' => $struct->status,
                    'feedback' => $struct->feedback,
                    ];

                $feedback['samples'] = isset($struct->samples) ? (int) $struct->samples : Config::get('eq_record.record_default_samples');
            }

            if (!$user->id) {
                //直接可进行关闭
                $ret['switch_to'] = ['user'=>['username' => ''], 'power_on'=>FALSE, 'feedback'=>$feedback, 'extra'=> @json_decode($struct->extra, TRUE)];
            }
            else {

                Cache::L('ME', $user);  //当前用户切换为该用户

                $now = Date::time();

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

                /**
                 * 【通用】RQ205308 南开大学 反馈时，如果选择仪器故障，则样品数可以填0
                 */
                $is_feedback_problem = ($feedback['status'] == EQ_Record_Model::FEEDBACK_PROBLEM);
                // 如果samples为必填
                if (Config::get('eq_record.glogon_require_samples') && (version_compare($version, 2.0) >= 0)) {
                    if (Config::get('equipment.feedback_samples_allow_zero', FALSE)) {
                        if ($feedback['samples'] < 0) {
                            throw new EQDevice_Exception(I18N::T('equipments', '样品数填写有误, 请填写大于或等于0的整数!'));
                        }
                    } else {
                        if ($is_feedback_problem) {
                            if ($feedback['samples'] < 0) {
                                throw new EQDevice_Exception(I18N::T('equipments', '样品数填写有误, 请填写大于或等于0的整数!'));
                            }
                        } else {
                            if ($feedback['samples'] <= 0) {
                                throw new EQDevice_Exception(I18N::T('equipments', '样品数填写有误, 请填写大于0的整数!'));
                            }
                        }
                    }
                }

                if (class_exists('Lab_Project_Model')) {

                    if ($new_protocol) {
                        $project_id = $extra['project'];
                    }
                    else {
                        //如果必须填写project
                        $project_id = $struct->project;
                    }

                    //如果为fake的数据, 表示与对应的预约记录的关联项目相同
                    if ($project_id == self::FAKE_PROJECT_ID && Module::is_installed('eq_reserv')) {
                        $dtstart = Q("eq_record[user={$user}][equipment={$equipment}][dtend=0]:limit(1)")->current()->dtstart;
                        $dtend = $now;
                        $reserv = Q("eq_reserv[user={$user}][equipment={$equipment}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}|dtend={$dtstart}~{$dtend}]:limit(1)")->current();

                        $project_id = $reserv->project->id ? : $project_id;
                    }

                    $project = O('lab_project', $project_id);
                    $status = Lab_Project_Model::STATUS_ACTIVED;
                    $count = Q("$user lab lab_project[status={$status}]")->total_count();
                    $must_connect_project = Config::get('eq_record.must_connect_lab_project');

                    if ($must_connect_project && $count && (!$project->id && $project_id != self::FAKE_PROJECT_ID)) {
                        //如果选择了临时项目且不是预约选择了项目，则先让用户退出来，项目=0
                        throw new EQDevice_Exception(I18N::T('equipments', '请选择项目后再进行提交!'));
                    }

                    $feedback['project'] = $project->id;

                    if ($must_connect_project && !$count) {
                        unset($feedback['status']);
                        unset($feedback['feedback']);
                    }

                }

                $version = $equipment->device['version'];

                //登出trigger
                Event::trigger('equipments.glogon.logout', $struct, $user, $equipment);

                // $this->command_switch_to(array('user'=>$user, 'power_on'=>FALSE, 'agent'=>$agent, 'feedback'=>$feedback));
                // $agent = new Device_Agent($equipment);
                // $agent->call('switch_to', array('user'=>$user, 'power_on'=>FALSE, 'feedback'=>$feedback));

                $ret['switch_to'] = ['user'=>['username' => $user->token], 'power_on'=>FALSE, 'feedback'=>$feedback, 'extra'=> @json_decode($struct->extra, TRUE)];
            }
        }
        catch(EQDevice_Exception $e) {

            $ret['message'] = [
                'text' => $e->getMessage(),
            ];

            //$this->post_command('logout');
        }


        return $ret;
    }

    function samples($device, $struct) {

        $this->_ready();

        $ret['samples'] = [
            'require'=> Config::get('eq_record.glogon_require_samples'),
        ];

        return $ret;
    }

    function switch_to($device, $data) {

        $this->_ready();

        $equipment = $this->_get_equipment($device);

        $user_token = $data['user']['username'];
        $user = O('user', ['token'=>$user_token]);

        $power_on = $data['power_on'];
        $agent = $data['agent'];
        $feedback = $data['feedback'];

        $ret = [];
        $now = Date::time();

        $this->log('%s[%d] 尝试切换%s[%d] (%s) 的状态 => %s', $user->name, $user->id, $equipment->name, $equipment->id, $equipment->location , $power_on ? '打开':'关闭');

        $equipment->is_using = $power_on;
        $equipment->user_using = $power_on ? $user : null;
        $equipment->save();

        if ($power_on) {
            // 关闭该仪器所有因意外未关闭的record
            foreach (Q("eq_record[dtend=0][dtstart<=$now][equipment=$equipment]") as $record) {
                if ($record->dtstart==$now) {
                    $record->delete();
                    continue;
                }
                $record->dtend = $now - 1;
                // 关闭因为意外未关闭的record，应该为未反馈
                // $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
                $record->save();
            }

            $record = O('eq_record');
            $record->is_computer_device = TRUE;
            $record->dtstart = $now;
            $record->dtend = 0;
            $record->user = $user;
            $record->equipment = $equipment;
            $record->samples = Config::get('eq_record.must_samples') ? 0 : Config::get('eq_record.record_default_samples');
            
            if (Config::get('equipment.enable_use_type')) {
                $record->use_type = $data['extra']['use_type'] ? : EQ_Record_Model::USE_TYPE_USING;
                $record->use_type_desc = $data['extra']['use_type_desc'];
            }

            $record->save();
            Event::trigger('equipments.glogon.switch_to.login.record_saved', $record, $data);


            $name = $user->name;

            $labs = Q("$user lab");
            if ($labs->total_count() == 1) {
                $name .= ' ('.$labs->current()->name.')';
            }

            //获取 locale
            $locale = $equipment->device['lang'];
            if ($locale && !in_array($locale, ['zh-CN', 'zh_CN'])) {
                $name = PinYin::code($name);
            }

            $data = [
                'user' => $user->token,
                'name' => $name,
                'dtstart' => $now,
            ];

            if (Module::is_installed('nfs_windows')) {
				$data['fsip'] = Config::get('nfs.fsip');
				$data['fsfolder'] = Config::get('nfs.folder');
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

            $ret['login'] = $data;

        }
        else {
            $ret['logout'] = [];

            $record =  Q("eq_record[dtstart<{$now}][dtend=0][equipment={$equipment}]:sort(dtstart D):limit(1)")->current();
            if ($record->id) {


                $record->dtend = $now;
                //负责人关闭设备时,当这条记录是负责人自己的,那么状态默认是正常，如果是代开,status不设置
                if ($feedback) {
                    if (!is_array($feedback)) {
                        $feedback = json_decode($feedback, true);
                    }
                    $feedback_status = self::$feedback_status_map;

                    if (isset($feedback_status[($feedback['status'])])) {
                        $feedback['status'] = $feedback_status[($feedback['status'])];
                    }
                    $record->status = (int)$feedback['status'];
                    $record->feedback = $feedback['feedback'];
                    
                    if (isset($feedback['samples']) && $feedback['samples'] >= 0) {
                        // 用户反馈优先级最高
                        $record->samples = (int) $feedback['samples'];
                    } elseif (Config::get('equipment.feedback_show_samples', 0)) {
                        // 系统设置 - 反馈显示样品数
                        $record->samples = max(0, (int) $feedback['samples']);

                        // 系统设置 - 使用记录反馈样品数默认为空(需勾选反馈显示样品数)
                        // 都开启反馈了，反馈的数据一定不是空了，此处不作处理
                    } else {
                        $record->samples = Config::get('eq_record.record_default_samples');
                    }

                    if ($feedback['project']) $record->project = O('lab_project', $feedback['project']);
                }
                elseif ($record->user->is_allowed_to('管理使用', $equipment)) {

                    $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
                }

                if (Config::get('eq_record.duty_teacher') && $record->equipment->require_dteacher) {
                    $duty_teacher = O('user', $data['extra']['duty_teacher']);
                    $record->duty_teacher = $duty_teacher;
                }
                Event::trigger('equipments.glogon.switch_to.logout.record_before_save', $record, $data);
                $record->save();
                Event::trigger('equipments.glogon.switch_to.logout.record_saved', $record, $data);
            }
        }

        return $ret;
    }

    function install($struct) {

        $this->_ready();

        $struct = (object) $struct;
        $ret = [];

        $sn = $struct->access_code;
        $computer_name = $struct->computer_name;
        $os = $struct->os;
        $now = time();

        try {

            $locales = (array) Config::get('system.locales');

            /** install 的会话是一次性的, 所以 lang 不需保存 */
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
            $equipment->access_code_ctime = 0;  // 设置序列号过期

            if(!$equipment->save()){
                throw new EQDevice_Exception(I18N::T('equipments', '设备信息修改失败, 请联系技术支持!'));
            }

            $ph = openssl_get_privatekey(Config::get("equipment.private_key.{$equipment->site}", config::get('equipment.private_key')));
            $details = openssl_pkey_get_details($ph);
            $public_key = $details['key'];


            $update = Updater::available_update('LIMSLogon', $os);
            /*
            $this->post_command('install', array(
                                    'private_rsa'=>$eq_private_key,
                                    'public_rsa'=>$public_key,
                                    'super_key'=>$super_key,
                                    'update_uri'=>$update->update_uri,
                                    'uninstall_uri'=>$update->uninstall_uri,
                                    'public_key_token'=>$update->public_key_token,
                                    'autorun_uri'=>$update->autorun_uri,
                                    ));
            */
            $ret['install'] = [
                                    'private_rsa'=>$eq_private_key,
                                    'public_rsa'=>$public_key,
                                    'super_key'=>$super_key,
                                    'update_uri'=>$update->update_uri,
                                    'uninstall_uri'=>$update->uninstall_uri,
                                    'public_key_token'=>$update->public_key_token,
                                    'autorun_uri'=>$update->autorun_uri,
                ];


            $this->log('发送安装请求');
            //throw new Device_Exception;
        }
        catch(EQDevice_Exception $e){
            /*
            $this->post_command('message', array(
                                    'text' => $e->getMessage(),
                                    ));
            */
            $ret['message'] = [
                                    'text' => $e->getMessage(),
                ];
        }

        return $ret;

    }

    function connect($device, $code, $signature) {

        $this->_ready();

        $equipment = $this->_get_equipment($device);


        $priv = @openssl_get_privatekey(Config::get("equipment.private_key.{$equipment->site}", config::get('equipment.private_key')));
        if ($priv === FALSE || !@openssl_private_decrypt(@base64_decode($code), $client_code, $priv)) {
            // $this->log('%s[%d]OPENSSL解密签名失败', $equipment->name, $equipment->id);
            throw new API_Exception(self::$errors[1003], 1003);
        }

        $pub = @openssl_get_publickey($equipment->public_key);
        if ($pub === FALSE ||
            !@openssl_verify($client_code, @base64_decode($signature), $pub, OPENSSL_ALGO_SHA1)) {
            // $this->log('%s[%d]OPENSSL验证签名失败', $equipment->name, $equipment->id);
            throw new API_Exception(self::$errors[1004], 1004);
        }

        $server_code = Misc::random_password(8, 3);

        if (!@openssl_public_encrypt($server_code, $encoded_server_code, $pub)) {
            $server_code = NULL;
            // $this->log('%s[%d]OPENSSL加密签名失败', $equipment->name, $equipment->id);
            throw new API_Exception(self::$errors[1005], 1005);
        }

        if (!@openssl_sign($client_code, $server_signature, $priv, OPENSSL_ALGO_SHA1)) {
            $server_code = NULL;
            // $this->log('%s[%d]OPENSSL生成签名失败', $equipment->name, $equipment->id);
            throw new API_Exception(self::$errors[1006], 1006);
        }

        $ret = [
            'id' => $equipment->id,
            'name' => $equipment->name,
            'server_code_base64' => base64_encode($server_code),
            'client_code_base64' => base64_encode($client_code), // client_code 无法直接 return (Xiaopei Li@2014-04-09)
            'signature' => @base64_encode($server_signature),
            'code' => @base64_encode($encoded_server_code),
            ];

        // $this->log('%s[%d]已连接', $equipment->name, $equipment->id);

        /**
           connect中, struct 里会有 lang, 但在使用 glogon-server 的方式中,
           connect 的 lang 会被 glogon-server 暂时保存, 并在 confirm 中
           传给 lims2 API, confirm 中会保存 lang.

           以下流程会拆作 2 步:
           1. confirm 中保存 device lang;
           2. _get_equipment 中, 设置 API 中使用的 lang;

           (Xiaopei Li@2014-04-16)
        */
        /*
        if ($struct->lang) {

            $this->log('SET LOCALE=%s', $struct->lang);
            Config::set('system.locale', $struct->lang);
            I18N::shutdown();
            I18N::setup();
        }
        */

        return $ret;
    }

    function confirm ($device, $user_token, $info = []) {
        $this->_ready();
        $equipment = $this->_get_equipment($device, $lang);

        // 此处有部分修改ipc修改为rest
        // 暂时抛弃没有使用的plugin
        $equipment->server = $info['rest'];
        $equipment->connect = true;
        $equipment->device = [
            'uuid'=> $device, // device_agent 中会用到 uuid (device)
            'os' => $info['os'],
            'version' => $info['version'],
            'lang' => $info['lang']
        ];

        $ret = [];
        $user = O('user', ['token' => Auth::normalize($user_token)]);
        if (!$user->id && $struct->user != 'administrator') {
            // 当前无用户, 并且不为administrator
            // post_command status
            // 仪器未使用
            if ($equipment->is_using) $equipment->is_using = FALSE;
        }
        else {
            //仪器使用中
            $now = time();
            if (!$equipment->is_using) $equipment->is_using = TRUE;

            //如果仪器无使用中使用记录
            //但是当前仪器在使用
            //进行version比对, 如果超过2.2版本
            //会有离线记录进行更新, 不予自动创建
            //反之, 创建使用中使用记录
            if (version_compare($version, '2.2') < 0 
            && !Q("eq_record[equipment={$equipment}][dtend=0]")->total_count()) {
                $record = O('eq_record');
                $record->dtstart = $now;
                $record->dtend = 0;
                $record->user = $user;
                $record->equipment = $equipment;
                $record->save();
            }
        }
        $equipment->save();

        // 发送backends信息更新login窗口
        $ret['backends'] = [];
        $auth_backends = (array) Config::get('auth.backends');
        $num = 1;
        foreach ($auth_backends as $k => $o) {
            $ret['backends'][$k] = $num . '. ' . I18N::T('people', $o['title']);
            $num++;
        }
        $ret['default_backend'] = Config::get('auth.default_backend');

        $cards = [];
        $free_access_cards = $equipment->get_free_access_cards();
        foreach($free_access_cards as $card_no => $user) {
            if (isset($_SERVER['CARD_BYTE_SWAP'])) {
                $card_no = (string)Misc::byte_swap32($card_no);
            }
            $cards[$card_no] = (string)$card_no;
        }
        $ret['cards'] = array_values($cards);

        $ret['key'] = Cache::normalize_key($equipment->cache_name($equipment->id));
        $offline_md5 = array_merge(Config::get('glogon.offline_md5'), Config::get('glogon.offline_md5_with_evaluate', []));

        $computer_url = Config::get('equipment.computer_url') ?: URI::url('/');

        $ret['views'] = [
            'login'=> URI::url($computer_url.'!equipments/glogon/login', ['locale' => $info['lang'], 'id' => $equipment->id]),
            'logout'=> URI::url($computer_url.'!equipments/glogon/logout', ['locale' => $info['lang'], 'id' => $equipment->id]),
            'prompt'=> URI::url($computer_url.'!equipments/glogon/prompt', ['locale' => $info['lang'], 'id' => $equipment->id]),
            'offline_login_md5' => $offline_md5[$info['lang']]['login'],
            'offline_login_package' => URI::url($computer_url.'!equipments/glogon/offline_login.zip', ['locale' => $info['lang']]),
            'offline_logout_md5' => $offline_md5[$info['lang']]['logout'],
            'offline_logout_package' => URI::url($computer_url.'!equipments/glogon/offline_logout.zip', ['locale' => $info['lang']]),
            'offline_prompt_md5' => $offline_md5[$info['lang']]['prompt'],
            'offline_prompt_package' => URI::url($computer_url.'!equipments/glogon/offline_prompt.zip', ['locale' => $info['lang']]),
        ];
        error_log(print_r($ret, 1));

        return $ret;
    }
    
    // 单纯去做仪器断网的状态更新
    function disconnect($device) {
        $this->_ready();
        $equipment = $this->_get_equipment($device);
        $equipment->connect = false;
        return $equipment->save();
    }

    function status($device, $struct) {
        $this->_ready();

        $equipment = $this->_get_equipment($device);
        $struct = (object) $struct;
        $ret = [];

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
                //代开相同则同一人使用
                if ($user->id == $last_record->user->id || $user->id == $last_record->agent->id) {
                    $is_using = TRUE;
                }
                else {
                    //最后的使用中的使用记录的闭合
                    $last_record->dtend = $now;
                    $last_record->save();
                    $ret['logout'] = [];
                }
            }
            else {
                //非使用中
                // $this->post_command('status');
                // $ret['status'] = [];
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
        }

        return $ret;
    }

    function plugins($device, $struct) {

        $this->_ready();

        $equipment = $this->_get_equipment($device);
        $struct = (object) $struct;
        $ret = [];

        /** @todo 需确认此处用 device2 还是 device (Xiaopei Li@2014-04-15) */
        $device = (array) $equipment->device;
        $device['plugins']  = (array) $struct->plugins;
        $equipment->device = $device;
        $equipment->save();

        $this->log('客户端插件: %s', @json_encode($equipment->device['plugins']));
        return $ret;
    }

    /* 
     * cheng.liu@geneegrouop.com (应急需求)
     * 2016.1.11 获取固定时间段内已预约用户卡号对应的时间段表 
     */
    function offline_reserv($device) {
        $this->_ready();
        $equipment = $this->_get_equipment($device);
        
        /* 该仪器允许预约 */
        if (!$equipment->accept_reserv) { return false; }

        /* 该仪器允许用户在他人预约时段使用仪器（非预约段除外） */
        if ($equipment->unbind_reserv_time) { /* 暂时商量不予处理 */ }

        $dtstart = Date::get_day_start();
        $dtend = Date::next_time($dtstart, Config::get('glogon.offline_reserv_day', 5));
        $ret = [];
        $reservs = Q("eq_reserv[equipment={$equipment}][dtstart=$dtstart~$dtend]");
        foreach ($reservs as $r) {
            $user = $r->user;
            if ($card_no = ($user->card_no ? : $user->get_card_no())) {
                !is_array($ret[$card_no]) and $ret[$card_no] = [];
                $ret[$card_no][] = ['dtstart' => $r->dtstart, 'dtend' => $r->dtend];
            }
        }

        return $ret ?: false;
        
    }

}
