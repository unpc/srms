<?php

/**
 * Class API_Summarize
 * 第三方外包前台所需要统计接口数据获取
 * 1.获取人员分布情况
 * 2.获取课题组测试情况
 */

class API_Summarize extends API_Common
{

    /**
     * 获取人员分布情况
     * @return array
     * @throws API_Exception
     */
    public function userStatus()
    {
        $this->_ready();

        $data = [
            'total' => 0,
            'inner' => 0,
            'outer' => 0,
            'incharge' => 0,
        ];
        $root = Tag_Model::root('group');

        //获取校内组织机构根结点列表
        $innerGroupNames = Config::get('summarize.inner_group_root');
        if (false === $innerGroupNames) {
            throw new API_Exception('group config error', 400);
        }
        if (!$innerGroupNames) {
            //默认获取第一个
            $innerGroupNames = Q("tag_group[parent={$root}]")->to_assoc('name');
        }

        foreach ($innerGroupNames as $groupName) {
            if (!empty($groupName)) {
                $group = O('tag_group', [
                    'name' => $groupName,
                    'root' => $root,
                ]);
                $data['inner'] += Q("$group user")->total_count();
            }
        }

        $data['total'] = Q('user')->total_count();
        $data['outer'] = $data['total'] - $data['inner'];
        $data['incharge'] = Q("equipment user.incharge")->total_count();

        return $data;
    }


    /**
     * 获取课题组测试分布情况
     * @param int $dtstart
     * @param int $dtend
     * @param int $type 科研1，教学0，全部all
     * @return array
     * @throws API_Exception
     */
    public function labStatus($dtstart = 0, $dtend = 0, $type = 'all')
    {
        $this->_ready();

        if (!$dtstart) $dtstart = Date::get_year_start();
        if (!$dtend) $dtend = Date::get_year_end();
        $data = [];
        //课题组项目类型
        $allowType = [
            'all',
            Lab_Project_Model::TYPE_EDUCATION,
            Lab_Project_Model::TYPE_RESEARCH
        ];
        if (!in_array($type, $allowType)) {
            throw new API_Exception('project type error', 400);
        }

        //获取当前类型的课题信息
        $reserv = "eq_reserv[dtend={$dtstart}~{$dtend}]";
        $sample = "eq_record[dtend={$dtstart}~{$dtend}]";
        $record = "eq_sample[dtend={$dtstart}~{$dtend}]";
        $project = ($type == 'all' ? 'lab_project' : "lab_project[type={$type}]");

        $projectId = Q($project)->to_assoc('id', 'id');
        $cond = !empty($projectId) ? implode(',', $projectId) : '';
        $reserv .= ($type == 'all' ? '[project]' : "[project_id={$cond}]");
        $sample .= ($type == 'all' ? '[project]' : "[project_id={$cond}]");
        $record .= ($type == 'all' ? '[project]' : "[project_id={$cond}]");

        $projects = count(array_unique(Q($reserv)->to_assoc('id', 'project_id')
            + Q($sample)->to_assoc('id', 'project_id')
            + Q($record)->to_assoc('id', 'project_id')));

        $data['project'] = Q($project)->total_count();
        $data['lab'] = $projects;
        $data['test'] = Q($reserv)->total_count()
            + Q($sample)->total_count()
            + Q($record)->total_count();

        return $data;
    }


    public function getEqRecordInfo($year = NULL)
    {
        $tmp_time = Date::get_year_start() + 1296000;
        if ($year) $tmp_time = Date::get_year_start($year) + 1296000;

        $res = [];
        for ($i = 0; $i < 12; $i++) {
            $time = $tmp_time + $i * 2592000;
            $dtstart = Date::get_month_start($time);
            $dtend = Date::get_month_end($time);

            # 每个月所有仪器的使用次数
            $eq_used_count = Q("eq_record[dtstart={$dtstart}~{$dtend}][dtend=0|dtend>{$dtstart}]")->total_count();

            # 每个月所有仪器使用的人数
            $eq_used_user_count = Q("eq_record[dtstart={$dtstart}~{$dtend}][dtend=0|dtend>{$dtstart}] user")->total_count();

            # 每个月所有仪器使用的总机时
            $db = Database::factory();

            $SQL = "SELECT SUM(`dtend` - `dtstart`) as `time` FROM `eq_record` "
                . "WHERE (dtstart >= {$dtstart} AND dtstart <= {$dtend})"
                . "AND (dtend >= {$dtstart} AND dtend <= {$dtend})";

            $result = $db->query($SQL);
            $row = $result->rows();
            $eq_used_time_1 = $row[0]->time;

            $SQL = "SELECT SUM({$dtend} - `dtstart`) as `time` FROM `eq_record` "
                . "WHERE (dtstart >= {$dtstart} AND dtstart <= {$dtend})"
                . "AND (dtend = 0 OR dtend > {$dtend})";

            $result = $db->query($SQL);
            $row = $result->rows();
            $eq_used_time_2 = $row[0]->time;
            $eq_used_time = $eq_used_time_1 + $eq_used_time_2;

            $res[$i + 1] = [
                'eq_used_count' => $eq_used_count,
                'eq_used_user_count' => $eq_used_user_count,
                'eq_used_time' => round($eq_used_time / 3600)
            ];
        }

        return $res;
    }
}