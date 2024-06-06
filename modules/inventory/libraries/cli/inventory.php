<?php

class CLI_Inventory {

	static function import_stocks($file=null) {
	    /*
	     * file import_stocks.php
	     * author Rui Ma <rui.ma@geneegroup.com>
	     * date 2013-11-11
	     *
	     * useage SITE_ID=lab LAB_ID=demo php import_stocks.php xxx.csv
	     * brief 调用该方法进行stocks的批量导出, 需传入用于导入stocks的csv文件
	     */

		if (!File::exists($file)) {
		    die("Usage: SITE_ID=lab LAB_ID=xx php cli.php invenroty import_stocks xxx.csv \n");
		}

		$csv = new CSV($file, 'r');

		//表头, 跳过
		$line = $csv->read();

		//进行获取
		/* csv文件内列内结构如下:
		 [0] => 产品名称    //product_name
		 [1] => 自定义编号  //ref_no
		 [2] => 生产商      //manufacturer
		 [3] => 供应商      //vendor
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

		while (count($line = $csv->read())) {

		    $stock = O('stock');
		    $stock->product_name = $line[0]; //产品名称
		    if (!$stock->product_name) continue;
		    $stock->ref_no = $line[1]; //自定义编号
		    $stock->manufacturer = $line[2]; //生产商
		    $stock->vendor = $line[3]; //供应商
		    $stock->catalog_no = $line[4]; //目录号
		    $stock->model = $line[5]; //型号
		    $stock->spec = $line[6]; //规格
		    $stock->unit_price = $line[7]; //单价
		    $stock->barcode = $line[8]; //条码
		    $stock->quantity = $line[9]; //存量
		    $stock->summation = max($line[10], $line[9]);   //总量 必须大于存量
		    $stock->location = $line[11];   //存放位置
		    $stock->status = in_array($line[12], Stock_Model::$stock_status) ? $line[12] : Stock_Model::UNKNOWN; //库存状态
		    $stock->note = $line[14]; //备注
		    $stock->creater = O('user', ['token' => Lab::get('lab.pi')]);

		    $stock->save();

		    if ($stock->id) {
		        Tag_Model::replace_tags($stock, explode(',', $line[13]), 'inventory', true);
		        $parent = O('stock', ['product_name'=> $line[15]]);
		        if ($parent->id) {
		            $stock->parent = $parent;
		            $stock->save();
		        }
		        echo strtr("\033[32m导入存货 %product_name[%id]成功!\033[0m\n", [
		            '%product_name'=> $stock->product_name,
		            '%id'=> $stock->id
		        ]);
		    }
		    else {
		        echo strtr("\033[31m导入存货 %product_name 失败!\033[0m\n", [
		            '%product_name'=> $line[0]
		        ]);
		    }
		}

		$csv->close();
	}
	
    static function expiration_notify() {

        $users = Lab::get('stock.default.expire_inform_people', []);

        if (count($users)) {

            $neverexpire = Stock_Model::$never_expired;

            //分页加载存货，拼接提醒所需的存货过期时间和存货名称
            $per_page = 50;
            $start = 0;

            $now = Date::time();
            $now_start = Date::get_day_start($now);
            $now_end = $now_start + 86399;

            //今天之后过期的stock
            $stocks = Q("stock[expire_status!={$neverexpire}][expire_time>={$now_start}]");
            
            while(TRUE) {

                $pstocks = $stocks->limit($start, $per_page);

                if (!count($pstocks)) break;

                foreach($pstocks as $stock) {
                    $expire_time = $stock->expire_time;
                    $notice_time = $stock->expire_notice_time;
                    $almost_expire_time = $expire_time - $notice_time;
                    /*即将过期、过期当天才提醒一次*/
                    if (($expire_time >= $now_start && $expire_time <= $now_end) || 
                    	($almost_expire_time >= $now_start && $almost_expire_time <= $now_end)) {
                    	
                    	if ($expire_time >= $now_start && $expire_time <= $now_end) {
                        	$expires_on = I18N::T('inventory', '今天');
	                    }
	                    else {
	                        $expires_on = I18N::T('inventory', '%days天后', ['%days' => ($expire_time - $now_end) / 86400]);
	                    }
	
	                    //发送消息提醒
	                    foreach($users as $id =>$name) {
	                        $user = O('user', $id);
	                        if ($user->id) {
	                            Notification::send('stock.expiration', $user, [
	                                '%user' => Markup::encode_Q($user),
	                                '%product_name' => Markup::encode_Q($stock),
	                                '%expires_on' => $expires_on,
	                                '%date' => Date::format($expire_time, 'Y/m/d'),
	                            ]);
	                        }
	                    }
	                    	
                    }
                }

                $start += $per_page;
            }
        }
    }
	
	static function expiration_inspect() {

        //分页加载存货
        $neverexpire = Stock_Model::$never_expired;
        $expired = Stock_Model::$has_expired;

        $now = Date::time();
        $now_end = Date::get_day_end($now);

        //获取最后一次过期时间
        $last_time = Lab::get('inventory.last_expiration_inspect_time', 0);

        $per_page = 50;
        $start = 0;

        $stocks = Q("stock[is_collection=0][expire_status!=$neverexpire][mtime>{$last_time}]:sort(id)");

        while(TRUE) {
            //查找最后一次执行时间之后创建的stock进行判断即可
            //查找ctime在last_time之后的设定为可过期的存货
            $pstocks = $stocks->limit($start, $per_page);

            if (!count($pstocks)) break;

            foreach($pstocks as $stock) {

                //过期时间
                $expire_time = $stock->expire_time;

                //提前提醒时间
                $notice_time = $stock->expire_notice_time;

                //过期时间小于当前时间
                //则已过期
                if ($expire_time <= $now) {
                    $stock->expire_status = Stock_Model::$has_expired;
                }
                elseif (($expire_time - $now_end) <= $notice_time) {
                    $stock->expire_status = Stock_Model::$almost_expired;
                }

                $stock->save();
            }

            $start += $per_page;
        }

        //统计'合集'下各状态存货的数量, 已确定'合集'存货的状态
        $has_expired = Stock_Model::$has_expired;
        $not_expired = Stock_Model::$not_expired;
        $almost_expired = Stock_Model::$almost_expired;

        //重新进行分页限制
        $start = 0;
        $per_page = 50;

        $collection_stocks = Q('stock[is_collection=1]');

        while(TRUE) {

            $pcollection_stocks = $collection_stocks->limit($start, $per_page);

            if (!count($pcollection_stocks)) break;

            foreach($pcollection_stocks as $collection_stock) {

                $collection_stock->has_exd_num = Q("stock[parent={$collection_stock}][expire_status={$has_expired}]:limit(1)")->total_count();

                $collection_stock->almost_exd_num = Q("stock[parent={$collection_stock}][expire_status={$almost_expired}]:limit(1)")->total_count();

                $collection_stock->not_exd_num = Q("stock[parent={$collection_stock}][expire_status={$not_expired}]:limit(1)")->total_count();

                $collection_stock->save();
            }

            $start += $per_page;
        }

        //设定时间
        Lab::set('inventory.last_expiration_inspect_time', $now);
    }

    static function create_stocks($file) {
    	$now = Date::time();

        if (!($file && file_exists($file))) {
            print("usage: SITE_ID=cf LAB_ID=test php cli.php inventory create_stocks o.csv\n");
            die;
        }

        $csv = new CSV($file, 'r');
        $csv->read(',');
        $total_count = $success_count = 0;

        while ($row = $csv->read(',')) {
        	$total_count ++;
        	if (!$row[0]) continue;
        	$ref_no = $row[1];
        	if ($ref_no && O('stock', ['ref_no'=>$ref_no])->id) continue;
        	foreach (Stock_Model::$stock_status as $k => $label) {
        		if ($label == $row[12]) {
        			$status = $k;
        			break;
        		}
        	}

        	$stock = O('stock');
            $stock->ref_no = $ref_no ? : NULL;
			$stock->product_name = $row[0];
			$stock->manufacturer = trim($row[2]);
			$stock->vendor = O('vendor', ['name'=>$row[3]])->id ? $row[3] : '';
			$stock->catalog_no = trim($row[4]);
			$stock->model = $row[5];
			$stock->spec = $row[6];
			$stock->unit_price = $row[7];
			$stock->barcode = strtoupper(trim($row[8]));
			$stock->quantity = (int)$row[9];
            $stock->summation = (int)$row[10];
			$stock->location = trim($row[11]);
			$stock->status = (int)$status ?: Stock_Model::UNKNOWN;
			if ($row[13]) {
				$stock->expire_time = Date::get_day_end(strtotime($row[13]));
				$stock->expire_notice_time = (int)Config::get('stock.stock.default.expire_notice_days') * 86400;
				$stock->expire_status = Stock::get_stock_expire_status($stock);
			}
			else {
				$stock->expire_status = Stock_Model::$never_expired;
			}
			
			$stock->note = trim($row[15]);

			if ($stock->save()) {
				/*
				//暂时不需要使用加入已有存货字段，该操作不适合批量操作
				$merge_name = trim($row[16]);
				if ($merge_name) {
                    $stock_merge = O('stock', ['product_name' => $merge_name]);
                    if (!$stock_merge->id) {
                        $stock->parent = $stock;
                		$stock->save();
                    }
                    else {
                    	$stock->merge($stock_merge);
                    }
                }
                else {
                	$stock->parent = $stock;
                	$stock->save();
                }
                */
                $tags = explode(',', $row[14]);
                Tag_Model::replace_tags($stock, $tags, 'inventory', TRUE);
                $success_count++;

                echo "\033[1;40;32m";
		        echo sprintf("%s ==> 生成存货[%d]\n", $stock->product_name, $stock->id);
		        echo "\033[0m";	
			}
        }

        echo "=============\n";
        echo "\033[1;40;32m";
        echo sprintf("共计导入%s条存货, 成功条数 %s\n", $total_count, $success_count);
        echo "\033[0m";
    }
}
