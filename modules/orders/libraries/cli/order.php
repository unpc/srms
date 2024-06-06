<?php

class CLI_Order{
	static function sync_remote_orders() {
        $sync_file = Config::get('system.tmp_dir').Misc::key('order_sync');
        File::check_path($sync_file);
        $fp = fopen($sync_file, 'w+');

        if($fp){
            if (flock($fp, LOCK_EX | LOCK_NB)) {

                try {
                    //mall.binded_sites 为已绑定的sources
                    foreach(Lab::get('mall.binded_sites', []) as $s) {
                        Mall::sync_remote_order($s);
                    }
                }
                catch (Exception $e) {
                    echo $e->getMessage();
                }

                flock($fp, LOCK_UN);
            }
            else {
                echo "同步订单脚本正在执行，请稍后再试\n";
            }

            fclose($fp);
        }
	}

    static function create_orders($file) {
        $now = Date::time();

        if (!($file && file_exists($file))) {
            print("usage: SITE_ID=cf LAB_ID=test php cli.php order create_orders o.csv\n");
            die;
        }

        $group_root = Tag_Model::root('group');
        $csv = new CSV($file, 'r');
        $csv->read(',');
        $total_count = $success_count = 0;

        $addvendor = Module::is_installed('vendor');

        while ($row = $csv->read(',')) {
            $total_count ++;
            $vname = trim($row[3]);
            if (!$row[0] || !$vname) continue;

            $tags = explode(',', $row[12]);

            foreach (Order_Model::$order_status as $k => $name) {
                if ($name == $row[4]) {
                    $status = $k;
                    break;
                }
            }

            $order = O('order');
            $order->product_name = trim($row[0]);
            if ($addvendor) {
                $order->vendor = O('vendor', ['name'=> $vname])->id ? $vname : '';
            }
            $order->manufacturer = $row[1];
            $order->catalog_no = $row[2];
            $order->status = $status;
            $order->model = $row[5];
            $order->spec = $row[6];
            $order->order_no = $row[7];
            $order->quantity = (int)$row[8];
            $order->unit_price = $row[9];
            $order->price = $row[10] ?: $row[8] * $row[9];
            $order->fare = (int)$row[11];
            $order->link = $row[13];

            $order->receive_address = $row[14];
            $order->receive_postcode = $row[15];
            $order->receive_phone = $row[16];
            $order->receive_email = $row[17];

            $order->requester = O('user', ['name' => trim($row[18])]);
            $order->request_date = strtotime($row[19]) ?: Date::time();
            $order->request_note = trim($row[20]);

            $order->purchaser = O('user', ['name' => trim($row[21])]);
            $order->purchase_date = strtotime($row[22]) ?: Date::time();
            $order->purchase_note = trim($row[23]);

            /* 24-26 确认人?? 貌似这个不通用吧，暂时不列入批量行列 */

            $order->receive_status = $row[27] == '已到货' ? Order_Model::RECEIVED : Order_Model::NOT_RECEIVED;

            $order->receiver = O('user', ['name' => trim($row[28])]);
            $order->receive_date = strtotime($row[29]) ?: Date::time();
            $order->receive_note = trim($row[30]);

            /* 31:加为存货， 32: 关联经费  现阶段不建议使用*/

            if ($order->save()) {
                Tag_Model::replace_tags($order, $tags, 'inventory', TRUE);
                $order->update_status();
                $success_count ++;
                echo "\033[1;40;32m";
                echo sprintf("[%s] ==> 生成订单[%d]\n", $order->product_name, $order->id);
                echo "\033[0m";
            }

        }

        echo "=============\n";
        echo "\033[1;40;32m";
        echo sprintf("共计导入%s条订单, 成功条数 %s\n", $total_count, $success_count);
        echo "\033[0m";
    }
}
