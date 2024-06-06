<?php

$host = ($_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];

$config['consumers']['approval'] = [
    // 'disabled' => TRUE,
    'title' => '送样审核系统',
    'key' => '22849539-D66D-4EB1-B1F9-95271EE8ER11',
    'secret' => '07C7ECF5-7EE7-4492-BBA3-187352DEE78Y',
    'redirect_uri' => $host . "/approval/oauth/client/auth?source=approval",
    'auto_authorise' => TRUE,
];
