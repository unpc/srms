<?php

class Analysis_Maintain {

    static function init($e, $rest) {
        $fields = Config::get('schema.analysis_maintain')['fields'];
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
                'key' => 'maintain',
                'name' => '仪器维修表',
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
        echo "   \e[32m 仪器维修表创建{$result} \e[0m\n";

    }

    static function increment($e, $rest) {
        self::pour();

        $analysis = Q("analysis_maintain");
        $data = [];
        foreach ($analysis as $item) {
            $row = [];
            $row['id'] = $item->id;
            $row['equipment'] = $item->equipment->id;
            $row['dtstart'] = $item->dtstart;
            $row['dtend'] = $item->dtend;
            $data[] = $row;
        }

        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'maintain',
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
        Log::add("[push] maintain pushing", 'analysis');
        self::pour();
        
        // 至此数据更新完毕
        $analysis = Q("analysis_maintain");
        $data = [];
        foreach ($analysis as $item) {
            $row = [];
            $row['id'] = $item->id;
            $row['equipment'] = $item->equipment->id;
            $row['dtstart'] = $item->dtstart;
            $row['dtend'] = $item->dtend;
            $data[] = $row;
        }

        $purge = true;
        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'maintain',
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

        Log::add("[push] maintain push done", 'analysis');
    }

    static function pour() {
        $db = Database::factory();
        $sql = 'TRUNCATE TABLE `analysis_maintain`';
        $rows = $db->query($sql);

        $status = EQ_Status_Model::OUT_OF_SERVICE;
        $maintains = Q("eq_status[status=$status]");

        foreach ($maintains as $maintain) {
            $analysis_maintain = O('analysis_maintain');
            $analysis_maintain->equipment = $maintain->equipment;
            $analysis_maintain->dtstart = $maintain->dtstart;
            $analysis_maintain->dtend = $maintain->dtend;
            $analysis_maintain->save();
        }
    }
}
