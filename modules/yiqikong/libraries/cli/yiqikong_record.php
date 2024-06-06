<?php

use \Pheanstalk\Pheanstalk;

class CLI_YiQiKong_Record
{
    // App统计 服务次数功能\服务时间功能\使用人数功能 旧数据刷新
    public static function sync_record()
    {
        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);


        // 数据刷新开始时间：2018年1月1日
        foreach (Q("eq_record") as $record) {
            //if (!$record->user->yiqikong_id) continue;

            $lab = Q("$record->user lab")->current();

            $payload = [
                'method' => 'post',
                'path' => 'record',
                'rpc_token' => $gatewayConfig['mq']['x-beanstalk-token'],
                'header' => [
                    'x-yiqikong-notify' => TRUE,
                ],
                'body' => [
                    'source_id' => $record->id,
                    'user' => $record->user->yiqikong_id,
                    'user_name' => $record->user->name,
                    'lab_name' => $lab->name ?? '',
                    'lab_id' => $lab->id ?? 0,
                    'is_locked' => $record->is_locked ?? 0,
                    'user_local' => $record->user->id,
                    'equipment' => $record->equipment->yiqikong_id,
                    'equipment_name' => $record->equipment->name,
                    'start_time' => $record->dtstart,
                    'end_time' => $record->dtend,
                    'feedback' => $record->feedback,
                    'charge' => Q("eq_charge[source={$record}]")->current()->amount ?? 0,
                    'samples' => $record->samples,
                    'status' => $record->status,
                    'reserve_id' => $record->reserv_id,
                    'remarks' => $record->use_type_desc, // 备注
                    'yiqikong_id' => $record->user->yiqikong_id ?? 0,//这里被占用了，保持之前的逻辑吧。
                    'extra_fields' => $record->extra_fields,
                    'source_name' => LAB_ID,
                    'preheat' => $record->preheat ?? 0,
                    'cooling' => $record->cooling ?? 0,
                    'use_type' => $record->use_type ?? 0,
                ]
            ];

            $mq
                ->useTube('stark')
                ->put(json_encode($payload, TRUE));

        }
        Upgrader::echo_success("Done.");
    }
}
