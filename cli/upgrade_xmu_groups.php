#!/usr/bin/env php
<?php
    /*
     * file upgrade_xmu_groups.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2015-03-26
     *
     * useage SITE_ID=cf LAB_ID=xmu php upgrade_xmu_groups.php
     * brief 更新厦门大学课题组组织机构
     * 需要将所有课题组的组织机构更改成本课题组的PI的组织机构
     * 如果课题组没有PI，课题组的组织机构不变。
     */



require 'base.php';

$root = Tag_Model::root('group');

foreach(Q('lab') as $lab) {

    $owner = $lab->owner;

    if ($owner->id) {

        foreach(Q("{$lab} tag_group[root={$root}]") as $tag) {
            $tag->disconnect($lab);
        }

        $lab->group = $owner->group;

        $lab->save();

        $lab->group->connect($lab);
    }
}
