<?php

class Equipment_Glogon {

    static function glogon_ret ($e, $ret, $user, $equipment) {
        if (!Lab::get('eq_reserv.glogon_safe')) return;

        $equipments = Q("equipment[id!={$equipment->id}][user_using={$user}][connect]")->to_assoc('id', 'name');
        if (!$equipments) return;
        
        $url = URI::url('!equipments/glogon/using', ['user' => $user->id, 'equipment' => $equipment->id]);
        $ret['using'] = [
            'url' => $url,
            'text' => "您的账号正在使用其他仪器，目前使用的仪器有：\n" . implode("\n", $equipments),
            'position' => 'center'
        ];
	}

}