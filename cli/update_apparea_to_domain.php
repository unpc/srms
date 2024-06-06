#!/usr/bin/env php
<?php

require "base.php";

$equipments = Q('equipment');
foreach ($equipments as $equipment) {
    $applicationarea = $equipment->applicationarea;
    $find = [',', '，', '  '];
    $applicationarea = str_replace($find, ' ', $applicationarea);
    $applicationarea = trim($applicationarea);
    $applicationarea = explode(' ', $applicationarea);

    $domain = [];

    foreach ($applicationarea as $apparea) {
        if (in_array($apparea, Config::get('equipment.domain'))) {
            array_push($domain, $apparea);
        }
        else {
            array_push($domain, '其他');
        }
    }

    $domain = trim(join(',', array_unique($domain)));

    $equipment->domain = $domain;
    $equipment->save();
}
