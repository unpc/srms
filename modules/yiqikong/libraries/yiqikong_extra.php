<?php

use \Pheanstalk\Pheanstalk;

class Yiqikong_Extra {
    
    static function format ($extra) {
        $name = $extra->object->name();
        $type = $extra->type;
        $config = Config::get("extra.{$name}.{$type}");
        $extra = json_decode($extra->params_json, TRUE);

        foreach ($extra as $key => $fields) {
            foreach ($fields as $name => $field) {
                if (!$field['type']) {
                    $extra[$key][$name]['type'] = $config[$key][$name]['type'];
                    $extra[$key][$name]['params'] = $config[$key][$name]['params'];
                    $extra[$key][$name]['default_value'] = $config[$key][$name]['default_value'];
                }
            }
        }

        return $extra;
    }

    static function on_extra_value_saved($e, $extra_value, $old_data, $new_data) {
        $object = $extra_value->object;
        if (!$object->id) return TRUE;

        $oname = $object->name();
        $extra_fields = $extra_value->values;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

        switch ($oname) {
            case 'eq_sample':
                $sample = $extra_value->object;
                if (Config::get('lab.modules')['app'] && !L('YiQiKongSampleAction')) {
                    $path = "sample/0";
                    $method = 'patch';
                    $state = Common_Base::STATE_UPDATE;
                    $lab = Q("$sample->sender lab")->current();
                    $not_send_message = 1;
                    $payload = [
                        'method' => $method,
                        'path' => $path,
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
                            'group_name' => $sample->sender->group->name,
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
                            'mtime' => $sample->mtime,
                            'ctime' => $sample->ctime,
                            'state' => $state,
                            'not_send_message' => $not_send_message,
                        ]
                    ];
                    $mq
                        ->useTube('stark')
                        ->put(json_encode($payload, TRUE));

                    Cache::L('YiQiKongSampleAction', NULL);
                    return true;
                }
                else if ($sample->sender->gapper_id) {
                    //这儿应该要删。先删了看看有没有异常
//                    $msg = [
//                        'method'=> 'YiQiKong/Sample/Update',
//                        'params'=> [
//                            'equipment' => $sample->equipment->yiqikong_id,
//                            'lims_id' => $sample->id,
//                            'user' => $sample->sender->gapper_id,
//                            'extra' => $extra_value->values
//                        ],
//                    ];
//                    Debade_Queue::of('YiQiKong')->push($msg, 'sample');
                }
                break;
            case 'eq_reserv':
                $reserv = $extra_value->object;
                if (Config::get('lab.modules')['app'] && !L('YiQiKongReservAction')) {
                    $lab = Q("$reserv->user lab")->current();
                    $state = Common_Base::STATE_UPDATE;
                    $path = "reserve/0";
                    $method = 'put';
                    $payload = [
                        'method' => $method,
                        'path' => $path,
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
                            'state' => $state,
                            'extra_fields' => $extra_fields,
                            'not_send_message' => 1,
                        ]
                    ];

                    $mq
                        ->useTube('stark')
                        ->put(json_encode($payload, TRUE));

                    Cache::L('YiQiKongReservAction', NULL);

                    return true;

                    Cache::L('YiQiKongReservId', NULL);

                    return true;
                }
                else if ($reserv->sender->gapper_id) {
//                    $reserv = $extra_value->object;
//                    $msg = [
//                        'method'=> 'YiQiKong/Reserve/Update',
//                        'params'=> [
//                            'equipment' => $reserv->equipment->yiqikong_id,
//                            'lims_id' => $reserv->component->id,
//                            'user' => $reserv->user->gapper_id,
//                            'extra' => $extra_value->values
//                        ],
//                    ];
//                    Debade_Queue::of('YiQiKong')->push($msg, 'reserve');
                }
                break;
            case 'eq_record':
            default:
                return TRUE;
        }
    }

}