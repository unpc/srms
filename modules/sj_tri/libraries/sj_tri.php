<?php

class Sj_Tri
{
    public static function is_accessible($e, $name)
    {
        $me = L('ME');
        $e->return_value = false;
        $is_in_charge = Q("$me<incharge equipment")->total_count() ? true : false;

        if ($me->access('管理所有内容') || $is_in_charge || $me->access('添加/修改下属机构的仪器')) {
            $e->return_value = true;
            return false;
        }
    }

    public static function people_extra_keys($e, $user, $info)
    {
        $info['group'] = $user->group->name;
        $info['is_center'] = $user->access('添加/修改下属机构的仪器') ? true : false;
        $info['is_incharge'] = Q("$user<incharge equipment")->total_count() ? true : false;

        return true;
    }
}
