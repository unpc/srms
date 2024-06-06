<?php
  /*
	导出仪器信息
	(xiaopei.li@2011.09.10)
  */
require 'base.php';

$charge_type = [
	"free"					=>	"免费使用",
	"time_reserv_record"	=>	"智能计费:综合预约 / 使用时间智能计费 (推荐)",
	"only_reserv_time"		=>	"按预约情况计费:按预约时间",
	"custom_reserv"			=>	"按预约情况计费:自定义",
	"record_time"			=>	"按实际使用情况计费:按使用时间",
	"record_times"			=>	"按实际使用情况计费:按使用次数",
	"record_samples"		=>	"按实际使用情况计费:按样品数",
	"custom_record"			=>	"按实际使用情况计费:自定义",
	"advanced_custom"		=>	"高级计费:高级自定义",
	"no_charge_sample"		=>	"免费检测",
	"sample_count"			=>	"按样品数",
	"custom_sample"			=>	"自定义"
 
];

$control_mode = [
	'nocontrol'		=>		"不控制",
	'power'			=>		"电源控制",
	'computer'		=>		"电脑控制"
];

$equipments = Q('equipment');

$output = new CSV('eq.csv', 'w');

$output->write(
	[
		'仪器ID',
		'仪器编号',
		'仪器名称',
		'仪器价格',
		'控制方式',
		'接受送样',
		'送样计费',
		'送样计费规则',
		'接受预约',
		'预约与使用计费',
		'预约与使用计费规则',
	]
);

foreach ($equipments as $e) {
	$charge_setting = EQ_Charge::get_charge_setting($e);
	$reserv_setting = $charge_setting['reserv']['*'] ? : ($charge_setting['record']['*'] ? : []);
	$sample_setting = $charge_setting['sample']['*'];
	$output->write(
			[
				$e->id,
				$e->ref_no,
				$e->name,
				$e->price,
				$control_mode[$e->control_mode ? : "nocontrol"],
				$e->accept_sample ? '接受' : '不接受',
				$e->accept_sample ? ($charge_type[
					$e->charge_template['sample'] 
					? : "no_charge_sample"
				]) : "",
				"开机费：" . $sample_setting['minimum_fee'] . " 每样品收费：" . $sample_setting['unit_price'],
				$e->accept_reserv ? '接受' : '不接受',
				$e->accept_reserv ? ($charge_type[
					$e->charge_template['reserv'] 
					? : (
							$e->charge_template['record']
							 ? : "free"
						)
				]) : "",
				"开机费：" . $reserv_setting['minimum_fee'] . " 每小时收费：" . $reserv_setting['unit_price'],
			]
		);
}

$output->close();
