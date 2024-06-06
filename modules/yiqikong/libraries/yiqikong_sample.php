<?php

use \Pheanstalk\Pheanstalk;

class Yiqikong_Sample
{

    //@TODO::这块需要改一下
    static function sample_tab_content_validate($e, $equipment)
    {
        $me = L('ME');
        if ($me->gapper_id && $me->outside && $equipment->yiqikong_id
            && !$me->is_allowed_to('添加送样记录', $equipment) && !$me->is_allowed_to('查看所有送样记录', $equipment)
            && Config::get('system.yiqikong_spread')) {
            error_log('下沉可能需要变动位置:yiqikong_sample::sample_tab_content_validate');
            $rpc_conf = Config::get('rpc.servers')['yiqikong'];
            $url = $rpc_conf['url'];
            $rpc = new RPC($url);
            if (!$rpc->YiQiKong->authorize($rpc_conf['client_id'], $rpc_conf['client_secret'])) {
                return TRUE;
            }

            $data = [];
            $data['site'] = SITE_ID;
            $data['lab'] = LAB_ID;
            $data['title'] = Config::get('page.title_default');
            $data['color'] = Config::get('page.title_color');
            $data['gapper'] = $me->gapper_id;
            $data['equipment'] = $equipment->yiqikong_id;
            $data['action'] = 'sample';
            $data['redirect'] = Config::get('yiqikong_user.redirect', $_SERVER['HTTP_HOST'] . '/lims');
            $data['redirect'] .= "/!people/profile/index.{$me->id}.eq_sample";

            $uuid = $rpc->YiQiKong->User->access($data);
            $url = Config::get('system.yiqikong_link') . "/equipment/authorize/{$uuid}";
            URI::redirect($url);
            return FALSE;
        }
    }

    static function on_eq_sample_saved($e, $sample, $old_data, $new_data)
    {
        /**
         * 确定为新架构下用户
         * 配合17kong-server 当Cache::L('YiQiKongSampleAction') 为FALSE时候才进行远程更新
         */
        if (Config::get('lab.modules')['app'] && isset($new_data['status'])) {
            Cache::L('YiQiKongSampleAction', FALSE);
        }
        if (Config::get('lab.modules')['app'] && !L('YiQiKongSampleAction')) {

            $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
            $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

            if (!$new_data['yiqikong_id'] && !$old_data['yiqikong_id'] ) { // 更新操作
                $path = 'sample';
                $method = 'post';
                $state = Common_Base::STATE_SUCCESS;
            } else { // 新增操作
                $path = "sample/0";
                $method = 'patch';
                $state = Common_Base::STATE_UPDATE;
            }


            $lab = Q("$sample->sender lab")->current();

            //判断是否是审批通过。。。。产品设计CF通过app不发通过消息，只有app通过才发审批消息但不能发更新消息。
            if (!$old_data->app_approval && $new_data->app_approval){
                $not_send_message = 1;
            }
            //测试项目和材料
            if($sample->materials){
                foreach (json_decode($sample->materials) as $id => $val) {
                    $materials_extra['m'.$id] = $val;
                }
                $sample->extra_fields = array_merge($sample->extra_fields, $materials_extra);
            }
            if($sample->test_projects){
                foreach (json_decode($sample->test_projects) as $id => $val) {
                    $test_project_extra['t'.$id] = $val;
                }
                $sample->extra_fields = array_merge($sample->extra_fields, $test_project_extra);
            }

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
                    'extra_fields' => $sample->extra_fields,
                    'not_send_message' => $not_send_message,
                    'record_source_id' => implode(',', Q("$sample eq_record")->to_assoc('id', 'id')),
                ]
            ];
            $mq
                ->useTube('stark')
                ->put(json_encode($payload, TRUE));

            Cache::L('YiQiKongSampleAction', NULL);
            return true;
        } elseif ($sample->sender->gapper_id) {
            error_log('下沉可能需要变动位置:yiqikong_sample::on_eq_sample_saved');
            $me = L('ME');
            $user_token = $sample->sender->token;

            //计算送样的费用并返回给yiqikong
            $charge_obj = O('eq_charge');
            $charge_obj->source = $sample;
            $lua = new EQ_Charge_LUA($charge_obj);
            $result = $lua->run(['fee']);
            $fee = $result['fee'];

            $charge = O('eq_charge', ['source' => $sample]);

            if (!$new_data['id']) {
                if (!L('YiQiKongSampleAction')) {
                    $_params = [];
                    $_params[lims_id] = $sample->id;


                    //debade
                    $check_keys = [
                        //暂时不对 user sample 进行处理
                        'samples' => 'count',
                        'success_samples' => 'success_samples',
                        'start_time' => 'dtstart',
                        'end_time' => 'dtend',
                        'submit_time' => 'dtsubmit',
                        'pickup_time' => 'dtpickup',
                        'status' => 'status',
                        'user' => 'sender',
                        'operator' => 'operator',
                        'charge' => 'charge',
                        'note' => 'note',
                        'description' => 'description',
                        'mtime' => 'mtime'
                    ];

                    foreach ($check_keys as $k => $v) {
                        switch ($v) {
                            case 'sender' :
                                $_params[$k] = $sample->sender->gapper_id;
                                break;
                            case 'operator' :
                                $_params[$k] = $sample->operator->gapper_id;
                                break;
                            case 'mtime' :
                                $_params[$k] = time();
                                break;
                            case 'charge' :
                                $_params[$k] = $charge->amount;
                                break;
                            default:
                                $_params[$k] = $sample->$v;
                        }
                    }

                    //将对应送样的收费返回给yiqikong
                    $_params[fee] = $fee;
                    $_params[equipment] = $sample->equipment->yiqikong_id;
                    $_params[yiqikong] = $sample->lab->id == YiQiKong_Lab::default_lab()->id;
                    $_params['source_site'] = SITE_ID;
                    $_params['source_lab'] = LAB_ID;

                    $extra = O('extra_value', ['object' => $sample]);
                    $_params[extra] = $extra->id ? $extra->values : '';

                    $msg = [
                        'method' => 'YiQiKong/Sample/Update',
                        'params' => $_params,
                    ];

                    Debade_Queue::of('YiQiKong')->push($msg, 'sample');
                } else {
                    Cache::L('YiQiKongSampleAction', NULL);
                }
            } else {
                /* 配合17kong-server 当Cache::L('YiQiKongSampleAction')为FALSE时候才进行远程更新 */
                if (!L('YiQiKongSampleAction')) {
                    $extra = O('extra_value', ['object' => $sample]);
                    $msg = [
                        'method' => 'YiQiKong/Sample/Add',
                        'params' => [
                            'user' => $sample->sender->gapper_id,
                            'operator' => $sample->operator->gapper_id,
                            'equipment' => $sample->equipment->yiqikong_id,
                            'samples' => $sample->count,
                            'success_samples' => $sample->success_samples,
                            'start_time' => $sample->dtstart,
                            'end_time' => $sample->dtend,
                            'pickup_time' => $sample->dtpickup,
                            'submit_time' => $sample->dtsubmit,
                            'status' => $sample->status,
                            'charge' => $charge->amount,
                            'note' => $sample->note,
                            'description' => $sample->description,
                            'lims_id' => $sample->id,
                            'source_site' => SITE_ID,
                            'source_lab' => LAB_ID,
                            'fee' => $fee,
                            'extra' => $extra->id ? $extra->values : '',
                            'yiqikong' => $sample->lab->id == YiQiKong_Lab::default_lab()->id,
                        ],
                    ];
                    Debade_Queue::of('YiQiKong')->push($msg, 'sample');
                } else {
                    Cache::L('YiQiKongSampleAction', NULL);
                }
            }
        }
    }

    static function on_eq_sample_deleted($e, $sample)
    {
        /**
         * 确定为新架构下用户
         * 配合17kong-server 当Cache::L('YiQiKongSampleAction') 为FALSE时候才进行远程更新
         */
        if (Config::get('lab.modules')['app'] && !L('YiQiKongSampleAction')) {

            $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
            $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

            $payload = [
                'method' => 'delete',
                'path' => 'sample/0',
                'rpc_token' => $gatewayConfig['mq']['x-beanstalk-token'],
                'header' => [
                    'x-yiqikong-notify' => TRUE,
                ],
                'body' => [
                    'source_name' => LAB_ID,
                    'source_id' => $sample->id,
                ]
            ];

            $mq
                ->useTube('stark')
                ->put(json_encode($payload, TRUE));

            return true;
        } elseif ($sample->sender->gapper_id) {
            error_log('下沉可能需要变动位置:yiqikong_sample::on_eq_sample_delete');
//            $me = L('ME');
//            if (!L('YiQiKongSampleAction')) {
//                $msg = [
//                    'method' => 'YiQiKong/Sample/Delete',
//                    'params' => [
//                        'lims_id' => $sample->id,
//                        'mtime' => Date::time(),
//                        'operator' => $me->name,
//                        'equipment' => $sample->equipment->yiqikong_id,
//                        'user' => $sample->sender->gapper_id,
//                        'yiqikong' => $sample->lab->id == YiQiKong_Lab::default_lab()->id,
//                        'source_site' => SITE_ID,
//                        'source_lab' => LAB_ID,
//                    ],
//                ];
//                Debade_Queue::of('YiQiKong')->push($msg, 'sample');
//            } else {
//                Cache::L('YiQiKongSampleAction', null);
//            }
        }
    }

    static function links($e, $user, $object, $params, $links)
    {
        if ($user->is_allowed_to('修改', $object)) {
            $links[] = [
                'title' => I18N::T('equipments', '编辑'),
                'icon' => '',
                'color' => 'colorBlue',
                'action' => 'edit',
                'params' => ''
            ];
        }
        if ($user->is_allowed_to('删除', $object)) {
            $links[] = [
                'title' => I18N::T('equipments', '删除'),
                'icon' => '',
                'color' => 'colorBlue',
                'action' => 'delete',
                'params' => ''
            ];
        }
    }


    static function on_eq_sample_eq_record_connect($e, $eq_sample, $eq_record, $type){
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = [
            'sample' => $eq_sample->id,
            'record' => $eq_record->id,
            'source_name' => LAB_ID,
        ];

        $payload = [
            'method' => "post",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  "sample/record",
            'body' => $data
        ];

        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));

        return TRUE;
    }

    static function on_eq_sample_eq_record_disconnect($e, $eq_sample, $eq_record, $type){
        if (!Config::get('lab.modules')['app']) return TRUE;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        $data = [
            'sample' => $eq_sample->id,
            'record' => $eq_record->id,
            'source_name' => LAB_ID,
        ];

        $payload = [
            'method' => "delete",
            'header' => ['x-yiqikong-notify' => TRUE],
            'path' =>  "sample/record",
            'body' => $data
        ];

        $mq
            ->useTube('stark')
            ->put(json_encode($payload, TRUE));

        return TRUE;
    }

}

