<?php
class Sync_Cal_Component extends Sync_Handler
{
    public static $publish_keys = [
        'calendar',
        'type',
        'subtype',
        'name',
        'description',
        'organizer',
        'dtstart',
        'dtend',
        'tzone',
        'ctime',
        'mtime',
        // 'cal_rrule',
        // 'me_room',
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
        $component = $this->object;
        $params    = [
            'calendar_id'  => $component->calendar->uuid,
            'type'         => $component->type,
            'subtype'      => $component->subtype,
            'name'         => $component->name,
            'description'  => $component->description,
            'organizer_id' => $component->organizer->uuid,
            'dtstart'      => $component->dtstart,
            'dtend'        => $component->dtend,
            'tzone'        => $component->tzone,
            'ctime'        => $component->ctime,
            'mtime'        => $component->mtime,
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
        $component = $this->object;

        $component->calendar    = O('calendar', ['uuid' => $params['calendar_id']]);
        $component->type        = $params['type'];
        $component->subtype     = $params['subtype'];
        $component->name        = $params['name'];
        $component->description = $params['description'];
        $component->organizer   = O('user', ['uuid' => $params['organizer_id']]);
        $component->dtstart     = $params['dtstart'];
        $component->dtend       = $params['dtend'];
        $component->tzone       = $params['tzone'];
        $component->ctime       = $params['ctime'];
        $component->mtime       = $params['mtime'];
        $component->save();
    }
}
