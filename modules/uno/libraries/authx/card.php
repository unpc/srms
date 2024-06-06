<?php

use GuzzleHttp\Client;

class AuthX_Card
{
    public static function get_user_from_sec_card($e, $card_no)
    {
        $rest = Config::get('authx.card');
        $client = new Client(['base_uri' => $rest['url'], 'timeout' => $rest['timeout'] ?: 5000]);
        try {
            $response = $client->get('api/v1/user', [
                'query' => ['card' => base_convert($card_no, 10, 16)],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Gateway::getToken()
                ]
            ]);
            $body = $response->getBody();
            $result = json_decode($body->getContents(), true);
            if ($gapper_id = $result['user']) {
                $e->return_value = Q("user[gapper_id={$gapper_id}]:limit(1)")->current();
            }
        } catch (Exception $e) {
        }
    }

    public static function get_user_card($e, $user)
    {
        if (!$user->gapper_id) {
            return;
        }
        $rest = Config::get('authx.card');
        $client = new Client(['base_uri' => $rest['url'], 'timeout' => $rest['timeout'] ?: 5000]);
        try {
            $response = $client->get('api/v1/cards', [
                'query' => ['user$' => $user->gapper_id],
                'headers' => [
                    'X-Gapper-OAuth-Token' => Gateway::getToken()
                ]
            ]);
            $body = $response->getBody();
            $result = json_decode($body->getContents(), true);
            $e->return_value = base_convert(array_flip($result['cards'])[$user->gapper_id], 16, 10);
        } catch (Exception $e) {
        }
    }
}
