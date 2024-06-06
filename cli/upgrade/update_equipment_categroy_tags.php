#!/usr/bin/env php
<?php

require "base.php";


try {
	$cat_root = Tag_Model::root('equipment');
	$not_in_server = EQ_Status_Model::NO_LONGER_IN_SERVICE;
	$equipments = Q("equipment[status!={$not_in_server}}]");
	$update = 0;
	$has_tag = 0;
	foreach ($equipments as $equipment) {
		$tags = Q("{$equipment} tag_equipment");
		$add = true;
		foreach ($tags as $tag) {
			if ($tag->id == $cat_root->id || $tag->root->id == $cat_root->id) {
				$add = false;
				break;
			}
		}
		if ($add) {
			$cat_root->connect($equipment);
			echo sprintf("%s[%d]仪器成功关联分类标签root.\n", $equipment->name, $equipment->id);
			$update ++;
		}
		else {
			$has_tag ++;
		}
	}
	
	echo "\n\n更新了".$update."条数据\n\n";
	echo "系统中有".$has_tag."台仪器已关联分类标签\n\n";
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}
