<?php

/**
 * RQ230114 - 将指定时间范围内的仪器统计未闭合记录计入当月统计
 */
class CLI_Segment_EQ_Record
{
    /**
     * 拆分规则
     *      时间拆分：
     *          2023-02-28 12:00:00 ~ 2023-03-01 15:00:00  => 2023-02-28 12:00:00 ~ 2023-02-28 23:59:59 + 2023-03-01 00:00:00 ~ 2023-03-01 15:00:00
     *      计费拆分:
     *          按时间计费：耗材费计入最后一天；开机费计入第一天；计费时长按正常拆开的计算即可；冷却时间计入最后一天；预热时间计入第一天
     *          按次数计费：耗材费计入最后一天；开机费计入第一天；按次计费费用计入最后一天；
     *          按样品数计费：耗材费计入最后一天；开机费计入第一天；按样品计费费用计入最后一天；
     */ 
    public static function segment()
    {
        $params = func_get_args();
        $start = (isset($params[0]) && self::isTimestamp($params[0])) ? $params[0] : strtotime(date('Y-m-d 00:00:00', strtotime("-3 month")));
        $end = (isset($params[1]) && self::isTimestamp($params[1])) ? $params[1] : time();

        $records = Q("eq_record[dtstart={$start}~{$end}]:sort(id D)");

        foreach ($records as $record) {
            $dtstart = $record->dtstart;
            $dtend = $record->dtend ?: time();
            $last_record_first_day = strtotime(date("Y-m-01 00:00:00", $dtend)); // 最后一条记录所在月份第一天
            $diff_records = self::getDiffRecords($dtstart, $dtend);
            $new_records = [];

            if (count($diff_records)) {
                // 修改原始的使用记录
                $record->cancel_minimum_fee = 1; // 取消开机费
                $record->cancel_lead_time = 1; // 取消预热时间
                $record->dtstart = $last_record_first_day;
                $record->save();

                // 原始的使用记录重新计费
                Event::trigger('extra.form.post_submit', $record, []);

                $charge = O('eq_charge', ['source' => $record]);

                foreach($diff_records as $index => $diff_record) {
                    // 新增一条使用记录
                    $new_record = clone $record;
                    $new_record->samples = 0; // 样品数
                    $new_record->materials = null; // 取消耗材费
                    $new_record->cancel_minimum_fee = $index > 0 ? 1 : 0; // 除第一条记录外，取消开机费
                    $new_record->cancel_lead_time = $index > 0 ? 1 : 0; // 除第一条记录外，取消预热时间
                    $new_record->cancel_post_time = 1; // 取消冷却时间
                    $new_record->cancel_unit_price = 1; // 按次数、样品计费，取消重复计费
                    $new_record->dtstart = $diff_record['dtstart'];
                    $new_record->dtend = $diff_record['dtend'];
                    $new_record->is_segment = $record->id;
                    $new_record->save();

                    // 新的使用记录重新计费
                    Event::trigger('extra.form.post_submit', $new_record, []);

                    $_charge = O('eq_charge', ['source' => $new_record]);
                    if ($charge->id && $charge->custom && $_charge->id) {
                        $_charge->amount = 0;
                        $_charge->custom = 1;
                        $_charge->save();
                    }

                    $new_records[] = $new_record->id;
                }

                Log::add(strtr('[eq_record] 使用记录 [%record_id] 被拆分成 [%new_records]', [
                    '%record_id' => $record->id,
                    '%new_records' => join(',', $new_records),
                ]), 'record');
            }
        }
    }


    /**
     * 判断是不是时间戳(0-2147483647)
     *
     * @param int $timestamp
     * @return false|int
     */
    private static function isTimestamp(int $timestamp)
    {
        return strtotime(date('Y-m-d H:i:s', $timestamp)) === $timestamp;
    }

    /**
     * 两个时间相差月份
     */
    private static function getDiffRecords($dtstart, $dtend)
    {
        $res = [];
        $index = 0;

        while(date('Ym', $dtstart) != date('Ym', $dtend)) {
            $dtstart_month = date('Y-m', $dtstart);

            $res[$index]['dtstart'] = $dtstart;
            $res[$index]['dtend'] = strtotime(date("Y-m-d 23:59:59", strtotime("last day of {$dtstart_month}")));

            $dtstart = $res[$index]['dtend'] + 1;
            $index++;
        }

        return $res;
    }
}