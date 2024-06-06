<?php

use \Pheanstalk\Pheanstalk;

class Yiqikong_Record
{

    static function on_eq_record_saved($e, $record, $old_data, $new_data)
    {

        /**
         * 确定为新架构下用户
         * 配合17kong-server 当Cache::L('YiQiKongSampleAction') 为FALSE时候才进行远程更新
         */

        if (Config::get('lab.modules')['app'] && !L('YiQiKongRecordAction')) {

            $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
            $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

            if ($new_data['id']) { // 新增
                $path = "record";
                $method = 'POST';
            } else { // 更新
                $path = "record/0";
                $method = 'PUT';
            }

            $lab = Q("$record->user lab")->current();

            $payload = [
                'method' => $method,
                'header' => ['x-yiqikong-notify' => TRUE],
                'path' => $path,
                'body' => [
                    'source_id' => $record->id,
                    'user' => $record->user->yiqikong_id,
                    'yiqikong_id' => $record->user->yiqikong_id ?? 0,
                    'user_name' => $record->user->name,
                    'user_local' => $record->user->id,
                    'lab_name' => $lab->name ?? '',
                    'lab_id' => $lab->id ?? 0,
                    'equipment' => $record->equipment->yiqikong_id,
                    'equipment_name' => $record->equipment->name,
                    'start_time' => $record->dtstart,
                    'end_time' => $record->dtend,
                    'feedback' => $record->feedback,
                    'status' => $record->status,
                    'samples' => $record->samples,
                    'reserve_id' => $record->reserv_id,
                    'agent_id' => $record->agent->id,
                    'agent_name' => $record->agent->name,
                    'description' => $record->description,
                    'is_missed' => $record->is_missed ?? 0,
                    'remarks' => $record->use_type_desc, // 备注
                    'is_locked' => $record->is_locked ?? 0,
                    'source_name' => LAB_ID,
                    'extra_fields' => $record->extra_fields,
                    'preheat' => $record->preheat ?? 0,
                    'cooling' => $record->cooling ?? 0,
                    'use_type' => $record->use_type ?? 0,
                ]
            ];

            $mq
                ->useTube('stark')
                ->put(json_encode($payload, TRUE));

            return TRUE;
        } elseif (!Config::get('lab.modules')['app'] && $record->user->gapper_id && Module::is_installed('eq_charge')) {
            $msg = self::format_record($record, $old_data, $new_data);
            Debade_Queue::of('YiQiKong')->push($msg, 'record');
        } else {
            return;
        }
    }

    static function on_eq_record_deleted($e, $record)
    {

        if (Config::get('lab.modules')['app']) {

            $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
            $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

            $path = "record/0";
            $method = 'delete';

            $payload = [
                'method' => $method,
                'header' => ['x-yiqikong-notify' => TRUE],
                'path' => $path,
                'body' => [
                    'source_id' => $record->id,
                    'user' => $record->user->yiqikong_id,
                    'yiqikong_id' => $record->user->yiqikong_id ?? 0,
                    'user_name' => $record->user->name,
                    'user_local' => $record->user->id,
                    'equipment' => $record->equipment->yiqikong_id,
                    'equipment_name' => $record->equipment->name,
                    'start_time' => $record->dtstart,
                    'end_time' => $record->dtend,
                    'feedback' => $record->feedback,
                    'status' => $record->status,
                    'samples' => $record->samples,
                    'reserve_id' => $record->reserv_id,
                    'remarks' => $record->use_type_desc, // 备注
                    'source_name' => LAB_ID
                ]
            ];

            $mq
                ->useTube('stark')
                ->put(json_encode($payload, TRUE));

            return true;
        } elseif ($record->user->gapper_id) {
            $msg = [
                'method' => 'YiQiKong/Record/Delete',
                'params' => [
                    'equipment' => $record->equipment->yiqikong_id,
                    'id' => $record->id,
                ],
            ];

            Debade_Queue::of('YiQiKong')->push($msg, 'record');
        }
    }

    static function format_record($record, $old_data, $new_data)
    {
        if (!$record->dtend) {
            $fee = 0;
        } else {
            $equipment = $record->equipment;
            //如果仪器 使用计费
            $charge = O('eq_charge', ['source' => $record]);
            if ($charge->id) {
                $charge->source = $record;
                $charge->user = $record->user;
                $charge->equipment = $record->equipment;
                $charge->calculate_amount();

                $fee = $charge->amount;
            } else {
                $dtstart = $record->dtstart;
                $dtend = $record->dtend;

                $ostart = $old_data['dtstart'] ?: $dtstart;
                $oend = $old_data['dtend'] ?: $dtend;
                $reserv = Q("eq_reserv[equipment={$equipment}][dtstart~dtend=$dtstart|dtstart~dtend=$dtend|dtstart=$dtstart~$dtend|dtstart~dtend=$ostart|dtstart~dtend=$oend|dtstart=$ostart~$oend]")->current();

                $charge = O('eq_charge', ['source' => $reserv]);
                if (!$charge->id && !$equipment->charge_script['eq_reserv']) {
                    $fee = 0;
                } elseif (!$charge->source->id) {
                    $charge->source = $reserv;
                    $charge->user = $reserv->user;
                    $charge->equipment = $reserv->equipment;
                    $charge->calculate_amount();
                    $fee = $charge->amount;
                } else {
                    $fee = $charge->amount;
                }
            }
        }

        $msg = [];
        if ($new_data['id']) {
            //添加
            $msg['method'] = 'YiQiKong/Record/Add';
        } else {
            //修改
            $msg['method'] = 'YiQiKong/Record/Update';
        }
        $msg['params'] = [
            'source_id' => $record->id,
            'user' => $record->user->gapper_id,
            'user_name' => $record->user->name,
            'user_local' => $record->user->id,
            'equipment' => $record->equipment->yiqikong_id,
            'equipment_name' => $record->equipment->name,
            'start_time' => $record->dtstart,
            'end_time' => $record->dtend,
            'charge' => $fee,
            'feedback' => $record->feedback,
            'status' => $record->status,
            'samples' => $record->samples,
            'reserve_id' => $record->reserv_id,
            'remarks' => $record->use_type_desc, // 备注
            'use_type' => $record->use_type ?? 0,
            'yiqikong_id' => $record->user->yiqikong_id,
            'source_name' => LAB_ID,
            'agent_id' => $record->agent->id,
            'agent_name' => $record->agent->name,
            'description' => $record->description,
            'extra_fields' => $record->extra_fields,
            'is_missed' => $record->is_missed ?? 0,
            'is_locked' => $record->is_locked ?? 0,
            'preheat' => $record->preheat ?? 0,
            'cooling' => $record->cooling ?? 0,
        ];
        return $msg;
    }

    static function links($e, $user, $object, $params, $links)
    {
        // 获取这个用户的每一个角色，看看时候支持对对象进行相关的操作
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
}