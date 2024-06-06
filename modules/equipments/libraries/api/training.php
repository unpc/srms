<?php

class API_Training extends API_Common {

    function post_training($params = []) {
        $this->_ready();
        $equipment = O('equipment', $params['equipmentId']);
        if (!$equipment->id) throw new API_Exception(self::$errors[404], 404);
        if (!$equipment->require_training) throw new API_Exception(I18N::T('equipments', '该仪器不需要培训!'), 400);

        $user = O('user', $params['userId']);
        $status = implode(',', [
            UE_Training_Model::STATUS_APPROVED,
        ]);
        $trainings = Q("ue_training[equipment={$equipment}][user={$user}][status={$status}]");
        if ($trainings->total_count()) {
            throw new API_Exception(I18N::T('equipments', '您已通过培训!'), 403);
        }

        $status = implode(',', [
            UE_Training_Model::STATUS_APPLIED,
            UE_Training_Model::STATUS_AGAIN
        ]);
        $trainings = Q("ue_training[equipment={$equipment}][user={$user}][status={$status}]:sort(ctime)");
        if ($trainings->total_count()) {
            foreach ($trainings as $train) {
                $train->check_time = Date::time();
                $train->save();
            }
        } else {
            $status = implode(',', [
                UE_Training_Model::STATUS_REFUSE,
                UE_Training_Model::STATUS_DELETED,
                UE_Training_Model::STATUS_OVERDUE
            ]);
            $trainings = Q("ue_training[equipment={$equipment}][user={$user}][status={$status}]");
            $train = O('ue_training');
            $train->user = $user;
            $train->equipment = $equipment;
            $train->ctime = -1;
            $train->check_time = Date::time();
            $train->status = $trainings->total_count() ? UE_Training_Model::STATUS_AGAIN : UE_Training_Model::STATUS_APPLIED;
            $train->type = $user->member_type;
            $train->atime = $params['atime'] ? : 0;
            $train->save();
        }

        return [
            'id' => $train->id, // 培训记录ID
            'userId' => $train->user->id, // 用户ID
            'equipmentId' => $train->equipment->id, // 仪器ID
            'ctime' => $train->ctime, // 申请时间
            'check_time' => $train->check_time, // 签到时间
            'mtime' => $train->mtime, // 通过时间
            'atime' => $train->atime, // 过期时间
            'type' => $train->type,
            'status' => $train->status // 状态'
        ];
    }
    
    function get_trainings($params = []) {
        $this->_ready();
        $selector = "ue_training";

        if ($params['equipment']) {
            $equipment = O('equipment', (int)$params['equipment']);
            $selector .= "[equipment={$equipment}]";
        }

        if ($params['dtstart']) {
            $dtstart = $params['dtstart'];
            $selector .= "[ctime>={$dtstart}]";
        }

        if ($params['dtend']) {
            $dtend = $params['dtend'];
            $selector .= "[ctime<={$dtend}]";
        }

        if ($params['status']) {
            $status = (int)$params['status'];
            $selector .= "[status={$status}]";
        }

        list($start, $step) = (array)$params['limit'];

        $start = max(0, (int)$start);
        $step = min(100, (int)$step);

        $selector .= ":limit({$start}, {$step})";

        $trainings = Q($selector);

        $data = [];

        foreach ($trainings as $t) {
            $data[] = [
                'id' => $t->id,
                'user' => [ $t->user->id => $t->user->name ],
                'proposer' => [ $t->proposer->id => $t->proposer->name ],
                'equipmentId' => $t->equipment->id,
                'ctime' => $t->ctime,
                'atime' => $t->atime,
                'description' => $t->description,
                'status' => $t->status
            ];
        }

        return $data;

    }

    function searchTrainings($criteria = [])
    {
        $this->_ready();

        $cache = Cache::factory('redis');
        $token = uniqid();
        $return_data['token'] = $token;

        $selector = "ue_training";

        if ($criteria['equipment']) {
            $equipment = O('equipment', (int) $criteria['equipment']);
            $selector .= "[equipment={$equipment}]";
        } else {
            $selector .= "[equipment_id>0]";
        }

        if ($criteria['dtstart']) {
            $dtstart = $criteria['dtstart'];
            $selector .= "[ctime>={$dtstart}]";
        }

        if ($criteria['dtend']) {
            $dtend = $criteria['dtend'];
            $selector .= "[ctime<={$dtend}]";
        }

        if ($criteria['status']) {
            $status = (int) $criteria['status'];
            $selector .= "[status={$status}]";
        }

        $cache->set($token, $selector, 3600);
        $total = Q($selector)->total_count();

        if ($opts['total']) {
            $db = Database::factory();
            $SQL = "SELECT SUM(`ge`.`napproved`) `count` " .
                "FROM `ge_training` `ge` " .
                "WHERE `ge`.`equipment_id` > 0";

            $ge_training = $db->query($SQL);
            $total += $ge_training->count;
        }

        $return_data['total'] = $total;
        return $return_data;
    }
}
