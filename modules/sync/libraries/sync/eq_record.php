<?php
class Sync_Eq_Record extends Sync_Handler
{
    public static $publish_keys = [
        'equipment',
        'user',
        'agent',
        'dtstart',
        'dtend',
        'status',
        'feedback',
        'mtime',
        'samples',
        'is_locked',
        'reserv',
        'project',
        'user_type',
        'user_type_desc',
        'charge_desc',
        'duty_teacher',
        'eq_abbr',
        'user_abbr',
        'agent_abbr',
        'flag',
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
        $eq_record = $this->object;
        $params    = [
            'equipment_id'    => $eq_record->equipment->uuid,
            'user_id'         => $eq_record->user->uuid,
            'agent_id'        => $eq_record->agent->uuid,
            'dtstart'         => $eq_record->dtstart,
            'dtend'           => $eq_record->dtend,
            'status'          => $eq_record->status,
            'feedback'        => $eq_record->feedback,
            'mtime'           => $eq_record->mtime,
            'samples'         => $eq_record->samples,
            'is_locked'       => $eq_record->is_locked,
            'reserv_id'       => $eq_record->reserv->uuid,
            'project_id'      => $eq_record->project->uuid,
            'user_type'       => $eq_record->user_type,
            'user_type_desc'  => $eq_record->user_type_desc,
            'charge_desc'     => $eq_record->charge_desc,
            'duty_teacher_id' => $eq_record->duty_teacher->uuid,
            'eq_abbr'         => $eq_record->eq_abbr,
            'user_abbr'       => $eq_record->user_abbr,
            'agent_abbr'      => $eq_record->agent_abbr,
            'flag'            => $eq_record->flag,
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
        $eq_record = $this->object;

        $eq_record->equipment      = O('equipment', ['uuid' => $params['equipment_id']]);
        $eq_record->user           = O('user', ['uuid' => $params['user_id']]);
        $eq_record->agent          = O('user', ['uuid' => $params['agent_id']]);
        $eq_record->dtstart        = $params['dtstart'];
        $eq_record->dtend          = $params['dtend'];
        $eq_record->status         = $params['status'];
        $eq_record->feedback       = $params['feedback'];
        $eq_record->mtime          = $params['mtime'];
        $eq_record->samples        = $params['samples'];
        $eq_record->is_locked      = $params['is_locked'];
        $eq_record->reserv         = O('reserv', ['uuid' => $params['reserv_id']]);
        $eq_record->project        = O('lab_project', ['uuid' => $params['project_id']]);
        $eq_record->user_type      = $params['user_type'];
        $eq_record->user_type_desc = $params['user_type_desc'];
        $eq_record->charge_desc    = $params['charge_desc'];
        $eq_record->duty_teacher   = O('user', ['uuid' => $params['duty_teacher_id']]);
        $eq_record->eq_abbr        = $params['eq_abbr'];
        $eq_record->user_abbr      = $params['user_abbr'];
        $eq_record->agent_abbr     = $params['agent_abbr'];
        $eq_record->flag           = $params['flag'];
        $eq_record->save();
    }
}
