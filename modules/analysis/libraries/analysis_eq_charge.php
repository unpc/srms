<?php

class Analysis_Eq_Charge
{

    //初始化remote数据库表结构
    static function init($e, $rest)
    {
        $fields = Config::get('schema.analysis_eq_charge')['fields'];
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
                'key' => 'eq_charge',
                'name' => '仪器收费明细表',
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

        echo "   \e[32m 仪器收费明细表创建{$result} \e[0m\n";
    }

    //每天增量数据推送
    static function increment($e, $rest)
    {
        $startTime = Date::get_day_start(strtotime('-1 day'));
        $endTime = Date::get_day_end($startTime);
        $data = [];
        //获取昨天增量数据
        $incrementData = Q("eq_charge[ctime<={$endTime}][ctime>={$startTime}]");
        foreach ($incrementData as $item) {
            $data[] = self::_format($item);
        }

        //获取修改数据
        $markData = Q("analysis_mark_desc[source_name=eq_charge][ctime<={$endTime}][ctime>={$startTime}]");
        $markId = [];
        foreach ($markData as $m) {
            $markId[] = $m->source_id;
        }
        if (!empty($markId)) {
            $idStr = implode(',', $markId);
            $markRecordData = Q("eq_charge[id={$idStr}]");
            foreach ($markRecordData as $item) {
                $data[] = self::_format($item);
            }
        }
        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {

            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'eq_charge',
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
        Log::add("[push] eq_charge pushing", 'analysis');
        $now = Date::get_day_start();
        $incrementData = Q("eq_charge");
        $total = $incrementData->total_count();
        $offset = 20;
        $totalPage = ceil($total / $offset);
        $page = 1;
        while ($page <= $totalPage) {
            $data = [];
            $start = ($page - 1) * $offset;
            $increment = $incrementData->limit($start, $offset);
            foreach ($increment as $c) {
                $data[] = self::_format($c);
            }

            $ids = implode(',', array_column($data, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'eq_charge',
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
        Log::add("[push] eq_charge push done", 'analysis');
    }

    private static function _format($item)
    {
        $row = [];
        $row['id'] = $item->id;
        $row['lab'] = $item->lab->id;
        $row['user'] = $item->user->id;
        $row['equipment'] = $item->equipment->id;
        $row['status'] = $item->status;
        $row['dtstart'] = $item->dtstart;
        $row['dtend'] = $item->dtend;
        $row['auto_amount'] = $item->auto_amount;
        $row['amount'] = $item->amount;
        $row['custom'] = $item->custom;
        $row['transaction_id'] = $item->transaction->id;
        $row['transaction'] = str_pad($item->transaction->id, 6, 0, STR_PAD_LEFT);
        $row['is_locked'] = $item->is_locked;
        $row['source_id'] = $item->source->id;
        switch ($item->source->name()) {
            case "eq_record":
                $source_name = "使用收费";
                break;
            case "eq_reserv":
                $source_name = "预约收费";
                break;
            case "eq_sample":
                $source_name = "送样收费";
                break;
        }
        $row['source_name'] = $source_name;
        $row['charge_duration_blocks'] = $item->charge_duration_blocks;
        $row['description'] = $item->description;
        $row['time'] = date("Y-m-d H:i:s", time()); // 这里通用是time类型
        $row['record_create_time'] = $item->ctime;
        $row['blstatus'] = $item->bl_status ?? 0;
        $row['is_incharge'] = (int) Equipments::user_is_eq_incharge($item->user, $item->equipment);
        return $row;
    }
}
