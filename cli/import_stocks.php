#!/usr/bin/env php
<?php
    /*
     * file import_stocks.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2013-11-11
     *
     * useage SITE_ID=lab LAB_ID=demo php import_stocks.php xxx.csv
     * brief 调用该方法进行stocks的批量导出, 需传入用于导入stocks的csv文件
     */

require 'base.php';

$file = $argv[1];

if (!File::exists($file)) {
    die("Usage: SITE_ID=lab LAB_ID=xx php import_stocks.php xxx.csv \n");
}

$csv = new CSV($file, 'r');

//表头, 跳过
$line = $csv->read();

//进行获取
/* csv文件内列内结构如下:
 [0] => 产品名称    //product_name
 [1] => 自定义编号  //ref_no
 [2] => 生产商      //manufacturer
 [3] => 经销商      //distributor
 [4] => 目录号      //catalog_no
 [5] => 型号        //model
 [6] => 规格        //spec
 [7] => 单价        //unit_price
 [8] => 条码        //barcode
 [9] => 存量        //quantity
 [10] => 总量       //summation
 [11] => 存放位置   //location
 [12] => 库存状态   //status
 [13] => 标签       //tags
 [14] => 备注       //note
 [15] 合并已有存货  //stock

 注:
    1、存货添加时间默认为当前时间
    2、存货创建人为Lab的PI
    3、如果标签为多个标签, 填写时需要使用半角","进行分割, 例如: A,B,C
    4、存货状态可选值为:
        1 不详
        2 充足
        3 紧张
        4 用磬
 */
$ref_no_max_length = Config::get('stock.default_ref_no_length') ? : 8;
while (count($line = $csv->read())) {
	//ref_no is unique, inspect whether or not the stock exists already first.
	if ($line[1]) {
		if (strlen($line[1]) > $ref_no_max_length) {
			echo strtr("\033[31m导入存货 %product_name 失败!\033[0m\n", [
		            '%product_name'=> $line[0],
		        ]);
				continue;
		}
		else {
			$ref_no_exist = O('stock', ['ref_no' => $line[1]]);
			if ($ref_no_exist->id) {
				echo strtr("\033[31m导入存货 %product_name 失败!\033[0m\n", [
		            '%product_name'=> $line[0],
		        ]);
				continue;
			}
		}
	}

    $stock = O('stock');
    $stock->product_name = trim($line[0]); //产品名称
    if (!$stock->product_name) continue;
    $stock->ref_no = $line[1] ? : NULL; //自定义编号
    $stock->manufacturer = $line[2]; //生产商
    $stock->distributor = $line[3]; //经销商
    $stock->catalog_no = $line[4]; //目录号
    $stock->model = $line[5]; //型号
    $stock->spec = $line[6]; //规格
    $stock->unit_price = $line[7]; //单价
    $stock->barcode = $line[8]; //条码
    $stock->quantity = $line[9]; //存量
    $stock->summation = max($line[10], $line[9]);   //总量 必须大于存量
    $stock->location = $line[11];   //存放位置
    $stock->status = array_key_exists($line[12], Stock_Model::$stock_status) ? $line[12] : Stock_Model::UNKNOWN; //库存状态
    $stock->note = $line[14] ? : ''; //备注
    $stock->creater = O('user', ['token' => Lab::get('lab.pi')]);

    //设定stock的过期时间为永不过期
    $stock->expire_status = Stock_Model::$never_expired;

    $stock->save();

    if ($stock->id) {
        if (count(array_filter(explode(',', $line[13])))) Tag_Model::replace_tags($stock, array_filter(explode(',', $line[13])), 'inventory', true);
        $parent = null;
        $other_info = '';
        if (trim($line[15])) {
	        $parent_stocks = Q(strtr("stock[product_name=%product_name]:sort(id A)", [
	        	'%product_name' => Q::quote($line[15])
	        ]));
	        
	        if ($parent_stocks->total_count()) {
	        	$collection_parent_stocks = $parent_stocks->find("[is_collection>0]:sort(id A)");
	        	if ($collection_parent_stocks->total_count()) {
		        	$parent = $collection_parent_stocks->current();
		        	$stock->parent = $parent;
	        	}
	        	else {
		        	$parent = $parent_stocks->current();
		        	$stock->merge($parent);
	        	}
	        	$other_info = strtr('合并入存货 %parent_name[%parent_id] 成功!', [
	            		'%parent_name' => $parent->parent->name,
						'%parent_id' => $parent->parent->id,
				]); 
	        }
	        
        }
      
        if (trim($line[15]) && $stock->parent->id == $stock->id) {
	        $other_info = strtr('合并失败, 未能找到存货 %product_name!', [
        		'%product_name' => $line[15],
        	]);
            $stock->parent = $stock;
        }
        
        $stock->save();

        echo strtr("\033[32m导入存货 %product_name[%id]成功! %other_info\033[0m\n", [
            '%product_name'=> $stock->product_name,
            '%id'=> $stock->id,
            '%other_info' => $other_info,
        ]);
    }
    else {
        echo strtr("\033[31m导入存货 %product_name 失败! %other_info\033[0m\n", [
            '%product_name'=> $line[0],
            '%other_info' => $other_info,
        ]);
    }
}

$csv->close();
