<?php

require 'base.php';

foreach (Q('equipment') as $equipment) {
    if($equipment->charge_template){
        foreach ($equipment->charge_template as $type => $template) {
            //获取options
            $options = $equipment->template_standard[$type];
            preg_match_all('/options\s*=(.*)\n?/',$options,$res);
            EQ_Charge::update_charge_script($equipment, $type, ['%options' => $res[1][0]]);
            echo "update-->{$equipment->id}\n";
        }
    }
}