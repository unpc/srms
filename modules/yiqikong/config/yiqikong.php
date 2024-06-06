<?php

//app下沉推送地址，mq只推送本地，各站点可单独配置自己的推送节点
$config['mq'] = [
    'host' => '172.17.42.1',
    'port' => 11300,
    'default_tube' => 'default',
    'timeout' => 60,
    'x-beanstalk-token' => 'Genee83719730'
];
