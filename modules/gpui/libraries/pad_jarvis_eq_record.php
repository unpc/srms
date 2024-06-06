<?php

class Pad_Jarvis_Eq_Record
{
    public static function on_record_saved($e, $object, $old_data, $new_data)
    {
        $equipment = $object->equipment;
        $user = $object->user;
        if (!$equipment->id
            || !$equipment->server
            || $equipment->control_mode != 'bluetooth'
            || !L('YiQiKongRecordAction')
        ) {
            return;
        }

        $client = new \GuzzleHttp\Client([
            'base_uri' => $equipment->server,
            'http_errors' => false,
            'timeout' => Config::get('device.computer.timeout', 5)
        ]);

        $config = Config::get('rpc.client.jarvis');
        if ($new_data['dtstart'] && $old_data['dtstart'] == 0) {
            $power_on = true;
        } elseif ($new_data['dtend'] && $old_data['dtend'] == 0) {
            $power_on = false;
        } else {
            Log::add(strtr('[jarvis] 检测到%equipment_name[%equipment_id]仪器使用记录[%record_id], 但是不知道如何通知平板', [
                '%equipment_name'=> $equipment->name,
                '%equipment_id'=> $equipment->id,
                '%record_id'=> $object->id
            ]), 'devices');
            return;
        }
        try {
            $success = (boolean) $client->post('switch_to', [
                'headers' => [
                    'HTTP-CLIENTID' => $config['client_id'],
                    'HTTP-CLIENTSECRET' => $config['client_secret'],
                ],
                'form_params' => [
                    'uuid' => $equipment->watcher_code,
                    'user' => [
                        'equipmentId' => $equipment->id,
                        'equipmentCode' => $equipment->code,
                        'username' => $user->token,
                        'cardno' => $user->card_no,
                        'name' => $user->name,
                        'id' => $user->id
                    ],
                    'power_on' => (int)$power_on
                ]
            ])->getBody()->getContents();

        } catch (Exception $e) {
            Log::add(strtr('[jarvis] 检测到%equipment_name[%equipment_id]仪器使用记录[%record_id], power: %power, 但是通知平板超时', [
                '%equipment_name'=> $equipment->name,
                '%equipment_id'=> $equipment->id,
                '%record_id'=> $object->id,
                '%power'=> $power_on ? 'on' : 'off',
            ]), 'devices');
        }
        if ($success) {
            if ($equipment->control_mode != 'power') {
                $equipment->is_using = false;
            }
            $equipment->save();
            Log::add(strtr('[jarvis] 检测到%equipment_name[%equipment_id]仪器使用记录[%record_id], power: %power, 通知平板成功', [
                '%equipment_name'=> $equipment->name,
                '%equipment_id'=> $equipment->id,
                '%record_id'=> $object->id,
                '%power'=> $power_on ? 'on' : 'off',
            ]), 'devices');
        }
    }
}
