<?php

class Reserv_Approve_Help {

    // 传入eq_reserv，返回审核状态供table使用
    static function get_status_str($reserv) {
        $user = $reserv->user;
        if ($reserv->name() == 'eq_reserv') {
            $latest_approve = Q("reserv_approve[reserv=$reserv]:limit(1):sort(id D)")->current();
        }
        else {
            $latest_approve = Q("reserv_approve[reserv_id=$reserv->old_id]:limit(1):sort(id D)")->current();
        }

        $lastest_status = $latest_approve->status;
        if ((!$latest_approve->id && $reserv->approve_status == Szu_EQ_Reserv_Model::$state_num[Szu_EQ_Reserv_Model::STATE_NO_NEED]) || $reserv->approve_status == 0) {
            $status = I18N::T('reserv_approve', '无需审核');
            return sprintf('<span>%s</span>', $status);
        }
        elseif ($reserv->approver_change) {
            $status = I18N::T('reserv_approve', '待审批者有变更，请删除预约并重新提交审核');
            return sprintf('<span>%s</span>', $status);
        }
        switch ($lastest_status) {
            case Reserv_Approve_Model::STATUS_INIT:
                $lab = Q("$reserv->user lab")->current();
                $lab_owner = Q("$lab<owner user");
                $status = I18N::T('reserv_approve', '课题组负责人未审核，待%next_user审核', [
                        '%next_user' => V('reserv_approve:approve_table/data/next_user', ['users' => $lab_owner])
                    ]);
                break;
            case Reserv_Approve_Model::STATUS_PI_APPROVE:
                $equipment = $reserv->equipment;
                $incharges = Q("$equipment<incharge user");
                $status = I18N::T('reserv_approve', '仪器负责人未审核，待%next_user审核', [
                        '%next_user' => V('reserv_approve:approve_table/data/next_user', ['users' => $incharges])
                    ]);
                break;
            case Reserv_Approve_Model::STATUS_INCHARG_APPROVE:
            case Reserv_Approve_Model::STATUS_PASS:
                $status = I18N::T('reserv_approve', '%next_user 于 %time 审核通过', [
                    '%next_user' => V('reserv_approve:approve_table/data/organizer', ['user' => $latest_approve->user]),
                    '%time' => date('Y-m-d H:i:s', $latest_approve->ctime),
                ]);
                break;
            case Reserv_Approve_Model::STATUS_DELETE:
                if ($latest_approve->user->id) {
                    $status = I18N::T('reserv_approve', '审核未通过，系统自动删除预约记录');
                }
                else {
                    $status = I18N::T('reserv_approve', '审核逾期，系统自动删除预约记录');
                }
                break;
            default:
                break;
        }

        return sprintf('<span>%s</span>', $status);
    }

    // 传入reserv_approve，返回审核详情供审核详情使用
    static function get_info_str($approve) {
        $user = $approve->user;
        $time = $approve->ctime;
        switch ($approve->status) {
            case Reserv_Approve_Model::STATUS_INIT:
                $status = I18N::T('reserv_approve', '用户%user发起审核', [
                        '%user' => V('reserv_approve:info/data/user', ['user' => $user])
                    ]);
                break;
            case Reserv_Approve_Model::STATUS_PI_APPROVE:
                $status = I18N::T('reserv_approve', '课题组负责人%user审核通过', [
                        '%user' => V('reserv_approve:info/data/user', ['user' => $user])
                    ]);
                break;
            case Reserv_Approve_Model::STATUS_INCHARG_APPROVE:
                $status = I18N::T('reserv_approve', '机主%user审核通过', [
                        '%user' => V('reserv_approve:info/data/user', ['user' => $user])
                    ]);
                break;
            case Reserv_Approve_Model::STATUS_PASS:
                $status = I18N::T('reserv_approve', '审核通过');
                break;
            case Reserv_Approve_Model::STATUS_DELETE:
                if ($user->id) {
                    $status = I18N::T('reserv_approve', '审核被%user驳回后删除', [
                            '%user' => V('reserv_approve:info/data/user', ['user' => $user])
                        ]);
                }
                else {
                    $status = I18N::T('reserv_approve', '审核由于审核逾期被删除', [
                            '%user' => V('reserv_approve:info/data/user', ['user' => $user])
                        ]);
                }
                break;
            default:
                break;
        }
        return sprintf('<span>%s</span>', $status);
    }

    static function links($object) {
        $me = L('ME');
        if ($object->name() == 'abandon_reserv') {
            $links['view'] = [
                'text' => I18N::T('reserv_approve', '详情'),
                'extra'=>'q-object="reject_info" q-event="click" q-src="' . H(URI::url('!reserv_approve/index')) .
                    '" q-static="' . H(['reserv_id'=>$object->old_id]) .
                    '" class="blue"',
            ];
        }
        else {
            $approve = Q("reserv_approve[reserv=$object]:limit(1):sort(id D)")->current();
            $links['view'] = [
                'text' => I18N::T('reserv_approve', '详情'),
                'extra'=>'q-object="info" q-event="click" q-src="' . H(URI::url('!reserv_approve/index')) .
                    '" q-static="' . H(['reserv_id'=>$object->id]) .
                    '" class="blue"',
            ];

            if(!$object->approver_change && (($approve->status == Reserv_Approve_Model::STATUS_INIT && $me->is_allowed_to('PI审核', $approve))
              || ($approve->status == Reserv_Approve_Model::STATUS_PI_APPROVE && $me->is_allowed_to('机主审核', $approve))
            )) {
                $links['approve'] = [
                    'text' => I18N::T('reserv_approve', '通过'),
                    'extra'=>'q-object="approve" q-event="click" q-src="' . H(URI::url('!reserv_approve/index')) .
                        '" q-static="' . H(['approve_id'=>$approve->id]) .
                        '" class="blue"',
                ];
                $links['reject'] = [
                    'text' => I18N::T('reserv_approve', '驳回'),
                    'extra'=>'q-object="reject" q-event="click" q-src="' . H(URI::url('!reserv_approve/index')) .
                        '" q-static="' . H(['approve_id'=>$approve->id]) .
                        '" class="blue"',
                ];
            }
        }
        return $links;
    }

    // 检测是否需要重新提交审核
    // approvers: 现有权限下拥有权限的用户
    // approvers_db: 数据库中真正关联的待审核用户
    // TRUE表示需重新提交
    static function check_reapprove($approvers, $approvers_db) {
        $ap = $approvers ? $approvers->to_assoc('id') : [];
        $ad = $approvers ? $approvers_db->to_assoc('id') : [];
        return (bool)!count(array_intersect($ap, $ad));
    }

    // TRUE表示需重新提交
    static function check_reapprove_by_approve($reserv, $approve) {

        switch ($approve->status) {
            case Reserv_Approve_Model::STATUS_INIT:
                $lab = Q("$reserv->user lab")->current();
                $approvers = Q("$lab<owner user");
                break;
            case Reserv_Approve_Model::STATUS_INCHARG_APPROVE:
                $equipment = $reserv->equipment;
                $approvers = Q("$equipment<incharge user");
                break;
            default:
                return FALSE;
                break;
        }
        // 数据库中真正关联的待审核的用户，用于检测是否需要重新提交审核
        $approvers_db = Q("$reserv<approver user");
        return self::check_reapprove($approvers, $approvers_db);
    }
}
