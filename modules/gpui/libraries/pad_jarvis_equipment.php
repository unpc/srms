<?php

class Pad_Jarvis_Equipment {

    public static function get_uuid ($equipment) {
        if ($equipment->id) {
            switch ($equipment->control_mode) {
                case 'computer':
                    $uuid = $equipment->control_address;
                    break;
                case 'power':
                    $address = $equipment->control_address;
                    list($mode, $uuid) = explode('//', $address);
                    break;
                default:
                    $uuid = $equipment->yiqikong_id ?: $equipment->id;
                    break;
            }
        }

        return $uuid;
    }
}
