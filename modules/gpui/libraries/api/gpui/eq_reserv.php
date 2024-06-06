<?php
class API_GPUI_Eq_Reserv extends API_Common
{
    /**
     * 实时预约记录
     *
     * @param integer $dtstart 时间段起始
     * @param integer $dtend 时间段截止
     * @param array $params
     * equipment_id 指定单台仪器
     * @return array
     * 当前状态列先判断status 0=正常 1=暂时故障 2=报废, status=0 的情况下 is_using=0空闲, is_using=1使用中
     * 审批状态列approval 'approve'=未审核 'done'=通过 'rejected'=驳回 其他=无需审核
     */
    public function reservList($dtstart = 0, $dtend = 0, $params = [])
    {
        $this->_ready('gpui');

        if (!$dtstart) {
            $dtstart = Date::time();
        }
        if (!$dtend) {
            $dtend = Date::get_day_end();
        }

        $type = Cal_Component_Model::TYPE_VEVENT;
        $pre_selector = [];
        $pre_selector[] = "cal_component[type={$type}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]<component";
        if ($params['equipment_id']) {
            $pre_selector[] = "equipment#" . (int) $params['equipment_id'];
        }
        if ($params['equipment']) {
            $pre_selector[] = "equipment[id={$params['equipment']}]";
        }
        if ($params['group_id']) {
            $pre_selector[] = "tag_group#" . (int) $params['group_id'] . " equipment";
        }

        $limit_start = $params['limit'][0] ?? 0;
        $limit_length = $params['limit'][1] ?? 1000;
        $selector = 'eq_reserv:sort(dtstart A)';
        $selector .= ":limit($limit_start, $limit_length)";
        if (count($pre_selector)) {
            $selector = '(' . join(', ', $pre_selector) . ') ' . $selector;
        }

        $data = [];
        foreach (Q($selector) as $reserv) {
            $equipment = $reserv->equipment;
            $info = [
                'user_id' => $reserv->user->id,
                'avatar' => $reserv->user->icon_url('128'),
                'user_name' => H($reserv->user->name),
                'equipment_id' => $equipment->id,
                'equipment_name' => H($equipment->name),
                'equipment_group' => H($equipment->group->name),
                'equipment_location' => H($equipment->location),
                'status' => $equipment->status,
                'is_using' => $equipment->is_using,
                'user_using' => $equipment->user_using->id,
                'user_using_name' => $equipment->user_using->name,
                'labs' => $reserv->project->lab->id ? H($reserv->project->lab->name) : H(Q("{$reserv->user} lab")->current()->name),
                'start' => $reserv->dtstart,
                'end' => $reserv->dtend,
            ];
            $info = new ArrayIterator($info);

            Event::trigger('gpui.reserv_list.extra.info', $reserv, $info);
            $data[] = (array) $info;
        }

        return $data;
    }

    public function reservRank($num = 10, $start = 0, $end = 0)
    {
        $this->_ready('gpui');
        $num > 100 and $num = 100;
        if (!$end) {
            $end = Date::time();
        }

        $db = Database::factory();

        // 只统计一段时间的预约时长, 有超过这段时间的记录只按这段时间计算
        // 比如统计上个月的预约时长, 同时有条预约记录长达一年, 只统计这条记录落入start~end的范围
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
            " FROM `eq_reserv` as `r`" .
            " WHERE `r`.`dtend` > 0 AND `r`.`dtend` BETWEEN %start AND %end AND `equipment_id` <> 0" .
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
     * 年预约机时对比
     *
     * @param integer $num 跨度
     * @param string $format 单位
     * 比如 默认 3, y 就是3年内 以年为单位统计
     * @return void
     */
    public function yearRank($num = 3, $format = 'y')
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
                return[];
        }

        $data = [];
        for ($i = 1; $i <= $num; $i++) {
            $dtstart = Date::prev_time($dtstart, 1, $format);
            $dtend = Date::prev_time($dtend, 1, $format);

            $SQL = "SELECT SUM(LEAST({$dtend}, `r`.`dtend`) - GREATEST({$dtstart}, `r`.`dtstart`)) as `sum`" .
                " FROM `eq_reserv` as `r`" .
                " WHERE `r`.`dtend` > 0 AND `r`.`dtend` BETWEEN %start AND %end AND `equipment_id` <> 0";

            $value = $db->value(strtr($SQL, [
                '%start' => (int) $dtstart,
                '%end' => (int) $dtend,
            ]));
            $data[Date::format($dtstart, $date_format)] = $value ? (float) sprintf('%.2f', ($value / 3600)) : 0;
        }
        return $data;
    }
}
