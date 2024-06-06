#!/usr/bin/env php

<?php
require_once "base.php";

foreach (Q("user") as $user) {
    $gapper_user_list = [];
    $gapper_user = new Gapper_User_Model($user);
    $gapper_user_list[] = $gapper_user->get_array($gapper_user);
    $gapper = Gapper::getInstance('push');
    $response = $gapper->pushUsers($gapper_user_list);
    print_r($response);
}
