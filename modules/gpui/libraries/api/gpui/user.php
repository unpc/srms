<?php

class API_GPUI_User extends API_Common
{
    /**
     * 平台人员分布
     *
     * @return array
     */
    function stat()
    {
        $this->_ready('gpui');

        $data = [];

        // 学生数量：人员类型为学生的总数
        $data['student'] = Q('user[member_type=0~4]')->total_count();
        // 课题组PI数量：课题组内负责人的数量
        $data['pi'] = Q('lab user.pi')->total_count();
        // 仪器管理员数量：在仪器信息内负责人内的人员数量
        $data['eqIncharge'] = Q('equipment user.incharge')->total_count();
        // 其他管理员：角色中有任意管理权限的用户数量 - PI数量 - 机主数量
        $data['other'] = Q('perm role user:not(lab user.pi):not(equipment user.incharge)')->total_count();
        // 校外用户数量：组织机构为非校内的用户总数
        $root = Tag_Model::root('group');
        $inner_group_name = Config::get('lab.inner_group_name');
        $inner_group = Q("tag[root={$root}][parent={$root}][name={$inner_group_name}]");
        $data['total'] = Q('user')->total_count();
        $data['inner'] = Q("{$inner_group} user")->total_count();;
        $data['outer'] = $data['total'] - $data['inner'];
        return $data;
    }

    /**
     * 人才队伍建设
     *
     * @return void
     */
    function userTypeStat()
    {
        $this->_ready('gpui');
        $data = [
            'undergraduate' => Q('user[member_type=0]')->total_count(),
            'graduate' => Q('user[member_type=1]')->total_count(),
            'doctor' => Q('user[member_type=2]')->total_count(),
            'teacher' => Q('user[member_type=10,11,12]')->total_count(),
        ];
        $data['other'] = Q('user')->total_count() - array_sum($data);
        return $data;
    }

    /**
     * 用户使用排行
     *
     * @return void
     */
    function timeRank($num = 10, $start = 0, $end = 0)
    {
        $this->_ready('gpui');
        $num > 100 and $num = 100;
        if (!$end) $end = Date::time();

        $db = Database::factory();

        // 只统计一段时间的使用时长, 有超过这段时间的记录只按这段时间计算
        // 比如统计上个月的使用时长, 同时有条使用记录长达一年, 只统计这条记录落入start~end的范围
        if ($end && $start) {
            $sum = "SUM(LEAST({$end}, `r`.`dtend`) - GREATEST({$start}, `r`.`dtstart`))";
        } elseif ($end) {
            $sum = "SUM(LEAST({$end}, `r`.`dtend`) - `r`.`dtstart`)";
        } elseif ($start) {
            $sum = "SUM(`r`.`dtend` - GREATEST({$start}, `r`.`dtstart`))";
        } else {
            $sum = "SUM(`r`.`dtend` - `r`.`dtstart`)";
        }
        $SQL = "SELECT `r`.`user_id`, {$sum} as `sum`" .
            " FROM `eq_record` as `r`" .
            " WHERE `r`.`dtend` > 0 AND `r`.`dtend` BETWEEN %start AND %end AND `user_id` <> 0" .
            " GROUP BY `r`.`user_id`" .
            " ORDER BY `sum` DESC LIMIT 0, %num";

        $rows = $db->query(strtr($SQL, [
            '%num' => (int) $num,
            '%start' => (int) $start,
            '%end' => (int) $end,
        ]))->rows();

        $users = [];

        foreach ($rows as $row) {
            $u = O('user', $row->user_id);
            $users[] = [
                'id' => $u->id,
                'name' => H($u->name),
                'time' => (float) sprintf('%.2f', ($row->sum / 3600))
            ];
        }

        return $users;
    }
    /**
     * 累计服务全国用户数
     *
     * @return void
     */
    function userGroupStat()
    {
        $this->_ready('gpui');

        $root = Tag_Model::root('group');
        $inner_group_name = Config::get('lab.inner_group_name');
        $inner_group = Q("tag[root={$root}][parent={$root}][name={$inner_group_name}]")->current();

        $data = [
            'number' => Q("({$inner_group}) user")->total_count()
        ];

        return $data;
    }

    /**
     * 用户行为分析
     * 昨日最晚关机时间：昨日使用记录结束时间最晚时间 
     * 当日最早开机时间：当日使用记录开始最早时间 
     * 学生使用预约等待路径最长时间：该用户实际使用开始时间-预约开始时间，取一个最长时间，取一个平均时间
     *  用户单次平均使用时长：使用者为非机主，使用总时长/使用总次数
     */
    public function userUseTimeStat()
    {
        $current = Date::get_day_start();
        $nex_current = Date::next_time($current, 1, 'd');
        $pre_current = Date::prev_time($current, 1, 'd');
        $db = Database::factory();
        //当日最早开机时间：当日使用记录开始最早时间
        $first = Q("eq_record[dtstart<{$nex_current}][dtstart >= {$current}]:sort(dtstart ASC)")->current();
        // 昨日最晚关机时间：昨日使用记录结束时间最晚时间
        $later = Q("eq_record[dtend>={$pre_current}][dtend < {$current}]:sort(dtstart DESC)")->current();
        //学生使用预约等待路径最长时间：该用户实际使用开始时间-预约开始时间，取一个最长时间，取一个平均时间
        $times = $db->query("SELECT Avg(eq_record.dtstart-eq_reserv.dtstart) as avg_time, max(eq_record.dtstart-eq_reserv.dtstart) as long_time FROM eq_record inner join (eq_reserv) on (eq_record.reserv_id=eq_reserv.id) ")->rows('assoc');
        //用户单次平均使用时长：使用者为非机主，使用总时长/使用总次数
        $uses = $db->query("SELECT AVG(dtend-dtstart) as avg_time FROM eq_record WHERE dtend>dtstart AND user_id not in (select _r_user_equipment.id1 from _r_user_equipment where _r_user_equipment.id2=eq_record.equipment_id and _r_user_equipment.type='incharge')")->rows('assoc');
        $data = [
            "first" => $first->dtstart ? $first->dtstart : 0,
            "later" => $later->dtend ? $later->dtend : 0,
            "avg" => count($times) > 0 ? (float) sprintf('%.2f', $times[0]['avg_time']) : 0,
            "long" => count($times) > 0 ? (int)$times[0]['long_time'] : 0,
            "use" => count($uses) > 0 ? (float) sprintf('%.2f', $uses[0]['avg_time']) : 0,
        ];

        return    $data;
    }

    public static function loginCount()
    {
        $this->_ready();
        return [
            'total' => Lab::get('login_plus.login_count') ?: 0
        ];
    }
}
