<?php

class Analysis_Achievement {

    static function init($e, $rest) {
        if (!Module::is_installed('achievements')) return;
        $fields = Config::get('schema.analysis_achievement')['fields'];
        $columns = [];
        $columns['id'] = ['name' => '标识', 'type' => 'int'];
        foreach ($fields as $key => $field) {
            if ($key == 'date') {
                $columns['date']['type'] = 'datetime';
            } else {
                $columns[$key]['associate'] = $field['oname'];
                $columns[$key]['name'] = $field['comment'];
                $columns[$key]['type'] = $field['type'] == 'int' ? 'int' : 'string';
            }
        }

        $response = $rest->post('origin', [
            'form_params' => [
                'key' => 'equipment_achievement',
                'name' => '仪器成果关系表',
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
        echo "   \e[32m 仪器成果关系表创建{$result} \e[0m\n";
    }

    static function increment($e, $rest) {
        if (!Module::is_installed('achievements')) return;
        self::pour();

        $analysis = Q("analysis_achievement");
        $data = [];
        foreach ($analysis as $item) {
            $row = [];
            $row['id'] = $item->id;
            $row['equipment'] = $item->equipment->id;
            $row['achievement'] = $item->achievement;
            $row['type'] = $item->type;
            $row['date'] = date('Y-m-d H:i:s', $item->date);
            $data[] = $row;
        }

        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'equipment_achievement',
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            echo "   \e[32m 仪器成果关系[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
    }

    static function full($e, $rest) {
        Log::add("[push] achievements pushing", 'analysis');
        if (!Module::is_installed('achievements')) return;
        self::pour();
        
        // 至此数据更新完毕
        $analysis = Q("analysis_achievement");
        $data = [];
        foreach ($analysis as $item) {
            $row = [];
            $row['id'] = $item->id;
            $row['equipment'] = $item->equipment->id;
            $row['achievement'] = $item->achievement;
            $row['type'] = $item->type;
            $row['date'] = date('Y-m-d H:i:s', $item->date);
            $data[] = $row;
        }

        $purge = true;
        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'equipment_achievement',
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
            echo "   \e[32m 仪器成果关系[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
        Log::add("[push] achievements push done", 'analysis');
    }

    static function pour() {
        $db = Database::factory();
        $s = 'TRUNCATE TABLE `analysis_achievement`';
        $r = $db->query($s);

        $sql = "(SELECT `r1`.`id1` `achievement`, 
        `r1`.`id2` `equipment`,  
        'publication' `type`, 
        `p`.`date` 
        FROM `publication` `p` 
        INNER JOIN `_r_publication_equipment` `r1` 
        INNER JOIN `equipment` `e1` 
        ON `r1`.`id1` = `p`.`id`
        AND `r1`.`id2` = `e1`.`id`) 
        UNION ALL
        (SELECT `r2`.`id1` `achievement`, 
        `r2`.`id2` `equipment`,  
        'patent' `type`, 
        `t`.`date` 
        FROM `patent` `t` 
        INNER JOIN `_r_patent_equipment` `r2` 
        INNER JOIN `equipment` `e2` 
        ON `r2`.`id1` = `t`.`id`
        AND `r2`.`id2` = `e2`.`id`)
        UNION ALL
        (SELECT
        `r3`.`id2` `achievement`,
        `r3`.`id1` `equipment`,
        'award' `type`, 
        `a`.`date` 
        FROM `award` `a` 
        INNER JOIN `_r_equipment_award` `r3` 
        INNER JOIN `equipment` `e3` 
        ON `r3`.`id2` = `a`.`id`
        AND `r3`.`id1` = `e3`.`id`)";

        $result = $db->query($sql);
        $achievements = $result ? $result->rows() : [];

        foreach ($achievements as $achievement) {
            $analysis_achievement = O('analysis_achievement');
            $equipment = O('equipment', $achievement->equipment);
            $analysis_achievement->equipment = $equipment;
            $analysis_achievement->achievement = $achievement->achievement;
            $analysis_achievement->type = $achievement->type;
            $analysis_achievement->date = $achievement->date;
            $analysis_achievement->save();
        }
    }
}
