<?php

class Labs_Com {

	static function views ($e, $components) {
        $me = L('ME');
        if (!$me->id) return TRUE;
        
        if ($me->access('管理所有内容') || Q("{$me} equipment.incharge")->total_count() 
        || Q("$me<pi lab")->total_count()) {
            $components[] = [
                'id' => 'projectStatus',
                'key' => 'projectStatus',
                'name' => '项目情况',
            ];
        }
        $e->return_value = $components;
        return TRUE;
    }

    static function view_projectStatus ($e, $query) {
        $me = L('ME');
        $selector = "lab_project";
        $title = '';

        if ($me->access('管理所有内容')) {
            $title = '项目总数';
            $count = Q($selector)->total_count();
        }
        else if (Q("{$me} equipment.incharge")->total_count()) {
            $count = Q("({$me} equipment.incharge) eq_sample[lab_project]")->total_count();
            $count += Q("({$me} equipment.incharge) eq_reserv[lab_project]")->total_count();
            $count += Q("({$me} equipment.incharge) eq_record[lab_project]")->total_count();
            $title = '仪器服务项目总数';
        }
        else if (Q("$me<pi lab")->total_count()) {
            $selector = "$me<pi lab " . $selector;
            $count = Q($selector)->total_count();
            $title = '课题组下项目数';
        }
        else {
            $title = '';
            $count = 0;
        }

        $view = V('labs:components/projectStatus', [
            'title' => $title,
            'count' => $count
        ]);
        
        $e->return_value = [
            'template' => (string)$view
        ];
        return FALSE;
    }

}