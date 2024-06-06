<?php

class CLI_Approval {

    public static function delete_expired_approval() {

        $now = Date::time();

	    $modules = Config::get('approval.modules');

        foreach (Q('approval') as $approval) {
            if (!in_array($approval->source->name(), $modules)) {
                continue;
            }
            if ($approval->source->name() == 'eq_sample') {
                continue;
            }
            if ($approval->source->dtstart < $now && ( $approval->flag == 'approve_pi' || $approval->flag == 'approve_incharge') ) {
                $approval->dtstart = $approval->source->dtstart;
                $approval->dtend = $approval->source->dtend;
                $approval->reserv_desc = $approval->source->component->description;
                $approval->project_id = $approval->source->project->id;
                $approval->flag = 'expired';
                if ($approval->save()) {
                    $approval->source->component->delete();
                }
            }
        }
    }

    //上线功能时需要添加一下已经预约的数据进入审核流程
    public static function add_approved() {
        $now = Date::time();
        $reservs = Q("eq_reserv[dtstart>{$now}]");
        foreach ($reservs as $object) {
            $modules = Config::get('approval.modules');
            if (!in_array($object->name(), $modules)) {
                return TRUE;
            }
            $approval = O('approval');
            $approval->source = $object;
            $approval->create($object);
        }
    }

    // 更新为待机主审核状态
    public static function sample_update_history()
    {
        $db = Database::factory();
        $db->query('UPDATE `eq_sample` SET `status` = 6 WHERE `status`=1');
    }

    // 让所有的课题组默认勾选pi审核
    public static function check_all_pi_approval()
    {
        foreach (Q("lab[hidden=0]") as $lab) {
            $lab->reserv_approval = true;
            $lab->sample_approval = true;
            $lab->save();
        }
    }

    static function history(){
        self::check_all_pi_approval();
        self::sample_update_history();
        self::add_approved();
    }

    // 课题组预约/送样审核 - 审核时限
    static function lab_approval_unlimit_time()
    {
        $now = Date::time();
        foreach (Q('approval[flag=approve_pi][source_name=eq_reserv,eq_sample]') as $approval) {
            $lab = Q("{$approval->user} lab")->current();
            if (!$lab->id) {
                continue;
            }

            switch($approval->source->name()) {
                case 'eq_sample':
                    if ($lab->sample_approval && $lab->sample_approval_unlimit_time_mode && ($now - $approval->ctime >= $lab->sample_approval_unlimit_time_mins)) {
                        if ($lab->sample_approval_unlimit_time_type == 1) {
                            $approval->pass(true);
                        } else {
                            $approval->reject(true);
                        }

                        Log::add(strtr('[approval_flow] 达到审核时限, 系统自动审核[送样], 审核ID[%approval_id], 提审时间[%approval_ctime], 审核时限[%approval_mins秒], 审核结果[%approval_result]', [
                            '%approval_id' => $approval->id,
                            '%approval_ctime' => date('Y-m-d H:i:s', $approval->ctime),
                            '%approval_mins' => $lab->sample_approval_unlimit_time_mins,
                            '%approval_result' => $lab->sample_approval_unlimit_time_type == 1 ? '通过' :  '拒绝',
                        ]), 'journal');
                    }
                    break;
                case 'eq_reserv':
                    if ($lab->reserv_approval && $lab->reserv_approval_unlimit_time_mode && ($now - $approval->ctime >= $lab->reserv_approval_unlimit_time_mins)) {
                        if ($lab->reserv_approval_unlimit_time_type == 1) {
                            $approval->pass(true);
                        } else {
                            $approval->reject(true);
                        }

                        Log::add(strtr('[approval_flow] 达到审核时限, 系统自动审核[预约], 审核ID[%approval_id], 提审时间[%approval_ctime], 审核时限[%approval_mins秒], 审核结果[%approval_result]', [
                            '%approval_id' => $approval->id,
                            '%approval_ctime' => date('Y-m-d H:i:s', $approval->ctime),
                            '%approval_mins' => $lab->reserv_approval_unlimit_time_mins,
                            '%approval_result' => $lab->reserv_approval_unlimit_time_type == 1 ? '通过' :  '拒绝',
                        ]), 'journal');
                    }
                    break;
            }
        }
    }
}
