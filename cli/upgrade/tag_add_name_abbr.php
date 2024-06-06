#!/usr/bin/env php
<?php
  /**
   * @file   tag_add_name_abbr.php
   * @author Rui Ma <rui.ma@geneegroup.com>
   * @date   2012.07.06
   * 
   * @brief  对所有tag添加name_abbr属性，
   *
   * usage: SITE_ID=cf LAB_ID=test php ./tag_add_name_abbr.php
   * 
   */

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;


$u = new Upgrader();

//备份数据
$u->backup = function() {

    $dbfile = LAB_PATH.'private/backup/before_tag_add_name_abbr.sql';
    File::check_path($dbfile);

    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "备份数据库表");

    $db = Database::factory();
    return $db->snapshot($dbfile);
};

// 检查是否升级
$u->check = function() {
    $db = Database::factory();
    $name_abbr_col_existed =  $db->value('SHOW COLUMNS FROM tag WHERE field LIKE "name_abbr"');

    if (!$name_abbr_col_existed) return TRUE;
    return FALSE;
};

//升级
$u->upgrade = function() {

    $db = Database::factory();

    $db->query("ALTER TABLE `tag` add `name_abbr` VARCHAR( 150 ) NOT NULL DEFAULT  ''");

    $tags = Q('tag');

    foreach ($tags as $tag) {
        $tag->name_abbr = PinYin::code($tag->name);
        if ($tag->save()) {
            echo sprintf('%s 增加name_abbr为 %s 成功！'. "\n", $tag->name, $tag->name_abbr);
        }
    }

};

//升级检测
$u->verify = function() {

    $db = Database::factory();
    if (!$db->value('SHOW COLUMNS FROM tag WHERE field LIKE "name_abbr"')) return FALSE;
};

//恢复数据
$u->restore = function() {

    $dbfile = LAB_PATH.'private/backup/before_tag_add_name_abbr.sql';
    File::check_path($dbfile);
    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "恢复数据库表");
    $db = Database::factory();
    $db->restore($dbfile);
};

$u->run();
