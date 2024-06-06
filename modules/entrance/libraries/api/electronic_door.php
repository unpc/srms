<?php

/*
*   Cheng Liu (cheng.liu@geneegroup.com)
*   配合电子门牌业务进行的接口判断，业务驱动学校：内蒙古大学
*   2018-10-16
    成哥说了是个超级临时版的api 别在这上面狂改 有时间应该重构抽离

    |——————————————|
    |              |        http       | ——————————|
    |   电子门牌    |  ----------------> |   CF     |
    |              |                   |___________|
    |              |
    |——————————————|
*/
class API_Electronic_Door {

    public static $errors = [
        1001 => '请求来源非法!',
        1002 => '找不到对应的门禁!',
        1003 => '找不到相应的用户!',
        1004 => '用户验证失败!',
        1005 => '用户无权打开仪器!',
        1006 => '不支持此验证方式!',
        1010 => '远程门禁控制连接失败!'
    ];

    private function _ready() {
	    return;
        $whitelist = Config::get('api.white_list_electronic_door', []);
        $whitelist[] = $_SERVER["SERVER_ADDR"];

        // 1. 先行判断是否在系统白名单设定ip内，如若在则可不进行密钥对验证
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

        // 2. 非系统白名单设定ip内地址请求，需要进行密钥对头验证
        $client_id = $_SERVER['HTTP_CLIENT_ID'];
        $client_secret = $_SERVER['HTTP_CLIENT_SECRET'];
        $provides = Config::get('rpc.electronic_door_identity');
        if ($provides['client_id'] == $client_id && $provides['client_secret'] == $client_secret) {
            return;
        }

        throw new API_Exception(self::$errors[1001], 1001);

    }

    private function log() {
        $args = func_get_args();
        if ($args) {
            $format = array_shift($args);
            if ($args) {
                $str = vsprintf($format, $args);
            }
            else {
                $str = $format;
            }
            Log::add("[electronic door api] {$str}", 'devices');
        }
    }

    public function auth($addr, $auth_info) {
        $this->_ready();
        $quoted_addr = Q::quote(trim($addr));

        $door = Q("door[in_addr=$quoted_addr|out_addr=$quoted_addr]:limit(1)")->current();
        if (!$door->id) throw new API_Exception(self::$errors[1002], 1002);

        $direction = $auth_info['direction'];

        switch ($auth_info['type']) {
            case 'card':
                $card_no = (int) $auth_info['card_no'];

                $user = Event::trigger('get_user_from_sec_card', $card_no) ? : O('user', ['card_no' => $card_no]);

                if (!$user->id) {
                    $card_no_s = sprintf('%u', $card_no & 0xffffff);
                    $user = Event::trigger('get_user_from_sec_card_s', $card_no_s) ? : O('user', ['card_no_s' => $card_no_s]);

                }

                if (!$user->id) {
                    $this->log("%s[%d](%s) %s 验证失败: 卡号 %s(%s) 未找到关联用户",
                            $door->name, $door->id, $door->location1 . $door->location2, $direction == 'in' ? '进门':'出门',
                            $card_no, $card_no_s);

                    throw new API_Exception(self::$errors[1003], 1003);
                }
                break;
            case 'user':
                $uid = (int) $auth_info['user'];

                $user = O('user', $uid);

                if (!$user->id && $auth_info['ref_no']) {
                    $user = O('user', ['ref_no' => $auth_info['ref_no']]);
                }

                if (!$user->id) {
                    $this->log("%s[%d](%s) %s 验证失败: 人员信息 %s 未找到关联用户",
                            $door->name, $door->id, $door->location1 . $door->location2, $direction == 'in' ? '进门':'出门', $uid ?: $auth_info['ref_no']);

                    throw new API_Exception(self::$errors[1003], 1003);
                }
                break;
            default:
                throw new API_Exception(self::$errors[1006], 1006);
        }

        $is_allowed = $user->is_allowed_to('刷卡控制', $door, ['direction'=> $auth_info['direction']]);
        $extra_allowed = Event::trigger('entrance_door_auth_direction', $direction);

        if ($extra_allowed) {
            $this->log("%s[%d](%s) %s 通过验证: %s[%d]",
                   $door->name, $door->id, $door->location1 . $door->location2, $direction == 'in' ? '进门':'出门',
                   $user->name, $user->id);

            return [
                'code' => '0000',
                'info' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ]
            ];
        }

        if (!$is_allowed && $door->cannot_access($user, $auth_info['time'], $auth_info['direction'])) {
            $this->log("%s[%d](%s) %s 验证失败: %s[%d]",
                       $door->name, $door->id, $door->location1 . $door->location2, $direction == 'in' ? '进门':'出门',
                       $user->name, $user->id);

            throw new API_Exception(self::$errors[1005], 1005);
        }

        $this->log("%s[%d](%s) %s 通过验证: %s[%d]",
            $door->name, $door->id, $door->location1 . $door->location2, $direction == 'in' ? '进门':'出门',
            $user->name, $user->id);

        return [
            'code' => '0000',
            'info' => [
                'id' => $user->id,
                'name' => $user->name,
            ]
        ];
    }

    public function sync_control($addr, $auth_info, $command='open') {
        $this->log("%s [%s] 发起 sync_control 控制！", $addr, $auth_info['card_no']);
        $user = $this->auth($addr, $auth_info);

        if ($user['info']['id']) {
            $quoted_addr = Q::quote(trim($addr));
            $door = Q("door[in_addr=$quoted_addr|out_addr=$quoted_addr]:limit(1)")->current();
            $u = O('user', $user['info']['id']);
            Cache::L('ME', $u);

            $type = explode(':', $door->device['uuid'])[0];
            try {
                if ($type == 'cacs' || $type == 'icco') {
                    $agent = new Device_Agent($door, FALSE, 'in');
                    if (!$agent->call('open')) {
                        throw new API_Exception(self::$errors[1010], 1010);
                    }

                    if (Event::trigger('door.in', $door)) {
                        $this->log("%s[%d](%s) 远程控制开门成功: %s[%d], 门禁连接方式: 自产门禁",
                            $door->name, $door->id, $door->location1 . $door->location2,
                            $u->name, $u->id);
                        return [
                            'code' => '0000',
                            'info' => true
                        ];
                    }
                } else {
                    $client = new \GuzzleHttp\Client([
                        'base_uri' => $door->server,
                        'http_errors' => FALSE,
                        'timeout' => Config::get('device.gdoor.timeout', 10)
                    ]);


                    $success = (boolean) $client->post('open', [
                        'form_params' => [
                            'uuid' => $door->device['uuid'],
                            'user' => [
                                'username' => $u->token,
                                'name' => $u->name
                            ]
                        ]
                    ])->getBody()->getContents();

                    if ($success && Event::trigger('door.in', $door)) {
                        $this->log("%s[%d](%s) 远程控制开门成功: %s[%d], 门禁连接方式: 三方门禁",
                            $door->name, $door->id, $door->location1 . $door->location2,
                            $u->name, $u->id);
                        return [
                            'code' => '0000',
                            'info' => true
                        ];
                    }
                }
            }
            catch (Exception $e) {
                $this->log("%s[%d](%s) 发起远程控制开门出现异常: %s[%d]",
                   $door->name, $door->id, $door->location1 . $door->location2,
                   $u->name, $u->id);
                throw new API_Exception(self::$errors[1010], 1010);
            }
        }

        throw new API_Exception(self::$errors[1004], 1004);
    }
}