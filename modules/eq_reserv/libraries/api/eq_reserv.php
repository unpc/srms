<?php

class API_Eq_Reserv {

    public static $errors = [
        1001 => '请求来源非法!',
        1002 => '找不到对应的仪器!',
        1003 => 'OPENSSL解密签名失败',
        1004 => 'OPENSSL验证签名失败',
        1005 => 'OPENSSL加密签名失败',
        1006 => 'OPENSSL生成签名失败',
        1007 => '仪器未建立初始连接',
        /**
           找不到相应的用户!
           用户验证失败!
           用户无权打开仪器!
           ...
        **/
        ];

    private function _ready() {

        $whitelist = Config::get('api.white_list_eq_reserv', []);
        $whitelist[] = $_SERVER['SERVER_ADDR'];

        if (in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
            return;
        }

        // whitelist 支持ip段配置 形如 192.168.*.*
        foreach ($whitelist as $white) {
            if (strpos($white, '*')) {
                $reg = str_replace('*', '(25[0-5]|2[0-4][0-9]|[0-1]?[0-9]?[0-9])', $white);
                if (preg_match('/^'.$reg.'$/', $_SERVER['REMOTE_ADDR'])) {
                    return;
                }
            }
        }
        throw new API_Exception(self::$errors[1001], 1001);
    }

    private function _get_equipment($device) {

        $equipment = O('equipment', [
                           'control_mode'    => 'computer',
                           'control_address' => $device
                           ]);

        if (!$equipment->id) {
            throw new API_Exception(self::$errors[1002], 1002);
        }

        return $equipment;

    }

    private function get_user_labs($user) {
        if ($GLOBALS['preload']['people.multi_lab']) {
            $lab_ids = [];
            foreach (Q("$user lab") as $lab) {
                $lab_ids[$lab->name] = $lab->id;
            }
            return $lab_ids;
        }
        else {
            $lab = Q("$user lab")->current();
            return [$lab->name => $lab->id];
        }
    }

    function get_current_reserv($uuid) {
        $this->_ready();

        $equipment = $this->_get_equipment($uuid);
        $now = Date::time();
        $ret = [];

        // 找到当前的使用记录
        $record = Q("eq_record[dtend=0][dtstart<=$now][equipment=$equipment]:limit(1)")->current();

        if ($record->id && $record->reserv->id) {
            $ret = [
                'id' => $record->id,
                'dtstart' => $record->reserv->dtstart,
                'dtend' => $record->reserv->dtend,
                ];
        }

        return $ret;
    }


    static function getTopEquipments($num = 10, $year = '', $criteria = []) {
        $tag = $criteria['group'] ? O('tag_group', $criteria['group']) : O('tag_group');
		$dimension = $criteria['dimension'];
        $pre = $dimension == 'equipment' ? "{$tag} equipment" : "{$tag} user";
        
        (!$year || !is_numeric($year)) and $year = date('Y');
        $dtstart = mktime(0, 0, 0, 1, 1, $year);
        $dtend = mktime(0, 0, 0, 1, 1, $year + 1) - 1;

        $num > 100 and $num = 100;

        $db = Database::factory();

        $join = $tag->id ? (
            $dimension == 'equipment' 
            ? " JOIN `_r_tag_group_equipment` as `r` ON `e`.`id` = `r`.`id2` AND `r`.`id1` = {$tag->id} " 
            : " JOIN `_r_user_tag_group` as `r` ON `re`.`user_id` = `r`.`id1` AND `r`.`id2` = {$tag->id} " 
        ) : '';

        $SQL = "SELECT `e`.`id`, `e`.`name`, SUM(`re`.`dtend` - `re`.`dtstart`) as `sum`
        FROM `equipment` as `e`" .
        " JOIN `eq_reserv` as `re` ON `e`.`id` = `re`.`equipment_id` "
        . $join .
        " WHERE `re`.`dtend` > 0 AND `re`.`dtstart` >= %dtstart " .
        " AND `re`.`dtend` <= %dtend AND `re`.`dtstart` > 0 GROUP BY `re`.`equipment_id` " .
        " ORDER BY `sum` DESC LIMIT 0, %num";
        
        $result = $db->query(strtr($SQL, [
            '%dtstart' => $dtstart,
            '%dtend' => $dtend,
            '%num' => (int)$num,
        ]));
        $rows = $result ? $result->rows() : [];

        $equipments = [];

        foreach ($rows as $row) {
            $equipments[] = [
                'id' => $row->id,
                'name' => H($row->name),
                'time' => sprintf('%.2f', (float)($row->sum / 3600))
            ];
        }

        return $equipments;
    }

    /**
     * @param int $num
     * @param string $year
     * @param string $month
     * @param array $criteria
     * @param int $type [pm,py]
     * @return array
     */
    static function getTopMonthEquipments($num = 10, $year = '', $month = '', $criteria = [], $type = '')
    {
        $tag = $criteria['group'] ? O('tag_group', $criteria['group']) : O('tag_group');
        $dimension = $criteria['dimension'];
        $pre = $dimension == 'equipment' ? "{$tag} equipment" : "{$tag} user";

        (!$year || !is_numeric($year)) and $year = date('Y');
        (!$month || !is_numeric($month)) and $month = date('m');
        $dtstart = mktime(0, 0, 0, $month, 1, $year);
        $dtend = Date::get_month_end($dtstart);
        $now = time();
        if ($type) {
            switch ($type) {
                case 'pm':
                    $m = date('m', $now) - 1 > 0 ? date('m', $now) - 1 : 12;
                    $y = date('m', $now) - 1 > 0 ? date('Y', $now) : date('Y', $now) - 1;
                    $dtstart = mktime(0, 0, 0, $m, 1, $y);
                    $dtend = mktime(23, 59, 59, $m, date('t', $dtstart), $y);
                    break;
            }
        }
        $num > 100 and $num = 100;

        $db = Database::factory();

        $join = $tag->id ? (
        $dimension == 'equipment'
            ? " JOIN `_r_tag_group_equipment` as `r` ON `e`.`id` = `r`.`id2` AND `r`.`id1` = {$tag->id} "
            : " JOIN `_r_user_tag_group` as `r` ON `re`.`user_id` = `r`.`id1` AND `r`.`id2` = {$tag->id} "
        ) : '';

        $SQL = "SELECT `e`.`id`, `e`.`name`, SUM(`re`.`dtend` - `re`.`dtstart`) as `sum`
        FROM `equipment` as `e`" .
            " JOIN `eq_reserv` as `re` ON `e`.`id` = `re`.`equipment_id` "
            . $join .
            " WHERE `re`.`dtend` > 0 AND `re`.`dtstart` >= %dtstart " .
            " AND `re`.`dtend` <= %dtend AND `re`.`dtstart` > 0 GROUP BY `re`.`equipment_id` " .
            " ORDER BY `sum` DESC LIMIT 0, %num";

        $result = $db->query(strtr($SQL, [
            '%dtstart' => $dtstart,
            '%dtend' => $dtend,
            '%num' => (int)$num,
        ]));
        $rows = $result ? $result->rows() : [];

        $equipments = [];

        foreach ($rows as $row) {
            $equipments[] = [
                'id' => $row->id,
                'name' => H($row->name),
                'time' => sprintf('%.2f', (float)($row->sum / 3600))
            ];
        }

        return $equipments;
    }
    
    static function getTopUsers($num=10, $year='')
    {
        (!$year || !is_numeric($year)) and $year = date('Y');
        $dtstart = mktime(0, 0, 0, 1, 1, $year);
        $dtend = mktime(0, 0, 0, 1, 1, $year + 1) - 1;

        $num > 100 and $num = 100;

        $db = Database::factory();

        $SQL = "SELECT `u`.`id`, `u`.`name`, SUM(re.dtend - re.dtstart) as `sum` " .
        " FROM `user` as `u`" .
        " JOIN `eq_reserv` as `re` ON `u`.`id` = `re`.`user_id` " .
        " WHERE `re`.`dtend` > 0 AND `re`.`dtstart` >= %dtstart " .
        " AND `re`.`dtend` <= %dtend GROUP BY `re`.`user_id` " .
        " ORDER BY `sum` DESC LIMIT 0, %num";

        $rows = $db->query(strtr($SQL, [
                '%dtstart' => $dtstart,
                '%dtend' => $dtend,
                '%num' => (int)$num
            ]))->rows();

        $users = [];

        foreach ($rows as $row) {
            $users[] = [
                'id' => $row->id,
                'name' => H($row->name),
                'time' => (int)($row->sum / 3600)
            ];
        }

        return $users;
    }

    static function getNewUsers($num = 10, $time = '', $criteria = []) {
        $tag = $criteria['group'] ? O('tag_group', $criteria['group']) : O('tag_group');
		$dimension = $criteria['dimension'];
        $pre = $dimension == 'equipment' ? "{$tag} equipment" : "{$tag} user";

        (!$time || !is_numeric($time)) and $time = Date::time();
        $num > 100 and $num = 100;

        $db = Database::factory();
        $join = $tag->id ? (
            $dimension == 'equipment' 
            ? " JOIN `_r_tag_group_equipment` as `r` ON `re`.`equipment_id` = `r`.`id2` AND `r`.`id1` = {$tag->id} " 
            : " JOIN `_r_user_tag_group` as `r` ON `re`.`user_id` = `r`.`id1` AND `r`.`id2` = {$tag->id} " 
        ) : '';

        $SQL = "SELECT `id` FROM `eq_reserv` AS `re` WHERE `ctime` <= %time AND `dtend` > 0 "
        . $join .
        " ORDER BY `dtstart` DESC LIMIT 0, %num";

        $result = $db->query(strtr($SQL, [
            '%time' => $time,
            '%num' => (int)$num,
        ]));
        $rows = $result ? $result->rows() : [];

        $query = [];
        foreach ($rows as $row) {
            $reserv = O('eq_reserv', $row->id);
            if ($reserv->id) {
                $query[] = [
                    'name' => H($reserv->user->name ?: T('匿名用户')),
                    'icon' => $reserv->user->icon_url(),
                    'group' => H($reserv->user->group->name),
                    'equipment' => H($reserv->equipment->name),
                    'dtstart' => Date::format($reserv->dtstart),
                    'dtend' => Date::format($reserv->dtend)
                ];
            }
        }

        return $query;
    }

    public function reserv_list($equipment_id)
	{
        $equipment = O('equipment', $equipment_id);

        if (!$equipment->id) {
            throw new API_Exception(self::$errors[1002], 1002);
        }

        $now = Date::time();

        $dtstart = Date::get_week_start($now);

        $dtend = Date::get_week_end($now);

        $reservs = Q("eq_reserv[equipment={$equipment}][dtstart>={$dtstart}][dtend<={$dtend}]");

        $data = [];

		foreach ($reservs as $reserv) {
			$data[] = [
				'id' => $reserv->id,
                'title' => H($reserv->component->name),
                'user_id' => $reserv->user->id,
                'user_name' => H($reserv->user->name),
                'avatar' => $reserv->user->icon_url('128'),
                'labs' => $this->get_user_labs($reserv->user),
                'start' => $reserv->dtstart,
	            'end' => $reserv->dtend,
            ]; 
        }
        
		return $data;
	}
}
