<?php

class Notification_SMS
{

    //参数按照原调用方式，扩展reveivers为数组
    static function send($sender, $receivers, $title, $body)
    {
        if (!is_array($receivers))
            $receivers = [$receivers];
        if (empty($receivers)) {
            Log::add('[error] 系统发送短信时缺少发送对象,receivers为空', 'sms');
            return true;
        }

        $config = Config::get('sms');

        //构造短信接受者,隔开
        $receiversPhone = [];
        foreach ($receivers as $receiver) {
            $p = $receiver->get_binding_phone() ?: $receiver->phone;
            if (preg_match('/^1[3456789]\d{9}$/', $p)) {
                $receiversPhone[] = $p;
            }
        }

        if (empty($receiversPhone)) {
            Log::add('[error] 系统发送短信时缺少发送对象电话,receiversPhone为空', 'sms');
            return true;
        }
        $perLImit = $config['per_limit'];
        if ($perLImit && count($receiversPhone) > $perLImit) {
            $receiversPhoneArray = array_chunk($receiversPhone, $perLImit);
        } else {
            $receiversPhoneArray = [$receiversPhone];
        }

        $handlerName = 'Provider_Sms_' . ($config['provider'] ?? 'common');
        $handler = new $handlerName($config);
        //根据per_limit多次发送
        try {
            $body = new Markup($body, true);
            $body = strip_tags((string)$body);
            foreach ($receiversPhoneArray as $value) {
                $phoneStr = join(',', $value);
                $handler->send($phoneStr, $body);
                $counts = count($value) ?? 0;
                Lab::set('lab.sms.count', (int)Lab::get('lab.sms.count') + $counts);
            }
            Log::add(strtr('[success] 系统发送短信给%receiver_phone, 内容[%content]', [
                '%receiver_phone' => $phoneStr,
                '%content' => $body,
            ]), 'sms');
        } catch (Exception $e) {
            Log::add(strtr('[error][%err] 系统发送短信给%receiver_phone, 内容[%content]', [
                '%err' => $e->getMessage(),
                '%receiver_phone' => $phoneStr,
                '%content' => $body,
            ]), 'sms');
        }
    }
}
