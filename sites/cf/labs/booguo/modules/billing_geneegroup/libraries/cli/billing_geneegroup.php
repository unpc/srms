<?php

class CLI_Billing_Geneegroup
{
    public static $_client;
    public static function handleHistory()
    {
        $noTrans = Q('eq_charge[confirm=0][amount>0]')->to_assoc('id', 'id');
        $needTrans = Q('eq_charge[confirm=1][amount>0]')->to_assoc('id', 'id');

        $db = Database::factory();
        // 上旧功能时未处理的历史数据，上线新功能时不进入报销，且不可确认
        foreach ($noTrans as $id) {
            $query = "UPDATE `eq_charge` SET `confirm` = 1 WHERE `id` = {$id}";
            $db->query($query);
        }
        // 机主未一次确认的，保证机主可在通用收费确认模块下可以看到此条记录
        foreach ($needTrans as $id) {
            $query = "UPDATE `eq_charge` SET `confirm` = 0 WHERE `id` = {$id}";
            $db->query($query);
        }
        // 机主已确认及之后的，进入报销管理
        $config = Config::get('rest.billing');
        self::$_client = new \GuzzleHttp\Client([
            'base_uri' => $config['url'],
            'http_errors' => false,
            'timeout' => 5000
        ]);
        foreach (Q('eq_charge[confirm>1][amount>0]') as $charge) {
            self::_eq_charge_confirmed($charge);
        }
    }
    private static function _eq_charge_confirmed($charge)
    {
        $client = self::$_client;
        try {
            switch ($charge->source->name()) {
                case 'eq_sample':
                    $samples = $charge->source->success_samples;
                    break;
                case 'eq_record':
                    $samples = $charge->source->samples;
                    break;
                case 'eq_reserv':
                    $samples = 0;
                    foreach (Q("eq_record[reserv={$charge->source}]") as $record) {
                        $samples += $record->samples;
                    }
                    break;
            }

            $params = [
                'account' => $charge->transaction->account->id,
                'user' => $charge->user->id,
                'amount' => 0 - $charge->amount,
                'source' => LAB_ID,
                'object' => (string)$charge->source,
                'subject' => (string)$charge->equipment,
                'note' => $charge->transaction->id,
                'remark' => $charge->source->name(),
                'description' => I18N::T('billing_geneegroup', '%user 于 %time 确认收费', [
                    '%user' => $charge->auditor->name ? : '系统',
                    '%time' => $charge->rtime ? Date::format($charge->rtime, 'Y-m-d H:i:s') : Date::format(null, 'Y-m-d H:i:s')
                ]),
                'ctime' => date('Y-m-d H:i:s', $charge->ctime),
                'evidence' => $charge->confirm, // 东北旧报销确认，状态
                'extra' => [
                    // 'samples' => $samples,
                    // 'time' => $charge->source->dtend - $charge->source->dtstart,
                    'charge_duration_blocks' => $charge->charge_duration_blocks,
                ]
            ];

            if ($charge->voucher) {
                $result = $client->put("transaction/{$charge->voucher}", [
                    'form_params' => $params
                ]);
            } else {
                $result = $client->post('transaction', [
                    'form_params' => $params
                ]);
            }
            $return = json_decode($result->getBody()->getContents(), true);

            if (!$return) {
                $charge->confirm = EQ_Charge_Confirm_Model::CONFIRM_PENDDING;
                echo $charge->id, I18N::T('eq_charge', '收费确认失败!');
            } else {
                $charge->confirm = EQ_Charge_Confirm_Model::CONFIRM_INCHARGE;
                $charge->voucher = $return['id'];
            }
        } catch (Exception $e) {
            $charge->confirm = EQ_Charge_Confirm_Model::CONFIRM_PENDDING;
            echo $charge->id, I18N::T('eq_charge', '收费确认失败!');
        } finally {
            $charge->save();
        }
    }
}
