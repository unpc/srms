<?php

class Capability
{
    static function is_accessible($e, $name) {
        $me = L('ME');
        if (!$me->access('管理所有内容')
            && !Q("lab[owner={$me}]")->total_count()
            && !Q("{$me}<incharge equipment")->total_count()
            && !$me->access('管理设置考核工作')
            && !$me->access('填报效益')
            && !$me->access('初审绩效')
            && !$me->access('管理下属机构仪器绩效考核')
            && !$me->access('管理所有仪器绩效考核')
            && !$me->access('录入考核结果')) {
            $e->return_value = FALSE;
            return FALSE;
        }
    }
}