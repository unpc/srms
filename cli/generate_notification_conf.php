#!/usr/bin/env php
<?php
    /*
     * file generate_notification_conf.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-10-09
     *
     * useage php generate_notification_conf.php
     * brief 自行生成generate_notification_conf.php脚本
     */

function generateRandomString($length = 16, $md5 = FALSE) {
    if ($md5) {
        return '$1$'.substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length - 4). '$';
    }
    else {
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
    }
}

//salt需要MD5加密
$salt = generateRandomString(12, TRUE);

//rpc_token 无需加密
$rpc_token = generateRandomString(16);

$content = <<<EOF
#以下为\033[32mmessages.php\033[0m配置
<?php
//独立服务器, 特殊messages配置
\$config['server'] = array(
    'addr' => 'http://localhost:8041',
    'salt' => '%salt',
);

\$config['rpc_token'] = '%rpc_token';

============================================

#以下为\033[32mnotification.php\033[0m配置
<?php
//独立服务器, 特殊notification配置
\$config['server'] = array(
    'addr' => 'http://localhost:8041',
    'salt' => '%salt',
);

\$config['rpc_token'] = '%rpc_token';

============================================

\033[31m注意: \033[0m \033[0m该脚本只能一次复制, 不可两次执行后分别复制\033[0m

EOF;

echo strtr($content, [
    '%salt'=> $salt,
    '%rpc_token'=> $rpc_token,
]);
