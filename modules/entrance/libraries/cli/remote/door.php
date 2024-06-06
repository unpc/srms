<?php

class CLI_Remote_Door
{

    static function get_door_records()
    {
        Cache::L("ME", Q('user')->current());
        if (!Q("door[type!=" . Door_Model::type('genee') . "]")->total_count()) return true;
        $iot_door = new Iot_door();
        $last_record_id = Q('dc_record:sort(voucher D)')->current()->voucher ?: 0;
        while (true) {
            $params = [
                'record_id' => $last_record_id,
                'st' => 0,
                'pp' => 10
            ];
            $result = $iot_door::doorRecords($params);
            if (!$result['total']) break;
            foreach ($result['items'] as $item) {
                if ($item['user_id']) $user = o('user', ['gapper_id' => $item['user_id']]);
                if ($item['user_id'] && !$user->id) $user = o('user', ['id' => $item['user_id']]);
                if ($item['door_id']) $door = o('door',  ['voucher' => $item['door_id']]);
                if ($door->id && $user->id) {
                    $record = O('dc_record', ['voucher' => $item['id']]);
                    $record->voucher = $item['id'];
                    $record->time = strtotime($item['ctime']);
                    $record->user = $user;
                    $record->door = $door;
                    $record->direction = 1;
                    $record->status = DC_Record_Model::STATUS_SUCCESS;
                    $record->save();
                }
                $last_record_id = $item['id'];
            }
        }
        return TRUE;
    }

    static function post_users_remote()
    {
        if (!Q("door[type!=" . Door_Model::type('genee') . "]")->total_count()) return true;
        $iot_door = new Iot_door();
        $start = 0;
        $step = 50;
        while (true) {
            $users = Q('user[card_no]')->limit($start, $step);
            if (!count($users)) break;
            $params = [
                'users' => $users->to_assoc('id', 'id')
            ];
            $iot_door::userRemote($params);
            $start = $start + $step;
        }
        return TRUE;
    }

    static function downgrade_from_uno()
    {

        $iot_door = new Iot_door();
        $start = 0;
        $n = 10;

        while (true) {
            $remote_devices = $iot_door::getDevicesList([
                'st' => $start,
                'pp' => $n,
                'keywords' => '',
                'type' => 'hikvision'
            ]);
            if (!count($remote_devices['items'])) {
                break;
            }
            foreach ($remote_devices['items'] as $remote) {
                $device = o('door_device', ['uuid' => $remote['id']]);
                $device->uuid = $remote['id'];
                $device->name = $remote['name'];
                $device->save();
            }
            $start += $n;
        }

        $start = 0;
        $n = 10;
        while (true) {
            $remote_doors = $iot_door::getDoors([
                'st' => $start,
                'pp' => $n,
            ]);
            if (!count($remote_doors['items'])) {
                break;
            }
            foreach ($remote_doors['items'] as $remote) {
                $door = O('door', ['voucher' => $remote['id']]);
                $door->name = $remote['name'];
                $door->is_single_direction = true;
                $door->type                = 2;
                $door->remote_device       = o('door_device', ['uuid' => $remote['device']['id']]);
                $door->voucher = $remote['id'];
                $door->save();

                $params = [
                    'door_id' => $door->voucher
                ];
                foreach (Q("$door<incharge user") as $incharge) {
                    $params[$incharge->id] = $incharge->name;
                }
                $result = $iot_door::getDoorOwner($params);
                foreach ($result['items'] as $r) {
                    $user = O('user', ['gapper_id' => $r['user_id']]);
                    if ($user->id) {
                        $door->connect($user, 'incharge');
                    }
                }
            }
            $start += $n;
        }

        $db = Database::factory();
        $sql = sprintf("select * from iot_gdoor");

        $result = $db->query($sql);
        $assoc = $result ? $result->rows() : [];

        foreach ($assoc as $iot_door) {
            if (!$iot_door->gdoor_id) {
                echo "continue1\n";
                continue;
            }
            $door = O('door', ['voucher' => $iot_door->gdoor_id]);
            if (!$door->id) {
                echo "continue2\n";
                continue;
            }

            $result = $db->query("select * from `_r_iot_gdoor_equipment` where id1 = {$iot_door->id}");
            $equipments = $result ? $result->rows() : [];
            foreach ($equipments as $eq) {
                $equipment = O('equipment', $eq->id2);
                echo "{$equipment->name}\t{$door->name}\n";
                $equipment->connect($door, 'asso');
            }
        }
    }
}
