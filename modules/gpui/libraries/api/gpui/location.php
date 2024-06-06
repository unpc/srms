<?php
class API_GPUI_Location extends API_Common
{
    public function ip_area($params = [])
    {
        $this->_ready('gpui');
        $dtstart = (!$params['dtstart'] || !is_numeric($params['dtstart'])) ? Date::time() - 12 * 30 * 86400 : $params['dtstart'];
        $dtend = (!$params['dtend'] || !is_numeric($params['dtend'])) ? Date::time() : $params['dtend'];

        $db = Database::factory();
        $SQL = "SELECT `a`.`ip` AS `ip`, COUNT(`a`.`id`) AS `cnt`, `a`.`lon`, `a`.`lat`, `a`.`area` FROM `action` AS `a` " .
            " WHERE `date` BETWEEN %start AND %end " .
            " AND `lon` <> 0 " .
            " GROUP BY `lon`, `lat` ORDER BY COUNT(`a`.`id`) DESC";
        $q = $db->query(strtr($SQL, [
            '%start' => (int) $dtstart,
            '%end' => (int) $dtend,
        ]));

        $datas = [];
        $total = 0;
        if ($q) {
            foreach ($q->rows() as $res) {
                $total += $res->cnt;
                $datas[] = [
                    'ip' => $res->ip,
                    'area' => $res->area,
                    'lon' => $res->lon,
                    'lat' => $res->lat,
                    'count' => $res->cnt,
                ];
            }
        }

        return [
            'total' => $total,
            'data' => $datas,
        ];
    }
}
