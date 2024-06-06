#!/usr/bin/env php
<?php
/*
 * useage SITE_ID=xx LAB_ID=xx php 03-upgrade_abbr
 * brief ORM:eq_banned记录写入abbr
 * brief ORM:历史数据处理从user-》user_violation
 * brief ORM:achievements三个ORM记录写入abbr
 * brief ORM:achievements的ac_author记录写入abbr
 * brief ORM:user写入creator_abbr、name_abbr和auditor_abbr
 * brief ORM:equipment写入using_abbr、contacts_abbr
*/
$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function() {
    return TRUE;
};

//数据库备份
$u->backup = function() {
    return TRUE;
};


$u->upgrade = function() {
    $db = Database::factory();

    $eq_banneds = Q('eq_banned[!user_abbr|!obj_abbr]');

    foreach ($eq_banneds as $eq_banned) {
        $obj_abbr = PinYin::code($eq_banned->object->name);

        $query = "UPDATE `eq_sample` SET
        `obj_abbr` = '{$obj_abbr}',
        WHERE `id` = {$eq_banned->id}";
        $db->query($query);
    }
    Upgrader::echo_success("eq_banned记录写入abbr成功!");

    $users = Q('user');
    foreach ($users as $user) {
        if ($user->creator->id) {
            $user->creator_abbr = PinYin::code($user->creator->name);
        }

        if ($user->auditor->id) {
            $user->auditor_abbr = PinYin::code($user->auditor->name);
        }
        $name_abbr = PinYin::code($user->name);
        $first_only_name_abbr = PinYin::code($user->name, TRUE);

        if ($name_abbr != $first_only_name_abbr) {
            $prefix = str_replace(' ', '', $name_abbr);
            $name_abbr = join(' ', [$name_abbr, $first_only_name_abbr, $prefix]);
        }
        $user->name_abbr = $name_abbr;

        $user->save();

        $user_v = O('user_violation',['user'=>$user]);
        if (!$user_v->id) {
            $user_v->user = $user;
            $user_v->user_abbr = PinYin::code($user->name);
            $user_v->address_abbr = PinYin::code($user->address);

            if ($user->eq_miss_count) {
                $user_v->eq_miss_count = $user->eq_miss_count;
                unset($user->eq_miss_count);
            }
            if ($user->eq_leave_early_count) {
                $user_v->eq_leave_early_count = $user->eq_leave_early_count;
                unset($user->eq_leave_early_count);
            }
            if ($user->eq_overtime_count) {
                $user_v->eq_overtime_count = $user->eq_overtime_count;
                unset($user->eq_overtime_count);
            }
            if ($user->eq_late_count) {
                $user_v->eq_late_count = $user->eq_late_count;
                unset($user->eq_late_count);
            }
            $user_v->save();
        }
    }
    Upgrader::echo_success("历史数据处理从user->user_violation 成功!");

    $equipments = Q('equipment');
    foreach ($equipments as $equipment) {
        $equipment->location_abbr = PinYin::code($equipment->location);
        $equipment->save();
    }
    Upgrader::echo_success("仪器location_abbr写入成功!");

    $tag_root = Tag_Model::root('achievements_publication');
    if ($db->value("SHOW TABLES LIKE '_r_tag_publication'")) {
        $query = "DELETE FROM `_r_tag_publication`
            WHERE `id1` = {$tag_root->id}";
        $db->query($query);
    }
    foreach (Q('publication') as $publication) {
        $abbr = PinYin::code($publication->title);
        $journal_abbr = PinYin::code($publication->journal);

        $query = "UPDATE `publication` SET
        `name_abbr` = '{$abbr}',
        `journal_abbr` = '{$journal_abbr}'
        WHERE `id` = {$publication->id}";
        $db->query($query);
        if (!Q("{$tag_root}<parent tag {$publication}")->total_count()) {
            $publication->connect($tag_root);
        }
    }
    Upgrader::echo_success("publication 记录写入abbr成功!");

    $tag_root = Tag_Model::root('achievements_award');
    if ($db->value("SHOW TABLES LIKE '_r_tag_award'")) {
        $query = "DELETE FROM `_r_tag_award`
            WHERE `id1` = {$tag_root->id}";
        $db->query($query);
    }

    foreach (Q('award') as $award) {
        $abbr = PinYin::code($award->name);

        $query = "UPDATE `award` SET
        `name_abbr` = '{$abbr}'
        WHERE `id` = {$award->id}";
        $db->query($query);
        if (!Q("{$tag_root}<parent tag {$award}")->total_count()) {
            $award->connect($tag_root);
        }
        if (!Q("{$award} ac_author")->total_count()) {
            $author = O('ac_author');
            $author->achievement = $award;
            $author->save();
        }
    }
    Upgrader::echo_success("award 记录写入abbr成功!");

    $tag_root = Tag_Model::root('achievements_patent');
    if ($db->value("SHOW TABLES LIKE '_r_tag_patent'")) {
        $query = "DELETE FROM `_r_tag_patent`
            WHERE `id1` = {$tag_root->id}";
        $db->query($query);
    }
    foreach (Q('patent') as $patent) {
        $abbr = PinYin::code($patent->name);

        $query = "UPDATE `patent` SET
        `name_abbr` = '{$abbr}'
        WHERE `id` = {$patent->id}";
        $db->query($query);
        if (!Q("{$tag_root}<parent tag {$patent}")->total_count()) {
            $patent->connect($tag_root);
        }
        if (!Q("{$patent} ac_author")->total_count()) {
            $author = O('ac_author');
            $author->achievement = $patent;
            $author->save();
        }
    }
    Upgrader::echo_success("patent 记录写入abbr成功!");

    foreach (Q('ac_author') as $ac_author) {
        $abbr = PinYin::code($ac_author->name);

        $query = "UPDATE `ac_author` SET
        `name_abbr` = '{$abbr}'
        WHERE `id` = {$ac_author->id}";
        $db->query($query);
    }
    Upgrader::echo_success("ac_author 记录写入abbr成功!");

    foreach (Q('equipment') as $equipment) {
        $current_user = $equipment->current_user();
        if ($current_user->id) {
            $abbr = $current_user->name_abbr;
            $using = 1;
        }
        else {
            $abbr = '';
            $using = 0;
        }
        $users = Q("{$equipment} user.contact");
		$contacts = [];
		foreach ($users as $user) {
			$contacts[] = $user->name_abbr;
		}
		$contacts_abbr = join(', ', $contacts);

        $query = "UPDATE `equipment` SET
        `using_abbr` = '{$abbr}',
        `contacts_abbr` = '{$contacts_abbr}',
        `is_using` = '{$using}'
        WHERE `id` = {$equipment->id}";
        $db->query($query);
    }
    Upgrader::echo_success("equipment 写入using_abbr成功!");
};

//恢复数据
$u->restore = function() {
    return TRUE;
};

$u->run();
