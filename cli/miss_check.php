#!/usr/bin/env php
<?php

require 'base.php';

try {

    $now = time();
    Log::add(date('c', $now), 'miss_check');

    function miss_reserv($user, $equipment) {
        $user_v = O('user_violation',['user'=>$user]);
        $user_v->eq_miss_count ++;
        $user_v->save();

        $incharges = Q("{$equipment} user.incharge");
        foreach ($incharges as $incharge) {
                Notification::send('eq_reserv.misstime', $incharge, [
                   '%user' => Markup::encode_Q($user),
                   '%equipment' => Markup::encode_Q($equipment),
                   '%contact' => Markup::encode_Q($incharge),
                   '%times' => $user->eq_miss_count
               ]);
        }


        Notification::send('eq_reserv.misstime.self', $user, [
                               '%user' => Markup::encode_Q($user),
                               '%equipment' => Markup::encode_Q($equipment),
                               '%times' => $user->eq_miss_count
                           ]);

        Log::add(sprintf("%s[%d] eq_miss_count => %d", $user->name, $user->id, $user->eq_miss_count), "miss_check");

        $max_allowed_miss_times =Lab::get('equipment.max_allowed_miss_times', Config::get('equipment.max_allowed_miss_times'),0);
        if($max_allowed_miss_times > 0 && $user->eq_miss_count > $max_allowed_miss_times){
            if (Q("eq_banned[user=$user]:limit(1)")->length()==0) {
                $banned = O('eq_banned');
                $banned->user = $user;
                $banned->reason = I18N::T('eq_reserv', '使用设备爽约次数超过系统预定义上限!');
                $banned->save();
            }
        }
    }

    //用户超时数加1，发送消息给负责人，超过最大次数加入黑名单
    function overtime_reserv($user, $equipment){
        $user_v = O('user_violation',['user'=>$user]);
        $user_v->eq_overtime_count ++;
        $user_v->save();

        $incharges = Q("{$equipment} user.incharge");
        foreach ($incharges as $incharge) {
            Notification::send('eq_reserv.overtime', $incharge, [
                   '%user' => Markup::encode_Q($user),
                   '%equipment' => Markup::encode_Q($equipment),
                   '%contact' => Markup::encode_Q($incharge),
                   '%times' => $user->eq_overtime_count
               ]);
        }


        Notification::send('eq_reserv.overtime.self', $user, [
                               '%user' => Markup::encode_Q($user),
                               '%equipment' => Markup::encode_Q($equipment),
                               '%times' => $user->eq_overtime_count
                           ]);


        Log::add(sprintf("%s[%d] eq_overtime_count => %d", $user->name, $user->id, $user->eq_overtime_count), "miss_check");

        $max_allowed_overtime_times = Lab::get('equipment.max_allowed_overtime_times', Config::get('equipment.max_allowed_overtime_times',0));
        if($max_allowed_overtime_times > 0 && $user->eq_overtime_count > $max_allowed_overtime_times){
            $banned = O('eq_banned');
            $banned->user = $user;
            $banned->reason = I18N::T('eq_reserv', '使用设备超时次数超过系统预定义上限!');
            $banned->save();
        }
    }

    //搜素当前时间之前的预约
    $reservs = Q("eq_reserv[status={EQ_Reserv_Model::PENDING}][dtend<{$now}]");

    foreach ($reservs as $reserv) {

        $user = $reserv->user;
        $equipment = $reserv->equipment;

        $in_control = $equipment->control_mode && $equipment->control_mode !== 'nocontrol';
        $record = O('eq_record', ['reserv'=>$reserv]);

        // const PENDING = 0;
        // const NORMAL = 1;
        // const MISSED = 2;
        // const INADVERTENTLY_MISSED = 3;
        // const OVERTIME = 4;

        //如果仪器不控制,将预约的状态设置成正常
        if(!$in_control) {
            $reserv->status = EQ_Reserv_Model::NORMAL;
            $reserv->save();
        }
        else{
            //在预约结束前,有个未关闭的使用记录，
            $using_record = Q("eq_record[user={$user}][equipment={$equipment}][dtstart<{$reserv->dtend}][dtend=0][reserv_id=0]:sort(dtstart):limit(1)")->current();
            if($using_record->id){
                //如果有个预约, 有个使用记录提前使用，还未结束，那么预约和使用记录需要关联，否则会认为是爽约
                //只是将预约和使用记录进行关联，不作其它处理，下次脚本执行的时候会发送
                if(!$using_record->reserv->id){
                    $using_record->reserv = $reserv;
                    $using_record->save();
                }
                continue;
            }


            if(!$record->id) {
                //没有正在使用的记录，是爽约记录
                $reserv->status = EQ_Reserv_Model::MISSED;
                $reserv->save();

                miss_reserv($user, $equipment);
            }
            else{
                //使用超时
                if($record->dtend > $reserv->dtend){
                    $reserv->status = EQ_Reserv_Model::OVERTIME;
                    $reserv->save();

                    overtime_reserv($user, $equipment);
                }
                else{
                    $reserv->status = EQ_Reserv_Model::NORMAL;
                    $reserv->save();
                }
            }
        }
    }
}
catch (Error_Exception $e) {
    Log::add($e->getMessage(), 'miss_check');
}
