<?php

class Room_Resource_Model extends Presentable_Model
{
    protected $object_page = [
    ];
    public function format($type)
    {
        switch ($type) {
            case 'gateway':
            default:
                return [
                    'room_id' => $this->room->gateway_id,
                    'type' => $this->resource->name(),
                    'object_id' => $this->resource->id,
                    'attrs' => [
                        'name' => $this->name,
                        'coordinate_x' => $this->coordinate_x,
                        'coordinate_y' => $this->coordinate_y,
                        'coordinate_z' => $this->coordinate_z,
                    ]
                ];
        }
    }
}
