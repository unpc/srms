#!/usr/bin/env php
<?php
    /*
     * file fix_nankai_sky_records.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-04-23
     *
     * useage SITE_ID=cf-lite LAB_ID=nankai_sky php fix_nankai_sky_records.php
     * brief 用于修复cf-lite  nankai_sky 的仪器中每5秒创建一条错误使用记录的问题
     */

if ($_SERVER['SITE_ID'] != 'cf-lite' || $_SERVER['LAB_ID'] != 'nankai_sky') {
    die("SITE_ID=cf-lite LAB_ID=nankai_sky php fix_nankai_sky_records.php\n");
}

require 'base.php';

//停止Notification
define('DISABLE_NOTIFICATION', TRUE);

//起始时间在2014年04月15日之后的使用记录进行修正

$start_time = mktime(0, 0, 0, 4, 15, 2014);

$fixed_records = [];

foreach(Q('equipment') as $equipment) {

    //倒序检查该仪器的records
    foreach(Q("eq_record[equipment={$equipment}][dtend>0][dtstart>{$start_time}]:sort(id D)") as $record) {

        //不存在last_record
        //则设定为第一个record
        if (!isset($last_record)) {
            $last_record = $record;
        }
        else {

            //当前record和上一个record进行比对
            //当前record使用时长不超过20秒,并且两个record的间隔不超过300秒,record为同一人使用, 则进行record合并
            //为同一人使用
            if (
                $last_record->user->id
                &&
                (($record->dtend - $record->dtstart) < 20) 
                && 
                ($last_record->dtstart - $record->dtend < 300) 
                && 
                $last_record->user->id == $record->user->id
                ) {

                //设定last_record的起始时间
                $last_record->dtstart = $record->dtstart;

                 ++ $ref;

                //进行record删除
                echo strtr("删除%id\n", ['%id'=> $record->id]);
                $record->delete();
            }
            else {
                //两个不相干的使用记录

                //有合并(引用)
                if ($ref) {
                    //存储last_record
                    $fixed_records[] = $last_record;
                }

                //设定为0
                $ref = 0;

                //清空last_record
                $last_record = $record;
            }
        }
    }
    //清空last_record
    unset($last_record);
}

if (count($fixed_records)) {
    foreach(array_reverse($fixed_records) as $reocrd) {
        $dtstart = $reocrd->dtstart;
        $dtend = $reocrd->dtend;

        $n = clone $reocrd;

        $n->dtstart = $dtstart;
        $n->dtend = $dtend;

        //清空id
        //$n->id = NULL;
        //会自定清空id, 无需再重复设定id

        //删除record
        $reocrd->delete();

        //存储n
        $n->save();
        echo strtr("保存新record[%id]\n", [
            '%id'=> $n->id,
        ]);
    }
}
