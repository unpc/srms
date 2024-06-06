<?php
class Sync_Publish
{
    public static function on_relationship_connect($e, $r1, $r2, $type = '')
    {
        if ($_SESSION['sync_relationship'] || !$r1->id || !$r2->id) {
            return;
        }

        $syncObj = new Sync_Relationship($r1, $r2);
        $syncObj->publish_save($type);
    }

    public static function on_relationship_disconnect($e, $r1, $r2, $type = '')
    {
        if ($_SESSION['sync_relationship'] || !$r1->id || !$r2->id) {
            return;
        }

        $syncObj = new Sync_Relationship($r1, $r2);
        $syncObj->publish_delete($type);
    }

    public static function save_uuid($e, $object, $new_data, $old_data)
    {
        $syncObj = new Sync($object);
        if (in_array("{$object->name()}.save", Config::get('sync.topics')) && $syncObj->should_save_uuid($old_data, $new_data)) {
            if (!$object->uuid) {
                $syncObj      = new Sync($object);
                $object->uuid = $syncObj->uuid();
            }
            if (!$new_data['platform'] || PHP_SAPI != 'cli') {
                $object->platform = LAB_ID;
            }
            if (!$new_data['version']) {
                $object->version = time();
            }
        }
    }

    public static function orm_model_saved($e, $object, $old_data, $new_data)
    {
        $syncObj = new Sync($object);
        if ($syncObj !== null && $syncObj->should_save_publish($old_data, $new_data)) {
            $syncObj->publish_save();
        }

    }
    
    public static function orm_model_deleted($e, $object)
    {
        $syncObj = new Sync($object);
        if ($syncObj !== null && $syncObj->should_delete_publish()) {
            $syncObj->publish_delete();
        }
        
    }

}
