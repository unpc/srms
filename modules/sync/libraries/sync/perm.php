<?php
class Sync_Perm extends Sync_Handler
{
    public static $publish_keys = [
        'name',
        'weight',
    ];

    public function uuid() {
        return $this->object->name;
    }

    public function should_save_uuid($old_data, $new_data)
    {
        return true;
    }

    public function should_save_publish($old_data, $new_data) {
        return true;
    }

    public function format() {
        return;
    }

    public function handle($params) {
        return;
    }
}
