<?php


class API_GPUI_Meeting extends API_Common
{
    public function getUserurl($user, $size=128)
    {
        $size = $user->normalize_icon_size($size);
        $icon_file = $user->icon_file($size);

        if (!$icon_file) {
            return "";
        }
        return Config::get('system.base_url').Cache::cache_file($icon_file).'?_='.$user->mtime;
    }
    public function meetingList($params = [])
    {
        if (!Module::is_installed('meeting')) {
            return [];
        }

        $this->_ready('gpui');

        if (!$params["doorAddr"]) {
            return [];
        }

        $door = Q("door[in_addr={$params["doorAddr"]}|out_addr={$params["doorAddr"]}]")->current();
        if (!$door->id) {
            return [];
        }

        $selector = "{$door}<asso meeting";

        if ($params["ids"]) {
            $selector .= "[id={$params["ids"]}]";
        }

        // 获取会议室
        $now = Date::time();
        $meeting_calendars = Q("cal_component[dtstart~dtend={$now}] calendar[parent_name=meeting]")->to_assoc('id', 'parent_id');
        $lab_calendars = Q("cal_component[dtstart~dtend={$now}][me_room]")->to_assoc('id', 'me_room_id');
        $calendars = array_merge($meeting_calendars, $lab_calendars);
        $calendar_ids = count($calendars) ? join(', ', $calendars) : 0;

        $next = 1;
        //使用中1 空闲2
        if ($params['status'] == Meeting_Model::STATUS_USING) {
            $next_selector = "[id={$calendar_ids}]";
            $next = 3;
        } elseif ($params['status'] == Meeting_Model::STATUS_AVAILABLE) {
            $next_selector = ":not(meeting[id={$calendar_ids}])";
        }

        $selector .= $next_selector;

        $data = [];

        $item = Q("{$selector}")->current();
        if (!$item->id) {
            return [];
        }
        //foreach (Q("{$selector}") as $item) {
        // 下一个预约者的信息
        $reserv_next = [];
        $components = Q("me_reserv[meeting={$item}][dtstart>{$now}]:sort(dtstart A):limit(1) cal_component.component:sort(dtstart A):limit({$next})");
        foreach ($components as $component) {
            if ($component->id) {
                $reserv_next[]  = [
                        "name" => $component->name,
                        "dtstart" => (int)$component->dtstart,
                        "dtend" => (int)$component->dtend
                    ];
            }
        }
        // 获取当前会议室预约的信息
        $component = Q("me_reserv[meeting={$item}][dtstart<{$now}][dtend>={$now}]:sort(dtstart D):limit(1) cal_component.component")->current();
        if ($component->id) {
            $reserv = O('me_reserv', ['component' => $component]);
            $type = $reserv->type ? : 'all';
            $indoor = DC_Record_Model::IN_DOOR;
            $db = Database::factory();
            $sql = "";
            if ($type == 'all') {
                $sql = "select user.* , (select count(*) from dc_record where user_id=user.id and time>={$reserv->dtstart} and direction={$indoor} and door_id={$door->id}) as isindoor from user" ;
                $rows = $db->query($sql)->rows();
            } else {
                $roles = @json_decode($reserv->roles, true);
                $groups = @json_decode($reserv->groups, true);
                $users = @json_decode($reserv->users, true);
                $_r_user_role = "";
                foreach ($roles as $role_id => $value) {
                    if ($role_id > 0) {
                        if ($_r_user_role !== "") {
                            $_r_user_role .= " or ";
                        }
                        $_r_user_role .= " _r1.id2={$role_id} ";
                    }
                }
                if ($_r_user_role != "") {
                    if ($sql != "") {
                        $sql .= " union ";
                    }
                    $sql .= "select user.* , (select count(*) from dc_record where user_id=user.id and time>={$reserv->dtstart}  and direction={$indoor} and door_id={$door->id}) as isindoor from user
                        inner join (_r_user_role as _r1) on (_r1.id1=user.id and ({$_r_user_role})) " ;
                }

                $_r_user_tag = "";
                foreach ($groups as $group_id => $value) {
                    if ($group_id > 0) {
                        if ($_r_user_tag !== "") {
                            $_r_user_tag .= " or ";
                        }
                        $_r_user_tag .= " _r2.id2={$group_id} ";
                    }
                }
                if ($_r_user_tag != "") {
                    if ($sql != "") {
                        $sql .= " union ";
                    }
                    $sql .= "select user.* , (select count(*) from dc_record where user_id=user.id and time>={$reserv->dtstart}  and direction={$indoor} and door_id={$door->id}) as isindoor from user
                        inner join (_r_user_tag as _r2) on (_r2.id1=user.id and ({$_r_user_tag})) " ;
                }

                $_r_user = "";
                foreach ($users as $user_id => $value) {
                    if ($user_id > 0) {
                        if ($_r_user !== "") {
                            $_r_user .= " or ";
                        }
                        $_r_user .= " user.id={$user_id} ";
                    }
                }
                if ($_r_user != "") {
                    if ($sql != "") {
                        $sql .= " union ";
                    }
                    $sql .= "select user.* , (select count(*) from dc_record where user_id=user.id and time>={$reserv->dtstart} and direction={$indoor} and door_id={$door->id}) as isindoor from user where ($_r_user)" ;
                }
            }
            $rows = $db->query($sql)->rows();
            $userList = [];
            foreach ($rows as $row) {
                $userList[] = [
                        'img' => $this->getUserurl(o("user", $row->id), $size=128),
                        'name' => H($row->name),
                        'isindoor' => $row->isindoor
                    ];
            }
            $reserv_current = [
                    "current_time" => $now,
                    "name" => H($component->name),
                    "speaker" => $component->organizer->name,
                    "userList" => $userList,
                    "dtstart" => (int)$component->dtstart,
                    "dtend" => (int)$component->dtend
                ];
        }


        return  [
                "name" => $item->name,
                "seats" => $item->seats,
                "time" => Date::time(),
                "reserv_next" => $reserv_next,
                "reserv_current" => $reserv_current,
            ];


        # }

        #return $data;
    }
}
