


<?php


class API_GPUI_Me_Reserv extends API_Common
{
    public function meRservList($params = [], $start = 0, $num = 0)
    {
        if (!Module::is_installed('meeting')) {
            return [];
        }

        $this->_ready('gpui');

        if ($params["doorAddr"]) {
            $door = Q("door[in_addr={$params["doorAddr"]}|out_addr={$params["doorAddr"]}]")->current();
            if (!$door->id) {
                return [];
            }

            $selector = "{$door}<asso meeting";
        } else {
            $selector = "door<asso meeting";
        }

        if ($params["ids"]) {
            $selector .= "[id={$params["ids"]}]";
        }

        $data = [];

        $now = Date::time();
        if (!($start == 0 && $num == 0)) {
            $reservs = Q("cal_component<component me_reserv[dtstart>={$now}]:sort(dtstart D)")->limit($start, $num);
        } else {
            $reservs = Q("cal_component<component me_reserv[dtstart>={$now}]:sort(dtstart D)");
        }
        //实时显示各会议室预约情况（房间号、主讲人、使用时段、当前状态）
        foreach ($reservs as $reserv) {
            $meeting = $reserv->meeting;
            $calendar = Q("calendar[parent_name=meeting][parent_id=$meeting->id]")->current();
            if ($calendar->id) {
                $status = Q("cal_component[calendar_id=$calendar->id|me_room={$meeting}][dtstart<$now][dtend>$now]")->current();
            }
            if ($status->id) {
                $meeting_status = '使用中';
            } else {
                $meeting_status = '空闲';
            }
            $data[] = [
                "name" => $meeting->name,
                "speaker" => $reserv->component->organizer->name,
                "dtstart" => $reserv->dtstart,
                "dtend" => $reserv->dtend,
                "status" => $meeting_status
            ];
        };

        return $data;
    }

    public function reservList($dtstart = 0, $dtend = 0, $params = [])
    {
        if (!Module::is_installed('meeting')) {
            return [];
        }

        $this->_ready('gpui');

        if (!$dtstart) {
            $dtstart = Date::get_day_start();
        }
        if (!$dtend) {
            $dtend = Date::get_day_end();
        }

        $pre_selector = [];
        $selector = "cal_component[dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]";
        if ($params['id']) {
            $meeting = O('meeting', (int)$params['id']);
            $pre_selector[] = "{$meeting}<parent calendar";
        }

        $limit_start = $params['limit'][0] ?? 0;
        $limit_length = $params['limit'][1] ?? 100;
        $selector .= ':sort(dtstart A)';
        $selector .= ":limit($limit_start, $limit_length)";
        if (count($pre_selector)) {
            $selector = '(' . join(', ', $pre_selector) . ') ' . $selector;
        }

        $data = [];
        foreach (Q($selector) as $component) {
            $data[] = [
                'name' => H($component->name),
                'user_name' => H($component->organizer->name),
                'user_id' => $component->organizer->id,
                'avatar' => $component->organizer->icon_url('128'),
                'start' => $component->dtstart,
                'end' => $component->dtend,
            ];
        }

        return $data;
    }
}
