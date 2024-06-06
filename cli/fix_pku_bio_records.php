#!/usr/bin/env php
<?php
    /*
     * file #!/usr/bin/env php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-04-01
     *
     * useage SITE_ID=cf-mini LAB_ID=pku_bio php fix_pku_bio_records.php
     * brief 用于修复cf-mini pku_bio 的5号仪器的错误的使用记录
     */

if ($_SERVER['SITE_ID'] != 'cf-mini' || $_SERVER['LAB_ID'] != 'pku_bio') {
    die("SITE_ID=cf-mini LAB_ID=pku_bio php fix_pku_bio_records.php\n");
}

require 'base.php';

//停止Notification
define('DISABLE_NOTIFICATION', TRUE);

//5号有该问题, 只针对5号仪器进行修正
$equipment = O('equipment', 5);

$fixed_records = [];
//倒序检查该仪器的records
foreach(Q("eq_record[equipment={$equipment}][dtend>0]:sort(id D)") as $record) {
    //不存在last_record
    //则设定为第一个record
    if (!isset($last_record)) {
        $last_record = $record;
    }
    else {

        //当前record和上一个record进行比对
        //当前record使用时长不超过10秒,并且两个record的间隔不超过300秒,record为同一人使用, 则进行record合并
        //为同一人使用

        if ((($record->dtend - $record->dtstart) < 20) && ($last_record->dtstart - $record->dtend < 300) && $last_record->user->id == $record->user->id) {
            $last_record->dtstart = $record->dtstart;

             ++ $ref;

            //进行record删除
            echo strtr("删除%id\n", ['%id'=> $record->id]);
            $record->delete();
        }
        else {
            //两个不相干的使用记录

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
