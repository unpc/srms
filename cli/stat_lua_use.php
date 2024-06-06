#!/usr/bin/env php
<?php
require 'base.php';

/*
====== 脚本说明 ======

此脚本现用来统计系统中对 lua 脚本的使用,
当前只有仪器使用 lua, 但以后若有其他地方加入 lua,
也可按仪器统计的方式扩展此脚本.
(xiaopei.li@2012-05-31)

===== 统计仪器 lua 使用 =====
==== 条件 ====

1. cf 中仪器 lua 脚本的用处有 3 点:

1) 预约限制; // 仅 reserv_script, 无开关
2) 使用计费; // charge_script, 开关为 charge_mode
3) 送样计费; // sample_charge_script, 开关为 sample_charge_mode

2. 以上每点都可设置脚本, 但设置脚本不一定就用(如设置过自定义预约脚本, 但未勾选使用) // 当前检查无此问题, 但需保留此机制备用

3. 脚本以附加属性保存.

==== 解决方法 ====

根据以上三个条件, 计划解决方法如下:

1. 编写一个 php 脚本, 遍历仪器, 若仪器属性中至少有一种脚本, 则按以下格式输出此仪器信息;
仪器id, 仪器名称, 负责人姓名, 预约脚本, 是否使用预约脚本, 使用计费脚本, 使用计费脚本方式, 送样计费脚本, 送样计费方式

2. 在各项目运行此脚本, 得到统计结果;

3. 手动用电子表格合并结果, 表头如下
项目, 仪器id, 仪器名称, 负责人姓名, 预约脚本, 是否使用预约脚本, 使用计费脚本, 使用计费脚本方式, 送样计费脚本, 送样计费方式

4. 另可以如下目录结构保存 lua 文件
项目号/仪器号/resev.lua
项目号/仪器号/usage.lua
项目号/仪器号/sample.lua

*/


function eq_use_lua($eq) {
	return ($eq->reserv_script ||
			$eq->charge_script ||
			$eq->sample_charge_script);
}

function eq_stat_append($eq) {
	return;

	// 脚本有换行, 不便输出

	$eq_stat_fields = ['id', 'name', 'reserve_script', 'charge_script', 'charge_mode', 'sample_charge_script', 'sample_charge_mode'];

	$text_wrapper = '';
	$field_separator = "\t";
	$line_separator = "\n";

	foreach ($eq_stat_fields as $field) {
		echo $text_wrapper;
		echo $eq->{$field};
		echo $text_wrapper;
		echo $field_separator;
	}

	echo $line_separator;
}

function eq_stat_print() {
	echo "done\n";
}

function eq_mk_file($eq) {
	$base_dir = '/tmp/' . getenv('LAB_ID');
	$eq_dir = $base_dir . '/' . $eq->id;

	@mkdir($eq_dir, 0755, TRUE);

	if ($eq->reserv_script) {
		$reserv_lua = $eq_dir . '/' . 'reserv.lua';
		file_put_contents($reserv_lua, $eq->reserv_script);
	}

	if ($eq->charge_script) {
		$charge_lua = $eq_dir . '/' . 'charge.lua';
		if ($eq->charge_mode != EQ_Charge::CHARGE_MODE_CUSTOMIZE) {
			$charge_lua .= '.bak';
		}

		file_put_contents($charge_lua, $eq->charge_script);
	}

	if ($eq->sample_charge_script) {
		$sample_charge_lua = $eq_dir . '/' . 'sample_charge.lua';
		if ($eq->sample_charge_mode != EQ_Sample_Model::CHARGE_MODE_CUSTOMIZE) {
			$charge_lua .= '.bak';
		}

		file_put_contents($sample_charge_lua, $eq->sample_charge_script);
	}

}

/******** main ********/

// 统计仪器
foreach (Q('equipment') as $eq) {
	if (eq_use_lua($eq)) {
		eq_stat_append($eq);
		eq_mk_file($eq);
	}
}

eq_stat_print();
