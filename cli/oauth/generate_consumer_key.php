<?php
// 此脚本作 OAuth Provider 生成 Consumer key/secret 之用
// 生成后的 key/secret 既要保存在 Provider config/oauth.php 的 $config['cosumers'] 中,
// 又要保存在 Consumer config/oauth.php 的 $config['providers'] 中
// (xiaopei.li@2012-12-22)

require dirname(dirname(__FILE__)). '/base.php';

echo "key: " . sha1(UUID::v4()) . "\n";
echo "secret: " . sha1(UUID::v4()) . "\n";
