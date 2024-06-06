<?php

class Analysis_Eq_Record
{

    //初始化remote数据库表结构
    static function init($e, $rest)
    {
        $fields = Config::get('schema.analysis_eq_record')['fields'];
        $columns = [];
        $columns['id'] = ['name' => '标识', 'type' => 'int'];
        foreach ($fields as $key => $field) {
            if (in_array($key, ['ctime', 'mtime'])) continue;
            $columns[$key]['associate'] = $field['oname'];
            $columns[$key]['name'] = $field['comment'];
            $columns[$key]['type'] = $field['type'] == 'int' ? 'int' : (isset($field['oname']) ? 'int' : 'string');
        }
        $columns['time']['type'] = 'time';
        $columns['is_incharge'] = ['name' => '是否是机主', 'type' => 'int'];

        $response = $rest->post('origin', [
            'form_params' => [
                'key' => 'eq_record',
                'name' => '仪器使用明细表',
                'type' => 'source',
                'columns' => $columns
            ],
            'headers' => [
                'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
            ]
        ]);
        $body = $response->getBody();
        $result = json_decode($body->getContents());
        $result = isset($result->key) ? '成功' : '失败';

        echo "   \e[32m 仪器使用记录表创建{$result} \e[0m\n";

    }

    //每天增量数据推送
    static function increment($e, $rest)
    {
        $startTime = Date::get_day_start(strtotime('-1 day'));
        $endTime = Date::get_day_end($startTime);
        $data = [];
        //获取昨天增量数据
        $incrementData = Q("eq_record[dtend<={$endTime}][dtend>={$startTime}]");
        foreach ($incrementData as $item) {
            $data[] = self::_format($item);
        }

        //获取修改数据
        $markData = Q("analysis_mark_desc[source_name=eq_record][ctime<={$endTime}][ctime>={$startTime}]");
        $markId = [];
        foreach ($markData as $m) {
            $markId[] = $m->source_id;
        }
        if (!empty($markId)) {
            $idStr = implode(',', $markId);
            $markRecordData = Q("eq_record[id={$idStr}]");
            foreach ($markRecordData as $item) {
                $data[] = self::_format($item);
            }
        }
        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {

            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'eq_record',
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            echo "\e[32m 仪器使用记录数据[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
    }

    //历史数据导入
    static function full($e, $rest)
    {
        Log::add("[push] eq_record pushing", 'analysis');
        $now = Date::get_day_start();
        $incrementData = Q("eq_record");
        $total = $incrementData->total_count();
        $offset = 20;
        $totalPage = ceil($total / $offset);
        $page = 1;
        while ($page <= $totalPage) {
            $data = [];
            $start = ($page - 1) * $offset;
            $increment = $incrementData->limit($start, $offset);
            foreach($increment as $c){
                $data[] = self::_format($c);
            }
            $ids = implode(',', array_column($data, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'eq_record',
                    'data' => $data
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            echo "\e[32m 数据[{$ids}]推送完成 返回值[{$content}] \e[0m\n";

            $page++;
        }
        echo " \e[32m [推送完成] \e[0m\n";
        Log::add("[push] eq_record push done", 'analysis');
    }


    private static function _format($item)
    {
        $row = [];
        $row['id'] = $item->id;
        $row['record_id'] = str_pad($item->id, 6, 0, STR_PAD_LEFT);
        $row['equipment'] = $item->equipment_id;
        $row['equipment_name'] = $item->equipment->name;
        $row['ref_no'] = $item->equipment->ref_no;
        $row['group'] = $item->equipment->group->id;
        $row['group_name'] = $item->equipment->group->name;
        $row['user'] = $item->user_id;
        $row['user_name'] = $item->user->name;
        $row['user_group'] = $item->user->group->id;
        $row['user_group_name'] = $item->user->group->name;
        $lab = Q("user#{$item->user_id} lab");
        $row['lab'] = $lab->id;
        $row['lab_name'] = $lab->name;
        $row['dtstart'] = $item->dtstart;
        //使用中的特殊情况处理
        $row['dtend'] = $item->dtend ?: 0;
        if ($item->dtend) {
            $row['time_total'] = $item->dtend - $item->dtstart;
        } else {
            $row['time_total'] = 0;
        }
        $row['samples'] = $item->samples;
        $row['is_locked'] = $item->is_locked;
        $row['reserv_id'] = $item->reserv_id;
        $row['agent'] = $item->agent->id;
        $row['feedback'] = $item->feedback;
        $row['amount'] = Q("eq_charge[source_name=eq_record][source_id={$item->id}]")->current()->amount;
        $row['amount'] = $row['amount'] ? $row['amount'] : 0;
        // $row['mtime'] = $item->mtime;
        $row['time'] = date('Y-m-d H:i:s', time());
        $row['record_create_time'] = $item->dtstart;
        $row['project'] = !is_null($item->project) ? (int)$item->project->id : -1;
        $row['is_incharge'] = (int) Equipments::user_is_eq_incharge($item->user, $item->equipment);
        return $row;
    }

}