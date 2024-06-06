<?php

require 'base.php';

$tmpfname = tempnam("/tmp", "offline_passwd");

$handle = fopen($tmpfname, "w");
$eqs = Q('equipment[control_mode=computer]');
if ($eqs->total_count()) {
	foreach ($eqs as $eq) {
		fwrite($handle, "{$eq->name}[{$eq->id}]\t{$eq->offline_password}\n");
	}
}
else {
	fwrite($handle, "无仪器使用离线密码\n");
}

fclose($handle);

$lab_name = Config::get('lab.name');
$page_title = Config::get('page.title_default');

$title = "$lab_name ($page_title) 所有仪器离线密码";
$content = file_get_contents($tmpfname);

echo $content;

$email = new Email;

$receiver =[
	'support@geneegroup.com',
	'maintain@geneegroup.com',
	];
$email->to($receiver);
$email->subject($title . date(' Y/m/d'));
$email->body($content);

$email->send();

unlink($tmpfname);
