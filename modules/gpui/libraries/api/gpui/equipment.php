<?php

/**
 * 全站仪器概况
 */
class API_GPUI_Equipment extends API_Common
{
    /**
     * 设备类型占比
     *
     * @return void
     */
    public function getEquipmentTags($params = [])
    {
        $this->_ready('gpui');
        $cat_parent = $cat_root = Tag_Model::root('equipment');

        if ($params['parent_id']) {
            $cat_parent = o($cat_root->name(), $params['parent_id']);
        }

        $tags = Q($cat_root->name() . "[parent={$cat_parent}][root={$cat_root}]");

        $dtstart = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? 0 : $params['dtstart'];
        $dtend = (!$params['dtend'] || !is_numeric($params['dtend'])) ? Date::time() : $params['dtend'];

        $data = [];
        $eq_counts = 0;
        foreach ($tags as $child) {
            $data[$child->id] = [
                'id' => $child->id,
                'name' => H($child->name),
                'eq_count' => Q("{$child} equipment[ctime>={$dtstart}][ctime<={$dtend}]")->total_count()
            ];
            $eq_counts += $data[$child->id]['eq_count'];
        }

        if ($params['parent_id']) {
            $data[] = [
                'name' => '其他',
                'eq_count' => Q("{$cat_parent} equipment[ctime>={$dtstart}][ctime<={$dtend}]")->total_count() - $eq_counts
            ];
        } else {
            $data[] = [
                'name' => '其他',
                'eq_count' => Q("equipment[ctime>={$dtstart}][ctime<={$dtend}]")->total_count() - $eq_counts
            ];
        }
        return $data;
    }

    /**
     * 设备数据总览
     *
     * @param array $params
     * @return array
     */
    public function getStat($params = [])
    {
        $this->_ready('gpui');

        $dtstart = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? 0 : $params['dtstart'];
        $dtend = (!$params['dtend'] || !is_numeric($params['dtend'])) ? Date::time() : $params['dtend'];

        $pre_selector = "";
        if ($params['group_id']) {
            $pre_selector = "tag_group[id=" . $params['group_id'] . "]";
        }

        $db = Database::factory();

        $summary = [];
        // 设备总数=统计时段内，系统设备总数（含故障设备、报废设备数）
        $summary['totalCount'] = Q("$pre_selector equipment[ctime>={$dtstart}][ctime<={$dtend}]")->total_count() ?: 0;
        $summary['inchargeEqs'] = Q("(" . join(',', ["user<incharge", $pre_selector]) . ") equipment[ctime>={$dtstart}][ctime<={$dtend}]")->total_count() ?: 0;
        $summary['inchargeCount'] = Q("$pre_selector equipment[ctime>={$dtstart}][ctime<={$dtend}] user.incharge")->total_count() ?: 0;
        // 设备总价值=统计时段内，系统设备单价总和（含故障设备、报废设备数）
        $summary['totalPrice'] = Q("$pre_selector equipment[ctime>=$dtstart][ctime<=$dtend][price>0]")->sum('price') ?: 0;
        // 贵重仪器率=统计时段内，仪器单价大于等于40万仪器总台数/ 系统仪器总台数*100%
        $summary['preciousEqs'] = Q("$pre_selector equipment[price>=400000]")->total_count() ?: 0;

        // 开放共享率=统计时段内，（系统内开放了预约仪器台数+开放送样的仪器台数-同时开放预约和送样的仪器台数）/ 系统仪器总台数*100%
        $reserv_eqs = Q("$pre_selector equipment[ctime>={$dtstart}][ctime<={$dtend}][accept_reserv=1][accept_sample=0]")->total_count();
        $sample_eqs = Q("$pre_selector equipment[ctime>={$dtstart}][ctime<={$dtend}][accept_sample=1][accept_reserv=0]")->total_count();
        $summary['shareEqs'] = $reserv_eqs + $sample_eqs;

        // 服务总人数=统计时段内，仪器预约+送样+使用的总人数，（人员去重显示，如：用户1有多条记录，总人数+1，包含机主）
        $user_ids = [];
        // TODO: nankai数据cgi超时
        // $SQL = "SELECT DISTINCT `user_id` FROM `eq_record` WHERE `dtstart` BETWEEN %start AND %end";
        $SQL = "SELECT DISTINCT `user_id` FROM `eq_record`";
        $rows = $db->query(strtr($SQL, [
            '%start' => (int) $dtstart,
            '%end' => (int) $dtend,
        ]))->rows();
        foreach ($rows as $row) {
            $user_ids[$row->user_id] = $row->user_id;
        }
        // TODO: nankai数据cgi超时
        // $SQL = "SELECT DISTINCT `sender_id` FROM `eq_sample` WHERE `ctime` BETWEEN %start AND %end";
        $SQL = "SELECT DISTINCT `sender_id` FROM `eq_sample`";
        $rows = $db->query(strtr($SQL, [
            '%start' => (int) $dtstart,
            '%end' => (int) $dtend,
        ]))->rows();
        foreach ($rows as $row) {
            $user_ids[$row->sender_id] = $row->sender_id;
        }
        // TODO: nankai数据cgi超时
        // $SQL = "SELECT DISTINCT `user_id` FROM `eq_reserv` WHERE `ctime` BETWEEN %start AND %end";
        $SQL = "SELECT DISTINCT `user_id` FROM `eq_reserv`";
        $rows = $db->query(strtr($SQL, [
            '%start' => (int) $dtstart,
            '%end' => (int) $dtend,
        ]))->rows();
        foreach ($rows as $row) {
            $user_ids[$row->user_id] = $row->user_id;
        }

        $summary['totalUsers'] = $summary['totalService'] = count($user_ids);
        // 服务总人次=统计时段内，仪器送样记录条数+使用记录条数
        $SQL = "SELECT COUNT(`id`) FROM `eq_record` WHERE `dtstart` BETWEEN %start AND %end";
        $cnt = $db->value(strtr($SQL, [
            '%start' => (int) $dtstart,
            '%end' => (int) $dtend,
        ]));

        $SQL = "SELECT COUNT(`id`) FROM `eq_sample` WHERE `ctime` BETWEEN %start AND %end";
        $cnt += $db->value(strtr($SQL, [
            '%start' => (int) $dtstart,
            '%end' => (int) $dtend,
        ]));
        $summary['totalUsetime'] = $summary['totalServiceTimes'] = $cnt;
        $user_ids = implode(',', array_values($user_ids));
        // 服务课题组数=统计时段内，仪器预约+送样+使用的总课题组数（去重显示）
        $summary['totalLabs'] = $summary['totalServiceLabs'] = $db->value("SELECT COUNT(DISTINCT `id2`) FROM `_r_user_lab` WHERE `id1` IN ({$user_ids})");

        // 服务总时长=统计时段内，系统内仪器所有使用记录中的使用时长总和——【注：此次统计时，去除爽约记录，与仪器统计中的“使用机时”规则保持一致】
        $sql = "SELECT sum(`dtend`-`dtstart`) FROM `eq_record` WHERE `_extra` NOT LIKE '%%is_missed%%' AND `dtend` > `dtstart` AND `dtend` BETWEEN %d AND %d";
        $query = sprintf($sql, $dtstart, $dtend);
        $summary['useDur'] = $db->value($query);

        // 已测样品数=统计时段内，系统内仪器送样预约中所有“已测试”状态的样品数总和
        $sql_sample = "SELECT sum(`count`) FROM `eq_sample` WHERE `status` = 5 AND `ctime` BETWEEN %d AND %d";
        $query_sample = sprintf($sql_sample, $dtstart, $dtend);
        $summary['totalSample'] = (int)$db->value($query_sample);

        // 科研成果数=统计时段内，系统内论文数、专利数及获奖数总和
        $publications = Q("publication")->total_count();
        $awards = Q("award")->total_count();
        $patents = Q("patent")->total_count();
        $summary['totalAchievement'] = $publications + $awards + $patents ?: 0;

        return $summary;
    }

    /**
     * 设备运行状态
     *
     * @return array
     */
    public function runingStatus($params = [])
    {
        $this->_ready('gpui');

        $selector = "equipment";
        if ($params['equipmentIds']) {
            $selector .= "[id=" . $params['equipmentIds'] . "]";
        }
        $pre_selector = [];
        if ($params['group_id']) {
            $pre_selector[] = "tag_group[id=" . $params['group_id'] . "]";
        }
        if ($params["doorAddr"]) {
            $door = Q("door[in_addr={$params["doorAddr"]}|out_addr={$params["doorAddr"]}]")->current();
            if (!$door->id) {
                return [];
            } else {
                $pre_selector[] = "{$door}<asso";
            }
        }
        if (count($pre_selector)) {
            $selector = '(' . join(', ', $pre_selector) . ') ' . $selector;
        }

        $out_status = EQ_Status_Model::OUT_OF_SERVICE;
        $summary = [];
        // 正在共享的仪器（开放预约/送样的仪器）
        $summary['shareCount'] = Q("{$selector}[accept_reserv|accept_sample]")->total_count();
        // 仪器总数
        $summary['totalCount'] = Q($selector)->total_count();
        // 正在实时监控仪器数
        $summary['controlCount'] = Q("{$selector}[control_mode]")->total_count();
        // 待机中的就是系统中 无使用中使用记录的正常仪器设备数 （正在使用仪器数）
        // 使用中的就是系统中 有使用中使用记录的正常仪器设备数 （待机中仪器数）
        $in_status = EQ_Status_Model::IN_SERVICE;
        $summary['usingCount'] =  Q("eq_record[dtend=0] {$selector}[status={$in_status}]")->total_count();
        $summary['unUsingCount'] =  Q("{$selector}[status={$in_status}]")->total_count() - $summary['usingCount'];
        
        // 故障仪器数
        $summary['outServiceCount'] = Q("{$selector}[status={$out_status}]")->total_count();

        return $summary;
    }

    /**
     * 使用时长排行
     *
     * @param integer $num 返回统计数量
     * @param integer $start 统计开始时间
     * @param integer $end 统计结束时间
     * @return array
     */
    public function timeRank($num = 10, $start = 0, $end = 0, $params = [])
    {
        $this->_ready('gpui');
        $num > 100 and $num = 100;
        if (!$end) {
            $end = Date::time();
        }

        $wheres = [];
        $join_tables = [];
        if ($params['group_id']) {
            array_push($join_tables, " LEFT OUTER JOIN `_r_tag_group_equipment` as `t`  ON `t`.`id2` = `r`.`equipment_id` ");
            array_push($wheres, " AND `t`.`id1` = {$params['group_id']}");
        }

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
        $SQL = "SELECT `r`.`equipment_id`, {$sum} as `sum`" .
            " FROM `eq_record` as `r`" .
            join("", $join_tables) .
            " WHERE `r`.`dtend` > 0 AND `equipment_id` <> 0" .
            " AND ((`r`.`dtstart` <= %start AND `r`.`dtend` >= %start) OR (`r`.`dtstart` <= %end AND `r`.`dtend` >= %end) OR (`r`.`dtstart`>= %start AND `r`.`dtstart` <= %end))" .
            join("", $wheres) .
            " GROUP BY `r`.`equipment_id`" .
            " ORDER BY `sum` DESC LIMIT 0, %num";

        $rows = $db->query(strtr($SQL, [
            '%num' => (int) $num,
            '%start' => (int) $start,
            '%end' => (int) $end,
        ]))->rows();

        $equipments = [];

        foreach ($rows as $row) {
            $e = O('equipment', $row->equipment_id);
            $equipments[] = [
                'id' => $e->id,
                'name' => H($e->name),
                'total' => (float) sprintf('%.2f', ($row->sum / 3600))
            ];
        }

        return $equipments;
    }

    /**
     * 资产价值排行
     *
     * @param integer $num 返回仪器数量
     * @param array $params
     * @return array
     */
    public function priceRank($num = 10, $params = [])
    {
        $this->_ready('gpui');
        $num > 100 and $num = 100;

        // TODO: 根据param筛选仪器, 比如所在学院
        if ($params['group']) {
        }

        $selector = "equipment:sort(price D):limit({$num})";

        $ret = [];
        foreach (Q($selector) as $equipment) {
            $ret[] = [
                'id' => $equipment->id,
                'name' => H($equipment->name),
                'price' => sprintf('%.2f', (float) ($equipment->price))
            ];
        }

        return $ret;
    }

    /**
     * 实时使用记录
     *
     * @param integer $dtstart 时间段起始
     * @param integer $dtend 时间段截止
     * @param array $params
     * @return array
     */
    public function recordList($dtstart = 0, $dtend = 0, $params = [])
    {
        $this->_ready('gpui');

        if (!$dtstart) {
            $dtstart = Date::get_day_start() - 86400 * 365;
        }
        if (!$dtend) {
            $dtend = Date::get_day_end();
        }
        $now = Date::time();
        $selector = "eq_record[dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]";
        if ($params['equipment']) {
            $selector .= "[equipment_id={$params['equipment']}]";
        }

        $limit_start = $params['limit'][0] ?? 0;
        $limit_length = $params['limit'][1] ?? 8;
        $selector .= ':sort(dtstart D)';
        $selector .= ":limit($limit_start, $limit_length)";
        $data = [];
        foreach (Q($selector) as $record) {
            $info = [
                'user_id' => $record->user->id,
                'avatar' => $record->user->icon_url('128'),
                'user_name' => H($record->user->name),
                'lab_name' => $record->project->lab->id ? H($record->project->lab->name) : H(Q("{$record->user} lab")->current()->name),
                'equipment_name' => H($record->equipment->name),
                'start' => $record->dtstart,
                'end' => $record->dtend,
                'feedback' => EQ_Record_Model::$status_type[$record->status]
            ];

            if (Config::get('equipment.enable_use_type')) {
                $info['use_type'] = EQ_Record_Model::$use_type[$record->use_type];
            }
            // $info = new ArrayIterator($info);

            // Event::trigger('gpui.record_list.extra.info', $record, $info);
            $data[] = (array) $info;
        }

        return $data;
    }

    /**
     * 实时送样记录,后续可以进行完善
     *
     * @param integer $dtstart 时间段起始
     * @param integer $dtend 时间段截止
     * @param array $params
     * @return array
     */
    public function sampleList($dtstart = 0, $dtend = 0, $params = [])
    {
        $this->_ready('gpui');

        if (!$dtstart) {
            $dtstart = Date::get_day_start() - 86400 * 365;
        }
        if (!$dtend) {
            $dtend = Date::get_day_end();
        }
        $now = Date::time();
        $selector = "eq_sample[ctime={$dtstart}~{$dtend}]";
        if ($params['equipment']) {
            $selector .= "[equipment_id={$params['equipment']}]";
        }

        $limit_start = $params['limit'][0] ?? 0;
        $limit_length = $params['limit'][1] ?? 8;
        $selector .= ':sort(ctime D)';
        $selector .= ":limit($limit_start, $limit_length)";
        $data = [];
        foreach (Q($selector) as $sample) {
            $info = [
                'user_id' => $sample->sender->id,
                'user_name' => H($sample->sender->name),
                'equipment_name' => H($sample->equipment->name),
                'ctime' => $sample->ctime,
                'dtstart' => $sample->dtstart,
                'dtend' => $sample->dtend,
                'status' => $sample->status,
                'status_dtr' => EQ_Sample_Model::$status[$sample->status] ?: '未知',
            ];

            if (Config::get('equipment.enable_use_type')) {
                $info['use_type'] = EQ_Record_Model::$use_type[$record->use_type];
            }
            // $info = new ArrayIterator($info);

            // Event::trigger('gpui.sample_list.extra.info', $sample, $info);
            $data[] = (array) $info;
        }

        return $data;
    }

    /**
     * 实时使用中的使用记录
     *
     * @param array $params
     * @return array
     */
    public function usingRecordList($params = [])
    {
        $this->_ready('gpui');
        $now = Date::time();
        $selector = "eq_record[dtend=0]";
        if ($params['equipment']) {
            $selector .= "[equipment_id={$params['equipment']}]";
        }

        $limit_start = $params['limit'][0] ?? 0;
        $limit_length = $params['limit'][1] ?? 8;
        $selector .= ':sort(dtstart D)';
        $selector .= ":limit($limit_start, $limit_length)";
        $data = [];
        foreach (Q($selector) as $record) {
            $info = [
                'user_id' => $record->user->id,
                'user_name' => H($record->user->name),
                'equipment_name' => H($record->equipment->name),
                'start' => $record->dtstart,
                'end' => $record->dtend,
            ];

            if (Config::get('equipment.enable_use_type')) {
                $info['use_type'] = EQ_Record_Model::$use_type[$record->use_type];
            }
            // $info = new ArrayIterator($info);

            // Event::trigger('gpui.record_list.extra.info', $record, $info);
            $data[] = (array) $info;
        }

        return $data;
    }

    /**
     * 使用用途分析
     *
     * @param integer $num 统计时长, 默认12个月
     * @return array
     */
    public function useTypeRank($num = 12)
    {
        $this->_ready('gpui');

        $db = Database::factory();
        $data = [];
        $dtstart = Date::prev_time(Date::get_month_start(), 1, 'm');
        $dtend = Date::get_month_end();

        for ($i = 1; $i <= 12; $i++) {
            $SQL = "SELECT `r`.`use_type`, SUM(LEAST({$dtend}, `r`.`dtend`) - GREATEST({$dtstart}, `r`.`dtstart`)) as `sum`" .
                " FROM `eq_record` as `r`" .
                " WHERE `r`.`dtend` > 0 AND `r`.`dtend` BETWEEN %start AND %end AND `equipment_id` <> 0" .
                " GROUP BY `r`.`use_type`";

            $rows = $db->query(strtr($SQL, [
                '%start' => (int) $dtstart,
                '%end' => (int) $dtend,
            ]))->rows();

            $d = [];
            foreach ($rows as $row) {
                $d[$row->use_type] = [
                    'use_type' => EQ_Record_Model::$use_type[$row->use_type],
                    'time' => (float) sprintf('%.2f', ($row->sum / 3600))
                ];
            }

            // 保证每个$d 内都是4组数据, 方便前端使用
            foreach (EQ_Record_Model::$use_type as $type => $value) {
                if (!isset($d[$type])) {
                    $d[$type] = [
                        'use_type' => EQ_Record_Model::$use_type[$type],
                        'time' => 0
                    ];
                }
            }
            $data[] = $d;
            $dtstart = Date::prev_time($dtstart, 1, 'm');
            $dtend = Date::prev_time($dtend, 1, 'm');
        }
        rsort($data);
        return $data;
    }

    /**
     * 校内外机时对比
     *
     * @param array $params
     * @return void
     */
    public function recordInnerOuterStat($params = [])
    {
        $this->_ready('gpui');

        $start = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? 0 : $params['dtstart'];
        $end = (!$params['dtend'] || !is_numeric($params['dtend'])) ? Date::time() : $params['dtend'];
        $db = Database::factory();

        $root = Tag_Model::root('group');
        $inner_group_name = Config::get('lab.inner_group_name');
        $inner_group = Q("tag[root={$root}][parent={$root}][name={$inner_group_name}]");

        if ($end && $start) {
            $sum = "SUM(LEAST({$end}, `r`.`dtend`) - GREATEST({$start}, `r`.`dtstart`))";
        } elseif ($end) {
            $sum = "SUM(LEAST({$end}, `r`.`dtend`) - `r`.`dtstart`)";
        } elseif ($start) {
            $sum = "SUM(`r`.`dtend` - GREATEST({$start}, `r`.`dtstart`))";
        } else {
            $sum = "SUM(`r`.`dtend` - `r`.`dtstart`)";
        }

        if ($inner_group->total_count() == 1) {
            $inner_group = $inner_group->current();

            $SQL = "SELECT {$sum} as `sum`" .
                " FROM `eq_record` as `r`" .
                " LEFT OUTER JOIN `_r_user_tag` as `_r` ON (`r`.`user_id` = `_r`.`id1`) " .
                " WHERE `r`.`dtend` > 0 AND `r`.`dtend` BETWEEN %start AND %end AND `equipment_id` <> 0" .
                " AND `_r`.`id2` = %inner_group_id";
            $SQL = strtr($SQL, [
                '%inner_group_id' => $inner_group->id,
                '%start' => (int) $start,
                '%end' => (int) $end,
            ]);
            $inner_time = (float) sprintf('%.2f', (float) ($db->value($SQL) / 3600));
        } else {
            $inner_time = 0;
        }

        $SQL = "SELECT {$sum} as `sum` FROM `eq_record` as `r` " .
            " WHERE `r`.`dtend` > 0 AND `r`.`dtend` BETWEEN %start AND %end AND `equipment_id` <> 0";
        $SQL = strtr($SQL, [
            '%start' => (int) $start,
            '%end' => (int) $end,
        ]);
        $total_time = (float) sprintf('%.2f', (float) ($db->value($SQL) / 3600));

        return [
            'inner' => $inner_time,
            'outer' => $total_time - $inner_time
        ];
    }
    /**
     * 实验室总运行数据
     *
     * @param integer $dtstart 时间段起始
     * @param integer $dtend 时间段截止
     * @param array $params
     * $lab 实验室
     */
    public function recordStat($params = [], $dtstart = 0, $dtend = 0)
    {
        $this->_ready('gpui');

        $db = Database::factory();

        $selector = " FROM `eq_record`  WHERE `dtend` > `dtstart`";
        $ids = "";
        if ($params["ids"]) {
            $selector .= " and `equipment_id` in (" . join(',', $params["ids"]) . ")";
        }

        if ($dtstart) {
            $selector .= " and dtend >= {$dtstart}";
        }
        if ($dtend) {
            $selector .= " and dtend < {$dtend}";
        }

        //使用总次数
        $useNum = $db->value("SELECT COUNT(`id`) {$selector}");
        //使用总机时
        $useDur = $db->value("SELECT sum(`dtend`-`dtstart`) {$selector}");
        //使用总人数
        $useUser = $db->value("SELECT COUNT(distinct `user_id`) {$selector}");

        $data = [
            'useDur' => $useDur,
            'useNum' => $useNum,
            'useUser' => $useUser
        ];

        return $data;
    }


    /**
     * 实验室设备使用状态
     * 实验室仪器列表
     * $start 开始
     * $num 数量
     */
    public function getEquipmentUrl($equipment, $size = 128)
    {
        $size = $equipment->normalize_icon_size($size);
        $icon_file = $equipment->icon_file($size);
        if (!$icon_file) {
            return "";
        }
        return Config::get('system.base_url') . Cache::cache_file($icon_file) . '?_=' . $equipment->mtime;
    }
    public function useStatus($params = [], $start = 0, $num = 5)
    {
        $this->_ready('gpui');

        if (!$params["doorAddr"]) {
            return [];
        }

        $door = Q("door[in_addr={$params["doorAddr"]}|out_addr={$params["doorAddr"]}]")->current();
        if (!$door->id) {
            return [];
        }

        $selector = "{$door}<asso equipment";

        if ($params["title"]) {
            $selector .= "[name*={$params["title"]}]";
        }
        if ($num || $start) {
            $selector .= ":limit({$start}, {$num})";
        }

        $data = [];

        foreach (Q($selector) as $item) {
            $charge = [];
            foreach (Q("{$item} user.incharge") as $user) {
                $charge[] = $user->name;
            }
            $data[] = [
                "name" => $item->name,
                "location" => $item->location . $item->location2,
                "status" => ($item->control_mode && $item->is_using == 1) ? "使用中" : "空闲中",
                "charge" =>  $charge,
                "accept_reserv" => $item->accept_reserv,
                "accept_sample" => $item->accept_sample,
                "phone" => $item->phone,
                "email" => $item->email,
                "picture" => $this->getEquipmentUrl($item)
            ];
        }

        return $data;
    }

    /**
     * 实验室实时预约记录
     * doorAddr 门禁地址
     * ids 仪器ID
     */
    public function doorStatusList($params = [])
    {
        $this->_ready('gpui');

        if (!$params["doorAddr"]) {
            return [];
        }

        $door = Q("door[in_addr={$params["doorAddr"]}|out_addr={$params["doorAddr"]}]")->current();
        if (!$door->id) {
            return [];
        }

        $selector = "{$door}<asso equipment";

        if ($params['ids']) {
            $selector .= "[id={$params['ids']}]";
        }

        $equipments_data = [];


        foreach (Q($selector) as $equipment) {
            $now = Date::time();

            $tag = $equipment->group;
            $group = $tag->id ? [$tag->id => $tag->name] : [];
            while ($tag->parent->id && $tag->parent->root->id) {
                $group += [$tag->parent->id => $tag->parent->name];
                $tag = $tag->parent;
            }
            $root = Tag_Model::root('equipment');
            $users = Q("{$equipment} user.contact")->to_assoc('id', 'name');
            $incharges = Q("{$equipment} user.incharge")->to_assoc('id', 'name');
            $tags = Q("{$equipment} tag[root=$root]")->to_assoc('id', 'name');
            $data = [
                'id'    => $equipment->id,
                'icon_url' => $equipment->icon_url('32'), //默认图标，向后兼容
                'icon16_url' => $equipment->icon_url('16'),
                'icon32_url' => $equipment->icon_url('32'),
                'icon48_url' => $equipment->icon_url('48'),
                'icon64_url' => $equipment->icon_url('64'),
                'icon128_url' => $equipment->icon_url('128'),
                'iconreal_url' => $equipment->icon_file('real') ?
                    Config::get('system.base_url') . Cache::cache_file($equipment->icon_file('real')) . '?_=' . $equipment->mtime :
                    $equipment->icon_url('128'),
                'url' => $equipment->url(),
                'name' => $equipment->name,
                'name_abbr' => $equipment->name_abbr,
                'phone' => $equipment->phone,
                'contact' => join(', ', $users),
                'email' => $equipment->email,
                'location' => $equipment->location,
                'location2' => $equipment->location2,
                'accept_sample' => $equipment->accept_sample,
                'accept_reserv' => $equipment->accept_reserv,
                'reserv_url' => $equipment->url('reserv'),
                'sample_url' => $equipment->url('sample'),
                'price' => $equipment->price,
                'status' => $equipment->status,

                'ref_no' => $equipment->ref_no,
                'cat_no' => $equipment->cat_no,
                'model_no' => $equipment->model_no,
                'control_mode' => $equipment->control_mode,
                'is_using' => $equipment->is_using,
                'connect' => $equipment->connect,
                'is_monitoring' => $equipment->is_monitoring,
                'is_monitoring_mtime' => $equipment->is_monitoring_mtime,
                'current_user' => $equipment->current_user()->name,
                'accept_limit_time' =>  $equipment->accept_limit_time,
                'organization' => $equipment->organization,
                'specification' => $equipment->specification,
                'tech_specs' => $equipment->tech_specs,
                'features' => $equipment->features,
                'configs' => $equipment->configs,
                'open_reserv' => $equipment->open_reserv,
                'charge_info' => $equipment->charge_info,

                'manu_at' => $equipment->manu_at,
                'manufacturer' => $equipment->manufacturer,
                'manu_date' => $equipment->manu_date,
                'purchased_date' => $equipment->purchased_date,
                'control_address' => $equipment->control_address,
                'require_training' => $equipment->require_training,

                'ctime' => $equipment->ctime,
                'atime' => $equipment->atime,
                'mtime' => $equipment->mtime,
                'access_code' => $equipment->access_code,
                'group' => $group,
                'group_id' => $equipment->group->id,
                'group_name' => $equipment->group->name,
                'tag_root_id' => $equipment->tag_root_id,
                'billing_dept_id' =>  $equipment->billing_dept_id,
                'incharges' => join(', ', $incharges),
                'tags' => join(', ', $tags),
                'incharges_info' => $incharges,
                'contacts_info' => $users,
                'tagsInfo' => $tags,
                'en_name' => $equipment->en_name,
                'yiqikong_share' => $equipment->yiqikong_share,
                'bluetooth_serial_address' => $equipment->control_address
            ];

            if (Module::is_installed('nrii')) {
                $nrii = O('nrii_equipment', ['eq_id' => $equipment->id]);
                if ($nrii->id) {
                    //参考收费标准
                    $data['charge_rule'] = $nrii->fee;
                    //对外开放共享规定
                    $data['requirement'] = $nrii->requirement;
                    //服务内容
                    $data['service_content'] = $nrii->service_content;
                    //服务典型成果
                    $data['achievement'] = $nrii->achievement;
                }
            }
            $data = Event::trigger('equipment.api.extra', $equipment, $data) ?: $data;

            //实时滚动展示实验室内各仪器的预约情况（仪器名称、图片及负责人，当前使用者信息，下一个预约者信息）
            $record = Q("eq_record[equipment={$equipment}][dtstart<$now][dtend=0]:sort(dtstart D):limit(1)")->current();
            if ($record->id) {
                // 当前使用者使用者信息包括：使用者姓名、所属课题组、预约时段；
                //$reserv = Q("{$record} eq_reserv");
                $data["usingUser"] = [
                    "img" => $this->getUserurl($record->user),
                    "name" => $record->user->name,
                    "lab" => H(join(' ', Q("{$record->user} lab")->to_assoc('id', 'name'))),
                    "start" => Q("{$record} eq_reserv")->current()->dtstart ?: $record->dtstart,
                    "end" => Q("{$record} eq_reserv")->current()->dtend ?: Date::time(),
                ];
            }

            //下一个使用者
            $reserv = Q("{$equipment} eq_reserv[dtstart>{$now}]:sort(dtstart A):limit(1)")->current();
            if ($reserv->id) {
                $data["nextUser"] = [
                    "img" => $this->getUserurl($reserv->user),
                    "name" => H($reserv->user->name),
                    'labs' => $reserv->project->lab->id ? H($reserv->project->lab->name) : H(Q("{$reserv->user} lab")->current()->name),
                    'start' => $reserv->dtstart,
                    'end' => $reserv->dtend,
                ];
            }

            $equipments_data[] = $data;
        }
        return $equipments_data;
    }


    /**
     *单台仪器详情
     *id 仪器ID
     * $params[reservList] 预约周记录
     * $params[using] 当前使用者
     * $params[reservNext] 下一个预约者
     * $params[basic] 仪器基本信息
     * $params[infor] 仪器使用信息
     * $params[stat] 累计运行数据
     * $params[jarvis] 仪器平板多媒体码
     *
     */
    public function inforDetail($id = 0, $params = [])
    {
        $this->_ready('gpui');

        if (!$id) {
            return [];
        }

        $equipment = O("equipment", $id);

        $data = new ArrayIterator;
        Event::trigger('gpui.api.equipment.inforDetail', $equipment, $params, $data);
        return (array)$data;
    }
    public function getUserurl($user, $size = 128)
    {
        $size = $user->normalize_icon_size($size);
        $icon_file = $user->icon_file($size);

        if (!$icon_file) {
            return "";
        }
        return Config::get('system.base_url') . Cache::cache_file($icon_file) . '?_=' . $user->mtime;
    }




    /**
     *固定资产 仪器总台数
     *统计时段内，仪器目录中有组织机构的仪器（包含正常设备，故障设备，废弃设备）数量之和
     */
    public function getNumber($params = [])
    {
        $this->_ready('gpui');

        $dtstart = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? 0 : $params['dtstart'];
        $dtend = (!$params['dtend'] || !is_numeric($params['dtend'])) ? Date::time() : $params['dtend'];

        // 设备总数=统计时段内，系统设备总数（含故障设备、报废设备数）
        $data = Q("equipment[ctime>={$dtstart}][ctime<={$dtend}]")->total_count() ?: 0;
        return $data;
    }
    /**
     * 仪器总价值
     */
    public function getPrice($params = [])
    {
        $this->_ready('gpui');

        $dtstart = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? 0 : $params['dtstart'];
        $dtend = (!$params['dtend'] || !is_numeric($params['dtend'])) ? Date::time() : $params['dtend'];
        $data = Q("equipment[ctime>=$dtstart][ctime<=$dtend][price>0]")->sum('price') ?: 0;
        return $data;
    }


    /**
     * 贵重仪器
     */

    public function getPrecious($params = [])
    {
        $this->_ready('gpui');

        $dtstart = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? Date::get_year_start() : $params['dtstart'];
        $dtend = (!$params['dtend'] || !is_numeric($params['dtend'])) ?  Date::get_year_end() : $params['dtend'];
        $price = (!$params['price'] || !is_numeric($params['price'])) ? 400000 : $params['price'];

        $num = 2;
        $format = 'y';
        $date_format = 'Y';

        for ($i = 1; $i <= $num; $i++) {
            $dtstart = Date::prev_time($dtstart, 1, $format);
            $dtend = Date::prev_time($dtend, 1, $format);

            $data[Date::format($dtstart, $date_format)] = [
                'preciousEqs' => Q("equipment[ctime>=$dtstart][ctime<=$dtend][price>={$price}]")->total_count() ?: 0,
                'totalCount' => Q("equipment[ctime>=$dtstart][ctime<=$dtend]")->total_count() ?: 0
            ];
        }
        return $data;
    }


    /**
     * 仪器组织机构分布
     */
    public function getEquipmentGroup($params = [])
    {
        $this->_ready('gpui');

        $root = Tag_Model::root('group');

        $parentList = ["parent={$root}"];
        $level = (!$params['level'] || !is_numeric($params['level'])) ? 0 : $params['level'];
        for ($i = 1; $i < $level; $i++) {
            $tags = Q("tag[" . implode("|", $parentList) . "][root={$root}]");
            $parentList = [];
            foreach ($tags as $tag) {
                $parentList[] = "parent={$tag}";
            }
        }

        $tags = Q("tag[" . implode("|", $parentList) . "][root={$root}]");

        $dtstart = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? 0 : $params['dtstart'];
        $dtend = (!$params['dtend'] || !is_numeric($params['dtend'])) ? Date::time() : $params['dtend'];

        $data = [];
        $number = 0;
        foreach ($tags as $child) {
            $data[H($child->name)] = Q("{$child} equipment[ctime>={$dtstart}][ctime<={$dtend}]")->total_count() ?: 0;
            $number += $data[H($child->name)];
        }
        $data[H('其他')] = Q("equipment[ctime>={$dtstart}][ctime<={$dtend}]")->total_count() - $number;


        return $data;
    }

    /**
     *
     * 使用总机时
     * @param array $params
     *
     */
    public function getTotalUseDur($params = [])
    {
        $this->_ready('gpui');

        $dtstart = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? 0 : $params['dtstart'];
        $dtend = (!$params['dtend'] || !is_numeric($params['dtend'])) ? Date::time() : $params['dtend'];

        $db = Database::factory();
        $sql = "SELECT sum(`dtend`-`dtstart`) FROM `eq_record` WHERE `_extra` NOT LIKE '%%is_missed%%' AND `dtend` > `dtstart` AND `dtend` BETWEEN %d AND %d";
        $query = sprintf($sql, $dtstart, $dtend);


        $data['useDur'] = $db->value($query);

        return $data;
    }


    /**
     * 仪器整体运行数据同比增速对比
     * @param int $num
     * @param string $format
     * @param array $params
     * @return array|bool
     * @throws API_Exception
     *
     */
    public function getGrowthRate($num = 2, $format = "y", $params = [])
    {
        $this->_ready('gpui');

        $db = Database::factory();
        switch ($format) {
            case 'y':
                $dtstart = Date::get_year_start();
                $dtend = Date::get_year_end();
                $date_format = 'Y';
                break;
            case 'm':
                $dtstart = Date::get_month_start();
                $dtend = Date::get_month_end();
                $date_format = 'm';
                break;
            default:
                return [];
        }

        $Rate_data_list = [];
        $data = [];
        for ($i = 1; $i <= $num; $i++) {
            $dtstart = Date::prev_time($dtstart, 1, $format);
            $dtend = Date::prev_time($dtend, 1, $format);
            //当年仪器平均使用机时
            if ($dtend && $dtstart) {
                $sum = "SUM(LEAST({$dtend}, `r`.`dtend`) - GREATEST({$dtstart}, `r`.`dtstart`))";
            } elseif ($dtend) {
                $sum = "SUM(LEAST({$dtend}, `r`.`dtend`) - `r`.`dtstart`)";
            } elseif ($dtstart) {
                $sum = "SUM(`r`.`dtend` - GREATEST({$dtstart}, `r`.`dtstart`))";
            } else {
                $sum = "SUM(`r`.`dtend` - `r`.`dtstart`)";
            }
            $SQL = "SELECT {$sum} as `sum`" .
                " FROM `eq_record` as `r`" .
                " WHERE `r`.`dtend` > 0 AND `r`.`dtend` BETWEEN %start AND %end AND `equipment_id` <> 0";
            $values = $db->query(strtr($SQL, [
                '%start' => (int) $dtstart,
                '%end' => (int) $dtend,
            ]))->rows();
            $next_time = Date::format(Date::next_time($dtstart, 1, $format), $date_format);
            foreach ($values as $value) {
                $data[Date::format($dtstart, $date_format)]['useDur'] = $value->sum ? (float) sprintf('%.2f', $value->sum) : 0;
                if (isset($data[$next_time]['useDur'])) {
                    $Rate_data["useDur"] =  ($data[$next_time]['useDur'] - $data[Date::format($dtstart, $date_format)]['useDur']) / $data[Date::format($dtstart, $date_format)]['useDur'];
                    $Rate_data["useDur"] = (float) sprintf('%.2f', $Rate_data["useDur"]);
                }
            }
            $data[Date::format($dtstart, $date_format)]['equipment'] =  Q("equipment[ctime>={$dtstart}][ctime<={$dtend}]")->total_count() ?: 0;
            if (isset($data[$next_time]['equipment'])) {
                $Rate_data["equipment"] =  ($data[$next_time]['equipment'] - $data[Date::format($dtstart, $date_format)]['equipment']) / $data[Date::format($dtstart, $date_format)]['equipment'];
                $Rate_data["equipment"] = (float) sprintf('%.2f', $Rate_data["equipment"]);
            }
            $data[Date::format($dtstart, $date_format)]['people'] =  Q("eq_reserv[dtstart>={$dtstart}][dtstart<={$dtend}]")->total_count() + Q("eq_sample[dtstart>={$dtstart}][dtstart<={$dtend}]")->total_count();
            if (isset($data[$next_time]['people'])) {
                $Rate_data["people"] =  ($data[$next_time]['people'] - $data[Date::format($dtstart, $date_format)]['people']) / $data[Date::format($dtstart, $date_format)]['people'];
                $Rate_data["people"] = (float) sprintf('%.2f', $Rate_data["people"]);
            }
            if ($Rate_data) {
                $Rate_data_list[$next_time] = $Rate_data;
            }
        }
        return $Rate_data_list;
    }

    /**
     * 所有记录
     * @param int $id
     * @param array $params
     * @return array
     * @throws API_Exception
     *
     */
    public function getAllRecord($id = 0, $params = [])
    {
        $this->_ready('gpui');

        if (!$id) {
            return [];
        }

        $equipment = O('equipment', $id);

        if (!$equipment->id) {
            return [];
        }
        //获取仪器基本状态
        $data = [];

        $data["name"] = $equipment->name;
        $data["status"] = (int)$equipment->status;
        $data["is_using"] = $equipment->control_mode && $equipment->is_using == 1 ? 1 : 0;
        $data["record"] = [];
        $data["sample"] = [];
        //列表字段包括记录编号、使用者、预约时间、样品数、金额
        $dtend = Date::get_day_end();
        $dtstart = Date::prev_time($dtend, 7, 'd');

        // 使用记录
        $selector = "{$equipment} eq_record[dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]:sort(dtstart D)";
        foreach (Q($selector) as $record) {
            $source = Event::trigger('sample_form.charge_get_source', $record) ?: $record;
            $charge = O("eq_charge", ['source' => $source]);
            $amount = $charge->amount;
            if (!$charge->id || !$charge->charge_type || $charge->charge_type == 'reserv') {
                $reserv_charge = O('eq_charge', ['source' => $record->reserv]);
            }
            if ($reserv_charge->id) {
                $amount += $reserv_charge->amount;
            }
            $data["record"][] = [
                "serialNumber" => Number::fill(H($record->id), 6),
                "name" => H($record->user->name),
                "dtstart" => $record->dtstart,
                "dtend" => $record->dtend ?: Date::time(),
                "samples" => $record->samples,
                "amount" => Number::currency($amount)
            ];
        }
        // 送样预约
        $selector = "{$equipment} eq_sample[ctime={$dtstart}~{$dtend}]:sort(ctime D)";
        foreach (Q($selector) as $record) {
            $charge = O('eq_charge', ['source' => $record]);
            $data["sample"][] = [
                "serialNumber" => Number::fill(H($record->id), 6),
                "name" => H($record->sender->name),
                "dtsubmit" => $record->dtsubmit,
                "samples" => $record->count,
                "amount" => Number::currency($charge->amount)
            ];
        }
        return $data;
    }


    /**
     * 仪器进门记录
     */
    function equipmentIndoorList($params = [], $start = 0, $num = 5)
    {
        if (!Module::is_installed('entrance')) return [];

        $this->_ready('gpui');

        $selector = "equipment";

        if ($params['equipment']) {
            $selector .= "[id={$params['equipment']}]";
        }

        $selector .= " door.asso dc_record:sort(time D)";

        $data = [];

        foreach (Q("$selector")->limit($start, $num) as $item) {
            $data[]  = [
                "name" => $item->door->name,
                "user_name" => $item->user->name,
                "time" => date('Y/m/d H:i:s', $item->time),
                "direction" => DC_Record_Model::$direction[$item->direction]
            ];
        }
        return $data;
    }

    /**
     * 用户使用排行
     *
     * @return void
     */
    function userTimeRank($params = [], $start = 0, $num = 5)
    {
        $this->_ready('gpui');

        $dtstart = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? 0 : $params['dtstart'];
        $dtend = (!$params['dtend'] || !is_numeric($params['dtend'])) ? Date::time() : $params['dtend'];


        $num > 100 and $num = 100;
        if (!$dtend) $dtend = Date::time();

        $db = Database::factory();

        // 只统计一段时间的使用时长, 有超过这段时间的记录只按这段时间计算
        // 比如统计上个月的使用时长, 同时有条使用记录长达一年, 只统计这条记录落入start~end的范围
        if ($dtend && $dtstart) {
            $sum = "SUM(LEAST({$dtend}, `r`.`dtend`) - GREATEST({$dtstart}, `r`.`dtstart`))";
        } elseif ($dtend) {
            $sum = "SUM(LEAST({$dtend}, `r`.`dtend`) - `r`.`dtstart`)";
        } elseif ($dtstart) {
            $sum = "SUM(`r`.`dtend` - GREATEST({$dtstart}, `r`.`dtstart`))";
        } else {
            $sum = "SUM(`r`.`dtend` - `r`.`dtstart`)";
        }
        $SQL = "SELECT `r`.`user_id`, {$sum} as `sum`" .
            " FROM `eq_record` as `r`" .
            " WHERE `r`.`dtend` > 0 AND `r`.`dtend` BETWEEN %dtstart AND %dtend AND `user_id` <> 0" .
            " GROUP BY `r`.`user_id`" .
            " ORDER BY `sum` DESC LIMIT %start, %num";

        if ($params['equipment']) {
            $SQL = "SELECT `r`.`user_id`, {$sum} as `sum`" .
                " FROM `eq_record` as `r`" .
                " WHERE `r`.`equipment_id` in (%equipment) AND `r`.`dtend` > 0 AND `r`.`dtend` BETWEEN %dtstart AND %dtend AND `user_id` <> 0" .
                " GROUP BY `r`.`user_id`" .
                " ORDER BY `sum` DESC LIMIT %start, %num";
        }
        $rows = $db->query(strtr($SQL, [
            '%equipment' => $params['equipment'],
            '%start' => (int) $start,
            '%num' => (int) $num,
            '%dtstart' => (int) $dtstart,
            '%dtend' => (int) $dtend,
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
}
