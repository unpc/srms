#!/usr/bin/env php
<?php
    /*
     * file delete_eq_charge.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-06-09
     *
     * useage SITE_ID=cf LAB_ID=nankai php delete_eq_charge.php
     * brief 删除某个时段内的所有的使用收费(自动删除对应的财务明细)
     */


require 'base.php';

fwrite(STDOUT, '请输入开始时间xxxx-xx-xx: ');

$dtstart = fgets(STDIN);

fwrite(STDOUT, '请输入结束时间xxxx-xx-xx: ');

$dtend = fgets(STDIN);

$dtstart = strtotime($dtstart) ? : 0;
$dtend = strtotime($dtend);

//查询ctime在该时段内的所有的eq_charge,
//进行删除
//删除时会自动删除对应的billing_transaction, 故无需再删除billing_transaction

Q("eq_charge[ctime={$dtstart}~{$dtend}]")->delete_all();
