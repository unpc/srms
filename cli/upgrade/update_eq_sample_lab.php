#!/usr/bin/env php
<?php
  /*
	修复更新到 2.1.0 后 eq_sample 没 lab 属性的问题
	usage: SITE_ID=cf LAB_ID=nankai ./update_eq_sample_lab.php
   */
require "base.php";

$eq_samples = Q('eq_sample');

$update = 0;

foreach ($eq_samples as $eq_sample) {
	if ($eq_sample->lab->id) continue;
	$eq_sample->lab = $eq_sample->sender->lab;
	$eq_sample->save();
	$update++;
}

echo sprintf('更新了%s条数据', $update);
