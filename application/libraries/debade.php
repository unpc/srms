<?php

class Debade {

    public static function hash($str, $secret) {

        return base64_encode(hash_hmac('sha1', $str, $secret, true));

    }
}
