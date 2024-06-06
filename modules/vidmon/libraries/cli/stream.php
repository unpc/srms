<?php

class CLI_Stream {

	static function refresh_list() {
        $config = Config::get('stream');

        if ($config['use_stream']) {
            Stream::refresh_list();
        }
    }
}
