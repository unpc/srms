#!/usr/bin/env php
<?php 
/**
 * @此脚本需要在一卡通导入之后再执行
 * @将实验室批量导入
 * @如果实验室不存在，则创建一个新实验室
 * @用法: SITE_ID=%s LAB_ID=%s ./lab_batch_process.php %s.csv
 * @备注: 课题组的名称和课题组负责人的组合不能出现重复，出现重复只会添加一条
 */

require 'base.php';

$filename = $argv[1];

if (file_exists($filename)) {

	$csv = new CSV($filename, 'r');
	
	$lab = new Lab_Batch();
	
	$header = TRUE;
	
	while ($row = $csv->read(',')) {
				
		if ($header == TRUE) {
			$header = FALSE;
			continue;
		}

		if ($row[0]) {

			$lab->add($row);
		}
	}

	$lab->count();	

}
else {
	echo 'Sorry! The file doesn\'t exists!';
}


class Lab_Batch {

	private $total = 0;
	private $fail = 0;
	private $count = 0;
	
	public function add($row=[]) {
		$this->total++;
    	$name = str_replace(' ','',$row[0]);
		$owner_name = str_replace(' ','',$row[1]);
		$owner = O('user', ['name'=>$owner_name]);

		if (Q("lab[name=$name][owner=$owner]")->total_count() > 0) {
		    $lab = O('lab', ['name'=>$name, 'owner'=>$owner]);
		}
		else{
			//添加实验室
			$lab = O('lab');
			$lab->name = $name;
			$lab->owner = $owner;
		}

		if ($lab->save()) {
			$this->count++;
			echo "\033[1;40;32m";
			echo $name." => ".$owner->name."[{$owner->id}]" ."\n";
			echo "\033[0m";
		}
		else {
			$this->fail++;
			echo "\033[1;40;31m";
			echo $name.'导入失败'."\n";
			echo "\033[0m";
		}
	}	

	public function count() {
		echo '共有'.$this->total.'条数据，'.'更新了'.$this->count.'条数据'."\n";
		if ($this->fail) {
			echo $this->fail.'条数据更新失败'."\n";
		}
	}
}
