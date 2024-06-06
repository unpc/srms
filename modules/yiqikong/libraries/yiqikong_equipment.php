<?php

use \Pheanstalk\Pheanstalk;

class YiQiKong_Equipment
{

    static function on_equipment_deleted($e, $equipment = null)
    {
        if (!Config::get('lab.modules')['app'] || !$equipment->yiqikong_id) return;
        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

        $data = [];
        $data['path'] = "equipment/{$equipment->yiqikong_id}";
        $data['method'] = "delete";
        $data['rpc_token'] = $gatewayConfig['mq']['x-beanstalk-token'];
        $data['body'] = [
            'source_name' => LAB_ID,
            'source_id' => $equipment->id
        ];
        $mq->useTube('stark')->put(json_encode($data, TRUE));
    }

    static function on_equipment_saved($e, $equipment, $old_data, $new_data)
    {
        if (!Config::get('lab.modules')['app'] || !$equipment->yiqikong_id) return TRUE;

        $incharges = $incharges_n = [];
        foreach (Q("{$equipment} user.incharge") as $incharge) {
            $incharges[$incharge->yiqikong_id ?: $incharge->name] = $incharge->name;
            $incharges_n[$incharge->id] = ['name' => $incharge->name, 'yiqikong_id' => $incharge->yiqikong_id];
        }

        $after = [
            'can_reserv' => (int)$equipment->accept_reserv,
            'reserv_require_pc' => (int)$equipment->reserv_require_pc,
            'can_sample' => (int)$equipment->accept_sample,
            'sample_require_pc' => (int)$equipment->sample_require_pc,
            'sample_lock' => $equipment->sample_lock == 'on' ? 1 : 0,
            'reserv_lock' => $equipment->reserv_lock == 'on' ? 1 : 0,
            'need_approval' => (int)$equipment->need_approval,
            'need_training' => (int)$equipment->require_training,
            'auto_apply' => (int)$equipment->sample_autoapply,
            'incharges' => $incharges,
            'incharges_n' => $incharges_n,
            'control_mode' => $equipment->control_mode,
            'control_address' => $equipment->control_address,
            'bluetooth_serial_address' => $equipment->bluetooth_serial_address, //增加蓝牙插座序列号
            'lock_incharge_control' => (int)$equipment->lock_incharge_control,
            'status' => $equipment->status,
//            'is_preheat' => $equipment->is_preheat ?? 0,
//            'is_cooling' => $equipment->is_cooling ?? 0,
        ];

        $preheatCooling = Q("eq_preheat_cooling[equipment={$equipment}]:sort('ctime D')")->current();
        if ($preheatCooling->id) {
            $after['preheat'] = $preheatCooling->preheat_time ?? 0;
            $after['cooling'] = $preheatCooling->cooling_time ?? 0;
        }

        //获取当前收费信息
        $chargeSetting = [];

        $department = $equipment->billing_dept;

        if ($department->id) {
            $u = O('user');
            $standards = EQ_Charge::charge_template_standards($equipment, null, $u);
            if ($equipment->accept_reserv && $standards['reserv']) {
                $rc = $equipment->charge_script['reserv'] && !$standards['record'] ? '预约 / 使用计费设置' : '预约计费设置';
                $reservDefault = ['name' => $rc, 'content' => str_replace(['<p>', '</p>', '<span>', '</span>'], ['', '', '', '.'], $standards['reserv'])];
                $reservAdmin = ['name' => $rc, 'content' => '免费使用'];
            }
            if ($standards['record']) {
                $recordDefault = ['name' => '使用计费设置', 'content' => str_replace(['<p>', '</p>', '<span>', '</span>'], ['', '', '', '.'], $standards['record'])];
                $recordAdmin = ['name' => '使用计费设置', 'content' => '免费使用'];
            }
            if ($equipment->accept_sample) {
                $sampleDefault = ['name' => '送样计费设置', 'content' => str_replace(['<p>', '</p>', '<span>', '</span>'], ['', '', '', '.'], $standards['sample'])];
                $sampleAdmin = ['name' => '送样计费设置', 'content' => '免费使用'];
            }
        }

        $set = [];
        $setDefault = [];
        isset($reservAdmin) ? $set[] = $reservAdmin : '';
        isset($recordAdmin) ? $set[] = $recordAdmin : '';
        isset($sampleAdmin) ? $set[] = $sampleAdmin : '';

        isset($reservDefault) ? $setDefault[] = $reservDefault : '';
        isset($recordDefault) ? $setDefault[] = $recordDefault : '';
        isset($sampleDefault) ? $setDefault[] = $sampleDefault : '';

        $chargeSetting['admin'] = $chargeSetting['incharge'] = $chargeSetting['genee'] = $set;

        $chargeSetting['default'] = $setDefault;

        $after['charge_template'] = $department->id ? $chargeSetting : [];

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

        $payload = [
            'method' => 'PATCH',
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' => "equipment/{$equipment->yiqikong_id}",
            'body' => $after,
        ];

        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));

        return TRUE;
    }

    static function on_training_saved($e, $training, $old_data, $new_data)
    {
        if (L('YiQiKongTrainingAction') || !Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

        if (!$new_data['id']) {  // 更新操作
            $path = "equipment/training/0";
            $method = 'PUT';
        } else { // 新增操作
            $path = "equipment/training";
            $method = 'POST';
        }

        $payload = [
            'method' => $method,
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' => $path,
            'body' => [
                'user' => $training->user->yiqikong_id,
                'user_name' => $training->user->name,
                'lab_name' => Q("$training->user lab")->current()->name,
                'address' => $training->user->address,
                'email' => $training->user->email,
                'user_local' => $training->user->id,
                'equipment' => $training->equipment->yiqikong_id,
                'equipment_local' => $training->equipment->id,
                'status' => $training->status,
                'atime' => date('Y-m-d H:i:s', $training->atime),
                'source_name' => LAB_ID,
                'source_id' => $training->id,
                'yiqikong_id' => $training->yiqikong_id
            ]
        ];

        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));

        Cache::L('YiQiKongTrainingAction', NULL);
        return TRUE;
    }

    static function on_training_deleted($e, $training)
    {
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

        $payload = [
            'method' => 'PUT',
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' => "equipment/training/0",
            'body' => [
                'source_name' => LAB_ID,
                'source_id' => $training->id,
                'status' => UE_Training_Model::STATUS_DELETED,
                'atime' => date('Y-m-d H:i:s', $training->atime),
            ]
        ];

        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));

        return TRUE;
    }

    static function on_status_saved($e, $status, $old_data, $new_data)
    {
        if (!Config::get('lab.modules')['app'] || !$status->equipment->yiqikong_id) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

        if (!$new_data['id']) {
            $path = "equipment/status/0";
            $method = 'patch';
        } else {
            $path = "equipment/status";
            $method = 'post';
        }

        $payload = [
            'method' => $method,
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' => $path,
            'body' => [
                'equipment' => $status->equipment->yiqikong_id,
                'source_name' => LAB_ID,
                'source_id' => $status->id,
                'dtstart' => $status->dtstart,
                'dtend' => $status->dtend,
                'status' => $status->status,
                'description' => $status->description,
                'ctime' => $status->ctime,
            ]
        ];

        $mq->useTube('stark')
            ->put(json_encode($payload, TRUE));

        return TRUE;
    }

    public static function on_eq_announce_saved($e, $announce, $old_data, $new_data)
    {
        if (!Config::get('lab.modules')['app'] || !$announce->equipment->yiqikong_id) return TRUE;
        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

        if ($old_data['title']) {  // 更新操作
            $path = "equipment/announce";
            $method = 'PUT';
        } else { // 新增操作
            $path = "equipment/announce";
            $method = 'POST';
        }

        $payload = [
            'method' => $method,
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' => $path,
            'body' => [
                'equipment' => $announce->equipment->yiqikong_id,
                'title' => $announce->title,
                'content' => $announce->content,
                'author_local' => $announce->author->id,
                'author' => $announce->author->yiqikong_id,
                'is_sticky' => $announce->is_sticky,
                'ctime' => $announce->ctime,
                'mtime' => $announce->mtime,
                'source_name' => LAB_ID,
                'source_id' => $announce->id,
                'dtstart' => $announce->dtstart,
                'dtend' => $announce->dtend,
            ]
        ];

        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));

        return TRUE;
    }

    static function on_eq_announce_deleted($e, $announce)
    {
        if (!Config::get('lab.modules')['app'] || !$announce->equipment->yiqikong_id) return TRUE;
        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $payload = [
            'method' => 'DELETE',
            'path' => 'equipment/announce',
            'header' => ['x-yiqikong-notify' => TRUE],
            'body' => [
                'source_name' => LAB_ID,
                'source_id' => $announce->id,
            ]
        ];

        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));

        return TRUE;
    }

    static function on_user_eq_announce_connect($e, $user, $announce)
    {
        if (!Config::get('lab.modules')['app'] || !$announce->equipment->yiqikong_id) return TRUE;
        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $payload = [
            'method' => 'POST',
            'path' => 'equipment/announce/user',
            'header' => ['x-yiqikong-notify' => TRUE],
            'body' => [
                'source_name' => LAB_ID,
                'user' => $user->yiqikong_id,
                'user_local' => $user->id,
                'announce' => $announce->id,
                'type' => 'read',
            ]
        ];
        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));

        return TRUE;
    }

    static function on_user_eq_announce_disconnect($e, $user, $announce)
    {
        if (!Config::get('lab.modules')['app'] || !$announce->equipment->yiqikong_id) return TRUE;
        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $payload = [
            'method' => 'DELETE',
            'path' => 'equipment/announce/user',
            'header' => ['x-yiqikong-notify' => TRUE],
            'body' => [
                'source_name' => LAB_ID,
                'announce' => $announce->id,
                'type' => 'read',
            ]
        ];
        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));

        return TRUE;
    }
}
