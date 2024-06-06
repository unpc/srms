#!/usr/bin/env php
<?php
/**
 * 门禁规则之前是人、课题组、组织机构单选，目前升级为多选
 */

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function () {
    $db      = Database::factory();
    $query   = "SHOW TABLES LIKE 'door'";
    $results = $db->query($query);
    if (!$results) {
        return false;
    }

    Upgrader::echo_title('正在升级');
    // 查看所有的门的自定义规则
    foreach (Q('door') as $door) {
        $rules = (array) @json_decode($door->rules, TRUE);
		foreach ($rules as &$rule) {
            if ($rule["select_user_mode"]) {
                $k = 'select_user_mode_'.$rule["select_user_mode"];
                $rule[$k] = 'on';
            }		
		}
        if (count($rules)) {
            $door->rules = json_encode($rules, true);
            $door->save();
        }
    } 
    Upgrader::echo_success('升级成功');
    return true;
};

// 数据库备份
$u->backup = function () {
    return true;
};

// 数据升级
$u->upgrade = function () {
    // TODO 
};

// 恢复数据
$u->restore = function () {
    return true;
};

$u->run();
