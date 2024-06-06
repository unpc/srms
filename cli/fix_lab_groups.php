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

$done_file = strtr('fix_lab_groups.%site.%lab.done', [
    '%site'=> $_SERVER['SITE_ID'],
    '%lab'=> $_SERVER['LAB_ID'],
]);

//已经运行过了, 或者为lims, 不予执行
if (File::exists($done_file) || $_SERVER['SITE_ID'] == 'lab') die;

$root = Tag_Model::root('group');

$count = 0;

foreach(Q('lab') as $lab) {

    //如果结构是正确的,当前所在的层数就应该和已关联的组织机构的数量相同
    if ($lab->group->id && Q("$lab tag_group[root={$root}]")->total_count() != $root->current_levels($lab->group)) {

        //已经关联的进行disconnect
        foreach(Q("{$lab} tag_group[root={$root}]") as $tag) {
            $tag->disconnect($lab);
        }

        if ($lab->group->id) $lab->group->connect($lab);
    }
}

@touch($done_file);
