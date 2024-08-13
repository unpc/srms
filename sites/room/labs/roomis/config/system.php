<?php
$config['email_name'] = '上海市第六人民医院';

if (defined('CLI_MODE')) {
    $config['base_url']  = $config['script_url'] = 'http://106.15.79.102/';
}
$config['local_api'] = 'http://172.17.42.1/api';