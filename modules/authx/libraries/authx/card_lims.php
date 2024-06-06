<?php
class AuthX_Card_Lims
{
    public static function api_user_get($e, $params, $data, $query)
    {
        $card_no = hexdec($query['card']);
        if (!$card_no) {
            throw new Exception('card is required', 400);
        }
        $user = O('user', ['card_no' => $card_no]);
        if (!$user->id) {
            $e->return_value = [
                'message' => 'Not Found'
            ];
        } else {
            $e->return_value = [
                'id' => $user->id,
                'user' => $user->id,
                'cardNo' => $query['card']
            ];
        }
    }
    public static function api_cards_get($e, $params, $data, $query)
    {

        $userIds = $query['user$'];
        $salt = $query['salt'];
        if (!$userIds) {
            throw new Exception('user$ is required', 400);
        }
        $userIds = explode(',', $userIds);
        if (!count($userIds)) {
            throw new Exception('user$ is required', 400);
        }
        $users = Q("user[id=" . join(',', $userIds) . "][gapper_id=" . join(',', $userIds) . "]|");
        $cards = [];
        foreach ($users as $user) {
            if ($salt) {
                $s = hash_hmac('sha256', strtoupper(dechex($user->card_no)), $salt, true);
                $key = base64_encode($s);
            } else {
                $key = strtoupper(dechex($user->card_no));
            }
            $cards[$key] = $user->id;
        }
        $e->return_value = [
            'cards' => $cards
        ];
    }
}
