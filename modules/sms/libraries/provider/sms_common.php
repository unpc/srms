<?php

/**
 * 保持原来notifition_sms发送逻辑，
 * 新增厂商对接,在各自站点下sms.php设置provider配置,获取相应的适配来完成发送任务
 */
class Provider_Sms_Common
{

    private $_config;

    public function __construct($config)
    {
        $this->_config = $config;
    }

    public function send($phones, $body)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->_config['url']);

            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);

            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "api:key-{$this->_config['api.key']}");

            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, array('mobile' => $phones, 'message' => $body . $this->_config['api.sign']));

            $res = curl_exec($ch);
            curl_close($ch);

        } catch (Exception $e) {
            throw new $e;
        }
    }
}