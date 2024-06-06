#!/usr/bin/env php
<?php
    /*
     * file init_groups.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2013-11-26
     *
     * useage SITE_ID=cf LAB_ID=tncic php init_groups.php
     * brief 初始化组织机构
     */

$_SERVER['SITE_ID'] = 'cf';
$_SERVER['LAB_ID'] = 'tncic';

require dirname(dirname(__FILE__)). '/base.php';

$file = $argv[1] ? : 'groups.sql';

if (!$file || !File::exists($file)) {
    die("Usage: SITE_ID=xx LAB_ID=xx php import_users.php [groups.sql]\n");
}

$db = Database::factory();

if ($db->restore($file)) {
    echo "初始化组织机构成功!\n";
    Lab::set('tag.group_id', 1);
}

//重置db
Database::reset();

//进行path update
foreach(Q('tag') as $tag) {
    $tag->update_tag_paths();
}
