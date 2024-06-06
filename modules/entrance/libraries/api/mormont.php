<?php
/**
 * gpui版门禁接口 2020-12-01
 */
class API_Mormont extends API_Common
{
    public static $errors = [
        1001 => '请求来源非法!',
        1002 => '找不到对应的门禁!',
        1003 => '找不到相应的用户!',
        1004 => '用户验证失败!',
        1005 => '用户无权打开仪器!',
        1006 => '不支持此验证方式!',
        1010 => '远程门禁控制连接失败!'
    ];

    private function log()
    {
        $args = func_get_args();
        if ($args) {
            $format = array_shift($args);
            if ($args) {
                $str = vsprintf($format, $args);
            } else {
                $str = $format;
            }
            Log::add("[mormont api] {$str}", 'devices');
        }
    }

    public function auth($addr, $auth_info)
    {
        $this->_ready('mormont');
        $quoted_addr = Q::quote(trim($addr));

        $door = Q("door[in_addr=$quoted_addr|out_addr=$quoted_addr]:limit(1)")->current();
        if (!$door->id) {
            throw new API_Exception(self::$errors[1002], 1002);
        }

        $direction = $auth_info['direction'];

        switch ($auth_info['type']) {
            case 'card':
                $card_no = (int)$auth_info['card_no'];
                $user = Event::trigger('get_user_from_sec_card', $card_no) ?: O('user', ['card_no' => $card_no]);
                if (!$user->id) {
                    $card_no_s = sprintf('%u', $card_no & 0xffffff);
                    $user = Event::trigger('get_user_from_sec_card_s', $card_no_s) ?: O('user', ['card_no_s' => $card_no_s]);
                }

                if (!$user->id) {
                    $this->log(
                        "%s[%d](%s) %s 验证失败: 卡号 %s(%s) 未找到关联用户",
                        $door->name,
                        $door->id,
                        $door->location1 . $door->location2,
                        $direction == 'in' ? '进门' : '出门',
                        $card_no,
                        $card_no_s
                    );

                    throw new API_Exception(self::$errors[1003], 1003);
                }
                break;
            default:
                throw new API_Exception(self::$errors[1006], 1006);
        }

        $is_allowed = $user->is_allowed_to('刷卡控制', $door, ['direction'=> $auth_info['direction']]);
        $extra_allowed = Event::trigger('entrance_door_auth_direction', $direction);

        if ($extra_allowed) {
            $this->log(
                "%s[%d](%s) %s 通过验证: %s[%d]",
                $door->name,
                $door->id,
                $door->location1 . $door->location2,
                $direction == 'in' ? '进门':'出门',
                $user->name,
                $user->id
            );

            return [
                'code' => '0000',
                'info' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ]
            ];
        }

        if (!$is_allowed && $door->cannot_access($user, $auth_info['time'], $auth_info['direction'])) {
            $this->log(
                "%s[%d](%s) %s 验证失败: %s[%d]",
                $door->name,
                $door->id,
                $door->location1 . $door->location2,
                $direction == 'in' ? '进门':'出门',
                $user->name,
                $user->id
            );

            throw new API_Exception(self::$errors[1005], 1005);
        }

        $this->log(
            "%s[%d](%s) %s 通过验证: %s[%d]",
            $door->name,
            $door->id,
            $door->location1 . $door->location2,
            $direction == 'in' ? '进门':'出门',
            $user->name,
            $user->id
        );

        return [
            'code' => '0000',
            'info' => [
                'id' => $user->id,
                'name' => $user->name,
            ]
        ];
    }

    public function sync_control($addr, $auth_info, $command='open')
    {
        $this->_ready('mormont');
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
                    $agent = new Device_Agent($door, false, 'in');
                    if (!$agent->call('open')) {
                        throw new API_Exception(self::$errors[1010], 1010);
                    }

                    if (Event::trigger('door.in', $door)) {
                        $this->log(
                            "%s[%d](%s) 远程控制开门成功: %s[%d], 门禁连接方式: 自产门禁",
                            $door->name,
                            $door->id,
                            $door->location1 . $door->location2,
                            $u->name,
                            $u->id
                        );
                        return [
                            'code' => '0000',
                            'info' => true
                        ];
                    }
                } else {
                    return [
                        'code' => '0000',
                        'info' => true
                    ];
                }
            } catch (Exception $e) {
                $this->log(
                    "%s[%d](%s) 发起远程控制开门出现异常: %s[%d]",
                    $door->name,
                    $door->id,
                    $door->location1 . $door->location2,
                    $u->name,
                    $u->id
                );
                throw new API_Exception(self::$errors[1010], 1010);
            }
        }

        throw new API_Exception(self::$errors[1004], 1004);
    }

    public function get_records($addr, $start = 0, $step = 10)
    {
        $this->_ready('mormont');
        $dc_records = Q("door[in_addr={$addr}] dc_record[direction=1]:sort(time D)")->limit($start, $step);
        $info = [];

        if (count($dc_records)) {
            foreach ($dc_records as $dc_record) {
                $data = new ArrayIterator([
                    'name' => $dc_record->door->name,
                    'user_name' => $dc_record->user->name,
                    'time' => date('Y/m/d H:i:s', $dc_record->time)
                ]);
                $info[] = $data->getArrayCopy();
            }
        }
        return $info;
    }
}
