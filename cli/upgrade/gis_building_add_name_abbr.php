#!/usr/bin/env php
<?php
  /**
   * @file   gis_building_add_name_abbr.php
   * @author Yu Li  <yu.li@geneegroup.com>
   * @date   2012.07.16
   * 
   * @brief  对所有gis_building添加name_abbr属性，
   *
   * usage: SITE_ID=cf LAB_ID=test php ./gis_building_add_name_abbr.php
   * 
   */
require '../base.php';

$u = new Upgrader();

//备份数据
$u->backup = function() {

    $dbfile = LAB_PATH.'private/backup/before_gis_building_add_name_abbr.sql';
    File::check_path($dbfile);

    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "备份数据库表");

    $db = Database::factory();
    return $db->snapshot($dbfile);
};

// 检查是否升级
$u->check = function() {
    $db = Database::factory();
    $name_abbr_col_existed =  $db->value('SHOW COLUMNS FROM gis_building WHERE field LIKE "name_abbr"');

    if (!$name_abbr_col_existed) return TRUE;
    return FALSE;
};

//升级
$u->upgrade = function() {

    $db = Database::factory();

    $db->query("ALTER TABLE `gis_building` add `name_abbr` VARCHAR( 150 ) NOT NULL DEFAULT  ''");

    $gis_buildings = Q('gis_building');

    foreach ($gis_buildings as $gis_building) {
        $gis_building->name_abbr = PinYin::code($gis_building->name);
        if ($gis_building->save()) {
            echo sprintf('%s 增加name_abbr为 %s 成功！'. "\n", $gis_building->name, $gis_building->name_abbr);
        }
    }

};

//升级检测
$u->verify = function() {

    $db = Database::factory();
    if (!$db->value('SHOW COLUMNS FROM gis_building WHERE field LIKE "name_abbr"')) return FALSE;
};

//恢复数据
$u->restore = function() {

    $dbfile = LAB_PATH.'private/backup/before_gis_building_add_name_abbr.sql';
    File::check_path($dbfile);
    Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "恢复数据库表");
    $db = Database::factory();
    $db->restore($dbfile);
};

$u->run();
