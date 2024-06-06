<?php
class Equipment_Training_API
{
    static $training_status_label = [
        UE_Training_Model::STATUS_APPLIED => 'requested',
        UE_Training_Model::STATUS_APPROVED => 'approved',
        UE_Training_Model::STATUS_REFUSE => 'rejected',
        UE_Training_Model::STATUS_OVERDUE => 'overdue',
        UE_Training_Model::STATUS_AGAIN => 'again',
        UE_Training_Model::STATUS_DELETED => 'deleted',
    ];

    public static function training_format($training)
    {
        $labs = [];
        foreach (Q("{$training->user} lab") as $lab) {
            $labs[$lab->id] = [
                'id' => $lab->id,
                'name' => $lab->name,
            ];
        }

        return [
            'user' => [
                'id' => (int) $training->user->id,
                'name' => $training->user->name,
                'address' => $training->user->address,
                'phone' => $training->user->phone,
                'email' => $training->user->email,
                'labs' => $labs,
            ],
            'proposer' => [
                'id' => (int) $training->user->id,
                'name' => $training->user->name,
            ],
            'equipment' => [
                'id' => (int) $training->equipment->id,
                'name' => $training->equipment->name,
                'icon' => [
                    'original' => $training->equipment->icon_url($training->equipment->icon_file('real') ? 'real' : 128),
                    '32×32' => $training->equipment->icon_url('32')
                ],
            ],
            'ctime' => $training->ctime,
            'description' => $training->description,
            'status' => self::$training_status_label[$training->status],
            'isExpire' => $training->atime ? 1 : 0,
            'deadline' => $training->atime ? : 0
        ];
    }

    public static function equipment_training_get($e, $params, $data, $query)
    {
        $training = O('ue_training', $params[0]);
        if (!$training->id) {
            throw new Exception('training not found', 404);
        }
        $e->return_value = self::training_format($training);
        return;
    }

    public static function equipment_trainings_get($e, $params, $data, $query)
    {
        $selector = "ue_training";
        if (isset($query['equipmentId$'])) {
            $ids = array_map(function ($i) {
                return (int)$i;
            }, explode(',', $query['equipmentId$']));
            if (!count($ids)) {
                throw new Exception('equipmentId cannot be empty', 404);
            }
            $selector .= "[equipment_id=" . join(",", $ids) . "]";
        }

        if (isset($query['userId$'])) {
            $ids = array_map(function ($i) {
                return (int)$i;
            }, explode(',', $query['userId$']));
            if (!count($ids)) {
                throw new Exception('userId cannot be empty', 404);
            }
            $selector .= "[user_id=" . join(",", $ids) . "]";
        }

        if (isset($query['status$'])) {
            $status = array_map(function ($i) {
                return array_flip(self::$training_status_label)[$i] ?: null;
            }, explode(',', $query['status$']));
            if (!count($status)) {
                throw new Exception('status cannot be empty', 404);
            }
            $selector .= "[status=" . join(",", $status) . "]";
        }

        error_log($selector);
        $total = Q("$selector")->total_count();

        $start = (int) $query['st'] ?: 0;
        $per_page = (int) $query['pp'] ?: 30;
        $start = $start - ($start % $per_page);
        $selector .= ":limit({$start},{$per_page}):sort(ctime D)";

        $trainings = [];
        foreach (Q("$selector") as $training) {
            $trainings[] = self::training_format($training);
        }
        $e->return_value = ["total" => $total, "items" => $trainings];
    }

    public static function equipment_training_post($e, $params, $data, $query)
    {
        $equipment = O('equipment', ['id' => $data['equipment']['identity']]);
        if (!$equipment->id) {
            throw new Exception('equipment not found', 404);
        }
        if (
            !$equipment->require_training
            || $equipment->status == EQ_Status_Model::NO_LONGER_IN_SERVICE
        ) {
            throw new Exception(I18N::T('equipments', '此仪器无需培训!'), 403);
        }
        $user = L("gapperUser");

        if ($user->is_allowed_to('管理培训', $equipment)) {
            throw new Exception(I18N::T('equipments', '您无需申请该设备的培训课程!'), 403);
        }

        //申请
        $status = implode(',', [
            UE_Training_Model::STATUS_APPLIED,
            UE_Training_Model::STATUS_APPROVED,
            UE_Training_Model::STATUS_AGAIN
        ]);
        $trainings = Q("ue_training[equipment={$equipment}][user={$user}][status={$status}]");
        if ($trainings->total_count()) {
            throw new Exception(I18N::T('equipments', '您已经申请该设备的培训课程!'), 403);
        }

        $status = implode(',', [
            UE_Training_Model::STATUS_REFUSE,
            UE_Training_Model::STATUS_DELETED,
            UE_Training_Model::STATUS_OVERDUE
        ]);
        $trainings = Q("ue_training[equipment={$equipment}][user={$user}][status={$status}]");

        $training = O('ue_training');
        $training->user = $user;
        $training->proposer = $user;
        $training->equipment = $equipment;
        $training->status = $trainings->total_count() ? UE_Training_Model::STATUS_AGAIN : UE_Training_Model::STATUS_APPLIED;
        $training->type = $user->member_type;
        $training->save();

        Log::add(strtr('[equipment_training API] %user_name[%user_id]申请仪器 %equipment_name [%equipment_id] 培训[%training_id]', [
            '%user_name' => $user->name,
            '%user_id' => $user->id,
            '%equipment_name' => $equipment->name,
            '%equipment_id' => $equipment->id,
            '%training_id' => $training->id,
        ]), 'journal');
        $e->return_value = self::training_format($training);
    }

    public static function equipment_training_patch($e, $params, $data, $query)
    {
        $me = L("gapperUser");

        $training = O('ue_training', ['id' => $params[0]]);
        if (!$training->id) {
            throw new Exception('training not found', 404);
        }

        $equipment = $training->equipment;
        $user = $training->user;
        if (!$equipment->id) {
            throw new Exception('equipment not found', 404);
        }
        if (
            !$equipment->require_training
            || $equipment->status == EQ_Status_Model::NO_LONGER_IN_SERVICE
        ) {
            throw new Exception(I18N::T('equipments', '此仪器无需培训!'), 403);
        }

        if (!$me->is_allowed_to('管理培训', $equipment)) {
            throw new Exception(I18N::T('equipments', '您无权修改该培训申请!'), 403);
        }

        if ($data['isExpire']) {
            $today = getdate(time());
            $now = mktime(0, 0, 0, $today['mon'], $today['mday'], $today['year']);
            $dl = getdate($data['deadline']);
            $deadline = mktime(0, 0, 0, $dl['mon'], $dl['mday'], $dl['year']);
            if ($now - $deadline > 0) {
                throw new Exception(I18N::T('equipments', '过期时间不能小于当前时间!'), 403);
            }
        }

        $training->atime = $data['isExpire'] ? $data['deadline']: '0';
        $training->save();

        $e->return_value = self::training_format($training);

        return;
    }
}
