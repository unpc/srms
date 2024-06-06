<?php 

class API_Sample extends API_Common {

    function get_samples($params = []) {
        $this->_ready();
        $selector = "eq_sample";

        if ($params['equipment']) {
            $equipment = O('equipment', $params['equipment']);
            $selector .= "[equipment={$equipment}]";
        }

        $now = Date::time();
        $dtstart = $params['dtstart'] ? : Date::get_week_start($now);
        $dtend = $params['dtend'] ? : Date::get_week_end($now);
        $selector .= "[dtsubmit=$dtstart~$dtend]";

        if ($params['order']) foreach ($params['order'] as $order) {
            list($field, $sort) = $order;
            $sort = strtoupper($sort);
            $selector .= ":sort({$field} {$sort})";
        }

        list($start, $end) = $params['limit'];
        $start = $start ? : 0;
        $end = $end ? : 100;
        $selector .= ":limit({$start}, {$end})";

		$samples = Q($selector);
		$info = [];

		if ($samples->total_count()) foreach ($samples as $sample) {
            $charge = O('eq_charge', ['source' => $sample]);
            $info[] = [
                'id' => $sample->id, // 预约ID
                'user' => [
                    $sample->sender->id => $sample->sender->name
                ], // 用户信息
                'lab' => $sample->project->id ? [
                    $sample->project->lab->id => $sample->project->lab->name
                ] : Q("{$sample->sender} lab")->to_assoc('id', 'name'), // 课题组信息
                'equipmentId' => $sample->equipment->id, // 仪器ID
                'dtsubmit' => $sample->dtsubmit,
                'dtstart' => $sample->dtstart, // 开始时间
                'dtend' => $sample->dtend, // 结束时间
                'count' => $sample->count, // 样品数
                'amount' => $charge->amount, // 收费金额
            ];
        }
        return $info;
	}
}
