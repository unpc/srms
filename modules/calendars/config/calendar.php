<?php
// 如果你打开这个配置文件，可能是遇到了lims预约不上的情况，列几个checklist
// 2.19+ LIMS之后应是V2的reserv-server

// * server配置块是LIMS和Reserv-server模块http交互地址(V2)
// * io.path配置是LIMS-web页面和reserv-server的Socket.io连接（V2）

// * 检查：
//     0. 本配置文件server、io.path
//     1. reserv-server 配置 url 部分
//     2. nginx 配置 socket.io(V1) 和 socket.iov2(V2)
//     3. 如果处在V1->V2过程中，请清理一下 lims/public/cache 目录
$config['server'] = [
    'url' => 'http://172.17.42.1:9899',
    'path' => '/'
];
$config['io.path'] = '/socket.iov2';
