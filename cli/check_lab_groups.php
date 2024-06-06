#!/usr/bin/env php
<?php
    /*
     * file check_lab_groups.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-09-17
     *
     * useage SITE_ID=cf LAB_ID=nankai php check_lab_groups.php
     * brief 用来检测实验室和组织机构tag关联关系的脚本
     */

require 'base.php';

$done_file = strtr('check_lab_groups.%site.%lab.done', [
    '%site'=> $_SERVER['SITE_ID'],
    '%lab'=> $_SERVER['LAB_ID'],
]);

//已经运行过了, 或者为lims, 不予执行
if (File::exists($done_file) || $_SERVER['SITE_ID'] == 'lab') die;

$root = Tag_Model::root('group');

$count = 0;

ob_start();

foreach(Q('lab') as $lab) {

    //如果结构是正确的,当前所在的层数就应该和已关联的组织机构的数量相同
    if ($lab->group->id && Q("$lab tag_group[root={$root}]")->total_count() != $root->current_levels($lab->group)) {
        echo "{$lab->name}[{$lab->id}] 结构错误\n";
        ++ $count;
    }
}

if ($count) {
    echo "共计{$count}个课题组有组织机构关联错误问题\n";
}
else {
    echo "没有课题组有组织机构关联错误问题\n";
}

$body = ob_get_contents();

ob_end_clean();

$mail = new Email();

$receivers = ['rui.ma@geneegroup.com'];

$mail->to($receivers);

$subject = Config::get('page.title_default'). '检测课题组组织机构关联是否正常';

$base_url = Config::get('system.base_url');

$mail->subject($subject);
$mail->body($body);
$mail->send();

@touch($done_file);
