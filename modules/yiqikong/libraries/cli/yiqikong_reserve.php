<?php

use \Pheanstalk\Pheanstalk;

class CLI_YiQiKong_Reserve
{
    // App统计 服务次数功能\服务时间功能\使用人数功能 旧数据刷新
    public static function sync_reserve()
    {
        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

        // 数据刷新开始时间：2018年1月1日
        foreach (Q("eq_reserv") as $reserv) {

            $lab = Q("$reserv->user lab")->current();

            $payload = [
                'method' => 'post',
                'path' => 'reserve',
                'rpc_token' => $gatewayConfig['mq']['x-beanstalk-token'],
                'header' => [
                    'x-yiqikong-notify' => TRUE,
                ],
                'body' => [
                    'title' => $reserv->component->name,
                    'user' => $reserv->user->yiqikong_id,
                    'user_local' => $reserv->user->id,
                    'user_name' => $reserv->user->name,
                    'lab_name' => $lab->name ?? '',
                    'lab_id' => $lab->id ?? 0,
                    'project_name' => $reserv->project->name,
                    'phone' => $reserv->user->phone,
                    'address' => $reserv->user->address,
                    'equipment' => $reserv->equipment->yiqikong_id,
                    'equipment_local' => $reserv->equipment->id,
                    'start_time' => $reserv->dtstart,
                    'end_time' => $reserv->dtend,
                    'ctime' => $reserv->ctime,
                    'mtime' => $reserv->mtime,
                    'project' => $reserv->project_id,
                    'description' => $reserv->component->description,
                    'status' => $reserv->status,
                    'source_name' => LAB_ID,
                    'source_id' => $reserv->id,
                    'component_id' => $reserv->component_id,
                    'token' => $reserv->component->token,
                    'approval' => $reserv->approval,
                ]
            ];

            $mq
                ->useTube('stark')
                ->put(json_encode($payload, TRUE));
        }
        Upgrader::echo_success("Done.");
    }
}