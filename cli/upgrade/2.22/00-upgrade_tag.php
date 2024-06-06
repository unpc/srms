#!/usr/bin/env php
<?php
/*
 * file 00-upgrade_tag
 * author Lianhui.Cao <lianhui.cao@geneegroup.com>
 * date 2020-11-22
 *
 * useage SITE_ID=xx LAB_ID=xxxx php 00-upgrade_tag.php
 * brief 2.22版本之后一校N区将会被合并进主版本，tag表被拆分
 */

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function () {
    $db      = Database::factory();
    $query   = "SHOW TABLES LIKE 'tag'";
    $results = $db->query($query);
    if (!$results) {
        return false;
    }

    return true;
};

// 数据库备份
$u->backup = function () {
    return true;
};

$u->upgrade = function () {
    $types = ['group', 'equipment', 'equipment_user_tags'];
    
    foreach ($types as $type) {
        $conf_name = 'tag.' . $type;
        $name      = Config::get($conf_name, false);
        if (!$name) {
            continue;
        }

        Upgrader::echo_title('正在升级 ' . $name);

        $db      = Database::factory();
        $sql     = "select id from tag where name = '{$name}'";
        $root_id = (int) $db->value($sql);
        if (!$root_id) {
            continue;
        }

        $table = "tag_{$type}";

        $sql = "truncate {$table}";
        $db->query($sql);

        $sql = "insert into {$table} select name, name_abbr, parent_id, root_id, readonly, ctime, mtime, weight, code, id, _extra from tag where id = {$root_id}";
        $db->query($sql);

        $sql = "insert into {$table} select name, name_abbr, parent_id, root_id, readonly, ctime, mtime, weight, code, id, _extra from tag where root_id = {$root_id}";
        $db->query($sql);

        switch ($type) {
            case 'group':
                $db->query('truncate _r_user_tag_group');
                $sql = "insert into _r_user_tag_group select r.* from _r_user_tag as r left join tag as t on r.id2 = t.id where t.root_id = {$root_id}";
                $db->query($sql);
                $db->query('truncate _r_tag_group_lab');
                $sql = "insert into _r_tag_group_lab select r.* from _r_tag_lab as r left join tag as t on r.id1 = t.id where t.root_id = {$root_id}";
                $db->query($sql);
                $db->query('truncate _r_tag_group_equipment');
                $sql = "insert into _r_tag_group_equipment select r.* from _r_tag_equipment as r left join tag as t on r.id1 = t.id where t.root_id = {$root_id}";
                $db->query($sql);
                break;
            case 'equipment_user_tags':
                $db->query('truncate _r_user_tag_equipment_user_tags');
                $sql = "insert into _r_user_tag_equipment_user_tags select r.* from _r_user_tag as r left join tag as t on r.id2 = t.id where t.root_id = {$root_id}";
                $db->query($sql);
                $db->query('truncate _r_tag_equipment_user_tags_lab');
                $sql = "insert into _r_tag_equipment_user_tags_lab select r.* from _r_tag_lab as r left join tag as t on r.id1 = t.id where t.root_id = {$root_id}";
                $db->query($sql);
                $db->query('truncate _r_tag_group_tag_equipment_user_tags');
                $sql = "insert into _r_tag_group_tag_equipment_user_tags select r.* from _r_tag_tag as r left join tag as t on r.id2 = t.id where t.root_id = {$root_id}";
                $db->query($sql);
                break;
            case 'equipment':
            default:
                break;
        }
        Upgrader::echo_success($name . ' 升级成功');
    }

    $sql = "CREATE TABLE `_r_tag_group_tag` (
        `id1` bigint(20) NOT NULL,
        `id2` bigint(20) NOT NULL,
        `type` varchar(20) NOT NULL,
        `approved` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id1`,`id2`,`type`),
        KEY `id1` (`id1`,`type`),
        KEY `id2` (`id2`,`type`),
        KEY `approved` (`approved`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
    $db->query($sql);
    
    $sql = 'insert into _r_tag_group_tag(id1, id2, type, approved) select id2, id1, type, approved from _r_tag_tag';
    $db->query($sql);

    Upgrader::echo_separator();

    $objects = [
            'equipment' => '仪器',
            'user' => '人员',
            'lab' => '课题组',
    ];

    foreach ($objects as $object => $object_name) {
        Upgrader::echo_title("正在更新{$object_name}所属组织机构");
        foreach (Q($object) as $ob) {
            $old_group = O('tag', $ob->group_id);
            if (!$old_group->id) {
                continue;
            }
            $new_group = O('tag_group', ['name' => $old_group->name]);
            if (!$new_group->id) {
                continue;
            }
            $ob->group = $new_group;
            $ob->save();
        }
        Upgrader::echo_success("{$object_name}所属组织机构更新成功");
    }

};

// 恢复数据
$u->restore = function () {
    return true;
};

$u->run();
