<?php
use \Pheanstalk\Pheanstalk;

class Yiqikong_Charge
{
    public static function on_eq_charge_saved($e, $charge, $old_data, $new_data)
    {
        $user = $charge->user;

        /* TODO L('YiQiKongReservAction') and L('YiQiKongSampleAction')判断暂时不需要增加, 后续可以考虑加上*/

        if ($user->gapper_id && !$new_data['id']) {
            $method = 'YiQiKong/Billing/Update';
            $yiqikong_lab_id= YiQiKong_Lab::default_lab()->id;

            $params = [
                'user'        => $user->gapper_id,
                'yiqikong'    => Q("$user lab[id={$yiqikong_lab_id}]")->total_count(),
                'equipment'   => $charge->equipment->yiqikong_id,
                'amount'      => $charge->amount,
                'custom'      => $charge->custom,
                'ctime'       => $charge->ctime,
                'mtime'       => $charge->mtime,
                'id'          => $charge->id,
                'source_lab'  => LAB_ID,
                'source_name' => $charge->source_name,
                'source_id'   => $charge->source_id,
                'description' => $charge->description,
            ];

            if ($params['source_name'] == 'eq_reserv') {
                $params['source_id'] = $charge->source->component->id;
            }

            $msg = [
                'method'=> $method,
                'params'=> $params,
            ];

            Debade_Queue::of('YiQiKong')->push($msg, 'billing');
        }
    }

    public static function on_eq_charge_deleted($e, $charge)
    {
        if ($charge->user->gapper_id) {
            $yiqikong_lab_id= YiQiKong_Lab::default_lab()->id;
            $msg = [
                'method' => 'YiQiKong/Billing/Delete',
                'params' => [
                    'equipment' => $charge->equipment->yiqikong_id,
                    'yiqikong' => Q("$user lab[id={$yiqikong_lab_id}]")->total_count(),
                    'source_name' => $charge->source_name,
                    'source_id' => $charge->source_id,
                    'source_lab' => LAB_ID,
                ],
            ];

            Debade_Queue::of('YiQiKong')->push($msg, 'billing');
        }
    }

    public static function on_eq_charge_saved_app($e, $charge, $old_data, $new_data)
    {
        if (!$charge->equipment->yiqikong_id || !Config::get('lab.modules')['app']) {
            return TRUE;
        }

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

        if ($new_data['id'] || L('YiQiKongChargeFirst')) { // 新增
            $path = "charge";
            $method = 'POST';
        } else { // 更新
            $path = "charge/0";
            $method = 'PUT';
        }

        $payload = [
            'method' => $method,
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' => $path,
            'body' => [
                'equipment' => $charge->equipment->yiqikong_id,
                'user' => $charge->user->yiqikong_id,
                'user_name' => $charge->user->name,
                'user_local' => $charge->user->id,
                'amount' => $charge->amount,
                'lab_id' => $charge->lab->id,
                'auto_amount' => $charge->auto_amount,
                'custom' => $charge->custom,
                'object_name' => $charge->source_name,
                'object_id' => $charge->source_id,
                'description' => $charge->description,
                'ctime' => date('Y-m-d H:i:s', $charge->ctime),
                'mtime' => date('Y-m-d H:i:s', $charge->mtime),
                'source_name' => LAB_ID,
                'source_id' => $charge->id,
                'transaction_id' => $charge->transaction->id,
            ]
        ];

        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));
    }

    public static function on_eq_charge_deleted_app($e, $charge)
    {
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

        $payload = [
            'method' => 'delete',
            'path' => 'charge/0',
            'rpc_token' => $gatewayConfig['mq']['x-beanstalk-token'],
            'header' => [
                'x-yiqikong-notify' => TRUE,
            ],
            'body' => [
                'source_name' => LAB_ID,
                'source_id' => $charge->id,
            ]
        ];

        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));

        return TRUE;
    }
}
