#!/usr/bin/env php
<?php
    /*
     * file create_accounts_for_all_labs.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-10-28
     *
     * useage SITE_ID=cf LAB_ID=nankai php create_accounts_for_all_labs.php
     * brief 给所有的实验室创建财务账号
     */

require 'base.php';

define('DISABLE_NOTIFICATION', TRUE);

if ($GLOBALS['preload']['billing.single_department']) {
    $departments = [Billing_Department::get()];
}
else {
    $departments = Q('billing_department');
}

foreach(Q('lab') as $l) {
    foreach($departments as $d) {
        $a = O('billing_account', ['lab'=> $l, 'department'=> $d]);
        if (!$a->id) {
            $a->department = $d;
            $a->lab = $l;
            if ($a->save()) {
                echo "\033[32m";
                echo "添加成功 {$l->name} => {$d->name}!\n";
                echo "\033[0m";
            }
            else {
                echo "\033[31m";
                echo "添加失败 {$l->name} => {$d->name}!\n";
                echo "\033[0m";
            }
        }
    }
}
