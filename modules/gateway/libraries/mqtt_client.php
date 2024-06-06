<?php

class Mqtt_Client
{
    public static function push($topic, $content)
    {
        $config = Config::get('mqtt.gateway_address');

        // 发送给订阅号信息,创建socket,无sam队列
        $mqtt = new Mqtt($config['server'], $config['port'], $config['client_id']); //实例化MQTT类
        if ($mqtt->connect(true, null, $config['username'], $config['password'])) {
            //如果创建链接成功
            $mqtt->publish($topic, $content, 0);
            $mqtt->close();    //发送后关闭链接
        } else {
            echo "Time out!\n";
        }
    }
}
