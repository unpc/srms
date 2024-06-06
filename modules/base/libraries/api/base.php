<?php

class API_Base {

    function location_rate () {

        $db = Database::factory();

        $SQL = "SELECT `area`, COUNT(`user_id`) as `count` FROM
        (SELECT DISTINCT `area`, `user_id` 
        FROM `action`) AS T1
        GROUP BY `area`";

        $rows = $db->query($SQL)->rows();

        $areas = [];

        foreach ($rows as $row) {
            $areas[] = [
                'area' => $row->area,
                'count' => (int)$row->count,
            ];
        }

        return $areas;
    }

}
