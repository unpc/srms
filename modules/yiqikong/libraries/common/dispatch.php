<?php

use \Pheanstalk\Pheanstalk;

class Common_Dispatch extends Common_Base
{
//    const STATE_PENDING = 0; // 订单准备状态
//    const STATE_SUCCESS = 1; // 订单成功
//    const STATE_FAILED = 2; // 订单失败
//    const STATE_DELETED = 3; // 订单已经删除

//    const SEND_PENDING = 1; //未发送
//    const SEND_ENDING = 2;
//    const SEND_SUCCESS = 3; //已发送
//    const SEND_NOPE = 4; //不发送

//    const STATUS_APPROVE = 0;
//    const STATUS_DONE = 1;
//    const STATUS_REJECTED = 2;

    public static function dispatch($name, $method, $data, $header = [])
    {

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

        $method = strtolower($method);

        if (strpos($name, 'sample') !== false) {
            if ($method == 'post' || $method == 'put') {
                try {
                    $res = $method == 'put' ? Common_Sample::update($data['source_id'], $data) : Common_Sample::create($data);
                    if ($res['id']) {
                        $data['source_id'] = $res['id'];
                        $data['user_local'] = $res['user_local'];
                        $data['user_name'] = $res['user_name'];
                        $data['lab_name'] = $res['lab_name'];
                        $data['lab_id'] = $res['lab_id'];
                        $data['group_name'] = $res['group_name'];
                        $data['state'] = $method == 'post' ? parent::STATE_SUCCESS : parent::STATE_UPDATE;
                        $data['status'] = o('eq_sample',$res['id'])->status;
                    }
                } catch (\Exception $e) {
                    $data['state'] = parent::STATE_FAILED;
                    $data['description'] = $e->getMessage();
                } finally {
                    $payload = [
                        'method' => 'patch',
                        'path' => 'sample/' . $data['yiqikong_id'],
                        'rpc_token' => $gatewayConfig['mq']['x-beanstalk-token'],
                        'header' => [
                            'x-yiqikong-notify' => TRUE,
                        ],
                        'body' => $data
                    ];
                    $mq
                        ->useTube('stark')
                        ->put(json_encode($payload, TRUE));
                }
            } elseif ($method == 'delete') {
                try {
                    if (Common_Sample::delete($data['source_id'], $data)) {
                        $data['state'] = parent::STATE_DELETED;
                    }
                } catch (\Exception $e) {
                } finally {
                    $payload = [
                        'method' => 'patch',
                        'path' => 'sample/' . $data['yiqikong_id'],
                        'rpc_token' => $gatewayConfig['mq']['x-beanstalk-token'],
                        'header' => [
                            'x-yiqikong-notify' => TRUE,
                        ],
                        'body' => $data
                    ];
                    $mq
                        ->useTube('stark')
                        ->put(json_encode($payload, TRUE));
                }
            }
        }
        if (strpos($name, 'reserve') !== false) {

            if ($method == 'delete') {
                try {
                    Common_Reserve::delete(json_encode($data));
                    $data['state'] = parent::STATE_DELETED;
                } catch (\Exception $e) {
                    $data['description'] = $e->getMessage();
                    $data['state'] = parent::STATE_SUCCESS;
                    $data['is_send'] = parent::SEND_SUCCESS;
                    $data['approval'] = parent::STATUS_DONE;
                } finally {
                    $payload = [
                        'method' => 'patch',
                        'path' => 'reserve/' . $data['yiqikong_id'],
                        'rpc_token' => $gatewayConfig['mq']['x-beanstalk-token'],
                        'header' => [
                            'x-yiqikong-notify' => TRUE,
                        ],
                        'body' => $data
                    ];
                    $mq
                        ->useTube('stark')
                        ->put(json_encode($payload, TRUE));
                }
            }
        }

        if (strpos($name, 'charge') !== false) {
            if ($method == 'put') {
                try {
                    Common_Charge::update($data);
                } catch (\Exception $e) {
                } finally {
                }
            } elseif ($method == 'post') {
                try {
                    Common_Charge::create($data);
                } catch (\Exception $e) {
                } finally {
                }
            }
        }

        if (strpos($name, 'approval') !== false) {
            if ($method == 'put') {
                try {
                    Common_Approval::update($data);
                } catch (\Exception $e) {
                } finally {
                }
            }
        }

        if (strpos($name, 'record') !== false) {
            if ($method == 'post' || $method == 'put') {
                try {
                    $res = $method == 'put' ? Common_Record::update($data['source_id'], $data) : Common_Record::create($data);
                    if ($res['id']) {
                        $data['source_id'] = $res['id'];
                        $data['user_name'] = $res['user_name'];
                        $data['group_name'] = $res['group_name'];
                        $data['user_local'] = $res['user_local'];
                        $data['preheat'] = $res['preheat'];
                        $data['cooling'] = $res['cooling'];
                    }
                } catch (\Exception $e) {
                    $data['msg'] = $e->getMessage();
                } finally {
                    $payload = [
                        'method' => 'PUT',
                        'path' => 'record/' . $data['yiqikong_record_id'],
                        'rpc_token' => $gatewayConfig['mq']['x-beanstalk-token'],
                        'header' => [
                            'x-yiqikong-notify' => TRUE,
                        ],
                        'body' => $data
                    ];
                    $mq
                        ->useTube('stark')
                        ->put(json_encode($payload, TRUE));
                }
            }
        }

        if (strpos($name, 'feedback') !== false) {
            //保留之前逻辑，不回复结果
            if ($method == 'post' || $method == 'put') {
                try {
                    Common_Record::update($data['source_id'], $data);
                } catch (\Exception $e) {
                } finally {
                }
            }
        }

        if (strpos($name, 'comment') !== false) {
            //保留之前逻辑，不回复结果
            if ($method == 'post' || $method == 'put') {
                try {
                    Common_Comment::update($data);
                } catch (\Exception $e) {
                } finally {
                }
            }
        }

        if (strpos($name, 'announce/user') !== false) {
            //保留之前逻辑，不回复结果
            if ($method == 'post' || $method == 'put') {
                try {
                    Common_Announce::read($data);
                } catch (\Exception $e) {
                } finally {
                }
            }
        }

        if (strpos($name, 'training') !== false) {
            if ($method == 'post' || $method == 'put') {
                $fail = false;
                try {
                    $res = $method == 'put' ? Common_Equipment::updateTraining($data) : Common_Equipment::createTraining($data);
                    if ($method == 'post' && !$res['success']) {
                        $fail = true;
                        $data['yiqikong_id'] = $data['id'];
                        $data['status'] = -1;
                        $data['err_msg'] = $res['err_msg'];
                    }
                } catch (\Exception $e) {
                    if ($method == 'post') {
                        $fail = true;
                        $data['yiqikong_id'] = $data['id'];
                        $data['status'] = -1;
                        $data['err_msg'] = "申请失败";
                    }
                } finally {
                    // 只有创建失败的时候才需要回传app
                    if ($fail) {
                        $payload = [
                            'method' => 'PUT',
                            'header' => ['x-yiqikong-notify' => TRUE],
                            'path' => "equipment/training/".$data["id"],
                            'body' => $data
                        ];
                        $mq
                            ->useTube('stark')
                            ->put(json_encode($payload, TRUE));

                    }
                }
            }
        }

        if (strpos($name, 'follow') !== false) {
            if ($method == 'post') {
                try {
                    Common_Follow::create($data);
                } catch (\Exception $e) {
                } finally {
                }
            } elseif ($method == 'delete') {
                try {
                    Common_Follow::delete($data);
                } catch (\Exception $e) {
                } finally {
                }
            }
        }

    }

}