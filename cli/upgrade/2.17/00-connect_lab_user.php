#!/usr/bin/env php
<?php
    /*
     * file 00-connect_lab_user.php
     * author Yusheng Wang <yusheng.wang@geneegroup.com>
     * date 2017-08-17
     *
     * useage SITE_ID=cf LAB_ID=test php 00-connect_lab_user.php
     * brief 删除user中lab_id字段，采用_r_表关联人员和课题组 
     * 同时更新user:name_abbr
     */

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function() {
    $db = Database::factory();
    return !!$db->value('DESC `user` lab_id');
};

//数据库备份
$u->backup = function() {
    return TRUE;
};

$u->upgrade = function() {

    $db = Database::factory();

    // create table _r_user_lab
    $query = "CREATE TABLE IF NOT EXISTS `_r_user_lab` ( `id1` bigint NOT NULL,`id2` bigint NOT NULL,`type` varchar(20) NOT NULL,`approved` int NOT NULL DEFAULT 0,PRIMARY KEY (`id1`,`id2`,`type`),KEY `id1` (`id1`,`type`),KEY `id2` (`id2`,`type`),KEY `approved` (`approved`) ) ENGINE = MyISAM DEFAULT CHARSET = utf8";
    $db->query($query);

    // connect user lab
    $query = "SELECT `lab_id`, `id` FROM `user` ORDER BY `id`";
    $results = $db->query($query)->rows();
    foreach ((array)$results as $res) {
        $query = "INSERT INTO `_r_user_lab` (`id1`, `id2`, `type`, `approved`) VALUES ({$res->id}, {$res->lab_id}, '', 0) ON DUPLICATE KEY UPDATE `approved`=0";
        $db->query($query);
    }
    // connect lab PIs
    $query = "SELECT `owner_id`, `id` FROM `lab` ORDER BY `id`";
    $results = $db->query($query)->rows();
    foreach ((array)$results as $res) {
        $query = "INSERT INTO `_r_user_lab` (`id1`, `id2`, `type`, `approved`) VALUES ({$res->owner_id}, {$res->id}, 'pi', 0) ON DUPLICATE KEY UPDATE `approved`=0";
        $db->query($query);
    }
    // DROP COLUMNs
    $query = "ALTER TABLE user DROP COLUMN lab_id;";
    $db->query($query);
    $query = "ALTER TABLE user DROP COLUMN lab_abbr;";
    $db->query($query);

    //用户名缩写
    $ds = $db->query('SELECT id,name FROM user');
    while ($row = $ds->row()) {
        $name = $row->name;
        $name_abbr = PinYin::code($name);
        $first_only_name_abbr = PinYin::code($name, TRUE);


        if ($name_abbr != $first_only_name_abbr) {
            $prefix = str_replace(' ', '', $name_abbr);
            $name_abbr = join(' ', [$name_abbr, $first_only_name_abbr, $prefix]);
        }
        $db->query('UPDATE user SET name_abbr = "%s" WHERE id = %d', $name_abbr, $row->id);
    }


    // update nfs floder
    foreach (Q('user') as $user) {
        $root = Config::get('nfs.root');
        File::rmdir($root . 'share/users/'. $user->id. '/lab');
        File::check_path($root . 'share/users/'. $user->id. '/lab/.');
        chown($root . 'share/users/'. $user->id. '/lab/.','www-data');
        chgrp($root . 'share/users/'. $user->id. '/lab/.','www-data');
        foreach (Q("$user lab") as $lab) {
            $target = NFS_Share::get_share_path($lab);
            $link = NFS_Share::get_share_path($user, 'lab/' . $lab->name . '-' . $lab->id);
            if($lab->nfs_size) is_dir($target) and @symlink($target, $link);
            chown($link,'www-data');
            chgrp($link,'www-data');
        }
    }

    Upgrader::echo_success("\nuser 数据升级成功! \n");
    return TRUE;
};

//恢复数据
$u->restore = function() {
    return TRUE;
};

$u->run();
