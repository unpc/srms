<?php

class Encrypt_Aes
{
    private static $key = '';

    // 先简单写。后期再丰富参数和逻辑吧
    public static function encrypt($rawData,$key,$iv){
        $ciphertext_raw = openssl_encrypt($rawData, 'AES-128-CBC', $key, 0, $iv);
        return $ciphertext_raw;
    }

    public static function decrypt($data,$key,$iv){
        $ciphertext_raw = openssl_decrypt($data, 'AES-128-CBC', $key, 0, $iv);
        return $ciphertext_raw;
    }
}
