<?php

class API_Basic_Info {

    // 抓取基表权限
    function get_role($user_id = null) {
        $perms = [];

        // 抓取人员信息
        $u = O('user', $user_id);
        if (!$u->id) return FALSE;

        // 抓取当前登录人员的权限列表
        $perms = [
            '管理实验室信息统计' => $u->access('管理实验室信息统计'),
            '查看下属机构的仪器信息' => $u->access('查看下属机构的仪器信息'),
            '管理下属机构的实验室统计信息' => $u->access('管理下属机构的实验室统计信息'),
            '管理下属机构实验室的教学项目信息' => $u->access('管理下属机构实验室的教学项目信息'),
            '管理下属机构实验室科研项目信息' => $u->access('管理下属机构实验室科研项目信息'),
        ];

        $perms = array_filter($perms);

        return $perms;
    }
}
