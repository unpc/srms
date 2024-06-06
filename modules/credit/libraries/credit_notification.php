<?php

class Credit_Notification
{
    //达到阈值发消息
    public static function send_msg($credit, $limit)
    {
        $credit_measures = Q("credit_measures[ref_no!=send_msg][type=" . Credit_Measures_Model::TYPE_PUNISHMENT . "]");
        $measures = [];
        foreach ($credit_measures as $measure) {
            $credit_limit = Credit_Limit::get_punishment_limit($credit->user, $measure);
            if ($credit_limit->id) {
                $measures[] = "当您的信用分降至{$credit_limit->score}时, 您将被{$measure->name}";
            }
        }
        Notification::send('credit.send_msg', $credit->user, [
            '%user' => Markup::encode_Q($credit->user),
            '%number' => $credit->total,
            '%measures' => I18N::T('credit', $limit->measures->name),
            '%measures' => implode("\n", $measures) . "\n\n",
        ]);
    }

    // 加分/扣分通知
    public static function after_credit_record_saved($e, $credit_record)
    {
        $key = $credit_record->credit_rule->type ? 'credit.credit_deduction' : 'credit.credit_increase';
        $credit_measures = Q("credit_measures[ref_no!=send_msg][type=" . Credit_Measures_Model::TYPE_PUNISHMENT . "]");
        $measures = [];
        foreach ($credit_measures as $measure) {
            $credit_limit = Credit_Limit::get_punishment_limit($credit_record->user, $measure);
            if ($credit_limit->id) {
                $measures[] = "当您的信用分降至{$credit_limit->score}时, 您将被{$measure->name}";
            }
        }
        Notification::send($key, $credit_record->user, [
            '%user' => Markup::encode_Q($credit_record->user),
            '%time' => Date::format($credit_record->ctime, 'Y/m/d H:i:s'),
            '%score' => abs($credit_record->score),
            '%reason' => $credit_record->description,
            '%measures' => implode("\n", $measures) . "\n\n",
        ]);
    }

    //信用过低账号变为未激活的通知
    public static function measure_notification($e, $credit, $limit, $ref_no)
    {
        if ($ref_no == 'send_msg') {
            self::send_msg($credit, $limit);
        }else{
            Notification::send('credit.' . $ref_no, $credit->user, [
                '%user' => Markup::encode_Q($credit->user),
                '%number' => $credit->total,
                '%banned_score' => $limit->score,
                '%measures' => I18N::T('credit', $limit->measures->name),
            ]);
        }
    }

    //解禁消息
    public static function thaw($e, $credit)
    {
        Notification::send('credit.thaw', $credit->user, [
            '%user' => Markup::encode_Q($credit->user),
            '%admin' => Markup::encode_Q(L('ME')),
            '%time' => Date::format(Date::time(), 'Y/m/d H:i:s'),
        ]);
    }

    public static function user_setting($e, $message)
    {
        // 先用title
        $types = ['credit.send_msg'];
        foreach ($types as $type) {
            $config = (array)Lab::get('notification.' . $type) + (array)Config::get('notification.' . $type);
            if (strpos($message->title, '请注意规范使用')) {
                $config['type'] = $type;
                $e->return_value = V('credit:notification_setting', ['message' => $message, 'config' => $config]);
            }
        }
    }

}
