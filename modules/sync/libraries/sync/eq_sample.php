<?php
class Sync_Eq_Sample extends Sync_Handler
{
    public static $publish_keys = [
        'sender',
        'lab',
        'equipment',
        'operator',
        'status',
        'dtsubmit',
        'dtstart',
        'dtend',
        'dtpickup',
        'count',
        'record',
        'is_locked',
        'success_samples',
        'project',
        'ctime',
        'mtime',
        'sender_abbr',
        'equipment_abbr',
        'operator_abbr',
        'feedback',
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
        $eq_sample = $this->object;
        $params    = [
            'sender_id'       => $eq_sample->sender->uuid,
            'lab_id'          => $eq_sample->lab->uuid,
            'equipment_id'    => $eq_sample->equipment->uuid,
            'operator_id'     => $eq_sample->operator->uuid,
            'status'          => $eq_sample->status,
            'dtsubmit'        => $eq_sample->dtsubmit,
            'dtstart'         => $eq_sample->dtstart,
            'dtend'           => $eq_sample->dtend,
            'dtpickup'        => $eq_sample->dtpickup,
            'count'           => $eq_sample->count,
            'record_id'       => $eq_sample->record->uuid,
            'is_locked'       => $eq_sample->is_locked,
            'success_samples' => $eq_sample->success_samples,
            'project_id'      => $eq_sample->project->uuid,
            'ctime'           => $eq_sample->ctime,
            'mtime'           => $eq_sample->mtime,
            'sender_abbr'     => $eq_sample->sender_abbr,
            'equipment_abbr'  => $eq_sample->equipment_abbr,
            'operator_abbr'   => $eq_sample->operator_abbr,
            'feedback'        => $eq_sample->feedback,
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
        $eq_sample = $this->object;

        $eq_sample->sender          = O('user', ['uuid' => $params['sender_id']]);
        $eq_sample->lab             = O('lab', ['uuid' => $params['lab_id']]);
        $eq_sample->equipment       = O('equipment', ['uuid' => $params['equipment_id']]);
        $eq_sample->operator        = O('user', ['uuid' => $params['operator_id']]);
        $eq_sample->status          = $params['status'];
        $eq_sample->dtsubmit        = $params['dtsubmit'];
        $eq_sample->dtstart         = $params['dtstart'];
        $eq_sample->dtend           = $params['dtend'];
        $eq_sample->dtpickup        = $params['dtpickup'];
        $eq_sample->count           = $params['count'];
        $eq_sample->record          = O('eq_record', ['uuid' => $params['record_id']]);
        $eq_sample->is_locked       = $params['is_locked'];
        $eq_sample->success_samples = $params['success_samples'];
        $eq_sample->project         = O('lab_project', ['uuid' => $params['project_id']]);
        $eq_sample->ctime           = $params['ctime'];
        $eq_sample->mtime           = $params['mtime'];
        $eq_sample->sender_abbr     = $params['sender_abbr'];
        $eq_sample->equipment_abbr  = $params['equipment_abbr'];
        $eq_sample->operator_abbr   = $params['operator_abbr'];
        $eq_sample->feedback        = $params['feedback'];
        $eq_sample->save();
    }
}
