#!/usr/bin/env php
<?php
require "base.php";
/*
LAB_ID=nankai SITE_ID=cf php export_data_to_csv.php
*/
////////////////////////////////////////
function select($value) {
	if (!is_array($value)) {
		return $value;
	}
	$i = 1;
	foreach ($value as $title=>$v) {
		echo "\t$i. $title\n";
		$i++;
	}
	fwrite(STDOUT, '请选择要执行的操作：');
	$n = (int) trim(fgets(STDIN));
	reset($value);
	for ($j=0; $j<$n-1; $j++) {
		next($value);
	}
	$ret = current($value);
	$key = key($value);
	echo "$key($ret)\n";
	return select($ret);
}

function csv($file, $objects, $cols, $header=null, $footer=null, $mode='w+') {
	/*
	 * $header = $footer = array(array('a','b','c'), array(...)...)
	 */
	$file = MY_ROOT_PATH . $file;
	$csv = new CSV($file, $mode);
	if (is_array($header)) foreach ($header as $row) {
		$csv->write($row);
	}
	foreach ($objects as $object) {
		$content = object_to_array($object, $cols);
		$csv->write($content);
	}
	if (is_array($footer)) foreach ($footer as $row) {
		$csv->write($row);
	}
	$csv->close();
}

function object_to_array($object, $cols) {
	/*
	 * $cols = arry(
	 *	'dastart'=>'date_format',
	 *	'amount',
	 *	'user'=>array('name')
	 * );
	 * */
	$content = [];
	foreach ($cols as $key=>$col) {
		if (!is_array($col)) {
			if (!is_numeric($key)) {
				$value = $col($object->$key);
			}
			else {
				$value = $object->$col;
			}
			$content[] = $value;
		}
		else {
			$key_content = object_to_array($object->$key, $col);
			$content = array_merge($content, $key_content);
		}
	}
	return $content;
}

function time2string($date) {
	return date('Y-m-d H:i:s', $date);
}

function prepare_directory_env() {
	$dir = __DIR__ . DIRECTORY_SEPARATOR . 'export' . DIRECTORY_SEPARATOR;
	FILE::check_path($dir.'foo');
	define('MY_ROOT_PATH', $dir);
}
////////////////////////////////////////
$list = [
	'仪器收费信息'=>'equipment_charge_info',
	'课题组使用仪器收费信息'=>'lab_charge_info',
];

function equipment_charge_info($start, $end) {
	$equipments = Q('equipment');
	if (!count($equipments)) die("系统中没有任何仪器\n");
	$cols = ['dtstart'=>'time2string','dtend'=>'time2string','amount','user'=>['name'],'lab'=>['name']];
	$header = [
		['开始时间：' . date('Y-m-d H:i:s', $start)],
		['结束时间：' . date('Y-m-d H:i:s', $end)]
	];
	foreach ($equipments as $equipment) {
		$charges = Q("eq_charge[equipment=$equipment][dtstart>=$start][dtend<=$end]");
		$samples= Q("eq_sample[equipment=$equipment][dtstart>=$start][dtend<=$end]");

		$transactions = [];

		foreach ($charges as $charge) {
			$transaction = $charge->transaction;
			$transaction->dtstart = $charge->dtstart;
			$transaction->dtend = $charge->dtend;
			$transaction->amount = $charge->amount;
			$transaction->user = $charge->user;
			$transaction->lab = $charge->lab;
			$transactions[] = $transaction;
		}

		foreach ($samples as $sample) {
			$transaction = $sample->transaction;
			$transaction->dtstart = $sample->dtstart;
			$transaction->dtend = $sample->dtend;
			$transaction->amount = $sample->amount;
			$transaction->user = $sample->sender;
			$transaction->lab = $sample->lab;
			$transactions[] = $transaction;
		}

		if (count($transactions)) {
			$name = $equipment->name;
			$number = str_replace('/', ' ', $equipment->ref_no);
			$file_name = "{$name}[{$number}].csv";
			$amount = 0;
			foreach ($transactions as $transaction) {
				$amount += $transaction->amount;
			}
			$my_header = $header;
			$my_header[] = ["消费金额：￥$amount"];
			echo "\t$file_name\n";
			csv($file_name, $transactions, $cols, $my_header);
		}
	}
}

function lab_charge_info($start, $end) {
	$equipments = Q('equipment');
	if (!count($equipments)) die("系统中没有任何仪器\n");
	$labs = Q('lab');
	if (!count($labs)) die("系统中没有任何课题组\n");
	$cols = ['dtstart'=>'time2string','dtend'=>'time2string','amount','user'=>['name']];
	$header = [
		['开始时间：' . date('Y-m-d H:i:s', $start)],
		['结束时间：' . date('Y-m-d H:i:s', $end)]
	];
	foreach ($labs as $lab) {
		foreach ($equipments as $equipment) {
			$charges = Q("eq_charge[equipment=$equipment][lab=$lab][dtstart>=$start][dtend<=$end]");
			$samples= Q("eq_sample[equipment=$equipment][dtstart>=$start][dtend<=$end]");

			$transactions = [];

			foreach ($charges as $charge) {
				$transaction = $charge->transaction;
				$transaction->dtstart = $charge->dtstart;
				$transaction->dtend = $charge->dtend;
				$transaction->amount = $charge->amount;
				$transaction->user = $charge->user;
				$transaction->lab = $charge->lab;
				$transactions[] = $transaction;
			}

			foreach ($samples as $sample) {
				$transaction = $sample->transaction;
				$transaction->dtstart = $sample->dtstart;
				$transaction->dtend = $sample->dtend;
				$transaction->amount = $sample->amount;
				$transaction->user = $sample->sender;
				$transaction->lab = $sample->lab;
				$transactions[] = $transaction;
			}

			if (count($transactions)) {
				$name = $equipment->name;
				$number = str_replace('/', ' ', $equipment->ref_no);
				$file_name = "{$lab->name}[{$lab->owner->name}]@{$name}[{$number}].csv";
				$amount = 0;
				foreach ($transactions as $transaction) {
					$amount += $transaction->amount;
				}
				$my_header = $header;
				$my_header[] = ["消费金额：￥$amount"];
				echo "\t$file_name\n";
				csv($file_name, $transactions, $cols, $my_header);
			}
		}
	}
}

$func = select($list);
if (function_exists($func)) {
	fwrite(STDOUT, '请输入开始时间, 默认从最早开始：');
	$start = trim(fgets(STDIN));
	$start = strtotime($start);
	$start = $start ?: strtotime('2010-01-01 00:00:00');
	fwrite(STDOUT, '请输入结束时间， 默认为当前时间：');
	$end = trim(fgets(STDIN));
	$end = strtotime($end);
	$end = $end ?: time();
	prepare_directory_env();
	call_user_func_array($func, [$start, $end]);
}

