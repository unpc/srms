<?php

/**
 * 从 uno 的门牌模块 获取门牌相关信息
 * 1.验证app登录 getAccessToken
 * 2.门牌服务auth认证
 */
class Remote_Door extends Remote_Base
{
    public static $_client;

    public static function init()
    {
        parent::init();
        self::$_client = new \GuzzleHttp\Client([
            'headers' => [
                'X-Gapper-OAuth-Token' => self::$_access_token
            ],
            'base_uri' => Config::get('remote.url'),
            'timeout' => 5,
            'http_errors' => true,
        ]);
    }

    /**
     *  获取门牌信息列表
     */
    public static function getDoors($selector = [])
    {
        self::init();
        $data = @json_decode(self::$_client->get(
            'api/v1/doors',
            [
                'query' => $selector
            ]
        )->getBody()->getContents(), true);
        return $data;
    }

    /**
     *  获取门牌详情
     */
    public static function getDoorInfo($id = 0)
    {
        self::init();
        $data = @json_decode(self::$_client->get(
            "api/v1/door/{$id}",
            [
                'query' => []
            ]
        )->getBody()->getContents(), true);
        return $data;
    }

    public static function pushRule($id, $rule, $type = '')
    {
        // if (!count($rule)) return;
        self::init();
        try {
            $data = @json_decode(self::$_client->post(
                "api/v1/door/{$id}/ruleremote",
                [
                    'form_params' => [
                        'rule' => $rule,
                        'type' => $type
                    ]
                ]
            )->getBody()->getContents(), true);
        } catch (Exception $e) {
            error_log('等待规则推送结果失败可能是超时了,iot-door里面日志正常就可以');
        }
        return $data;
    }
}
