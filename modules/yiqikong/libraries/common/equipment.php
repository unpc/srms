<?php

class Common_Equipment extends Common_Base
{

    public static function update($params = [])
    {
        if (!$params['_me']) throw new API_Exception(parent::$errors[401], 401);

        // 兼容有些时候没有仪器控ID _user_local
        if ($params['_me']['_user_local']) $me = parent::_MAKEUSER($params['_me']['id'], $params['_me']['_user_local']);
        else $me = parent::_MAKEUSER($params['_me']['id']);

        $equipment = O('equipment', ['yiqikong_id' => $params['uuid']]);
        if (!$equipment->id) throw new API_Exception(parent::$errors[404], 404);

        if (isset($params['can_reserv']) || isset($params['need_approval'])) {
            if ($me->is_allowed_to('修改预约设置', $equipment)) {
                isset($params['can_reserv']) and $equipment->accept_reserv = (int)$params['can_reserv'];
                isset($params['need_approval']) and $equipment->need_approval = (int)$params['need_approval'];
            } else {
                throw new API_Exception(parent::$errors[1002], 1002);
            }
        }

        if (isset($params['can_sample'])) {
            if ($me->is_allowed_to('修改送样设置', $equipment))
                $equipment->accept_sample = (int)$params['can_sample'];
            else
                throw new API_Exception(parent::$errors[1002], 1002);
        }

        //@TODO::这边需要加权限吗？先保持原样吧
        isset($params['need_training']) && $equipment->require_training = (int)$params['need_training'];
        isset($params['control_mode']) && $equipment->control_mode = $params['control_mode'];
        isset($params['lock_incharge_control']) && $equipment->lock_incharge_control = (int)$params['lock_incharge_control'];

        // TODO: 此处应统一为control_address
        if (isset($params['bluetooth_serial_address']) && $params['bluetooth_serial_address']) {
            $other_equipments = Q("equipment[bluetooth_serial_address={$params['bluetooth_serial_address']}][id!={$equipment->id}]");
            if ($other_equipments->total_count() > 0) {
                return [
                    "error" => true,
                    "message" => I18N::T("equipments", "因该蓝牙已被设备[{$other_equipments->current()->name}]绑定，绑定失败")
                ];
            }
        }

        if (isset($params['bluetooth_serial_address']) && $equipment->bluetooth_serial_address != $params['bluetooth_serial_address']) {
            $equipment->bluetooth_serial_address = $params['bluetooth_serial_address'];
            // 为什么字段不一样？目前不是很清楚,先这样写了
            $equipment->control_address = $params['bluetooth_serial_address'];
            Log::add(strtr('[equipments] %user_name[%user_id]修改%equipment_name[%equipment_id]仪器蓝牙控制, 蓝牙插座序列号为：%bluetooth_serial_address', [
                '%user_name' => $me->name,
                '%user_id' => $me->id,
                '%equipment_name' => $equipment->name,
                '%equipment_id' => $equipment->id,
                '%bluetooth_serial_address' => $equipment->bluetooth_serial_address
            ]), 'bluetooth_control');
        }

        if (isset($params['sample_require_pc'])){
            $equipment->sample_require_pc = (int) ($equipment->accept_sample && $params['sample_require_pc']);
        }
        if (isset($params['reserv_require_pc'])){
            $equipment->reserv_require_pc = (int) ($equipment->accept_reserv && $params['reserv_require_pc']);
        }

        $equipment->save();

        return [
            'id' => $equipment->id,
            'accept_reserv' => $equipment->accept_reserv,
            'accept_sample' => $equipment->accept_sample,
            'need_approval' => $equipment->need_approval,
            'require_training' => $equipment->require_training,
        ];
    }

    public static function updateTraining($params = [])
    {
        if (!$params['source_id']) return true;
        $ue_training = O('ue_training', $params['source_id']);
        if (!$ue_training->id) return true;
        isset($params['status']) && $ue_training->status = (int)$params['status'];
        isset($params['atime']) && $ue_training->atime = (int)$params['atime'];
        $ue_training->save();
        return true;
    }

    public static function createTraining($params = [])
    {
        if (!$params['equipment']) return true;
        if (!$params['user'] && !$params['user_local']) return true;
        if (!$params['id']) return true;
        $equipment = O('equipment', ['yiqikong_id' => $params['equipment']]);
        if (!$equipment->id || !$equipment->require_training
            || $equipment->status==EQ_Status_Model::NO_LONGER_IN_SERVICE) {
            return [
                'success' => 0,
                'id' => $params['id'],//app 端标志
                'err_msg' => I18N::T('yiqikong', '申请培训失败, 仪器不存在或该仪器不需要申请培训')
            ];
        }
        try{
            $user = parent::_MAKEUSER($params['user'], $params['user_local']);
        } catch (\Exception $e) {
            return [
                'success' => 0,
                'id' => $params['id'],//app 端标志
                'err_msg' => I18N::T('yiqikong', '申请培训失败, 没找到相关用户')
            ];
        }
        if (!$user->id ) {
            return [
                'success' => 0,
                'id' => $params['id'],//app 端标志
                'err_msg' => I18N::T('yiqikong', '申请培训失败, 没找到相关用户')
            ];
        }

        $require_exam = false;
        if ($equipment->require_exam) {
            $require_exam = true;
            if ($user->id && !$user->gapper_id) {
                $lousers = (new LoGapper())->get('users', ['email'=> $me->email]);
                $louser = @current($lousers['items']);
                if ($louser['id']) {
                    $user->gapper_id = $louser['id'];
                    $user->save();
                }
            }
            $history_exams =  (array)$equipment->history_exams;
            $exams_id_str = implode(',', $history_exams);
            $remote_exam_app = Config::get('exam.remote_exam_app');
            $remote_ids = Q("exam[id={$exams_id_str}][remote_app={$remote_exam_app}]")->to_assoc('remote_id', 'remote_id');
            $exam = Q("$equipment exam")->current();
            $url = $exam->getRemoteUrl();
            if ($user->gapper_id) $result = (new HiExam())->get("user/{$user->gapper_id}/exams/result", ['exams'=>$remote_ids]);
            foreach ((array)$result as $res) {
                if ($res['status'] == '通过') {
                    $require_exam = false;
                    break;
                }
            }
        }
        if ($require_exam) {
            return [
                'success' => 0,
                'id' => $params['id'],//app 端标志
                'err_msg' => I18N::T('yiqikong', '您还未通过理论考试和培训，请先到网页端参加理论考试和培训!')
            ];
        }


        $status = implode(',', [
            UE_Training_Model::STATUS_APPLIED,
            UE_Training_Model::STATUS_APPROVED,
            UE_Training_Model::STATUS_AGAIN
        ]);
        $trainings = Q("ue_training[equipment={$equipment}][user={$user}][status={$status}]");
        if ($trainings->total_count()) {
            return [
                'success' => 0,
                'id' => $params['id'],//app 端标志
                'err_msg' => I18N::T('yiqikong', '申请培训失败, 您已经申请过该设备的培训课程!')
            ];
        }
        $status = implode(',', [
            UE_Training_Model::STATUS_REFUSE,
            UE_Training_Model::STATUS_DELETED,
            UE_Training_Model::STATUS_OVERDUE
        ]);
        $trainings = Q("ue_training[equipment={$equipment}][user={$user}][status={$status}]");
        //增加培训报名
        $training = O('ue_training');
        $training->user = $user;
        $training->proposer = $user;
        $training->equipment = $equipment;
        $training->status = $trainings->total_count() ? UE_Training_Model::STATUS_AGAIN : UE_Training_Model::STATUS_APPLIED;
        $training->type = $user->member_type;
        $training->yiqikong_id = $params['id'];
        Event::trigger('ue_training.form.submit', $training, $params);
        $training->save();
        if ($training->id) {
            return [
                'success' => 1,
                'id' => $params['id'],//app 端标志
                'err_msg' => I18N::T('yiqikong', '申请培训成功!')
            ];
        } else {
            return [
                'success' => 0,
                'id' => $params['id'],//app 端标志
                'err_msg' => I18N::T('yiqikong', '申请培训失败!')
            ];
        }

    }
}
