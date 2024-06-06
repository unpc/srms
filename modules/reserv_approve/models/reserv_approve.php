<?php

class Reserv_Approve_Model extends Presentable_Model {

    const STATUS_INIT = 0;
    const STATUS_PI_APPROVE = 1;
    const STATUS_INCHARG_APPROVE = 2;
    const STATUS_PASS = 3;
    const STATUS_DELETE = 4;

    static $status = [
        self::STATUS_INIT => '发起',
        self::STATUS_PI_APPROVE => 'PI已审核',
        self::STATUS_INCHARG_APPROVE => '机主已审核',
        self::STATUS_PASS => '审核通过',
        self::STATUS_DELETE => '删除'
    ];

    // 发起审核，传入发起人和关联预约,生成发起审核记录
    static function create($eq_reserv) {
        if (!$eq_reserv->id) return;

        $approve = O('reserv_approve');
        $approve->user = $eq_reserv->user;
        $approve->reserv = $eq_reserv;
        $approve->status = self::STATUS_INIT;
        $approve->save();
        return $approve->id;
    }

    // 节点审核操作，传入审核人和通过or驳回，根据当前审核状态生成新审核记录
    function operate($user, $reject = FALSE, $des) {
        if (!$user->id || !$this->id) return;
        $new_status = $this->_get_approve_status_consent($reject);
        $new_approve = O('reserv_approve');
        $new_approve->user = $user;
        $new_approve->reserv = $this->reserv;
        $new_approve->status = $new_status;
        $new_approve->description = $des;
        $new_approve->save();
        Reserv_Approve_Message::send_to_user($user, $this->reserv, $reject, $new_approve);

        if (!$reject) {
            $equipment = $this->reserv->equipment;
            foreach (Q("$equipment<incharge user") as $approver) {
                Reserv_Approve_Message::send_to_incharges($approver, $this->reserv);
            }
        }

        // 被驳回删除后删除预约记录
        if($new_status == self::STATUS_DELETE) {
            $this->_delete();
        }
        // 最后一级审核后生成审核通过记录
        elseif ($new_status == self::STATUS_INCHARG_APPROVE) {
            $this->_pass($user);
        }
        return;
    }

    // 过期未审核，生成新审核记录，状态为已删除
    function overdue() {
        if (!$this->id) return;
        $approve = O('reserv_approve', [
            'reserv' => $this->reserv,
            'status' => self::STATUS_DELETE
        ]);
        if (!$approve->id) {
            $new_approve = O('reserv_approve');
            $new_approve->reserv = $this->reserv;
            $new_approve->status = self::STATUS_DELETE;
            $new_approve->save();
            Reserv_Approve_Message::send_to_user_overdue($this->reserv);
        }
        $this->_delete();
        return;
    }

    // 改变待审核用户
    function change_user($old_reserv) {
        if (!$this->id || $this->status != self::STATUS_INIT || !$old_reserv->id) return;
        $this->user = $this->reserv->user;
        return $this->save();
    }

    function approver_check() {
        if (!$this->id) return;
        $reserv = $this->reserv;
        if (!$reserv->user_id) return;
        // 数据库中真正关联的待审核的用户，用于检测是否需要重新提交审核
        $approvers_db = Q("$reserv<approver user");

        switch ($this->status) {
            case Reserv_Approve_Model::STATUS_INIT:
                $lab = Q("$reserv->user lab")->current();
                if ($lab->owner->id) {
                    $approvers = Q("$lab<owner user");
                } else {
                    $approvers = [];
                }
                break;
            case Reserv_Approve_Model::STATUS_PI_APPROVE:
                $equipment = $reserv->equipment;
                $approvers = Q("$equipment<incharge user");
                break;
            case Reserv_Approve_Model::STATUS_INCHARG_APPROVE:
            default:
                return;
                break;
        }
        if (Reserv_Approve_Help::check_reapprove($approvers, $approvers_db)) {
            if ($reserv->approver_change == FALSE) {
                Reserv_Approve_Message::send_to_user_approve_change($reserv);
                $reserv->approver_change = TRUE;
                $reserv->save();
            }
        }
        return;
    }
    // 获取下一级审核状态
    private function _get_approve_status_consent($reject) {
        switch ($this->status) {
            case self::STATUS_INIT :
                return $reject ? self::STATUS_DELETE : self::STATUS_PI_APPROVE;
                break;
            case self::STATUS_PI_APPROVE :
                return $reject ? self::STATUS_DELETE : self::STATUS_INCHARG_APPROVE;
                break;
        }
    }

    // 通过审核记录
    private function _pass($user) {
        if (!$this->id) return;
        $new_approve = O('reserv_approve');
        $new_approve->user = $user;
        $new_approve->reserv = $this->reserv;
        $new_approve->status = self::STATUS_PASS;
        return $new_approve->save();
    }

    // 删除预约记录
    private function _delete() {
        if (!$this->id) return;
        $reserv = $this->reserv;
        
        $abandon_reserv = O('abandon_reserv');
        $abandon_reserv->old_id = $reserv->id;
        $abandon_reserv->user = $reserv->user;
        $abandon_reserv->equipment = $reserv->equipment;
        $abandon_reserv->dtstart = $reserv->dtstart;
        $abandon_reserv->dtend = $reserv->dtend;
        $abandon_reserv->approve_status = $reserv->approve_status;
        $abandon_reserv->save();
        $pi = Q("$reserv->user lab")->current()->owner;
        $abandon_reserv->connect($pi, 'approver');
        $equipment = $reserv->equipment;
        foreach (Q("$equipment<incharge user") as $approver) {
            $abandon_reserv->connect($approver, 'approver');
        }
        $component = $reserv->component;
        // 兼容处理component删除无法删除预约的问题
        if (!$component->id && $reserv->id) {
            $reserv->delete();
        }
        $component->delete();
        return;
    }

    function save($overwrite = FALSE) {
        $this->ctime = Date::time();
        return parent::save($overwrite);
    }
}
