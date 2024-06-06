<?php

use \Pheanstalk\Pheanstalk;

class CLI_YiQiKong_charge
{

    public static function sync_charge()
    {
        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

        foreach (Q("eq_charge") as $charge) {

            $payload = [
                'method' => 'post',
                'path' => 'charge',
                'rpc_token' => $gatewayConfig['mq']['x-beanstalk-token'],
                'header' => [
                    'x-yiqikong-notify' => TRUE,
                ],
                'body' => [
                    'equipment' => $charge->equipment->yiqikong_id,
                    'user' => $charge->user->yiqikong_id,
                    'user_local' => $charge->user->id,
                    'user_name' => $charge->user->name,
                    'lab_id' => $charge->lab->id,
                    'amount' => $charge->amount,
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
        Upgrader::echo_success("Done.");
    }
}
