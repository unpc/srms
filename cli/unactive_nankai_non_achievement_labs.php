#!/usr/bin/env php
<?php
    /*
     * file unactive_nankai_non_achievement_labs.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-08-27
     *
     * useage php unactive_nankai_non_achievement_labs.php
     * brief 用来把所有的不包含实验室成果的实验室进行未激活处理, 同时发送消息提醒
     */

$_SERVER['SITE_ID'] = 'cf';
$_SERVER['LAB_ID'] = 'nankai';

require 'base.php';

$end = Date::time();

$start = $end - 365 * 60 * 60 * 24; //获取1年前的时间

$db = Database::factory();

$nankai_group = O('tag', [
    'root'=> Tag_Model::root('group'),
    'name'=> '南开大学',
]);

$sender = O('user');

//查找所有南开大学组织机构下的课题组
foreach(Q('lab') as $lab) {

    if (
        $nankai_group->is_itself_or_ancestor_of($lab->group) //南开大学组织机构下的实验室
        &&
        ! Q("publication[lab={$lab}][date={$start}~{$end}]")->total_count() //不存在论文
        &&
        ! Q("patent[lab={$lab}][date={$start}~{$end}]")->total_count() //不存在获奖
        &&
        ! Q("award[lab={$lab}][date={$start}~{$end}]")->total_count() //不存在专利
       ) {

        //不存在publication patent award
        //设定课题组未激活 //由于直接$lab->set('atime', 0)->save()会影响到用户激活状态, 故使用DB直接操作
        $db->query('UPDATE `lab` SET `atime` = 0 WHERE `id` = %d', $lab->id);

        //用来加载Notification_Handler
        class_exists('Notification');

        Notification_Email::send(
            $sender,
            [$lab->owner],
            '警告:因未填写成果您的课题组账号变为未激活',
            strtr("%PI, 您好!\n在贵单位正在使用的大型仪器共享管理系统中, 您课题组%lab由于在9月15日之前未填写课题组成果被修改为未激活. 请您尽快联系管理员进行课题组账号激活, 以免影响您课题组对仪器的正常使用.", [
                '%PI'=> $lab->owner->name,
                '%lab'=> $lab->name,
            ])
        );

        Notification_Message::send(
            $sender,
            [$lab->owner],
            '警告:因未填写成果您的课题组账号变为未激活',
            strtr("%PI, 您好!\n在贵单位正在使用的大型仪器共享管理系统中, 您课题组%lab由于在9月15日之前未填写课题组成果被修改为未激活. 请您尽快联系管理员进行课题组账号激活, 以免影响您课题组对仪器的正常使用.", [
                '%PI'=> $lab->owner->name,
                '%lab'=> $lab->name,
            ])
        );
    }
}
