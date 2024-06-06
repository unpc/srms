<?php
  /*
	导出仪器信息
	(xiaopei.li@2011.09.10)
  */
require 'base.php';

$equipments = Q('equipment');

$output = new CSV('eq.csv', 'w');
$output->write(
	[
		'id',
		'ref no',
		'name',
		'control mode',
		'location',
		'location2',
		'contact',
	]
);

foreach ($equipments as $e) {
	$contacts = Q("{$e} user.contact")->to_assoc('id', 'name');

	$output->write(
		[
			$e->id,
			$e->ref_no,
			$e->name,
			$e->control_mode,
			$e->location,
			$e->location2,
			join('/', $contacts),
			$e->group->name
			]
		);
}

$output->close();
