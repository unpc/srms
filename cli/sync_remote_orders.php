#!/usr/bin/env php
<?php

require dirname(__FILE__). '/base.php';

//防止同时有cron和同步订单操作，增加文件锁

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
        catch (RPC_Exception $e) {
            echo $e->getMessage();
        }

        flock($fp, LOCK_UN);
    }
    else {
        echo "同步订单脚本正在执行，请稍后再试\n";
    }

    fclose($fp);
}


