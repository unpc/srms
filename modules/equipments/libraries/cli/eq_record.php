<?php
class CLI_EQ_Record {

    static function update_record_flag() {
        $records = Q('eq_record');
        foreach ($records as $record) {
            $reserv = $record->reserv;
            if ($reserv->id) {
                $status = $reserv->get_status(FALSE, $record);
                $record->flag = $flag;
                $record->save();
            }
        }
    }

    static function auto_close_records() {
        $server   = '110.40.193.185';//连接地址
        $port     = 1883;//连接端口
        $clientId = 'epc-iot';//客户端ID，可随意填写，也可使用rand函数生成随机的
        $mqtt = new \PhpMqtt\Client\MqttClient($server, $port, $clientId);
        $e = O('equipment', 2);
        foreach (Q("eq_record[!dtend][equipment={$e}]") as $record) 
        {
            $equipment = $record->equipment;
            $user = $record->user;
            $dtstart = $record->dtstart;
            list($pre, $uuid) = explode("//", $equipment->control_address);
            $reserv = Q("eq_reserv[dtend>{$dtstart}][dtstart<={$dtstart}][user={$user}][equipment=$equipment]:sort(dtend D)")->current();
            if ($reserv->id) {
                $adjust = $reserv->dtend - Date::time();
                // 还剩余10分钟的时候进行报警
                if ($adjust <= 10 * 60 and $adjust >= 9 * 60) {
                    $mqtt->connect();
                    $topic = "/device/{$uuid}/down";
                    $params = json_encode([
                        'method' => "set_voice", 
                        'voice' => 10
                    ]);
                    $mqtt->publish($topic, $params, 0);
                    $mqtt->loop(true);
                    $mqtt->disconnect();
                }
                // 还剩余0分钟的时候直接关闭
                if ($adjust <= 0 and $adjust > -60) {
                    $record->dtend = Date::time();
                    $record->status = EQ_Record_Model::FEEDBACK_NORMAL;
                    $record->feedback = T('系统自动关机');
                    $record->samples = Config::get('eq_record.record_default_samples');
                    $record->save();
                    $mqtt->connect();
                    $topic = "/device/{$uuid}/down";
                    $sstart = Date::format($record->dtstart, 'H:i:s');
                    $send = Date::format(time(), 'H:i:s');
                    $params = json_encode([
                        'method' => "set_relay", 
                        'device' => $equipment->name,
                        'info' => "使用人:  {$user->name} \n\n 使用时间: {$sstart} ~ {$send}",
                        "relay" => 0
                    ]);
                    $mqtt->publish($topic, $params, 0);
                    $mqtt->loop(true);
                    $mqtt->disconnect();
                }
            }
        }
    }
}