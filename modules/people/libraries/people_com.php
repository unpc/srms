<?php

class People_Com {

	static function views ($e, $components) {
        $me = L('ME');
        if (!$me->id) return TRUE;
        $is_admin = $me->access('管理所有内容');
        $is_college = $me->access('管理组织机构');
        $is_pi = Q("$me<pi lab")->total_count();
        $is_incharge = !!Q("{$me} equipment.incharge")->total_count();
        
        if ($is_admin || $is_college || $is_pi) {
            $components[] = [
                'id' => 'userStatus',
                'key' => 'userStatus',
                'name' => '人员情况',
            ];
        }

        if ($is_admin) {
            $components[] = [
                'id' => 'userApproval',
                'key' => 'userApproval',
                'name' => '新用户审批',
            ];
        }

        $e->return_value = $components;
        return TRUE;
    }

    static function view_userStatus ($e, $query) {
        $me = L('ME');
        $selector = "user[atime>0]";
        $title = '';

        if ($me->access('管理所有内容')) {
            $title = '系统总人数';
        }
        else if ($me->access('管理组织机构')) {
            $group = $me->group;
            $selector = "{$group} {$selector}";
            $title = '下属机构人员总数';
        }
        elseif (Q("$me<pi lab")->total_count()) {
            $selector = "$me<pi lab " . $selector;
            $title = '课题组下总人数';
        }
        else {
            $selector = '';
            $title = '';
        }

        $count = Q($selector)->total_count();
        $view = V('people:components/view/userStatus', [
            'title' => $title,
            'count' => $count
        ]);
        $e->return_value = [
            'template' => (string)$view
        ];
        return FALSE;
    }

    static function view_userApproval ($e, $query) {
        $me = L('ME');
        if (!$me->id) return TRUE;

        if ($me->access('管理所有内容')) {
            $now = Date::time();
            $start = 0;//$now - 86400;
            $people = Q("user[ctime>{$start}][atime=0]")->limit(10);
        }

        $view = V('people:components/view/userApproval', [
            'people' => $people
        ]);
        $e->return_value = [
            'template' => (string)$view
        ];
        return FALSE;
    }

}