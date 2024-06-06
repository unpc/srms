#!/usr/bin/env php
<?php
    /*
     * file fix_user_groups.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-09-18
     *
     * useage SITE_ID=cf LAB_ID=nankai php fix_user_groups.php
     * brief 用来修正用户和组织机构tag关联关系的脚本
     */

require 'base.php';

$done_file = dirname(__FILE__). strtr('/fix_lab_groups.%site.%lab.done', [
    '%site'=> $_SERVER['SITE_ID'],
    '%lab'=> $_SERVER['LAB_ID'],
]);

//已经运行过了, 不予执行
if (File::exists($done_file)) die;

$root = Tag_Model::root('group');

$count = 0;

foreach(Q('user') as $user) {

    //如果结构是正确的,当前所在的层数就应该和已关联的组织机构的数量相同
    if ($user->group->id && Q("$user tag_group[root={$root}]")->total_count() != $root->current_levels($user->group)) {

        //已经关联的进行disconnect
        foreach(Q("{$user} tag_group[root={$root}]") as $tag) {
            $tag->disconnect($user);
        }

        if ($user->group->id) $user->group->connect($user);
    }
}

@touch($done_file);
