<?php
class API_GPUI_Graph extends API_Common
{
    /**
     * 关系图数据结构
     * {
     *    datas: [...{ id, name, weight }],
     *    links: [...{ id, sourceId, targetId, weight }]
     * }
     */
    public function user_equipment($params = [])
    {
        $this->_ready('gpui');
        $dtstart = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? Date::time() - 12 * 30 * 86400 : $params['dtstart'];
        $dtend = (!$params['dtend'] || !is_numeric($params['dtend'])) ? Date::time() : $params['dtend'];

        if ($params['member_type']) {
            $member_type = join(',', $params['member_type']);
        } else {
            $member_type = join(',', array_keys(User_Model::$members['教师']));
        }
        $num = $params['num'] ?? 8;

        $datas = new ArrayIterator;
        $links = new ArrayIterator;
        $db = Database::factory();
        $SQL = "SELECT `u`.`id` AS `uid`, COUNT(`r`.`id`) AS `cnt` FROM `eq_record` AS `r` " .
            " LEFT OUTER JOIN `user` AS `u` ON (`r`.`user_id` = `u`.`id`) " .
            " WHERE `u`.`member_type` IN (%member_type)" .
            " AND `dtstart` BETWEEN %start AND %end " .
            " GROUP BY `user_id` ORDER BY COUNT(`r`.`id`) DESC LIMIT %num";
        $q = $db->query(strtr($SQL, [
            '%member_type' =>  $member_type,
            '%start' => (int) $dtstart,
            '%end' => (int) $dtend,
            '%num' => (int) $num,
        ]));

        if ($q) {
            foreach ($q->rows() as $res) {
                $user = O('user', $res->uid);
                if ($user->id) {
                    $datas[(string) $user] = [
                        'id' => (string) $user,
                        'name' => $user->name,
                        'weight' => $res->cnt,
                    ];
                    $this->_fillLinks($user, ['dtstart' => $dtstart, 'dtend' => $dtend], $datas, $links);
                }
            }
        }

        return ['datas' => array_values((array)$datas), 'links' => array_values((array)$links)];
    }

    private function _fillLinks($user, $params, $datas, $links)
    {
        $db = Database::factory();
        $SQL = "SELECT `u`.`id` AS `uid`, `e`.`id` AS `eid`, COUNT(`r`.`id`) AS `cnt` FROM `eq_record` AS `r` " .
            " LEFT OUTER JOIN `user` AS `u` ON (`r`.`user_id` = `u`.`id`) " .
            " LEFT OUTER JOIN `equipment` AS `e` ON (`r`.`equipment_id` = `e`.`id`) " .
            " WHERE `u`.`id` = %uid" .
            " AND `dtstart` BETWEEN %start AND %end " .
            " GROUP BY `r`.`user_id`, `r`.`equipment_id` ";

        $q = $db->query(strtr($SQL, [
            '%uid' =>  $user->id,
            '%start' => (int) $params['dtstart'],
            '%end' => (int) $params['dtend'],
        ]));

        if ($q) {
            foreach ($q->rows() as $res) {
                $equipment = O('equipment', $res->eid);
                if ($equipment->id) {
                    $datas[(string) $equipment] = [
                        'id' => (string) $equipment,
                        'name' => $equipment->name,
                        'weight' => "1",
                    ];

                    $links[] = [
                        'id' => uniqid(),
                        'sourceId' => (string) $user,
                        'targetId' => (string) $equipment,
                        'weight' => $res->cnt
                    ];
                }
            }
        }
    }
}
