<?php
class API_GPUI_Eq_Sample extends API_Common
{
    /**
     * 平台年送样对比
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
                return [];
        }

        $data = [];
        for ($i = 1; $i <= $num; $i++) {
            $dtstart = Date::prev_time($dtstart, 1, $format);
            $dtend = Date::prev_time($dtend, 1, $format);

            $SQL = "SELECT SUM(`count`) as `cnt`" .
                " FROM `eq_sample` as `s`" .
                " WHERE `s`.`ctime` BETWEEN %start AND %end AND `equipment_id` <> 0";

            $value = $db->value(strtr($SQL, [
                '%start' => (int) $dtstart,
                '%end' => (int) $dtend,
            ]));
            $data[Date::format($dtstart, $date_format)] = (int) $value;
        }
        return $data;
    }


    /**
     * 平台年送样对比 已测试
     *
     * @param integer $num 跨度
     * @param string $format 单位
     * 比如 默认 3, y 就是3年内 以年为单位统计
     * @return void
     */
    public function yearRankHaveTest($num = 3, $format = 'y')
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

        $data = [];
        for ($i = 1; $i <= $num; $i++) {
            $dtstart = Date::prev_time($dtstart, 1, $format);
            $dtend = Date::prev_time($dtend, 1, $format);

            $SQL = "SELECT SUM(`count`) as `cnt`" .
                " FROM `eq_sample` as `s`" .
                " WHERE `s`.`ctime` BETWEEN %start AND %end AND `equipment_id` <> 0 AND `status`=" . EQ_Sample_Model::STATUS_TESTED;

            $value = $db->value(strtr($SQL, [
                '%start' => (int) $dtstart,
                '%end' => (int) $dtend,
            ]));
            $data[Date::format($dtstart, $date_format)] = (int) $value;
        }
        return $data;
    }

    public function recordStat($params = [], $dtstart = 0, $dtend = 0)
    {
        $this->_ready('gpui');

        $db = Database::factory();

        $selector = " FROM `eq_sample`  WHERE `status` = 5";
        $ids = "";
        if ($params["ids"]) {
            $selector .= " and `equipment_id` in (" . join(',', $params["ids"]) . ")";
        }

        if ($dtstart) {
            $selector .= " and dtsubmit >= {$dtstart}";
        }
        if ($dtend) {
            $selector .= " and dtsubmit < {$dtend}";
        }

        error_log("SELECT SUM(`count`) {$selector} ");
        //使用总次数
        $useNum = $db->value("SELECT SUM(`count`) {$selector} ");
        // //使用总机时
        // $useDur = $db->value("SELECT sum(`dtend`-`dtstart`) {$selector}");
        // //使用总人数
        // $useUser = $db->value("SELECT COUNT(distinct `user_id`) {$selector}");

        $data = [
            // 'useDur' => $useDur,
            'useNum' => $useNum,
            // 'useUser' => $useUser
        ];

        return $data;
    }

    /**
     * 实时送样记录
     *
     * @param integer $dtstart 时间段起始
     * @param integer $dtend 时间段截止
     * @param array $params
     * equipment_id 指定单台仪器
     * @return array
     */
    public function sampleList($dtstart = 0, $dtend = 0, $params = [])
    {
        $this->_ready('gpui');

        $dtstart = Date::get_day_start();
        $dtend = Date::get_day_end();

        // $type = Cal_Component_Model::TYPE_VEVENT;
        $pre_selector = [];
        // $pre_selector[] = "cal_component[type={$type}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]<component";
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
        $selector = "eq_sample[dtsubmit={$dtstart}~{$dtend}]:sort(dtsubmit D)";
        $selector .= ":limit($limit_start, $limit_length)";
        if (count($pre_selector)) {
            $selector = '(' . join(', ', $pre_selector) . ') ' . $selector;
        }

        $data = [];
        error_log($selector);
        foreach (Q($selector) as $sample) {
            $equipment = $sample->equipment;
            $info = [
                'id' => str_pad($sample->id, 6, 0, STR_PAD_LEFT),
                'user_id' => $sample->sender->id,
                'user_name' => H($sample->sender->name),
                'avatar' => $sample->sender->icon_url('128'),
                'equipment_name' => H($equipment->name),
                'equipment_group' => H($equipment->group->name),
                'equipment_location' => H($equipment->location),
                'lab_name' => H($sample->lab->name),
                'status' => H(EQ_Sample_Model::$status[$sample->status]),
                'dtsubmit' => $sample->dtsubmit
            ];
            $info = new ArrayIterator($info);

            Event::trigger('gpui.sample_list.extra.info', $reserv, $info);
            $data[] = (array) $info;
        }

        return $data;
    }
}
