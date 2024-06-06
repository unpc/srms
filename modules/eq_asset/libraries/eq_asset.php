<?php

class Eq_Asset
{

    static function is_accessible($e, $name)
    {
        $me = L('ME');
        if ($me->access('管理所有内容')
            ||
            Q("{$me} equipment.incharge")->total_count()
        ) {
            $e->return_value = true;
            return false;
        }

        $e->return_value = false;
        return false;

    }

}