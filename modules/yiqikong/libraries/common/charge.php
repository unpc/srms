<?php

class Common_Charge extends Common_Base
{

    public static function update($data)
    {
        $charge = O('eq_charge', $data['source_id']);
        if ($charge->id) {
            isset($data['custom']) && $charge->custom = $data['custom'];
            isset($data['amount']) && $charge->amount = $data['amount'];
            $charge->save();
        }
    }

    public static function create($data)
    {
        Cache::L('YiQiKongChargeFirst', TRUE);
        $source = O($data['object_name'], $data['object_id']);
        if (!$source->id) return false;
        $source->user = $source->user;
        $source->save();

        $charge = O('eq_charge', ['source' => $source]);
        if (!$charge->id && $data['object_name'] == "eq_record") {
            // 触发一下计费修改
            $source->dtend = $source->dtend - 1;
            $source->save();
            $source->dtend = $source->dtend + 1;
            $source->save();
            $charge = O('eq_charge', ['source' => $source]);
        };
        if (!$charge->id) return false;

        isset($data['custom']) && $charge->custom = $data['custom'];
        isset($data['amount']) && $charge->amount = $data['amount'];
        $charge->save();
    }

}
