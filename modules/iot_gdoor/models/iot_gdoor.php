<?php

class Iot_Gdoor_Model extends Presentable_Model
{
    public function doorName()
    {
        $data = Remote_Door::getDoorInfo($this->gdoor_id);
        if ($data['name']) {
            return $data['name'];
        }
        return "--";
    }
}
