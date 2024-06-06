<?php

class CLI_Reserv_Approve {

    static function adjustApprove()
    {
        $now = Date::time();
        $check_status = join(',', [
            Szu_EQ_Reserv_Model::$state_num[Szu_EQ_Reserv_Model::STATE_APPROVE],
            Szu_EQ_Reserv_Model::$state_num[Szu_EQ_Reserv_Model::STATE_CANCEL],
            ]);
        foreach (Q("eq_reserv[approve_status={$check_status}]") as $reserv) {
            $approve = Q("$reserv<reserv reserv_approve:limit(1):sort(id D)")->current();
            if ($approve->id) {
                // 先进行当前预约审核的状态信息监测，确保相关人员得到信息通知
                $approve->approver_check();
                // 如果当前预约审核的预约时间已经过了当前时间，需要进行即时的过期删除处理
                if ($reserv->dtstart <= $now) {
                    $approve->overdue();
                }
            }
        }
    }
}
