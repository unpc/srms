<?php

class Eq_Purchase {
    static function is_accessible($e, $name) {
        $me = L('ME');
        if (
        !($me->access('申购仪器') ||
            $me->access('初审下属仪器购置申请') ||
            $me->access('查看下属仪器申购') ||
            $me->access('复审仪器购置申请') ||
            $me->access('查看全部仪器申购') ||
            $me->access('录入专家终审结果') ||
            $me->access('下载仪器论证报告')
            // || Q("{$me}<incharge equipment")->total_count()
        )) {
            $e->return_value = false;
            return FALSE;
        }
    }

}
