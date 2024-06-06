<?php
$config['email_name'] = '天津市基理科技有限公司';

if (defined('CLI_MODE')) {
    $config['base_url']  = $config['script_url'] = 'http://lims3.17kong.com/lims/';
}
$config['local_api'] = 'http://172.17.42.1/lims/api';