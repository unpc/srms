#!/usr/bin/env php
<?php

$base = dirname(dirname(dirname(__FILE__))) . '/base.php';
require $base;

$u = new Upgrader;

$u->check = function() {
    $db = Database::factory();
    return !!$db->value('DESC `eq_reserv_time` uncontroluser');
};

//数据库备份
$u->backup = function() {
    return TRUE;
};

$u->upgrade = function() {
    $db = Database::factory();
    foreach (Q("equipment") as $equipment) {
        $ds = $db->query('SELECT * FROM eq_reserv_time WHERE equipment_id = ' . $equipment->id);
        $row = $ds->row();

        $ds = $db->query('SELECT ltstart FROM eq_reserv_time WHERE equipment_id = ' . $equipment->id
            . ' ORDER BY ltstart ASC limit 1');
        $ltstart = $ds ? $ds->value() : 1;

        $ds = $db->query('SELECT ltend FROM eq_reserv_time WHERE equipment_id = ' . $equipment->id
            . ' ORDER BY ltend DESC limit 1');
        $ltend = $ds ? $ds->value() : 2145888000;

        if ($row->uncontroluser || $row->uncontrollab || $row->uncontrolgroup) {
            $time = O('eq_reserv_time');
            $time->equipment = $equipment;
            $time->ltstart = $ltstart;
            $time->ltend = $ltend;
            $time->dtstart = 31507200;
            $time->dtend = 31593599;
            $time->type = 1;
            $time->num = 1;
            $time->days = '';
            $time->controlall = 0;
            $time->controluser = $row->uncontroluser;
            $time->controllab = $row->uncontrollab;
            $time->controlgroup = $row->uncontrolgroup;
            $time->save();
        }
    }
    $query = "ALTER TABLE eq_reserv_time DROP COLUMN uncontroluser, DROP COLUMN uncontrollab, DROP COLUMN uncontrolgroup;";
    $db->query($query);

    Upgrader::echo_success("Done.");

    return TRUE;
};

//恢复数据
$u->restore = function() {
    return TRUE;
};

$u->run();
