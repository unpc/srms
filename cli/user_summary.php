#!/usr/bin/env php
<?php

require 'base.php';

$data = [];
$now = time();
$root = Tag_Model::root('group');

echo "站点名, 学院, 用户名, 邮箱\n";
// 已激活未过期用户 不要genee用户
foreach(Q("user[atime>0][dto>{$now}]") as $user) {
    if ($user->token == 'genee|database') continue;
    $groups = [];
    $g = $user->group;
    while ($g->id && $g->id != $root->id) {
        $groups[] = $g->name;
        $g = $g->parent;
    }
    echo join(',', [
        Config::get('page.title_default'),
        join(' > ', array_slice(array_reverse($groups), 0, 2)),
        $user->name,
        $user->email
    ]), "\n";
}
