#!/usr/bin/env php
<?php
    /*
     * file check_user_groups.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-09-18
     *
     * useage SITE_ID=cf LAB_ID=nankai php check_user_groups.php
     * brief 用来检测用户和组织机构tag关联关系的脚本
     */

require 'base.php';

$done_file = strtr('check_lab_groups.%site.%lab.done', [
    '%site'=> $_SERVER['SITE_ID'],
    '%lab'=> $_SERVER['LAB_ID'],
]);

//已经运行过了, 不予执行
if (File::exists($done_file)) die;

$root = Tag_Model::root('group');

$count = 0;

ob_start();

foreach(Q('user') as $user) {

    //如果结构是正确的,当前所在的层数就应该和已关联的组织机构的数量相同
    if ($user->group->id && Q("$user tag_group[root={$root}]")->total_count() != $root->current_levels($user->group)) {
        echo "{$user->name}[{$user->id}] 结构错误\n";
        ++ $count;
    }
}

if ($count) {
    echo "共计{$count}个用户有组织机构关联错误问题\n";
}
else {
    echo "没有用户有组织机构关联错误问题\n";
}

$body = ob_get_contents();

ob_end_clean();

$mail = new Email();

$receivers = ['rui.ma@geneegroup.com'];

$mail->to($receivers);

$subject = Config::get('page.title_default'). '检测用户组织机构关联是否正常';

$base_url = Config::get('system.base_url');
$body .= "\nbase_url: $base_url";

$mail->subject($subject);
$mail->body($body);
$mail->send();

@touch($done_file);
