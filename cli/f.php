<?php

require 'base.php';

$server   = '110.40.193.185';//连接地址
$port     = 1883;//连接端口
$clientId = 'epc-iot';//客户端ID，可随意填写，也可使用rand函数生成随机的
$mqtt = new \PhpMqtt\Client\MqttClient($server, $port, $clientId);
$mqtt->connect();
$mqtt->subscribe('#', function ($topic, $message) {
    echo sprintf("Received message on topic [%s]: %s\n", $topic, $message);
}, 0);
$mqtt->loop(true);
$mqtt->disconnect();



$topic = "/device/89860813102381170641/down";
$params = json_encode([
    'method' => "set_relay", 
    'device' => $equipment->name,
    'info' => "使用人:  {$user->name} \n\n 使用时间:  ",
    "relay" => 0
]);
$mqtt->publish($topic, $params, 0);

