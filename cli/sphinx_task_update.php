#!/usr/bin/env php
<?php
    /*
     * file  sphinx_task_update.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date  2012-11-21
     *
     * useage SITE_ID=com LAB_ID=genee php sphinx_task_update.php
     * brief 对系统中的sphinx进行索引升级
     */

require 'base.php';

//清空sphinx index
Search_Tn_Task::empty_index();

$tasks = Q('tn_task');

$total = $tasks->total_count();

$per_page = 20;
$start = 0;

//分页
while ($start < $total) {
    foreach($tasks->limit($start, $per_page + $start) as $task) {
        //重建
        Search_Tn_Task::update_index($task);
		ORM_Pool::release($task);
        echo '.';
    }
    $start += $per_page;
    echo "\n";
}
