<?php

class Iot_Gdoor_Access
{
    public static function access_iot_gdoor($e, $door, $params)
    {
        $equipments = Q("{$door}<asso equipment");
        $user = $params[0];

        foreach ($equipments as $equipment) {
            if ($user->is_allowed_to('管理使用', $equipment)) {
                $e->return_value = true;
                return false;
            }
            // 门牌关联多个仪器, 有一个仪器满足上机条件则可开门(此hook return true)
            // 若仪器仅接收送样, 不可开门 (不用进行cannot_access判断)
            if ($equipment->accept_sample && !$equipment->accept_reserv) {
                continue;
            }
            $now = time();
            $dtstart = $now - $equipment->slot_card_delay_time * 60;
            $dtend = $now + $equipment->slot_card_ahead_time * 60;

            if (!$equipment->cannot_access($user, $dtstart, $dtend)) {
                $e->return_value = true;
                return false;
            }
        }
    }

    public static function access_iot_gdoor_meeting($e, $door, $params)
    {
        if (!Module::is_installed('meeting')) {
            return;
        }
        $meetings = Q("{$door}<asso meeting");
        $user = $params[0];

        foreach ($meetings as $meeting) {
            if ($user->is_allowed_to('关联门禁', $meeting)) {
                $e->return_value = true;
                return false;
            }

            $end = Date::time();
            $start = $end + $meeting->ahead_time * 60;
            $reserv = Q("me_reserv[meeting={$meeting}][dtstart~dtend={$start}|dtstart~dtend={$end}]")->current();

            if (
                $reserv->user->id == $user->id
                || $reserv->type == 'all'
            ) {
                $e->return_value = true;
                return false;
            }

            $roles = @json_decode($reserv->roles, true) ?: [];
            $groups = @json_decode($reserv->groups, true) ?: [];
            $users = @json_decode($reserv->users, true) ?: [];

            if (array_intersect_key($user->roles(), $roles)) {
                $e->return_value = true;
                return true;
            }

            $user_groups = Q("{$user} tag_group")->to_assoc('id', 'name');
            if (array_intersect_key($user_groups, $groups)) {
                $e->return_value = true;
                return true;
            }

            if (array_key_exists($user->id, $users)) {
                $e->return_value = true;
                return true;
            }
        }
    }
}
