<?php

use \Pheanstalk\Pheanstalk;

class CLI_YiQiKong_Sample
{
    // App统计 服务次数功能\服务时间功能\使用人数功能 旧数据刷新
    public static function sync_sample()
    {
        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

        // 数据刷新开始时间：2018年1月1日
        foreach (Q("eq_sample") as $sample) {

            $lab = Q("$sample->sender lab")->current();

            $payload = [
                'method' => 'post',
                'path' => 'sample',
                'rpc_token' => $gatewayConfig['mq']['x-beanstalk-token'],
                'header' => [
                    'x-yiqikong-notify' => TRUE,
                ],
                'body' => [
                    'user' => $sample->sender->yiqikong_id,
                    'user_local' => $sample->sender->id,
                    'user_name' => $sample->sender->name,
                    'lab_name' => $lab->name ?? '',
                    'lab_id' => $lab->id ?? 0,
                    'equipment' => $sample->equipment->yiqikong_id,
                    'equipment_local' => $sample->equipment->id,
                    'operator' => $sample->operator->yiqikong_id,
                    'operator_local' => $sample->operator->id,
                    'start_time' => date('Y-m-d H:i:s', $sample->dtstart),
                    'end_time' => date('Y-m-d H:i:s', $sample->dtend),
                    'submit_time' => date('Y-m-d H:i:s', $sample->dtsubmit),
                    'pickup_time' => date('Y-m-d H:i:s', $sample->dtpickup),
                    'samples' => $sample->count,
                    'success_samples' => $sample->success_samples,
                    'description' => $sample->description,
                    'status' => $sample->status,
                    'source_name' => LAB_ID,
                    'source_id' => $sample->id,
                    'extra_fields' => $sample->extra_fields,
                    'mtime' => $sample->mtime,
                    'record_source_id' => implode(',', Q("$sample eq_record")->to_assoc('id', 'id')),
                    'ctime' => date('Y-m-d H:i:s', $sample->ctime),
                ]
            ];
            $mq
                ->useTube('stark')
                ->put(json_encode($payload, TRUE));
        }
        Upgrader::echo_success("Done.");
    }
}