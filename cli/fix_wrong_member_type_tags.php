#!/usr/bin/env php
<?php
    /*
     * file fix_wrong_member_type_tags.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-07-31
     *
     * useage SITE_ID=cf LAB_ID=nankai php fix_wrong_member_type_tags.php
     * brief 用于对创建的member_type的tags进行删除、重新创建
     */

require 'base.php';

//如果已存在了tag.member_type_id
//则说明已创建了member_type tags
if (Lab::get('tag.member_type_id', 0)) {
    $root = Tag_Model::root('member_type');
    Q("tag[root={$root}]")->delete_all();

    //重新根据User_Model::$members生成tag
    foreach(User_Model::get_members() as $title => $sub) {
        $tag = O('tag');
        $tag->parent = $root;
        $tag->root = $root;
        $tag->name = $title;
        $tag->save();

        foreach($sub as $sub_title) {
            $sub_tag = O('tag');
            $sub_tag->root = $root;
            $sub_tag->parent = $tag;
            $sub_tag->name = $sub_title;
            $sub_tag->save();
        }
    }
}
else {
    echo "无需升级\n";
}
