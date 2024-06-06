#!/usr/bin/env php
<?php 
  /**
   * @file   import_card.php
   * @author Xiaopei Li <xiaopei.li@gmail.com>
   * @date   2011.08.21
   * 
   * @brief  import card info
   * 1. 输入的csv文件应为","分隔，无文本符
   * 2. 第一行为表头，表头应为英文
   * 3. 表必须有ref和card列，但顺序无关，其他列也会被当作附加属性记录
   * eg. "name,ref,card,member_type"
   *
   * usage: SITE_ID=cf LAB_ID=test ./import_card.php card.csv
   * 
   */

require '../base.php';

$input_file = $argv[1];

if (!($input_file && file_exists($input_file))) {
	print("usage: SITE_ID=cf LAB_ID=test ./import_card.php card.csv\n");
	die;
}

$time = date('Ymdhis', time());

// 数据库备份文件名
$dbfile = LAB_PATH . 'private/backup/import_card_backup_'.$time.'.sql';

// 导入结果文件名
$result_file = LAB_PATH . 'private/backup/import_card_report_'.$time.'.csv';

$u = new Upgrader;

// 检查是否需要升级
$u->check = function() {
	return TRUE;
};

// 备份
$u->backup = function() use($dbfile) {
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "备份数据库");

	File::check_path($dbfile);
	$db = Database::factory();
	return $db->snapshot($dbfile);
};

// 恢复
$u->restore = function() use($dbfile) {
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "恢复数据库");

	$db = Database::factory();
	$db->restore($dbfile);
};

// 升级
$u->upgrade = function() use ($input_file, $result_file) {
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "导入数据");

	$input = new CSV($input_file, 'r');

	$card_total = 0; // 总共的条数
	$card_new = 0; // 新建的条数
	$card_update = 0; // 已存在、更新了的条数
	$card_failed = 0; // 保存失败的条目
	$failed_rows = []; // 失败的行

	$options = []; // $title => $index，记录一些附加属性

	// 处理表头
	$header = $input->read(',');
	foreach ($header as $header_index => $header_title) {
		if (trim($header_title) == 'ref') {
			$ref_index = $header_index;
		}
		else if (trim($header_title) == 'card') {
			$no_index = $header_index;
		}
		else {
			$options[trim($header_title)] = $header_index;
		}
	}

	// 处理数据
	while ($row = $input->read(',')) {
		$card_total++;

		$ref = trim($row[$ref_index]);
		$no = trim($row[$no_index]);

		$card = O('card', ['ref' => $ref]);
		if ($card->id) {
			// 更新已有此ref的card记录
			$card->no = $no;
			foreach ($options as $key => $index) {
				$card->$key = trim($row[$index]);
			}

			if ($card->save()) {
				$card_update++;
			}
			else {
				$card_failed++;
				$failed_rows[] = $row;
			}
		}
		else {
			// 新建新的card记录
			$card->ref = $ref;
			$card->no = $no;
			foreach ($options as $key => $index) {
				$card->$key = trim($row[$index]);
			}

			if ($card->save()) {
				$card_new++;
			}
			else {
				$card_failed++;
				$failed_rows[] = $row;
			}
		}
		if ($card_total % 100 == 0) {
			print('.');
		}
	}
	$input->close();

	// 汇报结果
	printf("\n=============\n");
	printf("共处理%d条信息\n", $card_total);
	printf("新建%d条信息\n", $card_new);
	printf("更新%d条信息\n", $card_update);
	if ($card_failed) {
		printf("导入失败%d条信息\n", $card_failed);

		$result = new CSV($result_file, 'w');
		$result->write($header);
		foreach ($failed_cards as $failed_card) {
			$result->write($failed_card);
		}
		$result->close();

		
		printf("失败数据已存入%s", $result_file);

		return FALSE;
	}

	return TRUE;
};

$u->verify = function() {
	Upgrader::echo_success('导入成功');
	return TRUE;
};

$u->post_upgrade = function() {};

$u->run();