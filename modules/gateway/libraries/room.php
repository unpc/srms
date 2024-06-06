<?php

class Room
{
    public static function init()
    {
        $param = [
            'name' => '房间仪器资源',
            'key' => 'device',
            'attrs'=> [
                [
                    'name'=>'房间id',
                    'key'=>'room_id',
                    'visible'=>'1'
                ],
                [
                    'name'=>'房间名称',
                    'key'=>'room_name',
                    'visible'=>'1'
                ],
                [
                    'name'=>'资源id',
                    'key'=>'resource_id',
                    'visible'=>'1'
                ],
                [
                    'name'=>'资源名称',
                    'key'=>'resource_name',
                    'visible'=>'1'
                ],
                [
                    'name'=>'资源类型',
                    'key'=>'resource_type',
                    'visible'=>'1'
                ],
                [
                    'name'=>'资源位置x',
                    'key'=>'coordinate_x',
                    'visible'=>'1'
                ],
                [
                    'name'=>'资源位置y',
                    'key'=>'coordinate_y',
                    'visible'=>'1'
                ],
                [
                    'name'=>'资源位置z',
                    'key'=>'coordinate_z',
                    'visible'=>'1'
                ]
            ],
        ];
        $result = Gateway::getRoomresourcetype($param);
        if ($result['status'] == 'success') {
            echo "done.\n";
        }
    }

    public static function pour($start = 0, $step = 20)
    {
        while (Q("room_resource")->total_count() > $start) {
            $data = [];
            foreach (Q("room_resource:limit({$start}, {$step})") as $resource) {
                $data[] = $resource->format('gateway');
                try {
                    $result = Gateway::postRoomResources(['resources'=> $data]);
                } catch (Exception $e) {
                    self::pour($start);
                }
            }
            $start += $step;
        }
    }
}
