<?php
/**
 * 收费概况
 */
class API_GPUI_Eq_Charge extends API_Common
{
    /**
     * 使用费用统计
     *  @param integer $num 跨度
     * @param string $format 单位
     * 比如 默认 3, y 就是3年内 以年为单位统计
     * @return void
     */
    public function chargeOutcomeUse($num = 3, $format = 'y')
    {
        $this->_ready('gpui');

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
            $outcome = Q("billing_transaction[source=local][!transfer][outcome][!manual][ctime>={$dtstart}][ctime<{$dtend}]")->SUM('outcome');
            $data[Date::format($dtstart, $date_format)] = $outcome ? (float) sprintf('%.2f', $outcome) : 0;
        }

        return $data;
    }

    /**
     * 按校内外区分，使用费用统计
     *  @param integer $start 开始时间
     *  @param integer $end 结束时间
     * 比如 默认 0, 0 就是累计所有
     * 使用者组织机构为校内，收费包含：仪器使用收费的总和使用者组织机构为非校内，收费包含：送仪器使用收费的总和系统所有收费总和
     * @return void
     */
    public function chargeOutcomeUseGroup($start = 0, $end = 0)
    {
        $root = Tag_Model::root('group');
        $group = O('tag', ['name' => '校外', 'parent' => $root, 'root'=>$root]);

        $use_group = "{$group} user billing_transaction[source=local][!transfer][outcome][!manual]";
        $use_all = "billing_transaction[source=local][!transfer][outcome][!manual]";

        if ($start) {
            $use_group .= "[ctime>=$start]";
            $use_all .= "[ctime>=$start]";
        }
        if ($end) {
            $use_group .= "[ctime<=$end]";
            $use_all .= "[ctime<=$end]";
        }

        $all = Q($use_all)->SUM('outcome');
        $outside = Q($use_group)->SUM('outcome');

        $data = [
            "all" => $all ? (float) sprintf('%.2f', $all):0,
            "outside" => $outside ? (float) sprintf('%.2f', $outside):0,
            "inside" => ($all-$outside) ? (float) sprintf('%.2f', ($all-$outside)):0,
        ];

        return $data;
    }
}
