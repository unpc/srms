<?php 
require ROOT_PATH . 'vendor/autoload.php';
use GuzzleHttp\Client;

class CLI_Analysis {

    static function register () {
        $post_data = (array)Config::get('analysis.application');
        $post_data['_expiretime'] = (string)Date::time();
        ksort($post_data);
        $response = self::rest()->post('app', [
            'form_params' => $post_data,
            'headers' => [
                'X-GINI-SIGN' => md5("geneegroup" . json_encode($post_data, JSON_UNESCAPED_UNICODE))
            ]
        ]);
        $body = $response->getBody();
        $content = $body->getContents();
        echo print_r(json_decode($content), true) . "\n";
    }

    static function init () {
        $fields = Config::get('schema.analysis')['fields'];
        $columns = [];
        $columns['id'] = ['name' => '标识', 'type' => 'int'];
        foreach ($fields as $key => $field) {
            if (in_array($key, ['ctime', 'mtime'])) continue;
            $columns[$key]['associate'] = $field['oname'];
            $columns[$key]['name'] = $field['comment'];
            switch ($field['type']) {
                case 'int':
                    $type = 'int';
                    break;
                case 'double':
                    $type = 'double';
                    break;
                default:
                    $type = 'string';
                    break;

            }
            $columns[$key]['type'] = $type;
        }
        $columns['time']['type'] = 'time';

        $rest = self::rest('godiva');
        $post_data = [
            'key' => 'polymerize',
            'name' => '仪器使用表',
            'type' => 'source',
            'columns' => $columns
        ];
        try {
            $response = $rest->post('origin', [
                'form_params' => $post_data,
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $result = json_decode($body->getContents());

            $result = $result ? '成功' : '失败';
            echo "   \e[32m 仪器使用表创建{$result} \e[0m\n";
    
            Event::trigger('analysis.init.table', $rest);
        }
        catch(Exception $e)
        {
            print_r($e->getMessage());
            error_log(print_r($e->getMessage(), 1));
        }
        
       
    }

    static function full ($refresh = false, $start = 0, $end = 0) {
        $rest = self::rest('godiva');
        $now = time();
        if ($refresh) {
            // 将数据全部聚合的时间向后推 推到最后一条预约的时间
            $reserv = Q('eq_reserv:sort(dtend D):limit(1)')->current();
            $db = Database::factory();
            $start = $start ? : 1262275200;
            $end = $end ? : Date::get_day_end($reserv->dtend);
            for (; $start <= $end; $start += 86400) {
                $dtend = $start + 86399;
                echo date('Y-m-d',$dtend)."\n";
                // 做全量数据更新时，聚合每天有记录的数据 再进行更新
                $sql = "SELECT DISTINCT `T1`.*, `p`.`type` AS `project`, `p`.`lab_id`
                FROM (
                    SELECT `equipment_id`, `user_id`, `project_id`, 'eq_record' as `source_name`,`id` as `source_id`,
                    DATE_FORMAT(FROM_UNIXTIME(`dtend`), '%Y-%m-%d') AS `time`
                    FROM `eq_record`
                    WHERE `dtend` BETWEEN {$start} AND {$dtend}
                    UNION
                    SELECT `equipment_id`, `user_id`, `project_id`, 'eq_reserv' as `source_name`,`id` as `source_id`,
                    DATE_FORMAT(FROM_UNIXTIME(`dtend`), '%Y-%m-%d') AS `time`
                    FROM `eq_reserv`
                    WHERE `dtend` BETWEEN {$start} AND {$dtend}
                    UNION
                    SELECT `equipment_id`, `sender_id` AS `user_id`, `project_id`, 'eq_sample' as `source_name`,`id` as `source_id`,
                    DATE_FORMAT(FROM_UNIXTIME(`dtsubmit`), '%Y-%m-%d') AS `time`
                    FROM `eq_sample`
                    WHERE `dtsubmit` BETWEEN {$start} AND {$dtend}
                ) AS T1
                LEFT JOIN `lab_project` AS `p` ON `p`.`id` = `T1`.`project_id`";
                $rows = $db->query($sql)->rows();

                if (count($rows)) foreach($rows as $row) {
                    // #20201880 最后一个问题，这里row->time为空导致reserv_dur计算出来的时间为所有时间，明显不合理，所以先行跳过
                    if (!$row->time) continue;
                    $equipment = O('equipment', $row->equipment_id);
                    $user = O('user', $row->user_id);
                    $lab = O('lab', $row->lab_id);
                    $mark = O('analysis_mark', [
                        'equipment' => $equipment,
                        'user' => $user,
                        'lab' => $lab,
                        'project' => !is_null($row->project) ? (int)$row->project : -1,
                        'date' => Date::get_day_start(strtotime($row->time))
                    ]);
                    $mark->equipment = $equipment;
                    $mark->user = $user;
                    $mark->source_id = $row->source_id;
                    $mark->source_name = $row->source_name;
                    $mark->source_id = $row->source_id;
                    $mark->lab = $lab;
                    $mark->project = !is_null($row->project) ? (int)$row->project : -1;
                    $mark->date = Date::get_day_start(strtotime($row->time));
                    $mark->time = $now;
                    $mark->save();
                    $mark->polymerize();
                }
            }
        }
        // 至此数据更新完毕
        $start = 0;
        $step = 20;
        while (true) {
            $analysis = Q("analysis")->limit($start, $step);
            if (!count($analysis)) break;

            $data = [];
            foreach ($analysis as $item) {
                $row = [];
                $row['id'] = $item->id;
                $row['user'] = $item->user->id;
                $row['lab'] = $item->lab->id;
                $row['equipment'] = $item->equipment->id;
                $row['project'] = $item->project;
                $row['use_dur'] = $item->use_dur;
                $row['sample_dur'] = $item->sample_dur;
                $row['reserv_dur'] = $item->reserv_dur;
                $row['use_time'] = $item->use_time;
                $row['sample_time'] = $item->sample_time;
                $row['reserv_time'] = $item->reserv_time;
                $row['use_fee'] = (double)$item->use_fee;
                $row['sample_fee'] = (double)$item->sample_fee;
                $row['reserv_fee'] = (double)$item->reserv_fee;
                $row['success_sample'] = $item->success_sample;
                $row['use_sample'] = $item->use_sample;
                $row['sample_sample'] = $item->sample_sample;
                $row['reserv_sample'] = $item->reserv_sample;
                $row['use_project'] = $item->use_project;
                $row['sample_project'] = $item->sample_project;
                $row['reserv_project'] = $item->reserv_project;
                $row['use_type'] = $item->use_type;
                $row['is_outer'] = $item->is_outer;
                $row['source_name'] = $item->source_name;
                $row['open_dur'] = $item->open_dur;
                $row['time'] = date('Y-m-d', $item->time);
                $data[] = $row;
            }

            $ids = implode(',', array_column($data, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'polymerize',
                    'data' => $data
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            echo "   \e[32m 数据[{$ids}]推送完成 返回值[{$content}] \e[0m\n";

            $start += $step;
        }
        Event::trigger('analysis.full.data', $rest, $refresh);
    }

    // 每天进行的增量推送
    static function increment () {
        // 对产生任何改动的数据做记录
        $time = Date::get_day_start(strtotime('-1 day'));
        // $time = Date::get_day_start();

        $marks = Q("analysis_mark[time>={$time}]");
        foreach ($marks as $mark) {
            $mark->polymerize();
        }
        echo "   \e[32m 数据聚合完成 \e[0m\n";

        // 至此数据更新完毕
        $start = Date::get_day_start();
        $analysis = Q("analysis[mtime>{$start}]");
        $data = [];
        foreach ($analysis as $item) {
            $row = [];
            $row['id'] = $item->id;
            $row['user'] = $item->user->id;
            $row['lab'] = $item->lab->id;
            $row['equipment'] = $item->equipment->id;
            $row['project'] = $item->project;
            $row['use_dur'] = $item->use_dur;
            $row['sample_dur'] = $item->sample_dur;
            $row['reserv_dur'] = $item->reserv_dur;
            $row['use_time'] = $item->use_time;
            $row['sample_time'] = $item->sample_time;
            $row['reserv_time'] = $item->reserv_time;
            $row['use_fee'] = (double)$item->use_fee;
            $row['sample_fee'] = (double)$item->sample_fee;
            $row['reserv_fee'] = (double)$item->reserv_fee;
            $row['use_sample'] = $item->use_sample;
            $row['sample_sample'] = $item->sample_sample;
            $row['reserv_sample'] = $item->reserv_sample;
            $row['use_project'] = $item->use_project;
            $row['sample_project'] = $item->sample_project;
            $row['reserv_project'] = $item->reserv_project;
            $row['time'] = date('Y-m-d', $item->time);
            $data[] = $row;
        }

        $rest = self::rest('godiva');
        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'polymerize',
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            echo "   \e[32m 数据[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }

        Event::trigger('analysis.increment.data', $rest);
    }

    private static function rest ($type='app') {
        $rest = Config::get('rest.analysis')[$type];
        $client = new Client(['base_uri' => $rest['url'], 'timeout' => $rest['timeout']]);
        return $client;
    }
}
