<?php

class Equipments_Com {

	static function views ($e, $components) {
        $me = L('ME');
        if (!$me->id) return TRUE;
        $is_admin = $me->access('管理所有内容');
        $is_college = $me->access('管理组织机构');
        $is_pi = Q("$me<pi lab")->total_count();
        $is_incharge = !!Q("{$me} equipment.incharge")->total_count();

        if ($is_admin || $is_college || $is_incharge) {
            $components[] = [
                'id' => 'equipmentCount',
                'key' => 'equipmentCount',
                'name' => '仪器数量',
            ];
        }

        if ($is_admin || $is_college) {
            $components[] = [
                'id' => 'useRank',
                'key' => 'useRank',
                'name' => '仪器使用机时排行',
            ];
        }
        
        if ($is_admin) {
            $components[] = [
                'id' => 'sharingRate',
                'key' => 'sharingRate',
                'name' => '开放共享率',
            ];
        }
        
        if ($is_pi) {
            $components[] = [
                'id' => 'useProRank',
                'key' => 'useProRank',
                'name' => '项目关联仪器使用排行',
            ];
        }

        if ($is_incharge) {
            $components[] = [
                'id' => 'totalService',
                'key' => 'totalService',
                'name' => '服务总人数',
            ];
        }

        $components[] = [
            'id' => 'serviceCondition',
            'key' => 'serviceCondition',
            'name' => '个人使用情况',
        ];

        $components[] = [
            'id' => 'feedback',
            'key' => 'feedback',
            'name' => '待反馈记录',
        ];

        $e->return_value = $components;
        return TRUE;
    }

    static function view_sharingRate ($e, $query) {
        $me = L('ME');
        if (!$me->access('管理所有内容')) return FALSE;

        $type = $query['type'] ? : 'text';
        $status = EQ_Status_Model::IN_SERVICE;

        $title = '开放共享率';
        $all = (int)Q("equipment[status={$status}]")->total_count();
        $out_service = (int)Q("equipment[status={$status}][accept_reserv=0][accept_sample=0]")->total_count();
        $data['out_service'] = [
            'name' => '未开放',
            'value' => $out_service,
            'color' => '#3983d4',
        ];
        $in_service = $all - $out_service;
        $data['in_service'] = [
            'name' => '已开放',
            'value' => $in_service,
            'color' => '#ffbe28',
        ];
        
        $view = V("equipments:components/view/sharingRate/{$type}", [
            'title' => $title,
            'data' => $data
        ]);
        
        $e->return_value = [
            'template' => (string)$view
        ];
        return FALSE;
    }

    static function settings_sharingRate ($e, $query) {
        $view = V("equipments:components/settings/sharingRate", [
            'query' => $query
        ]);

        $e->return_value = [
            'type' => 'local',
            'error' => false,
            'template' => (string)$view
        ];
        return FALSE;
    }

    static function view_useRank ($e, $query) {
        $me = L('ME');
        if (!$me->access('管理所有内容') && !$me->access('管理组织机构')) return FALSE;

        // 针对两种权限做组织机构限制
        if ($me->access('管理所有内容')) $group = $query['group'] ? : 0;
        else if ($me->access('管理组织机构')) $group = $me->group;

        $type = $query['type'] ? : 'bar';
        $tag = $query['tag'] ? : 0;

        $sql = "SELECT `d`.`equipment_id` AS `id`,
        `e`.`name` AS `name`, 
        SUM(`d`.`dtend` - `d`.`dtstart`) AS `time` 
        FROM `eq_record` AS `d`
        INNER JOIN `equipment` AS `e`
        ON `d`.`equipment_id` = `e`.`id` ";

        // 如果有组织机构的查询条件再做拼接
        if ($group) $sql .=" INNER JOIN (`_r_tag_group_equipment` AS `r1`, `tag_group` `t1`)
        ON (`r1`.`id1` = :group AND `r1`.`id2` = `d`.`equipment_id` AND `r1`.`id1` = `t1`.`id`) ";

        // 如果有仪器分类的查询条件再做拼接
        if ($tag) $sql .= " INNER JOIN (`_r_tag_equipment_equipment` AS `r2`, `tag_equipment` `t2`)
        ON (`r2`.`id1` = :tag AND `r2`.`id2` = `d`.`equipment_id` AND `r2`.`id2` = `t2`.`id`) ";
        
        $sql .= " GROUP BY `d`.`equipment_id` ORDER BY `time` DESC LIMIT 0, 10";

        $exec = strtr($sql, [
            ':group' => $group->id,
            ':tag' => $tag,
        ]);
        
        $db = Database::factory();
        $query = $db->query($exec);
        if ($query) $result = $query->rows('assoc') ? : [];
        
        $data = [];
        foreach ($result as $item) {
            $data[] = [
                'name' => $item['name'],
                'value' => round($item['time'] / 3600 , 2)
            ];
        }
        
        $view = V("equipments:components/view/useRank/{$type}", [
            'title' => '仪器使用机时',
            'data' => $data
        ]);
        
        $e->return_value = [
            'template' => (string)$view
        ];
        return FALSE;
    }

    static function settings_useRank ($e, $query) {
        $root = Tag_Model::root('equipment');
        $tags = Q("tag_equipment[parent={$root}]");

        $root = Tag_Model::root('group');
        $groups = Q("tag_group[parent={$root}]");
        
        $view = V("equipments:components/settings/useRank", [
            'tags' => $tags,
            'groups' => $groups,
            'query' => $query,
        ]);

        $e->return_value = [
            'type' => 'local',
            'error' => false,
            'template' => (string)$view
        ];
        return FALSE;
    }

    static function view_useProRank ($e, $query) {
        $me = L('ME');
        $is_pi = Q("$me<pi lab")->total_count;

        if (!Q("$me<pi lab")->total_count()) return FALSE;

        $type = $query['type'] ? : 'bar';

        $sql = "SELECT `d`.`equipment_id` AS `id`,
        `e`.`name` AS `name`, 
        SUM(`d`.`dtend` - `d`.`dtstart`) AS `time` 
        FROM `eq_record` AS `d`
        INNER JOIN `equipment` AS `e`
        ON `d`.`equipment_id` = `e`.`id`
        INNER JOIN `lab_project` AS `p`
        ON `p`.`id` = `d`.`project_id`
        WHERE `p`.`lab_id` IN (:lab)
        GROUP BY `d`.`equipment_id` ORDER BY `time` DESC LIMIT 0, 10";
        
        $exec = strtr($sql, [
            ':lab' => join(',', Q("$me lab")->to_assoc('id', 'id'))
        ]);
        
        $db = Database::factory();
        $query = $db->query($exec);
        if ($query) $result = $query->rows('assoc') ? : [];
        
        $data = [];
        if ($result) foreach ($result as $item) {
            $data[] = [
                'name' => $item['name'],
                'value' => round($item['time'] / 3600 , 2)
            ];
        }
        
        $view = V("equipments:components/view/useProRank/{$type}", [
            'title' => '仪器使用机时',
            'data' => $data
        ]);
        
        $e->return_value = [
            'template' => (string)$view
        ];
        return FALSE;
    }

    static function settings_useProRank ($e, $query) {
        $view = V("equipments:components/settings/useProRank", [
            'query' => $query,
        ]);

        $e->return_value = [
            'type' => 'local',
            'error' => false,
            'template' => (string)$view
        ];
        return FALSE;
    }

    static function view_equipmentCount ($e, $query) {
        $me = L('ME');
        $is_admin = $me->access('管理所有内容');
        $is_college = $me->access('管理组织机构');
        $is_incharge = !!Q("{$me} equipment.incharge")->total_count();

        if (!$is_admin && !$is_college && !$is_incharge) return FALSE;

        $title = '仪器数量';
        if ($is_admin) {
            $count = Q('equipment')->total_count();
        }
        else if ($is_college) {
            $count = Q("({$me->group}) equipment")->total_count();
        }
        else if ($is_incharge) {
            $count = Q("{$me} equipment.incharge")->total_count();
        }
        
        $view = V('equipments:components/view/equipmentCount', [
            'title' => $title,
            'count' => $count ? : 0
        ]);
        
        $e->return_value = [
            'template' => (string)$view
        ];
        return FALSE;
    }

    static function view_totalService ($e, $query) {
        $me = L('ME');

        if (!!Q("{$me} equipment.incharge")->total_count()) {
            $title = '服务总人数';
            $users = Q("({$me} equipment.incharge) eq_record")->to_assoc('user_id', 'user_id');
            $count = count(array_unique($users));
        }
        
        $view = V('equipments:components/view/totalService', [
            'title' => $title,
            'count' => $count
        ]);
        
        $e->return_value = [
            'template' => (string)$view
        ];
        return FALSE;
    }

    static function view_serviceCondition ($e, $query) {
        $me = L('ME');

        if (!$me->id) return FALSE;

        $record_count = Q("eq_record[user={$me}]")->total_count() ? : 0;
        $sample_count = Q("eq_sample[sender={$me}]")->sum('count') ? : 0;
        
        $view = V('equipments:components/view/serviceCondition', [
            'record_count' => $record_count,
            'sample_count' => $sample_count
        ]);
        
        $e->return_value = [
            'template' => (string)$view
        ];
        return FALSE;
    }

    static function view_feedback ($e, $query) {
        $me = L('ME');
        if (!$me->id) return FALSE;

        $now = Date::time();
        $records = Q("eq_record[user=$me][dtend>0][dtend<=$now][status=0]:sort(dtend D)");
        
        $view = V('equipments:components/view/feedback', [
            'records' => $records
        ]);
        
        $e->return_value = [
            'template' => (string)$view
        ];
        return FALSE;
    }

}