#!/usr/bin/env php
<?php

error_reporting(0);

function usage() {
    die("usage: courier.php -a='tcp://localhost:3333' -q='lims' -d='{\"a\":\"b\"}' [-k='nankai'] \n");
}

//用于进行发送命令给 DeBaDe-Courier
// a(ddress) -> 地址
// q(ueue) -> 队列名称
// d(ata) -> 消息内容
// d 需要自己处理为 json 数据
$shortopts = 'a:q:d:k::';
$longopts = [
    'address:',
    'queue:',
    'data:',
    'key::',
];

$opts = getopt($shortopts, $longopts);

if ($opts['a'] || $opts['address']) {
    $address = $opts['a'] ? : $opts['address'];
}
else {
    usage();
}

if ($opts['q'] || $opts['queue']) {
    $queue = $opts['q'] ? : $opts['queue'];
}
else {
    usage();
}

if ($opts['d'] || $opts['data']) {
    $data = $opts['d'] ? : $opts['data'];
}
else {
    usage();
}

$m = [
    'queue'=> $queue,
    'data'=> json_decode($data, TRUE),
];

$key = $opts['k'] ? $opts['k'] : $opts['key'];

if ($key) {
    $m['routing'] = $key;
}

echo "数据结构:\n";
print_r($m);

$sock = new ZMQSocket(new ZMQContext(), ZMQ::SOCKET_PUSH);
$sock->connect($address);
$sock->send(json_encode($m, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));

echo "推送成功\n";
echo "如果无法正常推送, 请查看 courier 的 queue 配置名称是否正确\n";
