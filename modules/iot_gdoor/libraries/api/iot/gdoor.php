<?php

class API_Iot_Gdoor extends API_Common
{
    private function log()
    {
        $args = func_get_args();
        if ($args) {
            $format = array_shift($args);
            if ($args) {
                $str = vsprintf($format, $args);
            } else {
                $str = $format;
            }
            Log::add("[iot-gdoor api] {$str}", 'devices');
        }
    }

    public function entrance($door_id, $user_id, $time = 0)
    {
        $this->_ready('iot_gdoor');

        if (!$time) {
            $time = time();
        }

        $door = O('iot_gdoor', ['gdoor_id' => $door_id]);
        if (!$door->id) {
            throw new API_Exception('找不到对应的门牌');
        }
        $user = Event::trigger('get_user_from_gapper_id', $user_id) ? : O('user', ['gapper_id' => $user_id]);
        if (!$user->id) {
            $user = O('user', $user_id);
        }
        if (!$user->id) {
            $this->log(
                "门牌[%d] 开门验证失败: 人员信息 %s 未找到关联用户",
                $door->id,
                $user_id
            );

            throw new API_Exception('找不到相应的用户');
        }

        if ($door->access($user)) {
            $this->log(
                "门牌[%d] 通过验证: %s[%d]",
                $door->id,
                $user->name,
                $user->id
            );
    
            return 'yes';
        }

        $this->log(
            "门牌[%d] 开门验证失败: 禁止用户%s[%d] 开门",
            $door->id,
            $user->name,
            $user->id
        );
        return 'no';
    }

    public function assoEquipments($door_id, $start, $per)
    {
        $this->_ready('iot_gdoor');
        $door = O('iot_gdoor', ['gdoor_id' => $door_id]);
        if (!$door->id) {
            throw new API_Exception('找不到对应的门牌');
        }
        $start = $start ?? 0;
        $per = $per ?? 20;
        $equipments = Q("{$door}<asso equipment:limit({$start},{$per})");

        $data = [];
        foreach ($equipments as $equipment) {
            $contacts = Q("{$equipment} user.contact")->to_assoc('id', 'name');
            $data[] = [
                'id' => $equipment->id,
                'icon_url' => $equipment->icon_file('real') ? Config::get('system.base_url') . Cache::cache_file($equipment->icon_file('real')) . '?_=' . $equipment->mtime : $equipment->icon_url('128'),
                'url' => $equipment->url(),
                'accept_reserv' => $equipment->accept_reserv,
                'reserv_url' => $equipment->url('reserv'),
                'accept_sample' => $equipment->accept_sample,
                'sample_url' => $equipment->url('sample'),
                'eq_name' => $equipment->name,
                'model_no' => $equipment->model_no,
                'location' => $equipment->location . $equipment->location2,
                'contacts' => join(', ', $contacts),
                'control_mode' => $equipment->control_mode,
                'is_using' => $equipment->is_using,
                'current_user' => $equipment->current_user()->name,
                'status' => $equipment->status,

                'connect' => $equipment->connect,
                'is_monitoring' => $equipment->is_monitoring,
                'control_address' => $equipment->control_address,
            ];
        }
        return [
            'total' => Q("{$door}<asso equipment")->total_count(),
            'items' => $data
        ];
    }
}
