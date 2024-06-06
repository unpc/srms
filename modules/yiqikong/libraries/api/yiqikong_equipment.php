<?php

class API_YiQiKong_Equipment extends API_Common
{
    // TODO: 一定要切到特么的C层
    public function _MAKEUSER($id)
    {
        if (is_numeric($id)) {
            $user = O('user', ['yiqikong_id' => $id]);
        } elseif (preg_match('/^#\d+/', $id)) {
            $user = O('user', str_replace("#",'',$id));
        } else {
            $user = O('user', ['email' => $id]);
        }
        if (!$user->id) throw new API_Exception('没找到对应用户', 404);
        return $user;
    }

    public function update($params = [])
    {
        $this->_ready();
        try {
            $res = Common_Equipment::update($params);
            return $res;
        } catch (API_Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function swtich($data)
    {
        $this->_ready();
        $now = Date::time();
        $equipment = O('equipment', ['yiqikong_id' => $data['equipment']]);
        $user = self::_MAKEUSER($data['user']);

        if (!$user->id || !$equipment->id) throw new API_Exception('未找到对应信息', 404);
        Cache::L('ME', $user);
        Cache::L('YiQiKongSwitchAction', TRUE);

        if ($equipment->control_mode == 'computer'
            || ($equipment->control_mode == 'power' && preg_match('/^gmeter/', $equipment->control_address))
            || $equipment->control_mode == 'ultron' || $equipment->control_mode == 'bluetooth') {
            if (!$equipment->server) throw new API_Exception('仪器信息异常', 500);
            //进行物理关机
            $config = Config::get('rpc.servers')['jarvis'];
            $client = new \GuzzleHttp\Client([
                'base_uri' => $equipment->server,
                'timeout' => Config::get('device.computer.timeout', 5),
                'headers' => [
                    'HTTP-CLIENTID' => $config['client_id'],
                    'HTTP-CLIENTSECRET' => $config['client_secret'],
                ],
            ]);
        } else {
            throw new API_Exception('该仪器的控制方式目前不支持移动端开机', 401);
        }

        $record = Q("eq_record[dtstart<={$now}][dtend=0][equipment={$equipment}][user={$user}]:sort(dtstart D):limit(1)")->current();

        switch ($data['action']) {
            case 'on' :
                // 用户有权管理, 或者用户可使用 可开机, 则开机
                if ($user->is_allowed_to('管理使用', $equipment)
                    || !$equipment->cannot_access($user, Date::time())) {
                    $success = (boolean)$client->post('switch_to', [
                        'form_params' => [
                            'uuid' => str_replace('gmeter://', '', $equipment->control_address),
                            'user' => [
                                'username' => $user->token,
                                'name' => $user->name
                            ],
                            'power_on' => TRUE
                        ]
                    ])->getBody()->getContents();

                    if ($success) {
                        $equipment->is_using = TRUE;
                        $equipment->save();
                    }
                }
                break;
            case 'off' :
                if ($user->is_allowed_to('管理使用', $equipment)
                    || $record->id) {
                    $success = (boolean)$client->post('switch_to', [
                        'form_params' => [
                            'uuid' => str_replace('gmeter://', '', $equipment->control_address),
                            'feedback' => json_encode($data['feedback']),
                            'user' => [
                                'equipmentId' => $equipment->id,
                                'username' => $user->token,
                                'name' => $user->name,
                                'id' => $user->id
                            ],
                            'power_on' => FALSE
                        ]
                    ])->getBody()->getContents();

                    if ($success) {
                        $equipment->is_using = FALSE;
                        $equipment->save();
                    }
                }
                break;
            default :
        }
        return ['flag' => $success, 'source_name' => LAB_ID, 'source_id' => $record->id];
    }

    public function status($data)
    {
        $this->_ready();
        $now = Date::time();
        $equipment = O('equipment', ['yiqikong_id' => $data['equipment']]);
        if (!$equipment->id) throw new API_Exception('未找到对应信息', 404);

        //$user = O('user', $equipment->user_using_id);
        $record = O('eq_record', ['equipment' => $equipment, 'dtend' => 0]);
        $user = $record->user;
        return [
            'using' => !!$equipment->is_using,
            'user' => $user->id ? [$user->id => $user->name] : [],
            'record' => [
                'source_id' => $record->id,
                'source_name' => LAB_ID,
                'dtstart' => date('Y-m-d H:i:s', $record->dtstart),
                'user_id' => $user->id,
                'user_name' => $user->name
            ],
            'dtstart' => date('Y-m-d H:i:s', $record->dtstart),
            'now' => time()
        ];
    }

    public function switchPermission($data)
    {
        $this->_ready();
        $equipment = O('equipment', ['yiqikong_id' => $data['equipment']]);
        $user = self::_MAKEUSER($data['user']);

        if (!$user->id || !$equipment->id) throw new API_Exception('未找到对应信息', 404);
        Cache::L('ME', $user);

        // 被实体客户端控制
        $actual = $equipment->control_mode == 'computer'
            || ($equipment->control_mode == 'power' && preg_match('/^gmeter/', $equipment->control_address))
            || $equipment->control_mode == 'ultron' || $equipment->control_mode == 'veronica';

        // 被虚拟客户端控制
        $virtual = $equipment->control_mode == 'bluetooth' || $equipment->control_mode == '';

        if (!$actual && !$virtual) throw new API_Exception('该仪器的控制方式目前不支持移动端开机', 401);

        // 仪器在使用, 判断用户是否有权限关机
        $now = Date::time();
        $record = Q("eq_record[dtstart<={$now}][dtend=0][equipment={$equipment}]:sort(dtstart D):limit(1)")->current();
        if ($record->id && $record->user->id == $user->id) return true;

        if ($record->id && $record->user->id != $user->id
            && !$user->is_allowed_to('管理使用', $equipment)) throw new API_Exception('您无权使用该仪器', 401);

        // 权限检查判断
        if (!$equipment->is_using) {
            // 仪器未使用, 判断用户是否有权限开机
            $cannot = !$user->is_allowed_to('管理使用', $equipment) && $equipment->cannot_access($user, Date::time());
            $messages = Lab::messages(Lab::MESSAGE_ERROR) ?: ['您无权使用该仪器'];
            if ($cannot) throw new API_Exception(implode(',', $messages), 401);
        }
        return true;
    }

    public function get_charge_rules($con)
    {
        try {
            $equipment = O('equipment', ['yiqikong_id' => $con['equipment']]);
            $user = O('user', $con['user']);
            if (!$equipment->id || !$user->id) {
                throw new API_Exception('未找到相关信息');
            }

            $standards = EQ_Charge::charge_template_standards($equipment, null, $user);
            $rules = [];
            $mix = 0;
            if ($equipment->accept_reserv && $standards['reserv']) {
                $mix = $equipment->charge_script['reserv'] && !$standards['record'] ? 1 : 0;
                $rc = '预约计费设置';
                $rules['reserv'] = ['free' => ($standards['reserv'] == '免费使用' && !$equipment->charge_script['reserv']) ? 1 : 0, 'name' => $rc, 'content' => str_replace(['<p>', '</p>', '<span>', '</span>'], ['', '', '', '.'], $standards['reserv'])];
            }
            if ($mix) {
                $rerule = $rules['reserv'];
                $rerule['name'] = '使用计费设置';
                $rules['record'] = $rerule;
            } else {
                $rules['record'] = ['free' => ($standards['record'] == '免费使用' && !$equipment->charge_script['record']) ? 1 : 0, 'name' => '使用计费设置', 'content' => str_replace(['<p>', '</p>', '<span>', '</span>'], ['', '', '', '.'], $standards['record'])];
            }

            if ($equipment->accept_sample) {
                $rules['sample'] = ['free' => ($standards['sample'] == '免费使用' && !$equipment->charge_script['sample']) ? 1 : 0, 'name' => '送样计费设置', 'content' => str_replace(['<p>', '</p>', '<span>', '</span>'], ['', '', '', '.'], $standards['sample'])];
            }
            return $rules;
        } catch (API_Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
