<?php

$config['server'] = [
    "url" => "http://172.17.42.1:4028/api/v1/"
];
$config['hkisc_url'] = "http://17kong.com/home";
// 门禁设备信息
$config['getDevicesList'] = ['path' => 'devices', 'method' => 'get'];
// 获取门禁
$config['getDoors']       = ['path' => 'doors', 'method' => 'get'];
// 增加门禁
$config['postDoor']       = ['path' => 'door', 'method' => 'post'];
// 修改门禁
$config['putDoor']        = ['path' => 'door/{DOOR_ID}', 'method' => 'put'];
// 删除门禁
$config['deleteDoor']     = ['path' => 'door/{DOOR_ID}', 'method' => 'delete'];
// 远程开门
$config['doorAction']     = ['path' => 'door-action/{DOOR_ID}', 'method' => 'post'];
// 推送开门规则
$config['doorRuleRemote'] = ['path' => 'door/{DOOR_ID}/ruleremote', 'method' => 'post'];
// 获取门禁负责人
$config['getDoorOwner'] = ['path' => 'door/{DOOR_ID}/owners', 'method' => 'get'];
// 推送门禁负责人
$config['doorOwner'] = ['path' => 'door/{DOOR_ID}/owners', 'method' => 'put'];
// 获取进门记录
$config['doorRecords'] = ['path' => 'door-records', 'method' => 'get'];
// 推送开门记录 （用不到，测试用）
$config['doorRecordsLog'] = ['path' => 'door-device/{DOOR_ID}/log', 'method' => 'post'];
// 推送用户
$config['userRemote'] = ['path' => 'userremote', 'method' => 'post'];