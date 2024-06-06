<?php

class Analysis_Lab_Project
{

    //初始化remote数据库表结构
    static function init($e, $rest)
    {
        $fields = Config::get('schema.analysis_lab_project')['fields'];
        $columns = [];
        $columns['id'] = ['name' => '标识', 'type' => 'int'];
        foreach ($fields as $key => $field) {
            if (in_array($key, ['ctime', 'mtime'])) continue;
            $columns[$key]['associate'] = $field['oname'];
            $columns[$key]['name'] = $field['comment'];
            $columns[$key]['type'] = $field['type'] == 'int' ? 'int' : (isset($field['oname']) ? 'int' : 'string');
        }
        $columns['time']['type'] = 'time';

        $response = $rest->post('origin', [
            'form_params' => [
                'key' => 'lab_project',
                'name' => '项目明细表',
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

        echo "   \e[32m 项目明细表创建{$result} \e[0m\n";

    }

    //每天增量数据推送
    static function increment($e, $rest)
    {
        self::full($e, $rest);
    }

    //历史数据导入
    static function full($e, $rest)
    {
        Log::add("[push] lab_project pushing", 'analysis');
        self::_pour();
        $now = Date::get_day_start();
        $incrementData = Q("lab_project");
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
                    'key' => 'lab_project',
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
        Log::add("[push] lab_project push done", 'analysis');
    }


    private static function _format($item)
    {
        $row = [];
        $row['id'] = $item->id;
        $row['lab'] = $item->lab->id;
        $row['name'] = $item->name;
        $row['type'] = $item->type;
        $row['dtstart'] = $item->dtstart;
        $row['dtend'] = $item->dtend;
        $row['status'] = $item->status;
        $row['level'] = $item->level;
        $row['cat_no'] = $item->cat_no;
        $row['project_no'] = $item->project_no;
        $row['group_name'] = $item->group_name;
        $row['incharge_no'] = $item->incharge_no;
        $row['time'] = date("Y-m-d H:i:s",$item->ctime);
        return $row;
    }

    /**
     * 因为数据不是很多，不捕获更改事件了
     */
    private static function _pour(){
        $db = Database::factory();
        $s = 'TRUNCATE TABLE `analysis_lab_project`';
        $r = $db->query($s);
    }

}