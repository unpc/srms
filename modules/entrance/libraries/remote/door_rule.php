<?php

class Remote_Door_Rule
{
    public static function on_eq_reserv_saved($e, $reserv, $old_data, $new_data)
    {
        self::assoRuleEqReserv($reserv, 'add');
    }

    public static function on_eq_reserv_deleted($e, $reserv)
    {
        self::assoRuleEqReserv($reserv, 'delete');
    }

    public static function assoRuleEqReserv($reserv, $action = 'add')
    {
        $equipment = $reserv->equipment;
        $ahead_time = $equipment->slot_card_ahead_time * 60;
        $delay_time = $equipment->slot_card_delay_time * 60;
        // 如果仪器关联了门禁
        foreach (Q("{$equipment} door.asso") as $door) {
            if ($door->voucher) {
                $rule = [
                    'userId' => $reserv->user->gapper_id ?: $reserv->user->id,
                    'userName' => $reserv->user->name,
                    'source' => (string) $reserv,
                    'action' => $action,
                    'startTime' => max($now, $reserv->dtstart - $ahead_time),
                    'endTime' => $reserv->dtend + $delay_time
                ];
                self::pushRule($door->voucher, [$rule], 'eq_reserv');
            }
        }
    }

    static function on_equipment_door_connect($e, $equipment, $door, $type)
    {
        if ($type != 'asso') {
            return;
        }
        self::assoRuleEqIncharge($door, 'add');
    }

    static function on_equipment_door_disconnect($e, $equipment, $door, $type)
    {
        if ($type != 'asso') {
            return;
        }
        self::assoRuleEqIncharge($door, 'delete');
    }

    static function on_user_equipment_connect($e, $equipment,  $user, $type)
    {
        if ($type != 'incharge') {
            return;
        }
        self::assoRuleEqIncharge($equipment, 'add');
    }

    static function on_user_equipment_disconnect($e, $equipment,  $user, $type)
    {
        if ($type != 'incharge') {
            return;
        }
        self::assoRuleEqIncharge($equipment, 'delete');
    }


    public static function assoRuleEqIncharge($obj, $action = 'add')
    {
        if ($obj->name() == 'door') {
            $doors = [$obj];
        } elseif ($obj->name() == 'equipment') {
            $doors = Q("{$obj} door.asso");
        } else {
            return;
        }
        $now = time();
        foreach ($doors as $door) {
            $door_rules = [];
            if (!$door->voucher) {
                continue;
            }
            foreach (Q("{$door}<asso equipment") as $equipment) {
                foreach (Q("{$equipment} user.incharge") as $user) {
                    $door_rules[] = [
                        'userId' => $user->gapper_id ?: $user->id,
                        'userName' => $user->name,
                        'source' => (string) $equipment,
                        'action' => $action,
                        'startTime' => 0,
                        'endTime' => 0
                    ];
                }
            }

            self::pushRule($door->voucher, $door_rules, 'eq_incharge');
        }
    }

    public static function on_me_reserv_saved($e, $reserv, $old_data, $new_data)
    {
        self::assoRuleMeReserv($reserv, 'add');
    }

    public static function on_me_reserv_deleted($e, $reserv)
    {
        self::assoRuleMeReserv($reserv, 'delete');
    }

    public static function assoRuleMeReserv($reserv, $action = 'add')
    {
        $meeting = $reserv->meeting;
        $ahead_time = $meeting->ahead_time * 60;
        foreach (Q("{$meeting} door.asso") as $door) {
            if ($door->voucher) {
                $rule = [
                    'userId' => $reserv->user->gapper_id ?: $reserv->user->id,
                    'userName' => $reserv->user->name,
                    'source' => (string) $reserv,
                    'action' => $action,
                    'startTime' => max($now, $reserv->dtstart - $ahead_time),
                    'endTime' => $reserv->dtend
                ];
                self::pushRule($door->voucher, [$rule], 'me_reserv');
            }
        }
    }

    static function on_meeting_door_connect($e, $meeting, $door, $type)
    {
        if ($type != 'asso') {
            return;
        }
        self::assoRuleMeIncharge($door, 'add');
    }

    static function on_meeting_door_disconnect($e, $meeting, $door, $type)
    {
        if ($type != 'asso') {
            return;
        }
        self::assoRuleMeIncharge($door, 'delete');
    }

    static function on_user_meeting_connect($e, $meeting,  $user, $type)
    {
        if ($type != 'incharge') {
            return;
        }
        self::assoRuleMeIncharge($meeting, 'add');
    }

    static function on_user_meeting_disconnect($e, $meeting,  $user, $type)
    {
        if ($type != 'incharge') {
            return;
        }
        self::assoRuleMeIncharge($meeting, 'delete');
    }


    public static function assoRuleMeIncharge($obj, $action = 'add')
    {
        if ($obj->name() == 'door') {
            $doors = [$obj];
        } elseif ($obj->name() == 'meeting') {
            $doors = Q("{$obj} door.asso");
        } else {
            return;
        }
        $now = time();
        foreach ($doors as $door) {
            $door_rules = [];
            if (!$door->voucher) {
                continue;
            }
            foreach (Q("{$door}<asso meeting") as $meeting) {
                foreach (Q("{$meeting} user.incharge") as $user) {
                    $door_rules[] = [
                        'userId' => $user->gapper_id ?: $user->id,
                        'userName' => $user->name,
                        'source' => (string) $meeting,
                        'action' => $action,
                        'startTime' => 0,
                        'endTime' => 0
                    ];
                }
            }

            self::pushRule($door->voucher, $door_rules, 'me_incharge');
        }
    }

    public static function pushRule($id, $rule, $type = '')
    {
        $iot_door = new Iot_door();
        $params = [
            'door_id' => $id,
            'rule' => $rule,
            'type' => $type
        ];
        try {
            $result = $iot_door::doorRuleRemote($params);
        } catch (Exception $e) {
            error_log('等待规则推送结果失败可能是超时了,iot-door里面日志正常就可以');
        }
    }
}
