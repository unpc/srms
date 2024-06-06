<?php

/**
 * 接入第三方门禁统一入口
 *
 * 应用级别错误代码:
 * 1001: 请求来源非法!
 * 1002: 找不到对应的门禁!
 * 1003: 找不到相应的用户!
 * 1004: 用户验证失败!
 * 1005: 用户无权打开门禁!
 **/
class API_Entrance_Door
{

    public static $errors = [
        1000 => '请求参数不正确!',
        1001 => '请求来源非法!',
        1002 => '找不到对应的门禁!',
        1003 => '找不到相应的用户!',
        1004 => '用户验证失败!',
        1005 => '用户无权打开仪器!',
        1006 => '不支持此验证方式!',
    ];

    private static $_apps = [];

    /**
     * 认证方式
     * header中带g-key,g-time,g-app
     * 字典排序
     * @throws API_Exception
     */
    private function _ready()
    {
        empty(self::$_apps) && self::$_apps = Config::get('door_apps.door_apps');
        //获取header里信息
        $params['G_APP'] = $_SERVER['HTTP_G_APP'];//appname
        $params['G_KEY'] = $_SERVER['HTTP_G_KEY'];//appkey
        $params['G_TIME'] = $_SERVER['HTTP_G_TIME'];//当前发起请求的时间戳
        if (!$params['G_KEY'] || !$params['G_TIME']
            || !$params['G_APP']
            || !$_SERVER['HTTP_G_SIGNATURE']
        ) {
            throw new API_Exception(self::$errors[1000], 1000);
        }

        if (isset(self::$_apps[$params['G_APP']])
            && self::$_apps[$params['G_APP']]['appKey'] == $params['G_KEY']
        ) {
            $appSecret = self::$_apps[$params['G_APP']]['appSecret'];
            sort($params);
            $tmpStr = implode('', $params);
            $signature = base64_encode(hash_hmac('sha256', $tmpStr, $appSecret));
            if ($signature == $_SERVER['HTTP_G_SIGNATURE']) {
                return true;
            }
        }

        throw new API_Exception(self::$errors[1001], 1001);
    }

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
            Log::add("[entrance api] {$str}", 'devices');
        }
    }


    function connect($addr, $server)
    {
        $this->_ready();

        $quoted_addr = Q::quote(trim($addr));

        $door = Q("door[in_addr=$quoted_addr|out_addr=$quoted_addr]:limit(1)")->current();
        if (!$door->id) throw new API_Exception(self::$errors[1002], 1002);

        $door->device = ['uuid' => $addr];
        $door->server = $server;
        $door->save();

        //加入列表
        //方向 进门:1, 出门:0
        $direction = ($door->in_addr == $addr) ? 'in' : 'out';

        $this->log("%s[%d](%s) %s读卡器 请求连接", $door->name, $door->id, $door->location1 . $door->location2, $direction == 'in' ? '进门' : '出门');

        $free_access_cards = (array)$door->get_free_access_cards();

        return [
            'id' => $door->id,
            'name' => $door->name,
            'lock_id' => $door->lock_id,
            'detector_id' => $door->detector_id,
            'direction' => $direction,
            'access_cards' => $free_access_cards,
        ];
    }

    function auth($door_id, $auth_info, $protocol = '')
    {
        $this->_ready();

        if ($protocol) {
            $door = O('door', ['in_addr' => $door_id]);
        } else {
            $door = O('door', $door_id);
        }
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
                    $this->log("%s[%d](%s) %s 验证失败: 卡号 %s(%s) 未找到关联用户",
                        $door->name, $door->id, $door->location1 . $door->location2, $direction == 'in' ? '进门' : '出门',
                        $card_no, $card_no_s);

                    throw new API_Exception(self::$errors[1003], 1003);
                }
                break;
            default:
                throw new API_Exception(self::$errors[1006], 1006);
        }

        $is_allowed = $user->is_allowed_to('刷卡控制', $door, ['direction' => $auth_info['direction']]);
        $extra_allowed = Event::trigger('entrance_door_auth_direction', $direction);

        if ($extra_allowed) {
            $this->log("%s[%d](%s) %s 通过验证: %s[%d]",
                $door->name, $door->id, $door->location1 . $door->location2, $direction == 'in' ? '进门' : '出门',
                $user->name, $user->id);

            return [
                'id' => $user->id,
                'name' => $user->name,
            ];
        }

        if (!$is_allowed && $door->cannot_access($user, $auth_info['time'], $auth_info['direction'])) {
            $this->log("%s[%d](%s) %s 验证失败: %s[%d]",
                $door->name, $door->id, $door->location1 . $door->location2, $direction == 'in' ? '进门' : '出门',
                $user->name, $user->id);

            throw new API_Exception(self::$errors[1005], 1005);
        }

        $this->log("%s[%d](%s) %s 通过验证: %s[%d]",
            $door->name, $door->id, $door->location1 . $door->location2, $direction == 'in' ? '进门' : '出门',
            $user->name, $user->id);

        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    function record($door_id, $record_info)
    {
        $this->_ready();

        $door = O('door', $door_id);
        if (!$door->id) {
            throw new API_Exception(self::$errors[1002], 1002);
        }

        if ($record_info['user_id']) {
            $user = O('user', $record_info['user_id']);
        } else if ($record_info['card_no']) {
            $card_no = (int)$record_info['card_no'];
            $user = Event::trigger('get_user_from_sec_card', $card_no) ?: O('user', ['card_no' => $card_no]);
            if (!$user->id) {
                $card_no_s = sprintf('%u', $card_no & 0xffffff);
                $user = Event::trigger('get_user_from_sec_card_s', $card_no_s) ?: O('user', ['card_no_s' => $card_no_s]);
            }

        } else {
            throw new API_Exception(self::$errors[1003], 1003);
        }

        if (!$user->id) {
            throw new API_Exception(self::$errors[1003], 1003);
        }

        $record = O('dc_record');
        $record->time = $record_info['time'];
        $record->user = $user;
        $record->door = $door;
        $record->direction = $record_info['direction'] == 'in' ? 1 : 0;

        $this->log("%s[%d](%s) %s生成记录: %s[%d] %s",
            $door->name, $door->id, $door->location1 . $door->location2, $record->direction ? '进门' : '出门',
            $user->name, $user->id, Date::format($record->time, 'Y/m/d H:i:s'));

        return $record->save();
    }

    function status($door_id, $status)
    {
        $this->_ready();

        $door = O('door', $door_id);
        if (!$door->id) {
            throw new API_Exception(self::$errors[1002], 1002);
        }

        switch ($status) {
            case 'open':
                $this->log("%s[%d](%s) 门已打开", $door->name, $door->id, $door->location1 . $door->location2);
                $door->is_open = TRUE;
                break;
            case 'close':
                $this->log("%s[%d](%s) 门已关闭", $door->name, $door->id, $door->location1 . $door->location2);
                $door->is_open = FALSE;
                break;
        }

        return $door->save();
    }

    /*
     * cheng.liu@geneegrouop.com (应急需求)
     * 2016.1.11 获取固定时间段内可以进门的卡号信息
     */
    function offline_reserv($door_id)
    {
        $this->_ready();
        $door = O('door', $door_id);
        if (!$door->id) {
            throw new API_Exception(self::$errors[1002], 1002);
        }

        $rules = (array)@json_decode($door->rules, TRUE);
        $default_rule = (array)$rules['default'];
        unset($rules['default']);
        $cards = [];
        $dtstart = Date::get_day_start();
        $dtend = Date::next_time($dtstart, Config::get('entrance.offline_reserv_day', 5));
        // 还是要先取出来符合门禁规则的卡号放在一边
        foreach ($rules as $rule) {
            if ($rule['access']) {
                // TODO $rule[directions][1] or $rule[directions][0] 后期可做进出门判断

                // 判断时间规则是否在需要给出卡号的最大时间周期内
                if ($rule['dtstart'] < $dtend && $rule['dtend'] > $dtstart) {

                    $tmp = $dtstart;
                    while ($tmp <= $dtend) {
                        if ($tmp >= $rule['dtstart'] && $tmp <= $rule['dtend']) {
                            // 由于不想过多的来进行代码重构，所以需要模拟出来对应起始时间的 时:分:秒 的两次数据来进行判断
                            $pre = Date::format($tmp, 'Y-m-d');
                            $one = strtotime($pre . ' ' . Date::format($rule['dtstart'], 'H:i:s'));
                            $two = strtotime($pre . ' ' . Date::format($rule['dtend'], 'H:i:s'));
                            if (TM_RRule::match_time_rule($one, $rule)
                                || TM_RRule::match_time_rule($two, $rule)) {
                                $next = Date::next_time($tmp);
                                foreach (['user', 'lab', 'group'] as $k) {
                                    if (!$rule['select_user_mode_'.$k] || !$rule['select_user_mode_'.$k] == 'on') continue;
                                    switch ($k) {
                                        case 'user':
                                            foreach ((array)$rule['users'] as $id => $name) {
                                                $user = O('user', $id);
                                                if ($user->is_active() && $card_no = $user->card_no) {
                                                    $cards[$card_no] = true;
                                                }
                                            }
                                            break;
                                        case 'lab':
                                            foreach ((array)$rule['labs'] as $id => $name) {
                                                $users = Q("user[lab_id=$id][atime>0]");
                                                foreach ($users as $user) {
                                                    if ($card_no = $user->card_no) {
                                                        $cards[$card_no] = true;
                                                    }
                                                }
                                            }
                                            break;
                                        case 'group':
                                            foreach ((array)$rule['groups'] as $id => $value) {
                                                $g = O('tag_group', $id);
                                                foreach (Q("{$g} lab[atime>0]") as $lab) {
                                                    foreach (Q("{$lab} user[atime>0]") as $user) {
                                                        if ($card_no = $user->card_no) {
                                                            $cards[$card_no] = true;
                                                        }
                                                    }
                                                }
                                            }
                                            break;
                                        default:
                                            // Nothing
                                            break;
                                    }
                                }
                            }
                        }
                        $tmp = Date::next_time($tmp);
                    }
                }

            }
        }

        // 计算关联的所有仪器预约的人员卡号对应进去表
        $equipments = Q("{$door}<asso equipment");
        $ret = [];

        $db = Database::factory();

        if ($equipments->total_count()) foreach ($equipments as $equipment) {
            /* 该仪器允许预约 */
            if ($equipment->accept_reserv) {
                $before = $equipment->slot_card_ahead_time * 60;
                $after = $equipment->slot_card_delay_time * 60;
                $reservs = Q("eq_reserv[equipment={$equipment}][dtstart=$dtstart~$dtend]");
                foreach ($reservs as $r) {
                    $user = $r->user;
                    if ($card_no = $user->card_no) {
                        !is_array($ret[$card_no]) and $ret[$card_no] = [];
                        $ret[$card_no][] = ['dtstart' => $r->dtstart - $before, 'dtend' => $r->dtend + $after];
                    }
                }

                foreach (Q("{$equipment} user.incharge") as $incharge) {
                    if ($card_no = $incharge->card_no) {
                        !is_array($ret[$card_no]) and $ret[$card_no] = [];
                        $ret[$card_no][] = ['dtstart' => $dtstart, 'dtend' => $dtend];
                    }
                }

                $dtends = $dtstart + (30 * 86400);
                $sql = "SELECT `u`.`card_no`, COUNT(`r`.`id`) AS `count` FROM `eq_reserv` AS `r`
                INNER JOIN `user` AS `u` ON `r`.`user_id` = `u`.`id`
                WHERE `u`.`card_no` IS NOT NULL AND `u`.`card_no` <> ''
                AND `r`.`dtend` BETWEEN {$dtstart} AND {$dtends}
                AND `r`.`equipment_id` = {$equipment->id}
                GROUP BY `u`.`card_no` ORDER BY `count` DESC LIMIT 0, 20";
                $result = $db->query($sql);
                $assoc = $result ? $result->rows('assoc') : [];

                foreach ($assoc as $data) {
                    if (in_array($data['card_no'], $cards)) {
                        $ret[$data['card_no']][] = ['dtstart' => $dtstart, 'dtend' => $dtend];
                    }
                }
            }
        }

        $meetings = Q("{$door}<asso meeting");
        if ($meetings->total_count()) foreach ($meetings as $meeting) {
            $before = $meetings->ahead_time * 60;
            $reservs = Q("me_reserv[meeting={$meeting}][dtstart=$dtstart~$dtend]");
            foreach ($reservs as $r) {
                $user = $r->user;
                if ($card_no = $user->card_no) {
                    !is_array($ret[$card_no]) and $ret[$card_no] = [];
                    $ret[$card_no][] = ['dtstart' => $r->dtstart - $before, 'dtend' => $r->dtend];
                }
            }

            foreach (Q("{$meeting} user.incharge") as $incharge) {
                if ($card_no = $incharge->card_no) {
                    !is_array($ret[$card_no]) and $ret[$card_no] = [];
                    $ret[$card_no][] = ['dtstart' => $dtstart, 'dtend' => $dtend];
                }
            }
        }

        return $ret ?: $ret[-1] = true;
    }

}
