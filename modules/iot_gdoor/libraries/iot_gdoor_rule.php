<?php

class Iot_Gdoor_Rule
{
    public static function on_eq_reserv_saved($e, $reserv, $old_data, $new_data)
    {
        self::assoRuleEqReserv($reserv, 'add');
    }

    public static function on_eq_reserv_deleted($e, $reserv)
    {
        self::assoRuleEqReserv($reserv, 'delete');
    }

    static function on_iot_gdoor_equipment_connect($e, $door, $equipment, $type)
    {
        if ($type != 'asso') {
            return;
        }
        self::assoRuleEqIncharge($door);
    }

    static function on_iot_gdoor_equipment_disconnect($e, $door, $equipment, $type)
    {
        if ($type != 'asso') {
            return;
        }
        self::assoRuleEqIncharge($door);
    }

    static function on_user_equipment_connect($e, $equipment, $user, $type)
    {
        if ($type != 'incharge') {
            return;
        }
        self::assoRuleEqIncharge($equipment);
    }

    static function on_user_equipment_disconnect($e, $equipment, $user, $type)
    {
        if ($type != 'incharge') {
            return;
        }
        self::assoRuleEqIncharge($equipment);
    }


    public static function assoRuleEqIncharge($obj)
    {
        if ($obj->name() == 'iot_gdoor') {
            $doors = [$obj];
        } elseif ($obj->name() == 'equipment') {
            $doors = Q("{$obj} iot_gdoor.asso");
        } else {
            return;
        }
        $now = time();
        foreach ($doors as $door) {
            $id = $door->gdoor_id;
            $door_rules = [];
            foreach (Q("{$door}<asso equipment") as $equipment) {
                foreach (Q("{$equipment} user.incharge") as $user) {
                    if (!$user->gapper_id) {
                        continue;
                    }
                    $door_rules[] = [
                        'userId' => $user->gapper_id,
                        'userName' => $user->name,
                        'source' => (string) $equipment,
                        'startTime' => 0,
                        'endTime' => 0
                    ];
                }
            }
            Remote_Door::pushRule($id, $door_rules, 'eq_incharge');
        }
    }

    public static function assoRuleEqReserv($reserv, $action = 'add')
    {
        $equipment = $reserv->equipment;
        $ahead_time = $equipment->slot_card_ahead_time * 60;
        $delay_time = $equipment->slot_card_delay_time * 60;
        if (!$reserv->user->gapper_id) {
            return;
        }

        foreach (Q("{$equipment} iot_gdoor.asso") as $door) {
            $id = $door->gdoor_id;

            $rule = [
                'userId' => $reserv->user->gapper_id,
                'userName' => $reserv->user->name,
                'source' => (string) $reserv,
                'action' => $action,
                'startTime' => max($now, $reserv->dtstart - $ahead_time),
                'endTime' => $reserv->dtend + $delay_time
            ];
            Remote_Door::pushRule($id, [$rule], 'eq_reserv');
        }
    }
}
