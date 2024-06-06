<?php 

class API_Record extends API_Common {

    function get_records($params = []) {
        $this->_ready();
        $selector = "eq_record";

        if ($params['equipment']) {
            $equipment = O('equipment', $params['equipment']);
            $selector .= "[equipment={$equipment}]";
        }

        $now = Date::time();
        $dtstart = $params['dtstart'] ? : Date::get_week_start($now);
        $dtend = $params['dtend'] ? : Date::get_week_end($now);
        // 22568 （3）17Kong/Sprint-252：1.4.0全面测试：机主在平板上刷卡上机，点击仪器管理，点击所有记录，点击使用记录，未显示使用中的记录
        $selector .= ($params['dtend'] && $params['dtend'] <= $now) ? "[dtend=$dtstart~$dtend]" : "[dtend=$dtstart~$dtend|dtend=0]";

        if ($params['order']) foreach ($params['order'] as $order) {
            list($field, $sort) = $order;
            $sort = strtoupper($sort);
            $selector .= ":sort({$field} {$sort})";
        }

        list($start, $end) = $params['limit'];
        $start = $start ? : 0;
        $end = $end ? : 100;
        $selector .= ":limit({$start}, {$end})";

		$records = Q($selector);
		$info = [];

		if ($records->total_count()) foreach ($records as $record) {
            $charge = O('eq_charge', ['source' => $record]);
            $info[] = [
                'id' => $record->id, // 预约ID
                'user' => [
                    $record->user->id => $record->user->name
                ], // 用户信息
                'lab' => $record->project->id ? [
                    $record->project->lab->id => $record->project->lab->name
                ] : Q("{$record->user} lab")->to_assoc('id', 'name'), // 课题组信息
                'equipmentId' => $record->equipment->id, // 仪器ID
                'dtstart' => $record->dtstart, // 开始时间
                'dtend' => $record->dtend, // 结束时间
                'amount' => $charge->amount, // 收费金额
                'samples' => $record->samples, // 样品数
            ];
        }
        return $info;
	}
}
