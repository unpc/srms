<?php
class Billing_Neu
{
    const STATUS_DRAFT = 0;
    const STATUS_PENDDING = 1;
    const STATUS_RECORD = 2;
    const STATUS_HISTORY = 3;

    public static $STATUS_LABEL = [
        self::STATUS_DRAFT => '未报销',
        self::STATUS_PENDDING => '报销中',
        self::STATUS_RECORD => '已报销',
        self::STATUS_HISTORY => '--'
    ];

    public static $STATUS_COLOR = [
        self::STATUS_DRAFT => '#f93',
        self::STATUS_PENDDING => '#7498e0',
        self::STATUS_RECORD => '#6c9',
        self::STATUS_HISTORY => '#6c9'
    ];

    /* 这里是东北大学报销管理确认以后会调用这个方法，然后通过接口发送到
    *  报销服务，如果发送失败则将收费状态重新改为未确认
    * PS: 我想说这里面的提示信息用户是看不到的
    */
    public static function eq_charge_confirmed($e, $charge)
    {
        if ($charge->confirm != EQ_Charge_Confirm_Model::CONFIRM_INCHARGE) {
            return;
        }
        $config = Config::get('rest.billing');

        try {
            $client = new \GuzzleHttp\Client([
                'base_uri' => $config['url'],
                'http_errors' => false,
                'timeout' => 5000
            ]);

            $params = [
                'account' => $charge->transaction->account->id,
                'user' => $charge->user->id,
                'amount' => 0 - $charge->amount,
                'source' => LAB_ID,
                'object' => (string)$charge->source,
                'subject' => (string)$charge->equipment,
                'note' => $charge->transaction->id,
                'remark' => $charge->source->name(),
                'description' => I18N::T('billing_neu', '%user 于 %time 确认收费', [
                    '%user' => $charge->auditor->name ? : (L('ME')->name ? : '系统'),
                    '%time' => $charge->rtime ? Date::format($charge->rtime, 'Y-m-d H:i:s') : Date::format(null, 'Y-m-d H:i:s')
                ]),
                'ctime' => date('Y-m-d H:i:s', $charge->ctime),
                'evidence' => $charge->confirm, // 东北旧报销确认，状态
                'extra' => [
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
                Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '收费确认失败!'));
            } else {
                $charge->voucher = $return['id'];
            }
        } catch (Exception $e) {
            $charge->confirm = EQ_Charge_Confirm_Model::CONFIRM_PENDDING;
            Lab::message(Lab::MESSAGE_ERROR, I18N::T('eq_charge', '收费确认失败!'));
        } finally {
            $charge->save();
        }
    }
}
