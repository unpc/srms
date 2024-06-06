<?php

class Analysis_Eq_Sample
{

    //初始化remote数据库表结构
    static function init($e, $rest)
    {
        $fields = Config::get('schema.analysis_eq_sample')['fields'];
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
                'key' => 'eq_sample',
                'name' => '仪器送样明细表',
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

        echo "   \e[32m 仪器送样记录表创建{$result} \e[0m\n";

    }

    //每天增量数据推送
    static function increment($e, $rest)
    {
        $startTime = Date::get_day_start(strtotime('-1 day'));
        // $endTime = Date::get_day_end($startTime);
        $data = [];
        //获取昨天增量数据
        $incrementData = Q("eq_sample[dtend>={$startTime}]");
        foreach ($incrementData as $item) {
            $data[] = self::_format($item);
        }

        //获取修改数据
        $markData = Q("analysis_mark_desc[source_name=eq_sample][ctime>={$startTime}]");
        $markId = [];
        foreach ($markData as $m) {
            $markId[] = $m->source_id;
        }
        if (!empty($markId)) {
            $idStr = implode(',', $markId);
            $markRecordData = Q("eq_sample[id={$idStr}]");
            foreach ($markRecordData as $item) {
                $data[] = self::_format($item);
            }
        }

        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {
            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'eq_sample',
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            echo "\e[32m 仪器送样记录数据[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
    }

    //历史数据导入select * from eq_sample where id = 169\G;
    static function full($e, $rest)
    {
        Log::add("[push] eq_sample pushing", 'analysis');
        $now = Date::get_day_start();
        $incrementData = Q("eq_sample");
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
                    'key' => 'eq_sample',
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
        Log::add("[push] eq_sample push done", 'analysis');
    }


    private static function _format($item)
    {
        $row = [];
        $row['id'] = $row['sample_id'] = $item->id;
        $row['equipment'] = $item->equipment->id;
        $row['equipment_name'] = $item->equipment->name;
        $row['ref_no'] = $item->equipment->ref_no;
        $row['group'] = $item->equipment->group->id;
        $row['group_name'] = $item->equipment->group->name;
        $row['sender'] = $item->sender->id;
        $row['sender_name'] = $item->sender->name;
        $row['sender_group'] = $item->sender->group->name;
        $lab = Q("{$item->sender} lab");
        $row['lab'] = $lab->id;
        $row['dtstart'] = $item->dtstart;
        //使用中的特殊情况处理
        $row['dtend'] = $item->dtend ?: 0;
        $row['dtsubmit'] = $item->dtsubmit ?: 0;
        $row['dtpickup'] = $item->dtpickup ?: 0;
        $row['status'] = $item->status;
        $row['samples'] = $item->count;
        $row['success_samples'] = $item->success_samples;
        $row['operator'] = $item->operator->id;
        $row['record'] = $item->record->id;
        $row['is_locked'] = $item->is_locked;
        $row['amount'] = Q("eq_charge[source_name=eq_sample][source_id={$item->id}]")
            ->current()
            ->amount;
        $row['amount'] = $row['amount'] ? $row['amount'] : 0;
        $row['description'] = $item->description;
        // $row['mtime'] = $item->mtime;
        $row['time'] = date('Y-m-d H:i:s', time());
        $row['project'] = !is_null($item->project) ? (int)$item->project->id : -1;
        $row['record_create_time'] = $item->ctime;
        $row['is_incharge'] = (int) Equipments::user_is_eq_incharge($item->sender, $item->equipment);
        return $row;
    }

}