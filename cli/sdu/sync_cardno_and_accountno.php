#!/usr/bin/env php
<?php

// 访问 synjones 接口, 通过用户的学工号设置一卡通卡号 (xiaopei.li@2012.01.05)

require dirname(dirname(__FILE__)) . '/base.php';

$server = Config::get('lab.sdu_synjones_server');
$port = Config::get('lab.sdu_synjones_port');

$client = new Synjones($server, $port);

// TODO 每次更新应有报告(xiaopei.li@2012-01-07)
$client->update_users_card_no_by_ref_no();
