<?php
class Sync_Eq_Reserv extends Sync_Handler
{
    public static $publish_keys = [
        'equipment',
        'user',
        'component',
        'status',
        'dtstart',
        'dtend',
        'ctime',
        'mtime',
        'project',
        'approval',
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
        $eq_reserv = $this->object;
        $params    = [
            'equipment_id' => $eq_reserv->equipment->uuid,
            'user_id'      => $eq_reserv->user->uuid,
            'component_id' => $eq_reserv->component->uuid,
            'status'       => $eq_reserv->status,
            'dtstart'      => $eq_reserv->dtstart,
            'dtend'        => $eq_reserv->dtend,
            'ctime'        => $eq_reserv->ctime,
            'mtime'        => $eq_reserv->mtime,
            'project_id'   => $eq_reserv->project->uuid,
            'approval'     => $eq_reserv->approval,
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
        $eq_reserv            = $this->object;
        $eq_reserv->component = O('cal_component', ['uuid' => $params['component_id']]);
        $eq_reserv->user      = O('user', ['uuid' => $params['user_id']]);
        $eq_reserv->equipment = O('equipment', ['uuid' => $params['equipment_id']]);

        // 去除脏数据
        if (!$eq_reserv->user->id || !$eq_reserv->equipment->id) {
            return;
        }

        /**
         * cal_component_saved 会自动生成eq_reserv，只需要将来源同步信息补全就行
         */
        if (!$eq_reserv->id && $eq_reserv->component->id) {
            $reserv           = O('eq_reserv', ['component' => $eq_reserv->component]);
            $reserv->version  = $eq_reserv->version;
            $reserv->uuid     = $eq_reserv->uuid;
            $reserv->platform = $eq_reserv->platform;
            $reserv->save();
        }
        
    }
}
