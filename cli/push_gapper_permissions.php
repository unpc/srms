#!/usr/bin/env php

<?php
require_once "base.php";

$gapper_group=new Gapper_Permission_Group_Model();
foreach(Q("perm[module_id=3]") as $perm)
{
    $gapper_permission=new Gapper_Permission_Model($perm);
    $gapper_permission->key=md5($perm->name);
    $gapper_group->add_permission($gapper_permission);
}
print_r(json_encode($gapper_group->get_array(),JSON_UNESCAPED_UNICODE));
echo "\n";


$gapper=Gapper::getInstance('push');
$response=$gapper->pushPermissions($gapper_group->get_array());
print_r($response);