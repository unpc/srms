#!/usr/bin/env php
<?php

require "base.php";
$users = [];

foreach (Q("user") as $user) {
    if (strlen((string)$user->card_no) > 0 &&strlen((string)$user->card_no) < 6) {
        $users[$user->id] = $user->name;
    }
}


if (count($users)) {
    $output = new CSV('/usr/share/lims2/cli/simple_'.SITE_ID.'_'.LAB_ID.'.csv', 'w');
    $output->write(['ID', '姓名']);
    foreach ($users as $key => $value) {
        $output->write([$key, $value]);
    }
    $subject = strtr('%name, 简单卡号检测结果', [
        '%name' => Lab::get('lab.name')
    ]);
    $output->close();
}