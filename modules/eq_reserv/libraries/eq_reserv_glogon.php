<?php

class EQ_Reserv_Glogon {
    
    static function glogon_ret($e, $ret, $user, $equipment) {
        if (!Lab::get('eq_reserv.glogon_arrival')) return;
        
        $dtstart = Date::time();
        if (!$equipment->accept_reserv) return;

        // 取出在改时间段内符合用户仪器条件的预约
        $reserv = Q("eq_reserv[equipment={$equipment}][user={$user}][dtstart~dtend={$dtstart}]:limit(1)")->current();
        if (!$reserv->id) return;

        // 将预约的开始结束时间和仪器客户端的语言包取出
        $end = H(date('Y-m-d H:i:s', $reserv->dtend));
        $locale = $equipment->device['lang'];
        // 取出提前多少分钟提示用户预约即将结束并做运算
        $limit = $equipment->reserv_arrival_limit ? $equipment->reserv_arrival_limit_time : 0;
        $url = URI::url('!eq_reserv/glogon/arrival', ['id' => $reserv->id]);
        $ret['alert'] = [
            'delay' => $reserv->dtend - Date::time() - $limit,
            'url' => $url,
            'text' => "您的预约即将结束，请合理安排实验时间\n\n预约结束时间：\n{$end}",
            'position' => 'center'
        ];
    }

    static function on_eq_reserv_changed($e, $reserv, $old_data = null, $new_data = null) {
        $now = Date::time();
        $buffer = 900;
        if ($reserv->dtstart - $buffer > $now && $now < $reserv->dtend + $buffer) {
            $root = ROOT_PATH;
            $site = SITE_ID;
            $lab = LAB_ID;
            putenv("Q_ROOT_PATH={$root}");
            $cmd = "SITE_ID={$site} LAB_ID={$lab} php {$root}cli/cli.php 
            eq_reserv glogon_current_reserv {$reserv->id} >/dev/null 2>&1 &";
            exec($cmd, $output);
        }
    }

    static function push_reserv_glogon($e, $reserv) {
        //保留原glogon逻辑
        // 找到当前的使用记录
        $record = Q("eq_record[dtend=0][reserv={$reserv}]")->current();
        $ret = ($reserv->id && $record->id) ? [
            'id' => $record->id,
            'dtstart' => $reserv->dtstart,
            'dtend' => $reserv->dtend,
        ] : [];

        // 向server同步预约
        $client = new \GuzzleHttp\Client([
            'base_uri' => $reserv->equipment->server,
            'http_errors' => FALSE, 
            'timeout' => Config::get('device.computer.timeout', 5)
        ]);

        (boolean) $client->post('status', [
            'form_params' => [
                'uuid' => $reserv->equipment->control_address,
                'reserv' => $ret
            ]
        ]);
    }

    static function push_reserv_veronica($e, $reserv) {
        // 找到当前的使用记录
        $card_no = $reserv->user->card_no;
        if (!$card_no) return;
        $ret[$card_no] = [
            'dtstart' => $reserv->dtstart,
            'dtend' => $reserv->dtend,
        ];

        // 向server同步预约
        $client = new \GuzzleHttp\Client([
            'base_uri' => $reserv->equipment->server,
            'http_errors' => FALSE, 
            'timeout' => Config::get('device.computer.timeout', 5)
        ]);

        (boolean) $client->post('reserv', [
            'form_params' => [
                'uuid' => $reserv->equipment->control_address,
                'reserv' => $ret
            ]
        ]);
    }

}