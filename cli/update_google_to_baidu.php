#!/usr/bin/env php
<?php

require "base.php";

$key = Config::get('gismon.server.key');
$url = 'http://api.map.baidu.com/geoconv/v1/';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

$buildings = Q("gis_building");
foreach ($buildings as $building) {
    $lon = $building->longitude;
    $lng = $building->latitude;

    $data = 'coords='.$lon.','.$lng."&from=3&to=5&ak=$key";

    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $rel = json_decode(curl_exec($ch));

    if ($rel->status == 0) {
        $building->longitude = $rel->result[0]->x;
        $building->latitude = $rel->result[0]->y;
    }

    $building->save();
}