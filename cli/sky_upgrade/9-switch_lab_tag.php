#!/usr/bin/env php

<?php
  /*
	实验室标签转换(分发实验室标签)
   (xiaopei.li@2011.09.20)
  */

  /*
	1. 找出所有实验室标签
	2. 找出使用标签的仪器
	3. 遍历使用标签的仪器，建立用户标签
	4. 对用户标签关联实验室
	5. 删除实验室标签
   */

require '../base.php';

$lab_tag_root = O('tag', ['name' => '实验室标签']);
$lab_tags = Q("{$lab_tag_root}<root tag");

$equipments = Q('equipment');

foreach ($equipments as $eq) {
	echo $eq->name . "\n";

	$tagged = P($eq)->get('@TAG');
	if ($tagged) {
		foreach ($lab_tags as $lab_tag) {
			foreach ($tagged as $tag_name => $data) {
				if ($lab_tag->name == $tag_name) {
					$root = $eq->get_root();
					$tag = O('tag', ['root_id'=>$root->id, 'name'=>$tag_name]);
					if (!$tag->id) {
						$tag->name = $tag_name;
						$tag->parent = $root;
						$tag->update_root()->save();

						$labs = Q("$lab_tag lab");
						foreach ($labs as $lab) {
							if ($lab->id) {
								$tag->connect($lab);
							}
						}
					}
				}
			}
		}
	}
}

foreach ($lab_tags as $lab_tag) {
	$lab_tag->delete();
}

$lab_tag_root->delete();
