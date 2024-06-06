<?php

class EQ_Reserv_Time_Model extends ORM_Model {
    // 是否适用于该人员
    public function check_user($user) {
        // 当没设置适用人员的时候 代表该规则适用于所有人
        if ($this->controlall) return true;

        $users = array_keys(json_decode($this->controluser, TRUE) ?? []);
        if (in_array($user->id, $users)) return true;

        $labs = implode(',', array_keys(json_decode($this->controllab, TRUE) ?? []));
        $user_labs = Q("$user lab[id={$labs}]")->total_count();
        if ($user_labs > 0) return true;

        $groups = implode(',', array_keys(json_decode($this->controlgroup, TRUE) ?? []));
        $user_groups = Q("$user tag_group[id={$groups}]")->total_count();
        if ($user_groups > 0) return true;

        return false;
    }

    // 是否在规定的工作时间内
    public function check_time($date, $time) {
        $num = $this->num;
        $diff1 = date_create(date('Y-m-d', $date));
        $diff2 = date_create(date('Y-m-d', $this->ltstart));
        $diff = date_diff($diff1, $diff2);
        $days = explode(',', $this->days);
        switch ($this->type) {
            case WT_RRule::RRULE_DAILY:
                if ($diff->d % $this->num == 0) {
                    if ($time >= $this->dtstart && $time <= $this->dtend) {
                        return true;
                        break;
                    }
                }
                break;
            case WT_RRule::RRULE_WEEKDAY:
            case WT_RRule::RRULE_WEEKEND_DAY:
            case WT_RRule::RRULE_WEEKLY:
                $diff->w = abs(date('W', $date) - date('W', $this->dtstart));
                if (($diff->w % $this->num) == 0 && in_array(date('w', $date), $days)) {
                    if ($time >= $this->dtstart && $time <= $this->dtend) {
                        return true;
                        break;
                    }
                }
                break;
            case WT_RRule::RRULE_MONTHLY:
                if (($diff->m % $this->num) == 0 && in_array(date('d', $date), $days)) {
                    if ($time >= $this->dtstart && $time <= $this->dtend) {
                        return true;
                        break;
                    }
                }
                break;
            case WT_RRule::RRULE_YEARLY:
                if (($diff->y % $this->num) == 0 && in_array(date('m', $date), $days)) {
                    if ($time >= $this->dtstart && $time <= $this->dtend) {
                        return true;
                        break;
                    }
                }
                break;
            default:
            	return false;
                break;
        }
        return false;
    }

    // 按照规则进行时间切分 以便和其他工作时间进行融合
    // ↑这个注释是我按照我4年前的写代码补充的
    public function clipping($times, $date) {
        $start = strtotime(date('Y-m-d', $date) . date('H:i:s', $this->dtstart));
        $end = strtotime(date('Y-m-d', $date) . date('H:i:s', $this->dtend));
        if ($start == $end) return $times;

        $flag = false;

        if (!$times) {
            $times[] = [
                'start' => $start,
                'end' => $end,
            ];
            return $times;
        }
        
        foreach ($times as &$time) {
            if (!($time['start'] > $end || $time['end'] < $start)) {
                $flag = true;
                $time['start'] = min($time['start'], $start);
                $time['end'] = max($time['end'], $end);
            }

            if (!$flag) {
                $times[] = [
                    'start' => $start,
                    'end' => $end,
                ];
            }
        }
        return $times;
    }
}
