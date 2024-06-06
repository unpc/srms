<?php
class AuthX_Face_Lims
{
    public static function api_user_get($e, $params, $data, $query)
    {
        $server = Config::get('gateway.authx_face_server');
        if (!$server) {
            $e->return_value = [
                'uid' => '',
                'message' => 'No Server'
            ];
            return;
        }
        $request_url = $server['url'] . "user";
        $token = Gateway::getToken();
        $options = [
            'headers' => [
                'X-Gapper-OAuth-Token' => $token,
            ],
            'query' => [
                'feature' => $query['feature'],
                'threshold' => 0.8
            ],
        ];
        $result = Gateway::exec($request_url, 'GET', $options);
        if (!$result || !isset($result['result']['uid']) || !$result['result']['uid']) {
            $e->return_value = [
                'uid' => '',
                'message' => 'Not Found'
            ];
            return;
        }
        $user = O("user", ['gapper_id' => $result['result']['uid']]);
        if ($user) {
            $e->return_value = [
                'uid' => $user->id,
                'message' => 'success'
            ];
        } else {
            $e->return_value = [
                'uid' => '',
                'message' => 'Not Found'
            ];
        }
    }
    public static function api_features_get($e, $params, $data, $query)
    {

        $faces = [
            'feature' => ""
        ];
        $e->return_value = [
            'faces' => $faces
        ];
    }
}
