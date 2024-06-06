#!/usr/bin/env php
<?php
    /*
     * file fix_lims_proj_list
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2015-01-30
     *
     * useage SITE_ID=lab LAB_ID=nankai_admin php fix_lims_proj_list.php
     * brief 用来比对 /etc/lims2/proj_list 文件和 数据库内容, 修正 /etc/lims2/proj_list 
     */

require 'base.php';

$proj_list = '/etc/lims2/proj_list';

$fp = fopen($proj_list, 'r');

while($line = fgets($fp)) {

    list($site, $lab) = explode("\t", $line, 2);
    $lab = rtrim($lab);
    $list_dbs[] = $lab;

}

fclose($fp);

$db = Database::factory();

//获取系统中
$query = $db->query("SELECT `SCHEMA_NAME` FROM INFORMATION_SCHEMA.SCHEMATA WHERE `SCHEMA_NAME` LIKE 'lims2_%'");

//获取当前系统中有的

$exists_dbs = [];
if ($query) while($line = $query->row()) {
    $exists_dbs[] = str_replace('lims2_', NULL, $line->SCHEMA_NAME);
}

//获取到多余的 proj_list 的 lab
$uncalled_for_dbs = array_diff($list_dbs, $exists_dbs);

if (!count($uncalled_for_dbs)) return;

$content = [];
$fp = fopen($proj_list, 'r');
while($line = fgets($fp)) {
    list($site, $lab) = explode("\t", $line, 2);
    $lab = rtrim($lab);
    //如果该行没从多余的 proj_list 中找到, 则保留
    if (array_search($lab, $uncalled_for_dbs) === FALSE) {
        $content[] = $line;
    }
}
fclose($fp);

//写
file_put_contents($proj_list, join("", $content));
