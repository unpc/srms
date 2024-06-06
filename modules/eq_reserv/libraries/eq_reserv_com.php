<?php

class EQ_Reserv_Com {

	static function views ($e, $components) {
        $me = L('ME');
        if (!$me->id) return TRUE;

        if ($me->access('管理组织机构')) {
            // 预约机时/全机时的仪器排行
            $components[] = [
                'id' => 'plumpness',
                'key' => 'plumpness',
                'name' => '仪器预约饱满度排行',
            ];
        }
    }

    static function view_plumpness ($e, $query) {
        $me = L('ME');
        $type = $query['type'] ? : 'bar';

        if ($me->access('管理组织机构')) {
            $group = $me->group ? : Tag_Model::root('group')->id;
            $tag = $query['tag'] ? : 0;

            $reserv = Q('eq_reserv[dtstart>0]:sort(dtstart ASC)')->current();
            $start = $reserv->dtstart ? : Date::time();
            $end = Date::time();

            $sql = "SELECT `v`.`equipment_id` AS `id`,
            `e`.`name` AS `name`, 
            SUM(`v`.`dtend` - `v`.`dtstart`) AS `time` 
            FROM `eq_reserv` AS `v`
            INNER JOIN `equipment` AS `e`
            ON `v`.`equipment_id` = `e`.`id`
            INNER JOIN (`_r_tag_group_equipment` AS `r1`, `tag_group` `t1`)
            ON (`r1`.`id1` = :group AND `r1`.`id2` = `v`.`equipment_id` AND `r1`.`id1` = `t1`.`id`) ";

            // 如果有仪器分类的查询条件再做拼接
            if ($tag) $sql .= "INNER JOIN (`_r_tag_equipment_equipment` AS `r2`, `tag_equipment` `t2`)
            ON (`r2`.`id1` = :tag AND `r2`.`id2` = `v`.`equipment_id` AND `r2`.`id2` = `t2`.`id`)";
            
            // 要取有预约开始之后到现在的预约饱满度
            $sql .= "WHERE `v`.`dtstart` >= :start
            AND `v`.`dtend` <= :end
            GROUP BY `v`.`equipment_id` ORDER BY `time` DESC LIMIT 0, 10";
            
            $exec = strtr($sql, [
                ':group' => $group->id,
                ':tag' => $tag,
                ':start' => $start,
                ':end' => $end
            ]);

            $db = Database::factory();
            $query = $db->query($exec);
            if ($query) $result = $query->rows('assoc') ? : [];
            
            $data = [];
            foreach ($result as $item) {
                $data[] = [
                    'name' => $item['name'],
                    'value' => round($item['time'] / ($end - $start) , 2) * 100
                ];
            }
        }
        
        $view = V("eq_reserv:components/view/plumpness/{$type}", [
            'title' => '仪器预约饱满度',
            'data' => $data
        ]);
        
        $e->return_value = [
            'template' => (string)$view
        ];
        return FALSE;
    }

    static function settings_plumpness ($e, $query) {
        $root = Tag_Model::root('equipment');
        $tags = Q("tag_equipment[parent={$root}]");
        
        $view = V("eq_reserv:components/settings/plumpness", [
            'tags' => $tags,
            'query' => $query,
        ]);

        $e->return_value = [
            'type' => 'local',
            'error' => false,
            'template' => (string)$view
        ];
        return FALSE;
    }

}