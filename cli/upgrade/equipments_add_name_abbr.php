#!/usr/bin/env php
<?php
  /**
   * @file   equipments_add_name_abbr.php
   * @author Xiaopei Li <toksea@gmail.com>
   * @date   2011.07.18
   * 
   * @brief  对所有仪器添加name_abbr属性，
   *         升级到release-2.1.0时需运行一次此脚本
   *
   * usage: SITE_ID=cf LAB_ID=test ./equipments_add_name_abbr.php
   * 
   */
require "base.php";

if (!class_exists('PinYin')) {
	die("系统中无PinYin类\n");
}

$equipments = Q('equipment');
$time = time();

foreach ($equipments as $e) {
	$e->name_abbr = PinYin::code($e->name);
	if ($e->save()) {
		echo $e->name_abbr . "\n";
	}
}
