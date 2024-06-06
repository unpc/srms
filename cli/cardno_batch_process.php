#!/usr/bin/env php
<?php 
/**
 * @将一卡通用户批量导入
 * @如果用户存在，则更正一卡通信息
 * @如果用户不存在，则创建一个用户，然后更正一卡通信息 
 * @用法: SITE_ID=%s LAB_ID=%s ./cardno_batch_process.php %s.csv
 * @备注：一卡通帐号姓名不能重名，重名的只会有一个被添加 
 */

require 'base.php';

$filename = $argv[1];

if (file_exists($filename)) {

	$csv = new CSV($filename, 'r');
	
	$card = new Cardno_Batch();
	
	$header = TRUE;
	
	while ($row = $csv->read(',')) {
				
		if ($header == TRUE) {
			$header = FALSE;
			continue;
		}

		if ($row[0]) {

			$card->update($row);
		}
	}

	$card->count();	

}
else {
	echo 'Sorry! The file doesn\'t exists!';
}


class Cardno_Batch {

	private $fail = 0;
    private $total = 0;	
	private $count = 0;
	private $suffix = '|ids.nankai.edu.cn';
	
	public function update($row=[]) {
		$this->total++;
		$name = str_replace(' ', '', $row[0]);
		$token_card = str_replace([' ', '*'], '', $row[1]);
		$token = $token_card.$this->suffix;
		$card_no = str_replace(' ', '', $row[2]) + 0;
		$email = str_replace(' ', '', $row[3]);
        $lab_name = str_replace(' ', '', $row[4]);
        		
		if (!Q("user[name=$name]")->total_count()) {
			//添加新成员
			$user = O('user');
			$user->name = $name;
			$user->email = $email;
			$user->ctime = time();
		}
		else {
			echo "\033[1;40;31m". $name ."已有同名用户\n". "\033[0m";
			// continue;
			$user = O('user', ['name'=>$name]);
		}

		if ($token_card) {
			$user->token = $token;
		}

		if ($card_no) {
			$user->card_no = $card_no;
			$user->card_no_s = $card_no & 0xffffff;
		}

		$user->mtime = time();
		$user->atime = time();

		if ($user->save()) {
			if ($lab_name) {
				$lab = O('lab', ['name'=>$lab_name]);
				$user->connect($lab);
			}
			$this->count++;
			echo "\033[1;40;32m";
			echo $name.' ==> '.$token.' ==> '.$card_no."\n";
			echo "\033[0m";
		}
		else {
			$this->fail++;
			echo "\033[1;40;31m". $name ."导入失败\n". "\033[0m";
		}
	}	

	public function count() {
		echo '总共有'.$this->total.'条数据，'.'更新了'.$this->count.'条数据'."\n";
		if ($this->fail) {
			echo '有'.$this->fail.'条数据导入失败'."\n";
		}
	}
}
