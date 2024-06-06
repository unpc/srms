<?php

class Analysis_Project_Patent
{

    //初始化remote数据库表结构
    static function init($e, $rest)
    {
        if (!Module::is_installed('achievements')) return;
        $fields = Config::get('schema.analysis_project_patent')['fields'];
        $columns = [];
        $columns['id'] = ['name' => '标识', 'type' => 'int'];
        foreach ($fields as $key => $field) {
            if (in_array($key, ['ctime', 'mtime'])) continue;
            $columns[$key]['associate'] = $field['oname'];
            $columns[$key]['name'] = $field['comment'];
            $columns[$key]['type'] = $field['type'] == 'int' ? 'int' : 'string';
        }

        $response = $rest->post('origin', [
            'form_params' => [
                'key' => 'project_patent',
                'name' => '项目专利关联表',
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

        echo "   \e[32m 项目专利关联表创建{$result} \e[0m\n";
    }


    //每天增量数据推送
    static function increment($e, $rest)
    {
        if (!Module::is_installed('achievements')) return;
        $projectPubliation = Q("analysis_project_patent");
        foreach($projectPubliation as $p){
            $data[] = self::_format($p);
        }

        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {

            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'project_patent',
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            echo "\e[32m 项目专利关联表[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
    }

    //历史数据导入
    static function full($e, $rest)
    {
        Log::add("[push] proeject_patent pushing", 'analysis');
        if (!Module::is_installed('achievements')) return;
        self::_pour();

        $projectPubliation = Q("analysis_project_patent");
        foreach($projectPubliation as $p){
            $data[] = self::_format($p);
        }

        $chunks = array_chunk($data, 20, true);
        foreach ($chunks as $chunk) {

            $ids = implode(',', array_column($chunk, 'id'));
            $response = $rest->post('polymer', [
                'form_params' => [
                    'key' => 'project_patent',
                    'data' => $chunk
                ],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Remote_Godiva_Auth::getToken()
                ]
            ]);
            $body = $response->getBody();
            $content = trim($body->getContents(), "\n");
            echo "\e[32m 项目专利关联表[{$ids}]推送完成 返回值[{$content}] \e[0m\n";
        }
        Log::add("[push] project_patent push done", 'analysis');
    }


    private static function _format($item)
    {
        $row = [];
        $row['id'] = $item->id;
        $row['title'] = $item->title;
        $row['author'] = $item->author;
        $row['journal'] = $item->journal;
        $row['date'] = $item->date;
        $row['volume'] = $item->volume;
        $row['issue'] = $item->issue;
        $row['lab_id'] = $item->lab_id;
        $row['tag_id'] = $item->tag_id;
        $row['tag_name'] = $item->tag_name;
        $row['project'] = $item->project;
        $row['equipment_id'] = $item->equipment_id;
        return $row;
    }

    /**
     * 因为数据不是很多，不捕获更改事件了
     */
    private static function _pour(){
        $db = Database::factory();
        $s = 'TRUNCATE TABLE `analysis_project_patent`';
        $r = $db->query($s);
        $sql = "SELECT `lp`.`name` `project_name`,
        `lp`.`lab_id` `lab_id`,
        `lp`.`id` as `lp_id`,
        `p`.`name` as `title`,
        `p`.`ref_no` as `ref_no`,
        `p`.`date` as `date`,
        `p`.`id` as `pid`,
        `e`.`id` as `equipment_id`,
        `t`.`id` as `tag_id`,
        `t`.`name` as `tag_name`
        
        FROM `lab_project` `lp`
        INNER JOIN
        `_r_patent_lab_project` `rplp` ON `lp`.`id` = `rplp`.`id2`
        INNER JOIN
        `patent` `p` ON `p`.`id` = `rplp`.`id1`
        LEFT JOIN
        `_r_patent_equipment` `rpe` ON `rpe`.`id1` = `p`.`id`
        LEFT JOIN
        `equipment` `e` ON `e`.`id` = `rpe`.`id2`
        LEFT JOIN
        `_r_tag_patent` `tp` ON `tp`.`id2` = `p`.`id`
        LEFT JOIN
        `tag` `t` ON `t`.`id` = `tp`.`id1`
        ";
        $result = $db->query($sql);
        $publications = $result ? $result->rows() : [];
        foreach ($publications as $p) {
            $analysisProject = O('analysis_project_patent');
            $analysisProject->title = $p->title;
            //获取当前成果作者
            $authors = Q("ac_author[achievement_name=patent][achievement_id=$p->pid]");
            $author_list = [];
            foreach($authors as $author) {
                $author_list[] = $author->name;
            }
            $authorNames = implode(', ', $author_list);
            $analysisProject->author = $authorNames ? : '';
            $analysisProject->ref_no = $p->ref_no;
            $analysisProject->date = $p->date;
            $analysisProject->lab_id = $p->lab_id;
            $analysisProject->project = $p->project_name;
            $analysisProject->equipment_id = $p->equipment_id ? : 0;
            $analysisProject->tag_id = $p->tag_id ? : 0;
            $analysisProject->tag_name = $p->tag_name ? : '';
            $analysisProject->save();
        }
    }

}