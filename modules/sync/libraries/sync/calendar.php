<?php
class Sync_Calendar extends Sync_Handler
{
    public static $publish_keys = [
        'name',
        'description',
        'parent_name',
        'parent_id',
        'ctime',
        'mtime',
        'type',
    ];

    public function uuid()
    {
        return uniqid(LAB_ID, true);
    }

    public function should_save_uuid($old_data, $new_data)
    {
        return true;
    }

    public function should_save_publish($old_data, $new_data)
    {
        foreach (self::$publish_keys as $key) {
            if (isset($new_data[$key])) {
                if (is_object($new_data[$key]) && $new_data[$key]->id != $old_data[$key]->id) {
                    return true;
                } elseif (is_scalar($new_data[$key]) && $new_data[$key] != $old_data[$key]) {
                    return true;
                }
            }
        }
        return false;
    }

    public function format()
    {
        $calendar = $this->object;
        $params   = [
            'name'        => $calendar->name,
            'description' => $calendar->description,
            'parent_name' => $calendar->parent->name(),
            'parent_id'   => $calendar->parent->uuid,
            'ctime'       => $calendar->ctime,
            'mtime'       => $calendar->mtime,
            'type'        => $calendar->type,
        ];

        foreach ($params as $k => $v) {
            if (is_null($v)) {
                $params[$k] = "";
            }
        }
        return $params;
    }

    public function handle($params)
    {
        $calendar = $this->object;

        $calendar->name        = $params['name'];
        $calendar->description = $params['description'];
        $calendar->parent      = O($params['parent_name'], ['uuid' => $params['parent_id']]);
        $calendar->ctime       = $params['ctime'];
        $calendar->mtime       = $params['mtime'];
        $calendar->type        = $params['type'];
        $calendar->save();
    }
}
