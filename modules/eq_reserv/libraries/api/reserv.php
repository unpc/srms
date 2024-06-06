<?php 

class API_Reserv extends API_Common {

    function get_reservs($params = []) {
        $this->_ready();
        $selector = "eq_reserv";

        if ($params['equipment']) {
            if (is_numeric($params['equipment'])) {
                $equipment = O('equipment', $params['equipment']);
            }

            if (!$equipment->id) {
                $equipment = O('equipment', ['ref_no' => $params['equipment']]);
            }
            $selector .= "[equipment={$equipment}]";
        }

        $now = Date::time();
        $dtstart = $params['dtstart'] ? : Date::get_week_start($now);
        $dtend = $params['dtend'] ? : Date::get_week_end($now);
        $selector .= "[dtend=$dtstart~$dtend]";

        if ($params['order']) foreach ($params['order'] as $order) {
            list($field, $sort) = $order;
            $sort = strtoupper($sort);
            $selector .= ":sort({$field} {$sort})";
        }

        list($start, $end) = $params['limit'];
        $start = $start ? : 0;
        $end = $end ? : 100;
        $selector .= ":limit({$start}, {$end})";
        
		$reservs = Q($selector);
		$info = [];

		if ($reservs->total_count()) foreach ($reservs as $reserv) {
            $charge = O('eq_charge', ['source' => $reserv]);
            $info[] = [
                'id' => $reserv->id, // 预约ID
                'user' => [
                    $reserv->user->id => $reserv->user->name
                ], // 用户信息
                'lab' => $reserv->project->id ? [
                    $reserv->project->lab->id => $reserv->project->lab->name
                ] : Q("{$reserv->user} lab")->to_assoc('id', 'name'), // 课题组信息
                'equipmentId' => $reserv->equipment->id, // 仪器ID
                'dtstart' => $reserv->dtstart, // 开始时间
                'dtend' => $reserv->dtend, // 结束时间
                'type' => $reserv->component->type, // 预约类型
                'amount' => $charge->amount, // 收费金额
            ];
        }
        return $info;
    }
    
    public function get_reserv_status($reserv_id)
    {
        // error_log('call get_reserv_status .. ' . $reserv_id);
        if (!$reserv_id) {
            throw new API_Exception(self::$errors[404], 404);
        }

        return O('eq_reserv', $reserv_id)->get_status();
    }
}
