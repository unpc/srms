<?php

$config['web.key'] = 'hRlrnhyGzlLIIDDKeq9kblLL37P6vugX';
$config['server.key'] = '71d8edd32cf6e3355760eb5c638c15dd';


$host = ($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://');
$config['web.url'] = $host . "api.map.baidu.com";

