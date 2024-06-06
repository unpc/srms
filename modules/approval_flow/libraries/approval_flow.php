<?php
class Approval_Flow
{
    public static function model_approval_create($e, $object)
    {
        $me = L('ME');
        switch ($object->name()) {
            case 'eq_reserv':
                $lab = Q("{$object} user lab")->current();
                $equipment = $object->equipment;
                if ($lab->reserv_approval || $equipment->need_approval) {
                    $e->return_value = false;
                    return false;
                }
                $e->return_value = true;
                return false;
                break;
            case 'eq_sample':
                $lab = $object->lab;
                if (self::sample_flow_lab()
                    && $lab->sample_approval
                    // 无奈之举, 高权限用户更改送养申请者时, 审批流程如果已经进行完成, 此时不进行二次审核
                    && $object->status == EQ_Sample_Model::STATUS_APPLIED
                ) {
                    $e->return_value = false;
                    return false;
                } elseif (!self::sample_flow_lab()) {
                    $e->return_value = false;
                    return false;
                }
                if (Config::get('flow.eq_sample')['approve_incharge'] && $object->status == EQ_Sample_Model::STATUS_SEND) {
                    $e->return_value = false;
                    return false;
                }
                $e->return_value = true;
                return false;
                break;
            case 'ue_training':
                if ($object->status == UE_Training_Model::STATUS_APPLIED
                    || $object->status == UE_Training_Model::STATUS_AGAIN
                ) {
                    $e->return_value = false;
                    return false;
                }
            default:
                $e->return_value = true;
                return false;
        }
    }

    public static function eq_sample_approval_after_pass($e, $approval)
    {
        if ($approval->source->name() != 'eq_sample') {
            return true;
        }

        switch ($approval->flag) {
            case 'approve_incharge':
                if(Approval_Flow::sample_flow_lab()) {
                    $approval->source->status = EQ_Sample_Model::STATUS_SEND;
                }
                $approval->source->save();
                break;
            case 'done':
                if(Approval_Flow::sample_flow_lab()) {
                    $approval->source->status = EQ_Sample_Model::STATUS_APPROVED;
                } else {
                    $approval->source->status = EQ_Sample_Model::STATUS_APPROVED;
                }
                $approval->source->save();
                break;
        }
    }

    public static function ue_training_approval_after_pass($e, $approval)
    {
        if ($approval->source->name() != 'ue_training') {
            return true;
        }

        switch ($approval->flag) {
            case 'done':
                $approval->source->status = UE_Training_Model::STATUS_APPROVED;
                $approval->source->save();
                break;
        }
    }

    public static function ue_training_approval_after_reject($e, $approval)
    {
        if ($approval->source->name() != 'ue_training') {
            return true;
        }

        switch ($approval->flag) {
            case 'reject':
            case 'rejected':
                $approval->source->status = UE_Training_Model::STATUS_REFUSE;
                $approval->source->save();
                break;
        }
    }

    public static function approval_model_saved($e, $approval, $old_data, $new_data) {
        switch($approval->source->name()) {
            case 'eq_sample':
                switch($approval->flag) {
                    case 'approve_pi':
                        // 免审用户
                        $lab = Q("{$approval->user} lab")->current();
                        if ($lab->sample_approval_unlimit_users_mode && in_array($approval->user->id, $lab->sample_approval_unlimit_users)) {
                            $approval->pass(true);
                            break;
                        }

                        //　免审金额
                        if ($lab->sample_approval_unlimit_amount_mode) {
                            // 院级管理员收费为0,命中该条
                            if ($approval->user->group->id && $approval->user->access('修改下属机构仪器的送样') && $approval->user->group->is_itself_or_ancestor_of($approval->equipment->group)) {
                                $approval->pass(true);
                                break;
                            }

                            // 机主收费为0,命中该条
                            $is_incharge = Equipments::user_is_eq_incharge($approval->user, $approval->equipment);
                            if ($is_incharge) {
                                $approval->pass(true);
                                break;
                            }

                            $charge = O('eq_charge');
                            $charge->source = $approval->source;
                            $lua = new EQ_Charge_LUA($charge);
                            $result = Event::trigger('eq_charge.lua_cal_ext_amount', $approval->equipment, $lua) ?: $lua->run(['fee']);
                            if ($result['fee'] < $lab->sample_approval_unlimit_amount) {
                                $approval->pass(true);
                                break;
                            }
                        }

                        break;
                    case 'approve_incharge':
                        $is_incharge = Equipments::user_is_eq_incharge($approval->user, $approval->equipment);
                        if ($approval->equipment->sample_autoapply || $is_incharge) {
                            $approval->pass(true);
                        }
                        break;
                    }
                break;
            case 'eq_reserv':
                switch($approval->flag) {
                    case 'approve_pi':
                        $lab = Q("{$approval->user} lab")->current();
                        if (!$lab->reserv_approval) {
                            $approval->pass(true);
                            break;
                        }

                        // 免审用户
                        $lab = Q("{$approval->user} lab")->current();
                        if ($lab->reserv_approval_unlimit_users_mode && in_array($approval->user->id, $lab->reserv_approval_unlimit_users)) {
                            $approval->pass(true);
                            break;
                        }

                        //　免审金额
                        if ($lab->reserv_approval_unlimit_amount_mode) {
                            // 院级管理员收费为0,命中该条
                            if ($approval->user->group->id && $approval->user->access('修改下属机构仪器的预约') && $approval->user->group->is_itself_or_ancestor_of($approval->equipment->group)) {
                                $approval->pass(true);
                                break;
                            }

                            // 机主收费为0,命中该条
                            $is_incharge = Equipments::user_is_eq_incharge($approval->user, $approval->equipment);
                            if ($is_incharge) {
                                $approval->pass(true);
                                break;
                            }

                            $charge = O('eq_charge');
                            $charge->source = $approval->source;
                            $lua = new EQ_Charge_LUA($charge);
                            $result = Event::trigger('eq_charge.lua_cal_ext_amount', $approval->equipment, $lua) ?: $lua->run(['fee']);
                            if ($result['fee'] < $lab->reserv_approval_unlimit_amount) {
                                $approval->pass(true);
                                break;
                            }
                        }

                        break;
                    case 'approve_incharge':
                        $is_incharge = Equipments::user_is_eq_incharge($approval->user, $approval->equipment);
                        if (!$approval->equipment->need_approval || $is_incharge) {
                            $approval->pass(true);
                        }
                        break;
                }
                break;
        }

    }

    public static function eq_sample_model_before_save($e, $sample, $new_data) 
    {
        $me = L('ME');

        $equipment = $sample->equipment;
        // 如果PI开启了审批，以下逻辑就会跳过PI审批环境，故此处注释，还是走一遍审批环节
        // //自动批准送样申请。该处不会陷入死循环，已测试
        // if (
        //     $equipment->sample_autoapply //自动批准
        //     &&
        //     ! $sample->sender->is_allowed_to('添加送样记录', $equipment)     //普通用户申请
        // ) {
        //     if (!$sample->id && $sample->status == EQ_Sample_Model::STATUS_APPLIED){
        //         //自动批准
        //         $sample->status = EQ_Sample_Model::STATUS_APPROVED;
        //     }elseif ($sample->status == EQ_Sample_Model::STATUS_SEND){
        //         //自动批准
        //         $sample->status = EQ_Sample_Model::STATUS_APPROVED;
        //     }

        // }

        /**
         * 送样记录关联课题组
         * 送样状态是待PI审核
         * 课题组没有勾选「送样需要审核」|| 送样者就是该课题组P
         * 
         * 更新状态至待机主审核
         */
        if (
            $sample->lab->id 
            && $sample->status == EQ_Sample_Model::STATUS_APPLIED 
            && (!$sample->lab->sample_approval || Q("{$sample->sender}<pi {$sample->lab}")->total_count())
            ) {
                if (Approval_Flow::sample_flow_lab()) {
                    $sample->status = EQ_Sample_Model::STATUS_SEND;
                }
        }
        return TRUE;
    }

    static function cannot_access_equipment($e, $equipment, $params) {
        if (L('skip_cannot_access_hook')) {
            $e->return_value = FALSE;
            return TRUE;
        }
        $me = $params[0];
        $dtstart = (int)$params[1];

        if ((int)$params[2]==0) {
            $dtend = $dtstart;
        }
        else {
            $dtend = (int)$params[2];
        }
        if ( $equipment->accept_reserv ) {
            $eq_reservs = Q("eq_reserv[equipment={$equipment}][user={$me}][dtstart={$dtstart}~{$dtend}|dtend={$dtstart}~{$dtend}|dtstart~dtend={$dtstart}]");
            foreach ($eq_reservs as $eq_reserv){
                //只有未审核的预约记录
                if(Q("approval[source={$eq_reserv}]")->total_count()){
                    $approval = O('approval', ['source' => $eq_reserv,'flag'=>'done']);
                    if(!$approval->id){
                        Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_reserv', '审核未通过，不可使用仪器'));
                        $e->return_value = TRUE;
                        return FALSE;
                    }
                }

            }
        }
    }

    public static function sample_flow_lab()
    {
        return in_array('eq_sample', Config::get('approval.modules')) && Config::get('flow.eq_sample')['approve_pi'];
    }

}
