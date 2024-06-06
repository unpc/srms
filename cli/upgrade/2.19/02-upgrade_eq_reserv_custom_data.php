#!/usr/bin/env php
<?php

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function() {
    return TRUE;
};

//数据库备份
$u->backup = function() {
    return TRUE;
};

$u->upgrade = function() {

    $status = EQ_Status_Model::OUT_OF_SERVICE.','.EQ_Status_Model::IN_SERVICE;
    foreach(Q("equipment[status={$status}]") as $equipment){
        if($equipment->visual_vars){
            $custom_vars = $equipment->custom_vars ?? [];
            $custom_content = $equipment->custom_content ?? [];
            $custom_vars = array_merge($custom_vars,['eq_reserv'=>$equipment->visual_vars]);
            $custom_content = array_merge($custom_content,['eq_reserv'=>$equipment->visual_html]);
            $equipment->custom_vars = $custom_vars;
            $equipment->custom_content = $custom_content;
            unset($equipment->visual_vars);
            unset($equipment->visual_html);
            $equipment->save();
        }
    }

    Upgrader::echo_success("Done.");

    return TRUE;
};

//恢复数据
$u->restore = function() {
    return TRUE;
};

$u->run();