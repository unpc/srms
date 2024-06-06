<?php

class Analysis_Credit
{

    //初始化remote数据库表结构
    static function init($e, $rest)
    {
        $fields = Config::get('schema.analysis_credit')['fields'];
        $columns = [];
        $columns['id'] = ['name' => '标识', 'type' => 'int'];
        foreach ($fields as $key => $field) {
            $columns[$key]['associate'] = $field['oname'];
            $columns[$key]['name'] = $field['comment'];
            $columns[$key]['type'] = $field['type'] == 'int' ? 'int' : (isset($field['oname']) ? 'int' : 'string');
        }
        $columns['ctime']['type'] = 'time';
        $columns['mtime']['type'] = 'time';
        $columns['utime']['type'] = 'time';
        $columns['time']['type'] = 'time';
        $response = $rest->post('origin', [
            'form_params' => [
                'key' => 'credit',
                'name' => 'c',
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

        echo "   \e[32m 用户信用分表创建{$result} \e[0m\n";

    }

    //每天增量数据推送
    static function increment($e, $rest)
    {
        $startTime = Date::get_day_start(strtotime('-1 day'));
        $endTime = Date::get_day_end($startTime);
        $data = [];
        //获取增量数据
        $incrementData = Q("credit[mtime>={$startTime}]");
        foreach ($incrementData as $item) {
            $data[] = self::_format($item);
        }

        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {

            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'credit',
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            echo "\e[32m 用户信用分表数据[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
    }

    //历史数据导入
    static function full($e, $rest)
    {
        Log::add("[push] analysis_credit", 'analysis');
        $now = Date::get_day_start();
        $incrementData = Q("credit");
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
                    'key' => 'credit',
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
        Log::add("[push] credit push done", 'analysis');
    }


    private static function _format($item)
    {
        $row = [];
        $row['id'] = $item->id;
        $row['user'] = $item->user->id;
        $row['credit_level'] = $item->credit_level->id;
        $row['ctime'] = date('Y-m-d H:i:s', $item->ctime);
        $row['mtime'] = date('Y-m-d H:i:s', $item->mtime);
        $row['utime'] = date('Y-m-d H:i:s', $item->utime);
        $row['percent'] = $item->percent;
        $row['total'] = $item->total;
        $row['time'] = date('Y-m-d H:i:s', time());;
        return $row;
    }
}