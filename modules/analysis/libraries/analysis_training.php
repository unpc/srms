<?php

class Analysis_Training {

    static function init($e, $rest) {
        $fields = Config::get('schema.analysis_training')['fields'];
        $columns = [];
        $columns['id'] = ['name' => '标识', 'type' => 'int'];
        foreach ($fields as $key => $field) {
            if (in_array($key, ['ctime', 'mtime'])) continue;
            $columns[$key]['associate'] = $field['oname'];
            $columns[$key]['name'] = $field['comment'];
            $columns[$key]['type'] = $field['type'] == 'int' ? 'int' : 'string';
        }
        $columns['time']['type'] = 'time';

        $response = $rest->post('origin', [
            'form_params' => [
                'key' => 'training',
                'name' => '仪器培训表',
                'type' => 'source',
                'columns' => $columns
            ],
            'headers' => [
                'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
            ]
        ]);
        $body = $response->getBody();
        $result = json_decode($body->getContents());
        
        $result = $result ? '成功' : '失败';
        echo "   \e[32m 仪器培训表创建{$result} \e[0m\n";

    }
    
    static function mark($e, $object, $old = [], $new = []) {
        if ($old && $new && !array_diff($old, $new)) return TRUE;
        $equipment = $object->equipment;
        
        // 对产生任何改动的数据做记录
        $time = Date::get_day_start();
        $date = Date::get_day_start($object->dtend);
        $mark = O('analysis_mark_training', [
            'equipment' => $equipment
        ]);
        
        if (!$mark->id || $mark->time != $time) {
            $mark->equipment = $equipment;
            $mark->date = $date;
            $mark->time = $time;
            $mark->save();
        }
        
        return TRUE;
    }

    static function increment($e, $rest) {
        // 对产生任何改动的数据做记录
        $time = Date::get_day_start(strtotime('-1 day'));
        // $time = Date::get_day_start();

        $marks = Q("analysis_mark_training[time={$time}]");
        foreach ($marks as $mark) {
            $mark->polymerize();
        }
        echo "   \e[32m 数据聚合完成 \e[0m\n";
        
        // 至此数据更新完毕
        // 至此数据更新完毕
        $start = Date::get_day_start();
        $analysis = Q("analysis_training[mtime>{$start}]");
        $data = [];
        foreach ($analysis as $item) {
            $row = [];
            $row['id'] = $item->id;
            $row['equipment'] = $item->equipment->id;
            $row['student_count'] = $item->student_count;
            $row['teacher_count'] = $item->teacher_count;
            $row['other_count'] = $item->other_count;
            $row['time'] = date('Y-m-d', $item->time);
            $data[] = $row;
        }

        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'training',
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            echo "   \e[32m 培训数据[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
    }

    static function full($e, $rest) {
        Log::add("[push] training pushing", 'analysis');
        $now = Date::get_day_start();
        $db = Database::factory();
        // 做全量数据更新时，聚合每天有记录的数据 再进行更新
        $sql = "SELECT `equipment_id`,
        DATE_FORMAT(FROM_UNIXTIME(`mtime`), '%Y-%m-%d') AS `time`
        FROM `ue_training`";
        $rows = $db->query($sql)->rows();

        if (count($rows)) foreach($rows as $row) {
            $mark = O('analysis_mark_training');
            $mark->equipment = O('equipment', $row->equipment_id);
            $mark->date = Date::get_day_start(strtotime($row->time));
            $mark->time = $now;
            $mark->save();
            $mark->polymerize();
        }
        
        // 至此数据更新完毕
        $analysis = Q("analysis_training");
        $data = [];
        foreach ($analysis as $item) {
            $row = [];
            $row['id'] = $item->id;
            $row['equipment'] = $item->equipment->id;
            $row['student_count'] = $item->student_count;
            $row['teacher_count'] = $item->teacher_count;
            $row['other_count'] = $item->other_count;
            $row['apply_count'] = $item->apply_count;
            $row['time'] = date('Y-m-d', $item->time);
            $data[] = $row;
        }

        $purge = true;
        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'training',
                    'purge' => $purge,
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            $purge = false;
            echo "   \e[32m 培训数据[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
        Log::add("[push] training push done", 'analysis');
    }

    static function student_count_refresh ($e, $db, $mark) {
        $start = $mark->date;
        $end = Date::get_day_end($start);

        $query =
       "SELECT
        COUNT(`ue`.`id`)
        FROM
        `ue_training` AS `ue`
        INNER JOIN `user` AS `u` ON `ue`.`user_id` = `u`.`id`
        WHERE
        `ue`.`equipment_id` = %d
        AND `ue`.`status` = %d
        AND `u`.`member_type` BETWEEN 0 AND 9
        AND `ue`.`mtime` BETWEEN %d AND %d";

        $e->return_value = $db->value($query, $mark->equipment->id, UE_Training_Model::STATUS_APPROVED, 
        $start, $end);
        return FALSE;
    }

    static function teacher_count_refresh ($e, $db, $mark) {
        $start = $mark->date;
        $end = Date::get_day_end($start);

        $query =
       "SELECT
        COUNT(`ue`.`id`)
        FROM
        `ue_training` AS `ue`
        INNER JOIN `user` AS `u` ON `ue`.`user_id` = `u`.`id`
        WHERE
        `ue`.`equipment_id` = %d
        AND `ue`.`status` = %d
        AND `u`.`member_type` BETWEEN 10 AND 19
        AND `ue`.`mtime` BETWEEN %d AND %d";

        $e->return_value = $db->value($query, $mark->equipment->id, UE_Training_Model::STATUS_APPROVED, 
        $start, $end);
        return FALSE;
    }

    static function other_count_refresh ($e, $db, $mark) {
        $start = $mark->date;
        $end = Date::get_day_end($start);

        $query = 
       "SELECT
        COUNT(`ue`.`id`)
        FROM
        `ue_training` AS `ue`
        INNER JOIN `user` AS `u` ON `ue`.`user_id` = `u`.`id`
        WHERE
        `ue`.`equipment_id` = %d
        AND `ue`.`status` = %d
        AND `u`.`member_type` BETWEEN 20 AND 29
        AND `ue`.`mtime` BETWEEN %d AND %d";

        $e->return_value = $db->value($query, $mark->equipment->id, UE_Training_Model::STATUS_APPROVED, 
        $start, $end);
        return FALSE;
    }

    static function apply_count_refresh ($e, $db, $mark) {
        $start = $mark->date;
        $end = Date::get_day_end($start);

        $query =
            "SELECT
        COUNT(`ue`.`id`)
        FROM
        `ue_training` AS `ue`
        INNER JOIN `user` AS `u` ON `ue`.`user_id` = `u`.`id`
        WHERE
        `ue`.`equipment_id` = %d
        AND `ue`.`status` = %d
        AND `ue`.`mtime` BETWEEN %d AND %d";

        $e->return_value = $db->value($query, $mark->equipment->id, UE_Training_Model::STATUS_APPLIED,
            $start, $end);
        return FALSE;
    }

}
