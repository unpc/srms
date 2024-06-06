<?php
use OAuth2\Util\SecureKey;

class API_V1
{
    public static function agent_token_post($e, $params, $data, $query)
    {
        $config = Config::get('agent_token');
        if (!$data['id'] || !$data['secret'] || $config[$data['id']] != $data['secret']) {
            throw new Exception('invalid secret', 401);
            return false;
        }

        $token = self::keygen();
        $cache = Cache::factory('redis');
        $cache->set($token, $data['id'], 3600);

        $e->return_value = [
            'token' => $token,
            'expires' => Date::time() + 3600
        ];
        return false;
    }

    public static function keyValidate()
    {
        $key = preg_replace('/^Bearer /', '', $_SERVER['HTTP_AUTHORIZATION']);
        $cache = Cache::factory('redis');
        if (!$key || !$cache->get($key)) {
            return false;
        }
        return true;
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
}
