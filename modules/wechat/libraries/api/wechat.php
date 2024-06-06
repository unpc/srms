<?php

class API_Wechat extends API_Common {

    protected static $feedback_status_map = [
        1 => EQ_Record_Model::FEEDBACK_NORMAL,
        2 => EQ_Record_Model::FEEDBACK_PROBLEM,
        0 => EQ_Record_Model::FEEDBACK_NOTHING
    ];

    protected static $errors = [
        401 => 'Access Denied',
        404 => 'Can Not Find Item',
        500 => 'Internal Error',
        501 => 'Can Not Item',
        1004 => '找不到匹配的用户',
        1005 => '账户名密码不匹配',
        1010 => '用户信息不合法',
        1011 => '用户保存失败',
    ];

    public function getToken($criteria) {
        $app_id = $criteria['appID'];
        $secret = $criteria['secret'];
        if(!Wechat::check_secret($app_id, $secret)) {
            throw new API_Exception(self::$errors[401], 401);
        }

        //create token
        $token = uniqid();
        $_SESSION['api_token'] = $token;
        $_SESSION['api_criteria'] = $criteria;
        return $token;
    }

    protected function checkToken($token) {
        if(strlen($token) > 0 && isset($_SESSION['api_token'])) {
            return $token == $_SESSION['api_token'];
        }
        return false;
    }

    public function create($data) {
        $this->_ready();

        $validate = [
            'name',
            'backend',
            'password',
        ];

        foreach ($validate as $key) {
            if (!isset($data[$key])) {
                return [
                    'code' => 400,
                    'message' => self::$errors[1010]
                ];
            }
        }
        $token = Auth::normalize($data['name'], $data['backend']);
        $user = O('user', ['token' => $token]);

        if (!$user->id) {
            return [
                'code' => 400,
                'message' => self::$errors[1004]
            ];
        }

        $auth = new Auth($token);
        if (!$auth->verify($data['password'])) {
            return [
                'code' => 400,
                'message' => self::$errors[1005]
            ];
        }

        $url = Config::get('wechat.wechat_yiqikong_user_url');
        $client = new \GuzzleHttp\Client([
            'base_uri' => $url,
            'http_errors' => FALSE,
            'timeout' => 5
        ]);

        try{
            $path = "user";
            $method = 'get';
            $response = $client->{$method}($path, [
                'query' => [
                    'email' => $user->email
                ]
            ])->getBody()->getContents();

            $yiqikong_user = @json_decode($response,true);

            if (!$yiqikong_user['total']) {
                // 通用添加仪器控用户
                $path = "user";
                $method = 'post';

                $response = $client->{$method}($path, [
                    'headers' => [
                        'CLIENTID' => Config::get('wechat.wechat_client_id'),
                        'CLIENTSECRET' => Config::get('wechat.wechat_client_secret'),
                    ],
                    'form_params' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'password' => $data['password'],
                    ]
                ])->getBody()->getContents();

                $response = @json_decode($response,true);
                $user->yiqikong_id = $response['id'];
                $user->gapper_id = $response['gapper_id'];
                $user->save();
            }
        }
        catch(Error_Exception $e) {
            throw new Error_Exception(self::$errors[1011]);
        }
        return [
            'code' => 200,
            'info' => [
                "id" => $user->id,
                "yiqikong_id" => $user->yiqikong_id,
                "gapper_id" => $user->gapper_id,
                "email" => $user->email,
                "name" => $user->name,
            ]
        ];
    }
}
