<?php
  /*
	导出用户
	(xiaopei.li@2012-12-13)
  */
require 'base.php';

$output = new CSV('people.csv', 'w');
$output->write(
	[
		'id',
		'token',
		'name',
		'phone',
		'email',
		'ref_no',
		'is_active',
	]
);
/*
$member_types = array(
	0=>'本科生',
	1=>'硕士研究生',
	2=>'博士研究生',
	3=>'其他学生',
	10=>'课题负责人(PI)',
	11=>'科研助理',
	12=>'PI助理/实验室管理员',
	13=>'其他教师',
	20=>'技术员',
	21=>'博士后',
	22=>'其他',
	);
*/

foreach (Q('user') as $u) {
	$output->write(
		[
			$u->id,
			$u->token,
			$u->name,
			$u->phone,
			$u->email,
			$u->ref_no,
			$u->atime ? 'TRUE' : 'FALSE',
			]
		);
}

$output->close();
