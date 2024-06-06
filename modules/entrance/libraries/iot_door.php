<?php

use \GuzzleHttp\Client;

class Iot_door
{
    public static function exec($url, $method = 'GET', $options = [])
    {
        try {
            $client   = new \GuzzleHttp\Client(['timeout' => 30]);
            $response = $client->request($method, $url, $options);
            if ($response->getStatusCode() == 200) {
                $response = json_decode($response->getBody()->getContents(), true);
                return $response;
            }
        } catch (Exception $e) {
        }
        return false;
    }

    public static function __callStatic($function, $args = [])
    {
        $args = $args[0] ?: []; // 参数作用数组传入，第一个元素即所有参数

        // 接口不存在
        $func = Config::get('iot_door.' . $function);
        if (!$func) {
            return false;
        }

        $Cache     = Cache::factory();

        // 替换path中的参数 例：/group/{GROUP_ID}/ => /group/1/
        if ($args) {
            foreach ($args as $key => $value) {
                $paramName = '{' . strtoupper($key) . '}';
                if (strpos($func['path'], $paramName) != false) {
                    $func['path'] = str_replace($paramName, $value, $func['path']);
                    unset($args[$key]);
                }
            }
        }

        $server = Config::get('iot_door.server');

        $access_token = self::getToken();
        if (!$access_token) {
            return false;
        }
        $remote_url   = $server['url'];
        $url          = $remote_url . $func['path'];
        $response     = [];
        $options      = ['headers' => ['authorization' => $access_token]];
        $params       = $args;

        switch (strtoupper($func['method'])) {
            case 'GET':
                $url .= '?' . static::build_query($params);
                break;
            case 'POST':
                $options += ['form_params' => $params];
                break;
            case 'PUT':
                $options += ['form_params' => $params];
                break;
        }
        $response = static::exec($url, $func['method'], $options);
        return $response;
    }

    public static function build_query($params)
    {
        $str    = '';
        $i      = 0;
        $length = count($params);
        foreach ($params as $key => $value) {
            $str .= "$key=$value";
            if ($i < $length - 1) {
                $str .= '&';
            }
            $i++;
        }
        return $str;
    }

    private static function keygen($len = 40)
    {
        // We generate twice as many bytes here because we want to ensure we have
        // enough after we base64 encode it to get the length we need because we
        // take out the "/", "+", and "=" characters.
        $bytes = openssl_random_pseudo_bytes($len * 2, $strong);
        // We want to stop execution if the key fails because, well, that is bad.
        if ($bytes === false || $strong === false) {
            // @codeCoverageIgnoreStart
            throw new \Exception('Error Generating Key');
            // @codeCoverageIgnoreEnd
        }
        return substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $len);
    }

    public static function current_user($e, $params, $data, $query)
    {
        $key = preg_replace('/^Bearer /', '', $_SERVER['HTTP_AUTHORIZATION']);
        $cache = Cache::factory('redis');
        $user_id = $cache->get($key);

        $user = O('user', $user_id);
        if ($user->id) {
            $e->return_value = [
                'id' => $user->id,
                'name' => $user->name,
                'token' => $user->token,
                'email' => $user->email,
                'ref_no' => $user->ref_no,
                'card_no' => $user->card_no,
                'dfrom' => $user->dfrom,
                'dto' => $user->dto,
                'organization' => $user->organization,
                'group' => $user->group->name,
                'gender' => $user->gender,
                'major' => $user->major,
                'phone' => $user->phone,
                'mobile' => $user->mobile,
                'address' => $user->address,
                'member_type' => $user->member_type,
            ];
        } else {
            $e->return_value = [];
        }
        return;
    }

    public static function getToken()
    {
        $user = L("ME");
        if (!$user->id) {
            if (PHP_SAPI != 'cli') {
                Auth::logout();
                URI::redirect('/');
            }
            if (Module::is_installed('gateway')) {
                return Gateway::getToken();
            }
            return null;
        }
        $cache = Cache::factory('redis');
        $token = self::keygen();
        $cache->set($token, $user->id, 600);
        $server = Config::get('iot_door.server');
        $auth = static::exec($server['url'] . "auth/lims" . '?' . static::build_query(['token' => $token]));
        return $auth['accessToken'];
    }
}
