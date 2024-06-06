#!/usr/bin/env php
<?php
/*
  在串口服务器能连到服务器, 但所有传感器都不正常时,
  可用此脚本检测基站(871)是否活着及其频道;

  步骤如下:
  0. 现场短接 871 左1 和 左2 线 !!!!
  1. 修改 xinetd 配置, 让 tszz 转至本脚本, 即
     server = /usr/share/lims2/cli/check_871.php
  2. 确保此脚本 xinetd 配置所述 user 或 group 可执行
  3. 重启 xinetd
  4. kill 相应的 php, 使 tszz 重连
  5. tail /var/log/php5/cli.log

  命令 ： AA 40 00 00 BB <发送命令之前需要短接z-871机站设备的L1、L2线>
  返回 ： AA 4F [发射功率] [发射频道] [信号强度] BB

  (xiaopei.li@2013-02-05)
*/

$check_871_cmd = chr(0xAA) . chr(0x40) . chr(0x00) . chr(0x00) . chr(0xBB);

while (1) {

	log_command($check_871_cmd);
	$ret = @fwrite(STDOUT, $check_871_cmd);
	if ($ret === FALSE) {
		error_log('lost connection');
	}

	sleep(1); // wait for recv

	$raw = @fread(STDIN, 1048576);
	log_command($raw, TRUE);

	sleep(10); // wait for next check
}

function log_command( $data, $recv = FALSE ) {
	if ($recv) {
		$log_mode = "RECV <== ";
	}
	else {
		$log_mode = "SEND ==> ";
	}

	$hex = '';
	for($i=0; $i<strlen($data); $i++) {
		$hex .= sprintf("%02X ", ord($data[$i]));
	}

	error_log($log_mode . ' ' . $hex);
}
