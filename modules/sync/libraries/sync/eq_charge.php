<?php
class Sync_Eq_Charge extends Sync_Handler
{
    public static $publish_keys = [
        'user',
        'lab',
        'equipment',
        'ctime',
        'mtime',
        'auto_amount',
        'amount',
        'custom',
        'transaction',
        'is_locked',
        'source_name',
        'source_id',
        'description',
        'status',
        'dtstart',
        'dtend',
        'charge_template',
        'charge_type',
        'serialcode',
        'confirm',
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
        $eq_charge = $this->object;
        $params    = [
            'user_id'         => $eq_charge->user->uuid,
            'lab_id'          => $eq_charge->lab->uuid,
            'equipment_id'    => $eq_charge->equipment->uuid,
            'ctime'           => $eq_charge->ctime,
            'mtime'           => $eq_charge->mtime,
            'auto_amount'     => $eq_charge->auto_amount,
            'amount'          => $eq_charge->amount,
            'custom'          => $eq_charge->custom,
            'transaction_id'  => 0,
            'is_locked'       => $eq_charge->is_locked,
            'source_name'     => $eq_charge->source_name,
            'source_id'       => $eq_charge->source->uuid,
            'description'     => $eq_charge->description,
            'status'          => $eq_charge->status,
            'dtstart'         => $eq_charge->dtstart,
            'dtend'           => $eq_charge->dtend,
            'charge_template' => $eq_charge->charge_template,
            'charge_type'     => $eq_charge->charge_type,
            'serialcode'      => $eq_charge->serialcode,
            'confirm'         => $eq_charge->confirm,
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
        $eq_charge         = $this->object;
        $eq_charge->source = O($params['source_name'], ['uuid' => $params['source_id']]);

        /**
         * source->save() 会自动生成eq_charge，只需要将来源同步信息补全就行
         */
        if (!$eq_charge->id && $eq_charge->source->id) {
            $charge           = O('eq_charge', ['source' => $eq_charge->source]);
            $charge->version  = $eq_charge->version;
            $charge->uuid     = $eq_charge->uuid;
            $charge->platform = $eq_charge->platform;
            $charge->save();
        }

    }
}
