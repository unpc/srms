<?php

class Remote_Door
{

    static function on_door_saved($e, $door, $old_data, $new_data)
    {
        if ($door->voucher) {
            $iot_door = new Iot_door();
            $params = [
                'door_id' => $door->voucher
            ];
            $handler = Config::get('entrance.remote_door_hanlder') ?: 'lims';
            foreach (Q("$door<incharge user") as $incharge) {
                $id = ($handler == 'gapper' || $incharge->gapper_id) ?: $incharge->id;
                $params[$id] = $incharge->name;
            }
            $result = $iot_door::doorOwner($params);
        }
        return TRUE;
    }

    static function on_door_before_save($e, $door, $new_data)
    {
        if (!isset($new_data['remote_device']) || !isset($new_data['remote_device2'])) return;
        // 增加/修改门禁 基本信息
        $remote_device = $new_data['remote_device'];
        $remote_device2 = $new_data['remote_device2'];
        $result = [];
        $params = [];
        $iot_door = new Iot_door();
        if (!$remote_device->id) {
            if ($door->voucher && !isset($new_data['rules'])) {
                // 之前是iot_gdoor门禁，现在不是了, 删除门禁
                $params = [
                    'door_id' => $door->voucher
                ];
                $result = $iot_door::deleteDoor($params);
                $door->voucher = 0;
            }
        } else {
            if ($door->voucher) {
                // 之前是iot_gdoor门禁，现在是, 但是关联的门禁点变了，修改门禁
                $params = [
                    'name' => $door->name,
                    'device' => [
                        'id' => $remote_device->uuid,
                        'type' => Door_Model::iot_door_driver()[$door->type]
                    ],
                    'door_id' => $door->voucher
                ];
                if ($remote_device2->id) {
                    $params['device2'] = [
                        'id' => $remote_device2->uuid,
                    ];
                }
                $result = $iot_door::putDoor($params);
                if ($result && $result['id']) {
                    $door->voucher = $result['id'];
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('entrance', '门禁关联门禁设备失败!'));
                }
            } else {
                // 之前不是iot_gdoor门禁，现在是, 增加门禁
                $params = [
                    'name' => $door->name,
                    'device' => [
                        'id' => $remote_device->uuid,
                        'type' => Door_Model::iot_door_driver()[$door->type]
                    ]
                ];
                $result = $iot_door::postDoor($params);
                if ($result && $result['id']) {
                    $door->voucher = $result['id'];
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('entrance', '门禁关联门禁设备失败!'));
                }
            }
        }
    }

    static function on_door_deleted($e, $door)
    {
        if ($door->voucher) {
            // 之前是iot_gdoor门禁，现在需要删除了, 删除门禁
            $iot_door = new Iot_door();
            $params = [
                'door_id' => $door->voucher
            ];
            $result = $iot_door::deleteDoor($params);
        }
        return TRUE;
    }

    static function open_by_remote($e, $door, $params)
    {
        if ($door->voucher) {
            $iot_door = new Iot_door();
            $params = [
                'door_id' => $door->voucher,
                'action' => 'open'
            ];
            $result = $iot_door::doorAction($params);
            if ($result)  {
                JS::alert(I18N::T('entrance', '进门成功!'));
                JS::refresh();
            } else {
                JS::alert(I18N::T('entrance', '进门失败!'));
            }
            $e->return_value = true;
        }
        return true;
    }

    static function sync_card($e, $user)
    {
        if (!Q("door[type!=" . Door_Model::type('genee') . "]")->total_count()) return true;
        $iot_door = new Iot_door();
        $params = [
            'users' => [$user->id => $user->id]
        ];
        $iot_door::userRemote($params);
        $e->return_value = true;
        return true;
    }
}
