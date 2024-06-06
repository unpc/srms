#!/usr/bin/env php
<?php

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

foreach (Q('eq_empower') as $eq_empower) {
    if ($eq_empower->rules) {
        echo print_r(json_decode($eq_empower->rules, true));
        foreach (json_decode($eq_empower->rules, true) as $rule) {
            $eq_reserv_time = O('eq_reserv_time');
            $eq_reserv_time->equipment = $eq_empower->equipment;
            $eq_reserv_time->uncontroluser = $eq_empower->uncontroluser;
            $eq_reserv_time->uncontrollab = $eq_empower->uncontrollab;
            $eq_reserv_time->uncontrolgroup = $eq_empower->uncontrolgroup;
            $eq_reserv_time->ltstart = $rule[startdate];
            $eq_reserv_time->ltend = $rule[enddate];
            $eq_reserv_time->dtstart = $rule[starttime];
            $eq_reserv_time->dtend = $rule[endtime];
            $eq_reserv_time->type = $rule[rtype];
            $eq_reserv_time->num = $rule[rnum];
            $eq_reserv_time->days = $rule[week_day];
            $eq_reserv_time->save();
        }
    }
    else {
        $eq_reserv_time = O('eq_reserv_time');
        $eq_reserv_time->equipment = $eq_empower->equipment;
        $eq_reserv_time->uncontroluser = $eq_empower->uncontroluser;
        $eq_reserv_time->uncontrollab = $eq_empower->uncontrollab;
        $eq_reserv_time->uncontrolgroup = $eq_empower->uncontrolgroup;
        $eq_reserv_time->save();
    }
}