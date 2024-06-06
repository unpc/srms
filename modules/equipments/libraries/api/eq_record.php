<?php

class API_EQ_Record {

    public static $errors = [
        1001 => '请求来源非法!',
        1002 => '找不到对应的仪器!',
        1003 => 'OPENSSL解密签名失败',
    ];

    public function record_list($equipment_id, $page = 1, $step = 20) {
        $equipment = O('equipment', $equipment_id);

        if (!$equipment->id) {
            throw new API_Exception(self::$errors[1002], 1002);
        }

        $now = Date::time();

        $start = ($page - 1 ) * $step;

        $records = Q("eq_record[equipment={$equipment}][dtend>=0][dtend<={$now}]:sort(dtstart D):limit({$start}, {$step})");

        $data = [];

		foreach ($records as $record) {
			$c = O('eq_charge', ['source' => $record]);
			if ($GLOBALS['preload']['people.multi_lab']) {
				$lab = $record->project->lab;
			} else {
				$lab = Q("$user lab")->current();
            }

			$data[] = [
				'id' => $record->id,
				'no' => str_pad($record->id, 6, 0, STR_PAD_LEFT),
				'user_name' => H($record->user->name),
				'lab' => H($lab->name),
				'dtstart' => $record->dtstart,
				'dtend' => $record->dtend,
				'sample' => (int)$record->samples,
				'amount' => $c->id ? $c->amount : 0,
				'auto_amount' => $c->id ? $c->auto_amount : 0
			];
		}

		return $data;
	}
	
	# 用户使用机时排行
	static function time_rank($num = 10, $start = 0, $end = 0)
	{
		$num > 100 and $num = 100;
		if(!$start || !$end){
			$start = mktime(0, 0, 0, 1, 1, date('Y'));
            $end = mktime(0, 0, 0, 1, 1, date('Y') + 1) - 1;
		}

		$db = Database::factory();

		$SQL = "SELECT `u`.`id`, `u`.`name`, SUM(`r`.`dtend` - `r`.`dtstart`) as `sum`" .
			" FROM `user` as `u`" .
			" JOIN `eq_record` as `r` ON `u`.`id` = `r`.`user_id`" .
			" WHERE `r`.`dtend` > 0 AND `r`.`dtend` BETWEEN %start AND %end" .
			" GROUP BY `u`.`id`" .
			" ORDER BY `sum` DESC LIMIT 0, %num";

		$rows = $db->query(strtr($SQL, [
			'%num' => (int) $num,
			'%start' => (int) $start,
			'%end' => (int) $end,
		]))->rows();

		$users = [];

		foreach ($rows as $row) {
			$users[] = [
				'id' => $row->id,
				'name' => H($row->name),
				'time' => sprintf('%.2f', (float) ($row->sum / 3600))
			];
		}

		return $users;
	}
}